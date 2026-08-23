{{-- ============================================
     Reusable Cash Drawer Session Slide-Over Drawer
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
             class="relative w-screen max-w-lg h-full bg-[#13151A] border-l border-[#272C35] shadow-2xl flex flex-col overflow-hidden z-10">

            <!-- Pinned Header -->
            <div class="shrink-0 p-4 border-b border-[#272C35] flex items-center justify-between bg-[#111419]">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-[22px] text-amber-accent">point_of_sale</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-mono text-[16px] font-bold text-white tracking-tight m-0"
                                x-text="'Session #' + (activeSession?.id || '')"></h3>
                            
                            <!-- Dynamic Status Pill -->
                            <template x-if="activeSession?.status === 'open'">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Open
                                </span>
                            </template>
                            <template x-if="activeSession?.status !== 'open'">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-slate-500/10 text-slate-400 border border-slate-500/25 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Closed
                                </span>
                            </template>
                        </div>
                        <p class="text-[11px] text-[#8A8A93] m-0"
                           x-text="(activeSession?.opened_at_formatted || '') + ' · ' + (activeSession?.duration || '')"></p>
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

                <!-- Loading State -->
                <div x-show="drawerLoading" class="py-12 text-center text-amber-accent">
                    <span class="material-symbols-outlined text-[32px] animate-spin">progress_activity</span>
                    <p class="text-[12px] text-[#8A8A93] mt-2">Loading session details…</p>
                </div>

                <div x-show="!drawerLoading" class="flex flex-col gap-4">
                    <!-- Session Staff & Timestamps Cluster -->
                    <div class="p-3 rounded-[6px] bg-[#171A20] border border-[#272C35] grid grid-cols-2 gap-3 text-[12px]">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[#8A8A93] block">Opened By</span>
                            <span class="text-white font-medium" x-text="activeSession?.opened_by_name || 'Staff'"></span>
                            <span class="text-[10px] text-[#8A8A93] block" x-text="activeSession?.opened_at_formatted"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[#8A8A93] block">Closed By</span>
                            <span class="text-white font-medium" x-text="activeSession?.closed_by_name || 'Still Open'"></span>
                            <span class="text-[10px] text-[#8A8A93] block" x-text="activeSession?.closed_at_formatted || '—'"></span>
                        </div>
                    </div>

                    <!-- Financial Reconciliation Card -->
                    <div class="p-3 rounded-[6px] bg-[#171A20] border border-[#272C35] flex flex-col gap-2.5 text-[12px]">
                        <span class="text-[10px] uppercase font-bold text-[#8A8A93] tracking-wider block">Financial Reconciliation</span>

                        <div class="grid grid-cols-2 gap-2 text-[11px] pb-2 border-b border-[#272C35]/50">
                            <div class="flex justify-between text-[#8A8A93]">
                                <span>Opening Balance:</span>
                                <span class="text-white font-medium tabular-nums" x-text="'Rs. ' + Number(activeSession?.opening_balance || 0).toLocaleString('en-US', {minimumFractionDigits:2})"></span>
                            </div>
                            <div class="flex justify-between text-[#8A8A93]">
                                <span>Cash Sales:</span>
                                <span class="text-emerald-400 font-medium tabular-nums" x-text="'+Rs. ' + Number(activeSession?.cash_sales || 0).toLocaleString('en-US', {minimumFractionDigits:2})"></span>
                            </div>
                            <div class="flex justify-between text-[#8A8A93]">
                                <span>Cash In:</span>
                                <span class="text-emerald-400 font-medium tabular-nums" x-text="'+Rs. ' + Number(activeSession?.total_cash_in || 0).toLocaleString('en-US', {minimumFractionDigits:2})"></span>
                            </div>
                            <div class="flex justify-between text-[#8A8A93]">
                                <span>Cash Out:</span>
                                <span class="text-rose-400 font-medium tabular-nums" x-text="'−Rs. ' + Number(activeSession?.total_cash_out || 0).toLocaleString('en-US', {minimumFractionDigits:2})"></span>
                            </div>
                            <div class="flex justify-between text-[#8A8A93]">
                                <span>Cash Refunds:</span>
                                <span class="text-rose-400 font-medium tabular-nums" x-text="'−Rs. ' + Number(activeSession?.cash_refunds || 0).toLocaleString('en-US', {minimumFractionDigits:2})"></span>
                            </div>
                        </div>

                        <!-- Expected vs Actual vs Variance Hero Row -->
                        <div class="grid grid-cols-3 gap-2 pt-1">
                            <div>
                                <span class="text-[10px] text-[#8A8A93] uppercase font-bold block">Expected</span>
                                <span class="text-[14px] font-bold text-amber-accent tabular-nums font-['Inter']"
                                      x-text="'Rs. ' + Number(activeSession?.expected_balance || 0).toLocaleString('en-US', {minimumFractionDigits:2})"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-[#8A8A93] uppercase font-bold block">Counted</span>
                                <span class="text-[14px] font-bold text-white tabular-nums font-['Inter']"
                                      x-text="activeSession?.closing_balance !== null ? 'Rs. ' + Number(activeSession?.closing_balance || 0).toLocaleString('en-US', {minimumFractionDigits:2}) : '—'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-[#8A8A93] uppercase font-bold block">Variance</span>
                                <template x-if="activeSession?.difference !== null && activeSession?.difference !== undefined">
                                    <span class="text-[14px] font-bold tabular-nums font-['Inter']"
                                          :class="Number(activeSession?.difference) === 0 ? 'text-emerald-400' : (Number(activeSession?.difference) > 0 ? 'text-amber-400' : 'text-rose-400')"
                                          x-text="(Number(activeSession?.difference) > 0 ? '+' : '') + 'Rs. ' + Number(activeSession?.difference).toLocaleString('en-US', {minimumFractionDigits:2})"></span>
                                </template>
                                <template x-if="activeSession?.difference === null || activeSession?.difference === undefined">
                                    <span class="text-[11px] text-[#8A8A93] italic">Unclosed</span>
                                </template>
                            </div>
                        </div>

                        <!-- Notes (if any) -->
                        <template x-if="activeSession?.close_notes || activeSession?.notes">
                            <div class="pt-2 border-t border-[#272C35]/50 text-[11px] text-[#d7c3ae] italic">
                                <template x-if="activeSession?.notes">
                                    <div><strong class="text-[#8A8A93] not-italic">Open Note:</strong> <span x-text="activeSession.notes"></span></div>
                                </template>
                                <template x-if="activeSession?.close_notes">
                                    <div class="mt-0.5"><strong class="text-[#8A8A93] not-italic">Close Note:</strong> <span x-text="activeSession.close_notes"></span></div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Transactions List -->
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between text-[11px] font-bold text-[#8A8A93] uppercase tracking-wider px-1">
                            <span>Session Transactions</span>
                            <span x-text="(activeSession?.transactions?.length || 0) + ' entries'"></span>
                        </div>

                        <div class="rounded-[6px] bg-[#171A20] border border-[#272C35] overflow-hidden divide-y divide-[#272C35]/50">
                            <template x-for="(tx, idx) in (activeSession?.transactions || [])" :key="idx">
                                <div class="p-2.5 flex items-center justify-between gap-2 text-[12px]">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <!-- Type Pill -->
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider"
                                                  :class="tx.type === 'cash_sale' || tx.type === 'cash_in' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                                                  x-text="tx.type.replace('_', ' ')"></span>
                                            <span class="text-white font-medium truncate" x-text="tx.description"></span>
                                        </div>
                                        <div class="text-[10px] text-[#8A8A93] mt-0.5"
                                             x-text="(tx.time || '') + ' · By ' + (tx.user?.name || tx.user_name || 'Staff')"></div>
                                    </div>

                                    <div class="text-right whitespace-nowrap">
                                        <div class="font-bold tabular-nums text-[12px]"
                                             :class="tx.type === 'cash_sale' || tx.type === 'cash_in' ? 'text-emerald-400' : 'text-rose-400'"
                                             x-text="(tx.type === 'cash_sale' || tx.type === 'cash_in' ? '+Rs. ' : '−Rs. ') + Number(tx.amount).toLocaleString('en-US', {minimumFractionDigits:2})"></div>
                                        <template x-if="tx.running_balance !== undefined">
                                            <div class="text-[10px] text-[#8A8A93] tabular-nums"
                                                 x-text="'Bal: Rs. ' + Number(tx.running_balance).toLocaleString('en-US', {minimumFractionDigits:2})"></div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!activeSession?.transactions || activeSession?.transactions?.length === 0">
                                <div class="p-6 text-center text-[#8A8A93] text-[11px]">
                                    No transactions recorded for this session.
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Pinned Footer Actions -->
            <div class="shrink-0 p-3.5 border-t border-[#272C35] bg-[#111419] flex items-center justify-between gap-2">
                <button type="button"
                        @click="drawerOpen = false"
                        class="dashboard-control h-[36px] px-4 text-[#d7c3ae] hover:text-white rounded-[6px] text-[11px] font-semibold uppercase tracking-wider cursor-pointer border-0">
                    Close
                </button>
            </div>

        </div>
    </div>
</template>
