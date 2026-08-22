@props(['stockAlerts' => []])

<div class="bg-card-surface border border-border-color rounded-card p-md flex flex-col min-h-[160px]">
    <div class="flex justify-between items-center mb-2">
        <div class="flex items-center gap-2">
            <h2 class="font-headline-md text-[16px] text-on-surface m-0 font-semibold">Stock Alerts</h2>
            <span class="bg-red-500/20 text-red-400 border border-red-500/30 text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none tabular-nums"
                  x-text="metrics?.stock_alerts?.critical_label ?? '0 Critical'">
                {{ $stockAlerts['critical_label'] ?? '0 Critical' }}
            </span>
        </div>
        <a href="{{ route('products.index', ['stock' => 'low']) }}" class="text-primary font-label-caps text-label-caps uppercase hover:underline no-underline font-bold">Inv</a>
    </div>

    <div class="flex flex-col gap-1.5 overflow-y-auto custom-scrollbar pr-1 max-h-[140px]">
        <template x-for="item in (metrics?.stock_alerts?.items ?? [])" :key="item.id">
            <div class="flex items-center justify-between py-1.5 border-b border-border-color/50 group hover:bg-hover-surface/50 rounded px-1 transition-colors">
                <div class="flex items-center gap-2 min-w-0">
                    <!-- Thumbnail or fallback -->
                    <div class="w-8 h-8 rounded bg-surface-variant border border-border-color flex items-center justify-center overflow-hidden flex-shrink-0">
                        <template x-if="item.image_url">
                            <img :src="item.image_url" :alt="item.name" class="w-full h-full object-cover" loading="lazy">
                        </template>
                        <template x-if="!item.image_url">
                            <span class="material-symbols-outlined text-on-surface-variant text-[16px]">inventory_2</span>
                        </template>
                    </div>
                    <div class="min-w-0">
                        <div class="font-metric-sm text-[12px] text-on-surface leading-tight group-hover:text-amber-accent transition-colors truncate"
                             x-text="item.name"></div>
                        <div class="font-body-sm text-[10px] text-on-surface-variant tabular-nums truncate"
                             x-text="'SKU: ' + item.sku"></div>
                    </div>
                </div>

                <div class="font-metric-sm text-[14px] tabular-nums font-bold flex-shrink-0 ml-2"
                     :class="item.stock <= 0 ? 'text-red-500' : 'text-red-400'">
                    <span x-text="item.stock"></span>
                    <span class="text-[10px] font-normal text-on-surface-variant">left</span>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <template x-if="!metrics?.stock_alerts?.items?.length">
            <div class="text-center py-4 text-on-surface-variant">
                <span class="material-symbols-outlined text-green-400 text-[24px] block mb-1">check_circle</span>
                <div class="text-[12px]">All products are well stocked</div>
            </div>
        </template>
    </div>
</div>
