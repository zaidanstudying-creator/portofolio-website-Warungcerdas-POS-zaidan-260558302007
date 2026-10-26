@extends('layouts.app')

@section('title', 'Promo')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Daftar Promo</h2>
            <p class="text-sm text-slate-500">Kelola potongan harga yang bisa dipakai kasir saat checkout.</p>
        </div>
        <a href="{{ route('promos.create') }}" class="btn-primary">+ Tambah Promo</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="th">Kode</th>
                        <th class="th">Nama</th>
                        <th class="th">Diskon</th>
                        <th class="th">Cakupan</th>
                        <th class="th">Periode</th>
                        <th class="th">Kuota</th>
                        <th class="th">Status</th>
                        <th class="th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($promos as $promo)
                        @php
                            $now = now();
                            $isExpired = $promo->ends_at && $promo->ends_at->lt($now);
                            $isScheduled = $promo->starts_at && $promo->starts_at->gt($now);
                            $isExhausted = $promo->usage_limit !== null && $promo->usage_count >= $promo->usage_limit;
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="td font-mono font-semibold text-slate-900">{{ $promo->code }}</td>
                            <td class="td">
                                <p class="font-medium text-slate-900">{{ $promo->name }}</p>
                                @if ((float) $promo->min_subtotal > 0)
                                    <p class="text-xs text-slate-500">Min. <x-money :value="$promo->min_subtotal" /></p>
                                @endif
                            </td>
                            <td class="td text-slate-700">
                                @if ($promo->type === 'percentage')
                                    {{ rtrim(rtrim(number_format((float) $promo->value, 2, ',', '.'), '0'), ',') }}%
                                @else
                                    <x-money :value="$promo->value" />
                                @endif
                                @if ((float) $promo->max_discount > 0)
                                    <p class="text-xs text-slate-500">Maks. <x-money :value="$promo->max_discount" /></p>
                                @endif
                            </td>
                            <td class="td text-slate-600">
                                @if ($promo->scope === 'product')
                                    Produk: {{ $promo->product?->name ?? '—' }}
                                @elseif ($promo->scope === 'category')
                                    Kategori: {{ $promo->category?->name ?? '—' }}
                                @else
                                    Semua item
                                @endif
                            </td>
                            <td class="td text-xs text-slate-600">
                                <p>{{ $promo->starts_at?->timezone(\App\Services\OrderService::LOCAL_TIMEZONE)->format('d/m/Y H:i') ?? '—' }}</p>
                                <p class="text-slate-400">s.d. {{ $promo->ends_at?->timezone(\App\Services\OrderService::LOCAL_TIMEZONE)->format('d/m/Y H:i') ?? '—' }}</p>
                            </td>
                            <td class="td text-slate-600">
                                {{ $promo->usage_count }}{{ $promo->usage_limit !== null ? ' / '.$promo->usage_limit : ' / ∞' }}
                            </td>
                            <td class="td">
                                @if (! $promo->is_active)
                                    <span class="badge-slate">Nonaktif</span>
                                @elseif ($isExpired)
                                    <span class="badge-slate">Berakhir</span>
                                @elseif ($isScheduled)
                                    <span class="badge-slate">Terjadwal</span>
                                @elseif ($isExhausted)
                                    <span class="badge-slate">Habis</span>
                                @else
                                    <span class="badge-green">Aktif</span>
                                @endif
                            </td>
                            <td class="td">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('promos.edit', $promo) }}" class="btn-ghost">Ubah</a>
                                    <form
                                        method="POST"
                                        action="{{ route('promos.destroy', $promo) }}"
                                        onsubmit="return confirm('Hapus promo ini? Riwayat transaksi lama tetap tersimpan.')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">
                                Belum ada promo. Tambahkan promo pertama Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($promos->hasPages())
        <div class="mt-4">
            {{ $promos->links() }}
        </div>
    @endif
@endsection
