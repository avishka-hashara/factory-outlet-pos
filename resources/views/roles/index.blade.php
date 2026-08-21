{{-- ============================================
     Roles & Permissions Page - Admin Only
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Roles & Permissions')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4><i class="fas fa-user-shield me-2 text-gold"></i> Roles & Permissions</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('staff.index') }}">Staff</a></li>
                <li class="breadcrumb-item active">Roles & Permissions</li>
            </ol>
        </nav>
    </div>
    <div>
        <button class="btn btn-gold btn-sm" onclick="openAddRole()">
            <i class="fas fa-plus me-1"></i> Add Role
        </button>
    </div>
</div>

{{-- Flash --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Permission Legend --}}
<div class="pos-card card mb-4">
    <div class="card-body py-3">
        <h6 class="fw-bold mb-2"><i class="fas fa-info-circle me-1 text-gold"></i> Available Permissions</h6>
        <div class="d-flex flex-wrap gap-2">
            @foreach($permissions as $key => $label)
                <span class="badge bg-light text-dark border"><i class="fas fa-key me-1 text-gold"></i>{{ $label }}</span>
            @endforeach
        </div>
        <p class="text-muted small mt-2 mb-0"><i class="fas fa-shield-alt me-1"></i> <strong>Admin</strong> users always have full access to everything and bypass all permission checks.</p>
    </div>
</div>

{{-- Roles Cards --}}
<div class="row g-4" id="roles-container">
    @forelse($roles as $role)
    <div class="col-md-6 col-lg-4" id="role-card-{{ $role->id }}">
        <div class="card h-100 shadow-sm border-0" style="border-radius:14px;">
            <div class="card-header d-flex align-items-center justify-content-between py-3"
                 style="background:linear-gradient(135deg,var(--gold) 0%,var(--gold-dark) 100%);border-radius:14px 14px 0 0;">
                <div>
                    <h6 class="mb-0 text-white fw-bold">
                        <i class="fas fa-user-tag me-2"></i>{{ $role->name }}
                        @if($role->is_system)
                            <span class="badge bg-white text-warning ms-1" style="font-size:0.6rem;">SYSTEM</span>
                        @endif
                    </h6>
                    <small class="text-white opacity-75">{{ $role->description ?: 'No description' }}</small>
                </div>
                <span class="badge bg-white text-dark">{{ $role->users_count }} staff</span>
            </div>
            <div class="card-body">
                <h6 class="fw-semibold small text-muted text-uppercase mb-3">Allowed Pages</h6>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @forelse($permissions as $key => $label)
                        @if(in_array($key, $role->permissions ?? []))
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                <i class="fas fa-check me-1"></i>{{ $label }}
                            </span>
                        @else
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                <i class="fas fa-times me-1"></i>{{ $label }}
                            </span>
                        @endif
                    @empty
                    @endforelse
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 d-flex gap-2 pb-3 px-3">
                <button class="btn btn-sm btn-outline-primary flex-fill"
                        data-role-id="{{ $role->id }}"
                        onclick="openEditRole(this)">
                    <i class="fas fa-edit me-1"></i> Edit
                </button>
                @if(!$role->is_system)
                <button class="btn btn-sm btn-outline-danger flex-fill"
                        data-role-id="{{ $role->id }}"
                        data-role-name="{{ $role->name }}"
                        onclick="deleteRole(this)">
                    <i class="fas fa-trash me-1"></i> Delete
                </button>
                @else
                <button class="btn btn-sm btn-outline-secondary flex-fill" disabled title="System roles cannot be deleted">
                    <i class="fas fa-lock me-1"></i> Protected
                </button>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="text-center text-muted py-5">
            <i class="fas fa-user-shield fa-3x mb-3 d-block opacity-25"></i>
            No roles defined yet. Click <strong>Add Role</strong> to create one.
        </div>
    </div>
    @endforelse
</div>

{{-- ===== ADD Role Modal ===== --}}
<div class="modal fade" id="addRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2 text-gold"></i> Add New Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                    <input type="text" id="addRoleName" class="form-control" placeholder="e.g. Cashier, Floor Manager…" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <input type="text" id="addRoleDesc" class="form-control" placeholder="Brief description of what this role can do">
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Page Permissions</label>
                    <div class="border rounded p-3 bg-light">
                        <div class="row g-2">
                            @foreach($permissions as $key => $label)
                            <div class="col-md-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input add-perm-check" type="checkbox"
                                           value="{{ $key }}" id="add_perm_{{ $key }}">
                                    <label class="form-check-label small" for="add_perm_{{ $key }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="border-top mt-2 pt-2 d-flex gap-3">
                            <button type="button" class="btn btn-xs btn-link p-0 text-success text-decoration-none small" onclick="checkAll('add')">
                                <i class="fas fa-check-double me-1"></i>Select All
                            </button>
                            <button type="button" class="btn btn-xs btn-link p-0 text-danger text-decoration-none small" onclick="uncheckAll('add')">
                                <i class="fas fa-times me-1"></i>Clear All
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-gold" onclick="saveNewRole()">
                    <i class="fas fa-save me-1"></i> Save Role
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ===== EDIT Role Modal ===== --}}
<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2 text-primary"></i> Edit Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editRoleId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                    <input type="text" id="editRoleName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <input type="text" id="editRoleDesc" class="form-control">
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Page Permissions</label>
                    <div class="border rounded p-3 bg-light">
                        <div class="row g-2">
                            @foreach($permissions as $key => $label)
                            <div class="col-md-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input edit-perm-check" type="checkbox"
                                           value="{{ $key }}" id="edit_perm_{{ $key }}">
                                    <label class="form-check-label small" for="edit_perm_{{ $key }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="border-top mt-2 pt-2 d-flex gap-3">
                            <button type="button" class="btn btn-xs btn-link p-0 text-success text-decoration-none small" onclick="checkAll('edit')">
                                <i class="fas fa-check-double me-1"></i>Select All
                            </button>
                            <button type="button" class="btn btn-xs btn-link p-0 text-danger text-decoration-none small" onclick="uncheckAll('edit')">
                                <i class="fas fa-times me-1"></i>Clear All
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveEditRole()">
                    <i class="fas fa-save me-1"></i> Update Role
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function openAddRole() {
    document.getElementById('addRoleName').value = '';
    document.getElementById('addRoleDesc').value = '';
    uncheckAll('add');
    new bootstrap.Modal(document.getElementById('addRoleModal')).show();
}

