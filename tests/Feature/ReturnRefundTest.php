<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_discounted_sale_refunds_discounted_unit_amount(): void
    {
        $user = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);
        $product = Product::create([
            'name' => 'Diamond Necklace',
            'sku' => 'DN-1',
            'price' => 89000,
            'stock' => 1,
        ]);
        $sale = Sale::create([
            'user_id' => $user->id,
            'invoice_number' => 'TEST-1',
            'subtotal' => 89000,
            'total' => 80000,
            'discount_type' => 'fixed',
            'discount_value' => 9000,
            'discount_amount' => 9000,
            'payment_method' => 'cash',
        ]);
        $item = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'qty' => 1,
            'price' => 89000,
        ]);

        $this->actingAs($user)
            ->post(route('returns.store', $sale), [
                'items' => [
                    ['sale_item_id' => $item->id, 'qty' => 1, 'condition' => 'good', 'restock' => 1],
                ],
                'refund_method' => 'cash',
                'reason' => 'customer_request',
            ])
            ->assertRedirect(route('returns.index'));

        $this->assertSame(80000.0, (float) ProductReturn::first()->total_refunded);
        $this->assertSame(80000.0, (float) ReturnItem::first()->price);
    }

    public function test_one_item_can_be_returned_from_multi_item_sale(): void
    {
        $user = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);
        $firstProduct = Product::create(['name' => 'Diamond Necklace', 'sku' => 'DN-2', 'price' => 89000, 'stock' => 1]);
        $secondProduct = Product::create(['name' => 'Diamond Ring', 'sku' => 'DR-1', 'price' => 25900, 'stock' => 1]);
        $sale = Sale::create([
            'user_id' => $user->id,
            'invoice_number' => 'TEST-2',
            'subtotal' => 114900,
            'total' => 114900,
            'discount_amount' => 0,
            'payment_method' => 'cash',
        ]);
        $firstItem = SaleItem::create(['sale_id' => $sale->id, 'product_id' => $firstProduct->id, 'qty' => 1, 'price' => 89000]);
        $secondItem = SaleItem::create(['sale_id' => $sale->id, 'product_id' => $secondProduct->id, 'qty' => 1, 'price' => 25900]);

        $this->actingAs($user)
            ->post(route('returns.store', $sale), [
                'items' => [
                    ['sale_item_id' => $firstItem->id],
                    ['sale_item_id' => $secondItem->id, 'qty' => 1, 'condition' => 'good', 'restock' => 1],
                ],
                'refund_method' => 'cash',
                'reason' => 'customer_request',
            ])
            ->assertRedirect(route('returns.index'));

        $return = ProductReturn::first();
        $this->assertSame(25900.0, (float) $return->total_refunded);
        $this->assertSame(1, $return->items()->count());
        $this->assertSame($secondItem->id, $return->items()->first()->sale_item_id);
    }
}
