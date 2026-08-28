@extends('layouts.app')

@section('title', 'Pengaturan — NUSA')
@section('content')
{{-- Hidden safelist for Tailwind JIT --}}
<div class="hidden dark:bg-emerald-500 dark:bg-[#3F4040]"></div>

<div class="min-h-screen bg-gray-50 dark:bg-[#191A1A]">
    {{-- Header --}}
    <div class="bg-white dark:bg-[#202222] border-b border-gray-200 dark:border-[#2F3030]">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 py-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('chat') }}" class="rounded-lg p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-[#2F3030] transition">
                    <i data-lucide="arrow-left" class="h-5 w-5"></i>
                </a>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Pengaturan</h1>
            </div>
        </div>
    </div>
    
    {{-- Content --}}
    <div class="mx-auto max-w-3xl px-4 sm:px-6 py-8 space-y-6">
        
        {{-- Account Section --}}
        <div class="bg-white dark:bg-[#202222] rounded-lg border border-gray-200 dark:border-[#2F3030] p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Akun</h2>
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-900 dark:bg-[#2F3030] text-white dark:text-gray-200 font-semibold border border-transparent dark:border-gray-600">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1">
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>
        
        {{-- Preferences Section --}}
        <div class="bg-white dark:bg-[#202222] rounded-lg border border-gray-200 dark:border-[#2F3030] p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Preferensi</h2>
            
            <div class="space-y-4">
                {{-- Dark Mode --}}
                <div class="flex items-center justify-between py-2">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Mode Gelap</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Gunakan tema gelap</p>
                    </div>
                    <button id="darkModeToggle" 
                            onclick="toggleDarkMode()"
                            class="toggle-btn relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none bg-gray-200 dark:bg-[#3F4040]">
                        <span class="toggle-dot pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out translate-x-0"></span>
                    </button>
                </div>
                
                {{-- Auto-save Chat --}}
                <div class="flex items-center justify-between py-2">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Auto-save Chat</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Simpan riwayat otomatis</p>
                    </div>
                    <button id="autoSaveToggle" 
                            onclick="toggleSetting('auto_save', this)"
                            class="toggle-btn relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none bg-gray-200 dark:bg-[#3F4040]">
                        <span class="toggle-dot pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out translate-x-0"></span>
                    </button>
                </div>
                
                {{-- Streaming Response --}}
                <div class="flex items-center justify-between py-2">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Streaming Response</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Jawaban AI real-time</p>
                    </div>
                    <button id="streamingToggle" 
                            onclick="toggleSetting('streaming', this)"
                            class="toggle-btn relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none bg-gray-200 dark:bg-[#3F4040]">
                        <span class="toggle-dot pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out translate-x-0"></span>
                    </button>
                </div>
            </div>
        </div>
        
        {{-- Chat Settings --}}
        <div class="bg-white dark:bg-[#202222] rounded-lg border border-gray-200 dark:border-[#2F3030] p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Chat</h2>
            
            <div class="space-y-4">
                {{-- Default Model --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Model AI Default</label>
                    <select id="defaultModel" 
                            class="w-full rounded-lg border border-gray-300 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-4 py-2.5 text-sm focus:border-gray-900 dark:focus:border-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400 dark:text-gray-100">
                        @foreach(config('services.ai.available_models', []) as $modelId => $modelData)
                            <option value="{{ $modelId }}">{{ $modelData['name'] }} ({{ $modelData['multiplier'] }})</option>
                        @endforeach
                    </select>
                    <button id="saveModelBtn" onclick="saveDefaultModel(event)" 
                            class="mt-3 w-full rounded-lg bg-gray-900 dark:bg-[#2F3030] border border-transparent dark:border-gray-600 px-4 py-2 text-sm font-medium text-white dark:text-gray-200 hover:bg-gray-800 dark:hover:bg-[#3a3b3b] transition">
                        Simpan
                    </button>
                </div>
                
                {{-- Clear Chat History --}}
                <div class="border-t border-gray-200 dark:border-[#2F3030] pt-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">Riwayat Chat</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Hapus semua riwayat</p>
                        </div>
                        <button onclick="clearAllChatHistory()" 
                                class="rounded-lg border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-900/20 px-4 py-2 text-sm font-medium text-red-700 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 transition">
                            Hapus Semua
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Data --}}
        <div class="bg-white dark:bg-[#202222] rounded-lg border border-gray-200 dark:border-[#2F3030] p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Data</h2>
            
            <div class="space-y-4">
                {{-- Export --}}
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Export Data</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Download data JSON</p>
                    </div>
                    <button onclick="exportAllData()" 
                            class="rounded-lg border border-gray-200 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#2F3030] transition">
                        Export
                    </button>
                </div>
            </div>
        </div>
        
        {{-- Danger Zone --}}
        <div class="bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-900/30 rounded-lg p-6">
            <h3 class="font-semibold text-red-900 dark:text-red-400 mb-2">Zone Bahaya</h3>
            <p class="text-sm text-red-700 dark:text-red-300 mb-4">Tindakan ini bersifat permanen dan tidak dapat dibatalkan.</p>
            <button onclick="showDeleteAccountModal()" 
                    class="rounded-lg bg-red-600 dark:bg-red-700/80 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:hover:bg-red-600 transition border border-transparent dark:border-red-500/50">
                Hapus Akun
            </button>
        </div>
    </div>
</div>

{{-- Delete Account Modal --}}
<div id="deleteAccountModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="hideDeleteAccountModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#202222] rounded-lg border border-transparent dark:border-[#2F3030] shadow-xl max-w-md w-full p-6" onclick="event.stopPropagation()">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Hapus Akun?</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Tindakan ini <strong class="text-red-600 dark:text-red-400">tidak dapat dibatalkan</strong>. Semua data akan dihapus permanen.
            </p>
            <input type="password" 
                   id="deletePassword" 
                   placeholder="Password"
                   class="w-full rounded-lg border border-gray-300 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-4 py-2.5 text-sm mb-4 focus:border-red-500 dark:focus:border-red-400 focus:outline-none focus:ring-1 focus:ring-red-500 dark:focus:ring-red-400 dark:text-gray-100">
            <div class="flex gap-3">
                <button onclick="hideDeleteAccountModal()" 
                        class="flex-1 rounded-lg border border-gray-300 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#2F3030] transition">
                    Batal
                </button>
                <button onclick="confirmDeleteAccount()" 
                        class="flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition">
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Clear Chat History Modal --}}
<div id="clearHistoryModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="hideClearHistoryModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#202222] rounded-lg border border-transparent dark:border-[#2F3030] shadow-xl max-w-md w-full p-6" onclick="event.stopPropagation()">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <i data-lucide="trash-2" class="h-5 w-5 text-red-600 dark:text-red-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Hapus Riwayat Chat?</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                Semua riwayat chat bakal kehapus <strong class="text-red-600 dark:text-red-400">permanen</strong> dan gak bisa dibalikin. Yakin nih?
            </p>
            <div class="flex gap-3">
                <button onclick="hideClearHistoryModal()" 
                        class="flex-1 rounded-lg border border-gray-300 dark:border-[#2F3030] bg-white dark:bg-[#191A1A] px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#2F3030] transition">
                    Batal
                </button>
                <button onclick="executeClearHistory()" 
                        class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700 transition">
                    Ya, Hapus Semua
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Load settings from localStorage
const savedDarkMode = localStorage.getItem('nusa-dark-mode') === 'true' || localStorage.getItem('darkMode') === 'true';
const savedSettings = JSON.parse(localStorage.getItem('nusaSettings') || localStorage.getItem('nusa-settings') || '{}');

