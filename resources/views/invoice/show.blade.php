{{-- ============================================
Invoice Template - 80mm Thermal Receipt (Redesigned)
Jewellery POS System
============================================ --}}
@extends('layouts.pos')
@php
    $isEmbedded = request()->boolean('embed');
    $invoiceNumber = $sale->invoice_number
        ?? (($settings['invoice_prefix'] ?? 'INV-') . str_pad($sale->id, 4, '0', STR_PAD_LEFT));
    $payLabels = [
        'cash' => 'Cash',
        'koko' => 'Koko',
        'mintpay' => 'Mint Pay',
        'bank' => 'Bank',
        'card' => 'Card',
        'payzy' => 'Payzy',
        'multipay' => 'Multipay',
    ];
    $paymentSplits = collect($sale->payment_splits ?? []);
    $amountReceived = $sale->amount_received;
    if ($amountReceived === null && $paymentSplits->isNotEmpty()) {
        $amountReceived = $paymentSplits->sum(fn($split) => (float) ($split['amount'] ?? 0));
    }
    $changeAmount = $sale->change_amount ?? max(0, ((float) ($amountReceived ?? 0)) - (float) $sale->total);
@endphp
@section('title', 'Invoice #' . $invoiceNumber)

@section('content')
    <!-- Page Header (no-print) -->
    @unless($isEmbedded)
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap no-print mb-3">
        <div>
            <h4><i class="fas fa-file-invoice me-2 text-gold"></i> Receipt</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
                    <li class="breadcrumb-item active">Invoice #{{ $invoiceNumber }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <button class="btn btn-gold btn-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Print Receipt
            </button>
            <a href="{{ route('pos.index') }}" class="btn btn-outline-gold btn-sm">
                <i class="fas fa-cash-register me-1"></i> Back to POS
            </a>
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Sales History
            </a>
        </div>
    </div>
    @endunless

    <!-- 80mm Receipt Container -->
    <div class="receipt-wrapper no-print-hide">
        <div class="receipt" id="receipt">

            <!-- Store Header -->
            <div class="receipt-header">
                @if(!empty($settings['invoice_logo']))
                    <div class="receipt-logo-img">
                        <img src="{{ asset($settings['invoice_logo']) }}"
                            alt="{{ $settings['invoice_shop_name'] ?? 'Store Logo' }}">
                    </div>
                @else
                    <div class="receipt-logo-icon">&#9670;</div>
                @endif
                <div class="receipt-store-name">{{ $settings['invoice_shop_name'] ?? 'Jewellery POS' }}</div>
                <div class="receipt-store-tagline">{{ $settings['invoice_tagline'] ?? 'Premium Jewellery Store' }}</div>
                <div class="receipt-store-info">
                    <span>{{ $settings['invoice_address'] ?? '123 Main Street, Colombo 03' }}</span><br>
                    <span>Phone: {{ $settings['invoice_phone'] ?? '+94 11 234 5678' }}</span><br>
                    <span>{{ $settings['invoice_email'] ?? 'info@jewellerypos.com' }}</span>
                </div>
            </div>

            <!-- Section Divider: thick top -->
            <div class="divider divider-thick"></div>

            <!-- Invoice Info -->
            <div class="receipt-info-section">
                <div class="info-row">
                    <span class="info-label">Invoice No</span>
                    <span class="info-value info-highlight">#{{ $invoiceNumber }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date</span>
                    <span class="info-value">{{ $sale->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Time</span>
                    <span class="info-value">{{ $sale->created_at->format('h:i:s A') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cashier</span>
                    <span class="info-value">{{ $sale->user->name ?? 'Staff' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment</span>
                    <span class="info-value">
                        {{ $payLabels[$sale->payment_method] ?? ucfirst($sale->payment_method) }}
                    </span>
                </div>
            </div>

            <!-- Section Divider: thin -->
            <div class="divider divider-thin"></div>

            <!-- Items Table Header -->
            <div class="items-header">
                <span class="col-item">Item</span>
                <span class="col-qty">Qty</span>
                <span class="col-price">Price</span>
                <span class="col-total">Total</span>
            </div>

            <div class="divider divider-thin"></div>

            <!-- Items List -->
            <div class="items-body">
                @foreach($sale->items as $item)
                    <div class="item-row-group">
                        <div class="item-name">
                            {{ $item->product->name ?? $item->item_label ?? 'Deleted Product' }}
                            @if($item->variant)
                                @php $vLabel = collect([$item->variant->color, $item->variant->size])->filter()->implode(' / '); @endphp
                                <span style="font-size:0.8em; color:#666;"> - {{ $vLabel }}</span>
                            @endif
                        </div>
                        <div class="item-detail-row">
                            <span class="col-item"></span>
                            <span class="col-qty">{{ $item->qty }}</span>
                            <span class="col-price">{{ number_format($item->price, 2) }}</span>
                            <span class="col-total">{{ number_format($item->price * $item->qty, 2) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="divider divider-thin"></div>

            <!-- Subtotal / Discount -->
            @php
                $subtotal = $sale->items->sum(fn($item) => $item->price * $item->qty);
                $shippingFee = max(0, (float) $sale->total - (float) $subtotal + (float) $sale->discount_amount);
            @endphp
            <div class="totals-section">
                <div class="totals-row">
                    <span class="totals-label">Subtotal ({{ $sale->items->sum('qty') }} items)</span>
                    <span class="totals-value">Rs. {{ number_format($subtotal, 2) }}</span>
                </div>
                @if($shippingFee > 0)
                    <div class="totals-row">
                        <span class="totals-label">Shipping fee</span>
                        <span class="totals-value">Rs. {{ number_format($shippingFee, 2) }}</span>
                    </div>
                @endif
                @if($sale->discount_amount > 0)
                    <div class="totals-row">
                        <span class="totals-label">Discount
                            @if($sale->discount_type === 'percentage')({{ $sale->discount_value }}%)@endif
                        </span>
                        <span class="totals-value discount-value">- Rs. {{ number_format($sale->discount_amount, 2) }}</span>
                    </div>
                @endif
            </div>

            <!-- Grand Total -->
            <div class="grand-total-section">
                <div class="grand-total-row">
                    <span class="grand-label">TOTAL</span>
                    <span class="grand-amount">Rs. {{ number_format($sale->total, 2) }}</span>
                </div>
            </div>

            @if($sale->payment_method === 'multipay' && $paymentSplits->isNotEmpty())
                <div class="payment-breakdown-section">
                    @foreach($paymentSplits as $split)
                        <div class="payment-row">
                            <span class="payment-label">{{ $payLabels[$split['method'] ?? ''] ?? ucfirst($split['method'] ?? 'Payment') }}</span>
                            <span class="payment-value">Rs. {{ number_format((float) ($split['amount'] ?? 0), 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($amountReceived !== null && in_array($sale->payment_method, ['cash', 'multipay'], true))
                <div class="payment-breakdown-section">
                    <div class="payment-row">
                        <span class="payment-label">Amount Received</span>
                        <span class="payment-value">Rs. {{ number_format((float) $amountReceived, 2) }}</span>
                    </div>
                    <div class="payment-row">
                        <span class="payment-label">Balance</span>
                        <span class="payment-value">Rs. {{ number_format((float) $changeAmount, 2) }}</span>
                    </div>
                </div>
            @endif

            <div class="divider divider-thick"></div>

            <!-- Footer -->
            <div class="receipt-footer">
                <div class="footer-thanks">{{ $settings['invoice_thank_you'] ?? 'Thank you for your purchase!' }}</div>
                @if(!empty($settings['invoice_footer_line1'] ?? 'Goods once sold cannot be returned.'))
                    <div class="footer-note">{{ $settings['invoice_footer_line1'] ?? 'Goods once sold cannot be returned.' }}
                    </div>
                @endif
                @if(!empty($settings['invoice_footer_line2'] ?? 'Exchange within 7 days with receipt.'))
                    <div class="footer-note">{{ $settings['invoice_footer_line2'] ?? 'Exchange within 7 days with receipt.' }}
                    </div>
                @endif
                @if(!empty($settings['invoice_footer_line3'] ?? ''))
                    <div class="footer-note">{{ $settings['invoice_footer_line3'] }}</div>
                @endif
                <div class="footer-brand">POSSYSTEM.LK</div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Auto-print when ?print=1 is in the URL (triggered by "View Invoice" from POS)
        (function () {
            const params = new URLSearchParams(window.location.search);
            if (params.get('print') === '1') {
                // Small delay to ensure styles and images are rendered
                setTimeout(function () {
                    window.print();
                }, 600);
            }
        })();
    </script>
@endpush

@push('styles')
    <style>
        /* ========================================
               Receipt Redesign - Clean Modern Layout
               80mm Thermal Receipt
               ======================================== */

        /* --- Wrapper & Card --- */
        .receipt-wrapper {
            display: flex;
            justify-content: center;
            padding: 30px 0 40px;
            background: transparent;
        }

        .receipt {
            width: 360px;
            background: #ffffff;
            border-radius: 4px;
            padding: 22px 20px 20px;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.55;
            color: #1a1a1a;
            box-shadow: 0 4px 32px rgba(0, 0, 0, 0.13);
            border: 1px solid #e0e0e0;
        }

        /* --- Header --- */
        .receipt-header {
            text-align: center;
            padding-bottom: 10px;
        }

        .receipt-logo-img img {
            max-width: 80px;
            max-height: 60px;
            object-fit: contain;
            margin-bottom: 6px;
        }

        .receipt-logo-icon {
            font-size: 26px;
            color: #c8a951;
            margin-bottom: 4px;
        }

        .receipt-store-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #111;
            margin-bottom: 3px;
        }

        .receipt-store-tagline {
            font-size: 12px;
            color: #888;
            font-style: italic;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .receipt-store-info {
            font-size: 12px;
            color: #555;
            line-height: 1.8;
            font-weight: 500;
        }

        /* --- Dividers --- */
        .divider {
            width: 100%;
            border: none;
            margin: 0;
        }

        .divider-thick {
            border-top: 2px solid #111;
            margin: 10px 0;
        }

        .divider-thin {
            border-top: 1px dashed #bbb;
            margin: 8px 0;
        }

        /* --- Invoice Info Section --- */
        .receipt-info-section {
            padding: 2px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
            font-size: 13px;
        }

        .info-label {
            color: #555;
            font-weight: 600;
            flex: 1;
            white-space: nowrap;
        }

        .info-value {
            font-weight: 700;
            color: #111;
            text-align: right;
            flex: 1;
        }

        .info-highlight {
            font-weight: 800;
            color: #1a1a1a;
            letter-spacing: 0.5px;
        }

        /* --- Items Table --- */
        .items-header {
            display: flex;
            align-items: center;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #333;
            padding: 4px 0;
        }

        .col-item {
            flex: 1;
            text-align: left;
        }

        .col-qty {
            width: 32px;
            text-align: center;
        }

        .col-price {
            width: 68px;
            text-align: right;
        }

        .col-total {
            width: 72px;
            text-align: right;
        }

        /* --- Items Body --- */
        .items-body {
            padding: 2px 0;
        }

        .item-row-group {
            margin-bottom: 6px;
        }

        .item-name {
            font-size: 14px;
            font-weight: 700;
            color: #111;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-detail-row {
            display: flex;
            align-items: center;
            font-size: 13px;
            font-weight: 600;
            color: #333;
            padding-left: 10px;
        }

        /* --- Totals Section --- */
        .totals-section {
            padding: 2px 0;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            font-weight: 600;
            padding: 4px 0;
            color: #333;
        }

        .totals-label {
            flex: 1;
        }

        .totals-value {
            font-weight: 700;
            color: #111;
            text-align: right;
        }

        .discount-value {
            color: #c0392b;
        }

        /* --- Payment Breakdown --- */
        .payment-breakdown-section {
            padding: 4px 0;
            margin: 2px 0 4px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            font-weight: 600;
            padding: 3px 0;
            color: #333;
        }

        .payment-label {
            flex: 1;
        }

        .payment-value {
            font-weight: 700;
            color: #111;
            text-align: right;
        }

        /* --- Grand Total --- */
        .grand-total-section {
            background: #f7f7f7;
            border-radius: 4px;
            padding: 10px 12px;
            margin: 8px 0 4px;
        }

        .grand-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .grand-label {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #111;
        }

        .grand-amount {
            font-size: 19px;
            font-weight: 800;
            color: #111;
            letter-spacing: 0.5px;
        }

        /* --- Footer --- */
        .receipt-footer {
            text-align: center;
            padding-top: 4px;
        }

        .footer-thanks {
            font-size: 14px;
            font-weight: 800;
            color: #111;
            margin-bottom: 7px;
            letter-spacing: 0.3px;
        }

        .footer-note {
            font-size: 11px;
            color: #777;
            font-weight: 500;
            line-height: 1.7;
        }

        .footer-brand {
            font-size: 10px;
            color: #aaa;
            font-style: italic;
            margin-top: 8px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* ==============================
               Print Styles - 80mm Thermal
               ============================== */
        @media print {
            body * {
                visibility: hidden;
            }

            .receipt,
            .receipt * {
                visibility: visible;
            }

            .receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm;
                padding: 3mm 4mm;
                margin: 0;
                border: none;
                box-shadow: none;
                border-radius: 0;
                font-size: 13px;
            }

            .receipt-store-name {
                font-size: 17px;
            }

            .receipt-store-info {
                font-size: 11px;
                font-weight: 500;
            }

            .receipt-store-tagline {
                font-size: 11px;
            }

            .info-row {
                font-size: 12px;
                padding: 3px 0;
            }

            .info-label {
                font-weight: 600;
            }

            .info-value {
                font-weight: 700;
            }

            .items-header {
                font-size: 11px;
            }

            .item-name {
                font-size: 13px;
                font-weight: 700;
            }

            .item-detail-row {
                font-size: 12px;
                font-weight: 600;
            }

            .totals-row {
                font-size: 12px;
                font-weight: 600;
            }

            .payment-row {
                font-size: 12px;
                font-weight: 600;
            }

            .grand-label {
                font-size: 15px;
            }

            .grand-amount {
                font-size: 17px;
            }

            .footer-thanks {
                font-size: 13px;
                font-weight: 800;
            }

            .footer-note {
                font-size: 10px;
            }

            .grand-total-section {
                border-top: 2px solid #000;
                border-bottom: 2px solid #000;
                border-radius: 0;
                background: none;
            }

            @page {
                size: 80mm auto;
                margin: 0;
            }

            .pos-navbar,
            .pos-sidebar,
            .no-print,
            .page-header,
            .breadcrumb {
                display: none !important;
            }

            #page-content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }

            body {
                background: white !important;
                margin: 0;
                padding: 0;
            }
        }

        @if($isEmbedded)
        html,
        body {
            background: #f4f6f8 !important;
            overflow-x: hidden;
        }

        .pos-navbar,
        .pos-sidebar,
        .sidebar-overlay {
            display: none !important;
        }

        #page-content-wrapper {
            margin: 0 !important;
            min-height: 100vh !important;
        }

        #page-content-wrapper > .container-fluid {
            padding: 0 !important;
        }

        .receipt-wrapper {
            min-height: 100vh;
            align-items: flex-start;
            padding: 24px 12px 32px;
        }

        .receipt {
            max-width: calc(100vw - 24px);
        }
        @endif
    </style>
@endpush
