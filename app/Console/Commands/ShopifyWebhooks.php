<?php

namespace App\Console\Commands;

use App\Services\ShopifyService;
use Illuminate\Console\Command;

class ShopifyWebhooks extends Command
{
    protected $signature = 'shopify:webhooks
                            {action=list : Action to perform (list, register, delete-all)}
                            {--base-url= : Base URL for webhook endpoints (required for register)}';

    protected $description = 'Manage Shopify webhooks (list, register, delete-all)';

    public function handle(ShopifyService $shopify): int
    {
        if (!ShopifyService::isEnabled()) {
            $this->warn('Shopify integration is disabled (SHOPIFY_ENABLED=false). Nothing to do.');
            return self::SUCCESS;
        }

        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->listWebhooks($shopify),
            'register' => $this->registerWebhooks($shopify),
            'delete-all' => $this->deleteAllWebhooks($shopify),
            default => $this->invalidAction($action),
        };
    }

    private function listWebhooks(ShopifyService $shopify): int
    {
        $webhooks = $shopify->getWebhooks();

        if (empty($webhooks)) {
            $this->info('No webhooks registered.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Topic', 'Address', 'Created'],
            collect($webhooks)->map(fn($w) => [
                $w['id'],
                $w['topic'],
                $w['address'],
                $w['created_at'] ?? '-',
            ])->toArray()
        );

        return self::SUCCESS;
    }

    private function registerWebhooks(ShopifyService $shopify): int
    {
        $baseUrl = $this->option('base-url') ?? config('app.url');

        if (!$baseUrl || $baseUrl === 'http://localhost') {
            $baseUrl = $this->ask('Enter your public base URL (e.g. https://abc123.ngrok-free.app)');
        }

        if (!$baseUrl) {
            $this->error('Base URL is required to register webhooks.');
            return self::FAILURE;
        }

        $webhookTopics = [
            'orders/create' => '/shopify/webhook/orders-create',
            'inventory_levels/update' => '/shopify/webhook/inventory-update',
        ];

        foreach ($webhookTopics as $topic => $path) {
            $address = rtrim($baseUrl, '/') . $path;
            $this->info("Registering: {$topic} → {$address}");

            $result = $shopify->registerWebhook($topic, $address);

            if (isset($result['webhook']['id'])) {
                $this->info("  ✅ Registered (ID: {$result['webhook']['id']})");
            } else {
                $errors = $result['errors'] ?? $result;
                $this->warn("  ⚠️  Response: " . json_encode($errors));
            }
        }

        return self::SUCCESS;
    }

    private function deleteAllWebhooks(ShopifyService $shopify): int
    {
        $webhooks = $shopify->getWebhooks();

        if (empty($webhooks)) {
            $this->info('No webhooks to delete.');
            return self::SUCCESS;
        }

        if (!$this->confirm("Delete all " . count($webhooks) . " webhooks?")) {
            return self::SUCCESS;
        }

        foreach ($webhooks as $webhook) {
            $deleted = $shopify->deleteWebhook($webhook['id']);
            $status = $deleted ? '✅ Deleted' : '❌ Failed';
            $this->info("  {$status}: {$webhook['topic']} (ID: {$webhook['id']})");
        }

        return self::SUCCESS;
    }

    private function invalidAction(string $action): int
    {
        $this->error("Invalid action: {$action}. Use: list, register, or delete-all");
        return self::FAILURE;
    }
}
