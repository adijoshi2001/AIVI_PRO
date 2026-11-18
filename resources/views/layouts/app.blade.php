<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AIVI PRO — AI Virtual Interviewer')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/aivi.css') }}">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-inner">
            <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="nav-logo">
                <span class="nav-logo-badge">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </span>
                <span class="nav-logo-text">AIVI PRO</span>
                <span class="nav-tag">{{ $roadmap->completionPercentage() }}% • P{{ $roadmap->completedCount() }}/{{ $roadmap->totalCount() }}</span>
            </a>

            <div class="nav-links">
                <a href="{{ route('roadmap') }}" class="nav-link {{ request()->routeIs('roadmap') ? 'active' : '' }}">Roadmap</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('interviews.create') }}" class="nav-link {{ request()->routeIs('interviews.create') ? 'active' : '' }}">New Interview</a>

                    <span class="nav-user-chip" title="{{ auth()->user()->email }}">
                        @if (auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" class="user-avatar-sm" referrerpolicy="no-referrer">
                        @else
                            <span class="nav-user-initials">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        @endif
                        <span>{{ \Illuminate\Support\Str::words(auth()->user()->name, 1, '') }}</span>
                    </span>

                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="nav-logout-btn">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">Login</a>
                    <a href="{{ route('register') }}" class="btn-primary btn-sm">Get Started</a>
                @endauth
            </div>
        </div>
    </nav>

    <main class="fade-up">
        @if (session('success'))
            <div class="container" style="padding-bottom: 0;">
                <div class="alert alert-success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="container" style="padding-bottom: 0;">
                <div class="alert alert-danger">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <strong style="color: #e2e8f0;">AIVI PRO</strong>
                <span>•</span>
                <span>AI Virtual Interviewer Platform</span>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="{{ route('roadmap') }}" style="color: var(--primary-bright); text-decoration: none; font-weight: 600;">
                    Milestone: {{ $roadmap->completionPercentage() }}% ({{ $roadmap->completedCount() }}/{{ $roadmap->totalCount() }} Phases)
                </a>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
