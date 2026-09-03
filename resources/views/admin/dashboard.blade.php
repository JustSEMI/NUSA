@extends('admin.layouts.admin')

@section('title', 'Dashboard — Admin NUSA')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Ringkasan statistik aplikasi NUSA')

@section('content')

{{-- Stat Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">

    {{-- Total User --}}
    <div class="stat-card animate__animated animate__fadeInUp animate__faster">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-widest font-semibold mb-1">Total User</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_users']) }}</p>
                <p class="text-xs text-gray-400 mt-1">+{{ $stats['new_users_today'] }} hari ini</p>
            </div>
            <div class="h-10 w-10 rounded-lg bg-gray-100 dark:bg-[#2F3030] flex items-center justify-center">
                <i data-lucide="users" class="h-5 w-5 text-gray-600 dark:text-gray-300"></i>
            </div>
        </div>
    </div>

    {{-- Admin User --}}
    <div class="stat-card animate__animated animate__fadeInUp animate__faster" style="animation-delay:0.05s">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-widest font-semibold mb-1">Administrator</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['admin_users']) }}</p>
                <p class="text-xs text-gray-400 mt-1">dari {{ $stats['total_users'] }} user</p>
            </div>
            <div class="h-10 w-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                <i data-lucide="shield-check" class="h-5 w-5 text-emerald-600 dark:text-emerald-400"></i>
            </div>
        </div>
    </div>

    {{-- Total Sesi --}}
    <div class="stat-card animate__animated animate__fadeInUp animate__faster" style="animation-delay:0.1s">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-widest font-semibold mb-1">Total Sesi Chat</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_sessions']) }}</p>
                <p class="text-xs text-gray-400 mt-1">+{{ $stats['active_sessions_today'] }} aktif hari ini</p>
            </div>
            <div class="h-10 w-10 rounded-lg bg-gray-100 dark:bg-[#2F3030] flex items-center justify-center">
                <i data-lucide="message-square" class="h-5 w-5 text-gray-600 dark:text-gray-300"></i>
            </div>
        </div>
    </div>

    {{-- Total Pesan --}}
    <div class="stat-card animate__animated animate__fadeInUp animate__faster" style="animation-delay:0.15s">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-widest font-semibold mb-1">Total Pesan</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_messages']) }}</p>
                <p class="text-xs text-gray-400 mt-1">Semua sesi</p>
            </div>
            <div class="h-10 w-10 rounded-lg bg-gray-100 dark:bg-[#2F3030] flex items-center justify-center">
                <i data-lucide="messages-square" class="h-5 w-5 text-gray-600 dark:text-gray-300"></i>
            </div>
        </div>
    </div>

    {{-- Avg Pesan per Sesi --}}
    <div class="stat-card animate__animated animate__fadeInUp animate__faster" style="animation-delay:0.2s">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-widest font-semibold mb-1">Rata-rata Pesan</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $stats['total_sessions'] > 0 ? number_format($stats['total_messages'] / $stats['total_sessions'], 1) : 0 }}
                </p>
                <p class="text-xs text-gray-400 mt-1">Pesan per sesi</p>
            </div>
            <div class="h-10 w-10 rounded-lg bg-gray-100 dark:bg-[#2F3030] flex items-center justify-center">
                <i data-lucide="bar-chart-2" class="h-5 w-5 text-gray-600 dark:text-gray-300"></i>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="stat-card animate__animated animate__fadeInUp animate__faster flex flex-col justify-between" style="animation-delay:0.25s">
        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-widest font-semibold mb-3">Aksi Cepat</p>
        <div class="space-y-2">
            <a href="{{ route('admin.users.create') }}" class="btn-primary w-full justify-center text-center">
                <i data-lucide="user-plus" class="h-3.5 w-3.5"></i>
                Tambah User
            </a>
            <a href="{{ route('admin.users') }}" class="btn-secondary w-full justify-center text-center">
                <i data-lucide="users" class="h-3.5 w-3.5"></i>
                Kelola User
            </a>
        </div>
    </div>

</div>

{{-- Recent Users --}}
<div class="bg-white dark:bg-[#202222] rounded-xl border border-gray-200 dark:border-[#2F3030] overflow-hidden animate__animated animate__fadeIn animate__faster" style="animation-delay:0.3s">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-[#2F3030] flex items-center justify-between">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">User Terbaru</h2>
        <a href="{{ route('admin.users') }}" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 flex items-center gap-1 transition">
            Lihat semua <i data-lucide="arrow-right" class="h-3 w-3"></i>
        </a>
    </div>

    @if($recent_users->isEmpty())
        <div class="px-6 py-10 text-center">
            <i data-lucide="users" class="h-8 w-8 mx-auto text-gray-300 dark:text-gray-600 mb-2"></i>
            <p class="text-sm text-gray-400">Belum ada user</p>
        </div>
    @else
        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th class="text-left">User</th>
                    <th class="text-left">Email</th>
                    <th class="text-left">Status</th>
                    <th class="text-left">Bergabung</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-[#2F3030]">
                @foreach($recent_users as $user)
                    <tr>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <div class="h-7 w-7 rounded-full bg-gray-900 dark:bg-[#2F3030] text-white text-xs font-bold flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="text-gray-500 dark:text-gray-400">{{ $user->email }}</td>
                        <td>
                            @if($user->is_admin)
                                <span class="badge badge-admin">Admin</span>
                            @else
                                <span class="badge badge-user">User</span>
                            @endif
                        </td>
                        <td class="text-gray-400 text-xs">{{ $user->created_at->format('d M Y') }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
                                Edit
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@endsection
