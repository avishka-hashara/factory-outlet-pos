@extends('layouts.pos')

@section('title', 'Settings')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4><i class="fas fa-cog me-2 text-gold"></i> Settings</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Settings</li>
            </ol>
        </nav>
    </div>
</div>

@if ($message = Session::get('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- Settings Navigation -->
    <div class="col-md-3">
        <div class="list-group">
            <a href="#general" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                <i class="fas fa-sliders-h me-2"></i> General Settings
            </a>
            <a href="#store" class="list-group-item list-group-item-action" data-bs-toggle="list">
                <i class="fas fa-store me-2"></i> Store Information
            </a>
            <a href="#billing" class="list-group-item list-group-item-action" data-bs-toggle="list">
                <i class="fas fa-receipt me-2"></i> Billing
            </a>
            <a href="#invoice" class="list-group-item list-group-item-action" data-bs-toggle="list">
                <i class="fas fa-file-invoice me-2"></i> Invoice / Receipt
            </a>
            <a href="#barcode" class="list-group-item list-group-item-action" data-bs-toggle="list">
                <i class="fas fa-barcode me-2"></i> Barcode
            </a>
            <a href="#security" class="list-group-item list-group-item-action" data-bs-toggle="list">
                <i class="fas fa-lock me-2"></i> Security
            </a>
        </div>
    </div>

    <!-- Settings Content -->
    <div class="col-md-9">
        <div class="tab-content">
            <!-- General Settings -->
            <div class="tab-pane fade show active" id="general">
                <div class="card shadow-sm">
                    <div class="card-header bg-gold text-white">
                        <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i> General Settings</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="app_name" class="form-label">Application Name</label>
                                <input type="text" class="form-control" id="app_name" name="app_name" value="{{ \App\Models\Setting::get('app_name', config('app.name', 'Clothing Outlet POS')) }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">System Logo
                                    <small class="text-muted fw-normal">(shown in the top navbar &amp; sidebar)</small>
                                </label>
                                @if(!empty($settings['system_logo']))
                                    <div class="mb-2">
                                        <img src="{{ asset($settings['system_logo']) }}" alt="Current System Logo" id="system-logo-preview"
                                             style="max-height:60px;max-width:200px;object-fit:contain;border:1px solid #dee2e6;padding:6px;border-radius:6px;background:#1f2937;">
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="remove_system_logo" id="remove_system_logo" value="1">
                                        <label class="form-check-label text-danger" for="remove_system_logo">Remove current logo (revert to default)</label>
                                    </div>
                                @else
                                    <div class="mb-2">
                                        <img src="" alt="" id="system-logo-preview"
                                             style="max-height:60px;max-width:200px;object-fit:contain;border:1px solid #dee2e6;padding:6px;border-radius:6px;background:#1f2937;display:none;">
                                    </div>
                                @endif
                                <input type="file" class="form-control" id="system_logo" name="system_logo" accept="image/*">
                                <small class="text-muted">Recommended: square or wide PNG with a transparent background. Supports JPG, PNG, GIF, SVG, WEBP &mdash; max 2&nbsp;MB.</small>
                            </div>

                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-select" id="currency" name="currency">
                                    <option value="USD" {{ ($settings['currency'] ?? '') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                                    <option value="EUR" {{ ($settings['currency'] ?? '') == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                                    <option value="GBP" {{ ($settings['currency'] ?? '') == 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                                    <option value="LKR" {{ ($settings['currency'] ?? 'LKR') == 'LKR' ? 'selected' : '' }}>LKR (₨)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="timezone" class="form-label">Timezone</label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <option value="UTC" {{ ($settings['timezone'] ?? '') == 'UTC' ? 'selected' : '' }}>UTC</option>
                                    <option value="Asia/Colombo" {{ ($settings['timezone'] ?? 'Asia/Colombo') == 'Asia/Colombo' ? 'selected' : '' }}>Asia/Colombo</option>
                                    <option value="Asia/Bangkok" {{ ($settings['timezone'] ?? '') == 'Asia/Bangkok' ? 'selected' : '' }}>Asia/Bangkok</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-gold">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Store Information -->
            <div class="tab-pane fade" id="store">
                <div class="card shadow-sm">
                    <div class="card-header bg-gold text-white">
                        <h5 class="mb-0"><i class="fas fa-store me-2"></i> Store Information</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('settings.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <div class="mb-3">
                                <label for="store_name" class="form-label">Store Name</label>
                                <input type="text" class="form-control" id="store_name" name="store_name" value="{{ $settings['store_name'] ?? '' }}" placeholder="Enter store name">
                            </div>

                            <div class="mb-3">
                                <label for="store_email" class="form-label">Store Email</label>
                                <input type="email" class="form-control" id="store_email" name="store_email" value="{{ $settings['store_email'] ?? '' }}" placeholder="Enter store email">
                            </div>

                            <div class="mb-3">
                                <label for="store_phone" class="form-label">Store Phone</label>
                                <input type="tel" class="form-control" id="store_phone" name="store_phone" value="{{ $settings['store_phone'] ?? '' }}" placeholder="Enter store phone">
                            </div>

                            <div class="mb-3">
                                <label for="store_address" class="form-label">Store Address</label>
                                <textarea class="form-control" id="store_address" name="store_address" rows="3" placeholder="Enter store address">{{ $settings['store_address'] ?? '' }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-gold">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Billing Settings -->
            <div class="tab-pane fade" id="billing">
                <div class="card shadow-sm">
                    <div class="card-header bg-gold text-white">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i> Billing Settings</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('settings.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <div class="mb-3">
                                <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                <input type="number" class="form-control" id="tax_rate" name="tax_rate" step="0.01" value="{{ $settings['tax_rate'] ?? '' }}" placeholder="e.g., 10">
                            </div>

                            <div class="mb-3">
                                <label for="discount_enabled" class="form-check-label">
                                    <input type="checkbox" class="form-check-input" id="discount_enabled" name="discount_enabled" {{ ($settings['discount_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                    Enable Discounts
                                </label>
                            </div>

                            <div class="mb-3">
                                <label for="invoice_prefix" class="form-label">Invoice Prefix</label>
                                <input type="text" class="form-control" id="invoice_prefix" name="invoice_prefix" value="{{ $settings['invoice_prefix'] ?? 'INV-' }}" placeholder="Invoice prefix">
                            </div>

                            <button type="submit" class="btn btn-gold">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Invoice / Receipt Settings -->
            <div class="tab-pane fade" id="invoice">
                <div class="card shadow-sm">
                    <div class="card-header bg-gold text-white">
                        <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i> Invoice / Receipt Settings</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="fas fa-heading me-1"></i> Header Details</h6>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Store Logo <small class="text-muted fw-normal">(displayed at the top of every receipt)</small></label>
                                @if(!empty($settings['invoice_logo']))
                                    <div class="mb-2">
                                        <img src="{{ asset($settings['invoice_logo']) }}" alt="Current Logo" id="current-logo-preview" style="max-height:80px;max-width:220px;object-fit:contain;border:1px solid #dee2e6;padding:6px;border-radius:6px;background:#f8f9fa;">
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="remove_invoice_logo" id="remove_invoice_logo" value="1">
                                        <label class="form-check-label text-danger" for="remove_invoice_logo">Remove current logo (revert to diamond icon)</label>
                                    </div>
                                @else
                                    <div class="mb-2">
                                        <img src="" alt="" id="current-logo-preview" style="max-height:80px;max-width:220px;object-fit:contain;border:1px solid #dee2e6;padding:6px;border-radius:6px;background:#f8f9fa;display:none;">
                                    </div>
                                @endif
                                <input type="file" class="form-control" id="invoice_logo" name="invoice_logo" accept="image/*">
                                <small class="text-muted">Recommended: PNG with transparent background, max 500&times;200 px. Supports JPG, PNG, GIF, SVG.</small>
                            </div>

                            <div class="mb-3">
                                <label for="invoice_shop_name" class="form-label">Shop Name</label>
                                <input type="text" class="form-control" id="invoice_shop_name" name="invoice_shop_name" value="{{ $settings['invoice_shop_name'] ?? 'Jewellery POS' }}" placeholder="e.g., Jewellery POS">
                                <small class="text-muted">Displayed as the main title on the receipt</small>
                            </div>

                            <div class="mb-3">
                                <label for="invoice_tagline" class="form-label">Tagline / Subtitle</label>
                                <input type="text" class="form-control" id="invoice_tagline" name="invoice_tagline" value="{{ $settings['invoice_tagline'] ?? 'Premium Jewellery Store' }}" placeholder="e.g., Premium Jewellery Store">
                            </div>

                            <div class="mb-3">
                                <label for="invoice_address" class="form-label">Address</label>
                                <textarea class="form-control" id="invoice_address" name="invoice_address" rows="2" placeholder="Shop address for receipt">{{ $settings['invoice_address'] ?? '123 Main Street, Colombo 03' }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="invoice_phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="invoice_phone" name="invoice_phone" value="{{ $settings['invoice_phone'] ?? '+94 11 234 5678' }}" placeholder="+94 XX XXX XXXX">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="invoice_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="invoice_email" name="invoice_email" value="{{ $settings['invoice_email'] ?? 'info@jewellerypos.com' }}" placeholder="shop@example.com">
                                </div>
                            </div>

                            <hr class="my-4">

                            <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="fas fa-shoe-prints me-1"></i> Footer Details</h6>

                            <div class="mb-3">
                                <label for="invoice_thank_you" class="form-label">Thank You Message</label>
                                <input type="text" class="form-control" id="invoice_thank_you" name="invoice_thank_you" value="{{ $settings['invoice_thank_you'] ?? 'Thank you for your purchase!' }}" placeholder="e.g., Thank you for your purchase!">
                            </div>

                            <div class="mb-3">
                                <label for="invoice_footer_line1" class="form-label">Footer Line 1</label>
                                <input type="text" class="form-control" id="invoice_footer_line1" name="invoice_footer_line1" value="{{ $settings['invoice_footer_line1'] ?? 'Goods once sold cannot be returned.' }}" placeholder="e.g., Return policy note">
                            </div>

                            <div class="mb-3">
                                <label for="invoice_footer_line2" class="form-label">Footer Line 2</label>
                                <input type="text" class="form-control" id="invoice_footer_line2" name="invoice_footer_line2" value="{{ $settings['invoice_footer_line2'] ?? 'Exchange within 7 days with receipt.' }}" placeholder="e.g., Exchange policy note">
                            </div>

                            <div class="mb-3">
                                <label for="invoice_footer_line3" class="form-label">Footer Line 3 <small class="text-muted">(optional)</small></label>
                                <input type="text" class="form-control" id="invoice_footer_line3" name="invoice_footer_line3" value="{{ $settings['invoice_footer_line3'] ?? '' }}" placeholder="e.g., Visit us at www.example.com">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Branding / Footer Credit Text <i class="fas fa-lock text-muted ms-1" title="Locked"></i></label>
                                <input type="text" class="form-control bg-light" value="POSSYSTEM.LK" readonly disabled>
                                <small class="text-muted">Fixed system branding &mdash; always shown at the very bottom of every receipt and cannot be changed.</small>
                            </div>

                            <hr class="my-4">

                            <!-- Live Preview -->
                            <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="fas fa-eye me-1"></i> Live Preview</h6>
                            <div class="d-flex justify-content-center mb-3">
                                <div style="width:302px;background:#fff;border:1px solid #ddd;padding:12px 10px;font-family:'Courier New',monospace;font-size:12px;color:#000;box-shadow:0 2px 10px rgba(0,0,0,0.08);">
                                    <div style="text-align:center;">
                                        <div id="preview-logo-wrap" style="{{ !empty($settings['invoice_logo']) ? '' : 'display:none;' }}margin-bottom:4px;">
                                            <img id="preview-logo-img" src="{{ !empty($settings['invoice_logo']) ? asset($settings['invoice_logo']) : '' }}" alt="Logo" style="max-height:60px;max-width:160px;object-fit:contain;">
                                        </div>
                                        <div id="preview-diamond" style="{{ !empty($settings['invoice_logo']) ? 'display:none;' : '' }}font-size:28px;">&#9670;</div>
                                        <div id="preview-shop-name" style="font-size:16px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">{{ $settings['invoice_shop_name'] ?? 'Jewellery POS' }}</div>
                                        <div id="preview-tagline" style="font-size:10px;color:#555;">{{ $settings['invoice_tagline'] ?? 'Premium Jewellery Store' }}</div>
                                        <div id="preview-info" style="font-size:10px;color:#333;line-height:1.5;margin-top:4px;">
                                            <span id="preview-address">{{ $settings['invoice_address'] ?? '123 Main Street, Colombo 03' }}</span><br>
                                            Phone: <span id="preview-phone">{{ $settings['invoice_phone'] ?? '+94 11 234 5678' }}</span><br>
                                            <span id="preview-email">{{ $settings['invoice_email'] ?? 'info@jewellerypos.com' }}</span>
                                        </div>
                                    </div>
                                    <div style="text-align:center;margin:6px 0;letter-spacing:3px;font-size:12px;">================================</div>
                                    <div style="font-size:10px;color:#888;text-align:center;">... items will appear here ...</div>
                                    <div style="text-align:center;margin:6px 0;letter-spacing:3px;font-size:12px;">================================</div>
                                    <div style="text-align:center;margin-top:6px;">
                                        <div id="preview-thanks" style="font-weight:700;font-size:12px;">{{ $settings['invoice_thank_you'] ?? 'Thank you for your purchase!' }}</div>
                                        <div id="preview-footer1" style="font-size:9px;color:#555;">{{ $settings['invoice_footer_line1'] ?? 'Goods once sold cannot be returned.' }}</div>
                                        <div id="preview-footer2" style="font-size:9px;color:#555;">{{ $settings['invoice_footer_line2'] ?? 'Exchange within 7 days with receipt.' }}</div>
                                        <div id="preview-footer3" style="font-size:9px;color:#555;">{{ $settings['invoice_footer_line3'] ?? '' }}</div>
                                        <div id="preview-generated" style="font-size:9px;color:#555;font-style:italic;margin-top:4px;">POSSYSTEM.LK</div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-gold"><i class="fas fa-save me-1"></i> Save Invoice Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Barcode Settings -->
            <div class="tab-pane fade" id="barcode">
                <div class="card shadow-sm">
                    <div class="card-header bg-gold text-white">
                        <h5 class="mb-0"><i class="fas fa-barcode me-2"></i> Barcode Label Settings</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('settings.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <p class="text-muted small mb-4"><i class="fas fa-info-circle me-1"></i> These settings control how barcode labels look when printed from the Products page.</p>

                            <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="fas fa-ruler-combined me-1"></i> Barcode Dimensions</h6>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="barcode_bar_width" class="form-label fw-semibold">Bar Width <small class="text-muted fw-normal">(px, 1&ndash;5)</small></label>
                                    <input type="number" class="form-control" id="barcode_bar_width" name="barcode_bar_width"
                                        value="{{ $settings['barcode_bar_width'] ?? '2.5' }}" min="1" max="5" step="0.5">
                                    <small class="text-muted">Width of each bar. Higher = thicker bars.</small>
                                </div>
                                <div class="col-md-4">
                                    <label for="barcode_bar_height" class="form-label fw-semibold">Bar Height <small class="text-muted fw-normal">(px, 20&ndash;150)</small></label>
                                    <input type="number" class="form-control" id="barcode_bar_height" name="barcode_bar_height"
                                        value="{{ $settings['barcode_bar_height'] ?? '70' }}" min="20" max="150" step="5">
                                    <small class="text-muted">Height of the barcode bars.</small>
                                </div>
                            </div>

                            <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="fas fa-font me-1"></i> Label Text Sizes</h6>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="barcode_name_font_size" class="form-label fw-semibold">Product Name Size <small class="text-muted fw-normal">(pt)</small></label>
                                    <input type="number" class="form-control" id="barcode_name_font_size" name="barcode_name_font_size"
                                        value="{{ $settings['barcode_name_font_size'] ?? '9' }}" min="6" max="24" step="0.5">
                                    <small class="text-muted">Font size of the product name on labels.</small>
                                </div>
                                <div class="col-md-4">
                                    <label for="barcode_price_font_size" class="form-label fw-semibold">Price Size <small class="text-muted fw-normal">(pt)</small></label>
                                    <input type="number" class="form-control" id="barcode_price_font_size" name="barcode_price_font_size"
                                        value="{{ $settings['barcode_price_font_size'] ?? '11' }}" min="6" max="28" step="0.5">
                                    <small class="text-muted">Font size of the price on labels.</small>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-gold"><i class="fas fa-save me-1"></i> Save Barcode Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="tab-pane fade" id="security">
                <div class="card shadow-sm">
                    <div class="card-header bg-gold text-white">
                        <h5 class="mb-0"><i class="fas fa-lock me-2"></i> Security Settings</h5>
                    </div>
                    <div class="card-body">
                        <!-- Change Username (Email) -->
                        <div class="mb-4">
                            <h6><i class="fas fa-user-edit me-1"></i> Change Username (Email)</h6>
                            <p class="text-muted small">Update the email address used to log into the system. You must confirm your current password.</p>

                            @if ($errors->has('current_password_username'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ $errors->first('current_password_username') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                            @if ($errors->has('new_email'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ $errors->first('new_email') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                            @if ($errors->has('new_email_confirmation'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ $errors->first('new_email_confirmation') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <form action="{{ route('settings.changeUsername') }}" method="POST">
                                @csrf
                                @method('PATCH')

                                <div class="mb-3">
                                    <label class="form-label">Current Email</label>
                                    <input type="email" class="form-control" value="{{ auth()->user()->email }}" disabled>
                                </div>

                                <div class="mb-3">
                                    <label for="new_email" class="form-label">New Email (Username)</label>
                                    <input type="email" class="form-control" id="new_email" name="new_email" value="{{ old('new_email') }}" required placeholder="Enter new email address">
                                </div>

                                <div class="mb-3">
                                    <label for="new_email_confirmation" class="form-label">Confirm New Email</label>
                                    <input type="email" class="form-control" id="new_email_confirmation" name="new_email_confirmation" required placeholder="Confirm new email address">
                                </div>

                                <div class="mb-3">
                                    <label for="current_password_for_username" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password_for_username" name="current_password" required placeholder="Enter current password to confirm">
                                </div>

                                <button type="submit" class="btn btn-gold"><i class="fas fa-save me-1"></i> Update Username</button>
                            </form>
                        </div>

                        <hr>

                        <!-- Change Password -->
                        <div class="mb-4">
                            <h6>Change Password</h6>
                            <form action="{{ route('profile.update') }}" method="POST">
                                @csrf
                                @method('PATCH')
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                                </div>

                                <button type="submit" class="btn btn-gold">Update Password</button>
                            </form>
                        </div>

                        <hr>

                        <div class="mb-4">
                            <h6>Session Management</h6>
                            <p class="text-muted">Manage your active sessions and logout from other devices.</p>
                            <button class="btn btn-outline-danger" onclick="logoutAllDevices()">Logout from All Devices</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function logoutAllDevices() {
    if (confirm('Are you sure? You will be logged out from all devices.')) {
        // Implementation for logout all devices
        alert('Logged out from all devices');
    }
}

// ---- Invoice Settings Live Preview ----
document.addEventListener('DOMContentLoaded', function() {
    const previewMap = {
        'invoice_shop_name': 'preview-shop-name',
        'invoice_tagline': 'preview-tagline',
        'invoice_address': 'preview-address',
        'invoice_phone': 'preview-phone',
        'invoice_email': 'preview-email',
        'invoice_thank_you': 'preview-thanks',
        'invoice_footer_line1': 'preview-footer1',
        'invoice_footer_line2': 'preview-footer2',
        'invoice_footer_line3': 'preview-footer3',
    };

    Object.keys(previewMap).forEach(function(inputId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewMap[inputId]);
        if (input && preview) {
            input.addEventListener('input', function() {
                preview.textContent = this.value;
            });
        }
    });

    // Logo live preview
    var logoInput = document.getElementById('invoice_logo');
    var previewLogoWrap = document.getElementById('preview-logo-wrap');
    var previewLogoImg = document.getElementById('preview-logo-img');
    var previewDiamond = document.getElementById('preview-diamond');
    if (logoInput) {
        logoInput.addEventListener('change', function() {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewLogoImg.src = e.target.result;
                    previewLogoWrap.style.display = '';
                    previewDiamond.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    var removeLogoCheck = document.getElementById('remove_invoice_logo');
    if (removeLogoCheck) {
        removeLogoCheck.addEventListener('change', function() {
            if (this.checked) {
                previewLogoWrap.style.display = 'none';
                previewDiamond.style.display = '';
            } else {
                previewLogoWrap.style.display = '';
                previewDiamond.style.display = 'none';
            }
        });
    }

    // System logo live preview
    var sysLogoInput = document.getElementById('system_logo');
    var sysLogoPreview = document.getElementById('system-logo-preview');
    if (sysLogoInput && sysLogoPreview) {
        sysLogoInput.addEventListener('change', function() {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    sysLogoPreview.src = e.target.result;
                    sysLogoPreview.style.display = '';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>
@endsection
