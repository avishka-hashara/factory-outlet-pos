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

<div class="bg-card-surface border border-border-color rounded-card p-md relative overflow-hidden flex flex-col justify-between hover:bg-hover-surface transition-colors cursor-default group min-h-[108px]">
    @if($isPrimary)
        <!-- 2px Amber Top Edge and Blurred Amber Glow on KPI 1 Only -->
        <div class="absolute top-0 left-0 w-full h-[2px] bg-amber-accent"></div>
        <div class="absolute -right-4 -top-4 w-16 h-16 bg-amber-accent/5 rounded-full blur-xl group-hover:bg-amber-accent/10 transition-colors pointer-events-none"></div>
    @endif

    <div class="flex justify-between items-start mb-2 relative z-10">
        <span class="font-label-caps text-label-caps uppercase text-on-surface-variant tracking-wider">{{ $title }}</span>
        <span class="material-symbols-outlined text-[16px] {{ $isPrimary ? 'text-amber-accent' : 'text-on-surface-variant' }}">{{ $icon }}</span>
    </div>

    <div class="relative z-10 flex items-end justify-between">
        <!-- Display Value with tabular-nums -->
        <div class="font-display-metrics text-display-metrics leading-none tabular-nums {{ $isPrimary ? 'text-amber-accent' : 'text-on-surface' }}"
             x-text="{{ $fieldKey }}?.formatted ?? '{{ $value }}'">
            {{ $value }}
        </div>

        <!-- Delta comparison pill -->
        <div class="flex flex-col items-end">
            <span class="flex items-center font-metric-sm text-[12px] leading-none tabular-nums"
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
