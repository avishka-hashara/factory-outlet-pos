<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$shop = config('services.shopify.shop');
$token = config('services.shopify.token');

echo "Shop: {$shop}\n";
echo "Token: " . substr($token, 0, 10) . "...\n";
echo "Token prefix: " . substr($token, 0, 6) . "\n\n";

echo "Testing Admin API...\n";
$response = \Illuminate\Support\Facades\Http::withHeaders([
    'X-Shopify-Access-Token' => $token,
    'Content-Type' => 'application/json',
])->get("https://{$shop}/admin/api/2026-01/shop.json");

echo "HTTP Status: " . $response->status() . "\n";
echo "Response: " . $response->body() . "\n";
