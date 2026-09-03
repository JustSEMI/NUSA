@extends('layouts.app')

@section('title', 'Chat - NUSA')

@section('styles')
<style>
    /* Custom Dropdown Animation */
    #modelDropdownMenu, #thinkingEffortMenu, #attachMenu {
        transform-origin: bottom left;
    }

    #modelDropdownMenu.invisible, #thinkingEffortMenu.invisible, #attachMenu.invisible {
        pointer-events: none;
    }

    #modelDropdownMenu.visible, #thinkingEffortMenu.visible, #attachMenu.visible {
        pointer-events: auto;
    }

    /* Message Animation */
    @keyframes messageSlideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes messageFadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .message-animate {
        animation: messageSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .message-fade-in {
        animation: messageFadeIn 0.3s ease-out;
    }

    /* Error Message Styling */
    .error-message {
        background-color: #fef2f2;
        border-left: 4px solid #ef4444;
        padding: 12px 16px;
        border-radius: 8px;
    }

    .error-message strong {
        color: #dc2626;
        font-weight: 600;
    }

    /* Typing cursor animation */
    .typing-cursor::after {
        content: '|';
        animation: blink 1s step-start infinite;
        color: #9ca3af;
    }

    @keyframes blink {
        50% { opacity: 0; }
    }

    /* Markdown Styles */
    .markdown-content {
        line-height: 1.7;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .markdown-content > *:first-child {
        margin-top: 0;
    }

    .markdown-content > *:last-child {
        margin-bottom: 0;
    }

    .markdown-content h1,
    .markdown-content h2,
    .markdown-content h3,
    .markdown-content h4,
    .markdown-content h5,
    .markdown-content h6 {
        margin-top: 1.5em;
        margin-bottom: 0.75em;
        font-weight: 600;
        line-height: 1.3;
    }

    .markdown-content h1 { font-size: 1.5em; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.3em; }
    .markdown-content h2 { font-size: 1.3em; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.3em; }
    .markdown-content h3 { font-size: 1.1em; }
    .markdown-content h4 { font-size: 1em; }

    .markdown-content p {
        margin-bottom: 1em;
    }

    .markdown-content ul,
    .markdown-content ol {
        margin-bottom: 1em;
        padding-left: 1.5em;
    }

    .markdown-content ul { list-style-type: disc; }
    .markdown-content ol { list-style-type: decimal; }

    .markdown-content code {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        padding: 0.25em 0.5em;
        border-radius: 6px;
        font-size: 0.825em;
        font-family: 'JetBrains Mono', 'Courier New', monospace;
        color: #92400e;
        font-weight: 600;
        border: 1px solid rgba(217, 119, 6, 0.2);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .markdown-content pre code {
        color: #e5e7eb;
        background: transparent;
        border: none;
        box-shadow: none;
    }

    .markdown-content pre {
        margin: 1em 0;
        border-radius: 8px;
        overflow: hidden;
        background-color: #1f2937;
    }

    .markdown-content pre code {
        background-color: transparent;
        padding: 1em;
        display: block;
        overflow-x: auto;
        font-size: 0.85em;
        line-height: 1.6;
    }

    .markdown-content blockquote {
        border-left: 4px solid #e5e7eb;
        padding-left: 1em;
        margin: 1em 0;
        color: #6b7280;
        font-style: italic;
    }

    .markdown-content table {
        border-collapse: collapse;
        width: 100%;
        margin: 1em 0;
    }

    .markdown-content th,
    .markdown-content td {
        border: 1px solid #e5e7eb;
        padding: 0.5em 1em;
        text-align: left;
    }

    .markdown-content th {
        background-color: #f9fafb;
        font-weight: 600;
    }

    .markdown-content hr {
        border: none;
        border-top: 1px solid #e5e7eb;
        margin: 2em 0;
    }

    .markdown-content strong {
        font-weight: 600;
        color: #111827;
    }

    .markdown-content em {
        font-style: italic;
    }

    /* Code block with copy button */
    .code-block-wrapper {
        position: relative;
        margin: 1.5em 0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .code-block-wrapper:hover {
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }

    .code-block-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75em 1em;
        background: linear-gradient(135deg, #374151 0%, #1f2937 100%);
        border-bottom: 1px solid #4b5563;
    }

    .code-block-language {
        color: #d1d5db;
        font-size: 0.7em;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .copy-code-btn {
        display: flex;
        align-items: center;
        gap: 0.4em;
        padding: 0.35em 0.85em;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 6px;
        color: #9ca3af;
        font-size: 0.7em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(4px);
    }

    .copy-code-btn:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        border-color: rgba(255, 255, 255, 0.4);
        transform: translateY(-1px) scale(1.02);
    }

    .copy-code-btn.copied {
        background: rgba(5, 150, 105, 0.3);
        border-color: #059669;
        color: #fff;
    }

    .code-block-wrapper pre {
        margin: 0;
        border-radius: 0;
        background: #1f2937 !important;
    }

    .code-block-wrapper pre code {
        padding: 1.5em;
        display: block;
        overflow-x: auto;
        font-size: 0.825em;
        line-height: 1.7;
        font-family: 'JetBrains Mono', 'Fira Code', monospace;
        line-height: 1.6;
    }

    /* Search Highlight */
    .search-highlight {
        background-color: rgb(254, 240, 138) !important;
        border-radius: 4px;
        padding: 2px 4px;
    }

    /* Attachment Preview Tray & Chips */
    .attachment-preview-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        padding: 0.35rem 0.65rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        max-width: 260px;
        position: relative;
        transition: all 0.2s ease;
    }

    .dark .attachment-preview-chip {
        background: #191A1A;
        border-color: #2F3030;
        color: #e5e7eb;
    }

    .attachment-preview-chip img {
        width: 32px;
        height: 32px;
        object-fit: cover;
        border-radius: 6px;
    }

    .attachment-chip-remove {
        color: #9ca3af;
        border-radius: 9999px;
        padding: 2px;
        transition: all 0.15s ease;
        cursor: pointer;
    }

    .attachment-chip-remove:hover {
        color: #ef4444;
        background-color: rgba(239, 68, 68, 0.15);
    }

    /* Message Bubble Attachment Styles */
    .msg-attachment-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.65rem;
    }

    .msg-attachment-img {
        max-width: 240px;
        max-height: 180px;
        border-radius: 0.5rem;
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .msg-attachment-img:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .msg-attachment-doc {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 0.4rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        text-decoration: none;
        color: #f9fafb;
        transition: all 0.2s ease;
    }

    .msg-attachment-doc:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.35);
    }

    .dark .msg-attachment-doc {
        background: #191A1A;
        border-color: #2F3030;
        color: #e5e7eb;
    }

    .dark .msg-attachment-doc:hover {
        background: #252727;
        border-color: #404242;
    }
</style>
@endsection

@section('content')
<div class="relative flex h-screen flex-col md:flex-row overflow-hidden">
    {{-- Mobile Overlay for Sidebar --}}
    <div id="sidebarBackdrop" onclick="toggleChatSidebar(false)" class="fixed inset-0 z-40 hidden bg-gray-900/50 backdrop-blur-sm md:hidden transition-opacity"></div>

    {{-- Sidebar - Chat History --}}
    <div id="chatSidebar" class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] transform -translate-x-full transition-transform duration-300 ease-in-out md:static md:w-64 md:translate-x-0 border-r border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] flex flex-col h-full shadow-lg md:shadow-none">
        {{-- Brand --}}
        <div class="px-4 py-3 border-b border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A]">
            <div>
                <h2 class="text-xl font-bold tracking-widest text-gray-900 dark:text-white">NUSA</h2>
                <p class="text-xs text-gray-500 mt-0.5">AI Assistant</p>
            </div>
            <button onclick="toggleChatSidebar(false)" class="absolute top-3 right-3 rounded-md p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-[#2F3030] md:hidden">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

    {{-- Status AI Button --}}
    <div class="p-4 pb-2">
        <button onclick="openStatusModal()"
                class="w-full flex items-center justify-center gap-2 rounded-md border border-gray-300 dark:border-[#2F3030] bg-white dark:bg-[#202222] px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-[#2F3030] active:scale-95 transition-all">
            <i data-lucide="activity" class="h-4 w-4"></i> Status AI
        </button>
    </div>

        {{-- History --}}
        <div class="flex-1 overflow-y-auto p-2" id="chatHistoryList">
            <div class="px-2 mb-3 mt-1">
                <div class="relative">
                    <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"></i>
                    <input type="text" id="searchHistoryInput" placeholder="Cari percakapan..." class="w-full bg-gray-50 dark:bg-[#202222] border border-gray-200 dark:border-[#333538] rounded-md pl-8 pr-3 py-1.5 text-sm text-gray-900 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-emerald-500 placeholder-gray-400 transition-colors">
                </div>
            </div>
            <div id="chatHistoryContainer" class="space-y-1">
                {{-- Chat history akan dimuat di sini dari backend --}}
                <div class="text-center py-8 text-xs text-gray-400">
                    <i data-lucide="message-square" class="h-6 w-6 mx-auto mb-2 opacity-50"></i>
                    <p>Belum ada riwayat chat</p>
                </div>
            </div>
        </div>

        {{-- New chat --}}
        <div class="p-4 pt-2">
            <button id="newChatBtn"
                    class="w-full flex items-center justify-center gap-2 rounded-md border border-transparent dark:border-[#2F3030] bg-black dark:bg-[#202222] px-4 py-2 text-sm font-medium text-white dark:text-gray-200 hover:bg-gray-800 dark:hover:bg-[#2F3030] active:scale-95 transition-all shadow-sm">
                <i data-lucide="plus" class="h-4 w-4"></i> Chat Baru
            </button>
        </div>

        {{-- User --}}
        <div class="p-3 border-t border-gray-200 dark:border-[#2F3030] space-y-2 bg-white dark:bg-[#191A1A]">
            <div class="flex items-center gap-3 rounded-md px-2 py-2">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-black dark:bg-[#202222] border border-transparent dark:border-[#2F3030] text-sm font-bold text-white dark:text-gray-100 shadow-sm">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ auth()->user()->name ?? 'Pengguna' }}</p>
                    <p class="truncate text-xs text-gray-400">{{ auth()->user()->email ?? '' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 px-2">
                <a href="{{ route('settings') }}" class="flex-1 rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#202222] px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#2F3030] active:scale-95 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="settings" class="h-4 w-4"></i> Settings
                </a>
                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <button type="submit" title="Keluar" class="w-full rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#202222] px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#2F3030] active:scale-95 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="log-out" class="h-4 w-4"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Main Chat Area --}}
    <div class="flex-1 flex flex-col bg-gray-50 dark:bg-[#191A1A] min-w-0">
        {{-- Chat Header --}}
        <div class="border-b border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#202222] px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <button onclick="toggleChatSidebar(true)" class="rounded-md p-1.5 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#2F3030] md:hidden" title="Buka Riwayat">
                        <i data-lucide="history" class="h-5 w-5"></i>
                    </button>
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        <span class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Online — siap membantu</span>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2">
                    {{-- Search Input --}}
                    <div class="relative hidden sm:block">
                        <input type="text"
                               id="searchInput"
                               placeholder="Cari di chat..."
                               class="w-48 rounded-lg border border-gray-300 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-3 py-1.5 pl-9 text-xs focus:border-gray-900 dark:focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-500 text-gray-900 dark:text-gray-100"
                               oninput="searchChat(this.value)">
                        <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400"></i>
                    </div>
                    {{-- Export Button --}}
                    <button onclick="exportConversation()"
                            class="rounded-lg p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#2F3030]"
                            title="Export Chat">
                        <i data-lucide="download" class="h-5 w-5"></i>
                    </button>
                    <button id="darkModeToggle"
                            onclick="toggleDarkMode()"
                            class="rounded-lg p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#2F3030]"
                            title="Toggle Dark Mode">
                        <i data-lucide="moon" class="h-5 w-5"></i>
                    </button>
                    <button id="deleteChatBtn"
                            onclick="showDeleteConfirm()"
                            class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 whitespace-nowrap disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-gray-600 transition-opacity"
                            disabled>
                        Hapus Chat Ini
                    </button>
                </div>
            </div>
        </div>
        <div id="chatMessages" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">
            {{-- Delete Confirmation Modal --}}
            <div id="deleteConfirmModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50" style="display: none;">
                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-[#202222] border border-transparent dark:border-[#2F3030] rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden transform transition-all" onclick="event.stopPropagation()">
                        <div class="p-6">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                    <i data-lucide="trash-2" class="h-6 w-6 text-red-600 dark:text-red-400"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Hapus Chat Ini?</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Gak bisa dibalikin lho</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                                Semua pesan di chat ini bakal kehapus permanen. Yakin nih?
                            </p>
                            <div class="flex gap-3">
                                <button onclick="hideDeleteConfirm()"
                                        class="flex-1 rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#2F3030] transition">
                                    Batal
                                </button>
                                <button onclick="confirmDeleteChat()"
                                        class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700 transition">
                                    Ya, Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Welcome state --}}
            <div id="welcomeState" class="h-full flex items-center justify-center py-6">
                <div class="text-center px-4">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#202222] shadow-sm">
                        <i data-lucide="message-square" class="h-7 w-7 text-gray-400 dark:text-gray-500"></i>
                    </div>

                    @php
                        $subtitles = [
                            "Kamu bisa bertanya ke saya bebas apa saja.",
                            "Ada yang bisa saya bantu untuk tugas atau proyekmu hari ini?",
                            "Tanya apa saja — mulai dari ide kreatif, kode program, hingga analisis data.",
                            "Siap membantu menyelesaikan masalahmu hari ini. Mau mulai dari mana?",
                            "Butuh inspirasi, revisi kode, atau sekadar teman diskusi? Tanyakan saja!",
                            "Ketik pertanyaan atau paste gambar dan file untuk mulai menganalisis.",
                        ];
                        $randomSubtitle = $subtitles[array_rand($subtitles)];
                    @endphp
                    <h3 class="mt-4 text-base sm:text-lg font-medium text-gray-900 dark:text-white">Halo, Selamat Datang {{ auth()->user()->name ?? 'Pengguna' }}! 👋</h3>
                    <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{ $randomSubtitle }}</p>
                </div>
            </div>

            {{-- Messages --}}
            <div id="messages" class="space-y-4 hidden"></div>
        </div>

        {{-- Chat Input --}}
        <div class="border-t border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#202222] p-3 sm:p-4">
            <form id="chatForm" class="flex flex-col gap-2 sm:gap-3 max-w-4xl mx-auto">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <div class="flex items-center gap-2">
                    {{-- Model Selector (Custom Dropdown) --}}
                    <div class="relative">
                        <button type="button"
                                id="modelDropdownBtn"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-3.5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#2F3030] focus:outline-none focus:ring-0 focus-visible:ring-0 active:outline-none transition-colors">
                            @php
                                $defaultModel = config('services.ai.model', 'qwen-flash');
                                $selectedModelData = config('services.ai.available_models.' . $defaultModel, ['name' => 'Qwen Flash', 'multiplier' => '1x']);
                            @endphp
                            <span id="selectedModelText">{{ $selectedModelData['name'] }}</span>
                            <span class="text-gray-400 text-xs">{{ $selectedModelData['multiplier'] }}</span>
                            <i data-lucide="chevron-up" class="h-4 w-4 transition-transform" id="modelDropdownIcon"></i>
                        </button>

                        {{-- Dropdown Menu (Opens Upward) --}}
                        <div id="modelDropdownMenu"
                             class="absolute bottom-full left-0 mb-1 w-56 rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#202222] shadow-xl opacity-0 invisible transition-all duration-200 transform translate-y-2" style="z-index: 9999;">
                            <div class="py-1">
                                @foreach(config('services.ai.available_models', []) as $modelId => $modelData)
                                <button type="button"
                                        class="model-option w-full text-left px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#2F3030] transition-colors flex justify-between items-center"
                                        data-value="{{ $modelId }}">
                                    <span class="font-medium">{{ $modelData['name'] }}</span>
                                    <span class="text-gray-400 text-xs ml-2">{{ $modelData['multiplier'] }}</span>
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Thinking Effort Selector (Custom Dropdown) --}}
                    <div class="relative">
                        <button type="button"
                                id="thinkingEffortBtn"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-3.5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#2F3030] focus:outline-none focus:ring-0 focus-visible:ring-0 active:outline-none transition-colors">
                            <span id="thinkingEffortText">High</span>
                            <i data-lucide="chevron-up" class="h-4 w-4 transition-transform" id="thinkingEffortIcon"></i>
                        </button>

                        {{-- Dropdown Menu --}}
                        <div id="thinkingEffortMenu"
                             class="absolute bottom-full left-0 mb-1 w-48 rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#202222] shadow-xl opacity-0 invisible transition-all duration-200 transform translate-y-2" style="z-index: 9999;">
                            <div class="py-1">
                                <button type="button" class="thinking-option w-full text-left px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#2F3030] transition-colors" data-value="low">
                                    <div>
                                        <span class="font-medium">Low</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">Cepat, basic</span>
                                    </div>
                                </button>
                                <button type="button" class="thinking-option w-full text-left px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#2F3030] transition-colors" data-value="medium">
                                    <div>
                                        <span class="font-medium">Medium</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">Balanced</span>
                                    </div>
                                </button>
                                <button type="button" class="thinking-option w-full text-left px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#2F3030] transition-colors" data-value="high">
                                    <div>
                                        <span class="font-medium">High</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">Deep reasoning</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Attachment Preview Tray --}}
                <div id="attachmentPreviewTray" class="hidden flex flex-wrap gap-2 pt-1 pb-1">
                    {{-- Dynamically inserted attachment chips --}}
                </div>

                <div class="flex gap-2 sm:gap-3 items-center">
                    {{-- Hidden Specialized File Inputs --}}
                    <input type="file" id="fileInput" class="hidden" multiple accept="image/*,.pdf,.doc,.docx,.xlsx,.xls,.csv,.tsv,.json,.txt,.md,.markdown,.log,.html,.xml,.yaml,.yml,.sql,.php,.js,.jsx,.ts,.tsx,.vue,.py,.java,.c,.cpp,.h,.cs,.go,.rs,.rb,.sh,.bash,.ini,.conf,.env">
                    <input type="file" id="fileInputDocument" class="hidden" multiple accept=".pdf,.doc,.docx,.xlsx,.xls,.csv,.tsv,.json,.txt,.md,.markdown,.log,.html,.xml,.yaml,.yml">
                    <input type="file" id="fileInputImage" class="hidden" multiple accept="image/*">
                    <input type="file" id="fileInputCode" class="hidden" multiple accept=".js,.jsx,.ts,.tsx,.vue,.py,.php,.java,.c,.cpp,.h,.cs,.go,.rs,.rb,.sh,.bash,.sql,.html,.css,.json,.yaml,.yml,.env,.ini,.conf">

                    {{-- Gemini-style Attachment Button & Dropdown --}}
                    <div class="relative shrink-0">
                        <button type="button"
                                id="attachFileBtn"
                                class="rounded-lg text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-[#282a2d] border border-gray-300 dark:border-[#383a3f] bg-white dark:bg-[#1e1f20] shrink-0 inline-flex items-center justify-center w-10 h-10 transition-all duration-200 active:scale-95 focus:outline-none"
                                title="Masukkan file, foto, atau kode"
                                aria-expanded="false"
                                aria-haspopup="true">
                            <i data-lucide="plus" id="attachFileIcon" class="h-5 w-5 transition-transform duration-200"></i>
                        </button>

                        {{-- Gemini Floating Attachment Menu --}}
                        <div id="attachMenu"
                             class="absolute bottom-full left-0 mb-2 w-64 rounded-2xl border border-gray-200 dark:border-[#333538] bg-white dark:bg-[#1e1f20] shadow-2xl p-1.5 opacity-0 invisible transition-all duration-200 ease-out transform translate-y-2 pointer-events-none"
                             style="z-index: 9999;">
                            <div class="space-y-0.5">
                                {{-- Impor File --}}
                                <button type="button"
                                        id="menuUploadFile"
                                        class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#2c2d30] transition-colors group">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-gray-100 dark:bg-[#2a2c30] text-gray-600 dark:text-gray-300 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-950/50 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors shrink-0">
                                        <i data-lucide="paperclip" class="h-4 w-4"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-sm text-gray-900 dark:text-gray-100">Impor file</div>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 truncate">PDF, Word, TXT, Dokumen</div>
                                    </div>
                                </button>

                                {{-- Foto --}}
                                <button type="button"
                                        id="menuUploadPhoto"
                                        class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#2c2d30] transition-colors group">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-gray-100 dark:bg-[#2a2c30] text-gray-600 dark:text-gray-300 group-hover:bg-blue-100 dark:group-hover:bg-blue-950/50 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors shrink-0">
                                        <i data-lucide="image" class="h-4 w-4"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-sm text-gray-900 dark:text-gray-100">Foto</div>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 truncate">PNG, JPG, WEBP, SVG</div>
                                    </div>
                                </button>

                                {{-- Impor Kode --}}
                                <button type="button"
                                        id="menuUploadCode"
                                        class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#2c2d30] transition-colors group">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-gray-100 dark:bg-[#2a2c30] text-gray-600 dark:text-gray-300 group-hover:bg-purple-100 dark:group-hover:bg-purple-950/50 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors shrink-0">
                                        <i data-lucide="code" class="h-4 w-4"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-sm text-gray-900 dark:text-gray-100">Impor kode</div>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 truncate">PHP, JS, Python, SQL, C++</div>
                                    </div>
                                </button>
                            </div>

                            <div class="mt-1 pt-1.5 border-t border-gray-100 dark:border-[#282a2e] px-2.5 py-1 flex items-center justify-between text-[11px] text-gray-400">
                                <span>Atau paste langsung (Ctrl+V)</span>
                            </div>
                        </div>
                    </div>

                    <input type="text"
                           id="messageInput"
                           name="message"
                           placeholder="Ketik pesan atau paste gambar (Ctrl+V)..."
                           class="flex-1 rounded-lg border border-gray-300 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] text-gray-900 dark:text-gray-100 px-4 py-2.5 text-sm focus:border-gray-900 dark:focus:border-gray-500 focus:outline-none focus:ring-0 focus-visible:ring-0 active:outline-none"
                           autocomplete="off">
                    <button type="submit"
                            id="sendButton"
                            class="rounded-lg text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-[#2F3030] disabled:opacity-50 shrink-0 inline-flex items-center justify-center w-10 h-10 transition-all duration-200 ease-in-out transform hover:scale-105 active:scale-95 focus:outline-none focus:ring-0 focus-visible:ring-0 active:outline-none"
                            title="Kirim">
                        <i data-lucide="send" class="h-5 w-5"></i>
                    </button>
                    <button type="button"
                            id="stopButton"
                            style="display: none;"
                            class="hidden rounded-lg text-red-600 dark:text-red-500 hover:text-red-700 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 shrink-0 inline-flex items-center justify-center w-10 h-10 transition-all duration-200 ease-in-out transform hover:scale-105 active:scale-95 focus:outline-none focus:ring-0 focus-visible:ring-0 active:outline-none"
                            title="Stop generating">
                        <i data-lucide="square" class="h-5 w-5"></i>
                    </button>
                </div>
            </form>
            <p class="mt-2 text-center text-[10px] sm:text-xs text-gray-400">
                AI bisa membuat kesalahan. Periksa informasi penting sebelum mengambil keputusan.
            </p>
        </div>
    </div>
