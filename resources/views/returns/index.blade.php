@extends('layouts.pos')
@section('title', 'Returns & Refunds')

@section('content')
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h4><i class="fas fa-undo-alt me-2 text-gold"></i> Returns & Refunds</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Returns</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats Row --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                        <i class="fas fa-undo-alt text-danger fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Returns</div>
                        <div class="fw-bold fs-5">{{ $totalCount }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold">To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small fw-semibold">Reason</label>
                    <select name="reason" class="form-select form-select-sm">
                        <option value="">All Reasons</option>
                        @foreach(\App\Models\ProductReturn::reasonLabels() as $val => $label)
                            <option value="{{ $val }}" {{ request('reason') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small fw-semibold">Refund Method</label>
                    <select name="refund_method" class="form-select form-select-sm">
                        <option value="">All Methods</option>
                        <option value="cash" {{ request('refund_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="card" {{ request('refund_method') === 'card' ? 'selected' : '' }}>Card</option>
                        <option value="store_credit" {{ request('refund_method') === 'store_credit' ? 'selected' : '' }}>Store Credit</option>
                        <option value="bank" {{ request('refund_method') === 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                    </select>
                </div>
                <div class="col-sm-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-gold btn-sm flex-fill"><i class="fas fa-filter me-1"></i>Filter</button>
                    <a href="{{ route('returns.index') }}" class="btn btn-outline-secondary btn-sm flex-fill">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Returns Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Sale Ref</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Reason</th>
                            <th>Refund Method</th>
                            <th>Total Refunded</th>
                            <th>Processed By</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $return)
                            <tr>
                                <td class="ps-3 text-muted small">{{ $return->id }}</td>
                                <td>
                                    <a href="{{ route('sales.show', $return->sale_id) }}" class="fw-semibold text-decoration-none">
                                        {{ $return->sale->invoice_number ?? ('INV-' . str_pad($return->sale_id, 4, '0', STR_PAD_LEFT)) }}
                                    </a>
                                </td>
                                <td class="small">{{ $return->created_at->format('d M Y, h:i A') }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $return->items->count() }} item(s)</span>
                                </td>
                                <td>
                                    <span class="badge
                                        @if($return->reason === 'damaged') bg-danger
                                        @elseif($return->reason === 'customer_request') bg-info text-dark
                                        @elseif($return->reason === 'wrong_item') bg-warning text-dark
                                        @else bg-secondary
                                        @endif">
                                        {{ $return->reason_label }}
                                    </span>
                                </td>
                                <td class="small">{{ $return->refund_method_label }}</td>
                                <td class="fw-bold text-danger">Rs. {{ number_format($return->total_refunded, 2) }}</td>
                                <td class="small">{{ $return->user->name ?? '—' }}</td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('returns.show', $return) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-undo-alt fa-2x mb-2 d-block opacity-25"></i>
                                    No returns found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($returns->hasPages())
            <div class="card-footer bg-white">{{ $returns->links() }}</div>
        @endif
    </div>
@endsection
