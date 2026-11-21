@extends('adminlte::auth.auth-page', ['auth_type' => 'login'])

@section('adminlte_css_pre')
    <link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts for gaming-style typography -->
    <link href="https://fonts.googleapis.com/css2?family=Russo+One&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Arista';
            font-style: normal;
            font-weight: normal;
            src: url('/assets/fonts/arista/[z] Arista.woff') format('woff');
        }
    
        @font-face {
            font-family: 'Arista ExtraFilled';
            font-style: normal;
            font-weight: normal;
            src: url('/assets/fonts/arista/[z] Arista ExtraFilled.woff') format('woff');
        }
    
        @font-face {
            font-family: 'Arista Light';
            font-style: normal;
            font-weight: normal;
            src: url('/assets/fonts/arista/[z] Arista light.woff') format('woff');
        }
    </style>
    <style>
        :root {
            --primary-color: #db890a;
            --primary-dark: #945d0a;
            --dark-color: #000000;
            --light-color: #1a1a1a;
        }
        
        body {
            background-color: var(--dark-color);
            color: white;
            font-family: 'Orbitron', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            background-image: url('https://images.unsplash.com/photo-1560253023-3ec5d502959f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: -1;
        }
        .form-label{
            color: #fff!important
        }
        button.btn-login {
            background-color: var(--primary-color);
            border: none;
            border-radius: 4px;
            padding: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
            color: black;
            font-family: 'Russo One', sans-serif;
        }
        .login-page, .register-page {
            background-color: #000!important
        }
        .particle {
            position: absolute;
            background-color: var(--primary-color);
            border-radius: 50%;
            pointer-events: none;
            z-index: -1;
            animation: float linear infinite;
            opacity: 0.6;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 0.6;
            }
            90% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100vh) translateX(calc(var(--random-x) * 100px));
                opacity: 0;
            }
        }
        
        .login-container {
            background-color: var(--light-color);
            border-radius: 8px;
            border: 1px solid var(--primary-color);
            box-shadow: 0 0 15px rgba(255, 107, 0, 0.5);
            overflow: hidden;
            position: relative;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, var(--primary-color), #ff9e00, var(--primary-color));
            z-index: -1;
            border-radius: 10px;
            animation: borderGlow 3s linear infinite;
            background-size: 400%;
        }
        
        @keyframes borderGlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .login-header {
            background-color: var(--dark-color);
            padding: 25px;
            text-align: center;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .login-header h2 {
            font-family: 'Russo One', sans-serif;
            color: var(--primary-color);
            margin: 0;
            font-size: 2rem;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .form-control {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid #333;
            color: white;
            border-radius: 4px;
            padding: 12px 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 0 0 0.25rem rgba(255, 107, 0, 0.25);
        }
        
        .input-group-text {
            background-color: var(--dark-color);
            border: 1px solid #333;
            color: var(--primary-color);
        }
        
        .btn-login {
            background-color: var(--primary-color);
            border: none;
            border-radius: 4px;
            padding: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
            color: black;
            font-family: 'Russo One', sans-serif;
        }
        
        button.btn-login:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 0, 0.4);
            color: black;
        }
        
        .social-login .btn {
            border-radius: 4px;
            margin: 5px 0;
            padding: 10px;
            font-weight: bold;
            border: 1px solid #333;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #333;
        }
        
        .divider-text {
            padding: 0 10px;
            color: var(--primary-color);
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .forgot-link {
            color: #777;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .forgot-link:hover {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .register-link {
            color: var(--primary-color);
            font-weight: bold;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .register-link:hover {
            color: #ff9e00;
            text-decoration: underline;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-check-label {
            color: #aaa;
        }
        
        button.btn-outline-orange {
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        button.btn-outline-orange:hover {
            background-color: var(--primary-color);
            color: black;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        .login-logo a, .register-logo a {
            color: rgba(255, 255, 255, 0.75)!important;
            font-family: 'Arista';
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 107, 0, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(255, 107, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 107, 0, 0); }
        }
    </style>
@stop

@section('auth_header', __('adminlte::adminlte.login_message'))

@section('auth_body')
@php
    $loginStep = $loginStep ?? 'email';
    $otpEmail = $otpEmail ?? old('email', session('login_otp_email'));
@endphp
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="login-container">
                <div class="login-header">
                    <div class="{{ $auth_type ?? 'login' }}-logo">
                        <a href="{{ url(config('adminlte.dashboard_url', 'manager')) }}">
                            @if (config('adminlte.auth_logo.enabled', false))
                                <img src="{{ asset(config('adminlte.auth_logo.img.path')) }}"
                                     alt="{{ config('adminlte.auth_logo.img.alt') }}"
                                     @if (config('adminlte.auth_logo.img.class', null))
                                        class="{{ config('adminlte.auth_logo.img.class') }}"
                                     @endif
                                     @if (config('adminlte.auth_logo.img.width', null))
                                        width="{{ config('adminlte.auth_logo.img.width') }}"
                                     @endif
                                     @if (config('adminlte.auth_logo.img.height', null))
                                        height="{{ config('adminlte.auth_logo.img.height') }}"
                                     @endif>
                            @else
                                <img src="{{ asset(config('adminlte.logo_img')) }}"
                                     alt="{{ config('adminlte.logo_img_alt') }}" height="50">
                            @endif
                            {!! config('adminlte.logo', '<b>Admin</b>LTE') !!}
                        </a>
                    </div>
                </div>

                <div class="p-4">
                    @if (session('status'))
                        <div class="alert alert-success" role="status">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($loginStep === 'code')
                        <form action="{{ route('manager.login.otp.verify') }}" method="post">
                            @csrf
                            <input type="hidden" name="email" value="{{ $otpEmail }}">

                            <div class="mb-3">
                                <label class="form-label">EMAIL</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" value="{{ $otpEmail }}" disabled>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="code" class="form-label">LOGIN CODE</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-shield-halved"></i></span>
                                    <input id="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                                           class="form-control @error('code') is-invalid @enderror"
                                           name="code" value="{{ old('code') }}" required autofocus
                                           autocomplete="one-time-code" placeholder="6-digit code">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">{{ __('adminlte::adminlte.remember_me') }}</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-login pulse">
                                Verify &amp; sign in <i class="fas fa-chevron-right ms-2"></i>
                            </button>
                        </form>

                        <form action="{{ route('manager.login.otp.request') }}" method="post" class="mt-3">
                            @csrf
                            <input type="hidden" name="email" value="{{ $otpEmail }}">
                            <button type="submit" class="btn btn-outline-orange w-100">Resend code</button>
                        </form>

                        <p class="text-center mt-3 mb-0">
                            <a href="{{ route('manager.login') }}" class="forgot-link">Use a different email</a>
                        </p>
                    @else
                        <form action="{{ route('manager.login.otp.request') }}" method="post">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">EMAIL</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input id="email" type="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           name="email" value="{{ old('email', $otpEmail) }}"
                                           required autofocus autocomplete="email"
                                           placeholder="you@example.com">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-login pulse">
                                Send login code <i class="fas fa-chevron-right ms-2"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@stop


@section('auth_footer')
@stop

@section('js')
<script>
    (function () {
        const loginBtn = document.querySelector('.btn-login');
        if (loginBtn) {
            loginBtn.addEventListener('mouseenter', () => loginBtn.classList.add('pulse'));
            loginBtn.addEventListener('mouseleave', () => loginBtn.classList.remove('pulse'));
        }

        function createParticles() {
            const particleCount = 50;
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                const size = Math.random() * 5 + 1;
                const posX = Math.random() * window.innerWidth;
                const delay = Math.random() * 5;
                const duration = Math.random() * 10 + 10;
                const opacity = Math.random() * 0.5 + 0.1;
                const randomX = (Math.random() - 0.5) * 2;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${posX}px`;
                particle.style.bottom = `-10px`;
                particle.style.opacity = opacity;
                particle.style.animationDuration = `${duration}s`;
                particle.style.animationDelay = `${delay}s`;
                particle.style.setProperty('--random-x', randomX);
                document.body.appendChild(particle);
            }
        }

        document.addEventListener('DOMContentLoaded', createParticles);
        window.addEventListener('resize', function () {
            document.querySelectorAll('.particle').forEach(p => p.remove());
            createParticles();
        });
    })();
</script>
@stop
