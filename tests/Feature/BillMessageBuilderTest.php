<?php

use App\Services\Sms\BillMessageBuilder;
use Illuminate\Support\Facades\Log;

const BILL_URL = 'https://pos.example/b/aBcD1234eFgH';
const CLOSING = 'Wishing you many memorable moments with your new jewelry.';

function buildBill(array $o = []): string
{
    return BillMessageBuilder::build(
        invoiceNo: $o['invoiceNo'] ?? '07-16-01',
        discount: $o['discount'] ?? 0.0,
        total: $o['total'] ?? 12500.00,
        billUrl: $o['billUrl'] ?? BILL_URL,
        shopName: $o['shopName'] ?? 'Glamon Jewellers',
    );
}

/* ── Exact layout ───────────────────────────────────────────────────── */

test('exact layout with a discount, blank lines included', function () {
    $message = buildBill(['discount' => 2500.00, 'total' => 12500.00]);

    expect($message)->toBe(
        "Thank you for your purchase at Glamon Jewellers.\n" .
        "\n" .
        "Bill: #07-16-01\n" .
        "Total Paid: Rs. 12,500.00\n" .
        "You saved: Rs. 2,500.00\n" .
        "\n" .
        "Download your invoice:\n" .
        BILL_URL . "\n" .
        "\n" .
        CLOSING
    );
});

test('exact layout without a discount, blank lines included', function () {
    $message = buildBill(['discount' => 0.0, 'total' => 12500.00]);

    expect($message)->toBe(
        "Thank you for your purchase at Glamon Jewellers.\n" .
        "\n" .
        "Bill: #07-16-01\n" .
        "Total Paid: Rs. 12,500.00\n" .
        "\n" .
        "Download your invoice:\n" .
        BILL_URL . "\n" .
        "\n" .
        CLOSING
    );
});

test('no discount leaves no orphaned blank line where the saved line would be', function () {
    $message = buildBill(['discount' => 0.0]);

    expect($message)->not->toContain('You saved')
        // Total Paid is followed by exactly one blank line, then the next block
        ->and($message)->toContain("Total Paid: Rs. 12,500.00\n\nDownload your invoice:")
        // never three newlines in a row anywhere
        ->and($message)->not->toContain("\n\n\n");
});

test('the discount line sits directly after Total Paid', function () {
    $message = buildBill(['discount' => 2500.00]);

    expect($message)->toContain("Total Paid: Rs. 12,500.00\nYou saved: Rs. 2,500.00\n\nDownload your invoice:")
        ->and($message)->not->toContain("\n\n\n");
});

test('shop name defaults to APP_NAME when not supplied', function () {
    config(['app.name' => 'Acme Gold']);

    $message = BillMessageBuilder::build(
        invoiceNo: '1',
        discount: 0.0,
        total: 100.0,
        billUrl: BILL_URL,
    );

    expect($message)->toStartWith("Thank you for your purchase at Acme Gold.\n\n");
});

test('money is formatted with thousands separators and 2 decimals up to 7 figures', function () {
    $message = buildBill(['discount' => 250000.00, 'total' => 1250000.00]);

    expect($message)->toContain('Total Paid: Rs. 1,250,000.00')
        ->and($message)->toContain('You saved: Rs. 250,000.00');
});

/* ── Budget / segments ──────────────────────────────────────────────── */

test('segment boundary maths', function () {
    expect(BillMessageBuilder::segmentsFor(0))->toBe(0)
        ->and(BillMessageBuilder::segmentsFor(160))->toBe(1)
        ->and(BillMessageBuilder::segmentsFor(161))->toBe(2)
        ->and(BillMessageBuilder::segmentsFor(306))->toBe(2)
        ->and(BillMessageBuilder::segmentsFor(307))->toBe(3);
});

test('character budget per segment count', function () {
    expect(BillMessageBuilder::maxChars(1))->toBe(160)
        ->and(BillMessageBuilder::maxChars(2))->toBe(306)
        ->and(BillMessageBuilder::maxChars(3))->toBe(459)
        ->and(BillMessageBuilder::maxChars(0))->toBe(160); // clamped to >= 1 segment
});

