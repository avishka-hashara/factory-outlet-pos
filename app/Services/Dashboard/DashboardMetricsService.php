<?php

namespace App\Services\Dashboard;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\CashDrawerSession;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardMetricsService
{
    /**
     * Supported date range presets.
     */
    public const VALID_RANGES = ['today', '7d', '30d'];

    /**
     * Resolve the date boundaries for current and comparison periods.
     *
     * @return array{current_start: Carbon, current_end: Carbon, prior_start: Carbon, prior_end: Carbon, range: string, label: string}
     */
    public function resolveDateRanges(string $range = 'today'): array
    {
        $range = strtolower(trim($range));
        if (!in_array($range, self::VALID_RANGES, true)) {
            $range = 'today';
        }

        $now = Carbon::now();

        switch ($range) {
            case '7d':
                $currentStart = $now->copy()->subDays(6)->startOfDay();
                $currentEnd   = $now->copy()->endOfDay();
                $priorStart   = $currentStart->copy()->subDays(7);
                $priorEnd     = $currentStart->copy()->subSecond();
                $label        = 'vs prev 7 days';
                break;

            case '30d':
                $currentStart = $now->copy()->subDays(29)->startOfDay();
                $currentEnd   = $now->copy()->endOfDay();
                $priorStart   = $currentStart->copy()->subDays(30);
                $priorEnd     = $currentStart->copy()->subSecond();
                $label        = 'vs prev 30 days';
                break;

            case 'today':
            default:
                $currentStart = $now->copy()->startOfDay();
                $currentEnd   = $now->copy()->endOfDay();
                $priorStart   = $now->copy()->subDay()->startOfDay();
                $priorEnd     = $now->copy()->subDay()->endOfDay();
                $label        = 'vs yday';
                break;
        }

        return [
            'current_start' => $currentStart,
            'current_end'   => $currentEnd,
            'prior_start'   => $priorStart,
            'prior_end'     => $priorEnd,
            'range'         => $range,
            'comparison_label' => $label,
        ];
    }

    /**
     * Get 4-up Summary KPIs (Sales, Transactions, Avg Bill Value, Items Sold).
     */
    public function getSummaryMetrics(string $range = 'today', ?int $branchId = null): array
    {
        $dates = $this->resolveDateRanges($range);
        $currency = Setting::get('currency_symbol', 'Rs.');

        // Current period aggregates
        $currentSalesSum = (float) Sale::whereBetween('created_at', [$dates['current_start'], $dates['current_end']])->sum('total');
        $currentRefunds  = (float) ProductReturn::where('status', 'completed')
            ->whereBetween('created_at', [$dates['current_start'], $dates['current_end']])
            ->sum('total_refunded');
        $currentNetSales = max(0, $currentSalesSum - $currentRefunds);

        $currentTxCount  = (int) Sale::whereBetween('created_at', [$dates['current_start'], $dates['current_end']])->count();
        $currentAvgBill  = $currentTxCount > 0 ? ($currentNetSales / $currentTxCount) : 0;

        $currentItemsSold = (int) SaleItem::whereIn('sale_id', function ($q) use ($dates) {
            $q->select('id')->from('sales')->whereBetween('created_at', [$dates['current_start'], $dates['current_end']]);
        })->sum('qty');

        // Prior period aggregates for comparison
        $priorSalesSum = (float) Sale::whereBetween('created_at', [$dates['prior_start'], $dates['prior_end']])->sum('total');
        $priorRefunds  = (float) ProductReturn::where('status', 'completed')
            ->whereBetween('created_at', [$dates['prior_start'], $dates['prior_end']])
            ->sum('total_refunded');
        $priorNetSales = max(0, $priorSalesSum - $priorRefunds);

        $priorTxCount  = (int) Sale::whereBetween('created_at', [$dates['prior_start'], $dates['prior_end']])->count();
        $priorAvgBill  = $priorTxCount > 0 ? ($priorNetSales / $priorTxCount) : 0;

        $priorItemsSold = (int) SaleItem::whereIn('sale_id', function ($q) use ($dates) {
            $q->select('id')->from('sales')->whereBetween('created_at', [$dates['prior_start'], $dates['prior_end']]);
        })->sum('qty');

        // Delta calculations
        $salesDelta    = $this->calcPercentageDelta($currentNetSales, $priorNetSales);
        $txDelta       = $this->calcPercentageDelta($currentTxCount, $priorTxCount);
        $avgBillDelta  = $this->calcPercentageDelta($currentAvgBill, $priorAvgBill);
        $itemsSoldDelta = $this->calcPercentageDelta($currentItemsSold, $priorItemsSold);

        return [
            'range' => $dates['range'],
            'comparison_label' => $dates['comparison_label'],
            'currency' => $currency,
            'sales' => [
                'raw' => $currentNetSales,
                'formatted' => $currency . ' ' . number_format($currentNetSales, 0),
                'delta' => $salesDelta['delta'],
                'is_positive' => $salesDelta['is_positive'],
            ],
            'transactions' => [
                'raw' => $currentTxCount,
                'formatted' => number_format($currentTxCount),
                'delta' => $txDelta['delta'],
                'is_positive' => $txDelta['is_positive'],
            ],
            'avg_bill' => [
                'raw' => $currentAvgBill,
                'formatted' => $currency . ' ' . number_format($currentAvgBill, 0),
                'delta' => $avgBillDelta['delta'],
                'is_positive' => $avgBillDelta['is_positive'],
            ],
            'items_sold' => [
                'raw' => $currentItemsSold,
                'formatted' => number_format($currentItemsSold),
                'delta' => $itemsSoldDelta['delta'],
                'is_positive' => $itemsSoldDelta['is_positive'],
            ],
        ];
    }