</div>

{{-- Drag & Drop Overlay --}}
<div id="dragDropOverlay" class="fixed inset-0 z-50 bg-gray-900/70 backdrop-blur-sm hidden flex-col items-center justify-center pointer-events-none transition-all">
    <div class="bg-white dark:bg-[#202222] border-2 border-dashed border-emerald-500 dark:border-emerald-400 rounded-2xl p-8 max-w-sm text-center shadow-2xl transform scale-105">
        <div class="h-16 w-16 mx-auto rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 mb-3">
            <i data-lucide="upload-cloud" class="h-8 w-8"></i>
        </div>
        <h4 class="text-base font-semibold text-gray-900 dark:text-white">Lepaskan file di sini</h4>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Mendukung gambar (PNG, JPG, WEBP) dan dokumen (PDF, Word, TXT, Code, dll.)</p>
    </div>
</div>

{{-- Lightbox Modal for Image Preview --}}
<div id="imageLightboxModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md hidden items-center justify-center p-4 transition-all" onclick="closeLightboxModal()">
    <button type="button" class="absolute top-4 right-4 text-white hover:text-gray-300 p-2 rounded-full bg-black/50 hover:bg-black/80 transition" onclick="closeLightboxModal()">
        <i data-lucide="x" class="h-6 w-6"></i>
    </button>
    <img id="lightboxImage" src="" alt="Full Preview" class="max-h-[88vh] max-w-[90vw] object-contain rounded-lg shadow-2xl" onclick="event.stopPropagation()">
