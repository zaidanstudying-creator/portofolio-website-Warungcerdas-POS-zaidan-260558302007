@extends('layouts.app')

@section('title', 'Arsip Transaksi')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Arsip Transaksi</h2>
            <p class="text-sm text-slate-500">Buat dan kelola arsip transaksi berdasarkan rentang tanggal.</p>
        </div>
        <button
            type="button"
            id="create-archive-btn"
            class="btn-primary"
            data-modal-target="#create-archive-modal"
        >
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
            </svg>
            <span>Buat Arsip Baru</span>
        </button>
    </div>

    {{-- Create Archive Modal --}}
    <dialog id="create-archive-modal" class="m-auto max-h-[calc(100vh-2rem)] w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 p-0 shadow-xl backdrop:bg-slate-950/50">
        <form method="GET" action="{{ route('archive.preview') }}" id="archive-preview-form">
            <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <p class="text-sm font-semibold text-slate-900">Buat Arsip Transaksi</p>
                    <p class="text-xs text-slate-500">Konfigurasi rentang tanggal dan filter</p>
                </div>
                <button type="button" class="grid h-8 w-8 place-items-center rounded-lg text-xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">&times;</button>
            </div>
            <div class="max-h-[75vh] space-y-4 overflow-y-auto px-5 py-4">
                <div class="space-y-4">
                    <div>
                        <label class="label" for="tanggal_mulai">Tanggal Mulai</label>
                        <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="input" max="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="label" for="tanggal_akhir">Tanggal Akhir</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" class="input" max="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="label" for="status_filter">Filter Status</label>
                        <select id="status_filter" name="status_filter" class="input" required>
                            <option value="all">Semua Status</option>
                            <option value="paid">Lunas</option>
                            <option value="unpaid">Belum Lunas</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>
                    <div>
                        <label class="label" for="format">Format Arsip</label>
                        <select id="format" name="format" class="input" required>
                            <option value="zip">ZIP (CSV + Manifest)</option>
                            <option value="csv">CSV Only</option>
                            <option value="pdf">PDF Ringkasan</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">ZIP berisi CSV transaksi + manifest JSON dengan checksum.</p>
                    </div>
                </div>
                <p id="archive-preview-error" class="hidden rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-700" role="alert"></p>
            </div>
            <div class="flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row-reverse">
                <button type="submit" class="btn-primary w-full sm:w-auto">Pratinjau & Buat</button>
                <button type="button" class="btn-ghost w-full sm:w-auto" data-modal-close>Batal</button>
            </div>
        </form>
    </dialog>

    {{-- Archive History --}}
    <div class="card p-5">
        <div class="min-w-0 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="th">Periode</th>
                        <th class="th">Filter</th>
                        <th class="th text-center">Format</th>
                        <th class="th text-right">Record</th>
                        <th class="th text-right">Total Nominal</th>
                        <th class="th text-center">Checksum</th>
                        <th class="th">Dibuat Oleh</th>
                        <th class="th">Waktu</th>
                        <th class="th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($archives as $arsip)
                    <tr class="hover:bg-slate-50/70">
                        <td class="td text-slate-600">
                            {{ $arsip->tanggal_mulai->format('d/m/Y') }} – {{ $arsip->tanggal_akhir->format('d/m/Y') }}
                        </td>
                        <td class="td">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium
                                @if ($arsip->status_filter === 'all')
                                    bg-slate-50 text-slate-700
                                @elseif ($arsip->status_filter === 'paid')
                                    bg-emerald-50 text-emerald-700
                                @elseif ($arsip->status_filter === 'unpaid')
                                    bg-amber-50 text-amber-700
                                @else
                                    bg-red-50 text-red-700
                                @endif
                            ">
                                {{ ucfirst($arsip->status_filter) }}
                            </span>
                        </td>
                        <td class="td text-center">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium bg-blue-50 text-blue-700">
                                {{ strtoupper($arsip->format) }}
                            </span>
                        </td>
                        <td class="td text-right text-slate-600">{{ number_format($arsip->record_count) }}</td>
                        <td class="td text-right font-medium text-slate-700"><x-money :value="$arsip->total_nominal" /></td>
                        <td class="td text-center">
                            @if ($arsip->checksum)
                                <span class="font-mono text-xs text-slate-500">{{ Str::limit($arsip->checksum, 16) }}</span>
                            @else
                                <span class="text-slate-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="td text-slate-600">{{ $arsip->user->name ?? '-' }}</td>
                        <td class="td text-slate-600">{{ $arsip->created_at->format('d/m/Y H:i') }}</td>
                        <td class="td text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('archive.download', $arsip) }}" class="btn-ghost text-xs">
                                    <svg class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
                                    </svg>
                                    Unduh
                                </a>
                                <form method="POST" action="{{ route('archive.destroy', $arsip) }}" onsubmit="return confirm('Yakin ingin menghapus riwayat arsip ini? File arsip juga akan dihapus.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-ghost text-xs text-red-600 hover:bg-red-50">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-5 py-10 text-center text-sm text-slate-500">
                            Belum ada arsip transaksi.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($archives->hasPages())
        <div class="mt-4">
            {{ $archives->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('create-archive-modal');
        const openBtn = document.getElementById('create-archive-btn');
        const closeBtns = modal.querySelectorAll('[data-modal-close]');
        const form = document.getElementById('archive-preview-form');
        const errorEl = document.getElementById('archive-preview-error');

        openBtn?.addEventListener('click', () => modal.showModal());
        closeBtns.forEach(btn => btn.addEventListener('click', () => modal.close()));
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.close(); });

        form.addEventListener('submit', (e) => {
            const start = document.getElementById('tanggal_mulai').value;
            const end = document.getElementById('tanggal_akhir').value;

            if (!start || !end) {
                e.preventDefault();
                showError('Tanggal mulai dan akhir wajib diisi.');
                return;
            }

            if (start > end) {
                e.preventDefault();
                showError('Tanggal akhir tidak boleh sebelum tanggal mulai.');
                return;
            }

            const diffDays = (new Date(end) - new Date(start)) / (1000 * 60 * 60 * 24);
            if (diffDays > 366) {
                e.preventDefault();
                showError('Rentang tanggal maksimal 1 tahun.');
                return;
            }
        });

        function showError(msg) {
            errorEl.textContent = msg;
            errorEl.classList.remove('hidden');
        }
    });
</script>
@endpush