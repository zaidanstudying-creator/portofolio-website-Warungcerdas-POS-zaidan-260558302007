@extends('layouts.app')

@section('title', 'Kategori Pengeluaran')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Kategori Pengeluaran</h2>
            <p class="text-sm text-slate-500">Kelola kategori untuk pengelompokan pengeluaran.</p>
        </div>
        <a href="{{ route('finance.kategori.create') }}" class="btn-primary">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
            </svg>
            <span>Tambah Kategori</span>
        </a>
    </div>

    <div class="card p-5">
        <div class="min-w-0 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="th">Nama</th>
                        <th class="th text-center">Status</th>
                        <th class="th text-center">Urutan</th>
                        <th class="th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($kategoris as $kategori)
                    <tr class="hover:bg-slate-50/70">
                        <td class="td font-medium text-slate-900">{{ $kategori->nama }}</td>
                        <td class="td text-center">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium
                                @if ($kategori->is_active)
                                    bg-emerald-50 text-emerald-700
                                @else
                                    bg-slate-50 text-slate-700
                                @endif
                            ">
                                {{ $kategori->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="td text-center text-slate-600">{{ $kategori->sort_order }}</td>
                        <td class="td text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('finance.kategori.edit', $kategori) }}" class="btn-ghost text-xs">Edit</a>
                                <form method="POST" action="{{ route('finance.kategori.destroy', $kategori) }}" onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-ghost text-xs text-red-600 hover:bg-red-50">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">
                            Belum ada kategori pengeluaran.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($kategoris->hasPages())
        <div class="mt-4">
            {{ $kategoris->links() }}
        </div>
        @endif
    </div>
</div>
@endsection