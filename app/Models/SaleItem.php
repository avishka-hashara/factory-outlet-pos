<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_variant_id',
        'gift_card_id',
        'item_label',
        'qty',
        'price',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function giftCard()
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function getLineSubtotalAttribute(): float
    {
        return (float) $this->price * (int) $this->qty;
    }

    public function getRefundUnitPriceAttribute(): float
    {
        $sale = $this->sale;
        $lineSubtotal = $this->line_subtotal;

        if (!$sale || (int) $this->qty <= 0 || (float) $sale->discount_amount <= 0) {
            return (float) $this->price;
        }

        $discountBase = (float) $sale->subtotal;
        if ($discountBase <= 0) {
            $discountBase = (float) $sale->items->sum(fn (SaleItem $item) => $item->line_subtotal);
        }

        if ($discountBase <= 0) {
            return (float) $this->price;
        }

        $lineDiscount = ((float) $sale->discount_amount) * ($lineSubtotal / $discountBase);
        $lineRefundTotal = max(0, $lineSubtotal - $lineDiscount);

        return round($lineRefundTotal / (int) $this->qty, 2);
    }
}
