<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    private string $shop = '';
    private string $token = '';
    private string $apiVersion = '2024-10';

    public function __construct()
    {
        // Cast to string so a null/absent config value can never throw a
        // TypeError on construction (the string-typed properties above default
        // to '' as a second line of defence). The service is only ever actually
        // called when Shopify is enabled — see self::isEnabled().
        $this->shop = (string) config('services.shopify.shop', '');
        $this->token = (string) config('services.shopify.token', '');
    }

    /**
     * Master switch for the Shopify integration.
     *
     * When false (the default), Shopify is fully dormant: no service should be
     * invoked, no API call made, no webhook processed. Every entry point that
     * touches Shopify must gate on this.
     */
    public static function isEnabled(): bool
    {
        return (bool) config('services.shopify.enabled', false);
    }

    private function getHeaders(): array
    {
        return [
            'X-Shopify-Access-Token' => $this->token,
            'Content-Type' => 'application/json',
        ];
    }

    private function baseUrl(): string
    {
        return "https://{$this->shop}/admin/api/{$this->apiVersion}";
    }

    public function getStoreInfo(): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl()}/shop.json");

        return $response->json();
    }

    public function getProducts(): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->timeout(60)
            ->get("{$this->baseUrl()}/products.json");

        return $response->json() ?? [];
    }

    /**
     * Fetch ALL products from Shopify using cursor-based pagination.
     * Returns a flat array of product objects.
     */
    public function getAllProducts(): array
    {
        $allProducts = [];
        $params = ['limit' => 250];
        $nextPageUrl = null;

        do {
            if ($nextPageUrl) {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(60)
                    ->get($nextPageUrl);
            } else {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(60)
                    ->get("{$this->baseUrl()}/products.json", $params);
            }

            if ($response->failed()) {
                Log::error('Shopify getAllProducts failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                break;
            }

            $products = $response->json('products', []);
            $allProducts = array_merge($allProducts, $products);

            // Follow the Link header for the next page
            $nextPageUrl = null;
            $linkHeader = $response->header('Link');
            if ($linkHeader) {
                foreach (explode(',', $linkHeader) as $part) {
                    $part = trim($part);
                    if (str_contains($part, 'rel="next"')) {
                        if (preg_match('/<([^>]+)>/', $part, $m)) {
                            $nextPageUrl = $m[1];
                        }
                    }
                }
            }
        } while ($nextPageUrl);

        return $allProducts;
    }

    public function createProduct(string $title, string $price): array
    {
        $data = [
            'product' => [
                'title' => $title,
                'variants' => [
                    ['price' => $price],
                ],
            ],
        ];

        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl()}/products.json", $data);

        return $response->json();
    }

    public function getLocations(): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl()}/locations.json");

        $this->logRequest('locations.json', 'GET', null, $response->json(), $response->status());

        return $response->json();
    }

    /**
     * Get the inventory level for a specific item, optionally at a specific location.
     */
    public function getInventoryLevel(string $inventoryItemId, string $locationId = ''): ?array
    {
        $params = ['inventory_item_ids' => $inventoryItemId];
        if ($locationId) {
            $params['location_ids'] = $locationId;
        }

        $response = Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl()}/inventory_levels.json", $params);

        $this->logRequest('inventory_levels.json', 'GET', $params, $response->json(), $response->status());

        $levels = $response->json('inventory_levels', []);
        return $levels[0] ?? null;
    }

    /**
     * Get all inventory levels for a specific inventory item (discovers location IDs).
     */
    public function getInventoryLevelsByItem(string $inventoryItemId): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl()}/inventory_levels.json", [
                'inventory_item_ids' => $inventoryItemId,
            ]);

        return $response->json('inventory_levels', []);
    }

    /**
     * Set the absolute inventory quantity for an item at a location.
     * Uses inventory_levels/set.json (supported across API versions).
     */
    public function setInventoryLevel(string $inventoryItemId, string $locationId, int $available): array
    {
        $data = [
            'location_id' => (int) $locationId,
            'inventory_item_id' => (int) $inventoryItemId,
            'available' => $available,
        ];

        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl()}/inventory_levels/set.json", $data);

        $this->logRequest('inventory_levels/set.json', 'POST', $data, $response->json(), $response->status());

        if ($response->failed()) {
            Log::error('Shopify inventory set failed', [
                'status' => $response->status(),
                'response' => $response->json(),
                'data' => $data,
            ]);
        }

        return $response->json() ?? [];
    }

    /**
     * Adjust inventory by a relative amount (decrement/increment).
     * Falls back to set if adjust endpoint is deprecated.
     */
    public function adjustInventory(string $inventoryItemId, string $locationId, int $adjustment): array
    {
        $data = [
            'location_id' => (int) $locationId,
            'inventory_item_id' => (int) $inventoryItemId,
            'available_adjustment' => $adjustment,
        ];

        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl()}/inventory_levels/adjust.json", $data);

        $this->logRequest('inventory_levels/adjust.json', 'POST', $data, $response->json(), $response->status());

        if ($response->failed()) {
            Log::warning('Shopify inventory adjust failed, falling back to set', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            // Fallback: get current level and set absolute value
            $currentLevel = $this->getInventoryLevel($inventoryItemId, $locationId);
            $currentAvailable = $currentLevel['available'] ?? 0;
            $newAvailable = max(0, $currentAvailable + $adjustment);

            return $this->setInventoryLevel($inventoryItemId, $locationId, $newAvailable);
        }

        return $response->json() ?? [];
    }

    /**
     * Get inventory levels for all items at a given location.
     */
    public function getInventoryLevelsForLocation(string $locationId): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl()}/inventory_levels.json", [
                'location_ids' => $locationId,
                'limit' => 250,
            ]);

        return $response->json('inventory_levels', []);
    }

    /**
     * Register a webhook with Shopify.
     */
    public function registerWebhook(string $topic, string $address): array
    {
        $data = [
            'webhook' => [
                'topic' => $topic,
                'address' => $address,
                'format' => 'json',
            ],
        ];

        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl()}/webhooks.json", $data);

        $this->logRequest('webhooks.json', 'POST', $data, $response->json(), $response->status());

        return $response->json() ?? [];
    }

    /**
     * List all registered webhooks.
     */
    public function getWebhooks(): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl()}/webhooks.json");

        return $response->json('webhooks', []);
    }

    /**
     * Create an order in Shopify from a POS sale.
     * This makes the POS sale appear in Shopify Admin → Orders.
     */
    public function createOrder(array $lineItems, array $options = []): array
    {
        $orderData = [
            'order' => [
                'line_items'          => $lineItems,
                'financial_status'    => 'paid',
                'tags'                => 'POS-Sale',
                'source_name'         => 'pos',
                'note'                => $options['note'] ?? 'POS Sale from Jewellery POS System',
                // Prevent Shopify from auto-decrementing inventory.
                // The POS already pushes the correct absolute level via setInventoryLevel.
                'inventory_behaviour' => 'bypass',
                'transactions' => [
                    [
                        'kind'    => 'sale',
                        'status'  => 'success',
                        'amount'  => $options['total'] ?? 0,
                        'gateway' => $options['payment_method'] ?? 'manual',
                    ],
                ],
            ],
        ];

        // Add discount if present
        if (!empty($options['discount_amount']) && $options['discount_amount'] > 0) {
            $discountCode = 'POS-DISCOUNT';
            if (($options['discount_type'] ?? '') === 'percentage') {
                $discountCode = 'POS-' . ($options['discount_value'] ?? 0) . '%-OFF';
            }
            $orderData['order']['discount_codes'] = [
                [
                    'code' => $discountCode,
                    'amount' => number_format($options['discount_amount'], 2, '.', ''),
                    'type' => ($options['discount_type'] ?? '') === 'percentage' ? 'percentage' : 'fixed_amount',
                ],
            ];
        }

        // Add customer note
        if (!empty($options['customer_notes'])) {
            $orderData['order']['note'] .= ' | Customer Notes: ' . $options['customer_notes'];
        }

        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl()}/orders.json", $orderData);

        $this->logRequest('orders.json', 'POST', $orderData, $response->json(), $response->status());

        if ($response->failed()) {
            Log::error('Shopify order creation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
                'data' => $orderData,
            ]);
        }

        return $response->json() ?? [];
    }

    /**
     * Delete a webhook by ID.
     */
    public function deleteWebhook(int $webhookId): bool
    {
        $response = Http::withHeaders($this->getHeaders())
            ->delete("{$this->baseUrl()}/webhooks/{$webhookId}.json");

        return $response->successful();
    }

    /**
     * Fetch ALL collects (custom collection ↔ product links) in bulk.
     * Returns [collection_id => [product_id, ...]] for quick lookup.
     */
    public function getAllCollects(): array
    {
        $map = [];
        $params = ['limit' => 250];
        $nextPageUrl = null;

        do {
            if ($nextPageUrl) {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(60)
                    ->get($nextPageUrl);
            } else {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(60)
                    ->get("{$this->baseUrl()}/collects.json", $params);
            }

            if ($response->failed()) {
                Log::error('Shopify getAllCollects failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                break;
            }

            foreach ($response->json('collects', []) as $collect) {
                $cid = (string) $collect['collection_id'];
                $pid = (string) $collect['product_id'];
                $map[$cid][] = $pid;
            }

            // Follow Link header pagination
            $nextPageUrl = null;
            $linkHeader = $response->header('Link');
            if ($linkHeader) {
                foreach (explode(',', $linkHeader) as $part) {
                    $part = trim($part);
                    if (str_contains($part, 'rel="next"')) {
                        if (preg_match('/<([^>]+)>/', $part, $m)) {
                            $nextPageUrl = $m[1];
                        }
                    }
                }
            }
        } while ($nextPageUrl);

        return $map;
    }

    /**
     * Get only product IDs for a given collection (lighter than full product fetch).
     * Uses /collections/{id}/products.json — the correct endpoint for API 2022-07+.
     * The old products.json?collection_id=X filter was removed in Shopify API 2022-07.
     */
    public function getCollectionProductIds(string $collectionId): ?array
    {
        $ids = [];
        $params = ['limit' => 250, 'fields' => 'id'];
        $nextPageUrl = null;
        $apiFailed = false;

        do {
            if ($nextPageUrl) {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(60)
                    ->get($nextPageUrl);
            } else {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(60)
                    ->get("{$this->baseUrl()}/collections/{$collectionId}/products.json", $params);
            }

            if ($response->failed()) {
                Log::error('Shopify getCollectionProductIds failed', [
                    'status'        => $response->status(),
                    'body'          => $response->body(),
                    'collection_id' => $collectionId,
                ]);
                $apiFailed = true;
                break;
            }

            foreach ($response->json('products', []) as $p) {
                $ids[] = (string) $p['id'];
            }

            // Follow Link header pagination
            $nextPageUrl = null;
            $linkHeader = $response->header('Link');
            if ($linkHeader) {
                foreach (explode(',', $linkHeader) as $part) {
                    $part = trim($part);
                    if (str_contains($part, 'rel="next"')) {
                        if (preg_match('/<([^>]+)>/', $part, $m)) {
                            $nextPageUrl = $m[1];
                        }
                    }
                }
            }
        } while ($nextPageUrl);

        // Return null on any API failure so callers can preserve existing pivot data
        if ($apiFailed) {
            return null;
        }

        return $ids;
    }

    /**
     * Get all custom collections from Shopify.
     */
    public function getCustomCollections(): array
    {
        $allCollections = [];
        $params = ['limit' => 250];
        $nextPageUrl = null;

        do {
            if ($nextPageUrl) {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(60)
                    ->get($nextPageUrl);
            } else {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(60)
                    ->get("{$this->baseUrl()}/custom_collections.json", $params);
            }

            if ($response->failed()) {
                Log::error('Shopify getCustomCollections failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                break;
            }

            $collections = $response->json('custom_collections', []);
            $allCollections = array_merge($allCollections, $collections);

            // Follow the Link header for the next page
            $nextPageUrl = null;
            $linkHeader = $response->header('Link');
            if ($linkHeader) {
                foreach (explode(',', $linkHeader) as $part) {
                    $part = trim($part);
                    if (str_contains($part, 'rel="next"')) {
                        if (preg_match('/<([^>]+)>/', $part, $m)) {
                            $nextPageUrl = $m[1];
                        }
                    }
                }
            }
        } while ($nextPageUrl);

        return $allCollections;
    }

    /**
     * Fetch all smart collections from Shopify.
     */
    public function getSmartCollections(): array
    {
        $allCollections = [];
        $params = ['limit' => 250];
        $nextPageUrl = null;

        do {
            if ($nextPageUrl) {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(60)
                    ->get($nextPageUrl);
            } else {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(60)
                    ->get("{$this->baseUrl()}/smart_collections.json", $params);
            }

            if ($response->failed()) {
                Log::error('Shopify getSmartCollections failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                break;
            }

            $collections = $response->json('smart_collections', []);
            $allCollections = array_merge($allCollections, $collections);

            // Follow the Link header for the next page
            $nextPageUrl = null;
            $linkHeader = $response->header('Link');
            if ($linkHeader) {
                foreach (explode(',', $linkHeader) as $part) {
                    $part = trim($part);
                    if (str_contains($part, 'rel="next"')) {
                        if (preg_match('/<([^>]+)>/', $part, $m)) {
                            $nextPageUrl = $m[1];
                        }
                    }
                }
            }
        } while ($nextPageUrl);

        return $allCollections;
    }

    /**
     * Get all products in a collection (custom or smart).
     * Uses /collections/{id}/products.json — the correct endpoint for API 2022-07+.
     */
    public function getCollectionProducts(string $collectionId): array
    {
        $allProducts = [];
        $params = ['limit' => 250];
        $nextPageUrl = null;

        do {
            if ($nextPageUrl) {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(60)
                    ->get($nextPageUrl);
            } else {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(60)
                    ->get("{$this->baseUrl()}/collections/{$collectionId}/products.json", $params);
            }

            if ($response->failed()) {
                Log::error('Shopify getCollectionProducts failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'collection_id' => $collectionId,
                ]);
                break;
            }

            $products = $response->json('products', []);
            $allProducts = array_merge($allProducts, $products);

            // Follow the Link header for the next page
            $nextPageUrl = null;
            $linkHeader = $response->header('Link');
            if ($linkHeader) {
                foreach (explode(',', $linkHeader) as $part) {
                    $part = trim($part);
                    if (str_contains($part, 'rel="next"')) {
                        if (preg_match('/<([^>]+)>/', $part, $m)) {
                            $nextPageUrl = $m[1];
                        }
                    }
                }
            }
        } while ($nextPageUrl);

        return $allProducts;
    }

    private function logRequest(string $endpoint, string $method, ?array $request, ?array $response, ?int $status): void
    {
        \App\Models\ShopifyLog::create([
            'endpoint' => $endpoint,
            'method' => $method,
            'request_payload' => $request,
            'response_payload' => $response,
            'status_code' => $status,
            'error' => $status >= 400 ? json_encode($response) : null,
        ]);
    }
}

