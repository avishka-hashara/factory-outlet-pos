<?php

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SmsOutbox;
use App\Models\User;

function makeSalesHistoryAdmin(): User
{
    return User::factory()->create(['role' => 'Admin', 'status' => 'Active']);
}

function makeHistorySale(array $overrides = []): Sale
{
    return Sale::create(array_merge([
        'user_id' => User::factory()->create(['status' => 'Active'])->id,
        'invoice_number' => '07-15-01',
        'total' => 12500.00,
        'payment_method' => 'cash',
    ], $overrides));
}

test('send-sms button pre-fills the number the bill was last SMSd to', function () {
    // Customer has a different number — the previously-SMS'd number must win
    $customer = Customer::create(['name' => 'Nimal', 'phone' => '0779999999']);
    $sale = makeHistorySale(['customer_id' => $customer->id]);
    SmsOutbox::create([
        'invoice_id' => $sale->id,
        'purpose' => SmsOutbox::PURPOSE_BILL,
        'to_number' => '94771234567',
        'body' => 'test',
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    $this->actingAs(makeSalesHistoryAdmin())
        ->get('/sales')
        ->assertOk()
        ->assertSee('js-sms-send')
        ->assertSee('data-phone="0771234567"', false);
});

test('send-sms button pre-fills the linked customer number when never SMSd', function () {
    $customer = Customer::create(['name' => 'Kamala', 'phone' => '0777654321']);
    makeHistorySale(['customer_id' => $customer->id]);

    $this->actingAs(makeSalesHistoryAdmin())
        ->get('/sales')
        ->assertOk()
        ->assertSee('data-phone="0777654321"', false);
});

test('send-sms button has an empty pre-fill when the sale has no number anywhere', function () {
    makeHistorySale();

    $this->actingAs(makeSalesHistoryAdmin())
        ->get('/sales')
        ->assertOk()
        ->assertSee('js-sms-send')
        ->assertSee('data-phone=""', false);
});

test('the existing four action buttons are still rendered on each row', function () {
    $sale = makeHistorySale();

    $this->actingAs(makeSalesHistoryAdmin())
        ->get('/sales')
        ->assertOk()
        ->assertSee('js-invoice-preview')                       // View
        ->assertSee('title="Print"', false)                     // Print
        ->assertSee(route('returns.create', $sale), false)      // Return
        ->assertSee('title="Delete Sale"', false);              // Delete
});
