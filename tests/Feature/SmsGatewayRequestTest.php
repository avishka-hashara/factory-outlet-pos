<?php

use App\Services\Sms\SmsGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Contract tests for the Dialog eSMS "Message via URL" flow.
 *
 * Response shapes here are the ones observed LIVE against account
 * PV00245309 on 2026-07-16:
 *   balance, real key      → "1|3437.99"
 *   balance, garbage key   → "2007|0"
 *   send, invalid list=123 → "2005"
 */
beforeEach(function () {
    config([
        'services.sms.driver' => 'dialog',
        'services.sms.key' => 'SECRETKEY1234',
        'services.sms.mask' => 'Glamon',
        'services.sms.url' => 'https://sms.test/api/v1/message-via-url/create/url-campaign',
        'services.sms.balance_url' => 'https://sms.test/api/v1/message-via-url/check/balance',
        'services.sms.timeout' => 10,
    ]);
});

test('outgoing request matches the verified url-campaign contract', function () {
    Http::fake(['sms.test/*' => Http::response('1', 200)]);

    $result = app(SmsGateway::class)->send('94771234567', 'Hello bill');

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($request) {
        $query = [];
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

        return $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://sms.test/api/v1/message-via-url/create/url-campaign')
            && $query['esmsqk'] === 'SECRETKEY1234'
            && $query['list'] === '0771234567'   // local format first
            && $query['source_address'] === 'Glamon'
            && $query['message'] === 'Hello bill'
            && !$request->hasHeader('Authorization'); // no Bearer on this flow
    });
});

test('success response "1" marks the send ok', function () {
    Http::fake(['sms.test/*' => Http::response('1', 200)]);

    $result = app(SmsGateway::class)->send('94771234567', 'Hi');

    expect($result->success)->toBeTrue()
        ->and($result->providerResponse['code'])->toBe('1');
});

test('code 2007 (invalid key) is a permanent failure with actionable message', function () {
    Http::fake(['sms.test/*' => Http::response('2007|0', 200)]);

    $result = app(SmsGateway::class)->send('94771234567', 'Hi');

    expect($result->success)->toBeFalse()
        ->and($result->retryable)->toBeFalse()
        ->and($result->error)->toContain('2007')
        ->and($result->error)->toContain('URL Message Key');
});

test('code 2005 triggers exactly one alternate-format retry (94-format)', function () {
    // 2005 is proven (live probe) to mean "rejected before send", so the
    // one-shot retry can never double-send.
    Http::fakeSequence('sms.test/*')
        ->push('2005', 200)
        ->push('1', 200);

    $result = app(SmsGateway::class)->send('94771234567', 'Hi');

    expect($result->success)->toBeTrue();

    $lists = [];
    Http::assertSentCount(2);
    Http::assertSent(function ($request) use (&$lists) {
        $query = [];
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
        $lists[] = $query['list'];

        return true;
    });
    expect($lists)->toBe(['0771234567', '94771234567']);
});

test('code 2005 in both formats is a permanent invalid-number failure', function () {
    Http::fakeSequence('sms.test/*')
        ->push('2005', 200)
        ->push('2005', 200);

    $result = app(SmsGateway::class)->send('94771234567', 'Hi');

    expect($result->success)->toBeFalse()
        ->and($result->retryable)->toBeFalse()
        ->and($result->error)->toContain('2005');
});

test('unknown failure codes stay retryable', function () {
    Http::fake(['sms.test/*' => Http::response('2009', 200)]);

    $result = app(SmsGateway::class)->send('94771234567', 'Hi');

    expect($result->success)->toBeFalse()
        ->and($result->retryable)->toBeTrue()
        ->and($result->error)->toContain('2009');
});

test('the key never appears in the redacted request preview', function () {
    Http::fake(['sms.test/*' => Http::response('1', 200)]);

    $result = app(SmsGateway::class)->send('94771234567', 'Hello');

    expect($result->request['query']['esmsqk'])->toBe('****1234');
    expect(json_encode($result->request))->not->toContain('SECRETKEY1234');
});

test('connection exception messages are scrubbed of the key', function () {
    // cURL messages embed the full URL — including ?esmsqk=<key>
    Http::fake(function () {
        throw new ConnectionException(
            'cURL error 28: timeout for https://sms.test/api?esmsqk=SECRETKEY1234&list=0771234567'
        );
    });

    $result = app(SmsGateway::class)->send('94771234567', 'Hello');

    expect($result->success)->toBeFalse()
        ->and($result->retryable)->toBeTrue()
        ->and($result->error)->not->toContain('SECRETKEY1234')
        ->and($result->error)->toContain('****1234');
});

test('balance check authenticates with only the key', function () {
    Http::fake(['sms.test/api/v1/message-via-url/check/balance*' => Http::response('1|3437.99', 200)]);

    $balance = app(SmsGateway::class)->checkBalance();

    expect($balance['success'])->toBeTrue()
        ->and($balance['balance'])->toBe('3437.99');
});

test('balance check surfaces an invalid key as code 2007', function () {
    Http::fake(['sms.test/api/v1/message-via-url/check/balance*' => Http::response('2007|0', 200)]);

    $balance = app(SmsGateway::class)->checkBalance();

    expect($balance['success'])->toBeFalse()
        ->and($balance['code'])->toBe('2007');
});

test('a missing sender mask fails fast without calling the gateway', function () {
    config(['services.sms.mask' => null]);
    Http::fake();

    $result = app(SmsGateway::class)->send('94771234567', 'Hello');

    expect($result->success)->toBeFalse()
        ->and($result->retryable)->toBeFalse()
        ->and($result->error)->toContain('SMS_SENDER_MASK');
    Http::assertNothingSent();
});
