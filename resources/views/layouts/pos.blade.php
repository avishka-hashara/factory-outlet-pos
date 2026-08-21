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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom POS Theme CSS -->
    <link href="{{ asset('css/pos-theme.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <!-- Top Navbar -->
    @include('partials.navbar')

    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        @include('partials.sidebar')

        <!-- Page Content -->
        <div id="page-content-wrapper" class="w-100">
            <div class="container-fluid px-4 py-4">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js (for reports) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        // Sidebar toggle for mobile
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.getElementById('sidebarClose');

        function openSidebar() {
            sidebar.classList.add('show');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }

        sidebarToggle?.addEventListener('click', function(e) {
            e.stopPropagation();
            if (window.innerWidth < 992) {
                if (sidebar.classList.contains('show')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            } else {
                sidebar.classList.toggle('collapsed');
                document.body.classList.toggle('sidebar-collapsed');
            }
        });

        sidebarClose?.addEventListener('click', closeSidebar);
        overlay?.addEventListener('click', closeSidebar);

        // Close sidebar when clicking a nav link on mobile
        if (window.innerWidth < 992) {
            document.querySelectorAll('#sidebar .nav-link').forEach(link => {
                link.addEventListener('click', function() {
                    closeSidebar();
                });
            });
        }

        // Active sidebar link
        document.querySelectorAll('#sidebar .nav-link').forEach(link => {
            if (link.href === window.location.href) {
                link.classList.add('active');
            }
        });
    </script>
    @stack('scripts')

    {{-- ===== Global Shopify Auto-Sync ===== --}}
    <script>
    (function () {
        const SYNC_URL         = '{{ route('shopify.sync.all') }}';
        const COLLECTIONS_URL  = '{{ route('shopify.fast.category.sync') }}';
        const STATUS_URL       = '{{ route('shopify.sync.status') }}';
        const CSRF             = document.querySelector('meta[name="csrf-token"]').content;
        const AUTO_SYNC_EVERY  = 15 * 60 * 1000; // 15 minutes

        const btn    = document.getElementById('global-sync-btn');
        const icon   = document.getElementById('sync-icon');
        const dot    = document.getElementById('sync-status-dot');

        function setDot(state) {
            // state: 'idle' | 'syncing' | 'ok' | 'error'
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

            // ── Step 1: sync products (may be slow on shared hosting) ──
            try {
                const resp = await fetch(SYNC_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                });
                const data = await resp.json();
                // Shopify integration is turned off — show a plain message, run no sync.
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
                // Network timeout on shared hosting — products may have synced partially
                productMsg = 'Products synced (timeout) — refreshing categories…';
            }

            // ── Step 2: ALWAYS sync collection-product links as a separate request ──
            // This guarantees category filtering works even when step 1 timed out on the host.
            try {
                const resp2 = await fetch(COLLECTIONS_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                });
                const data2 = await resp2.json();
                collOk = data2.success;
            } catch (e) { /* silent — best effort */ }

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

        // Fetch last-sync status on page load (just sets the dot colour, no network write)
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

        // Expose to onclick in navbar button
        window.triggerGlobalSync = () => runSync(true);

        // Kick off on load
        loadStatus();

        // Auto-sync every 15 minutes
        setInterval(() => runSync(false), AUTO_SYNC_EVERY);
    })();
    </script>
</body>
</html>
