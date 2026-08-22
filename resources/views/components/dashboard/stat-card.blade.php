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

<div class="dashboard-card group relative overflow-hidden flex flex-col justify-between select-none cursor-default transition-colors duration-200 box-border rounded-[8px] p-[10px] lg:p-[12px] bg-[#171A20] hover:bg-[#1E222A] border border-[#272C35]">
    
    @if($isPrimary)
        <!-- a) Top accent bar: 2px height, #F5A623 -->
        <div class="absolute top-0 left-0 w-full h-[2px] bg-[#F5A623] pointer-events-none z-0"></div>

        <!-- b) Corner glow: 64x64 blur 24px, #F5A623 5% -> 10% on hover -->
        <div class="absolute -right-[16px] -top-[16px] w-[64px] h-[64px] rounded-full bg-[rgba(245,166,35,0.05)] group-hover:bg-[rgba(245,166,35,0.10)] blur-[24px] pointer-events-none transition-colors duration-200 z-0"></div>
    @endif

    <!-- Row 1 (label row) — flex, justify-between, align-items: flex-start -->
    <div class="relative z-10 flex justify-between items-start mb-[4px] lg:mb-[6px]">
        <span class="text-[10px] leading-[14px] font-semibold uppercase tracking-[0.05em] text-[#d7c3ae]">
            {{ $title }}
        </span>
        <span class="material-symbols-outlined text-[16px] {{ $isPrimary ? 'text-[#F5A623]' : 'text-[#d7c3ae]' }}"
              style="font-variation-settings: 'wght' 300, 'FILL' 0, 'opsz' 20;">
            {{ $icon }}
        </span>
    </div>

    <!-- Row 2 (value row) — flex, align-items: FLEX-END, justify-between -->
    <div class="relative z-10 flex items-end justify-between gap-[8px]">
        <!-- Value (left): Inter, responsive font-size, tabular-nums -->
        <div class="font-['Inter'] text-[26px] sm:text-[28px] lg:text-[30px] xl:text-[34px] leading-tight tracking-[-0.02em] font-bold whitespace-nowrap tabular-nums {{ $isPrimary ? 'text-[#F5A623]' : 'text-[#FFFFFF]' }}"
             x-text="{{ $fieldKey }}?.formatted ?? '{{ $value }}'">
            {{ $value }}
        </div>

        <!-- Delta block (right): flex column, align-items: flex-end -->
        <div class="flex flex-col items-end flex-shrink-0">
            <span class="flex items-center text-[12px] font-bold leading-none tabular-nums"
                  :class="({{ $fieldKey }}?.is_positive ?? {{ $isPositive ? 'true' : 'false' }}) ? 'text-[#4ade80]' : 'text-[#f87171]'">
                <span class="material-symbols-outlined text-[14px] mr-[2px]"
                      x-text="({{ $fieldKey }}?.is_positive ?? {{ $isPositive ? 'true' : 'false' }}) ? 'arrow_upward' : 'arrow_downward'">
                    {{ $isPositive ? 'arrow_upward' : 'arrow_downward' }}
                </span>
                <span x-text="{{ $fieldKey }}?.delta ?? '{{ $delta }}'">{{ $delta }}</span>
            </span>
            <span class="text-[9px] font-semibold text-[#d7c3ae] uppercase mt-[2px] tracking-wider"
                  x-text="metrics?.summary?.comparison_label ?? '{{ $comparisonLabel }}'">
                {{ $comparisonLabel }}
            </span>
        </div>
    </div>
</div>
