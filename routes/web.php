<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\ShopifyWebhookController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ReturnController;
use App\Http\Controllers\CashDrawerController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceSmsController;
use App\Http\Controllers\PublicBillController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Shopify routes
Route::get('/shopify/store', [ShopifyController::class, 'storeInfo']);
Route::get('/shopify/products', [ShopifyController::class, 'products']);
Route::post('/shopify/products', [ShopifyController::class, 'createProduct']);
Route::post('/shopify/sync', [ShopifyController::class, 'syncProducts'])->name('shopify.sync');
Route::post('/shopify/sync-collections', [ShopifyController::class, 'syncCollections'])->name('shopify.sync.collections');
Route::post('/shopify/fast-category-sync', [ShopifyController::class, 'fastRefreshCategoryLinks'])->name('shopify.fast.category.sync');
Route::post('/shopify/sync-all', [ShopifyController::class, 'syncAll'])->name('shopify.sync.all');
Route::get('/shopify/sync-status', [ShopifyController::class, 'syncStatus'])->name('shopify.sync.status');

// POS routes (authenticated)
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/pos', [POSController::class, 'index'])->middleware('permission:pos')->name('pos.index');
    Route::get('/pos/search', [POSController::class, 'search'])->name('pos.search');
    Route::post('/pos/checkout', [POSController::class, 'checkout'])->name('pos.checkout');

    // SMS bill delivery (cashier = pos permission; admin bypasses)
    Route::post('/api/invoices/{sale}/send-sms', [InvoiceSmsController::class, 'send'])
        ->middleware('permission:pos')->name('invoices.sms.send');
    Route::get('/api/invoices/{sale}/sms-status', [InvoiceSmsController::class, 'status'])
        ->middleware('permission:pos')->name('invoices.sms.status');
    Route::get('/pos/recent-transactions', [POSController::class, 'recentTransactions'])->name('pos.recent');
    Route::post('/pos/quick-add-product', [POSController::class, 'quickAddProduct'])->name('pos.quick_add');

    // POS Sales Log (staff with pos_log permission, or admin)
    Route::get('/pos/log', function (\Illuminate\Http\Request $request) {
        $query = \App\Models\Sale::with(['items.product', 'items.variant', 'user'])->latest();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('staff_id')) {
            $query->where('user_id', $request->staff_id);
        }

        $sales = $query->paginate(20)->withQueryString();

        $statsBase = \App\Models\Sale::query();
        if ($request->filled('date_from'))
            $statsBase->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))
            $statsBase->whereDate('created_at', '<=', $request->date_to);
        if ($request->filled('payment_method'))
            $statsBase->where('payment_method', $request->payment_method);
        if ($request->filled('staff_id'))
            $statsBase->where('user_id', $request->staff_id);

        $totalOrders = (clone $statsBase)->count();
        $totalRevenue = (clone $statsBase)->sum('total');
        $totalDiscounts = (clone $statsBase)->sum('discount_amount');
        $totalItems = \App\Models\SaleItem::whereIn('sale_id', (clone $statsBase)->pluck('id'))->sum('qty');
        $staffList = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return view('pos.log', compact(
            'sales',
            'totalOrders',
            'totalRevenue',
            'totalDiscounts',
            'totalItems',
            'staffList'
        ));
    })->middleware('permission:pos_log')->name('pos.log');

    // Gift card lookup at the POS (cashier): live status by card number/barcode
    Route::get('/pos/gift-card-lookup', [App\Http\Controllers\GiftCardController::class, 'lookup'])
        ->middleware('permission:pos')->name('pos.giftcard.lookup');

    // Customers
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::resource('customers', CustomerController::class)->except(['create', 'edit']);
});

// Dashboard routes (authenticated + permission gated)
Route::middleware(['auth', 'verified', 'active', 'permission:dashboard'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/metrics', [\App\Http\Controllers\DashboardController::class, 'metrics'])->name('dashboard.metrics');
    Route::get('/dashboard/export', [\App\Http\Controllers\DashboardController::class, 'export'])->name('dashboard.export');
});

