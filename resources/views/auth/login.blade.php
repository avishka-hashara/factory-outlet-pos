<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Welcome - {{ \App\Models\Setting::get('app_name', config('app.name', 'Clothing Outlet POS')) }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Using Inter for body and Playfair Display for headings (system theme fonts) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --gold: #F5A623;
            --gold-light: #F7B84D;
            --gold-shine: #FCCF6E;
            --dark-blue: #1B2A4A;
            --dark-blue-deep: #050a15;
            --white: #FFFFFF;
            --text-muted: #6B7280;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100vw;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--dark-blue-deep) 0%, var(--dark-blue) 100%);
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            color: var(--white);
        }

        /* Top Left Brand */
        .top-left-brand {
            position: absolute;
            top: 30px;
            left: 40px;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 20;
        }
        .pos-icon-box {
            width: 48px;
            height: 48px;
            background: var(--gold);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(245, 166, 35, 0.3);
        }
        .pos-icon-box i {
            color: var(--dark-blue-deep);
            font-size: 1.4rem;
        }
        .pos-text {
            display: flex;
            flex-direction: column;
        }
        .pos-text strong {
            font-size: 1.15rem;
            color: var(--white);
            letter-spacing: 0.5px;
            line-height: 1.1;
            font-weight: 800;
        }
        .pos-text small {
            font-size: 0.65rem;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
            margin-top: 2px;
        }

        /* Center Logo */
        .center-logo-container {
            position: absolute;
            top: 40px;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
            z-index: 20;
        }
        .glamon-logo-icon {
            font-size: 2.8rem;
            background: linear-gradient(135deg, var(--gold-light), var(--gold-shine));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }
        .glamon-logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--gold-light), var(--gold-shine));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
            letter-spacing: 2px;
        }
        .glamon-logo-sub {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.8);
            letter-spacing: 3px;
            text-transform: uppercase;
            font-weight: 500;
            margin-top: 4px;
        }

        /* Login Card */
        .login-card-wrapper {
            position: relative;
            z-index: 20;
            width: 100%;
            max-width: 440px;
            margin-top: 80px;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .login-card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            color: var(--dark-blue);
        }

        .login-card-top {
            background: var(--dark-blue);
            color: var(--white);
            padding: 40px 35px 30px;
            text-align: left;
            border-bottom: 4px solid var(--gold);
        }
        .login-card-top h2 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 8px;
        }
        .login-card-top p {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
            font-weight: 400;
        }

        .login-card-body {
            padding: 35px 35px 40px;
        }

        .form-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
            display: block;
        }

        .input-group {
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.25s ease;
            overflow: hidden;
        }
        .input-group:focus-within {
            border-color: var(--gold);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(245, 166, 35, 0.1);
        }
        .input-group-text {
            background: transparent;
            border: none;
            color: var(--dark-blue);
            padding: 0 10px 0 18px;
            font-size: 1rem;
        }
        .form-control {
            background: transparent;
            border: none;
            padding: 14px 15px 14px 5px;
            font-size: 0.95rem;
            color: var(--dark-blue);
            font-weight: 500;
            box-shadow: none !important;
        }
        .form-control::placeholder {
            color: #a8a0ae;
            font-weight: 400;
        }

        .btn-signin {
            background: var(--gold);
            color: var(--dark-blue-deep);
            border: none;
            border-radius: 10px;
            padding: 16px;
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-signin:hover {
            background: var(--gold-shine);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 166, 35, 0.4);
        }
        .btn-signin i {
            font-size: 1.1rem;
        }

        .forgot-link {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--dark-blue);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .forgot-link:hover {
            color: var(--gold);
            text-decoration: underline;
        }

        .manager-link {
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 30px;
            font-weight: 500;
        }
        .manager-link a {
            color: var(--dark-blue);
            font-weight: 700;
            text-decoration: none;
        }
        .manager-link a:hover {
            color: var(--gold);
        }

        /* Animated Elements */
        .decor-item {
            position: absolute;
            color: var(--gold);
            opacity: 0.08;
            z-index: 10;
            pointer-events: none;
        }
        
        .anim-float-1 { animation: float1 15s infinite ease-in-out; }
        .anim-float-2 { animation: float2 18s infinite ease-in-out; }
        .anim-float-3 { animation: float3 20s infinite ease-in-out; }

        @keyframes float1 {
            0%, 100% { transform: translate(0, 0) rotate(0deg) scale(1); }
            33% { transform: translate(20px, -30px) rotate(15deg) scale(1.1); }
            66% { transform: translate(-15px, -15px) rotate(-10deg) scale(0.9); }
        }
        @keyframes float2 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-30px, -40px) rotate(45deg); }
        }
        @keyframes float3 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(40px, 30px) rotate(-30deg); }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .bottom-glow {
            position: absolute;
            bottom: -50px;
            left: 0;
            width: 100%;
            height: 350px;
            background: radial-gradient(ellipse at bottom, rgba(245, 166, 35, 0.1), transparent 70%);
            z-index: 5;
            pointer-events: none;
        }

        .sparkle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: var(--gold);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--gold-shine), 0 0 20px var(--gold);
            animation: twinkle 4s infinite ease-in-out;
            z-index: 10;
            pointer-events: none;
        }
        @keyframes twinkle {
            0%, 100% { opacity: 0; transform: scale(0.5); }
            50% { opacity: 1; transform: scale(1.5); }
        }

        /* Responsive Layout */
        @media (max-width: 768px) {
            .top-left-brand {
                top: 20px;
                left: 20px;
            }
            .center-logo-container {
                top: 80px;
                position: relative;
                transform: none;
                left: 0;
                margin-bottom: 10px;
                margin-top: 60px;
            }
            body, html {
                flex-direction: column;
                justify-content: flex-start;
                padding: 10px;
                height: auto;
                min-height: 100vh;
            }
            .login-card-wrapper {
                margin-top: 10px;
                padding: 0 15px;
                width: 100%;
                margin-bottom: 40px;
            }
        }
    </style>
