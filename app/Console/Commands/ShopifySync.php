<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Collection;
use App\Services\ShopifyService;
use Illuminate\Console\Command;

class ShopifySync extends Command
{
    protected $signature = 'shopify:sync
                            {--direction=pull : Sync direction (pull = Shopify→POS, push = POS→Shopify, collections = refresh category-product links only)}';

    protected $description = 'Sync products and inventory between Shopify and POS';

    public function handle(ShopifyService $shopify): int
    {
        if (!ShopifyService::isEnabled()) {
            $this->warn('Shopify integration is disabled (SHOPIFY_ENABLED=false). Nothing to sync.');
            return self::SUCCESS;
        }

        $direction = $this->option('direction');

        return match ($direction) {
            'pull'        => $this->pullFromShopify($shopify),
            'push'        => $this->pushToShopify($shopify),
            'collections' => $this->syncCollectionLinks($shopify),
            default => $this->error("Invalid direction: {$direction}. Use: pull, push, or collections") ?? self::FAILURE,
        };
    }

    private function pullFromShopify(ShopifyService $shopify, bool $syncCollectionsAfter = true): int
    {
        $this->info('🔄 Pulling products from Shopify...');

        $products = $shopify->getAllProducts();
        if (empty($products)) {
            $this->error('Failed to fetch products from Shopify (empty result).');
            return self::FAILURE;
        }

        $this->info("Found " . count($products) . " products on Shopify.");

        $defaultLocationId = null;
        $count = 0;

        $bar = $this->output->createProgressBar(count($products));
        $bar->start();

        foreach ($products as $item) {
            $variant = $item['variants'][0] ?? null;
            $price = $variant ? $variant['price'] : 0;
            $category = $item['product_type'] ?: 'Uncategorized';
            $inventoryItemId = $variant['inventory_item_id'] ?? null;

            $imageUrl = $item['image']['src'] ?? null;
            if (!$imageUrl && !empty($item['images'])) {
                $imageUrl = $item['images'][0]['src'] ?? null;
            }

            if (!$defaultLocationId && $inventoryItemId) {
                $levels = $shopify->getInventoryLevelsByItem((string) $inventoryItemId);
                $defaultLocationId = $levels[0]['location_id'] ?? null;
            }

            Product::updateOrCreate(
                ['shopify_id' => $item['id']],
                [
                    'name' => $item['title'],
                    'sku' => $variant['sku'] ?? 'SKU-' . $item['id'],
                    'price' => $price,
                    'stock' => $variant['inventory_quantity'] ?? 0,
                    'category' => $category,
                    'inventory_item_id' => $inventoryItemId,
                    'shopify_location_id' => $defaultLocationId,
                    'image_url' => $imageUrl,
                ]
            );
            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Synced {$count} products from Shopify → POS");

        if ($defaultLocationId) {
            $this->info("📍 Location ID: {$defaultLocationId}");
        }

        // Also refresh collection-product links so category filtering works
        if ($syncCollectionsAfter) {
            $this->info('🔗 Refreshing category-product links...');
            $linked = $this->syncCollectionLinks($shopify);
            if ($linked === self::SUCCESS) {
                $this->info('✅ Category links refreshed.');
            }
        }

        return self::SUCCESS;
    }

    private function syncCollectionLinks(ShopifyService $shopify): int
    {
        $this->info('🔗 Syncing collection-product links from Shopify...');

        try {
            $buildMaps = function () {
                $rows = Product::get(['id', 'shopify_id', 'sku', 'name']);

                $idMap = [];
                $skuMap = [];
                $titleMap = [];

                foreach ($rows as $row) {
                    $localId = (int) $row->id;

                    $raw = trim((string) $row->shopify_id);
                    if ($raw !== '') {
                        $idMap[$raw] = $localId;
                        if (preg_match('/(\d+)$/', $raw, $m)) {
                            $idMap[ltrim($m[1], '0') ?: '0'] = $localId;
                        }
                    }

                    $sku = strtolower(trim((string) $row->sku));
                    if ($sku !== '') {
                        $skuMap[$sku] = $localId;
                    }

                    $title = strtolower(trim((string) $row->name));
                    if ($title !== '') {
                        $titleMap[$title] = $localId;
                    }
                }

                return [$idMap, $skuMap, $titleMap];
            };

            [$productMap, $skuMap, $titleMap] = $buildMaps();

            if (empty($productMap)) {
                $this->warn('No products with Shopify IDs found. Pulling products first...');
                $pullResult = $this->pullFromShopify($shopify, false);
                if ($pullResult !== self::SUCCESS) {
                    $this->error('Unable to pull products, cannot sync collection links.');
                    return self::FAILURE;
                }

                [$productMap, $skuMap, $titleMap] = $buildMaps();

                if (empty($productMap)) {
                    $this->error('Products were pulled but no Shopify IDs were stored locally.');
                    return self::FAILURE;
                }
            }

            $customCollections = $shopify->getCustomCollections();
            $smartCollections  = $shopify->getSmartCollections();
            $allCollections    = array_merge($customCollections, $smartCollections);
            $customIds         = array_map('strval', array_column($customCollections, 'id'));
            $collectsMap       = $shopify->getAllCollects();

            $bar2 = $this->output->createProgressBar(count($allCollections));
            $bar2->start();

            $linked = 0;
            $linkedByFallback = 0;
            foreach ($allCollections as $c) {
                $cId = (string) $c['id'];
                $collection = Collection::where('shopify_id', $cId)->first();
                if (!$collection) {
                    // Upsert collection record if missing
                    $collection = Collection::updateOrCreate(
                        ['shopify_id' => $cId],
                        ['title' => $c['title'], 'handle' => $c['handle'] ?? null]
                    );
                }

                $isCustom = in_array($cId, $customIds);
                if ($isCustom && isset($collectsMap[$cId])) {
                    $shopifyProductIds = $collectsMap[$cId];
                    $collectionProducts = [];
                } else {
                    $shopifyProductIds = $shopify->getCollectionProductIds($cId);
                    if ($shopifyProductIds === null) {
                        $bar2->advance();
                        continue; // API failed — preserve existing links
                    }
                    $collectionProducts = [];
                    if (empty($shopifyProductIds)) {
                        // Fallback fetch with full payload when ID-only endpoint yields no rows.
                        $collectionProducts = $shopify->getCollectionProducts($cId);
                        if (!empty($collectionProducts)) {
                            $shopifyProductIds = array_map(fn ($p) => (string) ($p['id'] ?? ''), $collectionProducts);
                        }
                    }
                }

                $pivotData = [];
                $position  = 0;
                foreach ($shopifyProductIds as $shopifyPid) {
                    $rawPid = trim((string) $shopifyPid);
                    $normalizedPid = null;
                    if (preg_match('/(\d+)$/', $rawPid, $m)) {
                        $normalizedPid = ltrim($m[1], '0') ?: '0';
                    }

                    $localId = $productMap[$rawPid]
                        ?? ($normalizedPid !== null ? ($productMap[$normalizedPid] ?? null) : null)
                        ?? Product::where('shopify_id', $rawPid)
                            ->when($normalizedPid !== null && $normalizedPid !== $rawPid, function ($q) use ($normalizedPid) {
                                $q->orWhere('shopify_id', $normalizedPid);
                            })
                            ->value('id');

                    if ($localId) {
                        $productMap[$rawPid] = (int) $localId;
                        if ($normalizedPid !== null) {
                            $productMap[$normalizedPid] = (int) $localId;
                        }
                    }

                    if ($localId) {
                        $pivotData[$localId] = ['position' => $position++];
                    }
                }

                // Final hosted fallback: map by SKU/title if ID matching found nothing.
                if (empty($pivotData)) {
                    if (empty($collectionProducts)) {
                        $collectionProducts = $shopify->getCollectionProducts($cId);
                    }

                    foreach ($collectionProducts as $sp) {
                        $matchedId = null;

                        foreach (($sp['variants'] ?? []) as $variant) {
                            $sku = strtolower(trim((string) ($variant['sku'] ?? '')));
                            if ($sku !== '' && isset($skuMap[$sku])) {
                                $matchedId = (int) $skuMap[$sku];
                                break;
                            }
                        }

                        if (!$matchedId) {
                            $title = strtolower(trim((string) ($sp['title'] ?? '')));
                            if ($title !== '' && isset($titleMap[$title])) {
                                $matchedId = (int) $titleMap[$title];
                            }
                        }

                        if ($matchedId && !isset($pivotData[$matchedId])) {
                            $pivotData[$matchedId] = ['position' => $position++];
                        }
                    }

                    $linkedByFallback += count($pivotData);
                }

                $collection->products()->sync($pivotData);
                $linked += count($pivotData);
                $bar2->advance();
            }

            $bar2->finish();
            $this->newLine(2);
            $this->info('✅ Linked ' . $linked . ' product-collection pairs across ' . count($allCollections) . ' collections.');
            if ($linkedByFallback > 0) {
                $this->info('ℹ️ ' . $linkedByFallback . ' links were matched via SKU/title fallback.');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Collection link sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function pushToShopify(ShopifyService $shopify): int
    {
        $products = Product::whereNotNull('shopify_id')
            ->whereNotNull('inventory_item_id')
            ->get();

        if ($products->isEmpty()) {
            $this->warn('No products with Shopify IDs found. Run pull first.');
            return self::FAILURE;
        }

        $this->info("🔄 Pushing stock for {$products->count()} products to Shopify...");

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($products as $product) {
            $locationId = $product->shopify_location_id;

            if (!$locationId) {
                $levels = $shopify->getInventoryLevelsByItem((string) $product->inventory_item_id);
                $locationId = $levels[0]['location_id'] ?? null;

                if ($locationId) {
                    $product->update(['shopify_location_id' => $locationId]);
                }
            }

            if (!$locationId) {
                $failed++;
                $bar->advance();
                continue;
            }

            $result = $shopify->setInventoryLevel(
                (string) $product->inventory_item_id,
                (string) $locationId,
                max(0, $product->stock)
            );

            if (isset($result['inventory_level'])) {
                $success++;
            } else {
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Pushed: {$success} | ❌ Failed: {$failed}");

        return self::SUCCESS;
    }
}
