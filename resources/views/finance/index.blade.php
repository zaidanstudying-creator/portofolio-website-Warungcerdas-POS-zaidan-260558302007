@extends('layouts.app')

@section('title', 'Keuangan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Dashboard Keuangan</h2>
            <p class="text-sm text-slate-500">Ringkasan pemasukan, pengeluaran, dan laba.</p>
        </div>
        <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
            <a
                href="{{ route('finance.export', array_filter(request()->query(), fn ($v) => $v !== '')) }}"
                class="btn-ghost min-h-11 whitespace-nowrap"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
                </svg>
                <span>Ekspor CSV</span>
            </a>
            <a href="{{ route('finance.pengeluaran.index') }}" class="btn-primary min-h-11 whitespace-nowrap">Kelola Pengeluaran</a>
            <a href="{{ route('finance.pemasukan-lain.index') }}" class="btn-primary min-h-11 whitespace-nowrap">Kelola Pemasukan Lain</a>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="card p-5">
        <form method="GET" action="{{ route('finance.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-0 basis-full sm:basis-44 sm:grow">
                <label class="label" for="period">Periode</label>
                <select id="period" name="period" class="input" onchange="toggleCustomDates()">
                    <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hari Ini</option>
                    <option value="yesterday" {{ $period === 'yesterday' ? 'selected' : '' }}>Kemarin</option>
                    <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>7 Hari Terakhir</option>
                    <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>30 Hari Terakhir</option>
                    <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Rentang Tanggal Custom</option>
                </select>
            </div>

            <div class="min-w-0 basis-full sm:basis-44 sm:grow" id="start-date-group" style="display: {{ $period === 'custom' ? 'block' : 'none' }};">
                <label class="label" for="start_date">Tanggal Mulai</label>
                <input type="date" id="start_date" name="start_date" class="input" value="{{ $startDate ?? '' }}" max="{{ $endDate ?? \Carbon\Carbon::now()->format('Y-m-d') }}">
            </div>

            <div class="min-w-0 basis-full sm:basis-44 sm:grow" id="end-date-group" style="display: {{ $period === 'custom' ? 'block' : 'none' }};">
                <label class="label" for="end_date">Tanggal Akhir</label>
                <input type="date" id="end_date" name="end_date" class="input" value="{{ $endDate ?? '' }}" min="{{ $startDate ?? '' }}" max="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
            </div>

            <div class="min-w-0 basis-full sm:basis-44 sm:grow">
                <button type="submit" class="btn-primary w-full sm:w-auto">Terapkan</button>
            </div>
        </form>
    </div>

    {{-- HPP Warning --}}
    @if ($hasIncompleteHpp)
    <div class="card p-4 border-amber-200 bg-amber-50 rounded-xl">
        <div class="flex items-start gap-3">
            <svg class="shrink-0 mt-0.5 h-5 w-5 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <div class="flex-1">
                <p class="text-sm font-medium text-amber-800">Perhitungan Laba Belum Lengkap</p>
                <p class="mt-1 text-xs text-amber-700">
                    Terdapat <strong>{{ $productsWithoutHpp }}</strong> produk dan <strong>{{ $toppingsWithoutHpp }}</strong> topping yang terjual belum memiliki HPP snapshot.
                    Angka laba kotor dan laba operasional di bawah bersifat <strong>estimasi, dapat lebih tinggi dari laba sebenarnya</strong> (HPP null dianggap 0).
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Ringkasan Kartu --}}
    <div class="grid min-w-0 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Omzet Bersih --}}
        <div class="card p-5">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Omzet Bersih</p>
            <p class="mt-2 text-2xl font-bold text-slate-900"><x-money :value="$omzetBersih" /></p>
            <p class="mt-1 text-xs text-slate-500">Total penjualan lunas</p>
        </div>

        {{-- Total HPP --}}
        <div class="card p-5">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">HPP</p>
            <p class="mt-2 text-2xl font-bold {{ $hasIncompleteHpp ? 'text-amber-600' : 'text-slate-900' }}"><x-money :value="$totalHpp" /></p>
            <p class="mt-1 text-xs text-slate-500">Biaya pokok penjualan</p>
        </div>

        {{-- Laba Kotor --}}
        <div class="card p-5">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Laba Kotor</p>
            <p class="mt-2 text-2xl font-bold {{ $labaKotor >= 0 ? 'text-emerald-600' : 'text-red-600' }}"><x-money :value="$labaKotor" /></p>
            <p class="mt-1 text-xs text-slate-500">Omzet - HPP</p>
        </div>

        {{-- Laba Operasional --}}
        <div class="card p-5">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Laba Operasional</p>
            <p class="mt-2 text-2xl font-bold {{ $labaOperasional >= 0 ? 'text-emerald-600' : 'text-red-600' }}"><x-money :value="$labaOperasional" /></p>
            <p class="mt-1 text-xs text-slate-500">Laba Kotor - Pengeluaran + Pemasukan Lain</p>
        </div>
    </div>

    {{-- Detail Breakdown --}}
    <div class="grid min-w-0 gap-4 lg:grid-cols-2">
        {{-- Pemasukan --}}
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-4 flex items-center justify-between">
                Pemasukan
                <span class="text-emerald-600 font-medium"><x-money :value="$kasMasuk" /></span>
            </h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Penjualan</dt>
                    <dd class="font-medium text-slate-700"><x-money :value="$totalPenjualan" /></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Diskon</dt>
                    <dd class="font-medium text-red-600">− <x-money :value="$totalDiskon" /></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Biaya Tambahan</dt>
                    <dd class="font-medium text-slate-700"><x-money :value="$totalBiayaTambahan" /></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Biaya Antar (Delivery)</dt>
                    <dd class="font-medium text-slate-700"><x-money :value="$totalDeliveryFee" /></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Komisi Platform</dt>
                    <dd class="font-medium text-red-600">− <x-money :value="$totalPlatformCommission" /></dd>
                </div>
                <div class="flex justify-between border-t border-slate-100 pt-2">
                    <dt class="text-slate-500">Pemasukan Lain (Modal)</dt>
                    <dd class="font-medium text-slate-700"><x-money :value="$pemasukanModal" /></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Pemasukan Lain (Lainnya)</dt>
                    <dd class="font-medium text-slate-700"><x-money :value="$pemasukanLainnya" /></dd>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold">
                    <dt class="text-slate-900">Total Kas Masuk</dt>
                    <dd class="text-emerald-600"><x-money :value="$kasMasuk" /></dd>
                </div>
            </dl>
        </div>

        {{-- Pengeluaran --}}
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-4 flex items-center justify-between">
                Pengeluaran Operasional
                <span class="text-red-600 font-medium"><x-money :value="$totalPengeluaran" /></span>
            </h3>
            <dl class="space-y-2 text-sm max-h-64 overflow-y-auto">
                @forelse ($pengeluaranPerKategori as $item)
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ $item->nama }}</dt>
                    <dd class="font-medium text-red-600"><x-money :value="$item->total" /></dd>
                </div>
                @empty
                <p class="text-sm text-slate-500 text-center py-4">Belum ada pengeluaran pada periode ini.</p>
                @endforelse
                <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold">
                    <dt class="text-slate-900">Total Kas Keluar</dt>
                    <dd class="text-red-600"><x-money :value="$totalPengeluaran" /></dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Arus Kas & Margin --}}
    <div class="grid min-w-0 gap-4 lg:grid-cols-3">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">Arus Kas</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Kas Masuk</dt>
                    <dd class="font-medium text-emerald-600"><x-money :value="$kasMasuk" /></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Kas Keluar</dt>
                    <dd class="font-medium text-red-600"><x-money :value="$kasKeluar" /></dd>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold">
                    <dt class="text-slate-900">Saldo Bersih</dt>
                    <dd class="{{ $saldoAkhir >= 0 ? 'text-emerald-600' : 'text-red-600' }}"><x-money :value="$saldoAkhir" /></dd>
                </div>
            </dl>
        </div>

        <div class="card p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">Margin Laba</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Margin Laba Kotor</dt>
                    <dd class="font-medium {{ $omzetBersih > 0 ? ($labaKotor / $omzetBersih * 100 >= 0 ? 'text-emerald-600' : 'text-red-600') : 'text-slate-500' }}">
                        {{ $omzetBersih > 0 ? number_format(($labaKotor / $omzetBersih) * 100, 1) : 0 }}%
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Margin Laba Operasional</dt>
                    <dd class="font-medium {{ $omzetBersih > 0 ? ($labaOperasional / $omzetBersih * 100 >= 0 ? 'text-emerald-600' : 'text-red-600') : 'text-slate-500' }}">
                        {{ $omzetBersih > 0 ? number_format($marginLabaOperasional, 1) : 0 }}%
                    </dd>
                </div>
            </dl>
        </div>

        <div class="card p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">Periode</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Dari</dt>
                    <dd class="font-medium text-slate-700">{{ $resolvedStart->format('d/m/Y') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Sampai</dt>
                    <dd class="font-medium text-slate-700">{{ $resolvedEnd->format('d/m/Y') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Grafik Tren Laba Harian --}}
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Tren Laba Harian</h3>
        <div class="relative h-80 w-full min-w-0">
            <canvas id="profitChart"></canvas>
        </div>
    </div>

    {{-- Grafik Arus Kas Harian --}}
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Arus Kas Harian</h3>
        <div class="relative h-80 w-full min-w-0">
            <canvas id="cashFlowChart"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const formatRupiah = (value) => 'Rp ' + Math.round(Number(value)).toLocaleString('id-ID');

    const dailyData = @json($dailyData);

    // Profit Chart
    const profitCtx = document.getElementById('profitChart');
    if (profitCtx) {
        const labels = Object.keys(dailyData).map(d => d.split('-').slice(1).join('/'));
        const labaKotorData = Object.values(dailyData).map(d => d.laba_kotor);
        const labaOperasionalData = Object.values(dailyData).map(d => d.laba_operasional);

        new Chart(profitCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Laba Kotor',
                        data: labaKotorData,
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5, 150, 105, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'Laba Operasional',
                        data: labaOperasionalData,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ctx.dataset.label + ': ' + formatRupiah(ctx.raw),
                            title: (ctx) => 'Tanggal: ' + ctx[0].label
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => formatRupiah(value)
                        }
                    }
                }
            }
        });
    }

    // Cash Flow Chart
    const cashFlowCtx = document.getElementById('cashFlowChart');
    if (cashFlowCtx) {
        const labels = Object.keys(dailyData).map(d => d.split('-').slice(1).join('/'));
        const kasMasukData = Object.values(dailyData).map(d => d.penjualan + d.pemasukan_lain);
        const kasKeluarData = Object.values(dailyData).map(d => d.hpp + d.pengeluaran);

        new Chart(cashFlowCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Kas Masuk',
                        data: kasMasukData,
                        backgroundColor: 'rgba(5, 150, 105, 0.7)',
                        borderColor: '#059669',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                    {
                        label: 'Kas Keluar',
                        data: kasKeluarData,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: '#ef4444',
                        borderWidth: 1,
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ctx.dataset.label + ': ' + formatRupiah(ctx.raw),
                            title: (ctx) => 'Tanggal: ' + ctx[0].label
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => formatRupiah(value)
                        }
                    }
                }
            }
        });
    }

    function toggleCustomDates() {
        const period = document.getElementById('period').value;
        const show = period === 'custom';
        document.getElementById('start-date-group').style.display = show ? 'block' : 'none';
        document.getElementById('end-date-group').style.display = show ? 'block' : 'none';
    }

    document.addEventListener('DOMContentLoaded', toggleCustomDates);
</script>
@endpush