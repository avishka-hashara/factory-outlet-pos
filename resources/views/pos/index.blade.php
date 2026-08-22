{{-- ============================================
POS Billing Page - Lumen Terminal Modern Layout
Matches DESIGN.md + Stitch Terminal Mockup
============================================ --}}
@extends('layouts.pos-billing')
@section('title', 'POS Billing')

{{-- ============ MAIN CONTENT (CENTER PRODUCT PANEL + RIGHT CART PANEL) ============ --}}
@section('content')

    <!-- ===== CENTER PANEL (Product Catalogue / Search Tile Grid) ===== -->
    <div id="pos-center-panel">
        
        <!-- Category & Source Filter Header (Single Consolidated Row) -->
        <div id="catalogue-header">
            <div class="catalogue-filter-bar">
                <!-- Source filter buttons (Segmented Control) -->
                <div class="source-segmented-control">
                    <button class="source-toggle-btn active" id="btn-local-only" onclick="setSourceFilter('local', this)">
                        <i class="fas fa-store me-1"></i>Local Only
                    </button>
                    <button class="source-toggle-btn" id="btn-all-products" onclick="setSourceFilter('all', this)">
                        <i class="fas fa-globe me-1"></i>All Products
                    </button>
                </div>

                <!-- Category Chips (Horizontally Scrollable) -->
                <div class="cat-chips-scroll-area">
                    <!-- Local Category Chips -->
                    <div id="local-category-pills-wrap" class="cat-pills">
                        <button class="cat-pill active" data-category="" onclick="selectCategory(this)">All</button>
                        @foreach($localCategories as $cat)
                            <button class="cat-pill" data-category="{{ $cat }}" onclick="selectCategory(this)">{{ $cat }}</button>
                        @endforeach
                    </div>

                    <!-- Shopify Collection Chips -->
                    <div id="shopify-collection-pills-wrap" class="cat-pills" style="display:none;">
                        <button class="cat-pill active" data-collection="" onclick="selectCollection(this)">All</button>
                        @foreach($collections as $col)
                            <button class="cat-pill" data-collection="{{ $col->id }}" onclick="selectCollection(this)">{{ $col->title }}</button>
                        @endforeach
                    </div>
                </div>

                <!-- Filter Search Input (Right Aligned) -->
                <div class="catalogue-search-box ms-auto">
                    <i class="fas fa-search"></i>
                    <input type="text" id="catalogue-search" placeholder="Filter..." oninput="filterProducts()">
                </div>
            </div>
        </div>

        <!-- Product Tiles Grid Container -->
        <div id="catalogue-grid-wrap">
            <div id="product-grid">
                @forelse($products as $product)
                    @php
                        $variantData = $product->variants->map(function($v) use ($product) {
                            return [
                                'id'        => $v->id,
                                'color'     => $v->color,
                                'color_code'=> $v->color_code,
                                'size'      => $v->size,
                                'price'     => $v->price ?? $product->price,
                                'stock'     => $v->stock,
                                'sku'       => $v->sku,
                                'image_url' => $v->image_url,
                            ];
                        })->values()->toArray();

                        $firstVariant = $product->variants->first();
                        $metaLabel = $firstVariant 
                            ? trim(($firstVariant->color ? $firstVariant->color : '') . ($firstVariant->size ? ' / ' . $firstVariant->size : ''), ' / ')
                            : ($product->category ?? 'Standard');
                        $skuLabel = $product->sku ?? ($firstVariant->sku ?? 'N/A');

                        $isLowStock = $product->stock > 0 && $product->stock <= 5;
                        $isOutOfStock = $product->stock <= 0;
                    @endphp
                    <div class="product-item"
                        data-name="{{ strtolower($product->name) }}"
                        data-sku="{{ strtolower($product->sku ?? '') }}"
                        data-collections="{{ $product->collections->pluck('id')->implode(',') }}"
                        data-category="{{ strtolower($product->category ?? '') }}"
                        data-source="{{ $product->shopify_id ? 'shopify' : 'local' }}">

                        <div class="prod-card{{ $isOutOfStock ? ' out-of-stock' : '' }}"
                            data-id="{{ $product->id }}"
                            data-name="{{ $product->name }}"
                            data-price="{{ $product->price }}"
                            data-stock="{{ $product->stock }}"
                            data-sku="{{ $skuLabel }}"
                            data-image="{{ $product->image_url ?? '' }}"
                            data-variants='@json($variantData)'
                            onclick="handleProductClick(this)">
                            
                            <div class="prod-img">
                                @if($isOutOfStock)
                                    <span class="prod-stock-badge out-of-stock">OUT OF STOCK</span>
                                @elseif($isLowStock)
                                    <span class="prod-stock-badge low-stock">{{ $product->stock }} IN STOCK</span>
                                @else
                                    <span class="prod-stock-badge">{{ $product->stock }} IN STOCK</span>
                                @endif
                                
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                                @else
                                    <i class="fas fa-tshirt"></i>
                                @endif
                            </div>
                            <div class="prod-info">
                                <div>
                                    <div class="prod-name" title="{{ $product->name }}">{{ $product->name }}</div>
                                    <div class="prod-sku">{{ $metaLabel }} • SKU: {{ $skuLabel }}</div>
                                </div>
                                <div class="prod-price-tag">Rs. {{ number_format($product->price, 2) }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    @php
                        $sampleProducts = [
                            ['id'=>1,'name'=>'Classic Cotton Crewneck Tee - Black','sku'=>'TSH-BLK-M','meta'=>'M / Black','price'=>2450,'stock'=>25,'category'=>'Men'],
                            ['id'=>2,'name'=>'Slim Fit Chino Trousers - Navy','sku'=>'CHN-NVY-32','meta'=>'32 / Navy','price'=>4850,'stock'=>15,'category'=>'Men'],
                            ['id'=>3,'name'=>'Oxford Long Sleeve Shirt - White','sku'=>'SHT-WHT-L','meta'=>'L / White','price'=>5200,'stock'=>18,'category'=>'Men'],
                            ['id'=>4,'name'=>'Oversized Heavyweight Hoodie - Grey','sku'=>'HD-GRY-XL','meta'=>'XL / Grey','price'=>6500,'stock'=>12,'category'=>'Unisex'],
                            ['id'=>5,'name'=>'Vintage Wash Denim Jacket - Blue','sku'=>'JKT-BLU-L','meta'=>'L / Indigo','price'=>8900,'stock'=>8,'category'=>'Outerwear'],
                            ['id'=>6,'name'=>'Relaxed Linen Blend Shorts - Beige','sku'=>'SHT-BEI-M','meta'=>'M / Beige','price'=>3200,'stock'=>20,'category'=>'Bottoms'],
                            ['id'=>7,'name'=>'Floral Summer Wrap Dress - Red','sku'=>'DRS-RED-S','meta'=>'S / Red','price'=>5900,'stock'=>10,'category'=>'Women'],
                            ['id'=>8,'name'=>'Premium Pique Polo Shirt - Emerald','sku'=>'POLO-GRN-L','meta'=>'L / Emerald','price'=>3800,'stock'=>14,'category'=>'Men'],
                            ['id'=>9,'name'=>'High-Rise Straight Jeans - Light Wash','sku'=>'JNS-LGT-28','meta'=>'28 / Light Wash','price'=>6200,'stock'=>11,'category'=>'Women'],
                            ['id'=>10,'name'=>'Kids Graphic Print Tee - Yellow','sku'=>'KID-YEL-06','meta'=>'6Y / Yellow','price'=>1850,'stock'=>22,'category'=>'Kids'],
                            ['id'=>11,'name'=>'Ribbed Knit Crop Top - Olive','sku'=>'TOP-OLV-M','meta'=>'M / Olive','price'=>2900,'stock'=>16,'category'=>'Women'],
                            ['id'=>12,'name'=>'Classic Leather Belt - Brown','sku'=>'ACC-BLT-BRN','meta'=>'OS / Brown','price'=>2200,'stock'=>30,'category'=>'Accessories'],
                        ];
                    @endphp
                    @foreach($sampleProducts as $p)
                        <div class="product-item" data-name="{{ strtolower($p['name']) }}"
                            data-sku="{{ strtolower($p['sku']) }}" data-collections="" data-source="local" data-category="{{ $p['category'] }}">
                            <div class="prod-card" data-id="{{ $p['id'] }}" data-name="{{ $p['name'] }}"
                                data-price="{{ $p['price'] }}" data-stock="{{ $p['stock'] }}" data-sku="{{ $p['sku'] }}"
                                data-variants='[]' onclick="handleProductClick(this)">
                                <div class="prod-img">
                                    <span class="prod-stock-badge">{{ $p['stock'] }} IN STOCK</span>
                                    <i class="fas fa-tshirt"></i>
                                </div>
                                <div class="prod-info">
                                    <div>
                                        <div class="prod-name" title="{{ $p['name'] }}">{{ $p['name'] }}</div>
                                        <div class="prod-sku">{{ $p['meta'] }} • SKU: {{ $p['sku'] }}</div>
                                    </div>
                                    <div class="prod-price-tag">Rs. {{ number_format($p['price'], 2) }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endforelse

                <div id="no-results" style="display:none;">
                    <i class="fas fa-search" style="font-size:1.8rem;margin-bottom:8px;color:var(--color-outline);"></i>
                    <div>No products found</div>
                </div>
            </div>
        </div>

        <!-- Center Panel Bottom Pagination Bar -->
        <div id="catalogue-pagination">
            <button class="page-btn" title="Previous Page"><i class="fas fa-chevron-left"></i></button>
            <span>PAGE 1 OF 1</span>
            <button class="page-btn" title="Next Page"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>

    <!-- ===== RIGHT CART PANEL (~38% Width) ===== -->
    <div id="pos-cart-panel">

        <!-- Cart Panel Header: Current Bill # + Scanned Count + Delete All -->
        <div id="cart-panel-header">
            <div class="current-bill-info">
                <h4 class="current-bill-title">
                    Current Bill #<span id="pos-bill-number">{{ $nextBillNo ?? '1042' }}</span>
                </h4>
                <div class="current-bill-subtitle">
                    <span id="cart-count">0</span> Items Scanned
                </div>
                <div id="pos-customer-tag" class="customer-badge-tag" style="display:none;">
                    <i class="fas fa-user-check"></i>
                    <span id="pos-customer-name">Customer</span>
                    <button type="button" onclick="clearCustomer(event)" title="Remove Customer">&times;</button>
                </div>
            </div>
            
            <button id="btn-clear-cart-header" onclick="clearCart()" title="Clear / Void Cart">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>

        <!-- Cart Line-Items List -->
        <div id="billing-table-wrap">
            <div id="cart-items">
                <div class="empty-cart-state" id="empty-cart-row">
                    <i class="fas fa-shopping-basket"></i>
                    <div class="empty-title">Cart is empty</div>
                    <div class="empty-sub">Search and add products to start billing</div>
                </div>
            </div>
        </div>

        <!-- Totals Summary Breakdown Section -->
        <div id="billing-summary-section">
            <div class="summary-item-row">
                <span class="summary-lbl">Subtotal</span>
                <span class="summary-val" id="subtotal">0.00</span>
            </div>
            
            <div class="summary-item-row">
                <span class="summary-lbl">Item Discount</span>
                <span class="summary-val text-muted" id="item-discount-display">-0.00</span>
            </div>

            <div class="summary-item-row clickable-row" onclick="openDiscountModal()" title="Click to edit Bill Discount">
                <span class="summary-lbl">
                    Bill Discount <i class="fas fa-edit" style="font-size:0.7rem;color:#f5a524;opacity:0.7;margin-left:4px;"></i>
                </span>
                <span class="summary-val text-warning" id="bill-discount-display">-0.00</span>
            </div>

            <div class="summary-item-row" id="shipping-tax-row">
                <span class="summary-lbl">GST/Tax (10%)</span>
                <span class="summary-val" id="shipping-tax-display">0.00</span>
            </div>

            <!-- Applied Gift Card Redemption -->
            <div class="summary-item-row text-warning" id="giftcard-redeem-row" style="display:none;">
                <span class="summary-lbl">
                    <i class="fas fa-gift me-1"></i> Gift Card <span id="gc-row-number" style="font-weight:600;"></span> (-):
                </span>
                <span class="summary-val">
                    -<span id="gc-row-amount">0.00</span>
                    <button onclick="removeGiftCardRedemption()" class="btn-gc-del" title="Remove Gift Card">&times;</button>
                </span>
            </div>
        </div>

        <!-- Hidden Inputs for backwards-compatibility -->
        <div style="display:none;">
            <select id="discount-type">
                <option value="">None</option>
                <option value="percentage">%</option>
                <option value="fixed">Rs.</option>
            </select>
            <input type="number" id="discount-value" value="0">
            <input type="number" id="shipping-value" value="0">
            <textarea id="customer-notes"></textarea>
            <span id="discount-amount-text">0</span>
            <div id="discount-display"></div>
            <span id="pos-customer-sub"></span>
            <button id="checkout-btn" disabled onclick="checkout()"></button>
            <button id="pay-btn-giftcard" disabled onclick="openGiftCardRedeemModal()"></button>
            <button class="paybar-btn" id="pay-btn-multipay" onclick="selectPaymentBar('multipay', this)"></button>
            <button class="paybar-btn" id="pay-btn-koko" onclick="selectPaymentBar('koko', this)"></button>
            <button class="paybar-btn" id="pay-btn-mintpay" onclick="selectPaymentBar('mintpay', this)"></button>
            <button class="paybar-btn" id="pay-btn-payzy" onclick="selectPaymentBar('payzy', this)"></button>
            <button class="paybar-btn" id="pay-btn-bank" onclick="selectPaymentBar('bank', this)"></button>
        </div>

        <!-- Grand Total Row -->
        <div id="cart-grand-total-wrap">
            <span class="grand-total-label">GRAND TOTAL</span>
            <span class="grand-total-amount" id="total">0.00</span>
        </div>

        <!-- 4 Action Buttons Bar (HOLD, DISCOUNT, CUSTOMER, VOID) -->
        <div class="pos-action-grid">
            <button type="button" class="action-grid-btn" id="btn-action-hold" onclick="handleHoldOrder()" title="Hold / Park Order">
                <i class="far fa-pause-circle"></i>
                <span>HOLD</span>
                <span class="held-badge" id="held-orders-badge" style="display:none;">0</span>
            </button>
            <button type="button" class="action-grid-btn" id="btn-action-discount" onclick="openDiscountModal()" title="Add Bill Discount">
                <i class="fas fa-tag"></i>
                <span>DISCOUNT</span>
            </button>
            <button type="button" class="action-grid-btn" id="btn-action-customer" data-bs-toggle="modal" data-bs-target="#customerSelectModal" title="Select Customer">
                <i class="far fa-user"></i>
                <span id="btn-customer-label">CUSTOMER</span>
            </button>
            <button type="button" class="action-grid-btn btn-action-void" id="btn-action-void" onclick="clearCart()" title="Void Order">
                <i class="fas fa-ban"></i>
                <span>VOID</span>
            </button>
        </div>

        <!-- Bottom Payment Bar (CASH, CARD, UPI/QR) -->
        <div class="pos-bottom-paybar">
            <button type="button" class="pay-main-btn pay-btn-cash paybar-btn" id="pay-btn-cash" onclick="selectPaymentBar('cash', this)">
                <i class="fas fa-money-bill-wave"></i>
                <span>CASH</span>
            </button>
            <button type="button" class="pay-main-btn pay-btn-card paybar-btn" id="pay-btn-card" onclick="selectPaymentBar('card', this)">
                <i class="fas fa-credit-card"></i>
                <span>CARD</span>
            </button>
            <button type="button" class="pay-main-btn pay-btn-qr paybar-btn" id="pay-btn-qr" onclick="openOtherPaymentModal()">
                <i class="fas fa-qrcode"></i>
                <span>UPI / QR</span>
            </button>
        </div>
    </div>

@endsection

@section('modals')
    <!-- ===== MODALS ===== -->

    <!-- Variant Selection Modal -->
    <div class="modal fade" id="colorModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.15);">
                <div class="modal-header" style="background:linear-gradient(135deg,#1B2A4A,#2C3E6B);color:#fff;border-radius:12px 12px 0 0;padding:1rem 1.5rem;">
                    <h5 class="modal-title" style="font-size:1rem;"><i class="fas fa-palette me-2" style="color:#f5a623;"></i>Select Variant</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div id="color-modal-img" class="me-3">
                            <div style="width:60px;height:60px;background:#f3f4f6;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-tshirt" style="color:#f5a623;font-size:1.3rem;"></i>
                            </div>
                        </div>
                        <div>
                            <div id="color-modal-name" style="font-weight:700;font-size:0.9rem;color:#1f2937;"></div>
                            <div id="color-modal-sku" style="font-size:0.75rem;color:#9ca3af;"></div>
                        </div>
                    </div>
                    <div style="font-size:0.8rem;font-weight:600;color:#374151;margin-bottom:10px;">Available Options</div>
                    <div id="color-options" class="d-flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cash Payment Modal -->
    <div class="modal fade" id="cashPaymentModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.15);">
                <div class="modal-header" style="background:#fff;border-bottom:1px solid #e5e7eb;border-radius:12px 12px 0 0;padding:1rem 1.5rem;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:40px;height:40px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-money-bill-wave" style="color:#22c55e;font-size:1.2rem;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title" style="font-size:1.1rem;font-weight:700;color:#1f2937;margin:0;">Cash Payment</h5>
                            <div style="font-size:0.75rem;color:#6b7280;">Enter the cash received from the customer.</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem;">
                    <!-- Total Payable -->
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:1rem;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:1.2rem;">
                        <span style="font-weight:600;color:#374151;">Total Payable</span>
                        <span id="cash-modal-total" style="font-weight:700;font-size:1.2rem;color:#d97706;">Rs. 0.00</span>
                    </div>

                    <!-- Cash Given -->
                    <div style="margin-bottom:1.2rem;">
                        <label style="font-weight:600;color:#374151;font-size:0.9rem;margin-bottom:0.5rem;display:block;">Cash Given</label>
                        <div class="input-group" style="border-radius:8px;overflow:hidden;">
                            <span class="input-group-text" style="background:#f9fafb;border:1px solid #d1d5db;border-right:none;font-weight:600;color:#6b7280;">Rs.</span>
                            <input type="number" id="cash-modal-given" class="form-control form-control-lg" placeholder="0.00" oninput="updateCashModalBalance()" style="border:1px solid #d1d5db;border-left:none;font-weight:700;font-size:1.2rem;color:#1f2937;">
                        </div>
                        
                        <!-- Quick Cash Buttons -->
                        <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;" id="cash-quick-buttons">
                            <button onclick="setCashGiven(6000)" style="flex:1;background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:6px;font-size:0.8rem;color:#374151;font-weight:600;cursor:pointer;">6000</button>
                            <button onclick="setCashGiven(7000)" style="flex:1;background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:6px;font-size:0.8rem;color:#374151;font-weight:600;cursor:pointer;">7000</button>
                            <button onclick="setCashGiven(8000)" style="flex:1;background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:6px;font-size:0.8rem;color:#374151;font-weight:600;cursor:pointer;">8000</button>
                            <button onclick="setCashGiven(9000)" style="flex:1;background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:6px;font-size:0.8rem;color:#374151;font-weight:600;cursor:pointer;">9000</button>
                            <button onclick="setCashGiven(10000)" style="flex:1;background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:6px;font-size:0.8rem;color:#374151;font-weight:600;cursor:pointer;">10000</button>
                        </div>
                    </div>

                    <!-- Change -->
                    <div style="margin-bottom:1.5rem;">
                        <label style="font-weight:600;color:#374151;font-size:0.9rem;display:block;">Change (Balance)</label>
                        <div id="cash-modal-change" style="font-weight:700;font-size:1.5rem;color:#16a34a;">Rs. 0.00</div>
                    </div>

                    <!-- Payment Summary -->
                    <div style="background:#f9fafb;border:1px solid #f3f4f6;border-radius:8px;padding:1rem;">
                        <div style="font-weight:700;color:#374151;font-size:0.85rem;margin-bottom:0.8rem;">Payment Summary</div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.85rem;color:#4b5563;">
                            <span>Total Payable</span>
                            <span id="cash-summary-total">Rs. 0.00</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:0.8rem;font-size:0.85rem;color:#4b5563;">
                            <span>Cash Given</span>
                            <span id="cash-summary-given">Rs. 0.00</span>
                        </div>
                        <div style="height:1px;background:#e5e7eb;margin-bottom:0.8rem;"></div>
                        <div style="display:flex;justify-content:space-between;font-size:0.85rem;font-weight:700;color:#1f2937;">
                            <span>Change (Balance)</span>
                            <span id="cash-summary-change" style="color:#16a34a;">Rs. 0.00</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e5e7eb;padding:1rem 1.5rem;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="flex:1;border:1px solid #d1d5db;font-weight:600;border-radius:8px;">Cancel</button>
                    <button type="button" id="confirm-cash-btn" class="btn btn-success" onclick="confirmCashPayment()" style="flex:2;background:#16a34a;border:none;font-weight:600;border-radius:8px;" disabled>
                        <i class="fas fa-check-circle me-1"></i> Confirm Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Multiple Payment Modal -->
    <div class="modal fade" id="multiPaymentModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.15);">
                <div class="modal-header" style="background:#fff;border-bottom:1px solid #e5e7eb;border-radius:12px 12px 0 0;padding:1rem 1.5rem;">
                    <div>
                        <h5 class="modal-title" style="font-size:1.1rem;font-weight:700;color:#1f2937;margin:0;">Multiple Payment</h5>
                        <div style="font-size:0.75rem;color:#6b7280;">Split the total amount using multiple payment methods</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem;">
                    <!-- Total Payable -->
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:1rem;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:1.5rem;">
                        <span style="font-weight:600;color:#374151;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-file-invoice-dollar" style="color:#6b7280;"></i> Total Amount
                        </span>
                        <span id="multipay-modal-total" style="font-weight:700;font-size:1.1rem;color:#16a34a;">Rs. 0.00</span>
                    </div>

                    <!-- Payment Splits Container -->
                    <div style="font-weight:700;color:#374151;font-size:0.9rem;margin-bottom:0.8rem;">Add Payments</div>
                    <div id="multipay-rows-container" style="display:flex;flex-direction:column;gap:12px;margin-bottom:1rem;">
                        <!-- Rows will be dynamically injected here -->
                    </div>

                    <!-- Add Another Payment Button -->
                    <button onclick="addMultiPayRow()" style="width:100%;padding:0.8rem;background:#fff;border:1px dashed #d1d5db;border-radius:8px;color:#6b7280;font-weight:600;font-size:0.85rem;cursor:pointer;transition:background 0.2s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">
                        <i class="fas fa-plus me-1"></i> Add Another Payment
                    </button>

                    <!-- Summary Box -->
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:1rem;margin-top:1.5rem;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:0.8rem;font-size:0.9rem;font-weight:600;color:#374151;">
                            <span>Total Paid</span>
                            <span id="multipay-summary-paid" style="color:#16a34a;">Rs. 0.00</span>
                        </div>
                        <div style="height:1px;background:#e5e7eb;margin-bottom:0.8rem;"></div>
                        <div style="display:flex;justify-content:space-between;font-size:0.9rem;font-weight:700;color:#1f2937;">
                            <span>Balance Due</span>
                            <span id="multipay-summary-balance" style="color:#16a34a;">Rs. 0.00</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e5e7eb;padding:1rem 1.5rem;display:flex;justify-content:center;gap:12px;flex-direction:column;">
                    <div style="display:flex;gap:12px;width:100%;">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="flex:1;border:1px solid #d1d5db;font-weight:600;border-radius:8px;">Cancel</button>
                        <button type="button" id="confirm-multipay-btn" class="btn" onclick="confirmMultiPayment()" style="flex:2;background:#f59e0b;color:#fff;border:none;font-weight:600;border-radius:8px;" disabled>
                            <i class="fas fa-check-circle me-1"></i> Confirm Payment
                        </button>
                    </div>
                    <div style="font-size:0.75rem;color:#6b7280;text-align:center;">Once confirmed, the sale will be completed.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content" style="border:none;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,0.15);">
                <div class="modal-body p-4 position-relative">
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" onclick="startNewSale()"></button>
                    
                    <div class="text-center mt-3 mb-4">
                        <div style="width:70px;height:70px;border-radius:50%;background:#dcfce7;display:inline-flex;align-items:center;justify-content:center;margin-bottom:15px;">
                            <i class="fas fa-check fa-2x" style="color:#22c55e;"></i>
                        </div>
                        <h4 style="font-weight:800;color:#1f2937;margin-bottom:4px;">Sale Complete!</h4>
                        <div style="color:#6b7280;font-size:0.9rem;">Thank you for your purchase.</div>
                    </div>

                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <i class="fas fa-file-invoice" style="color:#10b981;font-size:1.2rem;margin-bottom:5px;"></i>
                                <div style="font-weight:700;color:#1f2937;font-size:0.9rem;" id="success-sale-id">Sale #--</div>
                                <div style="font-size:0.75rem;color:#64748b;">Invoice No.</div>
                            </div>
                            <div class="col-4 border-start border-end">
                                <i class="fas fa-rupee-sign" style="color:#10b981;font-size:1.2rem;margin-bottom:5px;"></i>
                                <div style="font-weight:700;color:#1f2937;font-size:0.9rem;" id="success-sale-total">Rs. 0.00</div>
                                <div style="font-size:0.75rem;color:#64748b;">Total Amount</div>
                            </div>
                            <div class="col-4">
                                <i class="far fa-calendar-alt" style="color:#10b981;font-size:1.2rem;margin-bottom:5px;"></i>
                                <div style="font-weight:700;color:#1f2937;font-size:0.85rem;" id="success-sale-date">--</div>
                                <div style="font-size:0.75rem;color:#64748b;" id="success-sale-time">--</div>
                            </div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding-top:1rem;border-top:1px dashed #cbd5e1;">
                            <div style="font-size:0.8rem;color:#475569;display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-wallet"></i> Paid via <span id="success-sale-method" style="text-transform:uppercase;">--</span>
                            </div>
                            <div id="success-sale-method-badge" style="font-weight:700;font-size:0.75rem;color:#7c3aed;text-transform:uppercase;">--</div>
                        </div>
                    </div>

                    <div style="text-align:center;position:relative;margin-bottom:1.5rem;">
                        <hr style="border-color:#e2e8f0;margin:0;">
                        <span style="position:absolute;top:50%;left:50%;transform:translate(-50%, -50%);background:#fff;padding:0 15px;color:#64748b;font-size:0.85rem;font-weight:600;">What would you like to do?</span>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-4">
                            <button id="view-invoice-btn" onclick="printBill()" class="w-100 btn" style="background:#f59e0b;color:#fff;border-radius:8px;padding:0.75rem 0;font-weight:600;border:none;">
                                <i class="fas fa-print d-block mb-1" style="font-size:1.1rem;"></i>
                                <span style="font-size:0.85rem;">Print Bill</span>
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="w-100 btn" onclick="openSmsSection()" style="background:#3b82f6;color:#fff;border-radius:8px;padding:0.75rem 0;font-weight:600;border:none;">
                                <i class="fas fa-comment-dots d-block mb-1" style="font-size:1.1rem;"></i>
                                <span style="font-size:0.85rem;">SMS Bill</span>
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="w-100 btn btn-outline-secondary" onclick="startNewSale()" style="border-radius:8px;padding:0.75rem 0;font-weight:600;">
                                <i class="fas fa-plus d-block mb-1" style="font-size:1.1rem;"></i>
                                <span style="font-size:0.85rem;">New Sale</span>
                            </button>
                        </div>
                    </div>

                    <!-- SMS Form Section -->
                    <div id="sms-form-section" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;">
                        <div style="font-weight:600;font-size:0.9rem;color:#334155;margin-bottom:10px;">Send Bill via SMS</div>

                        <!-- Step 1: number entry -->
                        <div id="sms-step-input">
                            <div style="font-size:0.75rem;color:#64748b;margin-bottom:10px;">Enter customer mobile number to send the bill</div>
                            <div class="d-flex gap-2 mb-2">
                                <input type="text" id="sms-phone-number" class="form-control" placeholder="07X XXX XXXX" style="border-radius:6px;font-size:0.9rem;">
                                <button class="btn btn-primary" onclick="smsShowConfirm()" style="border-radius:6px;font-weight:600;display:flex;align-items:center;gap:6px;white-space:nowrap;">
                                    <i class="fas fa-paper-plane"></i> Send SMS
                                </button>
                            </div>
                            <div id="sms-input-error" style="display:none;font-size:0.75rem;color:#dc2626;margin-bottom:8px;"></div>
                        </div>

                        <!-- Step 2: confirm masked number -->
                        <div id="sms-step-confirm" style="display:none;">
                            <div style="font-size:0.85rem;color:#334155;margin-bottom:10px;">
                                Send this bill to <span id="sms-confirm-number" style="font-weight:700;"></span>?
                            </div>
                            <div class="d-flex gap-2 mb-2">
                                <button id="sms-confirm-btn" class="btn btn-primary btn-sm" onclick="smsConfirmSend()" style="border-radius:6px;font-weight:600;">
                                    <i class="fas fa-check"></i> Confirm &amp; Send
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="smsShowStep('input')" style="border-radius:6px;">
                                    Change number
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: live status -->
                        <div id="sms-step-status" style="display:none;">
                            <div id="sms-status-content" style="font-size:0.85rem;margin-bottom:8px;"></div>
                            <div class="d-flex gap-2">
                                <button id="sms-retry-btn" class="btn btn-outline-danger btn-sm" onclick="smsConfirmSend()" style="display:none;border-radius:6px;font-weight:600;">
                                    <i class="fas fa-redo"></i> Retry
                                </button>
                                <button id="sms-change-btn" class="btn btn-outline-secondary btn-sm" onclick="smsShowStep('input')" style="display:none;border-radius:6px;">
                                    Change number
                                </button>
                            </div>
                        </div>

                        <div style="font-size:0.7rem;color:#94a3b8;display:flex;align-items:center;gap:4px;margin-top:8px;">
                            <i class="fas fa-shield-alt"></i> We respect your privacy. Your number will only be used to send this bill.
                        </div>
                    </div>

                    <div style="text-align:center;margin-top:1rem;font-size:0.75rem;color:#94a3b8;display:flex;justify-content:center;align-items:center;gap:6px;">
                        <i class="fas fa-lock"></i> This is a computer generated invoice and does not require a signature.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Add Product Modal -->
    <div class="modal fade" id="quickAddProductModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.15);">
                <div class="modal-header" style="background:#fff;border-bottom:1px solid #e5e7eb;border-radius:12px 12px 0 0;padding:1rem 1.5rem;">
                    <div>
                        <h5 class="modal-title" style="font-size:1.1rem;font-weight:700;color:#1f2937;margin:0;"><i class="fas fa-box-open text-primary me-2"></i>Quick Add Product</h5>
                        <div style="font-size:0.75rem;color:#6b7280;">Add a new product and instantly add it to cart</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem;">
                    <form id="quick-add-product-form" onsubmit="submitQuickAddProduct(event)">
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.85rem;font-weight:600;color:#374151;">Product Name <span class="text-danger">*</span></label>
                            <input type="text" id="qa-name" class="form-control" required placeholder="e.g. 18k Gold Ring">
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label" style="font-size:0.85rem;font-weight:600;color:#374151;">Price (Rs.) <span class="text-danger">*</span></label>
                                <input type="number" id="qa-price" class="form-control" required min="0" step="0.01" placeholder="0.00">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label" style="font-size:0.85rem;font-weight:600;color:#374151;">Stock <span class="text-danger">*</span></label>
                                <input type="number" id="qa-stock" class="form-control" required min="0" value="1">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label" style="font-size:0.85rem;font-weight:600;color:#374151;">SKU</label>
                                <input type="text" id="qa-sku" class="form-control" placeholder="Leave blank to auto-generate">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label" style="font-size:0.85rem;font-weight:600;color:#374151;">Category</label>
                                <input type="text" id="qa-category" class="form-control" placeholder="e.g. Rings">
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-light w-50" data-bs-dismiss="modal" style="border:1px solid #d1d5db;font-weight:600;border-radius:8px;">Cancel</button>
                            <button type="submit" id="qa-submit-btn" class="btn w-50" style="background:#2563eb;color:#fff;border:none;font-weight:600;border-radius:8px;">
                                <i class="fas fa-plus me-1"></i> Save & Add to Cart
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions Modal -->
    <div class="modal fade" id="recentTransactionsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border:none;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.15);">
                <div class="modal-header" style="background:#fff;border-bottom:1px solid #e5e7eb;border-radius:12px 12px 0 0;padding:1rem 1.5rem;">
                    <h5 class="modal-title" style="font-size:1.1rem;font-weight:700;color:#1f2937;">
                        <i class="fas fa-history" style="color:#3b82f6;margin-right:8px;"></i>Today's Transactions
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:0;background:#f9fafb;">
                    <div style="padding:1rem 1.5rem;font-size:0.85rem;color:#6b7280;background:#fff;border-bottom:1px solid #f3f4f6;">
                        All transactions made today
                    </div>
                    
                    <div style="padding:1rem 1.5rem; max-height:400px; overflow-y:auto;">
                        <table style="width:100%;border-collapse:separate;border-spacing:0 8px;">
                            <thead>
                                <tr>
                                    <th style="padding:0 12px 8px;font-size:0.7rem;font-weight:600;color:#9ca3af;text-transform:uppercase;">Bill ID</th>
                                    <th style="padding:0 12px 8px;font-size:0.7rem;font-weight:600;color:#9ca3af;text-transform:uppercase;">Time</th>
                                    <th style="padding:0 12px 8px;font-size:0.7rem;font-weight:600;color:#9ca3af;text-transform:uppercase;text-align:center;">Payment Method</th>
                                    <th style="padding:0 12px 8px;font-size:0.7rem;font-weight:600;color:#9ca3af;text-transform:uppercase;text-align:right;">Total Amount</th>
                                    <th style="padding:0 12px 8px;font-size:0.7rem;font-weight:600;color:#9ca3af;text-transform:uppercase;text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="recent-transactions-tbody">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="padding:1rem 1.5rem;background:#f8fafc;border-top:1px solid #e2e8f0;border-radius:0 0 12px 12px;display:flex;align-items:center;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:12px;background:#e2e8f0;padding:8px 16px;border-radius:8px;">
                            <i class="fas fa-receipt" style="color:#3b82f6;"></i>
                            <span style="font-size:0.85rem;font-weight:600;color:#475569;">Total Transactions</span>
                            <span id="recent-total-count" style="font-size:1rem;font-weight:700;color:#1e293b;">0</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <span style="font-size:0.85rem;font-weight:600;color:#475569;">Total Sales</span>
                            <span id="recent-total-amount" style="font-size:1.1rem;font-weight:700;color:#10b981;">Rs. 0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gift Card Redeem: Popup 1 — lookup (Scan Barcode / Enter Manually) -->
    <style>
        /* Post-lookup result: two columns on desktop/laptop (card visual left,
           status + details right) so the popup fits a ~768px-tall viewport
           without scrolling; stacks vertically on narrow screens. */
        #giftCardRedeemModal .gc-result-grid { display:flex; flex-direction:column; gap:14px; }
        @media (min-width: 992px) {
            #giftCardRedeemModal .gc-result-grid { flex-direction:row; align-items:flex-start; }
            /* Card column kept narrow enough that the modal stays under ~600px
               tall and Close/Continue never need scrolling on a 768px screen */
            #giftCardRedeemModal .gc-result-card { flex:1 1 46%; min-width:0; }
            #giftCardRedeemModal .gc-result-info { flex:1 1 54%; min-width:0; }
            #giftCardRedeemModal #gc-lookup-result { margin-top:0.9rem !important; }
        }
    </style>
    <div class="modal fade" id="giftCardRedeemModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border:none;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.15);">
                <div class="modal-header" style="background:#fff;border-bottom:1px solid #e5e7eb;border-radius:12px 12px 0 0;padding:1rem 1.5rem;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:40px;height:40px;border-radius:50%;background:#fce7f3;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-gift" style="color:#be185d;font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title" style="font-size:1.1rem;font-weight:700;color:#1f2937;margin:0;">Redeem Gift Card</h5>
                            <div style="font-size:0.75rem;color:#6b7280;">Scan or enter the card to check it and apply it to this bill.</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem;">
                    <!-- Tabs -->
                    <div style="display:flex;gap:8px;margin-bottom:1rem;">
                        <button type="button" id="gc-tab-scan" onclick="gcSwitchTab('scan')"
                            style="flex:1;padding:0.6rem;border-radius:8px;border:1px solid #be185d;background:#be185d;color:#fff;font-weight:600;font-size:0.85rem;cursor:pointer;">
                            <i class="fas fa-barcode me-1"></i> Scan Barcode
                        </button>
                        <button type="button" id="gc-tab-manual" onclick="gcSwitchTab('manual')"
                            style="flex:1;padding:0.6rem;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#374151;font-weight:600;font-size:0.85rem;cursor:pointer;">
                            <i class="fas fa-keyboard me-1"></i> Enter Manually
                        </button>
                    </div>

                    <!-- Scan pane -->
                    <div id="gc-pane-scan">
                        <div style="text-align:center;padding:0.8rem 0 0.4rem;color:#9ca3af;">
                            <i class="fas fa-barcode" style="font-size:2rem;"></i>
                            <div style="font-size:0.75rem;margin-top:4px;">Point the scanner at the card's barcode</div>
                        </div>
                        <input type="text" id="gc-scan-input" class="form-control form-control-lg" autocomplete="off"
                            placeholder="Waiting for scan..." style="text-align:center;font-weight:700;letter-spacing:1px;"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();gcLookup(this.value);}">
                    </div>

                    <!-- Manual pane -->
                    <div id="gc-pane-manual" style="display:none;">
                        <label style="font-weight:600;color:#374151;font-size:0.85rem;margin-bottom:0.4rem;display:block;">Card number or barcode</label>
                        <div class="d-flex gap-2">
                            <input type="text" id="gc-manual-input" class="form-control" autocomplete="off" placeholder="e.g. GC10000001"
                                onkeydown="if(event.key==='Enter'){event.preventDefault();gcLookup(this.value);}">
                            <button type="button" class="btn" onclick="gcLookup(document.getElementById('gc-manual-input').value)"
                                style="background:#be185d;color:#fff;font-weight:600;border-radius:8px;white-space:nowrap;">
                                <i class="fas fa-search me-1"></i> Look Up
                            </button>
                        </div>
                    </div>

                    <!-- Lookup result: card visual | status + details -->
                    <div id="gc-lookup-result" style="display:none;margin-top:1.2rem;">
                        <div class="gc-result-grid">
                            <!-- LEFT: card visual -->
                            <div class="gc-result-card">
                                <div style="position:relative;width:100%;aspect-ratio:1578/997;border-radius:12px;overflow:hidden;box-shadow:0 4px 14px rgba(0,0,0,0.25);background:url('{{ asset('images/giftcard.jpg') }}') center/cover no-repeat;">
                                    <!-- Value (left) and card number (right) share one baseline -->
                                    <div style="position:absolute;left:5%;right:5%;bottom:6%;display:flex;justify-content:space-between;align-items:baseline;gap:10px;">
                                        <div id="gc-visual-value" style="color:#e7c26a;font-weight:700;font-size:1.05rem;text-shadow:0 1px 3px rgba(0,0,0,0.6);white-space:nowrap;"></div>
                                        <div id="gc-visual-number" style="color:#e7c26a;font-weight:600;font-size:0.8rem;letter-spacing:2px;text-shadow:0 1px 3px rgba(0,0,0,0.6);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT: state banner + details -->
                            <div class="gc-result-info">
                                <div id="gc-state-banner" style="margin:0 0 0.8rem;border-radius:8px;padding:0.7rem 1rem;font-weight:600;font-size:0.85rem;"></div>

                                <div id="gc-details" style="background:#f9fafb;border:1px solid #f3f4f6;border-radius:8px;padding:0.9rem 1rem;font-size:0.85rem;color:#4b5563;">
                                    <div style="display:flex;justify-content:space-between;margin-bottom:0.4rem;"><span>Card Value</span><span id="gc-detail-value" style="font-weight:700;color:#1f2937;"></span></div>
                                    <div style="display:flex;justify-content:space-between;margin-bottom:0.4rem;"><span>Status</span><span id="gc-detail-status" style="font-weight:700;text-transform:uppercase;"></span></div>
                                    <div style="display:flex;justify-content:space-between;margin-bottom:0.4rem;"><span>Issued</span><span id="gc-detail-issued"></span></div>
                                    <div style="display:flex;justify-content:space-between;"><span>Expiry</span><span id="gc-detail-expiry"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e5e7eb;padding:1rem 1.5rem;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="flex:1;border:1px solid #d1d5db;font-weight:600;border-radius:8px;">Close</button>
                    <button type="button" id="gc-continue-btn" class="btn" onclick="openGiftCardConfirmModal()" disabled
                        style="flex:2;background:#be185d;color:#fff;border:none;font-weight:600;border-radius:8px;">
                        Continue <i class="fas fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Gift Card Redeem: Popup 2 — confirm full-value redemption -->
    <div class="modal fade" id="giftCardConfirmModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.15);">
                <div class="modal-header" style="background:#fff;border-bottom:1px solid #e5e7eb;border-radius:12px 12px 0 0;padding:1rem 1.5rem;">
                    <div>
                        <h5 class="modal-title" style="font-size:1.1rem;font-weight:700;color:#1f2937;margin:0;">Confirm Gift Card Redemption</h5>
                        <div style="font-size:0.75rem;color:#6b7280;">The full card value is applied to this bill. No partial use, no change given.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem;">
                    <div style="background:#f9fafb;border:1px solid #f3f4f6;border-radius:8px;padding:1rem;font-size:0.9rem;color:#4b5563;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;"><span>Card Number</span><span id="gc-confirm-number" style="font-weight:700;color:#1f2937;"></span></div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;"><span>Card Value</span><span id="gc-confirm-value" style="font-weight:700;color:#1f2937;"></span></div>
                        <div style="display:flex;justify-content:space-between;"><span>Status</span><span id="gc-confirm-status" style="font-weight:700;color:#15803d;text-transform:uppercase;"></span></div>
                    </div>

                    <div style="border:1px solid #e5e7eb;border-radius:8px;padding:1rem;margin-top:1rem;font-size:0.9rem;color:#4b5563;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;"><span>Current Bill Total</span><span id="gc-confirm-bill" style="font-weight:700;color:#1f2937;"></span></div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;"><span>Amount to Redeem</span><span id="gc-confirm-redeem" style="font-weight:700;color:#be185d;"></span></div>
                        <div style="height:1px;background:#e5e7eb;margin-bottom:0.5rem;"></div>
                        <div style="display:flex;justify-content:space-between;font-weight:700;color:#1f2937;"><span>Payable After Redemption</span><span id="gc-confirm-after" style="color:#16a34a;"></span></div>
                    </div>

                    <div id="gc-confirm-warning" style="margin-top:1rem;border-radius:8px;padding:0.7rem 1rem;font-size:0.8rem;"></div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e5e7eb;padding:1rem 1.5rem;">
                    <button type="button" class="btn btn-light" onclick="gcBackToLookup()" style="border:1px solid #d1d5db;font-weight:600;border-radius:8px;">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </button>
                    <button type="button" id="gc-confirm-redeem-btn" class="btn" onclick="confirmGiftCardRedemption()"
                        style="flex:1;background:#16a34a;color:#fff;border:none;font-weight:600;border-radius:8px;">
                        <i class="fas fa-check-circle me-1"></i> Confirm Redemption
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Print iframe -->
    <iframe id="print-frame" src="" style="display:none;width:0;height:0;border:none;" tabindex="-1" aria-hidden="true"></iframe>

