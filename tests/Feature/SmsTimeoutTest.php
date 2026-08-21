<?php

use App\Models\Sale;
use App\Models\SmsOutbox;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function timeoutSale(): Sale
{
    // Distinct per call: sales.invoice_number now carries a UNIQUE index
    static $seq = 0;
    $seq++;

    return Sale::create([
        'user_id' => User::factory()->create(['status' => 'Active'])->id,
        'invoice_number' => '07-16-' . str_pad((string) (8 + $seq), 2, '0', STR_PAD_LEFT),
        'total' => 1000.00,
        'payment_method' => 'cash',
    ]);
}

function pendingOutbox(Sale $sale, int $ageSeconds, int $attempts = 0): SmsOutbox
{
    $outbox = SmsOutbox::create([
        'invoice_id' => $sale->id,
        'purpose' => 'bill',
        'to_number' => '94771234567',
        'body' => 'test',
        'status' => 'pending',
        'attempts' => $attempts,
    ]);

    DB::table('sms_outbox')->where('id', $outbox->id)
        ->update(['created_at' => now()->subSeconds($ageSeconds)]);

    return $outbox->fresh();
}

test('a fresh pending row is not timed out', function () {
    $outbox = pendingOutbox(timeoutSale(), 5);
    expect($outbox->isTimedOut())->toBeFalse();
});

test('a job still waiting on the cron-driven queue is not called failed', function () {
    // The live queue is drained by cron, so ~60s with no attempt recorded is
    // normal, not a failure. This is the false-failure regression.
    $sale = timeoutSale();
    pendingOutbox($sale, 60, attempts: 0);

    $cashier = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);

    $this->actingAs($cashier)
        ->getJson("/api/invoices/{$sale->id}/sms-status")
        ->assertOk()
        ->assertJson(['status' => 'pending', 'error' => null]);

    $this->assertDatabaseHas('sms_outbox', ['invoice_id' => $sale->id, 'status' => 'pending']);
});

test('the worker-stall window outlives the worst-case cron queue pickup', function () {
    expect(SmsOutbox::WORKER_STALL_SECONDS)->toBeGreaterThan(60)
        ->and(SmsOutbox::HARD_TIMEOUT_SECONDS)->toBeGreaterThan(SmsOutbox::WORKER_STALL_SECONDS);
});

test('pending with no attempt past the worker-stall window is timed out', function () {
    $outbox = pendingOutbox(timeoutSale(), SmsOutbox::WORKER_STALL_SECONDS + 5, attempts: 0);
    expect($outbox->isTimedOut())->toBeTrue();
});

test('pending mid-retry is not timed out until the hard ceiling', function () {
    // attempts>0 means a worker is active — hold off until the hard ceiling
    $inRetry = pendingOutbox(timeoutSale(), SmsOutbox::WORKER_STALL_SECONDS + 5, attempts: 1);
    expect($inRetry->isTimedOut())->toBeFalse();

    $wayOver = pendingOutbox(timeoutSale(), SmsOutbox::HARD_TIMEOUT_SECONDS + 5, attempts: 1);
    expect($wayOver->isTimedOut())->toBeTrue();
});

test('status endpoint flips a stuck pending row to failed with a clear reason', function () {
    $sale = timeoutSale();
    pendingOutbox($sale, SmsOutbox::WORKER_STALL_SECONDS + 5, attempts: 0);

    $cashier = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);

    $res = $this->actingAs($cashier)
        ->getJson("/api/invoices/{$sale->id}/sms-status")
        ->assertOk()
        ->assertJson(['status' => 'failed']);

    expect($res->json('error'))->toContain('worker may not be running');
    $this->assertDatabaseHas('sms_outbox', ['invoice_id' => $sale->id, 'status' => 'failed']);
});

test('resolveIfTimedOut leaves a fresh row untouched', function () {
    $outbox = pendingOutbox(timeoutSale(), 5);
    expect($outbox->resolveIfTimedOut())->toBeFalse()
        ->and($outbox->fresh()->status)->toBe('pending');
});