console.log('Loaded settings:', savedSettings);

if (savedDarkMode) {
    document.documentElement.classList.add('dark');
    updateToggleVisual(document.getElementById('darkModeToggle'), true);
}

if (savedSettings.auto_save !== undefined) {
    updateToggleVisual(document.getElementById('autoSaveToggle'), savedSettings.auto_save);
} else {
    // Default: auto_save = true
    updateToggleVisual(document.getElementById('autoSaveToggle'), true);
}

if (savedSettings.streaming !== undefined) {
    updateToggleVisual(document.getElementById('streamingToggle'), savedSettings.streaming);
} else {
    // Default: streaming = true
    updateToggleVisual(document.getElementById('streamingToggle'), true);
}

// Model selector
const modelSelect = document.getElementById('defaultModel');
const savedModel = localStorage.getItem('nusa-default-model');
if (savedModel && Array.from(modelSelect.options).some(opt => opt.value === savedModel)) {
    modelSelect.value = savedModel;
}

function updateToggleVisual(toggle, enabled) {
    if (!toggle) return;
    
    const dot = toggle.querySelector('.toggle-dot');
    if (enabled) {
        toggle.classList.remove('bg-gray-200', 'dark:bg-[#3F4040]');
        toggle.classList.add('bg-gray-900', 'dark:bg-emerald-500');
        dot.classList.remove('translate-x-0');
        dot.classList.add('translate-x-5');
    } else {
        toggle.classList.remove('bg-gray-900', 'dark:bg-emerald-500');
        toggle.classList.add('bg-gray-200', 'dark:bg-[#3F4040]');
        dot.classList.remove('translate-x-5');
        dot.classList.add('translate-x-0');
    }
}

