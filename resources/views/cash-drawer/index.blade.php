{{-- ============================================
     Cash Drawer Management - Redesigned Dark Dashboard Experience
     Jewellery / Clothing Outlet POS
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Cash Drawer')

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
    $isOpen = $session && $session->status === 'open';
    $isLongOpen = $isOpen && $session->opened_at->diffInHours(now()) >= 16;
    
    $openingBalance = $session ? (float)$session->opening_balance : 0.0;
    $cashSales = $session ? (float)$session->cash_sales : 0.0;
    $cashSalesCount = $session ? (int)$session->cash_sales_count : 0;
    $cashRefunds = $session ? (float)$session->cash_refunds : 0.0;
    $cashIn = $session ? (float)$session->total_cash_in : 0.0;
    $cashOut = $session ? (float)$session->total_cash_out : 0.0;
    $expectedCash = $session ? (float)$session->expected_balance : 0.0;

    // Format transactions payload
    $txPayload = $transactions->map(function ($tx, $idx) use ($transactions) {
        $isPositive = in_array($tx['type'], ['cash_in', 'cash_sale'], true);
        return [
            'id' => $tx['id'] ?? ('tx-' . $idx),
            'index' => $transactions->count() - $idx,
            'type' => $tx['type'],
            'description' => $tx['description'] ?? 'Transaction',
            'amount' => (float)$tx['amount'],
            'amount_formatted' => ($isPositive ? '+Rs. ' : '−Rs. ') . number_format((float)$tx['amount'], 2),
            'is_positive' => $isPositive,
            'running_balance' => (float)($tx['running_balance'] ?? 0),
            'running_balance_formatted' => 'Rs. ' . number_format((float)($tx['running_balance'] ?? 0), 2),
            'staff_name' => $tx['user']['name'] ?? 'Staff',
            'time_formatted' => $tx['created_at']->format('h:i A'),
            'date_formatted' => $tx['created_at']->format('M d, Y'),
            'timestamp' => $tx['created_at']->timestamp,
            'invoice_url' => $tx['invoice_url'] ?? null,
        ];
    });

    // Format closed session history
    $historyPayload = $history->map(function ($sess) {
        return [
            'id' => $sess->id,
            'opened_at_formatted' => $sess->opened_at->format('d M Y · h:i A'),
            'closed_at_formatted' => $sess->closed_at ? $sess->closed_at->format('d M Y · h:i A') : '—',
            'duration' => $sess->duration,
            'opened_by_name' => $sess->openedBy->name ?? 'Staff',
            'closed_by_name' => $sess->closedByUser->name ?? 'Staff',
            'opening_balance' => (float)$sess->opening_balance,
            'cash_sales' => (float)$sess->cash_sales,
            'cash_sales_count' => (int)$sess->cash_sales_count,
            'total_cash_in' => (float)$sess->total_cash_in,
            'total_cash_out' => (float)$sess->total_cash_out,
            'cash_refunds' => (float)$sess->cash_refunds,
            'expected_balance' => (float)$sess->expected_balance,
            'closing_balance' => $sess->closing_balance !== null ? (float)$sess->closing_balance : null,
            'difference' => $sess->difference,
            'notes' => $sess->notes,
            'close_notes' => $sess->close_notes,
            'status' => $sess->status,
        ];
    });
@endphp

@section('content')
<div x-data="cashDrawerApp({
        isOpen: {{ $isOpen ? 'true' : 'false' }},
        isLongOpen: {{ $isLongOpen ? 'true' : 'false' }},
        sessionId: {{ $session->id ?? 'null' }},
        openingBalance: {{ $openingBalance }},
        cashSales: {{ $cashSales }},
        cashSalesCount: {{ $cashSalesCount }},
        cashRefunds: {{ $cashRefunds }},
        cashIn: {{ $cashIn }},
        cashOut: {{ $cashOut }},
        expectedCash: {{ $expectedCash }},
        initialTransactions: @js($txPayload),
        initialHistory: @js($historyPayload),
     })"
     class="w-full flex flex-col select-none gap-3.5 box-border">

    <!-- ========================================================
         1. PAGE HEADER
         ======================================================== -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 shrink-0">
        <!-- Title & Status Pill -->
        <div class="flex flex-col sm:flex-row sm:items-baseline gap-2 sm:gap-3">
            <div class="flex items-center gap-2.5">
                <h1 class="font-headline-lg text-[20px] lg:text-[22px] text-on-surface m-0 font-bold tracking-tight">
                    Cash Drawer
                </h1>
                
                @if($isOpen)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>OPEN
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-500/10 text-slate-400 border border-slate-500/25">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>CLOSED
                    </span>
                @endif
            </div>

            <p class="font-body-sm text-[12px] text-on-surface-variant m-0">
                @if($isOpen)
                    Session #{{ $session->id }} &middot; Main Branch
                @else
                    No active shift &middot; Main Branch
                @endif
            </p>
        </div>

        <!-- Right Side: Action Buttons -->
        <div class="flex items-center gap-2">
            @if($isOpen)
                <!-- Cash In Button (Muted outline + green icon) -->
                <button type="button"
                        @click="openCashInModal()"
                        class="dashboard-control h-[34px] px-3 flex items-center gap-1.5 text-[#d7c3ae] hover:text-white text-[11px] font-semibold uppercase tracking-wider rounded-[6px] transition-colors cursor-pointer border-0 active:scale-95">
                    <span class="material-symbols-outlined text-[16px] text-emerald-400">add_circle</span>
                    <span>Cash In</span>
                </button>

                <!-- Cash Out Button (Muted outline + red icon) -->
                <button type="button"
                        @click="openCashOutModal()"
                        class="dashboard-control h-[34px] px-3 flex items-center gap-1.5 text-[#d7c3ae] hover:text-white text-[11px] font-semibold uppercase tracking-wider rounded-[6px] transition-colors cursor-pointer border-0 active:scale-95">
                    <span class="material-symbols-outlined text-[16px] text-rose-400">remove_circle</span>
                    <span>Cash Out</span>
                </button>

                <!-- Close Drawer (Primary Amber Button) -->
                <button type="button"
                        @click="openCloseModal()"
                        class="h-[34px] px-3.5 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95 shadow-md">
                    <span class="material-symbols-outlined text-[16px]">lock</span>
                    <span>Close Drawer</span>
                </button>
            @else
                <!-- Open Drawer (Primary Amber Button) -->
                <button type="button"
                        @click="openOpenModal()"
                        class="h-[34px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95 shadow-md">
                    <span class="material-symbols-outlined text-[16px]">lock_open</span>
                    <span>Open Drawer</span>
                </button>
            @endif
        </div>
    </div>

    @if($isOpen)
        <!-- ========================================================
             2. SESSION BANNER (Normal or >16h Warning State)
             ======================================================== -->
        <div class="dashboard-card rounded-[8px] p-3.5 border border-[#272C35] flex flex-col md:flex-row md:items-center justify-between gap-3"
             :class="isLongOpen ? 'border-l-4 border-l-amber-500 bg-amber-500/5' : 'border-l-4 border-l-emerald-500 bg-[#171A20]'">
            
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0"
                     :class="isLongOpen ? 'bg-amber-500/15 text-amber-accent' : 'bg-emerald-500/15 text-emerald-400'">
                    <span class="material-symbols-outlined text-[20px]"
                          x-text="isLongOpen ? 'warning' : 'schedule'"></span>
                </div>

                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-[13px] text-white"
                              x-text="isLongOpen ? 'Shift Exceeded Warning' : 'Active Register Session'"></span>
                        <span class="text-[11px] text-[#8A8A93]" x-text="'Session #' + sessionId"></span>
                    </div>

                    <div class="text-[11px] text-[#8A8A93] mt-0.5">
                        Opened by <strong class="text-white">{{ $session->openedBy->name ?? 'Staff' }}</strong> &bull;
                        {{ $session->opened_at->format('d M Y, h:i A') }} &bull;
                        <span class="text-amber-accent font-semibold">{{ $session->duration }}</span>
                    </div>

                    @if($isLongOpen)
                        <div class="text-[11px] text-amber-accent/90 font-medium mt-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[13px]">info</span>
                            <span>This session has been open longer than a shift (16h+) — close it to reconcile and start a fresh shift.</span>
                        </div>
                    @endif

                    @if($session->notes)
                        <div class="text-[11px] text-[#d7c3ae] italic mt-1">
                            Note: {{ $session->notes }}
                        </div>
                    @endif
                </div>
            </div>

            @if($isLongOpen)
                <div class="shrink-0 flex items-center">
                    <button type="button"
                            @click="openCloseModal()"
                            class="h-[30px] px-3 bg-amber-500 hover:bg-amber-400 text-black font-bold text-[10px] uppercase tracking-wider rounded-[4px] flex items-center gap-1 cursor-pointer border-0">
                        <span class="material-symbols-outlined text-[14px]">lock</span>
                        <span>Close to Reconcile</span>
                    </button>
                </div>
            @endif
        </div>

        <!-- ========================================================
             3. RECONCILIATION HERO PANEL
             ======================================================== -->
        <div class="dashboard-card rounded-[8px] p-4 bg-[#171A20] border border-[#272C35] flex flex-col gap-3">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                
                <!-- 1. Expected Cash Hero -->
                <div class="flex-1">
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.08em] text-on-surface-variant mb-1">
                        <span class="material-symbols-outlined text-[14px] text-amber-accent">account_balance_wallet</span>
                        <span>Expected Cash in Drawer</span>
                    </div>
                    <div class="font-['Inter'] font-bold text-[24px] lg:text-[28px] text-amber-accent tracking-tight tabular-nums"
                         x-text="'Rs. ' + Number(expectedCash).toLocaleString('en-US', {minimumFractionDigits:2})"></div>
                </div>

                <!-- 2. Counted Cash Input & Denominations Button -->
                <div class="flex-1">
                    <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-[0.08em] text-on-surface-variant mb-1">
                        <span>Counted Cash</span>
                        <button type="button"
                                @click="openDenominationsModal()"
                                class="text-amber-accent hover:underline flex items-center gap-0.5 cursor-pointer bg-transparent border-0 text-[10px] uppercase font-bold">
                            <span class="material-symbols-outlined text-[12px]">calculate</span>
                            <span>Denominations</span>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] text-[#8A8A93] font-semibold">Rs.</span>
                            <input type="number"
                                   x-model="countedCash"
                                   @input="calculateVariance()"
                                   placeholder="0.00"
                                   step="0.01"
                                   min="0"
                                   class="dashboard-control w-full h-[36px] pl-8 pr-3 text-[14px] font-bold text-white bg-[#111419] rounded-[6px] border border-[#272C35] focus:border-[#F5A623] outline-none font-['Inter'] tabular-nums">
                        </div>
                        <button type="button"
                                @click="openDenominationsModal()"
                                class="dashboard-control h-[36px] px-3 text-[11px] font-semibold text-[#d7c3ae] hover:text-white rounded-[6px] flex items-center gap-1 cursor-pointer">
                            <span>Count Cash</span>
                        </button>
                    </div>
                </div>

                <!-- 3. Variance Status Box -->
                <div class="flex-1 lg:max-w-[220px] p-2.5 rounded-[6px] bg-[#111419] border border-[#272C35] flex flex-col justify-between">
                    <div class="flex items-center justify-between text-[10px] uppercase font-bold text-on-surface-variant">
                        <span>Discrepancy / Variance</span>
                        <template x-if="varianceStatus === 'BALANCED'">
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">BALANCED</span>
                        </template>
                        <template x-if="varianceStatus === 'SHORT'">
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">SHORT</span>
                        </template>
                        <template x-if="varianceStatus === 'OVER'">
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">OVER</span>
                        </template>
                    </div>

                    <div class="mt-1">
                        <template x-if="countedCash === '' || countedCash === null">
                            <span class="text-[12px] text-[#8A8A93] italic">Not counted yet</span>
                        </template>
                        <template x-if="countedCash !== '' && countedCash !== null">
                            <div class="font-['Inter'] font-bold text-[18px] tabular-nums"
                                 :class="varianceValue === 0 ? 'text-emerald-400' : (varianceValue < 0 ? 'text-rose-400' : 'text-amber-400')"
                                 x-text="(varianceValue > 0 ? '+Rs. ' : (varianceValue < 0 ? '−Rs. ' : 'Rs. ')) + Number(Math.abs(varianceValue)).toLocaleString('en-US', {minimumFractionDigits:2})"></div>
                        </template>
                    </div>
                </div>

            </div>

            <!-- Mathematical Breakdown Line -->
            <div class="pt-2 border-t border-[#272C35]/60 text-[11px] text-[#8A8A93] flex items-center gap-1.5 flex-wrap">
                <span class="font-semibold text-white">Reconciliation Math:</span>
                <span>Opening (Rs. {{ number_format($openingBalance, 2) }})</span>
                <span>+</span>
                <span class="text-emerald-400">Cash Sales (Rs. {{ number_format($cashSales, 2) }})</span>
                <span>+</span>
                <span class="text-emerald-400">Cash In (Rs. {{ number_format($cashIn, 2) }})</span>
                <span>−</span>
                <span class="text-rose-400">Cash Refunds (Rs. {{ number_format($cashRefunds, 2) }})</span>
                <span>−</span>
                <span class="text-rose-400">Cash Out (Rs. {{ number_format($cashOut, 2) }})</span>
                <span>=</span>
                <strong class="text-amber-accent">Expected (Rs. {{ number_format($expectedCash, 2) }})</strong>
            </div>
        </div>

        <!-- ========================================================
             4. KPI ROW (5 CARDS EVEN GRID)
             ======================================================== -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 shrink-0">
            <!-- 1. Opening Balance -->
            <x-pos.kpi-card
                title="Opening Balance"
                icon="account_balance_wallet"
                :value="'Rs. ' . number_format($openingBalance, 2)"
                subtext="Float at Shift Start"
            />

            <!-- 2. Cash Sales -->
            <x-pos.kpi-card
                title="Cash Sales"
                icon="shopping_cart"
                :value="'Rs. ' . number_format($cashSales, 2)"
                :subtext="$cashSalesCount . ($cashSalesCount === 1 ? ' sale' : ' sales')"
            />

            <!-- 3. Cash Refunds -->
            <x-pos.kpi-card
                title="Cash Refunds"
                icon="assignment_return"
                :value="'Rs. ' . number_format($cashRefunds, 2)"
                subtext="Returned Cash"
            />

            <!-- 4. Cash In -->
            <x-pos.kpi-card
                title="Cash In"
                icon="arrow_downward"
                :value="'Rs. ' . number_format($cashIn, 2)"
                subtext="Manual Deposits"
            />

            <!-- 5. Cash Out -->
            <x-pos.kpi-card
                title="Cash Out"
                icon="arrow_upward"
                :value="'Rs. ' . number_format($cashOut, 2)"
                subtext="Manual Expenses"
            />
        </div>

        <!-- ========================================================
             5. SESSION TRANSACTIONS TABLE
             ======================================================== -->
        <div class="dashboard-card rounded-[8px] overflow-hidden bg-[#171A20] border border-[#272C35] flex flex-col">
            <!-- Table Header Bar & Filter Chips -->
            <div class="px-3.5 lg:px-4 py-2.5 border-b border-[#272C35] flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-[#111419]/60">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-amber-accent">list_alt</span>
                    <span class="font-semibold text-white text-[13px] tracking-tight">Session Transactions</span>
                    <span class="px-1.5 py-0.2 rounded bg-surface-variant/40 border border-[#272C35] text-[10px] font-bold text-amber-accent tabular-nums">
                        <span x-text="filteredTransactions.length"></span> entries
                    </span>
                </div>

                <!-- Transaction Type Filter Chips -->
                <div class="flex items-center gap-1 overflow-x-auto custom-scrollbar select-none">
                    <button type="button"
                            @click="txFilter = 'all'"
                            :class="txFilter === 'all' ? 'bg-[#1E222A] text-amber-accent border-amber-accent/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white'"
                            class="px-2 py-0.5 rounded-[4px] border text-[10px] cursor-pointer">
                        All
                    </button>
                    <button type="button"
                            @click="txFilter = 'cash_sale'"
                            :class="txFilter === 'cash_sale' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white'"
                            class="px-2 py-0.5 rounded-[4px] border text-[10px] cursor-pointer">
                        Sales
                    </button>
                    <button type="button"
                            @click="txFilter = 'cash_in'"
                            :class="txFilter === 'cash_in' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white'"
                            class="px-2 py-0.5 rounded-[4px] border text-[10px] cursor-pointer">
                        Cash In
                    </button>
                    <button type="button"
                            @click="txFilter = 'cash_out'"
                            :class="txFilter === 'cash_out' ? 'bg-rose-500/10 text-rose-400 border-rose-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white'"
                            class="px-2 py-0.5 rounded-[4px] border text-[10px] cursor-pointer">
                        Cash Out
                    </button>
                    <button type="button"
                            @click="txFilter = 'cash_refund'"
                            :class="txFilter === 'cash_refund' ? 'bg-rose-500/10 text-rose-400 border-rose-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white'"
                            class="px-2 py-0.5 rounded-[4px] border text-[10px] cursor-pointer">
                        Refunds
                    </button>
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="overflow-x-auto custom-scrollbar hidden md:block" x-show="filteredTransactions.length > 0">
                <table class="w-full text-left border-collapse text-body-sm">
                    <thead>
                        <tr class="border-b border-[#272C35] text-on-surface-variant font-label-caps text-[10px] uppercase bg-[#111419]/30">
                            <th class="py-2.5 px-3.5 font-bold tracking-wider w-[50px]">#</th>
                            <th class="py-2.5 px-3 font-bold tracking-wider">Time</th>
                            <th class="py-2.5 px-3 font-bold tracking-wider">Type</th>
                            <th class="py-2.5 px-3 font-bold tracking-wider">Description</th>
                            <th class="py-2.5 px-3 font-bold tracking-wider">By</th>
                            <th class="py-2.5 px-3.5 font-bold tracking-wider text-right">Amount</th>
                            <th class="py-2.5 px-3.5 font-bold tracking-wider text-right">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="tx in filteredTransactions" :key="tx.id">
                            <tr class="border-b border-[#272C35]/50 last:border-0 hover:bg-[#1E222A] transition-colors h-[48px]">
                                <!-- # -->
                                <td class="py-2 px-3.5 text-[#8A8A93] text-[11px] tabular-nums" x-text="tx.index"></td>

                                <!-- Time -->
                                <td class="py-2 px-3 text-white text-[12px] tabular-nums font-medium" x-text="tx.time_formatted"></td>

                                <!-- Type -->
                                <td class="py-2 px-3 whitespace-nowrap">
                                    <template x-if="tx.type === 'cash_sale'">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            <span class="material-symbols-outlined text-[13px]">shopping_cart</span> Cash Sale
                                        </span>
                                    </template>
                                    <template x-if="tx.type === 'cash_in'">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            <span class="material-symbols-outlined text-[13px]">arrow_downward</span> Cash In
                                        </span>
                                    </template>
                                    <template x-if="tx.type === 'cash_out'">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                            <span class="material-symbols-outlined text-[13px]">arrow_upward</span> Cash Out
                                        </span>
                                    </template>
                                    <template x-if="tx.type === 'cash_refund'">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                            <span class="material-symbols-outlined text-[13px]">undo</span> Cash Refund
                                        </span>
                                    </template>
                                </td>

                                <!-- Description (Link if invoice) -->
                                <td class="py-2 px-3 text-[12px] text-white">
                                    <template x-if="tx.invoice_url">
                                        <a :href="tx.invoice_url"
                                           target="_blank"
                                           class="text-[#d7c3ae] hover:text-amber-accent hover:underline font-medium flex items-center gap-1 no-underline">
                                            <span x-text="tx.description"></span>
                                            <span class="material-symbols-outlined text-[12px] opacity-60">open_in_new</span>
                                        </a>
                                    </template>
                                    <template x-if="!tx.invoice_url">
                                        <span x-text="tx.description"></span>
                                    </template>
                                </td>

                                <!-- By -->
                                <td class="py-2 px-3 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-5 h-5 rounded-full bg-surface-variant flex items-center justify-center text-[9px] font-bold text-on-surface uppercase flex-shrink-0"
                                              x-text="tx.staff_name.charAt(0)"></span>
                                        <span class="text-[12px] text-[#d7c3ae] font-medium truncate max-w-[100px]"
                                              x-text="tx.staff_name"></span>
                                    </div>
                                </td>

                                <!-- Amount (Signed and Colored) -->
                                <td class="py-2 px-3.5 text-right whitespace-nowrap">
                                    <span class="font-['Inter'] font-bold text-[13px] tabular-nums tracking-tight"
                                          :class="tx.is_positive ? 'text-emerald-400' : 'text-rose-400'"
                                          x-text="tx.amount_formatted"></span>
                                </td>

                                <!-- Running Balance -->
                                <td class="py-2 px-3.5 text-right whitespace-nowrap">
                                    <span class="font-['Inter'] font-semibold text-[12px] text-[#d7c3ae] tabular-nums"
                                          x-text="tx.running_balance_formatted"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Stacked Card View (< md) -->
            <div class="md:hidden flex flex-col divide-y divide-[#272C35]/50" x-show="filteredTransactions.length > 0">
                <template x-for="tx in filteredTransactions" :key="'mob-' + tx.id">
                    <div class="p-3 flex flex-col gap-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-white" x-text="tx.description"></span>
                            <span class="font-['Inter'] font-bold text-[13px] tabular-nums"
                                  :class="tx.is_positive ? 'text-emerald-400' : 'text-rose-400'"
                                  x-text="tx.amount_formatted"></span>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-[#8A8A93]">
                            <span x-text="tx.time_formatted + ' · By ' + tx.staff_name"></span>
                            <span x-text="'Bal: ' + tx.running_balance_formatted"></span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty Transactions State -->
            <div x-show="filteredTransactions.length === 0" class="py-12 px-4 text-center">
                <span class="material-symbols-outlined text-[36px] text-[#5A5A63] mb-2 block">inbox</span>
                <div class="text-[14px] font-semibold text-white mb-1">No transactions yet</div>
                <p class="text-[12px] text-[#8A8A93] max-w-sm mx-auto">
                    Cash sales and manual adjustments will appear here as they occur.
                </p>
            </div>
        </div>

    @else
        <!-- ========================================================
             CLOSED REGISTER STATE: HERO ACTION TO OPEN DRAWER
             ======================================================== -->
        <div class="dashboard-card rounded-[8px] p-6 lg:p-8 bg-[#171A20] border border-[#272C35] text-center max-w-lg mx-auto flex flex-col items-center gap-3 my-4">
            <div class="w-14 h-14 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-accent flex items-center justify-center mb-1">
                <span class="material-symbols-outlined text-[28px]">lock_open</span>
            </div>
            <h2 class="text-[18px] font-bold text-white m-0">Open Cash Drawer to Start Shift</h2>
            <p class="text-[12px] text-[#8A8A93] m-0 max-w-sm leading-relaxed">
                The cash drawer is currently closed. Enter your starting cash float to open a new register session.
            </p>
            <button type="button"
                    @click="openOpenModal()"
                    class="mt-2 h-[38px] px-5 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[12px] uppercase tracking-wider rounded-[6px] flex items-center gap-1.5 cursor-pointer border-0 shadow-lg">
                <span class="material-symbols-outlined text-[18px]">lock_open</span>
                <span>Open Drawer Session</span>
            </button>
        </div>
    @endif

    <!-- ========================================================
         6. PREVIOUS SESSIONS (Collapsible Accordion)
         ======================================================== -->
    <div class="dashboard-card rounded-[8px] overflow-hidden bg-[#171A20] border border-[#272C35] flex flex-col mt-2"
         x-data="{ sessionsExpanded: true }">
        
        <div @click="sessionsExpanded = !sessionsExpanded"
             class="px-3.5 lg:px-4 py-2.5 border-b border-[#272C35] flex items-center justify-between bg-[#111419]/60 cursor-pointer select-none">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px] text-amber-accent">history</span>
                <span class="font-semibold text-white text-[13px] tracking-tight">Previous Sessions</span>
                <span class="px-1.5 py-0.2 rounded bg-surface-variant/40 border border-[#272C35] text-[10px] font-bold text-[#8A8A93] tabular-nums">
                    {{ $history->total() }}
                </span>
            </div>

            <button type="button" class="text-[#8A8A93] hover:text-white bg-transparent border-0 cursor-pointer flex items-center">
                <span class="material-symbols-outlined text-[18px] transition-transform duration-200"
                      :class="sessionsExpanded ? 'rotate-180' : ''">expand_more</span>
            </button>
        </div>

        <div x-show="sessionsExpanded" x-transition class="flex flex-col">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse text-body-sm">
                    <thead>
                        <tr class="border-b border-[#272C35] text-on-surface-variant font-label-caps text-[10px] uppercase bg-[#111419]/30">
                            <th class="py-2.5 px-3.5 font-bold tracking-wider">Session</th>
                            <th class="py-2.5 px-3 font-bold tracking-wider">Date Range</th>
                            <th class="py-2.5 px-3 font-bold tracking-wider">Duration</th>
                            <th class="py-2.5 px-3 font-bold tracking-wider">Opened By</th>
                            <th class="py-2.5 px-3 font-bold tracking-wider">Closed By</th>
                            <th class="py-2.5 px-3.5 font-bold tracking-wider text-right">Opening</th>
                            <th class="py-2.5 px-3.5 font-bold tracking-wider text-right">Cash Sales</th>
                            <th class="py-2.5 px-3.5 font-bold tracking-wider text-right">Closing</th>
                            <th class="py-2.5 px-3.5 font-bold tracking-wider text-center">Variance</th>
                            <th class="py-2.5 px-3 font-bold tracking-wider text-right w-[60px]">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $sess)
                        @php
                            $diff = $sess->difference;
                        @endphp
                        <tr @click="viewSessionDetail({{ $sess->id }})"
                            class="border-b border-[#272C35]/50 last:border-0 hover:bg-[#1E222A] transition-colors cursor-pointer h-[48px]">
                            
                            <!-- Session # -->
                            <td class="py-2 px-3.5 whitespace-nowrap font-mono text-amber-accent font-bold text-[12px]">
                                #{{ $sess->id }}
                            </td>

                            <!-- Date Range -->
                            <td class="py-2 px-3 whitespace-nowrap text-[11px] text-white">
                                <div>{{ $sess->opened_at->format('d M Y, h:i A') }}</div>
                                <div class="text-[10px] text-[#8A8A93]">→ {{ $sess->closed_at ? $sess->closed_at->format('d M Y, h:i A') : 'Still Open' }}</div>
                            </td>

                            <!-- Duration -->
                            <td class="py-2 px-3 text-[11px] text-[#8A8A93] whitespace-nowrap">
                                {{ $sess->duration }}
                            </td>

                            <!-- Opened By -->
                            <td class="py-2 px-3 text-[12px] text-[#d7c3ae] whitespace-nowrap">
                                {{ $sess->openedBy->name ?? 'Staff' }}
                            </td>

                            <!-- Closed By -->
                            <td class="py-2 px-3 text-[12px] text-[#d7c3ae] whitespace-nowrap">
                                {{ $sess->closedByUser->name ?? '—' }}
                            </td>

                            <!-- Opening Float -->
                            <td class="py-2 px-3.5 text-right whitespace-nowrap text-[12px] font-semibold text-white tabular-nums">
                                Rs. {{ number_format($sess->opening_balance, 2) }}
                            </td>

                            <!-- Cash Sales -->
                            <td class="py-2 px-3.5 text-right whitespace-nowrap text-[12px] font-semibold text-emerald-400 tabular-nums">
                                +Rs. {{ number_format($sess->cash_sales, 2) }}
                            </td>

                            <!-- Closing / Counted -->
                            <td class="py-2 px-3.5 text-right whitespace-nowrap text-[12px] font-semibold text-white tabular-nums">
                                Rs. {{ number_format($sess->closing_balance ?? 0, 2) }}
                            </td>

                            <!-- Variance -->
                            <td class="py-2 px-3.5 text-center whitespace-nowrap">
                                @if($diff !== null)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tabular-nums {{ $diff == 0 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($diff > 0 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20') }}">
                                        {{ $diff >= 0 ? '+' : '' }}Rs. {{ number_format($diff, 2) }}
                                    </span>
                                @else
                                    <span class="text-[#8A8A93] text-[11px]">—</span>
                                @endif
                            </td>

                            <!-- Detail Eye Button -->
                            <td class="py-2 px-3 text-right whitespace-nowrap" @click.stop>
                                <button type="button"
                                        @click="viewSessionDetail({{ $sess->id }})"
                                        class="w-7 h-7 rounded-[4px] bg-[#111419] hover:bg-amber-accent/15 text-[#8A8A93] hover:text-amber-accent border border-[#272C35] flex items-center justify-center transition-colors cursor-pointer"
                                        title="View session details">
                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-[#8A8A93] text-[12px]">
                                No closed sessions found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($history->hasPages())
                <div class="p-3 border-t border-[#272C35] flex items-center justify-between text-[11px] text-[#8A8A93] bg-[#111419]/40">
                    <div>Showing {{ $history->firstItem() ?? 0 }} to {{ $history->lastItem() ?? 0 }} of {{ $history->total() }} sessions</div>
                    <div>{{ $history->links() }}</div>
                </div>
            @endif
        </div>
    </div>

    <!-- ========================================================
         7. SESSION DETAIL DRAWER (Teleported to Body)
         ======================================================== -->
    <x-pos.session-drawer />

    <!-- ========================================================
         8. MODALS (Alpine.js Teleported to Body)
         ======================================================== -->

    <!-- Modal 1: Cash In Modal -->
    <template x-teleport="body">
        <div x-show="cashInModalOpen"
             @keydown.escape.window="cashInModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-sm rounded-[8px] bg-[#13151A] border border-[#272C35] p-5 shadow-2xl flex flex-col gap-3.5">
                <div class="flex items-center justify-between border-b border-[#272C35] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-emerald-400">add_circle</span>
                        <h4 class="m-0 text-[14px] font-bold text-white">Record Cash In</h4>
                    </div>
                    <button type="button" @click="cashInModalOpen = false" class="dashboard-control w-6 h-6 rounded flex items-center justify-center text-[#8A8A93] hover:text-white border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <div class="flex flex-col gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Amount (Rs.) <span class="text-rose-400">*</span></label>
                        <input type="number" x-model="cashInAmount" placeholder="0.00" min="0.01" step="0.01"
                               class="dashboard-control w-full h-[36px] px-3 text-[13px] font-bold text-white bg-[#111419] rounded-[6px] font-['Inter'] tabular-nums">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Reason <span class="text-rose-400">*</span></label>
                        <select x-model="cashInReason" class="dashboard-control w-full h-[36px] px-2.5 text-[12px] text-white bg-[#111419] rounded-[6px]">
                            <option value="Float top-up">Float top-up</option>
                            <option value="Owner deposit">Owner deposit</option>
                            <option value="Petty cash refund">Petty cash refund</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Description / Note <span class="text-rose-400">*</span></label>
                        <input type="text" x-model="cashInNote" placeholder="e.g. Midday change top-up float…"
                               class="dashboard-control w-full h-[36px] px-3 text-[12px] text-white bg-[#111419] rounded-[6px]">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#272C35]">
                    <button type="button" @click="cashInModalOpen = false" class="dashboard-control h-[32px] px-3 text-[11px] font-semibold text-[#d7c3ae] hover:text-white rounded-[6px] cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" @click="submitCashIn()" class="h-[32px] px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] uppercase tracking-wider rounded-[6px] border-0 cursor-pointer">
                        Record Cash In
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal 2: Cash Out Modal -->
    <template x-teleport="body">
        <div x-show="cashOutModalOpen"
             @keydown.escape.window="cashOutModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-sm rounded-[8px] bg-[#13151A] border border-[#272C35] p-5 shadow-2xl flex flex-col gap-3.5">
                <div class="flex items-center justify-between border-b border-[#272C35] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-rose-400">remove_circle</span>
                        <h4 class="m-0 text-[14px] font-bold text-white">Record Cash Out</h4>
                    </div>
                    <button type="button" @click="cashOutModalOpen = false" class="dashboard-control w-6 h-6 rounded flex items-center justify-center text-[#8A8A93] hover:text-white border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <div class="flex flex-col gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Amount (Rs.) <span class="text-rose-400">*</span></label>
                        <input type="number" x-model="cashOutAmount" placeholder="0.00" min="0.01" step="0.01"
                               class="dashboard-control w-full h-[36px] px-3 text-[13px] font-bold text-white bg-[#111419] rounded-[6px] font-['Inter'] tabular-nums">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Reason <span class="text-rose-400">*</span></label>
                        <select x-model="cashOutReason" class="dashboard-control w-full h-[36px] px-2.5 text-[12px] text-white bg-[#111419] rounded-[6px]">
                            <option value="Store supplies">Store supplies</option>
                            <option value="Vendor payment">Vendor payment</option>
                            <option value="Bank drop">Bank drop</option>
                            <option value="Owner drawing">Owner drawing</option>
                            <option value="Staff expense">Staff expense</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Description / Note <span class="text-rose-400">*</span></label>
                        <input type="text" x-model="cashOutNote" placeholder="e.g. Store cleaning supplies purchase…"
                               class="dashboard-control w-full h-[36px] px-3 text-[12px] text-white bg-[#111419] rounded-[6px]">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#272C35]">
                    <button type="button" @click="cashOutModalOpen = false" class="dashboard-control h-[32px] px-3 text-[11px] font-semibold text-[#d7c3ae] hover:text-white rounded-[6px] cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" @click="submitCashOut()" class="h-[32px] px-4 bg-rose-600 hover:bg-rose-500 text-white font-bold text-[11px] uppercase tracking-wider rounded-[6px] border-0 cursor-pointer">
                        Record Cash Out
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal 3: Denomination Counter Modal -->
    <template x-teleport="body">
        <div x-show="denomModalOpen"
             @keydown.escape.window="denomModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-md rounded-[8px] bg-[#13151A] border border-[#272C35] p-5 shadow-2xl flex flex-col gap-3.5">
                <div class="flex items-center justify-between border-b border-[#272C35] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-amber-accent">calculate</span>
                        <h4 class="m-0 text-[15px] font-bold text-white">Cash Denominations Counter</h4>
                    </div>
                    <button type="button" @click="denomModalOpen = false" class="dashboard-control w-6 h-6 rounded flex items-center justify-center text-[#8A8A93] hover:text-white border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <!-- Denomination Grid -->
                <div class="grid grid-cols-2 gap-2 max-h-[300px] overflow-y-auto custom-scrollbar pr-1">
                    <template x-for="d in denominations" :key="d.val">
                        <div class="p-2 rounded bg-[#171A20] border border-[#272C35] flex items-center justify-between gap-2">
                            <span class="font-mono text-[12px] font-bold text-white" x-text="'Rs. ' + d.val"></span>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] text-[#8A8A93]">×</span>
                                <input type="number" x-model.number="d.count" min="0" placeholder="0"
                                       class="dashboard-control w-16 h-[28px] px-1.5 text-center text-[12px] font-bold text-amber-accent rounded tabular-nums">
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Total Counted Line -->
                <div class="p-3 rounded bg-[#111419] border border-[#272C35] flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase text-[#8A8A93]">Calculated Total</span>
                    <span class="font-['Inter'] font-bold text-[18px] text-amber-accent tabular-nums"
                          x-text="'Rs. ' + Number(calculatedDenomTotal).toLocaleString('en-US', {minimumFractionDigits:2})"></span>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#272C35]">
                    <button type="button" @click="resetDenoms()" class="dashboard-control h-[32px] px-3 text-[11px] text-[#8A8A93] hover:text-white rounded-[6px]">
                        Reset
                    </button>
                    <button type="button" @click="applyDenominations()" class="h-[32px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] border-0 cursor-pointer">
                        Apply Count
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal 4: Close Drawer Modal -->
    <template x-teleport="body">
        <div x-show="closeDrawerModalOpen"
             @keydown.escape.window="closeDrawerModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-md rounded-[8px] bg-[#13151A] border border-[#272C35] p-5 shadow-2xl flex flex-col gap-3.5">
                <div class="flex items-center justify-between border-b border-[#272C35] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-amber-accent">lock</span>
                        <h4 class="m-0 text-[15px] font-bold text-white">Reconcile & Close Drawer</h4>
                    </div>
                    <button type="button" @click="closeDrawerModalOpen = false" class="dashboard-control w-6 h-6 rounded flex items-center justify-center text-[#8A8A93] hover:text-white border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <!-- Summary Breakdown -->
                <div class="p-3 rounded bg-[#171A20] border border-[#272C35] flex flex-col gap-2 text-[12px]">
                    <div class="grid grid-cols-2 gap-2 text-[11px] pb-2 border-b border-[#272C35]/50">
                        <div class="flex justify-between text-[#8A8A93]"><span>Opening:</span> <span class="text-white tabular-nums" x-text="'Rs. ' + Number(openingBalance).toLocaleString('en-US', {minimumFractionDigits:2})"></span></div>
                        <div class="flex justify-between text-[#8A8A93]"><span>Sales:</span> <span class="text-emerald-400 tabular-nums" x-text="'+Rs. ' + Number(cashSales).toLocaleString('en-US', {minimumFractionDigits:2})"></span></div>
                        <div class="flex justify-between text-[#8A8A93]"><span>Cash In:</span> <span class="text-emerald-400 tabular-nums" x-text="'+Rs. ' + Number(cashIn).toLocaleString('en-US', {minimumFractionDigits:2})"></span></div>
                        <div class="flex justify-between text-[#8A8A93]"><span>Cash Out:</span> <span class="text-rose-400 tabular-nums" x-text="'−Rs. ' + Number(cashOut).toLocaleString('en-US', {minimumFractionDigits:2})"></span></div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <span class="text-[#8A8A93] uppercase font-bold text-[10px]">Expected Balance</span>
                        <span class="font-['Inter'] font-bold text-[16px] text-amber-accent tabular-nums"
                              x-text="'Rs. ' + Number(expectedCash).toLocaleString('en-US', {minimumFractionDigits:2})"></span>
                    </div>
                </div>

                <!-- Actual Count Input -->
                <div>
                    <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Actual Counted Cash (Rs.) <span class="text-rose-400">*</span></label>
                    <input type="number" x-model="closingCountInput" @input="updateCloseDiff()" placeholder="Enter counted physical cash…" step="0.01" min="0"
                           class="dashboard-control w-full h-[36px] px-3 text-[14px] font-bold text-white bg-[#111419] rounded-[6px] font-['Inter'] tabular-nums">
                </div>

                <!-- Difference Warning / Pill -->
                <template x-if="closingDiffText">
                    <div class="p-2.5 rounded text-[11px] font-bold text-center flex items-center justify-center gap-1.5"
                         :class="closingDiffValue === 0 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : (closingDiffValue < 0 ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20')"
                         x-text="closingDiffText"></div>
                </template>

                <div>
                    <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Closing Remarks / Discrepancy Note <span x-show="closingDiffValue !== 0" class="text-rose-400">*</span></label>
                    <input type="text" x-model="closingNotes" placeholder="Reason for variance or shift closing note…"
                           class="dashboard-control w-full h-[36px] px-3 text-[12px] text-white bg-[#111419] rounded-[6px]">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#272C35]">
                    <button type="button" @click="closeDrawerModalOpen = false" class="dashboard-control h-[32px] px-3 text-[11px] font-semibold text-[#d7c3ae] hover:text-white rounded-[6px]">
                        Cancel
                    </button>
                    <button type="button" @click="submitCloseDrawer()" class="h-[32px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] border-0 cursor-pointer">
                        Confirm & Close Session
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal 5: Open Drawer Modal -->
    <template x-teleport="body">
        <div x-show="openDrawerModalOpen"
             @keydown.escape.window="openDrawerModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-sm rounded-[8px] bg-[#13151A] border border-[#272C35] p-5 shadow-2xl flex flex-col gap-3.5">
                <div class="flex items-center justify-between border-b border-[#272C35] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-amber-accent">lock_open</span>
                        <h4 class="m-0 text-[15px] font-bold text-white">Open Cash Drawer</h4>
                    </div>
                    <button type="button" @click="openDrawerModalOpen = false" class="dashboard-control w-6 h-6 rounded flex items-center justify-center text-[#8A8A93] hover:text-white border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <div class="flex flex-col gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Starting Float Balance (Rs.) <span class="text-rose-400">*</span></label>
                        <input type="number" x-model="openBalanceInput" placeholder="0.00" min="0" step="0.01"
                               class="dashboard-control w-full h-[36px] px-3 text-[14px] font-bold text-white bg-[#111419] rounded-[6px] font-['Inter'] tabular-nums">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Shift Notes <span class="text-[#8A8A93] font-normal">(optional)</span></label>
                        <input type="text" x-model="openNotesInput" placeholder="e.g. Morning shift with standard float…"
                               class="dashboard-control w-full h-[36px] px-3 text-[12px] text-white bg-[#111419] rounded-[6px]">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#272C35]">
                    <button type="button" @click="openDrawerModalOpen = false" class="dashboard-control h-[32px] px-3 text-[11px] font-semibold text-[#d7c3ae] hover:text-white rounded-[6px]">
                        Cancel
                    </button>
                    <button type="button" @click="submitOpenDrawer()" class="h-[32px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] border-0 cursor-pointer">
                        Open Drawer Now
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
function cashDrawerApp(config) {
    return {
        isOpen: config.isOpen,
        isLongOpen: config.isLongOpen,
        sessionId: config.sessionId,
        openingBalance: config.openingBalance,
        cashSales: config.cashSales,
        cashSalesCount: config.cashSalesCount,
        cashRefunds: config.cashRefunds,
        cashIn: config.cashIn,
        cashOut: config.cashOut,
        expectedCash: config.expectedCash,
        transactions: config.initialTransactions || [],
        history: config.initialHistory || [],

        // UI States
        txFilter: 'all',
        countedCash: '',
        varianceValue: 0,
        varianceStatus: 'NONE',

        // Modals
        cashInModalOpen: false,
        cashInAmount: '',
        cashInReason: 'Float top-up',
        cashInNote: '',

        cashOutModalOpen: false,
        cashOutAmount: '',
        cashOutReason: 'Store supplies',
        cashOutNote: '',

        denomModalOpen: false,
        denominations: [
            { val: 5000, count: 0 },
            { val: 1000, count: 0 },
            { val: 500,  count: 0 },
            { val: 100,  count: 0 },
            { val: 50,   count: 0 },
            { val: 20,   count: 0 },
            { val: 10,   count: 0 },
            { val: 5,    count: 0 },
            { val: 2,    count: 0 },
            { val: 1,    count: 0 },
        ],

        closeDrawerModalOpen: false,
        closingCountInput: '',
        closingNotes: '',
        closingDiffValue: 0,
        closingDiffText: '',

        openDrawerModalOpen: false,
        openBalanceInput: '',
        openNotesInput: '',

        // Drawer
        drawerOpen: false,
        drawerLoading: false,
        activeSession: null,

        get filteredTransactions() {
            if (this.txFilter === 'all') return this.transactions;
            return this.transactions.filter(t => t.type === this.txFilter);
        },

        get calculatedDenomTotal() {
            return this.denominations.reduce((acc, d) => acc + (d.val * (Number(d.count) || 0)), 0);
        },

        calculateVariance() {
            if (this.countedCash === '' || this.countedCash === null) {
                this.varianceStatus = 'NONE';
                this.varianceValue = 0;
                return;
            }
            const actual = Number(this.countedCash) || 0;
            const diff = actual - this.expectedCash;
            this.varianceValue = diff;
            if (Math.abs(diff) < 0.01) {
                this.varianceStatus = 'BALANCED';
            } else if (diff < 0) {
                this.varianceStatus = 'SHORT';
            } else {
                this.varianceStatus = 'OVER';
            }
        },

        openDenominationsModal() {
            this.denomModalOpen = true;
        },

        resetDenoms() {
            this.denominations.forEach(d => d.count = 0);
        },

        applyDenominations() {
            this.countedCash = this.calculatedDenomTotal.toFixed(2);
            this.calculateVariance();
            this.denomModalOpen = false;
        },

        openCashInModal() {
            this.cashInAmount = '';
            this.cashInNote = '';
            this.cashInModalOpen = true;
        },

        async submitCashIn() {
            const amt = Number(this.cashInAmount);
            if (!amt || amt <= 0) {
                alert('Please enter a valid cash in amount.');
                return;
            }
            if (!this.cashInNote.trim()) {
                alert('Please provide a description or reason note.');
                return;
            }

            const desc = `${this.cashInReason}: ${this.cashInNote.trim()}`;
            try {
                const resp = await fetch('/cash-drawer/cash-in', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ amount: amt, description: desc })
                });
                const data = await resp.json();
                if (resp.ok && data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to record cash in.');
                }
            } catch (e) {
                alert('Network error while recording cash in.');
            }
        },

        openCashOutModal() {
            this.cashOutAmount = '';
            this.cashOutNote = '';
            this.cashOutModalOpen = true;
        },

        async submitCashOut() {
            const amt = Number(this.cashOutAmount);
            if (!amt || amt <= 0) {
                alert('Please enter a valid cash out amount.');
                return;
            }
            if (!this.cashOutNote.trim()) {
                alert('Please provide a description or reason note.');
                return;
            }

            const desc = `${this.cashOutReason}: ${this.cashOutNote.trim()}`;
            try {
                const resp = await fetch('/cash-drawer/cash-out', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ amount: amt, description: desc })
                });
                const data = await resp.json();
                if (resp.ok && data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to record cash out.');
                }
            } catch (e) {
                alert('Network error while recording cash out.');
            }
        },

        openCloseModal() {
            this.closingCountInput = this.countedCash || '';
            this.closingNotes = '';
            this.updateCloseDiff();
            this.closeDrawerModalOpen = true;
        },

        updateCloseDiff() {
            if (this.closingCountInput === '' || this.closingCountInput === null) {
                this.closingDiffText = '';
                this.closingDiffValue = 0;
                return;
            }
            const actual = Number(this.closingCountInput) || 0;
            const diff = actual - this.expectedCash;
            this.closingDiffValue = diff;

            if (Math.abs(diff) < 0.01) {
                this.closingDiffText = '✓ Balanced Perfectly!';
            } else if (diff > 0) {
                this.closingDiffText = `▲ Surplus / Over: +Rs. ${diff.toLocaleString('en-US', {minimumFractionDigits:2})}`;
            } else {
                this.closingDiffText = `▼ Shortage: −Rs. ${Math.abs(diff).toLocaleString('en-US', {minimumFractionDigits:2})}`;
            }
        },

        async submitCloseDrawer() {
            const actual = Number(this.closingCountInput);
            if (isNaN(actual) || this.closingCountInput === '') {
                alert('Please enter the actual counted cash.');
                return;
            }
            if (this.closingDiffValue !== 0 && !this.closingNotes.trim()) {
                alert('A discrepancy exists. Please provide a closing note explaining the variance.');
                return;
            }

            try {
                const resp = await fetch('/cash-drawer/close', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        closing_balance: actual,
                        close_notes: this.closingNotes.trim() || null
                    })
                });
                const data = await resp.json();
                if (resp.ok && data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to close drawer.');
                }
            } catch (e) {
                alert('Network error while closing drawer.');
            }
        },

        openOpenModal() {
            this.openBalanceInput = '';
            this.openNotesInput = '';
            this.openDrawerModalOpen = true;
        },

        async submitOpenDrawer() {
            const floatBal = Number(this.openBalanceInput);
            if (isNaN(floatBal) || floatBal < 0 || this.openBalanceInput === '') {
                alert('Please enter a valid starting float amount.');
                return;
            }

            try {
                const resp = await fetch('/cash-drawer/open', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        opening_balance: floatBal,
                        notes: this.openNotesInput.trim() || null
                    })
                });
                const data = await resp.json();
                if (resp.ok && data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to open drawer.');
                }
            } catch (e) {
                alert('Network error while opening drawer.');
            }
        },

        async viewSessionDetail(id) {
            this.drawerLoading = true;
            this.drawerOpen = true;
            try {
                const resp = await fetch(`/cash-drawer/session/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await resp.json();
                if (data.success && data.session) {
                    const s = data.session;
                    this.activeSession = {
                        id: s.id,
                        status: s.status,
                        opened_at_formatted: new Date(s.opened_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
                        closed_at_formatted: s.closed_at ? new Date(s.closed_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : null,
                        duration: s.duration,
                        opened_by_name: s.opened_by?.name || 'Staff',
                        closed_by_name: s.closed_by_user?.name || 'Staff',
                        opening_balance: s.opening_balance,
                        cash_sales: s.cash_sales,
                        total_cash_in: s.total_cash_in,
                        total_cash_out: s.total_cash_out,
                        cash_refunds: s.cash_refunds,
                        expected_balance: s.expected_balance,
                        closing_balance: s.closing_balance,
                        difference: s.difference,
                        notes: s.notes,
                        close_notes: s.close_notes,
                        transactions: (s.transactions || []).map(t => ({
                            type: t.type,
                            description: t.description,
                            amount: t.amount,
                            time: new Date(t.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }),
                            user_name: t.user?.name || t.user_name || 'Staff',
                            running_balance: t.running_balance,
                        }))
                    };
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.drawerLoading = false;
            }
        }
    };
}
</script>
@endpush
