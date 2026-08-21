<?php

use App\Models\GiftCard;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SmsOutbox;
use App\Models\User;
use App\Services\CheckoutIdempotencyService;
use App\Services\InvoiceNumberService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function idemCashier(): User
{
    return User::factory()->create(['role' => 'Staff', 'status' => 'Active']);
}

function idemProduct(int $stock = 10): Product
{
    return Product::create([
        'name' => 'Idem Test Ring',
        'sku' => 'IDEM' . uniqid(),
        'price' => '2500',
        'stock' => $stock,
    ]);
}

function idemPayload(Product $product, string $key, array $overrides = []): array
{
    return array_merge([
        'idempotency_key' => $key,
        'items' => [[
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 1,
            'price' => 2500,
        ]],
        'payment_method' => 'cash',
        'subtotal' => 2500,
        'total' => 2500,
        'amount_received' => 2500,
        'change_amount' => 0,
    ], $overrides);
}

/*
|--------------------------------------------------------------------------
| The bug: one purchase submitted twice
|--------------------------------------------------------------------------
*/

it('creates only one sale when the same checkout is submitted twice', function () {
    // Exactly the production symptom: a double-clicked payment button sends the
    // request twice. Same cart, same second, same amount, same method — the old
    // code made two sales with two invoice numbers.
    $cashier = idemCashier();
    $product = idemProduct();
    $key = 'a1b2c3d4-0000-4000-8000-000000000001';

    $first = $this->actingAs($cashier)->postJson('/pos/checkout', idemPayload($product, $key));
    $second = $this->actingAs($cashier)->postJson('/pos/checkout', idemPayload($product, $key));

    $first->assertOk()->assertJson(['success' => true, 'duplicate' => false]);
    // The repeat is a success, not an error — the POS shows the sale it made.
    $second->assertOk()->assertJson(['success' => true, 'duplicate' => true]);

    expect($second->json('sale_id'))->toBe($first->json('sale_id'))
        ->and($second->json('invoice_number'))->toBe($first->json('invoice_number'))
        ->and(Sale::count())->toBe(1)
        ->and(Sale::pluck('invoice_number')->unique()->count())->toBe(1)
        // No invoice number was burned by the duplicate...
        ->and((int) DB::table('invoice_counters')->where('counter_date', now()->toDateString())->value('last_sequence'))->toBe(1)
        // ...and stock was decremented exactly once.
        ->and((int) $product->fresh()->stock)->toBe(9);
});

it('still creates separate sales for genuinely different purchases', function () {
    $cashier = idemCashier();
    $product = idemProduct();

    $first = $this->actingAs($cashier)->postJson('/pos/checkout', idemPayload($product, 'key-purchase-one'));
    $second = $this->actingAs($cashier)->postJson('/pos/checkout', idemPayload($product, 'key-purchase-two'));

    $first->assertOk()->assertJson(['duplicate' => false]);
    $second->assertOk()->assertJson(['duplicate' => false]);

    expect($second->json('sale_id'))->not->toBe($first->json('sale_id'))
        ->and(Sale::count())->toBe(2)
        ->and(Sale::pluck('invoice_number')->unique()->count())->toBe(2)
        ->and((int) $product->fresh()->stock)->toBe(8);
});

