<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_SOLD = 'sold';

    public const STATUS_GIFTED = 'gifted';

    public const STATUS_REDEEMED = 'redeemed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'card_number',
        'barcode',
        'value',
        'issued_date',
        'expiry_date',
        'status',
        'issued_sale_id',
        'issued_at',
        'redeemed_sale_id',
        'redeemed_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'issued_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    public function issuedSale()
    {
        return $this->belongsTo(Sale::class, 'issued_sale_id');
    }

    public function redeemedSale()
    {
        return $this->belongsTo(Sale::class, 'redeemed_sale_id');
    }

    /**
     * Exact lookup by card number OR barcode (both are unique).
     */
    public static function findByCode(string $code): ?self
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        return static::where('card_number', $code)->orWhere('barcode', $code)->first();
    }

    /**
     * A card with no expiry_date never expires.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date !== null
            && $this->expiry_date->toDateString() < now()->toDateString();
    }

    /**
     * Live display status: an issued-but-expired card shows as 'expired'.
     */
    public function liveStatus(): string
    {
        if (in_array($this->status, [self::STATUS_SOLD, self::STATUS_GIFTED], true) && $this->isExpired()) {
            return 'expired';
        }

        return $this->status;
    }

    /**
     * The redeemability WHERE guard. Never trusts the status column alone:
     * redeemed_at and expiry are re-checked in the same predicate.
     */
    public function scopeRedeemable(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [self::STATUS_SOLD, self::STATUS_GIFTED])
            ->whereNull('redeemed_at')
            ->where(function (Builder $q) {
                $q->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', now()->toDateString());
            });
    }

    /**
     * Race-safe redemption: a SINGLE guarded UPDATE, no check-then-update.
     * Two terminals (or a double-click) racing on the same card can each run
     * this, but only one UPDATE matches the guard; the loser gets false.
     * Must be called inside the sale's DB transaction so a failed sale rolls
     * the redemption back.
     */
    public function redeemForSale(Sale $sale): bool
    {
        $affected = static::whereKey($this->getKey())
            ->redeemable()
            ->update([
                'status' => self::STATUS_REDEEMED,
                'redeemed_sale_id' => $sale->id,
                'redeemed_at' => now(),
            ]);

        if ($affected === 1) {
            $this->refresh();

            return true;
        }

        return false;
    }

    /**
     * Race-safe available -> sold, bound to a completed checkout. Called only
     * inside the sale transaction, so an abandoned/failed checkout leaves the
     * card 'available'.
     */
    public function markSoldForSale(Sale $sale): bool
    {
        $affected = static::whereKey($this->getKey())
            ->where('status', self::STATUS_AVAILABLE)
            ->update([
                'status' => self::STATUS_SOLD,
                'issued_sale_id' => $sale->id,
                'issued_at' => now(),
            ]);

        if ($affected === 1) {
            $this->refresh();

            return true;
        }

        return false;
    }

    /**
     * Race-safe available -> gifted (given away free: out of shop, no bill,
     * no revenue).
     */
    public function markGifted(): bool
    {
        $affected = static::whereKey($this->getKey())
            ->where('status', self::STATUS_AVAILABLE)
            ->update([
                'status' => self::STATUS_GIFTED,
                'issued_at' => now(),
            ]);

        if ($affected === 1) {
            $this->refresh();

            return true;
        }

        return false;
    }

    /**
     * Why a redemption attempt cannot proceed, for operator-facing messages.
     * Only for reporting AFTER the atomic UPDATE matched zero rows — never as
     * a pre-check.
     */
    public function redeemRejectionReason(): string
    {
        return match (true) {
            $this->status === self::STATUS_REDEEMED || $this->redeemed_at !== null => "Gift card {$this->card_number} has already been redeemed.",
            $this->status === self::STATUS_AVAILABLE => "Gift card {$this->card_number} has not been issued yet — it must be sold or gifted before it can be redeemed.",
            $this->status === self::STATUS_CANCELLED => "Gift card {$this->card_number} has been cancelled.",
            $this->isExpired() => "Gift card {$this->card_number} expired on {$this->expiry_date->format('Y-m-d')}.",
            default => "Gift card {$this->card_number} cannot be redeemed.",
        };
    }

    /**
     * Printable line-item label for the bill when this card is sold.
     */
    public function saleItemLabel(): string
    {
        return number_format((float) $this->value, 0).' Gift Card';
    }
}
