<!-- ============================================
     Top Navbar - Jewellery POS System
     ============================================ -->
<nav class="navbar navbar-expand-lg navbar-dark pos-navbar fixed-top px-3">
    <div class="container-fluid px-0">
        <!-- Left Group: Toggle + Brand -->
        <div class="d-flex align-items-center">
            <div class="d-flex justify-content-center align-items-center" style="width: 54px;">
                <button class="btn btn-link text-white sidebar-toggle-btn" id="sidebarToggle" style="margin: 0;">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <a class="navbar-brand d-flex align-items-center mb-0 ms-2" href="{{ route('dashboard') }}">
                <img src="{{ !empty($systemLogo) ? asset($systemLogo) : asset('images/bc-logo.png') }}" alt="Logo" class="navbar-logo-image me-2">
                <span class="brand-text">{{ \App\Models\Setting::get('app_name', config('app.name', 'Clothing Outlet POS')) }}</span>
            </a>
        </div>

        @if(request()->routeIs('pos.*') || request()->is('pos*'))
        <!-- Center Group: Wide Search Bar + Amber Add Button -->
        <div class="topbar-search-cluster d-none d-md-flex">
            <div class="billing-search-wrap">
                <i class="fas fa-barcode"></i>
                <input type="text" id="product-search" placeholder="Enter Product name / SKU / Scan bar code" autocomplete="off" onfocus="handleSearchDropdown()" onblur="setTimeout(() => { document.getElementById('product-search-dropdown').style.display = 'none'; }, 200)">
                <div id="product-search-dropdown" style="display:none; position:absolute; top:100%; left:0; right:0; margin-top:4px; z-index:1000; flex-direction:column; max-height:400px; overflow-y:auto;">
                </div>
            </div>
            <button class="billing-add-btn" title="Add custom item" data-bs-toggle="modal" data-bs-target="#quickAddProductModal"><i class="fas fa-plus"></i></button>
        </div>
        @endif

        <!-- Right Side Items -->
        <div class="d-flex align-items-center navbar-right-items ms-auto">
            <!-- Notifications -->
            <div class="dropdown me-2 me-md-3">
                <button class="btn btn-link text-white position-relative nav-icon-btn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    @if(($notifCount ?? 0) > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;">
                            {{ $notifCount > 9 ? '9+' : $notifCount }}
                        </span>
                    @endif
                </button>

                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0 notification-dropdown">
                    <!-- Header -->
                    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center bg-light">
                        <strong class="text-dark" style="font-size:0.9rem;"><i class="fas fa-bell text-gold me-1"></i> Notifications</strong>
                        @if(($notifCount ?? 0) > 0)
                            <span class="badge bg-danger rounded-pill">{{ $notifCount }}</span>
                        @endif
                    </div>

                    @php $hasAny = false; @endphp

                    {{-- Out of Stock --}}
                    @if(!empty($outOfStock) && $outOfStock->count())
                        @php $hasAny = true; @endphp
                        <div class="px-3 pt-2 pb-1">
                            <small class="text-uppercase text-muted fw-bold" style="font-size:0.7rem;">Out of Stock</small>
                        </div>
                        @foreach($outOfStock as $p)
                            <a class="dropdown-item d-flex align-items-center py-2" href="{{ route('products.index', ['stock' => 'out']) }}">
                                <span class="me-2 text-danger" style="width:22px;text-align:center;"><i class="fas fa-exclamation-circle"></i></span>
                                <span class="flex-grow-1" style="font-size:0.85rem;">{{ Str::limit($p->name, 30) }}</span>
                                <span class="badge bg-danger ms-2">0 left</span>
                            </a>
                        @endforeach
                        <div class="dropdown-divider my-0"></div>
                    @endif

                    {{-- Low Stock --}}
                    @if(!empty($lowStock) && $lowStock->count())
                        @php $hasAny = true; @endphp
                        <div class="px-3 pt-2 pb-1">
                            <small class="text-uppercase text-muted fw-bold" style="font-size:0.7rem;">Low Stock</small>
                        </div>
                        @foreach($lowStock as $p)
                            <a class="dropdown-item d-flex align-items-center py-2" href="{{ route('products.index', ['stock' => 'low']) }}">
                                <span class="me-2 text-warning" style="width:22px;text-align:center;"><i class="fas fa-exclamation-triangle"></i></span>
                                <span class="flex-grow-1" style="font-size:0.85rem;">{{ Str::limit($p->name, 30) }}</span>
                                <span class="badge bg-warning text-dark ms-2">{{ $p->stock }} left</span>
                            </a>
                        @endforeach
                        <div class="dropdown-divider my-0"></div>
                    @endif

                    {{-- Recent Sales --}}
                    @if(!empty($recentSales) && $recentSales->count())
                        @php $hasAny = true; @endphp
                        <div class="px-3 pt-2 pb-1">
                            <small class="text-uppercase text-muted fw-bold" style="font-size:0.7rem;">Recent Sales (24h)</small>
                        </div>
                        @foreach($recentSales as $sale)
                            <a class="dropdown-item d-flex align-items-center py-2" href="{{ route('sales.index') }}">
                                <span class="me-2 text-success" style="width:22px;text-align:center;"><i class="fas fa-shopping-cart"></i></span>
                                <span class="flex-grow-1" style="font-size:0.85rem;">
                                    #{{ $sale->invoice_number ?? str_pad($sale->id, 4, '0', STR_PAD_LEFT) }} &mdash; Rs.&nbsp;{{ number_format($sale->total, 2) }}
                                </span>
                                <small class="text-muted ms-2" style="font-size:0.75rem;">{{ $sale->created_at->diffForHumans(null, true) }}</small>
                            </a>
                        @endforeach
                        <div class="dropdown-divider my-0"></div>
                    @endif

                    {{-- Last Shopify Sync --}}
                    @if(!empty($lastSync))
                        @php $hasAny = true; @endphp
                        <div class="px-3 pt-2 pb-1">
                            <small class="text-uppercase text-muted fw-bold" style="font-size:0.7rem;">Shopify</small>
                        </div>
                        <div class="dropdown-item d-flex align-items-center py-2" style="cursor:default;">
                            <span class="me-2 {{ $lastSync->error ? 'text-danger' : 'text-info' }}" style="width:22px;text-align:center;">
                                <i class="fas {{ $lastSync->error ? 'fa-times-circle' : 'fa-sync' }}"></i>
                            </span>
                            <span class="flex-grow-1" style="font-size:0.85rem;">
                                {{ $lastSync->error ? 'Sync error' : 'Sync complete' }}
                            </span>
                            <small class="text-muted ms-2" style="font-size:0.75rem;">{{ $lastSync->created_at->diffForHumans(null, true) }}</small>
                        </div>
                        <div class="dropdown-divider my-0"></div>
                    @endif

                    {{-- Empty state --}}
                    @if(!$hasAny)
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                            <div style="font-size:0.85rem;">All clear — no alerts</div>
                        </div>
                    @endif

                    <!-- Footer -->
                    <div class="border-top">
                        <a class="dropdown-item text-center text-primary py-2" href="{{ route('products.index', ['stock' => 'low']) }}" style="font-size:0.85rem;">
                            <i class="fas fa-box-open me-1"></i> View Stock Alerts
                        </a>
                    </div>
                </div>
            </div>

            <!-- Global Shopify Sync Button -->
            <div class="me-2 me-md-3 d-flex align-items-center">
                <button id="global-sync-btn" class="btn btn-sm btn-outline-light d-flex align-items-center gap-1" onclick="triggerGlobalSync()" title="Sync Products &amp; Collections from Shopify">
                    <i class="fab fa-shopify" id="sync-icon"></i>
                    <span class="d-none d-md-inline" style="font-size:0.78rem;">Sync</span>
                </button>
                <span id="sync-status-dot" class="ms-1" title="" style="width:8px;height:8px;border-radius:50%;background:#6c757d;display:inline-block;flex-shrink:0;"></span>
            </div>

            <!-- Current Date/Time -->
            <span class="text-white-50 d-none d-md-inline me-3">
                <i class="fas fa-calendar-alt me-1"></i>
                <span id="current-date">{{ now()->format('D, M d Y') }}</span>
            </span>

            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="btn btn-link text-white dropdown-toggle d-flex align-items-center nav-icon-btn" data-bs-toggle="dropdown">
                    <div class="user-avatar me-1 me-md-2">
                        <i class="fas fa-user-circle fa-lg"></i>
                    </div>
                    <span class="d-none d-md-inline user-name-text">{{ Auth::user()->name ?? 'Admin' }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                    <li>
                        <div class="dropdown-header">
                            <strong>{{ Auth::user()->name ?? 'Admin' }}</strong>
                            <br><small class="text-muted">{{ Auth::user()->email ?? 'admin@store.com' }}</small>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user-cog me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="fas fa-cog me-2"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