it('checks out normally when no idempotency key is sent', function () {
    // Backwards compatibility: an API client or a stale cached page that does
    // not send a key must still be able to sell (it just loses the protection).
    $cashier = idemCashier();
    $product = idemProduct();
    $payload = idemPayload($product, 'ignored');
    unset($payload['idempotency_key']);

    $this->actingAs($cashier)->postJson('/pos/checkout', $payload)->assertOk()->assertJson(['success' => true]);
    $this->actingAs($cashier)->postJson('/pos/checkout', $payload)->assertOk()->assertJson(['success' => true]);

    expect(Sale::count())->toBe(2)
        ->and(Sale::whereNotNull('idempotency_key')->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Concurrency: the unique index is the authority
|--------------------------------------------------------------------------
*/

it('rejects two sales sharing an idempotency key at the database level', function () {
    // This constraint is what makes two SIMULTANEOUS requests safe: they can
    // both pass the lookup, but only one INSERT can survive.
    $user = idemCashier();
    Sale::create([
        'user_id' => $user->id,
        'invoice_number' => '07-21-01',
        'idempotency_key' => 'shared-key',
        'total' => 2500,
        'payment_method' => 'cash',
    ]);

    expect(fn () => Sale::create([
        'user_id' => $user->id,
        'invoice_number' => '07-21-02',
        'idempotency_key' => 'shared-key',
        'total' => 2500,
        'payment_method' => 'cash',
    ]))->toThrow(QueryException::class);

    expect(Sale::where('idempotency_key', 'shared-key')->count())->toBe(1);
});

it('recovers from losing the idempotency race instead of returning a 500', function () {
    // Reproduce the loser's exact interleaving: a concurrent request inserts a
    // row with our key after we passed the lookup but before our own INSERT, so
    // the unique index rejects us mid-transaction.
    $cashier = idemCashier();
    $product = idemProduct();
    $key = 'raced-key';
    $collided = false;

    Sale::creating(function () use (&$collided, $key, $cashier) {
        if ($collided) {
            return; // one-shot: only the first attempt loses the race
        }
        $collided = true;

        DB::table('sales')->insert([
            'user_id' => $cashier->id,
            'invoice_number' => 'RACE-01',
            'idempotency_key' => $key,
            'subtotal' => 2500,
            'total' => 2500,
            'payment_method' => 'cash',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    $resp = $this->actingAs($cashier)->postJson('/pos/checkout', idemPayload($product, $key));

    // Not a 500: the duplicate-key violation is recognised and retried.
    $resp->assertOk()->assertJson(['success' => true]);

    expect($collided)->toBeTrue()
        // The losing transaction rolled back whole — no orphan stock movement.
        ->and(Sale::where('idempotency_key', $key)->count())->toBe(1)
        ->and(Sale::count())->toBe(1)
        ->and((int) $product->fresh()->stock)->toBe(9);
});

it('identifies idempotency-key duplicate errors without swallowing other violations', function () {
    $user = idemCashier();
    Sale::create([
        'user_id' => $user->id,
        'invoice_number' => '07-21-11',
        'idempotency_key' => 'dup-key',
        'total' => 100,
        'payment_method' => 'cash',
    ]);

    try {
        Sale::create([
            'user_id' => $user->id,
            'invoice_number' => '07-21-12',
            'idempotency_key' => 'dup-key',
            'total' => 100,
            'payment_method' => 'cash',
        ]);
        $this->fail('Expected a duplicate-key QueryException on idempotency_key');
    } catch (QueryException $e) {
        expect(CheckoutIdempotencyService::isDuplicateKey($e))->toBeTrue()
            // Must not be mistaken for the invoice-number retry path
            ->and(InvoiceNumberService::isDuplicateInvoiceNumber($e))->toBeFalse();
    }

    // ...and an invoice-number violation must NOT be treated as an idempotency hit
    try {
        Sale::create([
            'user_id' => $user->id,
            'invoice_number' => '07-21-11',
            'total' => 100,
            'payment_method' => 'cash',
        ]);
        $this->fail('Expected a duplicate-key QueryException on invoice_number');
    } catch (QueryException $e) {
        expect(CheckoutIdempotencyService::isDuplicateKey($e))->toBeFalse()
            ->and(InvoiceNumberService::isDuplicateInvoiceNumber($e))->toBeTrue();
    }
});

/*
|--------------------------------------------------------------------------
| A duplicate must not repeat any side effect
|--------------------------------------------------------------------------
*/

it('does not redeem a gift card twice when the checkout is resubmitted', function () {
    $cashier = idemCashier();
    $product = idemProduct();
    $card = GiftCard::factory()->sold()->create(['value' => '2500.00']);
    $key = 'gift-card-double-click';

    $payload = idemPayload($product, $key, [
        'gift_card_redeem' => ['id' => $card->id, 'amount' => 2500],
    ]);

    $first = $this->actingAs($cashier)->postJson('/pos/checkout', $payload)->assertOk();

    $card->refresh();
    $redeemedSaleId = $card->redeemed_sale_id;
    $redeemedAt = $card->redeemed_at;

    // Same click again. Without idempotency this hit the "already redeemed"
    // guard and returned a 500 — or, worse, made a second sale.
    $second = $this->actingAs($cashier)->postJson('/pos/checkout', $payload)
        ->assertOk()
        ->assertJson(['success' => true, 'duplicate' => true]);

    $card->refresh();
    expect($second->json('sale_id'))->toBe($first->json('sale_id'))
        ->and(Sale::count())->toBe(1)
        ->and($card->status)->toBe(GiftCard::STATUS_REDEEMED)
        ->and($card->redeemed_sale_id)->toBe($redeemedSaleId)
        // Redeemed once, at the original moment — not re-stamped.
        ->and($card->redeemed_at->eq($redeemedAt))->toBeTrue();
});

it('does not sell the same gift card twice when the checkout is resubmitted', function () {
    $cashier = idemCashier();
    $card = GiftCard::factory()->create(['value' => '5000.00']); // available
    $key = 'gift-card-sale-double-click';

    $payload = [
        'idempotency_key' => $key,
        'gift_card_sale' => ['id' => $card->id, 'price' => 5000],
        'payment_method' => 'cash',
        'subtotal' => 5000,
        'total' => 5000,
        'amount_received' => 5000,
        'change_amount' => 0,
    ];

    $first = $this->actingAs($cashier)->postJson('/pos/checkout', $payload)->assertOk();
    $second = $this->actingAs($cashier)->postJson('/pos/checkout', $payload)
        ->assertOk()
        ->assertJson(['duplicate' => true]);

    expect($second->json('sale_id'))->toBe($first->json('sale_id'))
        ->and(Sale::count())->toBe(1)
        ->and(GiftCard::whereKey($card->id)->value('status'))->toBe(GiftCard::STATUS_SOLD);
});

it('leaves a resubmitted checkout with at most one bill to SMS', function () {
    // Checkout itself never sends an SMS — the cashier triggers it from the
    // success modal for the returned sale_id. Because the duplicate returns the
    // SAME sale_id, there is only ever one bill that can be sent.
    $cashier = idemCashier();
    $product = idemProduct();
    $key = 'sms-double-click';

    $first = $this->actingAs($cashier)->postJson('/pos/checkout', idemPayload($product, $key))->assertOk();
    $second = $this->actingAs($cashier)->postJson('/pos/checkout', idemPayload($product, $key))->assertOk();

    expect(SmsOutbox::count())->toBe(0)
        ->and($second->json('sale_id'))->toBe($first->json('sale_id'))
        ->and(Sale::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Frontend guard
|--------------------------------------------------------------------------
*/

it('ships the POS page with the double-submit guard and idempotency key', function () {
    $admin = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);

    $html = $this->actingAs($admin)->get('/pos')->getContent();

    expect($html)
        // Re-entrancy flag + the lock applied to every checkout entry point
        ->toContain('let checkoutInFlight = false;')
        ->toContain('function setCheckoutBusy(busy)')
        ->toContain('if (checkoutInFlight) return;')
        ->toContain('Processing...')
        // Key generated per purchase and sent with the request
        ->toContain('function ensureCheckoutKey()')
        ->toContain('idempotency_key: idempotencyKey')
        // Released again once the response (or error) comes back
        ->toContain('setCheckoutBusy(false);');
});
