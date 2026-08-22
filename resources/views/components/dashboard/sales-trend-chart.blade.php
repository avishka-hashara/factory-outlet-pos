@props(['trend' => []])

<div class="lg:col-span-8 flex flex-col justify-between min-h-[310px] select-none box-border rounded-[8px] p-3.5 lg:p-4 bg-[#171A20] border-0"
     style="background-color: #171A20; border: none; outline: none; box-shadow: none;">
    <div class="flex justify-between items-center mb-2">
        <h2 class="font-headline-md text-[14px] lg:text-[15px] text-on-surface m-0 font-semibold tracking-tight">Sales Trend</h2>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-[10px] text-on-surface-variant font-label-caps uppercase font-bold">
                <span class="w-2 h-2 rounded-full bg-amber-accent"></span>
                <span x-text="metrics?.trend?.current_label ?? 'Today'">Today</span>
            </span>
            <span class="flex items-center gap-1.5 text-[10px] text-on-surface-variant font-label-caps uppercase font-bold">
                <span class="w-2 h-2 rounded-full border border-border-color bg-surface-variant"></span>
                <span x-text="metrics?.trend?.comparison_label ?? 'Yesterday'">Yesterday</span>
            </span>
            <button class="text-on-surface-variant hover:text-amber-accent transition-colors ml-1 p-0 border-0 bg-transparent cursor-pointer" title="Options">
                <span class="material-symbols-outlined text-[18px]">more_vert</span>
            </button>
        </div>
    </div>

    <!-- Chart Canvas Area -->
    <div class="flex-1 relative w-full min-h-[200px] border-b border-l border-[#272C35] flex items-end pt-2 pr-2 pl-9 pb-6">
        
        <!-- Y-Axis Labels (Auto-scaled derived from data) -->
        <div class="absolute left-1 top-1 bottom-6 flex flex-col justify-between text-on-surface-variant font-label-caps text-[9px] py-0.5 select-none pointer-events-none text-right w-6">
            <span x-text="trendMaxLabel">20k</span>
            <span x-text="trendMidHighLabel">15k</span>
            <span x-text="trendMidLabel">10k</span>
            <span x-text="trendMidLowLabel">5k</span>
            <span>0</span>
        </div>

        <!-- Horizontal Grid Lines -->
        <div class="absolute inset-0 pl-9 pr-2 pb-6 pt-2 flex flex-col justify-between pointer-events-none">
            <div class="border-t border-[#272C35]/50 w-full h-0"></div>
            <div class="border-t border-[#272C35]/50 w-full h-0"></div>
            <div class="border-t border-[#272C35]/50 w-full h-0"></div>
            <div class="border-t border-[#272C35]/50 w-full h-0"></div>
        </div>

        <!-- Dynamic SVG Area Chart with Smooth Monotone Curves -->
        <div class="absolute inset-0 pl-9 pr-2 pb-6 pt-2 overflow-hidden">
            <svg class="w-full h-full" preserveAspectRatio="none" viewBox="0 0 100 100">
                <defs>
                    <linearGradient id="trendChartGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#F5A623" stop-opacity="0.22"></stop>
                        <stop offset="100%" stop-color="#F5A623" stop-opacity="0.00"></stop>
                    </linearGradient>
                </defs>

                <!-- Comparison Line (Dashed Gray #2d3542) -->
                <path :d="trendComparisonSmoothPath"
                      fill="none"
                      stroke="#454f5d"
                      stroke-dasharray="4 4"
                      stroke-linejoin="round"
                      stroke-linecap="round"
                      stroke-width="1.5"
                      vector-effect="non-scaling-stroke"></path>

                <!-- Main Line Fill Area (Orange to Transparent) -->
                <path :d="trendAreaSmoothPath"
                      fill="url(#trendChartGrad)"></path>

                <!-- Main Line (Orange #F5A623 2px) -->
                <path :d="trendLineSmoothPath"
                      fill="none"
                      stroke="#F5A623"
                      stroke-linejoin="round"
                      stroke-linecap="round"
                      stroke-width="2"
                      vector-effect="non-scaling-stroke"></path>

                <!-- Peak / Latest Active Dot -->
                <template x-if="trendLastPoint">
                    <circle :cx="trendLastPoint.x" :cy="trendLastPoint.y" fill="#0E1013" r="3.5" stroke="#F5A623" stroke-width="2" vector-effect="non-scaling-stroke"></circle>
                </template>
            </svg>
        </div>

        <!-- X-Axis Labels (08:00 to 20:00, no 22:00, no dead space) -->
        <div class="absolute bottom-1 left-9 right-2 flex justify-between text-on-surface-variant font-label-caps text-[9px] select-none pointer-events-none">
            <template x-if="range === 'today'">
                <div class="w-full flex justify-between">
                    <span>08:00</span>
                    <span>10:00</span>
                    <span>12:00</span>
                    <span>14:00</span>
                    <span>16:00</span>
                    <span>18:00</span>
                    <span>20:00</span>
                </div>
            </template>
            <template x-if="range !== 'today'">
                <div class="w-full flex justify-between">
                    <template x-for="(pt, idx) in (metrics?.trend?.current ?? [])" :key="idx">
                        <span x-text="pt.label" :class="idx % 2 !== 0 && (metrics?.trend?.current?.length > 10) ? 'hidden sm:inline-block' : ''"></span>
                    </template>
                </div>
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
