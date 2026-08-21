<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductReturn extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'sale_id',
        'user_id',
        'total_refunded',
        'refund_method',
        'reason',
        'notes',
        'status',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    /**
     * Human-readable reason labels.
     */
    public static function reasonLabels(): array
    {
        return [
            'customer_request' => 'Customer Request',
            'damaged'          => 'Damaged',
            'wrong_item'       => 'Wrong Item',
            'quality_issue'    => 'Quality Issue',
            'other'            => 'Other',
        ];
    }

    public function getReasonLabelAttribute(): string
    {
        return self::reasonLabels()[$this->reason] ?? ucfirst($this->reason);
    }

    public function getRefundMethodLabelAttribute(): string
    {
        return match ($this->refund_method) {
            'cash'         => 'Cash',
            'card'         => 'Card',
            'store_credit' => 'Store Credit',
            'bank'         => 'Bank Transfer',
            default        => ucfirst($this->refund_method),
        };
    }
}
