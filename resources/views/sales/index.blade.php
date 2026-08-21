{{-- ============================================
     Sales History Page - Jewellery POS System
     Real data from database with filters
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Sales History')

@section('content')
<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h4><i class="fas fa-receipt me-2 text-gold"></i> Sales History</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Sales History</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Filters -->
<div class="pos-card card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('sales.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold">Invoice No.</label>
                    <div class="input-group">
                        <span class="input-group-text text-muted">#</span>
                        <input type="text" name="invoice" class="form-control" placeholder="e.g. 0023" value="{{ request('invoice') }}">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold">Payment Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="">All Methods</option>
                        <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="koko" {{ request('payment_method') === 'koko' ? 'selected' : '' }}>Koko</option>
                        <option value="mintpay" {{ request('payment_method') === 'mintpay' ? 'selected' : '' }}>Mint Pay</option>
                        <option value="bank" {{ request('payment_method') === 'bank' ? 'selected' : '' }}>Bank Deposit</option>
                        <option value="card" {{ request('payment_method') === 'card' ? 'selected' : '' }}>Card</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-blue flex-grow-1">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary" title="Clear filters">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4 align-items-stretch">
    <div class="col-12 col-sm-4">
        <div class="stat-card gold p-3 h-100 sales-summary-card">
            <div class="stat-label">Total Sales</div>
            <div class="stat-value">{{ number_format($totalSales) }}</div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="stat-card blue p-3 h-100 sales-summary-card">
            <div class="stat-label">Total Revenue (Net)</div>
            <div class="stat-value">Rs. {{ number_format($netRevenue, 2) }}</div>
            @if($totalRefunded > 0)
                <div class="sales-summary-note">-Rs. {{ number_format($totalRefunded, 2) }} Refunds</div>
            @endif
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="stat-card green p-3 h-100 sales-summary-card">
            <div class="stat-label">Average Sale</div>
            <div class="stat-value">Rs. {{ number_format($avgSale, 2) }}</div>
        </div>
    </div>
</div>

<!-- Sales Table -->
<div class="pos-card card">

    {{-- ── Desktop table (md+) ── --}}
    <div class="d-none d-md-block">
        <div class="table-responsive">
            <table class="table pos-table mb-0">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Date & Time</th>
                        <th>Staff</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td><strong class="text-primary">#{{ $sale->invoice_number ?? ('INV-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT)) }}</strong></td>
                        <td>{{ $sale->created_at->format('M d, Y h:i:s A') }}</td>
                        <td>{{ $sale->user->name ?? 'Staff' }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $sale->items->sum('qty') }} items</span>
                        </td>
                        <td class="fw-bold">
                            @if($sale->total !== $sale->net_total)
                                <span class="text-muted text-decoration-line-through small">Rs. {{ number_format($sale->total, 2) }}</span><br>
                            @endif
                            Rs. {{ number_format($sale->net_total, 2) }}
                            @if($sale->discount_amount > 0)
                                <br><small class="text-danger"><i class="fas fa-tag"></i> -Rs. {{ number_format($sale->discount_amount, 2) }}
                                @if($sale->discount_type === 'percentage') ({{ $sale->discount_value }}%) @endif
                                </small>
                            @endif
                            @if($sale->has_return)
                                <br><span class="badge bg-danger mt-1"><i class="fas fa-undo me-1"></i> Returned / Refunded</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $payMap = [
                                    'cash'    => ['label'=>'Cash',         'bg'=>'#198754','color'=>'#fff'],
                                    'koko'    => ['label'=>'Koko',         'bg'=>'#0d6efd','color'=>'#fff'],
                                    'mintpay' => ['label'=>'Mint Pay',     'bg'=>'#e67e00','color'=>'#fff'],
                                    'bank'    => ['label'=>'Bank Deposit', 'bg'=>'#0891b2','color'=>'#fff'],
                                    'card'    => ['label'=>'Card',         'bg'=>'#6c757d','color'=>'#fff'],
                                    'multipay'=> ['label'=>'Multi Pay',    'bg'=>'#f59e0b','color'=>'#fff'],
                                    'payzy'   => ['label'=>'Payzy',        'bg'=>'#f43f5e','color'=>'#fff'],
                                ];
                            @endphp
                            @if($sale->payment_method === 'multipay' && is_array($sale->payment_splits))
                                <div class="d-flex flex-column gap-1">
                                    @foreach($sale->payment_splits as $split)
                                        @php
                                            $spay = $payMap[$split['method'] ?? ''] ?? ['label'=>ucfirst($split['method'] ?? 'Unknown'),'bg'=>'#6c757d','color'=>'#fff'];
                                        @endphp
                                        <span class="badge fw-semibold"
                                              style="background:{{ $spay['bg'] }};color:{{ $spay['color'] }};font-size:0.75rem;padding:0.25rem 0.5rem;text-align:left;">
                                            {{ $spay['label'] }}: {{ number_format($split['amount'] ?? 0) }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                @php
                                    $pay = $payMap[$sale->payment_method] ?? ['label'=>ucfirst($sale->payment_method),'bg'=>'#6c757d','color'=>'#fff'];
                                @endphp
                                <span class="badge fw-semibold"
                                      style="background:{{ $pay['bg'] }};color:{{ $pay['color'] }};font-size:0.78rem;padding:0.3rem 0.65rem;">
                                    {{ $pay['label'] }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('invoice.show', ['id' => $sale->id]) }}"
                               class="btn btn-sm btn-outline-primary js-invoice-preview"
                               title="View Invoice"
                               data-invoice-url="{{ route('invoice.show', ['id' => $sale->id, 'embed' => 1]) }}"
                               data-invoice-number="#{{ $sale->invoice_number ?? ('INV-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT)) }}">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('invoice.show', ['id' => $sale->id]) }}" target="_blank" class="btn btn-sm btn-outline-gold" title="Print">
                                <i class="fas fa-print"></i>
                            </a>
                            @php
                                // Pre-fill priority: number the bill was last SMS'd to,
                                // then the linked customer's number, else empty.
                                $smsBillTo = $sale->smsOutbox->firstWhere('purpose', \App\Models\SmsOutbox::PURPOSE_BILL)?->to_number;
                                $smsPrefill = \App\Services\Sms\SriLankanPhone::tryNormalizeLocal($smsBillTo)
                                    ?? ($sale->customer?->phone ?? '');
                            @endphp
                            <button type="button"
                                    class="btn btn-sm btn-outline-info js-sms-send"
                                    title="Send SMS Bill"
                                    data-sale-id="{{ $sale->id }}"
                                    data-phone="{{ $smsPrefill }}"
                                    data-invoice-number="#{{ $sale->invoice_number ?? ('INV-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT)) }}">
                                <i class="fas fa-comment-dots"></i>
                            </button>
                            @php
                                $alreadyFullyReturned = false;
                                if ($sale->has_return) {
                                    $totalReturnedQty = $sale->returns->flatMap->items->sum('qty');
                                    $totalSaleQty     = $sale->items->sum('qty');
                                    $alreadyFullyReturned = $totalReturnedQty >= $totalSaleQty;
                                }
                            @endphp
                            @if(!$alreadyFullyReturned)
                                <a href="{{ route('returns.create', $sale) }}" class="btn btn-sm btn-outline-danger" title="Process Return">
                                    <i class="fas fa-undo-alt"></i>
                                </a>
                            @else
                                <span class="btn btn-sm btn-outline-secondary disabled" title="Fully Returned">
                                    <i class="fas fa-check"></i>
                                </span>
                            @endif
                            <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this sale? This will restore stock and delete the record permanently.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-dark" title="Delete Sale">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-receipt fa-3x mb-3 d-block opacity-25"></i>
                                <p class="mb-0">No sales found.</p>
                                <small>Complete a sale from the <a href="{{ route('pos.index') }}">POS Billing</a> screen.</small>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Mobile cards (< md) ── --}}
    <div class="d-md-none p-2">
        @forelse($sales as $sale)
    @php
        $payMap = [
            'cash'    => ['label'=>'Cash',         'bg'=>'#198754','color'=>'#fff'],
            'koko'    => ['label'=>'Koko',         'bg'=>'#0d6efd','color'=>'#fff'],
            'mintpay' => ['label'=>'Mint Pay',     'bg'=>'#e67e00','color'=>'#fff'],
            'bank'    => ['label'=>'Bank Deposit', 'bg'=>'#0891b2','color'=>'#fff'],
            'card'    => ['label'=>'Card',         'bg'=>'#6c757d','color'=>'#fff'],
            'multipay'=> ['label'=>'Multi Pay',    'bg'=>'#f59e0b','color'=>'#fff'],
            'payzy'   => ['label'=>'Payzy',        'bg'=>'#f43f5e','color'=>'#fff'],
        ];
        $pay = $payMap[$sale->payment_method] ?? ['label'=>ucfirst($sale->payment_method),'bg'=>'#6c757d','color'=>'#fff'];
        $alreadyFullyReturned = false;
        if ($sale->has_return) {
            $totalReturnedQty = $sale->returns->flatMap->items->sum('qty');
            $totalSaleQty     = $sale->items->sum('qty');
            $alreadyFullyReturned = $totalReturnedQty >= $totalSaleQty;
        }
        // Pre-fill priority: number the bill was last SMS'd to,
        // then the linked customer's number, else empty.
        $smsBillTo = $sale->smsOutbox->firstWhere('purpose', \App\Models\SmsOutbox::PURPOSE_BILL)?->to_number;
        $smsPrefill = \App\Services\Sms\SriLankanPhone::tryNormalizeLocal($smsBillTo)
            ?? ($sale->customer?->phone ?? '');
    @endphp
        <div class="border rounded mb-2 p-3 bg-white" style="box-shadow:0 1px 3px rgba(0,0,0,.06);">
            {{-- Invoice + amount --}}
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <strong class="text-primary" style="font-size:0.9rem;">
                        #{{ $sale->invoice_number ?? ('INV-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT)) }}
                    </strong>
                    @if($sale->payment_method === 'multipay' && is_array($sale->payment_splits))
                        <div class="d-inline-flex flex-wrap gap-1 ms-1 mt-1">
                            @foreach($sale->payment_splits as $split)
                                @php
                                    $spay = $payMap[$split['method'] ?? ''] ?? ['label'=>ucfirst($split['method'] ?? 'Unknown'),'bg'=>'#6c757d','color'=>'#fff'];
                                @endphp
                                <span class="badge fw-semibold"
                                      style="background:{{ $spay['bg'] }};color:{{ $spay['color'] }};font-size:0.65rem;padding:0.2rem 0.4rem;">
                                    {{ $spay['label'] }}: {{ number_format($split['amount'] ?? 0) }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <span class="badge fw-semibold ms-1"
                              style="background:{{ $pay['bg'] }};color:{{ $pay['color'] }};font-size:0.68rem;padding:0.2rem 0.5rem;">
                            {{ $pay['label'] }}
                        </span>
                    @endif
                    @if($sale->has_return)
                        <span class="badge bg-danger ms-1" style="font-size:0.68rem;"><i class="fas fa-undo me-1"></i> Returned</span>
                    @endif
                </div>
                <div class="text-end">
                    @if($sale->total !== $sale->net_total)
                        <span class="text-muted text-decoration-line-through small d-block">Rs.&nbsp;{{ number_format($sale->total, 2) }}</span>
                    @endif
                    <span class="fw-bold" style="font-size:1rem;">Rs.&nbsp;{{ number_format($sale->net_total, 2) }}</span>
                </div>
            </div>

            {{-- Meta: date / staff / qty --}}
            <div class="text-muted mb-2" style="font-size:0.77rem;line-height:1.6;">
                <i class="fas fa-calendar-day me-1"></i>{{ $sale->created_at->format('d M Y, h:i A') }}
                &nbsp;&middot;&nbsp;
                <i class="fas fa-user me-1"></i>{{ $sale->user->name ?? 'Staff' }}
                &nbsp;&middot;&nbsp;
                {{ $sale->items->sum('qty') }}&nbsp;item{{ $sale->items->sum('qty') != 1 ? 's' : '' }}
            </div>

            {{-- Discount --}}
            @if($sale->discount_amount > 0)
            <div class="text-danger mb-2" style="font-size:0.78rem;">
                <i class="fas fa-tag me-1"></i>Discount:&nbsp;-Rs.&nbsp;{{ number_format($sale->discount_amount, 2) }}
                @if($sale->discount_type === 'percentage') ({{ $sale->discount_value }}%) @endif
            </div>
            @endif

            {{-- Action buttons (full-width, labelled) --}}
            <div class="d-flex gap-2 pt-1">
                <a href="{{ route('invoice.show', ['id' => $sale->id]) }}"
                   class="btn btn-sm btn-outline-primary flex-fill js-invoice-preview"
                   data-invoice-url="{{ route('invoice.show', ['id' => $sale->id, 'embed' => 1]) }}"
                   data-invoice-number="#{{ $sale->invoice_number ?? ('INV-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT)) }}">
                    <i class="fas fa-eye me-1"></i>View
                </a>
                <a href="{{ route('invoice.show', ['id' => $sale->id]) }}" target="_blank"
                   class="btn btn-sm btn-outline-gold flex-fill">
                    <i class="fas fa-print me-1"></i>Print
                </a>
                <button type="button"
                        class="btn btn-sm btn-outline-info flex-fill js-sms-send"
                        data-sale-id="{{ $sale->id }}"
                        data-phone="{{ $smsPrefill }}"
                        data-invoice-number="#{{ $sale->invoice_number ?? ('INV-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT)) }}">
                    <i class="fas fa-comment-dots me-1"></i>SMS
                </button>
                @if(!$alreadyFullyReturned)
                    <a href="{{ route('returns.create', $sale) }}"
                       class="btn btn-sm btn-outline-danger flex-fill">
                        <i class="fas fa-undo-alt me-1"></i>Return
                    </a>
                @else
                    <span class="btn btn-sm btn-outline-secondary disabled flex-fill" title="Fully Returned">
                        <i class="fas fa-check me-1"></i>Returned
                    </span>
                @endif
                <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" class="flex-fill d-flex" onsubmit="return confirm('Are you sure you want to delete this sale? This will restore stock and delete the record permanently.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-dark w-100">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="fas fa-receipt fa-3x mb-3 d-block opacity-25"></i>
            <p class="mb-0">No sales found.</p>
            <small>Complete a sale from the <a href="{{ route('pos.index') }}">POS Billing</a> screen.</small>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top flex-wrap gap-2">
        <small class="text-muted">
            Showing {{ $sales->firstItem() ?? 0 }}&ndash;{{ $sales->lastItem() ?? 0 }} of {{ $sales->total() }} sales
        </small>
        <nav>
            <ul class="pagination pagination-sm mb-0 flex-wrap">
                {{-- Previous --}}
                @if ($sales->onFirstPage())
                    <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $sales->previousPageUrl() }}">&laquo;</a></li>
                @endif

                {{-- Page Numbers --}}
                @foreach ($sales->getUrlRange(1, $sales->lastPage()) as $page => $url)
                    @if ($page == $sales->currentPage())
                        <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach

                {{-- Next --}}
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

