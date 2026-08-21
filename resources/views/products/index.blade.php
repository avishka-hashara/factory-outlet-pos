{{-- ============================================
Products Page - Jewellery POS System
Shopify products (read-only) + Local in-store products (full CRUD)
============================================ --}}
@extends('layouts.pos')
@section('title', 'Products')

@section('content')
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4><i class="fas fa-tshirt me-2 text-gold"></i> Products</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Products</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <button class="btn btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus me-1"></i> Add Local Product
            </button>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Source Tabs -->
    <ul class="nav nav-pills mb-3" id="sourceTab">
        <li class="nav-item">
            <a class="nav-link {{ request('source', 'all') === 'all' ? 'active' : '' }}"
               href="{{ route('products.index', array_merge(request()->except(['source','page']), ['source'=>'all'])) }}">
                All Products <span class="badge bg-secondary ms-1">{{ $totalCount }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('source') === 'shopify' ? 'active' : '' }}"
               href="{{ route('products.index', array_merge(request()->except(['source','page']), ['source'=>'shopify'])) }}">
                <i class="fab fa-shopify me-1"></i> Shopify
                <span class="badge bg-info ms-1">{{ $shopifyCount }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('source') === 'local' ? 'active' : '' }}"
               href="{{ route('products.index', array_merge(request()->except(['source','page']), ['source'=>'local'])) }}">
                <i class="fas fa-store me-1"></i> Local (In-Store)
                <span class="badge bg-warning text-dark ms-1">{{ $localCount }}</span>
            </a>
        </li>
    </ul>

    <!-- Filters Row -->
    <form method="GET" action="{{ route('products.index') }}" id="filterForm">
        <input type="hidden" name="source" value="{{ request('source', 'all') }}">
        <div class="pos-card card mb-4">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-gold"></i></span>
                            <input type="text" name="search" class="form-control"
                                placeholder="Search by name or SKU..."
                                value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Stock Status</label>
                        <select name="stock" class="form-select">
                            <option value="">All</option>
                            <option value="instock" {{ request('stock') === 'instock' ? 'selected' : '' }}>In Stock</option>
                            <option value="low"     {{ request('stock') === 'low'     ? 'selected' : '' }}>Low Stock (&le;5)</option>
                            <option value="out"     {{ request('stock') === 'out'     ? 'selected' : '' }}>Out of Stock</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark-blue w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-undo me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Products Table -->
    <div class="pos-card card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table pos-table mb-0">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th style="width:60px;">Image</th>
                            <th>Product Name</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th style="width:110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $p)
                            <tr data-source="{{ $p->shopify_id ? 'shopify' : 'local' }}"
                                data-stock="{{ $p->stock <= 0 ? 'out' : ($p->stock <= 5 ? 'low' : 'instock') }}"
                                data-product-id="{{ $p->id }}">
                                <td>{{ $p->id }}</td>
                                <td id="ptd-img-{{ $p->id }}">
                                    @if($p->image_url)
                                        <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="rounded"
                                            style="width:40px;height:40px;object-fit:cover;">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center rounded"
                                            style="width:40px;height:40px;background:var(--light-grey);">
                                            <i class="fas fa-tshirt text-gold"></i>
                                        </div>
                                    @endif
                                </td>
                                <td id="ptd-name-{{ $p->id }}">
                                    <strong id="ptd-nametxt-{{ $p->id }}">{{ $p->name }}</strong>
                                    <div id="ptd-vdots-{{ $p->id }}" class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                        @foreach($p->variants->take(6) as $v)
                                            @php $vtitle = collect([$v->color, $v->size])->filter()->implode(' / '); @endphp
                                            @if($v->color_code)
                                                <span title="{{ $vtitle }} ({{ $v->stock }})"
                                                    style="width:16px;height:16px;border-radius:50%;display:inline-block;border:1px solid #ccc;background:{{ $v->color_code }};"></span>
                                            @elseif($v->size)
                                                <span class="badge bg-secondary" title="{{ $vtitle }} ({{ $v->stock }})" style="font-size:0.6rem;padding:2px 4px;">{{ $v->size }}</span>
                                            @else
                                                <span title="{{ $vtitle }} ({{ $v->stock }})"
                                                    style="width:16px;height:16px;border-radius:50%;display:inline-block;border:1px solid #ccc;background:#ccc;"></span>
                                            @endif
                                        @endforeach
                                        @if($p->variants->count() > 6)
                                            <small class="text-muted">+{{ $p->variants->count() - 6 }}</small>
                                        @endif
                                        @if($p->variants->count() > 0)
                                            <small class="text-muted ms-1">({{ $p->variants->count() }} variants)</small>
                                        @endif
                                    </div>
                                </td>
                                <td><code id="ptd-sku-{{ $p->id }}">{{ $p->sku }}</code></td>
                                <td id="ptd-cat-{{ $p->id }}">
                                    <span class="badge bg-light text-dark border">{{ $p->category ?? '—' }}</span>
                                    @if($p->subcategory)
                                        <br><span class="badge bg-secondary bg-opacity-25 text-secondary border mt-1" style="font-size:0.7rem;">
                                            <i class="fas fa-angle-right me-1"></i>{{ $p->subcategory }}</span>
                                    @endif
                                </td>
                                <td class="fw-bold" id="ptd-price-{{ $p->id }}">Rs. {{ number_format($p->price, 2) }}</td>
                                <td id="ptd-stock-{{ $p->id }}">
                                    @php $displayStock = $p->hasVariants() ? $p->total_stock : $p->stock; @endphp
                                    @if($displayStock > 5)
                                        <span class="fw-semibold text-success">{{ $displayStock }}</span>
                                    @elseif($displayStock > 0)
                                        <span class="fw-semibold text-warning">{{ $displayStock }}</span>
                                    @else
                                        <span class="fw-semibold text-danger">0</span>
                                    @endif
                                    @if($p->hasVariants())
                                        <br><small class="text-muted">across {{ $p->variants->count() }} variants</small>
                                    @endif
                                </td>
                                <td>
                                    @if($p->shopify_id)
                                        <span class="badge bg-info text-white" title="Synced from Shopify">
                                            <i class="fab fa-shopify me-1"></i> Shopify
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark" title="Local in-store product">
                                            <i class="fas fa-store me-1"></i> Local
                                        </span>
                                    @endif
                                </td>
                                <td id="ptd-status-{{ $p->id }}">
                                    @if($p->stock > 0)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Out of Stock</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info me-1" title="Manage Variants"
                                        onclick="openVariantsModal({{ $p->id }}, '{{ addslashes($p->name) }}', {{ $p->shopify_id ? 'true' : 'false' }})">
                                        <i class="fas fa-palette"></i>
                                    </button>
                                    @if(!$p->shopify_id)
                                        <button class="btn btn-sm btn-outline-warning me-1" title="Print Barcode"
                                            onclick="openBarcodeModal({{ $p->id }}, '{{ addslashes($p->name) }}', '{{ addslashes($p->sku) }}', '{{ $p->price }}')"><i class="fas fa-barcode"></i></button>
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Edit"
                                            id="ptd-editbtn-{{ $p->id }}"
                                            onclick="openEdit({{ $p->id }}, '{{ addslashes($p->name) }}', '{{ addslashes($p->sku) }}', '{{ addslashes($p->category ?? '') }}', '{{ addslashes($p->subcategory ?? '') }}', '{{ $p->price }}', '{{ $p->stock }}', '{{ addslashes($p->image_url ?? '') }}')"><i class="fas fa-edit"></i></button>
                                        <form method="POST" action="{{ route('products.destroy', $p) }}" class="delete-product-form d-inline"
                                            data-product-id="{{ $p->id }}" data-product-name="{{ addslashes($p->name) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @else
                                        <span class="text-muted" title="Managed in Shopify"><i class="fas fa-lock"></i></span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted p-5">
                                    <i class="fas fa-box-open fa-3x mb-3 d-block"></i>
                                    No products found. Sync with Shopify or add a local product.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-3">
                <small class="text-muted">Showing {{ $products->firstItem() ?? 0 }}&ndash;{{ $products->lastItem() ?? 0 }}
                    of {{ $products->total() }} products</small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        {{-- Previous --}}
                        @if ($products->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $products->previousPageUrl() }}">&laquo;</a>
                            </li>
                        @endif

                        {{-- Page Numbers --}}
                        @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                            @if ($page == $products->currentPage())
                                <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach

                        {{-- Next --}}
                        @if ($products->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $products->nextPageUrl() }}">&raquo;</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                        @endif
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    {{-- ===== ADD Local Product Modal ===== --}}
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" id="addProductForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-store me-2 text-warning"></i> Add Local In-Store Product
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            This product will be stored <strong>only on this server</strong> and will
                            <strong>not</strong> be added to your Shopify store.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    placeholder="e.g. Classic Cotton Crewneck Tee" value="{{ old('name') }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">SKU <span class="text-danger">*</span></label>
                                <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror"
                                    placeholder="e.g. TSH-BLK-M" value="{{ old('sku') }}" required>
                                @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                                <input type="text" name="category" class="form-control @error('category') is-invalid @enderror"
                                    placeholder="e.g. Men, Women, Kids, Unisex..." value="{{ old('category') }}" list="categoryList" required>
                                <datalist id="categoryList">
                                    <option value="Men">
                                    <option value="Women">
                                    <option value="Kids">
                                    <option value="Unisex">
                                    <option value="Tops">
                                    <option value="Bottoms">
                                    <option value="Outerwear">
                                    <option value="Accessories">
                                    @foreach(\App\Models\Product::whereNotNull('category')->distinct()->pluck('category') as $_c)
                                        @if(!in_array($_c, ['Men', 'Women', 'Kids', 'Unisex', 'Tops', 'Bottoms', 'Outerwear', 'Accessories']))
                                            <option value="{{ $_c }}">
                                        @endif
                                    @endforeach
                                </datalist>
                                @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Sub-Category</label>
                                <input type="text" name="subcategory" class="form-control @error('subcategory') is-invalid @enderror"
                                    placeholder="e.g. T-Shirts, Shirts, Jeans, Dresses..." value="{{ old('subcategory') }}" list="subcategoryList">
                                <datalist id="subcategoryList">
                                    <option value="T-Shirts">
                                    <option value="Casual Shirts">
                                    <option value="Formal Shirts">
                                    <option value="Jeans">
                                    <option value="Trousers">
                                    <option value="Shorts">
                                    <option value="Dresses">
                                    <option value="Hoodies &amp; Sweatshirts">
                                    <option value="Jackets">
                                    <option value="Activewear">
                                    @foreach(\App\Models\Product::whereNotNull('subcategory')->distinct()->pluck('subcategory') as $_s)
                                        @if(!in_array($_s, ['T-Shirts', 'Casual Shirts', 'Formal Shirts', 'Jeans', 'Trousers', 'Shorts', 'Dresses', 'Hoodies & Sweatshirts', 'Jackets', 'Activewear']))
                                            <option value="{{ $_s }}">
                                        @endif
                                    @endforeach
                                </datalist>
                                <div class="form-text">Optional — groups products under a main category.</div>
                                @error('subcategory') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Price (Rs.) <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control @error('price') is-invalid @enderror"
                                    placeholder="0.00" step="0.01" min="0" value="{{ old('price') }}" required>
                                @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Stock Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="stock" id="add_stock" class="form-control @error('stock') is-invalid @enderror"
                                    placeholder="0" min="0" value="{{ old('stock') }}" required>
                                <div class="form-text text-muted" id="add_stock_hint">Set to 0 if you are adding variants below.</div>
                                @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Product Image</label>
                                <input type="file" name="image" id="add_image_input"
                                    class="form-control @error('image') is-invalid @enderror"
                                    accept="image/jpg,image/jpeg,image/png,image/webp"
                                    onchange="previewImage(this, 'add_image_preview')">
                                <div class="form-text">JPG, PNG or WebP &mdash; max 2 MB. Optional.</div>
                                @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <img id="add_image_preview" src="" alt="Preview"
                                    class="mt-2 rounded d-none" style="height:100px;object-fit:cover;">
                            </div>
                        </div>

                        {{-- Variants Section --}}
                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-semibold text-muted text-uppercase small mb-0">
                                <i class="fas fa-palette me-1"></i> Variants
                                <span class="badge bg-secondary ms-1" id="add_variant_count">0</span>
                                <span class="fw-normal ms-1" style="font-size:0.75rem;text-transform:none;">&mdash; Optional</span>
                            </h6>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="addProductVariantRow()">
                                <i class="fas fa-plus me-1"></i> Add Variant
                            </button>
                        </div>
                        <div id="add_variant_rows_empty" class="text-muted small py-2 ps-1">
                            No variants added. Click <strong>Add Variant</strong> to add color / size options.
                        </div>
                        <div class="table-responsive d-none" id="add_variants_table_wrap">
                            <table class="table table-sm table-bordered align-middle mb-1">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:36px;">Color</th>
                                        <th>Color Name</th>
                                        <th>Size</th>
                                        <th>Variant SKU</th>
                                        <th>Price (Rs.)</th>
                                        <th style="width:80px;">Stock <span class="text-danger">*</span></th>
                                        <th style="width:40px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="add_variants_tbody"></tbody>
                            </table>
                        </div>
                        <small class="text-muted d-none" id="add_variant_footer_hint">
                            <i class="fas fa-info-circle me-1"></i>At least one of Color or Size is required per row. Product stock will be set to the sum of all variant stocks on save.
                        </small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-gold">
                            <i class="fas fa-save me-1"></i> Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== EDIT Local Product Modal ===== --}}
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editProductForm" action="" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2 text-primary"></i> Edit Local Product
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">SKU <span class="text-danger">*</span></label>
                                <input type="text" name="sku" id="edit_sku" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                                <input type="text" name="category" id="edit_category" class="form-control" list="categoryList" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Sub-Category</label>
                                <input type="text" name="subcategory" id="edit_subcategory" class="form-control" list="subcategoryList" placeholder="e.g. Necklaces, Rings...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Price (Rs.) <span class="text-danger">*</span></label>
                                <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Stock Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="stock" id="edit_stock" class="form-control" min="0" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Product Image</label>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <img id="edit_image_preview" src="" alt="Current"
                                        class="rounded d-none" style="height:70px;object-fit:cover;">
                                    <div id="edit_no_image" class="d-flex align-items-center justify-content-center rounded bg-light"
                                        style="width:70px;height:70px;">
                                        <i class="fas fa-tshirt text-secondary"></i>
                                    </div>
                                </div>
                                <input type="file" name="image" id="edit_image_input"
                                    class="form-control"
                                    accept="image/jpg,image/jpeg,image/png,image/webp"
                                    onchange="previewImage(this, 'edit_image_preview')">
                                <div class="form-text">Upload a new image to replace the current one. JPG, PNG or WebP &mdash; max 2 MB.</div>
                                <div class="form-check mt-2" id="edit_remove_wrap">
                                    <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="edit_remove_image">
                                    <label class="form-check-label text-danger small" for="edit_remove_image">Remove current image</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== MANAGE VARIANTS Modal ===== --}}
    <div class="modal fade" id="variantsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-palette me-2 text-gold"></i> Manage Variants &mdash; <span id="variants-modal-product-name"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Current Variants -->
                    <h6 class="fw-semibold mb-2">Current Variants</h6>
                    <div id="variants-list-loading" class="text-center py-3 text-muted">
                        <i class="fas fa-spinner fa-spin me-1"></i> Loading...
                    </div>
                    <div id="variants-list-container" class="d-none"></div>

                    <!-- Add Variant Form (local products only) -->
                    <div id="add-variant-section" class="mt-4 d-none">
                        <h6 class="fw-semibold mb-2"><i class="fas fa-plus me-1 text-success"></i> Add New Variant</h6>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Color Name</label>
                                <input type="text" id="v_color" class="form-control form-control-sm" placeholder="e.g. Gold, Silver, Red..." list="colorSuggestions">
                                <datalist id="colorSuggestions">
                                    <option value="Gold"><option value="Silver"><option value="Rose Gold">
                                    <option value="Platinum"><option value="White Gold"><option value="Yellow Gold">
                                    <option value="Black"><option value="Pink"><option value="Red"><option value="Blue">
                                </datalist>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Color Code</label>
                                <div class="input-group input-group-sm">
                                    <input type="color" id="v_color_picker" class="form-control form-control-color p-1"
                                        value="#FFD700" oninput="document.getElementById('v_color_code').value=this.value"
                                        style="max-width:38px;min-width:38px;">
                                    <input type="text" id="v_color_code" class="form-control form-control-sm" placeholder="#FFD700" maxlength="20">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Size</label>
                                <input type="text" id="v_size" class="form-control form-control-sm" placeholder="e.g. S, M, L, XL, 6, 7, Free Size..." list="sizeSuggestions">
                                <datalist id="sizeSuggestions">
                                    <option value="XS"><option value="S"><option value="M"><option value="L">
                                    <option value="XL"><option value="XXL">
                                    <option value="5"><option value="6"><option value="7"><option value="8"><option value="9"><option value="10">
                                    <option value="Free Size">
                                </datalist>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">SKU</label>
                                <input type="text" id="v_sku" class="form-control form-control-sm" placeholder="Optional">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Price (Rs.)</label>
                                <input type="number" id="v_price" class="form-control form-control-sm" placeholder="Blank = product price" step="0.01" min="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Stock <span class="text-danger">*</span></label>
                                <input type="number" id="v_stock" class="form-control form-control-sm" placeholder="0" min="0" value="0">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button class="btn btn-success btn-sm w-100" onclick="addVariant()" id="add-variant-btn">
                                    <i class="fas fa-plus me-1"></i> Add Variant
                                </button>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1"><i class="fas fa-info-circle me-1"></i>At least one of Color or Size is required.</small>
                    </div>

                    <!-- Shopify note -->
                    <div id="shopify-variants-note" class="d-none mt-3">
                        <div class="alert alert-info mb-0">
                            <i class="fab fa-shopify me-1"></i> Variants for Shopify products are managed in your Shopify store. Use <strong>Sync with Shopify</strong> to refresh them here.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ADVANCED BARCODE Modal ===== --}}
    <div class="modal fade" id="barcodeModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" style="border:none; border-radius:12px; overflow:hidden;">
                <!-- Header -->
                <div class="modal-header" style="background:#0f172a; color:#fff; padding:1.2rem 1.5rem; border-bottom:none;">
                    <div>
                        <h5 class="modal-title mb-1" style="font-weight:700; display:flex; align-items:center; gap:10px;">
                            <i class="fas fa-barcode text-warning"></i> Product Barcode Printing
                        </h5>
                        <div style="font-size:0.8rem; color:#94a3b8;">Design and print professional barcode labels</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-0" style="background:#f8fafc;">
                    <div class="row g-0">
                        <!-- LEFT COLUMN: Preview & Templates -->
                        <div class="col-lg-5 p-4 border-end" style="background:#fff;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Label Preview</h6>
                                    <small class="text-muted">This is how your label will look</small>
                                </div>
                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill" id="lbl-size-badge" style="font-size:0.75rem; font-weight:600;">Size: 38mm x 25mm</span>
                            </div>

                            <!-- Live Preview Area -->
                            <div class="d-flex justify-content-center align-items-center p-4 mb-4" style="background:#f1f5f9; border-radius:12px; border:1px solid #e2e8f0; min-height: 250px;">
                                <div id="barcode-preview-wrap" style="background:#fff; width: 38mm; height: 25mm; display:flex; flex-direction:column; justify-content:center; align-items:center; box-sizing: border-box; overflow:hidden; border-radius:4px; box-shadow:0 4px 6px rgba(0,0,0,0.05); position:relative;">
                                    <div class="position-absolute" style="left:-25px; top:50%; transform:translateY(-50%) rotate(-90deg); font-size:0.6rem; color:#64748b; letter-spacing:1px;" id="lbl-dim-h">25mm</div>
                                    <div class="position-absolute" style="bottom:-20px; left:50%; transform:translateX(-50%); font-size:0.6rem; color:#64748b; letter-spacing:1px;" id="lbl-dim-w">38mm</div>
                                    
                                    <div id="prev-brand" class="fw-bold" style="font-size: 8pt; letter-spacing: 0.5px; margin-bottom: 0.5mm; line-height:1; font-family:'Arial', sans-serif;">APPAREL OUTLET</div>
                                    <div id="prev-tagline" style="font-size: 4.5pt; font-weight: bold; margin-bottom: 1mm; line-height:1; font-family:'Arial', sans-serif;">PREMIUM QUALITY | 100% COTTON</div>
                                    <svg id="barcode-svg" style="height:7mm; width:auto; display:block; margin:0 auto;"></svg>
                                    <div id="prev-sku" class="fw-bold" style="font-size: 6pt; margin-top: 0.5mm; line-height:1; font-family:'Arial', sans-serif;">TSH-BLK-L</div>
                                    <div id="prev-name" style="font-size: 5pt; margin-top: 0.5mm; line-height:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; width:100%; text-align:center; font-family:'Arial', sans-serif;">Classic Cotton Crewneck Tee - Black (L)</div>
                                    <div id="prev-price" class="fw-bold" style="font-size: 7.5pt; margin-top: 0.5mm; line-height:1; font-family:'Arial', sans-serif;">Rs. 2,450</div>
                                </div>
                            </div>

                            <!-- Quick Templates -->
                            <h6 class="fw-bold mb-1 text-dark">Quick Templates</h6>
                            <small class="text-muted d-block mb-3">Choose a ready-made layout</small>
                            <div class="row g-2">
                                <div class="col-4">
                                    <div class="template-card border rounded p-2 text-center position-relative active-template" onclick="applyTemplate('standard', this)" style="cursor:pointer; border-color:#f59e0b!important;">
                                        <i class="fas fa-check-circle text-warning position-absolute top-0 end-0 m-1"></i>
                                        <div style="background:#f8fafc; border:1px solid #e2e8f0; height:60px; margin-bottom:8px; display:flex; flex-direction:column; justify-content:center; align-items:center;">
                                            <div style="font-size:4px; font-weight:bold;">APPAREL</div>
                                            <div style="font-size:3px;">PREMIUM COTTON...</div>
                                            <div style="width:70%; height:10px; background:#333; margin:2px 0;"></div>
                                            <div style="font-size:4px;">TSH-BLK-L</div>
                                            <div style="font-size:5px; font-weight:bold;">Rs. 2,450</div>
                                        </div>
                                        <div class="fw-bold" style="font-size:0.75rem;">Standard</div>
                                        <div style="font-size:0.65rem; color:#64748b;">38mm x 25mm</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="template-card border rounded p-2 text-center position-relative" onclick="applyTemplate('compact', this)" style="cursor:pointer;">
                                        <div style="background:#f8fafc; border:1px solid #e2e8f0; height:60px; margin-bottom:8px; display:flex; flex-direction:column; justify-content:center; align-items:center;">
                                            <div style="font-size:5px; font-weight:bold;">APPAREL</div>
                                            <div style="width:80%; height:12px; background:#333; margin:2px 0;"></div>
                                            <div style="font-size:4px;">TSH-BLK-L</div>
                                            <div style="font-size:5px; font-weight:bold;">Rs. 2,450</div>
                                        </div>
                                        <div class="fw-bold" style="font-size:0.75rem;">Compact</div>
                                        <div style="font-size:0.65rem; color:#64748b;">38mm x 20mm</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="template-card border rounded p-2 text-center position-relative" onclick="applyTemplate('minimal', this)" style="cursor:pointer;">
                                        <div style="background:#f8fafc; border:1px solid #e2e8f0; height:60px; margin-bottom:8px; display:flex; flex-direction:column; justify-content:center; align-items:center;">
                                            <div style="width:80%; height:15px; background:#333; margin-bottom:4px;"></div>
                                            <div style="font-size:4px;">TSH-BLK-L</div>
                                            <div style="font-size:5px; font-weight:bold;">Rs. 2,450</div>
                                        </div>
                                        <div class="fw-bold" style="font-size:0.75rem;">Minimal</div>
                                        <div style="font-size:0.65rem; color:#64748b;">38mm x 25mm</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-muted" style="font-size:0.7rem;">
                                <i class="fas fa-info-circle me-1"></i> This is a preview only. Actual print may vary slightly depending on printer settings.
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: Settings -->
                        <div class="col-lg-7 p-4">
                            <h6 class="fw-bold mb-1 text-dark">Print Settings</h6>
                            <small class="text-muted d-block mb-4">Configure your barcode label</small>

                            <!-- Select Product -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold" style="font-size:0.85rem;">Select Product</label>
                                <select id="bc-product-select" class="form-select">
                                    <!-- Populated dynamically via openBarcodeModal -->
                                </select>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-4">
                                    <label class="form-label fw-semibold" style="font-size:0.85rem;">Number of Labels</label>
                                    <input type="number" id="bc-qty" class="form-control" value="1" min="1">
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-semibold" style="font-size:0.85rem;">Labels Per Row</label>
                                    <select id="bc-per-row" class="form-select">
                                        <option value="1">1</option>
                                        <option value="2" selected>2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-semibold" style="font-size:0.85rem;">Barcode Format</label>
                                    <select id="bc-format" class="form-select" onchange="generateLiveBarcode()">
                                        <option value="CODE128" selected>CODE128</option>
                                        <option value="EAN13">EAN-13</option>
                                        <option value="CODE39">CODE39</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Customize Content -->
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                                    <h6 class="fw-bold mb-0" style="font-size:0.85rem;">Customize Label Content</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex flex-column gap-3">
                                        <!-- Brand -->
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input check-brand" type="checkbox" id="chk-brand" checked onchange="togglePreview('brand')">
                                                <label class="form-check-label fw-semibold" style="font-size:0.85rem;" for="chk-brand">
                                                    Brand Name (<span id="val-brand">APPAREL OUTLET</span>)
                                                </label>
                                            </div>
                                            <button class="btn btn-sm btn-outline-secondary" style="font-size:0.75rem;" onclick="editContent('brand')"><i class="fas fa-pen me-1"></i> Edit</button>
                                        </div>
                                        <!-- Tagline -->
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input check-tagline" type="checkbox" id="chk-tagline" checked onchange="togglePreview('tagline')">
                                                <label class="form-check-label fw-semibold" style="font-size:0.85rem;" for="chk-tagline">
                                                    Tagline (<span id="val-tagline">PREMIUM QUALITY | 100% COTTON</span>)
                                                </label>
                                            </div>
                                            <button class="btn btn-sm btn-outline-secondary" style="font-size:0.75rem;" onclick="editContent('tagline')"><i class="fas fa-pen me-1"></i> Edit</button>
                                        </div>
                                        <!-- SKU -->
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input check-sku" type="checkbox" id="chk-sku" checked onchange="togglePreview('sku')">
                                                <label class="form-check-label fw-semibold" style="font-size:0.85rem;" for="chk-sku">
                                                    Product SKU (<span id="val-sku">GLM156G50</span>)
                                                </label>
                                            </div>
                                            <button class="btn btn-sm btn-outline-secondary" style="font-size:0.75rem;" onclick="editContent('sku')"><i class="fas fa-pen me-1"></i> Edit</button>
                                        </div>
                                        <!-- Name -->
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input check-name" type="checkbox" id="chk-name" checked onchange="togglePreview('name')">
                                                <label class="form-check-label fw-semibold" style="font-size:0.85rem;" for="chk-name">
                                                    Product Name (<span id="val-name">2mm Cuban Nc GOLD 50cm</span>)
                                                </label>
                                            </div>
                                            <button class="btn btn-sm btn-outline-secondary" style="font-size:0.75rem;" onclick="editContent('name')"><i class="fas fa-pen me-1"></i> Edit</button>
                                        </div>
                                        <!-- Price -->
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input check-price" type="checkbox" id="chk-price" checked onchange="togglePreview('price')">
                                                <label class="form-check-label fw-semibold" style="font-size:0.85rem;" for="chk-price">
                                                    Price (<span id="val-price">Rs. 2,190</span>)
                                                </label>
                                            </div>
                                            <button class="btn btn-sm btn-outline-secondary" style="font-size:0.75rem;" onclick="editContent('price')"><i class="fas fa-pen me-1"></i> Edit</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold" style="font-size:0.85rem;">Font Settings</label>
                                    <small class="text-muted d-block mb-2" style="font-size:0.7rem;">Choose font for label text</small>
                                    <select id="bc-font" class="form-select mb-2" onchange="updatePreviewStyles()">
                                        <option value="'Zebra 0', sans-serif">Zebra 0 (Recommended)</option>
                                    </select>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="bc-bold" checked onchange="updatePreviewStyles()">
                                        <label class="form-check-label fw-semibold" style="font-size:0.8rem;" for="bc-bold">Bold Text</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold" style="font-size:0.85rem;">Label Size</label>
                                    <small class="text-muted d-block mb-2" style="font-size:0.7rem;">Select label size (Width x Height)</small>
                                    <select id="bc-size" class="form-select" onchange="updateLabelSize()">
                                        <option value="38x25" selected>38mm x 25mm</option>
                                        <option value="38x20">38mm x 20mm</option>
                                        <option value="50x25">50mm x 25mm</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between align-items-center" style="background:#fff; border-top:1px solid #e2e8f0; padding:1.2rem 1.5rem;">
                    <button class="btn btn-outline-secondary" style="font-weight:600;" onclick="resetBarcodeDefaults()"><i class="fas fa-undo me-2"></i>Reset to Default</button>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light" data-bs-dismiss="modal" style="font-weight:600; border:1px solid #d1d5db;">Cancel</button>
                        <button class="btn text-white" onclick="executeBarcodePrint()" style="background:#f59e0b; font-weight:600; padding:0.5rem 2rem;">
                            <i class="fas fa-print me-2"></i> Print Barcode
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
    // ── Live image preview ─────────────────────────────────────────
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                // In edit modal, hide the no-image placeholder
                const noImg = document.getElementById('edit_no_image');
                if (noImg) noImg.classList.add('d-none');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // ── Edit modal population ──────────────────────────────────────
    function openEdit(id, name, sku, category, subcategory, price, stock, imageUrl) {
        document.getElementById('edit_name').value        = name;
        document.getElementById('edit_sku').value         = sku;
        document.getElementById('edit_category').value    = category;
        document.getElementById('edit_subcategory').value = subcategory;
        document.getElementById('edit_price').value       = price;
        document.getElementById('edit_stock').value       = stock;
        document.getElementById('editProductForm').action = '{{ url('/products') }}/' + id;

        // Reset file input
        document.getElementById('edit_image_input').value = '';
        document.getElementById('edit_remove_image').checked = false;

        // Show / hide current image preview
        const preview = document.getElementById('edit_image_preview');
        const noImg   = document.getElementById('edit_no_image');
        const removeWrap = document.getElementById('edit_remove_wrap');
        if (imageUrl) {
            preview.src = imageUrl;
            preview.classList.remove('d-none');
            noImg.classList.add('d-none');
            removeWrap.classList.remove('d-none');
        } else {
            preview.src = '';
            preview.classList.add('d-none');
            noImg.classList.remove('d-none');
            removeWrap.classList.add('d-none');
        }

        new bootstrap.Modal(document.getElementById('editProductModal')).show();
    }

    // ── Shopify Sync ───────────────────────────────────────────────
    async function syncShopify(event) {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Syncing...';
        btn.disabled = true;
        try {
            const response = await fetch('{{ route('shopify.sync') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            });
            const data = await response.json();
            if (data.disabled) { alert(data.message); }
            else if (data.success) { alert('\u2713 ' + data.message); location.reload(); }
            else { alert('Sync failed: ' + data.message); }
        } catch (error) {
            alert('Network error during sync.');
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled  = false;
        }
    }

    // ── Sync Collections ───────────────────────────────────────────
    async function syncCollections(event) {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Syncing...';
        btn.disabled = true;
        try {
            const response = await fetch('{{ route('shopify.sync.collections') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            });
            const data = await response.json();
            if (data.disabled) { alert(data.message); }
            else if (data.success) { alert('\u2713 ' + data.message); }
            else { alert('Sync failed: ' + data.message); }
        } catch (error) {
            alert('Network error during sync.');
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled  = false;
        }
    }

    // ── Re-open add modal on validation error ──────────────────────
    @if($errors->any())
        document.addEventListener('DOMContentLoaded', () => {
            new bootstrap.Modal(document.getElementById('addProductModal')).show();
        });
    @endif

    // ── Manage Variants ────────────────────────────────────────────
    let currentVariantsProductId = null;
    let currentVariantsIsShopify = false;

    async function openVariantsModal(productId, productName, isShopify) {
        currentVariantsProductId = productId;
        currentVariantsIsShopify = isShopify;
        document.getElementById('variants-modal-product-name').textContent = productName;
        document.getElementById('add-variant-section').classList.toggle('d-none', isShopify);
        document.getElementById('shopify-variants-note').classList.toggle('d-none', !isShopify);
        document.getElementById('variants-list-loading').classList.remove('d-none');
        document.getElementById('variants-list-container').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('variantsModal')).show();
        await refreshVariantsList();
    }

    async function refreshVariantsList() {
        try {
            const resp = await fetch(`/products/${currentVariantsProductId}/variants`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            const variants = await resp.json();
            document.getElementById('variants-list-loading').classList.add('d-none');
            const container = document.getElementById('variants-list-container');
            container.classList.remove('d-none');
            if (variants.length === 0) {
                container.innerHTML = '<p class="text-muted small py-2">No variants yet.</p>';
                return;
            }
            const delBtn = !currentVariantsIsShopify
                ? (id) => `<button class="btn btn-sm btn-outline-danger ms-1" onclick="deleteVariant(${id})" title="Delete"><i class="fas fa-trash"></i></button>`
                : () => '';
            const stockBadge = (stock) => stock > 5 ? 'text-success' : (stock > 0 ? 'text-warning' : 'text-danger');
            container.innerHTML = `<table class="table table-sm table-bordered mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px;">Color</th>
                        <th>Color Name</th>
                        <th>Size</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th style="width:160px;">Stock</th>
                        <th style="width:${!currentVariantsIsShopify ? '80' : '50'}px;"></th>
                    </tr>
                </thead>
                <tbody>
                    ${variants.map(v => `<tr id="vrow-${v.id}">
                        <td class="text-center">${v.color_code ? `<span style="display:inline-block;width:22px;height:22px;border-radius:50%;background:${v.color_code};border:1px solid #ccc;"></span>` : '&mdash;'}</td>
                        <td>${v.color || '&mdash;'}</td>
                        <td>${v.size || '&mdash;'}</td>
                        <td><code>${v.sku || '&mdash;'}</code></td>
                        <td>${v.price ? 'Rs. ' + parseFloat(v.price).toLocaleString('en-US', {minimumFractionDigits:2}) : '<span class="text-muted fst-italic">Product price</span>'}</td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <input type="number" id="vstk-${v.id}" class="form-control form-control-sm text-center fw-semibold ${stockBadge(v.stock)}"
                                    value="${v.stock}" min="0" style="width:68px;"
                                    onkeydown="if(event.key==='Enter'){saveVariantStock(${v.id},${currentVariantsProductId})}">
                                <button class="btn btn-sm btn-outline-primary px-2" onclick="saveVariantStock(${v.id},${currentVariantsProductId})" title="Save stock" id="vstkbtn-${v.id}">
                                    <i class="fas fa-save"></i>
                                </button>
                            </div>
                        </td>
                        <td class="text-center">${delBtn(v.id)}</td>
                    </tr>`).join('')}
                </tbody>
            </table>`;
            updateProductRowVariants(currentVariantsProductId, variants);
        } catch (e) {
            document.getElementById('variants-list-loading').classList.add('d-none');
            document.getElementById('variants-list-container').innerHTML = '<p class="text-danger small">Failed to load variants.</p>';
            document.getElementById('variants-list-container').classList.remove('d-none');
        }
    }

    async function saveVariantStock(variantId, productId) {
        const input = document.getElementById(`vstk-${variantId}`);
        const btn   = document.getElementById(`vstkbtn-${variantId}`);
        const newStock = parseInt(input.value);
        if (isNaN(newStock) || newStock < 0) { input.focus(); return; }

        const origHtml = btn.innerHTML;
        btn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled   = true;
        input.disabled = true;

        try {
            const resp = await fetch(`/products/${productId}/variants/${variantId}/stock`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ stock: newStock })
            });
            const data = await resp.json();
            if (data.success) {
                // Update stock colour class
                input.className = input.className.replace(/text-(success|warning|danger)/g, '');
                const cls = newStock > 5 ? 'text-success' : (newStock > 0 ? 'text-warning' : 'text-danger');
                input.classList.add(cls);
                btn.innerHTML = '<i class="fas fa-check text-success"></i>';
                if (data.shopify_synced) {
                    btn.title = 'Saved & synced to Shopify';
                }
                setTimeout(() => { btn.innerHTML = origHtml; btn.title = 'Save stock'; }, 1500);
                updateProductRowStock(productId, data.product_stock, data.variants_count || 0);
            } else {
                alert(data.error || 'Failed to update stock.');
                btn.innerHTML = origHtml;
            }
        } catch (e) {
            alert('Network error. Please try again.');
            btn.innerHTML = origHtml;
        }
        btn.disabled   = false;
        input.disabled = false;
    }

    async function addVariant() {
        const color = document.getElementById('v_color').value.trim();
        const colorCode = document.getElementById('v_color_code').value.trim();
        const size = document.getElementById('v_size').value.trim();
        const sku = document.getElementById('v_sku').value.trim();
        const price = document.getElementById('v_price').value;
        const stock = document.getElementById('v_stock').value;
        if (!color && !size) { alert('Please provide at least a Color or Size.'); return; }
        if (stock === '' || parseInt(stock) < 0) { alert('Please enter a valid stock quantity (0 or more).'); return; }
        const btn = document.getElementById('add-variant-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>';
        try {
            const resp = await fetch(`/products/${currentVariantsProductId}/variants`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ color: color || null, color_code: colorCode || null, size: size || null, sku: sku || null, price: price || null, stock: parseInt(stock) })
            });
            const data = await resp.json();
            if (data.success) {
                ['v_color','v_color_code','v_size','v_sku','v_price'].forEach(id => document.getElementById(id).value = '');
                document.getElementById('v_stock').value = '0';
                await refreshVariantsList();
            } else { alert(data.errors ? Object.values(data.errors).flat().join('\n') : (data.error || 'Failed to add variant.')); }
        } catch(e) { alert('Network error. Please try again.'); }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus me-1"></i> Add Variant';
    }

    async function deleteVariant(variantId) {
        if (!confirm('Delete this variant? This cannot be undone.')) return;
        try {
            const resp = await fetch(`/products/${currentVariantsProductId}/variants/${variantId}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            const data = await resp.json();
            if (data.success) { await refreshVariantsList(); }
            else { alert(data.error || 'Failed to delete variant.'); }
        } catch(e) { alert('Network error. Please try again.'); }
    }

    // ── Advanced Barcode ───────────────────────────────────────────────────────────────
    let _bcProduct = {};
    let _bcTemplates = {
        standard: { height: '25mm', display: ['brand', 'tagline', 'sku', 'name', 'price'] },
        compact: { height: '20mm', display: ['brand', 'sku', 'price'] },
        minimal: { height: '25mm', display: ['sku', 'price'] }
    };
    let _currentTemplate = 'standard';
    let _customText = { brand: 'APPAREL OUTLET', tagline: 'PREMIUM QUALITY | 100% COTTON' };

    function openBarcodeModal(id, name, sku, price) {
        _bcProduct = { id, name, sku, price: parseFloat(price).toLocaleString('en-US', { minimumFractionDigits: 0 }) };
        
        // Populate the dropdown with this product only (simulate selection)
        const sel = document.getElementById('bc-product-select');
        sel.innerHTML = `<option value="${id}" selected>${name} (${sku}) - Rs. ${_bcProduct.price}</option>`;

        // Update customized texts dynamically
        document.getElementById('val-sku').textContent = sku;
        document.getElementById('val-name').textContent = name;
        document.getElementById('val-price').textContent = 'Rs. ' + _bcProduct.price;

        document.getElementById('bc-qty').value      = 1;
        document.getElementById('bc-per-row').value  = 2;
        document.getElementById('bc-format').value   = 'CODE128';
        
        resetBarcodeDefaults();
        new bootstrap.Modal(document.getElementById('barcodeModal')).show();
    }

    function getBarcodeOptions(format) {
        const size = document.getElementById('bc-size').value;
        const options = { format, lineColor: '#000', width: 1.5, height: 30, displayValue: false, margin: 0 };

        if (size === '38x25') {
            options.width = 1.15;
            options.height = 28;
        } else if (size === '38x20') {
            options.width = 1.2;
            options.height = 24;
        }

        return options;
    }

    function applySizeSpecificPreviewStyles() {
        const size = document.getElementById('bc-size').value;
        const barcode = document.getElementById('barcode-svg');
        const brand = document.getElementById('prev-brand');
        const tagline = document.getElementById('prev-tagline');
        const sku = document.getElementById('prev-sku');
        const name = document.getElementById('prev-name');
        const price = document.getElementById('prev-price');

        barcode.style.maxWidth = size === '38x25' ? '32mm' : (size === '38x20' ? '33mm' : '100%');
        barcode.style.height = size === '38x20' ? '6mm' : '7mm';

        brand.style.fontSize = size === '38x20' ? '7.2pt' : '8pt';
        brand.style.marginBottom = size === '38x20' ? '0.25mm' : '0.5mm';
        tagline.style.fontSize = size === '38x20' ? '3.8pt' : '4.5pt';
        tagline.style.marginBottom = size === '38x20' ? '0.35mm' : '1mm';
        sku.style.fontSize = size === '38x20' ? '5pt' : '6pt';
        sku.style.marginTop = size === '38x20' ? '0.25mm' : '0.5mm';
        name.style.fontSize = size === '38x20' ? '4pt' : '5pt';
        name.style.marginTop = size === '38x20' ? '0.2mm' : '0.5mm';
        price.style.fontSize = size === '38x20' ? '6.6pt' : '7.5pt';
        price.style.marginTop = size === '38x20' ? '0.25mm' : '0.5mm';
    }

    function generateLiveBarcode() {
        if (!_bcProduct.sku) return;
        
        // Update DOM elements
        document.getElementById('prev-brand').textContent = _customText.brand;
        document.getElementById('prev-tagline').textContent = _customText.tagline;
        document.getElementById('prev-sku').textContent = _bcProduct.sku;
        document.getElementById('prev-name').textContent = _bcProduct.name;
        document.getElementById('prev-price').textContent = 'Rs. ' + _bcProduct.price;

        const fmt = document.getElementById('bc-format').value;
        try {
            JsBarcode('#barcode-svg', _bcProduct.sku, getBarcodeOptions(fmt));
        } catch(e) {
            JsBarcode('#barcode-svg', _bcProduct.sku, getBarcodeOptions('CODE128'));
            document.getElementById('bc-format').value = 'CODE128';
        }
        applySizeSpecificPreviewStyles();
    }

    function togglePreview(field) {
        const isChecked = document.getElementById('chk-' + field).checked;
        const el = document.getElementById('prev-' + field);
        if (el) el.style.display = isChecked ? 'block' : 'none';
    }

    function editContent(field) {
        let currentText = '';
        if (field === 'brand') currentText = _customText.brand;
        else if (field === 'tagline') currentText = _customText.tagline;
        else if (field === 'sku') currentText = _bcProduct.sku;
        else if (field === 'name') currentText = _bcProduct.name;
        else if (field === 'price') currentText = 'Rs. ' + _bcProduct.price;

        const newText = prompt(`Enter new text for ${field.toUpperCase()}:`, currentText);
        if (newText !== null) {
            if (field === 'brand') _customText.brand = newText;
            else if (field === 'tagline') _customText.tagline = newText;
            else if (field === 'sku') _bcProduct.sku = newText;
            else if (field === 'name') _bcProduct.name = newText;
            else if (field === 'price') _bcProduct.price = newText.replace('Rs. ', ''); // Store cleanly if needed

            document.getElementById('val-' + field).textContent = field === 'price' && !newText.includes('Rs') ? 'Rs. ' + newText : newText;
            generateLiveBarcode();
        }
    }

    function applyTemplate(tmpl, el) {
        document.querySelectorAll('.template-card').forEach(c => {
            c.style.borderColor = '#dee2e6';
            const icon = c.querySelector('i');
            if (icon) icon.remove();
        });
        if(el) {
            el.style.borderColor = '#f59e0b';
            el.innerHTML += '<i class="fas fa-check-circle text-warning position-absolute top-0 end-0 m-1"></i>';
        }

        _currentTemplate = tmpl;
        const conf = _bcTemplates[tmpl];
        
        // Update checkboxes
        ['brand', 'tagline', 'sku', 'name', 'price'].forEach(f => {
            const chk = document.getElementById('chk-' + f);
            chk.checked = conf.display.includes(f);
            togglePreview(f);
        });

        // Update Size Dropdown
        const sizeSel = document.getElementById('bc-size');
        if (tmpl === 'compact') sizeSel.value = '38x20';
        else sizeSel.value = '38x25';
        updateLabelSize();
    }

    function updateLabelSize() {
        const val = document.getElementById('bc-size').value; // 38x25
        const [w, h] = val.split('x');
        const wrap = document.getElementById('barcode-preview-wrap');
        wrap.style.width = w + 'mm';
        wrap.style.height = h + 'mm';
        
        document.getElementById('lbl-dim-w').textContent = w + 'mm';
        document.getElementById('lbl-dim-h').textContent = h + 'mm';
        document.getElementById('lbl-size-badge').textContent = `Size: ${w}mm x ${h}mm`;
        applySizeSpecificPreviewStyles();
        generateLiveBarcode();
    }

    function updatePreviewStyles() {
        const font = document.getElementById('bc-font').value;
        const isBold = document.getElementById('bc-bold').checked;
        const wrap = document.getElementById('barcode-preview-wrap');
        
        wrap.style.fontFamily = font;
        wrap.style.fontWeight = isBold ? 'bold' : 'normal';
    }

    function resetBarcodeDefaults() {
        _customText = { brand: 'APPAREL OUTLET', tagline: 'PREMIUM QUALITY | 100% COTTON' };
        document.getElementById('val-brand').textContent = _customText.brand;
        document.getElementById('val-tagline').textContent = _customText.tagline;
        
        document.getElementById('bc-font').value = "'Zebra 0', sans-serif";
        document.getElementById('bc-bold').checked = true;
        
        applyTemplate('standard', document.querySelector('.template-card'));
        updatePreviewStyles();
        generateLiveBarcode();
    }

    // ── Add Product Modal — Variant Rows ─────────────────────────────────────
    let _addVariantIdx = 0;

    function addProductVariantRow() {
        const idx   = _addVariantIdx++;
        const tbody = document.getElementById('add_variants_tbody');
        const row   = document.createElement('tr');
        row.id      = 'avrow_' + idx;
        row.innerHTML = `
            <td class="text-center" style="padding:4px 6px;">
                <input type="color" id="avc_picker_${idx}"
                    class="form-control form-control-color p-0 border-0" value="#FFD700"
                    style="width:30px;height:30px;cursor:pointer;"
                    oninput="document.getElementById('avc_code_${idx}').value = this.value">
            </td>
            <td style="padding:4px 4px;">
                <input type="text" name="variants[${idx}][color]" id="avc_color_${idx}"
                    class="form-control form-control-sm" placeholder="e.g. Gold" list="colorSuggestions">
            </td>
            <td style="padding:4px 4px;">
                <input type="text" name="variants[${idx}][size]"
                    class="form-control form-control-sm" placeholder="e.g. M, 7, Free Size" list="sizeSuggestions">
            </td>
            <td style="padding:4px 4px;">
                <input type="text" name="variants[${idx}][sku]"
                    class="form-control form-control-sm" placeholder="Optional">
            </td>
            <td style="padding:4px 4px;">
                <input type="number" name="variants[${idx}][price]"
                    class="form-control form-control-sm" placeholder="Blank = product price" step="0.01" min="0">
            </td>
            <td style="padding:4px 4px;">
                <input type="number" name="variants[${idx}][stock]"
                    class="form-control form-control-sm" value="0" min="0">
                <input type="hidden" name="variants[${idx}][color_code]" id="avc_code_${idx}" value="#FFD700">
            </td>
            <td class="text-center" style="padding:4px 2px;">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeProductVariantRow(${idx})" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </td>`;
        tbody.appendChild(row);
        _updateAddVariantUI();
    }

    function removeProductVariantRow(idx) {
        const row = document.getElementById('avrow_' + idx);
        if (row) row.remove();
        _updateAddVariantUI();
    }

    function _updateAddVariantUI() {
        const tbody  = document.getElementById('add_variants_tbody');
        const count  = tbody ? tbody.querySelectorAll('tr').length : 0;
        document.getElementById('add_variant_count').textContent = count;
        document.getElementById('add_variant_rows_empty').classList.toggle('d-none',  count > 0);
        document.getElementById('add_variants_table_wrap').classList.toggle('d-none', count === 0);
        document.getElementById('add_variant_footer_hint').classList.toggle('d-none', count === 0);
        // When variants exist, hint user to set stock 0
        const stockHint = document.getElementById('add_stock_hint');
        if (stockHint) stockHint.classList.toggle('text-warning', count > 0);
    }

    // ── Real-time DOM helpers ──────────────────────────────────────────────
    function showProductToast(message, type = 'success') {
        const colors = {success:'#198754',danger:'#dc3545',warning:'#856404',info:'#0c63e4'};
        const icons  = {success:'fa-check-circle',danger:'fa-exclamation-circle',warning:'fa-exclamation-triangle',info:'fa-info-circle'};
        const el = document.createElement('div');
        el.style.cssText = `position:fixed;top:70px;right:20px;z-index:9999;background:${colors[type]||colors.success};color:#fff;padding:10px 18px;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.18);font-size:.9rem;max-width:340px;transition:opacity .4s;`;
        el.innerHTML = `<i class="fas ${icons[type]||icons.success} me-2"></i>${message}`;
        document.body.appendChild(el);
        setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 420); }, 2800);
    }

    function buildVariantDotsHtml(variants) {
        if (!variants || variants.length === 0) return '';
        let html = '';
        variants.slice(0, 6).forEach(v => {
            const tip = [v.color, v.size].filter(Boolean).join(' / ');
            if (v.color_code) {
                html += `<span title="${tip} (${v.stock})" style="width:16px;height:16px;border-radius:50%;display:inline-block;border:1px solid #ccc;background:${v.color_code};"></span>`;
            } else if (v.size) {
                html += `<span class="badge bg-secondary" title="${tip} (${v.stock})" style="font-size:.6rem;padding:2px 4px;">${v.size}</span>`;
            } else {
                html += `<span title="${tip} (${v.stock})" style="width:16px;height:16px;border-radius:50%;display:inline-block;border:1px solid #ccc;background:#ccc;"></span>`;
            }
        });
        if (variants.length > 6) html += `<small class="text-muted">+${variants.length - 6}</small>`;
        if (variants.length > 0) html += `<small class="text-muted ms-1">(${variants.length} variants)</small>`;
        return html;
    }

    function updateProductRowStock(productId, stock, variantCount) {
        stock = parseInt(stock) || 0;
        const cls = stock > 5 ? 'text-success' : (stock > 0 ? 'text-warning' : 'text-danger');
        const el = document.getElementById(`ptd-stock-${productId}`);
        if (el) el.innerHTML = `<span class="fw-semibold ${cls}">${stock}</span>` +
            (variantCount > 0 ? `<br><small class="text-muted">across ${variantCount} variants</small>` : '');
        const st = document.getElementById(`ptd-status-${productId}`);
        if (st) st.innerHTML = stock > 0 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Out of Stock</span>';
        const tr = document.querySelector(`tr[data-product-id="${productId}"]`);
        if (tr) tr.dataset.stock = stock <= 0 ? 'out' : (stock <= 5 ? 'low' : 'instock');
    }

    function updateProductRowVariants(productId, variants) {
        const el = document.getElementById(`ptd-vdots-${productId}`);
        if (el) el.innerHTML = buildVariantDotsHtml(variants);
        const total = (variants || []).reduce((s, v) => s + (parseInt(v.stock) || 0), 0);
        updateProductRowStock(productId, total, (variants || []).length);
    }

    function updateProductRow(product) {
        const tr = document.querySelector(`tr[data-product-id="${product.id}"]`);
        if (!tr) return;
        const variants = product.variants || [];
        const total = variants.length > 0 ? variants.reduce((s, v) => s + (parseInt(v.stock) || 0), 0) : parseInt(product.stock) || 0;
        const imgEl = document.getElementById(`ptd-img-${product.id}`);
        if (imgEl) imgEl.innerHTML = product.image_url
            ? `<img src="${product.image_url}" alt="${product.name}" class="rounded" style="width:40px;height:40px;object-fit:cover;">`
            : `<div class="d-flex align-items-center justify-content-center rounded" style="width:40px;height:40px;background:var(--light-grey);"><i class="fas fa-tshirt text-gold"></i></div>`;
        const nameEl = document.getElementById(`ptd-nametxt-${product.id}`);
        if (nameEl) nameEl.textContent = product.name;
        const skuEl = document.getElementById(`ptd-sku-${product.id}`);
        if (skuEl) skuEl.textContent = product.sku;
        const catEl = document.getElementById(`ptd-cat-${product.id}`);
        if (catEl) catEl.innerHTML = `<span class="badge bg-light text-dark border">${product.category || '&mdash;'}</span>` +
            (product.subcategory ? `<br><span class="badge bg-secondary bg-opacity-25 text-secondary border mt-1" style="font-size:.7rem;"><i class="fas fa-angle-right me-1"></i>${product.subcategory}</span>` : '');
        const priceEl = document.getElementById(`ptd-price-${product.id}`);
        if (priceEl) priceEl.textContent = 'Rs. ' + parseFloat(product.price).toLocaleString('en-US', {minimumFractionDigits:2});
        updateProductRowStock(product.id, total, variants.length);
        const vdotsEl = document.getElementById(`ptd-vdots-${product.id}`);
        if (vdotsEl) vdotsEl.innerHTML = buildVariantDotsHtml(variants);
        const editBtn = document.getElementById(`ptd-editbtn-${product.id}`);
        if (editBtn) {
            const esc = s => (s || '').replace(/'/g, "\\'");
            editBtn.setAttribute('onclick', `openEdit(${product.id},'${esc(product.name)}','${esc(product.sku)}','${esc(product.category)}','${esc(product.subcategory)}','${product.price}','${total}','${esc(product.image_url)}')`);
        }
    }

    function prependProductRow(product) {
        const tbody = document.querySelector('.pos-table tbody');
        if (!tbody) return;
        const emptyMsg = tbody.querySelector('td[colspan="10"]');
        if (emptyMsg) emptyMsg.closest('tr').remove();
        const variants = product.variants || [];
        const total = variants.length > 0 ? variants.reduce((s, v) => s + (parseInt(v.stock) || 0), 0) : parseInt(product.stock) || 0;
        const esc = s => (s || '').replace(/'/g, "\\'");
        const stockCls = total > 5 ? 'text-success' : (total > 0 ? 'text-warning' : 'text-danger');
        const imgHtml = product.image_url
            ? `<img src="${product.image_url}" alt="${product.name}" class="rounded" style="width:40px;height:40px;object-fit:cover;">`
            : `<div class="d-flex align-items-center justify-content-center rounded" style="width:40px;height:40px;background:var(--light-grey);"><i class="fas fa-tshirt text-gold"></i></div>`;
        const catHtml = `<span class="badge bg-light text-dark border">${product.category || '&mdash;'}</span>` +
            (product.subcategory ? `<br><span class="badge bg-secondary bg-opacity-25 text-secondary border mt-1" style="font-size:.7rem;"><i class="fas fa-angle-right me-1"></i>${product.subcategory}</span>` : '');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const eN = esc(product.name), eSku = esc(product.sku), eCat = esc(product.category), eSub = esc(product.subcategory), eImg = esc(product.image_url);
        const tr = document.createElement('tr');
        tr.setAttribute('data-source', 'local');
        tr.setAttribute('data-stock', total <= 0 ? 'out' : (total <= 5 ? 'low' : 'instock'));
        tr.setAttribute('data-product-id', product.id);
        tr.innerHTML = `
            <td>${product.id}</td>
            <td id="ptd-img-${product.id}">${imgHtml}</td>
            <td id="ptd-name-${product.id}">
                <strong id="ptd-nametxt-${product.id}">${product.name}</strong>
                <div id="ptd-vdots-${product.id}" class="d-flex align-items-center gap-1 mt-1 flex-wrap">${buildVariantDotsHtml(variants)}</div>
            </td>
            <td><code id="ptd-sku-${product.id}">${product.sku}</code></td>
            <td id="ptd-cat-${product.id}">${catHtml}</td>
            <td class="fw-bold" id="ptd-price-${product.id}">Rs. ${parseFloat(product.price).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
            <td id="ptd-stock-${product.id}"><span class="fw-semibold ${stockCls}">${total}</span>${variants.length > 0 ? '<br><small class="text-muted">across ' + variants.length + ' variants</small>' : ''}</td>
            <td><span class="badge bg-warning text-dark"><i class="fas fa-store me-1"></i> Local</span></td>
            <td id="ptd-status-${product.id}">${total > 0 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Out of Stock</span>'}</td>
            <td>
                <button class="btn btn-sm btn-outline-info me-1" title="Manage Variants"
                    onclick="openVariantsModal(${product.id},'${eN}',false)"><i class="fas fa-palette"></i></button>
                <button class="btn btn-sm btn-outline-warning me-1" title="Print Barcode"
                    onclick="openBarcodeModal(${product.id},'${eN}','${eSku}','${product.price}')"><i class="fas fa-barcode"></i></button>
                <button class="btn btn-sm btn-outline-primary me-1" title="Edit" id="ptd-editbtn-${product.id}"
                    onclick="openEdit(${product.id},'${eN}','${eSku}','${eCat}','${eSub}','${product.price}','${total}','${eImg}')"><i class="fas fa-edit"></i></button>
                <form method="POST" action="/products/${product.id}" class="delete-product-form d-inline"
                    data-product-id="${product.id}" data-product-name="${eN}">
                    <input type="hidden" name="_token" value="${csrf}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
            </td>`;
        tbody.prepend(tr);
        attachDeleteHandler(tr.querySelector('.delete-product-form'));
    }

    function attachDeleteHandler(form) {
        if (!form) return;
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const productId   = this.dataset.productId;
            const productName = this.dataset.productName || 'this product';
            if (!confirm(`Delete product "${productName}"? This cannot be undone.`)) return;
            try {
                const resp = await fetch(this.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(this)
                });
                const data = await resp.json();
                if (resp.ok && data.success) {
                    const tr = document.querySelector(`tr[data-product-id="${productId}"]`);
                    if (tr) { tr.style.transition = 'opacity .3s'; tr.style.opacity = '0'; setTimeout(() => tr.remove(), 320); }
                    showProductToast(data.message || 'Product deleted.', 'success');
                } else { alert(data.message || 'Delete failed.'); }
            } catch(err) { alert('Network error.'); }
        });
    }

    // Reset variant rows when add modal is closed
    document.addEventListener('DOMContentLoaded', () => {
        const addModal = document.getElementById('addProductModal');
        if (addModal) {
            addModal.addEventListener('hidden.bs.modal', () => {
                document.getElementById('add_variants_tbody').innerHTML = '';
                _addVariantIdx = 0;
                _updateAddVariantUI();
            });
        }

        // Intercept Add Product form — AJAX submit
        const addForm = document.getElementById('addProductForm');
        if (addForm) {
            addForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const btn = this.querySelector('[type=submit]');
                const origHtml = btn.innerHTML;
                btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
                this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                try {
                    const resp = await fetch(this.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: new FormData(this)
                    });
                    const data = await resp.json();
                    if (resp.ok && data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
                        this.reset();
                        document.getElementById('add_image_preview').classList.add('d-none');
                        prependProductRow(data.product);
                        showProductToast('"' + data.product.name + '" added successfully.', 'success');
                    } else if (resp.status === 422 && data.errors) {
                        Object.entries(data.errors).forEach(([field, msgs]) => {
                            const inp = this.querySelector(`[name="${field}"]`);
                            if (!inp) return;
                            inp.classList.add('is-invalid');
                            let fb = inp.nextElementSibling;
                            if (!fb || !fb.classList.contains('invalid-feedback')) { fb = document.createElement('div'); fb.className = 'invalid-feedback'; inp.after(fb); }
                            fb.textContent = msgs[0];
                        });
                    } else { alert(data.message || 'Failed to save product.'); }
                } catch(err) { alert('Network error.'); }
                btn.disabled = false; btn.innerHTML = origHtml;
            });
        }

        // Intercept Edit Product form — AJAX submit
        const editForm = document.getElementById('editProductForm');
        if (editForm) {
            editForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const btn = this.querySelector('[type=submit]');
                const origHtml = btn.innerHTML;
                btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
                this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                try {
                    const resp = await fetch(this.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: new FormData(this)
                    });
                    const data = await resp.json();
                    if (resp.ok && data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
                        updateProductRow(data.product);
                        showProductToast('"' + data.product.name + '" updated successfully.', 'success');
                    } else if (resp.status === 422 && data.errors) {
                        Object.entries(data.errors).forEach(([field, msgs]) => {
                            const inp = this.querySelector(`[name="${field}"]`);
                            if (!inp) return;
                            inp.classList.add('is-invalid');
                            let fb = inp.nextElementSibling;
                            if (!fb || !fb.classList.contains('invalid-feedback')) { fb = document.createElement('div'); fb.className = 'invalid-feedback'; inp.after(fb); }
                            fb.textContent = msgs[0];
                        });
                    } else { alert(data.message || 'Failed to update product.'); }
                } catch(err) { alert('Network error.'); }
                btn.disabled = false; btn.innerHTML = origHtml;
            });
        }

        // Wire up all existing delete forms
        document.querySelectorAll('.delete-product-form').forEach(attachDeleteHandler);
    });

    function executeBarcodePrint() {
        const qty = Math.max(1, parseInt(document.getElementById('bc-qty').value) || 1);
        const perRow = parseInt(document.getElementById('bc-per-row').value) || 2;
        const [w, h] = document.getElementById('bc-size').value.split('x'); // e.g. 38, 25
        
        const font = document.getElementById('bc-font').value;
        const isBold = document.getElementById('bc-bold').checked ? 'bold' : 'normal';

        // Get inner HTML of the preview wrap to clone exact visibility
        const wrap = document.getElementById('barcode-preview-wrap');
        
        // We need to clone it to strip out the dimensions indicators before printing
        const clone = wrap.cloneNode(true);
        const dim1 = clone.querySelector('#lbl-dim-h'); if(dim1) dim1.remove();
        const dim2 = clone.querySelector('#lbl-dim-w'); if(dim2) dim2.remove();
        
        const singleLabelHtml = clone.innerHTML;

        const sizeClass = `size-${w}x${h}`;
        const allLabels = Array(qty).fill(`<div class="label ${sizeClass}">${singleLabelHtml}</div>`).join('');
        
        // Calculate grid width based on perRow (width * perRow + gaps)
        const totalWidth = (parseInt(w) * perRow) + ((perRow - 1) * 2); // 2mm gap

        const printHtml = `<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Barcode Print</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: ${font}; font-weight: ${isBold}; background:#fff; margin: 0; padding: 0; }
        .grid {
            display: grid;
            grid-template-columns: repeat(${perRow}, ${w}mm);
            gap: 2mm;
            width: ${totalWidth}mm;
            margin: 0 auto;
            justify-content: center;
        }
        .label {
            width: ${w}mm;
            height: ${h}mm;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            page-break-inside: avoid;
            break-inside: avoid;
            background: #fff;
            padding: 1mm;
        }
        .label.size-38x25 #barcode-svg {
            max-width: 32mm !important;
            height: 7mm !important;
            flex-shrink: 0;
        }
        .label.size-38x20 {
            padding: 0.6mm 1mm;
        }
        .label.size-38x20 #barcode-svg {
            max-width: 33mm !important;
            height: 6mm !important;
            flex-shrink: 0;
        }
        .label.size-38x20 #prev-brand {
            font-size: 7.2pt !important;
            margin-bottom: 0.25mm !important;
        }
        .label.size-38x20 #prev-tagline {
            font-size: 3.8pt !important;
            margin-bottom: 0.35mm !important;
        }
        .label.size-38x20 #prev-sku {
            font-size: 5pt !important;
            margin-top: 0.25mm !important;
        }
        .label.size-38x20 #prev-name {
            font-size: 4pt !important;
            line-height: 1 !important;
            margin-top: 0.2mm !important;
            max-height: 4.2pt;
        }
        .label.size-38x20 #prev-price {
            font-size: 6.6pt !important;
            margin-top: 0.25mm !important;
        }
        
        @page { size: ${totalWidth}mm auto; margin: 0; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .grid { padding-top: 2mm; } 
        }
    </style>
</head>
<body>
    <div class="grid">${allLabels}</div>
    <script>window.onload=function(){ setTimeout(function(){ window.print(); }, 300); };<\/script>
</body>
</html>`;

        const win = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');
        if (!win) { alert('Pop-up blocked. Please allow pop-ups for this site to print barcodes.'); return; }
        win.document.open();
        win.document.write(printHtml);
        win.document.close();
    }
</script>
@endpush
