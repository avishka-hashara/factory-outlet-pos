{{-- ============================================
     Reports & Analytics - Redesigned Dark Dashboard Experience
     Sales, Stock, Category & Staff Performance
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Reports & Analytics')

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

    /* Dark native date picker indicator */
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(0.8) sepia(100%) saturate(100%) hue-rotate(0deg);
        cursor: pointer;
        opacity: 0.7;
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
    $currentTab = $tab ?? 'daily';
    
    // Period label for chip
    $periodLabel = match($currentTab) {
        'daily'   => \Carbon\Carbon::parse($date)->isToday() ? ('Today · ' . \Carbon\Carbon::parse($date)->format('M d, Y')) : \Carbon\Carbon::parse($date)->format('M d, Y'),
        'monthly' => \Carbon\Carbon::parse($month . '-01')->format('F Y'),
        'custom'  => \Carbon\Carbon::parse($dateFrom)->format('M d') . ' → ' . \Carbon\Carbon::parse($dateTo)->format('M d, Y'),
        'stock'   => 'Live Inventory Snapshot',
        default   => 'Analytics'
    };

    // Active KPI metrics based on current tab
    $activeMetrics = match($currentTab) {
        'monthly' => [
            'sales' => $monthlyTotalSales,
            'revenue' => $monthlyRevenue,
            'items' => $monthlyItemsSold,
            'refunds' => $monthlyTotalRefunds,
            'refund_val' => $monthlyRefundValue,
            'avg_sale' => $monthlyAvgSale,
            'deltas' => $monthlyDeltas,
            'comp_label' => 'vs last month',
            'payments' => $monthlyPayments,
            'top_products' => $monthlyTopProducts,
            'staff_sales' => $monthlyStaffSales,
            'category_sales' => $monthlyCategorySales,
            'date_from' => \Carbon\Carbon::parse($month . '-01')->startOfMonth()->toDateString(),
            'date_to' => \Carbon\Carbon::parse($month . '-01')->endOfMonth()->toDateString(),
        ],
        'custom' => [
            'sales' => $customTotalSales,
            'revenue' => $customRevenue,
            'items' => $customItemsSold,
            'refunds' => $customTotalRefunds,
            'refund_val' => $customRefundValue,
            'avg_sale' => $customAvgSale,
            'deltas' => $customDeltas,
            'comp_label' => 'vs prev period',
            'payments' => $customPayments,
            'top_products' => $customTopProducts,
            'staff_sales' => $customStaffSales,
            'category_sales' => $customCategorySales,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ],
        default => [ // daily
            'sales' => $totalSales,
            'revenue' => $revenue,
            'items' => $itemsSold,
            'refunds' => $totalRefunds,
            'refund_val' => $refundValue,
            'avg_sale' => $avgSale,
            'deltas' => $dailyDeltas,
            'comp_label' => 'vs yesterday',
            'payments' => $payments,
            'top_products' => $topProducts,
            'staff_sales' => $dailyStaffSales,
            'category_sales' => $dailyCategorySales,
            'date_from' => $date,
            'date_to' => $date,
        ],
    };

    // Total non-zero payment count
    $nonZeroPayments = collect($activeMetrics['payments'])->filter(fn($amount) => abs($amount) > 0.01);
    $totalPaymentRevenue = $nonZeroPayments->sum(fn($v) => max(0, $v));

    // Colors mapping for payment methods
    $paymentColors = [
        'cash'         => '#10B981', // Emerald
        'card'         => '#3B82F6', // Blue
        'koko'         => '#8B5CF6', // Purple
        'payzy'        => '#F5A623', // Amber
        'mintpay'      => '#EC4899', // Rose
        'bank'         => '#06B6D4', // Cyan
        'store_credit' => '#EAB308', // Gold
        'multipay'     => '#A855F7',
    ];
@endphp

@section('content')
<div class="w-full flex flex-col select-none gap-3.5 box-border"
     x-data="reportsApp({
        tab: '{{ $currentTab }}',
        date: '{{ $date }}',
        month: '{{ $month }}',
        dateFrom: '{{ $dateFrom }}',
        dateTo: '{{ $dateTo }}',
     })">

    <!-- ========================================================
         1. PAGE HEADER
         ======================================================== -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 shrink-0">
        <!-- Title & Subtitle -->
        <div class="flex flex-col sm:flex-row sm:items-baseline gap-2 sm:gap-3">
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="font-headline-lg text-[20px] lg:text-[22px] text-on-surface m-0 font-bold tracking-tight">
                    Reports
                </h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-amber-500/10 text-amber-accent border border-amber-500/25">
                    <span class="material-symbols-outlined text-[13px] text-amber-accent">insights</span>
                    Analytics
                </span>
                <span class="px-2 py-0.5 rounded bg-surface-variant/40 border border-[#272C35] text-[11px] font-medium text-[#d7c3ae]">
                    {{ $periodLabel }}
                </span>
            </div>
            <p class="font-body-sm text-[12px] text-on-surface-variant m-0">
                Sales, stock and performance
            </p>
        </div>

        <!-- Right Side Actions: Solid Amber Export + Overflow ⋯ -->
        <div class="flex items-center gap-2">
            <!-- Export Primary Dropdown Button -->
            <div class="relative" x-data="{ exportOpen: false }">
                <button type="button"
                        @click="exportOpen = !exportOpen"
                        class="h-[34px] px-3.5 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95 shadow-md">
                    <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'wght' 700;">download</span>
                    <span>Export</span>
                    <span class="material-symbols-outlined text-[14px]">expand_more</span>
                </button>

                <div x-show="exportOpen"
                     @click.outside="exportOpen = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="absolute right-0 top-full mt-1.5 w-44 rounded-[6px] bg-[#13151A] border border-white/10 shadow-2xl py-1 z-50 text-[11px]"
                     style="display: none;">
                    <a :href="'{{ route('reports.exportPdf') }}' + buildExportQuery()"
                       target="_blank"
                       class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left no-underline cursor-pointer">
                        <span class="material-symbols-outlined text-[14px] text-rose-400">picture_as_pdf</span>
                        <span>Export PDF</span>
                    </a>
                    <a :href="'{{ route('reports.exportExcel') }}' + buildExportQuery()"
                       class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left no-underline cursor-pointer">
                        <span class="material-symbols-outlined text-[14px] text-emerald-400">table_view</span>
                        <span>Export Excel (CSV)</span>
                    </a>
                </div>
            </div>

            <!-- Overflow ⋯ Header Menu -->
            <div class="relative" x-data="{ headerMenuOpen: false }">
                <button type="button"
                        @click="headerMenuOpen = !headerMenuOpen"
                        class="dashboard-control h-[34px] w-[34px] rounded-[6px] flex items-center justify-center text-[#8A8A93] hover:text-white cursor-pointer"
                        title="More Actions">
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
                            @click="headerMenuOpen = false; syncShopify()"
                            class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[14px] text-amber-accent">cloud_sync</span>
                        <span>Sync with Shopify</span>
                    </button>
                    <button type="button"
                            @click="headerMenuOpen = false; window.print()"
                            class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[14px]">print</span>
                        <span>Print Report</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Alert -->
    @if(session('pdf_error'))
        <div class="p-3 rounded-[6px] bg-rose-500/10 border border-rose-500/30 text-rose-400 text-[12px] flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">error</span>
                <span>{{ session('pdf_error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-400 hover:text-white bg-transparent border-0 cursor-pointer">
                <span class="material-symbols-outlined text-[14px]">close</span>
            </button>
        </div>
    @endif

    <!-- ========================================================
         2. TABS & DYNAMIC FILTER BAR
         ======================================================== -->
    <div class="dashboard-card rounded-[8px] p-2.5 lg:p-3 bg-[#171A20] border border-[#272C35] flex flex-col gap-2.5">
        <!-- Muted Pill Tabs (Daily / Monthly / Custom Range / Stock) -->
        <div class="flex items-center gap-1.5 overflow-x-auto custom-scrollbar select-none">
            <a href="{{ route('reports.index', ['tab' => 'daily', 'date' => $date]) }}"
               class="px-3 py-1.5 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors no-underline whitespace-nowrap {{ $currentTab === 'daily' ? 'bg-[#1E222A] text-amber-accent border-amber-accent/40 font-bold shadow-xs' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white' }}">
                <span class="material-symbols-outlined text-[15px]">calendar_today</span>
                <span>Daily Report</span>
            </a>

            <a href="{{ route('reports.index', ['tab' => 'monthly', 'month' => $month]) }}"
               class="px-3 py-1.5 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors no-underline whitespace-nowrap {{ $currentTab === 'monthly' ? 'bg-[#1E222A] text-amber-accent border-amber-accent/40 font-bold shadow-xs' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white' }}">
                <span class="material-symbols-outlined text-[15px]">calendar_month</span>
                <span>Monthly Report</span>
            </a>

            <a href="{{ route('reports.index', ['tab' => 'custom', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
               class="px-3 py-1.5 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors no-underline whitespace-nowrap {{ $currentTab === 'custom' ? 'bg-[#1E222A] text-amber-accent border-amber-accent/40 font-bold shadow-xs' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white' }}">
                <span class="material-symbols-outlined text-[15px]">date_range</span>
                <span>Custom Range</span>
            </a>

            <a href="{{ route('reports.index', ['tab' => 'stock']) }}"
               class="px-3 py-1.5 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors no-underline whitespace-nowrap {{ $currentTab === 'stock' ? 'bg-[#1E222A] text-amber-accent border-amber-accent/40 font-bold shadow-xs' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white' }}">
                <span class="material-symbols-outlined text-[15px]">inventory_2</span>
                <span>Stock Report</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-amber-accent">{{ $stockProductCount }}</span>
            </a>
        </div>

        <!-- Filter Form Adapting to Active Tab -->
        @if($currentTab !== 'stock')
            <form method="GET" action="{{ route('reports.index') }}" class="w-full pt-1 border-t border-[#272C35]/50">
                <input type="hidden" name="tab" value="{{ $currentTab }}">

                <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-2">
                    @if($currentTab === 'daily')
                        <!-- Daily Date Picker -->
                        <div class="flex-1 sm:max-w-xs relative">
                            <input type="date"
                                   name="date"
                                   value="{{ $date }}"
                                   aria-label="Select Date"
                                   class="dashboard-control w-full h-[34px] px-3 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] [color-scheme:dark]">
                        </div>
                    @elseif($currentTab === 'monthly')
                        <!-- Month Picker -->
                        <div class="flex-1 sm:max-w-xs relative">
                            <select name="month"
                                    aria-label="Select Month"
                                    class="dashboard-control w-full h-[34px] px-3 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] cursor-pointer">
                                @foreach($availableMonths as $availMonth)
                                    <option value="{{ $availMonth }}" {{ $availMonth === $month ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::parse($availMonth . '-01')->format('F Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($currentTab === 'custom')
                        <!-- Custom Date Range -->
                        <div class="flex items-center gap-1.5">
                            <div class="relative flex-1 sm:w-[140px]">
                                <input type="date"
                                       name="date_from"
                                       id="customDateFrom"
                                       value="{{ $dateFrom }}"
                                       aria-label="From Date"
                                       class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] [color-scheme:dark]">
                            </div>
                            <span class="text-on-surface-variant text-[11px] font-semibold">→</span>
                            <div class="relative flex-1 sm:w-[140px]">
                                <input type="date"
                                       name="date_to"
                                       id="customDateTo"
                                       value="{{ $dateTo }}"
                                       aria-label="To Date"
                                       class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] [color-scheme:dark]">
                            </div>
                        </div>

                        <!-- Quick Presets -->
                        <div class="flex items-center gap-1 overflow-x-auto custom-scrollbar py-0.5">
                            <button type="button" @click="applyPreset('today')" class="px-2 py-1 rounded bg-[#111419] hover:bg-[#1E222A] text-[#8A8A93] hover:text-white border border-[#272C35] text-[10px] font-semibold cursor-pointer">Today</button>
                            <button type="button" @click="applyPreset('yesterday')" class="px-2 py-1 rounded bg-[#111419] hover:bg-[#1E222A] text-[#8A8A93] hover:text-white border border-[#272C35] text-[10px] font-semibold cursor-pointer">Yesterday</button>
                            <button type="button" @click="applyPreset('last7')" class="px-2 py-1 rounded bg-[#111419] hover:bg-[#1E222A] text-[#8A8A93] hover:text-white border border-[#272C35] text-[10px] font-semibold cursor-pointer">Last 7d</button>
                            <button type="button" @click="applyPreset('last30')" class="px-2 py-1 rounded bg-[#111419] hover:bg-[#1E222A] text-[#8A8A93] hover:text-white border border-[#272C35] text-[10px] font-semibold cursor-pointer">Last 30d</button>
                            <button type="button" @click="applyPreset('this_month')" class="px-2 py-1 rounded bg-[#111419] hover:bg-[#1E222A] text-[#8A8A93] hover:text-white border border-[#272C35] text-[10px] font-semibold cursor-pointer">This Month</button>
                            <button type="button" @click="applyPreset('last_month')" class="px-2 py-1 rounded bg-[#111419] hover:bg-[#1E222A] text-[#8A8A93] hover:text-white border border-[#272C35] text-[10px] font-semibold cursor-pointer">Last Month</button>
                        </div>
                    @endif

                    <!-- Generate Button -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="submit"
                                class="h-[34px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95 shadow-md">
                            <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'wght' 700;">query_stats</span>
                            <span>Generate Report</span>
                        </button>
                    </div>
                </div>
            </form>
        @else
            <!-- Stock Tab Filters -->
            <div class="w-full pt-1 border-t border-[#272C35]/50 flex flex-col sm:flex-row items-center gap-2">
                <div class="relative flex-1 w-full">
                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[16px] text-[#8A8A93]">search</span>
                    <input type="text" id="stockSearch" placeholder="Filter product name or SKU..." oninput="filterStockTable()"
                           class="dashboard-control w-full h-[34px] pl-8 pr-3 text-[11px] text-white placeholder-[#8A8A93] bg-[#111419] rounded-[6px]">
                </div>
                <div class="w-full sm:w-[150px]">
                    <select id="stockCategoryFilter" onchange="filterStockTable()"
                            class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] cursor-pointer">
                        <option value="">All Categories</option>
                        @foreach($stockProducts->pluck('category')->filter()->unique()->sort() as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-[140px]">
                    <select id="stockStatusFilter" onchange="filterStockTable()"
                            class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="in">In Stock</option>
                        <option value="low">Low Stock (≤5)</option>
                        <option value="out">Out of Stock</option>
                    </select>
                </div>
            </div>
        @endif
    </div>

    @if($currentTab !== 'stock')
        <!-- ========================================================
             3. KPI ROW (6 STAT CARDS)
             ======================================================== -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 shrink-0">
            <!-- 1. Total Sales -->
            <a href="{{ route('sales.index', ['date_from' => $activeMetrics['date_from'], 'date_to' => $activeMetrics['date_to']]) }}"
               class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden group transition-all duration-200 no-underline cursor-pointer border-l-2 border-l-[#F5A623]">
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[10px] font-bold text-amber-accent uppercase tracking-[0.08em] truncate">Total Sales</span>
                        <span class="material-symbols-outlined text-[16px] text-amber-accent shrink-0">receipt_long</span>
                    </div>
                    <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-white tracking-tight tabular-nums">
                        {{ number_format($activeMetrics['sales']) }}
                    </div>
                </div>
                <div class="text-[10px] text-[#8A8A93] font-medium tracking-wide flex items-center gap-1 mt-1.5">
                    @php $d = $activeMetrics['deltas']['sales']; @endphp
                    @if($d !== null)
                        <span class="px-1 py-0.2 rounded font-bold {{ $d >= 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                            {{ $d >= 0 ? '↑' : '↓' }} {{ abs($d) }}%
                        </span>
                    @endif
                    <span class="truncate">{{ $activeMetrics['comp_label'] }}</span>
                </div>
            </a>

            <!-- 2. Revenue -->
            <a href="{{ route('sales.index', ['date_from' => $activeMetrics['date_from'], 'date_to' => $activeMetrics['date_to']]) }}"
               class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden group transition-all duration-200 no-underline cursor-pointer border-l-2 border-l-emerald-500">
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-[0.08em] truncate">Net Revenue</span>
                        <span class="material-symbols-outlined text-[16px] text-emerald-400 shrink-0">payments</span>
                    </div>
                    <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-emerald-400 tracking-tight tabular-nums">
                        Rs. {{ number_format($activeMetrics['revenue'], 0) }}
                    </div>
                </div>
                <div class="text-[10px] text-[#8A8A93] font-medium tracking-wide flex items-center gap-1 mt-1.5">
                    @php $d = $activeMetrics['deltas']['revenue']; @endphp
                    @if($d !== null)
                        <span class="px-1 py-0.2 rounded font-bold {{ $d >= 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                            {{ $d >= 0 ? '↑' : '↓' }} {{ abs($d) }}%
                        </span>
                    @endif
                    <span class="truncate">{{ $activeMetrics['comp_label'] }}</span>
                </div>
            </a>

            <!-- 3. Items Sold -->
            <div class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden group transition-all duration-200 border-l-2 border-l-sky-500">
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[10px] font-bold text-sky-400 uppercase tracking-[0.08em] truncate">Items Sold</span>
                        <span class="material-symbols-outlined text-[16px] text-sky-400 shrink-0">shopping_bag</span>
                    </div>
                    <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-white tracking-tight tabular-nums">
                        {{ number_format($activeMetrics['items']) }}
                    </div>
                </div>
                <div class="text-[10px] text-[#8A8A93] font-medium tracking-wide flex items-center gap-1 mt-1.5">
                    @php $d = $activeMetrics['deltas']['items']; @endphp
                    @if($d !== null)
                        <span class="px-1 py-0.2 rounded font-bold {{ $d >= 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                            {{ $d >= 0 ? '↑' : '↓' }} {{ abs($d) }}%
                        </span>
                    @endif
                    <span class="truncate">{{ $activeMetrics['comp_label'] }}</span>
                </div>
            </div>

            <!-- 4. Total Refunds -->
            <a href="{{ route('returns.index', ['date_from' => $activeMetrics['date_from'], 'date_to' => $activeMetrics['date_to']]) }}"
               class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden group transition-all duration-200 no-underline cursor-pointer border-l-2 border-l-amber-500">
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[10px] font-bold text-amber-400 uppercase tracking-[0.08em] truncate">Total Refunds</span>
                        <span class="material-symbols-outlined text-[16px] text-amber-400 shrink-0">assignment_return</span>
                    </div>
                    <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-white tracking-tight tabular-nums">
                        {{ number_format($activeMetrics['refunds']) }}
                    </div>
                </div>
                <div class="text-[10px] text-[#8A8A93] font-medium tracking-wide flex items-center gap-1 mt-1.5">
                    @php $d = $activeMetrics['deltas']['refunds']; @endphp
                    @if($d !== null)
                        <span class="px-1 py-0.2 rounded font-bold {{ $d <= 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                            {{ $d <= 0 ? '↓' : '↑' }} {{ abs($d) }}%
                        </span>
                    @endif
                    <span class="truncate">{{ $activeMetrics['comp_label'] }}</span>
                </div>
            </a>

            <!-- 5. Refund Value -->
            <a href="{{ route('returns.index', ['date_from' => $activeMetrics['date_from'], 'date_to' => $activeMetrics['date_to']]) }}"
               class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden group transition-all duration-200 no-underline cursor-pointer border-l-2 border-l-rose-500">
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[10px] font-bold text-rose-400 uppercase tracking-[0.08em] truncate">Refund Value</span>
                        <span class="material-symbols-outlined text-[16px] text-rose-400 shrink-0">keyboard_return</span>
                    </div>
                    <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-rose-400 tracking-tight tabular-nums">
                        Rs. {{ number_format($activeMetrics['refund_val'], 0) }}
                    </div>
                </div>
                <div class="text-[10px] text-[#8A8A93] font-medium tracking-wide flex items-center gap-1 mt-1.5">
                    @php $d = $activeMetrics['deltas']['refund_val']; @endphp
                    @if($d !== null)
                        <span class="px-1 py-0.2 rounded font-bold {{ $d <= 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                            {{ $d <= 0 ? '↓' : '↑' }} {{ abs($d) }}%
                        </span>
                    @endif
                    <span class="truncate">{{ $activeMetrics['comp_label'] }}</span>
                </div>
            </a>

            <!-- 6. Average Sale -->
            <div class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden group transition-all duration-200 border-l-2 border-l-purple-500">
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[10px] font-bold text-purple-400 uppercase tracking-[0.08em] truncate">Average Sale</span>
                        <span class="material-symbols-outlined text-[16px] text-purple-400 shrink-0">analytics</span>
                    </div>
                    <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-white tracking-tight tabular-nums">
                        Rs. {{ number_format($activeMetrics['avg_sale'], 0) }}
                    </div>
                </div>
                <div class="text-[10px] text-[#8A8A93] font-medium tracking-wide flex items-center gap-1 mt-1.5">
                    @php $d = $activeMetrics['deltas']['avg_sale']; @endphp
                    @if($d !== null)
                        <span class="px-1 py-0.2 rounded font-bold {{ $d >= 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                            {{ $d >= 0 ? '↑' : '↓' }} {{ abs($d) }}%
                        </span>
                    @endif
                    <span class="truncate">{{ $activeMetrics['comp_label'] }}</span>
                </div>
            </div>
        </div>

        <!-- ========================================================
             4. CHARTS ROW (TREND CHART + PAYMENT BREAKDOWN)
             ======================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3.5">
            <!-- 1. Trend Chart (Hourly or Daily) -->
            <div class="lg:col-span-2 dashboard-card rounded-[8px] p-4 bg-[#171A20] border border-[#272C35] flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-amber-accent">show_chart</span>
                        <h4 class="m-0 text-[13px] font-bold text-white tracking-tight">
                            {{ $currentTab === 'daily' ? 'Hourly Sales Trend' : ($currentTab === 'monthly' ? 'Daily Sales in ' . \Carbon\Carbon::parse($month . '-01')->format('F Y') : 'Daily Revenue Trend') }}
                        </h4>
                    </div>
                    <div class="flex items-center gap-3 text-[10px] text-[#8A8A93]">
                        <span class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-xs bg-[#F5A623]"></span> Current Period
                        </span>
                        @if($currentTab === 'daily')
                            <span class="flex items-center gap-1">
                                <span class="w-2.5 h-0.5 border-b border-dashed border-white/40"></span> Yesterday
                            </span>
                        @endif
                    </div>
                </div>

                <div class="relative w-full h-[220px]">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>

            <!-- 2. Payment Breakdown Donut Card -->
            <div class="dashboard-card rounded-[8px] p-4 bg-[#171A20] border border-[#272C35] flex flex-col justify-between">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-amber-accent">donut_small</span>
                        <h4 class="m-0 text-[13px] font-bold text-white tracking-tight">Payment Methods</h4>
                    </div>
                    <span class="text-[11px] text-[#8A8A93] font-bold tabular-nums">
                        Rs. {{ number_format($totalPaymentRevenue, 0) }}
                    </span>
                </div>

                @if($nonZeroPayments->count() > 1)
                    <!-- Donut Chart Canvas -->
                    <div class="relative w-full h-[150px] flex items-center justify-center my-1">
                        <canvas id="paymentDonutChart"></canvas>
                    </div>
                @endif

                <!-- Payment Methods Breakdown List -->
                <div class="flex flex-col gap-1.5 mt-2">
                    @forelse($nonZeroPayments as $method => $amount)
                        @php
                            $pct = $totalPaymentRevenue > 0 ? round(($amount / $totalPaymentRevenue) * 100, 1) : 0;
                            $col = $paymentColors[$method] ?? '#64748B';
                        @endphp
                        <div class="flex flex-col gap-0.5 text-[11px]">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 text-white capitalize font-medium">
                                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $col }};"></span>
                                    <span>{{ str_replace('_', ' ', $method) }}</span>
                                </span>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-[#d7c3ae] font-bold tabular-nums">Rs. {{ number_format($amount, 2) }}</span>
                                    <span class="text-[10px] text-[#8A8A93] tabular-nums font-bold w-10 text-right">{{ $pct }}%</span>
                                </div>
                            </div>
                            <div class="w-full h-1 bg-[#111419] rounded-full overflow-hidden border border-[#272C35]">
                                <div class="h-full rounded-full transition-all duration-300"
                                     style="width: {{ $pct }}%; background-color: {{ $col }};"></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-[#8A8A93] text-[11px]">
                            No payments recorded for this period.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- ========================================================
             5. ANALYTICS ROW: TOP PRODUCTS + SALES BY STAFF + CATEGORIES
             ======================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3.5">
            <!-- 1. Top Selling Products (Top 10) -->
            <div class="dashboard-card rounded-[8px] overflow-hidden bg-[#171A20] border border-[#272C35] flex flex-col">
                <div class="p-3 border-b border-[#272C35] flex items-center justify-between bg-[#111419]/60">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px] text-amber-accent">leaderboard</span>
                        <h4 class="m-0 text-[12px] font-bold text-white">Top Selling Products</h4>
                    </div>
                    <a href="{{ route('products.index') }}" class="text-[10px] text-amber-accent hover:underline font-semibold no-underline">
                        View Catalog &rarr;
                    </a>
                </div>

                <div class="p-0 overflow-x-auto custom-scrollbar flex-1">
                    <table class="w-full text-left text-[11px]">
                        <thead>
                            <tr class="border-b border-[#272C35] text-[#8A8A93] uppercase font-bold text-[9px] bg-[#111419]/30">
                                <th class="py-2 px-3">Product</th>
                                <th class="py-2 px-2 text-right">Sold</th>
                                <th class="py-2 px-3 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeMetrics['top_products'] as $item)
                                @php
                                    $share = $activeMetrics['revenue'] > 0 ? round(($item->total_revenue / $activeMetrics['revenue']) * 100, 1) : 0;
                                @endphp
                                <tr class="border-b border-[#272C35]/50 last:border-0 hover:bg-[#1E222A] transition-colors">
                                    <td class="py-2 px-3">
                                        <div class="flex items-center gap-2">
                                            @if($item->product?->image_url)
                                                <img src="{{ $item->product->image_url }}" alt="" class="w-7 h-7 rounded object-cover border border-white/10 shrink-0">
                                            @else
                                                <div class="w-7 h-7 rounded bg-surface-variant flex items-center justify-center text-[#8A8A93] border border-[#272C35] shrink-0">
                                                    <span class="material-symbols-outlined text-[14px]">inventory_2</span>
                                                </div>
                                            @endif
                                            <div class="truncate max-w-[140px]">
                                                <span class="font-bold text-white block truncate" title="{{ $item->product?->name }}">{{ $item->product?->name ?? 'Deleted Product' }}</span>
                                                <span class="text-[9px] font-mono text-[#8A8A93]">{{ $item->product?->sku ?? '—' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2 px-2 text-right font-semibold text-white tabular-nums">
                                        {{ $item->total_qty }}
                                    </td>
                                    <td class="py-2 px-3 text-right tabular-nums">
                                        <span class="font-bold text-amber-accent block">Rs. {{ number_format($item->total_revenue, 0) }}</span>
                                        <div class="w-16 ml-auto h-1 bg-[#111419] rounded-full overflow-hidden border border-[#272C35] mt-0.5">
                                            <div class="h-full bg-amber-accent rounded-full" style="width: {{ min(100, $share * 2) }}%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-8 text-[#8A8A93]">No sales recorded in this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Sales by Staff -->
            <div class="dashboard-card rounded-[8px] overflow-hidden bg-[#171A20] border border-[#272C35] flex flex-col">
                <div class="p-3 border-b border-[#272C35] flex items-center justify-between bg-[#111419]/60">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px] text-amber-accent">badge</span>
                        <h4 class="m-0 text-[12px] font-bold text-white">Sales by Staff</h4>
                    </div>
                </div>

                <div class="p-3 flex flex-col gap-3 flex-1 overflow-y-auto custom-scrollbar">
                    @forelse($activeMetrics['staff_sales'] as $staff)
                        @php
                            $share = $activeMetrics['revenue'] > 0 ? round(($staff->revenue / $activeMetrics['revenue']) * 100, 1) : 0;
                        @endphp
                        <div class="flex flex-col gap-1 text-[11px] p-2 rounded bg-[#111419] border border-[#272C35]/60">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-surface-variant flex items-center justify-center text-[10px] font-bold text-amber-accent border border-[#272C35]">
                                        {{ strtoupper(substr($staff->name, 0, 1)) }}
                                    </div>
                                    <span class="font-bold text-white">{{ $staff->name }}</span>
                                </div>
                                <span class="font-bold text-emerald-400 tabular-nums">Rs. {{ number_format($staff->revenue, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-[#8A8A93] pt-1 border-t border-[#272C35]/40">
                                <span>{{ $staff->transactions }} transactions · Avg: Rs. {{ number_format($staff->avg_sale, 0) }}</span>
                                <span class="font-bold text-amber-accent">{{ $share }}% share</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-[#8A8A93] text-[11px]">
                            No staff transaction records in this period.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- 3. Sales by Category -->
            <div class="dashboard-card rounded-[8px] overflow-hidden bg-[#171A20] border border-[#272C35] flex flex-col">
                <div class="p-3 border-b border-[#272C35] flex items-center justify-between bg-[#111419]/60">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px] text-amber-accent">category</span>
                        <h4 class="m-0 text-[12px] font-bold text-white">Sales by Category</h4>
                    </div>
                </div>

                <div class="p-3 flex flex-col gap-2.5 flex-1 overflow-y-auto custom-scrollbar">
                    @forelse($activeMetrics['category_sales'] as $cat)
                        @php
                            $share = $activeMetrics['revenue'] > 0 ? round(($cat->total_revenue / $activeMetrics['revenue']) * 100, 1) : 0;
                        @endphp
                        <div class="flex flex-col gap-1 text-[11px]">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-white">{{ $cat->category }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-amber-accent tabular-nums">Rs. {{ number_format($cat->total_revenue, 0) }}</span>
                                    <span class="text-[10px] text-[#8A8A93] font-bold w-9 text-right">{{ $share }}%</span>
                                </div>
                            </div>
                            <div class="w-full h-1.5 bg-[#111419] rounded-full overflow-hidden border border-[#272C35]">
                                <div class="h-full bg-amber-accent rounded-full" style="width: {{ $share }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-[#8A8A93] text-[11px]">
                            No category data in this period.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    @else
        <!-- ========================================================
             STOCK REPORT TAB VIEW
             ======================================================== -->
        <!-- Stock 6-Card KPI Row -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 shrink-0">
            <x-pos.kpi-card title="Total Products" icon="inventory_2" :value="$stockProductCount" :is-primary="true" subtext="Catalog Units" />
            <x-pos.kpi-card title="Inventory Value" icon="account_balance_wallet" :value="'Rs. ' . number_format($stockTotalValue, 0)" subtext="Retail Value" />
            <x-pos.kpi-card title="All-Time Revenue" icon="payments" :value="'Rs. ' . number_format($stockAllTimeRevenue, 0)" subtext="Cumulative" />
            <x-pos.kpi-card title="Healthy Stock" icon="check_circle" :value="$stockProductCount - $stockOutCount - $stockLowCount" subtext="In Stock > 5" />
            
            <!-- Low Stock Card -->
            <div class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden border-l-2 border-l-amber-500">
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[10px] font-bold text-amber-400 uppercase tracking-[0.08em] truncate">Low Stock (≤5)</span>
                        <span class="material-symbols-outlined text-[16px] text-amber-400 shrink-0">warning</span>
                    </div>
                    <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-amber-400 tracking-tight tabular-nums">
                        {{ $stockLowCount }}
                    </div>
                </div>
                <div class="text-[10px] text-[#8A8A93] mt-1">Reorder suggested</div>
            </div>

            <!-- Out of Stock Card -->
            <div class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden border-l-2 border-l-rose-500">
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[10px] font-bold text-rose-400 uppercase tracking-[0.08em] truncate">Out of Stock</span>
                        <span class="material-symbols-outlined text-[16px] text-rose-400 shrink-0">error</span>
                    </div>
                    <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-rose-400 tracking-tight tabular-nums">
                        {{ $stockOutCount }}
                    </div>
                </div>
                <div class="text-[10px] text-[#8A8A93] mt-1">Immediate action</div>
            </div>
        </div>

        <!-- Dense Stock Inventory Table -->
        <div class="dashboard-card rounded-[8px] overflow-hidden bg-[#171A20] border border-[#272C35] flex flex-col">
            <div class="px-4 py-2.5 border-b border-[#272C35] flex items-center justify-between bg-[#111419]/60">
                <span class="font-bold text-white text-[13px]">Stock Valuation & Movement Ledger</span>
                <span class="text-[11px] text-[#8A8A93]">{{ $stockProducts->count() }} items recorded</span>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-[11px]" id="stock-table">
                    <thead>
                        <tr class="border-b border-[#272C35] text-[#8A8A93] font-bold uppercase text-[9px] bg-[#111419]/30">
                            <th class="py-2.5 px-3.5">Product</th>
                            <th class="py-2.5 px-2.5">SKU</th>
                            <th class="py-2.5 px-2.5">Category</th>
                            <th class="py-2.5 px-3 text-right">Unit Price</th>
                            <th class="py-2.5 px-3 text-center">Stock</th>
                            <th class="py-2.5 px-3 text-right">Inv. Value</th>
                            <th class="py-2.5 px-3 text-center">Sold Units</th>
                            <th class="py-2.5 px-3 text-right">Revenue</th>
                            <th class="py-2.5 px-3">Last Sold</th>
                            <th class="py-2.5 px-3.5 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockProducts as $i => $p)
                            @php
                                $status = $p->stock <= 0 ? 'out' : ($p->stock <= 5 ? 'low' : 'in');
                            @endphp
                            <tr class="stock-row border-b border-[#272C35]/50 last:border-0 hover:bg-[#1E222A] transition-colors"
                                data-name="{{ strtolower($p->name) }}"
                                data-sku="{{ strtolower($p->sku ?? '') }}"
                                data-cat="{{ $p->category ?? '' }}"
                                data-status="{{ $status }}">
                                <td class="py-2.5 px-3.5 font-bold text-white">
                                    {{ $p->name }}
                                </td>
                                <td class="py-2.5 px-2.5 font-mono text-[#8A8A93]">
                                    {{ $p->sku ?? '—' }}
                                </td>
                                <td class="py-2.5 px-2.5 text-[#d7c3ae]">
                                    {{ $p->category ?? '—' }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-medium text-white tabular-nums">
                                    Rs. {{ number_format($p->price, 2) }}
                                </td>
                                <td class="py-2.5 px-3 text-center font-bold tabular-nums {{ $p->stock <= 0 ? 'text-rose-400' : ($p->stock <= 5 ? 'text-amber-400' : 'text-emerald-400') }}">
                                    {{ $p->stock }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-medium text-[#d7c3ae] tabular-nums">
                                    Rs. {{ number_format($p->stock_value, 2) }}
                                </td>
                                <td class="py-2.5 px-3 text-center text-white tabular-nums">
                                    {{ $p->total_sold }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-bold text-amber-accent tabular-nums">
                                    Rs. {{ number_format($p->total_revenue, 2) }}
                                </td>
                                <td class="py-2.5 px-3 text-[#8A8A93]">
                                    {{ $p->last_sold_at ? \Carbon\Carbon::parse($p->last_sold_at)->format('M d, Y') : 'Never' }}
                                </td>
                                <td class="py-2.5 px-3.5 text-center">
                                    @if($status === 'out')
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/25">Out</span>
                                    @elseif($status === 'low')
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/25">Low</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">In Stock</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-8 text-[#8A8A93]">No products match stock filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function reportsApp(config) {
    return {
        tab: config.tab || 'daily',
        date: config.date,
        month: config.month,
        dateFrom: config.dateFrom,
        dateTo: config.dateTo,

        buildExportQuery() {
            let p = '?tab=' + encodeURIComponent(this.tab);
            if (this.tab === 'daily') {
                p += '&date=' + encodeURIComponent(this.date);
            } else if (this.tab === 'monthly') {
                p += '&month=' + encodeURIComponent(this.month);
            } else if (this.tab === 'custom') {
                p += '&date_from=' + encodeURIComponent(this.dateFrom) + '&date_to=' + encodeURIComponent(this.dateTo);
            }
            return p;
        },

        applyPreset(preset) {
            const today = new Date();
            const formatDate = (d) => d.toISOString().split('T')[0];

            let from = new Date(today);
            let to = new Date(today);

            if (preset === 'today') {
                // from = to = today
            } else if (preset === 'yesterday') {
                from.setDate(today.getDate() - 1);
                to.setDate(today.getDate() - 1);
            } else if (preset === 'last7') {
                from.setDate(today.getDate() - 6);
            } else if (preset === 'last30') {
                from.setDate(today.getDate() - 29);
            } else if (preset === 'this_month') {
                from = new Date(today.getFullYear(), today.getMonth(), 1);
            } else if (preset === 'last_month') {
                from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                to = new Date(today.getFullYear(), today.getMonth(), 0);
            }

            const fromStr = formatDate(from);
            const toStr = formatDate(to);

            document.getElementById('customDateFrom').value = fromStr;
            document.getElementById('customDateTo').value = toStr;
            this.dateFrom = fromStr;
            this.dateTo = toStr;
        },

        syncShopify() {
            if (!confirm('Fetch latest products & inventory from Shopify?')) return;
            fetch('{{ route("shopify.sync") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message || 'Sync finished.');
                if (data.success) location.reload();
            })
            .catch(() => alert('Network error during sync.'));
        }
    };
}

// Chart.js Dark Mode Global Defaults
if (window.Chart) {
    Chart.defaults.color = 'rgba(255, 255, 255, 0.40)';
    Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.05)';
    Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Sales Trend Chart
    const trendCtx = document.getElementById('salesTrendChart')?.getContext('2d');
    if (trendCtx) {
        @if($currentTab === 'daily')
            // Hourly Chart
            const hourlyLabels = ['8AM','9AM','10AM','11AM','12PM','1PM','2PM','3PM','4PM','5PM','6PM','7PM','8PM','9PM','10PM'];
            const hourlySales = @json(array_values($hourlySales));
            const prevHourlySales = @json(array_values($prevHourlySales));
            const hourlyTransactions = @json(array_values($hourlyTransactions));
            const hourlyItems = @json(array_values($hourlyItems));

            new Chart(trendCtx, {
                type: 'bar',
                data: {
                    labels: hourlyLabels,
                    datasets: [
                        {
                            type: 'line',
                            label: 'Yesterday',
                            data: prevHourlySales,
                            borderColor: 'rgba(255, 255, 255, 0.25)',
                            borderDash: [4, 4],
                            borderWidth: 1.5,
                            pointRadius: 0,
                            fill: false,
                            tension: 0.3
                        },
                        {
                            type: 'bar',
                            label: 'Today (Rs.)',
                            data: hourlySales,
                            backgroundColor: 'rgba(245, 166, 35, 0.65)',
                            hoverBackgroundColor: 'rgba(245, 166, 35, 0.90)',
                            borderColor: '#F5A623',
                            borderWidth: 1,
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#13151A',
                            titleColor: '#FFFFFF',
                            bodyColor: '#d7c3ae',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            padding: 10,
                            callbacks: {
                                label: function(context) {
                                    const idx = context.dataIndex;
                                    if (context.dataset.type === 'line') {
                                        return 'Yesterday: Rs. ' + (context.raw || 0).toLocaleString();
                                    }
                                    return [
                                        'Revenue: Rs. ' + (context.raw || 0).toLocaleString(),
                                        'Transactions: ' + (hourlyTransactions[idx] || 0),
                                        'Items Sold: ' + (hourlyItems[idx] || 0)
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { callback: v => 'Rs.' + (v >= 1000 ? (v/1000) + 'k' : v) }
                        }
                    }
                }
            });

        @elseif($currentTab === 'monthly')
            new Chart(trendCtx, {
                type: 'bar',
                data: {
                    labels: @json($monthlyChartLabels),
                    datasets: [{
                        label: 'Daily Net (Rs.)',
                        data: @json($monthlyChartData),
                        backgroundColor: 'rgba(245, 166, 35, 0.65)',
                        borderColor: '#F5A623',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { callback: v => 'Rs.' + (v >= 1000 ? (v/1000) + 'k' : v) }
                        }
                    }
                }
            });

        @elseif($currentTab === 'custom')
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: @json($customChartLabels),
                    datasets: [{
                        label: 'Revenue (Rs.)',
                        data: @json($customChartData),
                        borderColor: '#F5A623',
                        backgroundColor: 'rgba(245, 166, 35, 0.10)',
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: '#F5A623',
                        pointBorderColor: '#13151A',
                        pointRadius: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { callback: v => 'Rs.' + (v >= 1000 ? (v/1000) + 'k' : v) }
                        }
                    }
                }
            });
        @endif
    }

    // 2. Payment Breakdown Donut Chart
    const payCtx = document.getElementById('paymentDonutChart')?.getContext('2d');
    if (payCtx) {
        const paymentLabels = @json($nonZeroPayments->keys()->map(fn($m) => ucfirst(str_replace('_', ' ', $m))));
        const paymentValues = @json($nonZeroPayments->values()->map(fn($v) => max(0, $v)));
        const paymentColors = @json($nonZeroPayments->keys()->map(fn($m) => $paymentColors[$m] ?? '#64748B'));

        new Chart(payCtx, {
            type: 'doughnut',
            data: {
                labels: paymentLabels,
                datasets: [{
                    data: paymentValues,
                    backgroundColor: paymentColors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#13151A',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        padding: 8,
                        callbacks: {
                            label: ctx => ' Rs. ' + (ctx.raw || 0).toLocaleString()
                        }
                    }
                }
            }
        });
    }
});

// Live Stock Table Filtering
function filterStockTable() {
    const q   = document.getElementById('stockSearch')?.value.toLowerCase().trim() ?? '';
    const cat = document.getElementById('stockCategoryFilter')?.value ?? '';
    const st  = document.getElementById('stockStatusFilter')?.value ?? '';

    document.querySelectorAll('#stock-table .stock-row').forEach(row => {
        const name = row.dataset.name || '';
        const sku  = row.dataset.sku  || '';
        const rCat = row.dataset.cat  || '';
        const rSt  = row.dataset.status || '';

        const matchQ   = !q   || name.includes(q) || sku.includes(q);
        const matchCat = !cat || rCat === cat;
        const matchSt  = !st  || rSt === st;

        row.style.display = (matchQ && matchCat && matchSt) ? '' : 'none';
    });
}
</script>
@endpush
