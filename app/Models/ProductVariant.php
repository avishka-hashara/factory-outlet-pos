<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'color',
        'color_code',
        'size',
        'sku',
        'price',
        'stock',
        'shopify_variant_id',
        'inventory_item_id',
        'shopify_location_id',
        'image_url',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'stock'      => 'integer',
        'price'      => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the effective price (variant price or fallback to product price)
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->price ?? $this->product->price;
    }

    /**
     * Get display name including color and/or size
     */
    public function getDisplayNameAttribute(): string
    {
        $label = collect([$this->color, $this->size])->filter()->implode(' / ');
        return $this->product->name . ($label ? ' - ' . $label : '');
    }
}
