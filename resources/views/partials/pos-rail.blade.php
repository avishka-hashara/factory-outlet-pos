<!-- ============================================
     Lumen Terminal — Global Navigation Rail
     Exactly 88px width, full height, Modern-Industrial Dark
     ============================================ -->
@php
    $u = auth()->user();
@endphp
<nav id="pos-icon-rail" class="pos-global-rail" aria-label="Global Navigation Rail">
    <div class="rail-scroll-body">
        <!-- 1. Dashboard -->
        @if(!$u || $u->canAccess('dashboard'))
        <a href="{{ route('dashboard') }}" 
           class="rail-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" 
           title="Dashboard" data-tooltip="Dashboard">
            <span class="material-symbols-outlined rail-icon">dashboard</span>
            <span class="rail-label">Dashboard</span>
        </a>
        @endif

        <!-- 2. POS Billing (Sales) -->
        @if(!$u || $u->canAccess('pos'))
        <a href="{{ route('pos.index') }}" 
           class="rail-item {{ request()->routeIs('pos.index') ? 'active' : '' }}" 
           title="POS Billing" data-tooltip="POS Billing">
            <span class="material-symbols-outlined rail-icon">shopping_cart</span>
            <span class="rail-label">Sales</span>
        </a>
        @endif

        <!-- 3. Returns & Refunds (Returns) -->
        @if(!$u || $u->canAccess('returns'))
        <a href="{{ route('returns.index') }}" 
           class="rail-item {{ request()->routeIs('returns.*') ? 'active' : '' }}" 
           title="Returns & Refunds" data-tooltip="Returns & Refunds">
            <span class="material-symbols-outlined rail-icon">assignment_return</span>
            <span class="rail-label">Returns</span>
        </a>
        @endif

        <!-- 4. Products (Stock) -->
        @if(!$u || $u->canAccess('products'))
        <a href="{{ route('products.index') }}" 
           class="rail-item {{ request()->routeIs('products.*') ? 'active' : '' }}" 
           title="Products & Stock" data-tooltip="Products & Stock">
            <span class="material-symbols-outlined rail-icon">inventory_2</span>
            <span class="rail-label">Stock</span>
        </a>
        @endif

        <!-- 5. Sales History (Orders) -->
        @if(!$u || $u->canAccess('sales') || $u->canAccess('pos_log'))
        <a href="{{ route('sales.index') }}" 
           class="rail-item {{ request()->routeIs('sales.*') || request()->routeIs('pos.log') ? 'active' : '' }}" 
           title="Sales History" data-tooltip="Sales History">
            <span class="material-symbols-outlined rail-icon">receipt_long</span>
            <span class="rail-label">Orders</span>
        </a>
        @endif

        <!-- 6. Reports -->
        @if(!$u || $u->canAccess('reports'))
        <a href="{{ route('reports.index') }}" 
           class="rail-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" 
           title="Analytics & Reports" data-tooltip="Analytics & Reports">
            <span class="material-symbols-outlined rail-icon">analytics</span>
            <span class="rail-label">Reports</span>
        </a>
        @endif

        <!-- 7. Cash Drawer (Drawer) -->
        @if(!$u || $u->canAccess('cash_drawer'))
        <a href="{{ route('cash-drawer.index') }}" 
           class="rail-item {{ request()->routeIs('cash-drawer.*') ? 'active' : '' }}" 
           title="Cash Drawer" data-tooltip="Cash Drawer">
            <span class="material-symbols-outlined rail-icon">wallet</span>
            <span class="rail-label">Drawer</span>
        </a>
        @endif

        <!-- 8. Customers -->
        <a href="{{ route('customers.index') }}" 
           class="rail-item {{ request()->routeIs('customers.*') ? 'active' : '' }}" 
           title="Customer Management" data-tooltip="Customer Management">
            <span class="material-symbols-outlined rail-icon">group</span>
            <span class="rail-label">Customers</span>
        </a>

        <!-- 9. Gift Cards -->
        @if(!$u || $u->canAccess('gift_cards'))
        <a href="{{ route('gift-cards.index') }}" 
           class="rail-item {{ request()->routeIs('gift-cards.*') ? 'active' : '' }}" 
           title="Gift Cards" data-tooltip="Gift Cards">
            <span class="material-symbols-outlined rail-icon">redeem</span>
            <span class="rail-label">Gift Cards</span>
        </a>
        @endif
    </div>

    <!-- Pinned Bottom Section (mt-auto, separated by 1px border) -->
    <div class="rail-bottom-pin">
        <!-- Lock Screen -->
        <form method="POST" action="{{ route('logout') }}" style="margin:0; width:100%;">
            @csrf
            <button type="submit" class="rail-item rail-bottom-btn" title="Lock Terminal" data-tooltip="Lock Terminal">
                <span class="material-symbols-outlined rail-icon">lock</span>
                <span class="rail-label">Lock</span>
            </button>
        </form>

        <!-- Logout -->
        <form method="POST" action="{{ route('logout') }}" style="margin:0; width:100%;">
            @csrf
            <button type="submit" class="rail-item rail-bottom-btn" title="Logout" data-tooltip="Logout">
                <span class="material-symbols-outlined rail-icon">logout</span>
                <span class="rail-label">Logout</span>
            </button>
        </form>
    </div>
</nav>