    /**
     * Get Sales Trend Series (Current vs Comparison) bucketed appropriately.
     */
    public function getSalesTrend(string $range = 'today', ?int $branchId = null): array
    {
        $cacheKey = "dash_trend_{$range}_" . ($branchId ?? 0);

        return Cache::remember($cacheKey, 60, function () use ($range) {
            $dates = $this->resolveDateRanges($range);
            $currentSeries = [];
            $comparisonSeries = [];

            if ($range === 'today') {
                $todayDate = $dates['current_start']->toDateString();
                $ydayDate  = $dates['prior_start']->toDateString();

                // Fetch sales for today and yesterday
                $todaySales = Sale::whereDate('created_at', $todayDate)->get(['created_at', 'total']);
                $ydaySales  = Sale::whereDate('created_at', $ydayDate)->get(['created_at', 'total']);

                // Build 30-minute intervals across 08:00 to 20:00 (25 slots)
                for ($h = 8; $h <= 20; $h++) {
                    for ($m = 0; $m < 60; $m += 30) {
                        if ($h === 20 && $m > 0) {
                            break; // End at exactly 20:00
                        }

                        $slotLabel = sprintf('%02d:%02d', $h, $m);
                        $slotStart = $h * 60 + $m;
                        $slotEnd   = $slotStart + 30;

                        $tVal = $todaySales->filter(function ($s) use ($slotStart, $slotEnd) {
                            if (!$s->created_at) return false;
                            $timeMin = $s->created_at->hour * 60 + $s->created_at->minute;
                            return $timeMin >= $slotStart && $timeMin < $slotEnd;
                        })->sum('total');

                        $yVal = $ydaySales->filter(function ($s) use ($slotStart, $slotEnd) {
                            if (!$s->created_at) return false;
                            $timeMin = $s->created_at->hour * 60 + $s->created_at->minute;
                            return $timeMin >= $slotStart && $timeMin < $slotEnd;
                        })->sum('total');

                        $currentSeries[] = [
                            'label' => $slotLabel,
                            'value' => round((float) $tVal, 2),
                        ];
                        $comparisonSeries[] = [
                            'label' => $slotLabel,
                            'value' => round((float) $yVal, 2),
                        ];
                    }
                }
            } else {
                // Bucketed by day
                $days = $range === '7d' ? 7 : 30;
                for ($i = $days - 1; $i >= 0; $i--) {
                    $cDate = Carbon::now()->subDays($i)->toDateString();
                    $pDate = Carbon::now()->subDays($i + $days)->toDateString();
                    $label = Carbon::now()->subDays($i)->format($days <= 7 ? 'D' : 'M d');

                    $cVal = (float) Sale::whereDate('created_at', $cDate)->sum('total');
                    $pVal = (float) Sale::whereDate('created_at', $pDate)->sum('total');

                    $currentSeries[] = ['label' => $label, 'value' => round($cVal, 2)];
                    $comparisonSeries[] = ['label' => $label, 'value' => round($pVal, 2)];
                }
            }

            return [
                'current' => $currentSeries,
                'comparison' => $comparisonSeries,
                'current_label' => $range === 'today' ? 'Today' : 'Current Period',
                'comparison_label' => $range === 'today' ? 'Yesterday' : 'Prior Period',
            ];
        });
    }

