<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <a class="skip-link" href="#main-content">Bỏ qua điều hướng</a>
    <header class="site-header">
        <div class="container header-content">
            <a class="brand" href="{{ auth()->check() ? route('home') : route('login') }}">
                <span class="brand-mark" aria-hidden="true">{{ config('property.code') }}</span>
                <span>{{ config('app.name') }}</span>
            </a>

            @auth
                <div class="account-actions">
                    <a class="nav-link @if(request()->routeIs('billing-periods.*', 'meter-readings.*')) is-active @endif" href="{{ route('billing-periods.index') }}">
                        Ghi số
                    </a>
                    <a class="nav-link @if(request()->routeIs('rooms.*')) is-active @endif" href="{{ route('rooms.index') }}">
                        Phòng
                    </a>
                    <span class="account-name">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="button button-secondary button-small" type="submit">Đăng xuất</button>
                    </form>
                </div>
            @endauth
        </div>
    </header>

    <main class="container page-content" id="main-content" tabindex="-1">
        @yield('content')
    </main>
    <script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
