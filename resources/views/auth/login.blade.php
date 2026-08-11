@extends('layouts.app')

@section('title', 'Đăng nhập — '.config('app.name'))

@section('content')
    <section class="auth-shell" aria-labelledby="login-title">
        <div class="card auth-card">
            <p class="eyebrow">Quản trị nội bộ</p>
            <h1 id="login-title">Đăng nhập</h1>
            <p class="muted">Nhập tài khoản quản trị để tiếp tục.</p>

            <form class="form-stack" method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="field">
                    <label for="username">Tên đăng nhập</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        value="{{ old('username') }}"
                        autocomplete="username"
                        autocapitalize="none"
                        required
                        autofocus
                        aria-describedby="username-error"
                    >
                    @error('username')
                        <p class="field-error" id="username-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Mật khẩu</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        aria-describedby="password-error"
                    >
                    @error('password')
                        <p class="field-error" id="password-error">{{ $message }}</p>
                    @enderror
                </div>

                <button class="button button-primary button-block" type="submit">Đăng nhập</button>
            </form>
        </div>
    </section>
@endsection
