@extends('layouts.app')

@section('title', 'Ubah Password')

@section('content')
    <form method="POST" action="{{ route('account.password.update') }}" class="card max-w-2xl space-y-5 p-6">
        @csrf
        @method('PUT')

        <div>
            <label for="current_password" class="label">Password saat ini</label>
            <input id="current_password" type="password" name="current_password" class="input" autocomplete="current-password" required>
            @error('current_password')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="label">Password baru</label>
            <input id="password" type="password" name="password" class="input" autocomplete="new-password" required>
            <p class="mt-1 text-sm text-slate-500">Minimal 8 karakter, mengandung huruf dan angka.</p>
            @error('password')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="label">Konfirmasi password baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="input" autocomplete="new-password" required>
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">Ubah Password</button>
            @if (auth()->user()?->isSuperAdmin())
                <a href="{{ route('users.index') }}" class="btn-ghost">Kembali</a>
            @endif
        </div>
    </form>
@endsection
