{{-- ============================================
     Products Page - Redesigned Dark Dashboard Experience
     Jewellery / Clothing Outlet POS
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Products')

@push('styles')
<style>
    :root {
        --dash-card-bg: #171A20;
        --dash-card-hover-bg: #1E222A;
        --dash-card-border: #272C35;
        --dash-card-border-width: 1px;
        --dash-card-radius: 8px;
        --dash-control-radius: 6px;
    }

    .dashboard-card {
        background-color: var(--dash-card-bg) !important;
        border: var(--dash-card-border-width) solid var(--dash-card-border) !important;
        border-radius: var(--dash-card-radius) !important;
        box-sizing: border-box !important;
        box-shadow: none !important;
        outline: none !important;
    }

    .dashboard-card:hover {
        background-color: var(--dash-card-hover-bg) !important;
    }

    .dashboard-control {
        background-color: #111419 !important;
        border: var(--dash-card-border-width) solid var(--dash-card-border) !important;
        border-radius: var(--dash-control-radius) !important;
        box-sizing: border-box !important;
        box-shadow: none !important;
        outline: none !important;
        color: #F5F5F5 !important;
    }

    .dashboard-control:hover {
        background-color: var(--dash-card-hover-bg) !important;
    }

    .dashboard-control:focus {
        border-color: #F5A623 !important;
        box-shadow: 0 0 0 1px #F5A623 !important;
    }

    /* Custom dark scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #111419;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #272C35;
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #3E444D;
    }
</style>
@endpush

@section('main-class', 'p-3.5 lg:p-4 pb-8 overflow-y-auto custom-scrollbar')

@php
    $isFiltered = request()->filled('search') || request()->filled('category') || request()->filled('stock') || (request()->filled('source') && request('source') !== 'all');

    // Build JSON payload for Alpine.js
    $productsPayload = $products->map(function ($p) {
        $displayStock = $p->hasVariants() ? $p->total_stock : $p->stock;
        $variantsMapped = $p->variants->map(function ($v) {
            $label = collect([$v->color, $v->size])->filter()->implode(' / ') ?: 'Standard';
            return [
                'id' => $v->id,
                'color' => $v->color,
                'color_code' => $v->color_code,
                'size' => $v->size,
                'sku' => $v->sku,
                'price' => $v->price ? (float)$v->price : null,
                'stock' => (int)$v->stock,
                'label' => $label,
            ];
        });

        return [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'category' => $p->category,
            'subcategory' => $p->subcategory,
            'price' => (float)$p->price,
            'price_formatted' => 'Rs. ' . number_format((float)$p->price, 2),
            'stock' => (int)$displayStock,
            'reorder_point' => 5,
            'image_url' => $p->image_url,
            'is_shopify' => !empty($p->shopify_id),
            'shopify_id' => $p->shopify_id,
            'variants_count' => $p->variants->count(),
            'variants' => $variantsMapped,
            'delete_url' => route('products.destroy', $p->id),
        ];
    });
@endphp

@section('content')
<div x-data="productsCatalogApp({
        initialProducts: @js($productsPayload),
        sourceTab: '{{ request('source', 'all') }}',
        totalProductsCount: {{ $totalProducts }}
     })"
     class="w-full flex flex-col select-none gap-3.5 box-border">

    <!-- ========================================================
         1. PAGE HEADER
         ======================================================== -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 shrink-0">
        <!-- Title & Subtitle -->
        <div class="flex flex-col sm:flex-row sm:items-baseline gap-2 sm:gap-3">
            <div class="flex items-center gap-2.5">
                <h1 class="font-headline-lg text-[20px] lg:text-[22px] text-on-surface m-0 font-bold tracking-tight">
                    Products
                </h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-amber-500/10 text-amber-accent border border-amber-500/25">
                    <span class="material-symbols-outlined text-[13px] text-amber-accent">inventory_2</span>
                    Inventory
                </span>
            </div>
            <p class="font-body-sm text-[12px] text-on-surface-variant m-0">
                Catalog and stock levels
            </p>
        </div>

        <!-- Right Side Actions: Add Product (Solid Amber) + Overflow ⋯ -->
        <div class="flex items-center gap-2">
            <!-- Add Product Button (Primary Solid Amber) -->
            <button type="button"
                    @click="openAddProductModal()"
                    class="h-[34px] px-3.5 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95 shadow-md">
                <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'wght' 700;">add</span>
                <span>Add Product</span>
            </button>

            <!-- Overflow ⋯ Header Menu -->
            <div class="relative" x-data="{ headerMenuOpen: false }">
                <button type="button"
                        @click="headerMenuOpen = !headerMenuOpen"
                        class="dashboard-control h-[34px] w-[34px] rounded-[6px] flex items-center justify-center text-[#8A8A93] hover:text-white cursor-pointer"
                        title="More Catalog Actions">
                    <span class="material-symbols-outlined text-[18px]">more_horiz</span>
                </button>

                <div x-show="headerMenuOpen"
                     @click.outside="headerMenuOpen = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="absolute right-0 top-full mt-1.5 w-44 rounded-[6px] bg-[#13151A] border border-white/10 shadow-2xl py-1 z-50 text-[11px]"
                     style="display: none;">
                    <button type="button"
                            @click="headerMenuOpen = false; exportCatalogCsv()"
                            class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[14px]">download</span>
                        <span>Export Catalog (CSV)</span>
                    </button>
                    <a href="{{ route('dashboard') }}"
                       class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left no-underline cursor-pointer">
                        <span class="material-symbols-outlined text-[14px]">sync</span>
                        <span>Sync with Shopify</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="p-3 rounded-[6px] bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[12px] flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-400 hover:text-white bg-transparent border-0 cursor-pointer">
                <span class="material-symbols-outlined text-[14px]">close</span>
            </button>
        </div>
    @endif
    @if(session('error'))
        <div class="p-3 rounded-[6px] bg-rose-500/10 border border-rose-500/30 text-rose-400 text-[12px] flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">error</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-400 hover:text-white bg-transparent border-0 cursor-pointer">
                <span class="material-symbols-outlined text-[14px]">close</span>
            </button>
        </div>
    @endif

    <!-- ========================================================
         2. KPI ROW (4 STAT CARDS)
         ======================================================== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-3.5 shrink-0">
        <!-- 1. Total Products -->
        <x-pos.kpi-card
            title="Total Products"
            icon="inventory_2"
            :value="number_format($totalProducts)"
            :is-primary="true"
            subtext="Catalog Items"
        />

        <!-- 2. Stock Value -->
        <x-pos.kpi-card
            title="Stock Value"
            icon="payments"
            :value="'Rs. ' . number_format($stockValue, 2)"
            subtext="Inventory Retail Worth"
        />

        <!-- 3. Low Stock (Clickable Filter Card) -->
        <a href="{{ route('products.index', ['stock' => 'low']) }}"
           class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden group transition-all duration-200 no-underline cursor-pointer">
            <div>
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[10px] lg:text-[11px] font-bold text-amber-400 uppercase tracking-[0.08em] truncate">
                        Low Stock
                    </span>
                    <span class="material-symbols-outlined text-[18px] text-amber-400 shrink-0">warning</span>
                </div>
                <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-amber-400 tracking-tight tabular-nums">
                    {{ number_format($lowStockCount) }}
                </div>
            </div>
            <div class="text-[10px] text-[#8A8A93] font-medium tracking-wide flex items-center gap-1 mt-2">
                <span>&le; 5 units remaining &rarr;</span>
            </div>
        </a>

        <!-- 4. Out of Stock (Clickable Filter Card) -->
        <a href="{{ route('products.index', ['stock' => 'out']) }}"
           class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden group transition-all duration-200 no-underline cursor-pointer">
            <div>
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[10px] lg:text-[11px] font-bold text-rose-400 uppercase tracking-[0.08em] truncate">
                        Out of Stock
                    </span>
                    <span class="material-symbols-outlined text-[18px] text-rose-400 shrink-0">block</span>
                </div>
                <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-rose-400 tracking-tight tabular-nums">
                    {{ number_format($outOfStockCount) }}
                </div>
            </div>
            <div class="text-[10px] text-[#8A8A93] font-medium tracking-wide flex items-center gap-1 mt-2">
                <span>0 units available &rarr;</span>
            </div>
        </a>
    </div>

    <!-- ========================================================
         3. TABS & FILTER BAR CARD
         ======================================================== -->
    <div class="dashboard-card rounded-[8px] p-2.5 lg:p-3 bg-[#171A20] border border-[#272C35] flex flex-col gap-2.5">
        <!-- Filter Form -->
        <form method="GET" action="{{ route('products.index') }}" id="productsFilterForm" class="w-full">
            <input type="hidden" name="source" id="productSourceInput" value="{{ request('source', 'all') }}">

            <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-2">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[200px]">
                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[16px] text-[#8A8A93]">search</span>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           x-model="searchQuery"
                           placeholder="Search product name or SKU..."
                           class="dashboard-control w-full h-[34px] pl-8 pr-3 text-[11px] text-white placeholder-[#8A8A93] bg-[#111419] rounded-[6px] border border-[#272C35] focus:border-[#F5A623] outline-none">
                </div>

                <!-- Category Select -->
                <div class="w-full lg:w-[150px]">
                    <select name="category"
                            aria-label="Category"
                            class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] cursor-pointer">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Stock Status Select -->
                <div class="w-full lg:w-[140px]">
                    <select name="stock"
                            aria-label="Stock Status"
                            class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] cursor-pointer">
                        <option value="">All Stock</option>
                        <option value="instock" {{ request('stock') === 'instock' ? 'selected' : '' }}>In Stock (>5)</option>
                        <option value="low"     {{ request('stock') === 'low'     ? 'selected' : '' }}>Low Stock (&le;5)</option>
                        <option value="out"     {{ request('stock') === 'out'     ? 'selected' : '' }}>Out of Stock (0)</option>
                    </select>
                </div>

                <!-- Sort Control -->
                <div class="w-full lg:w-[140px]">
                    <select name="sort"
                            aria-label="Sort by"
                            class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] cursor-pointer">
                        <option value="name_asc"   {{ request('sort', 'name_asc') === 'name_asc' ? 'selected' : '' }}>Name (A-Z)</option>
                        <option value="name_desc"  {{ request('sort') === 'name_desc'  ? 'selected' : '' }}>Name (Z-A)</option>
                        <option value="price_asc"  {{ request('sort') === 'price_asc'  ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="stock_desc" {{ request('sort') === 'stock_desc' ? 'selected' : '' }}>Stock: Highest</option>
                        <option value="stock_asc"  {{ request('sort') === 'stock_asc'  ? 'selected' : '' }}>Stock: Lowest</option>
                        <option value="updated"    {{ request('sort') === 'updated'    ? 'selected' : '' }}>Recently Updated</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="submit"
                            class="h-[34px] px-3.5 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95">
                        <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'wght' 700;">filter_alt</span>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('products.index') }}"
                       class="dashboard-control h-[34px] w-[34px] text-[#8A8A93] hover:text-[#EF4444] rounded-[6px] flex items-center justify-center transition-colors no-underline"
                       title="Reset filters">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </a>
                </div>
            </div>
        </form>

        <!-- Source Tabs (All / Shopify / Local) -->
        <div class="flex items-center gap-1.5 overflow-x-auto custom-scrollbar pt-1 border-t border-[#272C35]/50 select-none">
            <!-- All Products -->
            <a href="{{ route('products.index', array_merge(request()->except(['source','page']), ['source'=>'all'])) }}"
               class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors no-underline whitespace-nowrap {{ request('source', 'all') === 'all' ? 'bg-[#1E222A] text-amber-accent border-amber-accent/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white' }}">
                <span>All Products</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-white">{{ $totalCount }}</span>
            </a>

            <!-- Shopify Synced -->
            <a href="{{ route('products.index', array_merge(request()->except(['source','page']), ['source'=>'shopify'])) }}"
               class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors no-underline whitespace-nowrap {{ request('source') === 'shopify' ? 'bg-sky-500/10 text-sky-400 border-sky-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white' }}">
                <span class="material-symbols-outlined text-[14px]">cloud_sync</span>
                <span>Shopify</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-sky-400">{{ $shopifyCount }}</span>
            </a>

            <!-- Local In-Store -->
            <a href="{{ route('products.index', array_merge(request()->except(['source','page']), ['source'=>'local'])) }}"
               class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors no-underline whitespace-nowrap {{ request('source') === 'local' ? 'bg-amber-500/10 text-amber-400 border-amber-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white' }}">
                <span class="material-symbols-outlined text-[14px]">store</span>
                <span>Local (In-Store)</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-amber-400">{{ $localCount }}</span>
            </a>
        </div>
    </div>

    <!-- ========================================================
         4. PRODUCTS DATA TABLE & EMPTY STATES
         ======================================================== -->
    <div class="dashboard-card rounded-[8px] overflow-hidden bg-[#171A20] border border-[#272C35] flex flex-col">
        <!-- Table Header Bar -->
        <div class="px-3.5 lg:px-4 py-2.5 border-b border-[#272C35] flex items-center justify-between bg-[#111419]/60">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px] text-amber-accent">inventory_2</span>
                <span class="font-semibold text-white text-[13px] tracking-tight">Product Catalog</span>
                <span class="px-1.5 py-0.2 rounded bg-surface-variant/40 border border-[#272C35] text-[10px] font-bold text-amber-accent tabular-nums">
                    <span x-text="filteredProducts.length"></span>
                </span>
            </div>

            <div class="text-[11px] text-on-surface-variant">
                Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} products
            </div>
        </div>

        <!-- Desktop Dense Table View (>= md) -->
        <div class="overflow-x-auto custom-scrollbar hidden md:block" x-show="sortedProducts.length > 0">
            <table class="w-full text-left border-collapse text-body-sm">
                <thead>
                    <tr class="border-b border-[#272C35] text-on-surface-variant font-label-caps text-[10px] uppercase bg-[#111419]/30">
                        <th class="py-2.5 px-3.5 font-bold tracking-wider">Product</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider">SKU</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider">Category</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider text-right">Price</th>
                        <th class="py-2.5 px-3.5 font-bold tracking-wider">Stock</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider text-center">Source</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider">Status</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider text-right w-[90px]">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="p in sortedProducts" :key="p.id">
                        <tr @click="openProductDrawer(p)"
                            class="border-b border-[#272C35]/50 last:border-0 hover:bg-[#1E222A] transition-colors cursor-pointer group h-[58px]">

                            <!-- 1. Product (Thumbnail + Name + Subtitle) -->
                            <td class="py-2 px-3.5">
                                <div class="flex items-center gap-3">
                                    <!-- 48px Thumbnail -->
                                    <div class="w-10 h-10 rounded-[6px] bg-[#111419] border border-[#272C35] flex items-center justify-center overflow-hidden shrink-0">
                                        <template x-if="p.image_url">
                                            <img :src="p.image_url" :alt="p.name" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!p.image_url">
                                            <span class="material-symbols-outlined text-[18px] text-amber-accent/40">apparel</span>
                                        </template>
                                    </div>

                                    <!-- Name & Subtitle -->
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-[12px] text-white group-hover:text-amber-accent transition-colors truncate max-w-[240px]"
                                             x-text="p.name"></div>
                                        <div class="text-[10px] text-[#8A8A93] flex items-center gap-1.5 mt-0.5">
                                            <span x-text="p.variants_count > 0 ? (p.variants_count + ' variants') : 'Single item'"></span>
                                            <template x-if="p.subcategory">
                                                <span>&middot;</span>
                                            </template>
                                            <span x-text="p.subcategory"></span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- 2. SKU (Monospace Muted) -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <span class="font-mono text-[#d7c3ae] text-[11px]" x-text="p.sku"></span>
                            </td>

                            <!-- 3. Category -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/5 border border-white/10 text-white text-[11px] font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#F5A623]"></span>
                                    <span x-text="p.category || '—'"></span>
                                </span>
                            </td>

                            <!-- 4. Price (Right-aligned Tabular) -->
                            <td class="py-2 px-3 text-right whitespace-nowrap">
                                <span class="font-['Inter'] font-bold text-[13px] text-white tabular-nums tracking-tight"
                                      x-text="p.price_formatted"></span>
                            </td>

                            <!-- 5. Stock Level + Progress Bar -->
                            <td class="py-2 px-3.5 whitespace-nowrap min-w-[130px]">
                                <div class="flex items-center gap-2">
                                    <span class="font-['Inter'] font-bold text-[13px] tabular-nums w-7"
                                          :class="p.stock > 5 ? 'text-emerald-400' : (p.stock > 0 ? 'text-amber-400' : 'text-rose-400')"
                                          x-text="p.stock"></span>

                                    <!-- Mini level bar -->
                                    <div class="w-16 h-1.5 bg-[#111419] rounded-full overflow-hidden border border-[#272C35]">
                                        <div class="h-full rounded-full"
                                             :class="p.stock > 5 ? 'bg-emerald-400' : (p.stock > 0 ? 'bg-amber-400' : 'bg-rose-500')"
                                             :style="`width: ${Math.min(100, Math.max(0, (p.stock / 20) * 100))}%`"></div>
                                    </div>

                                    <template x-if="p.stock > 0 && p.stock <= 5">
                                        <span class="px-1 py-0.2 rounded text-[9px] font-bold uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20">Low</span>
                                    </template>
                                    <template x-if="p.stock <= 0">
                                        <span class="px-1 py-0.2 rounded text-[9px] font-bold uppercase bg-rose-500/10 text-rose-400 border border-rose-500/20">Out</span>
                                    </template>
                                </div>
                            </td>

                            <!-- 6. Source Icon Badge -->
                            <td class="py-2 px-3 text-center whitespace-nowrap">
                                <template x-if="p.is_shopify">
                                    <span class="w-6 h-6 rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/25 inline-flex items-center justify-center"
                                          title="Synced from Shopify">
                                        <span class="material-symbols-outlined text-[13px]">cloud_sync</span>
                                    </span>
                                </template>
                                <template x-if="!p.is_shopify">
                                    <span class="w-6 h-6 rounded-full bg-amber-500/10 text-amber-accent border border-amber-500/25 inline-flex items-center justify-center"
                                          title="Local In-Store Product">
                                        <span class="material-symbols-outlined text-[13px]">store</span>
                                    </span>
                                </template>
                            </td>

                            <!-- 7. Status Pill -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <template x-if="p.stock > 0">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Active
                                    </span>
                                </template>
                                <template x-if="p.stock <= 0">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>Out of Stock
                                    </span>
                                </template>
                            </td>

                            <!-- 8. Actions (Single Row, No Wrap!) -->
                            <td class="py-2 px-3 text-right whitespace-nowrap" @click.stop>
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Edit Pencil Button (if local) -->
                                    <template x-if="!p.is_shopify">
                                        <button type="button"
                                                @click="openEditModal(p)"
                                                class="w-7 h-7 rounded-[4px] bg-[#111419] hover:bg-amber-accent/15 text-[#8A8A93] hover:text-amber-accent border border-[#272C35] flex items-center justify-center transition-colors cursor-pointer"
                                                title="Edit Product">
                                            <span class="material-symbols-outlined text-[14px]">edit</span>
                                        </button>
                                    </template>

                                    <!-- Overflow ⋯ Menu Button -->
                                    <button type="button"
                                            @click.stop="toggleProductActionMenu(p, $event)"
                                            class="w-7 h-7 rounded-[4px] bg-[#111419] hover:bg-[#1E222A] text-[#8A8A93] hover:text-white border border-[#272C35] flex items-center justify-center transition-colors cursor-pointer"
                                            title="More Actions">
                                        <span class="material-symbols-outlined text-[15px]">more_horiz</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Mobile Stacked Card View (< md) -->
        <div class="md:hidden flex flex-col divide-y divide-[#272C35]/50" x-show="sortedProducts.length > 0">
            <template x-for="p in sortedProducts" :key="'mob-' + p.id">
                <div @click="openProductDrawer(p)"
                     class="p-3 hover:bg-[#1E222A] transition-colors cursor-pointer flex flex-col gap-2">
                    
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 rounded-[6px] bg-[#111419] border border-[#272C35] flex items-center justify-center overflow-hidden shrink-0">
                            <template x-if="p.image_url">
                                <img :src="p.image_url" :alt="p.name" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!p.image_url">
                                <span class="material-symbols-outlined text-[20px] text-amber-accent/40">apparel</span>
                            </template>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-[13px] text-white truncate" x-text="p.name"></span>
                                <span class="font-['Inter'] font-bold text-[13px] text-white tabular-nums" x-text="p.price_formatted"></span>
                            </div>
                            <div class="flex items-center gap-2 text-[10px] text-[#8A8A93] mt-0.5">
                                <span class="font-mono" x-text="p.sku"></span>
                                <span>&middot;</span>
                                <span x-text="p.category"></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1 border-t border-[#272C35]/50 text-[11px]" @click.stop>
                        <div class="flex items-center gap-2">
                            <span class="font-bold tabular-nums"
                                  :class="p.stock > 5 ? 'text-emerald-400' : (p.stock > 0 ? 'text-amber-400' : 'text-rose-400')"
                                  x-text="p.stock + ' units'"></span>
                            <span class="px-1.5 py-0.2 rounded text-[9px] uppercase font-bold"
                                  :class="p.stock > 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'"
                                  x-text="p.stock > 0 ? 'Active' : 'Out of Stock'"></span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <template x-if="!p.is_shopify">
                                <button type="button"
                                        @click="openEditModal(p)"
                                        class="px-2 py-1 rounded bg-[#111419] text-[#d7c3ae] border border-[#272C35] text-[10px] font-semibold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">edit</span> Edit
                                </button>
                            </template>
                            <button type="button"
                                    @click.stop="toggleProductActionMenu(p, $event)"
                                    class="w-7 h-6 rounded bg-[#111419] text-[#8A8A93] hover:text-white border border-[#272C35] flex items-center justify-center text-[10px]">
                                <span class="material-symbols-outlined text-[14px]">more_horiz</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- 1. Default First-Run Empty State (0 products total in DB) -->
        <div x-show="totalProductsCount === 0 && !isFiltered" class="py-16 px-4 text-center">
            <div class="w-14 h-14 rounded-full bg-surface-variant/40 border border-[#272C35] flex items-center justify-center mx-auto mb-3 text-[#5A5A63]">
                <span class="material-symbols-outlined text-[28px]">inventory_2</span>
            </div>
            <h3 class="text-[16px] font-bold text-white mb-1">No products yet</h3>
            <p class="text-[12px] text-[#8A8A93] max-w-sm mx-auto mb-4 leading-relaxed">
                Add your first in-store product or sync with Shopify to populate your catalog.
            </p>
            <button type="button"
                    @click="openAddProductModal()"
                    class="h-[34px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center gap-1.5 cursor-pointer border-0 transition-colors shadow-md mx-auto">
                <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'wght' 700;">add</span>
                <span>Add your first product</span>
            </button>
        </div>

        <!-- 2. Filtered Empty State (No products match active filters) -->
        <div x-show="(totalProductsCount > 0 || isFiltered) && sortedProducts.length === 0" class="py-14 px-4 text-center">
            <span class="material-symbols-outlined text-[36px] text-[#5A5A63] mb-2 block">filter_alt_off</span>
            <div class="text-[14px] font-semibold text-white mb-1">No products match these filters</div>
            <p class="text-[12px] text-[#8A8A93] max-w-sm mx-auto mb-3">
                Try adjusting your search query, category, or stock status filter.
            </p>
            <a href="{{ route('products.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[6px] bg-[#111419] hover:bg-[#1E222A] text-amber-accent border border-[#272C35] text-[11px] font-bold uppercase tracking-wider no-underline transition-colors">
                <span class="material-symbols-outlined text-[14px]">refresh</span>
                <span>Clear Filters</span>
            </a>
        </div>

        <!-- ========================================================
             5. FOOTER & PAGINATION
             ======================================================== -->
        @if ($products->hasPages())
            <div class="p-3 border-t border-[#272C35] flex flex-col sm:flex-row items-center justify-between gap-3 bg-[#111419]/40">
                <div class="text-[11px] text-[#8A8A93]">
                    Showing {{ $products->firstItem() ?? 0 }} to {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} products
                </div>

                <!-- Custom Dark Pagination -->
                <div class="flex items-center gap-1 select-none">
                    @if ($products->onFirstPage())
                        <span class="dashboard-control w-8 h-8 rounded-[4px] opacity-40 flex items-center justify-center cursor-not-allowed">
                            <span class="material-symbols-outlined text-[16px] text-[#8A8A93]">chevron_left</span>
                        </span>
                    @else
                        <a href="{{ $products->previousPageUrl() }}"
                           class="dashboard-control w-8 h-8 rounded-[4px] flex items-center justify-center hover:text-amber-accent transition-colors no-underline">
                            <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                        </a>
                    @endif

                    @foreach ($products->getUrlRange(max(1, $products->currentPage() - 2), min($products->lastPage(), $products->currentPage() + 2)) as $page => $url)
                        @if ($page == $products->currentPage())
                            <span class="w-8 h-8 rounded-[4px] bg-[#1E222A] border border-[#F5A623]/50 text-amber-accent font-bold text-[12px] flex items-center justify-center tabular-nums">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="dashboard-control w-8 h-8 rounded-[4px] text-[#d7c3ae] hover:text-white font-medium text-[12px] flex items-center justify-center transition-colors no-underline tabular-nums">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    @if ($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}"
                           class="dashboard-control w-8 h-8 rounded-[4px] flex items-center justify-center hover:text-amber-accent transition-colors no-underline">
                            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                        </a>
                    @else
                        <span class="dashboard-control w-8 h-8 rounded-[4px] opacity-40 flex items-center justify-center cursor-not-allowed">
                            <span class="material-symbols-outlined text-[16px] text-[#8A8A93]">chevron_right</span>
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- ========================================================
         6. SHARED PRODUCT DETAIL DRAWER (Teleported to Body)
         ======================================================== -->
    <x-pos.product-drawer />

    <!-- Actions "⋯" Dropdown (Teleported to Body with Fixed Coordinates) -->
    <template x-teleport="body">
        <div id="productActionsFloatingMenu"
             x-show="activeMenuProduct"
             @click.outside="closeProductActionMenu()"
             @keydown.escape.window="closeProductActionMenu()"
             :style="`position: fixed; top: ${menuY}px; left: ${menuX}px; width: 190px; z-index: 9999; margin: 0;`"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="rounded-[6px] bg-[#13151A] border border-white/10 shadow-2xl py-1.5 flex flex-col text-left text-[11px] select-none"
             style="display: none;">
            
            <!-- Manage Variants -->
            <button type="button"
                    @click="const p = activeMenuProduct; closeProductActionMenu(); openVariantsModal(p)"
                    class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer transition-colors">
                <span class="material-symbols-outlined text-[14px]">palette</span>
                <span>Manage Variants</span>
            </button>

            <!-- Print Barcode -->
            <template x-if="!activeMenuProduct?.is_shopify">
                <button type="button"
                        @click="const p = activeMenuProduct; closeProductActionMenu(); openBarcodeModal(p)"
                        class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer transition-colors">
                    <span class="material-symbols-outlined text-[14px]">barcode</span>
                    <span>Print Barcode Label</span>
                </button>
            </template>

            <!-- Edit (if local) -->
            <template x-if="!activeMenuProduct?.is_shopify">
                <button type="button"
                        @click="const p = activeMenuProduct; closeProductActionMenu(); openEditModal(p)"
                        class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer transition-colors">
                    <span class="material-symbols-outlined text-[14px]">edit</span>
                    <span>Edit Product</span>
                </button>
            </template>

            <!-- Delete Product (Red) -->
            <template x-if="!activeMenuProduct?.is_shopify">
                <button type="button"
                        @click="const p = activeMenuProduct; closeProductActionMenu(); confirmDeleteProduct(p)"
                        class="px-3 py-1.5 text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer transition-colors border-t border-[#272C35]/50 mt-1">
                    <span class="material-symbols-outlined text-[14px]">delete</span>
                    <span>Delete Product</span>
                </button>
            </template>
        </div>
    </template>

    <!-- Hidden form for deleting product -->
    <form id="deleteProductRealForm" method="POST" action="" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

    <!-- ========================================================
         7. ADD / EDIT / VARIANTS MODALS (Preserving Logic & Dark Theme)
         ======================================================== -->
    
    <!-- Modal: Add Product Modal -->
    <template x-teleport="body">
        <div x-show="addProductModalOpen"
             @keydown.escape.window="addProductModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-2xl rounded-[8px] bg-[#13151A] border border-[#272C35] p-5 shadow-2xl flex flex-col gap-3.5 max-h-[90vh] overflow-y-auto custom-scrollbar">
                <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="flex items-center justify-between border-b border-[#272C35] pb-3 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px] text-amber-accent">add_box</span>
                            <h4 class="m-0 text-[15px] font-bold text-white">Add Local Product (In-Store)</h4>
                        </div>
                        <button type="button" @click="addProductModalOpen = false" class="dashboard-control w-6 h-6 rounded flex items-center justify-center text-[#8A8A93] hover:text-white border-0 cursor-pointer">
                            <span class="material-symbols-outlined text-[15px]">close</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-[12px]">
                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Product Name <span class="text-rose-400">*</span></label>
                            <input type="text" name="name" required placeholder="e.g. Classic Pima Cotton T-Shirt"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px]">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">SKU <span class="text-rose-400">*</span></label>
                            <input type="text" name="sku" required placeholder="e.g. TEE-PIMA-BLK"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px] font-mono">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Category <span class="text-rose-400">*</span></label>
                            <input type="text" name="category" required placeholder="e.g. Men, Tops..." list="addCatList"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px]">
                            <datalist id="addCatList">
                                @foreach($categories as $c) <option value="{{ $c }}"> @endforeach
                            </datalist>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Sub-Category</label>
                            <input type="text" name="subcategory" placeholder="e.g. T-Shirts, Jeans..."
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px]">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Retail Price (Rs.) <span class="text-rose-400">*</span></label>
                            <input type="number" name="price" step="0.01" min="0" required placeholder="0.00"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px] font-['Inter'] tabular-nums">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Stock Quantity <span class="text-rose-400">*</span></label>
                            <input type="number" name="stock" min="0" required placeholder="0"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px] font-['Inter'] tabular-nums">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Product Image</label>
                            <input type="file" name="image" accept="image/*"
                                   class="dashboard-control w-full h-[36px] px-2 text-[11px] text-white bg-[#111419] rounded-[6px] file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:bg-amber-accent file:text-black">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 mt-4 border-t border-[#272C35]">
                        <button type="button" @click="addProductModalOpen = false" class="dashboard-control h-[32px] px-3 text-[11px] font-semibold text-[#d7c3ae] hover:text-white rounded-[6px]">
                            Cancel
                        </button>
                        <button type="submit" class="h-[32px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] border-0 cursor-pointer">
                            Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Modal: Edit Product Modal -->
    <template x-teleport="body">
        <div x-show="editProductModalOpen"
             @keydown.escape.window="editProductModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-2xl rounded-[8px] bg-[#13151A] border border-[#272C35] p-5 shadow-2xl flex flex-col gap-3.5 max-h-[90vh] overflow-y-auto custom-scrollbar">
                <form method="POST" :action="editingProduct?.id ? `/products/${editingProduct.id}` : '#'" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="flex items-center justify-between border-b border-[#272C35] pb-3 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px] text-amber-accent">edit_note</span>
                            <h4 class="m-0 text-[15px] font-bold text-white">Edit Product</h4>
                        </div>
                        <button type="button" @click="editProductModalOpen = false" class="dashboard-control w-6 h-6 rounded flex items-center justify-center text-[#8A8A93] hover:text-white border-0 cursor-pointer">
                            <span class="material-symbols-outlined text-[15px]">close</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-[12px]">
                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Product Name <span class="text-rose-400">*</span></label>
                            <input type="text" name="name" x-model="editingProduct.name" required
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px]">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">SKU <span class="text-rose-400">*</span></label>
                            <input type="text" name="sku" x-model="editingProduct.sku" required
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px] font-mono">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Category <span class="text-rose-400">*</span></label>
                            <input type="text" name="category" x-model="editingProduct.category" required list="editCatList"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px]">
                            <datalist id="editCatList">
                                @foreach($categories as $c) <option value="{{ $c }}"> @endforeach
                            </datalist>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Sub-Category</label>
                            <input type="text" name="subcategory" x-model="editingProduct.subcategory"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px]">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Retail Price (Rs.) <span class="text-rose-400">*</span></label>
                            <input type="number" name="price" x-model="editingProduct.price" step="0.01" min="0" required
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px] font-['Inter'] tabular-nums">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Stock Quantity <span class="text-rose-400">*</span></label>
                            <input type="number" name="stock" x-model="editingProduct.stock" min="0" required
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px] font-['Inter'] tabular-nums">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Replace Image</label>
                            <input type="file" name="image" accept="image/*"
                                   class="dashboard-control w-full h-[36px] px-2 text-[11px] text-white bg-[#111419] rounded-[6px] file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:bg-amber-accent file:text-black">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 mt-4 border-t border-[#272C35]">
                        <button type="button" @click="editProductModalOpen = false" class="dashboard-control h-[32px] px-3 text-[11px] font-semibold text-[#d7c3ae] hover:text-white rounded-[6px]">
                            Cancel
                        </button>
                        <button type="submit" class="h-[32px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] border-0 cursor-pointer">
                            Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Modal: Variants Modal -->
    <template x-teleport="body">
        <div x-show="variantsModalOpen"
             @keydown.escape.window="variantsModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-lg rounded-[8px] bg-[#13151A] border border-[#272C35] p-5 shadow-2xl flex flex-col gap-3.5">
                <div class="flex items-center justify-between border-b border-[#272C35] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-amber-accent">palette</span>
                        <h4 class="m-0 text-[15px] font-bold text-white" x-text="'Variants & Options: ' + (activeVariantsProduct?.name || '')"></h4>
                    </div>
                    <button type="button" @click="variantsModalOpen = false" class="dashboard-control w-6 h-6 rounded flex items-center justify-center text-[#8A8A93] hover:text-white border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <!-- Existing Variants List -->
                <div class="flex flex-col gap-2 max-h-[300px] overflow-y-auto custom-scrollbar pr-1">
                    <template x-for="v in (activeVariantsProduct?.variants || [])" :key="v.id">
                        <div class="p-2.5 rounded bg-[#171A20] border border-[#272C35] flex items-center justify-between text-[12px]">
                            <div class="flex items-center gap-2">
                                <template x-if="v.color_code">
                                    <span class="w-4 h-4 rounded-full border border-white/20 shrink-0"
                                          :style="`background-color: ${v.color_code};`"></span>
                                </template>
                                <div>
                                    <span class="text-white font-medium block" x-text="v.label"></span>
                                    <span class="font-mono text-[10px] text-[#8A8A93]" x-text="'SKU: ' + (v.sku || activeVariantsProduct.sku)"></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-bold tabular-nums"
                                      :class="v.stock > 0 ? 'text-emerald-400' : 'text-rose-400'"
                                      x-text="v.stock + ' in stock'"></span>
                            </div>
                        </div>
                    </template>

                    <template x-if="!activeVariantsProduct?.variants || activeVariantsProduct?.variants?.length === 0">
                        <div class="p-6 text-center text-[#8A8A93] text-[12px]">
                            No variants defined for this product.
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-end pt-2 border-t border-[#272C35]">
                    <button type="button" @click="variantsModalOpen = false" class="dashboard-control h-[32px] px-4 text-[11px] font-semibold text-[#d7c3ae] hover:text-white rounded-[6px]">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@floating-ui/core@1.6.0"></script>
<script src="https://cdn.jsdelivr.net/npm/@floating-ui/dom@1.6.3"></script>
<script>
function productsCatalogApp(config) {
    return {
        products: config.initialProducts || [],
        sourceTab: config.sourceTab || 'all',
        totalProductsCount: config.totalProductsCount || 0,
        searchQuery: '',
        drawerOpen: false,
        activeProduct: null,
        activeMenuProduct: null,
        menuX: 0,
        menuY: 0,

        // Modal states
        addProductModalOpen: false,
        editProductModalOpen: false,
        editingProduct: {},
        variantsModalOpen: false,
        activeVariantsProduct: null,

        get filteredProducts() {
            if (!this.searchQuery.trim()) {
                return this.products;
            }
            const q = this.searchQuery.toLowerCase().trim();
            return this.products.filter(p => {
                const nameMatch = (p.name || '').toLowerCase().includes(q);
                const skuMatch = (p.sku || '').toLowerCase().includes(q);
                const catMatch = (p.category || '').toLowerCase().includes(q);
                return nameMatch || skuMatch || catMatch;
            });
        },

        get sortedProducts() {
            return [...this.filteredProducts];
        },

        openProductDrawer(product) {
            this.activeProduct = product;
            this.drawerOpen = true;
        },

        toggleProductActionMenu(product, event) {
            if (this.activeMenuProduct && this.activeMenuProduct.id === product.id) {
                this.closeProductActionMenu();
                return;
            }

            const btn = event.currentTarget || event.target.closest('button');
            if (!btn) return;

            const rect = btn.getBoundingClientRect();
            const menuWidth = 190;
            const menuHeight = 140;

            let left = rect.right - menuWidth;
            if (left < 8) left = 8;
            if (left + menuWidth > window.innerWidth - 8) {
                left = window.innerWidth - menuWidth - 8;
            }

            let top = rect.bottom + 6;
            if (rect.bottom + menuHeight + 8 > window.innerHeight && rect.top > menuHeight + 8) {
                top = rect.top - menuHeight - 6;
            }

            this.menuX = Math.round(left);
            this.menuY = Math.round(top);
            this.activeMenuProduct = product;

            this.$nextTick(() => {
                const menuEl = document.getElementById('productActionsFloatingMenu');
                if (btn && menuEl && window.FloatingUIDOM) {
                    const { computePosition, offset, flip, shift } = window.FloatingUIDOM;
                    computePosition(btn, menuEl, {
                        placement: 'bottom-end',
                        strategy: 'fixed',
                        middleware: [
                            offset(6),
                            flip({ padding: 8 }),
                            shift({ padding: 8 })
                        ]
                    }).then(({ x, y }) => {
                        this.menuX = Math.round(x);
                        this.menuY = Math.round(y);
                    }).catch(() => {});
                }
            });
        },

        closeProductActionMenu() {
            this.activeMenuProduct = null;
        },

        openAddProductModal() {
            this.addProductModalOpen = true;
        },

        openEditModal(product) {
            this.editingProduct = {
                id: product.id,
                name: product.name,
                sku: product.sku,
                category: product.category,
                subcategory: product.subcategory,
                price: product.price,
                stock: product.stock,
            };
            this.editProductModalOpen = true;
        },

        openVariantsModal(product) {
            this.activeVariantsProduct = product;
            this.variantsModalOpen = true;
        },

        openBarcodeModal(product) {
            // Open standard barcode label generation for product
            window.open(`/products?print_barcode=${product.id}`, '_blank');
        },

        confirmDeleteProduct(product) {
            if (confirm(`Are you sure you want to delete "${product.name}"? This action cannot be undone.`)) {
                const f = document.getElementById('deleteProductRealForm');
                f.action = product.delete_url;
                f.submit();
            }
        },

        exportCatalogCsv() {
            const rows = this.filteredProducts;
            if (!rows.length) {
                alert('No products to export.');
                return;
            }

            const headers = ['ID', 'Name', 'SKU', 'Category', 'Subcategory', 'Price', 'Stock', 'Source'];
            const csvRows = [headers.join(',')];

            rows.forEach(p => {
                const values = [
                    p.id,
                    `"${p.name.replace(/"/g, '""')}"`,
                    `"${p.sku}"`,
                    `"${(p.category || '').replace(/"/g, '""')}"`,
                    `"${(p.subcategory || '').replace(/"/g, '""')}"`,
                    p.price.toFixed(2),
                    p.stock,
                    p.is_shopify ? 'Shopify' : 'Local',
                ];
                csvRows.push(values.join(','));
            });

            const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', `catalog_export_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };
}
</script>
@endpush