// Products
Route::middleware(['auth', 'active'])->group(function () {
    // Products — view all; local-only products can be added/edited/deleted here.
    // Shopify-synced products are read-only (managed via Shopify Sync).
    Route::get('/products', function (\Illuminate\Http\Request $request) {
        $query = \App\Models\Product::with('variants');

        // Source tab filter
        if ($request->source === 'shopify') {
            $query->whereNotNull('shopify_id');
        } elseif ($request->source === 'local') {
            $query->whereNull('shopify_id');
        }

        // Text search
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('sku',  'like', "%{$s}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Stock status filter
        if ($request->stock === 'instock') {
            $query->where('stock', '>', 5);
        } elseif ($request->stock === 'low') {
            $query->where('stock', '>', 0)->where('stock', '<=', 5);
        } elseif ($request->stock === 'out') {
            $query->where('stock', '<=', 0);
        }

        // Sorting
        $sort = $request->get('sort', 'name_asc');
        switch ($sort) {
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'stock_asc':
                $query->orderBy('stock', 'asc');
                break;
            case 'stock_desc':
                $query->orderBy('stock', 'desc');
                break;
            case 'updated':
                $query->orderBy('updated_at', 'desc');
                break;
            case 'name_asc':
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        $products = $query->paginate(20)->withQueryString();

        // KPI metrics from entire database
        $totalProducts   = \App\Models\Product::count();
        $stockValue      = (float) (\App\Models\Product::selectRaw('SUM(price * stock) as total_val')->value('total_val') ?? 0.0);
        $lowStockCount   = \App\Models\Product::where('stock', '>', 0)->where('stock', '<=', 5)->count();
        $outOfStockCount = \App\Models\Product::where('stock', '<=', 0)->count();

        // Tab counts (always from full DB)
        $totalCount   = $totalProducts;
        $shopifyCount = \App\Models\Product::whereNotNull('shopify_id')->count();
        $localCount   = \App\Models\Product::whereNull('shopify_id')->count();

        $categories = \App\Models\Product::whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category');
        $settings   = \App\Models\Setting::getAllSettings();

        return view('products.index', compact(
            'products',
            'totalCount',
            'shopifyCount',
            'localCount',
            'totalProducts',
            'stockValue',
            'lowStockCount',
            'outOfStockCount',
            'categories',
            'settings'
        ));
    })->middleware('permission:products')->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    // Variant management (AJAX)
    Route::get('/products/{product}/variants', [ProductController::class, 'variantsList'])->name('products.variants.index');
    Route::post('/products/{product}/variants', [ProductController::class, 'storeVariant'])->name('products.variants.store');
    Route::patch('/products/{product}/variants/{variant}/stock', [ProductController::class, 'updateVariantStock'])->name('products.variants.updateStock');
    Route::delete('/products/{product}/variants/{variant}', [ProductController::class, 'destroyVariant'])->name('products.variants.destroy');


    // Sales History (accessible via /sales and /orders)
    Route::get('/orders', fn() => redirect()->route('sales.index'))->middleware('permission:sales');
    Route::get('/sales', function (\Illuminate\Http\Request $request) {
        $query = \App\Models\Sale::with(['items.product', 'items.variant', 'user', 'customer', 'smsOutbox', 'returns.items'])->latest();

        // Invoice number filter — search by invoice_number or fallback to ID
        if ($request->filled('invoice')) {
            $invoiceInput = trim($request->invoice);
            $query->where(function ($q) use ($invoiceInput) {
                $q->where('invoice_number', 'like', '%' . $invoiceInput . '%')
                  ->orWhere('id', is_numeric($invoiceInput) ? (int) $invoiceInput : 0);
            });
        }
        // Date filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        // Payment method filter
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        // Staff filter
        if ($request->filled('staff_id')) {
            $query->where('user_id', $request->staff_id);
        }
        // Status filter
        if ($request->filled('status')) {
            $st = strtolower($request->status);
            if ($st === 'paid') {
                $query->where('has_return', false);
            } elseif (in_array($st, ['refunded', 'partially_refunded', 'void'])) {
                $query->where('has_return', true);
            }
        }

        $sales = $query->paginate(15)->withQueryString();

        // Summary stats (for the filtered date range)
        $statsQuery = \App\Models\Sale::query();
        if ($request->filled('invoice')) {
            $invoiceInput = trim($request->invoice);
            $statsQuery->where(function ($q) use ($invoiceInput) {
                $q->where('invoice_number', 'like', '%' . $invoiceInput . '%')
                  ->orWhere('id', is_numeric($invoiceInput) ? (int) $invoiceInput : 0);
            });
        }
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('payment_method')) {
            $statsQuery->where('payment_method', $request->payment_method);
        }
        if ($request->filled('staff_id')) {
            $statsQuery->where('user_id', $request->staff_id);
        }
        if ($request->filled('status')) {
            $st = strtolower($request->status);
            if ($st === 'paid') {
                $statsQuery->where('has_return', false);
            } elseif (in_array($st, ['refunded', 'partially_refunded', 'void'])) {
                $statsQuery->where('has_return', true);
            }
        }

        $totalSales   = $statsQuery->count();
        $totalRevenue = $statsQuery->sum('total');
        // Subtract returns for net revenue
        $returnQuery = \App\Models\ProductReturn::where('status', 'completed');
        if ($request->filled('date_from')) $returnQuery->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $returnQuery->whereDate('created_at', '<=', $request->date_to);
        $totalRefunded = $returnQuery->sum('total_refunded');
        $totalRefundedCount = $returnQuery->count();
        $netRevenue = max(0, $totalRevenue - $totalRefunded);
        $avgSale = $totalSales > 0 ? $netRevenue / $totalSales : 0;
        $staffList = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return view('sales.index', compact('sales', 'totalSales', 'totalRevenue', 'avgSale', 'totalRefunded', 'totalRefundedCount', 'netRevenue', 'staffList'));
    })->middleware('permission:sales')->name('sales.index');

    // Returns & Refunds
    Route::get('/returns', [ReturnController::class, 'index'])->middleware('permission:returns')->name('returns.index');
    Route::get('/returns/{return}', [ReturnController::class, 'show'])->name('returns.show');
    Route::get('/sales/{sale}/return', [ReturnController::class, 'create'])->name('returns.create');
    Route::post('/sales/{sale}/return', [ReturnController::class, 'store'])->name('returns.store');

    // Reports
    Route::get('/reports', [App\Http\Controllers\ReportsController::class, 'index'])->middleware('permission:reports')->name('reports.index');
    Route::get('/reports/export-pdf', [App\Http\Controllers\ReportsController::class, 'exportPdf'])->name('reports.exportPdf');
    Route::get('/reports/export-excel', [App\Http\Controllers\ReportsController::class, 'exportExcel'])->name('reports.exportExcel');

    // Cash Drawer (accessible via /cash-drawer and /drawer)
    Route::get('/drawer', fn() => redirect()->route('cash-drawer.index'))->middleware('permission:cash_drawer');
    Route::get('/cash-drawer', [CashDrawerController::class, 'index'])->middleware('permission:cash_drawer')->name('cash-drawer.index');
    Route::post('/cash-drawer/open', [CashDrawerController::class, 'open'])->name('cash-drawer.open');
    Route::post('/cash-drawer/close', [CashDrawerController::class, 'close'])->name('cash-drawer.close');
    Route::post('/cash-drawer/cash-in', [CashDrawerController::class, 'cashIn'])->name('cash-drawer.cashIn');
    Route::post('/cash-drawer/cash-out', [CashDrawerController::class, 'cashOut'])->name('cash-drawer.cashOut');
    Route::get('/cash-drawer/session/{session}', [CashDrawerController::class, 'sessionDetail'])->name('cash-drawer.session');

    // Gift Cards management (accessible via /gift-cards and /gifts)
    Route::middleware('permission:gift_cards')->group(function () {
        Route::get('/gifts', fn() => redirect()->route('gift-cards.index'));
        Route::get('/gift-cards', [App\Http\Controllers\GiftCardController::class, 'index'])->name('gift-cards.index');
        Route::get('/gift-cards/template', [App\Http\Controllers\GiftCardController::class, 'template'])->name('gift-cards.template');
        Route::post('/gift-cards', [App\Http\Controllers\GiftCardController::class, 'store'])->name('gift-cards.store');
        Route::post('/gift-cards/import', [App\Http\Controllers\GiftCardController::class, 'import'])->name('gift-cards.import');
        Route::post('/gift-cards/{giftCard}/gift', [App\Http\Controllers\GiftCardController::class, 'gift'])->name('gift-cards.gift');
        Route::post('/gift-cards/{giftCard}/sell', [App\Http\Controllers\GiftCardController::class, 'sell'])->name('gift-cards.sell');
    });

    // Settings (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    // Staff Management
    Route::middleware('admin')->group(function () {
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{id}', [StaffController::class, 'show'])->name('staff.show');
        Route::put('/staff/{id}', [StaffController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{id}', [StaffController::class, 'destroy'])->name('staff.destroy');
        Route::patch('/staff/{id}/status', [StaffController::class, 'updateStatus'])->name('staff.updateStatus');
        // Roles & Permissions Management
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    // Admin Change Username
    Route::patch('/settings/change-username', [SettingsController::class, 'changeUsername'])->name('settings.changeUsername');

    // Sales.show alias → invoice
    Route::get('/sales/{sale}', function ($sale) {
        return redirect()->route('invoice.show', ['id' => $sale]);
    })->name('sales.show');

    // Delete Sale
    Route::delete('/sales/{sale}', function ($saleId) {
        $sale = \App\Models\Sale::with('items')->findOrFail($saleId);
        
        // Restore stock
        foreach ($sale->items as $item) {
            if ($item->variant_id) {
                $variant = \App\Models\ProductVariant::find($item->variant_id);
                if ($variant) $variant->increment('stock', $item->qty);
            } else {
                $product = \App\Models\Product::find($item->product_id);
                if ($product) $product->increment('stock', $item->qty);
            }
        }
        
        // Delete items and sale
        $sale->items()->delete();
        $sale->delete();
        
        return back()->with('success', 'Sale record deleted and stock restored successfully.');
    })->name('sales.destroy')->middleware('admin');

    // Invoice
    Route::get('/invoice/{id}', function ($id) {
        $sale = \App\Models\Sale::with(['items.product', 'items.variant', 'user'])->findOrFail($id);
        $settings = \App\Models\Setting::getAllSettings();
        return view('invoice.show', compact('sale', 'settings'));
    })->name('invoice.show');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public read-only bill view (token lookup, no auth, no PII beyond the printed bill)
Route::get('/b/{token}', [PublicBillController::class, 'show'])->name('bill.public');

require __DIR__ . '/auth.php';

// Shopify Webhooks (no auth, HMAC verified)
Route::prefix('shopify/webhook')->middleware('shopify.webhook')->group(function () {
    Route::post('/orders-create', [ShopifyWebhookController::class, 'orderCreated'])
        ->name('shopify.webhook.orders-create');
    Route::post('/inventory-update', [ShopifyWebhookController::class, 'inventoryUpdated'])
        ->name('shopify.webhook.inventory-update');
});
