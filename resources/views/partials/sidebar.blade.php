<!-- ============================================
     Lumen Terminal — Hamburger Drawer Overlay
     Full-Route Map Overlay, Restyled with Design Tokens
     ============================================ -->
@php $u = auth()->user(); @endphp

<!-- Backdrop Overlay: Flat 60% opacity background, no blur -->
<div id="pos-sidebar-overlay" class="pos-drawer-backdrop" onclick="posSidebarClose()"></div>

<!-- Slideout Drawer Panel: 280px wide -->
<aside id="pos-sidebar-panel" class="pos-drawer-overlay-panel" aria-label="Full Navigation Drawer">
    <!-- Header with Branding + Close Button -->
    <div class="drawer-header flex items-center justify-between px-4 h-[64px] border-b border-surface-muted bg-surface flex-shrink-0">
        <div class="flex items-center gap-3">
            <span class="text-primary text-[20px] font-[900] tracking-tight" style="color: var(--primary) !important;">
                {{ \App\Models\Setting::get('app_name', config('app.name', 'LARAVEL')) }}
            </span>
            <span class="text-on-surface-variant text-[12px]">Menu</span>
        </div>
        <button type="button" 
                onclick="posSidebarClose()" 
                class="w-8 h-8 rounded flex items-center justify-center text-on-surface-variant hover:text-on-surface hover:bg-surface-high border-0 bg-transparent cursor-pointer p-0"
                title="Close Drawer">
            <span class="material-symbols-outlined text-[22px]">close</span>
        </button>
    </div>

    <!-- Scrollable Navigation Groups -->
    <div class="flex-1 overflow-y-auto overflow-x-hidden py-2" style="scrollbar-width: none;">
        <ul class="m-0 p-0 list-none">

            {{-- ===== 1. MAIN ===== --}}
            @if(!$u || $u->canAccess('dashboard') || $u->canAccess('pos'))
            <li class="drawer-heading">Main</li>
            @endif

            <!-- Dashboard -->
            @if(!$u || $u->canAccess('dashboard'))
            <li class="drawer-nav-item">
                <a href="{{ route('dashboard') }}" 
                   class="drawer-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Dashboard</span>
                </a>
            </li>
            @endif

            <!-- POS Billing -->
            @if(!$u || $u->canAccess('pos'))
            <li class="drawer-nav-item">
                <a href="{{ route('pos.index') }}" 
                   class="drawer-nav-link {{ request()->routeIs('pos.index') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">shopping_cart</span>
                    <span>POS Billing</span>
                </a>
            </li>
            @endif

            {{-- ===== 2. SALES ===== --}}
            @if(!$u || $u->canAccess('pos_log') || $u->canAccess('sales') || $u->canAccess('returns') || $u->canAccess('cash_drawer'))
            <li class="drawer-heading">Sales</li>
            @endif

            <!-- POS Sales Log -->
            @if(!$u || $u->canAccess('pos_log'))
            <li class="drawer-nav-item">
                <a href="{{ route('pos.log') }}" 
                   class="drawer-nav-link {{ request()->routeIs('pos.log') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">store</span>
                    <span>POS Sales Log</span>
                </a>
            </li>
            @endif

            <!-- Sales History -->
            @if(!$u || $u->canAccess('sales'))
            <li class="drawer-nav-item">
                <a href="{{ route('sales.index') }}" 
                   class="drawer-nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">receipt_long</span>
                    <span>Sales History</span>
                </a>
            </li>
            @endif

            <!-- Returns & Refunds -->
            @if(!$u || $u->canAccess('returns'))
            <li class="drawer-nav-item">
                <a href="{{ route('returns.index') }}" 
                   class="drawer-nav-link {{ request()->routeIs('returns.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">assignment_return</span>
                    <span>Returns & Refunds</span>
                </a>
            </li>
            @endif

            <!-- Cash Drawer -->
            @if(!$u || $u->canAccess('cash_drawer'))
            <li class="drawer-nav-item">
                <a href="{{ route('cash-drawer.index') }}" 
                   class="drawer-nav-link {{ request()->routeIs('cash-drawer.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">wallet</span>
                    <span>Cash Drawer</span>
                </a>
            </li>
            @endif

            {{-- ===== 3. INVENTORY ===== --}}
            @if(!$u || $u->canAccess('products') || $u->canAccess('gift_cards'))
            <li class="drawer-heading">Inventory</li>
            @endif

            <!-- Products -->
            @if(!$u || $u->canAccess('products'))
            <li class="drawer-nav-item">
                <a href="{{ route('products.index') }}" 
                   class="drawer-nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">inventory_2</span>
                    <span>Products</span>
                </a>
            </li>
            @endif

            <!-- Gift Cards -->
            @if(!$u || $u->canAccess('gift_cards'))
            <li class="drawer-nav-item">
                <a href="{{ route('gift-cards.index') }}" 
                   class="drawer-nav-link {{ request()->routeIs('gift-cards.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">redeem</span>
                    <span>Gift Cards</span>
                </a>
            </li>
            @endif

            {{-- ===== 4. CUSTOMERS ===== --}}
            <li class="drawer-heading">Customers</li>
            <li class="drawer-nav-item">
                <a href="{{ route('customers.index') }}" 
                   class="drawer-nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">group</span>
                    <span>Customers</span>
                </a>
            </li>

            {{-- ===== 5. REPORTS ===== --}}
            @if(!$u || $u->canAccess('reports'))
            <li class="drawer-heading">Reports</li>
            <li class="drawer-nav-item">
                <a href="{{ route('reports.index') }}" 
                   class="drawer-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">analytics</span>
                    <span>Reports</span>
                </a>
            </li>
            @endif

            {{-- ===== 6. ADMIN ===== --}}
            @if(!$u || $u->isAdmin())
            <li class="drawer-heading">Admin</li>

            <!-- Staff Management -->
            <li class="drawer-nav-item">
                <a href="{{ route('staff.index') }}" 
                   class="drawer-nav-link {{ request()->routeIs('staff.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">badge</span>
                    <span>Staff Management</span>
                </a>
            </li>

            <!-- Roles & Permissions -->
            <li class="drawer-nav-item">
                <a href="{{ route('roles.index') }}" 
                   class="drawer-nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">shield_person</span>
                    <span>Roles & Permissions</span>
                </a>
            </li>

            <!-- Settings -->
            <li class="drawer-nav-item">
                <a href="{{ route('settings.index') }}" 
                   class="drawer-nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">settings</span>
                    <span>Settings</span>
                </a>
            </li>
            @endif

            <li class="my-2 border-t border-surface-muted list-none"></li>

            <!-- Logout -->
            <li class="drawer-nav-item">
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="drawer-nav-link w-full border-0 bg-transparent text-left cursor-pointer text-error hover:bg-surface-high">
                        <span class="material-symbols-outlined text-error">logout</span>
                        <span>Logout</span>
                    </button>
                </form>
            </li>
        </ul>
    </div>
</aside>