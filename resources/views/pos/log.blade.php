{{-- ============================================
POS Sales Log - Redesigned Dark Dashboard Experience
Jewellery / Clothing Outlet POS
============================================ --}}
@extends('layouts.pos')
@section('title', 'POS Sales Log')

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

    /* Dark native date picker overrides */
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(0.8) sepia(100%) saturate(100%) hue-rotate(0deg);
        cursor: pointer;
        opacity: 0.7;
    }
    input[type="date"]::-webkit-calendar-picker-indicator:hover {
        opacity: 1;
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
    // Prepare JSON payload for Alpine.js client-side interactivity (drawer, sorting, fast receipt preview)
    $salesPayload = $sales->map(function ($sale) {
        $billNo = $sale->invoice_number ?? ('POS-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT));
        $itemsMapped = $sale->items->map(function ($item) {
            $variantLabel = $item->variant 
                ? collect([$item->variant->color, $item->variant->size])->filter()->implode(' / ') 
                : null;
            return [
                'id' => $item->id,
                'name' => $item->product->name ?? $item->item_label ?? 'Deleted Product',
                'sku' => $item->variant->sku ?? $item->product->sku ?? null,
                'variant' => $variantLabel,
                'qty' => (int) $item->qty,
                'price' => (float) $item->price,
                'price_formatted' => 'Rs. ' . number_format((float) $item->price, 2),
                'subtotal_formatted' => 'Rs. ' . number_format((float) $item->price * (int) $item->qty, 2),
            ];
        });

        return [
            'id' => $sale->id,
            'bill_number' => '#' . $billNo,
            'plain_bill_number' => $billNo,
            'created_at_formatted' => $sale->created_at->format('M d, Y · h:i A'),
            'date_formatted' => $sale->created_at->format('M d, Y'),
            'time_formatted' => $sale->created_at->format('h:i A'),
            'timestamp' => $sale->created_at->timestamp,
            'staff_name' => $sale->user->name ?? 'Staff',
            'customer_name' => $sale->customer->name ?? null,
            'customer_phone' => $sale->customer->phone ?? null,
            'payment_method' => $sale->payment_method ?? 'cash',
            'subtotal' => (float) $sale->subtotal,
            'subtotal_formatted' => 'Rs. ' . number_format((float) $sale->subtotal, 2),
            'discount_amount' => (float) $sale->discount_amount,
            'discount_formatted' => (float) $sale->discount_amount > 0 ? '-Rs. ' . number_format((float) $sale->discount_amount, 2) : '—',
            'discount_type' => $sale->discount_type,
            'discount_value' => $sale->discount_value,
            'total' => (float) $sale->total,
            'total_formatted' => 'Rs. ' . number_format((float) $sale->total, 2),
            'amount_received' => $sale->amount_received ? (float) $sale->amount_received : null,
            'amount_received_formatted' => $sale->amount_received ? 'Rs. ' . number_format((float) $sale->amount_received, 2) : null,
            'change_amount' => $sale->change_amount ? (float) $sale->change_amount : null,
            'change_amount_formatted' => $sale->change_amount ? 'Rs. ' . number_format((float) $sale->change_amount, 2) : null,
            'payment_splits' => $sale->payment_splits ?? [],
            'notes' => $sale->notes ?? null,
            'items_count' => (int) $sale->items->sum('qty'),
            'distinct_items_count' => $sale->items->count(),
            'items' => $itemsMapped,
            'first_item_name' => $itemsMapped->first()['name'] ?? 'No items',
            'first_item_variant' => $itemsMapped->first()['variant'] ?? null,
            'invoice_url' => route('invoice.show', ['id' => $sale->id]),
            'embed_url' => route('invoice.show', ['id' => $sale->id, 'embed' => 1]),
        ];
    });

    // Detect active range preset
    $dateFrom = request('date_from');
    $dateTo = request('date_to');
    $todayStr = now()->format('Y-m-d');
    $sevenDaysAgo = now()->subDays(6)->format('Y-m-d');
    $thirtyDaysAgo = now()->subDays(29)->format('Y-m-d');

    $activeRange = 'custom';
    if (!$dateFrom && !$dateTo) {
        $activeRange = 'all';
    } elseif ($dateFrom === $todayStr && $dateTo === $todayStr) {
        $activeRange = 'today';
    } elseif ($dateFrom === $sevenDaysAgo && $dateTo === $todayStr) {
        $activeRange = '7d';
    } elseif ($dateFrom === $thirtyDaysAgo && $dateTo === $todayStr) {
        $activeRange = '30d';
    }
@endphp

@section('content')
<div x-data="posSalesLogApp(@js($salesPayload), '{{ $activeRange }}')"
     x-init="initLog()"
     class="w-full flex flex-col select-none gap-3.5 box-border">

    <!-- ========================================================
         1. PAGE HEADER
         ======================================================== -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 shrink-0">
        <!-- Title & Subtitle + Muted Admin Badge -->
        <div class="flex flex-col sm:flex-row sm:items-baseline gap-2 sm:gap-3">
            <div class="flex items-center gap-2.5">
                <h1 class="font-headline-lg text-[20px] lg:text-[22px] text-on-surface m-0 font-bold tracking-tight">
                    POS Sales Log
                </h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-surface-variant/40 text-on-surface-variant border border-outline-variant/30">
                    <span class="material-symbols-outlined text-[13px] text-amber-accent">shield</span>
                    Admin Only
                </span>
            </div>
            <p class="font-body-sm text-[12px] text-on-surface-variant m-0">
                Complete transaction history
            </p>
        </div>

        <!-- Right Side: Segmented Range [TODAY | 7D | 30D | ALL] + Export -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <!-- Segmented Date Range -->
            <div class="dashboard-control flex items-center p-[3px] h-[34px] box-border" role="radiogroup" aria-label="Date Range Selector">
                <button type="button"
                        @click="setDateRangePreset('today')"
                        :class="currentRange === 'today' ? 'bg-[#1E222A] text-[#F5A623] font-bold shadow-none' : 'text-[#d7c3ae] hover:text-[#FFFFFF] font-semibold bg-transparent'"
                        class="px-2.5 sm:px-3 h-full rounded-[4px] font-['Inter'] text-[11px] uppercase tracking-wider transition-colors cursor-pointer border-0 flex items-center justify-center active:scale-95"
                        role="radio"
                        :aria-checked="currentRange === 'today'">
                    TODAY
                </button>
                <button type="button"
                        @click="setDateRangePreset('7d')"
                        :class="currentRange === '7d' ? 'bg-[#1E222A] text-[#F5A623] font-bold shadow-none' : 'text-[#d7c3ae] hover:text-[#FFFFFF] font-semibold bg-transparent'"
                        class="px-2.5 sm:px-3 h-full rounded-[4px] font-['Inter'] text-[11px] uppercase tracking-wider transition-colors cursor-pointer border-0 flex items-center justify-center active:scale-95"
                        role="radio"
                        :aria-checked="currentRange === '7d'">
                    7D
                </button>
                <button type="button"
                        @click="setDateRangePreset('30d')"
                        :class="currentRange === '30d' ? 'bg-[#1E222A] text-[#F5A623] font-bold shadow-none' : 'text-[#d7c3ae] hover:text-[#FFFFFF] font-semibold bg-transparent'"
                        class="px-2.5 sm:px-3 h-full rounded-[4px] font-['Inter'] text-[11px] uppercase tracking-wider transition-colors cursor-pointer border-0 flex items-center justify-center active:scale-95"
                        role="radio"
                        :aria-checked="currentRange === '30d'">
                    30D
                </button>
                <button type="button"
                        @click="setDateRangePreset('all')"
                        :class="currentRange === 'all' ? 'bg-[#1E222A] text-[#F5A623] font-bold shadow-none' : 'text-[#d7c3ae] hover:text-[#FFFFFF] font-semibold bg-transparent'"
                        class="px-2.5 sm:px-3 h-full rounded-[4px] font-['Inter'] text-[11px] uppercase tracking-wider transition-colors cursor-pointer border-0 flex items-center justify-center active:scale-95"
                        role="radio"
                        :aria-checked="currentRange === 'all'">
                    ALL
                </button>
            </div>

            <!-- Export Button (CSV Download of current dataset) -->
            <button type="button"
                    @click="exportToCsv()"
                    class="dashboard-control h-[34px] px-3 flex items-center gap-1.5 text-white hover:text-[#F5A623] transition-colors active:scale-95 border-0 font-['Inter'] text-[11px] font-bold uppercase tracking-wider cursor-pointer box-border"
                    title="Export filtered transactions to CSV">
                <span class="material-symbols-outlined text-[16px] text-white" style="font-variation-settings: 'wght' 400, 'opsz' 20;">download</span>
                <span>EXPORT</span>
            </button>
        </div>
    </div>

    <!-- ========================================================
         2. KPI ROW (4 CARDS)
         ======================================================== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-3.5 shrink-0">
        <!-- KPI 1: Total POS Orders (Primary Glow) -->
        <x-pos.kpi-card
            title="Total POS Orders"
            icon="receipt_long"
            :value="number_format($totalOrders)"
            :is-primary="true"
            subtext="Active Filters"
        />

        <!-- KPI 2: Total Revenue -->
        <x-pos.kpi-card
            title="Total Revenue"
            icon="payments"
            :value="'Rs. ' . number_format($totalRevenue, 2)"
            subtext="Gross Total"
        />

        <!-- KPI 3: Total Discounts -->
        <x-pos.kpi-card
            title="Total Discounts"
            icon="sell"
            :value="'Rs. ' . number_format($totalDiscounts, 2)"
            subtext="Deductions"
        />

        <!-- KPI 4: Items Sold -->
        <x-pos.kpi-card
            title="Items Sold"
            icon="inventory_2"
            :value="number_format($totalItems)"
            subtext="Units"
        />
    </div>

    <!-- ========================================================
         3. FILTER TOOLBAR & CHIPS
         ======================================================== -->
    <div class="dashboard-card rounded-[8px] p-2.5 lg:p-3 bg-[#171A20] border border-[#272C35] flex flex-col gap-2.5">
        <!-- Filter Form -->
        <form id="filterForm" method="GET" action="{{ route('pos.log') }}" class="w-full">
            <!-- Mobile Toggle Bar for Small Viewports (< 1024px) -->
            <div class="flex lg:hidden items-center justify-between gap-2">
                <div class="relative flex-1">
                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[16px] text-[#8A8A93]">search</span>
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Quick search bill #, item, staff..."
                           class="dashboard-control w-full h-[34px] pl-8 pr-3 text-[12px] text-white placeholder-[#8A8A93] bg-[#111419] rounded-[6px]">
                </div>
                <button type="button"
                        @click="filtersDrawerOpen = !filtersDrawerOpen"
                        class="dashboard-control h-[34px] px-3 flex items-center gap-1.5 text-[#F5F5F5] font-semibold text-[11px] uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[16px] text-amber-accent">filter_alt</span>
                    <span>Filters</span>
                    @if(request()->hasAny(['date_from', 'date_to', 'payment_method', 'staff_id']))
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-accent"></span>
                    @endif
                </button>
            </div>

            <!-- Toolbar Inputs Row (Always visible on lg+, collapsable on mobile) -->
            <div :class="filtersDrawerOpen ? 'flex' : 'hidden lg:flex'"
                 class="flex-col lg:flex-row items-stretch lg:items-center gap-2 pt-2.5 lg:pt-0 border-t lg:border-t-0 border-[#272C35]">

                <!-- Search Input (Desktop) -->
                <div class="relative flex-1 min-w-[200px] max-w-sm hidden lg:block">
                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[16px] text-[#8A8A93]">search</span>
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Search bill #, item, customer, SKU..."
                           class="dashboard-control w-full h-[34px] pl-8 pr-3 text-[12px] text-white placeholder-[#8A8A93] bg-[#111419] rounded-[6px] border border-[#272C35] focus:border-[#F5A623] outline-none">
                </div>

                <!-- Date Range Cluster -->
                <div class="flex items-center gap-1.5 flex-1 lg:flex-initial">
                    <div class="flex flex-col sm:flex-row items-center gap-1.5 w-full">
                        <div class="relative w-full sm:w-auto flex-1">
                            <input type="date"
                                   name="date_from"
                                   id="dateFromInput"
                                   value="{{ request('date_from') }}"
                                   aria-label="Date From"
                                   class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] [color-scheme:dark]">
                        </div>
                        <span class="text-on-surface-variant text-[11px] font-semibold hidden sm:inline">to</span>
                        <div class="relative w-full sm:w-auto flex-1">
                            <input type="date"
                                   name="date_to"
                                   id="dateToInput"
                                   value="{{ request('date_to') }}"
                                   aria-label="Date To"
                                   class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] [color-scheme:dark]">
                        </div>
                    </div>
                </div>

                <!-- Payment Method Select -->
                <div class="w-full lg:w-[130px]">
                    <select name="payment_method"
                            aria-label="Filter by Payment Method"
                            class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] cursor-pointer">
                        <option value="">All Methods</option>
                        <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="card" {{ request('payment_method') === 'card' ? 'selected' : '' }}>Card</option>
                        <option value="koko" {{ request('payment_method') === 'koko' ? 'selected' : '' }}>Koko</option>
                        <option value="mintpay" {{ request('payment_method') === 'mintpay' ? 'selected' : '' }}>Mint Pay</option>
                        <option value="bank" {{ request('payment_method') === 'bank' ? 'selected' : '' }}>Bank</option>
                        <option value="payzy" {{ request('payment_method') === 'payzy' ? 'selected' : '' }}>Payzy</option>
                    </select>
                </div>

                <!-- Staff Select -->
                <div class="w-full lg:w-[130px]">
                    <select name="staff_id"
                            aria-label="Filter by Staff Member"
                            class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] cursor-pointer">
                        <option value="">All Staff</option>
                        @foreach($staffList as $staff)
                            <option value="{{ $staff->id }}" {{ request('staff_id') == $staff->id ? 'selected' : '' }}>
                                {{ $staff->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-1.5 shrink-0 pt-1 lg:pt-0">
                    <button type="submit"
                            class="h-[34px] px-3.5 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95 flex-1 lg:flex-initial">
                        <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'wght' 700;">filter_alt</span>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('pos.log') }}"
                       class="dashboard-control h-[34px] w-[34px] text-[#8A8A93] hover:text-[#EF4444] rounded-[6px] flex items-center justify-center transition-colors no-underline"
                       title="Clear all filters">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </a>
                </div>

            </div>
        </form>

        <!-- Active Filter Chips Row -->
        @php
            $hasActiveFilters = request()->filled('date_from') || request()->filled('date_to') || request()->filled('payment_method') || request()->filled('staff_id');
            $activeStaffName = request('staff_id') ? ($staffList->firstWhere('id', request('staff_id'))->name ?? 'Staff #' . request('staff_id')) : null;
        @endphp

        <div x-show="hasAnyChips" class="flex items-center gap-1.5 flex-wrap pt-1 border-t border-[#272C35]/40 text-[11px]">
            <span class="text-[10px] uppercase font-bold tracking-wider text-[#8A8A93] mr-1">Active:</span>

            @if(request('date_from') || request('date_to'))
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-[#1E222A] border border-[#272C35] text-[#d7c3ae]">
                    <span class="material-symbols-outlined text-[12px] text-amber-accent">date_range</span>
                    <span>{{ request('date_from') ?: 'Start' }} → {{ request('date_to') ?: 'Now' }}</span>
                    <a href="{{ request()->fullUrlWithQuery(['date_from' => null, 'date_to' => null]) }}" class="text-[#8A8A93] hover:text-[#EF4444] ml-1 no-underline font-bold">×</a>
                </span>
            @endif

            @if(request('payment_method'))
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-[#1E222A] border border-[#272C35] text-[#d7c3ae]">
                    <span class="material-symbols-outlined text-[12px] text-amber-accent">payments</span>
                    <span class="capitalize">{{ request('payment_method') }}</span>
                    <a href="{{ request()->fullUrlWithQuery(['payment_method' => null]) }}" class="text-[#8A8A93] hover:text-[#EF4444] ml-1 no-underline font-bold">×</a>
                </span>
            @endif

            @if($activeStaffName)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-[#1E222A] border border-[#272C35] text-[#d7c3ae]">
                    <span class="material-symbols-outlined text-[12px] text-amber-accent">person</span>
                    <span>{{ $activeStaffName }}</span>
                    <a href="{{ request()->fullUrlWithQuery(['staff_id' => null]) }}" class="text-[#8A8A93] hover:text-[#EF4444] ml-1 no-underline font-bold">×</a>
                </span>
            @endif

            <!-- Live Client Search Chip -->
            <template x-if="searchQuery.trim() !== ''">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-[#1E222A] border border-[#272C35] text-[#d7c3ae]">
                    <span class="material-symbols-outlined text-[12px] text-amber-accent">search</span>
                    <span>Search: "<span x-text="searchQuery"></span>"</span>
                    <button type="button" @click="searchQuery = ''" class="text-[#8A8A93] hover:text-[#EF4444] ml-1 bg-transparent border-0 p-0 cursor-pointer font-bold leading-none">×</button>
                </span>
            </template>
        </div>
    </div>

    <!-- ========================================================
         4. TRANSACTIONS DATA TABLE (DESKTOP) & STACKED (MOBILE)
         ======================================================== -->
    <div class="dashboard-card rounded-[8px] overflow-hidden bg-[#171A20] border border-[#272C35] flex flex-col">
        <!-- Table Header Bar -->
        <div class="px-3.5 lg:px-4 py-2.5 border-b border-[#272C35] flex items-center justify-between bg-[#111419]/60">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px] text-amber-accent">list_alt</span>
                <span class="font-semibold text-white text-[13px] tracking-tight">Transactions</span>
                <span class="px-1.5 py-0.2 rounded bg-surface-variant/40 border border-[#272C35] text-[10px] font-bold text-amber-accent tabular-nums">
                    <span x-text="filteredSales.length"></span>
                </span>
            </div>

            <div class="text-[11px] text-on-surface-variant">
                Showing {{ $sales->firstItem() ?? 0 }}–{{ $sales->lastItem() ?? 0 }} of {{ $sales->total() }}
            </div>
        </div>

        <!-- Desktop Dense Table View (>= md) -->
        <div class="overflow-x-auto custom-scrollbar hidden md:block">
            <table class="w-full text-left border-collapse text-body-sm">
                <thead>
                    <tr class="border-b border-[#272C35] text-on-surface-variant font-label-caps text-[10px] uppercase bg-[#111419]/30">
                        <!-- Bill # Sortable -->
                        <th @click="toggleSort('bill_number')"
                            class="py-2.5 px-3.5 font-bold tracking-wider cursor-pointer hover:text-white transition-colors select-none">
                            <div class="flex items-center gap-1">
                                <span>Bill #</span>
                                <span class="material-symbols-outlined text-[13px]"
                                      :class="sortKey === 'bill_number' ? 'text-amber-accent' : 'opacity-30'"
                                      x-text="sortKey === 'bill_number' ? (sortAsc ? 'arrow_upward' : 'arrow_downward') : 'unfold_more'"></span>
                            </div>
                        </th>

                        <!-- Date & Time Sortable -->
                        <th @click="toggleSort('timestamp')"
                            class="py-2.5 px-3 font-bold tracking-wider cursor-pointer hover:text-white transition-colors select-none">
                            <div class="flex items-center gap-1">
                                <span>Date & Time</span>
                                <span class="material-symbols-outlined text-[13px]"
                                      :class="sortKey === 'timestamp' ? 'text-amber-accent' : 'opacity-30'"
                                      x-text="sortKey === 'timestamp' ? (sortAsc ? 'arrow_upward' : 'arrow_downward') : 'unfold_more'"></span>
                            </div>
                        </th>

                        <!-- Items Summary -->
                        <th class="py-2.5 px-3 font-bold tracking-wider">
                            <span>Items Summary</span>
                        </th>

                        <!-- Staff -->
                        <th class="py-2.5 px-3 font-bold tracking-wider">
                            <span>Staff</span>
                        </th>

                        <!-- Payment Method -->
                        <th class="py-2.5 px-3 font-bold tracking-wider">
                            <span>Payment</span>
                        </th>

                        <!-- Discount -->
                        <th class="py-2.5 px-3 font-bold tracking-wider text-right">
                            <span>Discount</span>
                        </th>

                        <!-- Amount Sortable -->
                        <th @click="toggleSort('total')"
                            class="py-2.5 px-3.5 font-bold tracking-wider text-right cursor-pointer hover:text-white transition-colors select-none">
                            <div class="flex items-center justify-end gap-1">
                                <span>Amount</span>
                                <span class="material-symbols-outlined text-[13px]"
                                      :class="sortKey === 'total' ? 'text-amber-accent' : 'opacity-30'"
                                      x-text="sortKey === 'total' ? (sortAsc ? 'arrow_upward' : 'arrow_downward') : 'unfold_more'"></span>
                            </div>
                        </th>

                        <!-- Actions (Hover Revealed) -->
                        <th class="py-2.5 px-3 font-bold tracking-wider text-right w-[80px]">
                            <span>Actions</span>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="sale in sortedSales" :key="sale.id">
                        <tr @click="openReceiptDrawer(sale)"
                            class="border-b border-[#272C35]/50 last:border-0 hover:bg-[#1E222A] transition-colors cursor-pointer group h-[52px]">

                            <!-- 1. Bill # -->
                            <td class="py-2 px-3.5 whitespace-nowrap">
                                <span class="font-mono text-amber-accent group-hover:underline font-bold text-[12px] tracking-tight"
                                      x-text="sale.bill_number"></span>
                            </td>

                            <!-- 2. Date & Time -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <div class="text-[12px] text-white font-medium tabular-nums" x-text="sale.date_formatted"></div>
                                <div class="text-[10px] text-[#8A8A93] tabular-nums" x-text="sale.time_formatted"></div>
                            </td>

                            <!-- 3. Items Summary -->
                            <td class="py-2 px-3 max-w-[280px]">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[12px] text-white font-medium truncate max-w-[180px]"
                                          x-text="sale.first_item_name"></span>
                                    <template x-if="sale.first_item_variant">
                                        <span class="text-[10px] text-[#8A8A93] truncate"
                                              x-text="'(' + sale.first_item_variant + ')'"></span>
                                    </template>
                                    <template x-if="sale.items.length > 1">
                                        <span class="px-1.5 py-0.5 rounded bg-[#111419] border border-[#272C35] text-[10px] font-semibold text-amber-accent"
                                              x-text="'+' + (sale.items.length - 1) + ' more'"></span>
                                    </template>
                                </div>
                            </td>

                            <!-- 4. Staff -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-full bg-surface-variant flex items-center justify-center text-[9px] font-bold text-on-surface uppercase flex-shrink-0"
                                          x-text="sale.staff_name.charAt(0)"></span>
                                    <span class="text-[12px] text-[#d7c3ae] font-medium truncate max-w-[100px]"
                                          x-text="sale.staff_name"></span>
                                </div>
                            </td>

                            <!-- 5. Payment Pill -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <template x-if="sale.payment_method === 'cash'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-['Inter'] uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
                                        <span class="material-symbols-outlined text-[12px]">payments</span>Cash
                                    </span>
                                </template>
                                <template x-if="sale.payment_method === 'card'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-['Inter'] uppercase tracking-wider bg-sky-500/10 text-sky-400 border border-sky-500/25">
                                        <span class="material-symbols-outlined text-[12px]">credit_card</span>Card
                                    </span>
                                </template>
                                <template x-if="sale.payment_method === 'koko'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-['Inter'] uppercase tracking-wider bg-blue-500/10 text-blue-400 border border-blue-500/25">
                                        <span class="material-symbols-outlined text-[12px]">smartphone</span>Koko
                                    </span>
                                </template>
                                <template x-if="sale.payment_method === 'mintpay'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-['Inter'] uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/25">
                                        <span class="material-symbols-outlined text-[12px]">credit_card</span>Mint Pay
                                    </span>
                                </template>
                                <template x-if="sale.payment_method === 'bank'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-['Inter'] uppercase tracking-wider bg-cyan-500/10 text-cyan-400 border border-cyan-500/25">
                                        <span class="material-symbols-outlined text-[12px]">account_balance</span>Bank
                                    </span>
                                </template>
                                <template x-if="sale.payment_method === 'payzy'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-['Inter'] uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/25">
                                        <span class="material-symbols-outlined text-[12px]">account_balance_wallet</span>Payzy
                                    </span>
                                </template>
                                <template x-if="!['cash', 'card', 'koko', 'mintpay', 'bank', 'payzy'].includes(sale.payment_method)">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-['Inter'] uppercase tracking-wider bg-slate-500/10 text-slate-300 border border-slate-500/25"
                                          x-text="sale.payment_method.toUpperCase()"></span>
                                </template>
                            </td>

                            <!-- 6. Discount -->
                            <td class="py-2 px-3 text-right whitespace-nowrap">
                                <template x-if="sale.discount_amount > 0">
                                    <span class="text-[11px] text-[#f87171] font-semibold tabular-nums"
                                          x-text="sale.discount_formatted"></span>
                                </template>
                                <template x-if="sale.discount_amount <= 0">
                                    <span class="text-[11px] text-[#5A5A63]">——</span>
                                </template>
                            </td>

                            <!-- 7. Total Amount -->
                            <td class="py-2 px-3.5 text-right whitespace-nowrap">
                                <span class="font-['Inter'] font-bold text-[13px] text-white tabular-nums tracking-tight"
                                      x-text="sale.total_formatted"></span>
                            </td>

                            <!-- 8. Actions (Hover revealed) -->
                            <td class="py-2 px-3 text-right whitespace-nowrap" @click.stop>
                                <div class="flex items-center justify-end gap-1 opacity-80 group-hover:opacity-100 transition-opacity">
                                    <button type="button"
                                            @click="openReceiptDrawer(sale)"
                                            class="w-7 h-7 rounded-[4px] bg-[#111419] hover:bg-amber-accent/15 text-[#8A8A93] hover:text-amber-accent border border-[#272C35] flex items-center justify-center transition-colors cursor-pointer"
                                            title="View receipt detail">
                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                    </button>
                                    <button type="button"
                                            @click="printReceipt(sale)"
                                            class="w-7 h-7 rounded-[4px] bg-[#111419] hover:bg-amber-accent/15 text-[#8A8A93] hover:text-amber-accent border border-[#272C35] flex items-center justify-center transition-colors cursor-pointer"
                                            title="Print receipt">
                                        <span class="material-symbols-outlined text-[15px]">print</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Mobile Stacked Card View (< md) -->
        <div class="md:hidden flex flex-col divide-y divide-[#272C35]/50">
            <template x-for="sale in sortedSales" :key="'mob-' + sale.id">
                <div @click="openReceiptDrawer(sale)"
                     class="p-3 hover:bg-[#1E222A] transition-colors cursor-pointer flex flex-col gap-2">
                    
                    <!-- Row 1: Bill # | Payment | Amount -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-amber-accent font-bold text-[13px]" x-text="sale.bill_number"></span>
                            <span class="text-[10px] text-[#8A8A93] uppercase" x-text="sale.payment_method"></span>
                        </div>
                        <span class="font-['Inter'] font-bold text-[14px] text-white tabular-nums" x-text="sale.total_formatted"></span>
                    </div>

                    <!-- Row 2: Date + Staff -->
                    <div class="flex items-center justify-between text-[11px] text-[#8A8A93]">
                        <span x-text="sale.created_at_formatted"></span>
                        <span x-text="'Staff: ' + sale.staff_name"></span>
                    </div>

                    <!-- Row 3: Items -->
                    <div class="flex items-center gap-1.5 flex-wrap text-[11px]">
                        <span class="text-white truncate max-w-[200px]" x-text="sale.first_item_name"></span>
                        <template x-if="sale.items.length > 1">
                            <span class="px-1.5 py-0.5 rounded bg-[#111419] border border-[#272C35] text-[9px] font-semibold text-amber-accent"
                                  x-text="'+' + (sale.items.length - 1) + ' more'"></span>
                        </template>
                    </div>

                    <!-- Row 4: Discount & Action Buttons -->
                    <div class="flex items-center justify-between pt-1">
                        <div>
                            <template x-if="sale.discount_amount > 0">
                                <span class="text-[10px] text-[#f87171] font-semibold"
                                      x-text="'Disc: ' + sale.discount_formatted"></span>
                            </template>
                        </div>
                        <div class="flex items-center gap-1" @click.stop>
                            <button type="button"
                                    @click="openReceiptDrawer(sale)"
                                    class="px-2 py-1 rounded bg-[#111419] text-[#d7c3ae] border border-[#272C35] text-[10px] font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[13px]">visibility</span> View
                            </button>
                            <button type="button"
                                    @click="printReceipt(sale)"
                                    class="px-2 py-1 rounded bg-[#111419] text-amber-accent border border-[#272C35] text-[10px] font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[13px]">print</span> Print
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State (No records matching filters or empty dataset) -->
        <div x-show="sortedSales.length === 0" class="py-12 px-4 text-center">
            <span class="material-symbols-outlined text-[36px] text-[#5A5A63] mb-2 block">receipt_long</span>
            <div class="text-[14px] font-semibold text-white mb-1">No sales found for this period</div>
            <p class="text-[12px] text-[#8A8A93] max-w-sm mx-auto mb-3">
                Try adjusting your date range or clearing search & staff filters.
            </p>
            <a href="{{ route('pos.log') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[6px] bg-[#111419] hover:bg-[#1E222A] text-amber-accent border border-[#272C35] text-[11px] font-bold uppercase tracking-wider no-underline transition-colors">
                <span class="material-symbols-outlined text-[14px]">refresh</span>
                <span>Reset Filters</span>
            </a>
        </div>

        <!-- ========================================================
             5. FOOTER & PAGINATION
             ======================================================== -->
        @if ($sales->hasPages())
            <div class="p-3 border-t border-[#272C35] flex flex-col sm:flex-row items-center justify-between gap-3 bg-[#111419]/40">
                <div class="text-[11px] text-[#8A8A93]">
                    Showing {{ $sales->firstItem() ?? 0 }} to {{ $sales->lastItem() ?? 0 }} of {{ $sales->total() }} results
                </div>

                <!-- Custom Dark Styled Pagination Links -->
                <div class="flex items-center gap-1 select-none">
                    {{-- Previous Page Link --}}
                    @if ($sales->onFirstPage())
                        <span class="dashboard-control w-8 h-8 rounded-[4px] opacity-40 flex items-center justify-center cursor-not-allowed">
                            <span class="material-symbols-outlined text-[16px] text-[#8A8A93]">chevron_left</span>
                        </span>
                    @else
                        <a href="{{ $sales->previousPageUrl() }}"
                           class="dashboard-control w-8 h-8 rounded-[4px] flex items-center justify-center hover:text-amber-accent transition-colors no-underline">
                            <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                        </a>
                    @endif

                    {{-- Page Numbers --}}
                    @foreach ($sales->getUrlRange(max(1, $sales->currentPage() - 2), min($sales->lastPage(), $sales->currentPage() + 2)) as $page => $url)
                        @if ($page == $sales->currentPage())
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

                    {{-- Next Page Link --}}
                    @if ($sales->hasMorePages())
                        <a href="{{ $sales->nextPageUrl() }}"
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
         6. SLIDE-OVER RECEIPT DETAIL DRAWER (Alpine.js Teleported to Body)
         ======================================================== -->
    <template x-teleport="body">
        <div x-show="drawerOpen"
             @keydown.escape.window="drawerOpen = false"
             class="fixed inset-0 z-[9999] overflow-hidden flex justify-end"
             style="display: none;"
             role="dialog"
             aria-modal="true">

            <!-- Overlay / Backdrop (Click to close) -->
            <div x-show="drawerOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="drawerOpen = false"
                 class="fixed inset-0 bg-black/70 backdrop-blur-xs transition-opacity"></div>

            <!-- Panel: h-full flex flex-col overflow-hidden, right-aligned -->
            <div x-show="drawerOpen"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="relative w-screen max-w-md h-full bg-[#13151A] border-l border-[#272C35] shadow-2xl flex flex-col overflow-hidden z-10">

                <!-- Header: shrink-0, pinned, contains bill # and working close button -->
                <div class="shrink-0 p-4 border-b border-[#272C35] flex items-center justify-between bg-[#111419]">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[20px] text-amber-accent">receipt</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-mono text-[16px] font-bold text-white tracking-tight m-0"
                                    x-text="activeSale?.bill_number"></h3>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
                                    PAID
                                </span>
                            </div>
                            <p class="text-[11px] text-[#8A8A93] m-0"
                               x-text="activeSale?.created_at_formatted"></p>
                        </div>
                    </div>
                    <button type="button"
                            @click="drawerOpen = false"
                            class="dashboard-control w-8 h-8 rounded-[4px] text-[#8A8A93] hover:text-white flex items-center justify-center cursor-pointer border-0"
                            title="Close drawer">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <!-- Body: flex-1 min-h-0 overflow-y-auto (min-h-0 is required) -->
                <div class="flex-1 min-h-0 overflow-y-auto p-4 custom-scrollbar flex flex-col gap-4">

                    <!-- Staff & Customer Cluster -->
                    <div class="p-3 rounded-[6px] bg-[#171A20] border border-[#272C35] flex justify-between items-center text-[12px]">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[#8A8A93] block">Cashier</span>
                            <span class="text-white font-medium" x-text="activeSale?.staff_name"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-[#8A8A93] block">Payment Method</span>
                            <span class="text-amber-accent font-semibold uppercase text-[11px]" x-text="activeSale?.payment_method"></span>
                        </div>
                    </div>

                    <!-- Customer Info (if attached) -->
                    <template x-if="activeSale?.customer_name">
                        <div class="p-3 rounded-[6px] bg-[#171A20] border border-[#272C35] text-[12px]">
                            <span class="text-[10px] uppercase font-bold text-[#8A8A93] block mb-1">Customer</span>
                            <div class="flex items-center justify-between">
                                <span class="text-white font-medium" x-text="activeSale?.customer_name"></span>
                                <span class="text-[#8A8A93]" x-text="activeSale?.customer_phone"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Items List Table -->
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between text-[11px] font-bold text-[#8A8A93] uppercase tracking-wider px-1">
                            <span>Itemized Products</span>
                            <span x-text="activeSale?.items_count + ' items'"></span>
                        </div>

                        <div class="rounded-[6px] bg-[#171A20] border border-[#272C35] overflow-hidden divide-y divide-[#272C35]/50">
                            <template x-for="item in (activeSale?.items ?? [])" :key="item.id">
                                <div class="p-2.5 flex items-start justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-[12px] font-medium text-white truncate" x-text="item.name"></div>
                                        <div class="flex items-center gap-2 text-[10px] text-[#8A8A93] mt-0.5">
                                            <span x-text="'Qty: ' + item.qty"></span>
                                            <span>&middot;</span>
                                            <span x-text="'@ ' + item.price_formatted"></span>
                                            <template x-if="item.variant">
                                                <span>&middot;</span>
                                                <span class="text-amber-accent/80" x-text="item.variant"></span>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="text-[12px] font-bold text-white tabular-nums" x-text="item.subtotal_formatted"></div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Financial Breakdown Summary -->
                    <div class="p-3 rounded-[6px] bg-[#171A20] border border-[#272C35] flex flex-col gap-2 text-[12px]">
                        <div class="flex justify-between text-[#8A8A93]">
                            <span>Subtotal</span>
                            <span class="text-white tabular-nums" x-text="activeSale?.subtotal_formatted"></span>
                        </div>

                        <template x-if="activeSale?.discount_amount > 0">
                            <div class="flex justify-between text-[#f87171]">
                                <span class="flex items-center gap-1">
                                    <span>Discount</span>
                                    <template x-if="activeSale?.discount_type === 'percentage'">
                                        <span x-text="'(' + activeSale.discount_value + '%)'"></span>
                                    </template>
                                </span>
                                <span class="tabular-nums" x-text="activeSale?.discount_formatted"></span>
                            </div>
                        </template>

                        <div class="border-t border-[#272C35] pt-2 flex justify-between items-baseline">
                            <span class="font-bold text-white uppercase text-[11px] tracking-wider">Total</span>
                            <span class="font-['Inter'] font-bold text-[20px] text-amber-accent tabular-nums"
                                  x-text="activeSale?.total_formatted"></span>
                        </div>

                        <!-- Tendered / Change details -->
                        <template x-if="activeSale?.amount_received">
                            <div class="border-t border-[#272C35]/40 pt-2 flex flex-col gap-1 text-[11px] text-[#8A8A93]">
                                <div class="flex justify-between">
                                    <span>Amount Received</span>
                                    <span class="text-white tabular-nums" x-text="activeSale?.amount_received_formatted"></span>
                                </div>
                                <template x-if="activeSale?.change_amount > 0">
                                    <div class="flex justify-between text-emerald-400">
                                        <span>Change Returned</span>
                                        <span class="tabular-nums" x-text="activeSale?.change_amount_formatted"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Order Notes (if present) -->
                    <template x-if="activeSale?.notes">
                        <div class="p-3 rounded-[6px] bg-[#171A20] border border-[#272C35] text-[12px]">
                            <span class="text-[10px] uppercase font-bold text-[#8A8A93] block mb-1">Notes</span>
                            <p class="text-[#d7c3ae] text-[11px] italic m-0" x-text="activeSale?.notes"></p>
                        </div>
                    </template>

                </div>

                <!-- Footer: shrink-0, pinned, holds Print Receipt + open-in-new buttons -->
                <div class="shrink-0 p-3.5 border-t border-[#272C35] bg-[#111419] flex items-center gap-2">
                    <button type="button"
                            @click="printReceipt(activeSale)"
                            class="flex-1 h-[36px] bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95">
                        <span class="material-symbols-outlined text-[16px]">print</span>
                        <span>Print Receipt</span>
                    </button>

                    <a :href="activeSale?.invoice_url"
                       target="_blank"
                       class="dashboard-control h-[36px] px-3 text-[#d7c3ae] hover:text-white rounded-[6px] flex items-center justify-center gap-1 text-[11px] font-semibold uppercase tracking-wider no-underline"
                       title="Open full receipt page">
                        <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                    </a>
                </div>

            </div>
        </div>
    </template>

    <!-- Hidden iframe for instant direct thermal receipt printing -->
    <iframe id="receiptSilentPrintFrame" class="hidden" style="display:none;" title="Print Frame"></iframe>

</div>
@endsection

@push('scripts')
<script>
function posSalesLogApp(initialSales, initialRange) {
    return {
        sales: initialSales || [],
        currentRange: initialRange || 'all',
        searchQuery: '',
        filtersDrawerOpen: false,
        sortKey: 'timestamp',
        sortAsc: false,
        drawerOpen: false,
        activeSale: null,

        initLog() {
            // Restore search query from URL if present
            const params = new URLSearchParams(window.location.search);
            if (params.get('search')) {
                this.searchQuery = params.get('search');
            }
        },

        get hasAnyChips() {
            const params = new URLSearchParams(window.location.search);
            return params.has('date_from') || params.has('date_to') || params.has('payment_method') || params.has('staff_id') || this.searchQuery.trim() !== '';
        },

        get filteredSales() {
            if (!this.searchQuery.trim()) {
                return this.sales;
            }
            const q = this.searchQuery.toLowerCase().trim();
            return this.sales.filter(s => {
                const billMatch = s.plain_bill_number.toLowerCase().includes(q) || s.bill_number.toLowerCase().includes(q);
                const staffMatch = (s.staff_name || '').toLowerCase().includes(q);
                const custMatch = (s.customer_name || '').toLowerCase().includes(q);
                const itemMatch = (s.items || []).some(item => (item.name || '').toLowerCase().includes(q) || (item.sku || '').toLowerCase().includes(q));
                return billMatch || staffMatch || custMatch || itemMatch;
            });
        },

        get sortedSales() {
            const list = [...this.filteredSales];
            const k = this.sortKey;
            const asc = this.sortAsc;

            return list.sort((a, b) => {
                let valA = a[k];
                let valB = b[k];

                if (k === 'bill_number') {
                    valA = a.id;
                    valB = b.id;
                }

                if (valA < valB) return asc ? -1 : 1;
                if (valA > valB) return asc ? 1 : -1;
                return 0;
            });
        },

        toggleSort(key) {
            if (this.sortKey === key) {
                this.sortAsc = !this.sortAsc;
            } else {
                this.sortKey = key;
                this.sortAsc = key === 'bill_number' ? true : false;
            }
        },

        setDateRangePreset(preset) {
            this.currentRange = preset;
            const form = document.getElementById('filterForm');
            const fromInput = document.getElementById('dateFromInput');
            const toInput = document.getElementById('dateToInput');

            const today = new Date();
            const formatYMD = (d) => d.toISOString().split('T')[0];

            if (preset === 'today') {
                const tStr = formatYMD(today);
                fromInput.value = tStr;
                toInput.value = tStr;
            } else if (preset === '7d') {
                const d7 = new Date();
                d7.setDate(today.getDate() - 6);
                fromInput.value = formatYMD(d7);
                toInput.value = formatYMD(today);
            } else if (preset === '30d') {
                const d30 = new Date();
                d30.setDate(today.getDate() - 29);
                fromInput.value = formatYMD(d30);
                toInput.value = formatYMD(today);
            } else if (preset === 'all') {
                fromInput.value = '';
                toInput.value = '';
            }

            form.submit();
        },

        openReceiptDrawer(sale) {
            this.activeSale = sale;
            this.drawerOpen = true;
        },

        printReceipt(sale) {
            if (!sale) return;
            const frame = document.getElementById('receiptSilentPrintFrame');
            if (frame) {
                frame.src = sale.embed_url;
                frame.onload = function() {
                    try {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                    } catch (e) {
                        window.open(sale.invoice_url, '_blank');
                    }
                };
            } else {
                window.open(sale.invoice_url, '_blank');
            }
        },

        exportToCsv() {
            const rows = this.filteredSales;
            if (!rows.length) {
                alert('No transactions to export.');
                return;
            }

            const headers = ['Bill Number', 'Date', 'Time', 'Staff', 'Payment Method', 'Items Count', 'Subtotal', 'Discount', 'Total'];
            const csvRows = [headers.join(',')];

            rows.forEach(r => {
                const values = [
                    `"${r.plain_bill_number}"`,
                    `"${r.date_formatted}"`,
                    `"${r.time_formatted}"`,
                    `"${r.staff_name.replace(/"/g, '""')}"`,
                    `"${r.payment_method}"`,
                    r.items_count,
                    r.subtotal.toFixed(2),
                    r.discount_amount.toFixed(2),
                    r.total.toFixed(2),
                ];
                csvRows.push(values.join(','));
            });

            const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', `pos_sales_log_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };
}
</script>
@endpush
