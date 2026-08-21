<?php

use App\Jobs\SendInvoiceSms;
use App\Models\Sale;
use App\Models\SmsOutbox;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function makeCashier(): User
{
    return User::factory()->create(['role' => 'Admin', 'status' => 'Active']);
}

function makeSale(array $overrides = []): Sale
{
    return Sale::create(array_merge([
        'user_id' => User::factory()->create(['status' => 'Active'])->id,
        'invoice_number' => '07-15-01',
        'total' => 12500.00,
        'payment_method' => 'cash',
    ], $overrides));
}

beforeEach(function () {
    config(['services.sms.driver' => 'log']);
});

test('cashier can queue a bill SMS and it is idempotent', function () {
    Queue::fake();
    $sale = makeSale();

    $res = $this->actingAs(makeCashier())
        ->postJson("/api/invoices/{$sale->id}/send-sms", ['phone' => '0771234567']);

    $res->assertOk()->assertJson(['success' => true, 'status' => 'pending']);
    $this->assertDatabaseHas('sms_outbox', [
        'invoice_id' => $sale->id,
        'purpose' => 'bill',
        'to_number' => '94771234567',
        'status' => 'pending',
    ]);
    Queue::assertPushed(SendInvoiceSms::class, 1);

    // Second click while still pending → no second job, still one row
    $this->actingAs(makeCashier())
        ->postJson("/api/invoices/{$sale->id}/send-sms", ['phone' => '0771234567'])
        ->assertOk();

    expect(SmsOutbox::where('invoice_id', $sale->id)->count())->toBe(1);
    Queue::assertPushed(SendInvoiceSms::class, 1);
});

