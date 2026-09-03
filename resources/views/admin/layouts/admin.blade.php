<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Panel — NUSA')</title>
    <link rel="icon" href="data:,">

    {{-- Prevent FOUC for Dark Mode --}}
    <script>
        try {
            const storedTheme = localStorage.getItem('nusa-dark-mode');
            if (storedTheme === 'true' || (storedTheme === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    </script>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,300..700;1,300..700&display=swap" rel="stylesheet">

    {{-- Lucide Icons --}}
    <script src="https://unpkg.com/lucide@latest"></script>

    {{-- Animate.css --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        *, body, input, select, textarea, button {
            font-family: 'JetBrains Mono', monospace !important;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #6b7280; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #4b5563; }

        /* Sidebar transitions */
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #6b7280;
            transition: background 0.15s, color 0.15s;
            text-decoration: none;
        }
        .sidebar-link:hover {
            background: #f3f4f6;
            color: #111827;
        }
        .sidebar-link.active {
            background: #111827;
            color: #ffffff;
        }
        .dark .sidebar-link {
            color: #9ca3af;
        }
        .dark .sidebar-link:hover {
            background: #2F3030;
            color: #e5e7eb;
        }
        .dark .sidebar-link.active {
            background: #e5e7eb;
            color: #111827;
        }

        /* Stat card */
        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1.25rem 1.5rem;
            transition: box-shadow 0.2s;
        }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .dark .stat-card {
            background: #202222;
            border-color: #2F3030;
        }

        /* Table */
        .admin-table th {
            background: #f9fafb;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            padding: 0.625rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .admin-table th {
            background: #191A1A;
            color: #6b7280;
            border-color: #2F3030;
        }
        .admin-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.8125rem;
            vertical-align: middle;
        }
        .dark .admin-table td {
            border-color: #2F3030;
        }
        .admin-table tr:last-child td { border-bottom: none; }
        .admin-table tbody tr {
            transition: background 0.1s;
        }
        .admin-table tbody tr:hover { background: #f9fafb; }
        .dark .admin-table tbody tr:hover { background: #191A1A; }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
            line-height: 1.4;
        }
        .badge-admin { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .badge-user  { background: #f9fafb; color: #6b7280; border: 1px solid #e5e7eb; }
        .dark .badge-admin { background: #14532d44; color: #4ade80; border-color: #16a34a55; }
        .dark .badge-user  { background: #2F303066; color: #9ca3af; border-color: #2F3030; }

        /* Buttons */
        .btn-primary {
            display: inline-flex; align-items: center; gap: 0.375rem;
            padding: 0.5rem 1rem; border-radius: 0.5rem;
            background: #111827; color: white; font-size: 0.8125rem; font-weight: 500;
            transition: background 0.15s;
            text-decoration: none; cursor: pointer; border: none;
        }
        .btn-primary:hover { background: #1f2937; }
        .dark .btn-primary { background: #e5e7eb; color: #111827; }
        .dark .btn-primary:hover { background: #d1d5db; }

        .btn-secondary {
            display: inline-flex; align-items: center; gap: 0.375rem;
            padding: 0.5rem 1rem; border-radius: 0.5rem;
            background: white; color: #374151; border: 1px solid #d1d5db;
            font-size: 0.8125rem; font-weight: 500;
            transition: background 0.15s; text-decoration: none; cursor: pointer;
        }
        .btn-secondary:hover { background: #f9fafb; }
        .dark .btn-secondary { background: #202222; color: #d1d5db; border-color: #2F3030; }
        .dark .btn-secondary:hover { background: #2F3030; }

        .btn-danger {
            display: inline-flex; align-items: center; gap: 0.375rem;
            padding: 0.375rem 0.75rem; border-radius: 0.5rem;
            background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;
            font-size: 0.75rem; font-weight: 500;
            transition: background 0.15s; cursor: pointer; border: none;
        }
        .btn-danger:hover { background: #fee2e2; }
        .dark .btn-danger { background: #7f1d1d33; color: #f87171; border-color: #dc262655; }
        .dark .btn-danger:hover { background: #7f1d1d55; }

        /* Alert */
        .alert-success {
            background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d;
            padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.8125rem;
        }
        .alert-error {
            background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
            padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.8125rem;
        }
        .dark .alert-success { background: #14532d33; border-color: #16a34a55; color: #4ade80; }
        .dark .alert-error   { background: #7f1d1d33; border-color: #dc262655; color: #f87171; }

        /* FOUC */
        body { opacity: 0; transition: opacity 0.2s ease-in-out; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('styles')

    <script>
        window.addEventListener('load', function() {
            document.body.style.opacity = '1';
        });
        setTimeout(function() { document.body.style.opacity = '1'; }, 400);
    </script>
</head>
<body class="bg-gray-50 dark:bg-[#191A1A] text-gray-900 dark:text-gray-100 antialiased">

<div class="min-h-screen flex">

    {{-- ─── Sidebar ─── --}}
    <aside class="w-56 shrink-0 bg-white dark:bg-[#202222] border-r border-gray-200 dark:border-[#2F3030] flex flex-col">
        {{-- Brand --}}
        <div class="px-4 pt-5 pb-4 border-b border-gray-200 dark:border-[#2F3030]">
            <div class="flex items-center gap-2 mb-0.5">
                <span class="text-lg font-bold tracking-widest text-gray-900 dark:text-white">NUSA</span>
                <span class="badge badge-admin text-[0.6rem]">Admin</span>
            </div>
            <p class="text-xs text-gray-400">Panel Administrasi</p>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
            <p class="px-2 pb-2 text-[0.6rem] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-600">Menu</p>

            <a href="{{ route('admin.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i data-lucide="layout-dashboard" class="h-4 w-4 shrink-0"></i>
                Dashboard
            </a>

            <a href="{{ route('admin.users') }}"
               class="sidebar-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                <i data-lucide="users" class="h-4 w-4 shrink-0"></i>
                Manajemen User
            </a>

            <a href="{{ route('admin.sessions') }}"
               class="sidebar-link {{ request()->routeIs('admin.sessions*') ? 'active' : '' }}">
                <i data-lucide="message-square" class="h-4 w-4 shrink-0"></i>
                Sesi Chat
            </a>

            <div class="pt-3 mt-3 border-t border-gray-100 dark:border-[#2F3030]">
                <p class="px-2 pb-2 text-[0.6rem] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-600">Aplikasi</p>
                <a href="{{ route('chat') }}" class="sidebar-link">
                    <i data-lucide="arrow-left" class="h-4 w-4 shrink-0"></i>
                    Kembali ke Chat
                </a>
            </div>
        </nav>

        {{-- User Info --}}
        <div class="px-4 py-3 border-t border-gray-200 dark:border-[#2F3030]">
            <div class="flex items-center gap-2.5">
                <div class="h-7 w-7 rounded-full bg-gray-900 dark:bg-[#2F3030] flex items-center justify-center text-white text-xs font-bold shrink-0">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-900 dark:text-gray-100 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[0.65rem] text-gray-400 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" class="w-full text-left sidebar-link text-rose-500 dark:text-rose-400 hover:!bg-rose-50 dark:hover:!bg-rose-900/20 hover:!text-rose-600">
                    <i data-lucide="log-out" class="h-4 w-4 shrink-0"></i>
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    {{-- ─── Main Content ─── --}}
    <main class="flex-1 min-w-0 flex flex-col">
        {{-- Top bar --}}
        <div class="bg-white dark:bg-[#202222] border-b border-gray-200 dark:border-[#2F3030] px-6 py-3.5 flex items-center justify-between shrink-0">
            <div>
                <h1 class="text-base font-semibold text-gray-900 dark:text-gray-100">@yield('page-title', 'Admin Panel')</h1>
                @hasSection('page-subtitle')
                    <p class="text-xs text-gray-400 mt-0.5">@yield('page-subtitle')</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @yield('page-actions')
            </div>
        </div>

        {{-- Flash messages --}}
        <div class="px-6 pt-4">
            @if (session('success'))
                <div class="alert-success flex items-center gap-2 animate__animated animate__fadeIn animate__faster">
                    <i data-lucide="check-circle" class="h-4 w-4 shrink-0"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert-error flex items-center gap-2 animate__animated animate__fadeIn animate__faster">
                    <i data-lucide="alert-circle" class="h-4 w-4 shrink-0"></i>
                    {{ session('error') }}
                </div>
            @endif
        </div>

        {{-- Page Content --}}
        <div class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </div>
    </main>

</div>

<script>
    if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>

@yield('scripts')
</body>
</html>
