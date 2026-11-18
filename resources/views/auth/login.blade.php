@extends('layouts.app')

@section('title', 'Login — AIVI PRO')

@section('content')
<div class="container">
    <div class="auth-wrapper">
        <div class="glass auth-card">
            <div style="text-align: center; margin-bottom: 1.75rem;">
                <div class="nav-logo-badge" style="width: 46px; height: 46px; margin: 0 auto 1rem; border-radius: 14px;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <h1 class="section-heading gradient-text" style="font-size: 1.85rem; margin-bottom: 0.35rem;">Welcome Back</h1>
                <p class="subtitle" style="font-size: 0.92rem;">Sign in to access Mission Control &amp; interview analytics.</p>
            </div>

            <a href="{{ route('auth.google.redirect') }}" class="btn-google">
                <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.2-2.27H12v4.3h6.45a5.52 5.52 0 0 1-2.39 3.62v3h3.87c2.26-2.08 3.56-5.15 3.56-8.65z"/>
                    <path fill="#34A853" d="M12 24c3.24 0 5.95-1.07 7.93-2.91l-3.87-3c-1.07.72-2.45 1.15-4.06 1.15-3.12 0-5.77-2.11-6.72-4.95H1.28v3.1A12 12 0 0 0 12 24z"/>
                    <path fill="#FBBC05" d="M5.28 14.29A7.2 7.2 0 0 1 4.9 12c0-.79.14-1.56.38-2.29v-3.1H1.28A12 12 0 0 0 0 12c0 1.94.46 3.77 1.28 5.39l4-3.1z"/>
                    <path fill="#EA4335" d="M12 4.76c1.76 0 3.34.61 4.59 1.8l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 0 0 1.28 6.61l4 3.1c.95-2.84 3.6-4.95 6.72-4.95z"/>
                </svg>
                <span>Continue with Google</span>
            </a>

            @if (! $googleConfigured)
                <p style="text-align: center; font-size: 0.76rem; color: var(--text-muted); margin-top: 0.6rem;">
                    Add <code>GOOGLE_CLIENT_ID</code> &amp; <code>GOOGLE_CLIENT_SECRET</code> in <code>.env</code> to activate Google sign-in.
                </p>
            @endif

            <div class="auth-divider">or sign in with email</div>

            <form action="{{ route('login') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="you@example.com" required autofocus>
                    @error('email')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input id="password" type="password" name="password" class="form-input" placeholder="••••••••" required>
                    @error('password')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 0.55rem;">
                    <input id="remember" type="checkbox" name="remember" value="1" style="accent-color: var(--primary);">
                    <label for="remember" style="font-size: 0.87rem; color: var(--text-muted); cursor: pointer;">Keep me signed in</label>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 0.35rem;">Sign In to Mission Control</button>
            </form>

            <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
                Don't have an account?
                <a href="{{ route('register') }}" style="color: var(--primary-bright); font-weight: 600; text-decoration: none;">Create one</a>
            </p>

            <div class="auth-trust-row">
                <span>🔒 OAuth 2.0</span>
                <span>•</span>
                <span>Bcrypt Encrypted</span>
                <span>•</span>
                <span>CSRF Protected</span>
            </div>
        </div>
    </div>
</div>
@endsection
