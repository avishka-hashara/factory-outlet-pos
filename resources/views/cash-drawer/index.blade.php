{{-- ============================================
     Cash Drawer Management
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Cash Drawer')

@section('content')

{{-- ===== Page Header ===== --}}
<div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1">
            <i class="fas fa-cash-register me-2 text-gold"></i> Cash Drawer
            @if($session)
                <span class="badge ms-2 fw-semibold" style="background:#10b981;font-size:0.65rem;letter-spacing:1px;padding:0.25rem 0.6rem;border-radius:20px;">OPEN</span>
            @else
                <span class="badge bg-secondary ms-2 fw-semibold" style="font-size:0.65rem;letter-spacing:1px;padding:0.25rem 0.6rem;border-radius:20px;">CLOSED</span>
            @endif
        </h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Cash Drawer</li>
            </ol>
        </nav>
    </div>
    @if(!$session)
    <button class="btn btn-gold btn-sm" onclick="openDrawerModal()">
        <i class="fas fa-lock-open me-1"></i> Open Drawer
    </button>
    @else
    <div class="d-flex gap-2">
        <button class="btn btn-sm" style="background:#10b981;color:#fff;border:none;" onclick="showCashInModal()">
            <i class="fas fa-plus me-1"></i> Cash In
        </button>
        <button class="btn btn-sm btn-danger" onclick="showCashOutModal()">
            <i class="fas fa-minus me-1"></i> Cash Out
        </button>
        <button class="btn btn-sm btn-outline-dark" onclick="showCloseModal()">
            <i class="fas fa-lock me-1"></i> Close Drawer
        </button>
    </div>
    @endif
</div>

@if($session)
{{-- ======================== OPEN SESSION ======================== --}}

{{-- Session Info Bar --}}
<div class="pos-card card mb-4 border-0" style="border-left:4px solid #10b981 !important;">
    <div class="card-body py-3">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                     style="width:42px;height:42px;background:rgba(16,185,129,0.12);">
                    <i class="fas fa-clock" style="color:#10b981;"></i>
                </div>
            </div>
            <div class="col">
                <div class="fw-bold" style="color:#10b981;">Session Open</div>
                <div class="small text-muted">
                    Opened by <strong>{{ $session->openedBy->name ?? 'Unknown' }}</strong>
                    &bull; {{ $session->opened_at->format('d M Y, h:i A') }}
                    &bull; <span id="session-duration">{{ $session->duration }}</span>
                </div>
                @if($session->notes)
                    <div class="small text-muted fst-italic">Note: {{ $session->notes }}</div>
                @endif
            </div>
            <div class="col-auto">
                <span class="small text-muted">Session #{{ $session->id }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Stats Cards --}}
<div class="row g-3 mb-4" id="stats-row">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-drawer-card h-100 border-0 shadow-sm text-center p-3">
            <div class="stat-drawer-icon" style="background:#e8f4fd;color:#0d6efd;"><i class="fas fa-coins"></i></div>
            <div class="stat-drawer-label mt-2">Opening Balance</div>
            <div class="stat-drawer-value" id="stat-opening">Rs. {{ number_format($session->opening_balance, 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-drawer-card h-100 border-0 shadow-sm text-center p-3">
            <div class="stat-drawer-icon" style="background:#dcfce7;color:#10b981;"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-drawer-label mt-2">Cash Sales</div>
            <div class="stat-drawer-value text-success" id="stat-sales">Rs. {{ number_format($session->cash_sales, 2) }}</div>
            <div class="stat-drawer-sub" id="stat-sales-count">{{ $session->cash_sales_count }} sales</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-drawer-card h-100 border-0 shadow-sm text-center p-3">
            <div class="stat-drawer-icon" style="background:#fee2e2;color:#ef4444;"><i class="fas fa-undo-alt"></i></div>
            <div class="stat-drawer-label mt-2">Cash Refunds</div>
            <div class="stat-drawer-value text-danger" id="stat-refunds">Rs. {{ number_format($session->cash_refunds, 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-drawer-card h-100 border-0 shadow-sm text-center p-3">
            <div class="stat-drawer-icon" style="background:#dcfce7;color:#10b981;"><i class="fas fa-arrow-down"></i></div>
            <div class="stat-drawer-label mt-2">Cash In</div>
            <div class="stat-drawer-value text-success" id="stat-cash-in">Rs. {{ number_format($session->total_cash_in, 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-drawer-card h-100 border-0 shadow-sm text-center p-3">
            <div class="stat-drawer-icon" style="background:#fee2e2;color:#ef4444;"><i class="fas fa-arrow-up"></i></div>
            <div class="stat-drawer-label mt-2">Cash Out</div>
            <div class="stat-drawer-value text-danger" id="stat-cash-out">Rs. {{ number_format($session->total_cash_out, 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-drawer-card h-100 border-0 shadow-sm text-center p-3"
             style="background:linear-gradient(135deg,var(--gold) 0%,var(--gold-dark) 100%);">
            <div class="stat-drawer-icon" style="background:rgba(255,255,255,0.25);color:#fff;"><i class="fas fa-wallet"></i></div>
            <div class="stat-drawer-label mt-2 text-white opacity-90">Expected Cash</div>
            <div class="stat-drawer-value text-white fw-bold" id="stat-expected">Rs. {{ number_format($session->expected_balance, 2) }}</div>
        </div>
    </div>
</div>

{{-- Transaction Log --}}
<div class="pos-card card">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-list-alt me-2 text-gold"></i> Session Transactions</h6>
        <span class="badge bg-secondary" id="tx-count-badge">{{ $transactions->count() }} entries</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table pos-table mb-0" id="tx-table">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>By</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody id="tx-tbody">
                    @forelse($transactions as $i => $tx)
                    @php
                        $isPositive = in_array($tx['type'], ['cash_in', 'cash_sale'], true);
                        $badge = match ($tx['type']) {
                            'cash_in' => ['#dcfce7', '#10b981', 'fa-arrow-down', 'Cash In'],
                            'cash_out' => ['#fee2e2', '#ef4444', 'fa-arrow-up', 'Cash Out'],
                            'cash_sale' => ['#dcfce7', '#059669', 'fa-shopping-cart', 'Cash Sale'],
                            'cash_refund' => ['#fee2e2', '#ef4444', 'fa-undo-alt', 'Cash Refund'],
                            default => ['#e5e7eb', '#374151', 'fa-circle', ucfirst(str_replace('_', ' ', $tx['type']))],
                        };
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $transactions->count() - $i }}</td>
                        <td class="text-muted small">{{ $tx['created_at']->format('h:i A') }}</td>
                        <td>
                            <span class="badge fw-semibold" style="background:{{ $badge[0] }};color:{{ $badge[1] }};">
                                <i class="fas {{ $badge[2] }} me-1"></i> {{ $badge[3] }}
                            </span>
                        </td>
                        <td>{{ $tx->description ?? '—' }}</td>
                        <td class="small text-muted">{{ $tx->user->name ?? '—' }}</td>
                        <td class="text-end fw-semibold {{ $isPositive ? 'text-success' : 'text-danger' }}">
                            {{ $isPositive ? '+' : '-' }}Rs. {{ number_format($tx['amount'], 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr id="tx-empty-row">
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                            No transactions yet in this session.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@else
{{-- ======================== NO OPEN SESSION ======================== --}}
<div class="row g-4 mb-4">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;overflow:hidden;">
            <div class="card-header py-4 text-center"
                 style="background:linear-gradient(135deg,var(--gold) 0%,var(--gold-dark) 100%);">
                <i class="fas fa-lock-open fa-3x text-white mb-2 d-block"></i>
                <h5 class="text-white mb-0 fw-bold">Open Cash Drawer</h5>
                <small class="text-white opacity-75">Start a new shift / session</small>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Opening Balance (Rs.)</label>
                    <input type="number" id="quickOpenBalance" class="form-control form-control-lg"
                           placeholder="0.00" min="0" step="0.01">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Notes <span class="text-muted small">(optional)</span></label>
                    <input type="text" id="quickOpenNotes" class="form-control" placeholder="e.g. Morning shift">
                </div>
                <button class="btn btn-gold w-100 py-2 fw-bold" onclick="openDrawerQuick()">
                    <i class="fas fa-lock-open me-2"></i> Open Drawer Now
                </button>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2 text-gold"></i> Last Session Summary</h6>
            </div>
            @php $last = $history->first(); @endphp
            @if($last)
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="bg-light rounded p-3 text-center">
                            <div class="small text-muted mb-1">Date</div>
                            <div class="fw-bold">{{ $last->opened_at->format('d M Y') }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light rounded p-3 text-center">
                            <div class="small text-muted mb-1">Duration</div>
                            <div class="fw-bold">{{ $last->duration }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light rounded p-3 text-center">
                            <div class="small text-muted mb-1">Opening</div>
                            <div class="fw-bold">Rs. {{ number_format($last->opening_balance, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light rounded p-3 text-center">
                            <div class="small text-muted mb-1">Cash Sales</div>
                            <div class="fw-bold text-success">Rs. {{ number_format($last->cash_sales, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light rounded p-3 text-center">
                            <div class="small text-muted mb-1">Closing (Actual)</div>
                            <div class="fw-bold">Rs. {{ number_format($last->closing_balance, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        @php $diff = $last->difference; @endphp
                        <div class="rounded p-3 text-center {{ $diff == 0 ? 'bg-light' : ($diff > 0 ? '' : '') }}"
                             style="{{ $diff > 0 ? 'background:#dcfce7;' : ($diff < 0 ? 'background:#fee2e2;' : '') }}">
                            <div class="small text-muted mb-1">Difference</div>
                            <div class="fw-bold {{ $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted') }}">
                                {{ $diff >= 0 ? '+' : '' }}Rs. {{ number_format($diff, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
                @if($last->close_notes)
                <p class="small text-muted fst-italic mb-0"><i class="fas fa-comment me-1"></i>{{ $last->close_notes }}</p>
                @endif
            </div>
            @else
            <div class="card-body d-flex align-items-center justify-content-center text-muted" style="min-height:220px;">
                <div class="text-center">
                    <i class="fas fa-history fa-3x d-block mb-2 opacity-25"></i>
                    No previous sessions found.
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- ======================== HISTORY TABLE ======================== --}}
<div class="pos-card card mt-4">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2 text-gold"></i> Session History</h6>
        <span class="badge bg-secondary">{{ $history->total() }} sessions</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table pos-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Opened</th>
                        <th>Closed</th>
                        <th>Duration</th>
                        <th>By</th>
                        <th class="text-end">Opening</th>
                        <th class="text-end">Cash Sales</th>
                        <th class="text-end">Closing</th>
                        <th class="text-center">Difference</th>
                        <th class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $sess)
                    @php $d = $sess->difference; @endphp
                    <tr>
                        <td class="text-muted small">{{ $sess->id }}</td>
                        <td class="small">{{ $sess->opened_at->format('d M Y') }}<br><span class="text-muted">{{ $sess->opened_at->format('h:i A') }}</span></td>
                        <td class="small">{{ $sess->closed_at?->format('d M Y') ?? '—' }}<br><span class="text-muted">{{ $sess->closed_at?->format('h:i A') ?? '' }}</span></td>
                        <td class="small text-muted">{{ $sess->duration }}</td>
                        <td class="small">{{ $sess->openedBy->name ?? '—' }}</td>
                        <td class="text-end">Rs. {{ number_format($sess->opening_balance, 2) }}</td>
                        <td class="text-end text-success fw-semibold">Rs. {{ number_format($sess->cash_sales, 2) }}</td>
                        <td class="text-end">Rs. {{ number_format($sess->closing_balance ?? 0, 2) }}</td>
                        <td class="text-center">
                            @if($d !== null)
                                <span class="badge fw-semibold"
                                      style="{{ $d == 0 ? 'background:#e5e7eb;color:#374151;' : ($d > 0 ? 'background:#dcfce7;color:#10b981;' : 'background:#fee2e2;color:#ef4444;') }}">
                                    {{ $d >= 0 ? '+' : '' }}Rs. {{ number_format($d, 2) }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewSession({{ $sess->id }})">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">No closed sessions yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($history->hasPages())
        <div class="px-3 py-2">{{ $history->links() }}</div>
        @endif
    </div>
</div>

{{-- ======================== MODALS ======================== --}}

{{-- OPEN DRAWER MODAL --}}
<div class="modal fade" id="openDrawerModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,var(--gold),var(--gold-dark));">
                <h5 class="modal-title text-white"><i class="fas fa-lock-open me-2"></i> Open Drawer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Opening Balance (Rs.) <span class="text-danger">*</span></label>
                    <input type="number" id="openBalance" class="form-control" placeholder="0.00" min="0" step="0.01" required>
                </div>
                <div class="mb-1">
                    <label class="form-label fw-semibold">Notes</label>
                    <input type="text" id="openNotes" class="form-control" placeholder="e.g. Morning shift">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-gold" onclick="submitOpenDrawer()">
                    <i class="fas fa-lock-open me-1"></i> Open
                </button>
            </div>
        </div>
    </div>
</div>

{{-- CASH IN MODAL --}}
<div class="modal fade" id="cashInModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:#10b981;">
                <h5 class="modal-title text-white"><i class="fas fa-arrow-down me-2"></i> Cash In</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Amount (Rs.) <span class="text-danger">*</span></label>
                    <input type="number" id="cashInAmount" class="form-control" placeholder="0.00" min="0.01" step="0.01" required>
                </div>
                <div class="mb-1">
                    <label class="form-label fw-semibold">Description</label>
                    <input type="text" id="cashInDesc" class="form-control" placeholder="e.g. Float top-up, Loan received…">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn" style="background:#10b981;color:#fff;" onclick="submitCashIn()">
                    <i class="fas fa-arrow-down me-1"></i> Record Cash In
                </button>
            </div>
        </div>
    </div>
</div>

{{-- CASH OUT MODAL --}}
<div class="modal fade" id="cashOutModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white"><i class="fas fa-arrow-up me-2"></i> Cash Out</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Amount (Rs.) <span class="text-danger">*</span></label>
                    <input type="number" id="cashOutAmount" class="form-control" placeholder="0.00" min="0.01" step="0.01" required>
                </div>
                <div class="mb-1">
                    <label class="form-label fw-semibold">Description</label>
                    <input type="text" id="cashOutDesc" class="form-control" placeholder="e.g. Petty cash, Expense payment…">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="submitCashOut()">
                    <i class="fas fa-arrow-up me-1"></i> Record Cash Out
                </button>
            </div>
        </div>
    </div>
</div>

{{-- CLOSE DRAWER MODAL --}}
<div class="modal fade" id="closeDrawerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h5 class="modal-title text-white"><i class="fas fa-lock me-2"></i> Close Drawer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if($session)
                {{-- Summary snapshot --}}
                <div class="rounded p-3 mb-4" style="background:#f8f9fa;">
                    <h6 class="fw-bold mb-3">Session Summary <span class="text-muted fw-semibold small">(Rs.)</span></h6>
                    <div class="row g-2 text-center">
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Opening</div>
                            <div class="fw-bold">{{ number_format($session->opening_balance, 2) }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Cash Sales</div>
                            <div class="fw-bold text-success"><span id="close-sum-sales">{{ number_format($session->cash_sales, 2) }}</span></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Cash In / Out</div>
                            @php $cashAdjustment = $session->total_cash_in - $session->total_cash_out; @endphp
                            <div class="fw-bold {{ $cashAdjustment >= 0 ? 'text-success' : 'text-danger' }}" id="close-sum-cash-adjustment">
                                {{ number_format($cashAdjustment, 2) }}
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Expected</div>
                            <div class="fw-bold text-primary"><span id="close-sum-expected">{{ number_format($session->expected_balance, 2) }}</span></div>
                        </div>
                    </div>
                </div>
                @endif
                <div class="mb-3">
                    <label class="form-label fw-semibold">Actual Closing Balance (Rs.) <span class="text-danger">*</span></label>
                    <input type="number" id="closeBalance" class="form-control" placeholder="Count the cash in drawer…"
                           min="0" step="0.01" oninput="updateDifference()" required>
                </div>
                <div id="diff-preview" class="alert d-none mb-3 py-2 text-center fw-bold"></div>
                <div class="mb-1">
                    <label class="form-label fw-semibold">Closing Notes</label>
                    <input type="text" id="closeNotes" class="form-control" placeholder="Any remarks…">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark" onclick="submitCloseDrawer()">
                    <i class="fas fa-lock me-1"></i> Close Drawer
                </button>
            </div>
        </div>
    </div>
</div>

{{-- SESSION DETAIL MODAL --}}
<div class="modal fade" id="sessionDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-info-circle me-2 text-gold"></i> Session Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="session-detail-body">
                <div class="text-center py-4"><div class="spinner-border text-gold"></div></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.stat-drawer-card { border-radius: 12px; }
.stat-drawer-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto; font-size: 1.1rem;
}
.stat-drawer-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: 600; }
.stat-drawer-value { font-size: 1rem; font-weight: 700; color: #1f2937; }
.stat-drawer-sub   { font-size: 0.75rem; color: #6b7280; }
</style>
@endpush

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
@if($session)
const SESSION_EXPECTED = {{ $session->expected_balance }};
@endif

/* ---- Helpers ---- */
function fmtMoney(n) { return 'Rs. ' + parseFloat(n).toLocaleString('en-LK', {minimumFractionDigits:2,maximumFractionDigits:2}); }

function showToast(msg, success = true) {
    const d = document.createElement('div');
    d.className = `alert alert-${success?'success':'danger'} position-fixed shadow-lg`;
    d.style.cssText = 'top:80px;right:20px;z-index:9999;min-width:280px;border-radius:10px;';
    d.innerHTML = `<i class="fas fa-${success?'check-circle':'exclamation-circle'} me-2"></i>${msg}`;
    document.body.appendChild(d);
    setTimeout(() => d.remove(), 3800);
}

function moneyNumber(text) {
    const value = String(text || '0')
        .replace(/Rs\.\s*/gi, '')
        .replace(/,/g, '')
        .trim();

    return parseFloat(value) || 0;
}

function syncCloseSummary() {
    const expected = moneyNumber(document.getElementById('stat-expected')?.textContent);
    const cashIn = moneyNumber(document.getElementById('stat-cash-in')?.textContent);
    const cashOut = moneyNumber(document.getElementById('stat-cash-out')?.textContent);
    const cashAdjustment = cashIn - cashOut;

    const expectedEl = document.getElementById('close-sum-expected');
    const cashAdjustmentEl = document.getElementById('close-sum-cash-adjustment');

    if (expectedEl) expectedEl.textContent = expected.toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (cashAdjustmentEl) {
        cashAdjustmentEl.textContent = cashAdjustment.toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        cashAdjustmentEl.classList.toggle('text-success', cashAdjustment >= 0);
        cashAdjustmentEl.classList.toggle('text-danger', cashAdjustment < 0);
    }
}

/* ---- Open Drawer ---- */
function openDrawerModal() {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('openDrawerModal')).show();
}
function openDrawerQuick() {
    const bal = document.getElementById('quickOpenBalance')?.value;
    const note = document.getElementById('quickOpenNotes')?.value;
    if (!bal) { alert('Enter the opening balance.'); return; }
    doOpenDrawer(bal, note);
}
async function submitOpenDrawer() {
    const bal  = document.getElementById('openBalance').value;
    const note = document.getElementById('openNotes').value;
    if (!bal) { alert('Enter the opening balance.'); return; }
    await doOpenDrawer(bal, note);
    bootstrap.Modal.getInstance(document.getElementById('openDrawerModal'))?.hide();
}
async function doOpenDrawer(balance, notes) {
    const resp = await fetch('/cash-drawer/open', {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
        body: JSON.stringify({ opening_balance: balance, notes }),
    });
    const data = await resp.json();
    if (data.success) { location.reload(); } else { alert(data.message); }
}

/* ---- Cash In ---- */
function showCashInModal() {
    document.getElementById('cashInAmount').value = '';
    document.getElementById('cashInDesc').value   = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('cashInModal')).show();
}
async function submitCashIn() {
    const amount = document.getElementById('cashInAmount').value;
    const desc   = document.getElementById('cashInDesc').value;
    if (!amount || parseFloat(amount) <= 0) { alert('Enter a valid amount.'); return; }
    const resp = await fetch('/cash-drawer/cash-in', {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
        body: JSON.stringify({ amount, description: desc }),
    });
    const data = await resp.json();
    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('cashInModal')).hide();
        appendTxRow(data.tx, data.expected_balance);
        document.getElementById('stat-cash-in').textContent = fmtMoney(data.total_cash_in);
        document.getElementById('stat-expected').textContent = fmtMoney(data.expected_balance);
        syncCloseSummary();
        showToast(data.message);
    } else { alert(data.message); }
}

/* ---- Cash Out ---- */
function showCashOutModal() {
    document.getElementById('cashOutAmount').value = '';
    document.getElementById('cashOutDesc').value   = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('cashOutModal')).show();
}
async function submitCashOut() {
    const amount = document.getElementById('cashOutAmount').value;
    const desc   = document.getElementById('cashOutDesc').value;
    if (!amount || parseFloat(amount) <= 0) { alert('Enter a valid amount.'); return; }
    const resp = await fetch('/cash-drawer/cash-out', {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
        body: JSON.stringify({ amount, description: desc }),
    });
    const data = await resp.json();
    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('cashOutModal')).hide();
        appendTxRow(data.tx, data.expected_balance);
        document.getElementById('stat-cash-out').textContent = fmtMoney(data.total_cash_out);
        document.getElementById('stat-expected').textContent  = fmtMoney(data.expected_balance);
        syncCloseSummary();
        showToast(data.message);
    } else { alert(data.message); }
}

/* ---- Append TX row (AJAX, no reload) ---- */
let txCounter = parseInt(document.getElementById('tx-count-badge')?.textContent) || 0;
function appendTxRow(tx, newExpected) {
    const empty = document.getElementById('tx-empty-row');
    if (empty) empty.remove();

    txCounter++;
    document.getElementById('tx-count-badge').textContent = txCounter + ' entries';

    const tbody = document.getElementById('tx-tbody');
    const isCashIn = tx.type === 'cash_in';
    const time = new Date(tx.created_at).toLocaleTimeString('en-LK', {hour:'2-digit',minute:'2-digit'});
    const row = document.createElement('tr');
    row.innerHTML = `
        <td class="text-muted">${txCounter}</td>
        <td class="text-muted small">${time}</td>
        <td>${isCashIn
            ? `<span class="badge fw-semibold" style="background:#dcfce7;color:#10b981;"><i class="fas fa-arrow-down me-1"></i> Cash In</span>`
            : `<span class="badge fw-semibold" style="background:#fee2e2;color:#ef4444;"><i class="fas fa-arrow-up me-1"></i> Cash Out</span>`
        }</td>
        <td>${tx.description || '—'}</td>
        <td class="small text-muted">${tx.user?.name || '—'}</td>
        <td class="text-end fw-semibold ${isCashIn?'text-success':'text-danger'}">
            ${isCashIn?'+':'-'}Rs. ${parseFloat(tx.amount).toFixed(2)}
        </td>`;
    tbody.insertBefore(row, tbody.firstChild);
}

/* ---- Close Drawer ---- */
function showCloseModal() {
    syncCloseSummary();
    document.getElementById('closeBalance').value = '';
    document.getElementById('closeNotes').value = '';
    document.getElementById('diff-preview').className = 'alert d-none mb-3 py-2 text-center fw-bold';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('closeDrawerModal')).show();
}
function updateDifference() {
    const actual   = parseFloat(document.getElementById('closeBalance').value) || 0;
    const expected = parseFloat(document.getElementById('close-sum-expected')?.textContent?.replace(/,/g, '') || SESSION_EXPECTED);
    const diff = actual - expected;
    const el  = document.getElementById('diff-preview');
    el.className = `alert mb-3 py-2 text-center fw-bold ${diff===0?'alert-success':diff>0?'alert-info':'alert-danger'}`;
    el.textContent = diff === 0
        ? '✓ Balanced!'
        : diff > 0
            ? `▲ Surplus: Rs. ${Math.abs(diff).toFixed(2)}`
            : `▼ Shortage: Rs. ${Math.abs(diff).toFixed(2)}`;
}
async function submitCloseDrawer() {
    const balance = document.getElementById('closeBalance').value;
    const notes   = document.getElementById('closeNotes').value;
    if (!balance) { alert('Enter the actual closing balance.'); return; }
    if (!confirm('Close the cash drawer? This will end the current session.')) return;
    const resp = await fetch('/cash-drawer/close', {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
        body: JSON.stringify({ closing_balance: balance, close_notes: notes }),
    });
    const data = await resp.json();
    if (data.success) { location.reload(); } else { alert(data.message); }
}

function txMeta(type) {
    const types = {
        cash_in: { label: 'Cash In', color: '#10b981', sign: '+', positive: true },
        cash_out: { label: 'Cash Out', color: '#ef4444', sign: '-', positive: false },
        cash_sale: { label: 'Cash Sale', color: '#059669', sign: '+', positive: true },
        cash_refund: { label: 'Cash Refund', color: '#ef4444', sign: '-', positive: false },
    };

    return types[type] || { label: type.replace(/_/g, ' '), color: '#374151', sign: '', positive: true };
}

/* ---- View Session Detail ---- */
async function viewSession(id) {
    document.getElementById('session-detail-body').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-gold"></div></div>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('sessionDetailModal')).show();
    const resp = await fetch(`/cash-drawer/session/${id}`, { headers: { 'Accept':'application/json' } });
    const data = await resp.json();
    if (!data.success) { document.getElementById('session-detail-body').innerHTML = '<p class="text-danger">Failed to load.</p>'; return; }
    const s = data.session;
    const d = s.difference;
    s.transactions = (s.transactions || []).map(t => {
        const meta = txMeta(t.type);
        return {
            ...t,
            meta,
            user: t.user || { name: t.user_name || '-' },
        };
    });
    const transactionRows = s.transactions.map(t => `
            <tr>
                <td class="small">${new Date(t.created_at).toLocaleTimeString('en-LK',{hour:'2-digit',minute:'2-digit'})}</td>
                <td><span style="color:${t.meta.color};font-weight:600;">${t.meta.label}</span></td>
                <td>${t.description || '-'}</td>
                <td>${t.user?.name || '-'}</td>
                <td class="text-end fw-semibold ${t.meta.positive ? 'text-success' : 'text-danger'}">${t.meta.sign}Rs. ${parseFloat(t.amount).toFixed(2)}</td>
            </tr>`).join('');
    document.getElementById('session-detail-body').innerHTML = `
        <div class="row g-3 mb-4">
            <div class="col-md-4 text-center"><div class="bg-light rounded p-3"><div class="small text-muted">Opened</div><div class="fw-bold">${new Date(s.opened_at).toLocaleString('en-LK')}</div></div></div>
            <div class="col-md-4 text-center"><div class="bg-light rounded p-3"><div class="small text-muted">Closed</div><div class="fw-bold">${s.closed_at ? new Date(s.closed_at).toLocaleString('en-LK') : 'Still open'}</div></div></div>
            <div class="col-md-4 text-center"><div class="bg-light rounded p-3"><div class="small text-muted">Duration</div><div class="fw-bold">${s.duration}</div></div></div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3 text-center"><div class="rounded p-3" style="background:#e8f4fd;"><div class="small text-muted">Opening Balance</div><div class="fw-bold">Rs. ${parseFloat(s.opening_balance).toFixed(2)}</div></div></div>
            <div class="col-6 col-md-3 text-center"><div class="rounded p-3" style="background:#dcfce7;"><div class="small text-muted">Cash Sales (${s.cash_sales_count})</div><div class="fw-bold text-success">Rs. ${parseFloat(s.cash_sales).toFixed(2)}</div></div></div>
            <div class="col-6 col-md-3 text-center"><div class="rounded p-3" style="background:#fee2e2;"><div class="small text-muted">Cash Refunds</div><div class="fw-bold text-danger">Rs. ${parseFloat(s.cash_refunds).toFixed(2)}</div></div></div>
            <div class="col-6 col-md-3 text-center"><div class="rounded p-3 fw-bold" style="${d===null?'background:#e5e7eb;':d===0?'background:#dcfce7;':d>0?'background:#dbeafe;':'background:#fee2e2;'}"><div class="small text-muted">Difference</div>${d!==null?`<div class="fw-bold ${d>0?'text-primary':d<0?'text-danger':'text-success'}">${d>=0?'+':''}Rs. ${parseFloat(d).toFixed(2)}</div>`:'<div>—</div>'}</div></div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-6 text-center"><div class="rounded p-2 bg-light"><div class="small text-muted">Cash In</div><div class="fw-bold text-success">Rs. ${parseFloat(s.total_cash_in).toFixed(2)}</div></div></div>
            <div class="col-6 text-center"><div class="rounded p-2 bg-light"><div class="small text-muted">Cash Out</div><div class="fw-bold text-danger">Rs. ${parseFloat(s.total_cash_out).toFixed(2)}</div></div></div>
        </div>
        <h6 class="fw-bold mb-2">Transactions</h6>
        ${s.transactions && s.transactions.length > 0 ? `<div class="table-responsive"><table class="table table-sm table-bordered mb-0">
            <thead class="table-dark"><tr><th>Time</th><th>Type</th><th>Description</th><th>By</th><th class="text-end">Amount</th></tr></thead>
            <tbody>${transactionRows}</tbody>
        </table></div>`:'<p class="text-muted text-center py-2">No transactions in this session.</p>'}
        ${s.close_notes?`<p class="mt-3 mb-0 small text-muted fst-italic"><i class="fas fa-comment me-1"></i>${s.close_notes}</p>`:''}`;
}
</script>
@endpush