test('the standard message fits 2 segments and is never trimmed', function ($discount) {
    Log::spy();
    config(['services.sms.max_segments' => 2]);

    $message = buildBill(['discount' => $discount]);

    expect(BillMessageBuilder::segmentsFor(strlen($message)))->toBe(2)
        ->and($message)->toEndWith(CLOSING); // nothing trimmed
    Log::shouldNotHaveReceived('warning');
})->with([
    'without discount' => 0.0,
    'with discount' => 2500.00,
]);

test('a 7-figure discounted sale stays within 2 segments and is not trimmed', function () {
    Log::spy();
    config(['services.sms.max_segments' => 2]);

    $message = buildBill(['discount' => 250000.00, 'total' => 1250000.00]);

    expect(BillMessageBuilder::segmentsFor(strlen($message)))->toBeLessThanOrEqual(2)
        ->and(strlen($message))->toBeLessThanOrEqual(306)
        ->and($message)->toEndWith(CLOSING)
        ->and($message)->toContain('You saved: Rs. 250,000.00');
    Log::shouldNotHaveReceived('warning');
});

/* ── GSM-7 protection ───────────────────────────────────────────────── */

test('emoji and Sinhala/Tamil input is stripped, never falling to Unicode', function () {
    config(['services.sms.max_segments' => 2]);

    $message = buildBill([
        'shopName' => 'Café “Gold” ✨ රත්නපුර நகை ~ {Jewels}',
        'discount' => 2500.00,
    ]);

    // Pure single-septet GSM-7 only — anything else would flip the SMS to UCS-2.
    expect($message)->toMatch('/^[A-Za-z0-9 \n@$_!"#%&\'()*+,\-.\/:;<=>?]*$/');
    // No multibyte survived: septet count == byte count.
    expect(mb_strlen($message))->toBe(strlen($message));
});

test('a newline in the shop name cannot corrupt the layout', function () {
    $message = buildBill(['shopName' => "Glamon\nJewellers"]);

    expect($message)->toStartWith("Thank you for your purchase at Glamon Jewellers.\n\n")
        ->and($message)->not->toContain("\n\n\n");
});

/* ── Trimming when over the ceiling ─────────────────────────────────── */

test('a message exceeding SMS_MAX_SEGMENTS is trimmed gracefully and warns', function () {
    Log::spy();
    config(['services.sms.max_segments' => 1]); // 160-char ceiling

    $message = buildBill([
        'invoiceNo' => '07-16-05',
        'discount' => 2500.00,
        'total' => 999.99,
    ]);

    // Trimmed to fit one segment...
    expect(strlen($message))->toBeLessThanOrEqual(160)
        ->and(BillMessageBuilder::segmentsFor(strlen($message)))->toBe(1)
        // ...but the essentials survive
        ->and($message)->toContain('Bill: #07-16-05')
        ->and($message)->toContain('Total Paid: Rs. 999.99')
        ->and($message)->toContain('You saved: Rs. 2,500.00')
        ->and($message)->toContain(BILL_URL);

    Log::shouldHaveReceived('warning')->once();
});

test('the trim warning reports actual char and segment counts', function () {
    $captured = null;
    Log::shouldReceive('warning')->once()->andReturnUsing(function ($msg, $ctx) use (&$captured) {
        $captured = $ctx;
    });
    config(['services.sms.max_segments' => 1]);

    buildBill(['discount' => 2500.00]);

    expect($captured)->toHaveKeys(['original_chars', 'original_segments', 'max_segments', 'max_chars', 'final_chars', 'final_segments'])
        ->and($captured['original_chars'])->toBeGreaterThan(160)
        ->and($captured['original_segments'])->toBeGreaterThan(1)
        ->and($captured['max_segments'])->toBe(1)
        ->and($captured['max_chars'])->toBe(160)
        ->and($captured['final_chars'])->toBeLessThanOrEqual(160);
});
