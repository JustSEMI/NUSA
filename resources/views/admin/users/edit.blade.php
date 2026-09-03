@extends('admin.layouts.admin')

@section('title', 'Edit User — Admin NUSA')
@section('page-title', 'Edit User')
@section('page-subtitle', 'Ubah data akun: {{ $user->name }}')

@section('page-actions')
    <a href="{{ route('admin.users') }}" class="btn-secondary">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Kembali
    </a>
@endsection

@section('content')

<div class="max-w-xl space-y-4 animate__animated animate__fadeIn animate__faster">

    {{-- User Info Card --}}
    <div class="bg-white dark:bg-[#202222] rounded-xl border border-gray-200 dark:border-[#2F3030] p-5 flex items-center gap-4">
        <div class="h-12 w-12 rounded-full flex items-center justify-center text-white text-lg font-bold shrink-0
            {{ $user->is_admin ? 'bg-emerald-600' : 'bg-gray-800 dark:bg-[#2F3030]' }}">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                @if($user->is_admin)
                    <span class="badge badge-admin">Admin</span>
                @else
                    <span class="badge badge-user">User</span>
                @endif
            </div>
            <p class="text-sm text-gray-400 mt-0.5">{{ $user->email }}</p>
        </div>
        <div class="text-right text-xs text-gray-400">
            <p>{{ $sessionCount }} sesi</p>
            <p>{{ $messageCount }} pesan</p>
            <p class="mt-1">Bergabung {{ $user->created_at->format('d M Y') }}</p>
        </div>
    </div>

    {{-- Edit Form --}}
    <div class="bg-white dark:bg-[#202222] rounded-xl border border-gray-200 dark:border-[#2F3030] p-6">

        @if($errors->any())
            <div class="alert-error flex flex-col gap-1 mb-6">
                @foreach($errors->all() as $err)
                    <p class="flex items-center gap-2">
                        <i data-lucide="alert-circle" class="h-3.5 w-3.5 shrink-0"></i>
                        {{ $err }}
                    </p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('admin.users.update', $user) }}" method="POST" autocomplete="off">
            @csrf
            @method('PUT')

            {{-- Nama --}}
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Nama <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text" id="name" name="name"
                    value="{{ old('name', $user->name) }}"
                    required autofocus autocomplete="off"
                    class="w-full rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400"
                >
            </div>

            {{-- Email --}}
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Email <span class="text-rose-500">*</span>
                </label>
                <input
                    type="email" id="email" name="email"
                    value="{{ old('email', $user->email) }}"
                    required autocomplete="off"
                    class="w-full rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400"
                >
            </div>

            {{-- Password (opsional) --}}
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Password Baru
                    <span class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span>
                </label>
                <input
                    type="password" id="password" name="password"
                    autocomplete="new-password"
                    class="w-full rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400"
                    placeholder="Minimal 8 karakter"
                >
            </div>

            {{-- Konfirmasi Password --}}
            <div class="mb-5">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Konfirmasi Password Baru
                </label>
                <input
                    type="password" id="password_confirmation" name="password_confirmation"
                    autocomplete="new-password"
                    class="w-full rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400"
                    placeholder="Ulangi password baru"
                >
            </div>

            {{-- Admin Toggle --}}
            <div class="mb-6 flex items-center justify-between py-3 px-4 rounded-lg bg-gray-50 dark:bg-[#191A1A] border border-gray-100 dark:border-[#2F3030]
                {{ $user->id === auth()->id() ? 'opacity-60' : '' }}">
                <div>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Status Administrator</p>
                    <p class="text-xs text-gray-400">
                        {{ $user->id === auth()->id() ? 'Tidak dapat mengubah status diri sendiri' : 'User dapat mengakses Admin Panel' }}
                    </p>
                </div>
                <label class="relative inline-flex items-center {{ $user->id === auth()->id() ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                    <input type="checkbox" name="is_admin" value="1" id="isAdminToggle"
                           class="sr-only peer"
                           {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}
                           {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                    <div class="w-10 h-5 bg-gray-200 dark:bg-[#2F3030] rounded-full peer-checked:bg-gray-900 dark:peer-checked:bg-gray-200 transition-colors"></div>
                    <div class="absolute left-0.5 top-0.5 h-4 w-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                </label>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Simpan Perubahan
                </button>
                <a href="{{ route('admin.users') }}" class="btn-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>

    {{-- Danger Zone --}}
    @if($user->id !== auth()->id())
        <div class="bg-white dark:bg-[#202222] rounded-xl border border-rose-200 dark:border-rose-900/40 p-5">
            <h3 class="text-sm font-semibold text-rose-600 dark:text-rose-400 mb-1 flex items-center gap-2">
                <i data-lucide="alert-triangle" class="h-4 w-4"></i>
                Danger Zone
            </h3>
            <p class="text-xs text-gray-400 mb-3">
                Menghapus akun ini akan menghapus semua sesi chat ({{ $sessionCount }} sesi, {{ $messageCount }} pesan) secara permanen.
            </p>
            <form action="{{ route('admin.users.delete', $user) }}" method="POST"
                  onsubmit="return confirm('Hapus akun {{ addslashes($user->name) }}? Tindakan ini tidak dapat dibatalkan.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="btn-danger flex items-center gap-2 px-4 py-2">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                    Hapus Akun Ini
                </button>
            </form>
        </div>
    @endif

</div>

@endsection