{{-- ============ BOTTOM PAYMENT BAR (Now integrated in Right Cart Panel) ============ --}}
@section('paybar')
@endsection

<!-- Customer Selection Modal -->
<div class="modal fade" id="customerSelectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#1B2A4A,#2C3E6B);color:#fff;border-radius:12px 12px 0 0;padding:1rem 1.5rem;">
                <h5 class="modal-title" style="font-size:1rem;"><i class="fas fa-user-tag me-2" style="color:#f5a623;"></i>Select Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="customer-search-input" class="form-control border-start-0" placeholder="Search by name or phone..." oninput="searchCustomers()">
                </div>
                
                <div id="customer-search-results" style="max-height:200px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:6px;margin-bottom:15px;display:none;">
                    <!-- Results injected here -->
                </div>

                <div class="text-center mb-3">
                    <span class="text-muted small">OR</span>
                </div>

                <form id="quickAddCustomerForm" onsubmit="quickAddCustomer(event)">
                    <div class="mb-2">
                        <input type="text" id="quick-cust-name" class="form-control form-control-sm" placeholder="New Customer Name (optional)">
                    </div>
                    <div class="mb-2">
                        <input type="tel" id="quick-cust-phone" class="form-control form-control-sm" placeholder="Phone Number">
                    </div>
                    <button type="submit" class="btn btn-gold btn-sm w-100"><i class="fas fa-plus me-1"></i> Quick Add Customer</button>
                </form>
            </div>
            <div class="modal-footer" style="padding:0.75rem 1.5rem;background:#fafafa;border-radius:0 0 12px 12px;border-top:1px solid #f3f4f6;">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearCustomer()">Clear / Walk-In</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Discount Modal -->
