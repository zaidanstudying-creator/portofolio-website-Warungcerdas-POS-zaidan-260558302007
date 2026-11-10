@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Manajemen Akun</h2>
            <p class="text-sm text-slate-500">Kelola akun Admin Toko dan Kasir, termasuk mengatur password baru secara langsung.</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn-primary">+ Tambah Akun</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="th">Nama</th>
                        <th class="th">Email</th>
                        <th class="th">Peran</th>
                        <th class="th">Status</th>
                        <th class="th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/70">
                            <td class="td font-medium text-slate-900">{{ $user->name }}</td>
                            <td class="td text-slate-600">{{ $user->email }}</td>
                            <td class="td">
                                @if ($user->hasSuperAdminMarker())
                                    <span class="badge-slate">Administrator Utama</span>
                                @elseif ($user->isAdmin())
                                    <span class="badge-green">Admin</span>
                                @else
                                    <span class="badge-slate">Kasir</span>
                                @endif
                            </td>
                            <td class="td">
                                @if ($user->isActive())
                                    <span class="badge-green">Aktif</span>
                                @else
                                    <span class="badge-slate">Nonaktif</span>
                                @endif
                            </td>
                            <td class="td">
                                <div class="flex justify-end gap-2">
                                    @unless ($user->hasSuperAdminMarker())
                                    <a href="{{ route('users.edit', $user) }}" class="btn-ghost">Ubah</a>
                                    @endunless
                                    @if (auth()->user()?->isAdmin() && !auth()->user()->is($user) && !$user->hasSuperAdminMarker())
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Yakin ingin menghapus akun ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost text-red-600 hover:bg-red-50">Hapus</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">
                                Belum ada akun pengguna.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
@endsection
