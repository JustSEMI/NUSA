@extends('admin.layouts.admin')

@section('title', 'Manajemen User — Admin NUSA')
@section('page-title', 'Manajemen User')
@section('page-subtitle', 'Kelola semua akun pengguna')

@section('page-actions')
    <a href="{{ route('admin.users.create') }}" class="btn-primary">
        <i data-lucide="user-plus" class="h-4 w-4"></i>
        Tambah User
    </a>
@endsection

@section('content')

{{-- Search & Filter --}}
<div class="bg-white dark:bg-[#202222] rounded-xl border border-gray-200 dark:border-[#2F3030] p-4 mb-4 flex flex-col sm:flex-row gap-3">
    <form action="{{ route('admin.users') }}" method="GET" class="flex flex-1 gap-2">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none"></i>
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama atau email..."
                class="w-full pl-9 pr-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400"
            >
        </div>
        <select name="filter" onchange="this.form.submit()"
            class="px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400">
            <option value="" {{ !request('filter') ? 'selected' : '' }}>Semua</option>
            <option value="admin" {{ request('filter') === 'admin' ? 'selected' : '' }}>Admin saja</option>
        </select>
        <button type="submit" class="btn-primary">
            <i data-lucide="search" class="h-4 w-4"></i>
        </button>
        @if(request('search') || request('filter'))
            <a href="{{ route('admin.users') }}" class="btn-secondary">
                <i data-lucide="x" class="h-4 w-4"></i>
                Reset
            </a>
        @endif
    </form>
</div>

{{-- Users Table --}}
<div class="bg-white dark:bg-[#202222] rounded-xl border border-gray-200 dark:border-[#2F3030] overflow-hidden animate__animated animate__fadeIn animate__faster">
    @if($users->isEmpty())
        <div class="px-6 py-16 text-center">
            <i data-lucide="users" class="h-10 w-10 mx-auto text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada user ditemukan</p>
            @if(request('search'))
                <p class="text-xs text-gray-400 mt-1">Coba kata kunci lain</p>
            @endif
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="admin-table w-full">
                <thead>
                    <tr>
                        <th class="text-left">#</th>
                        <th class="text-left">User</th>
                        <th class="text-left">Email</th>
                        <th class="text-left">Status</th>
                        <th class="text-left">Sesi</th>
                        <th class="text-left">Bergabung</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td class="text-gray-400 text-xs w-10">{{ $user->id }}</td>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0
                                        {{ $user->is_admin ? 'bg-emerald-600' : 'bg-gray-800 dark:bg-[#2F3030]' }}">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100 text-sm">{{ $user->name }}</p>
                                        @if($user->id === auth()->id())
                                            <p class="text-[0.65rem] text-gray-400">(Anda)</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-gray-500 dark:text-gray-400 text-sm">{{ $user->email }}</td>
                            <td>
                                @if($user->is_admin)
                                    <span class="badge badge-admin">Admin</span>
                                @else
                                    <span class="badge badge-user">User</span>
                                @endif
                            </td>
                            <td class="text-gray-500 dark:text-gray-400 text-sm">{{ $user->chat_sessions_count }}</td>
                            <td class="text-gray-400 text-xs">{{ $user->created_at->format('d M Y') }}</td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- Edit --}}
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="btn-secondary !py-1.5 !px-2.5 !text-xs">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Edit
                                    </a>

                                    {{-- Toggle Admin --}}
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.toggle-admin', $user) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="btn-secondary !py-1.5 !px-2.5 !text-xs {{ $user->is_admin ? 'text-amber-600 dark:text-amber-400' : '' }}"
                                                title="{{ $user->is_admin ? 'Cabut Admin' : 'Jadikan Admin' }}">
                                                <i data-lucide="{{ $user->is_admin ? 'shield-off' : 'shield-check' }}" class="h-3.5 w-3.5"></i>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Delete --}}
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.delete', $user) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Hapus akun {{ addslashes($user->name) }}? Semua data termasuk sesi chat akan dihapus permanen.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger !py-1.5 !px-2.5">
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-[#2F3030] flex items-center justify-between">
                <p class="text-xs text-gray-400">
                    Menampilkan {{ $users->firstItem() }}–{{ $users->lastItem() }} dari {{ $users->total() }} user
                </p>
                <div class="flex items-center gap-1">
                    @if($users->onFirstPage())
                        <span class="btn-secondary !py-1.5 !px-2.5 opacity-40 cursor-not-allowed">
                            <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                        </span>
                    @else
                        <a href="{{ $users->previousPageUrl() }}" class="btn-secondary !py-1.5 !px-2.5">
                            <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                        </a>
                    @endif

                    <span class="text-xs text-gray-500 px-2">{{ $users->currentPage() }} / {{ $users->lastPage() }}</span>

                    @if($users->hasMorePages())
                        <a href="{{ $users->nextPageUrl() }}" class="btn-secondary !py-1.5 !px-2.5">
                            <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                        </a>
                    @else
                        <span class="btn-secondary !py-1.5 !px-2.5 opacity-40 cursor-not-allowed">
                            <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                        </span>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>

@endsection
