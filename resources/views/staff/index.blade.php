{{-- ============================================
     Staff Management Page - Jewellery POS System
     Add/Edit/Delete staff with role badges
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Staff Management')

@section('content')
<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h4><i class="fas fa-users me-2 text-gold"></i> Staff Management</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Staff</li>
            </ol>
        </nav>
    </div>
    <div>
        <button class="btn btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#addStaffModal">
            <i class="fas fa-user-plus me-1"></i> Add Staff
        </button>
    </div>
</div>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card gold p-3">
            <div class="d-flex align-items-center">
                <div class="stat-icon gold-bg me-3"><i class="fas fa-users"></i></div>
                <div>
                    <div class="stat-label">Total Staff</div>
                    <div class="stat-value" style="font-size:1.3rem;">{{ count($staff) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card green p-3">
            <div class="d-flex align-items-center">
                <div class="stat-icon green-bg me-3"><i class="fas fa-user-check"></i></div>
                <div>
                    <div class="stat-label">Active</div>
                    <div class="stat-value" style="font-size:1.3rem;">{{ $staff->where('status', 'Active')->count() }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card red p-3">
            <div class="d-flex align-items-center">
                <div class="stat-icon red-bg me-3"><i class="fas fa-user-times"></i></div>
                <div>
                    <div class="stat-label">Inactive</div>
                    <div class="stat-value" style="font-size:1.3rem;">{{ $staff->where('status', 'Inactive')->count() }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card blue p-3">
            <div class="d-flex align-items-center">
                <div class="stat-icon blue-bg me-3"><i class="fas fa-user-shield"></i></div>
                <div>
                    <div class="stat-label">Admins</div>
                    <div class="stat-value" style="font-size:1.3rem;">{{ $staff->where('role', 'Admin')->count() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Staff Table -->
<div class="pos-card card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table pos-table mb-0">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Sales Today</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $index => $s)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-2"
                                     style="width:36px;height:36px;background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:white;font-weight:700;font-size:0.8rem;">
                                    {{ strtoupper(substr($s->name, 0, 1)) }}
                                </div>
                                <strong>{{ $s->name }}</strong>
                            </div>
                        </td>
                        <td>{{ $s->email }}</td>
                        <td>{{ $s->phone ?? 'N/A' }}</td>
                        <td>
                            @if($s->role === 'Admin')
                                <span class="badge badge-gold"><i class="fas fa-shield-alt me-1"></i> Admin</span>
                            @else
                                <span class="badge bg-info"><i class="fas fa-user me-1"></i> Staff</span>
                                @if($s->permissionRole)
                                    <br><small class="text-muted fst-italic">{{ $s->permissionRole->name }}</small>
                                @else
                                    <br><small class="text-muted fst-italic">No role set</small>
                                @endif
                            @endif
                        </td>
                        <td><span class="fw-semibold">{{ $s->sales_today ?? 0 }}</span></td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" {{ $s->status === 'Active' ? 'checked' : '' }}
                                       style="cursor:pointer;" onchange="toggleStaffStatus({{ $s->id }}, this)">
                                <label class="form-check-label small {{ $s->status === 'Active' ? 'text-success' : 'text-danger' }}">
                                    {{ $s->status }}
                                </label>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="editStaff({{ $s->id }})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteStaff({{ $s->id }})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No staff members found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i> Add New Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStaffForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" id="addStaffName" class="form-control" placeholder="Enter full name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" id="addStaffEmail" class="form-control" placeholder="Enter email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="tel" id="addStaffPhone" class="form-control" placeholder="+94 77 XXX XXXX" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" id="addStaffPassword" class="form-control" placeholder="Set password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirm Password</label>
                        <input type="password" id="addStaffPasswordConfirmation" class="form-control" placeholder="Re-enter password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role</label>
                        <select id="addStaffRole" class="form-select" required onchange="togglePermRoleDiv('add')">
                            <option value="Staff">Staff</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3" id="addPermRoleDiv">
                        <label class="form-label fw-semibold">Permission Role <span class="text-muted small">(optional, Staff only)</span></label>
                        <select id="addStaffRoleId" class="form-select">
                            <option value="">— No specific role (Dashboard &amp; POS only) —</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="addStaffStatus" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-gold" onclick="saveNewStaff()"><i class="fas fa-save me-1"></i> Save Staff</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i> Edit Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editStaffForm">
                    <input type="hidden" id="editStaffId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" id="editStaffName" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" id="editStaffEmail" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="tel" id="editStaffPhone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role</label>
                        <select id="editStaffRole" class="form-select" onchange="togglePermRoleDiv('edit')">
                            <option value="Staff">Staff</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3" id="editPermRoleDiv">
                        <label class="form-label fw-semibold">Permission Role <span class="text-muted small">(optional, Staff only)</span></label>
                        <select id="editStaffRoleId" class="form-select">
                            <option value="">— No specific role (Dashboard &amp; POS only) —</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-gold" onclick="saveEditStaff()"><i class="fas fa-save me-1"></i> Update</button>
            </div>
        </div>
    </div>
</div>
@endsection

<script>
function togglePermRoleDiv(prefix) {
    const role = document.getElementById(prefix + 'StaffRole').value;
    const div  = document.getElementById(prefix + 'PermRoleDiv');
    if (div) div.style.display = (role === 'Staff') ? '' : 'none';
}

function saveNewStaff() {
    const form = document.getElementById('addStaffForm');
    
    // Validate form
    if (!form.checkValidity()) {
        alert('Please fill in all required fields');
        return;
    }

    const data = {
        name: document.getElementById('addStaffName').value,
        email: document.getElementById('addStaffEmail').value,
        phone: document.getElementById('addStaffPhone').value,
        password: document.getElementById('addStaffPassword').value,
        password_confirmation: document.getElementById('addStaffPasswordConfirmation').value,
        role: document.getElementById('addStaffRole').value,
        role_id: document.getElementById('addStaffRole').value === 'Staff'
                    ? (document.getElementById('addStaffRoleId').value || null)
                    : null,
        status: document.getElementById('addStaffStatus').checked ? 'Active' : 'Inactive',
    };

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch('/staff', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            document.getElementById('addStaffForm').reset();
            const modal = bootstrap.Modal.getInstance(document.getElementById('addStaffModal'));
            modal.hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the staff member');
    });
}

function editStaff(staffId) {
    // Fetch staff details and populate the modal
    fetch(`/staff/${staffId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const staff = data.data;
                document.getElementById('editStaffId').value = staff.id;
                document.getElementById('editStaffName').value = staff.name;
                document.getElementById('editStaffEmail').value = staff.email;
                document.getElementById('editStaffPhone').value = staff.phone || '';
                document.getElementById('editStaffRole').value = staff.role;
                // Handle permission role
                document.getElementById('editStaffRoleId').value = staff.role_id || '';
                togglePermRoleDiv('edit');
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('editStaffModal'));
                modal.show();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading staff details');
        });
}

function saveEditStaff() {
    const staffId = document.getElementById('editStaffId').value;
    const data = {
        name: document.getElementById('editStaffName').value,
        email: document.getElementById('editStaffEmail').value,
        phone: document.getElementById('editStaffPhone').value,
        role: document.getElementById('editStaffRole').value,
        role_id: document.getElementById('editStaffRole').value === 'Staff'
                    ? (document.getElementById('editStaffRoleId').value || null)
                    : null,
    };

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch(`/staff/${staffId}`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': token,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the staff member');
    });
}

function deleteStaff(staffId) {
    if (confirm('Are you sure you want to delete this staff member?')) {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch(`/staff/${staffId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the staff member');
        });
    }
}

function toggleStaffStatus(staffId, checkbox) {
    const newStatus = checkbox.checked ? 'Active' : 'Inactive';
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch(`/staff/${staffId}/status`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': token,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Error: ' + data.message);
            checkbox.checked = !checkbox.checked; // Revert the checkbox
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the status');
        checkbox.checked = !checkbox.checked; // Revert the checkbox
    });
}
</script>
