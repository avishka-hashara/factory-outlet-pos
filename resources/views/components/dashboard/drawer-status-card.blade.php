@props(['drawerStatus' => []])

<div class="bg-card-surface border border-border-color rounded-card p-md flex flex-col justify-between relative overflow-hidden min-h-[160px]">
    <!-- Oversized 10%-opacity point_of_sale glyph bleeding off the bottom-right -->
    <div class="absolute right-0 bottom-0 opacity-10 pointer-events-none translate-x-1/4 translate-y-1/4 select-none">
        <span class="material-symbols-outlined text-[100px] text-amber-accent">point_of_sale</span>
    </div>

    <div class="relative z-10 flex justify-between items-start">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <h3 class="font-headline-md text-[16px] text-on-surface m-0 font-semibold">Drawer Status</h3>
                <span class="flex items-center gap-1 font-label-caps text-[9px] uppercase border px-1.5 py-0.5 rounded-sm font-bold"
                      :class="(metrics?.drawer_status?.is_open ?? false) ? 'text-green-400 border-green-400/20 bg-green-400/10' : 'text-red-400 border-red-400/20 bg-red-400/10'">
                    <span class="w-1.5 h-1.5 rounded-full"
                          :class="(metrics?.drawer_status?.is_open ?? false) ? 'bg-green-400' : 'bg-red-400'"></span>
                    <span x-text="metrics?.drawer_status?.status_label ?? 'Closed'">Closed</span>
                </span>
            </div>
            <div class="text-on-surface-variant text-[11px]" x-text="metrics?.drawer_status?.terminal_label ?? 'T01 - Main Branch'">
                T01 - Main Branch
            </div>
        </div>

        <div class="text-right">
            <div class="text-[10px] text-on-surface-variant font-label-caps uppercase mb-0.5 font-semibold">Expected Cash</div>
            <div class="font-display-metrics text-[24px] text-on-surface leading-none tabular-nums font-bold"
                 x-text="metrics?.drawer_status?.formatted_expected_cash ?? 'Rs. 0'">
                {{ $drawerStatus['formatted_expected_cash'] ?? 'Rs. 0' }}
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="relative z-10 flex gap-2 mt-4">
        <a href="{{ route('cash-drawer.index') }}"
           class="flex-1 h-8 flex items-center justify-center gap-1.5 rounded bg-dark-bg border border-border-color text-on-surface hover:bg-hover-surface hover:border-amber-accent transition-colors active:scale-95 font-label-caps text-[10px] uppercase font-bold no-underline cursor-pointer">
            <span class="material-symbols-outlined text-[14px]">sync_alt</span>
            <span>Drop/Payout</span>
        </a>
        <a href="{{ route('cash-drawer.index') }}"
           class="flex-1 h-8 flex items-center justify-center gap-1.5 rounded bg-surface-variant border border-border-color text-on-surface hover:bg-hover-surface hover:border-red-400 hover:text-red-400 transition-colors active:scale-95 font-label-caps text-[10px] uppercase font-bold no-underline cursor-pointer">
            <span class="material-symbols-outlined text-[14px]">lock_reset</span>
            <span>Close Shift</span>
        </a>
    </div>
</div>
