<!-- ============================================
     Lumen Terminal — Global Navigation Rail (REVISED)
     Exactly 88px width, full height, 64px item height, Modern-Industrial Dark
     ============================================ -->
@php
    $u = auth()->user();
@endphp
<nav id="pos-icon-rail" class="pos-global-rail" aria-label="Global Navigation Rail">
    <div class="rail-scroll-body">
        
        {{-- ===== 1. MAIN ===== --}}
        @if(!$u || $u->canAccess('dashboard'))
        <a href="{{ route('dashboard') }}" 
           class="rail-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" 
           data-tooltip="Dashboard">
            <span class="material-symbols-outlined rail-icon">dashboard</span>
            <span class="rail-label">Dashboard</span>
        </a>
        @endif

        <div class="rail-divider"></div>

        {{-- ===== 2. SALES ===== --}}
        <!-- POS Billing -->
        @if(!$u || $u->canAccess('pos'))
        <a href="{{ route('pos.index') }}" 
           class="rail-item {{ request()->routeIs('pos.index') ? 'active' : '' }}" 
           data-tooltip="POS Billing">
            <span class="material-symbols-outlined rail-icon">shopping_cart</span>
            <span class="rail-label">Sales</span>
        </a>
        @endif

        <!-- POS Sales Log -->
        @if(!$u || $u->canAccess('pos_log'))
        <a href="{{ route('pos.log') }}" 
           class="rail-item {{ request()->routeIs('pos.log') ? 'active' : '' }}" 
           data-tooltip="POS Sales Log">
            <span class="material-symbols-outlined rail-icon">point_of_sale</span>
            <span class="rail-label">POS Log</span>
        </a>
        @endif

        <!-- Sales History -->
        @if(!$u || $u->canAccess('sales'))
        <a href="{{ route('sales.index') }}" 
           class="rail-item {{ request()->routeIs('sales.*') && !request()->routeIs('pos.log') ? 'active' : '' }}" 
           data-tooltip="Sales History">
            <span class="material-symbols-outlined rail-icon">receipt_long</span>
            <span class="rail-label">Orders</span>
        </a>
        @endif

        <!-- Returns & Refunds -->
        @if(!$u || $u->canAccess('returns'))
        <a href="{{ route('returns.index') }}" 
           class="rail-item {{ request()->routeIs('returns.*') ? 'active' : '' }}" 
           data-tooltip="Returns & Refunds">
            <span class="material-symbols-outlined rail-icon">assignment_return</span>
            <span class="rail-label">Returns</span>
        </a>
        @endif

        <!-- Cash Drawer -->
        @if(!$u || $u->canAccess('cash_drawer'))
        <a href="{{ route('cash-drawer.index') }}" 
           class="rail-item {{ request()->routeIs('cash-drawer.*') ? 'active' : '' }}" 
           data-tooltip="Cash Drawer">
            <span class="material-symbols-outlined rail-icon">wallet</span>
            <span class="rail-label">Drawer</span>
        </a>
        @endif

        <div class="rail-divider"></div>

        {{-- ===== 3. INVENTORY ===== --}}
        <!-- Products -->
        @if(!$u || $u->canAccess('products'))
        <a href="{{ route('products.index') }}" 
           class="rail-item {{ request()->routeIs('products.*') ? 'active' : '' }}" 
           data-tooltip="Products & Stock">
            <span class="material-symbols-outlined rail-icon">inventory_2</span>
            <span class="rail-label">Products</span>
        </a>
        @endif

        <!-- Gift Cards -->
        @if(!$u || $u->canAccess('gift_cards'))
        <a href="{{ route('gift-cards.index') }}" 
           class="rail-item {{ request()->routeIs('gift-cards.*') ? 'active' : '' }}" 
           data-tooltip="Gift Cards">
            <span class="material-symbols-outlined rail-icon">redeem</span>
            <span class="rail-label">Gifts</span>
        </a>
        @endif

        <div class="rail-divider"></div>

        {{-- ===== 4. CUSTOMERS ===== --}}
        <!-- Customers -->
        <a href="{{ route('customers.index') }}" 
           class="rail-item {{ request()->routeIs('customers.*') ? 'active' : '' }}" 
           data-tooltip="Customer Management">
            <span class="material-symbols-outlined rail-icon">group</span>
            <span class="rail-label">Customers</span>
        </a>

        <div class="rail-divider"></div>

        {{-- ===== 5. REPORTS ===== --}}
        <!-- Reports -->
        @if(!$u || $u->canAccess('reports'))
        <a href="{{ route('reports.index') }}" 
           class="rail-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" 
           data-tooltip="Analytics & Reports">
            <span class="material-symbols-outlined rail-icon">analytics</span>
            <span class="rail-label">Reports</span>
        </a>
        @endif

        <div class="rail-divider"></div>

        {{-- ===== 6. ADMIN ===== --}}
        @if(!$u || $u->isAdmin())
        <!-- Staff Management -->
        <a href="{{ route('staff.index') }}" 
           class="rail-item {{ request()->routeIs('staff.*') ? 'active' : '' }}" 
           data-tooltip="Staff Management">
            <span class="material-symbols-outlined rail-icon">badge</span>
            <span class="rail-label">Staff</span>
        </a>

        <!-- Roles & Permissions -->
        <a href="{{ route('roles.index') }}" 
           class="rail-item {{ request()->routeIs('roles.*') ? 'active' : '' }}" 
           data-tooltip="Roles & Permissions">
            <span class="material-symbols-outlined rail-icon">shield_person</span>
            <span class="rail-label">Roles</span>
        </a>

        <!-- Settings -->
        <a href="{{ route('settings.index') }}" 
           class="rail-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" 
           data-tooltip="System Settings">
            <span class="material-symbols-outlined rail-icon">settings</span>
            <span class="rail-label">Settings</span>
        </a>
        @endif

    </div>

    <!-- Pinned Bottom Section (mt-auto, separated by 1px border) -->
    <div class="rail-bottom-pin">
        <!-- 14. Lock Screen -->
        <form method="POST" action="{{ route('logout') }}" style="margin:0; width:100%;">
            @csrf
            <button type="submit" class="rail-item rail-bottom-btn" data-tooltip="Lock Terminal">
                <span class="material-symbols-outlined rail-icon">lock</span>
                <span class="rail-label">Lock</span>
            </button>
        </form>

        <!-- 15. Logout -->
        <form method="POST" action="{{ route('logout') }}" style="margin:0; width:100%;">
            @csrf
            <button type="submit" class="rail-item rail-bottom-btn rail-logout-btn" data-tooltip="Logout">
                <span class="material-symbols-outlined rail-icon">logout</span>
                <span class="rail-label">Logout</span>
            </button>
        </form>
    </div>
</nav>