    /**
     * Get Payment Mix breakdown.
     */
    public function getPaymentMix(string $range = 'today', ?int $branchId = null): array
    {
        $cacheKey = "dash_paymix_{$range}_" . ($branchId ?? 0);

        return Cache::remember($cacheKey, 60, function () use ($range) {
            $dates = $this->resolveDateRanges($range);
            $currency = Setting::get('currency_symbol', 'Rs.');

            $sales = Sale::whereBetween('created_at', [$dates['current_start'], $dates['current_end']])
                ->selectRaw('payment_method, SUM(total) as total, COUNT(*) as count')
                ->groupBy('payment_method')
                ->get();

            $totalSum = (float) $sales->sum('total');

            $methodMeta = [
                'cash'     => ['label' => 'Cash', 'color' => '#524534', 'order' => 3],
                'card'     => ['label' => 'Card', 'color' => '#dae3f3', 'order' => 2],
                'koko'     => ['label' => 'Koko', 'color' => '#F5A623', 'order' => 1],
                'mintpay'  => ['label' => 'Mint Pay', 'color' => '#ffb955', 'order' => 4],
                'bank'     => ['label' => 'Bank', 'color' => '#222b36', 'order' => 5],
                'payzy'    => ['label' => 'Payzy', 'color' => '#22C55E', 'order' => 6],
                'multipay' => ['label' => 'Split', 'color' => '#c4c6ce', 'order' => 7],
            ];

            $items = [];
            foreach ($sales as $s) {
                $m = strtolower($s->payment_method ?? 'cash');
                $meta = $methodMeta[$m] ?? ['label' => ucfirst($m), 'color' => '#c4c6ce', 'order' => 99];
                $amt = (float) $s->total;
                $pct = $totalSum > 0 ? round(($amt / $totalSum) * 100, 1) : 0;

                $items[] = [
                    'key' => $m,
                    'label' => $meta['label'],
                    'color' => $meta['color'],
                    'order' => $meta['order'],
                    'amount' => $amt,
                    'formatted_amount' => $this->formatCompactCurrency($amt, $currency),
                    'percentage' => $pct,
                    'count' => (int) $s->count,
                ];
            }

            // If empty, supply default empty distribution
            if (empty($items)) {
                $items = [
                    ['key' => 'cash', 'label' => 'Cash', 'color' => '#524534', 'order' => 3, 'amount' => 0, 'formatted_amount' => $currency . '0', 'percentage' => 0, 'count' => 0],
                    ['key' => 'card', 'label' => 'Card', 'color' => '#dae3f3', 'order' => 2, 'amount' => 0, 'formatted_amount' => $currency . '0', 'percentage' => 0, 'count' => 0],
                    ['key' => 'koko', 'label' => 'Koko', 'color' => '#F5A623', 'order' => 1, 'amount' => 0, 'formatted_amount' => $currency . '0', 'percentage' => 0, 'count' => 0],
                ];
            }

            usort($items, fn($a, $b) => $b['amount'] <=> $a['amount']);

            $dominant = $items[0] ?? ['label' => 'None', 'percentage' => 0, 'amount' => 0];

            return [
                'total_amount' => $totalSum,
                'formatted_total' => $currency . ' ' . number_format($totalSum, 0),
                'dominant_method' => $dominant['label'],
                'dominant_percentage' => $dominant['percentage'],
                'methods' => $items,
            ];
        });
    }

    /**
     * Get Recent Transactions (Latest 10 sales).
     */
    public function getRecentTransactions(int $limit = 10, ?int $branchId = null): array
    {
        $sales = Sale::with(['customer', 'user'])
            ->withCount('items')
            ->latest('id')
            ->limit($limit)
            ->get();

        $currency = Setting::get('currency_symbol', 'Rs.');

        return $sales->map(function (Sale $sale) use ($currency) {
            $customerName = $sale->customer?->name ? trim($sale->customer->name) : 'Walk-in';
            $initials = $this->extractInitials($customerName);
            $invoiceNum = '#' . ($sale->invoice_number ?? str_pad($sale->id, 4, '0', STR_PAD_LEFT));

            return [
                'id' => $sale->id,
                'bill_number' => $invoiceNum,
                'time' => $sale->created_at ? $sale->created_at->format('H:i') : '--:--',
                'customer_name' => $customerName,
                'customer_initials' => $initials,
                'items_count' => (int) $sale->items_count,
                'items_label' => ($sale->items_count ?? 0) . ' ' . (($sale->items_count == 1) ? 'item' : 'items'),
                'status' => $sale->has_return ? 'Returned' : 'Paid',
                'status_color' => $sale->has_return ? 'amber' : 'green',
                'total_raw' => (float) $sale->total,
                'total_formatted' => $currency . ' ' . number_format($sale->total, 0),
                'invoice_url' => route('invoice.show', ['id' => $sale->id]),
            ];
        })->toArray();
    }

