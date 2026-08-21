{{-- ============================================
POS Sales Log - Admin Only - Jewellery POS
Shopify-style orders list for POS transactions
============================================ --}}
@extends('layouts.pos')
@section('title', 'POS Sales Log')

@section('content')
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h4><i class="fas fa-store me-2 text-gold"></i> POS Sales Log</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">POS Sales Log</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-danger text-white px-3 py-2 fs-6">
                <i class="fas fa-shield-alt me-1"></i> Admin Only
            </span>
            <a href="{{ route('sales.index') }}" class="btn btn-outline-gold btn-sm">
                <i class="fas fa-receipt me-1"></i> Full Sales History
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4 align-items-stretch">
        <div class="col-6 col-md-3">
            <div class="stat-card gold p-3 h-100 d-flex align-items-center gap-3 pos-log-summary-card">
                <div class="stat-icon gold-bg"><i class="fas fa-cash-register"></i></div>
                <div>
                    <div class="stat-label">Total POS Orders</div>
                    <div class="stat-value">{{ $totalOrders }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card blue p-3 h-100 d-flex align-items-center gap-3 pos-log-summary-card">
                <div class="stat-icon blue-bg"><i class="fas fa-rupee-sign"></i></div>
                <div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value" style="font-size:1rem;">Rs. {{ number_format($totalRevenue, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card green p-3 h-100 d-flex align-items-center gap-3 pos-log-summary-card">
                <div class="stat-icon green-bg"><i class="fas fa-tags"></i></div>
                <div>
                    <div class="stat-label">Total Discounts</div>
                    <div class="stat-value" style="font-size:1rem;">Rs. {{ number_format($totalDiscounts, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card warning p-3 h-100 d-flex align-items-center gap-3 pos-log-summary-card">
                <div class="stat-icon warning-bg"><i class="fas fa-box"></i></div>
                <div>
                    <div class="stat-label">Items Sold</div>
                    <div class="stat-value">{{ $totalItems }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="pos-card card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('pos.log') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold">Payment</label>
                        <select name="payment_method" class="form-select">
                            <option value="">All Methods</option>
                            <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="koko" {{ request('payment_method') === 'koko' ? 'selected' : '' }}>Koko</option>
                            <option value="mintpay" {{ request('payment_method') === 'mintpay' ? 'selected' : '' }}>Mint Pay</option>
                            <option value="bank" {{ request('payment_method') === 'bank' ? 'selected' : '' }}>Bank</option>
                            <option value="card" {{ request('payment_method') === 'card' ? 'selected' : '' }}>Card</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold">Staff</label>
                        <select name="staff_id" class="form-select">
                            <option value="">All Staff</option>
                            @foreach($staffList as $staff)
                                <option value="{{ $staff->id }}" {{ request('staff_id') == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-dark-blue flex-grow-1">
                            <i class="fas fa-search me-1"></i> Filter
                        </button>
                        <a href="{{ route('pos.log') }}" class="btn btn-outline-secondary" title="Clear">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders List (Shopify-style) -->
    <div class="pos-card card">
        <!-- Orders Header Bar -->
        <div class="card-header d-flex align-items-center justify-content-between px-4 py-3"
            style="background: var(--dark-blue); border-radius: 12px 12px 0 0;">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-list text-gold"></i>
                <span class="fw-bold text-white">Orders</span>
                <span class="badge badge-gold ms-1">{{ $sales->total() }}</span>
            </div>
            <small class="text-white opacity-75">
                Showing {{ $sales->firstItem() ?? 0 }}–{{ $sales->lastItem() ?? 0 }} of {{ $sales->total() }}
            </small>
        </div>

        <div class="card-body p-0">
            @forelse($sales as $sale)
                @php
                    $payMap = [
                        'cash'    => ['label'=>'Cash',         'bg'=>'#198754','color'=>'#fff','icon'=>'fas fa-money-bill-wave'],
                        'koko'    => ['label'=>'Koko',         'bg'=>'#0d6efd','color'=>'#fff','icon'=>'fas fa-mobile-alt'],
                        'mintpay' => ['label'=>'Mint Pay',     'bg'=>'#e67e00','color'=>'#fff','icon'=>'fas fa-credit-card'],
                        'bank'    => ['label'=>'Bank Deposit', 'bg'=>'#0e7490','color'=>'#fff','icon'=>'fas fa-university'],
                        'card'    => ['label'=>'Card',         'bg'=>'#6c757d','color'=>'#fff','icon'=>'fas fa-credit-card'],
                    ];
                    $pay = $payMap[$sale->payment_method] ?? ['label'=>ucfirst($sale->payment_method),'bg'=>'#6c757d','color'=>'#fff','icon'=>'fas fa-wallet'];
                @endphp
                <div class="pos-order-row border-bottom" id="order-{{ $sale->id }}">

                    {{-- ── DESKTOP layout (md+) ── --}}
                    <div class="d-none d-md-flex align-items-start gap-3 px-4 py-3">

                        <!-- Order Number + Date -->
                        <div class="pos-order-id-col">
                            <div class="fw-bold text-primary" style="font-size:0.95rem;">
                                #{{ $sale->invoice_number ?? ('POS-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT)) }}
                            </div>
                            <small class="text-muted">
                                {{ $sale->created_at->format('M d, Y') }}<br>
                                {{ $sale->created_at->format('h:i A') }}
                            </small>
                        </div>

                        <!-- Items -->
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                @foreach($sale->items as $item)
                                    <span class="badge bg-light text-dark border" style="font-size:0.78rem;">
                                        {{ $item->product->name ?? $item->item_label ?? 'Deleted Product' }}
                                        @if($item->variant)
                                            @php $vLabel = collect([$item->variant->color, $item->variant->size])->filter()->implode(' / '); @endphp
                                            <span class="text-primary">({{ $vLabel }})</span>
                                        @endif
                                        <span class="text-muted">× {{ $item->qty }}</span>
                                    </span>
                                @endforeach
                            </div>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <small class="text-muted"><i class="fas fa-user me-1 text-gold"></i>{{ $sale->user->name ?? 'Unknown Staff' }}</small>
                                <small class="text-muted"><i class="fas fa-box me-1"></i>{{ $sale->items->sum('qty') }} item(s)</small>
                                @if($sale->notes)
                                    <small class="text-muted" title="{{ $sale->notes }}"><i class="fas fa-comment me-1"></i>{{ Str::limit($sale->notes, 40) }}</small>
                                @endif
                            </div>
                        </div>

                        <!-- Financials -->
                        <div class="text-end" style="min-width:180px;">
                            <div class="mb-1">
                                <span class="badge fw-semibold"
                                    style="background:{{ $pay['bg'] }};color:{{ $pay['color'] }};font-size:0.78rem;padding:0.3rem 0.65rem;">
                                    <i class="{{ $pay['icon'] }} me-1"></i> {{ $pay['label'] }}
                                </span>
                            </div>
                            @if($sale->discount_amount > 0)
                                <div class="text-muted" style="font-size:0.78rem;text-decoration:line-through;">Rs. {{ number_format($sale->subtotal, 2) }}</div>
                                <div class="text-danger" style="font-size:0.78rem;"><i class="fas fa-tag me-1"></i>-Rs. {{ number_format($sale->discount_amount, 2) }}@if($sale->discount_type === 'percentage') ({{ $sale->discount_value }}%)@endif</div>
                            @endif
                            <div class="fw-bold text-gold" style="font-size:1.05rem;">Rs. {{ number_format($sale->total, 2) }}</div>
                            <div class="mt-2 d-flex justify-content-end gap-1">
                                <a href="{{ route('invoice.show', ['id' => $sale->id]) }}"
                                   class="btn btn-sm btn-outline-primary js-invoice-preview"
                                   title="View Invoice"
                                   data-invoice-url="{{ route('invoice.show', ['id' => $sale->id, 'embed' => 1]) }}"
                                   data-invoice-number="#{{ $sale->invoice_number ?? ('POS-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT)) }}">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('invoice.show', ['id' => $sale->id]) }}" target="_blank" class="btn btn-sm btn-outline-gold" title="Print Invoice"><i class="fas fa-print"></i></a>
                            </div>
                        </div>
                    </div>

                    {{-- ── MOBILE layout (< md) ── --}}
                    <div class="d-md-none px-3 py-3">

                        {{-- Row 1: Invoice # | Payment badge | Amount --}}
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-primary" style="font-size:0.9rem;">
                                #{{ $sale->invoice_number ?? ('POS-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT)) }}
                            </strong>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge fw-semibold"
                                      style="background:{{ $pay['bg'] }};color:{{ $pay['color'] }};font-size:0.7rem;padding:0.2rem 0.5rem;">
                                    <i class="{{ $pay['icon'] }} me-1"></i>{{ $pay['label'] }}
                                </span>
                                <span class="fw-bold text-gold" style="font-size:0.95rem;">Rs.&nbsp;{{ number_format($sale->total, 2) }}</span>
                            </div>
                        </div>

                        {{-- Row 2: Date + Staff + Items --}}
                        <div class="text-muted mb-2" style="font-size:0.76rem;">
                            <i class="fas fa-calendar-day me-1"></i>{{ $sale->created_at->format('d M Y, h:i A') }}
                            &nbsp;&middot;&nbsp;
                            <i class="fas fa-user me-1 text-gold"></i>{{ $sale->user->name ?? 'Staff' }}
                            &nbsp;&middot;&nbsp;
                            {{ $sale->items->sum('qty') }}&nbsp;item(s)
                        </div>

                        {{-- Row 3: Product badges --}}
                        <div class="d-flex flex-wrap gap-1 mb-2">
                            @foreach($sale->items as $item)
                                <span class="badge bg-light text-dark border" style="font-size:0.76rem;white-space:normal;text-align:left;">
                                    {{ $item->product->name ?? $item->item_label ?? 'Deleted Product' }}
                                    @if($item->variant)
                                        @php $vLabel = collect([$item->variant->color, $item->variant->size])->filter()->implode(' / '); @endphp
                                        <span class="text-primary">({{ $vLabel }})</span>
                                    @endif
                                    <span class="text-muted">× {{ $item->qty }}</span>
                                </span>
                            @endforeach
                        </div>

                        {{-- Row 4: Discount (if any) --}}
                        @if($sale->discount_amount > 0)
                        <div class="text-danger mb-2" style="font-size:0.76rem;">
                            <i class="fas fa-tag me-1"></i>Discount: -Rs.&nbsp;{{ number_format($sale->discount_amount, 2) }}
                            @if($sale->discount_type === 'percentage') ({{ $sale->discount_value }}%) @endif
                        </div>
                        @endif

                        {{-- Row 5: Notes (if any) --}}
                        @if($sale->notes)
                        <div class="text-muted mb-2" style="font-size:0.76rem;">
                            <i class="fas fa-comment me-1"></i>{{ Str::limit($sale->notes, 60) }}
                        </div>
                        @endif

                        {{-- Row 6: Action buttons --}}
                        <div class="d-flex gap-2">
                            <a href="{{ route('invoice.show', ['id' => $sale->id]) }}"
                               class="btn btn-sm btn-outline-primary flex-fill js-invoice-preview"
                               data-invoice-url="{{ route('invoice.show', ['id' => $sale->id, 'embed' => 1]) }}"
                               data-invoice-number="#{{ $sale->invoice_number ?? ('POS-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT)) }}">
                                <i class="fas fa-eye me-1"></i>View
                            </a>
                            <a href="{{ route('invoice.show', ['id' => $sale->id]) }}" target="_blank"
                               class="btn btn-sm btn-outline-gold flex-fill">
                                <i class="fas fa-print me-1"></i>Print
                            </a>
                        </div>
                    </div>

                </div>
            @empty
                <div class="text-center py-5">
                    <i class="fas fa-store fa-3x mb-3 d-block text-muted opacity-25"></i>
                    <p class="text-muted mb-0">No POS orders found.</p>
                    <small class="text-muted">Complete a sale from the <a href="{{ route('pos.index') }}">POS Billing</a>
                        screen.</small>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center p-3 border-top flex-wrap gap-2">
            <small class="text-muted">
                Showing {{ $sales->firstItem() ?? 0 }}–{{ $sales->lastItem() ?? 0 }} of {{ $sales->total() }} orders
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0 flex-wrap">
                    @if ($sales->onFirstPage())
                        <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $sales->previousPageUrl() }}">&laquo;</a></li>
                    @endif
                    @foreach ($sales->getUrlRange(1, $sales->lastPage()) as $page => $url)
                        @if ($page == $sales->currentPage())
                            <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                    @if ($sales->hasMorePages())
                        <li class="page-item"><a class="page-link" href="{{ $sales->nextPageUrl() }}">&raquo;</a></li>
                    @else
                        <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                    @endif
                </ul>
            </nav>
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
        .pos-order-row {
            transition: background 0.2s ease;
        }

        .pos-order-row:hover {
            background: rgba(245, 166, 35, 0.03);
        }

        .pos-order-row:last-child {
            border-bottom: none !important;
        }

        .pos-order-id-col {
            min-width: 110px;
        }

        .pos-log-summary-card {
            min-height: 50px;
        }

        .pos-log-summary-card > div:last-child {
            min-width: 0;
        }

        .pos-log-summary-card .stat-value {
            white-space: nowrap;
        }

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
    </script>
@endpush
