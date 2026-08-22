<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardMetricsService;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    /**
     * Render the Dashboard initial view.
     */
    public function index(Request $request, DashboardMetricsService $metricsService)
    {
        $range = $request->query('range', 'today');
        $metrics = $metricsService->getAllMetrics($range);

        return view('dashboard', compact('metrics', 'range'));
    }

    /**
     * JSON API Endpoint for widget AJAX live polling and instant range changes.
     */
    public function metrics(Request $request, DashboardMetricsService $metricsService): JsonResponse
    {
        $range = $request->query('range', 'today');
        $metrics = $metricsService->getAllMetrics($range);

        return response()->json($metrics);
    }

    /**
     * Streamed CSV Export of sales transactions for the active range.
     */
    public function export(Request $request, DashboardMetricsService $metricsService): StreamedResponse
    {
        $range = $request->query('range', 'today');
        $dates = $metricsService->resolveDateRanges($range);

        $fileName = "dashboard-sales-export-{$range}-" . now()->format('Y-m-d-His') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($dates) {
            $handle = fopen('php://output', 'w');
            
            // CSV Header Row
            fputcsv($handle, ['Bill #', 'Date & Time', 'Customer', 'Items Count', 'Payment Method', 'Subtotal', 'Discount', 'Total (Rs.)']);

            Sale::with(['customer'])
                ->withCount('items')
                ->whereBetween('created_at', [$dates['current_start'], $dates['current_end']])
                ->orderBy('id', 'asc')
                ->chunk(200, function ($sales) use ($handle) {
                    foreach ($sales as $sale) {
                        fputcsv($handle, [
                            $sale->invoice_number ?? ('#' . str_pad($sale->id, 4, '0', STR_PAD_LEFT)),
                            $sale->created_at ? $sale->created_at->format('Y-m-d H:i:s') : '',
                            $sale->customer?->name ?? 'Walk-in',
                            $sale->items_count ?? 0,
                            strtoupper($sale->payment_method ?? 'CASH'),
                            number_format($sale->subtotal, 2, '.', ''),
                            number_format($sale->discount_amount, 2, '.', ''),
                            number_format($sale->total, 2, '.', ''),
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