<div class="modal fade" id="discountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border:none;border-radius:12px;background:#171a22;color:#fff;box-shadow:0 10px 40px rgba(0,0,0,0.5);border:1px solid #282f3d;">
            <div class="modal-header" style="background:#111317;border-bottom:1px solid #282f3d;padding:1rem 1.25rem;">
                <h5 class="modal-title" style="font-size:1rem;font-weight:700;color:#f8fafc;">
                    <i class="fas fa-tag me-2" style="color:#f5a524;"></i>Bill Discount
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex gap-2 mb-3">
                    <button type="button" id="modal-disc-type-pct" class="btn btn-sm flex-fill" onclick="setDiscountModalType('percentage')" style="background:#f5a524;color:#111317;font-weight:700;border:none;">Percentage (%)</button>
                    <button type="button" id="modal-disc-type-fix" class="btn btn-sm flex-fill" onclick="setDiscountModalType('fixed')" style="background:#282f3d;color:#cbd5e1;font-weight:700;border:none;">Fixed Amount (Rs.)</button>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Discount Value</label>
                    <div class="input-group">
                        <span class="input-group-text" id="modal-disc-unit" style="background:#111317;border-color:#282f3d;color:#f5a524;font-weight:700;">%</span>
                        <input type="number" id="modal-discount-input" class="form-control" placeholder="0" min="0" step="0.01" style="background:#111317;border-color:#282f3d;color:#fff;font-weight:700;font-size:1.1rem;" oninput="previewModalDiscount()">
                    </div>
                </div>

                <!-- Quick Presets for % -->
                <div id="modal-pct-presets" class="d-flex gap-1 mb-3">
                    <button type="button" class="btn btn-xs flex-fill" style="background:#282f3d;color:#f1f5f9;font-size:0.75rem;padding:4px;" onclick="applyDiscountPreset(5)">5%</button>
                    <button type="button" class="btn btn-xs flex-fill" style="background:#282f3d;color:#f1f5f9;font-size:0.75rem;padding:4px;" onclick="applyDiscountPreset(10)">10%</button>
                    <button type="button" class="btn btn-xs flex-fill" style="background:#282f3d;color:#f1f5f9;font-size:0.75rem;padding:4px;" onclick="applyDiscountPreset(15)">15%</button>
                    <button type="button" class="btn btn-xs flex-fill" style="background:#282f3d;color:#f1f5f9;font-size:0.75rem;padding:4px;" onclick="applyDiscountPreset(20)">20%</button>
                </div>

                <div class="p-2 rounded mb-3" style="background:#111317;border:1px solid #282f3d;">
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Discounted Total:</span>
                        <span id="modal-disc-preview" style="color:#f5a524;font-weight:700;">Rs. 0.00</span>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm flex-fill" onclick="removeDiscountModal()">Remove</button>
                    <button type="button" class="btn btn-warning btn-sm flex-fill" style="background:#f5a524;color:#111317;font-weight:700;border:none;" onclick="saveDiscountModal()">Apply</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Other Payments & Split Modal -->
