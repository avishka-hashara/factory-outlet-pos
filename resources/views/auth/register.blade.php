{{-- ============================================
     Register Page - Jewellery POS System
     Standalone layout (match login page)
     ============================================ --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register - {{ config('app.name', 'Jewellery POS') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom Theme -->
    <link href="{{ asset('css/pos-theme.css') }}" rel="stylesheet">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card animate-fade-in">
        <!-- Register Header with Logo -->
        <div class="login-header">
            <div class="logo-icon">
                <img src="{{ asset('images/bc-logo.png') }}" alt="BC Logo" class="register-logo-image">
            </div>
            <h4>{{ config('app.name', 'B Classy') }}</h4>
            <p>Create an account</p>
        </div>

        <!-- Register Form -->
        <div class="login-body">
            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Name -->
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">
                        <i class="fas fa-user me-1 text-gold"></i> Full Name
                    </label>
                    <input type="text" class="form-control form-control-lg" id="name" name="name"
                           value="{{ old('name') }}" placeholder="Enter your full name" required autofocus>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">
                        <i class="fas fa-envelope me-1 text-gold"></i> Email Address
                    </label>
                    <input type="email" class="form-control form-control-lg" id="email" name="email"
                           value="{{ old('email') }}" placeholder="Enter your email" required>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">
                        <i class="fas fa-lock me-1 text-gold"></i> Password
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control form-control-lg" id="password" name="password"
                               placeholder="Create a password" required autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', 'toggleIcon')">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label fw-semibold">
                        <i class="fas fa-lock me-1 text-gold"></i> Confirm Password
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control form-control-lg" id="password_confirmation" name="password_confirmation"
                               placeholder="Confirm your password" required autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirmation', 'toggleIconConf')">
                            <i class="fas fa-eye" id="toggleIconConf"></i>
                        </button>
                    </div>
                </div>

                <!-- Register Button -->
                <button type="submit" class="btn btn-gold btn-lg w-100 mb-3">
                    <i class="fas fa-user-plus me-2"></i> Register
                </button>

                <!-- Login Link -->
                <p class="text-center text-muted mb-0">
                    Already have an account?
                    <a href="{{ route('login') }}" class="text-gold fw-semibold">Sign In</a>
                </p>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
</script>
</body>
</html>
