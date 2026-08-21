<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'shopify' => [
        // Master on/off switch for the entire Shopify integration.
        // Defaults to false: the whole integration stays dormant (no service
        // construction, no API calls, no config reads that can throw) until this
        // is explicitly set to true AND the credentials below are filled in.
        'enabled' => env('SHOPIFY_ENABLED', false),
        'shop' => env('SHOPIFY_SHOP'),
        'token' => env('SHOPIFY_TOKEN'),
        'api_key' => env('SHOPIFY_API_KEY'),
        'api_secret' => env('SHOPIFY_API_SECRET'),
    ],

    'sms' => [
        // 'dialog' sends via the Dialog eSMS "Message via URL" API (the flow the
        // portal's "URL Message Key" belongs to — verified live 2026-07-16);
        // 'log' writes the message to the application log instead (dry-run).
        'driver' => env('SMS_DRIVER', 'log'),
        'url' => env('SMS_GATEWAY_URL', 'https://e-sms.dialog.lk/api/v1/message-via-url/create/url-campaign'),
        'balance_url' => env('SMS_BALANCE_URL', 'https://e-sms.dialog.lk/api/v1/message-via-url/check/balance'),
        'key' => env('SMS_GATEWAY_KEY'),
        'mask' => env('SMS_SENDER_MASK'),
        'timeout' => (int) env('SMS_TIMEOUT', 10),
        // Max GSM-7 segments a bill SMS may span before it is trimmed to fit.
        // 1 = 160 chars, 2 = 306, 3 = 459 (153/segment past the first).
        'max_segments' => (int) env('SMS_MAX_SEGMENTS', 2),
        // Days a public /b/{token} bill link stays valid.
        // null (empty/blank env, or a non-positive number) = never expires.
        'bill_link_ttl_days' => (function () {
            $raw = env('SMS_BILL_LINK_TTL_DAYS');
            if ($raw === null || trim((string) $raw) === '') {
                return null;
            }
            $days = (int) $raw;

            return $days > 0 ? $days : null;
        })(),
    ],

];
