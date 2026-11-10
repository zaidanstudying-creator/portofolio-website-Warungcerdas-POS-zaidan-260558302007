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
                @yield('content')

                <p class="auth-footnote">{{ \App\Http\Controllers\SettingController::storeName() }} <span aria-hidden="true">·</span> Operasional kasir harian</p>
                <p class="mt-2 text-center text-xs text-slate-500">
                    Butuh bantuan? Hubungi Zaidan
                    <a href="tel:+6285158512007" class="font-semibold text-[#39734d] underline-offset-4 hover:underline">085158512007</a>
                </p>
            </div>
        </section>

        <aside class="auth-brand-panel" aria-labelledby="auth-brand-title">
            <div class="auth-brand-glow auth-brand-glow-one" aria-hidden="true"></div>
            <div class="auth-brand-glow auth-brand-glow-two" aria-hidden="true"></div>

            <a class="auth-wordmark" href="{{ route('login') }}" aria-label="WarungCerdas POS">
                <span class="auth-monogram" aria-hidden="true">{{ mb_substr(\App\Http\Controllers\SettingController::storeName(), 0, 1) }}</span>
                <span>{{ \App\Http\Controllers\SettingController::storeName() }}</span>
                <span class="auth-wordmark-badge">POS</span>
            </a>

            <div class="auth-brand-copy">
                <p class="auth-eyebrow"><span aria-hidden="true"></span> Kasir dan operasional dalam satu tempat</p>
                <h1 id="auth-brand-title">Jualan lancar,<br>warung <em>lebih rapi.</em></h1>
                <p>Bantu pelayanan tetap hangat, sementara transaksi dan pesanan tercatat dengan teratur.</p>
            </div>

            <div class="auth-illustration" aria-hidden="true">
                <svg viewBox="0 0 520 330" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="267" cy="303" rx="206" ry="17" fill="#0B2F25" fill-opacity=".26"/>
                    <circle cx="277" cy="169" r="146" fill="#E7B56E" fill-opacity=".14"/>
                    <path d="M82 228c28-43 67-64 117-64 32 0 58 11 83 34 24-30 57-45 98-45 31 0 60 9 87 27v96H82v-48Z" fill="#39745A" fill-opacity=".66"/>
                    <path d="M95 219h341c15 0 27 12 27 27v20H68v-20c0-15 12-27 27-27Z" fill="#E8B47D"/>
                    <path d="M82 264h367v34c0 8-7 15-15 15H97c-8 0-15-7-15-15v-34Z" fill="#C9814E"/>
                    <path d="M105 281h317" stroke="#E7AD78" stroke-width="5" stroke-linecap="round"/>
                    <rect x="206" y="118" width="137" height="111" rx="15" transform="rotate(-5 206 118)" fill="#F7F1E4"/>
                    <rect x="218" y="132" width="112" height="73" rx="8" transform="rotate(-5 218 132)" fill="#204C3A"/>
                    <path d="m234 151 78-7m-75 22 46-4m-43 19 58-5" stroke="#D5E4D7" stroke-width="5" stroke-linecap="round"/>
                    <circle cx="276" cy="216" r="5" fill="#E6A451"/>
                    <rect x="358" y="102" width="74" height="108" rx="9" transform="rotate(8 358 102)" fill="#FFF9EC"/>
                    <path d="m371 126 42 6m-45 9 39 6m-42 8 33 5" stroke="#C7CBBF" stroke-width="4" stroke-linecap="round"/>
                    <path d="m371 172 7 1m7 1 7 1m7 1 7 1m-30 8 7 1m8 1 7 1" stroke="#39805E" stroke-width="5" stroke-linecap="round"/>
                    <rect x="104" y="100" width="107" height="66" rx="14" fill="#FFF9EC"/>
                    <circle cx="126" cy="122" r="8" fill="#E6A451"/>
                    <path d="M143 120h49M117 143h74" stroke="#C7CBBF" stroke-width="5" stroke-linecap="round"/>
                    <path d="M78 200c0-19 10-35 23-42m-7 43c0-12 7-24 16-31m-8 34c0-7 4-14 10-19" stroke="#E5B373" stroke-width="6" stroke-linecap="round"/>
                    <path d="M451 203c0-18-10-32-22-40m7 42c0-12-6-22-15-29m8 32c0-7-4-13-9-18" stroke="#E5B373" stroke-width="6" stroke-linecap="round"/>
                    <circle cx="145" cy="197" r="12" fill="#E6A451"/>
                    <path d="M140 197h10m-5-5v10" stroke="#FFF9EC" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </div>

            <div class="auth-brand-footer">
                <span class="auth-footer-dot" aria-hidden="true"></span>
                <span>Setiap transaksi tercatat, pelayanan tetap hangat.</span>
            </div>
        </aside>
    </main>
</body>
</html>
