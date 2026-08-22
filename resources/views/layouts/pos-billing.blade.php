<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'POS Billing') - {{ config('app.name', 'Clothing Outlet POS') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts: Inter & Material Symbols Outlined -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Vite (Tailwind CSS + JS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Custom POS Tokens & Theme CSS -->
    <link href="{{ asset('css/pos-theme.css') }}" rel="stylesheet">
    <link href="{{ asset('css/pos-tokens.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="h-screen w-screen overflow-hidden bg-background text-on-surface font-sans">
    <!-- Top Navbar (reuse existing partial) -->
    @include('partials.navbar')

    <!-- POS App Container -->
    <div id="pos-app">
        <!-- Left Icon Rail (~88px) -->
        <nav id="pos-icon-rail" aria-label="POS Navigation Rail">
            <div class="rail-nav">
                @php $u = auth()->user(); @endphp

                <!-- Sales / POS (Active) -->
                @if($u?->canAccess('pos'))
                <a href="{{ route('pos.index') }}" class="rail-item {{ request()->routeIs('pos.*') ? 'active' : '' }}" title="Sales / POS">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Sales</span>
                </a>
                @endif

                <!-- Returns -->
                @if($u?->canAccess('returns'))
                <a href="{{ route('returns.index') }}" class="rail-item {{ request()->routeIs('returns.*') ? 'active' : '' }}" title="Returns & Refunds">
                    <i class="fas fa-undo-alt"></i>
                    <span>Returns</span>
                </a>
                @endif

                <!-- Stock / Products -->
                @if($u?->canAccess('products'))
                <a href="{{ route('products.index') }}" class="rail-item {{ request()->routeIs('products.*') ? 'active' : '' }}" title="Stock & Products">
                    <i class="fas fa-box-open"></i>
                    <span>Stock</span>
                </a>
                @endif

                <!-- Orders / Sales Log -->
                @if($u?->canAccess('pos_log') || $u?->canAccess('sales'))
                <a href="{{ route('pos.log') }}" class="rail-item {{ request()->routeIs('pos.log') || request()->routeIs('sales.*') ? 'active' : '' }}" title="Orders & Sales Log">
                    <i class="fas fa-receipt"></i>
                    <span>Orders</span>
                </a>
                @endif

                <!-- Reports -->
                @if($u?->canAccess('reports'))
                <a href="{{ route('reports.index') }}" class="rail-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" title="Reports">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                @endif

                <!-- Cash Drawer -->
                @if($u?->canAccess('cash_drawer'))
                <a href="{{ route('cash-drawer.index') }}" class="rail-item {{ request()->routeIs('cash-drawer.*') ? 'active' : '' }}" title="Cash Drawer">
                    <i class="fas fa-wallet"></i>
                    <span>Drawer</span>
                </a>
                @endif

                <!-- Customers -->
                <a href="{{ route('customers.index') }}" class="rail-item {{ request()->routeIs('customers.*') ? 'active' : '' }}" title="Customers">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>

                <!-- Gift Cards -->
                @if($u?->canAccess('gift_cards'))
                <a href="{{ route('gift-cards.index') }}" class="rail-item {{ request()->routeIs('gift-cards.*') ? 'active' : '' }}" title="Gift Cards">
                    <i class="fas fa-gift"></i>
                    <span>Gift Cards</span>
                </a>
                @endif

                <!-- Dashboard -->
                @if($u?->canAccess('dashboard'))
                <a href="{{ route('dashboard') }}" class="rail-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                @endif
            </div>

            <!-- Bottom Pin: Logout -->
            <div class="rail-bottom-pin" id="pos-rail-logout">
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="rail-bottom-btn" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </nav>

        <!-- Sidebar dim overlay -->
        <div id="pos-sidebar-overlay" onclick="posSidebarClose()"></div>

        <!-- Collapsible Sidebar Panel (Drawer) -->
        <div id="pos-sidebar-panel">
            @include('partials.sidebar')
        </div>

        @yield('content')
    </div>

    <!-- Bottom Payment Bar -->
    @yield('paybar')

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @yield('modals')

    @stack('scripts')

    {{-- ===== Global Shopify Auto-Sync ===== --}}
    <script>
    (function () {
        const SYNC_URL         = '{{ route('shopify.sync.all') }}';
        const COLLECTIONS_URL  = '{{ route('shopify.fast.category.sync') }}';
        const STATUS_URL       = '{{ route('shopify.sync.status') }}';
        const CSRF             = document.querySelector('meta[name="csrf-token"]').content;
        const AUTO_SYNC_EVERY  = 15 * 60 * 1000;

        const btn  = document.getElementById('global-sync-btn');
        const icon = document.getElementById('sync-icon');
        const dot  = document.getElementById('sync-status-dot');

        function setDot(state) {
            const map = { idle:'#6c757d', syncing:'#ffc107', ok:'#28a745', error:'#dc3545' };
            if (dot) dot.style.background = map[state] || map.idle;
        }

        function formatAgo(isoString) {
            if (!isoString) return 'never';
            const diff = Math.floor((Date.now() - new Date(isoString)) / 1000);
            if (diff < 60) return diff + 's ago';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            return Math.floor(diff / 3600) + 'h ago';
        }

        async function runSync(manual) {
            if (btn) btn.disabled = true;
            if (icon) icon.className = 'fas fa-spinner fa-spin';
            setDot('syncing');

            let productOk = false, productMsg = '', collOk = false;
            try {
                const resp = await fetch(SYNC_URL, { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF} });
                const data = await resp.json();
                // Shopify integration is turned off — show a plain message, run no sync.
                if (data.disabled) {
                    setDot('idle');
                    if (dot) dot.title = data.message || 'This feature is not enabled yet.';
                    if (icon) icon.className = 'fab fa-shopify';
                    if (btn) { btn.disabled = false; }
                    if (manual) showSyncToast(data.message || 'This feature is not enabled yet.', 'error');
                    return;
                }
                productOk = data.success; productMsg = data.message;
            } catch(e) { productMsg = 'Products synced (timeout)'; }

            try {
                const resp2 = await fetch(COLLECTIONS_URL, { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF} });
                const data2 = await resp2.json();
                collOk = data2.success;
            } catch(e) {}

            if (productOk || collOk) {
                setDot('ok');
                if (dot) dot.title = 'Last sync: just now';
                if (manual) showSyncToast(productMsg || 'Sync complete', 'success');
            } else {
                setDot('error');
                if (manual) showSyncToast('Sync failed', 'error');
            }
            if (icon) icon.className = 'fab fa-shopify';
            if (btn) { btn.disabled = false; }
        }

        async function loadStatus() {
            try {
                const resp = await fetch(STATUS_URL, { headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF} });
                const data = await resp.json();
                if (data.last_synced_at) { setDot('ok'); if(dot) dot.title='Last sync: '+formatAgo(data.last_synced_at); }
                else { setDot('idle'); }
            } catch { setDot('idle'); }
        }

        function showSyncToast(msg, type) {
            const t = document.createElement('div');
            t.className = 'position-fixed top-0 end-0 m-3 p-3 rounded shadow-lg text-white';
            t.style.cssText = 'z-index:9999;min-width:260px;font-size:.85rem;background:'+(type==='success'?'#198754':'#dc3545');
            t.innerHTML = `<i class="fab fa-shopify me-2"></i>${msg}`;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 4000);
        }

        window.triggerGlobalSync = () => runSync(true);
        loadStatus();
        setInterval(() => runSync(false), AUTO_SYNC_EVERY);
    })();

    // ===== POS SIDEBAR TOGGLE =====
    const posSidebarPanel   = document.getElementById('pos-sidebar-panel');
    const posSidebarOverlay = document.getElementById('pos-sidebar-overlay');
    const navbarToggleBtn   = document.getElementById('sidebarToggle');

    // Bind navbar toggle button to the POS sidebar logic
    if (navbarToggleBtn) {
        navbarToggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            posSidebarToggle();
        });
    }

    // Restore state from sessionStorage
    if (sessionStorage.getItem('pos_sidebar_open') === '1') {
        posSidebarPanel.classList.add('sidebar-open');
        posSidebarOverlay.classList.add('visible');
    }

    function posSidebarToggle() {
        const isOpen = posSidebarPanel.classList.contains('sidebar-open');
        if (isOpen) {
            posSidebarClose();
        } else {
            posSidebarOpen();
        }
    }

    function posSidebarOpen() {
        posSidebarPanel.classList.add('sidebar-open');
        posSidebarOverlay.classList.add('visible');
        sessionStorage.setItem('pos_sidebar_open', '1');
    }

    function posSidebarClose() {
        posSidebarPanel.classList.remove('sidebar-open');
        posSidebarOverlay.classList.remove('visible');
        sessionStorage.setItem('pos_sidebar_open', '0');
    }

    // Close with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') posSidebarClose();
    });

    // Active nav link highlight inside sidebar panel
    document.querySelectorAll('#pos-sidebar-panel .nav-link').forEach(link => {
        if (link.href === window.location.href) link.classList.add('active');
    });
    </script>
</body>
</html>
