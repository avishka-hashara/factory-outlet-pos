@props([
    'title' => '',
    'icon' => '',
    'value' => '',
    'delta' => '0%',
    'isPositive' => true,
    'comparisonLabel' => 'vs yday',
    'isPrimary' => false,
    'fieldKey' => '',
])

<div class="border rounded-card px-5 py-4 relative overflow-hidden flex flex-col justify-between hover:bg-hover-surface transition-colors cursor-default group h-[110px] min-h-[110px] max-h-[110px] box-border select-none"
     style="background: {{ $isPrimary ? 'radial-gradient(circle at top right, rgba(245, 166, 35, 0.08) 0%, transparent 65%), var(--card-surface)' : 'var(--card-surface)' }}; border-color: rgba(255, 255, 255, 0.06);">
    
    <!-- Top Label & Icon Row -->
    <div class="flex justify-between items-center relative z-10">
        <span class="font-label-caps text-[10px] uppercase text-on-surface-variant font-semibold tracking-wider">{{ $title }}</span>
        <span class="material-symbols-outlined text-[16px] {{ $isPrimary ? 'text-amber-accent' : 'text-on-surface-variant' }}">{{ $icon }}</span>
    </div>

    <!-- Bottom Value & Delta Row -->
    <div class="relative z-10 flex items-end justify-between gap-2">
        <!-- Value (~34px / weight 700 / nowrap) -->
        <div class="text-[34px] leading-[38px] font-[700] tabular-nums whitespace-nowrap truncate {{ $isPrimary ? 'text-amber-accent' : 'text-on-surface' }}"
             x-text="{{ $fieldKey }}?.formatted ?? '{{ $value }}'">
            {{ $value }}
        </div>

        <!-- Delta comparison pill -->
        <div class="flex flex-col items-end flex-shrink-0">
            <span class="flex items-center font-metric-sm text-[12px] leading-none tabular-nums font-bold"
                  :class="({{ $fieldKey }}?.is_positive ?? {{ $isPositive ? 'true' : 'false' }}) ? 'text-green-400' : 'text-red-400'">
                <span class="material-symbols-outlined text-[14px] mr-0.5"
                      x-text="({{ $fieldKey }}?.is_positive ?? {{ $isPositive ? 'true' : 'false' }}) ? 'arrow_upward' : 'arrow_downward'">
                    {{ $isPositive ? 'arrow_upward' : 'arrow_downward' }}
                </span>
                <span x-text="{{ $fieldKey }}?.delta ?? '{{ $delta }}'">{{ $delta }}</span>
            </span>
            <span class="font-label-caps text-[9px] text-on-surface-variant uppercase mt-0.5"
                  x-text="metrics?.summary?.comparison_label ?? '{{ $comparisonLabel }}'">
                {{ $comparisonLabel }}
            </span>
        </div>
    </div>
</div>
