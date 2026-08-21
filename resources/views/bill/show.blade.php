{{-- ============================================
Public Bill View — /b/{token}
Standalone page (no app layout, no auth).
Shows nothing beyond what is on the printed receipt.
============================================ --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    @php
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
        $subtotal = $sale->items->sum(fn($item) => $item->price * $item->qty);
        $shippingFee = max(0, (float) $sale->total - (float) $subtotal + (float) $sale->discount_amount);
    @endphp
    <title>Receipt #{{ $invoiceNumber }} — {{ $settings['invoice_shop_name'] ?? 'Jewellery POS' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
            color: #1f2937;
            padding: 16px 12px 40px;
        }
        .receipt {
            max-width: 420px;
            margin: 0 auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 24px 20px;
        }
        .shop-name { font-size: 1.25rem; font-weight: 800; text-align: center; }
        .shop-tagline { font-size: 0.8rem; color: #6b7280; text-align: center; margin-top: 2px; }
        .shop-info { font-size: 0.75rem; color: #6b7280; text-align: center; margin-top: 8px; line-height: 1.5; }
        .divider { border-top: 2px dashed #e2e8f0; margin: 16px 0; }
        .info-row { display: flex; justify-content: space-between; font-size: 0.85rem; padding: 3px 0; }
        .info-row .label { color: #64748b; }
        .info-row .value { font-weight: 600; }
        .item { padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .item-name { font-size: 0.85rem; font-weight: 600; }
        .item-variant { font-size: 0.75rem; color: #6b7280; font-weight: 400; }
        .item-detail { display: flex; justify-content: space-between; font-size: 0.8rem; color: #475569; margin-top: 2px; }
        .totals { margin-top: 12px; }
        .total-row { display: flex; justify-content: space-between; font-size: 0.85rem; padding: 3px 0; color: #475569; }
        .grand { display: flex; justify-content: space-between; font-size: 1.05rem; font-weight: 800; padding: 10px 0 0; }
        .discount { color: #dc2626; }
        .footer { text-align: center; font-size: 0.75rem; color: #94a3b8; margin-top: 18px; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="shop-name">{{ $settings['invoice_shop_name'] ?? 'Jewellery POS' }}</div>
        <div class="shop-tagline">{{ $settings['invoice_tagline'] ?? '' }}</div>
        <div class="shop-info">
            @if(!empty($settings['invoice_address'])){{ $settings['invoice_address'] }}<br>@endif
            @if(!empty($settings['invoice_phone']))Phone: {{ $settings['invoice_phone'] }}@endif
        </div>

        <div class="divider"></div>

        <div class="info-row"><span class="label">Invoice No</span><span class="value">#{{ $invoiceNumber }}</span></div>
        <div class="info-row"><span class="label">Date</span><span class="value">{{ $sale->created_at->format('d/m/Y') }}</span></div>
        <div class="info-row"><span class="label">Time</span><span class="value">{{ $sale->created_at->format('h:i A') }}</span></div>
        <div class="info-row"><span class="label">Payment</span><span class="value">{{ $payLabels[$sale->payment_method] ?? ucfirst($sale->payment_method) }}</span></div>

        <div class="divider"></div>

        @foreach($sale->items as $item)
            <div class="item">
                <div class="item-name">
                    {{ $item->product->name ?? $item->item_label ?? 'Item' }}
                    @if($item->variant)
                        @php $vLabel = collect([$item->variant->color, $item->variant->size])->filter()->implode(' / '); @endphp
                        @if($vLabel !== '')<span class="item-variant"> — {{ $vLabel }}</span>@endif
                    @endif
                </div>
                <div class="item-detail">
                    <span>{{ $item->qty }} × Rs. {{ number_format($item->price, 2) }}</span>
                    <span>Rs. {{ number_format($item->price * $item->qty, 2) }}</span>
                </div>
            </div>
        @endforeach

        <div class="totals">
            <div class="total-row"><span>Subtotal ({{ $sale->items->sum('qty') }} items)</span><span>Rs. {{ number_format($subtotal, 2) }}</span></div>
            @if($shippingFee > 0)
                <div class="total-row"><span>Shipping fee</span><span>Rs. {{ number_format($shippingFee, 2) }}</span></div>
            @endif
            @if($sale->discount_amount > 0)
                <div class="total-row discount">
                    <span>Discount @if($sale->discount_type === 'percentage')({{ $sale->discount_value }}%)@endif</span>
                    <span>- Rs. {{ number_format($sale->discount_amount, 2) }}</span>
                </div>
            @endif
            <div class="grand"><span>TOTAL</span><span>Rs. {{ number_format($sale->total, 2) }}</span></div>
        </div>

        <div class="divider"></div>

        <div class="footer">
            {{ $settings['invoice_thank_you'] ?? 'Thank you for your purchase!' }}<br>
            This is a computer generated receipt.
        </div>
    </div>
</body>
</html>