function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('nusa-dark-mode', isDark.toString());
    localStorage.setItem('darkMode', isDark.toString());
    
    const toggle = document.getElementById('darkModeToggle');
    updateToggleVisual(toggle, isDark);
    
    console.log(`Dark mode: ${isDark}`);
}

function toggleSetting(key, toggleElement) {
    const isChecked = toggleElement.classList.contains('bg-gray-900') || toggleElement.classList.contains('dark:bg-emerald-500');
    const newValue = !isChecked;
    
    updateToggleVisual(toggleElement, newValue);
    
    // Merge dengan settings yang sudah ada
    const currentSettings = JSON.parse(localStorage.getItem('nusaSettings') || '{}');
    currentSettings[key] = newValue;
    localStorage.setItem('nusaSettings', JSON.stringify(currentSettings));
    
    console.log(`Saved ${key}: ${newValue}`);
}

function saveDefaultModel(e) {
    const model = modelSelect ? modelSelect.value : 'qwen-3.5-flash';
    localStorage.setItem('nusa-default-model', model);
    
    const btn = (e && e.target) ? e.target : document.getElementById('saveModelBtn') || (window.event ? window.event.target : null);
    if (btn) {
        const originalText = btn.textContent;
        btn.textContent = '✓ Tersimpan!';
        btn.classList.add('bg-green-600', 'dark:bg-green-600', 'border-transparent');
        btn.classList.remove('bg-gray-900', 'dark:bg-[#2F3030]', 'dark:border-gray-600');
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.classList.remove('bg-green-600', 'dark:bg-green-600', 'border-transparent');
            btn.classList.add('bg-gray-900', 'dark:bg-[#2F3030]', 'dark:border-gray-600');
        }, 1500);
    }
}

async function clearAllChatHistory() {
    document.getElementById('clearHistoryModal').classList.remove('hidden');
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function hideClearHistoryModal() {
    document.getElementById('clearHistoryModal').classList.add('hidden');
}

async function executeClearHistory() {
    try {
        const response = await fetch('/api/chat/history', {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });
        
        const data = await response.json();
        
        if (data.sessions && data.sessions.length > 0) {
            const deletePromises = data.sessions.map(session => 
                fetch(`/api/chat/session/${session.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                })
            );
            
            await Promise.all(deletePromises);
            
            hideClearHistoryModal();
            window.location.reload();
        } else {
            hideClearHistoryModal();
            alert('Gak ada riwayat chat yang bisa dihapus.');
        }
    } catch (error) {
        console.error('Failed to clear chat history:', error);
        hideClearHistoryModal();
        alert('Gagal menghapus riwayat chat.');
    }
}

async function exportAllData() {
    try {
        const response = await fetch('/api/chat/history', {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });
        
        const data = await response.json();
        
        const exportData = {
            exported_at: new Date().toISOString(),
            user: {
                name: '{{ auth()->user()->name }}',
                email: '{{ auth()->user()->email }}',
            },
            sessions: data.sessions || [],
        };
        
        const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `nusa-data-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Failed to export data:', error);
        alert('Gagal export data.');
    }
}

function showDeleteAccountModal() {
    document.getElementById('deleteAccountModal').classList.remove('hidden');
}

function hideDeleteAccountModal() {
    document.getElementById('deleteAccountModal').classList.add('hidden');
    document.getElementById('deletePassword').value = '';
}

async function confirmDeleteAccount() {
    const password = document.getElementById('deletePassword').value;
    
    if (!password) {
        alert('Masukkan password untuk konfirmasi.');
        return;
    }
    
    try {
        const response = await fetch('/api/settings/delete-account', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ password }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = '/login';
        } else {
            alert(data.message || 'Gagal menghapus akun.');
        }
    } catch (error) {
        console.error('Failed to delete account:', error);
        alert('Terjadi kesalahan.');
    }
}

if (typeof lucide !== 'undefined') {
    setTimeout(() => lucide.createIcons(), 100);
}

window.toggleDarkMode = toggleDarkMode;
window.toggleSetting = toggleSetting;
window.saveDefaultModel = saveDefaultModel;
window.clearAllChatHistory = clearAllChatHistory;
window.hideClearHistoryModal = hideClearHistoryModal;
window.executeClearHistory = executeClearHistory;
window.exportAllData = exportAllData;
window.showDeleteAccountModal = showDeleteAccountModal;
window.hideDeleteAccountModal = hideDeleteAccountModal;
window.confirmDeleteAccount = confirmDeleteAccount;
window.updateToggleVisual = updateToggleVisual;
</script>
@endsection
