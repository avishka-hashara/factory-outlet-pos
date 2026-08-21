<?php

namespace App\Console\Commands;

use App\Services\ShopifyService;
use Illuminate\Console\Command;

class RegisterShopifyWebhooks extends Command
{
    protected $signature = 'shopify:register-webhooks {--base-url= : The public base URL for webhook callbacks}';

    protected $description = 'Register Shopify webhooks for two-way inventory sync';

    public function handle(ShopifyService $shopify): int
    {
        if (!ShopifyService::isEnabled()) {
            $this->warn('Shopify integration is disabled (SHOPIFY_ENABLED=false). Nothing to do.');
            return self::SUCCESS;
        }

        $baseUrl = $this->option('base-url') ?: config('app.url');

        if (str_contains($baseUrl, 'localhost') || str_contains($baseUrl, '127.0.0.1')) {
            $this->warn('⚠ Your base URL is localhost. Shopify cannot send webhooks to localhost.');
            $this->warn('  Use a tool like ngrok to expose your local server, then pass --base-url=https://your-ngrok-url');
            if (!$this->confirm('Continue anyway?')) {
                return 1;
            }
        }

        $webhooks = [
            [
                'topic' => 'orders/create',
                'url' => rtrim($baseUrl, '/') . '/shopify/webhook/orders-create',
            ],
            [
                'topic' => 'inventory_levels/update',
                'url' => rtrim($baseUrl, '/') . '/shopify/webhook/inventory-update',
            ],
        ];

        // Show existing webhooks
        $this->info('Fetching existing webhooks...');
        $existing = $shopify->getWebhooks();

        if (!empty($existing)) {
            $this->table(
                ['ID', 'Topic', 'Address'],
                collect($existing)->map(fn($w) => [$w['id'], $w['topic'], $w['address']])->toArray()
            );

            if ($this->confirm('Delete existing webhooks before registering new ones?', true)) {
                foreach ($existing as $webhook) {
                    $shopify->deleteWebhook($webhook['id']);
                    $this->line("  Deleted webhook {$webhook['id']} ({$webhook['topic']})");
                }
            }
        } else {
            $this->info('No existing webhooks found.');
        }

        // Register new webhooks
        $this->info('Registering webhooks...');

        foreach ($webhooks as $webhook) {
            $result = $shopify->registerWebhook($webhook['topic'], $webhook['url']);

            if (isset($result['webhook']['id'])) {
                $this->info("  ✓ Registered: {$webhook['topic']} → {$webhook['url']}");
            } else {
                $this->error("  ✗ Failed to register: {$webhook['topic']}");
                $this->line('    Response: ' . json_encode($result));
            }
        }

        $this->newLine();
        $this->info('Done! Webhooks registered for two-way inventory sync.');
        $this->info('When a sale happens on Shopify, your POS stock will auto-update.');

        return 0;
    }
}
