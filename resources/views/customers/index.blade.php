{{-- ============================================
     Customer Management - Redesigned Dark Dashboard Experience
     CRM Contacts, Spend, Loyalty & Purchase History
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Customers')

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
    $isFiltered = request()->filled('search') || request()->filled('type') || request()->filled('tier') || request()->filled('date_from') || request()->filled('date_to');

    // Build customers payload for Alpine.js
    $customersPayload = $customers->map(function ($c) {
        $spent = (float) ($c->sales_sum_total ?? 0);
        $ordersCount = (int) ($c->sales_count ?? 0);
        $avgBasket = $ordersCount > 0 ? round($spent / $ordersCount, 2) : 0.0;
        
        $tier = $c->tier;
        $type = $c->customer_type;

        $lastVisitDate = $c->sales_max_created_at ? \Carbon\Carbon::parse($c->sales_max_created_at) : null;
        $isLapsed = $lastVisitDate && $lastVisitDate->diffInDays(now()) > 90;

        $cleanPhone = preg_replace('/[^0-9]/', '', (string)$c->phone);
        $whatsappUrl = $cleanPhone ? ('https://wa.me/' . (str_starts_with($cleanPhone, '0') ? ('94' . substr($cleanPhone, 1)) : $cleanPhone)) : null;

        $recentSales = $c->sales ? $c->sales->map(function ($s) {
            return [
                'id' => $s->id,
                'invoice_number' => $s->invoice_number ?: ('INV-' . $s->id),
                'date_formatted' => $s->created_at->format('M d, Y · h:i A'),
                'items_count' => $s->items->count(),
                'total_formatted' => 'Rs. ' . number_format((float)$s->total, 2),
                'url' => route('sales.index', ['invoice' => $s->invoice_number ?: $s->id]),
            ];
        }) : [];

        return [
            'id' => $c->id,
            'name' => $c->name ?: 'Unnamed Customer',
            'phone' => $c->phone,
            'email' => $c->email,
            'address' => $c->address,
            'tier' => $tier,
            'type' => $type,
            'orders_count' => $ordersCount,
            'total_spent' => $spent,
            'total_spent_formatted' => 'Rs. ' . number_format($spent, 2),
            'avg_basket_formatted' => 'Rs. ' . number_format($avgBasket, 2),
            'last_visit_relative' => $lastVisitDate ? $lastVisitDate->diffForHumans() : null,
            'last_visit_formatted' => $lastVisitDate ? $lastVisitDate->format('M d, Y') : null,
            'is_lapsed' => $isLapsed,
            'created_at_formatted' => $c->created_at->format('M d, Y'),
            'whatsapp_url' => $whatsappUrl,
            'history_url' => route('sales.index', ['search' => $c->phone ?: $c->name]),
            'delete_url' => route('customers.destroy', $c->id),
            'recent_sales' => $recentSales,
        ];
    });
@endphp

@section('content')
<div x-data="customersApp({
        initialCustomers: @js($customersPayload),
        totalAllCount: {{ $totalAllCustomers }}
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
                    Customers
                </h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-amber-500/10 text-amber-accent border border-amber-500/25">
                    <span class="material-symbols-outlined text-[13px] text-amber-accent">contacts</span>
                    CRM
                </span>
            </div>
            <p class="font-body-sm text-[12px] text-on-surface-variant m-0">
                Contacts, spend and loyalty
            </p>
        </div>

        <!-- Right Side Actions: Add Customer (Solid Amber) + Overflow ⋯ -->
        <div class="flex items-center gap-2">
            <!-- Add Customer Button (Primary Solid Amber) -->
            <button type="button"
                    @click="openAddModal()"
                    class="h-[34px] px-3.5 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95 shadow-md">
                <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'wght' 700;">person_add</span>
                <span>Add Customer</span>
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
                            @click="headerMenuOpen = false; exportCustomersCsv()"
                            class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[14px]">table_view</span>
                        <span>Export Customers (CSV)</span>
                    </button>
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
    @if(session('error') || $errors->any())
        <div class="p-3 rounded-[6px] bg-rose-500/10 border border-rose-500/30 text-rose-400 text-[12px] flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">error</span>
                <span>{{ session('error') ?: $errors->first() }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-400 hover:text-white bg-transparent border-0 cursor-pointer">
                <span class="material-symbols-outlined text-[14px]">close</span>
            </button>
        </div>
    @endif

    <!-- ========================================================
         2. KPI ROW (4 STAT CARDS)
         ======================================================== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 shrink-0">
        <!-- 1. Total Customers -->
        <x-pos.kpi-card
            title="Total Customers"
            icon="groups"
            :value="number_format($totalCustomers)"
            :is-primary="true"
            subtext="Registered Profiles"
        />

        <!-- 2. New This Month -->
        <div class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden group transition-all duration-200">
            <div>
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[10px] lg:text-[11px] font-bold text-sky-400 uppercase tracking-[0.08em] truncate">
                        New This Month
                    </span>
                    <span class="material-symbols-outlined text-[18px] text-sky-400 shrink-0">person_add</span>
                </div>
                <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-white tracking-tight tabular-nums">
                    {{ number_format($newThisMonth) }}
                </div>
            </div>
            <div class="text-[10px] text-[#8A8A93] font-medium tracking-wide flex items-center gap-1 mt-2">
                @if($monthDelta !== null)
                    <span class="px-1 py-0.2 rounded font-bold {{ $monthDelta >= 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                        {{ $monthDelta >= 0 ? '+' : '' }}{{ $monthDelta }}%
                    </span>
                    <span>vs last month</span>
                @else
                    <span>Joined this month</span>
                @endif
            </div>
        </div>

        <!-- 3. Linked Revenue -->
        <div class="dashboard-card relative p-3 lg:p-3.5 flex flex-col justify-between overflow-hidden group transition-all duration-200">
            <div>
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[10px] lg:text-[11px] font-bold text-emerald-400 uppercase tracking-[0.08em] truncate">
                        Linked Revenue
                    </span>
                    <span class="material-symbols-outlined text-[18px] text-emerald-400 shrink-0">payments</span>
                </div>
                <div class="font-['Inter'] font-bold text-[18px] lg:text-[20px] text-white tracking-tight tabular-nums">
                    Rs. {{ number_format($linkedRevenue, 2) }}
                </div>
            </div>
            <div class="text-[10px] text-[#8A8A93] font-medium tracking-wide flex items-center gap-1 mt-2 truncate">
                <span class="text-amber-accent font-bold">{{ $linkedPercent }}%</span>
                <span>of total sales (Rs. {{ number_format($totalStoreRevenue, 0) }})</span>
            </div>
        </div>

        <!-- 4. Avg Customer Value -->
        <x-pos.kpi-card
            title="Avg Customer Value"
            icon="analytics"
            :value="'Rs. ' . number_format($avgCustomerValue, 2)"
            subtext="Linked Rev ÷ Customers"
        />
    </div>

    <!-- ========================================================
         3. FILTERS TOOLBAR
         ======================================================== -->
    <div class="dashboard-card rounded-[8px] p-2.5 lg:p-3 bg-[#171A20] border border-[#272C35]">
        <form method="GET" action="{{ route('customers.index') }}" class="w-full">
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-2">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[200px]">
                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[16px] text-[#8A8A93]">search</span>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           x-model="searchQuery"
                           placeholder="Search name, phone, email..."
                           class="dashboard-control w-full h-[34px] pl-8 pr-3 text-[11px] text-white placeholder-[#8A8A93] bg-[#111419] rounded-[6px] border border-[#272C35] focus:border-[#F5A623] outline-none">
                </div>

                <!-- Customer Type Select -->
                <div class="w-full sm:w-[130px]">
                    <select name="type"
                            x-model="typeFilter"
                            aria-label="Customer Type"
                            class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] cursor-pointer">
                        <option value="">All Types</option>
                        <option value="Retail"    {{ request('type') === 'Retail' ? 'selected' : '' }}>Retail</option>
                        <option value="Wholesale" {{ request('type') === 'Wholesale' ? 'selected' : '' }}>Wholesale</option>
                        <option value="Walk-in"   {{ request('type') === 'Walk-in' ? 'selected' : '' }}>Walk-in</option>
                    </select>
                </div>

                <!-- Loyalty Tier Select -->
                <div class="w-full sm:w-[130px]">
                    <select name="tier"
                            x-model="tierFilter"
                            aria-label="Loyalty Tier"
                            class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] cursor-pointer">
                        <option value="">All Tiers</option>
                        <option value="Platinum" {{ request('tier') === 'Platinum' ? 'selected' : '' }}>Platinum</option>
                        <option value="Gold"     {{ request('tier') === 'Gold' ? 'selected' : '' }}>Gold</option>
                        <option value="Silver"   {{ request('tier') === 'Silver' ? 'selected' : '' }}>Silver</option>
                        <option value="Bronze"   {{ request('tier') === 'Bronze' ? 'selected' : '' }}>Bronze</option>
                    </select>
                </div>

                <!-- Date Range Cluster -->
                <div class="flex items-center gap-1.5">
                    <div class="relative flex-1 sm:w-[125px]">
                        <input type="date"
                               name="date_from"
                               value="{{ request('date_from') }}"
                               aria-label="Added Date From"
                               class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] [color-scheme:dark]">
                    </div>
                    <span class="text-on-surface-variant text-[11px] font-semibold">→</span>
                    <div class="relative flex-1 sm:w-[125px]">
                        <input type="date"
                               name="date_to"
                               value="{{ request('date_to') }}"
                               aria-label="Added Date To"
                               class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] [color-scheme:dark]">
                    </div>
                </div>

                <!-- Sort Control -->
                <div class="w-full lg:w-[150px]">
                    <select name="sort"
                            aria-label="Sort by"
                            class="dashboard-control w-full h-[34px] px-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] border border-[#272C35] cursor-pointer">
                        <option value="spent_desc"   {{ request('sort', 'spent_desc') === 'spent_desc' ? 'selected' : '' }}>Total Spent: High → Low</option>
                        <option value="name_asc"     {{ request('sort') === 'name_asc'     ? 'selected' : '' }}>Name (A → Z)</option>
                        <option value="orders_desc"  {{ request('sort') === 'orders_desc'  ? 'selected' : '' }}>Orders: Most</option>
                        <option value="recent_visit" {{ request('sort') === 'recent_visit' ? 'selected' : '' }}>Last Visit: Recent</option>
                        <option value="newest"       {{ request('sort') === 'newest'       ? 'selected' : '' }}>Recently Added</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="submit"
                            class="h-[34px] px-3.5 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95">
                        <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'wght' 700;">filter_alt</span>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('customers.index') }}"
                       class="dashboard-control h-[34px] w-[34px] text-[#8A8A93] hover:text-[#EF4444] rounded-[6px] flex items-center justify-center transition-colors no-underline"
                       title="Reset filters">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================================
         4. CUSTOMERS DATA TABLE
         ======================================================== -->
    <div class="dashboard-card rounded-[8px] overflow-hidden bg-[#171A20] border border-[#272C35] flex flex-col">
        <!-- Table Header Bar -->
        <div class="px-3.5 lg:px-4 py-2.5 border-b border-[#272C35] flex items-center justify-between bg-[#111419]/60">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px] text-amber-accent">groups</span>
                <span class="font-semibold text-white text-[13px] tracking-tight">Customer Directory</span>
                <span class="px-1.5 py-0.2 rounded bg-surface-variant/40 border border-[#272C35] text-[10px] font-bold text-amber-accent tabular-nums">
                    <span x-text="filteredCustomers.length"></span>
                </span>
            </div>

            <div class="text-[11px] text-on-surface-variant">
                Showing {{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }} customers
            </div>
        </div>

        <!-- Desktop Dense Table View (>= md) -->
        <div class="overflow-x-auto custom-scrollbar hidden md:block" x-show="filteredCustomers.length > 0">
            <table class="w-full text-left border-collapse text-body-sm">
                <thead>
                    <tr class="border-b border-[#272C35] text-on-surface-variant font-label-caps text-[10px] uppercase bg-[#111419]/30">
                        <th class="py-2.5 px-3.5 font-bold tracking-wider">Customer</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider">Phone</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider">Email</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider text-center">Tier</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider text-right">Orders</th>
                        <th class="py-2.5 px-3.5 font-bold tracking-wider text-right">Total Spent</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider">Last Visit</th>
                        <th class="py-2.5 px-3 font-bold tracking-wider">Added On</th>
                        <th class="py-2.5 px-3.5 font-bold tracking-wider text-right w-[80px]">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="customer in filteredCustomers" :key="customer.id">
                        <tr @click="openCustomerDrawer(customer)"
                            class="border-b border-[#272C35]/50 last:border-0 hover:bg-[#1E222A] transition-colors cursor-pointer group h-[52px]">

                            <!-- 1. Customer Name + Avatar + Type -->
                            <td class="py-2 px-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-surface-variant flex items-center justify-center text-[11px] font-bold text-amber-accent border border-[#272C35] shrink-0"
                                         x-text="(customer.name || 'C').charAt(0).toUpperCase()"></div>
                                    <div>
                                        <span class="font-bold text-white group-hover:text-amber-accent transition-colors block text-[13px] tracking-tight leading-tight"
                                              x-text="customer.name"></span>
                                        <span class="text-[10px] text-[#8A8A93] leading-none" x-text="customer.type"></span>
                                    </div>
                                </div>
                            </td>

                            <!-- 2. Phone + WhatsApp -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5" @click.stop>
                                    <template x-if="customer.phone">
                                        <a :href="'tel:' + customer.phone" class="font-mono text-[#d7c3ae] hover:text-amber-accent text-[11px] font-medium" x-text="customer.phone"></a>
                                    </template>
                                    <template x-if="!customer.phone">
                                        <span class="text-[#8A8A93] text-[11px]">—</span>
                                    </template>

                                    <template x-if="customer.whatsapp_url">
                                        <a :href="customer.whatsapp_url" target="_blank" class="text-emerald-400 hover:text-emerald-300" title="Chat on WhatsApp">
                                            <span class="material-symbols-outlined text-[14px]">chat</span>
                                        </a>
                                    </template>
                                </div>
                            </td>

                            <!-- 3. Email -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <template x-if="customer.email">
                                    <a :href="'mailto:' + customer.email" @click.stop class="text-[#8A8A93] hover:text-white text-[11px] truncate max-w-[150px] block" :title="customer.email" x-text="customer.email"></a>
                                </template>
                                <template x-if="!customer.email">
                                    <span class="text-[#8A8A93] text-[11px]">—</span>
                                </template>
                            </td>

                            <!-- 4. Tier Badge -->
                            <td class="py-2 px-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-white/5 border border-white/10 text-white">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="getTierDotClass(customer.tier)"></span>
                                    <span x-text="customer.tier"></span>
                                </span>
                            </td>

                            <!-- 5. Orders Count -->
                            <td class="py-2 px-3 text-right whitespace-nowrap">
                                <span class="font-['Inter'] font-semibold text-[12px] text-white tabular-nums"
                                      x-text="customer.orders_count"></span>
                            </td>

                            <!-- 6. Total Spent -->
                            <td class="py-2 px-3.5 text-right whitespace-nowrap">
                                <span class="font-['Inter'] font-bold text-[13px] text-amber-accent tabular-nums tracking-tight"
                                      x-text="customer.total_spent_formatted"></span>
                            </td>

                            <!-- 7. Last Visit -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <template x-if="customer.last_visit_relative">
                                    <div>
                                        <div class="flex items-center gap-1">
                                            <span class="text-[12px] text-white font-medium" x-text="customer.last_visit_relative"></span>
                                            <template x-if="customer.is_lapsed">
                                                <span class="px-1 py-0.2 rounded text-[8px] font-bold uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20">Lapsed</span>
                                            </template>
                                        </div>
                                        <span class="text-[10px] text-[#8A8A93] block" x-text="customer.last_visit_formatted"></span>
                                    </div>
                                </template>
                                <template x-if="!customer.last_visit_relative">
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-white/5 text-[#8A8A93] border border-white/10">Never</span>
                                </template>
                            </td>

                            <!-- 8. Added On -->
                            <td class="py-2 px-3 whitespace-nowrap">
                                <span class="text-[11px] text-[#8A8A93]" x-text="customer.created_at_formatted"></span>
                            </td>

                            <!-- 9. Actions -->
                            <td class="py-2 px-3.5 text-right whitespace-nowrap" @click.stop>
                                <div class="flex items-center justify-end gap-1">
                                    <!-- View Eye Button -->
                                    <button type="button"
                                            @click="openCustomerDrawer(customer)"
                                            class="w-7 h-7 rounded-[4px] bg-[#111419] hover:bg-amber-accent/15 text-[#8A8A93] hover:text-amber-accent border border-[#272C35] flex items-center justify-center transition-colors cursor-pointer"
                                            title="View Details">
                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                    </button>

                                    <!-- Overflow ⋯ Menu Button -->
                                    <button type="button"
                                            @click="openFloatingMenu($event, customer)"
                                            class="w-7 h-7 rounded-[4px] bg-[#111419] hover:bg-[#1E222A] text-[#8A8A93] hover:text-white border border-[#272C35] flex items-center justify-center transition-colors cursor-pointer"
                                            title="More actions">
                                        <span class="material-symbols-outlined text-[15px]">more_vert</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Mobile Stacked Card View (< md) -->
        <div class="md:hidden flex flex-col divide-y divide-[#272C35]/50" x-show="filteredCustomers.length > 0">
            <template x-for="customer in filteredCustomers" :key="'mob-' + customer.id">
                <div @click="openCustomerDrawer(customer)"
                     class="p-3 hover:bg-[#1E222A] transition-colors cursor-pointer flex flex-col gap-2">
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-surface-variant flex items-center justify-center text-[10px] font-bold text-amber-accent border border-[#272C35]"
                                 x-text="(customer.name || 'C').charAt(0).toUpperCase()"></div>
                            <span class="font-bold text-white text-[13px]" x-text="customer.name"></span>
                        </div>
                        <span class="font-['Inter'] font-bold text-[14px] text-amber-accent tabular-nums" x-text="customer.total_spent_formatted"></span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-[#8A8A93]">
                        <span x-text="customer.phone || 'No phone'"></span>
                        <span class="font-bold uppercase text-[9px] px-1.5 py-0.2 rounded bg-white/5 border border-white/10 text-white" x-text="customer.tier"></span>
                    </div>

                    <div class="flex items-center justify-between pt-1 border-t border-[#272C35]/50 text-[11px]" @click.stop>
                        <span class="text-[#8A8A93]" x-text="customer.last_visit_relative ? ('Visit: ' + customer.last_visit_relative) : 'Never visited'"></span>

                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    @click="openCustomerDrawer(customer)"
                                    class="px-2 py-1 rounded bg-[#111419] text-[#d7c3ae] border border-[#272C35] text-[10px] font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[13px]">visibility</span> View
                            </button>
                            <button type="button"
                                    @click="openFloatingMenu($event, customer)"
                                    class="p-1 rounded bg-[#111419] text-[#8A8A93] border border-[#272C35]">
                                <span class="material-symbols-outlined text-[13px]">more_vert</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- 1. Default Onboarding Empty State (0 customers in DB) -->
        <div x-show="totalAllCount === 0 && !isFiltered" class="py-16 px-4 text-center">
            <div class="w-14 h-14 rounded-full bg-surface-variant/40 border border-[#272C35] flex items-center justify-center mx-auto mb-3 text-[#5A5A63]">
                <span class="material-symbols-outlined text-[28px]">groups</span>
            </div>
            <h3 class="text-[16px] font-bold text-white mb-1">No customers yet</h3>
            <p class="text-[12px] text-[#8A8A93] max-w-sm mx-auto mb-4 leading-relaxed">
                Add your first customer profile or import a contact list from CSV.
            </p>
            <div class="flex items-center justify-center gap-2 flex-wrap">
                <button type="button"
                        @click="openAddModal()"
                        class="h-[34px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center gap-1.5 cursor-pointer border-0 transition-colors shadow-md">
                    <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'wght' 700;">person_add</span>
                    <span>Add Customer</span>
                </button>
            </div>
        </div>

        <!-- 2. Filtered Empty State (Customers exist, but 0 match active filters) -->
        <div x-show="(totalAllCount > 0 || isFiltered) && filteredCustomers.length === 0" class="py-14 px-4 text-center">
            <span class="material-symbols-outlined text-[36px] text-[#5A5A63] mb-2 block">filter_alt_off</span>
            <div class="text-[14px] font-semibold text-white mb-1">No customers match these filters</div>
            <p class="text-[12px] text-[#8A8A93] max-w-sm mx-auto mb-3">
                Try adjusting your search query, type, loyalty tier, or date range.
            </p>
            <a href="{{ route('customers.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[6px] bg-[#111419] hover:bg-[#1E222A] text-amber-accent border border-[#272C35] text-[11px] font-bold uppercase tracking-wider no-underline transition-colors">
                <span class="material-symbols-outlined text-[14px]">refresh</span>
                <span>Clear Filters</span>
            </a>
        </div>

        <!-- ========================================================
             5. FOOTER & PAGINATION
             ======================================================== -->
        @if ($customers->hasPages())
            <div class="p-3 border-t border-[#272C35] flex flex-col sm:flex-row items-center justify-between gap-3 bg-[#111419]/40">
                <div class="text-[11px] text-[#8A8A93]">
                    Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }} customers
                </div>

                <!-- Custom Dark Pagination -->
                <div class="flex items-center gap-1 select-none">
                    @if ($customers->onFirstPage())
                        <span class="dashboard-control w-8 h-8 rounded-[4px] opacity-40 flex items-center justify-center cursor-not-allowed">
                            <span class="material-symbols-outlined text-[16px] text-[#8A8A93]">chevron_left</span>
                        </span>
                    @else
                        <a href="{{ $customers->previousPageUrl() }}"
                           class="dashboard-control w-8 h-8 rounded-[4px] flex items-center justify-center hover:text-amber-accent transition-colors no-underline">
                            <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                        </a>
                    @endif

                    @foreach ($customers->getUrlRange(max(1, $customers->currentPage() - 2), min($customers->lastPage(), $customers->currentPage() + 2)) as $page => $url)
                        @if ($page == $customers->currentPage())
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

                    @if ($customers->hasMorePages())
                        <a href="{{ $customers->nextPageUrl() }}"
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
         6. SHARED CUSTOMER DETAIL DRAWER (Teleported to Body)
         ======================================================== -->
    <x-pos.customer-drawer />

    <!-- ========================================================
         7. FLOATING ACTIONS MENU (Teleported to Body)
         ======================================================== -->
    <template x-teleport="body">
        <div x-show="floatingMenu.open"
             @click.outside="closeFloatingMenu()"
             @keydown.escape.window="closeFloatingMenu()"
             @scroll.window="closeFloatingMenu()"
             @resize.window="closeFloatingMenu()"
             class="fixed z-[10001] w-48 rounded-[6px] bg-[#13151A] border border-white/10 shadow-2xl py-1 text-[11px] select-none"
             :style="floatingMenuStyle"
             style="display: none;">
            
            <!-- 1. Edit Customer -->
            <button type="button"
                    @click="const c = floatingMenu.customer; closeFloatingMenu(); openEditModal(c)"
                    class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer">
                <span class="material-symbols-outlined text-[14px]">edit</span>
                <span>Edit Details</span>
            </button>

            <!-- 2. View Purchase History -->
            <a :href="floatingMenu.customer?.history_url"
               target="_blank"
               class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left no-underline cursor-pointer">
                <span class="material-symbols-outlined text-[14px]">receipt_long</span>
                <span>Purchase History</span>
            </a>

            <!-- 3. New Sale at POS -->
            <a :href="'/pos?customer_id=' + (floatingMenu.customer?.id || '')"
               class="px-3 py-1.5 text-[#d7c3ae] hover:text-white hover:bg-[#1E222A] flex items-center gap-2 w-full text-left no-underline cursor-pointer">
                <span class="material-symbols-outlined text-[14px]">point_of_sale</span>
                <span>New Sale for Customer</span>
            </a>

            <div class="my-1 border-t border-[#272C35]"></div>

            <!-- 4. Delete Customer -->
            <form :action="floatingMenu.customer?.delete_url"
                  method="POST"
                  onsubmit="return confirm('Are you sure you want to delete this customer record?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-3 py-1.5 text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 flex items-center gap-2 w-full text-left bg-transparent border-0 cursor-pointer">
                    <span class="material-symbols-outlined text-[14px]">delete</span>
                    <span>Delete Customer</span>
                </button>
            </form>
        </div>
    </template>

    <!-- ========================================================
         8. ADD & EDIT CUSTOMER MODALS
         ======================================================== -->
    
    <!-- Modal: Add Customer -->
    <template x-teleport="body">
        <div x-show="addModalOpen"
             @keydown.escape.window="addModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-md rounded-[8px] bg-[#13151A] border border-[#272C35] p-5 shadow-2xl flex flex-col gap-3.5">
                <form method="POST" action="{{ route('customers.store') }}">
                    @csrf
                    <div class="flex items-center justify-between border-b border-[#272C35] pb-3 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px] text-amber-accent">person_add</span>
                            <h4 class="m-0 text-[15px] font-bold text-white">Add New Customer</h4>
                        </div>
                        <button type="button" @click="addModalOpen = false" class="dashboard-control w-6 h-6 rounded flex items-center justify-center text-[#8A8A93] hover:text-white border-0 cursor-pointer">
                            <span class="material-symbols-outlined text-[15px]">close</span>
                        </button>
                    </div>

                    <div class="flex flex-col gap-3 text-[12px]">
                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Customer Name</label>
                            <input type="text" name="name" placeholder="Full name (optional)"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px]">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Phone Number</label>
                            <input type="tel" name="phone" placeholder="e.g. 0771234567"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px] font-mono">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Email Address</label>
                            <input type="email" name="email" placeholder="email@example.com"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px]">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Physical Address</label>
                            <textarea name="address" rows="2" placeholder="Street, city, postal code..."
                                      class="dashboard-control w-full p-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] resize-none"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 mt-4 border-t border-[#272C35]">
                        <button type="button" @click="addModalOpen = false" class="dashboard-control h-[32px] px-3 text-[11px] font-semibold text-[#d7c3ae] hover:text-white rounded-[6px]">
                            Cancel
                        </button>
                        <button type="submit" class="h-[32px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] border-0 cursor-pointer">
                            Save Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Modal: Edit Customer -->
    <template x-teleport="body">
        <div x-show="editModalOpen"
             @keydown.escape.window="editModalOpen = false"
             class="fixed inset-0 z-[10000] overflow-y-auto flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
             style="display: none;">
            <div class="w-full max-w-md rounded-[8px] bg-[#13151A] border border-[#272C35] p-5 shadow-2xl flex flex-col gap-3.5">
                <form :action="'/customers/' + (editingCustomer?.id || '')" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="flex items-center justify-between border-b border-[#272C35] pb-3 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px] text-amber-accent">edit</span>
                            <h4 class="m-0 text-[15px] font-bold text-white">Edit Customer</h4>
                        </div>
                        <button type="button" @click="editModalOpen = false" class="dashboard-control w-6 h-6 rounded flex items-center justify-center text-[#8A8A93] hover:text-white border-0 cursor-pointer">
                            <span class="material-symbols-outlined text-[15px]">close</span>
                        </button>
                    </div>

                    <div class="flex flex-col gap-3 text-[12px]">
                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Customer Name</label>
                            <input type="text" name="name" :value="editingCustomer?.name"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px]">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Phone Number</label>
                            <input type="tel" name="phone" :value="editingCustomer?.phone"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px] font-mono">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Email Address</label>
                            <input type="email" name="email" :value="editingCustomer?.email"
                                   class="dashboard-control w-full h-[36px] px-3 text-white bg-[#111419] rounded-[6px]">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-[#d7c3ae] mb-1">Physical Address</label>
                            <textarea name="address" rows="2" :value="editingCustomer?.address"
                                      class="dashboard-control w-full p-2.5 text-[11px] text-white bg-[#111419] rounded-[6px] resize-none"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 mt-4 border-t border-[#272C35]">
                        <button type="button" @click="editModalOpen = false" class="dashboard-control h-[32px] px-3 text-[11px] font-semibold text-[#d7c3ae] hover:text-white rounded-[6px]">
                            Cancel
                        </button>
                        <button type="submit" class="h-[32px] px-4 bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] border-0 cursor-pointer">
                            Update Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
function customersApp(config) {
    return {
        customers: config.initialCustomers || [],
        totalAllCount: config.totalAllCount || 0,
        searchQuery: '',
        typeFilter: '',
        tierFilter: '',
        drawerOpen: false,
        activeCustomer: null,

        // Modals
        addModalOpen: false,
        editModalOpen: false,
        editingCustomer: null,

        // Floating Action Menu State
        floatingMenu: {
            open: false,
            customer: null,
            top: 0,
            left: 0,
        },

        get floatingMenuStyle() {
            return `top: ${this.floatingMenu.top}px; left: ${this.floatingMenu.left}px;`;
        },

        get filteredCustomers() {
            let list = this.customers;
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase().trim();
                list = list.filter(c => {
                    const name = (c.name || '').toLowerCase();
                    const phone = (c.phone || '').toLowerCase();
                    const email = (c.email || '').toLowerCase();
                    return name.includes(q) || phone.includes(q) || email.includes(q);
                });
            }
            if (this.typeFilter) {
                list = list.filter(c => c.type === this.typeFilter);
            }
            if (this.tierFilter) {
                list = list.filter(c => c.tier === this.tierFilter);
            }
            return list;
        },

        openCustomerDrawer(customer) {
            this.activeCustomer = customer;
            this.drawerOpen = true;
        },

        openAddModal() {
            this.addModalOpen = true;
        },

        openEditModal(customer) {
            this.editingCustomer = customer;
            this.editModalOpen = true;
        },

        openFloatingMenu(event, customer) {
            const btn = event.currentTarget;
            const rect = btn.getBoundingClientRect();
            const menuWidth = 192; // 48rem / 192px
            const menuHeight = 150;

            let top = rect.bottom + 6;
            let left = rect.right - menuWidth;

            // Flip upward if overflowing bottom
            if (rect.bottom + menuHeight > window.innerHeight) {
                top = rect.top - menuHeight - 6;
            }

            // Clamp horizontal
            if (left < 10) left = 10;
            if (left + menuWidth > window.innerWidth - 10) {
                left = window.innerWidth - menuWidth - 10;
            }

            this.floatingMenu.top = top;
            this.floatingMenu.left = left;
            this.floatingMenu.customer = customer;
            this.floatingMenu.open = true;
        },

        closeFloatingMenu() {
            this.floatingMenu.open = false;
            this.floatingMenu.customer = null;
        },

        getTierDotClass(tier) {
            switch (tier) {
                case 'Platinum': return 'bg-slate-200';
                case 'Gold':     return 'bg-amber-400';
                case 'Silver':   return 'bg-slate-400';
                case 'Bronze':
                default:         return 'bg-amber-700';
            }
        },

        exportCustomersCsv() {
            const rows = this.filteredCustomers;
            if (!rows.length) {
                alert('No customer records to export.');
                return;
            }

            const headers = ['Name', 'Phone', 'Email', 'Tier', 'Type', 'Orders Count', 'Total Spent', 'Last Visit', 'Added On'];
            const csvRows = [headers.join(',')];

            rows.forEach(c => {
                const values = [
                    `"${c.name}"`,
                    `"${c.phone || ''}"`,
                    `"${c.email || ''}"`,
                    `"${c.tier}"`,
                    `"${c.type}"`,
                    c.orders_count,
                    c.total_spent.toFixed(2),
                    `"${c.last_visit_formatted || 'Never'}"`,
                    `"${c.created_at_formatted}"`,
                ];
                csvRows.push(values.join(','));
            });

            const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', `customers_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };
}
</script>
@endpush
