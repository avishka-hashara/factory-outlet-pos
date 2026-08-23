<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ReturnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'daily');
        $date = $request->get('date', Carbon::today()->toDateString());
        $month = $request->get('month', Carbon::today()->format('Y-m'));
        $dateFrom = $request->get('date_from', Carbon::today()->subDays(30)->toDateString());
        $dateTo = $request->get('date_to', Carbon::today()->toDateString());

        $carbonDate = Carbon::parse($date);
        $carbonMonth = Carbon::parse($month . '-01');
        $carbonFrom = Carbon::parse($dateFrom);
        $carbonTo = Carbon::parse($dateTo);

        // =====================
        // 1. DAILY REPORT DATA
        // =====================
        $dayStart = $carbonDate->copy()->startOfDay();
        $dayEnd = $carbonDate->copy()->endOfDay();
        $dailySalesQuery = Sale::whereBetween('created_at', [$dayStart, $dayEnd]);
        $totalSales = $dailySalesQuery->count();
        $revenue = $this->netRevenueBetween($dayStart, $dayEnd);
        $refundStats = $this->refundStatsBetween($dayStart, $dayEnd);
        $totalRefunds = $refundStats['count'];
        $refundValue = $refundStats['value'];
        $itemsSold = $this->netItemsSoldBetween($dayStart, $dayEnd);
        $avgSale = $totalSales > 0 ? $revenue / $totalSales : 0;

        // Daily comparison vs yesterday
        $yesterdayStart = $carbonDate->copy()->subDay()->startOfDay();
        $yesterdayEnd = $carbonDate->copy()->subDay()->endOfDay();
        $prevTotalSales = Sale::whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])->count();
        $prevRevenue = $this->netRevenueBetween($yesterdayStart, $yesterdayEnd);
        $prevItemsSold = $this->netItemsSoldBetween($yesterdayStart, $yesterdayEnd);
        $prevRefundStats = $this->refundStatsBetween($yesterdayStart, $yesterdayEnd);
        $prevRefundValue = $prevRefundStats['value'];
        $prevTotalRefunds = $prevRefundStats['count'];
        $prevAvgSale = $prevTotalSales > 0 ? $prevRevenue / $prevTotalSales : 0;

        $dailyDeltas = [
            'sales' => $prevTotalSales > 0 ? round((($totalSales - $prevTotalSales) / $prevTotalSales) * 100, 1) : null,
            'revenue' => $prevRevenue > 0 ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1) : null,
            'items' => $prevItemsSold > 0 ? round((($itemsSold - $prevItemsSold) / $prevItemsSold) * 100, 1) : null,
            'refunds' => $prevTotalRefunds > 0 ? round((($totalRefunds - $prevTotalRefunds) / $prevTotalRefunds) * 100, 1) : null,
            'refund_val' => $prevRefundValue > 0 ? round((($refundValue - $prevRefundValue) / $prevRefundValue) * 100, 1) : null,
            'avg_sale' => $prevAvgSale > 0 ? round((($avgSale - $prevAvgSale) / $prevAvgSale) * 100, 1) : null,
        ];

        // Payment Breakdown (daily, net of refunds)
        $payments = $this->netPaymentsBetween($dayStart, $dayEnd);

        // Hourly Trends (daily, net of refunds + previous day overlay)
        $hourExpression = $this->hourExpression('created_at');
        $hourlyData = Sale::whereDate('created_at', $carbonDate)
            ->select(DB::raw($hourExpression . ' as hour'), DB::raw('sum(total) as hourly_total'), DB::raw('count(*) as count'), DB::raw('sum((select sum(qty) from sale_items where sale_id = sales.id)) as items_count'))
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');
        
        $prevHourlyData = Sale::whereDate('created_at', $carbonDate->copy()->subDay())
            ->select(DB::raw($hourExpression . ' as hour'), DB::raw('sum(total) as hourly_total'))
            ->groupBy('hour')
            ->pluck('hourly_total', 'hour')
            ->toArray();

        $hourlyRefunds = $this->completedRefundsBetween($dayStart, $dayEnd)
            ->select(DB::raw($hourExpression . ' as hour'), DB::raw('sum(total_refunded) as hourly_total'))
            ->groupBy('hour')
            ->pluck('hourly_total', 'hour')
            ->toArray();

        $hourlySales = [];
        $prevHourlySales = [];
        $hourlyTransactions = [];
        $hourlyItems = [];
        for ($i = 8; $i <= 22; $i++) {
            $hourNet = ($hourlyData[$i]->hourly_total ?? 0) - ($hourlyRefunds[$i] ?? 0);
            $hourlySales[$i] = max(0, $hourNet);
            $prevHourlySales[$i] = max(0, (float) ($prevHourlyData[$i] ?? 0));
            $hourlyTransactions[$i] = (int) ($hourlyData[$i]->count ?? 0);
            $hourlyItems[$i] = (int) ($hourlyData[$i]->items_count ?? 0);
        }

        // Top Products (daily, net of returns)
        $topProducts = $this->netTopProductsBetween($dayStart, $dayEnd, 10);
        $dailyStaffSales = $this->staffSalesBetween($dayStart, $dayEnd);
        $dailyCategorySales = $this->categorySalesBetween($dayStart, $dayEnd);

        // ========================
        // 2. MONTHLY REPORT DATA
        // ========================
        $monthStart = $carbonMonth->copy()->startOfMonth();
        $monthEnd = $carbonMonth->copy()->endOfMonth();

        $monthlySalesQuery = Sale::whereBetween('created_at', [$monthStart, $monthEnd]);
        $monthlyTotalSales = $monthlySalesQuery->count();
        $monthlyRevenue = $this->netRevenueBetween($monthStart, $monthEnd);
        $monthlyItemsSold = $this->netItemsSoldBetween($monthStart, $monthEnd);
        $monthlyRefundStats = $this->refundStatsBetween($monthStart, $monthEnd);
        $monthlyTotalRefunds = $monthlyRefundStats['count'];
        $monthlyRefundValue = $monthlyRefundStats['value'];
        $monthlyAvgSale = $monthlyTotalSales > 0 ? $monthlyRevenue / $monthlyTotalSales : 0;

        $daysInMonth = $carbonMonth->daysInMonth;
        $daysPassed = $carbonMonth->isCurrentMonth() ? Carbon::today()->day : $daysInMonth;
        $monthlyAvgDaily = $daysPassed > 0 ? $monthlyRevenue / $daysPassed : 0;

        // Monthly comparison vs previous month
        $prevMonthStart = $carbonMonth->copy()->subMonth()->startOfMonth();
        $prevMonthEnd = $carbonMonth->copy()->subMonth()->endOfMonth();
        $prevMonthlySales = Sale::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count();
        $prevMonthlyRevenue = $this->netRevenueBetween($prevMonthStart, $prevMonthEnd);
        $prevMonthlyItemsSold = $this->netItemsSoldBetween($prevMonthStart, $prevMonthEnd);
        $prevMonthlyRefundStats = $this->refundStatsBetween($prevMonthStart, $prevMonthEnd);
        $prevMonthlyRefundValue = $prevMonthlyRefundStats['value'];
        $prevMonthlyTotalRefunds = $prevMonthlyRefundStats['count'];
        $prevMonthlyAvgSale = $prevMonthlySales > 0 ? $prevMonthlyRevenue / $prevMonthlySales : 0;

        $monthlyDeltas = [
            'sales' => $prevMonthlySales > 0 ? round((($monthlyTotalSales - $prevMonthlySales) / $prevMonthlySales) * 100, 1) : null,
            'revenue' => $prevMonthlyRevenue > 0 ? round((($monthlyRevenue - $prevMonthlyRevenue) / $prevMonthlyRevenue) * 100, 1) : null,
            'items' => $prevMonthlyItemsSold > 0 ? round((($monthlyItemsSold - $prevMonthlyItemsSold) / $prevMonthlyItemsSold) * 100, 1) : null,
            'refunds' => $prevMonthlyTotalRefunds > 0 ? round((($monthlyTotalRefunds - $prevMonthlyTotalRefunds) / $prevMonthlyTotalRefunds) * 100, 1) : null,
            'refund_val' => $prevMonthlyRefundValue > 0 ? round((($monthlyRefundValue - $prevMonthlyRefundValue) / $prevMonthlyRefundValue) * 100, 1) : null,
            'avg_sale' => $prevMonthlyAvgSale > 0 ? round((($monthlyAvgSale - $prevMonthlyAvgSale) / $prevMonthlyAvgSale) * 100, 1) : null,
        ];

        // Monthly Payment Breakdown (net of refunds)
        $monthlyPayments = $this->netPaymentsBetween($monthStart, $monthEnd);

        // Daily sales within the month (for chart, net of refunds)
        $dailyInMonth = $this->netDailyTotalsBetween($monthStart, $monthEnd);

        $monthlyChartLabels = [];
        $monthlyChartData = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dayDate = $carbonMonth->copy()->startOfMonth()->addDays($d - 1)->toDateString();
            $monthlyChartLabels[] = $carbonMonth->copy()->startOfMonth()->addDays($d - 1)->format('M d');
            $monthlyChartData[] = $dailyInMonth[$dayDate] ?? 0;
        }

        // Monthly Top Products & Staff
        $monthlyTopProducts = $this->netTopProductsBetween($monthStart, $monthEnd, 10);
        $monthlyStaffSales = $this->staffSalesBetween($monthStart, $monthEnd);
        $monthlyCategorySales = $this->categorySalesBetween($monthStart, $monthEnd);

        // Available months for dropdown
        $availableMonths = Sale::selectRaw($this->yearMonthExpression('created_at') . ' as ym')
            ->groupBy('ym')
            ->orderByDesc('ym')
            ->pluck('ym')
            ->toArray();
        if (empty($availableMonths)) {
            $availableMonths = [Carbon::today()->format('Y-m')];
        }

        // =============================
        // 3. CUSTOM RANGE REPORT DATA
        // =============================
        $customSalesQuery = Sale::whereDate('created_at', '>=', $carbonFrom)
            ->whereDate('created_at', '<=', $carbonTo);
        $customTotalSales = $customSalesQuery->count();
        $customStart = $carbonFrom->copy()->startOfDay();
        $customEnd = $carbonTo->copy()->endOfDay();
        $customRevenue = $this->netRevenueBetween($customStart, $customEnd);
        $customItemsSold = $this->netItemsSoldBetween($customStart, $customEnd);
        $customRefundStats = $this->refundStatsBetween($customStart, $customEnd);
        $customTotalRefunds = $customRefundStats['count'];
        $customRefundValue = $customRefundStats['value'];
        $customAvgSale = $customTotalSales > 0 ? $customRevenue / $customTotalSales : 0;

        $customDays = $carbonFrom->diffInDays($carbonTo) + 1;
        $customAvgDaily = $customDays > 0 ? $customRevenue / $customDays : 0;

        // Custom comparison vs previous equivalent range
        $prevCustomStart = $carbonFrom->copy()->subDays($customDays)->startOfDay();
        $prevCustomEnd = $carbonTo->copy()->subDays($customDays)->endOfDay();
        $prevCustomSales = Sale::whereBetween('created_at', [$prevCustomStart, $prevCustomEnd])->count();
        $prevCustomRevenue = $this->netRevenueBetween($prevCustomStart, $prevCustomEnd);
        $prevCustomItemsSold = $this->netItemsSoldBetween($prevCustomStart, $prevCustomEnd);
        $prevCustomRefundStats = $this->refundStatsBetween($prevCustomStart, $prevCustomEnd);
        $prevCustomRefundValue = $prevCustomRefundStats['value'];
        $prevCustomTotalRefunds = $prevCustomRefundStats['count'];
        $prevCustomAvgSale = $prevCustomSales > 0 ? $prevCustomRevenue / $prevCustomSales : 0;

        $customDeltas = [
            'sales' => $prevCustomSales > 0 ? round((($customTotalSales - $prevCustomSales) / $prevCustomSales) * 100, 1) : null,
            'revenue' => $prevCustomRevenue > 0 ? round((($customRevenue - $prevCustomRevenue) / $prevCustomRevenue) * 100, 1) : null,
            'items' => $prevCustomItemsSold > 0 ? round((($customItemsSold - $prevCustomItemsSold) / $prevCustomItemsSold) * 100, 1) : null,
            'refunds' => $prevCustomTotalRefunds > 0 ? round((($customTotalRefunds - $prevCustomTotalRefunds) / $prevCustomTotalRefunds) * 100, 1) : null,
            'refund_val' => $prevCustomRefundValue > 0 ? round((($customRefundValue - $prevCustomRefundValue) / $prevCustomRefundValue) * 100, 1) : null,
            'avg_sale' => $prevCustomAvgSale > 0 ? round((($customAvgSale - $prevCustomAvgSale) / $prevCustomAvgSale) * 100, 1) : null,
        ];

        // Custom Payment Breakdown (net of refunds)
        $customPayments = $this->netPaymentsBetween($customStart, $customEnd);

        // Daily sales within custom range (for chart, net of refunds)
        $dailyInRange = $this->netDailyTotalsBetween($customStart, $customEnd);

        $customChartLabels = [];
        $customChartData = [];
        $cursor = $carbonFrom->copy();
        while ($cursor->lte($carbonTo)) {
            $customChartLabels[] = $cursor->format('M d');
            $customChartData[] = $dailyInRange[$cursor->toDateString()] ?? 0;
            $cursor->addDay();
        }

        // Custom Top Products, Staff & Category
        $customTopProducts = $this->netTopProductsBetween($customStart, $customEnd, 10);
        $customStaffSales = $this->staffSalesBetween($customStart, $customEnd);
        $customCategorySales = $this->categorySalesBetween($customStart, $customEnd);

        // ========================
        // 4. STOCK REPORT DATA
        // ========================
        $stockData = $this->buildStockReport();
        $stockProducts       = $stockData['products'];
        $stockTotalValue     = $stockData['totalValue'];
        $stockAllTimeRevenue = $stockData['allTimeRevenue'];
        $stockOutCount       = $stockData['outOfStockCount'];
        $stockLowCount       = $stockData['lowStockCount'];
        $stockProductCount   = $stockData['productCount'];

        return view('reports.index', compact(
            'tab', 'date', 'month', 'dateFrom', 'dateTo',
            // Daily
            'totalSales', 'revenue', 'itemsSold', 'avgSale', 'totalRefunds', 'refundValue',
            'dailyDeltas', 'payments', 'hourlySales', 'prevHourlySales', 'hourlyTransactions', 'hourlyItems',
            'topProducts', 'dailyStaffSales', 'dailyCategorySales',
            // Monthly
            'monthlyTotalSales', 'monthlyRevenue', 'monthlyItemsSold', 'monthlyAvgDaily', 'monthlyAvgSale',
            'monthlyTotalRefunds', 'monthlyRefundValue', 'monthlyDeltas',
            'monthlyPayments', 'monthlyChartLabels', 'monthlyChartData', 'monthlyTopProducts',
            'monthlyStaffSales', 'monthlyCategorySales', 'availableMonths',
            // Custom
            'customTotalSales', 'customRevenue', 'customItemsSold', 'customAvgDaily', 'customAvgSale',
            'customTotalRefunds', 'customRefundValue', 'customDeltas',
            'customPayments', 'customChartLabels', 'customChartData', 'customTopProducts',
            'customStaffSales', 'customCategorySales',
            // Stock
            'stockProducts', 'stockTotalValue', 'stockAllTimeRevenue',
            'stockOutCount', 'stockLowCount', 'stockProductCount'
        ));
    }

    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(120);

        try {
            $tab      = $request->get('tab', 'daily');
            $date     = $request->get('date',      Carbon::today()->toDateString());
            $month    = $request->get('month',     Carbon::today()->format('Y-m'));
            $dateFrom = $request->get('date_from', Carbon::today()->subDays(30)->toDateString());
            $dateTo   = $request->get('date_to',   Carbon::today()->toDateString());

            // Defaults so compact() never has undefined variables
            $totalSales = $revenue = $itemsSold = $avgSale = $totalRefunds = $refundValue = 0;
            $payments   = ['cash' => 0, 'koko' => 0, 'mintpay' => 0, 'bank' => 0, 'card' => 0];
            $topProducts = collect();
            $monthlyTotalSales = $monthlyRevenue = $monthlyItemsSold = $monthlyAvgDaily = $monthlyTotalRefunds = $monthlyRefundValue = 0;
            $monthlyPayments = ['cash' => 0, 'koko' => 0, 'mintpay' => 0, 'bank' => 0, 'card' => 0];
            $monthlyTopProducts = collect();
            $customTotalSales = $customRevenue = $customItemsSold = $customAvgDaily = $customTotalRefunds = $customRefundValue = 0;
            $customPayments = ['cash' => 0, 'koko' => 0, 'mintpay' => 0, 'bank' => 0, 'card' => 0];
            $customTopProducts = collect();
            $stockProducts = collect();
            $stockTotalValue = $stockAllTimeRevenue = 0;
            $stockOutCount = $stockLowCount = $stockProductCount = 0;

            if ($tab === 'daily') {
                $carbonDate  = Carbon::parse($date);
                $dayStart    = $carbonDate->copy()->startOfDay();
                $dayEnd      = $carbonDate->copy()->endOfDay();
                $totalSales  = Sale::whereBetween('created_at', [$dayStart, $dayEnd])->count();
                $revenue     = $this->netRevenueBetween($dayStart, $dayEnd);
                $itemsSold   = $this->netItemsSoldBetween($dayStart, $dayEnd);
                $refundStats = $this->refundStatsBetween($dayStart, $dayEnd);
                $totalRefunds = $refundStats['count'];
                $refundValue = $refundStats['value'];
                $avgSale     = $totalSales > 0 ? $revenue / $totalSales : 0;
                $payments    = $this->netPaymentsBetween($dayStart, $dayEnd);
                $topProducts = $this->netTopProductsBetween($dayStart, $dayEnd);

            } elseif ($tab === 'monthly') {
                $carbonMonth       = Carbon::parse($month . '-01');
                $monthStart        = $carbonMonth->copy()->startOfMonth();
                $monthEnd          = $carbonMonth->copy()->endOfMonth();
                $monthlyTotalSales = Sale::whereBetween('created_at', [$monthStart, $monthEnd])->count();
                $monthlyRevenue    = $this->netRevenueBetween($monthStart, $monthEnd);
                $monthlyItemsSold  = $this->netItemsSoldBetween($monthStart, $monthEnd);
                $monthlyRefundStats = $this->refundStatsBetween($monthStart, $monthEnd);
                $monthlyTotalRefunds = $monthlyRefundStats['count'];
                $monthlyRefundValue = $monthlyRefundStats['value'];
                $daysPassed        = $carbonMonth->isCurrentMonth() ? Carbon::today()->day : $carbonMonth->daysInMonth;
                $monthlyAvgDaily   = $daysPassed > 0 ? $monthlyRevenue / $daysPassed : 0;
                $monthlyPayments   = $this->netPaymentsBetween($monthStart, $monthEnd);
                $monthlyTopProducts = $this->netTopProductsBetween($monthStart, $monthEnd);

            } elseif ($tab === 'custom') {
                $carbonFrom        = Carbon::parse($dateFrom);
                $carbonTo          = Carbon::parse($dateTo);
                $customStart       = $carbonFrom->copy()->startOfDay();
                $customEnd         = $carbonTo->copy()->endOfDay();
                $customTotalSales  = Sale::whereBetween('created_at', [$customStart, $customEnd])->count();
                $customRevenue     = $this->netRevenueBetween($customStart, $customEnd);
                $customItemsSold   = $this->netItemsSoldBetween($customStart, $customEnd);
                $customRefundStats = $this->refundStatsBetween($customStart, $customEnd);
                $customTotalRefunds = $customRefundStats['count'];
                $customRefundValue = $customRefundStats['value'];
                $customDays        = $carbonFrom->diffInDays($carbonTo) + 1;
                $customAvgDaily    = $customDays > 0 ? $customRevenue / $customDays : 0;
                $customPayments    = $this->netPaymentsBetween($customStart, $customEnd);
                $customTopProducts = $this->netTopProductsBetween($customStart, $customEnd);

            } elseif ($tab === 'stock') {
                $sd                  = $this->buildStockReport();
                $stockProducts       = $sd['products'];
                $stockTotalValue     = $sd['totalValue'];
                $stockAllTimeRevenue = $sd['allTimeRevenue'];
                $stockOutCount       = $sd['outOfStockCount'];
                $stockLowCount       = $sd['lowStockCount'];
                $stockProductCount   = $sd['productCount'];
            }

            $html = view('reports.pdf', compact(
                'tab', 'date', 'month', 'dateFrom', 'dateTo',
                'totalSales', 'revenue', 'itemsSold', 'avgSale', 'totalRefunds', 'refundValue', 'payments', 'topProducts',
                'monthlyTotalSales', 'monthlyRevenue', 'monthlyItemsSold', 'monthlyAvgDaily', 'monthlyTotalRefunds', 'monthlyRefundValue', 'monthlyPayments', 'monthlyTopProducts',
                'customTotalSales', 'customRevenue', 'customItemsSold', 'customAvgDaily', 'customTotalRefunds', 'customRefundValue', 'customPayments', 'customTopProducts',
                'stockProducts', 'stockTotalValue', 'stockAllTimeRevenue', 'stockOutCount', 'stockLowCount', 'stockProductCount'
            ))->render();

            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            $options->set('isPhpEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', $tab === 'stock' ? 'landscape' : 'portrait');
            $dompdf->render();

            $filename = 'report-' . $tab . '-' . date('Y-m-d') . '.pdf';
            return response()->make($dompdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Throwable $e) {
            \Log::error('PDF Export Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->route('reports.index')
                ->with('pdf_error', 'PDF export failed: ' . $e->getMessage());
        }
    }

    // =====================================================================
    // STOCK REPORT BUILDER (shared by index, exportPdf, exportExcel)
    // =====================================================================
    private function buildStockReport(): array
    {
        // All-time revenue per product — join sales for the date (sale_items has no timestamps)
        $revMap = SaleItem::select(
                'sale_items.product_id',
                DB::raw('SUM(sale_items.qty) as total_sold'),
                DB::raw('SUM(sale_items.price * sale_items.qty) as total_revenue'),
                DB::raw('MAX(sales.created_at) as last_sold_at')
            )
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->groupBy('sale_items.product_id')
            ->get()
            ->keyBy('product_id');

        $returnMap = ReturnItem::select(
                'return_items.product_id',
                DB::raw('SUM(return_items.qty) as total_returned'),
                DB::raw('SUM(return_items.price * return_items.qty) as total_refunded')
            )
            ->join('returns', 'returns.id', '=', 'return_items.return_id')
            ->where('returns.status', 'completed')
            ->groupBy('return_items.product_id')
            ->get()
            ->keyBy('product_id');

        $products = Product::orderBy('category')->orderBy('name')->get()
            ->map(function ($product) use ($revMap, $returnMap) {
                $rev = $revMap->get($product->id);
                $returned = $returnMap->get($product->id);
                $product->total_sold    = max(0, ($rev ? (int) $rev->total_sold : 0) - ($returned ? (int) $returned->total_returned : 0));
                $product->total_revenue = ($rev ? (float) $rev->total_revenue : 0.0) - ($returned ? (float) $returned->total_refunded : 0.0);
                $product->last_sold_at  = $rev ? $rev->last_sold_at         : null;
                $product->stock_value   = (float) ($product->stock * $product->price);
                return $product;
            })
            ->sortByDesc('total_revenue')
            ->values();

        $allTimeRevenue  = $products->sum('total_revenue');
        $totalStockValue = $products->sum('stock_value');

        return [
            'products'       => $products,
            'totalValue'     => $totalStockValue,
            'allTimeRevenue' => $allTimeRevenue,
            'outOfStockCount'=> $products->where('stock', '<=', 0)->count(),
            'lowStockCount'  => $products->where('stock', '>', 0)->where('stock', '<=', 5)->count(),
            'productCount'   => $products->count(),
        ];
    }

    private function completedRefundsBetween(Carbon $start, Carbon $end)
    {
        return ProductReturn::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end]);
    }

    private function hourExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%H', {$column}) AS INTEGER)"
            : "HOUR({$column})";
    }

    private function yearMonthExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    private function netRevenueBetween(Carbon $start, Carbon $end): float
    {
        $gross = (float) Sale::whereBetween('created_at', [$start, $end])->sum('total');
        $refunds = (float) $this->completedRefundsBetween($start, $end)->sum('total_refunded');

        return $gross - $refunds;
    }

    private function refundStatsBetween(Carbon $start, Carbon $end): array
    {
        $refunds = $this->completedRefundsBetween($start, $end);

        return [
            'count' => (int) (clone $refunds)->count(),
            'value' => (float) $refunds->sum('total_refunded'),
        ];
    }

    private function netItemsSoldBetween(Carbon $start, Carbon $end): int
    {
        $sold = (int) SaleItem::whereHas('sale', fn($q) => $q->whereBetween('created_at', [$start, $end]))->sum('qty');
        $returned = (int) ReturnItem::whereHas('productReturn', fn($q) => $q->where('status', 'completed')->whereBetween('created_at', [$start, $end]))->sum('qty');

        return max(0, $sold - $returned);
    }

    private function netPaymentsBetween(Carbon $start, Carbon $end): array
    {
        $payments = ['cash' => 0, 'koko' => 0, 'mintpay' => 0, 'bank' => 0, 'card' => 0, 'payzy' => 0, 'multipay' => 0, 'store_credit' => 0];

        Sale::whereBetween('created_at', [$start, $end])
            ->select('payment_method', DB::raw('sum(total) as total'))
            ->groupBy('payment_method')
            ->get()
            ->each(function ($stat) use (&$payments) {
                $payments[$stat->payment_method] = ($payments[$stat->payment_method] ?? 0) + (float) $stat->total;
            });

        $this->completedRefundsBetween($start, $end)
            ->select('refund_method', DB::raw('sum(total_refunded) as total'))
            ->groupBy('refund_method')
            ->get()
            ->each(function ($stat) use (&$payments) {
                $payments[$stat->refund_method] = ($payments[$stat->refund_method] ?? 0) - (float) $stat->total;
            });

        return $payments;
    }

    private function netDailyTotalsBetween(Carbon $start, Carbon $end): array
    {
        $daily = [];

        Sale::whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as activity_date'), DB::raw('sum(total) as total'))
            ->groupBy('activity_date')
            ->get()
            ->each(function ($row) use (&$daily) {
                $daily[$row->activity_date] = ($daily[$row->activity_date] ?? 0) + (float) $row->total;
            });

        $this->completedRefundsBetween($start, $end)
            ->select(DB::raw('DATE(created_at) as activity_date'), DB::raw('sum(total_refunded) as total'))
            ->groupBy('activity_date')
            ->get()
            ->each(function ($row) use (&$daily) {
                $daily[$row->activity_date] = ($daily[$row->activity_date] ?? 0) - (float) $row->total;
            });

        return $daily;
    }

    private function netTopProductsBetween(Carbon $start, Carbon $end, ?int $limit = 5)
    {
        $salesRows = SaleItem::whereHas('sale', fn($q) => $q->whereBetween('created_at', [$start, $end]))
            ->select('product_id', DB::raw('sum(qty) as sold_qty'), DB::raw('sum(price * qty) as gross_revenue'))
            ->with('product')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $returnRows = ReturnItem::whereHas('productReturn', fn($q) => $q->where('status', 'completed')->whereBetween('created_at', [$start, $end]))
            ->select('product_id', DB::raw('sum(qty) as returned_qty'), DB::raw('sum(price * qty) as refunded_revenue'))
            ->with('product')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        return $salesRows->keys()
            ->merge($returnRows->keys())
            ->unique()
            ->map(function ($productId) use ($salesRows, $returnRows) {
                $saleRow = $salesRows->get($productId);
                $returnRow = $returnRows->get($productId);

                return (object) [
                    'product_id' => $productId,
                    'product' => $saleRow?->product ?? $returnRow?->product,
                    'total_qty' => max(0, (int) ($saleRow->sold_qty ?? 0) - (int) ($returnRow->returned_qty ?? 0)),
                    'total_revenue' => (float) ($saleRow->gross_revenue ?? 0) - (float) ($returnRow->refunded_revenue ?? 0),
                ];
            })
            ->filter(fn($row) => $row->total_qty > 0 || abs($row->total_revenue) > 0.01)
            ->sortByDesc('total_revenue')
            ->when($limit !== null, fn($rows) => $rows->take($limit))
            ->values();
    }

    private function staffSalesBetween(Carbon $start, Carbon $end)
    {
        return Sale::whereBetween('created_at', [$start, $end])
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('count(*) as transactions'), DB::raw('sum(total) as revenue'))
            ->with('user')
            ->groupBy('user_id')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($s) {
                return (object) [
                    'user_id' => $s->user_id,
                    'name' => $s->user?->name ?: ('Staff #' . $s->user_id),
                    'transactions' => (int) $s->transactions,
                    'revenue' => (float) $s->revenue,
                    'avg_sale' => $s->transactions > 0 ? (float) ($s->revenue / $s->transactions) : 0.0,
                ];
            });
    }

    private function categorySalesBetween(Carbon $start, Carbon $end)
    {
        return SaleItem::whereHas('sale', fn($q) => $q->whereBetween('created_at', [$start, $end]))
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->select('products.category', DB::raw('sum(sale_items.qty) as total_qty'), DB::raw('sum(sale_items.price * sale_items.qty) as total_revenue'))
            ->whereNotNull('products.category')
            ->where('products.category', '!=', '')
            ->groupBy('products.category')
            ->orderByDesc('total_revenue')
            ->get();
    }

    // =====================================================================
    // EXCEL (CSV) EXPORT
    // =====================================================================
    public function exportExcel(Request $request)
    {
        $tab = $request->get('tab', 'stock');

        if ($tab === 'stock') {
            $data     = $this->buildStockReport();
            $products = $data['products'];
            $filename = 'stock-report-' . date('Y-m-d') . '.csv';

            $callback = function () use ($products, $data) {
                $f = fopen('php://output', 'w');
                fputs($f, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

                fputcsv($f, ['B CLASSY LIFESTYLE — Stock & Revenue Report', '', '', '', '', '', 'Generated: ' . date('Y-m-d H:i')]);
                fputcsv($f, []);
                fputcsv($f, ['SUMMARY']);
                fputcsv($f, ['Total Products',            $data['productCount']]);
                fputcsv($f, ['Total Inventory Value (Rs.)', number_format($data['totalValue'], 2)]);
                fputcsv($f, ['All-Time Revenue (Rs.)',    number_format($data['allTimeRevenue'], 2)]);
                fputcsv($f, ['Out of Stock Items',        $data['outOfStockCount']]);
                fputcsv($f, ['Low Stock Items (≤5)',      $data['lowStockCount']]);
                fputcsv($f, []);
                fputcsv($f, ['#', 'Product Name', 'SKU', 'Category', 'Sub-Category', 'Unit Price (Rs.)', 'Current Stock', 'Inventory Value (Rs.)', 'Units Sold', 'Revenue (Rs.)', 'Last Sold Date', 'Status']);

                foreach ($products as $i => $p) {
                    if ($p->stock <= 0)      $status = 'Out of Stock';
                    elseif ($p->stock <= 5)  $status = 'Low Stock';
                    else                     $status = 'In Stock';

                    $lastSold = $p->last_sold_at
                        ? Carbon::parse($p->last_sold_at)->format('Y-m-d')
                        : 'Never';

                    fputcsv($f, [
                        $i + 1,
                        $p->name,
                        $p->sku        ?? '',
                        $p->category   ?? '',
                        $p->subcategory ?? '',
                        number_format($p->price,         2),
                        $p->stock,
                        number_format($p->stock_value,   2),
                        $p->total_sold,
                        number_format($p->total_revenue, 2),
                        $lastSold,
                        $status,
                    ]);
                }
                fclose($f);
            };

            return response()->stream($callback, 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        // --- Sales CSV (daily / monthly / custom) ---
        $date     = $request->get('date',      Carbon::today()->toDateString());
        $month    = $request->get('month',     Carbon::today()->format('Y-m'));
        $dateFrom = $request->get('date_from', Carbon::today()->subDays(30)->toDateString());
        $dateTo   = $request->get('date_to',   Carbon::today()->toDateString());

        if ($tab === 'daily') {
            $carbonDate = Carbon::parse($date);
            $salesRows  = $this->netTopProductsBetween(
                $carbonDate->copy()->startOfDay(),
                $carbonDate->copy()->endOfDay(),
                null
            );
            $label    = 'Daily-' . $date;
        } elseif ($tab === 'monthly') {
            $mStart   = Carbon::parse($month . '-01')->startOfMonth();
            $mEnd     = Carbon::parse($month . '-01')->endOfMonth();
            $salesRows = $this->netTopProductsBetween($mStart, $mEnd, null);
            $label = 'Monthly-' . $month;
        } else {
            $cFrom    = Carbon::parse($dateFrom);
            $cTo      = Carbon::parse($dateTo);
            $salesRows = $this->netTopProductsBetween(
                $cFrom->copy()->startOfDay(),
                $cTo->copy()->endOfDay(),
                null
            );
            $label = 'Custom-' . $dateFrom . '-to-' . $dateTo;
        }

        $filename = 'sales-report-' . $label . '.csv';

        $callback = function () use ($salesRows, $label) {
            $f = fopen('php://output', 'w');
            fputs($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($f, ['B CLASSY LIFESTYLE — Sales Report (' . $label . ')', '', '', '', 'Generated: ' . date('Y-m-d H:i')]);
            fputcsv($f, []);
            fputcsv($f, ['#', 'Product Name', 'SKU', 'Category', 'Units Sold', 'Revenue (Rs.)']);
            $total = 0;
            foreach ($salesRows as $i => $row) {
                fputcsv($f, [
                    $i + 1,
                    $row->product->name     ?? 'N/A',
                    $row->product->sku      ?? '',
                    $row->product->category ?? '',
                    $row->total_qty,
                    number_format($row->total_revenue, 2),
                ]);
                $total += $row->total_revenue;
            }
            fputcsv($f, []);
            fputcsv($f, ['', '', '', 'TOTAL', '', number_format($total, 2)]);
            fclose($f);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
