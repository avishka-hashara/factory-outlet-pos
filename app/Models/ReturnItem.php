<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    protected $table = 'return_items';

    protected $fillable = [
        'return_id',
        'sale_item_id',
        'product_id',
        'product_variant_id',
        'qty',
        'price',
        'condition',
        'restock',
        'condition_notes',
    ];

    protected $casts = [
        'restock' => 'boolean',
    ];

    public function productReturn(): BelongsTo
    {
        return $this->belongsTo(ProductReturn::class, 'return_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getSubtotalAttribute(): float
    {
        return $this->price * $this->qty;
    }

    public function getConditionLabelAttribute(): string
    {
        return match ($this->condition) {
            'good'    => 'Good Condition',
            'damaged' => 'Damaged',
            'other'   => 'Other',
            default   => ucfirst($this->condition),
        };
    }
}
