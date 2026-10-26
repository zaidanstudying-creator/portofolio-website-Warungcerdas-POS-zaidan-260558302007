@extends('layouts.app')

@section('title', 'Catat Pengeluaran')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Catat Pengeluaran Baru</h2>
        <p class="text-sm text-slate-500">Masukkan data pengeluaran operasional.</p>
    </div>

    <form method="POST" action="{{ route('finance.pengeluaran.store') }}" class="card p-5 space-y-5" enctype="multipart/form-data">
        @csrf

        <div>
            <label class="label" for="tanggal">Tanggal</label>
            <input type="date" id="tanggal" name="tanggal" class="input" max="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
            @error('tanggal')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="nominal">Nominal (Rp)</label>
            <input type="number" id="nominal" name="nominal" class="input" min="0" step="100" inputmode="numeric" placeholder="0" required>
            @error('nominal')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="kategori_pengeluaran_id">Kategori</label>
            <select id="kategori_pengeluaran_id" name="kategori_pengeluaran_id" class="input" required>
                <option value="">Pilih Kategori</option>
                @foreach ($kategoris as $kategori)
                    <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                @endforeach
            </select>
            @error('kategori_pengeluaran_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="metode_pembayaran">Metode Pembayaran</label>
            <select id="metode_pembayaran" name="metode_pembayaran" class="input" required>
                <option value="cash">Tunai</option>
                <option value="qris">QRIS</option>
                <option value="transfer">Transfer</option>
                <option value="lain">Lainnya</option>
            </select>
        </div>

        <div>
            <label class="label" for="keterangan">Keterangan</label>
            <textarea id="keterangan" name="keterangan" class="input" rows="3" maxlength="1000" placeholder="Deskripsi pengeluaran..."></textarea>
            @error('keterangan')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="bukti">Bukti Struk/Foto (Opsional)</label>
            <input type="file" id="bukti" name="bukti" class="input" accept="image/jpeg,image/png,application/pdf">
            <p class="mt-1 text-xs text-slate-500">Format: JPG, PNG, PDF. Maksimal 2MB.</p>
            @error('bukti')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ route('finance.pengeluaran.index') }}" class="btn-ghost">Batal</a>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection