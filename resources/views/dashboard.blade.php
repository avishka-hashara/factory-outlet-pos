@extends('layouts.pos')
@section('title', 'Dashboard')

@push('styles')
<style>
    /* ========================================================
       DASHBOARD CARD CUSTOMIZATION VARIABLES
       Adjust border color, width, radius, and background here:
       ======================================================== */
    :root {
        --dash-card-bg: #171A20;           /* Background: Dark Surface */
        --dash-card-hover-bg: #1E222A;     /* Hover Background */
        --dash-card-border: #272C35;       /* Border Color (Hex: #272C35 | RGB: 39,44,53 | HSL: 219,15%,18%) */
        --dash-card-border-width: 1px;     /* Border Width */
        --dash-card-radius: 8px;           /* Corner Radius */
    }

    .dashboard-card {
        background-color: var(--dash-card-bg) !important;
        border: var(--dash-card-border-width) solid var(--dash-card-border) !important;
        border-radius: var(--dash-card-radius) !important;
        box-sizing: border-box !important;
        box-shadow: none !important;
    }

    .dashboard-card:hover {
        background-color: var(--dash-card-hover-bg) !important;
    }
</style>
@endpush

@section('main-class', 'p-3.5 lg:p-4 pb-8 overflow-y-auto')

@section('content')
<div x-data="dashboardApp(@js($metrics), '{{ $range }}')"
     x-init="initDashboard()"
     class="w-full flex flex-col select-none gap-3.5 box-border">

    <!-- ===== Page Header ===== -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 shrink-0">
        <div class="flex items-baseline gap-3">
            <h1 class="font-headline-lg text-[20px] lg:text-[22px] text-on-surface m-0 font-bold tracking-tight">Dashboard</h1>
            <p class="font-body-sm text-[12px] text-on-surface-variant m-0">Live Performance Overview</p>
        </div>

        <!-- Range Selector & Export -->
        <x-dashboard.range-selector :current-range="$range" />
    </div>

    <!-- ===== KPI Row (4-up) ===== -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-3.5 shrink-0"
         :class="isLoading ? 'opacity-60 transition-opacity' : ''">
        <!-- KPI 1: Sales -->
        <x-dashboard.stat-card
            title="Today's Sales"
            icon="payments"
            :value="$metrics['summary']['sales']['formatted']"
            :delta="$metrics['summary']['sales']['delta']"
            :is-positive="$metrics['summary']['sales']['is_positive']"
            :comparison-label="$metrics['summary']['comparison_label']"
            :is-primary="true"
            field-key="metrics?.summary?.sales"
        />

        <!-- KPI 2: Transactions -->
        <x-dashboard.stat-card
            title="Transactions"
            icon="receipt_long"
            :value="$metrics['summary']['transactions']['formatted']"
            :delta="$metrics['summary']['transactions']['delta']"
            :is-positive="$metrics['summary']['transactions']['is_positive']"
            :comparison-label="$metrics['summary']['comparison_label']"
            field-key="metrics?.summary?.transactions"
        />

        <!-- KPI 3: Avg Bill Value -->
        <x-dashboard.stat-card
            title="Avg Bill Value"
            icon="calculate"
            :value="$metrics['summary']['avg_bill']['formatted']"
            :delta="$metrics['summary']['avg_bill']['delta']"
            :is-positive="$metrics['summary']['avg_bill']['is_positive']"
            :comparison-label="$metrics['summary']['comparison_label']"
            field-key="metrics?.summary?.avg_bill"
        />

        <!-- KPI 4: Items Sold -->
        <x-dashboard.stat-card
            title="Items Sold"
            icon="inventory_2"
            :value="$metrics['summary']['items_sold']['formatted']"
            :delta="$metrics['summary']['items_sold']['delta']"
            :is-positive="$metrics['summary']['items_sold']['is_positive']"
            :comparison-label="$metrics['summary']['comparison_label']"
            field-key="metrics?.summary?.items_sold"
        />
    </div>

    <!-- ===== Charts Row (8 / 4 Split) ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 lg:gap-3.5"
         :class="isLoading ? 'opacity-60 transition-opacity' : ''">
        <!-- Sales Trend Area Chart (8 Cols) -->
        <x-dashboard.sales-trend-chart :trend="$metrics['trend']" />

        <!-- Payment Mix Donut (4 Cols) -->
        <x-dashboard.payment-mix-donut :payment-mix="$metrics['payment_mix']" />
    </div>

    <!-- ===== Bottom Row (2fr / 1fr Split) ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 lg:gap-3.5"
         :class="isLoading ? 'opacity-60 transition-opacity' : ''">
        <!-- Recent Transactions Table (2fr / 2 cols) -->
        <x-dashboard.recent-transactions-table :recent-transactions="$metrics['recent_transactions']" />

        <!-- Stack of Stock Alerts & Drawer Status (1fr / 1 col) -->
        <div class="flex flex-col gap-3 lg:gap-3.5 h-full">
            <!-- Stock Alerts Panel -->
            <x-dashboard.stock-alerts-panel :stock-alerts="$metrics['stock_alerts']" />

            <!-- Drawer Status Card -->
            <x-dashboard.drawer-status-card :drawer-status="$metrics['drawer_status']" />
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function dashboardApp(initialMetrics, initialRange) {
    return {
        metrics: initialMetrics,
        range: initialRange || 'today',
        isLoading: false,
        pollTimer: null,

        initDashboard() {
            // Check URL query parameter if present on load
            const urlParams = new URLSearchParams(window.location.search);
            const r = urlParams.get('range');
            if (r && ['today', '7d', '30d'].includes(r) && r !== this.range) {
                this.setRange(r);
            }

            // Start 30s polling
            this.startPolling();

            // Pause polling when tab is inactive to save network/CPU
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopPolling();
                } else {
                    this.fetchMetrics();
                    this.startPolling();
                }
            });
        },

        startPolling() {
            this.stopPolling();
            this.pollTimer = setInterval(() => {
                if (!this.isLoading) {
                    this.fetchMetrics(true);
                }
            }, 30000);
        },

        stopPolling() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        async setRange(newRange) {
            if (this.range === newRange && !this.isLoading) return;
            this.range = newRange;

            // Push selection into browser URL history
            const url = new URL(window.location);
            url.searchParams.set('range', newRange);
            window.history.pushState({}, '', url);

            await this.fetchMetrics();
        },

        async fetchMetrics(isSilent = false) {
            if (!isSilent) this.isLoading = true;
            try {
                const response = await fetch(`/dashboard/metrics?range=${this.range}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });
                if (response.ok) {
                    this.metrics = await response.json();
                }
            } catch (err) {
                console.error('Failed to update metrics:', err);
            } finally {
                if (!isSilent) this.isLoading = false;
            }
        },

        // ===== SVG Trend Calculations (Auto-Scaled Ticks) =====
        get trendMaxVal() {
            const curVals = (this.metrics?.trend?.current || []).map(p => Number(p.value) || 0);
            const compVals = (this.metrics?.trend?.comparison || []).map(p => Number(p.value) || 0);
            const peak = Math.max(...curVals, ...compVals, 1000);
            if (peak <= 5000) return 5000;
            if (peak <= 10000) return 10000;
            if (peak <= 15000) return 15000;
            if (peak <= 20000) return 20000;
            if (peak <= 30000) return 30000;
            if (peak <= 40000) return 40000;
            if (peak <= 50000) return 50000;
            if (peak <= 60000) return 60000;
            return Math.ceil(peak / 20000) * 20000;
        },

        get trendMaxLabel() {
            return this.trendMaxVal >= 1000 ? (this.trendMaxVal / 1000) + 'k' : this.trendMaxVal;
        },
        get trendMidHighLabel() {
            const v = this.trendMaxVal * 0.75;
            return v >= 1000 ? (v % 1000 === 0 ? (v / 1000) : (v / 1000).toFixed(1)) + 'k' : Math.round(v);
        },
        get trendMidLabel() {
            const v = this.trendMaxVal * 0.5;
            return v >= 1000 ? (v % 1000 === 0 ? (v / 1000) : (v / 1000).toFixed(1)) + 'k' : Math.round(v);
        },
        get trendMidLowLabel() {
            const v = this.trendMaxVal * 0.25;
            return v >= 1000 ? (v % 1000 === 0 ? (v / 1000) : (v / 1000).toFixed(1)) + 'k' : Math.round(v);
        },

        // Monotone Cubic Spline (Fritsch-Carlson algorithm) to prevent overshoot humps
        getSmoothPath(pts, max) {
            if (!pts || !pts.length) return 'M 0 100 L 100 100';
            if (pts.length === 1) {
                const y = Math.max(5, Math.min(95, 100 - ((Number(pts[0].value) || 0) / max) * 90));
                return `M 0 ${y.toFixed(2)} L 100 ${y.toFixed(2)}`;
            }

            const n = pts.length;
            const step = 100 / (n - 1);
            const coords = pts.map((p, i) => ({
                x: i * step,
                y: Math.max(5, Math.min(95, 100 - ((Number(p.value) || 0) / max) * 90))
            }));

            // 1. Secant slopes
            const deltas = new Array(n - 1);
            for (let i = 0; i < n - 1; i++) {
                deltas[i] = (coords[i + 1].y - coords[i].y) / (coords[i + 1].x - coords[i].x);
            }

            // 2. Initial tangents
            const m = new Array(n);
            m[0] = deltas[0];
            for (let i = 1; i < n - 1; i++) {
                m[i] = (deltas[i - 1] + deltas[i]) / 2;
            }
            m[n - 1] = deltas[n - 2];

            // 3. Fritsch-Carlson monotone slope adjustments
            for (let i = 0; i < n - 1; i++) {
                if (Math.abs(deltas[i]) < 1e-7) {
                    m[i] = 0;
                    m[i + 1] = 0;
                } else {
                    const alpha = m[i] / deltas[i];
                    const beta = m[i + 1] / deltas[i];
                    if (alpha < 0) m[i] = 0;
                    if (beta < 0) m[i + 1] = 0;
                    const dist = alpha * alpha + beta * beta;
                    if (dist > 9) {
                        const tau = 3 / Math.sqrt(dist);
                        m[i] = tau * alpha * deltas[i];
                        m[i + 1] = tau * beta * deltas[i];
                    }
                }
            }

            // 4. Cubic Bezier path
            let d = `M ${coords[0].x.toFixed(2)} ${coords[0].y.toFixed(2)}`;
            for (let i = 0; i < n - 1; i++) {
                const dx = coords[i + 1].x - coords[i].x;
                const cp1x = coords[i].x + dx / 3;
                const cp1y = coords[i].y + (m[i] * dx) / 3;
                const cp2x = coords[i + 1].x - dx / 3;
                const cp2y = coords[i + 1].y - (m[i + 1] * dx) / 3;

                d += ` C ${cp1x.toFixed(2)} ${cp1y.toFixed(2)}, ${cp2x.toFixed(2)} ${cp2y.toFixed(2)}, ${coords[i + 1].x.toFixed(2)} ${coords[i + 1].y.toFixed(2)}`;
            }
            return d;
        },

        get trendLineSmoothPath() {
            const pts = this.metrics?.trend?.current || [];
            return this.getSmoothPath(pts, this.trendMaxVal);
        },

        get trendAreaSmoothPath() {
            const pts = this.metrics?.trend?.current || [];
            if (!pts.length) return 'M 0 100 L 100 100 Z';
            const line = this.trendLineSmoothPath;
            return `${line} L 100 100 L 0 100 Z`;
        },

        get trendComparisonSmoothPath() {
            const pts = this.metrics?.trend?.comparison || [];
            return this.getSmoothPath(pts, this.trendMaxVal);
        },

        get trendLastPoint() {
            const pts = this.metrics?.trend?.current || [];
            if (!pts.length) return null;
            const lastIdx = pts.length - 1;
            const max = this.trendMaxVal;
            const lastVal = Number(pts[lastIdx].value) || 0;
            return {
                x: 100,
                y: Math.max(5, Math.min(95, 100 - (lastVal / max) * 90)).toFixed(2),
            };
        },

        // ===== SVG Donut Calculations (3 Distinct Proportional Arcs) =====
        getDonutSegment(index, defaultColor) {
            const methods = this.metrics?.payment_mix?.methods || [];
            const circumference = 2 * Math.PI * 38; // ~238.76
            const gap = 3.5;

            let accPct = 0;
            for (let i = 0; i < index; i++) {
                accPct += Math.max(0, Number(methods[i]?.percentage) || 0);
            }

            const curMethod = methods[index];
            const pct = Math.max(0, Number(curMethod?.percentage) || 0);
            const color = curMethod?.color || defaultColor;

            if (pct <= 0) {
                return {
                    color: 'transparent',
                    dashArray: `0 ${circumference.toFixed(2)}`,
                    transform: 'rotate(0 50 50)',
                };
            }

            const dashLen = Math.max(0.1, (pct / 100) * circumference - gap);
            const gapLen = circumference - dashLen;
            const rotation = (accPct / 100) * 360;

            return {
                color: color,
                dashArray: `${dashLen.toFixed(2)} ${gapLen.toFixed(2)}`,
                transform: `rotate(${rotation.toFixed(1)} 50 50)`,
            };
        },

        get donutSegment1() { return this.getDonutSegment(0, '#F5A623'); },
        get donutSegment2() { return this.getDonutSegment(1, '#dae3f3'); },
        get donutSegment3() { return this.getDonutSegment(2, '#524534'); },
        get donutDominantPercentage() { return this.metrics?.payment_mix?.dominant_percentage ?? 0; },
        get donutDominantMethod() { return this.metrics?.payment_mix?.dominant_method ?? 'KOKO'; },
    };
}
</script>
@endpush
