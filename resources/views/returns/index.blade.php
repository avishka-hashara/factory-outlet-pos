{{-- ============================================
     Returns & Refunds Page - Redesigned Dark Dashboard Experience
     Jewellery / Clothing Outlet POS
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Returns & Refunds')

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
    // Prepare JSON payload for Alpine.js
    $returnsPayload = $returns->map(function ($ret) {
        $retNo = 'RET-' . str_pad($ret->id, 4, '0', STR_PAD_LEFT);
        $saleNo = $ret->sale->invoice_number ?? ('INV-' . str_pad($ret->sale_id, 4, '0', STR_PAD_LEFT));

        $itemsMapped = $ret->items->map(function ($item) {
            $variantLabel = $item->variant 
                ? collect([$item->variant->color, $item->variant->size])->filter()->implode(' / ') 
                : null;
            return [
                'id' => $item->id,
                'name' => $item->product->name ?? 'Unknown Product',
                'sku' => $item->variant->sku ?? $item->product->sku ?? null,
                'variant' => $variantLabel,
                'qty' => (int) $item->qty,
                'price' => (float) $item->price,
                'price_formatted' => 'Rs. ' . number_format((float) $item->price, 2),
                'subtotal' => (float) ($item->price * $item->qty),
                'subtotal_formatted' => 'Rs. ' . number_format((float) ($item->price * $item->qty), 2),
                'condition' => $item->condition,
                'condition_label' => ucfirst($item->condition),
                'restock' => (bool) $item->restock,
                'condition_notes' => $item->condition_notes,
            ];
        });

        return [
            'id' => $ret->id,
            'return_number' => '#' . $retNo,
            'plain_return_number' => $retNo,
            'sale_id' => $ret->sale_id,
            'sale_invoice_number' => '#' . $saleNo,
            'created_at_formatted' => $ret->created_at->format('M d, Y · h:i A'),
            'date_formatted' => $ret->created_at->format('M d, Y'),
            'time_formatted' => $ret->created_at->format('h:i A'),
            'timestamp' => $ret->created_at->timestamp,
            'staff_name' => $ret->user->name ?? 'Staff',
            'customer_name' => $ret->sale->customer->name ?? null,
            'customer_phone' => $ret->sale->customer->phone ?? null,
            'reason' => $ret->reason,
            'reason_label' => $ret->reason_label,
            'refund_method' => $ret->refund_method,
            'refund_method_label' => $ret->refund_method_label,
            'total_refunded' => (float) $ret->total_refunded,
            'total_refunded_formatted' => '−Rs. ' . number_format((float) $ret->total_refunded, 2),
            'status' => $ret->status ?? 'completed',
            'notes' => $ret->notes,
            'items_count' => (int) $ret->items->sum('qty'),
            'distinct_items_count' => $ret->items->count(),
            'items' => $itemsMapped,
            'first_item_name' => $itemsMapped->first()['name'] ?? 'No items',
            'first_item_variant' => $itemsMapped->first()['variant'] ?? null,
            'show_url' => route('returns.show', $ret->id),
            'invoice_url' => route('invoice.show', ['id' => $ret->sale_id]),
            'invoice_embed_url' => route('invoice.show', ['id' => $ret->sale_id, 'embed' => 1]),
        ];
    });

    $isFiltered = request()->filled('search') || request()->filled('date_from') || request()->filled('date_to') || request()->filled('reason') || request()->filled('refund_method') || (request()->filled('status') && request('status') !== 'all');
    $currentStatusTab = request('status', 'all');
@endphp

@section('content')
<div x-data="returnsLedgerApp(@js($returnsPayload), '{{ $currentStatusTab }}', {{ $totalAllReturns }})"
     x-init="initReturns()"
     class="w-full flex flex-col select-none gap-3.5 box-border">

    <!-- ========================================================
         1. PAGE HEADER
         ======================================================== -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 shrink-0">
        <!-- Title & Subtitle -->
        <div class="flex flex-col sm:flex-row sm:items-baseline gap-2 sm:gap-3">
            <div class="flex items-center gap-2.5">
                <h1 class="font-headline-lg text-[20px] lg:text-[22px] text-on-surface m-0 font-bold tracking-tight">
                    Returns & Refunds
                </h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/25">
                    <span class="material-symbols-outlined text-[13px] text-rose-400">assignment_return</span>
                    Refund Ledger
                </span>
            </div>
            <p class="font-body-sm text-[12px] text-on-surface-variant m-0">
                All processed returns and refunded amounts
            </p>
        </div>

        <!-- Right Side Actions: Export + New Return Button -->
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="exportToCsv()"
                    class="dashboard-control h-[34px] px-3.5 flex items-center gap-1.5 text-white hover:text-[#F5A623] transition-colors active:scale-95 border-0 font-['Inter'] text-[11px] font-bold uppercase tracking-wider cursor-pointer box-border"
                    title="Export returns to CSV">
                <span class="material-symbols-outlined text-[16px] text-white">download</span>
                <span>EXPORT</span>
            </button>

            <button type="button"
                    @click="openNewReturnModal()"
                    class="h-[34px] px-3.5 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95 shadow-md">
                <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'wght' 700;">add</span>
                <span>New Return</span>
            </button>
        </div>
    </div>

    <!-- Flash Messages (if any) -->
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
         2. KPI ROW (4 CARDS)
         ======================================================== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-3.5 shrink-0">
        <!-- KPI 1: Total Returns -->
        <x-pos.kpi-card
            title="Total Returns"
            icon="history"
            :value="number_format($totalReturns)"
            :is-primary="true"
            :subtext="$isFiltered ? 'Filtered Count' : 'All Processed'"
        />

        <!-- KPI 2: Total Refunded (Red Figure) -->
        <div class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden group transition-all duration-200">
            <div>
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[10px] lg:text-[11px] font-bold text-on-surface-variant uppercase tracking-[0.08em] truncate">
                        Total Refunded
                    </span>
                    <span class="material-symbols-outlined text-[18px] text-rose-400 shrink-0">payments</span>
                </div>
                <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-rose-400 tracking-tight tabular-nums">
                    −Rs. {{ number_format($totalRefunded, 2) }}
                </div>
            </div>
            <div class="text-[10px] text-[#8A8A93] font-medium tracking-wide flex items-center gap-1 mt-2">
                <span>Completed Refunds</span>
            </div>
        </div>

        <!-- KPI 3: Items Returned -->
        <x-pos.kpi-card
            title="Items Returned"
            icon="inventory_2"
            :value="number_format($itemsReturned)"
            subtext="Units Restocked/Logged"
        />

        <!-- KPI 4: Return Rate -->
        <x-pos.kpi-card
            title="Return Rate"
            icon="trending_down"
            :value="number_format($returnRate, 1) . '%'"
            subtext="vs Total Invoices"
        />
    </div>

    <!-- ========================================================
         3. FILTER BAR CARD & STATUS TABS
         ======================================================== -->
    <div class="dashboard-card rounded-[8px] p-2.5 lg:p-3 bg-[#171A20] border border-[#272C35] flex flex-col gap-2.5">
        <!-- Filter Form -->
        <form id="returnsFilterForm" method="GET" action="{{ route('returns.index') }}" class="w-full">
            <input type="hidden" name="status" id="returnsStatusInput" value="{{ request('status', 'all') }}">

            <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-2">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[180px]">
                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[16px] text-[#8A8A93]">search</span>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           x-model="searchQuery"
                           placeholder="Search invoice #, return #, product..."
                           class="dashboard-control w-full h-[34px] pl-8 pr-3 text-[11px] text-white placeholder-[#8A8A93] bg-[#111419] rounded-[6px] border border-[#272C35] focus:border-[#F5A623] outline-none">
                </div>

                <!-- Date Range Cluster -->
                <div class="flex items-center gap-1.5">
                    <div class="relative flex-1 sm:w-[135px]">
                        <input type="date"
                               name="date_from"
                               value="{{ request('date_from') }}"
                               aria-label="Date From"
                               class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] [color-scheme:dark]">
                    </div>
                    <span class="text-on-surface-variant text-[11px] font-semibold">→</span>
                    <div class="relative flex-1 sm:w-[135px]">
                        <input type="date"
                               name="date_to"
                               value="{{ request('date_to') }}"
                               aria-label="Date To"
                               class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] [color-scheme:dark]">
                    </div>
                </div>

                <!-- Reason Select -->
                <div class="w-full lg:w-[140px]">
                    <select name="reason"
                            aria-label="Return Reason"
                            class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] cursor-pointer">
                        <option value="">All Reasons</option>
                        @foreach(\App\Models\ProductReturn::reasonLabels() as $val => $label)
                            <option value="{{ $val }}" {{ request('reason') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Refund Method Select -->
                <div class="w-full lg:w-[130px]">
                    <select name="refund_method"
                            aria-label="Refund Method"
                            class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] cursor-pointer">
                        <option value="">All Methods</option>
                        <option value="cash" {{ request('refund_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="card" {{ request('refund_method') === 'card' ? 'selected' : '' }}>Card</option>
                        <option value="store_credit" {{ request('refund_method') === 'store_credit' ? 'selected' : '' }}>Store Credit</option>
                        <option value="bank" {{ request('refund_method') === 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="submit"
                            class="h-[34px] px-3.5 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95">
                        <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'wght' 700;">filter_alt</span>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('returns.index') }}"
                       class="dashboard-control h-[34px] w-[34px] text-[#8A8A93] hover:text-[#EF4444] rounded-[6px] flex items-center justify-center transition-colors no-underline"
                       title="Clear all filters">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </a>
                </div>
            </div>
        </form>

        <!-- Status Filter Tabs (All / Completed / Pending / Rejected) -->
        <div class="flex items-center gap-1.5 overflow-x-auto custom-scrollbar pt-1 border-t border-[#272C35]/50 select-none">
            <!-- All Tab -->
            <button type="button"
                    @click="setStatusTab('all')"
                    :class="activeStatusTab === 'all' ? 'bg-[#1E222A] text-amber-accent border-amber-accent/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white'"
                    class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors cursor-pointer whitespace-nowrap">
                <span>All Returns</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-white" x-text="returns.length"></span>
            </button>

            <!-- Completed Tab -->
            <button type="button"
                    @click="setStatusTab('completed')"
                    :class="activeStatusTab === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white'"
                    class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors cursor-pointer whitespace-nowrap">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                <span>Completed</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-emerald-400"
                      x-text="returns.filter(r => r.status === 'completed').length"></span>
            </button>

            <!-- Pending Tab -->
            <button type="button"
                    @click="setStatusTab('pending')"
                    :class="activeStatusTab === 'pending' ? 'bg-amber-500/10 text-amber-400 border-amber-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white'"
                    class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors cursor-pointer whitespace-nowrap">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                <span>Pending</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-amber-400"
                      x-text="returns.filter(r => r.status === 'pending').length"></span>
            </button>

            <!-- Rejected Tab -->
            <button type="button"
                    @click="setStatusTab('rejected')"
                    :class="activeStatusTab === 'rejected' ? 'bg-rose-500/10 text-rose-400 border-rose-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white'"
                    class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors cursor-pointer whitespace-nowrap">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                <span>Rejected</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-rose-400"
                      x-text="returns.filter(r => r.status === 'rejected').length"></span>
            </button>
        </div>
    </div>

    <!-- ========================================================
         4. RETURNS DATA TABLE & EMPTY STATES
         ======================================================== -->
    <div class="dashboard-card rounded-[8px] overflow-hidden bg-[#171A20] border border-[#272C35] flex flex-col">
        <!-- Table Header Bar -->
        <div class="px-3.5 lg:px-4 py-2.5 border-b border-[#272C35] flex items-center justify-between bg-[#111419]/60">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px] text-rose-400">assignment_return</span>
                <span class="font-semibold text-white text-[13px] tracking-tight">Return Records</span>
                <span class="px-1.5 py-0.2 rounded bg-surface-variant/40 border border-[#272C35] text-[10px] font-bold text-rose-400 tabular-nums">
                    <span x-text="filteredReturns.length"></span>
                </span>
            </div>

            <div class="text-[11px] text-on-surface-variant">
                Showing {{ $returns->firstItem() ?? 0 }}–{{ $returns->lastItem() ?? 0 }} of {{ $returns->total() }}
            </div>
        </div>

        <!-- Desktop Dense Table View (>= md) -->
        <div class="overflow-x-auto custom-scrollbar hidden md:block" x-show="sortedReturns.length > 0">
            <table class="w-full text-left border-collapse text-body-sm">
                <thead>
                    <tr class="border-b border-[#272C35] text-on-surface-variant font-label-caps text-[10px] uppercase bg-[#111419]/30">
                        <!-- Return # Sortable -->
                        <th @click="toggleSort('return_number')"
                            class="py-2.5 px-3.5 font-bold tracking-wider cursor-pointer hover:text-white transition-colors select-none">
                            <div class="flex items-center gap-1">
                                <span>Return #</span>
                                <span class="material-symbols-outlined text-[13px]"
                                      :class="sortKey === 'return_number' ? 'text-amber-accent' : 'opacity-30'"
                                      x-text="sortKey === 'return_number' ? (sortAsc ? 'arrow_upward' : 'arrow_downward') : 'unfold_more'"></span>
                            </div>
                        </th>

                        <!-- Sale Ref -->
                        <th class="py-2.5 px-3 font-bold tracking-wider">
                            <span>Sale Ref</span>
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

                        <!-- Items -->
                        <th class="py-2.5 px-3 font-bold tracking-wider">
                            <span>Items</span>
                        </th>

                        <!-- Reason -->
                        <th class="py-2.5 px-3 font-bold tracking-wider">
                            <span>Reason</span>
                        </th>

                        <!-- Refund Method -->
                        <th class="py-2.5 px-3 font-bold tracking-wider">
                            <span>Refund Method</span>
                        </th>

                        <!-- Processed By -->
                        <th class="py-2.5 px-3 font-bold tracking-wider">
                            <span>Processed By</span>
                        </th>

                        <!-- Refunded Sortable -->
                        <th @click="toggleSort('total_refunded')"
                            class="py-2.5 px-3.5 font-bold tracking-wider text-right cursor-pointer hover:text-white transition-colors select-none">
                            <div class="flex items-center justify-end gap-1">
                                <span>Refunded</span>
                                <span class="material-symbols-outlined text-[13px]"
                                      :class="sortKey === 'total_refunded' ? 'text-rose-400' : 'opacity-30'"
                                      x-text="sortKey === 'total_refunded' ? (sortAsc ? 'arrow_upward' : 'arrow_downward') : 'unfold_more'"></span>
                            </div>
                        </th>

                        <!-- Actions -->
                        <th class="py-2.5 px-3 font-bold tracking-wider text-right w-[95px]">
                            <span>Actions</span>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="ret in sortedReturns" :key="ret.id">
                        <tr @click="openReturnDrawer(ret)"
                            class="border-b border-[#272C35]/50 last:border-0 hover:bg-[#1E222A] transition-colors cursor-pointer group h-[54px]">

                            <!-- 1. Return # -->
                            <td class="py-2 px-3.5 whitespace-nowrap">
                                <span class="font-mono text-amber-accent group-hover:underline font-bold text-[12px] tracking-tight"
                                      x-text="ret.return_number"></span>
                            </td>

                            <!-- 2. Sale Ref -->
                            <td class="py-2 px-3 whitespace-nowrap" @click.stop>
                                <a :href="ret.invoice_url"
                                   target="_blank"
                                   class="font-mono text-[#d7c3ae] hover:text-amber-accent hover:underline font-semibold text-[12px] tracking-tight flex items-center gap-1">
                                    <span x-text="ret.sale_invoice_number"></span>
                                    <span class="material-symbols-outlined text-[12px] opacity-60">open_in_new</span>
                                </a>
                            </td>

                            <!-- 3. Date & Time -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <div class="text-[12px] text-white font-medium tabular-nums" x-text="ret.date_formatted"></div>
                                <div class="text-[10px] text-[#8A8A93] tabular-nums" x-text="ret.time_formatted"></div>
                            </td>

                            <!-- 4. Items -->
                            <td class="py-2 px-3 max-w-[240px]">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[12px] text-white font-medium truncate max-w-[150px]"
                                          x-text="ret.first_item_name"></span>
                                    <template x-if="ret.items.length > 1">
                                        <span class="px-1.5 py-0.5 rounded bg-[#111419] border border-[#272C35] text-[10px] font-semibold text-amber-accent"
                                              x-text="'+' + (ret.items.length - 1) + ' more'"></span>
                                    </template>
                                </div>
                            </td>

                            <!-- 5. Reason -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-white/5 border border-white/10 text-white text-[11px] font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="getReasonDotClass(ret.reason)"></span>
                                    <span x-text="ret.reason_label"></span>
                                </span>
                            </td>

                            <!-- 6. Refund Method -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-white/5 border border-white/10 text-white text-[11px] font-medium uppercase tracking-wider">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="getPaymentDotClass(ret.refund_method)"></span>
                                    <span x-text="ret.refund_method_label"></span>
                                </span>
                            </td>

                            <!-- 7. Processed By -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-full bg-surface-variant flex items-center justify-center text-[9px] font-bold text-on-surface uppercase flex-shrink-0"
                                          x-text="ret.staff_name.charAt(0)"></span>
                                    <span class="text-[12px] text-[#d7c3ae] font-medium truncate max-w-[100px]"
                                          x-text="ret.staff_name"></span>
                                </div>
                            </td>

                            <!-- 8. Refunded (Red Tabular Figures) -->
                            <td class="py-2 px-3.5 text-right whitespace-nowrap">
                                <span class="font-['Inter'] font-bold text-[13px] text-rose-400 tabular-nums tracking-tight"
                                      x-text="ret.total_refunded_formatted"></span>
                            </td>

                            <!-- 9. Actions -->
                            <td class="py-2 px-3 text-right whitespace-nowrap" @click.stop>
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Primary View Button -->
                                    <button type="button"
                                            @click="openReturnDrawer(ret)"
                                            class="w-7 h-7 rounded-[4px] bg-[#111419] hover:bg-rose-500/15 text-[#8A8A93] hover:text-rose-400 border border-[#272C35] flex items-center justify-center transition-colors cursor-pointer"
                                            title="View return details">
                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                    </button>

                                    <!-- Overflow ⋯ Trigger -->
                                    <button type="button"
                                            @click.stop="toggleActionMenu(ret, $event)"
                                            :class="activeMenuReturn?.id === ret.id ? 'bg-[#1E222A] text-white border-rose-500/50' : 'bg-[#111419] text-[#8A8A93] hover:text-white border-[#272C35]'"
                                            class="w-7 h-7 rounded-[4px] hover:bg-[#1E222A] border flex items-center justify-center transition-colors cursor-pointer"
                                            title="More options">
                                        <span class="material-symbols-outlined text-[16px]">more_horiz</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Mobile Stacked Card View (< md) -->
        <div class="md:hidden flex flex-col divide-y divide-[#272C35]/50" x-show="sortedReturns.length > 0">
            <template x-for="ret in sortedReturns" :key="'mob-' + ret.id">
                <div @click="openReturnDrawer(ret)"
                     class="p-3 hover:bg-[#1E222A] transition-colors cursor-pointer flex flex-col gap-2">
                    
                    <!-- Row 1: Return # | Sale Ref | Refunded -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-amber-accent font-bold text-[13px]" x-text="ret.return_number"></span>
                            <span class="font-mono text-[11px] text-[#8A8A93]" x-text="'for ' + ret.sale_invoice_number"></span>
                        </div>
                        <span class="font-['Inter'] font-bold text-[14px] text-rose-400 tabular-nums" x-text="ret.total_refunded_formatted"></span>
                    </div>

                    <!-- Row 2: Date + Staff -->
                    <div class="flex items-center justify-between text-[11px] text-[#8A8A93]">
                        <span x-text="ret.created_at_formatted"></span>
                        <span x-text="'By: ' + ret.staff_name"></span>
                    </div>

                    <!-- Row 3: Reason & Items -->
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-white truncate max-w-[200px]" x-text="ret.first_item_name"></span>
                        <span class="px-1.5 py-0.2 rounded bg-white/5 border border-white/10 text-white text-[10px]" x-text="ret.reason_label"></span>
                    </div>

                    <!-- Row 4: Actions -->
                    <div class="flex items-center justify-between pt-1" @click.stop>
                        <div class="inline-flex items-center gap-1 text-[10px] text-[#8A8A93]">
                            <span class="w-1.5 h-1.5 rounded-full" :class="getPaymentDotClass(ret.refund_method)"></span>
                            <span x-text="ret.refund_method_label"></span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    @click="openReturnDrawer(ret)"
                                    class="px-2 py-1 rounded bg-[#111419] text-[#d7c3ae] border border-[#272C35] text-[10px] font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[13px]">visibility</span> View
                            </button>
                            <button type="button"
                                    @click.stop="toggleActionMenu(ret, $event)"
                                    class="w-7 h-6 rounded bg-[#111419] text-[#8A8A93] hover:text-white border border-[#272C35] flex items-center justify-center text-[10px] font-semibold">
                                <span class="material-symbols-outlined text-[14px]">more_horiz</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- 1. Default Empty State (No returns in system) -->
        <div x-show="totalAllReturns === 0 && !isFiltered" class="py-16 px-4 text-center">
            <div class="w-14 h-14 rounded-full bg-surface-variant/40 border border-[#272C35] flex items-center justify-center mx-auto mb-3 text-[#5A5A63]">
                <span class="material-symbols-outlined text-[28px]">assignment_return</span>
            </div>
            <h3 class="text-[16px] font-bold text-white mb-1">No returns yet</h3>
            <p class="text-[12px] text-[#8A8A93] max-w-sm mx-auto mb-4 leading-relaxed">
                Refunds processed from the Sales History page will appear here.
            </p>
            <div class="flex items-center justify-center gap-2">
                <button type="button"
                        @click="openNewReturnModal()"
                        class="h-[34px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center gap-1.5 cursor-pointer border-0 transition-colors shadow-md">
                    <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'wght' 700;">add</span>
                    <span>Process a return</span>
                </button>
                <a href="{{ route('sales.index') }}"
                   class="dashboard-control h-[34px] px-3.5 text-[#d7c3ae] hover:text-white text-[11px] font-semibold uppercase tracking-wider rounded-[6px] flex items-center gap-1.5 no-underline transition-colors">
                    <span class="material-symbols-outlined text-[15px]">receipt_long</span>
                    <span>Go to Sales History</span>
                </a>
            </div>
        </div>

        <!-- 2. Filtered Empty State (Returns exist, but none match active filters) -->
        <div x-show="(totalAllReturns > 0 || isFiltered) && sortedReturns.length === 0" class="py-14 px-4 text-center">
            <span class="material-symbols-outlined text-[36px] text-[#5A5A63] mb-2 block">filter_alt_off</span>
            <div class="text-[14px] font-semibold text-white mb-1">No returns match these filters</div>
            <p class="text-[12px] text-[#8A8A93] max-w-sm mx-auto mb-3">
                Try adjusting your search query, status tab, or date range.
            </p>
            <a href="{{ route('returns.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[6px] bg-[#111419] hover:bg-[#1E222A] text-amber-accent border border-[#272C35] text-[11px] font-bold uppercase tracking-wider no-underline transition-colors">
                <span class="material-symbols-outlined text-[14px]">refresh</span>
                <span>Clear Filters</span>
            </a>
        </div>

        <!-- ========================================================
             5. FOOTER & PAGINATION
             ======================================================== -->
        @if ($returns->hasPages())
            <div class="p-3 border-t border-[#272C35] flex flex-col sm:flex-row items-center justify-between gap-3 bg-[#111419]/40">
                <div class="text-[11px] text-[#8A8A93]">
                    Showing {{ $returns->firstItem() ?? 0 }} to {{ $returns->lastItem() ?? 0 }} of {{ $returns->total() }} returns
                </div>

                <!-- Custom Dark Pagination Links -->
                <div class="flex items-center gap-1 select-none">
                    @if ($returns->onFirstPage())
                        <span class="dashboard-control w-8 h-8 rounded-[4px] opacity-40 flex items-center justify-center cursor-not-allowed">
                            <span class="material-symbols-outlined text-[16px] text-[#8A8A93]">chevron_left</span>
                        </span>
                    @else
                        <a href="{{ $returns->previousPageUrl() }}"
                           class="dashboard-control w-8 h-8 rounded-[4px] flex items-center justify-center hover:text-amber-accent transition-colors no-underline">
                            <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                        </a>
                    @endif

                    @foreach ($returns->getUrlRange(max(1, $returns->currentPage() - 2), min($returns->lastPage(), $returns->currentPage() + 2)) as $page => $url)
                        @if ($page == $returns->currentPage())
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

                    @if ($returns->hasMorePages())
                        <a href="{{ $returns->nextPageUrl() }}"
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
         6. SHARED RETURN DETAIL DRAWER (Teleported to Body)
         ======================================================== -->
    <x-pos.return-drawer />

    <!-- Actions "⋯" Dropdown (Teleported to Body with Fixed Coordinates) -->
    <template x-teleport="body">
        <div id="returnsActionsFloatingMenu"
             x-show="activeMenuReturn"
             @click.outside="closeActionMenu()"
             @keydown.escape.window="closeActionMenu()"
             :style="`position: fixed; top: ${menuY}px; left: ${menuX}px; width: 180px; z-index: 9999; margin: 0;`"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="rounded-[6px] bg-[#13151A] border border-white/10 shadow-2xl py-1.5 flex flex-col text-left text-[11px] select-none"
             style="display: none;">
            
            <!-- Print Credit Note -->
            <button type="button"
                    @click="const r = activeMenuReturn; closeActionMenu(); printCreditNote(r)"
                    class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer transition-colors">
                <span class="material-symbols-outlined text-[14px]">print</span>
                <span>Print Credit Note</span>
            </button>

            <!-- View Original Invoice -->
            <a :href="activeMenuReturn?.invoice_url"
               target="_blank"
               class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left no-underline cursor-pointer transition-colors">
                <span class="material-symbols-outlined text-[14px]">receipt</span>
                <span>View Original Invoice</span>
            </a>

            <!-- Reprint Original Receipt -->
            <button type="button"
                    @click="const r = activeMenuReturn; closeActionMenu(); printOriginalReceipt(r)"
                    class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer transition-colors">
                <span class="material-symbols-outlined text-[14px]">receipt_long</span>
                <span>Reprint Receipt</span>
            </button>
        </div>
    </template>

    <!-- Hidden iframe for instant direct thermal receipt / credit note printing -->
    <iframe id="returnsSilentPrintFrame" class="hidden" style="display:none;" title="Print Frame"></iframe>

    <!-- ========================================================
         7. PROCESS NEW RETURN INVOICE LOOKUP MODAL
         ======================================================== -->
    <template x-teleport="body">
        <div x-show="newReturnModalOpen"
             @keydown.escape.window="newReturnModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-md rounded-[8px] bg-[#13151A] border border-[#272C35] p-5 shadow-2xl flex flex-col gap-3.5">
                <div class="flex items-center justify-between border-b border-[#272C35] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-amber-accent">assignment_return</span>
                        <h4 class="m-0 text-[15px] font-bold text-white">Process New Return</h4>
                    </div>
                    <button type="button"
                            @click="newReturnModalOpen = false"
                            class="dashboard-control w-6 h-6 rounded flex items-center justify-center text-[#8A8A93] hover:text-white border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <div>
                    <p class="text-[12px] text-[#8A8A93] m-0 mb-2 leading-relaxed">
                        Enter the Invoice / Bill Number of the sale you wish to process a refund or item return for:
                    </p>
                    <div class="flex gap-2">
                        <input type="text"
                               x-model="lookupInvoiceNumber"
                               @keydown.enter.prevent="submitInvoiceLookup()"
                               class="dashboard-control flex-1 h-[36px] px-3 text-[12px] text-white bg-[#111419] rounded-[6px] font-mono"
                               placeholder="e.g. INV-01114 or 0023">
                        <button type="button"
                                @click="submitInvoiceLookup()"
                                class="h-[36px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center gap-1 cursor-pointer border-0">
                            <span>Lookup</span>
                            <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                        </button>
                    </div>
                </div>

                <div class="pt-2 border-t border-[#272C35] flex items-center justify-between text-[11px]">
                    <span class="text-[#8A8A93]">Or browse all past sales directly:</span>
                    <a href="{{ route('sales.index') }}"
                       class="text-amber-accent hover:underline font-semibold flex items-center gap-1 no-underline">
                        <span>Sales History</span>
                        <span class="material-symbols-outlined text-[12px]">open_in_new</span>
                    </a>
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
function returnsLedgerApp(initialReturns, initialStatusTab, totalAllReturnsCount) {
    return {
        returns: initialReturns || [],
        activeStatusTab: initialStatusTab || 'all',
        totalAllReturns: totalAllReturnsCount || 0,
        isFiltered: {{ $isFiltered ? 'true' : 'false' }},
        searchQuery: '',
        sortKey: 'timestamp',
        sortAsc: false,
        drawerOpen: false,
        activeReturn: null,
        activeMenuReturn: null,
        menuX: 0,
        menuY: 0,
        newReturnModalOpen: false,
        lookupInvoiceNumber: '',

        initReturns() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('search')) {
                this.searchQuery = params.get('search');
            }

            // Close actions dropdown on window scroll or resize
            window.addEventListener('scroll', () => {
                if (this.activeMenuReturn) this.closeActionMenu();
            }, true);
            window.addEventListener('resize', () => {
                if (this.activeMenuReturn) this.closeActionMenu();
            });
        },

        setStatusTab(tab) {
            this.activeStatusTab = tab;
            const input = document.getElementById('returnsStatusInput');
            if (input) input.value = tab;
        },

        get filteredReturns() {
            let list = this.returns;

            // Status tab filter
            if (this.activeStatusTab !== 'all') {
                list = list.filter(r => r.status === this.activeStatusTab);
            }

            // Quick search filter
            if (!this.searchQuery.trim()) {
                return list;
            }
            const q = this.searchQuery.toLowerCase().trim();
            return list.filter(r => {
                const retMatch = r.plain_return_number.toLowerCase().includes(q) || r.return_number.toLowerCase().includes(q);
                const saleMatch = r.sale_invoice_number.toLowerCase().includes(q);
                const staffMatch = (r.staff_name || '').toLowerCase().includes(q);
                const reasonMatch = (r.reason_label || '').toLowerCase().includes(q);
                const itemMatch = (r.items || []).some(item => (item.name || '').toLowerCase().includes(q) || (item.sku || '').toLowerCase().includes(q));
                return retMatch || saleMatch || staffMatch || reasonMatch || itemMatch;
            });
        },

        get sortedReturns() {
            const list = [...this.filteredReturns];
            const k = this.sortKey;
            const asc = this.sortAsc;

            return list.sort((a, b) => {
                let valA = a[k];
                let valB = b[k];

                if (k === 'return_number') {
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
                this.sortAsc = key === 'return_number' ? true : false;
            }
        },

        getReasonDotClass(reason) {
            const r = (reason || '').toLowerCase();
            if (r === 'damaged') return 'bg-rose-400';
            if (r === 'wrong_item') return 'bg-amber-400';
            if (r === 'customer_request') return 'bg-sky-400';
            if (r === 'quality_issue') return 'bg-purple-400';
            return 'bg-slate-400';
        },

        getPaymentDotClass(method) {
            const m = (method || '').toLowerCase();
            if (m === 'cash') return 'bg-emerald-400';
            if (m === 'card') return 'bg-sky-400';
            if (m === 'store_credit') return 'bg-amber-400';
            if (m === 'bank') return 'bg-cyan-400';
            return 'bg-slate-400';
        },

        openReturnDrawer(ret) {
            this.activeReturn = ret;
            this.drawerOpen = true;
        },

        toggleActionMenu(ret, event) {
            if (this.activeMenuReturn && this.activeMenuReturn.id === ret.id) {
                this.closeActionMenu();
                return;
            }

            const btn = event.currentTarget || event.target.closest('button');
            if (!btn) return;

            // Immediately compute synchronous coordinates
            const rect = btn.getBoundingClientRect();
            const menuWidth = 180;
            const menuHeight = 120;

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
            this.activeMenuReturn = ret;

            // Refine with Floating UI on next tick
            this.$nextTick(() => {
                const menuEl = document.getElementById('returnsActionsFloatingMenu');
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

        closeActionMenu() {
            this.activeMenuReturn = null;
        },

        openNewReturnModal() {
            this.lookupInvoiceNumber = '';
            this.newReturnModalOpen = true;
        },

        submitInvoiceLookup() {
            const raw = (this.lookupInvoiceNumber || '').trim();
            if (!raw) {
                alert('Please enter an invoice or bill number.');
                return;
            }
            window.location.href = `/sales?invoice=${encodeURIComponent(raw)}`;
        },

        printCreditNote(ret) {
            if (!ret) return;
            const frame = document.getElementById('returnsSilentPrintFrame');
            if (frame) {
                frame.src = ret.show_url;
                frame.onload = function() {
                    try {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                    } catch (e) {
                        window.open(ret.show_url, '_blank');
                    }
                };
            } else {
                window.open(ret.show_url, '_blank');
            }
        },

        printOriginalReceipt(ret) {
            if (!ret) return;
            const frame = document.getElementById('returnsSilentPrintFrame');
            if (frame) {
                frame.src = ret.invoice_embed_url;
                frame.onload = function() {
                    try {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                    } catch (e) {
                        window.open(ret.invoice_url, '_blank');
                    }
                };
            } else {
                window.open(ret.invoice_url, '_blank');
            }
        },

        exportToCsv() {
            const rows = this.filteredReturns;
            if (!rows.length) {
                alert('No return records to export.');
                return;
            }

            const headers = ['Return Number', 'Sale Invoice', 'Date', 'Time', 'Staff', 'Reason', 'Refund Method', 'Status', 'Items Count', 'Total Refunded'];
            const csvRows = [headers.join(',')];

            rows.forEach(r => {
                const values = [
                    `"${r.plain_return_number}"`,
                    `"${r.sale_invoice_number}"`,
                    `"${r.date_formatted}"`,
                    `"${r.time_formatted}"`,
                    `"${r.staff_name.replace(/"/g, '""')}"`,
                    `"${r.reason_label}"`,
                    `"${r.refund_method_label}"`,
                    `"${r.status}"`,
                    r.items_count,
                    r.total_refunded.toFixed(2),
                ];
                csvRows.push(values.join(','));
            });

            const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', `returns_refunds_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };
}
</script>
@endpush
