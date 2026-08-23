@props([
    'title' => '',
    'icon' => '',
    'value' => '',
    'delta' => null,
    'isPositive' => true,
    'comparisonLabel' => null,
    'isPrimary' => false,
    'subtext' => null,
])

<div class="dashboard-card group relative overflow-hidden flex flex-col justify-between select-none cursor-default transition-colors duration-200 box-border rounded-[8px] p-[10px] lg:p-[12px] bg-[#171A20] hover:bg-[#1E222A] border border-[#272C35]">
    @if($isPrimary)
        <!-- Top accent bar: 2px height, #F5A623 -->
        <div class="absolute top-0 left-0 w-full h-[2px] bg-[#F5A623] pointer-events-none z-0"></div>

        <!-- Corner glow: 64x64 blur 24px, #F5A623 5% -> 10% on hover -->
        <div class="absolute -right-[16px] -top-[16px] w-[64px] h-[64px] rounded-full bg-[rgba(245,166,35,0.05)] group-hover:bg-[rgba(245,166,35,0.10)] blur-[24px] pointer-events-none transition-colors duration-200 z-0"></div>
    @endif

    <!-- Row 1 (Label Row) -->
    <div class="relative z-10 flex justify-between items-start mb-[4px] lg:mb-[6px]">
        <span class="text-[10px] leading-[14px] font-semibold uppercase tracking-[0.05em] text-[#d7c3ae]">
            {{ $title }}
        </span>
        <span class="material-symbols-outlined text-[16px] {{ $isPrimary ? 'text-[#F5A623]' : 'text-[#d7c3ae]' }}"
              style="font-variation-settings: 'wght' 300, 'FILL' 0, 'opsz' 20;">
            {{ $icon }}
        </span>
    </div>

    <!-- Row 2 (Value Row) -->
    <div class="relative z-10 flex items-end justify-between gap-[8px]">
        <!-- Value (Left): Inter, tabular-nums -->
        <div class="font-['Inter'] text-[22px] sm:text-[24px] lg:text-[26px] xl:text-[28px] leading-tight tracking-[-0.02em] font-bold whitespace-nowrap tabular-nums {{ $isPrimary ? 'text-[#F5A623]' : 'text-[#FFFFFF]' }}">
            {{ $value }}
        </div>

        <!-- Delta / Subtext (Right) -->
        @if($delta !== null)
            <div class="flex flex-col items-end flex-shrink-0">
                <span class="flex items-center text-[12px] font-bold leading-none tabular-nums {{ $isPositive ? 'text-[#4ade80]' : 'text-[#f87171]' }}">
                    <span class="material-symbols-outlined text-[14px] mr-[2px]">
                        {{ $isPositive ? 'arrow_upward' : 'arrow_downward' }}
                    </span>
                    <span>{{ $delta }}</span>
                </span>
                @if($comparisonLabel)
                    <span class="text-[9px] font-semibold text-[#d7c3ae] uppercase mt-[2px] tracking-wider">
                        {{ $comparisonLabel }}
                    </span>
                @endif
            </div>
        @elseif($subtext)
            <div class="flex flex-col items-end flex-shrink-0">
                <span class="text-[10px] font-semibold text-[#d7c3ae] uppercase tracking-wider">
                    {{ $subtext }}
                </span>
            </div>
        @endif
    </div>
</div>
