@extends('layouts.app')

@section('title', 'Pengeluaran')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Pengeluaran</h2>
            <p class="text-sm text-slate-500">Catatan pengeluaran operasional.</p>
        </div>
        <a href="{{ route('finance.pengeluaran.create') }}" class="btn-primary">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
            </svg>
            <span>Catat Pengeluaran</span>
        </a>
    </div>

    {{-- Filter Form --}}
    <div class="card p-5">
        <form method="GET" action="{{ route('finance.pengeluaran.index') }}" class="flex flex-wrap items-end gap-3">
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
                <label class="label" for="kategori_id">Kategori</label>
                <select id="kategori_id" name="kategori_id" class="input">
                    <option value="">Semua Kategori</option>
                    @foreach ($kategoris as $kategori)
                        <option value="{{ $kategori->id }}" {{ $kategoriId == $kategori->id ? 'selected' : '' }}>{{ $kategori->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-0 basis-full sm:basis-44 sm:grow">
                <button type="submit" class="btn-primary w-full sm:w-auto">Filter</button>
            </div>

            @if (request()->filled('kategori_id') || $period === 'custom')
                <a href="{{ route('finance.pengeluaran.index') }}" class="btn-ghost text-xs">Reset</a>
            @endif
        </form>
    </div>

    {{-- Total --}}
    <div class="card p-4 bg-emerald-50 border-emerald-200">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-emerald-800">Total Pengeluaran Periode</span>
            <span class="text-2xl font-bold text-emerald-700"><x-money :value="$total" /></span>
        </div>
    </div>

    <div class="card p-5">
        <div class="min-w-0 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="th">Tanggal</th>
                        <th class="th">Kategori</th>
                        <th class="th text-right">Nominal</th>
                        <th class="th">Metode</th>
                        <th class="th">Keterangan</th>
                        <th class="th">Pencatat</th>
                        <th class="th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pengeluarans as $pengeluaran)
                    <tr class="hover:bg-slate-50/70">
                        <td class="td text-slate-600">{{ $pengeluaran->tanggal->format('d/m/Y') }}</td>
                        <td class="td font-medium text-slate-900">{{ $pengeluaran->kategori->nama }}</td>
                        <td class="td text-right font-medium text-red-600"><x-money :value="$pengeluaran->nominal" /></td>
                        <td class="td">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium
                                @if ($pengeluaran->metode_pembayaran === 'cash')
                                    bg-emerald-50 text-emerald-700
                                @elseif ($pengeluaran->metode_pembayaran === 'qris')
                                    bg-blue-50 text-blue-700
                                @elseif ($pengeluaran->metode_pembayaran === 'transfer')
                                    bg-indigo-50 text-indigo-700
                                @else
                                    bg-slate-50 text-slate-700
                                @endif
                            ">
                                {{ ucfirst($pengeluaran->metode_pembayaran) }}
                            </span>
                        </td>
                        <td class="td text-slate-600 max-w-xs truncate">{{ $pengeluaran->keterangan ?? '-' }}</td>
                        <td class="td text-slate-600">{{ $pengeluaran->user->name ?? '-' }}</td>
                        <td class="td text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('finance.pengeluaran.edit', $pengeluaran) }}" class="btn-ghost text-xs">Edit</a>
                                <form method="POST" action="{{ route('finance.pengeluaran.destroy', $pengeluaran) }}" onsubmit="return confirm('Yakin ingin menghapus pengeluaran ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-ghost text-xs text-red-600 hover:bg-red-50">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">
                            Belum ada pengeluaran pada filter ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pengeluarans->hasPages())
        <div class="mt-4">
            {{ $pengeluarans->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleCustomDates() {
        const period = document.getElementById('period').value;
        const show = period === 'custom';
        document.getElementById('start-date-group').style.display = show ? 'block' : 'none';
        document.getElementById('end-date-group').style.display = show ? 'block' : 'none';
    }

    document.addEventListener('DOMContentLoaded', toggleCustomDates);
</script>
@endpush