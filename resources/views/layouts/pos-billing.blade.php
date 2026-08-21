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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom POS Theme CSS -->
    <link href="{{ asset('css/pos-theme.css') }}" rel="stylesheet">
    <style>
        /* ---- POS Billing Full-Screen Layout ---- */
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: #f0f0f0;
            font-family: 'Inter', sans-serif;
        }

        /* Navbar stays at top */
        .pos-navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1040;
            height: 60px;
        }

        /* Full-screen POS container below navbar */
        #pos-app {
            position: fixed;
            top: 60px; left: 0; right: 0; bottom: 70px; /* Increased bottom to match paybar height */
            display: flex;
            flex-direction: row;
            background: #ffffff;
            overflow: hidden;
        }

        /* ---- POS SIDEBAR OVERLAY ---- */
        #pos-sidebar-panel {
            position: absolute;
            top: 0; left: 0; bottom: 0;
            width: 250px;
            background: linear-gradient(180deg, #1B2A4A 0%, #000 100%);
            z-index: 200;
            transform: translateX(-250px);
            transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 4px 0 20px rgba(0,0,0,0.25);
            display: flex;
            flex-direction: column;
        }
        #pos-sidebar-panel.sidebar-open {
            transform: translateX(0);
        }
        /* Dim overlay behind sidebar */
        #pos-sidebar-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 199;
            display: none;
            opacity: 0;
            transition: opacity 0.25s ease;
        }
        #pos-sidebar-overlay.visible {
            display: block;
            opacity: 1;
        }
        /* Sidebar toggle button in the billing topbar */
        .pos-sidebar-toggle {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            color: #374151;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: background 0.15s, border-color 0.15s;
            font-size: 0.9rem;
        }
        .pos-sidebar-toggle:hover {
            background: #1B2A4A;
            border-color: #1B2A4A;
            color: #fff;
        }
        .pos-sidebar-toggle.sidebar-open {
            background: #1B2A4A;
            border-color: #1B2A4A;
            color: #fff;
        }
        /* Style the existing sidebar nav for use inside our overlay */
        #pos-sidebar-panel .pos-sidebar {
            position: static !important;
            width: 100% !important;
            height: 100% !important;
            top: auto !important;
            left: auto !important;
            background: transparent !important;
            box-shadow: none !important;
            overflow-y: auto;
            padding-bottom: 1rem;
        }

        /* ---- LEFT BILLING PANEL ---- */
        #pos-billing {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            flex-direction: column;
            background: #fff;
            border-right: 1px solid #e0e0e0;
            overflow: hidden;
        }

        /* Billing top bar: customer + search */
        #billing-topbar {
            flex-shrink: 0;
            padding: 10px 14px 8px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
        }

        .customer-selector {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 6px 10px;
            cursor: pointer;
            white-space: nowrap;
            font-size: 0.82rem;
            font-weight: 600;
            color: #374151;
            transition: border-color 0.2s;
        }
        .customer-selector:hover { border-color: #d4891a; }

        .billing-search-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0 12px;
            gap: 10px;
            height: 50px;
        }
        .billing-search-wrap:focus-within { border-color: #d4891a; box-shadow: 0 0 0 3px rgba(212,137,26,0.12); }
        .billing-search-wrap i { color: #9ca3af; font-size: 0.85rem; }
        .billing-search-wrap input {
            flex: 1;
            border: none;
            background: transparent;
            outline: none;
            font-size: 0.9rem;
            color: #374151;
            padding: 10px 0; /* Increased height */
        }
        .billing-search-wrap input::placeholder { color: #9ca3af; }

        .billing-add-btn {
            width: 50px; height: 50px;
            border-radius: 8px;
            background: linear-gradient(135deg, #f5a623, #d4891a);
            border: none;
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            font-size: 0.8rem;
        }

        /* Cart table */
        #billing-table-wrap {
            flex: 1 1 auto;
            overflow-y: auto;
            min-height: 0;
        }

        #cart-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }
        #cart-table thead tr {
            background: #fff;
            border-bottom: 2px solid #e5e7eb;
            position: sticky; top: 0; z-index: 1;
        }
        #cart-table thead th {
            padding: 14px 16px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        #cart-table thead th:last-child { text-align: center; }
        #cart-table tbody tr { border-bottom: 1px solid #f3f4f6; }
        #cart-table tbody tr:hover { background: #fffbf0; }
        #cart-table tbody td { padding: 12px 16px; vertical-align: middle; }

        #empty-cart-row td {
            height: calc(100vh - 350px);
            padding: 0 20px;
            text-align: center;
            color: #9ca3af;
            vertical-align: middle;
        }
        #empty-cart-row i { font-size: 3.5rem; color: #e5e7eb; display: block; margin-bottom: 15px; }
        #empty-cart-row small { font-size: 0.95rem; }

        /* Billing footer */
        #billing-footer {
            flex-shrink: 0;
            padding: 12px 18px;
            border-top: 1px solid #e5e7eb;
            background: #fafafa;
        }
        .billing-footer-row {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 8px;
            font-size: 0.95rem;
            color: #374151;
        }
        .billing-footer-row label { color: #6b7280; white-space: nowrap; }
        .billing-footer-row input, .billing-footer-row select {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 5px 10px;
            font-size: 0.95rem;
            color: #374151;
            background: #fff;
            outline: none;
        }
        .billing-footer-row input:focus, .billing-footer-row select:focus {
            border-color: #d4891a;
            box-shadow: 0 0 0 2px rgba(212,137,26,0.1);
        }
        .billing-footer-row strong { font-weight: 700; color: #1f2937; font-size: 1.05rem; }

        #billing-notes { margin-top: 8px; }
        #billing-notes textarea {
            width: 100%;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 0.9rem;
            resize: none;
            outline: none;
            background: #fff;
            color: #374151;
        }
        #billing-notes textarea:focus { border-color: #d4891a; }

        /* ---- RIGHT CATALOGUE PANEL ---- */
        #pos-catalogue {
            flex: 0 0 420px;
            width: 420px;
            display: flex;
            flex-direction: column;
            background: #f7f7f7;
            border-left: 1px solid #e0e0e0;
            overflow: hidden;
        }

        #catalogue-header {
            flex-shrink: 0;
            padding: 8px 10px 6px;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
        }

        .cat-filter-row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 6px;
        }

        .cat-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        .cat-pill {
            background: #f3f4f6;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 2px 10px;
            font-size: 0.7rem;
            font-weight: 600;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.15s;
        }
        .cat-pill:hover, .cat-pill.active {
            background: #f5a623;
            border-color: #f5a623;
            color: #fff;
        }

        #catalogue-grid-wrap {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 6px;
        }

        #product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 5px;
        }

        /* Compact product card */
        .prod-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            overflow: hidden;
            transition: border-color 0.15s, box-shadow 0.15s;
            display: flex;
            flex-direction: column;
        }
        .prod-card:hover {
            border-color: #f5a623;
            box-shadow: 0 2px 6px rgba(245,166,35,0.2);
        }
        .prod-card.out-of-stock { opacity: 0.5; cursor: not-allowed; }

        .prod-img {
            width: 100%;
            aspect-ratio: 1 / 1;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .prod-img img { width: 100%; height: 100%; object-fit: cover; }
        .prod-img i { font-size: 1.6rem; color: #d4891a; opacity: 0.4; }

        .prod-info {
            padding: 4px 5px 5px;
        }
        .prod-name {
            font-size: 0.67rem;
            font-weight: 600;
            color: #1f2937;
            line-height: 1.25;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .prod-sku {
            font-size: 0.6rem;
            color: #9ca3af;
            margin-top: 1px;
        }

        #no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
            font-size: 0.82rem;
        }

        /* ---- BOTTOM PAYMENT BAR ---- */
        #pos-paybar {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 70px; /* Increased height */
            background: #1b2a4a;
            display: flex;
            align-items: center;
            padding: 0 12px;
            gap: 6px;
            z-index: 1040;
            border-top: 2px solid rgba(245,166,35,0.35);
        }

        .paybar-btn {
            height: 46px; /* Increased height */
            border: none;
            border-radius: 23px; /* Adjusted radius */
            padding: 0 16px;
            font-size: 0.82rem; /* Increased font size */
            font-weight: 700;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            transition: opacity 0.15s, transform 0.15s;
            letter-spacing: 0.1px;
            flex-shrink: 0;
        }
        .paybar-btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .paybar-btn:active { transform: translateY(0); }
        .paybar-btn.active-pay {
            outline: 2px solid #fff;
            outline-offset: 2px;
        }
        .paybar-btn:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }

        /* Specific style to make Checkout bigger */
        #checkout-btn {
            height: 54px; /* Increased height */
            padding: 0 32px;
            font-size: 1rem; /* Increased font size */
            font-weight: 800;
            border-radius: 27px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 10px rgba(245, 166, 35, 0.4);
        }
        #checkout-btn:not(:disabled) {
            animation: pulse-glow 2s infinite;
        }
        @keyframes pulse-glow {
            0% { box-shadow: 0 0 0 0 rgba(245, 166, 35, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(245, 166, 35, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 166, 35, 0); }
        }

        .pb-orange  { background: #f97316; }
        .pb-red     { background: #ef4444; }
        .pb-blue    { background: #3b82f6; }
        .pb-multi   { background: #1f2937; border: 1px solid #6b7280; }
        .pb-purple  { background: #a855f7; }
        .pb-teal    { background: #14b8a6; }
        .pb-indigo  { background: #6366f1; }
        .pb-green   { background: #22c55e; }
        .pb-navy    { background: #1d4ed8; }
        .pb-gray    { background: #6b7280; }
        .pb-gold    { background: linear-gradient(135deg,#f5a623,#d4891a); }
        .pb-sky     { background: #0ea5e9; }

        /* Total Payable box */
        .paybar-total {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #0f172a;
            border: 1.5px solid #f5a623;
            border-radius: 8px;
            padding: 4px 14px;
            min-width: 90px;
            flex: 0 0 auto;
            white-space: nowrap;
        }
        .paybar-total-label {
            font-size: 0.6rem;
            color: rgba(255,255,255,0.65);
            font-weight: 600;
            line-height: 1.2;
            text-align: right;
        }
        .paybar-total-value {
            font-size: 1.35rem;
            font-weight: 800;
            color: #fccf6e;
            min-width: 30px;
            white-space: nowrap;
        }

        /* push total to right */
        .paybar-spacer { flex: 1; }

        /* Keep the complete payment row inside common laptop/desktop widths. */
        @media (max-width: 1920px) {
            #pos-paybar {
                padding-inline: 10px;
                gap: 5px;
            }
            .paybar-btn {
                padding-inline: 12px;
                font-size: 0.78rem;
            }
            #checkout-btn {
                padding-inline: 24px;
                font-size: 0.92rem;
            }
            .paybar-total {
                padding-inline: 10px;
                gap: 6px;
            }
            .paybar-total-value {
                font-size: 1.25rem;
            }
        }

        @media (max-width: 1600px) {
            #pos-paybar { gap: 4px; }
            .paybar-btn {
                padding-inline: 9px;
                font-size: 0.72rem;
            }
            #checkout-btn {
                padding-inline: 16px;
                font-size: 0.82rem;
            }
            .paybar-total-value { font-size: 1.1rem; }
        }

        /* Scrollbar styling */
        #billing-table-wrap::-webkit-scrollbar,
        #catalogue-grid-wrap::-webkit-scrollbar { width: 5px; }
        #billing-table-wrap::-webkit-scrollbar-track,
        #catalogue-grid-wrap::-webkit-scrollbar-track { background: transparent; }
        #billing-table-wrap::-webkit-scrollbar-thumb,
        #catalogue-grid-wrap::-webkit-scrollbar-thumb { background: #e0e0e0; border-radius: 3px; }
        #billing-table-wrap::-webkit-scrollbar-thumb:hover,
        #catalogue-grid-wrap::-webkit-scrollbar-thumb:hover { background: #f5a623; }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Top Navbar (reuse existing partial) -->
    @include('partials.navbar')

    <!-- POS App Container -->
    <div id="pos-app">
        <!-- Sidebar dim overlay -->
        <div id="pos-sidebar-overlay" onclick="posSidebarClose()"></div>

        <!-- Collapsible Sidebar Panel -->
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
