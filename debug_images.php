<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$shopify = app(\App\Services\ShopifyService::class);
$products = $shopify->getProducts();

foreach ($products['products'] ?? [] as $item) {
    echo "Product: {$item['title']}" . PHP_EOL;
    echo "  image key exists: " . (array_key_exists('image', $item) ? 'YES' : 'NO') . PHP_EOL;
    echo "  image value: " . json_encode($item['image'] ?? 'NULL') . PHP_EOL;
    echo "  images count: " . count($item['images'] ?? []) . PHP_EOL;
    if (!empty($item['images'])) {
        echo "  first image src: " . ($item['images'][0]['src'] ?? 'NONE') . PHP_EOL;
    }
    echo PHP_EOL;
}
