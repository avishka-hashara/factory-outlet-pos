<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Sale extends Model
{
    protected $fillable = [
        'user_id',
        'customer_id',
        'invoice_number',
        'bill_token',
        'idempotency_key',
        'total',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'payment_method',
        'payment_splits',
        'amount_received',
        'change_amount',
        'has_return',
        'gift_card_redemption_amount',
    ];

    protected $casts = [
        'has_return' => 'boolean',
        'payment_splits' => 'array',
        'amount_received' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'gift_card_redemption_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function returns()
    {
        return $this->hasMany(ProductReturn::class);
    }

    public function smsOutbox()
    {
        return $this->hasMany(SmsOutbox::class, 'invoice_id');
    }

    /**
     * Get (or lazily create) the random token for the public bill view.
     * The token — never the sale ID — is what goes into SMS links.
     */
    public function ensureBillToken(): string
    {
        if (!$this->bill_token) {
            do {
                $token = Str::random(12);
            } while (static::where('bill_token', $token)->exists());

            $this->forceFill(['bill_token' => $token])->save();
        }

        return $this->bill_token;
    }

    /**
     * Whether the public bill link has expired.
     *
     * TTL comes from config('services.sms.bill_link_ttl_days'), which is
     * cast to either null (never expires) or a positive integer number of
     * days. Expiry is anchored to the sale's created_at, so a link placed in
     * an SMS immediately after checkout is always fresh on arrival.
     */
    public function billLinkExpired(): bool
    {
        $ttlDays = config('services.sms.bill_link_ttl_days');

        // null / non-positive → links never expire
        if (!is_int($ttlDays) || $ttlDays <= 0) {
            return false;
        }

        if ($this->created_at === null) {
            return false;
        }

        return $this->created_at->copy()->addDays($ttlDays)->isPast();
    }

    /**
     * Net revenue after deducting all refunds.
     */
    public function getNetTotalAttribute(): float
    {
        $refunded = $this->returns()->where('status', 'completed')->sum('total_refunded');
        return max(0, $this->total - $refunded);
    }

    /**
     * Cash portion received for this sale.
     */
    public function getCashPaidAmountAttribute(): float
    {
        if ($this->payment_method === 'cash') {
            return (float) $this->total;
        }

        if ($this->payment_method !== 'multipay' || !is_array($this->payment_splits)) {
            return 0.0;
        }

        return (float) collect($this->payment_splits)
            ->filter(fn ($split) => ($split['method'] ?? null) === 'cash')
            ->sum(fn ($split) => (float) ($split['amount'] ?? 0));
    }
}
