{{-- ============================================
     Dashboard Page - Jewellery POS System
     Real data: summary cards, charts, recent sales
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Dashboard')

@section('content')
<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h4><i class="fas fa-tachometer-alt me-2 text-gold"></i> Dashboard</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>
    </div>
    <div class="d-none d-sm-block">
        <span class="text-muted"><i class="fas fa-calendar me-1"></i> {{ now()->format('l, F d, Y') }}</span>
    </div>
    <div class="d-sm-none mt-1">
        <span class="text-muted" style="font-size:0.8rem;"><i class="fas fa-calendar me-1"></i> {{ now()->format('M d, Y') }}</span>
    </div>
</div>

<!-- ---- Summary Stat Cards ---- -->
<div class="row g-3 mb-4">
    <!-- Today's Sales -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card gold p-3 animate-fade-in">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Today's Sales</div>
                    <div class="stat-value">Rs. {{ number_format($todaySales, 2) }}</div>
                    @if($yesterdaySales > 0)
                        @php $changePercent = round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1); @endphp
                        <small class="{{ $changePercent >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="fas fa-arrow-{{ $changePercent >= 0 ? 'up' : 'down' }} me-1"></i>{{ abs($changePercent) }}% from yesterday
                        </small>
                    @else
                        <small class="text-muted">{{ $todayCount }} transactions today</small>
                    @endif
                </div>
                <div class="stat-icon gold-bg">
                    <i class="fas fa-shopping-bag"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Revenue -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card blue p-3 animate-fade-in animate-fade-in-delay-1">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">This Month Revenue</div>
                    <div class="stat-value">Rs. {{ number_format($monthRevenue, 2) }}</div>
                    @if($lastMonthRevenue > 0)
                        @php $monthChange = round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1); @endphp
                        <small class="{{ $monthChange >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="fas fa-arrow-{{ $monthChange >= 0 ? 'up' : 'down' }} me-1"></i>{{ abs($monthChange) }}% vs last month
                        </small>
                    @else
                        <small class="text-muted">{{ now()->format('F Y') }}</small>
                    @endif
                </div>
                <div class="stat-icon blue-bg">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Products -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card green p-3 animate-fade-in animate-fade-in-delay-2">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value">{{ $totalProducts }}</div>
                    <small class="text-muted">{{ $totalCategories }} categories</small>
                </div>
                <div class="stat-icon green-bg">
                    <i class="fas fa-tshirt"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card red p-3 animate-fade-in animate-fade-in-delay-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Low Stock Alerts</div>
                    <div class="stat-value text-danger">{{ $lowStockCount }}</div>
                    @if($outOfStock > 0)
                        <small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>{{ $outOfStock }} out of stock</small>
                    @else
                        <small class="text-muted">All items available</small>
                    @endif
                </div>
                <div class="stat-icon red-bg">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ---- Payment Method Breakdown Cards ---- -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card gold p-3">
            <div class="d-flex align-items-center">
                <div class="stat-icon gold-bg me-3"><i class="fas fa-money-bill-wave"></i></div>
                <div>
                    <div class="stat-label">Cash</div>
                    <div class="stat-value" style="font-size:1.2rem;">Rs. {{ number_format($paymentBreakdown['cash'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card blue p-3">
            <div class="d-flex align-items-center">
                <div class="stat-icon blue-bg me-3"><i class="fas fa-mobile-alt"></i></div>
                <div>
                    <div class="stat-label">Koko</div>
                    <div class="stat-value" style="font-size:1.2rem;">Rs. {{ number_format($paymentBreakdown['koko'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card green p-3">
            <div class="d-flex align-items-center">
                <div class="stat-icon green-bg me-3"><i class="fas fa-credit-card"></i></div>
                <div>
                    <div class="stat-label">Mint Pay</div>
                    <div class="stat-value" style="font-size:1.2rem;">Rs. {{ number_format($paymentBreakdown['mintpay'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card warning p-3">
            <div class="d-flex align-items-center">
                <div class="stat-icon warning-bg me-3"><i class="fas fa-university"></i></div>
                <div>
                    <div class="stat-label">Bank Deposit</div>
                    <div class="stat-value" style="font-size:1.2rem;">Rs. {{ number_format($paymentBreakdown['bank'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ---- Charts & Recent Transactions ---- -->
<div class="row g-4 mb-4">
    <!-- Sales Chart -->
    <div class="col-lg-8">
        <div class="pos-card card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fas fa-chart-area me-2 text-gold"></i> Sales Overview (Last 7 Days)</h5>
                </div>
                <canvas id="salesChart" height="110" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Payment Breakdown Pie Chart -->
    <div class="col-lg-4">
        <div class="pos-card card">
            <div class="card-body">
                <h5 class="mb-3"><i class="fas fa-chart-pie me-2 text-gold"></i> Payment Methods</h5>
                <canvas id="paymentChart" height="180" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ---- Recent Transactions Table ---- -->
<div class="row g-4 mb-4">
    <div class="col-lg-8 col-12">
        <div class="pos-card card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fas fa-history me-2 text-gold"></i> Recent Transactions</h5>
                    <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-gold">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table pos-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Staff</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                            <tr>
                                <td><strong class="text-primary">#{{ $sale->invoice_number ?? ('INV-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT)) }}</strong></td>
                                <td>{{ $sale->created_at->format('M d, Y h:i A') }}</td>
                                <td>{{ $sale->user->name ?? 'Staff' }}</td>
                                <td class="fw-bold">Rs. {{ number_format($sale->total, 2) }}</td>
                                <td>
                                    @php
                                        $payLabels = ['cash'=>'Cash','koko'=>'Koko','mintpay'=>'Mint Pay','bank'=>'Bank','card'=>'Card'];
                                        $payColors = ['cash'=>'success','koko'=>'primary','mintpay'=>'warning','bank'=>'info','card'=>'secondary'];
                                    @endphp
                                    <span class="badge bg-{{ $payColors[$sale->payment_method] ?? 'secondary' }}">
                                        {{ $payLabels[$sale->payment_method] ?? ucfirst($sale->payment_method) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('invoice.show', $sale->id) }}"
                                       class="btn btn-sm btn-outline-primary js-invoice-preview"
                                       title="View Invoice"
                                       data-invoice-url="{{ route('invoice.show', ['id' => $sale->id, 'embed' => 1]) }}"
                                       data-invoice-number="#{{ $sale->invoice_number ?? ('INV-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT)) }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-receipt fa-2x mb-2 d-block opacity-25"></i>
                                    No sales yet. <a href="{{ route('pos.index') }}">Make your first sale</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alerts List -->
    <div class="col-lg-4 col-12">
        <div class="pos-card card">
            <div class="card-body">
                <h5 class="mb-3"><i class="fas fa-exclamation-triangle me-2 text-danger"></i> Low Stock Alerts</h5>
                <div class="list-group list-group-flush">
                    @forelse($lowStockProducts as $product)
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                        <div>
                            <strong class="d-block">{{ $product->name }}</strong>
                            <small class="text-muted">SKU: {{ $product->sku ?? 'N/A' }}</small>
                        </div>
                        @if($product->stock <= 0)
                            <span class="badge bg-danger rounded-pill">Out of stock</span>
                        @elseif($product->stock <= 2)
                            <span class="badge bg-danger rounded-pill">{{ $product->stock }} left</span>
                        @else
                            <span class="badge bg-warning text-dark rounded-pill">{{ $product->stock }} left</span>
                        @endif
                    </div>
                    @empty
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2 text-success d-block"></i>
                        All products are well stocked!
                    </div>
                    @endforelse
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-gold w-100 mt-3">
                    View All Products
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Preview Modal -->
<div class="modal fade" id="invoicePreviewModal" tabindex="-1" aria-labelledby="invoicePreviewTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl invoice-preview-dialog">
        <div class="modal-content invoice-preview-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoicePreviewTitle">
                    <i class="fas fa-file-invoice me-2 text-gold"></i> Invoice Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body invoice-preview-body">
                <div class="invoice-preview-loader" id="invoicePreviewLoader">
                    <i class="fas fa-spinner fa-spin me-2"></i> Loading invoice...
                </div>
                <iframe id="invoicePreviewFrame" class="invoice-preview-frame" title="Invoice preview"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-gold" id="invoicePreviewPrint">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .invoice-preview-dialog {
        max-width: min(960px, calc(100vw - 24px));
    }

    .invoice-preview-content {
        overflow: hidden;
    }

    .invoice-preview-body {
        position: relative;
        height: min(76vh, 760px);
        padding: 0;
        background: #f4f6f8;
    }

    .invoice-preview-frame {
        width: 100%;
        height: 100%;
        border: 0;
        display: block;
        background: #f4f6f8;
    }

    .invoice-preview-loader {
        position: absolute;
        inset: 0;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        background: #f4f6f8;
        font-weight: 600;
    }

    @media (max-width: 575.98px) {
        .invoice-preview-dialog {
            margin: 0.5rem;
            max-width: calc(100vw - 1rem);
        }

        .invoice-preview-body {
            height: 74vh;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Sales Overview Chart — Real data from last 7 days
    (function () {
        const modalEl = document.getElementById('invoicePreviewModal');
        const frame = document.getElementById('invoicePreviewFrame');
        const loader = document.getElementById('invoicePreviewLoader');
        const title = document.getElementById('invoicePreviewTitle');
        const printBtn = document.getElementById('invoicePreviewPrint');

        if (!modalEl || !frame || !loader || !title || !printBtn) {
            return;
        }

        const previewModal = bootstrap.Modal.getOrCreateInstance(modalEl);

        document.querySelectorAll('.js-invoice-preview').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                const invoiceUrl = button.dataset.invoiceUrl || button.href;
                const invoiceNumber = button.dataset.invoiceNumber || '';

                loader.style.display = 'flex';
                printBtn.disabled = true;
                const icon = document.createElement('i');
                icon.className = 'fas fa-file-invoice me-2 text-gold';
                title.replaceChildren(icon, document.createTextNode(' Invoice Preview ' + invoiceNumber));
                frame.src = invoiceUrl;

                previewModal.show();
            });
        });

        frame.addEventListener('load', function () {
            loader.style.display = 'none';
            printBtn.disabled = false;
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            frame.removeAttribute('src');
            loader.style.display = 'flex';
            printBtn.disabled = true;
        });

        printBtn.addEventListener('click', function () {
            if (frame.contentWindow) {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            }
        });
    })();

    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($dailySales->pluck('label')) !!},
            datasets: [{
                label: 'Sales (Rs.)',
                data: {!! json_encode($dailySales->pluck('amount')) !!},
                borderColor: '#F5A623',
                backgroundColor: 'rgba(245, 166, 35, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#F5A623',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: function(items) {
                            const idx = items[0].dataIndex;
                            return {!! json_encode($dailySales->pluck('date')) !!}[idx];
                        },
                        label: function(context) {
                            return 'Rs. ' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) return 'Rs. ' + (value / 1000000).toFixed(1) + 'M';
                            if (value >= 1000) return 'Rs. ' + (value / 1000).toFixed(0) + 'K';
                            return 'Rs. ' + value;
                        }
                    }
                }
            }
        }
    });

    // Payment Methods Pie Chart — Real data
    const paymentCtx = document.getElementById('paymentChart').getContext('2d');
    new Chart(paymentCtx, {
        type: 'doughnut',
        data: {
            labels: ['Cash', 'Koko', 'Mint Pay', 'Bank Deposit', 'Card'],
            datasets: [{
                data: [
                    {{ $paymentBreakdown['cash'] ?? 0 }},
                    {{ $paymentBreakdown['koko'] ?? 0 }},
                    {{ $paymentBreakdown['mintpay'] ?? 0 }},
                    {{ $paymentBreakdown['bank'] ?? 0 }},
                    {{ $paymentBreakdown['card'] ?? 0 }}
                ],
                backgroundColor: ['#10B981', '#3B82F6', '#F5A623', '#F59E0B', '#6B7280'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 15, usePointStyle: true }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': Rs. ' + context.parsed.toLocaleString('en-US', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });
</script>
@endpush
