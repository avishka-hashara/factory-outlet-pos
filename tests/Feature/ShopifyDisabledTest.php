<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\ShopifyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopifyDisabledTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Force the production scenario: integration off, every Shopify env value empty.
     */
    private function disableShopify(): void
    {
        config([
            'services.shopify.enabled'     => false,
            'services.shopify.shop'        => null,
            'services.shopify.token'       => null,
            'services.shopify.api_key'     => null,
            'services.shopify.api_secret'  => null,
        ]);
    }

    private function enableShopifyWithConfig(): void
    {
        config([
            'services.shopify.enabled'    => true,
            'services.shopify.shop'       => 'test-shop.myshopify.com',
            'services.shopify.token'      => 'shpat_testtoken',
            'services.shopify.api_key'    => 'test_key',
            'services.shopify.api_secret' => 'test_secret',
        ]);
    }

    private function cashier(): User
    {
        return User::factory()->create(['role' => 'Admin', 'status' => 'Active']);
    }

    private function sellableProduct(): Product
    {
        return Product::create([
            'name'  => 'Gold Ring',
            'sku'   => 'GR-' . uniqid(),
            'price' => 15000,
            'stock' => 50,
        ]);
    }

    private function checkoutPayload(Product $product, string $paymentMethod): array
    {
        return [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'price' => 15000],
            ],
            'payment_method' => $paymentMethod,
            'subtotal'       => 15000,
            'total'          => 15000,
            'amount_received' => 15000,
            'change_amount'  => 0,
        ];
    }

    /**
     * The production bug: checkout must complete for EVERY payment method while
     * Shopify is disabled and all Shopify env values are empty — no 500s.
     */
    public function test_checkout_completes_for_every_payment_method_with_shopify_disabled(): void
    {
        $this->disableShopify();
        // Any accidental HTTP call would be a bug: fail loudly if one is attempted.
        Http::preventStrayRequests();

        $user = $this->cashier();

        $methods = ['cash', 'card', 'koko', 'mintpay', 'bank', 'payzy', 'multipay'];

        foreach ($methods as $method) {
            $product = $this->sellableProduct();

            $response = $this->actingAs($user)
                ->postJson(route('pos.checkout'), $this->checkoutPayload($product, $method));

            $response->assertOk();
            $response->assertJson(['success' => true]);
        }

        $this->assertSame(count($methods), Sale::count());
    }

    /**
     * ShopifyService must never throw on construction, even when every config
     * value is null (belt-and-braces for the string-typed properties).
     */
    public function test_shopify_service_constructs_when_config_is_null(): void
    {
        $this->disableShopify();

        $service = new ShopifyService();

        $this->assertInstanceOf(ShopifyService::class, $service);
        $this->assertFalse(ShopifyService::isEnabled());
    }

    /**
     * A Shopify UI action (the global "Sync" button hits sync-all) must run no
     * sync and return the "not enabled yet" message when disabled.
     */
    public function test_shopify_sync_action_shows_not_enabled_message_and_runs_no_sync(): void
    {
        $this->disableShopify();
        Http::preventStrayRequests();

        $response = $this->postJson(route('shopify.sync.all'));

        $response->assertOk();
        $response->assertJson([
            'success'  => false,
            'disabled' => true,
            'message'  => 'This feature is not enabled yet.',
        ]);
    }

    public function test_all_shopify_sync_endpoints_are_dormant_when_disabled(): void
    {
        $this->disableShopify();
        Http::preventStrayRequests();

        $endpoints = [
            'shopify.sync',
            'shopify.sync.collections',
            'shopify.fast.category.sync',
            'shopify.sync.all',
        ];

        foreach ($endpoints as $name) {
            $this->postJson(route($name))
                ->assertOk()
                ->assertJson(['disabled' => true, 'message' => 'This feature is not enabled yet.']);
        }
    }

    /**
     * When enabled with valid config, existing Shopify behaviour is unchanged:
     * the API is actually called and the sync runs (so the feature still works
     * when it is later turned on).
     */
    public function test_sync_runs_and_calls_api_when_enabled_with_valid_config(): void
    {
        $this->enableShopifyWithConfig();

        Http::fake([
            '*/products.json*' => Http::response(['products' => []], 200),
        ]);

        $response = $this->postJson(route('shopify.sync'));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/products.json'));
    }

    /**
     * Checkout still attempts the Shopify order push when enabled — the sale
     * completes and the Shopify API is contacted.
     */
    public function test_checkout_pushes_to_shopify_when_enabled(): void
    {
        $this->enableShopifyWithConfig();

        Http::fake([
            '*' => Http::response(['order' => ['id' => 123, 'order_number' => 1001]], 200),
        ]);

        $user = $this->cashier();
        $product = $this->sellableProduct();

        $response = $this->actingAs($user)
            ->postJson(route('pos.checkout'), $this->checkoutPayload($product, 'cash'));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/orders.json'));
    }
}
