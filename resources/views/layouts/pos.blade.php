<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Clothing Outlet POS') - {{ config('app.name', 'Clothing Outlet POS') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts: Inter & Material Symbols Outlined -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Vite (Tailwind CSS + JS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Custom POS Tokens & Theme CSS -->
    <link href="{{ asset('css/pos-tokens.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="h-screen w-screen overflow-hidden bg-background text-on-surface font-sans flex flex-col m-0 p-0">
    <!-- Top Navbar -->
    @include('partials.navbar')

    <!-- POS App Container -->
    <div id="pos-app" class="flex-1 flex overflow-hidden min-h-0 w-full">
        <!-- Global Icon Rail (88px) -->
        @include('partials.pos-rail')

        <!-- Collapsible Sidebar Panel (Drawer Overlay) -->
        @include('partials.sidebar')

        <!-- Page Content -->
        <main id="page-content-wrapper" class="flex-1 min-w-0 overflow-y-auto overflow-x-hidden bg-background p-6">
            @yield('content')
        </main>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js (for reports) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    @stack('scripts')

    <!-- Drawer Toggle Script -->
    <script>
    function posSidebarToggle() {
        const p = document.getElementById('pos-sidebar-panel');
        const o = document.getElementById('pos-sidebar-overlay');
        if (!p) return;
        if (p.classList.contains('sidebar-open')) {
            posSidebarClose();
        } else {
            posSidebarOpen();
        }
    }

    function posSidebarOpen() {
        const p = document.getElementById('pos-sidebar-panel');
        const o = document.getElementById('pos-sidebar-overlay');
        if (p) p.classList.add('sidebar-open');
        if (o) o.classList.add('visible');
    }

    function posSidebarClose() {
        const p = document.getElementById('pos-sidebar-panel');
        const o = document.getElementById('pos-sidebar-overlay');
        if (p) p.classList.remove('sidebar-open');
        if (o) o.classList.remove('visible');
    }

    // Bind navbar toggle button
    const navbarToggleBtn = document.getElementById('sidebarToggle');
    if (navbarToggleBtn) {
        navbarToggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            posSidebarToggle();
        });
    }

    // Close with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') posSidebarClose();
    });
    </script>

    {{-- ===== Global Shopify Auto-Sync ===== --}}
    <script>
    (function () {
        const SYNC_URL         = '{{ route('shopify.sync.all') }}';
        const COLLECTIONS_URL  = '{{ route('shopify.fast.category.sync') }}';
        const STATUS_URL       = '{{ route('shopify.sync.status') }}';
        const CSRF             = document.querySelector('meta[name="csrf-token"]')?.content;
        const AUTO_SYNC_EVERY  = 15 * 60 * 1000; // 15 minutes

        const btn    = document.getElementById('global-sync-btn');
        const icon   = document.getElementById('sync-icon');
        const dot    = document.getElementById('sync-status-dot');

        if (!CSRF) return;

        function setDot(state) {
            const map = { idle:'#6c757d', syncing:'#ffc107', ok:'#28a745', error:'#dc3545' };
            if (dot) dot.style.background = map[state] || map.idle;
        }

        function formatAgo(isoString) {
            if (!isoString) return 'never';
            const diff = Math.floor((Date.now() - new Date(isoString)) / 1000);
            if (diff < 60)  return diff + 's ago';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            return Math.floor(diff / 3600) + 'h ago';
        }

        async function runSync(manual) {
            if (btn) btn.disabled = true;
            if (icon) icon.className = 'fas fa-spinner fa-spin';
            setDot('syncing');
            if (manual && btn) btn.title = 'Syncing…';

            let productOk = false, productMsg = '', collOk = false;

            try {
                const resp = await fetch(SYNC_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                });
                const data = await resp.json();
                if (data.disabled) {
                    setDot('idle');
                    if (dot) dot.title = data.message || 'This feature is not enabled yet.';
                    if (icon) icon.className = 'fab fa-shopify';
                    if (btn) { btn.disabled = false; btn.title = 'Sync Products & Collections from Shopify'; }
                    if (manual) showSyncToast(data.message || 'This feature is not enabled yet.', 'error');
                    return;
                }
                productOk  = data.success;
                productMsg = data.message;
            } catch (e) {
                productMsg = 'Products synced (timeout) — refreshing categories…';
            }

            try {
                const resp2 = await fetch(COLLECTIONS_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                });
                const data2 = await resp2.json();
                collOk = data2.success;
            } catch (e) { /* silent */ }

            if (productOk || collOk) {
                setDot('ok');
                if (dot) dot.title = 'Last sync: just now — ' + (productMsg || 'Collections refreshed');
                if (manual) showSyncToast(productMsg || 'Sync complete', 'success');
            } else {
                setDot('error');
                if (dot) dot.title = 'Sync error: ' + (productMsg || 'Failed');
                if (manual) showSyncToast('Sync failed: ' + (productMsg || 'Please try again'), 'error');
            }

            if (icon) icon.className = 'fab fa-shopify';
            if (btn)  { btn.disabled = false; btn.title = 'Sync Products & Collections from Shopify'; }
        }

        async function loadStatus() {
            try {
                const resp = await fetch(STATUS_URL, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
                const data = await resp.json();
                if (data.last_synced_at) {
                    setDot('ok');
                    if (dot) dot.title = 'Last sync: ' + formatAgo(data.last_synced_at);
                } else {
                    setDot('idle');
                    if (dot) dot.title = 'Never synced';
                }
            } catch { setDot('idle'); }
        }

        function showSyncToast(msg, type) {
            const t = document.createElement('div');
            t.className = 'position-fixed top-0 end-0 m-3 p-3 rounded shadow-lg text-white';
            t.style.cssText = 'z-index:9999;min-width:260px;font-size:.85rem;background:' + (type === 'success' ? '#198754' : '#dc3545');
            t.innerHTML = `<i class="fab fa-shopify me-2"></i>${msg}`;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 4000);
        }

        window.triggerGlobalSync = () => runSync(true);
        loadStatus();
        setInterval(() => runSync(false), AUTO_SYNC_EVERY);
    })();
    </script>
</body>
</html>
