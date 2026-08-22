<!-- ============================================
     Lumen Terminal - Top Navigation Bar
     56px height, Modern-Industrial Dark (DESIGN.md / Mock Spec)
     ============================================ -->
<header class="pos-navbar w-full h-[56px] min-h-[56px] max-h-[56px] bg-surface border-b border-outline-variant px-lg flex items-center justify-between flex-shrink-0 z-40 select-none">
    
    <!-- 1. BRAND & BRANCH (Left Zone) -->
    <div class="flex items-center flex-shrink-0 gap-md">
        <!-- Hamburger / Compact Toggle -->
        <button id="sidebarToggle" type="button" class="w-8 h-8 flex items-center justify-center rounded border border-border-color bg-transparent hover:bg-hover-surface text-on-surface-variant hover:text-primary hover:border-primary transition-colors cursor-pointer p-0 active:scale-95" title="Toggle Navigation">
            <span class="material-symbols-outlined text-[18px]">menu</span>
        </button>

        <!-- Brand Name -->
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="font-headline-md text-headline-md font-bold tracking-tight text-on-surface hover:opacity-90 transition-opacity no-underline">
                {{ \App\Models\Setting::get('app_name', config('app.name', 'RETAIL CORE')) }}
            </a>
            <!-- Branch Badge -->
            <div class="hidden md:flex items-center gap-2 px-2 py-0.5 rounded border border-border-color bg-hover-surface">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]"></span>
                <span class="font-label-caps text-[9px] uppercase text-on-surface-variant">Main Branch</span>
            </div>
        </div>
    </div>

    <!-- 2. SEARCH (Center Zone) -->
    @if(request()->routeIs('pos.*') || request()->is('pos*'))
    <div class="flex-1 max-w-[540px] min-w-[240px] mx-4 flex items-center gap-2">
        <div class="relative flex-1 h-8">
            <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px] pointer-events-none">
                barcode_scanner
            </span>
            <input type="text"
                   id="product-search"
                   placeholder="Scan Barcode or Search SKU..."
                   autocomplete="off"
                   onfocus="handleSearchDropdown()"
                   onblur="setTimeout(() => { const el = document.getElementById('product-search-dropdown'); if (el) el.style.display = 'none'; }, 200)"
                   class="w-full h-8 bg-dark-bg border border-border-color rounded text-on-surface placeholder:text-on-surface-variant text-body-sm font-body-sm pl-8 pr-3 focus:outline-none focus:border-amber-accent focus:ring-1 focus:ring-amber-accent transition-colors">
            
            <!-- Live Search Dropdown -->
            <div id="product-search-dropdown" class="hidden absolute top-full left-0 right-0 mt-1 z-50 flex-col max-h-96 overflow-y-auto bg-surface border border-border-color rounded shadow-none">
            </div>
        </div>

        <!-- Amber Add Button: 32x32 -->
        <button type="button"
                id="billing-add-btn"
                class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded hover:opacity-90 transition-opacity cursor-pointer border-0 bg-primary-container text-black font-bold active:scale-95"
                title="Add custom item"
                data-bs-toggle="modal"
                data-bs-target="#quickAddProductModal">
            <span class="material-symbols-outlined text-[18px] font-bold text-black">add</span>
        </button>
    </div>
    @elseif(request()->routeIs('dashboard'))
    <div class="relative hidden lg:block">
        <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px] pointer-events-none">search</span>
        <input class="w-56 h-8 bg-dark-bg border border-border-color rounded pl-8 pr-3 text-on-surface placeholder:text-on-surface-variant focus:outline-none focus:border-amber-accent focus:ring-1 focus:ring-amber-accent transition-colors text-body-sm font-body-sm" placeholder="Search..." type="text" id="dashboard-global-search"/>
    </div>
    @else
    <div class="flex-1"></div>
    @endif

    <!-- 3. STATUS & USER CLUSTER (Right Zone) -->
    <div class="flex items-center gap-md flex-shrink-0">
        
        <!-- Live Clock & Date -->
        <div class="font-metric-sm text-metric-sm tabular-nums text-on-surface-variant hidden sm:block">
            <span id="pos-live-clock">{{ now()->format('H:i:s') }}</span> | {{ now()->format('d M') }}
        </div>

        <!-- Notification Bell -->
        <div class="dropdown relative">
            <button class="w-8 h-8 rounded border border-border-color flex items-center justify-center text-on-surface-variant hover:text-primary hover:border-primary transition-colors active:scale-95 cursor-pointer bg-transparent p-0 relative" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                <span class="material-symbols-outlined text-[18px]">notifications</span>
                @if(($notifCount ?? 0) > 0)
                    <span class="w-2 h-2 rounded-full bg-error absolute top-1.5 right-1.5"></span>
                @endif
            </button>

            <!-- Notification Dropdown Menu -->
            <div class="dropdown-menu dropdown-menu-end bg-card-surface border border-border-color rounded p-0 overflow-hidden w-80 shadow-none z-50">
                <div class="px-4 py-2.5 border-b border-border-color flex justify-between items-center bg-dark-bg">
                    <span class="font-label-caps text-[10px] font-semibold text-on-surface uppercase tracking-wider">Notifications</span>
                    @if(($notifCount ?? 0) > 0)
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-error text-on-error font-bold leading-none">{{ $notifCount }}</span>
                    @endif
                </div>

                @php $hasAny = false; @endphp

                {{-- Out of Stock --}}
                @if(!empty($outOfStock) && (is_countable($outOfStock) ? count($outOfStock) : 0))
                    @php $hasAny = true; @endphp
                    <div class="px-4 pt-2 pb-1 text-[10px] uppercase tracking-wider text-on-surface-variant font-bold">Out of Stock</div>
                    @foreach($outOfStock as $p)
                        <a class="dropdown-item flex items-center px-4 py-2 text-on-surface hover:bg-hover-surface text-body-sm no-underline border-b border-border-color/30" href="{{ route('products.index', ['stock' => 'out']) }}">
                            <span class="material-symbols-outlined text-error text-[18px] mr-2">error</span>
                            <span class="flex-grow truncate">{{ Str::limit($p->name, 26) }}</span>
                            <span class="text-error font-mono tabular-nums text-[12px] ml-2">0 left</span>
                        </a>
                    @endforeach
                @endif

                {{-- Low Stock --}}
                @if(!empty($lowStock) && (is_countable($lowStock) ? count($lowStock) : 0))
                    @php $hasAny = true; @endphp
                    <div class="px-4 pt-2 pb-1 text-[10px] uppercase tracking-wider text-on-surface-variant font-bold">Low Stock</div>
                    @foreach($lowStock as $p)
                        <a class="dropdown-item flex items-center px-4 py-2 text-on-surface hover:bg-hover-surface text-body-sm no-underline border-b border-border-color/30" href="{{ route('products.index', ['stock' => 'low']) }}">
                            <span class="material-symbols-outlined text-amber-accent text-[18px] mr-2">warning</span>
                            <span class="flex-grow truncate">{{ Str::limit($p->name, 26) }}</span>
                            <span class="text-amber-accent font-mono tabular-nums text-[12px] ml-2">{{ $p->stock }} left</span>
                        </a>
                    @endforeach
                @endif

                @if(!$hasAny)
                    <div class="text-center py-6 text-on-surface-variant">
                        <span class="material-symbols-outlined text-green-400 text-[28px] block mb-1">check_circle</span>
                        <div class="text-body-sm">All clear — no alerts</div>
                    </div>
                @endif

                <div class="border-t border-border-color p-2 bg-dark-bg/50">
                    <a class="block text-center text-primary font-label-caps text-[10px] uppercase font-semibold hover:underline no-underline py-1" href="{{ route('products.index', ['stock' => 'low']) }}">
                        View Stock Alerts
                    </a>
                </div>
            </div>
        </div>

        <!-- Settings Gear -->
        <div class="dropdown relative">
            <button class="w-8 h-8 rounded border border-border-color flex items-center justify-center text-on-surface-variant hover:text-primary hover:border-primary transition-colors active:scale-95 cursor-pointer bg-transparent p-0" data-bs-toggle="dropdown" aria-expanded="false" title="Account Settings">
                <span class="material-symbols-outlined text-[18px]">settings</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end bg-card-surface border border-border-color rounded p-1 shadow-none min-w-[180px] z-50">
                <li class="px-3 py-2 border-b border-border-color">
                    <div class="text-body-sm font-semibold text-on-surface">{{ Auth::user()->name ?? 'Admin' }}</div>
                    <div class="text-[11px] text-on-surface-variant truncate">{{ Auth::user()->email ?? 'admin@possystem.lk' }}</div>
                </li>
                <li>
                    <a class="dropdown-item flex items-center px-3 py-2 text-on-surface hover:bg-hover-surface rounded text-body-sm no-underline" href="{{ route('profile.edit') }}">
                        <span class="material-symbols-outlined text-[18px] mr-2">person</span> Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item flex items-center px-3 py-2 text-on-surface hover:bg-hover-surface rounded text-body-sm no-underline" href="{{ route('settings.index') }}">
                        <span class="material-symbols-outlined text-[18px] mr-2">tune</span> Settings
                    </a>
                </li>
                <li class="border-t border-border-color my-1"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item w-full flex items-center px-3 py-2 text-error hover:bg-hover-surface rounded text-body-sm border-0 bg-transparent cursor-pointer">
                            <span class="material-symbols-outlined text-[18px] mr-2 text-error">logout</span> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>

        <!-- User Avatar / Cashier Identity -->
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded bg-surface-variant border border-border-color overflow-hidden cursor-pointer hover:border-amber-accent transition-colors flex items-center justify-center text-on-surface font-metric-sm text-[12px] flex-shrink-0">
                @if(!empty(Auth::user()->avatar))
                    <img src="{{ asset(Auth::user()->avatar) }}" alt="Avatar" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr(Auth::user()->name ?? 'AD', 0, 2)) }}
                @endif
            </div>
            <div class="hidden xl:block text-left leading-tight">
                <div class="font-metric-sm text-[12px] text-on-surface truncate max-w-[120px]">
                    {{ Auth::user()->name ?? 'Admin' }}
                </div>
                <div class="font-label-caps text-[9px] uppercase text-on-surface-variant">
                    {{ Auth::user()->role ?? 'Admin' }}
                </div>
            </div>
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
