<?php

namespace App\Http\Controllers;

use App\Services\ShopifyService;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopifyController extends Controller
{
    public function __construct(
        private ShopifyService $shopify
    ) {
    }

    /**
     * JSON response returned by every Shopify action when the integration is
     * disabled. The `disabled` flag lets the front-end show a plain
     * "not enabled yet" message instead of a "sync failed" error.
     */
    private function disabledResponse(): JsonResponse
    {
        return response()->json([
            'success'  => false,
            'disabled' => true,
            'message'  => 'This feature is not enabled yet.',
        ]);
    }

    /**
     * Normalize Shopify IDs (supports plain numeric IDs and gid://... formats).
     */
    private function normalizeShopifyId(mixed $id): ?string
    {
        $value = trim((string) $id);
        if ($value === '') {
            return null;
        }

        if (preg_match('/(\d+)$/', $value, $m)) {
            return ltrim($m[1], '0') ?: '0';
        }

        return $value;
    }

    /**
     * Build a resilient product lookup map keyed by normalized Shopify ID.
     */
    private function buildProductMap(): array
    {
        $map = [];
        $rows = \App\Models\Product::whereNotNull('shopify_id')->get(['id', 'shopify_id']);

        foreach ($rows as $row) {
            $raw = trim((string) $row->shopify_id);
            if ($raw === '') {
                continue;
            }

            $map[$raw] = (int) $row->id;
            $normalized = $this->normalizeShopifyId($raw);
            if ($normalized !== null) {
                $map[$normalized] = (int) $row->id;
            }
        }

        return $map;
    }

    /**
     * Resolve local product ID from Shopify product ID with DB fallback on misses.
     */
    private function resolveLocalProductId(array &$productMap, mixed $shopifyProductId): ?int
    {
        $raw = trim((string) $shopifyProductId);
        $normalized = $this->normalizeShopifyId($raw);

        if ($raw !== '' && isset($productMap[$raw])) {
            return (int) $productMap[$raw];
        }

        if ($normalized !== null && isset($productMap[$normalized])) {
            return (int) $productMap[$normalized];
        }

        $query = \App\Models\Product::query();
        if ($raw !== '') {
            $query->where('shopify_id', $raw);
        }
        if ($normalized !== null && $normalized !== $raw) {
            $query->orWhere('shopify_id', $normalized);
        }

        $foundId = $query->value('id');
        if ($foundId) {
            if ($raw !== '') {
                $productMap[$raw] = (int) $foundId;
            }
            if ($normalized !== null) {
                $productMap[$normalized] = (int) $foundId;
            }
            return (int) $foundId;
        }

        return null;
    }

    /**
     * Bootstrap local products from Shopify when hosted DB has no shopify_id mappings.
     * Returns number of products created/updated.
     */
    private function bootstrapProductsFromShopify(): int
    {
        $items = $this->shopify->getAllProducts();
        $count = 0;
        $defaultLocationId = null;

        foreach ($items as $item) {
            $variants = $item['variants'] ?? [];
            $firstVariant = $variants[0] ?? null;
            $price = $firstVariant['price'] ?? 0;
            $inventoryItemId = $firstVariant['inventory_item_id'] ?? null;

            if (!$defaultLocationId && $inventoryItemId) {
                $levels = $this->shopify->getInventoryLevelsByItem((string) $inventoryItemId);
                $defaultLocationId = $levels[0]['location_id'] ?? null;
            }

            $totalStock = 0;
            foreach ($variants as $variant) {
                $totalStock += $variant['inventory_quantity'] ?? 0;
            }

            $imageUrl = $item['image']['src'] ?? null;
            if (!$imageUrl && !empty($item['images'])) {
                $imageUrl = $item['images'][0]['src'] ?? null;
            }

            \App\Models\Product::updateOrCreate(
                ['shopify_id' => (string) $item['id']],
                [
                    'name' => $item['title'] ?? ('Product ' . $item['id']),
                    'sku' => $firstVariant['sku'] ?? ('SKU-' . $item['id']),
                    'price' => $price,
                    'stock' => $totalStock,
                    'category' => trim((string) ($item['product_type'] ?? '')) ?: 'Uncategorized',
                    'subcategory' => null,
                    'shopify_variant_id' => isset($firstVariant['id']) ? (string) $firstVariant['id'] : null,
                    'inventory_item_id' => isset($inventoryItemId) ? (string) $inventoryItemId : null,
                    'shopify_location_id' => isset($defaultLocationId) ? (string) $defaultLocationId : null,
                    'image_url' => $imageUrl,
                ]
            );

            $count++;
        }

        return $count;
    }

    public function storeInfo(): JsonResponse
    {
        if (!ShopifyService::isEnabled()) {
            return $this->disabledResponse();
        }

        $info = $this->shopify->getStoreInfo();
        return response()->json($info);
    }

    public function products(): JsonResponse
    {
        if (!ShopifyService::isEnabled()) {
            return $this->disabledResponse();
        }

        $products = $this->shopify->getProducts();
        return response()->json($products);
    }

    public function createProduct(Request $request): JsonResponse
    {
        if (!ShopifyService::isEnabled()) {
            return $this->disabledResponse();
        }

        $request->validate([
            'title' => 'required|string',
            'price' => 'required|numeric|min:0',
        ]);

        $product = $this->shopify->createProduct($request->title, $request->price);
        return response()->json($product, 201);
    }

    public function syncProducts(): JsonResponse
    {
        if (!ShopifyService::isEnabled()) {
            return $this->disabledResponse();
        }

        try {
            // Fetch ALL products from Shopify (paginated)
            $shopifyItems = $this->shopify->getAllProducts();
            $count = 0;
            $variantCount = 0;
            $defaultLocationId = null;

            // Collect all Shopify IDs from this sync
            $syncedShopifyIds = [];

            foreach ($shopifyItems as $item) {
                $variants = $item['variants'] ?? [];
                $firstVariant = $variants[0] ?? null;
                $price = $firstVariant ? $firstVariant['price'] : 0;
                $inventoryItemId = $firstVariant['inventory_item_id'] ?? null;

                // Parse category / subcategory from product_type (supports "Cat / SubCat" slash format)
                $fullType = trim($item['product_type'] ?? '');
                if ($fullType === '') {
                    $category    = 'Uncategorized';
                    $subcategory = null;
                } elseif (str_contains($fullType, '/')) {
                    [$category, $subcategory] = array_map('trim', explode('/', $fullType, 2));
                    if ($category === '') $category = 'Uncategorized';
                    if ($subcategory === '') $subcategory = null;
                } else {
                    $category    = $fullType;
                    $subcategory = null;
                    // Auto-detect subcategory from product tags
                    $knownTypes  = ['necklaces','necklace','bracelets','bracelet','rings','ring',
                                    'earrings','earring','pendants','pendant','bangles','bangle',
                                    'chains','chain','anklets','anklet','cufflinks','brooch'];
                    $tags = array_map('trim', explode(',', $item['tags'] ?? ''));
                    foreach ($tags as $tag) {
                        if (in_array(strtolower($tag), $knownTypes)) {
                            $subcategory = ucfirst(strtolower($tag));
                            // Normalise plurals
                            if (!str_ends_with($subcategory, 's')) $subcategory .= 's';
                            break;
                        }
                    }
                }

                // Get the main product image
                $imageUrl = $item['image']['src'] ?? null;
                if (!$imageUrl && !empty($item['images'])) {
                    $imageUrl = $item['images'][0]['src'] ?? null;
                }

                // Discover location ID from the first product's inventory level
                if (!$defaultLocationId && $inventoryItemId) {
                    $levels = $this->shopify->getInventoryLevelsByItem((string) $inventoryItemId);
                    $defaultLocationId = $levels[0]['location_id'] ?? null;
                }

                // Calculate total stock across all variants
                $totalStock = 0;
                foreach ($variants as $v) {
                    $totalStock += $v['inventory_quantity'] ?? 0;
                }

                // Create/update the parent product
                $product = \App\Models\Product::updateOrCreate(
                    ['shopify_id' => $item['id']],
                    [
                        'name'                 => $item['title'],
                        'sku'                  => $firstVariant['sku'] ?? 'SKU-' . $item['id'],
                        'price'                => $price,
                        'stock'                => $totalStock,
                        'category'             => $category,
                        'subcategory'          => $subcategory,
                        'shopify_variant_id'   => $firstVariant['id'] ?? null,
                        'inventory_item_id'    => $inventoryItemId,
                        'shopify_location_id'  => $defaultLocationId,
                        'image_url'            => $imageUrl,
                    ]
                );

                // Determine if this product has Color and/or Size option
                $options = $item['options'] ?? [];
                $colorOptionIndex = null;
                $sizeOptionIndex = null;
                foreach ($options as $idx => $option) {
                    $optionName = strtolower($option['name']);
                    if ($optionName === 'color' || $optionName === 'colour') {
                        $colorOptionIndex = $idx;
                    } elseif ($optionName === 'size') {
                        $sizeOptionIndex = $idx;
                    }
                }

                // Build image map: variant_id -> image_url from Shopify images
                $variantImageMap = [];
                if (!empty($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (!empty($img['variant_ids'])) {
                            foreach ($img['variant_ids'] as $vid) {
                                $variantImageMap[$vid] = $img['src'];
                            }
                        }
                    }
                }

                // Sync variants if the product has a Color/Size option or multiple variants
                $syncedVariantIds = [];
                if ($colorOptionIndex !== null || $sizeOptionIndex !== null || count($variants) > 1) {
                    foreach ($variants as $v) {
                        // Extract color from the option value
                        $color = null;
                        if ($colorOptionIndex !== null) {
                            $optionKey = 'option' . ($colorOptionIndex + 1);
                            $color = $v[$optionKey] ?? null;
                        } elseif ($sizeOptionIndex === null && count($variants) > 1) {
                            // Use option1 as color only when there's no explicit Size option either
                            $color = $v['option1'] ?? null;
                        }

                        // Extract size from the option value
                        $size = null;
                        if ($sizeOptionIndex !== null) {
                            $optionKey = 'option' . ($sizeOptionIndex + 1);
                            $size = $v[$optionKey] ?? null;
                        }

                        $variantImage = $variantImageMap[$v['id']] ?? $imageUrl;

                        $variant = ProductVariant::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'shopify_variant_id' => $v['id'],
                            ],
                            [
                                'color'              => $color,
                                'color_code'         => $color ? $this->colorNameToHex($color) : null,
                                'size'               => $size,
                                'sku'                => $v['sku'] ?? null,
                                'price'              => $v['price'] ?? null,
                                'stock'              => $v['inventory_quantity'] ?? 0,
                                'inventory_item_id'  => $v['inventory_item_id'] ?? null,
                                'shopify_location_id' => $defaultLocationId,
                                'image_url'          => $variantImage,
                            ]
                        );

                        $syncedVariantIds[] = $variant->id;
                        $variantCount++;
                    }
                }

                // Remove variants that no longer exist in Shopify
                if (!empty($syncedVariantIds)) {
                    ProductVariant::where('product_id', $product->id)
                        ->whereNotIn('id', $syncedVariantIds)
                        ->delete();
                }

                $syncedShopifyIds[] = (string) $item['id'];
                $count++;
            }

            // Remove Shopify-linked products that are no longer in Shopify.
            // Also cascade-deletes their variants via foreign key.
            $removed = \App\Models\Product::whereNotNull('shopify_id')
                ->whereNotIn('shopify_id', $syncedShopifyIds)
                ->delete();

            // Refresh collection-product links to keep category filtering up to date
            $this->refreshCollectionProductLinks();

            return response()->json([
                'success'     => true,
                'message'     => "Successfully synced {$count} products ({$variantCount} color variants) from Shopify.",
                'synced'      => $count,
                'variants'    => $variantCount,
                'removed'     => $removed,
                'location_id' => $defaultLocationId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh only the collection_product pivot table without re-syncing collection metadata.
     * Called automatically after syncProducts so category filtering stays accurate.
     */
    private function refreshCollectionProductLinks(): void
    {
        try {
            $productMap = $this->buildProductMap();
            if (empty($productMap)) {
                $bootstrapped = $this->bootstrapProductsFromShopify();
                if ($bootstrapped > 0) {
                    $productMap = $this->buildProductMap();
                }
            }

            $customCollections  = $this->shopify->getCustomCollections();
            $smartCollections   = $this->shopify->getSmartCollections();
            $allCollections     = array_merge($customCollections, $smartCollections);
            $customIds          = array_map('strval', array_column($customCollections, 'id'));
            $collectsMap        = $this->shopify->getAllCollects();

            foreach ($allCollections as $c) {
                $cId = (string) $c['id'];
                $collection = \App\Models\Collection::where('shopify_id', $cId)->first();
                if (!$collection) continue;

                $isCustom = in_array($cId, $customIds);
                if ($isCustom && isset($collectsMap[$cId])) {
                    $shopifyProductIds = $collectsMap[$cId];
                } else {
                    $shopifyProductIds = $this->shopify->getCollectionProductIds($cId);
                    if ($shopifyProductIds === null) continue; // API failed — preserve existing links
                }

                $pivotData = [];
                $position  = 0;
                foreach ($shopifyProductIds as $shopifyPid) {
                    $localId = $this->resolveLocalProductId($productMap, $shopifyPid);
                    if ($localId) {
                        $pivotData[$localId] = ['position' => $position++];
                    }
                }
                $collection->products()->sync($pivotData);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('refreshCollectionProductLinks failed: ' . $e->getMessage());
        }
    }

    /**
     * Sync Shopify collections and product-collection relationships.
     */
    public function syncCollections(): JsonResponse
    {
        if (!ShopifyService::isEnabled()) {
            return $this->disabledResponse();
        }

        set_time_limit(300); // allow up to 5 minutes

        try {
            // --- 1. Fetch both collection types in parallel-ish calls ---
            $customCollections = $this->shopify->getCustomCollections();
            $smartCollections  = $this->shopify->getSmartCollections();
            $allCollections    = array_merge($customCollections, $smartCollections);

            // --- 2. Pre-build local product map: shopify_id => local id ---
            $productMap = $this->buildProductMap(); // [shopify_id => local_id]
            if (empty($productMap)) {
                $bootstrapped = $this->bootstrapProductsFromShopify();
                if ($bootstrapped > 0) {
                    $productMap = $this->buildProductMap();
                }
            }

            // --- 3. Build a set of custom collection IDs for fast lookup ---
            $customCollectionIds = array_map('strval', array_column($customCollections, 'id'));

            // --- 4. Bulk-fetch all custom collection memberships via /collects.json ---
            // collects only covers custom collections; smart collections need per-collection calls
            $collectsMap = $this->shopify->getAllCollects(); // [collection_id => [product_id,...]]

            $syncedShopifyIds = [];
            $count = 0;

            foreach ($allCollections as $c) {
                $cId = (string) $c['id'];
                $imageUrl = $c['image']['src'] ?? null;

                $collection = \App\Models\Collection::updateOrCreate(
                    ['shopify_id' => $cId],
                    [
                        'title'       => $c['title'],
                        'handle'      => $c['handle'] ?? null,
                        'description' => $c['body_html'] ?? null,
                        'image_url'   => $imageUrl,
                        'sort_order'  => $count,
                    ]
                );

                $syncedShopifyIds[] = $cId;
                $count++;

                // Determine product shopify IDs for this collection
                $isCustom = in_array($cId, $customCollectionIds);

                if ($isCustom && isset($collectsMap[$cId])) {
                    // Use the pre-fetched collects map (fast — no extra API call)
                    $shopifyProductIds = $collectsMap[$cId];
                } else {
                    // Smart collection or missing from collects — fetch product IDs only
                    $shopifyProductIds = $this->shopify->getCollectionProductIds($cId);
                    if ($shopifyProductIds === null) continue; // API failed — preserve existing links
                }

                // Build pivot using local product map
                $productPivotData = [];
                $position = 0;
                foreach ($shopifyProductIds as $shopifyPid) {
                    $localId = $this->resolveLocalProductId($productMap, $shopifyPid);
                    if ($localId) {
                        $productPivotData[$localId] = ['position' => $position++];
                    }
                }

                $collection->products()->sync($productPivotData);
            }

            // Remove collections that no longer exist in Shopify
            $removed = \App\Models\Collection::whereNotNull('shopify_id')
                ->whereNotIn('shopify_id', $syncedShopifyIds)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully synced {$count} collections from Shopify.",
                'synced'  => $count,
                'removed' => $removed,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Collection sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fast pivot-only refresh — skips collection metadata API calls.
     * Uses the bulk /collects.json endpoint for custom collections
     * and per-collection calls only for smart collections not covered by that endpoint.
     * Much faster than syncCollections() because it avoids 2–4 metadata API calls
     * and relies on collection records already in the local DB.
     */
    public function fastRefreshCategoryLinks(): JsonResponse
    {
        if (!ShopifyService::isEnabled()) {
            return $this->disabledResponse();
        }

        set_time_limit(120);

        try {
            // Build product map from local DB (no API call)
            $productMap = $this->buildProductMap();

            if (empty($productMap)) {
                $bootstrapped = $this->bootstrapProductsFromShopify();
                if ($bootstrapped > 0) {
                    $productMap = $this->buildProductMap();
                }
            }

            if (empty($productMap)) {
                return response()->json(['success' => false, 'message' => 'No synced products found after bootstrap. Check Shopify credentials/connectivity.']);
            }

            // Fetch collections from Shopify to create any missing local records
            $customCollections = $this->shopify->getCustomCollections();
            $smartCollections  = $this->shopify->getSmartCollections();
            $allCollections    = array_merge($customCollections, $smartCollections);
            $customCollectionIds = array_column($customCollections, 'id');

            // Create/update local collection records
            $sortOrder = 0;
            foreach ($allCollections as $c) {
                $imageUrl = $c['image']['src'] ?? null;
                \App\Models\Collection::updateOrCreate(
                    ['shopify_id' => (string) $c['id']],
                    [
                        'title'       => $c['title'],
                        'handle'      => $c['handle'] ?? null,
                        'description' => $c['body_html'] ?? null,
                        'image_url'   => $imageUrl,
                        'sort_order'  => $sortOrder++,
                    ]
                );
            }

            $dbCollections = \App\Models\Collection::whereNotNull('shopify_id')->get();

            if ($dbCollections->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No collections found in Shopify.']);
            }

            // One bulk API call — covers ALL custom collection memberships
            $collectsMap = $this->shopify->getAllCollects();

            $linked = 0;
            $processed = 0;
            $startTime = microtime(true);

            foreach ($dbCollections as $collection) {
                $shopifyColId = (string) $collection->shopify_id;

                if (isset($collectsMap[$shopifyColId])) {
                    // Custom collection — no extra API call needed
                    $shopifyProductIds = $collectsMap[$shopifyColId];
                } else {
                    // Smart collection — needs per-collection API call.
                    // Stop if we've used > 50 seconds to avoid hosting timeout.
                    if ((microtime(true) - $startTime) > 50) {
                        break;
                    }
                    $shopifyProductIds = $this->shopify->getCollectionProductIds($shopifyColId);
                    if ($shopifyProductIds === null) continue; // API failed — preserve existing links
                }

                $pivotData = [];
                $pos = 0;
                foreach ($shopifyProductIds as $pid) {
                    $localId = $this->resolveLocalProductId($productMap, $pid);
                    if ($localId) {
                        $pivotData[$localId] = ['position' => $pos++];
                    }
                }

                $collection->products()->sync($pivotData);
                $linked += count($pivotData);
                $processed++;
            }

            \App\Models\Setting::updateOrCreate(
                ['key' => 'last_category_sync_at'],
                ['value' => now()->toISOString()]
            );

            return response()->json([
                'success'     => true,
                'message'     => "Category links refreshed: {$linked} product links across {$processed} collections.",
                'linked'      => $linked,
                'collections' => $processed,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('fastRefreshCategoryLinks failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Category link refresh failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync both products and collections in one request.
     * Called by the global navbar "Sync Shopify" button and the auto-sync interval.
     */
    public function syncAll(): JsonResponse
    {
        if (!ShopifyService::isEnabled()) {
            return $this->disabledResponse();
        }

        set_time_limit(600);

        try {
            // --- Sync Products ---
            $shopifyItems = $this->shopify->getAllProducts();
            $count = 0;
            $variantCount = 0;
            $defaultLocationId = null;
            $syncedShopifyIds = [];

            foreach ($shopifyItems as $item) {
                $variants   = $item['variants'] ?? [];
                $firstVariant = $variants[0] ?? null;
                $price        = $firstVariant ? $firstVariant['price'] : 0;
                $inventoryItemId = $firstVariant['inventory_item_id'] ?? null;

                $fullType = trim($item['product_type'] ?? '');
                if ($fullType === '') {
                    $category    = 'Uncategorized';
                    $subcategory = null;
                } elseif (str_contains($fullType, '/')) {
                    [$category, $subcategory] = array_map('trim', explode('/', $fullType, 2));
                    if ($category === '') $category = 'Uncategorized';
                    if ($subcategory === '') $subcategory = null;
                } else {
                    $category    = $fullType;
                    $subcategory = null;
                    $knownTypes  = ['necklaces','necklace','bracelets','bracelet','rings','ring',
                                    'earrings','earring','pendants','pendant','bangles','bangle',
                                    'chains','chain','anklets','anklet','cufflinks','brooch'];
                    $tags = array_map('trim', explode(',', $item['tags'] ?? ''));
                    foreach ($tags as $tag) {
                        if (in_array(strtolower($tag), $knownTypes)) {
                            $subcategory = ucfirst(strtolower($tag));
                            if (!str_ends_with($subcategory, 's')) $subcategory .= 's';
                            break;
                        }
                    }
                }

                $imageUrl = $item['image']['src'] ?? null;
                if (!$imageUrl && !empty($item['images'])) {
                    $imageUrl = $item['images'][0]['src'] ?? null;
                }

                if (!$defaultLocationId && $inventoryItemId) {
                    $levels = $this->shopify->getInventoryLevelsByItem((string) $inventoryItemId);
                    $defaultLocationId = $levels[0]['location_id'] ?? null;
                }

                $totalStock = 0;
                foreach ($variants as $v) {
                    $totalStock += $v['inventory_quantity'] ?? 0;
                }

                $product = \App\Models\Product::updateOrCreate(
                    ['shopify_id' => $item['id']],
                    [
                        'name'                => $item['title'],
                        'sku'                 => $firstVariant['sku'] ?? 'SKU-' . $item['id'],
                        'price'               => $price,
                        'stock'               => $totalStock,
                        'category'            => $category,
                        'subcategory'         => $subcategory,
                        'shopify_variant_id'  => $firstVariant['id'] ?? null,
                        'inventory_item_id'   => $inventoryItemId,
                        'shopify_location_id' => $defaultLocationId,
                        'image_url'           => $imageUrl,
                    ]
                );

                $options = $item['options'] ?? [];
                $colorOptionIndex = null;
                $sizeOptionIndex  = null;
                foreach ($options as $idx => $option) {
                    $name = strtolower($option['name']);
                    if ($name === 'color' || $name === 'colour') $colorOptionIndex = $idx;
                    elseif ($name === 'size') $sizeOptionIndex = $idx;
                }

                $variantImageMap = [];
                if (!empty($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (!empty($img['variant_ids'])) {
                            foreach ($img['variant_ids'] as $vid) {
                                $variantImageMap[$vid] = $img['src'];
                            }
                        }
                    }
                }

                $syncedVariantIds = [];
                if ($colorOptionIndex !== null || $sizeOptionIndex !== null || count($variants) > 1) {
                    foreach ($variants as $v) {
                        $color = null;
                        if ($colorOptionIndex !== null) {
                            $color = $v['option' . ($colorOptionIndex + 1)] ?? null;
                        } elseif ($sizeOptionIndex === null && count($variants) > 1) {
                            $color = $v['option1'] ?? null;
                        }
                        $size = null;
                        if ($sizeOptionIndex !== null) {
                            $size = $v['option' . ($sizeOptionIndex + 1)] ?? null;
                        }
                        $variantImage = $variantImageMap[$v['id']] ?? $imageUrl;
                        $variant = ProductVariant::updateOrCreate(
                            ['product_id' => $product->id, 'shopify_variant_id' => $v['id']],
                            [
                                'color'               => $color,
                                'color_code'          => $color ? $this->colorNameToHex($color) : null,
                                'size'                => $size,
                                'sku'                 => $v['sku'] ?? null,
                                'price'               => $v['price'] ?? null,
                                'stock'               => $v['inventory_quantity'] ?? 0,
                                'inventory_item_id'   => $v['inventory_item_id'] ?? null,
                                'shopify_location_id' => $defaultLocationId,
                                'image_url'           => $variantImage,
                            ]
                        );
                        $syncedVariantIds[] = $variant->id;
                        $variantCount++;
                    }
                }

                if (!empty($syncedVariantIds)) {
                    ProductVariant::where('product_id', $product->id)
                        ->whereNotIn('id', $syncedVariantIds)
                        ->delete();
                }

                $syncedShopifyIds[] = (string) $item['id'];
                $count++;
            }

            $removedProducts = \App\Models\Product::whereNotNull('shopify_id')
                ->whereNotIn('shopify_id', $syncedShopifyIds)
                ->delete();

            // --- Sync Collections (create/update collection records + pivot links) ---
            $customCollections  = $this->shopify->getCustomCollections();
            $smartCollections   = $this->shopify->getSmartCollections();
            $allCollections     = array_merge($customCollections, $smartCollections);
            $customCollectionIds = array_map('strval', array_column($customCollections, 'id'));
            $collectsMap        = $this->shopify->getAllCollects();

            // Rebuild product map after product sync (IDs may have changed)
            $productMap = $this->buildProductMap();

            $syncedCollectionShopifyIds = [];
            $collectionCount = 0;

            foreach ($allCollections as $c) {
                $cId = (string) $c['id'];
                $imageUrl = $c['image']['src'] ?? null;

                $collection = \App\Models\Collection::updateOrCreate(
                    ['shopify_id' => $cId],
                    [
                        'title'       => $c['title'],
                        'handle'      => $c['handle'] ?? null,
                        'description' => $c['body_html'] ?? null,
                        'image_url'   => $imageUrl,
                        'sort_order'  => $collectionCount,
                    ]
                );

                $syncedCollectionShopifyIds[] = $cId;
                $collectionCount++;

                $isCustom = in_array($cId, $customCollectionIds);
                if ($isCustom && isset($collectsMap[$cId])) {
                    $shopifyProductIds = $collectsMap[$cId];
                } else {
                    $shopifyProductIds = $this->shopify->getCollectionProductIds($cId);
                    if ($shopifyProductIds === null) continue; // API failed — preserve existing links
                }

                $pivotData = [];
                $position = 0;
                foreach ($shopifyProductIds as $shopifyPid) {
                    $localId = $this->resolveLocalProductId($productMap, $shopifyPid);
                    if ($localId) {
                        $pivotData[$localId] = ['position' => $position++];
                    }
                }
                $collection->products()->sync($pivotData);
            }

            // Remove collections no longer in Shopify
            if (!empty($syncedCollectionShopifyIds)) {
                \App\Models\Collection::whereNotNull('shopify_id')
                    ->whereNotIn('shopify_id', $syncedCollectionShopifyIds)
                    ->delete();
            }

            return response()->json([
                'success'          => true,
                'message'          => "Synced {$count} products & {$collectionCount} collections.",
                'products_synced'  => $count,
                'variants_synced'  => $variantCount,
                'collections_synced' => $collectionCount,
                'removed_products' => $removedProducts,
                'synced_at'        => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return the last sync time (for the auto-sync status indicator).
     */
    public function syncStatus(): JsonResponse
    {
        $log = \App\Models\ShopifyLog::latest()->first();
        return response()->json([
            'last_synced_at' => $log?->created_at?->toISOString(),
            'products'       => \App\Models\Product::count(),
            'collections'    => \App\Models\Collection::count(),
        ]);
    }

    /**
     * Convert common color names to hex codes
     */
    private function colorNameToHex(string $color): ?string
    {
        $colors = [
            'red' => '#FF0000', 'blue' => '#0000FF', 'green' => '#008000',
            'yellow' => '#FFD700', 'gold' => '#FFD700', 'silver' => '#C0C0C0',
            'white' => '#FFFFFF', 'black' => '#000000', 'pink' => '#FFC0CB',
            'purple' => '#800080', 'orange' => '#FFA500', 'brown' => '#8B4513',
            'grey' => '#808080', 'gray' => '#808080', 'navy' => '#000080',
            'rose gold' => '#B76E79', 'rose' => '#FF007F', 'platinum' => '#E5E4E2',
            'copper' => '#B87333', 'bronze' => '#CD7F32', 'ivory' => '#FFFFF0',
            'cream' => '#FFFDD0', 'beige' => '#F5F5DC', 'teal' => '#008080',
            'turquoise' => '#40E0D0', 'coral' => '#FF7F50', 'maroon' => '#800000',
            'champagne' => '#F7E7CE', 'pearl' => '#EAE0C8', 'amber' => '#FFBF00',
            'burgundy' => '#800020', 'emerald' => '#50C878', 'ruby' => '#E0115F',
            'sapphire' => '#0F52BA', 'diamond' => '#B9F2FF', 'aqua' => '#00FFFF',
            'lavender' => '#E6E6FA', 'mint' => '#98FF98', 'peach' => '#FFCBA4',
            'magenta' => '#FF00FF', 'olive' => '#808000', 'tan' => '#D2B48C',
        ];

        return $colors[strtolower(trim($color))] ?? null;
    }
}
