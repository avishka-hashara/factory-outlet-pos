<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'shopify_id',
        'shopify_variant_id',
        'inventory_item_id',
        'shopify_location_id',
        'name',
        'sku',
        'category',
        'subcategory',
        'price',
        'stock',
        'image_url',
    ];

    protected $casts = [
        'shopify_id' => 'string',
        'shopify_variant_id' => 'string',
        'inventory_item_id' => 'string',
        'shopify_location_id' => 'string',
    ];

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Collections this product belongs to.
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_product')
                    ->withPivot('position')
                    ->withTimestamps();
    }

    /**
     * Check if this product has color variants
     */
    public function hasVariants(): bool
    {
        return $this->variants()->count() > 0;
    }

    /**
     * Get total stock across all variants (or product stock if no variants)
     */
    public function getTotalStockAttribute(): int
    {
        if ($this->variants()->exists()) {
            return $this->variants()->sum('stock');
        }
        return $this->stock;
    }
}
