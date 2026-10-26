@extends('layouts.app')

@section('title', 'Edit Pengeluaran')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Edit Pengeluaran</h2>
        <p class="text-sm text-slate-500">Perbarui data pengeluaran.</p>
    </div>

    <form method="POST" action="{{ route('finance.pengeluaran.update', $pengeluaran) }}" class="card p-5 space-y-5" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div>
            <label class="label" for="tanggal">Tanggal</label>
            <input type="date" id="tanggal" name="tanggal" class="input" max="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" value="{{ $pengeluaran->tanggal->format('Y-m-d') }}" required>
            @error('tanggal')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="nominal">Nominal (Rp)</label>
            <input type="number" id="nominal" name="nominal" class="input" min="0" step="100" inputmode="numeric" value="{{ $pengeluaran->nominal }}" required>
            @error('nominal')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="kategori_pengeluaran_id">Kategori</label>
            <select id="kategori_pengeluaran_id" name="kategori_pengeluaran_id" class="input" required>
                <option value="">Pilih Kategori</option>
                @foreach ($kategoris as $kategori)
                    <option value="{{ $kategori->id }}" {{ $pengeluaran->kategori_pengeluaran_id == $kategori->id ? 'selected' : '' }}>{{ $kategori->nama }}</option>
                @endforeach
            </select>
            @error('kategori_pengeluaran_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="metode_pembayaran">Metode Pembayaran</label>
            <select id="metode_pembayaran" name="metode_pembayaran" class="input" required>
                <option value="cash" {{ $pengeluaran->metode_pembayaran === 'cash' ? 'selected' : '' }}>Tunai</option>
                <option value="qris" {{ $pengeluaran->metode_pembayaran === 'qris' ? 'selected' : '' }}>QRIS</option>
                <option value="transfer" {{ $pengeluaran->metode_pembayaran === 'transfer' ? 'selected' : '' }}>Transfer</option>
                <option value="lain" {{ $pengeluaran->metode_pembayaran === 'lain' ? 'selected' : '' }}>Lainnya</option>
            </select>
        </div>

        <div>
            <label class="label" for="keterangan">Keterangan</label>
            <textarea id="keterangan" name="keterangan" class="input" rows="3" maxlength="1000" placeholder="Deskripsi pengeluaran...">{{ $pengeluaran->keterangan }}</textarea>
            @error('keterangan')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label">Bukti Struk/Foto</label>
            @if ($pengeluaran->bukti_path)
                <div class="mb-2">
                    @if (in_array(pathinfo($pengeluaran->bukti_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png']))
                        <img src="{{ Storage::disk('public')->url($pengeluaran->bukti_path) }}" alt="Bukti" class="max-w-xs rounded-lg border border-slate-200">
                    @else
                        <a href="{{ Storage::disk('public')->url($pengeluaran->bukti_path) }}" target="_blank" class="text-emerald-600 hover:underline">Lihat file</a>
                    @endif
                </div>
            @else
                <p class="text-sm text-slate-500 mb-2">Belum ada bukti.</p>
            @endif
            <input type="file" id="bukti" name="bukti" class="input" accept="image/jpeg,image/png,application/pdf">
            <p class="mt-1 text-xs text-slate-500">Format: JPG, PNG, PDF. Maksimal 2MB. Kosongkan untuk mempertahankan file lama.</p>
            @error('bukti')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <div class="mt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remove_bukti" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500/40">
                    <span class="text-sm text-slate-600">Hapus bukti yang ada</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ route('finance.pengeluaran.index') }}" class="btn-ghost">Batal</a>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection