@props(['currentRange' => 'today'])

<div class="flex items-center gap-xs">
    <div class="flex bg-dark-bg border border-border-color rounded p-0.5" role="radiogroup" aria-label="Date Range Selector">
        <button type="button"
                @click="setRange('today')"
                :class="range === 'today' ? 'bg-hover-surface text-primary border-border-color font-bold shadow-none' : 'text-on-surface-variant hover:text-on-surface border-transparent'"
                class="px-3 py-1 rounded-sm font-label-caps text-label-caps uppercase border transition-colors cursor-pointer active:scale-95"
                role="radio"
                :aria-checked="range === 'today'">
            Today
        </button>
        <button type="button"
                @click="setRange('7d')"
                :class="range === '7d' ? 'bg-hover-surface text-primary border-border-color font-bold shadow-none' : 'text-on-surface-variant hover:text-on-surface border-transparent'"
                class="px-3 py-1 rounded-sm font-label-caps text-label-caps uppercase border transition-colors cursor-pointer active:scale-95"
                role="radio"
                :aria-checked="range === '7d'">
            7D
        </button>
        <button type="button"
                @click="setRange('30d')"
                :class="range === '30d' ? 'bg-hover-surface text-primary border-border-color font-bold shadow-none' : 'text-on-surface-variant hover:text-on-surface border-transparent'"
                class="px-3 py-1 rounded-sm font-label-caps text-label-caps uppercase border transition-colors cursor-pointer active:scale-95"
                role="radio"
                :aria-checked="range === '30d'">
            30D
        </button>
    </div>

    <!-- Export Button -->
    <a :href="'/dashboard/export?range=' + range"
       class="h-8 px-3 flex items-center gap-1.5 rounded border border-border-color text-on-surface hover:border-amber-accent hover:text-amber-accent transition-colors active:scale-95 no-underline font-label-caps text-label-caps uppercase cursor-pointer"
       title="Export CSV of current range transactions">
        <span class="material-symbols-outlined text-[16px]">download</span>
        <span>Export</span>
    </a>
</div>
