@extends('layouts.pos')
@section('title', 'Dashboard')

@section('content')
<div x-data="dashboardApp(@js($metrics), '{{ $range }}')"
     x-init="initDashboard()"
     class="max-w-[1600px] mx-auto flex flex-col gap-gutter select-none pb-6">

    <!-- ===== Page Header ===== -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 mb-1">
        <div class="flex items-baseline gap-3">
            <h1 class="font-headline-lg text-headline-lg text-on-surface m-0 font-bold tracking-tight">Dashboard</h1>
            <p class="font-body-sm text-body-sm text-on-surface-variant m-0">Live Performance Overview</p>
        </div>

        <!-- Range Selector & Export -->
        <x-dashboard.range-selector :current-range="$range" />
    </div>

    <!-- ===== KPI Row (4-up) ===== -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter"
         :class="isLoading ? 'opacity-60 transition-opacity' : ''">
        <!-- KPI 1: Sales (Primary with amber top edge & corner glow) -->
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
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter min-h-[320px]"
         :class="isLoading ? 'opacity-60 transition-opacity' : ''">
        <!-- Sales Trend Area Chart (8 Cols) -->
        <x-dashboard.sales-trend-chart :trend="$metrics['trend']" />

        <!-- Payment Mix Donut (4 Cols) -->
        <x-dashboard.payment-mix-donut :payment-mix="$metrics['payment_mix']" />
    </div>

    <!-- ===== Second Row (7 / 5 Split) ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter"
         :class="isLoading ? 'opacity-60 transition-opacity' : ''">
        <!-- Recent Transactions Table (7 Cols) -->
        <x-dashboard.recent-transactions-table :recent-transactions="$metrics['recent_transactions']" />

        <!-- Low Stock Alerts & Drawer Status (5 Cols Stack) -->
        <div class="lg:col-span-5 grid grid-rows-2 gap-gutter">
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

        // ===== SVG Trend Calculations =====
        get trendMaxVal() {
            const curVals = (this.metrics?.trend?.current || []).map(p => Number(p.value) || 0);
            const compVals = (this.metrics?.trend?.comparison || []).map(p => Number(p.value) || 0);
            const max = Math.max(...curVals, ...compVals, 100);
            return Math.ceil(max * 1.15); // +15% headroom
        },

        get trendMaxLabel() {
            const m = this.trendMaxVal;
            return m >= 1000 ? Math.round(m / 1000) + 'k' : m;
        },
        get trendMidHighLabel() {
            const m = this.trendMaxVal * 0.75;
            return m >= 1000 ? Math.round(m / 1000) + 'k' : Math.round(m);
        },
        get trendMidLabel() {
            const m = this.trendMaxVal * 0.5;
            return m >= 1000 ? Math.round(m / 1000) + 'k' : Math.round(m);
        },
        get trendMidLowLabel() {
            const m = this.trendMaxVal * 0.25;
            return m >= 1000 ? Math.round(m / 1000) + 'k' : Math.round(m);
        },

        get trendLinePath() {
            const pts = this.metrics?.trend?.current || [];
            if (!pts.length) return 'M0,100 L100,100';
            const max = this.trendMaxVal;
            const step = 100 / (pts.length - 1 || 1);

            return pts.map((p, idx) => {
                const x = (idx * step).toFixed(2);
                const y = (100 - ((Number(p.value) || 0) / max) * 95).toFixed(2);
                return `${idx === 0 ? 'M' : 'L'}${x},${y}`;
            }).join(' ');
        },

        get trendAreaPath() {
            const pts = this.metrics?.trend?.current || [];
            if (!pts.length) return 'M0,100 L100,100 Z';
            const line = this.trendLinePath;
            return `${line} L100,100 L0,100 Z`;
        },

        get trendComparisonPath() {
            const pts = this.metrics?.trend?.comparison || [];
            if (!pts.length) return 'M0,100 L100,100';
            const max = this.trendMaxVal;
            const step = 100 / (pts.length - 1 || 1);

            return pts.map((p, idx) => {
                const x = (idx * step).toFixed(2);
                const y = (100 - ((Number(p.value) || 0) / max) * 95).toFixed(2);
                return `${idx === 0 ? 'M' : 'L'}${x},${y}`;
            }).join(' ');
        },

        get trendLastPoint() {
            const pts = this.metrics?.trend?.current || [];
            if (!pts.length) return null;
            const lastIdx = pts.length - 1;
            const max = this.trendMaxVal;
            return {
                x: 100,
                y: (100 - ((Number(pts[lastIdx].value) || 0) / max) * 95).toFixed(2),
            };
        },

        // ===== SVG Donut Calculations =====
        get paymentSegments() {
            const methods = this.metrics?.payment_mix?.methods || [];
            const circumference = 2 * Math.PI * 40; // r = 40 => ~251.327
            let accumulatedPercent = 0;

            return methods.map(m => {
                const pct = Math.max(0, Number(m.percentage) || 0);
                const dashLength = (pct / 100) * circumference;
                const gapLength = circumference - dashLength;
                const rotation = (accumulatedPercent / 100) * 360;

                accumulatedPercent += pct;

                return {
                    color: m.color || '#F5A623',
                    dashArray: `${dashLength.toFixed(2)} ${gapLength.toFixed(2)}`,
                    dashOffset: '0',
                    transform: `rotate(${rotation.toFixed(1)} 50 50)`,
                };
            });
        }
    };
}
</script>
@endpush
