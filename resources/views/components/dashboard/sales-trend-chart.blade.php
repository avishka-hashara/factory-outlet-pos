@props(['trend' => []])

<div class="lg:col-span-8 bg-card-surface border border-border-color rounded-card p-md flex flex-col min-h-[320px]">
    <div class="flex justify-between items-center mb-4">
        <h2 class="font-headline-md text-[16px] text-on-surface m-0 font-semibold">Sales Trend</h2>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-[10px] text-on-surface-variant font-label-caps uppercase">
                <span class="w-2 h-2 rounded-full bg-amber-accent"></span>
                <span x-text="metrics?.trend?.current_label ?? 'Today'">Today</span>
            </span>
            <span class="flex items-center gap-1.5 text-[10px] text-on-surface-variant font-label-caps uppercase">
                <span class="w-2 h-2 rounded-full border border-border-color bg-surface-variant"></span>
                <span x-text="metrics?.trend?.comparison_label ?? 'Yesterday'">Yesterday</span>
            </span>
            <button class="text-on-surface-variant hover:text-amber-accent transition-colors ml-1 p-0 border-0 bg-transparent cursor-pointer" title="Options">
                <span class="material-symbols-outlined text-[18px]">more_vert</span>
            </button>
        </div>
    </div>

    <!-- Chart Canvas Area -->
    <div class="flex-1 relative w-full h-full min-h-[220px] border-b border-l border-border-color flex items-end pt-4 pr-2 pl-8 pb-6">
        
        <!-- Y-Axis Labels (Dynamic scale based on peak value) -->
        <div class="absolute left-1 top-2 bottom-6 flex flex-col justify-between text-on-surface-variant font-label-caps text-[9px] py-1 select-none pointer-events-none">
            <span x-text="trendMaxLabel">20k</span>
            <span x-text="trendMidHighLabel">15k</span>
            <span x-text="trendMidLabel">10k</span>
            <span x-text="trendMidLowLabel">5k</span>
            <span>0</span>
        </div>

        <!-- Horizontal Grid Lines -->
        <div class="absolute inset-0 pl-8 pr-2 pb-6 pt-4 flex flex-col justify-between pointer-events-none">
            <div class="border-t border-border-color/20 w-full h-0"></div>
            <div class="border-t border-border-color/20 w-full h-0"></div>
            <div class="border-t border-border-color/20 w-full h-0"></div>
            <div class="border-t border-border-color/20 w-full h-0"></div>
        </div>

        <!-- Dynamic SVG Area Chart -->
        <div class="absolute inset-0 pl-8 pr-2 pb-6 pt-4 overflow-hidden">
            <svg class="w-full h-full" preserveAspectRatio="none" viewBox="0 0 100 100">
                <defs>
                    <linearGradient id="trendChartGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#F5A623" stop-opacity="0.25"></stop>
                        <stop offset="100%" stop-color="#F5A623" stop-opacity="0.00"></stop>
                    </linearGradient>
                </defs>

                <!-- Comparison Line (Dashed) -->
                <path :d="trendComparisonPath"
                      fill="none"
                      stroke="#2d3542"
                      stroke-dasharray="4 4"
                      stroke-linejoin="round"
                      stroke-width="1.5"
                      vector-effect="non-scaling-stroke"></path>

                <!-- Main Line Fill Area -->
                <path :d="trendAreaPath"
                      fill="url(#trendChartGrad)"></path>

                <!-- Main Line (Amber 2px) -->
                <path :d="trendLinePath"
                      fill="none"
                      stroke="#F5A623"
                      stroke-linejoin="round"
                      stroke-width="2"
                      vector-effect="non-scaling-stroke"></path>

                <!-- Peak / Latest Dot -->
                <template x-if="trendLastPoint">
                    <circle :cx="trendLastPoint.x" :cy="trendLastPoint.y" fill="#0E1013" r="3.5" stroke="#F5A623" stroke-width="2" vector-effect="non-scaling-stroke"></circle>
                </template>
            </svg>
        </div>

        <!-- X-Axis Labels -->
        <div class="absolute bottom-1 left-8 right-2 flex justify-between text-on-surface-variant font-label-caps text-[9px] select-none pointer-events-none">
            <template x-for="(pt, idx) in (metrics?.trend?.current ?? [])" :key="idx">
                <span x-text="pt.label" :class="idx % 2 !== 0 && (metrics?.trend?.current?.length > 8) ? 'hidden sm:inline-block' : ''"></span>
            </template>
        </div>

        <!-- Screen-Reader Accessible Fallback Table -->
        <table class="sr-only">
            <caption>Sales Trend Data</caption>
            <thead>
                <tr><th>Time/Period</th><th>Current (Rs.)</th><th>Comparison (Rs.)</th></tr>
            </thead>
            <tbody>
                <template x-for="(pt, idx) in (metrics?.trend?.current ?? [])" :key="idx">
                    <tr>
                        <td x-text="pt.label"></td>
                        <td x-text="pt.value"></td>
                        <td x-text="metrics?.trend?.comparison?.[idx]?.value ?? 0"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
