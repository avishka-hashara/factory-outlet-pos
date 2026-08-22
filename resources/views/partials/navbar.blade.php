<!-- ============================================
     Lumen Terminal - Top Navigation Bar
     Exactly 64px height, Modern-Industrial Dark
     ============================================ -->
<header class="pos-navbar w-full h-[64px] min-h-[64px] max-h-[64px] bg-surface border-b border-surface-muted px-6 flex items-center justify-between flex-shrink-0 z-30 select-none">
    
    <!-- 1. BRAND (Left Zone) -->
    <div class="flex items-center flex-shrink-0 gap-4">
        <!-- Hamburger: 40x40, transparent, hover:bg-surface-high, icon: on-surface-variant -->
        <button id="sidebarToggle" type="button" class="w-10 h-10 flex items-center justify-center rounded bg-transparent hover:bg-surface-high text-on-surface-variant transition-colors cursor-pointer border-0 p-0" title="Toggle Navigation">
            <span class="material-symbols-outlined text-[24px]">menu</span>
        </button>

        <!-- Wordmark + Subtitle -->
        <div class="flex items-baseline gap-4">
            <a href="{{ route('dashboard') }}" class="text-[24px] leading-[32px] font-[900] tracking-tight hover:opacity-90 transition-opacity no-underline" style="color: var(--primary) !important;">
                {{ \App\Models\Setting::get('app_name', config('app.name', 'LARAVEL')) }}
            </a>
            <span class="text-on-surface-variant text-[14px] leading-5 font-normal tracking-normal">
                Branch 01
            </span>
        </div>
    </div>

    <!-- 2. SEARCH (Center Zone, flex-1, max-w-640px) -->
    @if(request()->routeIs('pos.*') || request()->is('pos*'))
    <div class="flex-1 max-w-[640px] min-w-[280px] mx-4 lg:mx-8 flex items-center gap-2">
        <div class="relative flex-1 h-10">
            <!-- Barcode Scanner Icon -->
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px] pointer-events-none">
                barcode_scanner
            </span>

            <!-- Search Input -->
            <input type="text"
                   id="product-search"
                   placeholder="Scan Barcode or Search SKU..."
                   autocomplete="off"
                   onfocus="handleSearchDropdown()"
                   onblur="setTimeout(() => { const el = document.getElementById('product-search-dropdown'); if (el) el.style.display = 'none'; }, 200)"
                   class="w-full h-10 bg-surface-muted border border-border-strong rounded text-on-surface placeholder:text-on-surface-variant text-[14px] pl-10 pr-3 focus:border-primary focus:outline-none focus:ring-0 transition-colors">

            <!-- Live Search Dropdown -->
            <div id="product-search-dropdown" class="hidden absolute top-full left-0 right-0 mt-1 z-50 flex-col max-h-96 overflow-y-auto bg-surface border border-surface-muted rounded shadow-none">
            </div>
        </div>

        <!-- Amber Add Button: 40x40, rounded 8px, bg-primary, icon on-primary -->
        <button type="button"
                id="billing-add-btn"
                class="w-10 h-10 flex-shrink-0 flex items-center justify-center rounded hover:opacity-90 transition-opacity cursor-pointer border-0"
                style="background-color: var(--primary) !important; color: var(--on-primary) !important;"
                title="Add custom item"
                data-bs-toggle="modal"
                data-bs-target="#quickAddProductModal">
            <span class="material-symbols-outlined text-[20px] font-bold" style="color: var(--on-primary) !important;">add</span>
        </button>
    </div>
    @else
    <div class="flex-1"></div>
    @endif

    <!-- 3. STATUS CLUSTER (Right Zone, borderless text, 24px gaps) -->
    <div class="flex items-center gap-6 flex-shrink-0">
        
        <!-- a. Live clock, HH:MM:SS 24-hour, tabular-nums -->
        <span id="pos-live-clock" class="text-[14px] font-mono tabular-nums text-on-surface-variant whitespace-nowrap">
            {{ now()->format('H:i:s') }}
        </span>

        <!-- b. Date -->
        <span class="hidden md:inline-block text-[14px] text-on-surface-variant tabular-nums whitespace-nowrap" id="current-date">
            {{ now()->format('D, M d Y') }}
        </span>

        <!-- e. Notification Bell: 40x40, transparent, hover:bg-surface-high -->
        <div class="dropdown relative">
            <button class="w-10 h-10 flex items-center justify-center rounded bg-transparent hover:bg-surface-high text-on-surface-variant transition-colors cursor-pointer border-0 p-0 relative" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                <span class="material-symbols-outlined text-[22px]">notifications</span>
                @if(($notifCount ?? 0) > 0)
                    <!-- 8px error-solid dot at top-right, no number -->
                    <span class="w-2 h-2 rounded-full bg-error-solid absolute top-2.5 right-2.5"></span>
                @endif
            </button>

            <!-- Notification Dropdown Menu -->
            <div class="dropdown-menu dropdown-menu-end bg-surface border border-surface-muted rounded p-0 overflow-hidden w-80 shadow-none z-50">
                <div class="px-4 py-2.5 border-b border-surface-muted flex justify-between items-center bg-surface-muted">
                    <span class="text-[13px] font-semibold text-on-surface uppercase tracking-wider">Notifications</span>
                    @if(($notifCount ?? 0) > 0)
                        <span class="text-[11px] px-2 py-0.5 rounded bg-error-solid text-on-surface font-bold">{{ $notifCount }}</span>
                    @endif
                </div>

                @php $hasAny = false; @endphp

                {{-- Out of Stock --}}
                @if(!empty($outOfStock) && $outOfStock->count())
                    @php $hasAny = true; @endphp
                    <div class="px-4 pt-2 pb-1 text-[11px] uppercase tracking-wider text-on-surface-variant font-bold">Out of Stock</div>
                    @foreach($outOfStock as $p)
                        <a class="dropdown-item flex items-center px-4 py-2 text-on-surface hover:bg-surface-high text-[13px] no-underline border-b border-surface-muted/30" href="{{ route('products.index', ['stock' => 'out']) }}">
                            <span class="material-symbols-outlined text-error text-[18px] mr-2">error</span>
                            <span class="flex-grow truncate">{{ Str::limit($p->name, 28) }}</span>
                            <span class="text-error font-mono tabular-nums text-[12px] ml-2">0 left</span>
                        </a>
                    @endforeach
                @endif

                {{-- Low Stock --}}
                @if(!empty($lowStock) && $lowStock->count())
                    @php $hasAny = true; @endphp
                    <div class="px-4 pt-2 pb-1 text-[11px] uppercase tracking-wider text-on-surface-variant font-bold">Low Stock</div>
                    @foreach($lowStock as $p)
                        <a class="dropdown-item flex items-center px-4 py-2 text-on-surface hover:bg-surface-high text-[13px] no-underline border-b border-surface-muted/30" href="{{ route('products.index', ['stock' => 'low']) }}">
                            <span class="material-symbols-outlined text-primary text-[18px] mr-2">warning</span>
                            <span class="flex-grow truncate">{{ Str::limit($p->name, 28) }}</span>
                            <span class="text-primary font-mono tabular-nums text-[12px] ml-2">{{ $p->stock }} left</span>
                        </a>
                    @endforeach
                @endif

                {{-- Recent Sales --}}
                @if(!empty($recentSales) && $recentSales->count())
                    @php $hasAny = true; @endphp
                    <div class="px-4 pt-2 pb-1 text-[11px] uppercase tracking-wider text-on-surface-variant font-bold">Recent Sales (24h)</div>
                    @foreach($recentSales as $sale)
                        <a class="dropdown-item flex items-center px-4 py-2 text-on-surface hover:bg-surface-high text-[13px] no-underline border-b border-surface-muted/30" href="{{ route('sales.index') }}">
                            <span class="material-symbols-outlined text-success text-[18px] mr-2">receipt</span>
                            <span class="flex-grow truncate">#{{ $sale->invoice_number ?? str_pad($sale->id, 4, '0', STR_PAD_LEFT) }}</span>
                            <span class="text-success font-mono tabular-nums text-[12px] ml-2">Rs. {{ number_format($sale->total, 2) }}</span>
                        </a>
                    @endforeach
                @endif

                @if(!$hasAny)
                    <div class="text-center py-6 text-on-surface-variant">
                        <span class="material-symbols-outlined text-success text-[32px] block mb-1">check_circle</span>
                        <div class="text-[13px]">All clear — no alerts</div>
                    </div>
                @endif

                <div class="border-t border-surface-muted p-2">
                    <a class="block text-center text-primary text-[12px] font-semibold hover:underline no-underline py-1" href="{{ route('products.index', ['stock' => 'low']) }}">
                        View Stock Alerts
                    </a>
                </div>
            </div>
        </div>

        <!-- f. Vertical Divider: 1px wide, 24px tall, var(--surface-muted) -->
        <div class="w-[1px] h-6 bg-surface-muted flex-shrink-0"></div>

        <!-- g. User Block: two stacked lines (right-aligned) + Avatar to the right (12px gap) -->
        <div class="flex items-center gap-3 flex-shrink-0">
            <div class="text-right leading-tight">
                <div class="text-[14px] font-semibold text-on-surface truncate max-w-[140px]">
                    Cashier: {{ Auth::user()->name ?? 'Admin' }}
                </div>
                <div class="hidden md:block text-[12px] text-on-surface-variant truncate">
                    {{ ucfirst(Auth::user()->role ?? 'Manager') }}
                </div>
            </div>

            <!-- Avatar: 32x32, border-radius full, object-cover, 1px surface-muted border -->
            @if(!empty(Auth::user()->avatar))
                <img src="{{ asset(Auth::user()->avatar) }}" alt="Avatar" class="w-8 h-8 rounded-full object-cover border border-surface-muted flex-shrink-0">
            @else
                <div class="w-8 h-8 rounded-full border border-surface-muted bg-surface-muted text-on-surface flex items-center justify-center text-[12px] font-semibold flex-shrink-0">
                    {{ strtoupper(substr(Auth::user()->name ?? 'AD', 0, 2)) }}
                </div>
            @endif
        </div>

        <!-- h. Settings Gear: 40x40 transparent, 8px gap after avatar -->
        <div class="dropdown relative -ml-4 flex-shrink-0">
            <button class="w-10 h-10 flex items-center justify-center rounded bg-transparent hover:bg-surface-high text-on-surface-variant transition-colors cursor-pointer border-0 p-0" data-bs-toggle="dropdown" aria-expanded="false" title="Account Settings">
                <span class="material-symbols-outlined text-[22px]">settings</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end bg-surface border border-surface-muted rounded p-1 shadow-none min-w-[180px] z-50">
                <li class="px-3 py-2 border-b border-surface-muted">
                    <div class="text-[13px] font-semibold text-on-surface">{{ Auth::user()->name ?? 'Admin' }}</div>
                    <div class="text-[11px] text-on-surface-variant truncate">{{ Auth::user()->email ?? 'admin@store.com' }}</div>
                </li>
                <li>
                    <a class="dropdown-item flex items-center px-3 py-2 text-on-surface hover:bg-surface-high rounded text-[13px] no-underline" href="{{ route('profile.edit') }}">
                        <span class="material-symbols-outlined text-[18px] mr-2">person</span> Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item flex items-center px-3 py-2 text-on-surface hover:bg-surface-high rounded text-[13px] no-underline" href="{{ route('settings.index') }}">
                        <span class="material-symbols-outlined text-[18px] mr-2">tune</span> Settings
                    </a>
                </li>
                <li class="border-t border-surface-muted my-1"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item w-full flex items-center px-3 py-2 text-error-solid hover:bg-surface-high rounded text-[13px] border-0 bg-transparent cursor-pointer">
                            <span class="material-symbols-outlined text-[18px] mr-2">logout</span> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>

    </div>
</header>

<!-- Live Clock Script -->
<script>
(function() {
    function updateClock() {
        const clockEl = document.getElementById('pos-live-clock');
        if (clockEl) {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            clockEl.textContent = `${h}:${m}:${s}`;
        }
    }
    setInterval(updateClock, 1000);
})();
</script>