test('an already-sent bill is not re-queued without the resend flag', function () {
    Queue::fake();
    $sale = makeSale();
    SmsOutbox::create([
        'invoice_id' => $sale->id,
        'purpose' => 'bill',
        'to_number' => '94771234567',
        'body' => 'test',
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    $this->actingAs(makeCashier())
        ->postJson("/api/invoices/{$sale->id}/send-sms", ['phone' => '0771234567'])
        ->assertOk()
        ->assertJson(['success' => true, 'status' => 'sent']);

    Queue::assertNothingPushed();
});

test('resend flag re-queues an already-sent bill (Sales History re-send)', function () {
    Queue::fake();
    $sale = makeSale();
    SmsOutbox::create([
        'invoice_id' => $sale->id,
        'purpose' => 'bill',
        'to_number' => '94771234567',
        'body' => 'test',
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    $this->actingAs(makeCashier())
        ->postJson("/api/invoices/{$sale->id}/send-sms", ['phone' => '0771234567', 'resend' => true])
        ->assertOk()
        ->assertJson(['success' => true, 'status' => 'pending']);

    // Same row reset — never a second outbox row per invoice
    expect(SmsOutbox::where('invoice_id', $sale->id)->count())->toBe(1);
    $outbox = SmsOutbox::where('invoice_id', $sale->id)->first();
    expect($outbox->status)->toBe('pending');
    expect($outbox->sent_at)->toBeNull();
    Queue::assertPushed(SendInvoiceSms::class, 1);
});

test('resend flag does not bypass the in-flight guard', function () {
    Queue::fake();
    $sale = makeSale();

    $this->actingAs(makeCashier())
        ->postJson("/api/invoices/{$sale->id}/send-sms", ['phone' => '0771234567', 'resend' => true])
        ->assertOk();

    // Second click while the first send is still pending → no second job
    $this->actingAs(makeCashier())
        ->postJson("/api/invoices/{$sale->id}/send-sms", ['phone' => '0771234567', 'resend' => true])
        ->assertOk()
        ->assertJson(['message' => 'Send already in progress.']);

    Queue::assertPushed(SendInvoiceSms::class, 1);
});

test('dry-run log driver marks the row sent without hitting the gateway', function () {
    Http::fake(); // any HTTP call would be recorded
    $sale = makeSale();

    $this->actingAs(makeCashier())
        ->postJson("/api/invoices/{$sale->id}/send-sms", ['phone' => '0771234567'])
        ->assertOk();

    // sync queue ran the job inline
    $outbox = SmsOutbox::where('invoice_id', $sale->id)->first();
    expect($outbox->status)->toBe('sent');
    expect($outbox->provider_response['dry_run'])->toBeTrue();
    Http::assertNothingSent();
});

test('invalid mobile number is rejected before queueing', function () {
    Queue::fake();
    $sale = makeSale();

    $this->actingAs(makeCashier())
        ->postJson("/api/invoices/{$sale->id}/send-sms", ['phone' => '0112345678'])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    $this->assertDatabaseCount('sms_outbox', 0);
    Queue::assertNothingPushed();
});

test('falls back to the customer mobile when no phone is posted', function () {
    Queue::fake();
    $customer = \App\Models\Customer::create(['name' => 'Nimal', 'phone' => '0777654321']);
    $sale = makeSale(['customer_id' => $customer->id]);

    $this->actingAs(makeCashier())
        ->postJson("/api/invoices/{$sale->id}/send-sms", [])
        ->assertOk();

    $this->assertDatabaseHas('sms_outbox', [
        'invoice_id' => $sale->id,
        'to_number' => '94777654321',
    ]);
});

test('returns 422 when neither a phone nor a customer number is available', function () {
    $sale = makeSale();

    $this->actingAs(makeCashier())
        ->postJson("/api/invoices/{$sale->id}/send-sms", [])
        ->assertStatus(422);
});

test('a user without POS permission is forbidden', function () {
    $sale = makeSale();
    $noPerm = User::factory()->create(['role' => 'Staff', 'status' => 'Active', 'role_id' => null]);

    // Staff without a role default to dashboard + pos only... so grant a role without pos
    $role = \App\Models\Role::create(['name' => 'Stock', 'permissions' => ['products']]);
    $noPerm->update(['role_id' => $role->id]);

    $this->actingAs($noPerm)
        ->postJson("/api/invoices/{$sale->id}/send-sms", ['phone' => '0771234567'])
        ->assertStatus(403);
});

test('guests are redirected to login', function () {
    $sale = makeSale();
    $this->postJson("/api/invoices/{$sale->id}/send-sms", ['phone' => '0771234567'])
        ->assertStatus(401);
});

test('job records provider response and marks failed permanently on an invalid key', function () {
    config(['services.sms.driver' => 'dialog', 'services.sms.key' => 'k', 'services.sms.mask' => 'Glamon', 'services.sms.url' => 'https://sms.test/api']);
    Http::fake([
        // Observed live: invalid URL Message Key → "2007|0"
        'sms.test/*' => Http::response('2007|0', 200),
    ]);

    $sale = makeSale();
    $outbox = SmsOutbox::create([
        'invoice_id' => $sale->id,
        'purpose' => 'bill',
        'to_number' => '94771234567',
        'body' => 'test',
        'status' => 'pending',
    ]);

    try {
        (new SendInvoiceSms($outbox->id))->handle(app(\App\Services\Sms\SmsGateway::class));
    } catch (\Throwable $e) {
        // permanent failure calls $this->fail() which re-throws in this direct-call context
    }

    $outbox->refresh();
    expect($outbox->status)->toBe('failed');
    expect($outbox->error)->toContain('2007');
    expect($outbox->provider_response['code'])->toBe('2007');
});

test('job marks sent on a successful gateway response', function () {
    config(['services.sms.driver' => 'dialog', 'services.sms.key' => 'k', 'services.sms.mask' => 'Glamon', 'services.sms.url' => 'https://sms.test/api']);
    Http::fake([
        // Success is a plain-text "1" body
        'sms.test/*' => Http::response('1', 200),
    ]);

    $sale = makeSale();
    $outbox = SmsOutbox::create([
        'invoice_id' => $sale->id,
        'purpose' => 'bill',
        'to_number' => '94771234567',
        'body' => 'test',
        'status' => 'pending',
    ]);

    (new SendInvoiceSms($outbox->id))->handle(app(\App\Services\Sms\SmsGateway::class));

    $outbox->refresh();
    expect($outbox->status)->toBe('sent');
    expect($outbox->provider_response['code'])->toBe('1');
    expect($outbox->sent_at)->not->toBeNull();
});
