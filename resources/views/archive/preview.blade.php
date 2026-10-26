@extends('layouts.app')

@section('title', 'Pratinjau Arsip')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Pratinjau Arsip</h2>
            <p class="text-sm text-slate-500">Periksa data sebelum membuat arsip.</p>
        </div>
        <a href="{{ route('archive.index') }}" class="btn-ghost">Kembali</a>
    </div>

    {{-- Summary Cards --}}
    <div class="grid min-w-0 gap-4 sm:grid-cols-4">
        <div class="card p-5">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Rentang Tanggal</p>
            <p class="mt-1 text-sm font-medium text-slate-900">{{ $startDate->format('d/m/Y') }} – {{ $endDate->format('d/m/Y') }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Filter Status</p>
            <p class="mt-1 text-sm font-medium text-slate-900 capitalize">{{ $statusFilter }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Total Transaksi</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($recordCount) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Total Nominal</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600"><x-money :value="$totalNominal" /></p>
        </div>
    </div>

    {{-- Format Info --}}
    <div class="card p-4 bg-blue-50 border-blue-200">
        <div class="flex items-center gap-3">
            <svg class="shrink-0 h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
            </svg>
            <div>
                <p class="text-sm font-medium text-blue-800">Format: {{ strtoupper($format) }}</p>
                <p class="mt-1 text-xs text-blue-700">
                    @if ($format === 'zip')
                        ZIP berisi file CSV transaksi + manifest JSON (checksum SHA256).
                    @elseif ($format === 'csv')
                        File CSV saja dengan header lengkap.
                    @else
                        PDF ringkasan transaksi dalam format landscape.
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Sample Data --}}
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Contoh Data (10 Transaksi Terbaru)</h3>

        <div class="min-w-0 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="th">Nomor Transaksi</th>
                        <th class="th">Waktu</th>
                        <th class="th">Tipe</th>
                        <th class="th">Customer</th>
                        <th class="th text-right">Total</th>
                        <th class="th">Metode</th>
                        <th class="th">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sampleOrders as $order)
                    <tr class="hover:bg-slate-50/70">
                        <td class="td font-medium text-slate-900">{{ $order->order_number }}</td>
                        <td class="td text-slate-600">{{ $order->created_at->timezone(\App\Services\OrderService::LOCAL_TIMEZONE)->format('d/m/Y H:i') }}</td>
                        <td class="td"><x-order-type-badge :type="$order->type" /></td>
                        <td class="td text-slate-600">{{ $order->customer_name ?? '-' }}</td>
                        <td class="td text-right font-medium text-slate-700"><x-money :value="$order->total" /></td>
                        <td class="td">
                            @if ($order->payment_method)
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium
                                    @if ($order->payment_method === \App\Enums\PaymentMethod::Cash)
                                        bg-emerald-50 text-emerald-700
                                    @else
                                        bg-blue-50 text-blue-700
                                    @endif
                                ">
                                    {{ $order->payment_method === \App\Enums\PaymentMethod::Cash ? 'Tunai' : 'QRIS' }}
                                </span>
                            @else
                                <span class="text-slate-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="td"><x-order-status-badge :status="$order->status" /></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">
                            Tidak ada data transaksi.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($recordCount > 10)
        <p class="mt-3 text-xs text-slate-500 text-center">Menampilkan 10 dari {{ number_format($recordCount) }} transaksi.</p>
        @endif
    </div>

    {{-- Confirm & Create --}}
    <form method="POST" action="{{ route('archive.create') }}" class="card p-5 border-t-4 border-emerald-500">
        @csrf
        <input type="hidden" name="tanggal_mulai" value="{{ $startDate->format('Y-m-d') }}">
        <input type="hidden" name="tanggal_akhir" value="{{ $endDate->format('Y-m-d') }}">
        <input type="hidden" name="status_filter" value="{{ $statusFilter }}">
        <input type="hidden" name="format" value="{{ $format }}">

        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-900">Konfirmasi Pembuatan Arsip</p>
                <p class="text-xs text-slate-500">Arsip akan dibuat dan diunduh otomatis. Riwayat arsip akan tersimpan untuk unduh ulang.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('archive.index') }}" class="btn-ghost">Batal</a>
                <button type="submit" class="btn-primary">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
                    </svg>
                    Buat & Unduh Arsip
                </button>
            </div>
        </div>
    </form>
</div>
@endsection