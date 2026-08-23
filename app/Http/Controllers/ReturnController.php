<?php

namespace App\Http\Controllers;

use App\Models\ProductReturn;
use App\Models\ReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    /**
     * List all returns.
     */
    public function index(Request $request)
    {
        $query = ProductReturn::with(['sale.customer', 'user', 'items.product', 'items.variant'])->latest();

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('id', is_numeric($s) ? (int) $s : 0)
                  ->orWhereHas('sale', function ($sq) use ($s) {
                      $sq->where('invoice_number', 'like', "%{$s}%")
                         ->orWhere('id', is_numeric($s) ? (int) $s : 0)
                         ->orWhereHas('customer', function ($cq) use ($s) {
                             $cq->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%");
                         });
                  })
                  ->orWhereHas('items.product', function ($pq) use ($s) {
                      $pq->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%");
                  });
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }
        if ($request->filled('refund_method')) {
            $query->where('refund_method', $request->refund_method);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $returns = $query->paginate(20)->withQueryString();

        // Calculate KPI Metrics
        $statsQuery = ProductReturn::query();
        if ($request->filled('search')) {
            $s = trim($request->search);
            $statsQuery->where(function ($q) use ($s) {
                $q->where('id', is_numeric($s) ? (int) $s : 0)
                  ->orWhereHas('sale', function ($sq) use ($s) {
                      $sq->where('invoice_number', 'like', "%{$s}%")
                         ->orWhere('id', is_numeric($s) ? (int) $s : 0)
                         ->orWhereHas('customer', function ($cq) use ($s) {
                             $cq->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%");
                         });
                  })
                  ->orWhereHas('items.product', function ($pq) use ($s) {
                      $pq->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%");
                  });
            });
        }
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('reason')) {
            $statsQuery->where('reason', $request->reason);
        }
        if ($request->filled('refund_method')) {
            $statsQuery->where('refund_method', $request->refund_method);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $statsQuery->where('status', $request->status);
        }

        $totalReturns  = $statsQuery->count();
        $totalRefunded = (clone $statsQuery)->where('status', 'completed')->sum('total_refunded');
        
        // Sum of returned items
        $matchingReturnIds = (clone $statsQuery)->pluck('id');
        $itemsReturned = ReturnItem::whereIn('return_id', $matchingReturnIds)->sum('qty');

        // Total sales in the same period to calculate Return Rate
        $salesQuery = Sale::query();
        if ($request->filled('date_from')) {
            $salesQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $salesQuery->whereDate('created_at', '<=', $request->date_to);
        }
        $totalSalesCount = $salesQuery->count();
        $returnRate = $totalSalesCount > 0 ? round(($totalReturns / $totalSalesCount) * 100, 1) : 0.0;

        $totalAllReturns = ProductReturn::count();

        return view('returns.index', compact(
            'returns',
            'totalReturns',
            'totalRefunded',
            'itemsReturned',
            'returnRate',
            'totalAllReturns'
        ));
    }

    /**
     * Show the return form for a given sale.
     */
    public function create(Request $request, Sale $sale)
    {
        $sale->load(['items.sale', 'items.product', 'items.variant', 'returns.items']);

        // Calculate already-returned quantities per sale_item
        $alreadyReturned = [];
        foreach ($sale->returns()->where('status', 'completed')->with('items')->get() as $ret) {
            foreach ($ret->items as $ri) {
                $alreadyReturned[$ri->sale_item_id] = ($alreadyReturned[$ri->sale_item_id] ?? 0) + $ri->qty;
            }
        }

        // Returnable items = original qty - already returned
        $returnableItems = $sale->items->filter(function ($item) use ($alreadyReturned) {
            $already = $alreadyReturned[$item->id] ?? 0;
            return ($item->qty - $already) > 0;
        })->map(function ($item) use ($alreadyReturned) {
            $already = $alreadyReturned[$item->id] ?? 0;
            $item->returnable_qty = $item->qty - $already;
            return $item;
        });

        if ($returnableItems->isEmpty()) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'All items in this sale have already been returned.');
        }

        return view('returns.create', compact('sale', 'returnableItems'));
    }

    /**
     * Process and store the return.
     */
    public function store(Request $request, Sale $sale)
    {
        $request->validate([
            'items'                     => 'required|array|min:1',
            'items.*.sale_item_id'      => 'required|exists:sale_items,id',
            'items.*.qty'               => 'nullable|integer|min:0',
            'items.*.condition'         => 'nullable|in:good,damaged,other',
            'items.*.restock'           => 'nullable|boolean',
            'items.*.condition_notes'   => 'nullable|string|max:255',
            'refund_method'             => 'required|in:cash,card,store_credit,bank',
            'reason'                    => 'required|in:customer_request,damaged,wrong_item,quality_issue,other',
            'notes'                     => 'nullable|string|max:1000',
        ]);

        // Filter out unchecked/zero-quantity items before enforcing condition.
        $itemsToReturn = array_values(array_filter($request->items, fn($i) => (int) ($i['qty'] ?? 0) > 0));

        if (empty($itemsToReturn)) {
            return back()->withErrors(['items' => 'Please enter a return quantity for at least one item.'])->withInput();
        }

        // Load sale items with product/variant
        $saleItemMap = SaleItem::with(['sale.items', 'product', 'variant'])
            ->where('sale_id', $sale->id)
            ->get()
            ->keyBy('id');

        // Validate return quantities don't exceed returnable amounts
        $alreadyReturned = [];
        foreach ($sale->returns()->where('status', 'completed')->with('items')->get() as $ret) {
            foreach ($ret->items as $ri) {
                $alreadyReturned[$ri->sale_item_id] = ($alreadyReturned[$ri->sale_item_id] ?? 0) + $ri->qty;
            }
        }

        foreach ($itemsToReturn as $inputItem) {
            $saleItem   = $saleItemMap[$inputItem['sale_item_id']] ?? null;
            if (!$saleItem) {
                return back()->withErrors(['items' => 'Invalid sale item.'])->withInput();
            }
            if (empty($inputItem['condition'])) {
                $productName = $saleItem->product?->name ?? 'Unknown product';
                return back()->withErrors(['items' => "Please select a condition for \"{$productName}\"."])->withInput();
            }
            $already    = $alreadyReturned[$saleItem->id] ?? 0;
            $maxReturn  = $saleItem->qty - $already;
            if ($inputItem['qty'] > $maxReturn) {
                $productName = $saleItem->product?->name ?? 'Unknown product';
                return back()->withErrors(['items' => "Return quantity for \"{$productName}\" exceeds available quantity ({$maxReturn})."])->withInput();
            }
        }

        try {
            DB::transaction(function () use ($request, $sale, $itemsToReturn, $saleItemMap) {
                // Calculate total refund
                $totalRefunded = 0;
                foreach ($itemsToReturn as $inputItem) {
                    $saleItem = $saleItemMap[$inputItem['sale_item_id']];
                    $totalRefunded += $saleItem->refund_unit_price * (int) $inputItem['qty'];
                }

                // Create the return record
                $return = ProductReturn::create([
                    'sale_id'        => $sale->id,
                    'user_id'        => auth()->id(),
                    'total_refunded' => $totalRefunded,
                    'refund_method'  => $request->refund_method,
                    'reason'         => $request->reason,
                    'notes'          => $request->notes,
                    'status'         => 'completed',
                ]);

                // Process each returned item
                foreach ($itemsToReturn as $inputItem) {
                    $saleItem = $saleItemMap[$inputItem['sale_item_id']];
                    $product  = $saleItem->product;
                    $variant  = $saleItem->variant;
                    $productId = $product ? $saleItem->product_id : null;
                    $variantId = $variant ? $saleItem->product_variant_id : null;
                    $restock  = !empty($inputItem['restock']);
                    $qty      = (int) $inputItem['qty'];
                    $refundUnitPrice = $saleItem->refund_unit_price;

                    ReturnItem::create([
                        'return_id'          => $return->id,
                        'sale_item_id'       => $saleItem->id,
                        'product_id'         => $productId,
                        'product_variant_id' => $variantId,
                        'qty'                => $qty,
                        'price'              => $refundUnitPrice,
                        'condition'          => $inputItem['condition'],
                        'restock'            => $restock,
                        'condition_notes'    => $inputItem['condition_notes'] ?? null,
                    ]);

                    // Add back to inventory if in good condition and restock checkbox checked
                    if ($restock && $product) {
                        if ($variant) {
                            $variant->increment('stock', $qty);
                            // Sync parent product total stock
                            $product->update(['stock' => $product->variants()->sum('stock')]);

                            // Sync back to Shopify
                            if ($variant->shopify_variant_id) {
                                try {
                                    \App\Jobs\UpdateShopifyStock::dispatchSync($product, -$qty, $variant);
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::warning('Shopify restock on return failed', [
                                        'variant_id' => $variant->id,
                                        'error'      => $e->getMessage(),
                                    ]);
                                }
                            }
                        } else {
                            $product->increment('stock', $qty);

                            if ($product->shopify_id) {
                                try {
                                    \App\Jobs\UpdateShopifyStock::dispatchSync($product, -$qty);
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::warning('Shopify restock on return failed', [
                                        'product_id' => $product->id,
                                        'error'      => $e->getMessage(),
                                    ]);
                                }
                            }
                        }
                    } elseif ($restock && !$product) {
                        \Illuminate\Support\Facades\Log::warning('Return restock skipped because product no longer exists', [
                            'sale_id' => $sale->id,
                            'sale_item_id' => $saleItem->id,
                            'return_qty' => $qty,
                        ]);
                    }
                }

                // Flag the sale as having a return
                $sale->update(['has_return' => true]);
            });

            return redirect()->route('returns.index')
                ->with('success', 'Return processed successfully. Refund issued.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Return failed: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * View a single return.
     */
    public function show(ProductReturn $return)
    {
        $return->load(['sale.items.product', 'sale.items.variant', 'user', 'items.product', 'items.variant']);
        return view('returns.show', compact('return'));
    }
}
