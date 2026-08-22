@props(['paymentMix' => []])

<div class="lg:col-span-4 bg-card-surface border border-border-color rounded-card p-md flex flex-col min-h-[320px]">
    <div class="flex justify-between items-center mb-3">
        <h2 class="font-headline-md text-[16px] text-on-surface m-0 font-semibold">Payment Mix</h2>
        <button class="text-on-surface-variant hover:text-amber-accent transition-colors p-0 border-0 bg-transparent cursor-pointer" title="Options">
            <span class="material-symbols-outlined text-[18px]">more_vert</span>
        </button>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center relative">
        <!-- SVG Donut Graphic with computed stroke-dasharray -->
        <div class="w-32 h-32 rounded-full relative flex items-center justify-center">
            <svg class="absolute inset-0 w-full h-full -rotate-90 pointer-events-none" viewBox="0 0 100 100">
                <!-- Background track -->
                <circle cx="50" cy="50" r="40" fill="transparent" stroke="#2d3542" stroke-width="12"></circle>

                <!-- Dynamic Segments -->
                <template x-for="(seg, idx) in paymentSegments" :key="idx">
                    <circle class="transition-all duration-700 ease-out"
                            cx="50" cy="50" r="40"
                            fill="transparent"
                            :stroke="seg.color"
                            stroke-width="12"
                            :stroke-dasharray="seg.dashArray"
                            :stroke-dashoffset="seg.dashOffset"
                            :transform="seg.transform"></circle>
                </template>
            </svg>

            <!-- Center Metric -->
            <div class="text-center z-10 select-none">
                <div class="font-headline-lg text-[22px] text-on-surface leading-none font-bold"
                     x-text="(metrics?.payment_mix?.dominant_percentage ?? 0) + '%'">
                    {{ $paymentMix['dominant_percentage'] ?? 0 }}%
                </div>
                <div class="font-label-caps text-[9px] text-on-surface-variant uppercase mt-1"
                     x-text="metrics?.payment_mix?.dominant_method ?? 'Cash'">
                    {{ $paymentMix['dominant_method'] ?? 'Cash' }}
                </div>
            </div>
        </div>

        <!-- Payment Breakdown Badges / Legend Grid -->
        <div class="w-full mt-6 grid grid-cols-3 gap-1 text-center border-t border-border-color pt-3">
            <template x-for="(method, idx) in (metrics?.payment_mix?.methods ?? []).slice(0, 3)" :key="idx">
                <div class="flex flex-col items-center gap-0.5" :class="idx > 0 ? 'border-l border-border-color' : ''">
                    <div class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-sm" :style="'background-color:' + method.color"></div>
                        <span class="font-label-caps text-[9px] uppercase text-on-surface-variant" x-text="method.label"></span>
                    </div>
                    <span class="font-metric-sm text-[13px] text-on-surface tabular-nums font-semibold" x-text="method.formatted_amount"></span>
                </div>
            </template>
        </div>
    </div>
</div>
