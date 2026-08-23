{{-- ============================================
     Reusable Customer Detail Slide-Over Drawer
     Teleported to <body> with pinned header/footer & min-h-0 scroll body
     ============================================ --}}
<template x-teleport="body">
    <div x-show="drawerOpen"
         @keydown.escape.window="drawerOpen = false"
         class="fixed inset-0 z-[9999] overflow-hidden flex justify-end"
         style="display: none;"
         role="dialog"
         aria-modal="true">

        <!-- Overlay / Backdrop -->
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

            <!-- Pinned Header -->
            <div class="shrink-0 p-4 border-b border-[#272C35] flex items-center justify-between bg-[#111419]">
                <div class="flex items-center gap-3">
                    <!-- Avatar -->
                    <div class="w-10 h-10 rounded-full bg-surface-variant flex items-center justify-center text-[13px] font-bold text-amber-accent border border-[#272C35] shrink-0"
                         x-text="(activeCustomer?.name || 'C').charAt(0).toUpperCase()"></div>

                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-[15px] text-white tracking-tight m-0 truncate max-w-[200px]"
                                x-text="activeCustomer?.name || 'Unnamed Customer'"></h3>
                            
                            <!-- Tier Badge -->
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-white/5 border border-white/10 text-white">
                                <span class="w-1.5 h-1.5 rounded-full" :class="getTierDotClass(activeCustomer?.tier)"></span>
                                <span x-text="activeCustomer?.tier"></span>
                            </span>
                        </div>
                        <p class="text-[11px] text-[#8A8A93] m-0"
                           x-text="(activeCustomer?.type || 'Retail') + ' &middot; Member since ' + (activeCustomer?.created_at_formatted || '—')"></p>
                    </div>
                </div>
                <button type="button"
                        @click="drawerOpen = false"
                        class="dashboard-control w-8 h-8 rounded-[4px] text-[#8A8A93] hover:text-white flex items-center justify-center cursor-pointer border-0"
                        title="Close drawer">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <!-- Scrollable Body (min-h-0 is required) -->
            <div class="flex-1 min-h-0 overflow-y-auto p-4 custom-scrollbar flex flex-col gap-4">

                <!-- 4-Stat Strip Grid -->
                <div class="grid grid-cols-2 gap-2">
                    <!-- Total Spent -->
                    <div class="p-3 rounded-[6px] bg-[#171A20] border border-[#272C35] flex flex-col">
                        <span class="text-[10px] uppercase font-bold text-[#8A8A93] tracking-wider mb-1">Total Spent</span>
                        <div class="font-['Inter'] font-bold text-[16px] text-amber-accent tabular-nums"
                             x-text="activeCustomer?.total_spent_formatted"></div>
                    </div>

                    <!-- Total Orders -->
                    <div class="p-3 rounded-[6px] bg-[#171A20] border border-[#272C35] flex flex-col">
                        <span class="text-[10px] uppercase font-bold text-[#8A8A93] tracking-wider mb-1">Total Orders</span>
                        <div class="font-['Inter'] font-bold text-[16px] text-white tabular-nums"
                             x-text="activeCustomer?.orders_count + (activeCustomer?.orders_count === 1 ? ' order' : ' orders')"></div>
                    </div>

                    <!-- Avg Basket -->
                    <div class="p-3 rounded-[6px] bg-[#171A20] border border-[#272C35] flex flex-col">
                        <span class="text-[10px] uppercase font-bold text-[#8A8A93] tracking-wider mb-1">Avg Basket</span>
                        <div class="font-['Inter'] font-bold text-[15px] text-[#d7c3ae] tabular-nums"
                             x-text="activeCustomer?.avg_basket_formatted"></div>
                    </div>

                    <!-- Last Visit -->
                    <div class="p-3 rounded-[6px] bg-[#171A20] border border-[#272C35] flex flex-col">
                        <span class="text-[10px] uppercase font-bold text-[#8A8A93] tracking-wider mb-1">Last Visit</span>
                        <div class="text-[12px] text-white font-medium truncate"
                             x-text="activeCustomer?.last_visit_formatted || 'Never'"></div>
                    </div>
                </div>

                <!-- Contact & Address Card -->
                <div class="p-3.5 rounded-[8px] bg-[#171A20] border border-[#272C35] flex flex-col gap-2.5 text-[12px]">
                    <span class="text-[10px] uppercase font-bold text-[#8A8A93] tracking-wider">Contact & Address</span>

                    <div class="flex flex-col gap-2 text-[11px]">
                        <!-- Phone with Call & WhatsApp -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5 text-[#8A8A93]">
                                <span class="material-symbols-outlined text-[14px]">phone</span>
                                <span>Phone:</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <template x-if="activeCustomer?.phone">
                                    <a :href="'tel:' + activeCustomer.phone" class="font-mono text-amber-accent hover:underline font-bold" x-text="activeCustomer.phone"></a>
                                </template>
                                <template x-if="!activeCustomer?.phone">
                                    <span class="text-[#8A8A93] italic">No phone</span>
                                </template>

                                <template x-if="activeCustomer?.whatsapp_url">
                                    <a :href="activeCustomer.whatsapp_url" target="_blank" class="text-emerald-400 hover:text-emerald-300" title="Chat on WhatsApp">
                                        <span class="material-symbols-outlined text-[15px]">chat</span>
                                    </a>
                                </template>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5 text-[#8A8A93]">
                                <span class="material-symbols-outlined text-[14px]">mail</span>
                                <span>Email:</span>
                            </div>
                            <template x-if="activeCustomer?.email">
                                <a :href="'mailto:' + activeCustomer.email" class="text-[#d7c3ae] hover:underline font-medium truncate max-w-[200px]" x-text="activeCustomer.email"></a>
                            </template>
                            <template x-if="!activeCustomer?.email">
                                <span class="text-[#8A8A93] italic">No email</span>
                            </template>
                        </div>

                        <!-- Address -->
                        <div class="flex flex-col gap-0.5 pt-1 border-t border-[#272C35]/50">
                            <div class="flex items-center gap-1.5 text-[#8A8A93]">
                                <span class="material-symbols-outlined text-[14px]">location_on</span>
                                <span>Address:</span>
                            </div>
                            <p class="text-white text-[11px] m-0 pl-5" x-text="activeCustomer?.address || 'No physical address recorded.'"></p>
                        </div>
                    </div>
                </div>

                <!-- Recent Purchase History -->
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between text-[11px] font-bold text-[#8A8A93] uppercase tracking-wider px-1">
                        <span>Recent Invoices</span>
                        <a :href="activeCustomer?.history_url"
                           target="_blank"
                           class="text-amber-accent hover:underline text-[10px] font-semibold flex items-center gap-0.5 no-underline">
                            <span>View All</span>
                            <span class="material-symbols-outlined text-[12px]">open_in_new</span>
                        </a>
                    </div>

                    <div class="rounded-[8px] bg-[#171A20] border border-[#272C35] overflow-hidden divide-y divide-[#272C35]/50">
                        <template x-for="sale in (activeCustomer?.recent_sales || [])" :key="sale.id">
                            <div class="p-2.5 flex items-center justify-between text-[12px]">
                                <div>
                                    <a :href="sale.url" target="_blank" class="font-mono text-amber-accent hover:underline font-bold block" x-text="sale.invoice_number"></a>
                                    <span class="text-[10px] text-[#8A8A93]" x-text="sale.date_formatted + ' · ' + sale.items_count + ' items'"></span>
                                </div>
                                <div class="text-right">
                                    <div class="font-['Inter'] font-bold text-white tabular-nums" x-text="sale.total_formatted"></div>
                                    <span class="text-[9px] uppercase font-bold text-emerald-400">Paid</span>
                                </div>
                            </div>
                        </template>

                        <template x-if="!activeCustomer?.recent_sales || activeCustomer?.recent_sales?.length === 0">
                            <div class="p-5 text-center text-[#8A8A93] text-[11px]">
                                No past purchase records for this customer.
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <!-- Pinned Footer Actions -->
            <div class="shrink-0 p-3.5 border-t border-[#272C35] bg-[#111419] flex items-center gap-2">
                <button type="button"
                        @click="const c = activeCustomer; drawerOpen = false; openEditModal(c)"
                        class="flex-1 h-[36px] bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95">
                    <span class="material-symbols-outlined text-[16px]">edit</span>
                    <span>Edit Customer</span>
                </button>

                <a :href="'/pos?customer_id=' + (activeCustomer?.id || '')"
                   class="dashboard-control h-[36px] px-3 text-[#d7c3ae] hover:text-white rounded-[6px] flex items-center justify-center gap-1 text-[11px] font-semibold uppercase tracking-wider no-underline">
                    <span class="material-symbols-outlined text-[16px]">point_of_sale</span>
                    <span>New Sale</span>
                </a>
            </div>

        </div>
    </div>
</template>
