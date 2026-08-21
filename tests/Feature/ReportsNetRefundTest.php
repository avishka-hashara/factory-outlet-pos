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

class ReportsNetRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_report_uses_net_sales_after_completed_refunds(): void
    {
        $user = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);
        $product = Product::create([
            'name' => 'Diamond Ring',
            'sku' => 'DR-REPORT',
            'price' => 50000,
            'stock' => 10,
        ]);

        $sale = Sale::create([
            'user_id' => $user->id,
            'invoice_number' => 'REPORT-1',
            'subtotal' => 100000,
            'total' => 100000,
            'discount_amount' => 0,
            'payment_method' => 'cash',
            'created_at' => '2026-07-17 10:00:00',
            'updated_at' => '2026-07-17 10:00:00',
        ]);
        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'qty' => 2,
            'price' => 50000,
        ]);

        $return = ProductReturn::create([
            'sale_id' => $sale->id,
            'user_id' => $user->id,
            'total_refunded' => 50000,
            'refund_method' => 'cash',
            'reason' => 'customer_request',
            'status' => 'completed',
            'created_at' => '2026-07-17 11:00:00',
            'updated_at' => '2026-07-17 11:00:00',
        ]);
        ReturnItem::create([
            'return_id' => $return->id,
            'sale_item_id' => $saleItem->id,
            'product_id' => $product->id,
            'qty' => 1,
            'price' => 50000,
            'condition' => 'good',
            'restock' => true,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index', [
            'tab' => 'daily',
            'date' => '2026-07-17',
        ]));

        $response->assertOk();
        $response->assertViewHas('revenue', 50000.0);
        $response->assertViewHas('itemsSold', 1);
        $response->assertViewHas('totalRefunds', 1);
        $response->assertViewHas('refundValue', 50000.0);
        $response->assertViewHas('payments', fn (array $payments) => $payments['cash'] === 50000.0);
        $response->assertViewHas('topProducts', function ($products) {
            $topProduct = $products->first();

            return $topProduct
                && $topProduct->total_qty === 1
                && $topProduct->total_revenue === 50000.0;
        });
    }
}
