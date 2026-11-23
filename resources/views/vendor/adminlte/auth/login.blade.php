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
    $passwordLoginEnabled = (bool) ($passwordLoginEnabled ?? false);
    $otpLoginEnabled = (bool) ($otpLoginEnabled ?? true);
    $resendAvailableIn = max(0, (int) ($resendAvailableIn ?? 0));
    $statusLevel = session('status_level', 'success');
    $statusAlertClass = $statusLevel === 'warning' ? 'alert-warning' : 'alert-success';
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
                        <div class="alert {{ $statusAlertClass }}" role="status">{{ session('status') }}</div>
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

                    @if ($loginStep === 'password')
                        <form action="{{ route('manager.login.submit') }}" method="post">
                            @csrf
                            <div class="mb-3">
                                <label for="phone" class="form-label">PHONE</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input id="phone" type="text" class="form-control @error('phone') is-invalid @enderror"
                                           name="phone" value="{{ old('phone') }}" required autofocus autocomplete="username">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">PASSWORD</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" name="password" id="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="{{ __('adminlte::adminlte.password') }}"
                                           required autocomplete="current-password">
                                    <button class="btn btn-outline-orange" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-login pulse">
                                {{ __('adminlte::adminlte.sign_in') }} <i class="fas fa-chevron-right ms-2"></i>
                            </button>

                            @if ($otpLoginEnabled)
                                <p class="text-center mt-3 mb-0">
                                    <a href="{{ route('manager.login', ['method' => 'otp']) }}" class="forgot-link">Sign in with email code</a>
                                </p>
                            @endif
                        </form>
                    @elseif ($loginStep === 'code')
                        <p class="text-muted small text-center mb-3">
                            Check your inbox and spam. After a resend we keep the same code so older emails still work.
                        </p>

                        <form action="{{ route('manager.login.otp.verify') }}" method="post" data-otp-form>
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

                            <button type="submit" class="btn btn-login pulse">
                                Verify &amp; sign in <i class="fas fa-chevron-right ms-2"></i>
                            </button>
                        </form>

                        <form action="{{ route('manager.login.otp.request') }}" method="post" class="mt-3" data-otp-form id="login-otp-resend-form">
                            @csrf
                            <input type="hidden" name="email" value="{{ $otpEmail }}">
                            <button type="submit" class="btn btn-outline-orange w-100"
                                    id="login-otp-resend-btn"
                                    @if ($resendAvailableIn > 0) disabled @endif
                                    data-resend-label="Resend code"
                                    data-wait-label="Resend in __S__ s"
                                    data-available-in="{{ $resendAvailableIn }}">
                                @if ($resendAvailableIn > 0)
                                    Resend in {{ $resendAvailableIn }} s
                                @else
                                    Resend code
                                @endif
                            </button>
                        </form>

                        <p class="text-center mt-3 mb-0">
                            <a href="{{ route('manager.login', ['method' => 'otp']) }}" class="forgot-link">Use a different email</a>
                        </p>
                        @if ($passwordLoginEnabled)
                            <p class="text-center mt-2 mb-0">
                                <a href="{{ route('manager.login') }}" class="forgot-link">Sign in with phone &amp; password</a>
                            </p>
                        @endif
                    @else
                        <form action="{{ route('manager.login.otp.request') }}" method="post" data-otp-form>
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

                            @if ($passwordLoginEnabled)
                                <p class="text-center mt-3 mb-0">
                                    <a href="{{ route('manager.login') }}" class="forgot-link">
                                        Sign in with phone &amp; password
                                    </a>
                                </p>
                            @endif
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

        const toggle = document.getElementById('togglePassword');
        if (toggle) {
            toggle.addEventListener('click', function () {
                const password = document.getElementById('password');
                const icon = this.querySelector('i');
                if (!password || !icon) return;
                if (password.type === 'password') {
                    password.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    password.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        }

        if (!window.__adminOtpSubmitLockBound) {
            window.__adminOtpSubmitLockBound = true;
            // Do not disable the submitter — Chromium cancels navigation when
            // the submit button is disabled during/just after submit.
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!(form instanceof HTMLFormElement) || !form.matches('form[data-otp-form]')) {
                    return;
                }
                if (form.dataset.otpSubmitting === '1') {
                    e.preventDefault();
                    return;
                }
                form.dataset.otpSubmitting = '1';
                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.setAttribute('aria-busy', 'true');
                    btn.style.pointerEvents = 'none';
                }
            }, true);
        }

        const resend = document.getElementById('login-otp-resend-btn');
        if (resend) {
            let remaining = parseInt(resend.getAttribute('data-available-in'), 10) || 0;
            const resendLabel = resend.getAttribute('data-resend-label') || 'Resend code';
            const waitTemplate = resend.getAttribute('data-wait-label') || 'Resend in __S__ s';

            function paintResend() {
                if (remaining <= 0) {
                    resend.disabled = false;
                    resend.textContent = resendLabel;
                    return;
                }
                resend.disabled = true;
                resend.textContent = waitTemplate.split('__S__').join(String(remaining));
                remaining -= 1;
                window.setTimeout(paintResend, 1000);
            }

            if (remaining > 0) {
                paintResend();
            }
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
