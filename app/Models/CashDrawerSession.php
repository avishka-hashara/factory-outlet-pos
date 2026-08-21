<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashDrawerSession extends Model
{
    protected $fillable = [
        'user_id', 'closed_by', 'opening_balance', 'closing_balance',
        'cash_sales_total', 'cash_sales_count_total', 'cash_refunds_total',
        'cash_in_total', 'cash_out_total', 'expected_balance_total',
        'difference_total', 'notes', 'close_notes', 'opened_at', 'closed_at', 'status',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'cash_sales_total' => 'decimal:2',
        'cash_refunds_total' => 'decimal:2',
        'cash_in_total' => 'decimal:2',
        'cash_out_total' => 'decimal:2',
        'expected_balance_total' => 'decimal:2',
        'difference_total' => 'decimal:2',
        'opened_at'       => 'datetime',
        'closed_at'       => 'datetime',
    ];

    /* ------------------------------------------------
     * Relationships
     * ------------------------------------------------ */

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function transactions()
    {
        return $this->hasMany(CashDrawerTransaction::class, 'session_id')->latest();
    }

    public function cashSales()
    {
        $end = $this->closed_at ?? now();

        return Sale::with('user')
            ->whereBetween('created_at', [$this->opened_at, $end])
            ->where(function ($query) {
                $query->where('payment_method', 'cash')
                    ->orWhere('payment_method', 'multipay');
            })
            ->latest();
    }

    public function cashRefunds()
    {
        $end = $this->closed_at ?? now();

        return ProductReturn::with(['sale', 'user'])
            ->where('status', 'completed')
            ->where('refund_method', 'cash')
            ->whereBetween('created_at', [$this->opened_at, $end])
            ->latest();
    }

    /* ------------------------------------------------
     * Computed attributes (real-time, not stored)
     * ------------------------------------------------ */

    /** Total cash POS sales made during this session */
    public function getCashSalesAttribute(): float
    {
        if ($this->status === 'closed' && $this->cash_sales_total !== null) {
            return (float) $this->cash_sales_total;
        }

        $cashSales = (float) $this->cashSales()
            ->get()
            ->sum(fn (Sale $sale) => $sale->cash_paid_amount);

        if ($this->status === 'closed' && $cashSales === 0.0 && $this->closing_balance !== null) {
            return max(0,
                (float) $this->closing_balance
                - (float) $this->opening_balance
                + $this->cash_refunds
                - $this->total_cash_in
                + $this->total_cash_out
            );
        }

        return $cashSales;
    }

    /** Total cash refunds during this session */
    public function getCashRefundsAttribute(): float
    {
        if ($this->status === 'closed' && $this->cash_refunds_total !== null) {
            return (float) $this->cash_refunds_total;
        }

        return (float) $this->cashRefunds()
            ->sum('total_refunded');
    }

    /** Total count of POS cash sales during this session */
    public function getCashSalesCountAttribute(): int
    {
        if ($this->status === 'closed' && $this->cash_sales_count_total !== null) {
            return (int) $this->cash_sales_count_total;
        }

        return $this->cashSales()
            ->get()
            ->filter(fn (Sale $sale) => $sale->cash_paid_amount > 0)
            ->count();
    }

    /** Manual cash added to drawer */
    public function getTotalCashInAttribute(): float
    {
        if ($this->status === 'closed' && $this->cash_in_total !== null) {
            return (float) $this->cash_in_total;
        }

        return (float) $this->transactions()->where('type', 'cash_in')->sum('amount');
    }

    /** Manual cash removed from drawer */
    public function getTotalCashOutAttribute(): float
    {
        if ($this->status === 'closed' && $this->cash_out_total !== null) {
            return (float) $this->cash_out_total;
        }

        return (float) $this->transactions()->where('type', 'cash_out')->sum('amount');
    }

    /** Expected cash in drawer right now */
    public function getExpectedBalanceAttribute(): float
    {
        if ($this->status === 'closed' && $this->expected_balance_total !== null) {
            return (float) $this->expected_balance_total;
        }

        return max(0,
            (float) $this->opening_balance
            + $this->cash_sales
            - $this->cash_refunds
            + $this->total_cash_in
            - $this->total_cash_out
        );
    }

    /**
     * Difference between actual closing and expected (null when still open).
     */
    public function getDifferenceAttribute(): ?float
    {
        if ($this->closing_balance === null) return null;

        if ($this->status === 'closed' && $this->difference_total !== null) {
            return (float) $this->difference_total;
        }

        return (float) $this->closing_balance - $this->expected_balance;
    }

    /** Duration of the session in human-readable form. */
    public function getDurationAttribute(): string
    {
        $end = $this->closed_at ?? now();
        $diff = $this->opened_at->diff($end);
        if ($diff->days > 0) {
            return "{$diff->days}d {$diff->h}h {$diff->i}m";
        }
        return "{$diff->h}h {$diff->i}m";
    }

    /* ------------------------------------------------
     * Static helpers
     * ------------------------------------------------ */

    /** Returns the single open session, or null. */
    public static function currentOpen(): ?static
    {
        return static::where('status', 'open')->latest('opened_at')->first();
    }
}
