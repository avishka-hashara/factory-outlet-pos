<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateShopifyStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 600];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Product $product,
        public int $quantity,
        public ?ProductVariant $variant = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ShopifyService $shopify): void
    {
        // Belt-and-braces: never touch Shopify when the integration is disabled,
        // regardless of which caller dispatched this job.
        if (!ShopifyService::isEnabled()) {
            return;
        }

        // If variant is provided, sync at variant level
        if ($this->variant) {
            $this->syncVariant($shopify);
            return;
        }

        // Otherwise sync at product level (original behavior)
        $this->syncProduct($shopify);
    }

    private function syncVariant(ShopifyService $shopify): void
    {
        if (!$this->variant->inventory_item_id) {
            Log::warning("Cannot sync stock for variant {$this->variant->id} ({$this->variant->color}): Missing inventory_item_id");
            return;
        }

        $locationId = $this->variant->shopify_location_id ?? $this->product->shopify_location_id;

        if (!$locationId) {
            $level = $shopify->getInventoryLevel($this->variant->inventory_item_id, '');
            if (!$level) {
                $levels = $shopify->getInventoryLevelsByItem($this->variant->inventory_item_id);
                $level = $levels[0] ?? null;
            }
            $locationId = $level['location_id'] ?? null;
            if ($locationId) {
                $this->variant->update(['shopify_location_id' => $locationId]);
                Log::info("Auto-discovered location {$locationId} for variant {$this->variant->id}");
            } else {
                Log::error("Cannot sync stock for variant {$this->variant->id}: No Shopify location found");
                return;
            }
        }

        $this->variant->refresh();
        $newStock = max(0, $this->variant->stock);

        Log::info("Syncing variant {$this->variant->id} ({$this->product->name} - {$this->variant->color}) to Shopify: setting stock to {$newStock}");

        $result = $shopify->setInventoryLevel(
            $this->variant->inventory_item_id,
            $locationId,
            $newStock
        );

        if (isset($result['inventory_level'])) {
            Log::info("Shopify stock synced for variant {$this->variant->id}: available = {$result['inventory_level']['available']}");
        } else {
            Log::error("Shopify stock sync may have failed for variant {$this->variant->id}", ['result' => $result]);
        }
    }

    private function syncProduct(ShopifyService $shopify): void
    {
        if (!$this->product->shopify_id) {
            Log::info("Skipping Shopify sync for product {$this->product->id}: No shopify_id");
            return;
        }

        if (!$this->product->inventory_item_id) {
            Log::warning("Cannot sync stock for product {$this->product->id} ({$this->product->name}): Missing inventory_item_id");
            return;
        }

        $locationId = $this->product->shopify_location_id;

        if (!$locationId) {
            // Discover location from inventory levels
            $level = $shopify->getInventoryLevel(
                $this->product->inventory_item_id,
                ''
            );

            if (!$level) {
                $levels = $shopify->getInventoryLevelsByItem($this->product->inventory_item_id);
                $level = $levels[0] ?? null;
            }

            $locationId = $level['location_id'] ?? null;

            if ($locationId) {
                $this->product->update(['shopify_location_id' => $locationId]);
                Log::info("Auto-discovered location {$locationId} for product {$this->product->id}");
            } else {
                Log::error("Cannot sync stock for product {$this->product->id}: No Shopify location found via inventory_levels");
                return;
            }
        }

        $this->product->refresh();
        $newStock = max(0, $this->product->stock);

        Log::info("Syncing product {$this->product->id} ({$this->product->name}) to Shopify: setting stock to {$newStock}");

        $result = $shopify->setInventoryLevel(
            $this->product->inventory_item_id,
            $locationId,
            $newStock
        );

        if (isset($result['inventory_level'])) {
            Log::info("Shopify stock synced successfully for product {$this->product->id}: available = {$result['inventory_level']['available']}");
        } else {
            Log::error("Shopify stock sync may have failed for product {$this->product->id}", [
                'result' => $result,
            ]);
        }
    }
}
