@extends('layouts.pos')
@section('title', 'Process Return — ' . ($sale->invoice_number ?? ('INV-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT))))

@section('content')
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h4><i class="fas fa-undo-alt me-2 text-gold"></i> Process Return</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('returns.index') }}">Returns</a></li>
                    <li class="breadcrumb-item active">New Return for {{ $sale->invoice_number ?? ('INV-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT)) }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('sales.show', $sale) }}" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0">
            <i class="fas fa-file-invoice me-1"></i> View Original Invoice
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('returns.store', $sale) }}" id="returnForm">
        @csrf
        <div class="row g-4">
            {{-- Left: Items to Return --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-boxes me-2 text-gold"></i>Select Items to Return</h6>
                        <small class="text-muted">Only quantities not previously returned are shown.</small>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width:40px;"></th>
                                    <th>Product</th>
                                    <th class="text-center">Orig. Qty</th>
                                    <th class="text-center" style="width:110px;">Return Qty</th>
                                    <th style="width:150px;">Condition</th>
                                    <th class="text-center" style="width:90px;">Restock</th>
                                    <th style="width:170px;">Condition Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returnableItems as $index => $item)
                                    <input type="hidden" name="items[{{ $index }}][sale_item_id]" value="{{ $item->id }}">
                                    <tr class="return-row" data-price="{{ $item->refund_unit_price }}">
                                        <td class="ps-3">
                                            <input type="checkbox" class="form-check-input item-check" data-index="{{ $index }}"
                                                id="check_{{ $index }}" onchange="toggleRow(this)">
                                        </td>
                                        <td>
                                            <label for="check_{{ $index }}" class="d-flex align-items-center gap-2 cursor-pointer mb-0">
                                                @if($item->product?->image_url)
                                                    <img src="{{ $item->product->image_url }}" class="rounded" style="width:36px;height:36px;object-fit:cover;" alt="">
                                                @else
                                                    <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                                        <i class="fas fa-tshirt text-muted small"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-semibold small">{{ $item->product?->name ?? 'Unknown' }}</div>
                                                    @if($item->variant)
                                                        <div class="text-muted" style="font-size:0.72rem;">
                                                            {{ collect([$item->variant->color, $item->variant->size])->filter()->implode(' / ') }}
                                                        </div>
                                                    @endif
                                                    <div class="text-muted" style="font-size:0.72rem;">
                                                        Refund: Rs. {{ number_format($item->refund_unit_price, 2) }} each
                                                        @if(abs($item->refund_unit_price - (float) $item->price) >= 0.01)
                                                            <span class="text-decoration-line-through ms-1">Rs. {{ number_format($item->price, 2) }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </label>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ $item->returnable_qty }}</span>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" name="items[{{ $index }}][qty]"
                                                class="form-control form-control-sm text-center qty-input"
                                                data-index="{{ $index }}"
                                                min="0" max="{{ $item->returnable_qty }}"
                                                value="0" disabled
                                                style="width:75px; margin:auto;">
                                        </td>
                                        <td>
                                            <select name="items[{{ $index }}][condition]"
                                                class="form-select form-select-sm condition-select" data-index="{{ $index }}"
                                                disabled onchange="updateRestock(this, {{ $index }})">
                                                <option value="good">Good Condition</option>
                                                <option value="damaged">Damaged</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input restock-check" type="checkbox"
                                                    name="items[{{ $index }}][restock]" value="1"
                                                    id="restock_{{ $index }}" checked disabled>
                                            </div>
                                            <div class="text-muted" style="font-size:0.65rem;" id="restock_label_{{ $index }}">Add to stock</div>
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $index }}][condition_notes]"
                                                class="form-control form-control-sm notes-input"
                                                placeholder="Optional notes..." disabled>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Check items to include in this return</span>
                        <div class="fw-bold">
                            Refund Total: <span class="text-danger" id="refund-total">Rs. 0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Return Details --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2 text-gold"></i>Return Details</h6>
                    </div>
                    <div class="card-body">
                        {{-- Original Sale Info --}}
                        <div class="bg-light rounded p-3 mb-3">
                            <div class="small text-muted mb-1">Original Sale</div>
                            <div class="fw-bold">{{ $sale->invoice_number ?? ('INV-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT)) }}</div>
                            <div class="small text-muted">{{ $sale->created_at->format('d M Y, h:i A') }}</div>
                            <div class="small mt-1">
                                <span class="badge bg-success">{{ ucfirst($sale->payment_method) }}</span>
                                <span class="ms-1 fw-semibold">Rs. {{ number_format($sale->total, 2) }}</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Return Reason <span class="text-danger">*</span></label>
                            <select name="reason" class="form-select @error('reason') is-invalid @enderror" required>
                                @foreach(\App\Models\ProductReturn::reasonLabels() as $val => $label)
                                    <option value="{{ $val }}" {{ old('reason') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Refund Method <span class="text-danger">*</span></label>
                            <select name="refund_method" class="form-select @error('refund_method') is-invalid @enderror" required>
                                <option value="cash" {{ old('refund_method', $sale->payment_method) === 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="card" {{ old('refund_method', $sale->payment_method) === 'card' ? 'selected' : '' }}>Card</option>
                                <option value="store_credit" {{ old('refund_method') === 'store_credit' ? 'selected' : '' }}>Store Credit</option>
                                <option value="bank" {{ old('refund_method') === 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                            </select>
                            @error('refund_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"
                                placeholder="Additional notes about this return...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="alert alert-warning py-2 small mb-3">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>Inventory:</strong> Items marked "Add to stock" will be restocked. Damaged items should be unchecked.
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger" id="submitBtn" disabled>
                                <i class="fas fa-undo-alt me-2"></i>Process Return & Refund
                            </button>
                            <a href="{{ route('sales.show', $sale) }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    function toggleRow(checkbox) {
        const index = checkbox.dataset.index;
        const row = checkbox.closest('tr');
        const qtyInput    = row.querySelector('.qty-input');
        const condSelect  = row.querySelector('.condition-select');
        const restockChk  = row.querySelector('.restock-check');
        const notesInput  = row.querySelector('.notes-input');

        if (checkbox.checked) {
            qtyInput.disabled   = false;
            condSelect.disabled = false;
            restockChk.disabled = false;
            notesInput.disabled = false;
            if (parseInt(qtyInput.value) === 0) {
                qtyInput.value = qtyInput.max; // default to full returnable qty
            }
        } else {
            qtyInput.disabled   = true;
            condSelect.disabled = true;
            restockChk.disabled = true;
            notesInput.disabled = true;
            qtyInput.value = 0;
        }

        updateTotal();
        updateSubmitBtn();
    }

    function updateRestock(select, index) {
        const restockLabel = document.getElementById('restock_label_' + index);
        const restockChk   = document.getElementById('restock_' + index);
        if (select.value === 'damaged') {
            restockChk.checked = false;
            restockLabel.textContent = 'Keep as damaged';
        } else {
            restockChk.checked = true;
            restockLabel.textContent = 'Add to stock';
        }
    }

    function updateTotal() {
        let total = 0;
        document.querySelectorAll('.return-row').forEach(row => {
            const chk   = row.querySelector('.item-check');
            const qty   = row.querySelector('.qty-input');
            const price = parseFloat(row.dataset.price) || 0;
            if (chk && chk.checked && qty) {
                total += (parseInt(qty.value) || 0) * price;
            }
        });
        document.getElementById('refund-total').textContent = 'Rs. ' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function updateSubmitBtn() {
        const anyChecked = [...document.querySelectorAll('.item-check')].some(c => c.checked);
        document.getElementById('submitBtn').disabled = !anyChecked;
    }

    // Live total recalculation on qty change
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('input', updateTotal);
    });
</script>
@endpush
