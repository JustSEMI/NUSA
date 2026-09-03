@extends('admin.layouts.admin')

@section('title', 'Sesi Chat — Admin NUSA')
@section('page-title', 'Manajemen Sesi Chat')
@section('page-subtitle', 'Lihat dan kelola semua sesi chat pengguna')

@section('content')

{{-- Search & Filter --}}
<div class="bg-white dark:bg-[#202222] rounded-xl border border-gray-200 dark:border-[#2F3030] p-4 mb-4 flex flex-col sm:flex-row gap-3">
    <form action="{{ route('admin.sessions') }}" method="GET" class="flex flex-1 flex-wrap gap-2">
        <div class="relative flex-1 min-w-40">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none"></i>
            <input
                type="text" name="search"
                value="{{ request('search') }}"
                placeholder="Cari judul atau user..."
                class="w-full pl-9 pr-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400"
            >
        </div>
        <select name="user_id" onchange="this.form.submit()"
            class="px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400">
            <option value="">Semua User</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                    {{ $u->name }} ({{ $u->email }})
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn-primary">
            <i data-lucide="search" class="h-4 w-4"></i>
        </button>
        @if(request('search') || request('user_id'))
            <a href="{{ route('admin.sessions') }}" class="btn-secondary">
                <i data-lucide="x" class="h-4 w-4"></i>
                Reset
            </a>
        @endif
    </form>
</div>

{{-- Sessions Table --}}
<div class="bg-white dark:bg-[#202222] rounded-xl border border-gray-200 dark:border-[#2F3030] overflow-hidden animate__animated animate__fadeIn animate__faster">
    @if($sessions->isEmpty())
        <div class="px-6 py-16 text-center">
            <i data-lucide="message-square" class="h-10 w-10 mx-auto text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada sesi chat ditemukan</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="admin-table w-full">
                <thead>
                    <tr>
                        <th class="text-left">#</th>
                        <th class="text-left">Judul Sesi</th>
                        <th class="text-left">User</th>
                        <th class="text-left">Pesan</th>
                        <th class="text-left">Model</th>
                        <th class="text-left">Terakhir Aktif</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sessions as $session)
                        <tr>
                            <td class="text-gray-400 text-xs w-10">{{ $session->id }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    @if($session->is_pinned ?? false)
                                        <i data-lucide="pin" class="h-3.5 w-3.5 text-amber-500 shrink-0"></i>
                                    @else
                                        <i data-lucide="message-square" class="h-3.5 w-3.5 text-gray-300 dark:text-gray-600 shrink-0"></i>
                                    @endif
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate max-w-[180px]">
                                        {{ $session->title ?? 'Untitled' }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if($session->user)
                                    <div class="flex items-center gap-2">
                                        <div class="h-6 w-6 rounded-full bg-gray-800 dark:bg-[#2F3030] text-white text-xs font-bold flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($session->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ $session->user->name }}</p>
                                            <p class="text-[0.65rem] text-gray-400">{{ $session->user->email }}</p>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">(dihapus)</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-user">{{ $session->messages_count }} pesan</span>
                            </td>
                            <td class="text-gray-400 text-xs max-w-[120px] truncate">
                                {{ $session->model ?? '-' }}
                            </td>
                            <td class="text-gray-400 text-xs whitespace-nowrap">
                                {{ $session->updated_at->format('d M Y, H:i') }}
                            </td>
                            <td class="text-right">
                                <form action="{{ route('admin.sessions.delete', $session) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Hapus sesi ini? Semua pesan di dalamnya akan dihapus permanen.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger !py-1.5 !px-2.5">
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($sessions->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-[#2F3030] flex items-center justify-between">
                <p class="text-xs text-gray-400">
                    Menampilkan {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} dari {{ $sessions->total() }} sesi
                </p>
                <div class="flex items-center gap-1">
                    @if($sessions->onFirstPage())
                        <span class="btn-secondary !py-1.5 !px-2.5 opacity-40 cursor-not-allowed">
                            <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                        </span>
                    @else
                        <a href="{{ $sessions->previousPageUrl() }}" class="btn-secondary !py-1.5 !px-2.5">
                            <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                        </a>
                    @endif

                    <span class="text-xs text-gray-500 px-2">{{ $sessions->currentPage() }} / {{ $sessions->lastPage() }}</span>

                    @if($sessions->hasMorePages())
                        <a href="{{ $sessions->nextPageUrl() }}" class="btn-secondary !py-1.5 !px-2.5">
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
