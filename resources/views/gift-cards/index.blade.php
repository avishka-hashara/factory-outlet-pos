{{-- ============================================
     Gift Cards Management - Jewellery POS System
     Dashboard counts, list/search, add, bulk import, sell/gift issuing
     ============================================ --}}
@extends('layouts.pos')
@section('title', 'Gift Cards')

@section('content')
<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h4><i class="fas fa-gift me-2 text-gold"></i> Gift Cards</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Gift Cards</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('gift-cards.template') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-file-csv me-1"></i> CSV Template
        </a>
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importGiftCardsModal">
            <i class="fas fa-file-import me-1"></i> Bulk Import
        </button>
        <button class="btn btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#addGiftCardModal">
            <i class="fas fa-plus me-1"></i> Add Card
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
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

@if(session('gc_import_report'))
    @php $report = session('gc_import_report'); @endphp
    <div class="alert {{ count($report['rejected']) ? 'alert-warning' : 'alert-success' }} alert-dismissible fade show" role="alert">
        <div class="fw-bold mb-1">
            <i class="fas fa-file-import me-1"></i>
            Import finished: {{ $report['imported'] }} card(s) imported, {{ count($report['rejected']) }} row(s) rejected.
        </div>
        @if(count($report['rejected']))
            <div style="max-height:220px;overflow-y:auto;">
                <table class="table table-sm table-bordered bg-white mb-0" style="font-size:0.8rem;">
                    <thead>
                        <tr>
                            <th style="width:70px;">Row</th>
                            <th style="width:160px;">Card No.</th>
                            <th>Reason(s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['rejected'] as $rej)
                            <tr>
                                <td>{{ $rej['row'] }}</td>
                                <td>{{ $rej['card_number'] }}</td>
                                <td>{{ implode('; ', $rej['reasons']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Summary Cards -->
<div class="row g-3 mb-4 align-items-stretch gift-card-summary-row">
    <div class="col-6 col-md">
        <div class="stat-card gold p-3 h-100 gift-card-summary-card">
            <div class="d-flex align-items-center gift-card-summary-inner">
                <div class="stat-icon gold-bg me-3"><i class="fas fa-gift"></i></div>
                <div>
                    <div class="stat-label">Total Cards</div>
                    <div class="stat-value" style="font-size:1.3rem;">{{ number_format($counts['total']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card blue p-3 h-100 gift-card-summary-card">
            <div class="d-flex align-items-center gift-card-summary-inner">
                <div class="stat-icon blue-bg me-3"><i class="fas fa-store"></i></div>
                <div>
                    <div class="stat-label">Available (In Stock)</div>
                    <div class="stat-value" style="font-size:1.3rem;">{{ number_format($counts['available']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card green p-3 h-100 gift-card-summary-card">
            <div class="d-flex align-items-center gift-card-summary-inner">
                <div class="stat-icon green-bg me-3"><i class="fas fa-cash-register"></i></div>
                <div>
                    <div class="stat-label">Sold</div>
                    <div class="stat-value" style="font-size:1.3rem;">{{ number_format($counts['sold']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card warning p-3 h-100 gift-card-summary-card">
            <div class="d-flex align-items-center gift-card-summary-inner">
                <div class="stat-icon warning-bg me-3"><i class="fas fa-hand-holding-heart"></i></div>
                <div>
                    <div class="stat-label">Gifted</div>
                    <div class="stat-value" style="font-size:1.3rem;">{{ number_format($counts['gifted']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card red p-3 h-100 gift-card-summary-card">
            <div class="d-flex align-items-center gift-card-summary-inner">
                <div class="stat-icon red-bg me-3"><i class="fas fa-check-double"></i></div>
                <div>
                    <div class="stat-label">Redeemed</div>
                    <div class="stat-value" style="font-size:1.3rem;">{{ number_format($counts['redeemed']) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search / Filter -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('gift-cards.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                    placeholder="Search card number or barcode (scan works here too)...">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    @foreach(['available','sold','gifted','redeemed','cancelled'] as $st)
                        <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-gold btn-sm" type="submit"><i class="fas fa-search me-1"></i> Search</button>
            </div>
            @if(request('search') || request('status'))
                <div class="col-auto">
                    <a href="{{ route('gift-cards.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            @endif
        </form>
    </div>
</div>

<!-- Cards Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr style="font-size:0.78rem;text-transform:uppercase;color:#6b7280;">
                        <th class="ps-3">Card Number</th>
                        <th>Barcode</th>
                        <th class="text-end">Value (Rs.)</th>
                        <th class="text-center">Status</th>
                        <th>Expiry</th>
                        <th>Issued</th>
                        <th>Redeemed</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cards as $card)
                        @php $live = $card->liveStatus(); @endphp
                        <tr style="font-size:0.85rem;">
                            <td class="ps-3 fw-bold">{{ $card->card_number }}</td>
                            <td class="text-muted">{{ $card->barcode }}</td>
                            <td class="text-end fw-bold" style="color:#d4891a;">{{ number_format((float) $card->value, 2) }}</td>
                            <td class="text-center">
                                @php
                                    $badge = [
                                        'available' => 'background:#dbeafe;color:#1d4ed8;',
                                        'sold'      => 'background:#dcfce7;color:#15803d;',
                                        'gifted'    => 'background:#f3e8ff;color:#7c3aed;',
                                        'redeemed'  => 'background:#e5e7eb;color:#374151;',
                                        'expired'   => 'background:#fee2e2;color:#b91c1c;',
                                        'cancelled' => 'background:#fee2e2;color:#b91c1c;',
                                    ][$live] ?? 'background:#e5e7eb;color:#374151;';
                                @endphp
                                <span style="{{ $badge }}border-radius:20px;padding:3px 10px;font-size:0.72rem;font-weight:700;text-transform:uppercase;">{{ $live }}</span>
                            </td>
                            <td>{{ $card->expiry_date?->format('Y-m-d') ?? 'Never' }}</td>
                            <td>
                                @if($card->issued_at)
                                    {{ $card->issued_at->format('Y-m-d') }}
                                    @if($card->issued_sale_id)
                                        <a href="{{ route('invoice.show', $card->issued_sale_id) }}" class="text-decoration-none" title="Issuing sale">
                                            <i class="fas fa-receipt text-muted" style="font-size:0.75rem;"></i>
                                        </a>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($card->redeemed_at)
                                    {{ $card->redeemed_at->format('Y-m-d') }}
                                    @if($card->redeemed_sale_id)
                                        <a href="{{ route('invoice.show', $card->redeemed_sale_id) }}" class="text-decoration-none" title="Redeeming sale">
                                            <i class="fas fa-receipt text-muted" style="font-size:0.75rem;"></i>
                                        </a>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @if($card->status === \App\Models\GiftCard::STATUS_AVAILABLE)
                                    <form method="POST" action="{{ route('gift-cards.sell', $card) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm py-0 px-2" style="font-size:0.75rem;"
                                            title="Open POS with this card in the cart">
                                            <i class="fas fa-cash-register me-1"></i>Sell
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('gift-cards.gift', $card) }}" class="d-inline"
                                        onsubmit="return confirm('Gift card {{ $card->card_number }} (Rs. {{ number_format((float) $card->value, 2) }}) free of charge?\n\nIt leaves the shop immediately with NO bill and NO revenue.');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size:0.75rem;">
                                            <i class="fas fa-hand-holding-heart me-1"></i>Gift
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted" style="font-size:0.75rem;">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-gift d-block mb-2" style="font-size:1.5rem;"></i>
                                No gift cards found. Add one or import the CSV template.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($cards->hasPages())
        <div class="card-footer bg-white">
            {{ $cards->links() }}
        </div>
    @endif
</div>

<!-- Add Card Modal -->
<div class="modal fade" id="addGiftCardModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#1B2A4A,#2C3E6B);color:#fff;border-radius:12px 12px 0 0;">
                <h5 class="modal-title" style="font-size:1rem;"><i class="fas fa-gift me-2" style="color:#f5a623;"></i>Add Gift Card</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('gift-cards.store') }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">Card Number <span class="text-danger">*</span></label>
                        <input type="text" name="card_number" value="{{ old('card_number') }}" class="form-control" required placeholder="e.g. GC10000001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">
                            Barcode <span class="text-danger">*</span>
                            <span class="text-muted fw-normal">(click the field, then SCAN the card)</span>
                        </label>
                        <input type="text" name="barcode" value="{{ old('barcode') }}" class="form-control" required
                            autocomplete="off" placeholder="Scan with barcode reader..."
                            onkeydown="if(event.key==='Enter'){event.preventDefault();}">
                        <div class="form-text" style="font-size:0.72rem;">A barcode reader types the code and sends Enter — Enter is captured so the form won't submit early.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">Value (Rs.) <span class="text-danger">*</span></label>
                        <input type="text" inputmode="decimal" name="value" value="{{ old('value') }}" class="form-control" required placeholder="e.g. 5000 or 5000.00" pattern="\d{1,10}(\.\d{1,2})?">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">Issued Date</label>
                            <input type="date" name="issued_date" value="{{ old('issued_date') }}" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">Expiry Date</label>
                            <input type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="form-control">
                            <div class="form-text" style="font-size:0.72rem;">Leave empty = never expires.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e5e7eb;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border:1px solid #d1d5db;">Cancel</button>
                    <button type="submit" class="btn btn-gold"><i class="fas fa-plus me-1"></i> Add Card</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importGiftCardsModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#1B2A4A,#2C3E6B);color:#fff;border-radius:12px 12px 0 0;">
                <h5 class="modal-title" style="font-size:1rem;"><i class="fas fa-file-import me-2" style="color:#f5a623;"></i>Bulk Import Gift Cards</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('gift-cards.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-light border" style="font-size:0.8rem;">
                        <div class="fw-bold mb-1"><i class="fas fa-info-circle me-1 text-primary"></i>How it works</div>
                        <ol class="mb-1 ps-3">
                            <li>Download the <a href="{{ route('gift-cards.template') }}">CSV template</a> and fill one row per physical card.</li>
                            <li>Required: <code>card_number</code>, <code>barcode</code>, <code>value</code>. Optional: <code>issued_date</code>, <code>expiry_date</code> (YYYY-MM-DD).</li>
                            <li>Every card imports as <strong>Available</strong>. 15 cards of the same value = 15 rows, each with its own unique card number and barcode.</li>
                        </ol>
                        <div class="text-muted">Working in Excel? Save as <strong>CSV</strong> before uploading. Invalid rows are rejected with reasons; valid rows still import.</div>
                    </div>
                    <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e5e7eb;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border:1px solid #d1d5db;">Cancel</button>
                    <button type="submit" class="btn btn-gold"><i class="fas fa-file-import me-1"></i> Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .gift-card-summary-card {
        min-height: 50px;
        display: flex;
        align-items: center;
    }

    .gift-card-summary-inner {
        width: 100%;
        min-width: 0;
    }

    .gift-card-summary-inner > div:last-child {
        min-width: 0;
    }

    .gift-card-summary-card .stat-icon {
        flex: 0 0 62px;
    }

    .gift-card-summary-card .stat-label {
        min-height: 38px;
        display: flex;
        align-items: center;
        line-height: 1.35;
    }

    .gift-card-summary-card .stat-value {
        line-height: 1.15;
        white-space: nowrap;
    }

    @media (max-width: 575.98px) {
        .gift-card-summary-card {
            min-height: 96px;
        }

        .gift-card-summary-card .stat-icon {
            flex-basis: 50px;
            margin-right: 0.75rem !important;
        }
    }
</style>
@endpush
