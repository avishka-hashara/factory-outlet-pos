@props(['paymentMix' => []])

@php
    $methods = $paymentMix['methods'] ?? [];
    $total = (float) ($paymentMix['total_amount'] ?? 0);
    $dominantMethod = $paymentMix['dominant_method'] ?? 'KOKO';
    $dominantPct = $paymentMix['dominant_percentage'] ?? 0;

    $circumference = 2 * M_PI * 38; // ~238.76 for r=38
    $gap = 4; // gap between segments

    $seg1 = $methods[0] ?? ['label' => 'KOKO', 'percentage' => 45.7, 'color' => '#F5A623', 'formatted_amount' => 'Rs.58K'];
    $seg2 = $methods[1] ?? ['label' => 'CARD', 'percentage' => 28.5, 'color' => '#dae3f3', 'formatted_amount' => 'Rs.36K'];
    $seg3 = $methods[2] ?? ['label' => 'CASH', 'percentage' => 21.1, 'color' => '#524534', 'formatted_amount' => 'Rs.27K'];

    $pct1 = (float) ($seg1['percentage'] ?? 0);
    $pct2 = (float) ($seg2['percentage'] ?? 0);
    $pct3 = (float) ($seg3['percentage'] ?? 0);

    $len1 = max(0, ($pct1 / 100) * $circumference - ($pct1 > 0 ? $gap : 0));
    $len2 = max(0, ($pct2 / 100) * $circumference - ($pct2 > 0 ? $gap : 0));
    $len3 = max(0, ($pct3 / 100) * $circumference - ($pct3 > 0 ? $gap : 0));

    $rot1 = 0;
    $rot2 = ($pct1 / 100) * 360;
    $rot3 = (($pct1 + $pct2) / 100) * 360;
@endphp