</head>
<body>

    <!-- Animated Clothing Decor Background -->
    <i class="fas fa-tshirt decor-item anim-float-1" style="top: 15%; left: 18%; font-size: 5rem;"></i>
    <i class="fas fa-tag decor-item anim-float-2" style="top: 60%; left: 12%; font-size: 4rem;"></i>
    <i class="fas fa-shopping-bag decor-item anim-float-3" style="top: 25%; right: 18%; font-size: 4.5rem;"></i>
    <i class="fas fa-vest decor-item anim-float-1" style="top: 70%; right: 12%; font-size: 3.5rem; animation-delay: -5s;"></i>
    <i class="fas fa-socks decor-item anim-float-2" style="top: 40%; right: 25%; font-size: 5rem; animation-delay: -2s; opacity: 0.04;"></i>
    
    <!-- Sparkles -->
    <div class="sparkle" style="top: 20%; left: 25%; animation-delay: 0s;"></div>
    <div class="sparkle" style="top: 65%; left: 20%; animation-delay: 1.5s;"></div>
    <div class="sparkle" style="top: 30%; right: 28%; animation-delay: 0.8s;"></div>
    <div class="sparkle" style="top: 80%; right: 22%; animation-delay: 2s;"></div>
    <div class="sparkle" style="top: 45%; left: 10%; animation-delay: 0.3s;"></div>
    <div class="sparkle" style="top: 55%; right: 10%; animation-delay: 1.1s;"></div>

    <!-- Top Left Brand -->
    <div class="top-left-brand">
        <div class="pos-icon-box">
            <i class="fas fa-cash-register"></i>
        </div>
        <div class="pos-text">
            <strong>{{ \App\Models\Setting::get('app_name', config('app.name', 'Clothing Outlet POS')) }}</strong>
            <small>Retail Point of Sale</small>
        </div>
    </div>

    <!-- Center Logo -->
    <div class="center-logo-container">
        <i class="fas fa-tshirt glamon-logo-icon"></i>
        <div class="glamon-logo-text">{{ \App\Models\Setting::get('app_name', config('app.name', 'CLOTHING OUTLET')) }}</div>
        <div class="glamon-logo-sub">Fashion &amp; Apparel POS</div>
    </div>

    <!-- Login Card -->
    <div class="login-card-wrapper">
        <div class="login-card">
            <div class="login-card-top">
                <h2>Welcome back.</h2>
                <p>Sign in to continue to your store workspace.</p>
            </div>
            <div class="login-card-body">
                @if (session('status'))
                    <div class="alert alert-success py-2 mb-3 border-0" style="font-size:0.85rem; background:#dcfce7; color:#166534;">
                        {{ session('status') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger py-2 mb-3 border-0" style="font-size:0.85rem; background:#fee2e2; color:#991b1b;">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="far fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="admin@store.com" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="forgot-link">Forgot Password?</a>
                            @endif
                        </div>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                            <button type="button" class="input-group-text pe-3" onclick="togglePassword()" style="cursor:pointer; color:var(--text-muted);">
                                <i class="far fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Hidden Remember Me -->
                    <input type="checkbox" name="remember" id="remember" checked style="display:none;">

                    <button type="submit" class="btn-signin">
                        SIGN IN <i class="fas fa-sign-in-alt"></i>
                    </button>
                </form>

                <div class="manager-link">
                    Need access? <a href="#">Ask your manager to invite you.</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Glow -->
    <div class="bottom-glow"></div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
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
