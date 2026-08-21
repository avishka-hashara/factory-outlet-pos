<?php

use Illuminate\Support\Facades\Http;

test('sms:test runs under the log driver without spending credit', function () {
    config(['services.sms.driver' => 'log']);
    Http::fake(); // any real call would be recorded

    $this->artisan('sms:test', ['mobile' => '0771234567'])
        ->assertSuccessful();

    Http::assertNothingSent();
});

test('sms:test rejects an invalid mobile number', function () {
    config(['services.sms.driver' => 'log']);

    $this->artisan('sms:test', ['mobile' => '0112345678'])
        ->assertFailed();
});

test('sms:test passes under the dialog driver on a successful (faked) send', function () {
    config([
        'services.sms.driver' => 'dialog',
        'services.sms.key' => 'k',
        'services.sms.mask' => 'Glamon',
        'services.sms.url' => 'https://sms.test/api/v1/message-via-url/create/url-campaign',
        'services.sms.balance_url' => 'https://sms.test/api/v1/message-via-url/check/balance',
    ]);
    Http::fake([
        'sms.test/api/v1/message-via-url/check/balance*' => Http::response('1|3437.99', 200),
        'sms.test/api/v1/message-via-url/create/url-campaign*' => Http::response('1', 200),
    ]);

    $this->artisan('sms:test', ['mobile' => '0771234567'])
        ->expectsOutputToContain('3437.99')
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        if (!str_contains($request->url(), 'url-campaign')) {
            return false;
        }
        $query = [];
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query['list'] === '0771234567' && $query['source_address'] === 'Glamon';
    });
});

test('sms:test reports failure under the dialog driver when the key is rejected', function () {
    config([
        'services.sms.driver' => 'dialog',
        'services.sms.key' => 'k',
        'services.sms.mask' => 'Glamon',
        'services.sms.url' => 'https://sms.test/api/v1/message-via-url/create/url-campaign',
        'services.sms.balance_url' => 'https://sms.test/api/v1/message-via-url/check/balance',
    ]);
    Http::fake([
        // Observed live rejection shapes for a bad URL Message Key
        'sms.test/api/v1/message-via-url/check/balance*' => Http::response('2007|0', 200),
        'sms.test/api/v1/message-via-url/create/url-campaign*' => Http::response('2007', 200),
    ]);

    $this->artisan('sms:test', ['mobile' => '0771234567'])
        ->assertFailed();
});
