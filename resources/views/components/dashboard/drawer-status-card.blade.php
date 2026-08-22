@props(['drawerStatus' => []])

<div class="bg-card-surface border rounded-card p-4 flex flex-col justify-between relative overflow-hidden select-none box-border flex-1 min-h-[125px]"
     style="border-color: rgba(255, 255, 255, 0.06);">
    
    <!-- Top Row: Drawer Status + OPEN badge + EXPECTED CASH -->
    <div class="relative z-10 flex justify-between items-start">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <h3 class="font-headline-md text-[14px] text-on-surface m-0 font-semibold tracking-tight">Drawer Status</h3>
                <span class="inline-flex items-center gap-1 font-label-caps text-[9px] uppercase px-2 py-0.5 rounded-full font-bold"
                      :class="(metrics?.drawer_status?.is_open ?? {{ ($drawerStatus['is_open'] ?? false) ? 'true' : 'false' }}) ? 'text-green-400 bg-green-400/10 border border-green-400/20' : 'text-red-400 bg-red-400/10 border border-red-400/20'">
                    <span class="w-1.5 h-1.5 rounded-full"
                          :class="(metrics?.drawer_status?.is_open ?? {{ ($drawerStatus['is_open'] ?? false) ? 'true' : 'false' }}) ? 'bg-green-400' : 'bg-red-400'"></span>
                    <span x-text="(metrics?.drawer_status?.is_open ?? {{ ($drawerStatus['is_open'] ?? false) ? 'true' : 'false' }}) ? 'OPEN' : 'CLOSED'">
                        {{ ($drawerStatus['is_open'] ?? false) ? 'OPEN' : 'CLOSED' }}
                    </span>
                </span>
            </div>
            <div class="text-on-surface-variant text-[11px] font-medium" x-text="metrics?.drawer_status?.terminal_label ?? '{{ $drawerStatus['terminal_label'] ?? 'T01 - Main Branch' }}'">
                {{ $drawerStatus['terminal_label'] ?? 'T01 - Main Branch' }}
            </div>
        </div>

        <div class="text-right">
            <div class="text-[9px] text-on-surface-variant font-label-caps uppercase mb-0.5 font-bold tracking-wider">Expected Cash</div>
            <div class="text-[28px] lg:text-[30px] leading-[32px] text-on-surface tabular-nums font-[700]"
                 x-text="metrics?.drawer_status?.formatted_expected_cash ?? '{{ $drawerStatus['formatted_expected_cash'] ?? 'Rs. 0' }}'">
                {{ $drawerStatus['formatted_expected_cash'] ?? 'Rs. 0' }}
            </div>
        </div>
    </div>

    <!-- Bottom Buttons: DROP/PAYOUT and CLOSE SHIFT -->
    <div class="relative z-10 flex gap-2 mt-3">
        <a href="{{ route('cash-drawer.index') }}"
           class="flex-1 h-[34px] flex items-center justify-center gap-1.5 rounded bg-[#13161c] border border-border-color/80 text-on-surface hover:bg-hover-surface hover:border-amber-accent transition-colors active:scale-95 font-label-caps text-[11px] uppercase tracking-[0.5px] font-bold no-underline cursor-pointer">
            <span class="material-symbols-outlined text-[15px]">swap_horiz</span>
            <span>Drop/Payout</span>
        </a>
        <a href="{{ route('cash-drawer.index') }}"
           class="flex-1 h-[34px] flex items-center justify-center gap-1.5 rounded bg-[#242b36] border border-[#3b4352] text-on-surface hover:bg-[#2e3745] hover:border-border-color transition-colors active:scale-95 font-label-caps text-[11px] uppercase tracking-[0.5px] font-bold no-underline cursor-pointer">
            <span class="material-symbols-outlined text-[15px]">lock</span>
            <span>Close Shift</span>
        </a>
    </div>
</div>