<div class="modal fade" id="otherPaymentsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:12px;background:#171a22;color:#fff;box-shadow:0 10px 40px rgba(0,0,0,0.5);border:1px solid #282f3d;">
            <div class="modal-header" style="background:#111317;border-bottom:1px solid #282f3d;padding:1rem 1.25rem;">
                <h5 class="modal-title" style="font-size:1rem;font-weight:700;color:#f8fafc;">
                    <i class="fas fa-wallet me-2" style="color:#f5a524;"></i>Other Payment Methods
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="row g-2">
                    <div class="col-6">
                        <button type="button" class="btn w-100 p-3 text-start d-flex align-items-center gap-3" style="background:#111317;border:1px solid #282f3d;color:#fff;border-radius:8px;" onclick="chooseOtherPayMethod('multipay')">
                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(59,130,246,0.15);color:#3b82f6;display:flex;align-items:center;justify-content:center;font-size:1.1rem;"><i class="fas fa-layer-group"></i></div>
                            <div>
                                <div style="font-weight:700;font-size:0.85rem;">Split Payment</div>
                                <small style="color:#94a3b8;font-size:0.7rem;">Multiple methods</small>
                            </div>
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn w-100 p-3 text-start d-flex align-items-center gap-3" style="background:#111317;border:1px solid #282f3d;color:#fff;border-radius:8px;" onclick="chooseOtherPayMethod('koko')">
                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(139,92,246,0.15);color:#8b5cf6;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem;">K</div>
                            <div>
                                <div style="font-weight:700;font-size:0.85rem;">Koko</div>
                                <small style="color:#94a3b8;font-size:0.7rem;">Buy now pay later</small>
                            </div>
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn w-100 p-3 text-start d-flex align-items-center gap-3" style="background:#111317;border:1px solid #282f3d;color:#fff;border-radius:8px;" onclick="chooseOtherPayMethod('mintpay')">
                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(14,165,233,0.15);color:#0ea5e9;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem;">M</div>
                            <div>
                                <div style="font-weight:700;font-size:0.85rem;">Mintpay</div>
                                <small style="color:#94a3b8;font-size:0.7rem;">Installment plan</small>
                            </div>
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn w-100 p-3 text-start d-flex align-items-center gap-3" style="background:#111317;border:1px solid #282f3d;color:#fff;border-radius:8px;" onclick="chooseOtherPayMethod('bank')">
                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(100,116,139,0.15);color:#94a3b8;display:flex;align-items:center;justify-content:center;font-size:1.1rem;"><i class="fas fa-university"></i></div>
                            <div>
                                <div style="font-weight:700;font-size:0.85rem;">Bank Transfer</div>
                                <small style="color:#94a3b8;font-size:0.7rem;">Direct deposit</small>
                            </div>
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn w-100 p-3 text-start d-flex align-items-center gap-3" style="background:#111317;border:1px solid #282f3d;color:#fff;border-radius:8px;" onclick="chooseOtherPayMethod('payzy')">
                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(244,63,94,0.15);color:#f43f5e;display:flex;align-items:center;justify-content:center;font-size:1.1rem;"><i class="fas fa-bolt"></i></div>
                            <div>
                                <div style="font-weight:700;font-size:0.85rem;">Payzy</div>
                                <small style="color:#94a3b8;font-size:0.7rem;">Digital payment</small>
                            </div>
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn w-100 p-3 text-start d-flex align-items-center gap-3" style="background:#111317;border:1px solid #282f3d;color:#fff;border-radius:8px;" onclick="chooseOtherPayMethod('giftcard')">
                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(245,165,36,0.15);color:#f5a524;display:flex;align-items:center;justify-content:center;font-size:1.1rem;"><i class="fas fa-gift"></i></div>
                            <div>
                                <div style="font-weight:700;font-size:0.85rem;">Gift Card</div>
                                <small style="color:#94a3b8;font-size:0.7rem;">Redeem voucher</small>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Held Orders Modal -->
