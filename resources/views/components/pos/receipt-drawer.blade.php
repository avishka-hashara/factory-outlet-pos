{{-- ============================================
     Reusable POS & Sales Receipt Slide-Over Drawer
     Teleported to <body> with pinned header/footer & min-h-0 scrollable body
     ============================================ --}}
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

            <!-- Pinned Header -->
            <div class="shrink-0 p-4 border-b border-[#272C35] flex items-center justify-between bg-[#111419]">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-[20px] text-amber-accent">receipt</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-mono text-[16px] font-bold text-white tracking-tight m-0"
                                x-text="activeSale?.bill_number"></h3>
                            
                            <!-- Dynamic Status Pill in Drawer Header -->
                            <template x-if="activeSale?.status === 'Refunded'">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/25 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>Refunded
                                </span>
                            </template>
                            <template x-if="activeSale?.status === 'Partially Refunded'">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/25 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>Partial Refund
                                </span>
                            </template>
                            <template x-if="activeSale?.status === 'Void'">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-slate-500/10 text-slate-400 border border-slate-500/25 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Void
                                </span>
                            </template>
                            <template x-if="!activeSale?.status || activeSale?.status === 'Paid'">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Paid
                                </span>
                            </template>
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

            <!-- Scrollable Body (min-h-0 is strictly required) -->
            <div class="flex-1 min-h-0 overflow-y-auto p-4 custom-scrollbar flex flex-col gap-4">

                <!-- Staff & Payment Cluster -->
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

                <!-- Refund History (if any) -->
                <template x-if="activeSale?.returns && activeSale.returns.length > 0">
                    <div class="p-3 rounded-[6px] bg-rose-500/5 border border-rose-500/20 text-[12px]">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] uppercase font-bold text-rose-400 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[13px]">history</span>
                                Refund History
                            </span>
                            <span class="text-[11px] font-bold text-rose-400 tabular-nums"
                                  x-text="activeSale?.total_refunded_formatted ? '- ' + activeSale.total_refunded_formatted : ''"></span>
                        </div>
                        <div class="flex flex-col gap-1.5 text-[11px] text-[#d7c3ae]">
                            <template x-for="ret in activeSale.returns" :key="ret.id">
                                <div class="flex items-center justify-between pt-1 border-t border-rose-500/10">
                                    <div>
                                        <span class="text-white font-medium" x-text="ret.date"></span>
                                        <span class="text-[#8A8A93]" x-text="' · ' + (ret.reason || 'Refund')"></span>
                                    </div>
                                    <span class="text-rose-400 font-semibold tabular-nums" x-text="'Rs. ' + Number(ret.total_refunded).toLocaleString('en-US', {minimumFractionDigits:2})"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Itemized Products List -->
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between text-[11px] font-bold text-[#8A8A93] uppercase tracking-wider px-1">
                        <span>Itemized Products</span>
                        <span x-text="activeSale?.items_count + (activeSale?.items_count === 1 ? ' item' : ' items')"></span>
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

                <!-- Order / Staff Notes (if present) -->
                <template x-if="activeSale?.notes">
                    <div class="p-3 rounded-[6px] bg-[#171A20] border border-[#272C35] text-[12px]">
                        <span class="text-[10px] uppercase font-bold text-[#8A8A93] block mb-1">Staff Notes</span>
                        <p class="text-[#d7c3ae] text-[11px] italic m-0" x-text="activeSale?.notes"></p>
                    </div>
                </template>

            </div>

            <!-- Pinned Footer Actions -->
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
