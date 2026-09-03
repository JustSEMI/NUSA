@extends('layouts.app')

@section('title', 'Masuk - NUSA')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold tracking-widest text-gray-900">NUSA</h1>
            <p class="mt-2 text-sm text-gray-500">AI Assistant</p>
        </div>

        {{-- Login Card --}}
        <div class="rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Masuk ke Akun</h2>

            @if (session('error'))
                <div class="mb-6 rounded-md bg-rose-50 border border-rose-200 px-4 py-3">
                    <p class="text-sm text-rose-700">{{ session('error') }}</p>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-6 rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3">
                    <p class="text-sm text-emerald-700">{{ session('success') }}</p>
                </div>
            @endif

            @if ($errors->any() && !session('error'))
                <div class="mb-6 rounded-md bg-rose-50 border border-rose-200 px-4 py-3">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-rose-700">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST" autocomplete="off">
                @csrf

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
                        autofocus
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
                        autocomplete="current-password"
                        class="w-full rounded-md border border-gray-300 bg-white px-4 py-3 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                        placeholder="••••••••"
                    >
                </div>

                {{-- Remember Me --}}
                <div class="mb-6">
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            name="remember"
                            class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                        >
                        <span class="ml-2 text-sm text-gray-600">Ingat saya</span>
                    </label>
                </div>

                {{-- Submit Button --}}
                <button
                    type="submit"
                    class="w-full rounded-md bg-black px-4 py-3 text-sm font-medium text-white transition hover:bg-gray-800"
                >
                    Masuk
                </button>
            </form>


        </div>

        {{-- Footer --}}
        <p class="mt-8 text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} NUSA. AI Assistant.
        </p>
    </div>
</div>
@endsection
