@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
    <div class="auth-card">
        <header class="auth-card-heading">
            <a href="{{ route('login') }}" class="auth-back-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/><path d="M9 12h12"/></svg>
                Kembali ke login
            </a>
            <p class="auth-card-kicker">Keamanan akun</p>
            <h1 id="auth-page-title">Buat kata sandi baru</h1>
            <p>Pilih kata sandi baru untuk akun Anda. Gunakan setidaknya 8 karakter.</p>
        </header>

        @if ($errors->any())
            <div id="auth-errors" class="auth-alert auth-alert-error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                <p>{{ $errors->first() }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="auth-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="auth-field">
                <label for="password" class="label">Kata sandi baru</label>
                <div class="auth-password-wrap">
                    <input id="password" type="password" name="password" class="input" required autocomplete="new-password" autofocus data-password-input @if ($errors->has('password')) aria-invalid="true" aria-describedby="auth-errors" @endif>
                    <button class="auth-password-toggle" type="button" data-password-toggle aria-label="Tampilkan kata sandi" aria-pressed="false">
                        <svg data-eye-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                    </button>
                </div>
            </div>

            <div class="auth-field">
                <label for="password_confirmation" class="label">Konfirmasi kata sandi</label>
                <div class="auth-password-wrap">
                    <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password" data-password-input @if ($errors->has('password_confirmation')) aria-invalid="true" aria-describedby="auth-errors" @endif>
                    <button class="auth-password-toggle" type="button" data-password-toggle aria-label="Tampilkan konfirmasi kata sandi" aria-pressed="false">
                        <svg data-eye-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary auth-submit">Simpan kata sandi baru</button>
        </form>
    </div>
@endsection
