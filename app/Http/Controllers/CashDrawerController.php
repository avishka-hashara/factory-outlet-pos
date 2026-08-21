<?php

namespace App\Http\Controllers;

use App\Models\CashDrawerSession;
use App\Models\CashDrawerTransaction;
use App\Models\ProductReturn;
use App\Models\Sale;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CashDrawerController extends Controller
{
    /* ------------------------------------------------
     * GET /cash-drawer  – Main page
     * ------------------------------------------------ */
    public function index()
    {
        $session  = CashDrawerSession::currentOpen();
        $history  = CashDrawerSession::with(['openedBy', 'closedByUser'])
                        ->where('status', 'closed')
                        ->latest('closed_at')
                        ->paginate(8);

        $transactions = $session
            ? $this->sessionTransactions($session)
            : collect();

        return view('cash-drawer.index', compact('session', 'history', 'transactions'));
    }

    /* ------------------------------------------------
     * POST /cash-drawer/open
     * ------------------------------------------------ */
    public function open(Request $request): JsonResponse
    {
        // Check no session already open
        if (CashDrawerSession::currentOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'A drawer session is already open. Close it first.',
            ], 422);
        }

        $request->validate([
            'opening_balance' => 'required|numeric|min:0|max:9999999',
            'notes'           => 'nullable|string|max:500',
        ]);

        $session = CashDrawerSession::create([
            'user_id'         => auth()->id(),
            'opening_balance' => $request->opening_balance,
            'notes'           => $request->notes,
            'opened_at'       => now(),
            'status'          => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cash drawer opened successfully.',
            'session' => $session,
        ]);
    }

    /* ------------------------------------------------
     * POST /cash-drawer/close
     * ------------------------------------------------ */
    public function close(Request $request): JsonResponse
    {
        $session = CashDrawerSession::currentOpen();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'No open drawer session found.',
            ], 422);
        }

        $request->validate([
            'closing_balance' => 'required|numeric|min:0|max:9999999',
            'close_notes'     => 'nullable|string|max:500',
        ]);

        $cashSales = $session->cash_sales;
        $cashSalesCount = $session->cash_sales_count;
        $cashRefunds = $session->cash_refunds;
        $cashIn = $session->total_cash_in;
        $cashOut = $session->total_cash_out;
        $expected = $session->expected_balance;
        $actual = (float) $request->closing_balance;
        $difference = $actual - $expected;

        $session->update([
            'closed_by'              => auth()->id(),
            'closing_balance'        => $request->closing_balance,
            'cash_sales_total'       => $cashSales,
            'cash_sales_count_total' => $cashSalesCount,
            'cash_refunds_total'     => $cashRefunds,
            'cash_in_total'          => $cashIn,
            'cash_out_total'         => $cashOut,
            'expected_balance_total' => $expected,
            'difference_total'       => $difference,
            'close_notes'            => $request->close_notes,
            'closed_at'              => now(),
            'status'                 => 'closed',
        ]);

        $diffText = $difference == 0
            ? 'Balanced perfectly.'
            : ($difference > 0
                ? 'Surplus of Rs. ' . number_format(abs($difference), 2)
                : 'Shortage of Rs. ' . number_format(abs($difference), 2));

        return response()->json([
            'success'    => true,
            'message'    => "Drawer closed. {$diffText}",
            'difference' => $difference,
        ]);
    }

    /* ------------------------------------------------
     * POST /cash-drawer/cash-in
     * ------------------------------------------------ */
    public function cashIn(Request $request): JsonResponse
    {
        $session = CashDrawerSession::currentOpen();
        if (! $session) {
            return response()->json(['success' => false, 'message' => 'No open drawer session.'], 422);
        }

        $request->validate([
            'amount'      => 'required|numeric|min:0.01|max:9999999',
            'description' => 'nullable|string|max:255',
        ]);

        $tx = CashDrawerTransaction::create([
            'session_id'  => $session->id,
            'user_id'     => auth()->id(),
            'type'        => 'cash_in',
            'amount'      => $request->amount,
            'description' => $request->description ?? 'Cash In',
        ]);

        return response()->json([
            'success'          => true,
            'message'          => 'Cash In recorded.',
            'tx'               => $tx->load('user'),
            'expected_balance' => $session->fresh()->expected_balance,
            'total_cash_in'    => $session->fresh()->total_cash_in,
        ]);
    }

    /* ------------------------------------------------
     * POST /cash-drawer/cash-out
     * ------------------------------------------------ */
    public function cashOut(Request $request): JsonResponse
    {
        $session = CashDrawerSession::currentOpen();
        if (! $session) {
            return response()->json(['success' => false, 'message' => 'No open drawer session.'], 422);
        }

        $request->validate([
            'amount'      => 'required|numeric|min:0.01|max:9999999',
            'description' => 'nullable|string|max:255',
        ]);

        $tx = CashDrawerTransaction::create([
            'session_id'  => $session->id,
            'user_id'     => auth()->id(),
            'type'        => 'cash_out',
            'amount'      => $request->amount,
            'description' => $request->description ?? 'Cash Out',
        ]);

        return response()->json([
            'success'          => true,
            'message'          => 'Cash Out recorded.',
            'tx'               => $tx->load('user'),
            'expected_balance' => $session->fresh()->expected_balance,
            'total_cash_out'   => $session->fresh()->total_cash_out,
        ]);
    }

    /* ------------------------------------------------
     * GET /cash-drawer/session/{session}  – Session detail
     * ------------------------------------------------ */
    public function sessionDetail(CashDrawerSession $session): JsonResponse
    {
        $session->load(['openedBy', 'closedByUser']);

        return response()->json([
            'success' => true,
            'session' => array_merge($session->toArray(), [
                'cash_sales'        => $session->cash_sales,
                'cash_refunds'      => $session->cash_refunds,
                'cash_sales_count'  => $session->cash_sales_count,
                'total_cash_in'     => $session->total_cash_in,
                'total_cash_out'    => $session->total_cash_out,
                'expected_balance'  => $session->expected_balance,
                'difference'        => $session->difference,
                'duration'          => $session->duration,
                'transactions'      => $this->sessionTransactions($session)->values(),
            ]),
        ]);
    }

    private function sessionTransactions(CashDrawerSession $session): Collection
    {
        $manual = $session->transactions()
            ->with('user')
            ->get()
            ->map(fn (CashDrawerTransaction $tx) => [
                'source'      => 'manual',
                'type'        => $tx->type,
                'description' => $tx->description,
                'amount'      => (float) $tx->amount,
                'user_name'   => $tx->user?->name,
                'created_at'  => $tx->created_at,
            ]);

        $sales = $this->sessionCashSales($session)
            ->get()
            ->filter(fn (Sale $sale) => $sale->cash_paid_amount > 0)
            ->map(fn (Sale $sale) => [
                'source'      => 'sale',
                'type'        => 'cash_sale',
                'description' => 'POS Sale #' . ($sale->invoice_number ?: $sale->id),
                'amount'      => $sale->cash_paid_amount,
                'user_name'   => $sale->user?->name,
                'created_at'  => $sale->created_at,
            ]);

        $refunds = $this->sessionCashRefunds($session)
            ->get()
            ->map(fn (ProductReturn $return) => [
                'source'      => 'refund',
                'type'        => 'cash_refund',
                'description' => 'Cash Refund for Sale #' . ($return->sale?->invoice_number ?: $return->sale_id),
                'amount'      => (float) $return->total_refunded,
                'user_name'   => $return->user?->name,
                'created_at'  => $return->created_at,
            ]);

        return $manual
            ->concat($sales)
            ->concat($refunds)
            ->sortByDesc('created_at')
            ->map(fn (array $tx) => new Fluent($tx + [
                'user' => new Fluent(['name' => $tx['user_name']]),
            ]))
            ->values();
    }

    private function sessionCashSales(CashDrawerSession $session)
    {
        return Sale::with('user')
            ->whereBetween('created_at', $this->activityWindow($session, 'sales'))
            ->where(function ($query) {
                $query->where('payment_method', 'cash')
                    ->orWhere('payment_method', 'multipay');
            })
            ->latest();
    }

    private function sessionCashRefunds(CashDrawerSession $session)
    {
        return ProductReturn::with(['sale', 'user'])
            ->where('status', 'completed')
            ->where('refund_method', 'cash')
            ->whereBetween('created_at', $this->activityWindow($session, 'refunds'))
            ->latest();
    }

    private function activityWindow(CashDrawerSession $session, string $type): array
    {
        $end = $session->closed_at ?? now();
        $standardWindow = [$session->opened_at, $end];

        if ($session->status !== 'closed') {
            return $standardWindow;
        }

        $standardTotal = $type === 'refunds'
            ? (float) ProductReturn::where('status', 'completed')
                ->where('refund_method', 'cash')
                ->whereBetween('created_at', $standardWindow)
                ->sum('total_refunded')
            : (float) Sale::whereBetween('created_at', $standardWindow)
                ->where(function ($query) {
                    $query->where('payment_method', 'cash')
                        ->orWhere('payment_method', 'multipay');
                })
                ->get()
                ->sum(fn (Sale $sale) => $sale->cash_paid_amount);

        $snapshotTotal = $type === 'refunds'
            ? (float) $session->cash_refunds
            : (float) $session->cash_sales;

        if ($snapshotTotal <= 0 || abs($standardTotal - $snapshotTotal) < 0.01) {
            return $standardWindow;
        }

        $previousClosedAt = CashDrawerSession::where('status', 'closed')
            ->where('id', '!=', $session->id)
            ->whereNotNull('closed_at')
            ->where('closed_at', '<=', $end)
            ->latest('closed_at')
            ->value('closed_at');

        return [$previousClosedAt ?? $session->opened_at, $end];
    }
}
