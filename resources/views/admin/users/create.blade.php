@extends('admin.layouts.admin')

@section('title', 'Tambah User — Admin NUSA')
@section('page-title', 'Tambah User Baru')
@section('page-subtitle', 'Buat akun pengguna baru')

@section('page-actions')
    <a href="{{ route('admin.users') }}" class="btn-secondary">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Kembali
    </a>
@endsection

@section('content')

<div class="max-w-xl animate__animated animate__fadeIn animate__faster">
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

        <form action="{{ route('admin.users.store') }}" method="POST" autocomplete="off">
            @csrf

            {{-- Nama --}}
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Nama <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text" id="name" name="name"
                    value="{{ old('name') }}"
                    required autofocus autocomplete="off"
                    class="w-full rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400 {{ $errors->has('name') ? 'border-rose-400' : '' }}"
                    placeholder="Nama lengkap"
                >
            </div>

            {{-- Email --}}
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Email <span class="text-rose-500">*</span>
                </label>
                <input
                    type="email" id="email" name="email"
                    value="{{ old('email') }}"
                    required autocomplete="off"
                    class="w-full rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400 {{ $errors->has('email') ? 'border-rose-400' : '' }}"
                    placeholder="email@contoh.com"
                >
            </div>

            {{-- Password --}}
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Password <span class="text-rose-500">*</span>
                </label>
                <input
                    type="password" id="password" name="password"
                    required autocomplete="new-password"
                    class="w-full rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400 {{ $errors->has('password') ? 'border-rose-400' : '' }}"
                    placeholder="Minimal 8 karakter"
                >
            </div>

            {{-- Konfirmasi Password --}}
            <div class="mb-5">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Konfirmasi Password <span class="text-rose-500">*</span>
                </label>
                <input
                    type="password" id="password_confirmation" name="password_confirmation"
                    required autocomplete="new-password"
                    class="w-full rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400"
                    placeholder="Ulangi password"
                >
            </div>

            {{-- Admin Toggle --}}
            <div class="mb-6 flex items-center justify-between py-3 px-4 rounded-lg bg-gray-50 dark:bg-[#191A1A] border border-gray-100 dark:border-[#2F3030]">
                <div>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Jadikan Administrator</p>
                    <p class="text-xs text-gray-400">User dapat mengakses Admin Panel</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_admin" value="1" id="isAdminToggle"
                           class="sr-only peer" {{ old('is_admin') ? 'checked' : '' }}>
                    <div class="w-10 h-5 bg-gray-200 dark:bg-[#2F3030] rounded-full peer-checked:bg-gray-900 dark:peer-checked:bg-gray-200 transition-colors"></div>
                    <div class="absolute left-0.5 top-0.5 h-4 w-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                </label>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                    Buat Akun
                </button>
                <a href="{{ route('admin.users') }}" class="btn-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
