@props(['recentTransactions' => []])

<div class="dashboard-card lg:col-span-2 flex flex-col justify-between select-none box-border min-h-[300px] rounded-[8px] p-3.5 lg:p-4 bg-[#171A20] border border-[#272C35]">
    <!-- Card Header -->
    <div class="flex justify-between items-center mb-2">
        <h2 class="font-headline-md text-[14px] lg:text-[15px] text-on-surface m-0 font-semibold tracking-tight">Recent Transactions</h2>
        <a href="{{ route('pos.log') }}" class="text-primary font-label-caps text-[11px] uppercase hover:underline no-underline font-bold tracking-wider">View All</a>
    </div>

    <!-- Table showing 4 rows fully -->
    <div class="overflow-x-auto custom-scrollbar flex-1 flex flex-col justify-between">
        <table class="w-full text-left border-collapse text-body-sm">
            <thead>
                <tr class="border-b border-[#272C35] text-on-surface-variant font-label-caps text-[10px] uppercase">
                    <th class="py-1.5 px-2.5 font-bold tracking-wider">Bill #</th>
                    <th class="py-1.5 px-2.5 font-bold tracking-wider">Time</th>
                    <th class="py-1.5 px-2.5 font-bold tracking-wider">Customer / Items</th>
                    <th class="py-1.5 px-2.5 font-bold tracking-wider">Status</th>
                    <th class="py-1.5 px-2.5 font-bold tracking-wider text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="tx in ((metrics?.recent_transactions ?? @js($recentTransactions)).slice(0, 4))" :key="tx.id">
                    <tr @click="window.location.href = tx.invoice_url"
                        class="border-b border-[#272C35]/50 last:border-0 hover:bg-[#1E222A] transition-colors cursor-pointer group"
                        :class="tx.status === 'Pending' ? 'bg-amber-accent/5' : ''">
                        
                        <!-- Bill Number -->
                        <td class="py-2 px-2.5 font-metric-sm text-on-surface group-hover:text-amber-accent transition-colors tabular-nums font-bold text-[12px]"
                            x-text="tx.bill_number"></td>

                        <!-- Time -->
                        <td class="py-2 px-2.5 text-on-surface-variant text-[11px] tabular-nums"
                            x-text="tx.time"></td>

                        <!-- Customer & Items -->
                        <td class="py-2 px-2.5">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-surface-variant flex items-center justify-center text-[9px] font-bold text-on-surface uppercase flex-shrink-0"
                                      x-text="tx.customer_initials"></span>
                                <span class="text-on-surface truncate max-w-[180px] text-[12px] font-medium"
                                      x-text="tx.customer_name + ' · ' + tx.items_label"></span>
                            </div>
                        </td>

                        <!-- Status Chip -->
                        <td class="py-2 px-2">
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
                            <template x-if="tx.status !== 'Paid' && tx.status !== 'Pending' && tx.status !== 'Returned'">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-label-caps uppercase text-red-400 bg-red-400/10 border border-red-400/20 font-bold"
                                      x-text="tx.status">
                                </span>
                            </template>
                        </td>

                        <!-- Total Amount -->
                        <td class="py-2 px-2 font-metric-sm text-on-surface text-right tabular-nums font-bold text-[12px]"
                            x-text="tx.total_formatted"></td>
                    </tr>
                </template>

                <!-- Empty State -->
                <tr x-show="!((metrics?.recent_transactions ?? @js($recentTransactions)).length)">
                    <td colspan="5" class="py-6 text-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-[24px] opacity-30 block mb-1">receipt_long</span>
                        <div class="text-body-sm text-[11px]">No transactions in this period</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
