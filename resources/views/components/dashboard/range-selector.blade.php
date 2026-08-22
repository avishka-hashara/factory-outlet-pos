@props(['currentRange' => 'today'])

<div class="flex items-center gap-2 select-none">
    <!-- Segmented Date Range (TODAY | 7D | 30D) -->
    <div class="flex items-center bg-[#111419] border border-[#272C35] rounded-[6px] p-[3px] h-[34px] box-border" role="radiogroup" aria-label="Date Range Selector">
        <button type="button"
                @click="setRange('today')"
                :class="range === 'today' ? 'bg-[#1E222A] text-[#F5A623] font-bold shadow-none' : 'text-[#d7c3ae] hover:text-[#FFFFFF] font-semibold bg-transparent'"
                class="px-3 h-full rounded-[4px] font-['Inter'] text-[11px] uppercase tracking-wider transition-colors cursor-pointer border-0 flex items-center justify-center active:scale-95"
                role="radio"
                :aria-checked="range === 'today'">
            TODAY
        </button>
        <button type="button"
                @click="setRange('7d')"
                :class="range === '7d' ? 'bg-[#1E222A] text-[#F5A623] font-bold shadow-none' : 'text-[#d7c3ae] hover:text-[#FFFFFF] font-semibold bg-transparent'"
                class="px-3 h-full rounded-[4px] font-['Inter'] text-[11px] uppercase tracking-wider transition-colors cursor-pointer border-0 flex items-center justify-center active:scale-95"
                role="radio"
                :aria-checked="range === '7d'">
            7D
        </button>
        <button type="button"
                @click="setRange('30d')"
                :class="range === '30d' ? 'bg-[#1E222A] text-[#F5A623] font-bold shadow-none' : 'text-[#d7c3ae] hover:text-[#FFFFFF] font-semibold bg-transparent'"
                class="px-3 h-full rounded-[4px] font-['Inter'] text-[11px] uppercase tracking-wider transition-colors cursor-pointer border-0 flex items-center justify-center active:scale-95"
                role="radio"
                :aria-checked="range === '30d'">
            30D
        </button>
    </div>

    <!-- Export Button -->
    <a :href="'/dashboard/export?range=' + range"
       class="h-[34px] px-3.5 flex items-center gap-1.5 rounded-[6px] bg-[#111419] border border-[#272C35] text-white hover:bg-[#1E222A] hover:border-[#373E4B] hover:text-[#F5A623] transition-colors active:scale-95 no-underline font-['Inter'] text-[11px] font-bold uppercase tracking-wider cursor-pointer box-border"
       title="Export CSV of current range transactions">
        <span class="material-symbols-outlined text-[16px] text-white" style="font-variation-settings: 'wght' 400, 'opsz' 20;">download</span>
        <span>EXPORT</span>
    </a>
</div>
