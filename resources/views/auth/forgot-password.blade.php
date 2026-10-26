@extends('layouts.guest')

@section('title', 'Lupa Password')

@section('content')
    <div class="auth-card">
        <header class="auth-card-heading">
            <a href="{{ route('login') }}" class="auth-back-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/><path d="M9 12h12"/></svg>
                Kembali ke login
            </a>
            <p class="auth-card-kicker">Pemulihan akun</p>
            <h1 id="auth-page-title">Lupa kata sandi?</h1>
            <p>Masukkan email yang digunakan saat masuk. Kami akan mengirim tautan untuk mengatur ulang kata sandi jika akun dapat menerima email.</p>
        </header>

        @if (session('status'))
            <div class="auth-alert auth-alert-success" role="status">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                <p>{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div id="auth-errors" class="auth-alert auth-alert-error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                <p>{{ $errors->first() }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="auth-form">
            @csrf

            <div class="auth-field">
                <label for="email" class="label">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="input"
                    autocomplete="email"
                    required
                    @if ($errors->has('email')) aria-invalid="true" aria-describedby="auth-errors" @endif
                >
            </div>

            <button type="submit" class="btn-primary auth-submit">Kirim tautan reset</button>
        </form>
    </div>
@endsection