async function openEditRole(btn) {
    const id = btn.dataset.roleId;
    try {
        const resp = await fetch(`/roles/${id}`, {
            headers: { 'Accept': 'application/json' },
        });
        const data = await resp.json();
        if (!data.success || !data.role) {
            alert(data.message || 'Failed to load role details.');
            return;
        }

        const role = data.role;
        const permissions = Array.isArray(role.permissions) ? role.permissions : [];

        document.getElementById('editRoleId').value   = role.id;
        document.getElementById('editRoleName').value = role.name || '';
        document.getElementById('editRoleDesc').value = role.description || '';
        document.querySelectorAll('.edit-perm-check').forEach(cb => {
            cb.checked = permissions.includes(cb.value);
        });

        new bootstrap.Modal(document.getElementById('editRoleModal')).show();
    } catch (error) {
        console.error(error);
        alert('Failed to load role details.');
    }
}

function checkAll(prefix) {
    document.querySelectorAll('.' + prefix + '-perm-check').forEach(cb => cb.checked = true);
}
function uncheckAll(prefix) {
    document.querySelectorAll('.' + prefix + '-perm-check').forEach(cb => cb.checked = false);
}

async function saveNewRole() {
    const name = document.getElementById('addRoleName').value.trim();
    if (!name) { alert('Role name is required.'); return; }

    const permissions = [...document.querySelectorAll('.add-perm-check:checked')].map(cb => cb.value);

    const resp = await fetch('/roles', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({
            name,
            description: document.getElementById('addRoleDesc').value.trim(),
            permissions,
        }),
    });
    const data = await resp.json();
    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('addRoleModal')).hide();
        location.reload();
    } else {
        alert(data.message || Object.values(data.errors ?? {}).flat().join('\n'));
    }
}

async function saveEditRole() {
    const id   = document.getElementById('editRoleId').value;
    const name = document.getElementById('editRoleName').value.trim();
    if (!name) { alert('Role name is required.'); return; }

    const permissions = [...document.querySelectorAll('.edit-perm-check:checked')].map(cb => cb.value);

    const resp = await fetch(`/roles/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({
            name,
            description: document.getElementById('editRoleDesc').value.trim(),
            permissions,
        }),
    });
    const data = await resp.json();
    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('editRoleModal')).hide();
        location.reload();
    } else {
        alert(data.message || Object.values(data.errors ?? {}).flat().join('\n'));
    }
}

async function deleteRole(btn) {
    const id = btn.dataset.roleId;
    const name = btn.dataset.roleName;
    if (!confirm(`Delete role "${name}"? This cannot be undone.`)) return;
    const resp = await fetch(`/roles/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    const data = await resp.json();
    if (data.success) {
        document.getElementById('role-card-' + id)?.remove();
    } else {
        alert(data.message);
    }
}
</script>
@endpush
