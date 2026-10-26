<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-form-panel" aria-labelledby="auth-page-title">
            <div class="auth-form-wrap">
                <a class="auth-mobile-wordmark" href="{{ route('login') }}" aria-label="WarungCerdas POS">
                    <span>WarungCerdas</span>
                    <span class="auth-wordmark-badge">POS</span>
                </a>

                @yield('content')

                <p class="auth-footnote">WarungCerdas POS <span aria-hidden="true">·</span> Operasional kasir harian</p>
            </div>
        </section>

        <aside class="auth-brand-panel" aria-labelledby="auth-brand-title">
            <div class="auth-brand-glow auth-brand-glow-one" aria-hidden="true"></div>
            <div class="auth-brand-glow auth-brand-glow-two" aria-hidden="true"></div>

            <a class="auth-wordmark" href="{{ route('login') }}" aria-label="WarungCerdas POS">
                <span>WarungCerdas</span>
                <span class="auth-wordmark-badge">POS</span>
            </a>

            <div class="auth-brand-copy">
                <p class="auth-eyebrow"><span aria-hidden="true"></span> Teman usaha kuliner</p>
                <h1 id="auth-brand-title">Sibuk melayani,<br><em>tetap teratur.</em></h1>
                <p>Kelola pesanan dan transaksi kasir dengan lebih nyaman, agar fokus tetap pada pelanggan.</p>
            </div>

            <div class="auth-illustration" aria-hidden="true">
                <svg viewBox="0 0 520 330" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="260" cy="282" rx="205" ry="22" fill="#0B2E57" fill-opacity=".16"/>
                    <path d="M92 180c0 82 66 148 148 148s148-66 148-148H92Z" fill="#F7FAFC"/>
                    <path d="M92 180c0 82 66 148 148 148s148-66 148-148H92Z" stroke="#D7E7F3" stroke-width="4"/>
                    <ellipse cx="240" cy="179" rx="148" ry="46" fill="#E9F1F7"/>
                    <ellipse cx="240" cy="173" rx="128" ry="35" fill="#FFFDF8"/>
                    <path d="M157 174c18-20 47-20 65 0 18 20 47 20 65 0 18-20 46-20 64 0" stroke="#E99A32" stroke-width="9" stroke-linecap="round"/>
                    <path d="M180 157c12-11 28-12 39-2m31 35c12-11 28-12 39-2m21-31c9-8 20-9 29-3" stroke="#E9B54A" stroke-width="6" stroke-linecap="round"/>
                    <circle cx="188" cy="177" r="8" fill="#E86E43"/><circle cx="286" cy="159" r="7" fill="#E86E43"/><circle cx="326" cy="184" r="8" fill="#E86E43"/>
                    <path d="M363 72h83a16 16 0 0 1 16 16v109a16 16 0 0 1-16 16h-83a16 16 0 0 1-16-16V88a16 16 0 0 1 16-16Z" fill="#F7FAFC"/>
                    <path d="M374 106h60m-60 22h44m-44 22h54" stroke="#B8CCDD" stroke-width="7" stroke-linecap="round"/>
                    <path d="M374 177h27" stroke="#E89537" stroke-width="9" stroke-linecap="round"/>
                    <path d="M360 72v-9a17 17 0 0 1 17-17h55a17 17 0 0 1 17 17v9" stroke="#D7E7F3" stroke-width="4"/>
                    <path d="M102 117c0-13 10-23 23-23s23 10 23 23-10 23-23 23-23-10-23-23Z" fill="#F3A343" fill-opacity=".9"/>
                    <path d="M110 117h30m-15-15v30" stroke="#FFF7E8" stroke-width="4" stroke-linecap="round"/>
                    <path d="M422 246c0-10 8-18 18-18s18 8 18 18-8 18-18 18-18-8-18-18Z" fill="#E86E43" fill-opacity=".9"/>
                    <path d="M440 237v18m-6-12c0-2 2-4 6-4s6 2 6 4-2 4-6 4-6 2-6 4 2 4 6 4 6-2 6-4" stroke="#FFF7E8" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </div>

            <div class="auth-brand-footer">
                <span class="auth-footer-dot" aria-hidden="true"></span>
                <span>Pesanan rapi, pelayanan tetap hangat.</span>
            </div>
        </aside>
    </main>
</body>
</html>
