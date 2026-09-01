<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AIStatusController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('chat')
        : redirect()->route('login');
});

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Chatbot
Route::middleware('auth')->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::post('/api/chat', [ChatController::class, 'chat'])->name('chat.send');
    Route::post('/api/chat/upload', [ChatController::class, 'uploadAttachment'])->name('chat.upload');
    Route::delete('/api/chat/attachment', [ChatController::class, 'deleteAttachment'])->name('chat.attachment.delete');

    // Chat History API
    Route::get('/api/chat/history', [ChatController::class, 'history'])->name('chat.history');
    Route::get('/api/chat/{id}', [ChatController::class, 'showChat'])->name('chat.show');
    Route::post('/api/chat/session', [ChatController::class, 'createSession'])->name('chat.session.create');
    Route::delete('/api/chat/session/{id}', [ChatController::class, 'deleteSession'])->name('chat.session.delete');

    // New Chat Management APIs
    Route::put('/api/chat/session/{id}/rename', [ChatController::class, 'renameSession'])->name('chat.session.rename');
    Route::put('/api/chat/session/{id}/pin', [ChatController::class, 'togglePinSession'])->name('chat.session.pin');
    Route::put('/api/chat/session/{id}/settings', [ChatController::class, 'updateSessionSettings'])->name('chat.session.settings.update');
    Route::get('/api/chat/session/{id}/settings', [ChatController::class, 'getSessionSettings'])->name('chat.session.settings.get');
    Route::delete('/api/chat/session/{id}/truncate/{index}', [ChatController::class, 'truncateMessages'])->name('chat.session.truncate');
    Route::delete('/api/chat/session/{sessionId}/message/{messageId}', [ChatController::class, 'deleteMessage'])->name('chat.message.delete');
    Route::get('/api/chat/session/{id}/export', [ChatController::class, 'exportSession'])->name('chat.session.export');
    Route::delete('/api/chat/sessions/all', [ChatController::class, 'clearAllSessions'])->name('chat.sessions.clear-all');

    // Search APIs
    Route::get('/api/chat/search', [ChatController::class, 'searchAllSessions'])->name('chat.search.all');
    Route::get('/api/chat/session/{id}/search', [ChatController::class, 'searchInSession'])->name('chat.search.session');

    // AI Status API
    Route::get('/api/ai/status', [AIStatusController::class, 'index'])->name('ai.status');
    Route::get('/api/ai/heartbeat', [AIStatusController::class, 'check'])->name('ai.heartbeat');
    Route::post('/api/ai/check', [AIStatusController::class, 'check'])->name('ai.check');
    Route::post('/api/ai/check/{model}', [AIStatusController::class, 'check'])->name('ai.check.model');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::get('/api/settings/preferences', [SettingsController::class, 'getPreferences'])->name('settings.preferences.get');
    Route::post('/api/settings/preferences', [SettingsController::class, 'updatePreference'])->name('settings.preferences.update');
    Route::put('/api/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences.update-batch');
    Route::delete('/api/settings/preferences/{key}', [SettingsController::class, 'deletePreference'])->name('settings.preferences.delete');
    Route::post('/api/settings/delete-account', [SettingsController::class, 'deleteAccount'])->name('settings.delete-account');
});
