{{-- ============================================
     Customer Management Page - Jewellery POS System
     Add/Edit/Delete Customers
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Customers')

@section('content')
<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h4><i class="fas fa-users me-2 text-gold"></i> Customers</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Customers</li>
            </ol>
        </nav>
    </div>
    <div>
        <button class="btn btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
            <i class="fas fa-user-plus me-1"></i> Add Customer
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card gold p-3">
            <div class="d-flex align-items-center">
                <div class="stat-icon gold-bg me-3"><i class="fas fa-users"></i></div>
                <div>
                    <div class="stat-label">Total Customers</div>
                    <div class="stat-value" style="font-size:1.3rem;">{{ number_format($totalCustomers) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card blue p-3">
            <div class="d-flex align-items-center">
                <div class="stat-icon blue-bg me-3"><i class="fas fa-user-plus"></i></div>
                <div>
                    <div class="stat-label">New This Month</div>
                    <div class="stat-value" style="font-size:1.3rem;">{{ number_format($newThisMonth) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card green p-3">
            <div class="d-flex align-items-center">
                <div class="stat-icon green-bg me-3"><i class="fas fa-chart-line"></i></div>
                <div>
                    <div class="stat-label">Total Revenue (Linked)</div>
                    <div class="stat-value" style="font-size:1.3rem;">Rs. {{ number_format($totalRevenue, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="pos-card card mb-4">
    <div class="card-body py-3 px-4">
        <form method="GET" action="{{ route('customers.index') }}" id="customerFilterForm" class="d-flex align-items-end gap-3 flex-wrap">
            <div class="d-flex align-items-center me-2" style="font-size:0.75rem; font-weight:700; color:#6b7280; letter-spacing:0.5px;">
                <i class="fas fa-filter me-2"></i> FILTERS
            </div>
            
            <div class="flex-grow-1" style="min-width: 200px; max-width: 300px;">
                <label class="form-label text-muted fw-bold mb-1" style="font-size:0.65rem; letter-spacing:0.5px;">SEARCH</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Name, phone, email..." value="{{ request('search') }}">
                </div>
            </div>

            <div style="min-width: 150px;">
                <label class="form-label text-muted fw-bold mb-1" style="font-size:0.65rem; letter-spacing:0.5px;">TYPE</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="retail" {{ request('type') == 'retail' ? 'selected' : '' }}>Retail</option>
                    <option value="wholesale" {{ request('type') == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                </select>
            </div>

            <div style="min-width: 150px;">
                <label class="form-label text-muted fw-bold mb-1" style="font-size:0.65rem; letter-spacing:0.5px;">LOYALTY TIER</label>
                <select name="tier" class="form-select form-select-sm">
                    <option value="">All Tiers</option>
                    <option value="gold" {{ request('tier') == 'gold' ? 'selected' : '' }}>Gold</option>
                    <option value="silver" {{ request('tier') == 'silver' ? 'selected' : '' }}>Silver</option>
                    <option value="bronze" {{ request('tier') == 'bronze' ? 'selected' : '' }}>Bronze</option>
                </select>
            </div>

            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('customers.index') }}" class="btn btn-sm btn-light border" style="font-weight:600; color:#4b5563; background:#f3f4f6;">Reset</a>
                <button type="submit" class="btn btn-sm" style="background:var(--gold); color:white; font-weight:600;">Apply</button>
            </div>
        </form>
    </div>
</div>


<!-- Customer Table -->
<div class="pos-card card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table pos-table mb-0">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Added On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $index => $c)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-2"
                                     style="width:36px;height:36px;background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:white;font-weight:700;font-size:0.8rem;">
                                    {{ strtoupper(substr($c->name ?? '', 0, 1)) ?: '—' }}
                                </div>
                                <strong>{{ $c->name ?? '' }}</strong>
                            </div>
                        </td>
                        <td>{{ $c->phone ?? 'N/A' }}</td>
                        <td>{{ $c->email ?? 'N/A' }}</td>
                        <td>{{ $c->address ?? 'N/A' }}</td>
                        <td>{{ $c->created_at->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('customers.show', $c->id) }}" class="btn btn-sm btn-outline-info" title="View History">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-primary" title="Edit" 
                                onclick="editCustomer({{ $c->id }}, '{{ addslashes($c->name ?? '') }}', '{{ addslashes($c->phone ?? '') }}', '{{ addslashes($c->email ?? '') }}', '{{ addslashes($c->address ?? '') }}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('customers.destroy', $c->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No customers found. Click "Add Customer" to create one.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i> Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('customers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Customer Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter full name (optional)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" placeholder="Enter phone number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Enter address"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold"><i class="fas fa-save me-1"></i> Save Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i> Edit Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCustomerForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Customer Name</label>
                        <input type="text" id="editName" name="name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="tel" id="editPhone" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" id="editEmail" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea id="editAddress" name="address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold"><i class="fas fa-save me-1"></i> Update Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editCustomer(id, name, phone, email, address) {
    const form = document.getElementById('editCustomerForm');
    form.action = `/customers/${id}`;
    
    document.getElementById('editName').value = name;
    document.getElementById('editPhone').value = phone || '';
    document.getElementById('editEmail').value = email || '';
    document.getElementById('editAddress').value = address || '';
    
    new bootstrap.Modal(document.getElementById('editCustomerModal')).show();
}
</script>
@endpush
