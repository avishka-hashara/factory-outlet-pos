{{-- ============================================
     Customer Details & Sales History Page
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Customer Details - ' . $customer->name)

@section('content')
<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h4><i class="fas fa-user-circle me-2 text-gold"></i> {{ $customer->name }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                <li class="breadcrumb-item active">{{ $customer->name }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Customers
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Customer Info Card -->
    <div class="col-md-4">
        <div class="pos-card card h-100">
            <div class="card-header bg-white border-bottom pt-3 pb-2">
                <h6 class="mb-0 fw-bold"><i class="fas fa-address-card me-2 text-muted"></i>Profile Information</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                         style="width:60px;height:60px;background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:white;font-weight:700;font-size:1.5rem;">
                        {{ strtoupper(substr($customer->name ?? '', 0, 1)) ?: '—' }}
                    </div>
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $customer->name ?: $customer->phone }}</h5>
                        <div class="text-muted small">Customer since {{ $customer->created_at->format('M Y') }}</div>
                    </div>
                </div>

                <ul class="list-group list-group-flush text-sm">
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center border-0 py-2">
                        <span class="text-muted"><i class="fas fa-phone-alt me-2 w-15px"></i> Phone</span>
                        <span class="fw-medium">{{ $customer->phone ?? 'N/A' }}</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center border-0 py-2">
                        <span class="text-muted"><i class="fas fa-envelope me-2 w-15px"></i> Email</span>
                        <span class="fw-medium">{{ $customer->email ?? 'N/A' }}</span>
                    </li>
                    <li class="list-group-item px-0 border-0 py-2">
                        <div class="text-muted mb-1"><i class="fas fa-map-marker-alt me-2 w-15px"></i> Address</div>
                        <div class="fw-medium ms-4">{{ $customer->address ?? 'N/A' }}</div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Lifetime Stats Card -->
    <div class="col-md-8">
        <div class="row g-3 h-100">
            <div class="col-sm-6">
                <div class="stat-card gold p-4 h-100 d-flex flex-column justify-content-center">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon gold-bg me-3" style="width:60px;height:60px;font-size:1.5rem;"><i class="fas fa-wallet"></i></div>
                        <div>
                            <div class="stat-label text-uppercase opacity-75" style="letter-spacing:1px;">Total Spent</div>
                            <div class="stat-value" style="font-size:1.8rem;">Rs. {{ number_format($totalSpent, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="stat-card blue p-4 h-100 d-flex flex-column justify-content-center">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon blue-bg me-3" style="width:60px;height:60px;font-size:1.5rem;"><i class="fas fa-shopping-bag"></i></div>
                        <div>
                            <div class="stat-label text-uppercase opacity-75" style="letter-spacing:1px;">Total Purchases</div>
                            <div class="stat-value" style="font-size:1.8rem;">{{ $totalSales }} Orders</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sales History Table -->
<div class="pos-card card">
    <div class="card-header bg-white border-bottom pt-3 pb-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2 text-muted"></i>Purchase History</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table pos-table mb-0">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date & Time</th>
                        <th>Items</th>
                        <th>Payment Method</th>
                        <th>Cashier</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customer->sales->sortByDesc('created_at') as $sale)
                    <tr>
                        <td class="fw-bold text-gold">{{ $sale->invoice_number }}</td>
                        <td>{{ $sale->created_at->format('d M Y, h:i A') }}</td>
                        <td>{{ $sale->items->sum('qty') }} items</td>
                        <td>
                            @if($sale->payment_method === 'cash')
                                <span class="badge bg-success"><i class="fas fa-money-bill-wave me-1"></i> Cash</span>
                            @elseif($sale->payment_method === 'card')
                                <span class="badge bg-primary"><i class="fas fa-credit-card me-1"></i> Card</span>
                            @elseif($sale->payment_method === 'bank')
                                <span class="badge bg-info"><i class="fas fa-university me-1"></i> Bank</span>
                            @else
                                <span class="badge bg-secondary text-uppercase">{{ $sale->payment_method }}</span>
                            @endif
                        </td>
                        <td>{{ $sale->user->name ?? 'Unknown' }}</td>
                        <td class="text-end fw-bold">Rs. {{ number_format($sale->total, 2) }}</td>
                        <td class="text-center">
                            <a href="{{ route('invoice.show', $sale->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Invoice">
                                <i class="fas fa-file-invoice"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-shopping-cart fa-3x mb-3 opacity-25"></i>
                            <p class="mb-0">This customer hasn't made any purchases yet.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
