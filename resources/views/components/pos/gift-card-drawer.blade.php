{{-- ============================================
     Reusable Gift Card Detail Slide-Over Drawer
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
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-[22px] text-amber-accent">card_giftcard</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-mono text-[16px] font-bold text-white tracking-tight m-0"
                                x-text="activeCard?.card_number"></h3>
                            
                            <!-- Dynamic Status Pill -->
                            <template x-if="activeCard?.live_status === 'available'">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-slate-500/10 text-slate-300 border border-slate-500/25">
                                    In Stock
                                </span>
                            </template>
                            <template x-if="activeCard?.live_status === 'sold'">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-sky-500/10 text-sky-400 border border-sky-500/25">
                                    Sold
                                </span>
                            </template>
                            <template x-if="activeCard?.live_status === 'gifted'">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-purple-500/10 text-purple-400 border border-purple-500/25">
                                    Gifted
                                </span>
                            </template>
                            <template x-if="activeCard?.live_status === 'redeemed'">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
                                    Redeemed
                                </span>
                            </template>
                            <template x-if="activeCard?.live_status === 'expired'">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/25">
                                    Expired
                                </span>
                            </template>
                            <template x-if="activeCard?.live_status === 'cancelled'">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-slate-600/20 text-slate-400 border border-slate-600/30 line-through">
                                    Void
                                </span>
                            </template>
                        </div>
                        <p class="text-[11px] text-[#8A8A93] m-0"
                           x-text="'Barcode: ' + (activeCard?.barcode || '—')"></p>
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

                <!-- Digital Gift Card Mockup -->
                <div class="rounded-[12px] p-5 border border-amber-500/30 shadow-xl relative overflow-hidden flex flex-col justify-between"
                     style="background: linear-gradient(135deg, #1e1b18 0%, #12141a 60%, #1a1612 100%); min-height: 160px;">
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px] text-amber-accent">card_giftcard</span>
                            <span class="font-bold text-[11px] tracking-wider uppercase text-amber-accent">STORE GIFT CARD</span>
                        </div>
                        <span class="font-mono text-[10px] text-[#8A8A93]" x-text="activeCard?.barcode"></span>
                    </div>

                    <div class="my-2">
                        <span class="text-[10px] uppercase font-bold text-[#8A8A93] block">Card Face Value</span>
                        <div class="font-['Inter'] font-bold text-[26px] text-white tabular-nums tracking-tight"
                             x-text="activeCard?.value_formatted"></div>
                    </div>

                    <div class="flex items-end justify-between text-[11px] text-[#d7c3ae] border-t border-white/10 pt-2 font-mono">
                        <span x-text="activeCard?.card_number"></span>
                        <template x-if="activeCard?.expiry_formatted">
                            <span class="text-[10px] text-[#8A8A93]" x-text="'EXP: ' + activeCard.expiry_formatted"></span>
                        </template>
                    </div>
                </div>

                <!-- Balances Breakdown Card -->
                <div class="p-3.5 rounded-[8px] bg-[#171A20] border border-[#272C35] flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-[#8A8A93] tracking-wider">Remaining Balance</span>
                        <span class="text-[11px] font-bold tabular-nums"
                              :class="activeCard?.balance > 0 ? 'text-emerald-400' : 'text-[#8A8A93]'"
                              x-text="activeCard?.balance > 0 ? 'Active Credit' : 'Fully Spent'"></span>
                    </div>

                    <div class="font-['Inter'] font-bold text-[22px] text-white tabular-nums"
                         x-text="activeCard?.balance_formatted"></div>

                    <!-- Progress Bar (Redeemed vs Total) -->
                    <div class="w-full h-1.5 bg-[#111419] rounded-full overflow-hidden border border-[#272C35] mt-1">
                        <div class="h-full bg-amber-accent rounded-full transition-all duration-300"
                             :style="`width: ${activeCard?.value > 0 ? Math.min(100, Math.max(0, (activeCard?.balance / activeCard?.value) * 100)) : 0}%`"></div>
                    </div>
                </div>

                <!-- Card Details & Status -->
                <div class="p-3.5 rounded-[8px] bg-[#171A20] border border-[#272C35] flex flex-col gap-2.5 text-[12px]">
                    <span class="text-[10px] uppercase font-bold text-[#8A8A93] tracking-wider">Card Details</span>

                    <div class="grid grid-cols-2 gap-2 text-[11px]">
                        <div>
                            <span class="text-[#8A8A93] block">Status:</span>
                            <span class="text-white font-medium capitalize" x-text="activeCard?.live_status"></span>
                        </div>
                        <div>
                            <span class="text-[#8A8A93] block">Expiry Date:</span>
                            <span class="text-white font-medium" x-text="activeCard?.expiry_formatted || 'No Expiry'"></span>
                        </div>
                        <div>
                            <span class="text-[#8A8A93] block">Issued Date:</span>
                            <span class="text-white font-medium" x-text="activeCard?.issued_formatted || 'Not Issued'"></span>
                        </div>
                        <div>
                            <span class="text-[#8A8A93] block">Redeemed Date:</span>
                            <span class="text-white font-medium" x-text="activeCard?.redeemed_formatted || 'Not Redeemed'"></span>
                        </div>
                    </div>

                    <!-- Linked Invoices -->
                    <template x-if="activeCard?.issued_sale_invoice">
                        <div class="pt-2 border-t border-[#272C35]/60 flex items-center justify-between text-[11px]">
                            <span class="text-[#8A8A93]">Sold on Sale:</span>
                            <a :href="activeCard?.issued_sale_url"
                               target="_blank"
                               class="font-mono text-amber-accent hover:underline font-bold flex items-center gap-1">
                                <span x-text="activeCard?.issued_sale_invoice"></span>
                                <span class="material-symbols-outlined text-[12px]">open_in_new</span>
                            </a>
                        </div>
                    </template>

                    <template x-if="activeCard?.redeemed_sale_invoice">
                        <div class="pt-1.5 flex items-center justify-between text-[11px]">
                            <span class="text-[#8A8A93]">Redeemed on Sale:</span>
                            <a :href="activeCard?.redeemed_sale_url"
                               target="_blank"
                               class="font-mono text-emerald-400 hover:underline font-bold flex items-center gap-1">
                                <span x-text="activeCard?.redeemed_sale_invoice"></span>
                                <span class="material-symbols-outlined text-[12px]">open_in_new</span>
                            </a>
                        </div>
                    </template>
                </div>

            </div>

            <!-- Pinned Footer Actions -->
            <div class="shrink-0 p-3.5 border-t border-[#272C35] bg-[#111419] flex items-center gap-2">
                <button type="button"
                        @click="printCard(activeCard)"
                        class="flex-1 h-[36px] bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95">
                    <span class="material-symbols-outlined text-[16px]">print</span>
                    <span>Print Card</span>
                </button>

                <template x-if="activeCard?.live_status === 'available'">
                    <a :href="`/gift-cards/${activeCard?.id}/sell`"
                       class="dashboard-control h-[36px] px-3 text-[#d7c3ae] hover:text-white rounded-[6px] flex items-center justify-center gap-1 text-[11px] font-semibold uppercase tracking-wider no-underline">
                        <span class="material-symbols-outlined text-[16px]">point_of_sale</span>
                        <span>Sell at POS</span>
                    </a>
                </template>
            </div>

        </div>
    </div>
</template>
