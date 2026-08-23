{{-- ============================================
     Sales History Page - Redesigned Dark Dashboard Experience
     Jewellery / Clothing Outlet POS
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Sales History')

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
    // Prepare JSON payload for Alpine.js interactivity (drawer, tabs, quick sorting, search, action menus)
    $salesPayload = $sales->map(function ($sale) {
        $billNo = $sale->invoice_number ?? ('INV-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT));
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

        // Compute return details
        $totalReturnedQty = 0;
        $totalRefundedAmount = 0;
        $returnsMapped = [];
        if ($sale->has_return && $sale->returns) {
            $totalReturnedQty = $sale->returns->flatMap->items->sum('qty');
            $totalRefundedAmount = $sale->returns->sum('total_refunded');
            $returnsMapped = $sale->returns->map(function ($r) {
                return [
                    'id' => $r->id,
                    'total_refunded' => (float) $r->total_refunded,
                    'refund_method' => $r->refund_method_label ?? $r->refund_method,
                    'reason' => $r->reason_label ?? $r->reason,
                    'date' => $r->created_at->format('M d, Y · h:i A'),
                ];
            })->toArray();
        }

        $totalSaleQty = $sale->items->sum('qty');
        $isFullyReturned = $sale->has_return && ($totalReturnedQty >= $totalSaleQty || $totalRefundedAmount >= $sale->total);
        $isPartiallyReturned = $sale->has_return && !$isFullyReturned;

        $status = 'Paid';
        if ($isFullyReturned) {
            $status = 'Refunded';
        } elseif ($isPartiallyReturned) {
            $status = 'Partially Refunded';
        }

        // SMS prefill
        $smsBillTo = $sale->smsOutbox->firstWhere('purpose', \App\Models\SmsOutbox::PURPOSE_BILL)?->to_number;
        $smsPrefill = \App\Services\Sms\SriLankanPhone::tryNormalizeLocal($smsBillTo) ?? ($sale->customer?->phone ?? '');

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
            'net_total' => (float) ($sale->net_total ?? $sale->total),
            'net_total_formatted' => 'Rs. ' . number_format((float) ($sale->net_total ?? $sale->total), 2),
            'amount_received' => $sale->amount_received ? (float) $sale->amount_received : null,
            'amount_received_formatted' => $sale->amount_received ? 'Rs. ' . number_format((float) $sale->amount_received, 2) : null,
            'change_amount' => $sale->change_amount ? (float) $sale->change_amount : null,
            'change_amount_formatted' => $sale->change_amount ? 'Rs. ' . number_format((float) $sale->change_amount, 2) : null,
            'payment_splits' => $sale->payment_splits ?? [],
            'notes' => $sale->notes ?? null,
            'status' => $status,
            'has_return' => (bool) $sale->has_return,
            'is_fully_returned' => $isFullyReturned,
            'returns' => $returnsMapped,
            'total_refunded' => (float) $totalRefundedAmount,
            'total_refunded_formatted' => $totalRefundedAmount > 0 ? 'Rs. ' . number_format((float) $totalRefundedAmount, 2) : null,
            'items_count' => (int) $sale->items->sum('qty'),
            'distinct_items_count' => $sale->items->count(),
            'items' => $itemsMapped,
            'first_item_name' => $itemsMapped->first()['name'] ?? 'No items',
            'first_item_variant' => $itemsMapped->first()['variant'] ?? null,
            'invoice_url' => route('invoice.show', ['id' => $sale->id]),
            'embed_url' => route('invoice.show', ['id' => $sale->id, 'embed' => 1]),
            'return_url' => route('returns.create', $sale->id),
            'destroy_url' => route('sales.destroy', $sale->id),
            'sms_phone' => $smsPrefill,
        ];
    });

    $isFiltered = request()->filled('invoice') || request()->filled('date_from') || request()->filled('date_to') || request()->filled('payment_method') || request()->filled('staff_id') || request()->filled('status');
    $currentStatusTab = request('status', 'all');
@endphp

@section('content')
<div x-data="salesHistoryApp(@js($salesPayload), '{{ $currentStatusTab }}')"
     x-init="initSales()"
     class="w-full flex flex-col select-none gap-3.5 box-border">

    <!-- ========================================================
         1. PAGE HEADER
         ======================================================== -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 shrink-0">
        <!-- Title & Subtitle -->
        <div class="flex flex-col sm:flex-row sm:items-baseline gap-2 sm:gap-3">
            <div class="flex items-center gap-2.5">
                <h1 class="font-headline-lg text-[20px] lg:text-[22px] text-on-surface m-0 font-bold tracking-tight">
                    Sales History
                </h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-surface-variant/40 text-on-surface-variant border border-outline-variant/30">
                    <span class="material-symbols-outlined text-[13px] text-amber-accent">receipt_long</span>
                    Invoices & Refunds
                </span>
            </div>
            <p class="font-body-sm text-[12px] text-on-surface-variant m-0">
                All invoices, returns and refunds
            </p>
        </div>

        <!-- Right Side: Export Button -->
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="exportToCsv()"
                    class="dashboard-control h-[34px] px-3.5 flex items-center gap-1.5 text-white hover:text-[#F5A623] transition-colors active:scale-95 border-0 font-['Inter'] text-[11px] font-bold uppercase tracking-wider cursor-pointer box-border"
                    title="Export sales to CSV">
                <span class="material-symbols-outlined text-[16px] text-white" style="font-variation-settings: 'wght' 400, 'opsz' 20;">download</span>
                <span>EXPORT</span>
            </button>
        </div>
    </div>

    <!-- ========================================================
         2. KPI ROW (4 CARDS)
         ======================================================== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-3.5 shrink-0">
        <!-- KPI 1: Total Sales (Primary Glow) -->
        <x-pos.kpi-card
            title="Total Sales"
            icon="receipt_long"
            :value="number_format($totalSales)"
            :is-primary="true"
            :subtext="$isFiltered ? 'Active Filter' : 'All Time'"
        />

        <!-- KPI 2: Net Revenue -->
        <x-pos.kpi-card
            title="Net Revenue"
            icon="payments"
            :value="'Rs. ' . number_format($netRevenue, 2)"
            :subtext="$isFiltered ? 'Filtered Net' : 'After Refunds'"
        />

        <!-- KPI 3: Average Sale -->
        <x-pos.kpi-card
            title="Average Sale"
            icon="calculate"
            :value="'Rs. ' . number_format($avgSale, 2)"
            subtext="Per Invoice"
        />

        <!-- KPI 4: Refunded (Amount + Count) -->
        <x-pos.kpi-card
            title="Refunded"
            icon="history"
            :value="'Rs. ' . number_format($totalRefunded, 2)"
            :subtext="$totalRefundedCount . ($totalRefundedCount === 1 ? ' refund' : ' refunds')"
        />
    </div>

    <!-- ========================================================
         3. FILTER BAR CARD & STATUS TABS
         ======================================================== -->
    <div class="dashboard-card rounded-[8px] p-2.5 lg:p-3 bg-[#171A20] border border-[#272C35] flex flex-col gap-2.5">
        <!-- Filter Form -->
        <form id="salesFilterForm" method="GET" action="{{ route('sales.index') }}" class="w-full">
            <input type="hidden" name="status" id="statusFilterInput" value="{{ request('status', 'all') }}">

            <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-2">
                <!-- Search Invoice # Input -->
                <div class="relative flex-1 min-w-[180px]">
                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[16px] text-[#8A8A93]">search</span>
                    <input type="text"
                           name="invoice"
                           value="{{ request('invoice') }}"
                           x-model="searchQuery"
                           placeholder="Search invoice #, customer, item..."
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

                <!-- Payment Method Select -->
                <div class="w-full lg:w-[130px]">
                    <select name="payment_method"
                            aria-label="Payment Method"
                            class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] cursor-pointer">
                        <option value="">All Methods</option>
                        <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="card" {{ request('payment_method') === 'card' ? 'selected' : '' }}>Card</option>
                        <option value="koko" {{ request('payment_method') === 'koko' ? 'selected' : '' }}>Koko</option>
                        <option value="mintpay" {{ request('payment_method') === 'mintpay' ? 'selected' : '' }}>Mint Pay</option>
                        <option value="bank" {{ request('payment_method') === 'bank' ? 'selected' : '' }}>Bank Deposit</option>
                        <option value="payzy" {{ request('payment_method') === 'payzy' ? 'selected' : '' }}>Payzy</option>
                        <option value="multipay" {{ request('payment_method') === 'multipay' ? 'selected' : '' }}>Multi Pay</option>
                    </select>
                </div>

                <!-- Staff Select -->
                <div class="w-full lg:w-[130px]">
                    <select name="staff_id"
                            aria-label="Staff Member"
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
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="submit"
                            class="h-[34px] px-3.5 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95">
                        <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'wght' 700;">filter_alt</span>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('sales.index') }}"
                       class="dashboard-control h-[34px] w-[34px] text-[#8A8A93] hover:text-[#EF4444] rounded-[6px] flex items-center justify-center transition-colors no-underline"
                       title="Clear all filters">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </a>
                </div>
            </div>
        </form>

        <!-- Status Filter Tabs (All / Paid / Refunded / Partially Refunded / Void) -->
        <div class="flex items-center gap-1.5 overflow-x-auto custom-scrollbar pt-1 border-t border-[#272C35]/50 select-none">
            <!-- All Tab -->
            <button type="button"
                    @click="setStatusTab('all')"
                    :class="activeStatusTab === 'all' ? 'bg-[#1E222A] text-amber-accent border-amber-accent/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white'"
                    class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors cursor-pointer whitespace-nowrap">
                <span>All Invoices</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-white" x-text="sales.length"></span>
            </button>

            <!-- Paid Tab -->
            <button type="button"
                    @click="setStatusTab('paid')"
                    :class="activeStatusTab === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white'"
                    class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors cursor-pointer whitespace-nowrap">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                <span>Paid</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-emerald-400"
                      x-text="sales.filter(s => s.status === 'Paid').length"></span>
            </button>

            <!-- Refunded Tab -->
            <button type="button"
                    @click="setStatusTab('refunded')"
                    :class="activeStatusTab === 'refunded' ? 'bg-rose-500/10 text-rose-400 border-rose-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white'"
                    class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors cursor-pointer whitespace-nowrap">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                <span>Refunded</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-rose-400"
                      x-text="sales.filter(s => s.status === 'Refunded').length"></span>
            </button>

            <!-- Partially Refunded Tab -->
            <button type="button"
                    @click="setStatusTab('partially_refunded')"
                    :class="activeStatusTab === 'partially_refunded' ? 'bg-amber-500/10 text-amber-400 border-amber-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white'"
                    class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors cursor-pointer whitespace-nowrap">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                <span>Partially Refunded</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-amber-400"
                      x-text="sales.filter(s => s.status === 'Partially Refunded').length"></span>
            </button>

            <!-- Void Tab -->
            <button type="button"
                    @click="setStatusTab('void')"
                    :class="activeStatusTab === 'void' ? 'bg-slate-500/10 text-slate-400 border-slate-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white'"
                    class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors cursor-pointer whitespace-nowrap">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                <span>Void</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-slate-400"
                      x-text="sales.filter(s => s.status === 'Void').length"></span>
            </button>
        </div>
    </div>

    <!-- ========================================================
         4. SALES HISTORY DATA TABLE
         ======================================================== -->
    <div class="dashboard-card rounded-[8px] overflow-hidden bg-[#171A20] border border-[#272C35] flex flex-col">
        <!-- Table Header Bar -->
        <div class="px-3.5 lg:px-4 py-2.5 border-b border-[#272C35] flex items-center justify-between bg-[#111419]/60">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px] text-amber-accent">receipt</span>
                <span class="font-semibold text-white text-[13px] tracking-tight">Invoices</span>
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
                        <!-- Invoice No Sortable -->
                        <th @click="toggleSort('bill_number')"
                            class="py-2.5 px-3.5 font-bold tracking-wider cursor-pointer hover:text-white transition-colors select-none">
                            <div class="flex items-center gap-1">
                                <span>Invoice No</span>
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

                        <!-- Items -->
                        <th class="py-2.5 px-3 font-bold tracking-wider">
                            <span>Items</span>
                        </th>

                        <!-- Staff -->
                        <th class="py-2.5 px-3 font-bold tracking-wider">
                            <span>Staff</span>
                        </th>

                        <!-- Payment Method (Muted badge + dot) -->
                        <th class="py-2.5 px-3 font-bold tracking-wider">
                            <span>Payment</span>
                        </th>

                        <!-- Status Pill -->
                        <th class="py-2.5 px-3 font-bold tracking-wider">
                            <span>Status</span>
                        </th>

                        <!-- Amount Sortable -->
                        <th @click="toggleSort('net_total')"
                            class="py-2.5 px-3.5 font-bold tracking-wider text-right cursor-pointer hover:text-white transition-colors select-none">
                            <div class="flex items-center justify-end gap-1">
                                <span>Amount</span>
                                <span class="material-symbols-outlined text-[13px]"
                                      :class="sortKey === 'net_total' ? 'text-amber-accent' : 'opacity-30'"
                                      x-text="sortKey === 'net_total' ? (sortAsc ? 'arrow_upward' : 'arrow_downward') : 'unfold_more'"></span>
                            </div>
                        </th>

                        <!-- Actions -->
                        <th class="py-2.5 px-3 font-bold tracking-wider text-right w-[95px]">
                            <span>Actions</span>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="sale in sortedSales" :key="sale.id">
                        <tr @click="openReceiptDrawer(sale)"
                            class="border-b border-[#272C35]/50 last:border-0 hover:bg-[#1E222A] transition-colors cursor-pointer group h-[54px]">

                            <!-- 1. Invoice No -->
                            <td class="py-2 px-3.5 whitespace-nowrap">
                                <span class="font-mono text-amber-accent group-hover:underline font-bold text-[12px] tracking-tight"
                                      x-text="sale.bill_number"></span>
                            </td>

                            <!-- 2. Date & Time (Two lines) -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <div class="text-[12px] text-white font-medium tabular-nums" x-text="sale.date_formatted"></div>
                                <div class="text-[10px] text-[#8A8A93] tabular-nums" x-text="sale.time_formatted"></div>
                            </td>

                            <!-- 3. Items (First product + chip) -->
                            <td class="py-2 px-3 max-w-[260px]">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[12px] text-white font-medium truncate max-w-[170px]"
                                          x-text="sale.first_item_name"></span>
                                    <template x-if="sale.items.length > 1">
                                        <span class="px-1.5 py-0.5 rounded bg-[#111419] border border-[#272C35] text-[10px] font-semibold text-amber-accent"
                                              x-text="'+' + (sale.items.length - 1) + ' more'"></span>
                                    </template>
                                </div>
                            </td>

                            <!-- 4. Staff (Avatar + Name) -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-full bg-surface-variant flex items-center justify-center text-[9px] font-bold text-on-surface uppercase flex-shrink-0"
                                          x-text="sale.staff_name.charAt(0)"></span>
                                    <span class="text-[12px] text-[#d7c3ae] font-medium truncate max-w-[100px]"
                                          x-text="sale.staff_name"></span>
                                </div>
                            </td>

                            <!-- 5. Payment (Muted badge with colored dot) -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-white/5 border border-white/10 text-white text-[11px] font-medium uppercase tracking-wider">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="getPaymentDotClass(sale.payment_method)"></span>
                                    <span x-text="formatPaymentLabel(sale.payment_method)"></span>
                                </span>
                            </td>

                            <!-- 6. Status Pill -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <template x-if="sale.status === 'Paid'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Paid
                                    </span>
                                </template>
                                <template x-if="sale.status === 'Refunded'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>Refunded
                                    </span>
                                </template>
                                <template x-if="sale.status === 'Partially Refunded'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>Partial Refund
                                    </span>
                                </template>
                                <template x-if="sale.status === 'Void'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-500/10 text-slate-400 border border-slate-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Void
                                    </span>
                                </template>
                            </td>

                            <!-- 7. Amount (Single figure or struck-through with discount) -->
                            <td class="py-2 px-3.5 text-right whitespace-nowrap">
                                <template x-if="sale.discount_amount > 0">
                                    <div>
                                        <span class="text-[10px] text-[#8A8A93] line-through tabular-nums block"
                                              x-text="sale.total_formatted"></span>
                                        <span class="font-['Inter'] font-bold text-[13px] text-white tabular-nums tracking-tight block"
                                              x-text="sale.net_total_formatted"></span>
                                        <span class="text-[9px] text-amber-accent font-semibold tabular-nums block"
                                              x-text="sale.discount_formatted"></span>
                                    </div>
                                </template>
                                <template x-if="sale.discount_amount <= 0">
                                    <span class="font-['Inter'] font-bold text-[13px] text-white tabular-nums tracking-tight"
                                          x-text="sale.net_total_formatted"></span>
                                </template>
                            </td>

                            <!-- 8. Actions (View + Overflow Menu) -->
                            <td class="py-2 px-3 text-right whitespace-nowrap" @click.stop>
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Primary View Button -->
                                    <button type="button"
                                            @click="openReceiptDrawer(sale)"
                                            class="w-7 h-7 rounded-[4px] bg-[#111419] hover:bg-amber-accent/15 text-[#8A8A93] hover:text-amber-accent border border-[#272C35] flex items-center justify-center transition-colors cursor-pointer"
                                            title="View receipt detail">
                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                    </button>

                                    <!-- Overflow ⋯ Menu Trigger -->
                                    <button type="button"
                                            @click.stop="toggleActionMenu(sale, $event)"
                                            :class="activeMenuSale?.id === sale.id ? 'bg-[#1E222A] text-white border-amber-accent/50' : 'bg-[#111419] text-[#8A8A93] hover:text-white border-[#272C35]'"
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
        <div class="md:hidden flex flex-col divide-y divide-[#272C35]/50">
            <template x-for="sale in sortedSales" :key="'mob-' + sale.id">
                <div @click="openReceiptDrawer(sale)"
                     class="p-3 hover:bg-[#1E222A] transition-colors cursor-pointer flex flex-col gap-2">
                    
                    <!-- Row 1: Invoice # | Status | Amount -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-amber-accent font-bold text-[13px]" x-text="sale.bill_number"></span>
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider"
                                  :class="sale.status === 'Paid' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'"
                                  x-text="sale.status"></span>
                        </div>
                        <span class="font-['Inter'] font-bold text-[14px] text-white tabular-nums" x-text="sale.net_total_formatted"></span>
                    </div>

                    <!-- Row 2: Date + Staff -->
                    <div class="flex items-center justify-between text-[11px] text-[#8A8A93]">
                        <span x-text="sale.created_at_formatted"></span>
                        <span x-text="'Staff: ' + sale.staff_name"></span>
                    </div>

                    <!-- Row 3: Items Summary -->
                    <div class="flex items-center gap-1.5 flex-wrap text-[11px]">
                        <span class="text-white truncate max-w-[200px]" x-text="sale.first_item_name"></span>
                        <template x-if="sale.items.length > 1">
                            <span class="px-1.5 py-0.5 rounded bg-[#111419] border border-[#272C35] text-[9px] font-semibold text-amber-accent"
                                  x-text="'+' + (sale.items.length - 1) + ' more'"></span>
                        </template>
                    </div>

                    <!-- Row 4: Actions -->
                    <div class="flex items-center justify-between pt-1" @click.stop>
                        <div class="inline-flex items-center gap-1 text-[10px] text-[#8A8A93]">
                            <span class="w-1.5 h-1.5 rounded-full" :class="getPaymentDotClass(sale.payment_method)"></span>
                            <span x-text="formatPaymentLabel(sale.payment_method)"></span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    @click="openReceiptDrawer(sale)"
                                    class="px-2 py-1 rounded bg-[#111419] text-[#d7c3ae] border border-[#272C35] text-[10px] font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[13px]">visibility</span> View
                            </button>
                            <button type="button"
                                    @click.stop="toggleActionMenu(sale, $event)"
                                    class="w-7 h-6 rounded bg-[#111419] text-[#8A8A93] hover:text-white border border-[#272C35] flex items-center justify-center text-[10px] font-semibold">
                                <span class="material-symbols-outlined text-[14px]">more_horiz</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="sortedSales.length === 0" class="py-12 px-4 text-center">
            <span class="material-symbols-outlined text-[36px] text-[#5A5A63] mb-2 block">receipt_long</span>
            <div class="text-[14px] font-semibold text-white mb-1">No invoices match these filters</div>
            <p class="text-[12px] text-[#8A8A93] max-w-sm mx-auto mb-3">
                Try adjusting your search query, status tab, or date range.
            </p>
            <a href="{{ route('sales.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[6px] bg-[#111419] hover:bg-[#1E222A] text-amber-accent border border-[#272C35] text-[11px] font-bold uppercase tracking-wider no-underline transition-colors">
                <span class="material-symbols-outlined text-[14px]">refresh</span>
                <span>Clear Filters</span>
            </a>
        </div>

        <!-- ========================================================
             5. FOOTER & PAGINATION
             ======================================================== -->
        @if ($sales->hasPages())
            <div class="p-3 border-t border-[#272C35] flex flex-col sm:flex-row items-center justify-between gap-3 bg-[#111419]/40">
                <div class="text-[11px] text-[#8A8A93]">
                    Showing {{ $sales->firstItem() ?? 0 }} to {{ $sales->lastItem() ?? 0 }} of {{ $sales->total() }} invoices
                </div>

                <!-- Custom Dark Pagination Links -->
                <div class="flex items-center gap-1 select-none">
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
         6. SHARED RECEIPT DETAIL DRAWER (Teleported to Body)
         ======================================================== -->
    <x-pos.receipt-drawer />

    <!-- Actions "⋯" Dropdown (Teleported to Body with Fixed Coordinates) -->
    <template x-teleport="body">
        <div id="salesActionsFloatingMenu"
             x-show="activeMenuSale"
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
            
            <!-- Print Receipt -->
            <button type="button"
                    @click="const s = activeMenuSale; closeActionMenu(); printReceipt(s)"
                    class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer transition-colors">
                <span class="material-symbols-outlined text-[14px]">print</span>
                <span>Print Receipt</span>
            </button>

            <!-- Send SMS Bill -->
            <button type="button"
                    @click="const s = activeMenuSale; closeActionMenu(); triggerSmsModal(s)"
                    class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer transition-colors">
                <span class="material-symbols-outlined text-[14px]">sms</span>
                <span>Send SMS Bill</span>
            </button>

            <!-- View / Add Note -->
            <button type="button"
                    @click="const s = activeMenuSale; closeActionMenu(); openReceiptDrawer(s)"
                    class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer transition-colors">
                <span class="material-symbols-outlined text-[14px]">edit_note</span>
                <span>View / Add Note</span>
            </button>

            <!-- Red Actions Divider -->
            <template x-if="activeMenuSale && !activeMenuSale.is_fully_returned && activeMenuSale.status !== 'Void'">
                <div class="border-t border-white/10 my-1"></div>
            </template>

            <!-- Process Refund (Red text) -->
            <template x-if="activeMenuSale && !activeMenuSale.is_fully_returned && activeMenuSale.status !== 'Void'">
                <a :href="activeMenuSale?.return_url"
                   class="px-3 py-1.5 text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 flex items-center gap-2 w-full text-left no-underline font-medium transition-colors">
                    <span class="material-symbols-outlined text-[14px]">undo</span>
                    <span>Process Refund</span>
                </a>
            </template>

            <!-- Delete Sale (Red text) -->
            <template x-if="activeMenuSale && !activeMenuSale.is_fully_returned && activeMenuSale.status !== 'Void'">
                <button type="button"
                        @click="const s = activeMenuSale; closeActionMenu(); confirmDelete(s)"
                        class="px-3 py-1.5 text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer font-medium transition-colors">
                    <span class="material-symbols-outlined text-[14px]">delete</span>
                    <span>Delete Sale</span>
                </button>
            </template>
        </div>
    </template>

    <!-- Hidden iframe for instant direct thermal receipt printing -->
    <iframe id="receiptSilentPrintFrame" class="hidden" style="display:none;" title="Print Frame"></iframe>

    <!-- Delete Confirmation Modal (Alpine Powered) -->
    <template x-teleport="body">
        <div x-show="deleteModalOpen"
             @keydown.escape.window="deleteModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-sm rounded-[8px] bg-[#13151A] border border-rose-500/30 p-5 shadow-2xl flex flex-col gap-3.5">
                <div class="flex items-center gap-2.5 text-rose-400">
                    <span class="material-symbols-outlined text-[24px]">warning</span>
                    <h4 class="m-0 text-[15px] font-bold text-white">Delete Sale Record?</h4>
                </div>
                <p class="text-[12px] text-[#d7c3ae] m-0 leading-relaxed">
                    Are you sure you want to delete invoice <strong class="text-white font-mono" x-text="saleToDelete?.bill_number"></strong>? This will permanently remove the record and restore the inventory stock.
                </p>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#272C35]">
                    <button type="button"
                            @click="deleteModalOpen = false"
                            class="dashboard-control h-[32px] px-3 text-[11px] font-semibold text-[#d7c3ae] hover:text-white rounded-[6px] cursor-pointer">
                        Cancel
                    </button>
                    <form :action="saleToDelete?.destroy_url" method="POST" class="m-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="h-[32px] px-3.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-[11px] uppercase tracking-wider rounded-[6px] border-0 cursor-pointer">
                            Delete Sale
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- Send SMS Bill Modal (Teleported to Body, Dark Theme) -->
    <template x-teleport="body">
        <div id="salesSmsModalWrap"
             x-show="smsModalOpen"
             @keydown.escape.window="smsModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-sm rounded-[8px] bg-[#13151A] border border-[#272C35] p-5 shadow-2xl flex flex-col gap-3.5">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-[#272C35] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-amber-accent">sms</span>
                        <h4 class="m-0 text-[14px] font-bold text-white">Send Bill via SMS</h4>
                    </div>
                    <button type="button"
                            @click="smsModalOpen = false"
                            class="dashboard-control w-6 h-6 rounded flex items-center justify-center text-[#8A8A93] hover:text-white border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <!-- Step 1: Input -->
                <div id="ssms-step-input">
                    <p class="text-[11px] text-[#8A8A93] m-0 mb-2">
                        Enter Sri Lankan customer mobile number (07X XXX XXXX)
                    </p>
                    <div class="flex gap-2 mb-2">
                        <input type="text"
                               id="ssms-phone-number"
                               class="dashboard-control flex-1 h-[34px] px-2.5 text-[12px] text-white bg-[#111419] rounded-[6px]"
                               placeholder="07X XXX XXXX">
                        <button type="button"
                                id="ssms-send-btn"
                                onclick="salesSmsSend()"
                                class="h-[34px] px-3 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center gap-1 cursor-pointer border-0">
                            <span class="material-symbols-outlined text-[14px]">send</span>
                            <span>Send</span>
                        </button>
                    </div>
                    <div id="ssms-input-error" class="text-[11px] text-rose-400 mb-2" style="display:none;"></div>
                </div>

                <!-- Step 2: Live Status -->
                <div id="ssms-step-status" style="display:none;" class="flex flex-col gap-2">
                    <div id="ssms-status-content" class="text-[12px]"></div>
                    <div class="flex gap-2">
                        <button type="button" id="ssms-retry-btn" class="h-[28px] px-2.5 bg-rose-600/20 text-rose-400 border border-rose-500/30 rounded text-[10px] font-bold" onclick="salesSmsSend()" style="display:none;">
                            Retry
                        </button>
                        <button type="button" id="ssms-change-btn" class="dashboard-control h-[28px] px-2.5 text-[10px] font-semibold" onclick="salesSmsShowStep('input')" style="display:none;">
                            Change number
                        </button>
                    </div>
                </div>

                <div class="text-[10px] text-[#5A5A63] flex items-center gap-1 pt-2 border-t border-[#272C35]">
                    <span class="material-symbols-outlined text-[12px]">shield</span>
                    <span>Customer number is exclusively used for invoice delivery.</span>
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
function salesHistoryApp(initialSales, initialStatusTab) {
    return {
        sales: initialSales || [],
        activeStatusTab: initialStatusTab || 'all',
        searchQuery: '',
        sortKey: 'timestamp',
        sortAsc: false,
        drawerOpen: false,
        activeSale: null,
        activeMenuSale: null,
        menuX: 0,
        menuY: 0,
        deleteModalOpen: false,
        saleToDelete: null,
        smsModalOpen: false,

        initSales() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('invoice')) {
                this.searchQuery = params.get('invoice');
            }

            // Close actions dropdown on window scroll or resize
            window.addEventListener('scroll', () => {
                if (this.activeMenuSale) this.closeActionMenu();
            }, true);
            window.addEventListener('resize', () => {
                if (this.activeMenuSale) this.closeActionMenu();
            });
        },

        toggleActionMenu(sale, event) {
            if (this.activeMenuSale && this.activeMenuSale.id === sale.id) {
                this.closeActionMenu();
                return;
            }

            const btn = event.currentTarget || event.target.closest('button');
            if (!btn) return;

            // 1. Immediately compute synchronous coordinates with getBoundingClientRect
            const rect = btn.getBoundingClientRect();
            const menuWidth = 180;
            const menuHeight = 170;

            // Right-align: top = rect.bottom + 6, left = rect.right - menuWidth
            let left = rect.right - menuWidth;
            if (left < 8) left = 8;
            if (left + menuWidth > window.innerWidth - 8) {
                left = window.innerWidth - menuWidth - 8;
            }

            // Flip upward if rect.bottom + menuHeight exceeds window.innerHeight
            let top = rect.bottom + 6;
            if (rect.bottom + menuHeight + 8 > window.innerHeight && rect.top > menuHeight + 8) {
                top = rect.top - menuHeight - 6;
            }

            this.menuX = Math.round(left);
            this.menuY = Math.round(top);
            this.activeMenuSale = sale;

            // 2. Refine via Floating UI on $nextTick once menu DOM is mounted
            this.$nextTick(() => {
                const menuEl = document.getElementById('salesActionsFloatingMenu');
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
            this.activeMenuSale = null;
        },

        setStatusTab(tab) {
            this.activeStatusTab = tab;
            const input = document.getElementById('statusFilterInput');
            if (input) input.value = tab;
        },

        get filteredSales() {
            let list = this.sales;

            // Status tab filter
            if (this.activeStatusTab !== 'all') {
                if (this.activeStatusTab === 'paid') {
                    list = list.filter(s => s.status === 'Paid');
                } else if (this.activeStatusTab === 'refunded') {
                    list = list.filter(s => s.status === 'Refunded');
                } else if (this.activeStatusTab === 'partially_refunded') {
                    list = list.filter(s => s.status === 'Partially Refunded');
                } else if (this.activeStatusTab === 'void') {
                    list = list.filter(s => s.status === 'Void');
                }
            }

            // Quick search filter
            if (!this.searchQuery.trim()) {
                return list;
            }
            const q = this.searchQuery.toLowerCase().trim();
            return list.filter(s => {
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

        getPaymentDotClass(method) {
            const m = (method || '').toLowerCase();
            if (m === 'cash') return 'bg-emerald-400';
            if (m === 'card') return 'bg-sky-400';
            if (m === 'koko') return 'bg-blue-400';
            if (m === 'mintpay') return 'bg-amber-400';
            if (m === 'bank') return 'bg-cyan-400';
            if (m === 'payzy') return 'bg-indigo-400';
            if (m === 'multipay') return 'bg-purple-400';
            return 'bg-slate-400';
        },

        formatPaymentLabel(method) {
            const m = (method || '').toLowerCase();
            if (m === 'cash') return 'Cash';
            if (m === 'card') return 'Card';
            if (m === 'koko') return 'Koko';
            if (m === 'mintpay') return 'Mint Pay';
            if (m === 'bank') return 'Bank';
            if (m === 'payzy') return 'Payzy';
            if (m === 'multipay') return 'Multi Pay';
            return (method || 'Cash').toUpperCase();
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

        confirmDelete(sale) {
            this.saleToDelete = sale;
            this.deleteModalOpen = true;
        },

        triggerSmsModal(sale) {
            window.ssmsSaleId = sale.id;
            const phoneInput = document.getElementById('ssms-phone-number');
            if (phoneInput) phoneInput.value = sale.sms_phone || '';
            salesSmsShowStep('input');
            this.smsModalOpen = true;
        },

        exportToCsv() {
            const rows = this.filteredSales;
            if (!rows.length) {
                alert('No invoices to export.');
                return;
            }

            const headers = ['Invoice Number', 'Date', 'Time', 'Staff', 'Payment Method', 'Status', 'Items Count', 'Subtotal', 'Discount', 'Net Total'];
            const csvRows = [headers.join(',')];

            rows.forEach(r => {
                const values = [
                    `"${r.plain_bill_number}"`,
                    `"${r.date_formatted}"`,
                    `"${r.time_formatted}"`,
                    `"${r.staff_name.replace(/"/g, '""')}"`,
                    `"${r.payment_method}"`,
                    `"${r.status}"`,
                    r.items_count,
                    r.subtotal.toFixed(2),
                    r.discount_amount.toFixed(2),
                    r.net_total.toFixed(2),
                ];
                csvRows.push(values.join(','));
            });

            const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', `sales_history_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };
}
</script>
<script>
    // ---- Send SMS Bill Script ----
    window.ssmsSaleId = null;
    let ssmsNormalizedPhone = null;
    let ssmsPollTimer = null;
    let ssmsInFlight = false;

    function ssmsPhoneNormalize(raw) {
        const digits = (raw || '').replace(/\D+/g, '');
        let sub;
        if (digits.startsWith('0094')) sub = digits.slice(4);
        else if (digits.startsWith('94') && digits.length === 11) sub = digits.slice(2);
        else if (digits.startsWith('0') && digits.length === 10) sub = digits.slice(1);
        else sub = digits;
        if (sub.length !== 9) return null;
        if (!['70','71','72','74','75','76','77','78'].includes(sub.slice(0, 2))) return null;
        return '94' + sub;
    }

    function ssmsPhoneMask(n) { return n.slice(0, 4) + '•••' + n.slice(-4); }

    function salesSmsShowStep(step) {
        ['input', 'status'].forEach(s => {
            const el = document.getElementById('ssms-step-' + s);
            if (el) el.style.display = (s === step) ? 'block' : 'none';
        });
        if (step !== 'status' && ssmsPollTimer) { clearInterval(ssmsPollTimer); ssmsPollTimer = null; }
    }

    async function salesSmsSend() {
        if (!window.ssmsSaleId) return;
        if (ssmsInFlight) return;
        ssmsInFlight = true;

        const err = document.getElementById('ssms-input-error');
        const phoneEl = document.getElementById('ssms-phone-number');
        const normalized = ssmsPhoneNormalize(phoneEl ? phoneEl.value : '');
        if (!normalized) {
            if (err) {
                err.textContent = 'Not a valid Sri Lankan mobile number (07X XXX XXXX).';
                err.style.display = 'block';
            }
            salesSmsShowStep('input');
            ssmsInFlight = false;
            return;
        }
        if (err) err.style.display = 'none';
        ssmsNormalizedPhone = normalized;

        const sendBtn = document.getElementById('ssms-send-btn');
        const retryBtn = document.getElementById('ssms-retry-btn');
        if (sendBtn) sendBtn.disabled = true;
        if (retryBtn) retryBtn.disabled = true;

        salesSmsShowStep('status');
        salesSmsRenderStatus('pending', { to_masked: ssmsPhoneMask(ssmsNormalizedPhone) });

        try {
            const resp = await fetch(`/api/invoices/${window.ssmsSaleId}/send-sms`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ phone: ssmsNormalizedPhone, resend: true })
            });
            const data = await resp.json();

            if (resp.ok && data.success) {
                salesSmsRenderStatus(data.status, data);
                if (data.status === 'pending') salesSmsStartPolling(window.ssmsSaleId);
            } else {
                if (err) {
                    err.textContent = data.message || data.error || 'Failed to queue SMS.';
                    err.style.display = 'block';
                }
                salesSmsShowStep('input');
            }
        } catch (e) {
            salesSmsRenderStatus('failed', { error: 'Network error — check the connection and retry.' });
        }

        if (sendBtn) sendBtn.disabled = false;
        if (retryBtn) retryBtn.disabled = false;
        ssmsInFlight = false;
    }

    const SSMS_POLL_INTERVAL_MS = 3000;
    const SSMS_POLL_WINDOW_MS = 180000;

    function salesSmsStartPolling(saleId) {
        if (ssmsPollTimer) clearInterval(ssmsPollTimer);
        const deadline = Date.now() + SSMS_POLL_WINDOW_MS;
        ssmsPollTimer = setInterval(async () => {
            try {
                const resp = await fetch(`/api/invoices/${saleId}/sms-status`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await resp.json();
                if (data.status && data.status !== 'pending') {
                    clearInterval(ssmsPollTimer);
                    ssmsPollTimer = null;
                    salesSmsRenderStatus(data.status, data);
                    return;
                }
            } catch (e) {}

            if (Date.now() >= deadline) {
                clearInterval(ssmsPollTimer);
                ssmsPollTimer = null;
                salesSmsRenderStatus('queued_slow', {});
            }
        }, SSMS_POLL_INTERVAL_MS);
    }

    function salesSmsRenderStatus(status, data) {
        const el = document.getElementById('ssms-status-content');
        const retryBtn = document.getElementById('ssms-retry-btn');
        const changeBtn = document.getElementById('ssms-change-btn');
        if (retryBtn) retryBtn.style.display = 'none';
        if (changeBtn) changeBtn.style.display = 'none';
        if (!el) return;

        if (status === 'pending') {
            el.innerHTML = '<span style="color:#8A8A93;"><i class="fas fa-circle-notch fa-spin text-amber-accent"></i> Sending to <b>' + (data.to_masked || '') + '</b>…</span>' +
                '<div style="font-size:0.75rem;color:#5A5A63;margin-top:4px;">This can take up to a minute — you can keep working.</div>';
        } else if (status === 'sent') {
            el.innerHTML = '<span class="text-emerald-400 font-bold"><i class="fas fa-check-circle me-1"></i> Bill sent to <b>' + (data.to_masked || '') + '</b> ✓</span>';
        } else if (status === 'failed') {
            el.innerHTML = '<span class="text-rose-400 font-bold"><i class="fas fa-times-circle me-1"></i> Failed to send</span>' +
                (data.error ? '<div style="font-size:0.75rem;color:#f87171;margin-top:4px;"></div>' : '');
            if (data.error && el.lastElementChild) el.lastElementChild.textContent = data.error;
            if (retryBtn) retryBtn.style.display = 'inline-block';
            if (changeBtn) changeBtn.style.display = 'inline-block';
        } else {
            el.innerHTML = '<span style="color:#8A8A93;"><i class="fas fa-clock text-amber-accent"></i> Still queued — sending in background.</span>';
            if (changeBtn) changeBtn.style.display = 'inline-block';
        }
    }
</script>
@endpush