    /**
     * Get Stock Alerts (critical low stock & out of stock).
     */
    public function getStockAlerts(int $limit = 8, ?int $branchId = null): array
    {
        $cacheKey = "dash_stock_alerts_" . ($branchId ?? 0);

        return Cache::remember($cacheKey, 60, function () use ($limit) {
            $criticalThreshold = 5;

            $totalCritical = Product::where('stock', '<=', $criticalThreshold)->count();

            $products = Product::where('stock', '<=', $criticalThreshold)
                ->orderBy('stock', 'asc')
                ->limit($limit)
                ->get();

            $items = $products->map(function (Product $p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku ?? 'SKU-N/A',
                    'stock' => (int) $p->stock,
                    'is_out_of_stock' => $p->stock <= 0,
                    'image_url' => $p->image_url ?? null,
                ];
            })->toArray();

            return [
                'total_critical' => $totalCritical,
                'critical_label' => $totalCritical . ' Critical',
                'items' => $items,
            ];
        });
    }

    /**
     * Get Drawer Status (Current open shift or latest session).
     */
    public function getDrawerStatus(?int $branchId = null): array
    {
        $currency = Setting::get('currency_symbol', 'Rs.');
        $session = CashDrawerSession::currentOpen();

        if (!$session) {
            // Check latest closed session for display
            $latest = CashDrawerSession::latest('id')->first();
            return [
                'is_open' => false,
                'status_label' => 'Closed',
                'terminal_label' => 'T01 - Main Branch',
                'cashier_name' => $latest?->openedBy?->name ?? 'None',
                'opening_float' => (float) ($latest?->opening_balance ?? 0),
                'cash_in' => 0.0,
                'cash_out' => 0.0,
                'expected_cash' => 0.0,
                'formatted_expected_cash' => $currency . ' 0',
                'open_route' => route('cash-drawer.index'),
            ];
        }

        $expected = (float) $session->expected_balance;

        return [
            'is_open' => true,
            'status_label' => 'Open',
            'session_id' => $session->id,
            'terminal_label' => 'T01 - Main Branch',
            'cashier_name' => $session->openedBy?->name ?? 'Admin',
            'opening_float' => (float) $session->opening_balance,
            'cash_in' => (float) $session->total_cash_in,
            'cash_out' => (float) $session->total_cash_out,
            'expected_cash' => $expected,
            'formatted_expected_cash' => $currency . ' ' . number_format($expected, 0),
            'drawer_route' => route('cash-drawer.index'),
        ];
    }

    /**
     * Consolidate all widgets into one payload for initial server render & API response.
     */
    public function getAllMetrics(string $range = 'today', ?int $branchId = null): array
    {
        return [
            'range' => $range,
            'summary' => $this->getSummaryMetrics($range, $branchId),
            'trend' => $this->getSalesTrend($range, $branchId),
            'payment_mix' => $this->getPaymentMix($range, $branchId),
            'recent_transactions' => $this->getRecentTransactions(10, $branchId),
            'stock_alerts' => $this->getStockAlerts(8, $branchId),
            'drawer_status' => $this->getDrawerStatus($branchId),
        ];
    }

    /**
     * Percentage delta calculator.
     */
    private function calcPercentageDelta(float $current, float $prior): array
    {
        if ($prior <= 0) {
            return [
                'delta' => $current > 0 ? '100%' : '0%',
                'is_positive' => $current >= 0,
            ];
        }

        $pct = (($current - $prior) / $prior) * 100;
        return [
            'delta' => abs(round($pct, 1)) . '%',
            'is_positive' => $pct >= 0,
        ];
    }

    /**
     * Compact currency formatter (e.g. 56K / 1.2M).
     */
    private function formatCompactCurrency(float $amount, string $currency): string
    {
        if ($amount >= 1000000) {
            return $currency . round($amount / 1000000, 1) . 'M';
        }
        if ($amount >= 1000) {
            return $currency . round($amount / 1000, 0) . 'K';
        }
        return $currency . number_format($amount, 0);
    }

    /**
     * Extract 2 initials from a string.
     */
    private function extractInitials(string $name): string
    {
        $words = explode(' ', trim($name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}
