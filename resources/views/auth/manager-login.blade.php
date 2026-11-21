@extends('adminlte::auth.login')

@php
    $loginStep = $loginStep ?? 'email';
    $otpEmail = $otpEmail ?? old('email', session('login_otp_email'));
@endphp

@section('auth_body')
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
                                <label for="password" class="form-label">ACCESS CODE</label>
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

                            <div class="d-flex justify-content-between mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">{{ __('adminlte::adminlte.remember_me') }}</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-login pulse">
                                {{ __('adminlte::adminlte.sign_in') }} <i class="fas fa-chevron-right ms-2"></i>
                            </button>

                            <p class="text-center mt-3 mb-0">
                                <a href="{{ route('manager.login') }}" class="forgot-link">Sign in with email code</a>
                            </p>
                        </form>
                    @elseif ($loginStep === 'code')
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

                            <p class="text-center mt-3 mb-0">
                                <a href="{{ route('manager.login', ['method' => 'password']) }}" class="forgot-link">
                                    Sign in with phone &amp; password
                                </a>
                            </p>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@push('js')
<script>
    (function () {
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
@endpush
