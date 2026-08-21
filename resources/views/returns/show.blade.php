@extends('layouts.pos')
@section('title', 'Return #' . $return->id)

@section('content')
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h4><i class="fas fa-undo-alt me-2 text-gold"></i> Return #{{ $return->id }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('returns.index') }}">Returns</a></li>
                    <li class="breadcrumb-item active">Return #{{ $return->id }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <button class="btn btn-gold btn-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Print
            </button>
            <a href="{{ route('sales.show', $return->sale_id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-file-invoice me-1"></i> Original Invoice
            </a>
            <a href="{{ route('returns.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Return Details Card --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-danger text-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-undo-alt me-2"></i>Return Summary</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6 text-muted small">Return ID</dt>
                        <dd class="col-6 fw-bold">#{{ $return->id }}</dd>

                        <dt class="col-6 text-muted small">Original Sale</dt>
                        <dd class="col-6">
                            <a href="{{ route('sales.show', $return->sale_id) }}" class="fw-semibold text-decoration-none">
                                {{ $return->sale->invoice_number ?? ('INV-' . str_pad($return->sale_id, 4, '0', STR_PAD_LEFT)) }}
                            </a>
                        </dd>

                        <dt class="col-6 text-muted small">Return Date</dt>
                        <dd class="col-6 small">{{ $return->created_at->format('d M Y, h:i A') }}</dd>

                        <dt class="col-6 text-muted small">Processed By</dt>
                        <dd class="col-6 small">{{ $return->user->name ?? '—' }}</dd>

                        <dt class="col-6 text-muted small">Reason</dt>
                        <dd class="col-6">
                            <span class="badge
                                @if($return->reason === 'damaged') bg-danger
                                @elseif($return->reason === 'customer_request') bg-info text-dark
                                @elseif($return->reason === 'wrong_item') bg-warning text-dark
                                @else bg-secondary
                                @endif">
                                {{ $return->reason_label }}
                            </span>
                        </dd>

                        <dt class="col-6 text-muted small">Refund Method</dt>
                        <dd class="col-6 small">{{ $return->refund_method_label }}</dd>

                        @if($return->notes)
                            <dt class="col-6 text-muted small">Notes</dt>
                            <dd class="col-6 small">{{ $return->notes }}</dd>
                        @endif

                        <dt class="col-6 text-muted small">Total Refunded</dt>
                        <dd class="col-6 fw-bold text-danger fs-5">Rs. {{ number_format($return->total_refunded, 2) }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Original Sale Totals --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-receipt me-2 text-gold"></i>Original Sale</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6 text-muted small">Sale Date</dt>
                        <dd class="col-6 small">{{ $return->sale->created_at->format('d M Y') }}</dd>
                        <dt class="col-6 text-muted small">Subtotal</dt>
                        <dd class="col-6 small">Rs. {{ number_format($return->sale->subtotal, 2) }}</dd>
                        @if($return->sale->discount_amount > 0)
                        <dt class="col-6 text-muted small">Discount</dt>
                        <dd class="col-6 small text-success">- Rs. {{ number_format($return->sale->discount_amount, 2) }}</dd>
                        @endif
                        <dt class="col-6 text-muted small">Sale Total</dt>
                        <dd class="col-6 fw-semibold">Rs. {{ number_format($return->sale->total, 2) }}</dd>
                        <dt class="col-6 text-muted small">Total Refunded</dt>
                        <dd class="col-6 text-danger">- Rs. {{ number_format($return->total_refunded, 2) }}</dd>
                        <dt class="col-6 text-muted small fw-bold">Net Revenue</dt>
                        <dd class="col-6 fw-bold">Rs. {{ number_format($return->sale->net_total, 2) }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Returned Items --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-boxes me-2 text-gold"></i>Returned Items</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-center">Condition</th>
                                <th class="text-center">Restocked</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($return->items as $item)
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            @if($item->product?->image_url)
                                                <img src="{{ $item->product->image_url }}" class="rounded" style="width:36px;height:36px;object-fit:cover;" alt="">
                                            @else
                                                <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                                    <i class="fas fa-gem text-muted small"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="fw-semibold small">{{ $item->product?->name ?? 'Unknown' }}</div>
                                                @if($item->variant)
                                                    <div class="text-muted" style="font-size:0.72rem;">
                                                        {{ collect([$item->variant->color, $item->variant->size])->filter()->implode(' / ') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $item->qty }}</td>
                                    <td class="text-end small">Rs. {{ number_format($item->price, 2) }}</td>
                                    <td class="text-end fw-semibold">Rs. {{ number_format($item->subtotal, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge
                                            @if($item->condition === 'good') bg-success
                                            @elseif($item->condition === 'damaged') bg-danger
                                            @else bg-secondary
                                            @endif">
                                            {{ $item->condition_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($item->restock)
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Yes</span>
                                        @else
                                            <span class="badge bg-secondary"><i class="fas fa-times me-1"></i>No</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $item->condition_notes ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end fw-bold ps-3">Total Refunded:</td>
                                <td class="text-end fw-bold text-danger">Rs. {{ number_format($return->total_refunded, 2) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
