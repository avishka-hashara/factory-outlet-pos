<?php

use App\Models\GiftCard;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

function gcAdmin(): User
{
    return User::factory()->create(['role' => 'Admin', 'status' => 'Active']);
}

function gcCashier(): User
{
    // Staff with no permission role: defaults to POS + Dashboard access only
    return User::factory()->create(['role' => 'Staff', 'status' => 'Active']);
}

function gcProduct(string $price = '10000', int $stock = 10): Product
{
    return Product::create([
        'name'  => 'Test Chain',
        'sku'   => 'T' . uniqid(),
        'price' => $price,
        'stock' => $stock,
    ]);
}

function gcCheckoutPayload(Product $product, array $overrides = []): array
{
    return array_merge([
        'items' => [[
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity'   => 1,
            'price'      => (float) $product->price,
        ]],
        'payment_method'  => 'cash',
        'subtotal'        => (float) $product->price,
        'total'           => (float) $product->price,
        'amount_received' => (float) $product->price,
        'change_amount'   => 0,
    ], $overrides);
}

function gcBareSale(User $user): Sale
{
    return Sale::create([
        'user_id'        => $user->id,
        'total'          => 10000,
        'payment_method' => 'cash',
    ]);
}

/*
|--------------------------------------------------------------------------
| Redemption: race safety & state guards
|--------------------------------------------------------------------------
*/

it('lets only one of two simultaneous redeems of the same card succeed', function () {
    $user = gcCashier();
    $card = GiftCard::factory()->sold()->create();

    // Two terminals read the same card before either redeems (stale reads)
    $terminalA = GiftCard::find($card->id);
    $terminalB = GiftCard::find($card->id);

    $saleA = gcBareSale($user);
    $saleB = gcBareSale($user);

    expect($terminalA->redeemForSale($saleA))->toBeTrue();
    // Terminal B still believes the card is 'sold', but the guarded UPDATE
    // matches zero rows.
    expect($terminalB->redeemForSale($saleB))->toBeFalse();

    $card->refresh();
    expect($card->status)->toBe(GiftCard::STATUS_REDEEMED)
        ->and($card->redeemed_sale_id)->toBe($saleA->id);
});

it('rejects a double redeem across two checkout requests', function () {
    $cashier = gcCashier();
    $card = GiftCard::factory()->sold()->create(['value' => '5000.00']);
    $p1 = gcProduct('10000');
    $p2 = gcProduct('10000');

    $redeem = ['gift_card_redeem' => ['id' => $card->id, 'amount' => 5000], 'total' => 5000, 'amount_received' => 5000];

    $this->actingAs($cashier)
        ->postJson('/pos/checkout', gcCheckoutPayload($p1, $redeem))
        ->assertOk()
        ->assertJson(['success' => true]);

    $firstSaleId = $card->fresh()->redeemed_sale_id;

    $this->actingAs($cashier)
        ->postJson('/pos/checkout', gcCheckoutPayload($p2, $redeem))
        ->assertStatus(500)
        ->assertJsonFragment(['success' => false]);

    expect(Sale::count())->toBe(1)
        ->and($card->fresh()->redeemed_sale_id)->toBe($firstSaleId)
        ->and((int) $p2->fresh()->stock)->toBe(10); // second checkout fully rolled back
});

it('rejects an available (not yet issued) card at redemption', function () {
    $cashier = gcCashier();
    $card = GiftCard::factory()->create(['value' => '5000.00']); // available
    $product = gcProduct('10000');

    $resp = $this->actingAs($cashier)->postJson('/pos/checkout', gcCheckoutPayload($product, [
        'gift_card_redeem' => ['id' => $card->id, 'amount' => 5000],
        'total' => 5000,
    ]));

    $resp->assertStatus(500);
    expect($resp->json('message'))->toContain('not been issued');
    expect(Sale::count())->toBe(0)
        ->and($card->fresh()->status)->toBe(GiftCard::STATUS_AVAILABLE)
        ->and((int) $product->fresh()->stock)->toBe(10);
});

it('redeems sold and gifted cards', function (string $factoryState) {
    $cashier = gcCashier();
    $card = GiftCard::factory()->{$factoryState}()->create(['value' => '5000.00']);
    $product = gcProduct('10000');

    $this->actingAs($cashier)->postJson('/pos/checkout', gcCheckoutPayload($product, [
        'gift_card_redeem' => ['id' => $card->id, 'amount' => 5000],
        'total' => 5000,
        'amount_received' => 5000,
    ]))->assertOk()->assertJson(['success' => true]);

    $card->refresh();
    $sale = Sale::first();
    expect($card->status)->toBe(GiftCard::STATUS_REDEEMED)
        ->and($card->redeemed_sale_id)->toBe($sale->id)
        ->and($card->redeemed_at)->not->toBeNull()
        ->and((string) $sale->gift_card_redemption_amount)->toBe('5000.00')
        ->and((float) $sale->total)->toBe(5000.0);
})->with(['sold', 'gifted']);

it('never expires a card with a null expiry_date', function () {
    $cashier = gcCashier();
    $card = GiftCard::factory()->sold()->create([
        'value'       => '5000.00',
        'issued_date' => '2020-01-01',
        'issued_at'   => '2020-01-01 10:00:00',
        'expiry_date' => null,
    ]);
    $product = gcProduct('10000');

    $this->actingAs($cashier)->postJson('/pos/checkout', gcCheckoutPayload($product, [
        'gift_card_redeem' => ['id' => $card->id, 'amount' => 5000],
        'total' => 5000,
    ]))->assertOk()->assertJson(['success' => true]);

    expect($card->fresh()->status)->toBe(GiftCard::STATUS_REDEEMED);
});

it('rejects an expired card at redemption', function () {
    $cashier = gcCashier();
    $card = GiftCard::factory()->sold()->create([
        'value'       => '5000.00',
        'expiry_date' => now()->subDay()->toDateString(),
    ]);
    $product = gcProduct('10000');

    $resp = $this->actingAs($cashier)->postJson('/pos/checkout', gcCheckoutPayload($product, [
        'gift_card_redeem' => ['id' => $card->id, 'amount' => 5000],
        'total' => 5000,
    ]));

    $resp->assertStatus(500);
    expect($resp->json('message'))->toContain('expired');
    expect($card->fresh()->status)->toBe(GiftCard::STATUS_SOLD)
        ->and(Sale::count())->toBe(0);
});

it('accepts a card expiring today (expiry_date >= today)', function () {
    $user = gcCashier();
    $card = GiftCard::factory()->sold()->create(['expiry_date' => now()->toDateString()]);

    expect($card->redeemForSale(gcBareSale($user)))->toBeTrue();
});

it('rejects an already redeemed card', function () {
    $cashier = gcCashier();
    $card = GiftCard::factory()->redeemed()->create(['value' => '5000.00']);
    $product = gcProduct('10000');

    $resp = $this->actingAs($cashier)->postJson('/pos/checkout', gcCheckoutPayload($product, [
        'gift_card_redeem' => ['id' => $card->id, 'amount' => 5000],
        'total' => 5000,
    ]));

    $resp->assertStatus(500);
    expect($resp->json('message'))->toContain('already been redeemed');
    expect(Sale::count())->toBe(0);
});

it('rolls the redemption back when the sale fails to save', function () {
    $user = gcCashier();
    $card = GiftCard::factory()->sold()->create();

    try {
        DB::transaction(function () use ($card, $user) {
            $sale = gcBareSale($user);
            expect($card->redeemForSale($sale))->toBeTrue();
            throw new RuntimeException('sale failed to save');
        });
    } catch (RuntimeException) {
        // expected
    }

    $card->refresh();
    expect($card->status)->toBe(GiftCard::STATUS_SOLD)
        ->and($card->redeemed_at)->toBeNull()
        ->and($card->redeemed_sale_id)->toBeNull();
});

it('rejects a redemption amount that does not match the card value', function () {
    $cashier = gcCashier();
    $card = GiftCard::factory()->sold()->create(['value' => '5000.00']);
    $product = gcProduct('10000');

    $resp = $this->actingAs($cashier)->postJson('/pos/checkout', gcCheckoutPayload($product, [
        'gift_card_redeem' => ['id' => $card->id, 'amount' => 4000],
        'total' => 6000,
    ]));

    $resp->assertStatus(500);
    expect($card->fresh()->status)->toBe(GiftCard::STATUS_SOLD)
        ->and(Sale::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Issuing: gift (free) and sell (revenue)
|--------------------------------------------------------------------------
*/

it('marks a card gifted with no bill and no revenue', function () {
    $card = GiftCard::factory()->create();

    $this->actingAs(gcAdmin())
        ->post("/gift-cards/{$card->id}/gift")
        ->assertSessionHas('success');

    $card->refresh();
    expect($card->status)->toBe(GiftCard::STATUS_GIFTED)
        ->and($card->issued_at)->not->toBeNull()
        ->and($card->issued_sale_id)->toBeNull()
        ->and(Sale::count())->toBe(0);
});

it('marks a card sold with revenue only when checkout completes', function () {
    $card = GiftCard::factory()->create(['value' => '5000.00']);
    $admin = gcAdmin();

    // Sell action only opens the POS with the card bound — no state change
    $this->actingAs($admin)
        ->post("/gift-cards/{$card->id}/sell")
        ->assertRedirect(route('pos.index', ['sell_gift_card' => $card->id]));
    expect($card->fresh()->status)->toBe(GiftCard::STATUS_AVAILABLE);

    // Checkout completes: card becomes sold, its value is normal revenue
    $this->actingAs($admin)->postJson('/pos/checkout', [
        'gift_card_sale'  => ['id' => $card->id, 'price' => 5000],
        'payment_method'  => 'cash',
        'subtotal'        => 5000,
        'total'           => 5000,
        'amount_received' => 5000,
        'change_amount'   => 0,
    ])->assertOk()->assertJson(['success' => true]);

    $card->refresh();
    $sale = Sale::first();
    $item = SaleItem::first();
    expect($card->status)->toBe(GiftCard::STATUS_SOLD)
        ->and($card->issued_sale_id)->toBe($sale->id)
        ->and($card->issued_at)->not->toBeNull()
        ->and((float) Sale::sum('total'))->toBe(5000.0)
        ->and($item->product_id)->toBeNull()
        ->and($item->gift_card_id)->toBe($card->id)
        ->and($item->item_label)->toBe('5,000 Gift Card')
        ->and((float) $item->price)->toBe(5000.0);
});

it('leaves the card available when a sell checkout is abandoned or fails', function () {
    $card = GiftCard::factory()->create(['value' => '5000.00']);
    $admin = gcAdmin();

    // Abandoned: POS opened via sell, checkout never happens
    $this->actingAs($admin)->post("/gift-cards/{$card->id}/sell");
    expect($card->fresh()->status)->toBe(GiftCard::STATUS_AVAILABLE);

    // Failed checkout (price tampered / stale): whole transaction rolls back
    $this->actingAs($admin)->postJson('/pos/checkout', [
        'gift_card_sale'  => ['id' => $card->id, 'price' => 1],
        'payment_method'  => 'cash',
        'subtotal'        => 1,
        'total'           => 1,
    ])->assertStatus(500);

    expect($card->fresh()->status)->toBe(GiftCard::STATUS_AVAILABLE)
        ->and($card->fresh()->issued_sale_id)->toBeNull()
        ->and(Sale::count())->toBe(0)
        ->and(SaleItem::count())->toBe(0);
});

it('cannot sell or gift a card that is already sold, gifted, or redeemed', function (string $state) {
    $admin = gcAdmin();
    $card = GiftCard::factory()->{$state}()->create();
    $before = $card->fresh()->toArray();

    $this->actingAs($admin)->post("/gift-cards/{$card->id}/gift")->assertSessionHas('error');
    $this->actingAs($admin)->post("/gift-cards/{$card->id}/sell")->assertSessionHas('error');

    // Selling through checkout is also blocked and rolls back
    $this->actingAs($admin)->postJson('/pos/checkout', [
        'gift_card_sale' => ['id' => $card->id, 'price' => (float) $card->value],
        'payment_method' => 'cash',
        'subtotal' => (float) $card->value,
        'total' => (float) $card->value,
    ])->assertStatus(500);

    expect($card->fresh()->status)->toBe($before['status'])
        ->and(Sale::count())->toBe(0);
})->with(['sold', 'gifted', 'redeemed']);

/*
|--------------------------------------------------------------------------
| Bulk import
|--------------------------------------------------------------------------
*/

it('imports valid rows and reports rejected rows with reasons', function () {
    GiftCard::factory()->create(['card_number' => 'GCEXIST', 'barcode' => 'BEXIST']);

    $csv = implode("\r\n", [
        'card_number,barcode,value,issued_date,expiry_date',
        'GC001,B001,5000,,',                 // valid
        'GC002,,3000,,',                     // missing barcode
        'GC003,B003,abc,,',                  // non-numeric value
        'GC001,B004,2000,,',                 // duplicate card_number within file
        'GC005,B005,1000,2026-13-45,',       // bad date format
        'GC006,B006,2500.50,,2027-01-31',    // valid
        'GCEXIST,B007,900,,',                // duplicate against DB
        '',
    ]);

    $resp = $this->actingAs(gcAdmin())->post('/gift-cards/import', [
        'file' => UploadedFile::fake()->createWithContent('cards.csv', $csv),
    ]);

    $resp->assertSessionHas('gc_import_report');
    $report = session('gc_import_report');

    expect($report['imported'])->toBe(2)
        ->and(count($report['rejected']))->toBe(5)
        ->and(GiftCard::count())->toBe(3); // 1 pre-existing + 2 imported

    $reasons = collect($report['rejected'])->mapWithKeys(fn ($r) => [$r['row'] => implode('; ', $r['reasons'])]);
    expect($reasons[3])->toContain('missing barcode')
        ->and($reasons[4])->toContain('non-numeric value')
        ->and($reasons[5])->toContain('duplicate card_number within file')
        ->and($reasons[6])->toContain('bad issued_date format')
        ->and($reasons[8])->toContain('already exists');

    $imported = GiftCard::where('card_number', 'GC006')->first();
    expect($imported->status)->toBe(GiftCard::STATUS_AVAILABLE)
        ->and((string) $imported->value)->toBe('2500.50')
        ->and($imported->expiry_date->toDateString())->toBe('2027-01-31');
});

/*
|--------------------------------------------------------------------------
| Card creation & uniqueness
|--------------------------------------------------------------------------
*/

it('adds a single card and enforces card_number and barcode uniqueness', function () {
    $admin = gcAdmin();

    $this->actingAs($admin)->post('/gift-cards', [
        'card_number' => 'GC900',
        'barcode'     => 'B900',
        'value'       => '7500.50',
    ])->assertSessionHas('success');

    $card = GiftCard::where('card_number', 'GC900')->first();
    expect($card)->not->toBeNull()
        ->and($card->status)->toBe(GiftCard::STATUS_AVAILABLE)
        ->and((string) $card->value)->toBe('7500.50')
        ->and($card->expiry_date)->toBeNull();

    $this->actingAs($admin)->post('/gift-cards', [
        'card_number' => 'GC900',
        'barcode'     => 'B901',
        'value'       => '1000',
    ])->assertSessionHasErrors('card_number');

    $this->actingAs($admin)->post('/gift-cards', [
        'card_number' => 'GC901',
        'barcode'     => 'B900',
        'value'       => '1000',
    ])->assertSessionHasErrors('barcode');

    expect(GiftCard::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| POS page, lookup & permissions
|--------------------------------------------------------------------------
*/

it('renders the redeem button disabled on an empty cart', function () {
    $this->actingAs(gcAdmin())
        ->get('/pos')
        ->assertOk()
        ->assertSee('id="pay-btn-giftcard"', false)
        ->assertSee('onclick="openGiftCardRedeemModal()" disabled', false);
});

it('pre-binds an available card into the POS cart for the sell flow', function () {
    $card = GiftCard::factory()->create(['card_number' => 'GC555', 'value' => '5000.00']);

    $this->actingAs(gcAdmin())
        ->get('/pos?sell_gift_card=' . $card->id)
        ->assertOk()
        ->assertSee('gc_sale_' . $card->id, false)
        ->assertSee('GC555');

    // A non-available card is never pre-bound
    $card->update(['status' => GiftCard::STATUS_SOLD]);
    $this->actingAs(gcAdmin())
        ->get('/pos?sell_gift_card=' . $card->id)
        ->assertOk()
        ->assertDontSee('gc_sale_' . $card->id, false);
});

it('reports live card status through the POS lookup', function () {
    $cashier = gcCashier();

    $available = GiftCard::factory()->create(['card_number' => 'GCA', 'barcode' => 'BA1']);
    $expired = GiftCard::factory()->sold()->create(['card_number' => 'GCE', 'barcode' => 'BE1', 'expiry_date' => now()->subDay()->toDateString()]);
    $ready = GiftCard::factory()->gifted()->create(['card_number' => 'GCR', 'barcode' => 'BR1']);

    $this->actingAs($cashier)->getJson('/pos/gift-card-lookup?code=GCA')
        ->assertOk()->assertJsonFragment(['state' => 'not_issued']);

    // Lookup works by barcode too
    $this->actingAs($cashier)->getJson('/pos/gift-card-lookup?code=BE1')
        ->assertOk()->assertJsonFragment(['state' => 'expired']);

    $this->actingAs($cashier)->getJson('/pos/gift-card-lookup?code=GCR')
        ->assertOk()->assertJsonFragment(['state' => 'ready', 'live_status' => 'gifted']);

    $this->actingAs($cashier)->getJson('/pos/gift-card-lookup?code=NOPE')
        ->assertOk()->assertJsonFragment(['state' => 'not_found', 'found' => false]);
});

it('restricts gift card management to users with the gift_cards permission', function () {
    $cashier = gcCashier();
    $card = GiftCard::factory()->create();

    $this->actingAs($cashier)->get('/gift-cards')->assertStatus(403);
    $this->actingAs($cashier)->post("/gift-cards/{$card->id}/gift")->assertStatus(403);
    $this->actingAs($cashier)->post('/gift-cards', [
        'card_number' => 'GCX', 'barcode' => 'BX', 'value' => '1000',
    ])->assertStatus(403);

    // But cashiers can look cards up (and redeem via checkout)
    $this->actingAs($cashier)->getJson('/pos/gift-card-lookup?code=' . $card->card_number)->assertOk();

    // Admin sees the management page
    $this->actingAs(gcAdmin())->get('/gift-cards')->assertOk()->assertSee('Gift Cards');
});
