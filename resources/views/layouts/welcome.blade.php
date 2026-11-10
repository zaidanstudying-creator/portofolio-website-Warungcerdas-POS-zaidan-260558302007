<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Beranda') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-svh overflow-x-hidden bg-[#f3f1e9] font-sans text-slate-800 antialiased">
    <div class="relative isolate min-h-svh overflow-hidden">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_82%_12%,rgba(228,169,91,0.15),transparent_30%),radial-gradient(ellipse_at_8%_78%,rgba(49,112,77,0.10),transparent_34%)]"></div>

        <header class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-5 sm:px-8 lg:px-10">
            <a href="{{ route('welcome') }}" class="flex min-w-0 items-center gap-3 rounded-xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-700" aria-label="Beranda {{ \App\Http\Controllers\SettingController::storeName() }}">
                <x-store-logo class="h-11 w-11 rounded-2xl bg-[#285d3d] text-[#fff8e9] shadow-sm" />
                <span class="min-w-0">
                    <span class="block truncate text-sm font-bold tracking-tight text-[#244b34] sm:text-base">{{ \App\Http\Controllers\SettingController::storeName() }}</span>
                    <span class="mt-0.5 block text-[10px] font-bold uppercase tracking-[0.16em] text-[#9a6a31]">WarungCerdas POS</span>
                </span>
            </a>

            <div class="flex flex-wrap items-center justify-end gap-3 sm:gap-5">
                <x-store-status />
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ auth()->user()->isAdmin() ? 'Admin Toko' : 'Kasir' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-[#d8ddcf] bg-white/80 px-3.5 py-2 text-sm font-semibold text-[#345641] shadow-sm transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5H4v14h5M13 8l4 4-4 4M8 12h13" /></svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl px-4 pb-10 pt-3 sm:px-8 sm:pb-14 sm:pt-6 lg:px-10">
            <x-flash />
            @yield('content')
        </main>

        <footer class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-2 px-4 pb-6 text-xs text-slate-500 sm:px-8 lg:px-10">
            <span>{{ \App\Http\Controllers\SettingController::storeName() }} <span class="mx-1 text-[#d28b45]">·</span> Operasional kasir harian</span>
            <span class="inline-flex flex-wrap items-center gap-x-1.5">
                Butuh bantuan? Hubungi Zaidan
                <a href="tel:+6285158512007" class="font-semibold text-[#39734d] underline-offset-4 hover:underline">085158512007</a>
            </span>
        </footer>
    </div>

    <x-store-status-dialog />
</body>
</html>
