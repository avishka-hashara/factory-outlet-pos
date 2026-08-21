<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\GiftCard;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ShopifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class POSController extends Controller
{
    public function index()
    {
        $products = Product::with(['variants', 'collections'])->get();
        $collections = Collection::active()->ordered()->get();

        // Distinct categories from local (non-Shopify) products only
        $localCategories = Product::whereNull('shopify_id')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $needsCategorySync = false;

        // Self-heal: if products & collections exist but pivot is empty or mostly empty,
        // populate it inline (no separate HTTP request, no JS dependency).
        // Self-heal pulls collection membership from Shopify, so it only runs
        // when the integration is enabled. With Shopify dormant the pivot is
        // maintained locally and this whole block is skipped.
        if (ShopifyService::isEnabled() && $products->isNotEmpty() && $collections->isNotEmpty()) {
            $pivotCount = DB::table('collection_product')->count();
            $shouldSelfHeal = $pivotCount === 0;

            // Also detect partially-wiped pivot (e.g. after a failed sync cleared rows)
            if (!$shouldSelfHeal && $pivotCount > 0) {
                $productsWithCollections = $products->filter(fn($p) => $p->collections->isNotEmpty())->count();
                if ($productsWithCollections === 0) {
                    $shouldSelfHeal = true;
                }
            }

            if ($shouldSelfHeal) {
                try {
                    set_time_limit(120);

                    $shopify    = app(ShopifyService::class);
                    $productMap = Product::whereNotNull('shopify_id')
                        ->get(['id', 'shopify_id'])
                        ->reduce(function ($carry, $row) {
                            $raw = trim((string) $row->shopify_id);
                            if ($raw === '') {
                                return $carry;
                            }

                            $carry[$raw] = (int) $row->id;
                            if (preg_match('/(\d+)$/', $raw, $m)) {
                                $carry[ltrim($m[1], '0') ?: '0'] = (int) $row->id;
                            }

                            return $carry;
                        }, []);

                    // One bulk API call — covers ALL custom collection memberships
                    $collectsMap     = $shopify->getAllCollects();
                    $allDbCollections = Collection::whereNotNull('shopify_id')->get();
                    $startTime       = microtime(true);

                    foreach ($allDbCollections as $col) {
                        $shopifyColId = (string) $col->shopify_id;

                        if (isset($collectsMap[$shopifyColId])) {
                            // Custom collection — already in bulk map
                            $shopifyProductIds = $collectsMap[$shopifyColId];
                        } else {
                            // Smart collection — needs per-collection API call.
                            // Abort if we've used > 25 s to avoid hosting timeout.
                            if ((microtime(true) - $startTime) > 25) {
                                $needsCategorySync = true; // JS fallback for remaining
                                break;
                            }
                            $shopifyProductIds = $shopify->getCollectionProductIds($shopifyColId);
                            if ($shopifyProductIds === null) continue; // API failed — preserve existing links
                        }

                        $pivotData = [];
                        $pos = 0;
                        foreach ($shopifyProductIds as $pid) {
                            $rawPid = trim((string) $pid);
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
                                $pivotData[$localId] = ['position' => $pos++];
                            }
                        }
                        $col->products()->sync($pivotData);
                    }

                    // Reload products with freshly populated collection relationships
                    $products = Product::with(['variants', 'collections'])->get();
                } catch (\Exception $e) {
                    Log::warning('POS inline category sync failed: ' . $e->getMessage());
                    $needsCategorySync = true; // Let JS fallback try
                }
            }
        }

        // "Sell gift card" flow from the Gift Cards page: pre-bind the card as
        // a cart line. Binding is provisional — the card stays 'available'
        // until checkout commits.
        $pendingGiftCard = null;
        if (request()->filled('sell_gift_card')) {
            $pendingGiftCard = GiftCard::where('id', request('sell_gift_card'))
                ->where('status', GiftCard::STATUS_AVAILABLE)
                ->first();
        }

        $nextBillNo = (Sale::max('id') ?? 0) + 1;

        return view('pos.index', compact('products', 'collections', 'localCategories', 'needsCategorySync', 'pendingGiftCard', 'nextBillNo'));
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        $products = Product::with('variants')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%");
            })
            ->where('stock', '>', 0)
            ->get();

        return response()->json($products);
    }

    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            // A bill may consist of only a gift card being sold; otherwise at
            // least one product item is required.
            'items' => 'required_without:gift_card_sale|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,koko,mintpay,bank,payzy,multipay',
            'subtotal' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
            'payment_splits' => 'nullable|array',
            'payment_splits.*.method' => 'required_with:payment_splits|string',
            'payment_splits.*.amount' => 'required_with:payment_splits|numeric|min:0',
            'amount_received' => 'nullable|numeric|min:0',
            'change_amount' => 'nullable|numeric|min:0',
            // Selling a physical card pre-bound from the Gift Cards page
            'gift_card_sale' => 'nullable|array',
            'gift_card_sale.id' => 'required_with:gift_card_sale|integer|exists:gift_cards,id',
            'gift_card_sale.price' => 'required_with:gift_card_sale|numeric|min:0',
            // Redeeming a card against this bill (full card value only)
            'gift_card_redeem' => 'nullable|array',
            'gift_card_redeem.id' => 'required_with:gift_card_redeem|integer|exists:gift_cards,id',
            'gift_card_redeem.amount' => 'required_with:gift_card_redeem|numeric|min:0',
            // One purchase attempt = one key. A double-click or a retry after a
            // dropped connection reuses it, which is how we recognise them.
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        $idempotency = app(\App\Services\CheckoutIdempotencyService::class);
        $idempotencyKey = $idempotency->keyFrom($request->input('idempotency_key'));

        try {
            // Collect Shopify sync tasks to run AFTER the DB transaction commits
            $shopifySyncTasks = [];

            // Safety net for the sales.invoice_number unique index: if a
            // collision ever slips through (the DB rejects it), retry the whole
            // transaction — the fresh attempt sees the committed winner and
            // generates the next number instead of surfacing a 500.
            $maxAttempts = 3;
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                // ── Idempotency: has this exact purchase already been recorded? ──
                // Re-checked on every attempt, so the loser of a concurrent race
                // (whose INSERT was rejected by the unique index below) finds the
                // winning sale here on its next pass and returns it. Returning
                // early means NO new invoice number, NO second gift-card
                // redemption, NO second stock decrement and NO second SMS —
                // the caller just gets the sale it already made.
                if ($existingSale = $idempotency->findSale($idempotencyKey)) {
                    return $this->saleResponse($existingSale, duplicate: true);
                }

                try {
                    $shopifySyncTasks = [];

                    DB::transaction(function () use ($request, $idempotencyKey, &$shopifySyncTasks) {
                // Race-safe invoice number (MM-DD-daily sequence, e.g. 07-16-05):
                // locked atomic counter, generated inside this transaction.
                $invoiceNumber = app(\App\Services\InvoiceNumberService::class)->next();

                // The unique index on idempotency_key makes this INSERT the
                // single point where a duplicate checkout is stopped — before
                // any stock, gift card or invoice side effect is committed.
                $sale = Sale::create([
                    'user_id' => auth()->id(),
                    'invoice_number' => $invoiceNumber,
                    'idempotency_key' => $idempotencyKey,
                    'subtotal' => $request->subtotal,
                    'total' => $request->total,
                    'discount_type' => $request->discount_type,
                    'discount_value' => $request->discount_value ?? 0,
                    'discount_amount' => $request->discount_amount ?? 0,
                    'payment_method' => $request->payment_method,
                    'payment_splits' => $request->payment_splits,
                    'amount_received' => $request->amount_received,
                    'change_amount' => $request->change_amount ?? 0,
                    'customer_id' => $request->customer_id,
                ]);

                foreach (($request->items ?? []) as $item) {
                    $product = Product::find($item['product_id']);
                    $variant = !empty($item['variant_id']) ? ProductVariant::find($item['variant_id']) : null;

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'product_variant_id' => $variant?->id,
                        'qty' => $item['quantity'],
                        'price' => $item['price'],
                    ]);

                    // Reduce stock on variant and product
                    if ($variant) {
                        $variant->decrement('stock', $item['quantity']);
                        // Also update parent product's total stock
                        $product->update(['stock' => $product->variants()->sum('stock')]);
                    } else {
                        $product->decrement('stock', $item['quantity']);
                    }

                    // Queue Shopify sync to run after this transaction commits
                    if ($variant && $variant->shopify_variant_id) {
                        $shopifySyncTasks[] = ['product' => $product->fresh(), 'quantity' => $item['quantity'], 'variant' => $variant->fresh()];
                    } elseif ($product->shopify_id) {
                        $shopifySyncTasks[] = ['product' => $product->fresh(), 'quantity' => $item['quantity'], 'variant' => null];
                    }
                }

                // ── Gift card being SOLD on this bill ──
                // The card was pre-bound in the cart while still 'available'.
                // It becomes 'sold' only if this whole transaction commits; a
                // cancelled/abandoned/failed checkout leaves it 'available'.
                if ($request->filled('gift_card_sale.id')) {
                    $card = GiftCard::findOrFail((int) $request->input('gift_card_sale.id'));

                    // Price must be exactly the card's value (decimal-safe compare)
                    if (bccomp((string) $card->value, (string) $request->input('gift_card_sale.price'), 2) !== 0) {
                        throw new \RuntimeException("Gift card {$card->card_number} price does not match its value of Rs. {$card->value}. Remove it from the cart and add it again.");
                    }

                    // Single guarded UPDATE: available -> sold. Zero rows means
                    // another terminal issued it in the meantime.
                    if (!$card->markSoldForSale($sale)) {
                        throw new \RuntimeException("Gift card {$card->card_number} is no longer available to sell (current status: {$card->fresh()->status}).");
                    }

                    SaleItem::create([
                        'sale_id'      => $sale->id,
                        'product_id'   => null,
                        'gift_card_id' => $card->id,
                        'item_label'   => $card->saleItemLabel(),
                        'qty'          => 1,
                        'price'        => $card->value,
                    ]);
                }

                // ── Gift card being REDEEMED against this bill ──
                // Single atomic UPDATE guarded by status/redeemed_at/expiry:
                // no check-then-update, so two terminals racing on the same
                // card can never both succeed. Running inside this transaction
                // means a failed sale rolls the redemption back.
                if ($request->filled('gift_card_redeem.id')) {
                    $card = GiftCard::findOrFail((int) $request->input('gift_card_redeem.id'));

                    // The deduction the POS applied must equal the card's full
                    // value (full-amount redemption only, decimal-safe).
                    if (bccomp((string) $card->value, (string) $request->input('gift_card_redeem.amount'), 2) !== 0) {
                        throw new \RuntimeException("Gift card {$card->card_number} value mismatch — expected Rs. {$card->value}. Please look the card up again.");
                    }

                    if (!$card->redeemForSale($sale)) {
                        throw new \RuntimeException($card->fresh()->redeemRejectionReason());
                    }

                    $sale->update(['gift_card_redemption_amount' => $card->value]);
                }

                // Store sale ID so we can use it outside the transaction
                $request->merge(['_sale_id' => $sale->id]);
                    });

                    break; // transaction committed
                } catch (\Illuminate\Database\QueryException $e) {
                    // A concurrent identical checkout won the race: the unique
                    // index rejected this INSERT and the whole transaction rolled
                    // back, so nothing was double-applied. Loop round — the
                    // idempotency lookup at the top now finds the winner and
                    // returns it, instead of surfacing a 500.
                    if ($attempt < $maxAttempts && \App\Services\CheckoutIdempotencyService::isDuplicateKey($e)) {
                        continue;
                    }

                    if ($attempt < $maxAttempts && \App\Services\InvoiceNumberService::isDuplicateInvoiceNumber($e)) {
                        continue; // regenerate on a fresh transaction/snapshot
                    }

                    throw $e;
                }
            }

            // ── AFTER DB commit: push inventory + order to Shopify ──
            // Entirely skipped when Shopify is disabled: the sale is already
            // fully committed above, so checkout never depends on Shopify.
            if (ShopifyService::isEnabled()) {
                foreach ($shopifySyncTasks as $task) {
                    try {
                        \App\Jobs\UpdateShopifyStock::dispatchSync($task['product'], $task['quantity'], $task['variant']);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Shopify stock sync failed after checkout', [
                            'product_id' => $task['product']->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // ── Create order in Shopify so it shows in Orders page ──
                // (skipped for gift-card-only bills: gift cards are not Shopify products)
                try {
                    $shopifyLineItems = [];
                    foreach (($request->items ?? []) as $item) {
                        $product = Product::find($item['product_id']);
                        $variant = !empty($item['variant_id']) ? ProductVariant::find($item['variant_id']) : null;

                        $lineItem = [
                            'title' => $product->name . ($variant ? ' - ' . $variant->color : ''),
                            'quantity' => $item['quantity'],
                            'price' => number_format($item['price'], 2, '.', ''),
                        ];

                        // Use variant_id for Shopify line item
                        if ($variant && $variant->shopify_variant_id) {
                            $lineItem['variant_id'] = (int) $variant->shopify_variant_id;
                        } elseif ($product->shopify_variant_id) {
                            $lineItem['variant_id'] = (int) $product->shopify_variant_id;
                        }

                        $shopifyLineItems[] = $lineItem;
                    }

                    $shopifyService = app(\App\Services\ShopifyService::class);
                    $shopifyResult = empty($shopifyLineItems) ? [] : $shopifyService->createOrder($shopifyLineItems, [
                        'total' => $request->total,
                        'payment_method' => $request->payment_method,
                        'discount_type' => $request->discount_type,
                        'discount_value' => $request->discount_value ?? 0,
                        'discount_amount' => $request->discount_amount ?? 0,
                        'customer_notes' => $request->notes ?? '',
                        'note' => 'POS Sale #' . $request->_sale_id . ' by ' . (auth()->user()->name ?? 'Staff'),
                    ]);

                    // Store Shopify order ID if created successfully
                    if (isset($shopifyResult['order']['id'])) {
                        \Illuminate\Support\Facades\Log::info('Shopify order created for POS sale', [
                            'sale_id' => $request->_sale_id,
                            'shopify_order_id' => $shopifyResult['order']['id'],
                            'order_number' => $shopifyResult['order']['order_number'] ?? null,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Don't fail POS checkout if Shopify order creation fails
                    \Illuminate\Support\Facades\Log::warning('Shopify order creation failed for POS sale', [
                        'sale_id' => $request->_sale_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

                $sale = Sale::with('customer')->find($request->_sale_id);
                return $this->saleResponse($sale);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * The checkout success payload.
     *
     * A duplicate/retried checkout returns the EXISTING sale through this same
     * shape, so the POS shows the one sale it made — same id, same invoice,
     * same success modal — and can only ever offer to SMS that one bill.
     */
    private function saleResponse(?Sale $sale, bool $duplicate = false): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Sale completed successfully!',
            'sale_id' => $sale?->id,
            'invoice_number' => $sale?->invoice_number,
            'sale_date' => $sale?->created_at?->format('d M Y') ?? now()->format('d M Y'),
            'sale_time' => $sale?->created_at?->format('h:i A') ?? now()->format('h:i A'),
            'customer_phone' => $sale?->customer?->phone,
            'duplicate' => $duplicate,
        ]);
    }
    public function recentTransactions(Request $request): JsonResponse
    {
        $today = now()->format('Y-m-d');
        $sales = Sale::whereDate('created_at', $today)->orderBy('created_at', 'desc')->get();
        $totalSales = $sales->sum('total');
        
        return response()->json([
            'sales' => $sales->map(function($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'time' => $sale->created_at->format('h:i A'),
                    'date' => $sale->created_at->format('M d, Y'),
                    'payment_method' => ucfirst($sale->payment_method),
                    'total' => $sale->total
                ];
            }),
            'total_amount' => $totalSales,
            'count' => $sales->count()
        ]);
    }

    public function quickAddProduct(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
        ]);

        $sku = $request->sku;
        if (empty($sku)) {
            $sku = 'CUS-' . strtoupper(substr(uniqid(), -5));
        }

        $product = \App\Models\Product::create([
            'name' => $request->name,
            'price' => $request->price,
            'stock' => $request->stock,
            'sku' => $sku,
            'category' => $request->category,
        ]);

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'stock' => $product->stock,
                'category' => $product->category,
                'hasVariants' => false,
            ],
            'message' => 'Product added successfully!'
        ]);
    }
}
