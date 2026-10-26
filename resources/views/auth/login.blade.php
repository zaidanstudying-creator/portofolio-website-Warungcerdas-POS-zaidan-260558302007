@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div class="auth-card">
        <header class="auth-card-heading">
            <p class="auth-card-kicker">Selamat datang kembali</p>
            <h1 id="auth-page-title">Masuk ke akun Anda</h1>
            <p>Gunakan akun WarungCerdas POS untuk melanjutkan.</p>
        </header>

        <x-flash />

        @if ($errors->any())
            <div id="auth-errors" class="auth-alert auth-alert-error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" /><path d="M12 8v5M12 16.5v.01" />
                </svg>
                <p>{{ $errors->first() }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="auth-form" data-testid="login-form">
            @csrf

            <div class="auth-field">
                <label for="email" class="label">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    class="input"
                    autocomplete="username"
                    autofocus
                    required
                    @if ($errors->has('email')) aria-invalid="true" aria-describedby="auth-errors" @endif
                >
            </div>

            <div class="auth-field">
                <div class="auth-label-row">
                    <label for="password" class="label">Kata sandi</label>
                    <a href="{{ route('password.request') }}" class="auth-inline-link">Lupa kata sandi?</a>
                </div>
                <div class="auth-password-wrap">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="input"
                        autocomplete="current-password"
                        required
                        data-password-input
                        @if ($errors->has('password')) aria-invalid="true" aria-describedby="auth-errors" @endif
                    >
                    <button class="auth-password-toggle" type="button" data-password-toggle aria-label="Tampilkan kata sandi" aria-pressed="false">
                        <svg data-eye-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary auth-submit">Masuk</button>
        </form>
    </div>
@endsection
