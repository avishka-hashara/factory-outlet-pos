<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Sales Report</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
html, body { margin: 0; padding: 0; font-family: 'Calibri', Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
body { padding: 20px 25px; }
.header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #F5A623; padding-bottom: 15px; }
.header h1 { margin: 0; font-size: 24px; color: #1a1a1a; font-weight: bold; }
.header p { margin: 5px 0 0 0; font-size: 11px; color: #666; }
.content-wrapper { }
.section { margin-bottom: 20px; }
.section-title { background-color: #F5A623; color: white; padding: 10px 12px; font-size: 13px; font-weight: bold; margin: 0 0 15px 0; }
.stats { margin-bottom: 15px; }
.stat-row { display: flex; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
.stat-box { flex: 1; background-color: #f9f9f9; border: 1px solid #ddd; padding: 12px; min-width: 140px; }
.stat-label { font-size: 10px; color: #999; text-transform: uppercase; margin-bottom: 4px; font-weight: bold; }
.stat-value { font-size: 16px; font-weight: bold; color: #1a1a1a; }
.payment-section { margin-top: 15px; padding-top: 12px; border-top: 1px solid #ddd; }
.payment-section h3 { font-size: 12px; margin: 0 0 8px 0; font-weight: bold; color: #1a1a1a; }
.payment-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee; font-size: 11px; }
.payment-method { font-weight: 500; text-transform: capitalize; }
.payment-amount { font-weight: bold; color: #F5A623; }
.products-section { margin-top: 15px; padding-top: 12px; border-top: 1px solid #ddd; }
.products-section h3 { font-size: 12px; margin: 0 0 10px 0; font-weight: bold; color: #1a1a1a; }
table { width: 100%; border-collapse: collapse; font-size: 11px; line-height: 1.4; margin-top: 8px; }
table thead th { background-color: #f5f5f5; padding: 8px 10px; text-align: left; font-weight: bold; border-bottom: 2px solid #F5A623; font-size: 11px; }
table tbody td { padding: 7px 10px; border-bottom: 1px solid #eee; }
table tbody tr:nth-child(even) { background-color: #fafafa; }
.footer { margin-top: 25px; padding-top: 12px; border-top: 1px solid #ddd; text-align: center; color: #999; font-size: 10px; }
.page-break { display: none; }
</style>
</head>
<body>

<div class="header">
<h1>B CLASSY LIFESTYLE</h1>
<p>Sales Report | {{ date('F d, Y') }}</p>
</div>

<div class="content-wrapper">

@php
$reportTab = $tab ?? 'daily';
@endphp

@if($reportTab === 'daily')
<!-- Daily Report Section -->
<div class="section">
@php
$dateStr = $date ?? date('Y-m-d');
try {
    $dateObj = \Carbon\Carbon::parse($dateStr);
    $dateDisplay = $dateObj->format('F d, Y');
} catch (\Exception $e) {
    $dateDisplay = $dateStr;
}
@endphp
<div class="section-title">Daily Report - {{ $dateDisplay }}</div>

<div class="stats">
    <div class="stat-row">
        <div class="stat-box">
            <div class="stat-label">Total Sales</div>
            <div class="stat-value">{{ $totalSales ?? 0 }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value">Rs. {{ number_format($revenue ?? 0, 0) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Items Sold</div>
            <div class="stat-value">{{ $itemsSold ?? 0 }}</div>
        </div>
    </div>
    <div class="stat-row">
        <div class="stat-box">
            <div class="stat-label">Average Sale Value</div>
            <div class="stat-value">Rs. {{ number_format($avgSale ?? 0, 0) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Total Refunds</div>
            <div class="stat-value">{{ $totalRefunds ?? 0 }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Refund Value</div>
            <div class="stat-value">Rs. {{ number_format($refundValue ?? 0, 0) }}</div>
        </div>
    </div>
</div>

<!-- Payment Breakdown -->
@if(isset($payments) && is_array($payments) && collect($payments)->filter(fn($amount) => abs($amount) > 0.01)->isNotEmpty())
<div class="payment-section">
    <h3>Payment Methods Breakdown</h3>
    @foreach($payments as $method => $amount)
        @if(abs($amount) > 0.01)
        <div class="payment-row">
            <span class="payment-method">{{ ucfirst($method) }}</span>
            <span class="payment-amount">{{ $amount < 0 ? '-Rs. ' : 'Rs. ' }}{{ number_format(abs($amount), 2) }}</span>
        </div>
        @endif
    @endforeach
</div>
@endif

<!-- Top Products -->
@if(isset($topProducts) && is_object($topProducts) && $topProducts->count() > 0)
<div class="products-section">
    <h3>Top Selling Products</h3>
    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th style="text-align: right; width: 60px;">Quantity</th>
                <th style="text-align: right; width: 100px;">Revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topProducts->take(5) as $item)
            <tr>
                <td>{{ $item->product?->name ?? $item->item_label ?? 'Deleted Product' }}</td>
                <td style="text-align: right;">{{ $item->total_qty ?? 0 }}</td>
                <td style="text-align: right;">Rs. {{ number_format($item->total_revenue ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
</div>
@endif

@if($reportTab === 'monthly')

<!-- Monthly Report Section -->
<div class="section">
@php
$monthStr = $month ?? date('Y-m');
try {
    $monthObj = \Carbon\Carbon::parse($monthStr . '-01');
    $monthDisplay = $monthObj->format('F Y');
} catch (\Exception $e) {
    $monthDisplay = $monthStr;
}
@endphp
<div class="section-title">Monthly Report - {{ $monthDisplay }}</div>

<div class="stats">
    <div class="stat-row">
        <div class="stat-box">
            <div class="stat-label">Total Sales</div>
            <div class="stat-value">{{ $monthlyTotalSales ?? 0 }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value">Rs. {{ number_format($monthlyRevenue ?? 0, 0) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Items Sold</div>
            <div class="stat-value">{{ $monthlyItemsSold ?? 0 }}</div>
        </div>
    </div>
    <div class="stat-row">
        <div class="stat-box">
            <div class="stat-label">Average Daily Revenue</div>
            <div class="stat-value">Rs. {{ number_format($monthlyAvgDaily ?? 0, 0) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Total Refunds</div>
            <div class="stat-value">{{ $monthlyTotalRefunds ?? 0 }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Refund Value</div>
            <div class="stat-value">Rs. {{ number_format($monthlyRefundValue ?? 0, 0) }}</div>
        </div>
    </div>
</div>

<!-- Payment Breakdown -->
@if(isset($monthlyPayments) && is_array($monthlyPayments) && collect($monthlyPayments)->filter(fn($amount) => abs($amount) > 0.01)->isNotEmpty())
<div class="payment-section">
    <h3>Payment Methods Breakdown</h3>
    @foreach($monthlyPayments as $method => $amount)
        @if(abs($amount) > 0.01)
        <div class="payment-row">
            <span class="payment-method">{{ ucfirst($method) }}</span>
            <span class="payment-amount">{{ $amount < 0 ? '-Rs. ' : 'Rs. ' }}{{ number_format(abs($amount), 2) }}</span>
        </div>
        @endif
    @endforeach
</div>
@endif

<!-- Top Products -->
@if(isset($monthlyTopProducts) && is_object($monthlyTopProducts) && $monthlyTopProducts->count() > 0)
<div class="products-section">
    <h3>Top Selling Products</h3>
    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th style="text-align: right; width: 60px;">Quantity</th>
                <th style="text-align: right; width: 100px;">Revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthlyTopProducts->take(5) as $item)
            <tr>
                <td>{{ $item->product?->name ?? $item->item_label ?? 'Deleted Product' }}</td>
                <td style="text-align: right;">{{ $item->total_qty ?? 0 }}</td>
                <td style="text-align: right;">Rs. {{ number_format($item->total_revenue ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
</div>
@endif

@if($reportTab === 'custom')

<!-- Custom Range Report Section -->
<div class="section">
@php
$fromStr = $dateFrom ?? date('Y-m-d');
$toStr = $dateTo ?? date('Y-m-d');
try {
    $fromObj = \Carbon\Carbon::parse($fromStr);
    $toObj = \Carbon\Carbon::parse($toStr);
    $rangeDisplay = $fromObj->format('M d') . ' to ' . $toObj->format('M d, Y');
} catch (\Exception $e) {
    $rangeDisplay = $fromStr . ' to ' . $toStr;
}
@endphp
<div class="section-title">Custom Range Report - {{ $rangeDisplay }}</div>

<div class="stats">
    <div class="stat-row">
        <div class="stat-box">
            <div class="stat-label">Total Sales</div>
            <div class="stat-value">{{ $customTotalSales ?? 0 }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value">Rs. {{ number_format($customRevenue ?? 0, 0) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Items Sold</div>
            <div class="stat-value">{{ $customItemsSold ?? 0 }}</div>
        </div>
    </div>
    <div class="stat-row">
        <div class="stat-box">
            <div class="stat-label">Average Daily Revenue</div>
            <div class="stat-value">Rs. {{ number_format($customAvgDaily ?? 0, 0) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Total Refunds</div>
            <div class="stat-value">{{ $customTotalRefunds ?? 0 }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Refund Value</div>
            <div class="stat-value">Rs. {{ number_format($customRefundValue ?? 0, 0) }}</div>
        </div>
    </div>
</div>

<!-- Payment Breakdown -->
@if(isset($customPayments) && is_array($customPayments) && collect($customPayments)->filter(fn($amount) => abs($amount) > 0.01)->isNotEmpty())
<div class="payment-section">
    <h3>Payment Methods Breakdown</h3>
    @foreach($customPayments as $method => $amount)
        @if(abs($amount) > 0.01)
        <div class="payment-row">
            <span class="payment-method">{{ ucfirst($method) }}</span>
            <span class="payment-amount">{{ $amount < 0 ? '-Rs. ' : 'Rs. ' }}{{ number_format(abs($amount), 2) }}</span>
        </div>
        @endif
    @endforeach
</div>
@endif

<!-- Top Products -->
@if(isset($customTopProducts) && is_object($customTopProducts) && $customTopProducts->count() > 0)
<div class="products-section">
    <h3>Top Selling Products</h3>
    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th style="text-align: right; width: 60px;">Quantity</th>
                <th style="text-align: right; width: 100px;">Revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customTopProducts->take(5) as $item)
            <tr>
                <td>{{ $item->product?->name ?? $item->item_label ?? 'Deleted Product' }}</td>
                <td style="text-align: right;">{{ $item->total_qty ?? 0 }}</td>
                <td style="text-align: right;">Rs. {{ number_format($item->total_revenue ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
</div>
@endif

@if($reportTab === 'stock')
<!-- Stock & Revenue Report -->
<div class="section">
<div class="section-title">Stock &amp; Product Revenue Report — {{ date('F d, Y') }}</div>

<div class="stats">
    <div class="stat-row">
        <div class="stat-box">
            <div class="stat-label">Total Products</div>
            <div class="stat-value">{{ $stockProductCount ?? 0 }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Inventory Value</div>
            <div class="stat-value">Rs. {{ number_format($stockTotalValue ?? 0, 0) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">All-Time Revenue</div>
            <div class="stat-value">Rs. {{ number_format($stockAllTimeRevenue ?? 0, 0) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Out of Stock</div>
            <div class="stat-value" style="color:#ef4444;">{{ $stockOutCount ?? 0 }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Low Stock</div>
            <div class="stat-value" style="color:#d97706;">{{ $stockLowCount ?? 0 }}</div>
        </div>
    </div>
</div>

@if(isset($stockProducts) && $stockProducts->count() > 0)
<div class="products-section">
    <h3>All Products — Stock &amp; Revenue</h3>
    <table>
        <thead>
            <tr>
                <th style="width:22px;">#</th>
                <th>Product</th>
                <th>SKU</th>
                <th>Category</th>
                <th style="text-align:right;width:65px;">Price</th>
                <th style="text-align:center;width:45px;">Stock</th>
                <th style="text-align:right;width:80px;">Inv. Value</th>
                <th style="text-align:center;width:50px;">Sold</th>
                <th style="text-align:right;width:80px;">Revenue</th>
                <th style="text-align:center;width:50px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stockProducts as $i => $p)
            @php
                if ($p->stock <= 0)     { $stLabel = 'OUT';  $stColor = '#ef4444'; }
                elseif ($p->stock <= 5) { $stLabel = 'LOW';  $stColor = '#d97706'; }
                else                    { $stLabel = 'OK';   $stColor = '#10b981'; }
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $p->name }}</td>
                <td>{{ $p->sku ?? '—' }}</td>
                <td>{{ $p->category ?? '—' }}</td>
                <td style="text-align:right;">Rs. {{ number_format($p->price, 2) }}</td>
                <td style="text-align:center;font-weight:bold;color:{{ $stColor }};">{{ $p->stock }}</td>
                <td style="text-align:right;">Rs. {{ number_format($p->stock_value, 2) }}</td>
                <td style="text-align:center;">{{ $p->total_sold > 0 ? $p->total_sold : '—' }}</td>
                <td style="text-align:right;font-weight:bold;">{{ $p->total_revenue > 0 ? 'Rs. ' . number_format($p->total_revenue, 2) : '—' }}</td>
                <td style="text-align:center;color:{{ $stColor }};font-weight:bold;font-size:9px;">{{ $stLabel }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;background:#f0f0f0;">
                <td colspan="4" style="text-align:right;">TOTALS</td>
                <td></td>
                <td style="text-align:center;">{{ $stockProducts->sum('stock') }}</td>
                <td style="text-align:right;">Rs. {{ number_format($stockTotalValue ?? 0, 2) }}</td>
                <td style="text-align:center;">{{ number_format($stockProducts->sum('total_sold')) }}</td>
                <td style="text-align:right;color:#F5A623;">Rs. {{ number_format($stockAllTimeRevenue ?? 0, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
@endif
</div>
@endif

<div class="footer">
    <p>Generated on {{ date('F d, Y \a\t g:i A') }} | B Classy Lifestyle POS System</p>
</div>

</div>

</body>
</html>
