<!-- ============================================
     Left Sidebar - Jewellery POS System
     ============================================ -->
<div id="sidebar" class="pos-sidebar">
    <div class="sidebar-inner">
        <!-- Mobile Header with Close Button -->
        <div class="sidebar-mobile-header d-lg-none">
            <div class="d-flex align-items-center">
                <img src="{{ !empty($systemLogo) ? asset($systemLogo) : asset('images/bc-logo.png') }}" alt="Logo" style="max-width:32px;height:auto;filter:drop-shadow(0 2px 4px rgba(245,166,35,0.2));" class="me-2">
                <span style="font-family:'Inter',sans-serif;font-size:1.05rem;font-weight:700;background:linear-gradient(135deg,#F7B84D,#FCCF6E);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">{{ \App\Models\Setting::get('app_name', config('app.name', 'Clothing Outlet POS')) }}</span>
            </div>
            <button class="sidebar-close-btn" id="sidebarClose" aria-label="Close sidebar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <hr class="sidebar-divider mx-3 opacity-25 mt-2">

        <!-- Navigation Menu -->
        {{-- Grouped navigation. Section headers are visual labels only (not
             links). Each item keeps its exact route, icon, label and permission
             gate; a group's header is only shown when at least one of its items
             is visible to the current user. --}}
        @php $u = auth()->user(); @endphp
        <ul class="nav flex-column px-2">

            {{-- ===== MAIN ===== --}}
            @if($u?->canAccess('dashboard') || $u?->canAccess('pos'))
            <li class="sidebar-heading"><span class="nav-text">Main</span></li>
            @endif

            <!-- Dashboard -->
            @if(auth()->user()?->canAccess('dashboard'))
            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    href="{{ route('dashboard') }}">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            @endif

            <!-- POS Billing -->
            @if(auth()->user()?->canAccess('pos'))
            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('pos.index') ? 'active' : '' }}"
                    href="{{ route('pos.index') }}">
                    <i class="fas fa-cash-register me-2"></i>
                    <span class="nav-text">POS Billing</span>
                </a>
            </li>
            @endif

            {{-- ===== SALES ===== --}}
            @if($u?->canAccess('pos_log') || $u?->canAccess('sales') || $u?->canAccess('returns') || $u?->canAccess('cash_drawer'))
            <li class="sidebar-heading"><span class="nav-text">Sales</span></li>
            @endif

            <!-- POS Sales Log -->
            @if(auth()->user()?->canAccess('pos_log'))
                <li class="nav-item mb-1">
                    <a class="nav-link {{ request()->routeIs('pos.log') ? 'active' : '' }}" href="{{ route('pos.log') }}">
                        <i class="fas fa-store me-2"></i>
                        <span class="nav-text">POS Sales Log</span>
                    </a>
                </li>
            @endif

            <!-- Sales History -->
            @if(auth()->user()?->canAccess('sales'))
            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}"
                    href="{{ route('sales.index') }}">
                    <i class="fas fa-receipt me-2"></i>
                    <span class="nav-text">Sales History</span>
                </a>
            </li>
            @endif

            <!-- Returns & Refunds -->
            @if(auth()->user()?->canAccess('returns'))
            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('returns.*') ? 'active' : '' }}"
                    href="{{ route('returns.index') }}">
                    <i class="fas fa-undo-alt me-2"></i>
                    <span class="nav-text">Returns & Refunds</span>
                </a>
            </li>
            @endif

            <!-- Cash Drawer -->
            @if(auth()->user()?->canAccess('cash_drawer'))
            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('cash-drawer.*') ? 'active' : '' }}"
                    href="{{ route('cash-drawer.index') }}">
                    <i class="fas fa-cash-register me-2"></i>
                    <span class="nav-text">Cash Drawer</span>
                </a>
            </li>
            @endif

            {{-- ===== INVENTORY ===== --}}
            @if($u?->canAccess('products') || $u?->canAccess('gift_cards'))
            <li class="sidebar-heading"><span class="nav-text">Inventory</span></li>
            @endif

            <!-- Products -->
            @if(auth()->user()?->canAccess('products'))
            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}"
                    href="{{ route('products.index') }}">
                    <i class="fas fa-tshirt me-2"></i>
                    <span class="nav-text">Products</span>
                </a>
            </li>
            @endif

            <!-- Gift Cards (admin/manager) -->
            @if(auth()->user()?->canAccess('gift_cards'))
            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('gift-cards.*') ? 'active' : '' }}"
                    href="{{ route('gift-cards.index') }}">
                    <i class="fas fa-gift me-2"></i>
                    <span class="nav-text">Gift Cards</span>
                </a>
            </li>
            @endif

            {{-- ===== CUSTOMERS ===== --}}
            <li class="sidebar-heading"><span class="nav-text">Customers</span></li>

            <!-- Customers -->
            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}"
                    href="{{ route('customers.index') }}">
                    <i class="fas fa-users me-2"></i>
                    <span class="nav-text">Customers</span>
                </a>
            </li>

            {{-- ===== REPORTS ===== --}}
            @if(auth()->user()?->canAccess('reports'))
            <li class="sidebar-heading"><span class="nav-text">Reports</span></li>

            <!-- Reports -->
            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                    href="{{ route('reports.index') }}">
                    <i class="fas fa-chart-bar me-2"></i>
                    <span class="nav-text">Reports</span>
                </a>
            </li>
            @endif

            {{-- ===== ADMIN ===== --}}
            @if(auth()->user()?->isAdmin())
            <li class="sidebar-heading"><span class="nav-text">Admin</span></li>

            <!-- Staff Management -->
            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('staff.*') ? 'active' : '' }}"
                    href="{{ route('staff.index') }}">
                    <i class="fas fa-users me-2"></i>
                    <span class="nav-text">Staff</span>
                </a>
            </li>

            <!-- Roles & Permissions -->
            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}"
                    href="{{ route('roles.index') }}">
                    <i class="fas fa-user-shield me-2"></i>
                    <span class="nav-text">Roles & Permissions</span>
                </a>
            </li>

            <!-- Settings -->
            <li class="nav-item mb-1">
                <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}"
                    href="{{ route('settings.index') }}">
                    <i class="fas fa-cog me-2"></i>
                    <span class="nav-text">Settings</span>
                </a>
            </li>
            @endif

            <hr class="sidebar-divider mx-2 opacity-25">

            <!-- Logout -->
            <li class="nav-item mb-1">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        <span class="nav-text">Logout</span>
                    </button>
                </form>
            </li>
        </ul>
    </div>
</div>
<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>