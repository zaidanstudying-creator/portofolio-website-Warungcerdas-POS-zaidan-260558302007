@extends('layouts.app')

@section('title', isset($promo) ? 'Ubah Promo' : 'Tambah Promo')

@section('content')
    @php
        $defaultStart = now(\App\Services\OrderService::LOCAL_TIMEZONE)->format('Y-m-d\TH:i');
        $defaultEnd = now(\App\Services\OrderService::LOCAL_TIMEZONE)->addDays(30)->endOfDay()->format('Y-m-d\TH:i');
        $startValue = old('starts_at', isset($promo) ? $promo->starts_at?->timezone(\App\Services\OrderService::LOCAL_TIMEZONE)->format('Y-m-d\TH:i') : $defaultStart);
        $endValue = old('ends_at', isset($promo) ? $promo->ends_at?->timezone(\App\Services\OrderService::LOCAL_TIMEZONE)->format('Y-m-d\TH:i') : $defaultEnd);
    @endphp

    <form
        method="POST"
        action="{{ isset($promo) ? route('promos.update', $promo) : route('promos.store') }}"
        class="card max-w-3xl space-y-5 p-6"
    >
        @csrf
        @isset($promo)
            @method('PUT')
        @endisset

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="code" class="label">Kode promo</label>
                <input
                    id="code"
                    type="text"
                    name="code"
                    value="{{ old('code', $promo->code ?? '') }}"
                    class="input font-mono uppercase"
                    placeholder="Contoh: PROMO10"
                    maxlength="50"
                    autocomplete="off"
                    required
                >
                <p class="mt-1.5 text-xs text-slate-500">Kode unik yang diketik kasir saat checkout. Huruf otomatis menjadi kapital.</p>
                @error('code')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="name" class="label">Nama promo</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $promo->name ?? '') }}"
                    class="input"
                    placeholder="Contoh: Diskon Akhir Pekan"
                    maxlength="255"
                    required
                >
                @error('name')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="type" class="label">Jenis potongan</label>
                <select id="type" name="type" class="input" required>
                    <option value="percentage" @selected(old('type', $promo->type ?? 'percentage') === 'percentage')>Persentase (%)</option>
                    <option value="fixed" @selected(old('type', $promo->type ?? '') === 'fixed')>Nominal (Rp)</option>
                </select>
                @error('type')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="value" class="label">Nilai potongan</label>
                <input
                    id="value"
                    type="number"
                    name="value"
                    value="{{ old('value', $promo->value ?? '') }}"
                    class="input"
                    min="0.01"
                    step="0.01"
                    inputmode="decimal"
                    required
                >
                <p id="value-help" class="mt-1.5 text-xs text-slate-500">Persentase 0,01–100. Contoh: 10 untuk diskon 10%.</p>
                @error('value')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="min_subtotal" class="label">Minimal belanja</label>
                <div class="flex">
                    <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3.5 text-sm font-medium text-slate-500">Rp</span>
                    <input
                        id="min_subtotal"
                        type="number"
                        name="min_subtotal"
                        value="{{ old('min_subtotal', $promo->min_subtotal ?? '') }}"
                        class="input rounded-l-none"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        placeholder="0"
                    >
                </div>
                <p class="mt-1.5 text-xs text-slate-500">Kosongkan atau isi 0 jika tanpa minimal belanja.</p>
                @error('min_subtotal')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="max_discount" class="label">Maksimal potongan</label>
                <div class="flex">
                    <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3.5 text-sm font-medium text-slate-500">Rp</span>
                    <input
                        id="max_discount"
                        type="number"
                        name="max_discount"
                        value="{{ old('max_discount', $promo->max_discount ?? '') }}"
                        class="input rounded-l-none"
                        min="0.01"
                        step="0.01"
                        inputmode="decimal"
                        placeholder="Contoh: 20000"
                    >
                </div>
                <p class="mt-1.5 text-xs text-slate-500">Opsional, berguna untuk promo persentase.</p>
                @error('max_discount')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="scope" class="label">Cakupan promo</label>
            <select id="scope" name="scope" class="input" required>
                <option value="global" @selected(old('scope', $promo->scope ?? 'global') === 'global')>Semua item</option>
                <option value="product" @selected(old('scope', $promo->scope ?? '') === 'product')>Produk tertentu</option>
                <option value="category" @selected(old('scope', $promo->scope ?? '') === 'category')>Kategori tertentu</option>
            </select>
            @error('scope')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div data-scope-panel="product" @if (old('scope', $promo->scope ?? 'global') !== 'product') hidden @endif>
            <label for="product_id" class="label">Produk</label>
            <select id="product_id" name="product_id" class="input">
                <option value="" disabled @selected(! old('product_id', $promo->product_id ?? null))>Pilih produk</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected((int) old('product_id', $promo->product_id ?? 0) === $product->id)>
                        {{ $product->name }}{{ $product->category ? ' — '.$product->category->name : '' }}@unless ($product->is_available) (Nonaktif)@endunless
                    </option>
                @endforeach
            </select>
            @error('product_id')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div data-scope-panel="category" @if (old('scope', $promo->scope ?? 'global') !== 'category') hidden @endif>
            <label for="category_id" class="label">Kategori</label>
            <select id="category_id" name="category_id" class="input">
                <option value="" disabled @selected(! old('category_id', $promo->category_id ?? null))>Pilih kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) old('category_id', $promo->category_id ?? 0) === $category->id)>
                        {{ $category->name }}@unless ($category->is_active) (Nonaktif)@endunless
                    </option>
                @endforeach
            </select>
            @error('category_id')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="starts_at" class="label">Mulai berlaku (WIB)</label>
                <input
                    id="starts_at"
                    type="datetime-local"
                    name="starts_at"
                    value="{{ $startValue }}"
                    class="input"
                    required
                >
                @error('starts_at')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="ends_at" class="label">Berakhir (WIB)</label>
                <input
                    id="ends_at"
                    type="datetime-local"
                    name="ends_at"
                    value="{{ $endValue }}"
                    class="input"
                    required
                >
                @error('ends_at')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="usage_limit" class="label">Batas penggunaan</label>
            <input
                id="usage_limit"
                type="number"
                name="usage_limit"
                value="{{ old('usage_limit', $promo->usage_limit ?? '') }}"
                class="input"
                min="1"
                step="1"
                inputmode="numeric"
                placeholder="Kosongkan untuk tanpa batas"
            >
            <p class="mt-1.5 text-xs text-slate-500">Jumlah maksimal promo bisa dipakai. Kosongkan untuk tanpa batas.</p>
            @error('usage_limit')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <x-checkbox
            name="is_active"
            label="Promo aktif"
            :checked="old('is_active', $promo->is_active ?? true)"
        />

        <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">Simpan</button>
            <a href="{{ route('promos.index') }}" class="btn-ghost">Batal</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        const scopeSelect = document.getElementById('scope');
        const toggleScopePanels = () => {
            document.querySelectorAll('[data-scope-panel]').forEach((panel) => {
                const active = panel.dataset.scopePanel === scopeSelect.value;
                panel.hidden = !active;
                panel.querySelectorAll('select').forEach((select) => {
                    select.disabled = !active;
                });
            });
        };

        scopeSelect.addEventListener('change', toggleScopePanels);
        toggleScopePanels();

        const typeSelect = document.getElementById('type');
        const valueHelp = document.getElementById('value-help');
        const updateValueHelp = () => {
            valueHelp.textContent = typeSelect.value === 'percentage'
                ? 'Persentase 0,01–100. Contoh: 10 untuk diskon 10%.'
                : 'Nominal potongan dalam rupiah. Contoh: 5000 untuk potongan Rp 5.000.';
        };

        typeSelect.addEventListener('change', updateValueHelp);
        updateValueHelp();
    </script>
@endpush
