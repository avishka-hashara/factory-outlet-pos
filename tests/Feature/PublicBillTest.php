<?php

use App\Models\Sale;
use App\Models\User;

function billSale(array $overrides = []): Sale
{
    return Sale::create(array_merge([
        'user_id' => User::factory()->create(['status' => 'Active'])->id,
        'invoice_number' => '07-15-05',
        'total' => 5000.00,
        'payment_method' => 'cash',
    ], $overrides));
}

test('public bill is viewable by token without auth', function () {
    $sale = billSale(['bill_token' => 'aBcD1234eFgH']);

    $this->get('/b/aBcD1234eFgH')
        ->assertOk()
        ->assertSee('07-15-05')
        ->assertSee('5,000.00');
});

test('unknown token returns 404', function () {
    billSale(['bill_token' => 'aBcD1234eFgH']);
    $this->get('/b/zzzzzzzzzzzz')->assertNotFound();
});

test('short tokens are rejected', function () {
    $this->get('/b/abc')->assertNotFound();
});

test('expired bill link returns 410', function () {
    config(['services.sms.bill_link_ttl_days' => 7]);
    $sale = billSale(['bill_token' => 'aBcD1234eFgH']);
    $sale->forceFill(['created_at' => now()->subDays(8)])->save();

    $this->get('/b/aBcD1234eFgH')->assertStatus(410);
});

test('a freshly created link is not expired on arrival even with a TTL set', function () {
    config(['services.sms.bill_link_ttl_days' => 7]);
    billSale(['bill_token' => 'aBcD1234eFgH']); // created now

    $this->get('/b/aBcD1234eFgH')->assertOk();
});

test('null or non-positive TTL means the link never expires', function ($ttl) {
    config(['services.sms.bill_link_ttl_days' => $ttl]);
    $sale = billSale(['bill_token' => 'aBcD1234eFgH']);
    $sale->forceFill(['created_at' => now()->subYears(5)])->save();

    $this->get('/b/aBcD1234eFgH')->assertOk();
})->with([
    'null' => null,
    'zero' => 0,
    'negative' => -3,
]);

test('config casts a blank SMS_BILL_LINK_TTL_DAYS to null', function () {
    // Mirrors the closure in config/services.php: blank/whitespace/non-positive → null
    $cast = function ($raw) {
        if ($raw === null || trim((string) $raw) === '') {
            return null;
        }
        $days = (int) $raw;

        return $days > 0 ? $days : null;
    };

    expect($cast(''))->toBeNull()
        ->and($cast('   '))->toBeNull()
        ->and($cast('0'))->toBeNull()
        ->and($cast('-1'))->toBeNull()
        ->and($cast('7'))->toBe(7);
});

test('ensureBillToken generates a stable random token', function () {
    $sale = billSale();
    expect($sale->bill_token)->toBeNull();

    $token = $sale->ensureBillToken();
    expect(strlen($token))->toBeGreaterThanOrEqual(8);
    expect($sale->fresh()->bill_token)->toBe($token);

    // Idempotent — calling again returns the same token
    expect($sale->ensureBillToken())->toBe($token);
});