<div class="lg:col-span-4 flex flex-col justify-between min-h-[310px] select-none box-border rounded-[8px] p-3.5 lg:p-4 bg-[#171A20] border-0"
     style="background-color: #171A20; border: none; outline: none; box-shadow: none;">
    
    <!-- Card Header -->
    <div class="flex justify-between items-center mb-1">
        <h2 class="font-headline-md text-[14px] lg:text-[15px] text-on-surface m-0 font-semibold tracking-tight">Payment Mix</h2>
        <button class="text-on-surface-variant hover:text-amber-accent transition-colors p-0 border-0 bg-transparent cursor-pointer" title="Options">
            <span class="material-symbols-outlined text-[18px]">more_vert</span>
        </button>
    </div>

    <!-- Center Donut -->
    <div class="flex flex-col items-center justify-center relative my-auto py-2">
        <div class="w-[155px] h-[155px] relative flex items-center justify-center">
            <svg class="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 100 100">
                <!-- Background track circle -->
                <circle cx="50" cy="50" r="38" fill="transparent" stroke="#222b36" stroke-width="10"></circle>

                <!-- Arc 1 (Primary - Orange KOKO) -->
                <circle cx="50" cy="50" r="38"
                        fill="transparent"
                        :stroke="donutSegment1.color"
                        stroke-width="10"
                        stroke-linecap="round"
                        :stroke-dasharray="donutSegment1.dashArray"
                        :transform="donutSegment1.transform"
                        stroke="{{ $seg1['color'] ?? '#F5A623' }}"
                        stroke-dasharray="{{ number_format($len1, 2, '.', '') }} {{ number_format($circumference - $len1, 2, '.', '') }}"
                        transform="rotate({{ number_format($rot1, 1, '.', '') }} 50 50)"
                        class="transition-all duration-700 ease-out"></circle>

                <!-- Arc 2 (Secondary - Light Gray/White CARD) -->
                <circle cx="50" cy="50" r="38"
                        fill="transparent"
                        :stroke="donutSegment2.color"
                        stroke-width="10"
                        stroke-linecap="round"
                        :stroke-dasharray="donutSegment2.dashArray"
                        :transform="donutSegment2.transform"
                        stroke="{{ $seg2['color'] ?? '#dae3f3' }}"
                        stroke-dasharray="{{ number_format($len2, 2, '.', '') }} {{ number_format($circumference - $len2, 2, '.', '') }}"
                        transform="rotate({{ number_format($rot2, 1, '.', '') }} 50 50)"
                        class="transition-all duration-700 ease-out"></circle>

                <!-- Arc 3 (Tertiary - Dark Slate CASH) -->
                <circle cx="50" cy="50" r="38"
                        fill="transparent"
                        :stroke="donutSegment3.color"
                        stroke-width="10"
                        stroke-linecap="round"
                        :stroke-dasharray="donutSegment3.dashArray"
                        :transform="donutSegment3.transform"
                        stroke="{{ $seg3['color'] ?? '#524534' }}"
                        stroke-dasharray="{{ number_format($len3, 2, '.', '') }} {{ number_format($circumference - $len3, 2, '.', '') }}"
                        transform="rotate({{ number_format($rot3, 1, '.', '') }} 50 50)"
                        class="transition-all duration-700 ease-out"></circle>
            </svg>

            <!-- Center Label: percentage at 26px bold, method name below at 11px muted -->
            <div class="text-center z-10 select-none flex flex-col items-center justify-center">
                <div class="text-[26px] leading-none font-[700] text-on-surface tabular-nums"
                     x-text="donutDominantPercentage + '%'">
                    {{ $dominantPct }}%
                </div>
                <div class="font-label-caps text-[11px] text-on-surface-variant uppercase font-semibold mt-1 tracking-wider"
                     x-text="donutDominantMethod">
                    {{ $dominantMethod }}
                </div>
            </div>
        </div>
    </div>

    <!-- 3-Column Legend with colored dots and amounts -->
    <div class="w-full grid grid-cols-3 gap-1 text-center border-t border-[#272C35] pt-2.5 mt-auto">
        <!-- Col 1 -->
        <div class="flex flex-col items-center gap-0.5">
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full" :style="'background-color:' + (metrics?.payment_mix?.methods?.[0]?.color ?? '{{ $seg1['color'] }}')"></span>
                <span class="font-label-caps text-[10px] uppercase text-on-surface-variant font-bold"
                      x-text="metrics?.payment_mix?.methods?.[0]?.label ?? '{{ $seg1['label'] }}'">{{ $seg1['label'] }}</span>
            </div>
            <span class="font-metric-sm text-[13px] text-on-surface tabular-nums font-bold mt-0.5"
                  x-text="metrics?.payment_mix?.methods?.[0]?.formatted_amount ?? '{{ $seg1['formatted_amount'] }}'">{{ $seg1['formatted_amount'] }}</span>
        </div>

        <!-- Col 2 -->
        <div class="flex flex-col items-center gap-0.5 border-l border-[#272C35]">
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full" :style="'background-color:' + (metrics?.payment_mix?.methods?.[1]?.color ?? '{{ $seg2['color'] }}')"></span>
                <span class="font-label-caps text-[10px] uppercase text-on-surface-variant font-bold"
                      x-text="metrics?.payment_mix?.methods?.[1]?.label ?? '{{ $seg2['label'] }}'">{{ $seg2['label'] }}</span>
            </div>
            <span class="font-metric-sm text-[13px] text-on-surface tabular-nums font-bold mt-0.5"
                  x-text="metrics?.payment_mix?.methods?.[1]?.formatted_amount ?? '{{ $seg2['formatted_amount'] }}'">{{ $seg2['formatted_amount'] }}</span>
        </div>

        <!-- Col 3 -->
        <div class="flex flex-col items-center gap-0.5 border-l border-[#272C35]">
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full" :style="'background-color:' + (metrics?.payment_mix?.methods?.[2]?.color ?? '{{ $seg3['color'] }}')"></span>
                <span class="font-label-caps text-[10px] uppercase text-on-surface-variant font-bold"
                      x-text="metrics?.payment_mix?.methods?.[2]?.label ?? '{{ $seg3['label'] }}'">{{ $seg3['label'] }}</span>
            </div>
            <span class="font-metric-sm text-[13px] text-on-surface tabular-nums font-bold mt-0.5"
                  x-text="metrics?.payment_mix?.methods?.[2]?.formatted_amount ?? '{{ $seg3['formatted_amount'] }}'">{{ $seg3['formatted_amount'] }}</span>
        </div>
    </div>
</div>
