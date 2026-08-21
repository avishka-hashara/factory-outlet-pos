<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopifyLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookController extends Controller
{
    /**
     * Handle Shopify orders/create webhook.
     * When a sale happens on Shopify, reduce stock in POS.
     */
    public function orderCreated(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Shopify webhook: orders/create received', [
            'order_id' => $payload['id'] ?? null,
            'order_number' => $payload['order_number'] ?? null,
        ]);

        ShopifyLog::create([
            'endpoint' => 'webhook/orders/create',
            'method' => 'POST',
            'request_payload' => $payload,
            'response_payload' => null,
            'status_code' => 200,
        ]);

        // Process each line item — reduce local POS stock
        $lineItems = $payload['line_items'] ?? [];

        foreach ($lineItems as $lineItem) {
            $shopifyProductId = $lineItem['product_id'] ?? null;
            $shopifyVariantId = $lineItem['variant_id'] ?? null;
            $quantity = $lineItem['quantity'] ?? 0;

            if (!$shopifyProductId || $quantity <= 0) {
                continue;
            }

            // Find the matching product in our POS by shopify_id
            $product = Product::where('shopify_id', $shopifyProductId)->first();

            if ($product) {
                // Check if there's a matching variant
                $variant = null;
                if ($shopifyVariantId) {
                    $variant = ProductVariant::where('shopify_variant_id', $shopifyVariantId)->first();
                }

                if ($variant) {
                    $oldStock = $variant->stock;
                    $variant->decrement('stock', $quantity);
                    if ($variant->stock < 0) {
                        $variant->update(['stock' => 0]);
                    }
                    // Update parent product total stock
                    $product->update(['stock' => $product->variants()->sum('stock')]);
                    Log::info("Shopify order: Reduced variant stock for '{$product->name}' - {$variant->color}", [
                        'variant_id' => $variant->id,
                        'quantity_sold' => $quantity,
                        'old_stock' => $oldStock,
                        'new_stock' => max(0, $variant->stock),
                    ]);
                } else {
                    $oldStock = $product->stock;
                    $product->decrement('stock', $quantity);
                    $newStock = max(0, $product->stock);
                    if ($newStock < 0) {
                        $product->update(['stock' => 0]);
                        $newStock = 0;
                    }
                    Log::info("Shopify order: Reduced POS stock for '{$product->name}' (ID: {$product->id})", [
                        'shopify_product_id' => $shopifyProductId,
                        'quantity_sold' => $quantity,
                        'old_stock' => $oldStock,
                        'new_stock' => $newStock,
                    ]);
                }
            } else {
                Log::warning("Shopify order: Product not found in POS for shopify_id {$shopifyProductId}");
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle Shopify inventory_levels/update webhook.
     * When inventory is updated directly in Shopify, sync to POS.
     */
    public function inventoryUpdated(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Shopify webhook: inventory_levels/update received', $payload);

        ShopifyLog::create([
            'endpoint' => 'webhook/inventory_levels/update',
            'method' => 'POST',
            'request_payload' => $payload,
            'response_payload' => null,
            'status_code' => 200,
        ]);

        $inventoryItemId = $payload['inventory_item_id'] ?? null;
        $available = $payload['available'] ?? null;

        if (!$inventoryItemId || $available === null) {
            return response()->json(['status' => 'ignored', 'reason' => 'missing data']);
        }

        // Find the product or variant in our POS by inventory_item_id
        // First check variants (more specific)
        $variant = ProductVariant::where('inventory_item_id', $inventoryItemId)->first();
        if ($variant) {
            $oldStock = $variant->stock;
            if ($variant->stock !== (int) $available) {
                $variant->update(['stock' => max(0, (int) $available)]);
                // Update parent product total stock
                $product = $variant->product;
                if ($product) {
                    $product->update(['stock' => $product->variants()->sum('stock')]);
                }
                Log::info("Shopify inventory update: Updated variant stock for '{$variant->color}'", [
                    'inventory_item_id' => $inventoryItemId,
                    'old_stock' => $oldStock,
                    'new_stock' => $available,
                ]);
            }
            return response()->json(['status' => 'ok']);
        }

        // Fallback to product level
        $product = Product::where('inventory_item_id', $inventoryItemId)->first();

        if ($product) {
            $oldStock = $product->stock;

            // Only update if the stock is actually different (avoid infinite loops)
            if ($product->stock !== (int) $available) {
                $product->update(['stock' => max(0, (int) $available)]);

                Log::info("Shopify inventory update: Updated POS stock for '{$product->name}'", [
                    'inventory_item_id' => $inventoryItemId,
                    'old_stock' => $oldStock,
                    'new_stock' => $available,
                ]);
            } else {
                Log::info("Shopify inventory update: Stock already in sync for '{$product->name}'", [
                    'stock' => $available,
                ]);
            }
        } else {
            Log::warning("Shopify inventory update: No product found for inventory_item_id {$inventoryItemId}");
        }

        return response()->json(['status' => 'ok']);
    }
}