</div>

{{-- AI Status Modal --}}
<div id="statusModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeStatusModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-[#202222] border border-transparent dark:border-[#2F3030] text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-[#2F3030]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 dark:bg-[#191A1A]">
                            <i data-lucide="activity" class="h-5 w-5 text-gray-700 dark:text-gray-300"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">Status AI Models</h3>
                    </div>
                </div>
                <div class="px-6 py-4">
                    <div id="statusLoading" class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-gray-200 dark:border-[#2F3030] border-t-gray-900 dark:border-t-emerald-500"></div>
                        <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-200">Memeriksa status AI...</p>
                        <p class="mt-1 text-xs text-gray-400">Proses ini mungkin membutuhkan waktu beberapa saat.</p>
                    </div>
                    <div id="statusContent" class="hidden">
                        <div class="space-y-2" id="statusList"></div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-[#191A1A] px-6 py-4 flex flex-col gap-2">
                    <button onclick="manualHeartbeat()"
                            class="w-full flex items-center justify-center gap-2 rounded-lg bg-gray-900 dark:bg-[#2F3030] border border-transparent dark:border-gray-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-gray-800 dark:hover:bg-[#3F4040] transition">
                        <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                        Refresh Status
                    </button>
                    <button type="button" onclick="closeStatusModal()"
                            class="w-full rounded-lg border border-gray-300 dark:border-[#2F3030] bg-white dark:bg-[#202222] px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#2F3030] transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Pass config from Laravel to JavaScript
window.chatConfig = {!! json_encode([
    'defaultModel' => config('services.ai.model', 'qwen-flash'),
    'availableModels' => config('services.ai.available_models', []),
    'userInitial' => strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)),
    'userName' => auth()->user()->name ?? 'Pengguna',
    'csrfToken' => csrf_token(),
    'initialHistory' => $initialHistory ?? null,
]) !!};

function openLightbox(src) {
    const modal = document.getElementById('imageLightboxModal');
    const img = document.getElementById('lightboxImage');
    if (modal && img) {
        img.src = src;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeLightboxModal() {
    const modal = document.getElementById('imageLightboxModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}
</script>
@endsection
