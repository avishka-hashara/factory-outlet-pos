@props(['stockAlerts' => []])

<div class="dashboard-card flex flex-col justify-between select-none box-border rounded-[8px] p-3.5 lg:p-4 bg-[#171A20] border border-[#272C35] min-h-[145px]">
    <div class="flex justify-between items-center mb-2">
        <div class="flex items-center gap-2">
            <h2 class="font-headline-md text-[13px] lg:text-[14px] text-on-surface m-0 font-semibold tracking-tight">Stock Alerts</h2>
            <span class="bg-red-500/15 text-red-400 border border-red-500/25 text-[10px] font-bold px-2 py-0.5 rounded-full leading-none tabular-nums"
                  x-text="metrics?.stock_alerts?.critical_label ?? '{{ $stockAlerts['critical_label'] ?? '0 Critical' }}'">
                {{ $stockAlerts['critical_label'] ?? '0 Critical' }}
            </span>
        </div>
        <a href="{{ route('products.index', ['stock' => 'low']) }}" class="text-primary font-label-caps text-[11px] uppercase hover:underline no-underline font-bold tracking-wider">Inv</a>
    </div>

    <div class="flex flex-col gap-1.5">
        <template x-for="item in ((metrics?.stock_alerts?.items ?? @js($stockAlerts['items'] ?? [])).slice(0, 2))" :key="item.id">
            <div class="flex items-center justify-between py-1.5 border-b border-[#272C35]/50 last:border-0 group hover:bg-[#1E222A] rounded px-1.5 transition-colors">
                <div class="flex items-center gap-2.5 min-w-0">
                    <!-- Thumbnail or fallback -->
                    <div class="w-7 h-7 rounded bg-surface-variant border border-[#272C35] flex items-center justify-center overflow-hidden flex-shrink-0">
                        <template x-if="item.image_url">
                            <img :src="item.image_url" :alt="item.name" class="w-full h-full object-cover" loading="lazy">
                        </template>
                        <template x-if="!item.image_url">
                            <span class="material-symbols-outlined text-on-surface-variant text-[15px]">inventory_2</span>
                        </template>
                    </div>
                    <div class="min-w-0">
                        <div class="font-metric-sm text-[12px] text-on-surface leading-tight group-hover:text-amber-accent transition-colors truncate font-medium"
                             x-text="item.name"></div>
                        <div class="font-body-sm text-[10px] text-on-surface-variant tabular-nums truncate mt-0.5"
                             x-text="'SKU: ' + item.sku"></div>
                    </div>
                </div>

                <div class="font-metric-sm text-[13px] tabular-nums font-bold flex-shrink-0 ml-2 whitespace-nowrap"
                     :class="item.stock <= 0 ? 'text-red-500' : 'text-red-400'">
                    <span x-text="item.stock"></span>
                    <span class="text-[9px] font-normal text-on-surface-variant ml-0.5">left</span>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <template x-if="!(metrics?.stock_alerts?.items ?? @js($stockAlerts['items'] ?? []))?.length">
            <div class="text-center py-2 text-on-surface-variant flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-green-400 text-[18px]">check_circle</span>
                <div class="text-[11px]">All products are well stocked</div>
            </div>
        </template>
    </div>
</div>
