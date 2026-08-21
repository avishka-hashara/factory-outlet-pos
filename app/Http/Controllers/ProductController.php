<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Store a new local-only product (never synced to Shopify).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'sku'         => 'required|string|max:100|unique:products,sku',
            'category'    => 'required|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'variants'              => 'nullable|array',
            'variants.*.color'      => 'nullable|string|max:100',
            'variants.*.color_code' => 'nullable|string|max:20',
            'variants.*.size'       => 'nullable|string|max:100',
            'variants.*.sku'        => 'nullable|string|max:100',
            'variants.*.price'      => 'nullable|numeric|min:0',
            'variants.*.stock'      => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        $variantsData = $validated['variants'] ?? [];
        unset($validated['variants']);

        // shopify_id is intentionally left null — local product only
        $product = Product::create($validated);

        // Create any variants submitted with the form
        $validVariants = array_filter($variantsData, fn($v) => !empty($v['color']) || !empty($v['size']));
        foreach ($validVariants as $v) {
            $product->variants()->create([
                'color'      => $v['color']      ?? null,
                'color_code' => $v['color_code'] ?? null,
                'size'       => $v['size']       ?? null,
                'sku'        => $v['sku']        ?? null,
                'price'      => isset($v['price']) && $v['price'] !== '' ? $v['price'] : null,
                'stock'      => (int)($v['stock'] ?? 0),
            ]);
        }
        if (count($validVariants) > 0) {
            $product->update(['stock' => $product->variants()->sum('stock')]);
        }

        if ($request->wantsJson()) {
            $product->load('variants');
            return response()->json(['success' => true, 'product' => $product, 'message' => 'Product added successfully.']);
        }

        return back()->with('success', 'Local product "' . $validated['name'] . '" added successfully.');
    }

    /**
     * Update an existing local product.
     * Shopify-linked products are read-only here.
     */
    public function update(Request $request, Product $product)
    {
        if ($product->shopify_id) {
            return back()->with('error', 'Shopify products are read-only. Edit them in your Shopify store.');
        }

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'sku'          => 'required|string|max:100|unique:products,sku,' . $product->id,
            'category'     => 'required|string|max:100',
            'subcategory'  => 'nullable|string|max:100',
            'price'        => 'required|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_image' => 'nullable|boolean',
        ]);

        // Replace image
        if ($request->hasFile('image')) {
            // Delete old local image (skip external Shopify URLs)
            if ($product->image_url && str_starts_with($product->image_url, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $product->image_url));
            }
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = Storage::url($path);
        } elseif (!empty($validated['remove_image'])) {
            // Remove without replacement
            if ($product->image_url && str_starts_with($product->image_url, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $product->image_url));
            }
            $validated['image_url'] = null;
        }

        unset($validated['remove_image']);
        $product->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'product' => $product->fresh()->load('variants'), 'message' => 'Product updated successfully.']);
        }

        return back()->with('success', 'Product "' . $validated['name'] . '" updated successfully.');
    }

    /**
     * Delete a local product and its image.
     * Shopify-linked products cannot be deleted from here.
     */
    public function destroy(Product $product)
    {
        if ($product->shopify_id) {
            return back()->with('error', 'Shopify products cannot be deleted here. Remove them from your Shopify store.');
        }

        // Delete locally-stored image
        if ($product->image_url && str_starts_with($product->image_url, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $product->image_url));
        }

        $name = $product->name;
        $product->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Product "' . $name . '" deleted successfully.']);
        }

        return back()->with('success', 'Product "' . $name . '" deleted successfully.');
    }

    /**
     * List variants for a product (JSON).
     */
    public function variantsList(Product $product)
    {
        return response()->json($product->variants()->orderBy('id')->get());
    }

    /**
     * Add a variant to a local product (color and/or size).
     */
    public function storeVariant(Request $request, Product $product)
    {
        if ($product->shopify_id) {
            return response()->json(['error' => 'Shopify product variants are managed via Shopify sync.'], 403);
        }

        $validated = $request->validate([
            'color'      => 'nullable|string|max:100',
            'color_code' => 'nullable|string|max:20',
            'size'       => 'nullable|string|max:100',
            'sku'        => 'nullable|string|max:100',
            'price'      => 'nullable|numeric|min:0',
            'stock'      => 'required|integer|min:0',
        ]);

        if (empty($validated['color']) && empty($validated['size'])) {
            return response()->json(['error' => 'Please provide at least a Color or Size.'], 422);
        }

        $variant = $product->variants()->create($validated);
        $product->update(['stock' => $product->variants()->sum('stock')]);

        return response()->json(['success' => true, 'variant' => $variant]);
    }

    /**
     * Update the stock of a specific variant (works for both local and Shopify products).
     * For Shopify products the change is also pushed to Shopify inventory.
     */
    public function updateVariantStock(Request $request, Product $product, ProductVariant $variant)
    {
        // Ensure the variant actually belongs to this product
        if ((int) $variant->product_id !== (int) $product->id) {
            return response()->json(['error' => 'Variant does not belong to this product.'], 403);
        }

        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $newStock = (int) $validated['stock'];
        $variant->update(['stock' => $newStock]);
        $product->update(['stock' => $product->variants()->sum('stock')]);

        // Push to Shopify when enabled and the variant has inventory tracking IDs
        $shopifySynced = false;
        if (ShopifyService::isEnabled() && $variant->inventory_item_id && $variant->shopify_location_id) {
            try {
                $shopify = app(ShopifyService::class);
                $shopify->setInventoryLevel(
                    (string) $variant->inventory_item_id,
                    (string) $variant->shopify_location_id,
                    $newStock
                );
                $shopifySynced = true;
            } catch (\Exception $e) {
                Log::warning('updateVariantStock: Shopify sync failed for variant ' . $variant->id . ': ' . $e->getMessage());
            }
        }

        return response()->json([
            'success'        => true,
            'stock'          => $newStock,
            'product_stock'  => $product->fresh()->stock,
            'variants_count' => $product->variants()->count(),
            'shopify_synced' => $shopifySynced,
        ]);
    }

    /**
     * Delete a variant from a local product.
     */
    public function destroyVariant(Product $product, ProductVariant $variant)
    {
        if ($product->shopify_id) {
            return response()->json(['error' => 'Shopify product variants are managed via Shopify sync.'], 403);
        }

        $variant->delete();
        $product->update(['stock' => $product->variants()->sum('stock')]);

        return response()->json(['success' => true]);
    }
}
