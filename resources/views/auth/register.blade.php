@extends('layouts.app')

@section('title', 'Daftar Akun - NUSA')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold tracking-widest text-gray-900">NUSA</h1>
            <p class="mt-2 text-sm text-gray-500">AI Assistant</p>
        </div>

        {{-- Register Card --}}
        <div class="rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Buat Akun Baru</h2>
            <p class="text-xs text-gray-500 mb-6">Mulai ngobrol dengan asisten AI-mu sendiri</p>

            @if ($errors->any())
                <div class="mb-6 rounded-md bg-rose-50 border border-rose-200 px-4 py-3">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-rose-700">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('register.submit') }}" method="POST" autocomplete="off">
                @csrf

                {{-- Nama --}}
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-900 mb-2">
                        Nama Lengkap
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="off"
                        class="w-full rounded-md border border-gray-300 bg-white px-4 py-3 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                        placeholder="John Doe"
                    >
                </div>

                {{-- Email --}}
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-900 mb-2">
                        Email
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="off"
                        class="w-full rounded-md border border-gray-300 bg-white px-4 py-3 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                        placeholder="nama@email.com"
                    >
                </div>

                {{-- Password --}}
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-900 mb-2">
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-md border border-gray-300 bg-white px-4 py-3 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                        placeholder="Minimal 8 karakter"
                    >
                </div>

                {{-- Konfirmasi Password --}}
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-900 mb-2">
                        Konfirmasi Password
                    </label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-md border border-gray-300 bg-white px-4 py-3 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                        placeholder="Ulangi password"
                    >
                </div>

                {{-- Submit Button --}}
                <button
                    type="submit"
                    class="w-full rounded-md bg-black px-4 py-3 text-sm font-medium text-white transition hover:bg-gray-800"
                >
                    Daftar Sekarang
                </button>
            </form>

            {{-- Switch to Login --}}
            <div class="mt-6 text-center text-sm text-gray-600">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-medium text-black hover:underline">
                    Masuk di sini
                </a>
            </div>
        </div>

        {{-- Footer --}}
        <p class="mt-8 text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} NUSA. AI Assistant.
        </p>
    </div>
</div>
@endsection