<!-- Send SMS Bill Modal (reuses the POS bill-SMS flow: confirm → queue → live status) -->
<div class="modal fade" id="salesSmsModal" tabindex="-1" aria-labelledby="salesSmsTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="salesSmsTitle">
                    <i class="fas fa-comment-dots me-2 text-gold"></i> Send Bill via SMS
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1: confirm/edit the number (pre-filled when the sale has one) -->
                <div id="ssms-step-input">
                    <div style="font-size:0.8rem;color:#64748b;margin-bottom:10px;">Send the bill to this customer mobile number — confirm or change it</div>
                    <div class="d-flex gap-2 mb-2">
                        <input type="text" id="ssms-phone-number" class="form-control" placeholder="07X XXX XXXX" style="border-radius:6px;font-size:0.9rem;">
                        <button type="button" id="ssms-send-btn" class="btn btn-primary" onclick="salesSmsSend()" style="border-radius:6px;font-weight:600;display:flex;align-items:center;gap:6px;white-space:nowrap;">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                    </div>
                    <div id="ssms-input-error" style="display:none;font-size:0.75rem;color:#dc2626;margin-bottom:8px;"></div>
                </div>

                <!-- Step 2: live status -->
                <div id="ssms-step-status" style="display:none;">
                    <div id="ssms-status-content" style="font-size:0.9rem;margin-bottom:10px;"></div>
                    <div class="d-flex gap-2">
                        <button type="button" id="ssms-retry-btn" class="btn btn-outline-danger btn-sm" onclick="salesSmsSend()" style="display:none;border-radius:6px;font-weight:600;">
                            <i class="fas fa-redo"></i> Retry
                        </button>
                        <button type="button" id="ssms-change-btn" class="btn btn-outline-secondary btn-sm" onclick="salesSmsShowStep('input')" style="display:none;border-radius:6px;">
                            Change number
                        </button>
                    </div>
                </div>

                <div style="font-size:0.7rem;color:#94a3b8;display:flex;align-items:center;gap:4px;margin-top:12px;">
                    <i class="fas fa-shield-alt"></i> We respect your privacy. Your number will only be used to send this bill.
                </div>
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

    .sales-summary-card {
        min-height: 110px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .sales-summary-note {
        min-height: 18px;
        margin-top: 4px;
        color: #6b7280;
        font-size: 0.78rem;
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
<script>
    // ---- Send SMS Bill (same backend flow as the POS success modal:
    //      confirm → queue → live status via /api/invoices/...) ----
    let ssmsSaleId = null;
    let ssmsNormalizedPhone = null;
    let ssmsPollTimer = null;
    let ssmsInFlight = false;

    // Mirrors App\Services\Sms\SriLankanPhone (server re-validates authoritatively)
    function ssmsPhoneNormalize(raw) {
        const digits = (raw || '').replace(/\D+/g, '');
        let sub;
        if (digits.startsWith('0094')) sub = digits.slice(4);
        else if (digits.startsWith('94') && digits.length === 11) sub = digits.slice(2);
        else if (digits.startsWith('0') && digits.length === 10) sub = digits.slice(1);
        else sub = digits;
        if (sub.length !== 9) return null;
        if (!['70','71','72','74','75','76','77','78'].includes(sub.slice(0, 2))) return null;
        return '94' + sub;
    }

    function ssmsPhoneMask(n) { return n.slice(0, 4) + '•••' + n.slice(-4); }

    function salesSmsShowStep(step) {
        ['input', 'status'].forEach(s => {
            document.getElementById('ssms-step-' + s).style.display = (s === step) ? 'block' : 'none';
        });
        if (step !== 'status' && ssmsPollTimer) { clearInterval(ssmsPollTimer); ssmsPollTimer = null; }
    }

    async function salesSmsSend() {
        if (!ssmsSaleId) return;
        if (ssmsInFlight) return; // double-click guard: one tap, one send
        ssmsInFlight = true;

        const err = document.getElementById('ssms-input-error');
        const normalized = ssmsPhoneNormalize(document.getElementById('ssms-phone-number').value);
        if (!normalized) {
            err.textContent = 'Not a valid Sri Lankan mobile number (07X XXX XXXX).';
            err.style.display = 'block';
            salesSmsShowStep('input');
            ssmsInFlight = false;
            return;
        }
        err.style.display = 'none';
        ssmsNormalizedPhone = normalized;

        const sendBtn = document.getElementById('ssms-send-btn');
        const retryBtn = document.getElementById('ssms-retry-btn');
        sendBtn.disabled = true;
        retryBtn.disabled = true;

        salesSmsShowStep('status');
        salesSmsRenderStatus('pending', { to_masked: ssmsPhoneMask(ssmsNormalizedPhone) });

        try {
            const resp = await fetch(`/api/invoices/${ssmsSaleId}/send-sms`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                // resend: an already-sent bill may deliberately be sent again
                body: JSON.stringify({ phone: ssmsNormalizedPhone, resend: true })
            });
            const data = await resp.json();

            if (resp.ok && data.success) {
                salesSmsRenderStatus(data.status, data);
                if (data.status === 'pending') salesSmsStartPolling(ssmsSaleId);
            } else {
                err.textContent = data.message || data.error || 'Failed to queue SMS.';
                err.style.display = 'block';
                salesSmsShowStep('input');
            }
        } catch (e) {
            salesSmsRenderStatus('failed', { error: 'Network error — check the connection and retry.' });
        }

        sendBtn.disabled = false;
        retryBtn.disabled = false;
        ssmsInFlight = false;
    }

    // The live queue is drained by cron, so a healthy send can take ~60s.
    // Keep watching past the server's stall window (SmsOutbox::WORKER_STALL_SECONDS)
    // so a genuine failure still surfaces, and never call it failed ourselves.
    const SSMS_POLL_INTERVAL_MS = 3000;
    const SSMS_POLL_WINDOW_MS = 180000; // 3 minutes

    function salesSmsStartPolling(saleId) {
        if (ssmsPollTimer) clearInterval(ssmsPollTimer);
        const deadline = Date.now() + SSMS_POLL_WINDOW_MS;
        ssmsPollTimer = setInterval(async () => {
            try {
                const resp = await fetch(`/api/invoices/${saleId}/sms-status`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await resp.json();
                if (data.status && data.status !== 'pending') {
                    clearInterval(ssmsPollTimer);
                    ssmsPollTimer = null;
                    salesSmsRenderStatus(data.status, data);
                    return;
                }
            } catch (e) { /* transient poll error — keep polling */ }

            // Still pending after the whole window: stop polling, but this is
            // NOT a failure — the job is queued and will keep sending.
            if (Date.now() >= deadline) {
                clearInterval(ssmsPollTimer);
                ssmsPollTimer = null;
                salesSmsRenderStatus('queued_slow', {});
            }
        }, SSMS_POLL_INTERVAL_MS);
    }

    function salesSmsRenderStatus(status, data) {
        const el = document.getElementById('ssms-status-content');
        const retryBtn = document.getElementById('ssms-retry-btn');
        const changeBtn = document.getElementById('ssms-change-btn');
        retryBtn.style.display = 'none';
        changeBtn.style.display = 'none';

        if (status === 'pending') {
            el.innerHTML = '<span style="color:#64748b;"><i class="fas fa-circle-notch fa-spin"></i> Sending to <b>' + (data.to_masked || '') + '</b>…</span>' +
                '<div style="font-size:0.75rem;color:#94a3b8;margin-top:4px;">This can take up to a minute — you can keep working.</div>';
        } else if (status === 'sent') {
            el.innerHTML = '<span style="color:#16a34a;font-weight:600;"><i class="fas fa-check-circle"></i> Bill sent to <b>' + (data.to_masked || '') + '</b> ✓</span>';
        } else if (status === 'failed') {
            el.innerHTML = '<span style="color:#dc2626;font-weight:600;"><i class="fas fa-times-circle"></i> Failed to send</span>' +
                (data.error ? '<div style="font-size:0.75rem;color:#991b1b;margin-top:4px;"></div>' : '');
            if (data.error) el.lastElementChild.textContent = data.error;
            retryBtn.style.display = 'inline-block';
            changeBtn.style.display = 'inline-block';
        } else {
            el.innerHTML = '<span style="color:#64748b;"><i class="fas fa-clock"></i> Still queued — it will keep sending in the background.</span>';
            changeBtn.style.display = 'inline-block';
        }
    }

    (function () {
        const modalEl = document.getElementById('salesSmsModal');
        if (!modalEl) return;
        const smsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        const title = document.getElementById('salesSmsTitle');

        document.querySelectorAll('.js-sms-send').forEach(function (button) {
            button.addEventListener('click', function () {
                ssmsSaleId = button.dataset.saleId;
                ssmsNormalizedPhone = null;

                const icon = document.createElement('i');
                icon.className = 'fas fa-comment-dots me-2 text-gold';
                title.replaceChildren(icon, document.createTextNode(' Send Bill via SMS ' + (button.dataset.invoiceNumber || '')));

                document.getElementById('ssms-input-error').style.display = 'none';

                // Same popup for every sale — the field is simply pre-filled
                // when the sale (last SMS'd number or linked customer) has one.
                document.getElementById('ssms-phone-number').value = button.dataset.phone || '';
                salesSmsShowStep('input');

                smsModal.show();
            });
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            if (ssmsPollTimer) { clearInterval(ssmsPollTimer); ssmsPollTimer = null; }
        });
    })();
</script>
@endpush
