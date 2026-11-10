@extends('layouts.welcome')

@section('title', 'Beranda')

@section('content')
    <section class="grid items-center gap-8 rounded-[2rem] border border-[#e1e3d8] bg-[#fffdf8] px-5 py-7 shadow-[0_24px_70px_-42px_rgba(35,67,46,0.34)] sm:px-9 sm:py-10 lg:grid-cols-[1fr_0.95fr] lg:gap-12 lg:px-12 lg:py-12">
        <div class="relative z-10">
            <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-[#e4d7bd] bg-[#fbf4e5] px-3.5 py-2 text-[11px] font-bold uppercase tracking-[0.15em] text-[#76562e]">
                <span class="h-2 w-2 rounded-full bg-[#d89245]" aria-hidden="true"></span>
                Sistem kasir dan operasional
            </p>
            <p class="mb-2 text-sm font-semibold text-[#39734d]">Selamat datang, {{ auth()->user()->name }}</p>
            <h1 class="max-w-2xl text-3xl font-semibold leading-[1.1] tracking-[-0.04em] text-[#213e2c] sm:text-4xl lg:text-[3.25rem]">{{ \App\Http\Controllers\SettingController::storeTitle() }}</h1>
            <p class="mt-4 max-w-xl text-sm leading-7 text-slate-600 sm:text-base">
                {{ \App\Http\Controllers\SettingController::subtitle() ?: 'Kelola pesanan dan operasional toko dengan mudah melalui WarungCerdas POS.' }}
            </p>

            <p data-store-closed-banner @if (\App\Http\Controllers\SettingController::isStoreOpen()) hidden @endif class="mt-5 max-w-lg rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800" role="status">
                Toko sedang tutup. Transaksi tidak dapat diproses. Silakan hubungi admin untuk membuka toko kembali.
            </p>

            <div class="mt-7 flex flex-wrap items-center gap-3">
                <a data-store-start href="{{ route('pos.index') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#285d3d] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[#244b34]/15 transition hover:bg-[#1e4b31] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" @if (!\App\Http\Controllers\SettingController::isStoreOpen()) aria-disabled="true" tabindex="-1" @endif>
                    {{ \App\Http\Controllers\SettingController::isStoreOpen() ? 'Mulai Kasir' : 'Kasir Sedang Tutup' }}
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </a>
                <a href="{{ route('orders.index') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-[#d8ddcf] bg-white px-5 py-3 text-sm font-semibold text-[#345641] transition hover:bg-[#f8f8f2] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
                    Lihat Pesanan
                </a>
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-[#e9e9df] pt-5 text-xs font-medium text-slate-500">
                <span class="inline-flex items-center gap-2"><span class="grid h-5 w-5 place-items-center rounded-full bg-[#e8f1e6] text-[#39734d]" aria-hidden="true">✓</span>Transaksi tercatat rapi</span>
                <span class="inline-flex items-center gap-2"><span class="grid h-5 w-5 place-items-center rounded-full bg-[#fbf0db] text-[#9a6a31]" aria-hidden="true">✓</span>Mudah dipantau</span>
            </div>
        </div>

        <div class="relative mx-auto w-full max-w-[560px]" aria-hidden="true">
            <div class="absolute inset-[8%] rounded-full bg-[#e7eedf]"></div>
            <div class="absolute left-[12%] top-[8%] h-16 w-16 rounded-full bg-[#f1d6a7]/70 blur-2xl sm:h-24 sm:w-24"></div>
            <svg class="relative block h-auto w-full drop-shadow-[0_24px_36px_rgba(41,67,46,0.13)]" viewBox="0 0 620 480" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M72 359c26-91 108-149 229-149 103 0 198 50 248 149v54H72v-54Z" fill="#d4e2ce"/>
                <path d="M98 374c18-57 73-94 142-94 69 0 114 36 142 94" stroke="#f7f5e9" stroke-width="11" stroke-linecap="round"/>
                <path d="M373 372c20-47 59-73 111-73 38 0 69 13 95 39" stroke="#f7f5e9" stroke-width="11" stroke-linecap="round"/>
                <ellipse cx="327" cy="405" rx="223" ry="23" fill="#294734" fill-opacity=".13"/>

                <rect x="120" y="91" width="190" height="130" rx="19" transform="rotate(-5 120 91)" fill="#fffdf8"/>
                <rect x="137" y="108" width="44" height="44" rx="13" fill="#f7ecd8"/>
                <path d="m149 130 9 9 16-19" stroke="#39734d" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="m194 122 91-8m-87 25 65-6" stroke="#cbd2c4" stroke-width="7" stroke-linecap="round"/>
                <rect x="137" y="170" width="139" height="12" rx="6" fill="#e9eee5"/>
                <rect x="137" y="170" width="91" height="12" rx="6" fill="#d49248"/>

                <path d="M112 274h390c15 0 27 12 27 27v62H85v-62c0-15 12-27 27-27Z" fill="#2f6545"/>
                <path d="M85 355h444v39c0 12-10 22-22 22H107c-12 0-22-10-22-22v-39Z" fill="#bd7548"/>
                <path d="M115 379h386" stroke="#e0a274" stroke-width="6" stroke-linecap="round"/>
                <path d="M158 283v61m344-61v61" stroke="#7da17c" stroke-width="5" stroke-linecap="round"/>

                <rect x="268" y="174" width="164" height="183" rx="19" fill="#f8f4e9"/>
                <rect x="282" y="189" width="136" height="125" rx="10" fill="#244f37"/>
                <path d="M298 211h37m11 0h53M298 228h25m12 0h64M298 245h40m11 0h50" stroke="#d8e4d7" stroke-width="7" stroke-linecap="round"/>
                <rect x="298" y="267" width="104" height="28" rx="8" fill="#e9b468"/>
                <path d="M320 328h60" stroke="#c8cbbb" stroke-width="7" stroke-linecap="round"/>
                <circle cx="350" cy="344" r="4" fill="#9cae9a"/>

                <path d="M455 191c0-8 6-14 14-14s14 6 14 14v96h-28v-96Z" fill="#fffaf0"/>
                <path d="M455 202h28m-28 15h28m-28 15h28" stroke="#d4c7aa" stroke-width="4"/>
                <path d="M455 244h28" stroke="#d49248" stroke-width="5" stroke-dasharray="3 5"/>

                <path d="M115 262c0-18 12-34 27-40m-12 43c0-12 8-24 18-30m-9 33c0-8 4-15 10-20" stroke="#79946e" stroke-width="6" stroke-linecap="round"/>
                <path d="M524 268c0-17-10-30-23-37m8 40c0-11-6-21-15-28m8 31c0-7-4-13-9-18" stroke="#79946e" stroke-width="6" stroke-linecap="round"/>
                <circle cx="504" cy="161" r="14" fill="#e9b468"/>
                <path d="M498 161h12m-6-6v12" stroke="#fff9ec" stroke-width="3" stroke-linecap="round"/>
                <circle cx="177" cy="231" r="6" fill="#d49248"/>
                <circle cx="495" cy="113" r="5" fill="#79946e"/>
            </svg>
            <div class="absolute bottom-[10%] right-[1%] rounded-2xl border border-[#e5e5d9] bg-white/95 px-4 py-3 shadow-lg shadow-[#244b34]/10 sm:right-[4%] sm:px-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-500">Hari ini</p>
                <p class="mt-1 text-sm font-semibold text-[#285d3d]">Siap melayani pelanggan</p>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:mt-7 lg:grid-cols-3" aria-label="Akses cepat">
        <a href="{{ route('pos.index') }}" class="group flex items-center gap-4 rounded-2xl border border-[#e1e3d8] bg-white/85 p-5 shadow-sm transition hover:-translate-y-0.5 hover:bg-white hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#e8f1e6] text-[#39734d]">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2l2.3 11.1a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 8H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
            </span>
            <span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-slate-800">Kasir</span><span class="mt-1 block text-xs leading-5 text-slate-500">Mulai transaksi baru</span></span>
            <svg class="h-4 w-4 text-slate-400 transition group-hover:translate-x-1 group-hover:text-[#39734d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
        </a>

        <a href="{{ route('orders.index') }}" class="group flex items-center gap-4 rounded-2xl border border-[#e1e3d8] bg-white/85 p-5 shadow-sm transition hover:-translate-y-0.5 hover:bg-white hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#fbf0db] text-[#9a6a31]">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 4h11v16H5V4h3Zm0-1v4m8-4v4M8 11h8m-8 4h8"/></svg>
            </span>
            <span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-slate-800">Pesanan</span><span class="mt-1 block text-xs leading-5 text-slate-500">Pantau pesanan masuk</span></span>
            <svg class="h-4 w-4 text-slate-400 transition group-hover:translate-x-1 group-hover:text-[#39734d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
        </a>

        @if (auth()->user()->isAdmin())
            <a href="{{ route('dashboard.index') }}" class="group flex items-center gap-4 rounded-2xl border border-[#e1e3d8] bg-white/85 p-5 shadow-sm transition hover:-translate-y-0.5 hover:bg-white hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#f8e8e2] text-[#aa5e43]">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19V5m0 14h17M8 15l3-4 3 2 5-6"/><path d="M16 7h3v3"/></svg>
                </span>
                <span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-slate-800">Ringkasan Toko</span><span class="mt-1 block text-xs leading-5 text-slate-500">Lihat aktivitas operasional</span></span>
                <svg class="h-4 w-4 text-slate-400 transition group-hover:translate-x-1 group-hover:text-[#39734d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </a>
        @endif
    </section>
@endsection
