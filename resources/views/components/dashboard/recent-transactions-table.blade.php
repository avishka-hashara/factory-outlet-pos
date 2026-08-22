@props(['recentTransactions' => []])

<div class="lg:col-span-7 bg-card-surface border border-border-color rounded-card p-md flex flex-col min-h-[300px]">
    <div class="flex justify-between items-center mb-3">
        <h2 class="font-headline-md text-[16px] text-on-surface m-0 font-semibold">Recent Transactions</h2>
        <a href="{{ route('pos.log') }}" class="text-primary font-label-caps text-label-caps uppercase hover:underline no-underline font-bold">View All</a>
    </div>

    <div class="overflow-x-auto custom-scrollbar flex-1">
        <table class="w-full text-left border-collapse text-body-sm">
            <thead>
                <tr class="border-b border-border-color text-on-surface-variant font-label-caps text-[10px] uppercase bg-dark-bg/50">
                    <th class="py-2 px-2 font-semibold">Bill #</th>
                    <th class="py-2 px-2 font-semibold">Time</th>
                    <th class="py-2 px-2 font-semibold">Customer / Items</th>
                    <th class="py-2 px-2 font-semibold">Status</th>
                    <th class="py-2 px-2 font-semibold text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="tx in (metrics?.recent_transactions ?? [])" :key="tx.id">
                    <tr @click="window.location.href = tx.invoice_url"
                        class="border-b border-border-color hover:bg-hover-surface transition-colors cursor-pointer group"
                        :class="tx.status === 'Pending' ? 'bg-amber-accent/5' : ''">
                        
                        <!-- Bill Number -->
                        <td class="py-2.5 px-2 font-metric-sm text-on-surface group-hover:text-amber-accent transition-colors tabular-nums font-bold"
                            x-text="tx.bill_number"></td>

                        <!-- Time -->
                        <td class="py-2.5 px-2 text-on-surface-variant text-[11px] tabular-nums"
                            x-text="tx.time"></td>

                        <!-- Customer & Items -->
                        <td class="py-2.5 px-2">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-surface-variant flex items-center justify-center text-[9px] font-bold text-on-surface uppercase flex-shrink-0"
                                      x-text="tx.customer_initials"></span>
                                <span class="text-on-surface truncate max-w-[180px]"
                                      x-text="tx.customer_name + ' · ' + tx.items_label"></span>
                            </div>
                        </td>

                        <!-- Status Chip -->
                        <td class="py-2.5 px-2">
                            <template x-if="tx.status === 'Paid'">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-label-caps uppercase text-green-400 bg-green-400/10 border border-green-400/20 font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>Paid
                                </span>
                            </template>
                            <template x-if="tx.status === 'Pending'">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-label-caps uppercase text-amber-accent bg-amber-accent/10 border border-amber-accent/20 font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-accent animate-pulse"></span>Pending
                                </span>
                            </template>
                            <template x-if="tx.status === 'Returned'">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-label-caps uppercase text-amber-300 bg-amber-300/10 border border-amber-300/20 font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-300"></span>Refunded
                                </span>
                            </template>
                        </td>

                        <!-- Total Amount -->
                        <td class="py-2.5 px-2 font-metric-sm text-on-surface text-right tabular-nums font-bold"
                            x-text="tx.total_formatted"></td>
                    </tr>
                </template>

                <!-- Empty State -->
                <tr x-show="!metrics?.recent_transactions?.length">
                    <td colspan="5" class="py-8 text-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-[32px] opacity-30 block mb-1">receipt_long</span>
                        <div class="text-body-sm">No transactions found</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
