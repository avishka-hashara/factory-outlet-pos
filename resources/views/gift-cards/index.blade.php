{{-- ============================================
     Gift Cards Management - Redesigned Dark Dashboard Experience
     Jewellery / Clothing Outlet POS
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Gift Cards')

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
    $isFiltered = request()->filled('search') || request()->filled('date_from') || request()->filled('date_to') || (request()->filled('status') && request('status') !== 'all');
    $currentStatusTab = request('status', 'all');

    // Build JSON payload for Alpine.js
    $cardsPayload = $cards->map(function ($card) {
        $live = $card->liveStatus();
        $isRedeemed = $live === 'redeemed';
        $val = (float) $card->value;
        $bal = $isRedeemed ? 0.0 : $val;

        $daysToExpiry = $card->expiry_date ? now()->startOfDay()->diffInDays($card->expiry_date->startOfDay(), false) : null;

        return [
            'id' => $card->id,
            'card_number' => $card->card_number,
            'barcode' => $card->barcode,
            'value' => $val,
            'value_formatted' => 'Rs. ' . number_format($val, 2),
            'balance' => $bal,
            'balance_formatted' => 'Rs. ' . number_format($bal, 2),
            'status' => $card->status,
            'live_status' => $live,
            'is_expired' => $card->isExpired(),
            'days_to_expiry' => $daysToExpiry,
            'expiry_formatted' => $card->expiry_date ? $card->expiry_date->format('M d, Y') : null,
            'issued_formatted' => $card->issued_at ? $card->issued_at->format('M d, Y · h:i A') : ($card->issued_date ? $card->issued_date->format('M d, Y') : null),
            'issued_date_formatted' => $card->issued_at ? $card->issued_at->format('M d, Y') : ($card->issued_date ? $card->issued_date->format('M d, Y') : null),
            'issued_time_formatted' => $card->issued_at ? $card->issued_at->format('h:i A') : null,
            'redeemed_formatted' => $card->redeemed_at ? $card->redeemed_at->format('M d, Y · h:i A') : null,
            'redeemed_date_formatted' => $card->redeemed_at ? $card->redeemed_at->format('M d, Y') : null,
            'redeemed_time_formatted' => $card->redeemed_at ? $card->redeemed_at->format('h:i A') : null,
            'issued_sale_invoice' => $card->issuedSale ? ($card->issuedSale->invoice_number ?: ('INV-' . $card->issued_sale_id)) : null,
            'issued_sale_url' => $card->issued_sale_id ? route('sales.index', ['invoice' => $card->issuedSale?->invoice_number ?: $card->issued_sale_id]) : null,
            'redeemed_sale_invoice' => $card->redeemedSale ? ($card->redeemedSale->invoice_number ?: ('INV-' . $card->redeemed_sale_id)) : null,
            'redeemed_sale_url' => $card->redeemed_sale_id ? route('sales.index', ['invoice' => $card->redeemedSale?->invoice_number ?: $card->redeemed_sale_id]) : null,
            'sell_url' => route('gift-cards.sell', $card->id),
            'gift_url' => route('gift-cards.gift', $card->id),
        ];
    });
@endphp

@section('content')
<div x-data="giftCardsApp({
        initialCards: @js($cardsPayload),
        counts: @js($counts),
        totalAllCardsCount: {{ $totalAllCards }}
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
                    Gift Cards
                </h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-amber-500/10 text-amber-accent border border-amber-500/25">
                    <span class="material-symbols-outlined text-[13px] text-amber-accent">card_giftcard</span>
                    Store Credit
                </span>
            </div>
            <p class="font-body-sm text-[12px] text-on-surface-variant m-0">
                Issued cards, balances and redemptions
            </p>
        </div>

        <!-- Right Side Actions: Add Card (Solid Amber) + Overflow ⋯ -->
        <div class="flex items-center gap-2">
            <!-- Add Card Button (Primary Solid Amber) -->
            <button type="button"
                    @click="openAddCardModal()"
                    class="h-[34px] px-3.5 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95 shadow-md">
                <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'wght' 700;">add</span>
                <span>Add Card</span>
            </button>

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
                     class="absolute right-0 top-full mt-1.5 w-48 rounded-[6px] bg-[#13151A] border border-white/10 shadow-2xl py-1 z-50 text-[11px]"
                     style="display: none;">
                    <button type="button"
                            @click="headerMenuOpen = false; openImportModal()"
                            class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[14px]">file_upload</span>
                        <span>Bulk Import CSV</span>
                    </button>
                    <a href="{{ route('gift-cards.template') }}"
                       class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left no-underline cursor-pointer">
                        <span class="material-symbols-outlined text-[14px]">download</span>
                        <span>Download CSV Template</span>
                    </a>
                    <button type="button"
                            @click="headerMenuOpen = false; exportCardsCsv()"
                            class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[14px]">table_view</span>
                        <span>Export Cards (CSV)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages & Import Reports -->
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
    @if(session('gc_import_report'))
        @php $report = session('gc_import_report'); @endphp
        <div class="p-3.5 rounded-[8px] bg-[#171A20] border {{ count($report['rejected']) ? 'border-amber-500/40' : 'border-emerald-500/40' }} text-[12px] flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 font-bold {{ count($report['rejected']) ? 'text-amber-400' : 'text-emerald-400' }}">
                    <span class="material-symbols-outlined text-[18px]">file_upload</span>
                    <span>Import Summary: {{ $report['imported'] }} card(s) imported, {{ count($report['rejected']) }} row(s) rejected.</span>
                </div>
                <button type="button" @click="$el.parentElement.parentElement.remove()" class="text-[#8A8A93] hover:text-white bg-transparent border-0 cursor-pointer">
                    <span class="material-symbols-outlined text-[14px]">close</span>
                </button>
            </div>
            @if(count($report['rejected']))
                <div class="max-h-40 overflow-y-auto custom-scrollbar border border-[#272C35] rounded bg-[#111419] p-2">
                    <table class="w-full text-left text-[11px]">
                        <thead>
                            <tr class="text-[#8A8A93] border-b border-[#272C35]">
                                <th class="pb-1">Row</th>
                                <th class="pb-1">Card #</th>
                                <th class="pb-1">Reason(s)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['rejected'] as $rej)
                                <tr class="border-b border-[#272C35]/40 text-[#d7c3ae]">
                                    <td class="py-1">{{ $rej['row'] }}</td>
                                    <td class="py-1 font-mono">{{ $rej['card_number'] }}</td>
                                    <td class="py-1 text-rose-400">{{ implode('; ', $rej['reasons']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    <!-- ========================================================
         2. KPI ROW (5 STAT CARDS)
         ======================================================== -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 shrink-0">
        <!-- 1. Total Cards -->
        <x-pos.kpi-card
            title="Total Cards"
            icon="card_giftcard"
            :value="number_format($counts['total'])"
            :is-primary="true"
            subtext="All Stored Cards"
        />

        <!-- 2. Available (In Stock) -->
        <a href="{{ route('gift-cards.index', ['status' => 'available']) }}"
           class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden group transition-all duration-200 no-underline cursor-pointer">
            <div>
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[10px] lg:text-[11px] font-bold text-slate-300 uppercase tracking-[0.08em] truncate">
                        Available
                    </span>
                    <span class="material-symbols-outlined text-[18px] text-slate-300 shrink-0">store</span>
                </div>
                <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-white tracking-tight tabular-nums">
                    {{ number_format($counts['available']) }}
                </div>
            </div>
            <div class="text-[10px] text-[#8A8A93] font-medium tracking-wide flex items-center gap-1 mt-2">
                <span>In Stock & Ready &rarr;</span>
            </div>
        </a>

        <!-- 3. Sold (with Gifted caption) -->
        <a href="{{ route('gift-cards.index', ['status' => 'sold']) }}"
           class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden group transition-all duration-200 no-underline cursor-pointer">
            <div>
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[10px] lg:text-[11px] font-bold text-sky-400 uppercase tracking-[0.08em] truncate">
                        Sold Cards
                    </span>
                    <span class="material-symbols-outlined text-[18px] text-sky-400 shrink-0">shopping_cart</span>
                </div>
                <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-sky-400 tracking-tight tabular-nums">
                    {{ number_format($counts['sold']) }}
                </div>
            </div>
            <div class="text-[10px] text-[#8A8A93] font-medium tracking-wide flex items-center gap-1 mt-2">
                <span>+{{ number_format($counts['gifted']) }} gifted &rarr;</span>
            </div>
        </a>

        <!-- 4. Redeemed -->
        <a href="{{ route('gift-cards.index', ['status' => 'redeemed']) }}"
           class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden group transition-all duration-200 no-underline cursor-pointer">
            <div>
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[10px] lg:text-[11px] font-bold text-emerald-400 uppercase tracking-[0.08em] truncate">
                        Redeemed
                    </span>
                    <span class="material-symbols-outlined text-[18px] text-emerald-400 shrink-0">check_circle</span>
                </div>
                <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-emerald-400 tracking-tight tabular-nums">
                    {{ number_format($counts['redeemed']) }}
                </div>
            </div>
            <div class="text-[10px] text-[#8A8A93] font-medium tracking-wide flex items-center gap-1 mt-2">
                <span>Used at Checkout &rarr;</span>
            </div>
        </a>

        <!-- 5. Outstanding Balance (Unredeemed Liability) -->
        <x-pos.kpi-card
            title="Outstanding"
            icon="account_balance_wallet"
            :value="'Rs. ' . number_format($outstandingBalance, 2)"
            subtext="Unredeemed Liability"
        />
    </div>

    <!-- ========================================================
         3. FILTERS & STATUS TABS
         ======================================================== -->
    <div class="dashboard-card rounded-[8px] p-2.5 lg:p-3 bg-[#171A20] border border-[#272C35] flex flex-col gap-2.5">
        <!-- Filter Form -->
        <form method="GET" action="{{ route('gift-cards.index') }}" id="giftCardsFilterForm" class="w-full">
            <input type="hidden" name="status" id="giftCardStatusInput" value="{{ request('status', 'all') }}">

            <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-2">
                <!-- Search Input with Barcode Hint -->
                <div class="relative flex-1 min-w-[220px]">
                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[16px] text-[#8A8A93]">search</span>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           x-model="searchQuery"
                           autofocus
                           placeholder="Search card # or barcode (scan works here)..."
                           class="dashboard-control w-full h-[34px] pl-8 pr-3 text-[11px] text-white placeholder-[#8A8A93] bg-[#111419] rounded-[6px] border border-[#272C35] focus:border-[#F5A623] outline-none font-mono">
                </div>

                <!-- Date Range Cluster -->
                <div class="flex items-center gap-1.5">
                    <div class="relative flex-1 sm:w-[130px]">
                        <input type="date"
                               name="date_from"
                               value="{{ request('date_from') }}"
                               aria-label="Issued Date From"
                               class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] [color-scheme:dark]">
                    </div>
                    <span class="text-on-surface-variant text-[11px] font-semibold">→</span>
                    <div class="relative flex-1 sm:w-[130px]">
                        <input type="date"
                               name="date_to"
                               value="{{ request('date_to') }}"
                               aria-label="Issued Date To"
                               class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] [color-scheme:dark]">
                    </div>
                </div>

                <!-- Sort Control -->
                <div class="w-full lg:w-[150px]">
                    <select name="sort"
                            aria-label="Sort by"
                            class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] cursor-pointer">
                        <option value="created_desc" {{ request('sort', 'created_desc') === 'created_desc' ? 'selected' : '' }}>Newest First</option>
                        <option value="value_desc"   {{ request('sort') === 'value_desc'   ? 'selected' : '' }}>Value: High to Low</option>
                        <option value="value_asc"    {{ request('sort') === 'value_asc'    ? 'selected' : '' }}>Value: Low to High</option>
                        <option value="expiry_asc"   {{ request('sort') === 'expiry_asc'   ? 'selected' : '' }}>Expiry: Soonest</option>
                        <option value="expiry_desc"  {{ request('sort') === 'expiry_desc'  ? 'selected' : '' }}>Expiry: Latest</option>
                        <option value="issued_desc"  {{ request('sort') === 'issued_desc'  ? 'selected' : '' }}>Issued: Newest</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="submit"
                            class="h-[34px] px-3.5 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95">
                        <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'wght' 700;">filter_alt</span>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('gift-cards.index') }}"
                       class="dashboard-control h-[34px] w-[34px] text-[#8A8A93] hover:text-[#EF4444] rounded-[6px] flex items-center justify-center transition-colors no-underline"
                       title="Reset filters">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </a>
                </div>
            </div>
        </form>

        <!-- Status Tabs (All / Available / Sold / Gifted / Redeemed / Expired / Void) -->
        <div class="flex items-center gap-1.5 overflow-x-auto custom-scrollbar pt-1 border-t border-[#272C35]/50 select-none">
            <!-- All -->
            <a href="{{ route('gift-cards.index', array_merge(request()->except(['status','page']), ['status'=>'all'])) }}"
               class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors no-underline whitespace-nowrap {{ request('status', 'all') === 'all' ? 'bg-[#1E222A] text-amber-accent border-amber-accent/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white' }}">
                <span>All Cards</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-white">{{ $counts['total'] }}</span>
            </a>

            <!-- Available -->
            <a href="{{ route('gift-cards.index', array_merge(request()->except(['status','page']), ['status'=>'available'])) }}"
               class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors no-underline whitespace-nowrap {{ request('status') === 'available' ? 'bg-slate-500/20 text-white border-slate-400/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                <span>Available</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-slate-300">{{ $counts['available'] }}</span>
            </a>

            <!-- Sold -->
            <a href="{{ route('gift-cards.index', array_merge(request()->except(['status','page']), ['status'=>'sold'])) }}"
               class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors no-underline whitespace-nowrap {{ request('status') === 'sold' ? 'bg-sky-500/10 text-sky-400 border-sky-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                <span>Sold</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-sky-400">{{ $counts['sold'] }}</span>
            </a>

            <!-- Gifted -->
            <a href="{{ route('gift-cards.index', array_merge(request()->except(['status','page']), ['status'=>'gifted'])) }}"
               class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors no-underline whitespace-nowrap {{ request('status') === 'gifted' ? 'bg-purple-500/10 text-purple-400 border-purple-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                <span>Gifted</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-purple-400">{{ $counts['gifted'] }}</span>
            </a>

            <!-- Redeemed -->
            <a href="{{ route('gift-cards.index', array_merge(request()->except(['status','page']), ['status'=>'redeemed'])) }}"
               class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors no-underline whitespace-nowrap {{ request('status') === 'redeemed' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                <span>Redeemed</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-emerald-400">{{ $counts['redeemed'] }}</span>
            </a>

            <!-- Expired -->
            <a href="{{ route('gift-cards.index', array_merge(request()->except(['status','page']), ['status'=>'expired'])) }}"
               class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors no-underline whitespace-nowrap {{ request('status') === 'expired' ? 'bg-rose-500/10 text-rose-400 border-rose-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                <span>Expired</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-rose-400">{{ $counts['expired'] }}</span>
            </a>

            <!-- Cancelled / Void -->
            <a href="{{ route('gift-cards.index', array_merge(request()->except(['status','page']), ['status'=>'cancelled'])) }}"
               class="px-2.5 py-1 rounded-[6px] border text-[11px] flex items-center gap-1.5 transition-colors no-underline whitespace-nowrap {{ request('status') === 'cancelled' ? 'bg-slate-600/20 text-slate-400 border-slate-500/40 font-bold' : 'bg-[#111419] text-[#d7c3ae] border-[#272C35] font-semibold hover:text-white' }}">
                <span>Void</span>
                <span class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] tabular-nums text-slate-400">{{ $counts['cancelled'] }}</span>
            </a>
        </div>
    </div>

    <!-- ========================================================
         4. CARDS DATA TABLE & EMPTY STATES
         ======================================================== -->
    <div class="dashboard-card rounded-[8px] overflow-hidden bg-[#171A20] border border-[#272C35] flex flex-col">
        <!-- Table Header Bar -->
        <div class="px-3.5 lg:px-4 py-2.5 border-b border-[#272C35] flex items-center justify-between bg-[#111419]/60">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px] text-amber-accent">card_giftcard</span>
                <span class="font-semibold text-white text-[13px] tracking-tight">Gift Card Registry</span>
                <span class="px-1.5 py-0.2 rounded bg-surface-variant/40 border border-[#272C35] text-[10px] font-bold text-amber-accent tabular-nums">
                    <span x-text="filteredCards.length"></span>
                </span>
            </div>

            <div class="text-[11px] text-on-surface-variant">
                Showing {{ $cards->firstItem() ?? 0 }}–{{ $cards->lastItem() ?? 0 }} of {{ $cards->total() }} cards
            </div>
        </div>

        <!-- Desktop Dense Table View (>= md) -->
        <div class="overflow-x-auto custom-scrollbar hidden md:block" x-show="sortedCards.length > 0">
            <table class="w-full text-left border-collapse text-body-sm">
                <thead>
                    <tr class="border-b border-[#272C35] text-on-surface-variant font-label-caps text-[10px] uppercase bg-[#111419]/30">
                        <th class="py-2.5 px-3.5 font-bold tracking-wider">Card Number</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider">Barcode</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider text-right">Value (Face)</th>
                        <th class="py-2.5 px-3.5 font-bold tracking-wider text-right">Balance</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider text-center">Status</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider">Expiry</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider">Issued</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider">Redeemed</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider text-right w-[80px]">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="card in sortedCards" :key="card.id">
                        <tr @click="openCardDrawer(card)"
                            class="border-b border-[#272C35]/50 last:border-0 hover:bg-[#1E222A] transition-colors cursor-pointer group h-[52px]">

                            <!-- 1. Card Number -->
                            <td class="py-2 px-3.5 whitespace-nowrap">
                                <span class="font-mono text-amber-accent group-hover:underline font-bold text-[12px] tracking-tight"
                                      x-text="card.card_number"></span>
                            </td>

                            <!-- 2. Barcode -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <span class="font-mono text-[#8A8A93] text-[11px]" x-text="card.barcode"></span>
                            </td>

                            <!-- 3. Face Value -->
                            <td class="py-2 px-3 text-right whitespace-nowrap">
                                <span class="font-['Inter'] font-bold text-[13px] text-white tabular-nums tracking-tight"
                                      x-text="card.value_formatted"></span>
                            </td>

                            <!-- 4. Balance + Progress Bar -->
                            <td class="py-2 px-3.5 text-right whitespace-nowrap min-w-[120px]">
                                <div>
                                    <span class="font-['Inter'] font-bold text-[13px] tabular-nums"
                                          :class="card.balance > 0 ? 'text-emerald-400' : 'text-[#8A8A93]'"
                                          x-text="card.balance_formatted"></span>
                                    
                                    <div class="w-full h-1 bg-[#111419] rounded-full overflow-hidden border border-[#272C35] mt-0.5">
                                        <div class="h-full bg-amber-accent rounded-full transition-all duration-300"
                                             :style="`width: ${card.value > 0 ? Math.min(100, Math.max(0, (card.balance / card.value) * 100)) : 0}%`"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- 5. Status Pill -->
                            <td class="py-2 px-3 text-center whitespace-nowrap">
                                <template x-if="card.live_status === 'available'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-500/10 text-slate-300 border border-slate-500/25">
                                        In Stock
                                    </span>
                                </template>
                                <template x-if="card.live_status === 'sold'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-sky-500/10 text-sky-400 border border-sky-500/25">
                                        Sold
                                    </span>
                                </template>
                                <template x-if="card.live_status === 'gifted'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-purple-500/10 text-purple-400 border border-purple-500/25">
                                        Gifted
                                    </span>
                                </template>
                                <template x-if="card.live_status === 'redeemed'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
                                        Redeemed
                                    </span>
                                </template>
                                <template x-if="card.live_status === 'expired'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/25">
                                        Expired
                                    </span>
                                </template>
                                <template x-if="card.live_status === 'cancelled'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-600/20 text-slate-400 border border-slate-600/30 line-through">
                                        Void
                                    </span>
                                </template>
                            </td>

                            <!-- 6. Expiry Date & Chips -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[12px] text-white font-medium" x-text="card.expiry_formatted || 'Never'"></span>
                                    <template x-if="card.days_to_expiry !== null && card.days_to_expiry >= 0 && card.days_to_expiry <= 30 && card.live_status !== 'redeemed'">
                                        <span class="px-1 py-0.2 rounded text-[9px] font-bold uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20"
                                              x-text="card.days_to_expiry === 0 ? 'Expires today' : (card.days_to_expiry + 'd left')"></span>
                                    </template>
                                </div>
                            </td>

                            <!-- 7. Issued -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <template x-if="card.issued_date_formatted">
                                    <div class="flex items-center gap-1">
                                        <span class="text-[12px] text-white" x-text="card.issued_date_formatted"></span>
                                        <template x-if="card.issued_sale_invoice">
                                            <a :href="card.issued_sale_url" target="_blank" @click.stop class="text-amber-accent hover:underline text-[11px]" title="View sale">
                                                <span class="material-symbols-outlined text-[13px]">receipt</span>
                                            </a>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!card.issued_date_formatted">
                                    <span class="text-[#8A8A93]">—</span>
                                </template>
                            </td>

                            <!-- 8. Redeemed -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <template x-if="card.redeemed_date_formatted">
                                    <div class="flex items-center gap-1">
                                        <span class="text-[12px] text-emerald-400" x-text="card.redeemed_date_formatted"></span>
                                        <template x-if="card.redeemed_sale_invoice">
                                            <a :href="card.redeemed_sale_url" target="_blank" @click.stop class="text-emerald-400 hover:underline text-[11px]" title="View redemption sale">
                                                <span class="material-symbols-outlined text-[13px]">receipt</span>
                                            </a>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!card.redeemed_date_formatted">
                                    <span class="text-[#8A8A93]">—</span>
                                </template>
                            </td>

                            <!-- 9. Actions -->
                            <td class="py-2 px-3 text-right whitespace-nowrap" @click.stop>
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button"
                                            @click="openCardDrawer(card)"
                                            class="w-7 h-7 rounded-[4px] bg-[#111419] hover:bg-amber-accent/15 text-[#8A8A93] hover:text-amber-accent border border-[#272C35] flex items-center justify-center transition-colors cursor-pointer"
                                            title="View Details">
                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                    </button>

                                    <template x-if="card.live_status === 'available'">
                                        <a :href="card.sell_url"
                                           class="w-7 h-7 rounded-[4px] bg-[#111419] hover:bg-emerald-500/20 text-emerald-400 border border-[#272C35] flex items-center justify-center transition-colors no-underline"
                                           title="Sell at POS">
                                            <span class="material-symbols-outlined text-[14px]">point_of_sale</span>
                                        </a>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Mobile Stacked Card View (< md) -->
        <div class="md:hidden flex flex-col divide-y divide-[#272C35]/50" x-show="sortedCards.length > 0">
            <template x-for="card in sortedCards" :key="'mob-' + card.id">
                <div @click="openCardDrawer(card)"
                     class="p-3 hover:bg-[#1E222A] transition-colors cursor-pointer flex flex-col gap-2">
                    
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-amber-accent font-bold text-[13px]" x-text="card.card_number"></span>
                        <span class="font-['Inter'] font-bold text-[14px] text-white tabular-nums" x-text="card.value_formatted"></span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-[#8A8A93]">
                        <span x-text="'Barcode: ' + card.barcode"></span>
                        <span class="font-bold uppercase text-[9px] px-1.5 py-0.2 rounded"
                              :class="card.live_status === 'redeemed' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-500/10 text-slate-300'"
                              x-text="card.live_status"></span>
                    </div>

                    <div class="flex items-center justify-between pt-1 border-t border-[#272C35]/50 text-[11px]" @click.stop>
                        <span class="text-[#8A8A93]" x-text="card.expiry_formatted ? ('Exp: ' + card.expiry_formatted) : 'No expiry'"></span>

                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    @click="openCardDrawer(card)"
                                    class="px-2 py-1 rounded bg-[#111419] text-[#d7c3ae] border border-[#272C35] text-[10px] font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[13px]">visibility</span> View
                            </button>
                            <template x-if="card.live_status === 'available'">
                                <a :href="card.sell_url"
                                   class="px-2 py-1 rounded bg-[#111419] text-emerald-400 border border-[#272C35] text-[10px] font-semibold flex items-center gap-1 no-underline">
                                    <span class="material-symbols-outlined text-[13px]">point_of_sale</span> Sell
                                </a>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- 1. Default Onboarding Empty State (0 cards in DB) -->
        <div x-show="totalAllCardsCount === 0 && !isFiltered" class="py-16 px-4 text-center">
            <div class="w-14 h-14 rounded-full bg-surface-variant/40 border border-[#272C35] flex items-center justify-center mx-auto mb-3 text-[#5A5A63]">
                <span class="material-symbols-outlined text-[28px]">card_giftcard</span>
            </div>
            <h3 class="text-[16px] font-bold text-white mb-1">No gift cards yet</h3>
            <p class="text-[12px] text-[#8A8A93] max-w-sm mx-auto mb-4 leading-relaxed">
                Create cards individually, or import a batch from CSV to get started.
            </p>
            <div class="flex items-center justify-center gap-2 flex-wrap mb-2">
                <button type="button"
                        @click="openAddCardModal()"
                        class="h-[34px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center gap-1.5 cursor-pointer border-0 transition-colors shadow-md">
                    <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'wght' 700;">add</span>
                    <span>Add Card</span>
                </button>
                <button type="button"
                        @click="openImportModal()"
                        class="dashboard-control h-[34px] px-3.5 text-[#d7c3ae] hover:text-white text-[11px] font-semibold uppercase tracking-wider rounded-[6px] flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-[15px]">file_upload</span>
                    <span>Bulk Import</span>
                </button>
            </div>
            <a href="{{ route('gift-cards.template') }}" class="text-[11px] text-[#8A8A93] hover:text-amber-accent underline transition-colors">
                Download CSV template
            </a>
        </div>

        <!-- 2. Filtered Empty State (Cards exist, but 0 match active filters) -->
        <div x-show="(totalAllCardsCount > 0 || isFiltered) && sortedCards.length === 0" class="py-14 px-4 text-center">
            <span class="material-symbols-outlined text-[36px] text-[#5A5A63] mb-2 block">filter_alt_off</span>
            <div class="text-[14px] font-semibold text-white mb-1">No cards match these filters</div>
            <p class="text-[12px] text-[#8A8A93] max-w-sm mx-auto mb-3">
                Try adjusting your search query, status tab, or date range.
            </p>
            <a href="{{ route('gift-cards.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[6px] bg-[#111419] hover:bg-[#1E222A] text-amber-accent border border-[#272C35] text-[11px] font-bold uppercase tracking-wider no-underline transition-colors">
                <span class="material-symbols-outlined text-[14px]">refresh</span>
                <span>Clear Filters</span>
            </a>
        </div>

        <!-- ========================================================
             5. FOOTER & PAGINATION
             ======================================================== -->
        @if ($cards->hasPages())
            <div class="p-3 border-t border-[#272C35] flex flex-col sm:flex-row items-center justify-between gap-3 bg-[#111419]/40">
                <div class="text-[11px] text-[#8A8A93]">
                    Showing {{ $cards->firstItem() ?? 0 }} to {{ $cards->lastItem() ?? 0 }} of {{ $cards->total() }} gift cards
                </div>

                <!-- Custom Dark Pagination -->
                <div class="flex items-center gap-1 select-none">
                    @if ($cards->onFirstPage())
                        <span class="dashboard-control w-8 h-8 rounded-[4px] opacity-40 flex items-center justify-center cursor-not-allowed">
                            <span class="material-symbols-outlined text-[16px] text-[#8A8A93]">chevron_left</span>
                        </span>
                    @else
                        <a href="{{ $cards->previousPageUrl() }}"
                           class="dashboard-control w-8 h-8 rounded-[4px] flex items-center justify-center hover:text-amber-accent transition-colors no-underline">
                            <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                        </a>
                    @endif

                    @foreach ($cards->getUrlRange(max(1, $cards->currentPage() - 2), min($cards->lastPage(), $cards->currentPage() + 2)) as $page => $url)
                        @if ($page == $cards->currentPage())
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

                    @if ($cards->hasMorePages())
                        <a href="{{ $cards->nextPageUrl() }}"
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
         6. SHARED GIFT CARD DETAIL DRAWER (Teleported to Body)
         ======================================================== -->
    <x-pos.gift-card-drawer />

    <!-- ========================================================
         7. ADD CARD & BULK IMPORT MODALS
         ======================================================== -->
    
    <!-- Modal: Add Gift Card Modal -->
    <template x-teleport="body">
        <div x-show="addCardModalOpen"
             @keydown.escape.window="addCardModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-md rounded-[8px] bg-[#13151A] border border-[#272C35] p-5 shadow-2xl flex flex-col gap-3.5">
                <form method="POST" action="{{ route('gift-cards.store') }}">
                    @csrf
                    <div class="flex items-center justify-between border-b border-[#272C35] pb-3 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px] text-amber-accent">card_giftcard</span>
                            <h4 class="m-0 text-[15px] font-bold text-white">Add Gift Card</h4>
                        </div>
                        <button type="button" @click="addCardModalOpen = false" class="dashboard-control w-6 h-6 rounded flex items-center justify-center text-[#8A8A93] hover:text-white border-0 cursor-pointer">
                            <span class="material-symbols-outlined text-[15px]">close</span>
                        </button>
                    </div>

                    <div class="flex flex-col gap-3 text-[12px]">
                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Card Number <span class="text-rose-400">*</span></label>
                            <input type="text" name="card_number" required placeholder="e.g. GC10000001"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px] font-mono">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">
                                Barcode <span class="text-rose-400">*</span>
                                <span class="text-[#8A8A93] font-normal">(scan barcode directly)</span>
                            </label>
                            <input type="text" name="barcode" required autocomplete="off" placeholder="Scan or enter barcode..."
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();}"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px] font-mono">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Value (Rs.) <span class="text-rose-400">*</span></label>
                            <input type="number" name="value" step="0.01" min="1" required placeholder="5000.00"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px] font-['Inter'] tabular-nums font-bold">
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Issued Date</label>
                                <input type="date" name="issued_date"
                                       class="dashboard-control w-full h-[36px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] [color-scheme:dark]">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Expiry Date</label>
                                <input type="date" name="expiry_date"
                                       class="dashboard-control w-full h-[36px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] [color-scheme:dark]">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 mt-4 border-t border-[#272C35]">
                        <button type="button" @click="addCardModalOpen = false" class="dashboard-control h-[32px] px-3 text-[11px] font-semibold text-[#d7c3ae] hover:text-white rounded-[6px]">
                            Cancel
                        </button>
                        <button type="submit" class="h-[32px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] border-0 cursor-pointer">
                            Add Card
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Modal: Bulk Import Modal -->
    <template x-teleport="body">
        <div x-show="importModalOpen"
             @keydown.escape.window="importModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-md rounded-[8px] bg-[#13151A] border border-[#272C35] p-5 shadow-2xl flex flex-col gap-3.5">
                <form method="POST" action="{{ route('gift-cards.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="flex items-center justify-between border-b border-[#272C35] pb-3 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px] text-amber-accent">file_upload</span>
                            <h4 class="m-0 text-[15px] font-bold text-white">Bulk Import Gift Cards</h4>
                        </div>
                        <button type="button" @click="importModalOpen = false" class="dashboard-control w-6 h-6 rounded flex items-center justify-center text-[#8A8A93] hover:text-white border-0 cursor-pointer">
                            <span class="material-symbols-outlined text-[15px]">close</span>
                        </button>
                    </div>

                    <div class="p-3 rounded bg-[#111419] border border-[#272C35] text-[11px] text-[#8A8A93] flex flex-col gap-1.5 mb-3">
                        <div class="font-bold text-white flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px] text-amber-accent">info</span>
                            <span>Instructions:</span>
                        </div>
                        <p class="m-0">1. Download and populate the official CSV template.</p>
                        <p class="m-0">2. Required columns: <code class="text-amber-accent">card_number</code>, <code class="text-amber-accent">barcode</code>, <code class="text-amber-accent">value</code>.</p>
                        <p class="m-0">3. Every card will be imported as <strong class="text-white">Available (In Stock)</strong>.</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1.5">Select CSV File <span class="text-rose-400">*</span></label>
                        <input type="file" name="file" accept=".csv,.txt" required
                               class="dashboard-control w-full h-[36px] px-2 text-[11px] text-white bg-[#111419] rounded-[6px] file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:bg-amber-accent file:text-black">
                    </div>

                    <div class="flex items-center justify-between pt-3 mt-4 border-t border-[#272C35]">
                        <a href="{{ route('gift-cards.template') }}" class="text-[11px] text-[#8A8A93] hover:text-amber-accent underline">
                            Download Template
                        </a>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="importModalOpen = false" class="dashboard-control h-[32px] px-3 text-[11px] font-semibold text-[#d7c3ae] hover:text-white rounded-[6px]">
                                Cancel
                            </button>
                            <button type="submit" class="h-[32px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] border-0 cursor-pointer">
                                Import Cards
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
function giftCardsApp(config) {
    return {
        cards: config.initialCards || [],
        counts: config.counts || {},
        totalAllCardsCount: config.totalAllCardsCount || 0,
        searchQuery: '',
        drawerOpen: false,
        activeCard: null,

        // Modals
        addCardModalOpen: false,
        importModalOpen: false,

        get filteredCards() {
            if (!this.searchQuery.trim()) {
                return this.cards;
            }
            const q = this.searchQuery.toLowerCase().trim();
            return this.cards.filter(c => {
                const numMatch = (c.card_number || '').toLowerCase().includes(q);
                const barMatch = (c.barcode || '').toLowerCase().includes(q);
                return numMatch || barMatch;
            });
        },

        get sortedCards() {
            return [...this.filteredCards];
        },

        openCardDrawer(card) {
            this.activeCard = card;
            this.drawerOpen = true;
        },

        openAddCardModal() {
            this.addCardModalOpen = true;
        },

        openImportModal() {
            this.importModalOpen = true;
        },

        printCard(card) {
            if (!card) return;
            window.print();
        },

        exportCardsCsv() {
            const rows = this.filteredCards;
            if (!rows.length) {
                alert('No gift cards to export.');
                return;
            }

            const headers = ['Card Number', 'Barcode', 'Value', 'Balance', 'Status', 'Expiry Date', 'Issued Date', 'Redeemed Date'];
            const csvRows = [headers.join(',')];

            rows.forEach(c => {
                const values = [
                    `"${c.card_number}"`,
                    `"${c.barcode}"`,
                    c.value.toFixed(2),
                    c.balance.toFixed(2),
                    `"${c.live_status}"`,
                    `"${c.expiry_formatted || 'Never'}"`,
                    `"${c.issued_date_formatted || 'Not Issued'}"`,
                    `"${c.redeemed_date_formatted || 'Not Redeemed'}"`,
                ];
                csvRows.push(values.join(','));
            });

            const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', `gift_cards_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };
}
</script>
@endpush
