@extends('layouts.app')

@section('title', 'Tambah Kategori Pengeluaran')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Tambah Kategori Pengeluaran</h2>
        <p class="text-sm text-slate-500">Buat kategori baru untuk pengelompokan pengeluaran.</p>
    </div>

    <form method="POST" action="{{ route('finance.kategori.store') }}" class="card p-5 space-y-5">
        @csrf

        <div>
            <label class="label" for="nama">Nama Kategori</label>
            <input type="text" id="nama" name="nama" class="input" maxlength="100" required autofocus>
            @error('nama')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="is_active">Status</label>
                <select id="is_active" name="is_active" class="input">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>

            <div>
                <label class="label" for="sort_order">Urutan Tampil</label>
                <input type="number" id="sort_order" name="sort_order" class="input" min="0" value="0">
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ route('finance.kategori.index') }}" class="btn-ghost">Batal</a>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection