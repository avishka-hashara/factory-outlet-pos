{{-- ============================================
     Reports Page - Jewellery POS System
     Daily/Monthly/Custom tabs, charts, export
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Reports')

@section('content')
<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h4><i class="fas fa-chart-bar me-2 text-gold"></i> Reports</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Reports</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0 report-header-actions">
        <button class="btn btn-gold btn-sm" onclick="syncShopify()" id="sync-btn">
            <i class="fas fa-sync-alt me-1"></i><span class="d-none d-sm-inline"> Sync Shopify</span>
        </button>
        <button class="btn btn-outline-gold btn-sm" onclick="exportPDF()">
            <i class="fas fa-file-pdf me-1"></i><span class="d-none d-sm-inline"> Export PDF</span>
        </button>
        <button class="btn btn-outline-gold btn-sm" onclick="exportExcel()">
            <i class="fas fa-file-excel me-1"></i><span class="d-none d-sm-inline"> Export Excel</span>
        </button>
    </div>
</div>

@if(session('pdf_error'))
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i>{{ session('pdf_error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Report Tabs -->
<ul class="nav nav-pills mb-4 report-tabs flex-nowrap overflow-auto" id="reportTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active text-nowrap" data-bs-toggle="pill" data-bs-target="#daily" type="button">
            <i class="fas fa-calendar-day me-1"></i> Daily
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-nowrap" data-bs-toggle="pill" data-bs-target="#monthly" type="button">
            <i class="fas fa-calendar-alt me-1"></i> Monthly
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-nowrap" data-bs-toggle="pill" data-bs-target="#custom" type="button">
            <i class="fas fa-sliders-h me-1"></i> Custom Range
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-nowrap {{ $tab === 'stock' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#stock" type="button">
            <i class="fas fa-boxes me-1"></i> Stock
        </button>
    </li>
</ul>

<div class="tab-content" id="reportTabContent">
    <!-- ---- Daily Report Tab ---- -->
    <div class="tab-pane fade show active" id="daily" role="tabpanel">
        <!-- Date Selector -->
        <div class="pos-card card mb-4">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-8 col-md-4">
                        <label class="form-label small fw-semibold">Select Date</label>
                        <input type="date" name="date" class="form-control" value="{{ $date }}">
                    </div>
                    <div class="col-4 col-md-2">
                        <form method="GET" action="{{ route('reports.index') }}">
                            <button type="submit" class="btn btn-dark-blue w-100"><i class="fas fa-search me-1"></i> Generate</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="stat-card gold p-3">
                    <div class="stat-label">Total Sales</div>
                    <div class="stat-value">{{ $totalSales }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card blue p-3">
                    <div class="stat-label">Revenue</div>
                    <div class="stat-value">Rs. {{ number_format($revenue, 2) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card green p-3">
                    <div class="stat-label">Items Sold</div>
                    <div class="stat-value">{{ $itemsSold }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card warning p-3">
                    <div class="stat-label">Total Refunds</div>
                    <div class="stat-value">{{ $totalRefunds }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card red p-3">
                    <div class="stat-label">Refund Value</div>
                    <div class="stat-value">Rs. {{ number_format($refundValue, 2) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card warning p-3">
                    <div class="stat-label">Avg. Sale</div>
                    <div class="stat-value">Rs. {{ number_format($avgSale, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8 col-12">
                <div class="pos-card card">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="fas fa-chart-line me-2 text-gold"></i> Hourly Sales Trend</h5>
                        <canvas id="dailyHourlyChart" height="120" style="max-height:280px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-12">
                <div class="pos-card card">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="fas fa-chart-pie me-2 text-gold"></i> Payment Breakdown</h5>
                        <canvas id="dailyPaymentChart" height="200" style="max-height:250px;"></canvas>
                        <!-- Payment Summary -->
                        <div class="mt-3">
                            @foreach($payments as $method => $amount)
                                @if(abs($amount) > 0.01)
                                <div class="d-flex justify-content-between py-2 border-bottom">
                                    <span class="text-capitalize">
                                        <i class="fas fa-circle me-2" style="font-size:0.5rem; color: 
                                            {{ $method == 'cash' ? '#10B981' : 
                                               ($method == 'koko' ? '#3B82F6' : 
                                               ($method == 'mintpay' ? '#F5A623' : '#F59E0B')) }}">
                                        </i> {{ $method }}
                                    </span>
                                    <strong>{{ $amount < 0 ? '-Rs. ' : 'Rs. ' }}{{ number_format(abs($amount), 2) }}</strong>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Products Table -->
        <div class="pos-card card">
            <div class="card-body">
                <h5 class="mb-3"><i class="fas fa-trophy me-2 text-gold"></i> Top Selling Products Today</h5>
                <div class="table-responsive">
                    <table class="table pos-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Qty Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $item->product?->name ?? $item->item_label ?? 'Deleted Product' }}</strong></td>
                                <td>{{ $item->product?->sku ?? 'N/A' }}</td>
                                <td>{{ $item->total_qty }}</td>
                                <td class="fw-bold">Rs. {{ number_format($item->total_revenue, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted">No sales recorded for this date.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ---- Monthly Report Tab ---- -->
    <div class="tab-pane fade" id="monthly" role="tabpanel">
        <div class="pos-card card mb-4">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-8 col-md-3">
                        <label class="form-label small fw-semibold">Month</label>
                        <select name="month" class="form-select">
                            @foreach($availableMonths as $availMonth)
                            <option value="{{ $availMonth }}" {{ $availMonth === $month ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($availMonth . '-01')->format('F Y') }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4 col-md-2">
                        <form method="GET" action="{{ route('reports.index') }}">
                            <button type="submit" class="btn btn-dark-blue w-100"><i class="fas fa-search me-1"></i> Generate</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="stat-card gold p-3">
                    <div class="stat-label">Total Sales</div>
                    <div class="stat-value">{{ $monthlyTotalSales }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card blue p-3">
                    <div class="stat-label">Revenue</div>
                    <div class="stat-value">Rs. {{ number_format($monthlyRevenue, 0) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card green p-3">
                    <div class="stat-label">Items Sold</div>
                    <div class="stat-value">{{ $monthlyItemsSold }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card warning p-3">
                    <div class="stat-label">Total Refunds</div>
                    <div class="stat-value">{{ $monthlyTotalRefunds }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card red p-3">
                    <div class="stat-label">Refund Value</div>
                    <div class="stat-value">Rs. {{ number_format($monthlyRefundValue, 0) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card warning p-3">
                    <div class="stat-label">Avg. Daily Sales</div>
                    <div class="stat-value">Rs. {{ number_format($monthlyAvgDaily, 0) }}</div>
                </div>
            </div>
        </div>

        <div class="pos-card card">
            <div class="card-body">
                <h5 class="mb-3"><i class="fas fa-chart-bar me-2 text-gold"></i> Monthly Sales Trend</h5>
                <canvas id="monthlyChart" height="120" style="max-height:280px;"></canvas>
            </div>
        </div>
    </div>

    <!-- ---- Custom Range Tab ---- -->
    <div class="tab-pane fade" id="custom" role="tabpanel">
        <div class="pos-card card mb-4">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold">From Date</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold">To Date</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                    </div>
                    <div class="col-12 col-md-2">
                        <form method="GET" action="{{ route('reports.index') }}">
                            <button type="submit" class="btn btn-dark-blue w-100"><i class="fas fa-search me-1"></i> Generate</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="stat-card gold p-3">
                    <div class="stat-label">Total Sales</div>
                    <div class="stat-value">{{ $customTotalSales }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card blue p-3">
                    <div class="stat-label">Revenue</div>
                    <div class="stat-value">Rs. {{ number_format($customRevenue, 0) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card green p-3">
                    <div class="stat-label">Items Sold</div>
                    <div class="stat-value">{{ $customItemsSold }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card warning p-3">
                    <div class="stat-label">Total Refunds</div>
                    <div class="stat-value">{{ $customTotalRefunds }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card red p-3">
                    <div class="stat-label">Refund Value</div>
                    <div class="stat-value">Rs. {{ number_format($customRefundValue, 0) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card warning p-3">
                    <div class="stat-label">Avg. Daily Sales</div>
                    <div class="stat-value">Rs. {{ number_format($customAvgDaily, 0) }}</div>
                </div>
            </div>
        </div>

        <div class="pos-card card">
            <div class="card-body">
                <h5 class="mb-3"><i class="fas fa-chart-area me-2 text-gold"></i> Custom Range Revenue</h5>
                <canvas id="customChart" height="120" style="max-height:280px;"></canvas>
            </div>
        </div>
    </div>

    {{-- ======================================================
         STOCK REPORT TAB
         ====================================================== --}}
    <div class="tab-pane fade {{ $tab === 'stock' ? 'show active' : '' }}" id="stock" role="tabpanel">

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card gold p-3 text-center">
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value">{{ $stockProductCount }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card blue p-3 text-center">
                    <div class="stat-label">Inventory Value</div>
                    <div class="stat-value" style="font-size:1rem;">Rs.&nbsp;{{ number_format($stockTotalValue, 0) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card green p-3 text-center">
                    <div class="stat-label">All-Time Revenue</div>
                    <div class="stat-value" style="font-size:1rem;">Rs.&nbsp;{{ number_format($stockAllTimeRevenue, 0) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card warning p-3 text-center">
                    <div class="stat-label">In Stock</div>
                    <div class="stat-value">{{ $stockProductCount - $stockOutCount }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card warning p-3 text-center" style="background:#fff7ed;border-left:4px solid #f59e0b!important;">
                    <div class="stat-label">Low Stock (≤5)</div>
                    <div class="stat-value text-warning fw-bold">{{ $stockLowCount }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card p-3 text-center" style="background:#fff1f2;border-left:4px solid #ef4444!important;">
                    <div class="stat-label">Out of Stock</div>
                    <div class="stat-value" style="color:#ef4444;font-weight:800;">{{ $stockOutCount }}</div>
                </div>
            </div>
        </div>

        {{-- Filters + Export --}}
        <div class="pos-card card mb-3">
            <div class="card-body py-3">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-sm-6 col-md-3">
                        <input type="text" id="stockSearch" class="form-control form-control-sm"
                               placeholder="Search product / SKU…" oninput="filterStockTable()">
                    </div>
                    <div class="col-6 col-sm-6 col-md-2">
                        <select id="stockCategoryFilter" class="form-select form-select-sm" onchange="filterStockTable()">
                            <option value="">All Categories</option>
                            @foreach($stockProducts->pluck('category')->filter()->unique()->sort() as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-sm-6 col-md-2">
                        <select id="stockStatusFilter" class="form-select form-select-sm" onchange="filterStockTable()">
                            <option value="">All Status</option>
                            <option value="in">In Stock</option>
                            <option value="low">Low Stock</option>
                            <option value="out">Out of Stock</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Product Table - Desktop --}}
        <div class="pos-card card d-none d-md-block">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table pos-table mb-0" id="stock-table">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-center">Stock</th>
                                <th class="text-end">Inv. Value</th>
                                <th class="text-center">Units Sold</th>
                                <th class="text-end">Revenue</th>
                                <th class="text-end" style="white-space:nowrap;">% of Total</th>
                                <th>Last Sold</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockProducts as $i => $p)
                            @php
                                $pct = $stockAllTimeRevenue > 0 ? ($p->total_revenue / $stockAllTimeRevenue * 100) : 0;
                                if ($p->stock <= 0)     $status = 'out';
                                elseif ($p->stock <= 5) $status = 'low';
                                else                    $status = 'in';
                            @endphp
                            <tr class="stock-row"
                                data-name="{{ strtolower($p->name) }}"
                                data-sku="{{ strtolower($p->sku ?? '') }}"
                                data-cat="{{ $p->category ?? '' }}"
                                data-status="{{ $status }}">
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $p->name }}</div>
                                    @if($p->subcategory)
                                        <small class="text-muted">{{ $p->subcategory }}</small>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $p->sku ?? '—' }}</td>
                                <td class="small">{{ $p->category ?? '—' }}</td>
                                <td class="text-end">Rs. {{ number_format($p->price, 2) }}</td>
                                <td class="text-center fw-bold {{ $p->stock <= 0 ? 'text-danger' : ($p->stock <= 5 ? 'text-warning' : '') }}">
                                    {{ $p->stock }}
                                </td>
                                <td class="text-end text-muted">Rs. {{ number_format($p->stock_value, 2) }}</td>
                                <td class="text-center">{{ $p->total_sold > 0 ? number_format($p->total_sold) : '—' }}</td>
                                <td class="text-end fw-semibold {{ $p->total_revenue > 0 ? 'text-gold' : 'text-muted' }}">
                                    {{ $p->total_revenue > 0 ? 'Rs. ' . number_format($p->total_revenue, 2) : '—' }}
                                </td>
                                <td class="text-end">
                                    @if($pct > 0)
                                    <div class="small fw-semibold">{{ number_format($pct, 1) }}%</div>
                                    <div class="progress mt-1" style="height:4px;min-width:50px;">
                                        <div class="progress-bar" role="progressbar"
                                             style="width:{{ min($pct * 2, 100) }}%;background:var(--gold);"></div>
                                    </div>
                                    @else
                                    <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    {{ $p->last_sold_at ? \Carbon\Carbon::parse($p->last_sold_at)->format('d M Y') : '—' }}
                                </td>
                                <td class="text-center">
                                    @if($status === 'out')
                                        <span class="badge fw-semibold" style="background:#fee2e2;color:#ef4444;">Out of Stock</span>
                                    @elseif($status === 'low')
                                        <span class="badge fw-semibold" style="background:#fef3c7;color:#d97706;">Low Stock</span>
                                    @else
                                        <span class="badge fw-semibold" style="background:#dcfce7;color:#10b981;">In Stock</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="12" class="text-center py-4 text-muted">No products found.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr style="background:#f8f9fa;font-weight:700;">
                                <td colspan="4" class="text-end">TOTALS</td>
                                <td></td>
                                <td class="text-center">{{ $stockProducts->sum('stock') }}</td>
                                <td class="text-end">Rs. {{ number_format($stockTotalValue, 2) }}</td>
                                <td class="text-center">{{ number_format($stockProducts->sum('total_sold')) }}</td>
                                <td class="text-end text-gold">Rs. {{ number_format($stockAllTimeRevenue, 2) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Product Cards - Mobile --}}
        <div class="d-md-none">
            @forelse($stockProducts as $i => $p)
            @php
                $pct = $stockAllTimeRevenue > 0 ? ($p->total_revenue / $stockAllTimeRevenue * 100) : 0;
                if ($p->stock <= 0)     $status = 'out';
                elseif ($p->stock <= 5) $status = 'low';
                else                    $status = 'in';
            @endphp
            <div class="pos-card card mb-2 stock-row-mobile"
                 data-name="{{ strtolower($p->name) }}"
                 data-sku="{{ strtolower($p->sku ?? '') }}"
                 data-cat="{{ $p->category ?? '' }}"
                 data-status="{{ $status }}">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-truncate">{{ $p->name }}</div>
                            <small class="text-muted">{{ $p->sku ?? '—' }} · {{ $p->category ?? '—' }}</small>
                        </div>
                        <div class="ms-2 flex-shrink-0">
                            @if($status === 'out')
                                <span class="badge fw-semibold" style="background:#fee2e2;color:#ef4444;font-size:0.7rem;">Out</span>
                            @elseif($status === 'low')
                                <span class="badge fw-semibold" style="background:#fef3c7;color:#d97706;font-size:0.7rem;">Low</span>
                            @else
                                <span class="badge fw-semibold" style="background:#dcfce7;color:#10b981;font-size:0.7rem;">In Stock</span>
                            @endif
                        </div>
                    </div>
                    <div class="row g-1 text-center" style="font-size:0.78rem;">
                        <div class="col-3">
                            <div class="text-muted">Price</div>
                            <div class="fw-semibold">Rs.{{ number_format($p->price, 0) }}</div>
                        </div>
                        <div class="col-2">
                            <div class="text-muted">Stock</div>
                            <div class="fw-bold {{ $p->stock <= 0 ? 'text-danger' : ($p->stock <= 5 ? 'text-warning' : '') }}">{{ $p->stock }}</div>
                        </div>
                        <div class="col-3">
                            <div class="text-muted">Sold</div>
                            <div class="fw-semibold">{{ $p->total_sold > 0 ? number_format($p->total_sold) : '—' }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted">Revenue</div>
                            <div class="fw-semibold {{ $p->total_revenue > 0 ? 'text-gold' : 'text-muted' }}">{{ $p->total_revenue > 0 ? 'Rs.' . number_format($p->total_revenue, 0) : '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-4 text-muted">No products found.</div>
            @endforelse

            {{-- Mobile Totals --}}
            @if($stockProducts->count())
            <div class="pos-card card mt-2">
                <div class="card-body py-2 px-3">
                    <div class="row g-1 text-center" style="font-size:0.8rem;font-weight:700;">
                        <div class="col-4">
                            <div class="text-muted fw-normal">Total Stock</div>
                            <div>{{ $stockProducts->sum('stock') }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted fw-normal">Inv. Value</div>
                            <div>Rs.{{ number_format($stockTotalValue, 0) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted fw-normal">Revenue</div>
                            <div class="text-gold">Rs.{{ number_format($stockAllTimeRevenue, 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

    </div> {{-- /stock tab --}}
</div>
@endsection

@push('scripts')
<script>
    // Daily Hourly Chart
    const hourlyCtx = document.getElementById('dailyHourlyChart')?.getContext('2d');
    if (hourlyCtx) {
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: ['9AM','10AM','11AM','12PM','1PM','2PM','3PM','4PM','5PM','6PM'],
                datasets: [{
                    label: 'Sales (Rs.)',
                    data: @json(array_values($hourlySales)),
                    backgroundColor: 'rgba(245,166,35,0.6)',
                    borderColor: '#F5A623',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rs.' + (v/1000) + 'K' } } }
            }
        });
    }

    // Daily Payment Chart
    const dailyPayCtx = document.getElementById('dailyPaymentChart')?.getContext('2d');
    if (dailyPayCtx) {
        new Chart(dailyPayCtx, {
            type: 'doughnut',
            data: {
                labels: @json(collect($payments)->filter(fn($amount) => abs($amount) > 0.01)->keys()->map(fn($method) => ucfirst($method))->values()),
                datasets: [{
                    data: @json(collect($payments)->filter(fn($amount) => abs($amount) > 0.01)->values()->map(fn($amount) => abs($amount))->values()),
                    backgroundColor: ['#10B981', '#3B82F6', '#F5A623', '#F59E0B', '#6B7280'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: { legend: { display: false } }
            }
        });
    }

    // Monthly chart
    const monthlyCtx = document.getElementById('monthlyChart')?.getContext('2d');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: @json($monthlyChartLabels),
                datasets: [{
                    label: 'Daily Revenue (Rs.)',
                    data: @json($monthlyChartData),
                    backgroundColor: 'rgba(245,166,35,0.5)',
                    borderColor: '#F5A623',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rs.' + (v/1000) + 'K' } } }
            }
        });
    }

    // Custom range chart
    const customCtx = document.getElementById('customChart')?.getContext('2d');
    if (customCtx) {
        new Chart(customCtx, {
            type: 'line',
            data: {
                labels: @json($customChartLabels),
                datasets: [{
                    label: 'Revenue (Rs.)',
                    data: @json($customChartData),
                    borderColor: '#F5A623',
                    backgroundColor: 'rgba(245,166,35,0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#F5A623'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rs.' + (v/1000).toFixed(1) + 'K' } } }
            }
        });
    }
    // Sync Shopify
    function syncShopify() {
        const btn = document.getElementById('sync-btn');
        const icon = btn.querySelector('i');
        
        if (!confirm('This will fetch latest products from Shopify. Continue?')) return;

        btn.disabled = true;
        icon.classList.add('fa-spin');
        btn.innerHTML = '<i class="fas fa-sync-alt fa-spin me-1"></i> Syncing...';

        fetch('{{ route("shopify.sync") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.disabled) {
                alert(data.message);
            } else if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Sync Failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Network Error during sync.');
            console.error(error);
        })
        .finally(() => {
            btn.disabled = false;
            icon.classList.remove('fa-spin');
            btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Sync Shopify';
        });
    }

    // ---- Active tab detection helper ----
    function getActiveTab() {
        const panes = ['daily','monthly','custom','stock'];
        for (const id of panes) {
            const el = document.getElementById(id);
            if (el && el.classList.contains('show') && el.classList.contains('active')) return id;
        }
        return 'daily';
    }

    function buildQueryParams(tab) {
        let p = '?tab=' + encodeURIComponent(tab);
        if (tab === 'daily') {
            const d = document.querySelector('input[name="date"]');
            if (d?.value) p += '&date=' + encodeURIComponent(d.value);
        } else if (tab === 'monthly') {
            const m = document.querySelector('select[name="month"]');
            if (m?.value) p += '&month=' + encodeURIComponent(m.value);
        } else if (tab === 'custom') {
            const f = document.querySelector('input[name="date_from"]');
            const t = document.querySelector('input[name="date_to"]');
            if (f?.value) p += '&date_from=' + encodeURIComponent(f.value);
            if (t?.value) p += '&date_to='   + encodeURIComponent(t.value);
        }
        return p;
    }

    // ---- Export PDF ----
    function exportPDF()        { exportReportPDF(getActiveTab()); }
    function exportReportPDF(tab) {
        window.location.href = '{{ route("reports.exportPdf") }}' + buildQueryParams(tab);
    }

    // ---- Export Excel ----
    function exportExcel()        { exportReportExcel(getActiveTab()); }
    function exportReportExcel(tab) {
        window.location.href = '{{ route("reports.exportExcel") }}' + buildQueryParams(tab);
    }

    // ---- Stock table live filter ----
    function filterStockTable() {
        const q   = document.getElementById('stockSearch')?.value.toLowerCase()   ?? '';
        const cat = document.getElementById('stockCategoryFilter')?.value         ?? '';
        const st  = document.getElementById('stockStatusFilter')?.value           ?? '';
        // Desktop table rows
        document.querySelectorAll('#stock-table .stock-row').forEach(row => {
            const matchQ   = !q   || row.dataset.name.includes(q) || row.dataset.sku.includes(q);
            const matchCat = !cat || row.dataset.cat === cat;
            const matchSt  = !st  || row.dataset.status === st;
            row.style.display = (matchQ && matchCat && matchSt) ? '' : 'none';
        });
        // Mobile cards
        document.querySelectorAll('.stock-row-mobile').forEach(card => {
            const matchQ   = !q   || card.dataset.name.includes(q) || card.dataset.sku.includes(q);
            const matchCat = !cat || card.dataset.cat === cat;
            const matchSt  = !st  || card.dataset.status === st;
            card.style.display = (matchQ && matchCat && matchSt) ? '' : 'none';
        });
    }
</script>
@endpush