<div class="modal fade" id="heldOrdersModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:12px;background:#171a22;color:#fff;box-shadow:0 10px 40px rgba(0,0,0,0.5);border:1px solid #282f3d;">
            <div class="modal-header" style="background:#111317;border-bottom:1px solid #282f3d;padding:1rem 1.25rem;">
                <h5 class="modal-title" style="font-size:1rem;font-weight:700;color:#f8fafc;">
                    <i class="far fa-pause-circle me-2" style="color:#f5a524;"></i>Parked / Held Orders
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div id="held-orders-list">
                    <!-- Injected in JS -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let cart = [];
    let paymentMethod = null;
    let discountType = '';
    let discountValue = 0;
    let discountAmount = 0;
    let selectedCustomerId = null;
    let lastCashGiven = 0;

    // ---- Double-submit guard ----
    // checkout() is reachable from FOUR places: the Checkout button, every
    // paybar payment method (card/koko/mintpay/payzy/bank call it directly),
    // and the Cash + MultiPay modal confirm buttons. Guarding only the Checkout
    // button left the others free to fire a second POST while the first was
    // still in flight — that is what created two sales for one purchase.
    let checkoutInFlight = false;

    // The key identifies ONE purchase attempt. It is created when the cart is
    // finalised (first checkout attempt) and reused by every retry of that same
    // cart, so the server recognises the repeat and returns the sale it already
    // made. Changing the cart or the discount starts a new purchase, so the key
    // is dropped and the next attempt gets a fresh one.
    let checkoutIdempotencyKey = null;

    function newIdempotencyKey() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        // Fallback for older POS terminal browsers without randomUUID
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : ((r & 0x3) | 0x8)).toString(16);
        });
    }

    function ensureCheckoutKey() {
        if (!checkoutIdempotencyKey) checkoutIdempotencyKey = newIdempotencyKey();
        return checkoutIdempotencyKey;
    }

    function resetCheckoutKey() {
        // Never drop the key mid-request: a retry of the in-flight purchase
        // must carry the same one.
        if (!checkoutInFlight) checkoutIdempotencyKey = null;
    }

    // Lock/unlock every control that can start a checkout, for the whole
    // duration of the request.
    function setCheckoutBusy(busy) {
        checkoutInFlight = busy;
        const btn = document.getElementById('checkout-btn');
        const confirmBtns = ['confirm-cash-btn', 'confirm-multipay-btn']
            .map(id => document.getElementById(id))
            .filter(Boolean);

        if (busy) {
            document.querySelectorAll('.paybar-btn').forEach(b => b.disabled = true);
            confirmBtns.forEach(b => b.disabled = true);
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
            document.body.style.cursor = 'progress';
        } else {
            document.querySelectorAll('.paybar-btn').forEach(b => b.disabled = false);
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Checkout';
            document.body.style.cursor = '';
            // Restores the real disabled state of Checkout + Redeem Gift Card.
            // The modal confirm buttons stay disabled until their own balance
            // check re-arms them when the modal is reopened.
            updateCheckoutBtn();
        }
    }

    // Gift card redemption staged for this bill ({id, card_number, value}).
    // The card is NOT consumed here — the atomic redeem happens server-side
    // inside the checkout transaction.
    let redeemedGiftCard = null;
    let gcLookupResult = null;
    const gcLookupUrl = '{{ route('pos.giftcard.lookup') }}';

    function giftCardDeduction() {
        return redeemedGiftCard ? redeemedGiftCard.value : 0;
    }

    let activeCollection = '';
    let activeCategory  = '';
    let activeSource = 'local';

    // ---- Search & Barcode Scanner ----
    const prodSearch = document.getElementById('product-search');
    const catSearch  = document.getElementById('catalogue-search');
    const prodSearchDropdown = document.getElementById('product-search-dropdown');
    let currentSearchIndex = -1;

    prodSearch?.addEventListener('input', (e) => {
        filterProducts();
        handleSearchDropdown();
    });
    catSearch?.addEventListener('input', filterProducts);

    prodSearchDropdown?.addEventListener('mousedown', (e) => {
        if (e.target.closest('.search-item')) {
            e.preventDefault();
        }
    });

    prodSearch?.addEventListener('keydown', (e) => {
        const dropdown = document.getElementById('product-search-dropdown');
        if (dropdown && dropdown.style.display === 'flex') {
            const items = dropdown.querySelectorAll('.search-item');
            if (items.length > 0) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (currentSearchIndex < items.length - 1) currentSearchIndex++;
                    highlightSearchItem(items);
                    return;
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (currentSearchIndex > 0) currentSearchIndex--;
                    highlightSearchItem(items);
                    return;
                } else if (e.key === 'Enter') {
                    if (currentSearchIndex > -1 && currentSearchIndex < items.length) {
                        e.preventDefault();
                        items[currentSearchIndex].click();
                        return;
                    }
                }
            }
        }
        handleBarcodeScan(e);
    });

    catSearch?.addEventListener('keydown', handleBarcodeScan);

    function highlightSearchItem(items) {
        items.forEach((item, index) => {
            if (index === currentSearchIndex) {
                item.style.background = '#f3f4f6';
                item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                item.style.background = 'transparent';
            }
        });
    }

    function handleSearchDropdown() {
        currentSearchIndex = -1;
        const query = document.getElementById('product-search').value.trim().toLowerCase();
        const dropdown = document.getElementById('product-search-dropdown');
        if (!query) {
            dropdown.style.display = 'none';
            return;
        }

        let resultsHTML = '';
        let matchCount = 0;
        
        document.querySelectorAll('.product-item').forEach(item => {
            const name  = item.dataset.name || '';
            const sku   = item.dataset.sku || '';
            if (name.includes(query) || sku.includes(query)) {
                if (matchCount < 10) {
                    const card = item.querySelector('.prod-card');
                    if (card) {
                        const pid = card.dataset.id;
                        const pname = card.dataset.name;
                        const psku = card.dataset.sku || '';
                        const pprice = parseFloat(card.dataset.price).toLocaleString('en-US',{minimumFractionDigits:0});
                        
                        resultsHTML += `
                            <div class="search-item" style="padding:10px 15px; border-bottom:1px solid #f3f4f6; cursor:pointer; display:flex; align-items:center; gap:12px; transition:background 0.1s;" 
                                    onmouseover="this.style.background='#f9fafb'; currentSearchIndex=${matchCount}; highlightSearchItem(document.querySelectorAll('#product-search-dropdown .search-item'))" 
                                    onmouseout="this.style.background='transparent'" 
                                    onclick="addFromSearch(${pid})">
                                <div style="width:32px; height:32px; border-radius:50%; background:#fdf6e3; display:flex; align-items:center; justify-content:center; margin-right:12px; flex-shrink:0;">
                                   <i class="fas fa-tshirt" style="color:#f5a623;font-size:0.8rem;"></i>
                                </div>
                                <div style="flex:1; overflow:hidden;">
                                   <div style="font-weight:700; font-size:0.85rem; color:#1f2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${pname}</div>
                                   <div style="font-size:0.7rem; color:#9ca3af;">${psku}</div>
                                </div>
                                <div style="font-weight:700; font-size:0.85rem; color:#1f2937; margin-left:10px; white-space:nowrap;">Rs. ${pprice}</div>
                            </div>
                        `;
                    }
                    matchCount++;
                }
            }
        });

        if (matchCount > 0) {
            resultsHTML += `
                <div style="padding:10px 15px; display:flex; align-items:center; justify-content:space-between; font-size:0.75rem; color:#9ca3af; background:#f9fafb;">
                    <span><i class="fas fa-search me-1"></i> View all results for "<span style="color:#d4891a">${query}</span>"</span>
                    <span>Press <kbd style="background:#e5e7eb; padding:2px 5px; border-radius:4px; font-family:inherit; color:#374151; border:none; font-weight:600;">Enter</kbd> to select</span>
                </div>
            `;
            dropdown.innerHTML = resultsHTML;
            dropdown.style.display = 'flex';
        } else {
            dropdown.innerHTML = `<div style="padding:15px; text-align:center; color:#9ca3af; font-size:0.85rem;">No products found for "${query}"</div>`;
            dropdown.style.display = 'flex';
        }
    }

    window.addFromSearch = function(pid) {
        const el = document.querySelector(`.prod-card[data-id="${pid}"]`);
        if(el) {
            el.click();
            document.getElementById('product-search').value = '';
            document.getElementById('product-search-dropdown').style.display = 'none';
        } else {
            showToast('Product not found in local catalog', 'danger');
        }
    };

    function handleBarcodeScan(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = e.target.value.trim().toLowerCase();
            if (!query) return;

            let exactMatchCard = null;
            let exactVariant = null;

            const cards = document.querySelectorAll('.prod-card');
            for (const card of cards) {
                const prodSku = (card.dataset.sku || '').toLowerCase();
                if (prodSku === query) {
                    exactMatchCard = card;
                    break;
                }
                
                const variants = JSON.parse(card.dataset.variants || '[]');
                for (const v of variants) {
                    if ((v.sku || '').toLowerCase() === query) {
                        exactMatchCard = card;
                        exactVariant = v;
                        break;
                    }
                }
                if (exactMatchCard) break;
            }

            if (!exactMatchCard) {
                const matches = Array.from(cards).filter(card => 
                    (card.dataset.name || '').toLowerCase().includes(query) || 
                    (card.dataset.sku || '').toLowerCase().includes(query)
                );
                if (matches.length === 1) {
                    exactMatchCard = matches[0];
                } else if (matches.length > 1) {
                    showToast('Multiple products found. Please click from the dropdown.', 'info');
                    return;
                }
            }

            if (exactMatchCard) {
                if (exactVariant) {
                    selectVariant(
                        parseInt(exactMatchCard.dataset.id),
                        exactVariant.id,
                        exactVariant.color,
                        exactVariant.size,
                        exactVariant.price,
                        exactVariant.stock,
                        exactVariant.sku,
                        exactVariant.image_url || exactMatchCard.dataset.image
                    );
                } else {
                    handleProductClick(exactMatchCard);
                }
                
                // Clear the input and reset filter, ready for next scan
                e.target.value = '';
                filterProducts();
                e.target.focus();
            } else {
                // Not a product — it may be a gift card: show its live status.
                gcCheckScannedCode(e.target.value.trim(), e.target);
            }
        }
    }

    // Scanning/searching any gift card anywhere shows its live status.
    async function gcCheckScannedCode(code, inputEl) {
        try {
            const resp = await fetch(gcLookupUrl + '?code=' + encodeURIComponent(code), { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();
            if (data.found) {
                inputEl.value = '';
                filterProducts();
                gcResetModal();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('giftCardRedeemModal')).show();
                gcRenderResult(data);
                return;
            }
        } catch (err) { /* fall through to the normal not-found message */ }
        showToast('No product found for barcode: ' + code, 'warning');
        inputEl.select();
    }

    // ---- Source Filter ----
    function setSourceFilter(source, btn) {
        activeSource = source;
        document.querySelectorAll('#catalogue-header .source-toggle-btn, #catalogue-header .cat-pill[id]').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        if (source === 'all') {
            document.getElementById('local-category-pills-wrap').style.display = 'none';
            document.getElementById('shopify-collection-pills-wrap').style.display = 'flex';
        } else {
            document.getElementById('local-category-pills-wrap').style.display = 'flex';
            document.getElementById('shopify-collection-pills-wrap').style.display = 'none';
        }
        filterProducts();
    }

    // ---- Local Category ----
    function selectCategory(btn) {
        activeCategory = btn.dataset.category;
        document.querySelectorAll('#local-category-pills-wrap .cat-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        filterProducts();
    }

    // ---- Shopify Collection ----
    function selectCollection(btn) {
        activeCollection = btn.dataset.collection;
        document.querySelectorAll('#shopify-collection-pills-wrap .cat-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        filterProducts();
    }

    // ---- Filter Products ----
    function filterProducts() {
        const q1 = (document.getElementById('product-search')?.value || '').toLowerCase().trim();
        const q2 = (document.getElementById('catalogue-search')?.value || '').toLowerCase().trim();
        const query = q1 || q2;
        let visible = 0;
        document.querySelectorAll('.product-item').forEach(item => {
            const name  = item.dataset.name || '';
            const sku   = item.dataset.sku || '';
            const cols  = item.dataset.collections ? item.dataset.collections.split(',') : [];
            const cat   = (item.dataset.category || '').toLowerCase();
            const src   = item.dataset.source || 'local';
            const matchSearch = !query || name.includes(query) || sku.includes(query);
            const matchSource = activeSource === 'all' || src === 'local';
            const matchFilter = activeSource === 'local'
                ? (!activeCategory || cat === activeCategory.toLowerCase())
                : (!activeCollection || cols.includes(activeCollection));
            const show = matchSearch && matchSource && matchFilter;
            item.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const noRes = document.getElementById('no-results');
        if (noRes) noRes.style.display = visible > 0 ? 'none' : 'block';
    }

    // ---- Handle Product Click (variants) ----
    function handleProductClick(el) {
        const variants = JSON.parse(el.dataset.variants || '[]');
        if (variants.length > 0) {
            showColorModal(el, variants);
        } else {
            addToCart(el);
        }
    }

    // ---- Variant Modal ----
    function showColorModal(el, variants) {
        const id    = parseInt(el.dataset.id);
        const name  = el.dataset.name;
        const sku   = el.dataset.sku;
        const image = el.dataset.image;

        document.getElementById('color-modal-name').textContent = name;
        document.getElementById('color-modal-sku').textContent  = sku;

        const imgC = document.getElementById('color-modal-img');
        if (image) {
            imgC.innerHTML = `<img src="${image}" alt="${name}" style="width:60px;height:60px;object-fit:cover;border-radius:8px;">`;
        } else {
            imgC.innerHTML = `<div style="width:60px;height:60px;background:#f3f4f6;border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-tshirt" style="color:#f5a623;font-size:1.3rem;"></i></div>`;
        }

        const container = document.getElementById('color-options');
        container.innerHTML = variants.map(v => {
            const isOut = v.stock <= 0;
            const lbl   = [v.color, v.size].filter(Boolean).join(' / ') || 'Default';
            const sc    = (v.color || '').replace(/'/g, "\\'");
            const ss    = (v.size  || '').replace(/'/g, "\\'");
            const sk    = (v.sku   || '').replace(/'/g, "\\'");
            const si    = (v.image_url || '').replace(/'/g, "\\'");
            const dot   = v.color_code
                ? `<span style="background:${v.color_code};width:20px;height:20px;border-radius:50%;display:inline-block;border:2px solid rgba(0,0,0,0.15);flex-shrink:0;"></span>`
                : `<span style="width:20px;height:20px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;background:#f0f0f0;border:1px solid #ccc;font-size:0.6rem;font-weight:700;flex-shrink:0;">${v.size ? v.size.substring(0,2) : '?'}</span>`;
            return `<div onclick="${isOut ? '' : `selectVariant(${id},${v.id},'${sc}','${ss}',${v.price},${v.stock},'${sk}','${si}')`}"
                style="cursor:${isOut ? 'not-allowed' : 'pointer'};opacity:${isOut ? '0.5' : '1'};">
                <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;border:1px solid #e5e7eb;background:${isOut ? '#f9fafb' : '#fff'};min-width:120px;transition:border-color 0.15s;" onmouseover="if(!${isOut})this.style.borderColor='#f5a623'" onmouseout="if(!${isOut})this.style.borderColor='#e5e7eb'">
                    ${dot}
                    <div>
                        <div style="font-weight:600;font-size:0.82rem;color:#1f2937;">${lbl}</div>
                        <div style="font-size:0.7rem;color:${v.stock > 5 ? '#16a34a' : v.stock > 0 ? '#d97706' : '#ef4444'};">${isOut ? 'Out of stock' : 'Stock: '+v.stock}</div>
                        <div style="font-size:0.7rem;color:#6b7280;">Rs. ${parseFloat(v.price).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
                    </div>
                </div>
            </div>`;
        }).join('');

        bootstrap.Modal.getOrCreateInstance(document.getElementById('colorModal')).show();
    }

    // ---- Select Variant and Add ----
    function selectVariant(productId, variantId, color, size, price, stock, sku, imageUrl) {
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('colorModal'));
        if (modal) modal.hide();

        const card = document.querySelector(`.prod-card[data-id="${productId}"]`);
        const productName = card ? card.dataset.name : 'Product';
        const cartKey = `${productId}_${variantId}`;
        const existing = cart.find(i => i.cartKey === cartKey);

        if (existing) {
            if (existing.quantity >= stock) { showToast('Max stock reached!', 'warning'); return; }
            existing.quantity++;
        } else {
            if (stock <= 0) { showToast('Out of stock!', 'danger'); return; }
            cart.push({ cartKey, id: productId, variantId, name: productName, color, size, price: parseFloat(price), stock, sku, quantity: 1 });
        }
        renderCart();
    }

    // ---- Add to Cart (no variants) ----
    function addToCart(el) {
        const id    = parseInt(el.dataset.id);
        const name  = el.dataset.name;
        const price = parseFloat(el.dataset.price);
        const stock = parseInt(el.dataset.stock);
        const sku   = el.dataset.sku || 'N/A';

        if (stock <= 0) { showToast('Out of stock!', 'danger'); return; }

        const cartKey = `${id}_0`;
        const existing = cart.find(i => i.cartKey === cartKey);
        if (existing) {
            if (existing.quantity >= stock) { showToast('Max stock reached!', 'warning'); return; }
            existing.quantity++;
        } else {
            cart.push({ cartKey, id, variantId: null, name, color:'', size:'', price, stock, sku, quantity: 1 });
        }
        renderCart();
    }

    // ---- Update Quantity ----
    function updateQty(cartKey, delta) {
        const item = cart.find(i => i.cartKey === cartKey);
        if (!item || item.giftCard) return;
        item.quantity += delta;
        if (item.quantity <= 0) {
            cart = cart.filter(i => i.cartKey !== cartKey);
        } else if (item.quantity > item.stock) {
            item.quantity = item.stock;
            showToast('Max stock reached!', 'warning');
        }
        renderCart();
    }

    // ---- Remove from Cart ----
    function removeFromCart(cartKey) {
        cart = cart.filter(i => i.cartKey !== cartKey);
        renderCart();
    }

    // ---- Update Item Price ----
    function updateItemPrice(cartKey, newPrice) {
        const item = cart.find(i => i.cartKey === cartKey);
        if (!item || item.giftCard) return;
        const val = parseFloat(newPrice);
        if (!isNaN(val) && val >= 0) {
            item.price = val;
        }
        renderCart();
    }

    // ---- Render Cart (Item Cards List) ----
    function renderCart() {
        // Every cart mutation (add / qty / remove / price / gift card) funnels
        // through here, so this is where a changed cart becomes a NEW purchase
        // and therefore needs a new idempotency key.
        resetCheckoutKey();
        const tbody = document.getElementById('cart-items');

        if (cart.length === 0) {
            tbody.innerHTML = `<div class="empty-cart-state" id="empty-cart-row">
                <i class="fas fa-shopping-basket"></i>
                <div class="empty-title">Cart is empty</div>
                <div class="empty-sub">Search and add products to start billing</div>
            </div>`;
        } else {
            tbody.innerHTML = cart.map((item, idx) => {
                const vbadge = [item.color, item.size].filter(Boolean).join(' / ');
                const ek = item.cartKey.replace(/'/g, "\\'");
                if (item.giftCard) {
                    return `<div class="cart-item-card">
                        <div class="cart-item-left">
                            <div class="cart-item-name"><i class="fas fa-gift text-warning me-1"></i>${item.name}</div>
                            <div class="cart-item-meta">Card No: ${item.cardNumber} • <span style="color:#f5a524;font-weight:700;">GIFT CARD</span></div>
                            <div class="cart-item-unit-price">@ Rs. ${(item.price).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
                        </div>
                        <div class="cart-item-right">
                            <div class="cart-stepper"><span style="min-width:32px;">1</span></div>
                            <div class="cart-item-subtotal">Rs. ${(item.price).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
                            <button type="button" class="cart-item-remove-btn" onclick="removeFromCart('${ek}')" title="Remove gift card"><i class="fas fa-times"></i></button>
                        </div>
                    </div>`;
                }
                return `<div class="cart-item-card">
                    <div class="cart-item-left">
                        <div class="cart-item-name" title="${item.name}">${item.name}</div>
                        <div class="cart-item-meta">${vbadge ? vbadge + ' • ' : ''}SKU: ${item.sku}</div>
                        <div class="cart-item-unit-price">
                            @ Rs. 
                            <input type="number" value="${item.price}" min="0" step="0.01" 
                                onchange="updateItemPrice('${ek}', this.value)" 
                                style="width: 75px; border: 1px solid transparent; background: transparent; color: #f5a524; font-size: 0.76rem; font-weight:700; outline: none; padding: 1px 4px; border-radius: 4px; transition: all 0.15s;"
                                onfocus="this.style.borderColor='#f5a524'; this.style.background='#171a22';"
                                onblur="this.style.borderColor='transparent'; this.style.background='transparent';"
                                title="Click to edit unit price"
                            >
                        </div>
                    </div>
                    <div class="cart-item-right">
                        <div class="cart-stepper">
                            <button type="button" onclick="updateQty('${ek}',-1)"><i class="fas fa-minus"></i></button>
                            <span>${item.quantity}</span>
                            <button type="button" onclick="updateQty('${ek}',1)"><i class="fas fa-plus"></i></button>
                        </div>
                        <div class="cart-item-subtotal">Rs. ${(item.price*item.quantity).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
                        <button type="button" class="cart-item-remove-btn" onclick="removeFromCart('${ek}')" title="Remove item"><i class="fas fa-times"></i></button>
                    </div>
                </div>`;
            }).join('');
        }

        const sub = cart.reduce((s, i) => s + i.price * i.quantity, 0);
        document.getElementById('subtotal').textContent = 'Rs. ' + sub.toLocaleString('en-US', {minimumFractionDigits:2});
        document.getElementById('cart-count').textContent = cart.reduce((s, i) => s + i.quantity, 0);
        applyDiscount();
        updateCheckoutBtn();
    }

    // ---- Payment Selection ----
    function selectPaymentBar(method, el) {
        // Clicking a second payment method (or the same one twice) while a
        // checkout is in flight must not start another one.
        if (checkoutInFlight) return;
        if (cart.length === 0) {
            showToast('Cart is empty! Add products first.', 'warning');
            return;
        }
        paymentMethod = method;
        document.querySelectorAll('.paybar-btn[id^="pay-btn-"]').forEach(b => b.classList.remove('active-pay'));
        if(el) el.classList.add('active-pay');

        if (method === 'cash') {
            const sub = cart.reduce((s, i) => s + i.price * i.quantity, 0);
            const shipping = parseFloat(document.getElementById('shipping-value')?.value) || 0;
            const total = Math.max(0, sub - discountAmount + shipping - giftCardDeduction());

            document.getElementById('cash-modal-total').textContent = 'Rs. ' + total.toLocaleString('en-US',{minimumFractionDigits:2});
            document.getElementById('cash-summary-total').textContent = 'Rs. ' + total.toLocaleString('en-US',{minimumFractionDigits:2});
            document.getElementById('cash-modal-given').value = '';
            lastCashGiven = 0;
            updateCashModalBalance();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('cashPaymentModal')).show();
        } else if (method === 'multipay') {
            const sub = cart.reduce((s, i) => s + i.price * i.quantity, 0);
            const shipping = parseFloat(document.getElementById('shipping-value')?.value) || 0;
            const total = Math.max(0, sub - discountAmount + shipping - giftCardDeduction());

            document.getElementById('multipay-modal-total').textContent = 'Rs. ' + total.toLocaleString('en-US',{minimumFractionDigits:2});
            
            const container = document.getElementById('multipay-rows-container');
            container.innerHTML = '';
            addMultiPayRow('cash', total); // Add a default row with full amount

            updateMultiPayBalance();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('multiPaymentModal')).show();
        } else {
            checkout();
        }
    }

    // ---- Multiple Payment Logic ----
    function getPaymentMethodIcon(method) {
        const methods = {
            'cash': '<div style="width:28px;height:28px;border-radius:6px;background:#22c55e;color:#fff;display:flex;align-items:center;justify-content:center;"><i class="fas fa-money-bill-wave" style="font-size:0.75rem;"></i></div>',
            'card': '<div style="width:28px;height:28px;border-radius:6px;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;"><i class="fas fa-credit-card" style="font-size:0.75rem;"></i></div>',
            'koko': '<div style="width:28px;height:28px;border-radius:6px;background:#8b5cf6;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.75rem;font-family:sans-serif;">K</div>',
            'mintpay': '<div style="width:28px;height:28px;border-radius:6px;background:#0ea5e9;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.75rem;font-family:sans-serif;">M</div>',
            'payzy': '<div style="width:28px;height:28px;border-radius:6px;background:#f43f5e;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.75rem;font-family:sans-serif;">P</div>',
            'bank': '<div style="width:28px;height:28px;border-radius:6px;background:#64748b;color:#fff;display:flex;align-items:center;justify-content:center;"><i class="fas fa-university" style="font-size:0.75rem;"></i></div>'
        };
        return methods[method] || methods['cash'];
    }

    function addMultiPayRow(defaultMethod = 'cash', defaultAmount = '') {
        const container = document.getElementById('multipay-rows-container');
        
        if (defaultAmount === '') {
            const sub = cart.reduce((s, i) => s + i.price * i.quantity, 0);
            const shipping = parseFloat(document.getElementById('shipping-value')?.value) || 0;
            const total = Math.max(0, sub - discountAmount + shipping - giftCardDeduction());
            
            let totalPaid = 0;
            document.querySelectorAll('.mp-amount-input').forEach(input => {
                totalPaid += parseFloat(input.value) || 0;
            });
            
            const remaining = total - totalPaid;
            if (remaining > 0) {
                defaultAmount = remaining;
            }
        }

        const rowId = 'mp-row-' + Date.now();
        
        const row = document.createElement('div');
        row.id = rowId;
        row.className = 'multipay-row';
        row.style.cssText = 'display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #e5e7eb;padding:8px 12px;border-radius:8px;';
        
        row.innerHTML = `
            <div class="mp-icon-container">
                ${getPaymentMethodIcon(defaultMethod)}
            </div>
            <select class="form-select border-0 bg-transparent fw-bold mp-method-select" style="flex:1;box-shadow:none;cursor:pointer;" onchange="updateMultiPayIcon(this)">
                <option value="cash" ${defaultMethod==='cash'?'selected':''}>Cash</option>
                <option value="card" ${defaultMethod==='card'?'selected':''}>Card</option>
                <option value="koko" ${defaultMethod==='koko'?'selected':''}>KOKO</option>
                <option value="mintpay" ${defaultMethod==='mintpay'?'selected':''}>Mintpay</option>
                <option value="payzy" ${defaultMethod==='payzy'?'selected':''}>Payzy</option>
                <option value="bank" ${defaultMethod==='bank'?'selected':''}>Bank Transfer</option>
            </select>
            <div class="input-group" style="width:140px;border-radius:6px;overflow:hidden;border:1px solid #d1d5db;">
                <span class="input-group-text bg-light border-0" style="font-size:0.8rem;color:#6b7280;padding-right:4px;">Rs.</span>
                <input type="number" class="form-control border-0 mp-amount-input" value="${defaultAmount}" placeholder="0.00" oninput="updateMultiPayBalance()" style="font-weight:700;font-size:0.9rem;padding-left:4px;box-shadow:none;">
            </div>
            <button class="btn btn-link text-danger p-0" onclick="removeMultiPayRow('${rowId}')" style="text-decoration:none;"><i class="far fa-trash-alt"></i></button>
        `;
        
        container.appendChild(row);
        updateMultiPayBalance();
    }

    window.updateMultiPayIcon = function(selectEl) {
        const iconContainer = selectEl.previousElementSibling;
        iconContainer.innerHTML = getPaymentMethodIcon(selectEl.value);
    };

    window.removeMultiPayRow = function(rowId) {
        const row = document.getElementById(rowId);
        if (row) {
            row.remove();
            updateMultiPayBalance();
        }
    };

    window.updateMultiPayBalance = function() {
        const sub = cart.reduce((s, i) => s + i.price * i.quantity, 0);
        const shipping = parseFloat(document.getElementById('shipping-value')?.value) || 0;
        const total = Math.max(0, sub - discountAmount + shipping - giftCardDeduction());

        let totalPaid = 0;
        document.querySelectorAll('.mp-amount-input').forEach(input => {
            totalPaid += parseFloat(input.value) || 0;
        });

        document.getElementById('multipay-summary-paid').textContent = 'Rs. ' + totalPaid.toLocaleString('en-US',{minimumFractionDigits:2});

        const change = totalPaid - total;
        const confirmBtn = document.getElementById('confirm-multipay-btn');
        const balanceEl = document.getElementById('multipay-summary-balance');

        if (totalPaid >= total) {
            if (change > 0) {
                balanceEl.textContent = 'Change: Rs. ' + change.toLocaleString('en-US',{minimumFractionDigits:2});
            } else {
                balanceEl.textContent = 'Rs. 0.00';
            }
            balanceEl.style.color = '#16a34a';
            confirmBtn.disabled = false;
        } else {
            balanceEl.textContent = 'Rs. ' + Math.abs(change).toLocaleString('en-US',{minimumFractionDigits:2});
            balanceEl.style.color = '#ef4444';
            confirmBtn.disabled = true;
        }
    }

    window.confirmMultiPayment = function() {
        if (checkoutInFlight) return;
        document.getElementById('confirm-multipay-btn').disabled = true;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('multiPaymentModal')).hide();
        checkout();
    };

    function updateCashModalBalance() {
        const sub = cart.reduce((s, i) => s + i.price * i.quantity, 0);
        const shipping = parseFloat(document.getElementById('shipping-value')?.value) || 0;
        const total = Math.max(0, sub - discountAmount + shipping - giftCardDeduction());
        const given = parseFloat(document.getElementById('cash-modal-given').value) || 0;
        lastCashGiven = given;
        
        document.getElementById('cash-summary-given').textContent = 'Rs. ' + given.toLocaleString('en-US',{minimumFractionDigits:2});

        const change = given - total;
        const confirmBtn = document.getElementById('confirm-cash-btn');
        
        if (given > 0 && given >= total) {
            document.getElementById('cash-modal-change').textContent = 'Rs. ' + change.toLocaleString('en-US',{minimumFractionDigits:2});
            document.getElementById('cash-modal-change').style.color = '#16a34a';
            document.getElementById('cash-summary-change').textContent = 'Rs. ' + change.toLocaleString('en-US',{minimumFractionDigits:2});
            document.getElementById('cash-summary-change').style.color = '#16a34a';
            confirmBtn.disabled = false;
        } else {
            document.getElementById('cash-modal-change').textContent = 'Short: Rs. ' + Math.abs(change).toLocaleString('en-US',{minimumFractionDigits:2});
            document.getElementById('cash-modal-change').style.color = '#ef4444';
            document.getElementById('cash-summary-change').textContent = '- Rs. ' + Math.abs(change).toLocaleString('en-US',{minimumFractionDigits:2});
            document.getElementById('cash-summary-change').style.color = '#ef4444';
            confirmBtn.disabled = true;
        }
    }

    function setCashGiven(amount) {
        document.getElementById('cash-modal-given').value = amount;
        updateCashModalBalance();
    }

    function confirmCashPayment() {
        if (checkoutInFlight) return;
        document.getElementById('confirm-cash-btn').disabled = true;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('cashPaymentModal')).hide();
        checkout();
    }

    // ---- Apply Discount ----
    function applyDiscount() {
        const sub      = cart.reduce((s, i) => s + i.price * i.quantity, 0);
        const type     = document.getElementById('discount-type').value;
        const val      = parseFloat(document.getElementById('discount-value')?.value) || 0;
        const shipping = parseFloat(document.getElementById('shipping-value')?.value) || 0;
        const display  = document.getElementById('discount-display');

        // A changed discount/shipping is a different bill → different purchase.
        if (type !== discountType || val !== discountValue) resetCheckoutKey();

        discountType  = type;
        discountValue = val;

        if (!type || val <= 0) {
            discountAmount = 0;
            if (display) display.style.display = 'none';
        } else {
            if (type === 'percentage') {
                discountAmount = (sub * Math.min(val, 100)) / 100;
            } else {
                discountAmount = Math.min(val, sub);
            }
            discountAmount = Math.max(0, discountAmount);
            if (discountAmount > 0) {
                if (display) display.style.display = 'inline';
                const discText = document.getElementById('discount-amount-text');
                if (discText) {
                    discText.textContent = discountAmount.toLocaleString('en-US', {minimumFractionDigits:2}) + (type === 'percentage' ? ` (${val}%)` : '');
                }
            } else {
                if (display) display.style.display = 'none';
            }
        }

        // Summary Breakdown display updates
        const billDiscEl = document.getElementById('bill-discount-display');
        if (billDiscEl) {
            billDiscEl.textContent = discountAmount > 0 ? ('-Rs. ' + discountAmount.toLocaleString('en-US', {minimumFractionDigits:2})) : '-Rs. 0.00';
        }
        const itemDiscEl = document.getElementById('item-discount-display');
        if (itemDiscEl) {
            itemDiscEl.textContent = '-Rs. 0.00';
        }
        const shipTaxEl = document.getElementById('shipping-tax-display');
        if (shipTaxEl) {
            shipTaxEl.textContent = 'Rs. ' + shipping.toLocaleString('en-US', {minimumFractionDigits:2});
        }

        // Keep the staged gift card redemption valid: full-value redemption
        // only, so if the bill drops below the card value it is un-applied.
        const gcRow = document.getElementById('giftcard-redeem-row');
        if (redeemedGiftCard) {
            const grossTotal = Math.max(0, sub - discountAmount + shipping);
            if (grossTotal < redeemedGiftCard.value) {
                redeemedGiftCard = null;
                if (gcRow) gcRow.style.display = 'none';
                showToast('Gift card removed — the bill total fell below the card value.', 'warning');
            } else {
                document.getElementById('gc-row-number').textContent = redeemedGiftCard.card_number;
                document.getElementById('gc-row-amount').textContent = redeemedGiftCard.value.toLocaleString('en-US', {minimumFractionDigits:2});
                if (gcRow) gcRow.style.display = 'flex';
            }
        } else {
            if (gcRow) gcRow.style.display = 'none';
        }

        const total = Math.max(0, sub - discountAmount + shipping - giftCardDeduction());
        document.getElementById('total').textContent = 'Rs. ' + total.toLocaleString('en-US', {minimumFractionDigits:2});
        updateCheckoutBtn();
    }

    // ---- Discount Modal Management ----
    let modalDiscType = 'percentage';
    
    function openDiscountModal() {
        modalDiscType = discountType || 'percentage';
        setDiscountModalType(modalDiscType);
        document.getElementById('modal-discount-input').value = discountValue || '';
        previewModalDiscount();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('discountModal')).show();
    }

    function setDiscountModalType(type) {
        modalDiscType = type;
        const pctBtn = document.getElementById('modal-disc-type-pct');
        const fixBtn = document.getElementById('modal-disc-type-fix');
        const unitSpan = document.getElementById('modal-disc-unit');
        const presets = document.getElementById('modal-pct-presets');

        if (type === 'percentage') {
            pctBtn.style.background = '#f5a524';
            pctBtn.style.color = '#111317';
            fixBtn.style.background = '#282f3d';
            fixBtn.style.color = '#cbd5e1';
            unitSpan.textContent = '%';
            if (presets) presets.style.display = 'flex';
        } else {
            fixBtn.style.background = '#f5a524';
            fixBtn.style.color = '#111317';
            pctBtn.style.background = '#282f3d';
            pctBtn.style.color = '#cbd5e1';
            unitSpan.textContent = 'Rs.';
            if (presets) presets.style.display = 'none';
        }
        previewModalDiscount();
    }

    function applyDiscountPreset(pct) {
        setDiscountModalType('percentage');
        document.getElementById('modal-discount-input').value = pct;
        previewModalDiscount();
    }

    function previewModalDiscount() {
        const sub = cart.reduce((s, i) => s + i.price * i.quantity, 0);
        const val = parseFloat(document.getElementById('modal-discount-input').value) || 0;
        let disc = 0;
        if (modalDiscType === 'percentage') {
            disc = (sub * Math.min(val, 100)) / 100;
        } else {
            disc = Math.min(val, sub);
        }
        const previewTotal = Math.max(0, sub - disc);
        document.getElementById('modal-disc-preview').textContent = 'Rs. ' + previewTotal.toLocaleString('en-US', {minimumFractionDigits:2}) + ` (-Rs. ${disc.toLocaleString('en-US', {minimumFractionDigits:2})})`;
    }

    function saveDiscountModal() {
        const val = parseFloat(document.getElementById('modal-discount-input').value) || 0;
        document.getElementById('discount-type').value = val > 0 ? modalDiscType : '';
        document.getElementById('discount-value').value = val;
        applyDiscount();
        bootstrap.Modal.getInstance(document.getElementById('discountModal')).hide();
    }

    function removeDiscountModal() {
        document.getElementById('discount-type').value = '';
        document.getElementById('discount-value').value = 0;
        applyDiscount();
        bootstrap.Modal.getInstance(document.getElementById('discountModal')).hide();
    }

    // ---- Other Payments Modal ----
    function openOtherPaymentModal() {
        if (cart.length === 0) {
            showToast('Cart is empty! Add products first.', 'warning');
            return;
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('otherPaymentsModal')).show();
    }

    function chooseOtherPayMethod(method) {
        bootstrap.Modal.getInstance(document.getElementById('otherPaymentsModal')).hide();
        if (method === 'giftcard') {
            openGiftCardRedeemModal();
        } else if (method === 'multipay') {
            selectPaymentBar('multipay', document.getElementById('pay-btn-qr'));
        } else {
            selectPaymentBar(method, document.getElementById('pay-btn-qr'));
        }
    }

    // ---- Hold Orders Management ----
    let heldOrders = JSON.parse(localStorage.getItem('pos_held_orders') || '[]');

    function updateHeldOrdersBadge() {
        const badge = document.getElementById('held-orders-badge');
        if (!badge) return;
        if (heldOrders.length > 0) {
            badge.textContent = heldOrders.length;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }

    function handleHoldOrder() {
        if (cart.length === 0) {
            openHeldOrdersModal();
            return;
        }
        const orderId = document.getElementById('pos-bill-number')?.textContent || (Date.now() % 10000);
        const heldOrder = {
            id: orderId,
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            cart: JSON.parse(JSON.stringify(cart)),
            selectedCustomerId: selectedCustomerId,
            customerName: document.getElementById('pos-customer-name')?.textContent || 'Customer',
            discountType: discountType,
            discountValue: discountValue,
            total: document.getElementById('total')?.textContent || '0.00'
        };
        heldOrders.push(heldOrder);
        localStorage.setItem('pos_held_orders', JSON.stringify(heldOrders));
        updateHeldOrdersBadge();
        showToast(`Bill #${orderId} held successfully`, 'warning');
        clearCart();
    }

    function openHeldOrdersModal() {
        const list = document.getElementById('held-orders-list');
        if (heldOrders.length === 0) {
            list.innerHTML = `<div class="text-center py-4 text-muted"><i class="far fa-pause-circle fa-2x mb-2 d-block text-secondary"></i>No orders currently on hold.</div>`;
        } else {
            list.innerHTML = heldOrders.map((o, idx) => `
                <div class="p-3 mb-2 rounded d-flex align-items-center justify-content-between" style="background:#111317;border:1px solid #282f3d;">
                    <div>
                        <div class="fw-bold text-white">Bill #${o.id} <span class="badge bg-secondary ms-1">${o.time}</span></div>
                        <div class="small text-muted">${o.cart.length} items • ${o.total}</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-warning fw-bold" style="background:#f5a524;color:#111317;border:none;" onclick="resumeHeldOrder(${idx})">Resume</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteHeldOrder(${idx})"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `).join('');
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('heldOrdersModal')).show();
    }

    function resumeHeldOrder(idx) {
        const order = heldOrders.splice(idx, 1)[0];
        localStorage.setItem('pos_held_orders', JSON.stringify(heldOrders));
        updateHeldOrdersBadge();
        cart = order.cart;
        if (order.selectedCustomerId) {
            selectCustomer(order.selectedCustomerId, order.customerName);
        }
        document.getElementById('discount-type').value = order.discountType || '';
        document.getElementById('discount-value').value = order.discountValue || 0;
        renderCart();
        bootstrap.Modal.getInstance(document.getElementById('heldOrdersModal')).hide();
        showToast(`Bill #${order.id} resumed`, 'success');
    }

    function deleteHeldOrder(idx) {
        heldOrders.splice(idx, 1);
        localStorage.setItem('pos_held_orders', JSON.stringify(heldOrders));
        updateHeldOrdersBadge();
        openHeldOrdersModal();
    }

    // ---- Update Checkout Button ----
    function updateCheckoutBtn() {
        const sub      = cart.reduce((s, i) => s + i.price * i.quantity, 0);
        const shipping = parseFloat(document.getElementById('shipping-value')?.value) || 0;
        const total    = Math.max(0, sub - discountAmount + shipping - giftCardDeduction());
        // While a checkout is in flight everything stays locked — this function
        // runs from renderCart()/applyDiscount() and must never re-enable a
        // button underneath an active request.
        let disabled   = checkoutInFlight || cart.length === 0 || !paymentMethod;
        document.getElementById('checkout-btn').disabled = disabled;

        // Redeem Gift Card is only usable once the cart has at least one item
        const gcBtn = document.getElementById('pay-btn-giftcard');
        if (gcBtn) gcBtn.disabled = checkoutInFlight || cart.length === 0;
    }

    // ---- Checkout ----
    async function checkout() {
        // Re-entrancy guard: the cart is only cleared once the response comes
        // back, so without this a second click during the request would pass
        // the checks below and POST again.
        if (checkoutInFlight) return;
        if (cart.length === 0 || !paymentMethod) return;

        const idempotencyKey = ensureCheckoutKey();
        setCheckoutBusy(true);

        const sub      = cart.reduce((s, i) => s + i.price * i.quantity, 0);
        const shipping = parseFloat(document.getElementById('shipping-value')?.value) || 0;
        const total    = Math.max(0, sub - discountAmount + shipping - giftCardDeduction());

        let paymentSplits = null;
        let amountReceived = total;
        if (paymentMethod === 'multipay') {
            paymentSplits = [];
            document.querySelectorAll('.multipay-row').forEach(row => {
                const method = row.querySelector('.mp-method-select').value;
                const amount = parseFloat(row.querySelector('.mp-amount-input').value) || 0;
                if (amount > 0) {
                    paymentSplits.push({ method, amount });
                }
            });
            amountReceived = paymentSplits.reduce((sum, split) => sum + split.amount, 0);
        } else if (paymentMethod === 'cash') {
            amountReceived = lastCashGiven;
        }

        const changeAmount = Math.max(0, amountReceived - total);

        try {
            const resp = await fetch('/pos/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    // Same key on every retry of this cart: the server returns
                    // the sale it already made instead of creating a second one.
                    idempotency_key: idempotencyKey,
                    // Product lines only — a bound gift card is sent separately
                    items: cart.filter(i => !i.giftCard).map(i => ({ product_id: i.id, variant_id: i.variantId || null, quantity: i.quantity, price: i.price })),
                    gift_card_sale: (() => {
                        const gc = cart.find(i => i.giftCard);
                        return gc ? { id: gc.giftCardId, price: gc.price } : null;
                    })(),
                    gift_card_redeem: redeemedGiftCard ? { id: redeemedGiftCard.id, amount: redeemedGiftCard.value } : null,
                    payment_method: paymentMethod,
                    payment_splits: paymentSplits,
                    amount_received: amountReceived,
                    change_amount: changeAmount,
                    subtotal: sub,
                    total: total,
                    discount_type: discountType || null,
                    discount_value: discountValue,
                    discount_amount: discountAmount,
                    customer_id: selectedCustomerId,
                    notes: document.getElementById('customer-notes')?.value || '',
                }),
            });
            const data = await resp.json();
            if (data.success) {
                document.getElementById('success-sale-id').textContent = 'Sale #' + data.sale_id;
                document.getElementById('success-sale-total').textContent = 'Rs. ' + total.toLocaleString('en-US',{minimumFractionDigits:2});
                document.getElementById('success-sale-date').textContent = data.sale_date;
                document.getElementById('success-sale-time').textContent = data.sale_time;
                let methodText = paymentMethod;
                if (paymentMethod === 'multipay') methodText = 'Multiple Methods';
                document.getElementById('success-sale-method').textContent = methodText;
                document.getElementById('success-sale-method-badge').textContent = paymentMethod === 'multipay' ? 'MULTIPAY' : paymentMethod;
                
                document.getElementById('view-invoice-btn').setAttribute('data-sale-id', data.sale_id);
                document.getElementById('sms-form-section').style.display = 'none';
                window.lastCustomerPhone = data.customer_phone || '';
                
                clearCart();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('successModal')).show();
            } else {
                showToast(data.message || 'Checkout failed!', 'danger');
            }
        } catch(e) {
            // The key is deliberately KEPT here: if the sale actually committed
            // before the connection dropped, retrying with the same key returns
            // that sale instead of creating a second one.
            showToast('Network error! Please try again.', 'danger');
        } finally {
            setCheckoutBusy(false);
        }
    }

    // ---- SMS Bill (confirm → queue → live status) ----
    // The gateway key never reaches this page: the browser only calls our own
    // /api/invoices/... endpoints; all Dialog calls happen server-side.
    let smsNormalizedPhone = null;
    let smsPollTimer = null;

    // Mirrors App\Services\Sms\SriLankanPhone (server re-validates authoritatively)
    function slPhoneNormalize(raw) {
        const digits = (raw || '').replace(/\D+/g, '');
        let sub;
        if (digits.startsWith('0094')) sub = digits.slice(4);
        else if (digits.startsWith('94') && digits.length === 11) sub = digits.slice(2);
        else if (digits.startsWith('0') && digits.length === 10) sub = digits.slice(1);
        else sub = digits;
        if (sub.length !== 9) return null;
        if (!['70','71','72','74','75','76','77','78'].includes(sub.slice(0, 2))) return null;
        return '94' + sub;
    }

    function slPhoneMask(n) { return n.slice(0, 4) + '•••' + n.slice(-4); }

    function openSmsSection() {
        document.getElementById('sms-form-section').style.display = 'block';
        const input = document.getElementById('sms-phone-number');
        if (!input.value && window.lastCustomerPhone) input.value = window.lastCustomerPhone;
        smsShowStep('input');
    }

    function smsShowStep(step) {
        ['input', 'confirm', 'status'].forEach(s => {
            document.getElementById('sms-step-' + s).style.display = (s === step) ? 'block' : 'none';
        });
        if (step !== 'status' && smsPollTimer) { clearInterval(smsPollTimer); smsPollTimer = null; }
    }

    function smsShowConfirm() {
        const err = document.getElementById('sms-input-error');
        const normalized = slPhoneNormalize(document.getElementById('sms-phone-number').value);
        if (!normalized) {
            err.textContent = 'Not a valid Sri Lankan mobile number (07X XXX XXXX).';
            err.style.display = 'block';
            return;
        }
        err.style.display = 'none';
        smsNormalizedPhone = normalized;
        document.getElementById('sms-confirm-number').textContent = slPhoneMask(normalized);
        smsShowStep('confirm');
    }

    async function smsConfirmSend() {
        const saleId = document.getElementById('view-invoice-btn').getAttribute('data-sale-id');
        if (!saleId || !smsNormalizedPhone) return;

        const confirmBtn = document.getElementById('sms-confirm-btn');
        const retryBtn = document.getElementById('sms-retry-btn');
        confirmBtn.disabled = true;
        retryBtn.disabled = true;

        smsShowStep('status');
        smsRenderStatus('pending', { to_masked: slPhoneMask(smsNormalizedPhone) });

        try {
            const resp = await fetch(`/api/invoices/${saleId}/send-sms`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ phone: smsNormalizedPhone })
            });
            const data = await resp.json();

            if (resp.ok && data.success) {
                smsRenderStatus(data.status, data);
                if (data.status === 'pending') smsStartPolling(saleId);
            } else {
                document.getElementById('sms-input-error').textContent = data.message || 'Failed to queue SMS.';
                document.getElementById('sms-input-error').style.display = 'block';
                smsShowStep('input');
            }
        } catch (e) {
            smsRenderStatus('failed', { error: 'Network error — check the connection and retry.' });
        }

        confirmBtn.disabled = false;
        retryBtn.disabled = false;
    }

    // The live queue is drained by cron, so a healthy send can take ~60s.
    // Keep watching past the server's stall window (SmsOutbox::WORKER_STALL_SECONDS)
    // so a genuine failure still surfaces, and never call it failed ourselves.
    const SMS_POLL_INTERVAL_MS = 3000;
    const SMS_POLL_WINDOW_MS = 180000; // 3 minutes

    function smsStartPolling(saleId) {
        if (smsPollTimer) clearInterval(smsPollTimer);
        const deadline = Date.now() + SMS_POLL_WINDOW_MS;
        smsPollTimer = setInterval(async () => {
            try {
                const resp = await fetch(`/api/invoices/${saleId}/sms-status`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await resp.json();
                if (data.status && data.status !== 'pending') {
                    clearInterval(smsPollTimer);
                    smsPollTimer = null;
                    smsRenderStatus(data.status, data);
                    return;
                }
            } catch (e) { /* transient poll error — keep polling */ }

            // Still pending after the whole window: stop polling, but this is
            // NOT a failure — the job is queued and will keep sending.
            if (Date.now() >= deadline) {
                clearInterval(smsPollTimer);
                smsPollTimer = null;
                smsRenderStatus('queued_slow', {});
            }
        }, SMS_POLL_INTERVAL_MS);
    }

    function smsRenderStatus(status, data) {
        const el = document.getElementById('sms-status-content');
        const retryBtn = document.getElementById('sms-retry-btn');
        const changeBtn = document.getElementById('sms-change-btn');
        retryBtn.style.display = 'none';
        changeBtn.style.display = 'none';

        if (status === 'pending') {
            el.innerHTML = '<span style="color:#64748b;"><i class="fas fa-circle-notch fa-spin"></i> Sending to <b>' + (data.to_masked || '') + '</b>…</span>' +
                '<div style="font-size:0.75rem;color:#94a3b8;margin-top:4px;">This can take up to a minute — you can keep working.</div>';
        } else if (status === 'sent') {
            el.innerHTML = '<span style="color:#16a34a;font-weight:600;"><i class="fas fa-check-circle"></i> Bill sent to <b>' + (data.to_masked || '') + '</b> ✓</span>';
        } else if (status === 'failed') {
            el.innerHTML = '<span style="color:#dc2626;font-weight:600;"><i class="fas fa-times-circle"></i> Failed to send</span>' +
                (data.error ? '<div style="font-size:0.75rem;color:#991b1b;margin-top:4px;">' + data.error + '</div>' : '');
            retryBtn.style.display = 'inline-block';
            changeBtn.style.display = 'inline-block';
        } else {
            el.innerHTML = '<span style="color:#64748b;"><i class="fas fa-clock"></i> Still queued — it will keep sending in the background.</span>';
            changeBtn.style.display = 'inline-block';
        }
    }

    // ---- Clear Cart ----
    function clearCart() {
        cart = [];
        paymentMethod = null;
        lastCashGiven = 0;
        checkoutIdempotencyKey = null; // this purchase is done; next one gets its own key
        redeemedGiftCard = null;
        discountType  = '';
        discountValue = 0;
        discountAmount = 0;
        document.querySelectorAll('.paybar-btn[id^="pay-btn-"]').forEach(b => b.classList.remove('active-pay'));
        document.getElementById('customer-notes') && (document.getElementById('customer-notes').value = '');
        document.getElementById('discount-type').value  = '';
        document.getElementById('discount-value').value = '';
        if (document.getElementById('shipping-value')) document.getElementById('shipping-value').value = '';
        document.getElementById('discount-display').style.display = 'none';
        document.getElementById('total').textContent = '0';
        renderCart();
    }

    // Navigate to the clean POS URL so a ?sell_gift_card= binding is not re-added
    function startNewSale() { clearCart(); window.location.href = '{{ route('pos.index') }}'; }

    // ---- Print Bill ----
    function printBill() {
        const saleId = document.getElementById('view-invoice-btn').getAttribute('data-sale-id');
        if (!saleId) return;
        const mi = bootstrap.Modal.getOrCreateInstance(document.getElementById('successModal'));
        if (mi) mi.hide();
        const iframe = document.getElementById('print-frame');
        function onAfterPrint() {
            window.removeEventListener('afterprint', onAfterPrint);
            window.location.href = '{{ route("pos.index") }}';
        }
        iframe.onload = function() {
            try { window.addEventListener('afterprint', onAfterPrint); iframe.contentWindow.print(); }
            catch(e) { window.location.href = '/invoice/' + saleId + '?print=1'; }
            iframe.onload = null;
        };
        iframe.src = '/invoice/' + saleId;
    }

    function showRecentTransactions() {
        const tbody = document.getElementById('recent-transactions-tbody');
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#6b7280;">Loading...</td></tr>';
        
        bootstrap.Modal.getOrCreateInstance(document.getElementById('recentTransactionsModal')).show();

        fetch('/pos/recent-transactions')
            .then(res => res.json())
            .then(data => {
                document.getElementById('recent-total-count').textContent = data.count;
                document.getElementById('recent-total-amount').textContent = 'Rs. ' + parseFloat(data.total_amount).toLocaleString('en-US',{minimumFractionDigits:2});
                
                if (data.sales.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#6b7280;">No transactions found for today.</td></tr>';
                    return;
                }

                tbody.innerHTML = data.sales.map(sale => {
                    let pmIcon = 'fas fa-money-bill-wave';
                    let pmColor = '#10b981';
                    if(sale.payment_method === 'Card') { pmIcon = 'fas fa-credit-card'; pmColor = '#3b82f6'; }
                    else if(sale.payment_method === 'Koko') { pmIcon = 'fas fa-mobile-alt'; pmColor = '#8b5cf6'; }
                    else if(sale.payment_method === 'Mintpay') { pmIcon = 'fas fa-leaf'; pmColor = '#14b8a6'; }
                    else if(sale.payment_method === 'Payzy') { pmIcon = 'fas fa-bolt'; pmColor = '#3b82f6'; }
                    else if(sale.payment_method === 'Bank') { pmIcon = 'fas fa-university'; pmColor = '#1e3a8a'; }
                    
                    return `
                    <tr style="background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                        <td style="padding:12px;border-radius:8px 0 0 8px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:6px;background:#fffbeb;display:flex;align-items:center;justify-content:center;color:#f59e0b;">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <span style="font-weight:700;color:#1f2937;font-size:0.85rem;">#${sale.invoice_number}</span>
                            </div>
                        </td>
                        <td style="padding:12px;">
                            <div style="font-weight:600;color:#374151;font-size:0.85rem;">${sale.time}</div>
                            <div style="font-size:0.7rem;color:#9ca3af;">${sale.date}</div>
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <div style="display:inline-flex;align-items:center;gap:6px;font-weight:600;font-size:0.8rem;color:#4b5563;">
                                <i class="${pmIcon}" style="color:${pmColor};"></i> ${sale.payment_method}
                            </div>
                        </td>
                        <td style="padding:12px;text-align:right;font-weight:700;color:#1f2937;font-size:0.9rem;">
                            Rs. ${parseFloat(sale.total).toLocaleString('en-US',{minimumFractionDigits:2})}
                        </td>
                        <td style="padding:12px;text-align:center;border-radius:0 8px 8px 0;">
                            <button onclick="printRecentBill(${sale.id})" style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:4px 10px;font-size:0.75rem;font-weight:600;color:#3b82f6;margin-right:4px;">
                                <i class="fas fa-print"></i> Print Bill
                            </button>
                            <button onclick="window.open('/invoice/${sale.id}', '_blank')" style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:4px 10px;font-size:0.75rem;font-weight:600;color:#4b5563;">
                                <i class="fas fa-eye"></i> View Bill
                            </button>
                        </td>
                    </tr>
                    <tr><td colspan="5" style="padding:0;height:8px;"></td></tr>
                    `;
                }).join('');
            })
            .catch(err => {
                console.error(err);
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#ef4444;">Error loading transactions.</td></tr>';
            });
    }

    function printRecentBill(saleId) {
        const frame = document.getElementById('print-frame');
        frame.src = '/invoice/' + saleId + '?print=1';
        frame.onload = function() {
            try {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            } catch(e) {}
        };
    }

    // ---- Toast ----
    function showToast(message, type = 'info') {
        const colors = { info:'#3b82f6', danger:'#ef4444', warning:'#f59e0b', success:'#22c55e' };
        const el = document.createElement('div');
        el.style.cssText = `position:fixed;top:70px;right:20px;z-index:9999;min-width:240px;padding:12px 16px;border-radius:8px;background:${colors[type]||colors.info};color:#fff;font-size:0.82rem;font-weight:600;box-shadow:0 4px 12px rgba(0,0,0,0.15);animation:fadeInUp 0.3s ease;`;
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3000);
    }

    // ---- Fallback Category Sync ----
    @if($needsCategorySync)
    (function() {
        if (sessionStorage.getItem('cats_sync_ok')) return;
        const banner = document.createElement('div');
        banner.style.cssText = 'position:fixed;top:56px;left:0;right:0;z-index:1050;text-align:center;padding:5px 12px;background:#fff3cd;color:#856404;font-size:0.8rem;border-bottom:1px solid #ffc107;';
        banner.innerHTML = '<i class="fas fa-sync fa-spin me-2"></i>Finishing category setup…';
        document.body.prepend(banner);
        fetch('{{ route("shopify.fast.category.sync") }}', {
            method:'POST',
            headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}
        }).then(r => r.json()).then(data => {
            if (data.success) {
                sessionStorage.setItem('cats_sync_ok','1');
                banner.innerHTML = '<i class="fas fa-check-circle me-2"></i>Categories ready! Reloading…';
                banner.style.background = '#d1e7dd'; banner.style.color = '#0f5132';
                setTimeout(() => location.reload(), 600);
            } else {
                banner.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>'+(data.message||'Sync failed')+'. Try Sync button.';
                banner.style.background = '#f8d7da'; banner.style.color = '#842029';
            }
        }).catch(() => banner.remove());
    })();
    @endif

    // ---- Customer Selection Logic ----
    let searchCustomerTimeout = null;
    function searchCustomers() {
        clearTimeout(searchCustomerTimeout);
        const query = document.getElementById('customer-search-input').value.trim();
        const resultsDiv = document.getElementById('customer-search-results');
        
        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }

        searchCustomerTimeout = setTimeout(() => {
            fetch('/customers/search?q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(customers => {
                    if (customers.length === 0) {
                        resultsDiv.innerHTML = '<div class="p-3 text-muted text-center small">No customers found</div>';
                    } else {
                        resultsDiv.innerHTML = customers.map(c => {
                            // Name is optional — fall back to the phone for display/label.
                            const label = (c.name || c.phone || 'Customer');
                            return `
                            <div class="p-2 border-bottom d-flex justify-content-between align-items-center" style="cursor:pointer;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'" onclick="selectCustomer(${c.id}, '${label.replace(/'/g, "\\'")}')">
                                <div>
                                    <div class="fw-bold" style="font-size:0.85rem;">${c.name || '<span class=\'text-muted\'>(no name)</span>'}</div>
                                    <div class="text-muted" style="font-size:0.75rem;">${c.phone || ''}</div>
                                </div>
                                <i class="fas fa-plus-circle text-gold opacity-50"></i>
                            </div>
                        `;
                        }).join('');
                    }
                    resultsDiv.style.display = 'block';
                }).catch(e => console.error(e));
        }, 300);
    }

    function selectCustomer(id, name) {
        selectedCustomerId = id;
        const tag = document.getElementById('pos-customer-tag');
        const nameEl = document.getElementById('pos-customer-name');
        const btnLabel = document.getElementById('btn-customer-label');
        if (tag && nameEl) {
            nameEl.textContent = name;
            tag.style.display = 'inline-flex';
        }
        if (btnLabel) {
            btnLabel.textContent = name.length > 8 ? name.substring(0, 7) + '...' : name;
        }
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('customerSelectModal'));
        if (modal) modal.hide();
        
        // Reset search
        document.getElementById('customer-search-input').value = '';
        document.getElementById('customer-search-results').style.display = 'none';
    }

    function clearCustomer(e) {
        if (e && e.stopPropagation) e.stopPropagation();
        selectedCustomerId = null;
        const tag = document.getElementById('pos-customer-tag');
        const btnLabel = document.getElementById('btn-customer-label');
        if (tag) tag.style.display = 'none';
        if (btnLabel) btnLabel.textContent = 'CUSTOMER';
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('customerSelectModal'));
        if (modal) modal.hide();
    }

    function quickAddCustomer(e) {
        e.preventDefault();
        const name = document.getElementById('quick-cust-name').value;
        const phone = document.getElementById('quick-cust-phone').value;
        
        fetch('/customers', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ name: name, phone: phone })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Name is optional — show the phone (or a neutral label) when blank.
                selectCustomer(data.customer.id, data.customer.name || data.customer.phone || 'Customer');
                showToast('Customer created successfully', 'success');
                document.getElementById('quickAddCustomerForm').reset();
            } else {
                showToast(data.message || 'Error creating customer', 'danger');
            }
        }).catch(err => {
            showToast('Network error while adding customer', 'danger');
        });
    }

    async function submitQuickAddProduct(e) {
        e.preventDefault();
        const btn = document.getElementById('qa-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';

        try {
            const resp = await fetch('/pos/quick-add-product', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: document.getElementById('qa-name').value,
                    price: document.getElementById('qa-price').value,
                    stock: document.getElementById('qa-stock').value,
                    sku: document.getElementById('qa-sku').value,
                    category: document.getElementById('qa-category').value
                })
            });

            const data = await resp.json();
            if (data.success) {
                // Add to cart directly
                const prod = data.product;
                const existing = cart.find(i => i.id === prod.id && i.variantId === null);
                if (existing) {
                    existing.quantity += 1;
                } else {
                    cart.push({
                        id: prod.id,
                        variantId: null,
                        name: prod.name,
                        sku: prod.sku || 'N/A',
                        price: parseFloat(prod.price),
                        quantity: 1,
                        stock: parseInt(prod.stock),
                        image: null,
                        color: null,
                        size: null,
                        cartKey: 'prod_' + prod.id
                    });
                }
                renderCart();
                showToast('Product added successfully!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('quickAddProductModal')).hide();
                document.getElementById('quick-add-product-form').reset();
            } else {
                showToast(data.message || 'Failed to add product.', 'danger');
            }
        } catch(e) {
            showToast('Network error while adding product.', 'danger');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus me-1"></i> Save & Add to Cart';
    }

    // ==================== GIFT CARD REDEMPTION ====================

    function openGiftCardRedeemModal() {
        if (cart.length === 0) { showToast('Add items to the bill first.', 'warning'); return; }
        if (redeemedGiftCard) { showToast('A gift card is already applied to this bill.', 'info'); return; }
        gcResetModal();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('giftCardRedeemModal')).show();
    }

    document.getElementById('giftCardRedeemModal')?.addEventListener('shown.bs.modal', () => {
        const scanPane = document.getElementById('gc-pane-scan');
        if (scanPane.style.display !== 'none') document.getElementById('gc-scan-input').focus();
    });

    function gcResetModal() {
        gcLookupResult = null;
        document.getElementById('gc-scan-input').value = '';
        document.getElementById('gc-manual-input').value = '';
        document.getElementById('gc-lookup-result').style.display = 'none';
        document.getElementById('gc-continue-btn').disabled = true;
        gcSwitchTab('scan');
    }

    function gcSwitchTab(tab) {
        const scanBtn = document.getElementById('gc-tab-scan');
        const manualBtn = document.getElementById('gc-tab-manual');
        const active = 'flex:1;padding:0.6rem;border-radius:8px;border:1px solid #be185d;background:#be185d;color:#fff;font-weight:600;font-size:0.85rem;cursor:pointer;';
        const inactive = 'flex:1;padding:0.6rem;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#374151;font-weight:600;font-size:0.85rem;cursor:pointer;';
        scanBtn.style.cssText = tab === 'scan' ? active : inactive;
        manualBtn.style.cssText = tab === 'manual' ? active : inactive;
        document.getElementById('gc-pane-scan').style.display = tab === 'scan' ? 'block' : 'none';
        document.getElementById('gc-pane-manual').style.display = tab === 'manual' ? 'block' : 'none';
        const input = document.getElementById(tab === 'scan' ? 'gc-scan-input' : 'gc-manual-input');
        setTimeout(() => input.focus(), 50);
    }

    async function gcLookup(code) {
        code = (code || '').trim();
        if (!code) return;
        try {
            const resp = await fetch(gcLookupUrl + '?code=' + encodeURIComponent(code), { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();
            gcRenderResult(data);
        } catch (err) {
            showToast('Network error while looking up the card.', 'danger');
        }
    }

    function gcRenderResult(data) {
        gcLookupResult = data;
        const result = document.getElementById('gc-lookup-result');
        const banner = document.getElementById('gc-state-banner');
        const details = document.getElementById('gc-details');
        const continueBtn = document.getElementById('gc-continue-btn');
        result.style.display = 'block';

        const styles = {
            ready:      'background:#dcfce7;color:#15803d;',
            expired:    'background:#fee2e2;color:#b91c1c;',
            redeemed:   'background:#e5e7eb;color:#374151;',
            not_issued: 'background:#fef3c7;color:#92400e;',
            cancelled:  'background:#fee2e2;color:#b91c1c;',
            not_found:  'background:#fee2e2;color:#b91c1c;',
        };
        const icons = {
            ready: 'fa-check-circle', expired: 'fa-hourglass-end', redeemed: 'fa-ban',
            not_issued: 'fa-store', cancelled: 'fa-times-circle', not_found: 'fa-question-circle',
        };
        banner.style.cssText = 'margin:0 0 0.8rem;border-radius:8px;padding:0.7rem 1rem;font-weight:600;font-size:0.85rem;' + (styles[data.state] || styles.not_found);
        banner.innerHTML = `<i class="fas ${icons[data.state] || 'fa-question-circle'} me-1"></i> ` + (data.message || '');

        if (!data.found) {
            details.style.display = 'none';
            document.getElementById('gc-visual-number').textContent = '';
            document.getElementById('gc-visual-value').textContent = '';
            continueBtn.disabled = true;
            return;
        }

        const card = data.card;
        details.style.display = 'block';
        document.getElementById('gc-visual-number').textContent = card.card_number;
        document.getElementById('gc-visual-value').textContent = 'Rs. ' + parseFloat(card.value).toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('gc-detail-value').textContent = 'Rs. ' + parseFloat(card.value).toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('gc-detail-status').textContent = card.live_status;
        document.getElementById('gc-detail-status').style.color = data.state === 'ready' ? '#15803d' : '#b91c1c';
        document.getElementById('gc-detail-issued').textContent = card.issued_at || card.issued_date || '—';
        document.getElementById('gc-detail-expiry').textContent = card.expiry_date || 'Never expires';

        continueBtn.disabled = !(data.state === 'ready' && cart.length > 0 && !redeemedGiftCard);
    }

    function openGiftCardConfirmModal() {
        if (!gcLookupResult || gcLookupResult.state !== 'ready') return;
        const card = gcLookupResult.card;
        const value = parseFloat(card.value);
        const billTotal = (() => {
            const sub = cart.reduce((s, i) => s + i.price * i.quantity, 0);
            const shipping = parseFloat(document.getElementById('shipping-value')?.value) || 0;
            return Math.max(0, sub - discountAmount + shipping);
        })();
        const after = Math.max(0, billTotal - value);
        const fmt = n => 'Rs. ' + n.toLocaleString('en-US', {minimumFractionDigits: 2});

        document.getElementById('gc-confirm-number').textContent = card.card_number;
        document.getElementById('gc-confirm-value').textContent = fmt(value);
        document.getElementById('gc-confirm-status').textContent = card.live_status;
        document.getElementById('gc-confirm-bill').textContent = fmt(billTotal);
        document.getElementById('gc-confirm-redeem').textContent = '- ' + fmt(value);
        document.getElementById('gc-confirm-after').textContent = fmt(after);

        const warning = document.getElementById('gc-confirm-warning');
        const confirmBtn = document.getElementById('gc-confirm-redeem-btn');
        if (billTotal < value) {
            warning.style.cssText = 'margin-top:1rem;border-radius:8px;padding:0.7rem 1rem;font-size:0.8rem;background:#fee2e2;color:#b91c1c;';
            warning.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> The bill total is LESS than the card value. Gift cards cannot be used on smaller bills — no change or balance is given.';
            confirmBtn.disabled = true;
        } else {
            warning.style.cssText = 'margin-top:1rem;border-radius:8px;padding:0.7rem 1rem;font-size:0.8rem;background:#fef3c7;color:#92400e;';
            warning.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> After redemption this card is fully used and cannot be redeemed again. The redemption is finalised when checkout completes.';
            confirmBtn.disabled = false;
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('giftCardRedeemModal')).hide();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('giftCardConfirmModal')).show();
    }

    function gcBackToLookup() {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('giftCardConfirmModal')).hide();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('giftCardRedeemModal')).show();
    }

    function confirmGiftCardRedemption() {
        if (!gcLookupResult || gcLookupResult.state !== 'ready') return;
        const card = gcLookupResult.card;
        redeemedGiftCard = {
            id: card.id,
            card_number: card.card_number,
            value: parseFloat(card.value),
        };
        bootstrap.Modal.getOrCreateInstance(document.getElementById('giftCardConfirmModal')).hide();
        applyDiscount();
        showToast('Gift card ' + card.card_number + ' applied — it will be redeemed when checkout completes.', 'success');
    }

    function removeGiftCardRedemption() {
        redeemedGiftCard = null;
        applyDiscount();
        showToast('Gift card removed from this bill.', 'info');
    }

    // "Sell gift card" flow: card pre-bound into the cart while it is still
    // 'available'; it is marked sold only when checkout commits.
    @if(!empty($pendingGiftCard))
        cart.push({
            cartKey: 'gc_sale_{{ $pendingGiftCard->id }}',
            giftCard: true,
            giftCardId: {{ $pendingGiftCard->id }},
            id: null,
            variantId: null,
            name: @json($pendingGiftCard->saleItemLabel()),
            cardNumber: @json($pendingGiftCard->card_number),
            sku: @json($pendingGiftCard->card_number),
            color: '', size: '',
            price: parseFloat(@json((string) $pendingGiftCard->value)),
            stock: 1,
            quantity: 1,
        });
        renderCart();
        showToast(@json('Gift card ' . $pendingGiftCard->card_number . ' is in the cart — it becomes SOLD when checkout completes.'), 'info');
    @endif

    // Initial setup
    updateHeldOrdersBadge();
    filterProducts();
</script>
@endpush
