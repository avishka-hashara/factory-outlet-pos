# UI Inventory — POS Screen Restructure & Re-theme

This checklist captures every interactive, informational, and conditionally-rendered element on the POS terminal, mapping each element from its **Current Region** to its **New Region** per DESIGN.md and the target Stitch layout.

| Element | Type | ID / Selector | Label text | Current region | New region |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Top Bar (64px)** | | | | | |
| Sidebar / Rail Toggle Button | Button | `#sidebarToggle` | (Icon: `fas fa-bars`) | Top Navbar (Left) | Top Bar (Left) |
| Brand Logo Image | Image | `.navbar-logo-image` | Store Logo | Top Navbar (Left) | Top Bar (Left) |
| Brand Store Wordmark | Text / Heading | `.brand-text` | BC LARAVEL / Clothing Outlet POS | Top Navbar (Left) | Top Bar (Left) |
| Product Search Container | Input Wrapper | `.billing-search-wrap` | Search wrapper | Billing Topbar | Top Bar (Center, Wide) |
| Product Search Input | Text Input | `#product-search` | Enter Product name / SKU / Scan bar code | Billing Topbar | Top Bar (Center, Wide) |
| Product Search Live Dropdown | Live List | `#product-search-dropdown` | Search results container | Billing Topbar | Top Bar (Center Dropdown) |
| Quick Add Custom Item Button | Modal Trigger | `.billing-add-btn` | (Icon: `fas fa-plus`) | Billing Topbar | Top Bar (Center, Right of Search) |
| Notifications Trigger | Dropdown Button | `.nav-icon-btn` | (Icon: `fas fa-bell`) | Top Navbar (Right) | Top Bar (Right Cluster) |
| Notification Badge Counter | Badge | `.badge.bg-danger` | `{{ $notifCount }}` | Top Navbar (Right) | Top Bar (Right Cluster) |
| Notifications Menu Header | Heading | `.notification-dropdown strong` | Notifications | Top Navbar (Dropdown) | Top Bar (Dropdown) |
| Out of Stock Alert Items | Link List Item | `.dropdown-item` | Product name + `0 left` | Top Navbar (Dropdown) | Top Bar (Dropdown) |
| Low Stock Alert Items | Link List Item | `.dropdown-item` | Product name + `N left` | Top Navbar (Dropdown) | Top Bar (Dropdown) |
| Recent Sales Alert Items | Link List Item | `.dropdown-item` | `#Invoice` + `Rs. Total` + `Time` | Top Navbar (Dropdown) | Top Bar (Dropdown) |
| Shopify Sync Status Item | Info Item | `.dropdown-item` | `Sync complete / error` | Top Navbar (Dropdown) | Top Bar (Dropdown) |
| All Clear Alert State | Empty State | `.notification-dropdown .text-center` | All clear — no alerts | Top Navbar (Dropdown) | Top Bar (Dropdown) |
| View Stock Alerts Link | Link | `a[href*='products']` | View Stock Alerts | Top Navbar (Dropdown) | Top Bar (Dropdown) |
| Global Shopify Sync Button | Button | `#global-sync-btn` | Sync (Icon: `fab fa-shopify`) | Top Navbar (Right) | Top Bar (Right Cluster) |
| Sync Spinner Icon | Icon | `#sync-icon` | (Icon: `fab fa-shopify`) | Top Navbar (Right) | Top Bar (Right Cluster) |
| Sync Status Dot Indicator | Status Dot | `#sync-status-dot` | Tooltip status | Top Navbar (Right) | Top Bar (Right Cluster) |
| Calendar Icon & Date | Text | `#current-date` | Current Date (`D, M d Y`) | Top Navbar (Right) | Top Bar (Right Cluster) |
| User Profile Trigger | Dropdown Button | `.navbar-right-items .dropdown-toggle` | (User Avatar + Name) | Top Navbar (Far Right) | Top Bar (Far Right) |
| User Profile Header | Header Info | `.dropdown-header` | Name & Email | Top Navbar (Dropdown) | Top Bar (Dropdown) |
| Profile Link | Link | `a[href*='profile']` | Profile | Top Navbar (Dropdown) | Top Bar (Dropdown) |
| Settings Link | Link | `a[href*='settings']` | Settings | Top Navbar (Dropdown) | Top Bar (Dropdown) |
| Logout Button | Form Submit | `form[action*='logout'] button` | Logout | Top Navbar (Dropdown) | Top Bar (Dropdown) |
| **Left Icon Rail (~88px, Icon Over Label)** | | | | | |
| Icon Rail Container | Vertical Rail | `#pos-icon-rail` | Navigation rail container | Slideout Sidebar | Left Icon Rail (~88px) |
| POS Billing Link (Active) | Nav Link | `a[href*='pos']` | POS Billing / Sales | Slideout Sidebar | Left Icon Rail (Active) |
| Dashboard Link | Nav Link | `a[href*='dashboard']` | Dashboard | Slideout Sidebar | Left Icon Rail |
| POS Sales Log Link | Nav Link | `a[href*='pos/log']` | POS Sales Log | Slideout Sidebar | Left Icon Rail |
| Sales History Link | Nav Link | `a[href*='sales']` | Sales History | Slideout Sidebar | Left Icon Rail |
| Returns & Refunds Link | Nav Link | `a[href*='returns']` | Returns & Refunds | Slideout Sidebar | Left Icon Rail |
| Cash Drawer Link | Nav Link | `a[href*='cash-drawer']` | Cash Drawer | Slideout Sidebar | Left Icon Rail |
| Products Link | Nav Link | `a[href*='products']` | Products / Stock | Slideout Sidebar | Left Icon Rail |
| Gift Cards Link | Nav Link | `a[href*='gift-cards']` | Gift Cards | Slideout Sidebar | Left Icon Rail |
| Customers Link | Nav Link | `a[href*='customers']` | Customers | Slideout Sidebar | Left Icon Rail |
| Reports Link | Nav Link | `a[href*='reports']` | Reports | Slideout Sidebar | Left Icon Rail |
| Staff Link | Nav Link | `a[href*='staff']` | Staff | Slideout Sidebar | Left Icon Rail |
| Roles & Permissions Link | Nav Link | `a[href*='roles']` | Roles & Permissions | Slideout Sidebar | Left Icon Rail |
| Settings Link | Nav Link | `a[href*='settings']` | Settings | Slideout Sidebar | Left Icon Rail |
| Bottom Rail Logout Button | Form Submit | `#pos-rail-logout form button` | Logout | Slideout Sidebar | Left Icon Rail (Bottom Pin) |
| Sidebar Overlay (Slideout drawer) | Dim Layer | `#pos-sidebar-overlay` | (Click to close) | Overlay | Overlay / Slideout Drawer |
| Slideout Panel (Full Drawer) | Panel | `#pos-sidebar-panel` | Collapsible sidebar | Overlay | Slideout Panel (Drawer) |
| Mobile Close Button | Button | `#sidebarClose` | (Icon: `fas fa-times`) | Slideout Panel | Slideout Panel (Drawer) |
| **Center Panel (Product Grid & Search Results)** | | | | | |
| Center Panel Wrapper | Flex / Grid Container | `#pos-center-panel` | Center Panel area | (Former Cart location) | Center Panel |
| Filter Button: Local Only | Button | `#btn-local-only` | Local Only | Catalogue (Hidden) | Center Panel (Source Filter) |
| Filter Button: All Products | Button | `#btn-all-products` | All Products | Catalogue (Hidden) | Center Panel (Source Filter) |
| Category Pills Wrapper | Container | `#local-category-pills-wrap` | Categories list | Catalogue (Hidden) | Center Panel (Category Chips) |
| Category Filter Buttons | Pill Buttons | `.cat-pill` | All / Men / Women / Kids... | Catalogue (Hidden) | Center Panel (Category Chips) |
| Shopify Collections Wrapper | Container | `#shopify-collection-pills-wrap` | Collections list | Catalogue (Hidden) | Center Panel (Category Chips) |
| Catalogue Secondary Search | Text Input | `#catalogue-search` | Search... | Catalogue (Hidden) | Center Panel (Helper Filter) |
| Product Grid Container | Tile Grid | `#product-grid` | Product grid container | Catalogue (Hidden) | Center Panel (Tile Grid) |
| Product Tiles | Interactive Cards | `.prod-card` | Image, Name, Variants, Price, Stock | Catalogue (Hidden) | Center Panel (Tile Grid) |
| Product Tile Stock Badges | Badges | `.prod-stock-badge` | N IN STOCK | Catalogue (Hidden) | Center Panel (Tile Grid) |
| No Results Placeholder | Empty State | `#no-results` | No products found | Catalogue (Hidden) | Center Panel (Tile Grid) |
| Pagination Controls | Navigation Bar | `#catalogue-pagination` | `< PAGE 1 OF N >` | (New Visual Helper) | Center Panel (Bottom) |
| **Right Cart Panel (Full Height, ~38% Width)** | | | | | |
| Right Cart Panel Wrapper | Panel Container | `#pos-cart-panel` | Cart Panel | Left Billing Panel | Right Cart Panel (~38%) |
| Customer Selector Button | Modal Trigger | `#pos-customer-btn` / `.customer-selector` | Customer Details | Billing Topbar | Right Cart Panel (Header) |
| Customer Name Display | Text | `#pos-customer-name` | Customer Details / Selected Name | Billing Topbar | Right Cart Panel (Header) |
| Customer Phone/Subtext | Text | `#pos-customer-sub` | Add customer name & phone / Phone | Billing Topbar | Right Cart Panel (Header) |
| Customer Dropdown Caret | Icon | `.customer-selector .fa-caret-down` | (Caret icon) | Billing Topbar | Right Cart Panel (Header) |
| Clear Cart Header Button | Button | `#btn-clear-cart-header` | (Icon: `fas fa-trash-alt` / `fa-times`) | Payment Bar (Cancel) | Right Cart Panel (Header Right) |
| Items Count Header Display | Counter Text | `#cart-count-header` / `#cart-count` | Items: `0` | Billing Footer | Right Cart Panel (Under Title) |
| Cart Line-Items Wrapper | Scroll Area | `#billing-table-wrap` | Items scroll container | Billing Panel | Right Cart Panel (Items Area) |
| Cart Table Element | Table | `#cart-table` | Cart grid | Billing Panel | Right Cart Panel (Items Area) |
| Table Column: Product | Header | `th:nth-child(1)` | Product (with info tooltip) | Cart Table | Right Cart Panel (Items Area) |
| Table Column: Quantity | Header | `th:nth-child(2)` | Quantity | Cart Table | Right Cart Panel (Items Area) |
| Table Column: Subtotal | Header | `th:nth-child(3)` | Subtotal | Cart Table | Right Cart Panel (Items Area) |
| Table Column: Action/Remove | Header | `th:nth-child(4)` | (Icon: `fas fa-times`) | Cart Table | Right Cart Panel (Items Area) |
| Cart Items Body | Table Body | `#cart-items` | Cart items container | Cart Table | Right Cart Panel (Items Area) |
| Empty Cart Row State | Empty Row | `#empty-cart-row` | Cart is empty / Search and add products | Cart Table | Right Cart Panel (Items Area) |
| Cart Item Name | Text | `td:nth-child(1) .prod-name` | Item Name | Cart Item Row | Right Cart Panel (Item Row) |
| Cart Item Variant Badge | Badge | `td:nth-child(1) .badge` | Color / Size | Cart Item Row | Right Cart Panel (Item Row) |
| Cart Item SKU | Text | `td:nth-child(1)` | SKU: {sku} | Cart Item Row | Right Cart Panel (Item Row) |
| Cart Item Unit Price Input | Number Input | `td:nth-child(1) input[type='number']` | Editable unit price | Cart Item Row | Right Cart Panel (Item Row) |
| Cart Item Edit Price Pencil | Icon | `td:nth-child(1) .fa-pencil-alt` | (Pencil icon) | Cart Item Row | Right Cart Panel (Item Row) |
| Cart Item Qty Decrement Button | Button | `button[onclick*='updateQty'] (minus)` | (Icon: `fas fa-minus`) | Cart Item Row | Right Cart Panel (Item Row) |
| Cart Item Qty Counter | Text | `td:nth-child(2) span` | `{quantity}` | Cart Item Row | Right Cart Panel (Item Row) |
| Cart Item Qty Increment Button | Button | `button[onclick*='updateQty'] (plus)` | (Icon: `fas fa-plus`) | Cart Item Row | Right Cart Panel (Item Row) |
| Cart Item Subtotal Amount | Text | `td:nth-child(3)` | `Rs. {total}` | Cart Item Row | Right Cart Panel (Item Row) |
| Cart Item Remove Button | Button | `button[onclick*='removeFromCart']` | (Icon: `fas fa-times`) | Cart Item Row | Right Cart Panel (Item Row) |
| Sold Gift Card Row: Name & Tag | Text & Badge | `td:nth-child(1)` | Gift Card Name + `GIFT CARD` badge | Cart Item Row (GC) | Right Cart Panel (Item Row) |
| Sold Gift Card Row: Card Number | Text | `td:nth-child(1)` | Card No: {number} | Cart Item Row (GC) | Right Cart Panel (Item Row) |
| Sold Gift Card Row: Fixed Qty | Text | `td:nth-child(2)` | 1 | Cart Item Row (GC) | Right Cart Panel (Item Row) |
| Sold Gift Card Row: Price | Text | `td:nth-child(3)` | `Rs. {value}` | Cart Item Row (GC) | Right Cart Panel (Item Row) |
| Sold Gift Card Row: Remove | Button | `button[onclick*='removeFromCart']` | (Icon: `fas fa-times`) | Cart Item Row (GC) | Right Cart Panel (Item Row) |
| **Totals Block (Inside Right Cart Panel)** | | | | | |
| Totals Block Container | Container | `#billing-footer` | Totals Container | Billing Footer | Right Cart Panel (Totals Block) |
| Subtotal Label & Value | Text / Amount | `#subtotal` | Total: `0.00` / Subtotal | Billing Footer | Right Cart Panel (Totals Block) |
| Discount Label & Icon | Label | `label for discount` | Discount (-) (Icons: `fa-tag`, `fa-info-circle`, `fa-edit`) | Billing Footer | Right Cart Panel (Totals Block) |
| Discount Type Dropdown | Select | `#discount-type` | None / % / Rs. | Billing Footer | Right Cart Panel (Totals Block) |
| Discount Value Input | Number Input | `#discount-value` | `0` | Billing Footer | Right Cart Panel (Totals Block) |
| Discount Deducted Display | Badge / Amount | `#discount-display` / `#discount-amount-text` | `-Rs. {amount}` | Billing Footer | Right Cart Panel (Totals Block) |
| Shipping Label & Icon | Label | `label for shipping` | Shipping(+) (Icons: `fa-info-circle`, `fa-edit`) | Billing Footer | Right Cart Panel (Totals Block) |
| Shipping Value Input | Number Input | `#shipping-value` | `0` | Billing Footer | Right Cart Panel (Totals Block) |
| Gift Card Redemption Row | Row Container | `#giftcard-redeem-row` | Gift Card row | Billing Footer (Conditional) | Right Cart Panel (Totals Block) |
| Gift Card Number Badge | Text | `#gc-row-number` | Card Number | Billing Footer (Conditional) | Right Cart Panel (Totals Block) |
| Gift Card Deduction Value | Text / Amount | `#gc-row-amount` | `-Rs. {value}` | Billing Footer (Conditional) | Right Cart Panel (Totals Block) |
| Gift Card Remove Button | Button | `button[onclick*='removeGiftCardRedemption']` | (Icon: `fas fa-times`) | Billing Footer (Conditional) | Right Cart Panel (Totals Block) |
| Customer Notes Textarea | Textarea | `#customer-notes` | Customer notes (optional)... | Billing Footer | Right Cart Panel (Below Totals) |
| Grand Total Container | Highlight Box | `.paybar-total` / `#cart-grand-total-wrap` | Grand Total Box | Payment Bar | Right Cart Panel (Grand Total Row) |
| Grand Total Label | Label | `.paybar-total-label` | GRAND TOTAL / Total Payable | Payment Bar | Right Cart Panel (Grand Total Row) |
| Grand Total Amount Value | Large Display Price | `#total` | `0.00` (Amber, Inter Bold) | Payment Bar | Right Cart Panel (Grand Total Row) |
| **Payment & Action Zone (Bottom of Right Cart Panel)** | | | | | |
| Payment Zone Container | Container | `#pos-payment-zone` | Payment Action Grid | Payment Bar | Payment Zone (Bottom Right) |
| Card Payment Button | Button | `#pay-btn-card` | Card (Icon: `fas fa-credit-card`) | Payment Bar | Payment Zone (Grid 1/8) |
| Multiple Pay Button | Button | `#pay-btn-multipay` | Multiple Pay (Icon: `fas fa-layer-group`) | Payment Bar | Payment Zone (Grid 2/8) |
| Koko Payment Button | Button | `#pay-btn-koko` | Koko (Icon: `fas fa-mobile-alt`) | Payment Bar | Payment Zone (Grid 3/8) |
| Mintpay Payment Button | Button | `#pay-btn-mintpay` | Mintpay (Icon: `fas fa-leaf`) | Payment Bar | Payment Zone (Grid 4/8) |
| Payzy Payment Button | Button | `#pay-btn-payzy` | Payzy (Icon: `fas fa-bolt`) | Payment Bar | Payment Zone (Grid 5/8) |
| Cash Payment Button | Button | `#pay-btn-cash` | Cash (Icon: `fas fa-money-bill-wave`) | Payment Bar | Payment Zone (Grid 6/8) |
| Bank Transfer Button | Button | `#pay-btn-bank` | Bank Transfer (Icon: `fas fa-university`) | Payment Bar | Payment Zone (Grid 7/8) |
| Redeem Gift Card Button | Button | `#pay-btn-giftcard` | Redeem Gift Card (Icon: `fas fa-gift`) | Payment Bar | Payment Zone (Grid 8/8) |
| Cancel / Clear Cart Button | Destructive Button | `button[onclick*='clearCart']` | Cancel (Icon: `fas fa-times`) | Payment Bar | Payment Zone (Action Row) |
| Recent Transactions Button | Action Button | `button[onclick*='showRecentTransactions']` | Recent Transactions (Icon: `fas fa-history`) | Payment Bar | Payment Zone (Action Row) |
| Checkout Button | Primary Button | `#checkout-btn` | Checkout (Icon: `fas fa-check-circle`) | Payment Bar | Payment Zone (Bottom-Most, Full Width) |
| **All Modals & Popups (100% Retained)** | | | | | |
| Variant Selection Modal | Modal | `#colorModal` | Select Variant | Modal | Modal: Variant |
| Selected Product Image/Name/SKU | Details Box | `#color-modal-img`, `#color-modal-name`, `#color-modal-sku` | Variant details | Modal | Modal: Variant |
| Variant Options Container | Button Grid | `#color-options` | Available Options | Modal | Modal: Variant |
| Cash Payment Modal | Modal | `#cashPaymentModal` | Cash Payment | Modal | Modal: Cash |
| Cash Modal Total Payable | Box | `#cash-modal-total` | Rs. 0.00 | Modal | Modal: Cash |
| Cash Given Input | Input | `#cash-modal-given` | 0.00 | Modal | Modal: Cash |
| Quick Cash Presets | Buttons | `#cash-quick-buttons button` | 6000, 7000, 8000, 9000, 10000 | Modal | Modal: Cash |
| Change (Balance) Display | Display | `#cash-modal-change` | Rs. 0.00 | Modal | Modal: Cash |
| Cash Payment Summaries | Summary | `#cash-summary-total`, `#cash-summary-given`, `#cash-summary-change` | Summary amounts | Modal | Modal: Cash |
| Cash Cancel & Confirm Buttons | Buttons | `[data-bs-dismiss='modal']`, `#confirm-cash-btn` | Cancel / Confirm Payment | Modal | Modal: Cash |
| Multiple Payment Modal | Modal | `#multiPaymentModal` | Multiple Payment | Modal | Modal: MultiPay |
| MultiPay Total & Split Rows | Display & Rows | `#multipay-modal-total`, `#multipay-rows-container` | Split rows | Modal | Modal: MultiPay |
| MultiPay Add Row Button | Button | `button[onclick*='addMultiPayRow']` | Add Another Payment | Modal | Modal: MultiPay |
| MultiPay Summaries | Summary | `#multipay-summary-paid`, `#multipay-summary-balance` | Paid / Balance Due | Modal | Modal: MultiPay |
| MultiPay Cancel & Confirm | Buttons | `[data-bs-dismiss='modal']`, `#confirm-multipay-btn` | Cancel / Confirm Payment | Modal | Modal: MultiPay |
| Success Modal | Modal | `#successModal` | Sale Complete! | Modal | Modal: Success |
| Success Sale Details | Summary | `#success-sale-id`, `#success-sale-total`, `#success-sale-date`, `#success-sale-time`, `#success-sale-method` | Sale summary | Modal | Modal: Success |
| Success Action Buttons | Buttons | `#view-invoice-btn`, `openSmsSection()`, `startNewSale()` | Print Bill / SMS Bill / New Sale | Modal | Modal: Success |
| SMS Form Steps | Inputs & Buttons | `#sms-form-section`, `#sms-phone-number`, `#sms-confirm-btn`, `#sms-retry-btn` | SMS Workflow | Modal | Modal: Success (SMS) |
| Quick Add Product Modal | Modal | `#quickAddProductModal` | Quick Add Product | Modal | Modal: Quick Add |
| Quick Add Form Fields | Inputs | `#qa-name`, `#qa-price`, `#qa-stock`, `#qa-sku`, `#qa-category` | Product fields | Modal | Modal: Quick Add |
| Quick Add Submit Button | Primary Button | `#qa-submit-btn` | Save & Add to Cart | Modal | Modal: Quick Add |
| Recent Transactions Modal | Modal | `#recentTransactionsModal` | Today's Transactions | Modal | Modal: Recent Sales |
| Recent Transactions Table Body | Table Body | `#recent-transactions-tbody` | Transaction records | Modal | Modal: Recent Sales |
| Recent Transactions Totals | Summary | `#recent-total-count`, `#recent-total-amount` | Counters | Modal | Modal: Recent Sales |
| Gift Card Lookup Modal | Modal | `#giftCardRedeemModal` | Redeem Gift Card | Modal | Modal: GC Lookup |
| GC Scan & Manual Tabs/Inputs | Tabs & Inputs | `#gc-tab-scan`, `#gc-tab-manual`, `#gc-scan-input`, `#gc-manual-input` | Scan / Enter Manually | Modal | Modal: GC Lookup |
| GC Visual Card & Details | Display | `#gc-lookup-result`, `#gc-visual-value`, `#gc-visual-number`, `#gc-state-banner`, `#gc-detail-value` | Details | Modal | Modal: GC Lookup |
| GC Lookup Continue Button | Primary Button | `#gc-continue-btn` | Continue | Modal | Modal: GC Lookup |
| Gift Card Confirm Modal | Modal | `#giftCardConfirmModal` | Confirm Gift Card Redemption | Modal | Modal: GC Confirm |
| GC Confirm Details & Warning | Details | `#gc-confirm-number`, `#gc-confirm-value`, `#gc-confirm-bill`, `#gc-confirm-redeem`, `#gc-confirm-after`, `#gc-confirm-warning` | Redemption calculations | Modal | Modal: GC Confirm |
| GC Confirm Buttons | Buttons | `gcBackToLookup()`, `#gc-confirm-redeem-btn` | Back / Confirm Redemption | Modal | Modal: GC Confirm |
| Customer Select / Create Modal | Modal | `#customerSelectModal` | Select Customer | Modal | Modal: Customer |
| Customer Search Input & Results | Input & List | `#customer-search-input`, `#customer-search-results` | Search input & list | Modal | Modal: Customer |
| Customer Quick Add Form | Inputs & Button | `#quick-cust-name`, `#quick-cust-phone`, `#quickAddCustomerForm button` | Quick Add Form | Modal | Modal: Customer |
| Customer Clear & Close Buttons | Buttons | `clearCustomer()`, `[data-bs-dismiss='modal']` | Clear / Walk-In / Close | Modal | Modal: Customer |
| Silent Print iframe | iframe | `#print-frame` | (Hidden print container) | Global / DOM | Global / DOM |
