<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    use ApiResponse;

    /**
     * Show settings page.
     */
    public function index(): View
    {
        return view('settings.index');
    }

    /**
     * Get all user preferences.
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        $preferences = UserPreference::where('user_id', $userId)
            ->get()
            ->pluck('value', 'key')
            ->toArray();

        return $this->successResponse($preferences);
    }

    /**
     * Update user preference(s) - single or multiple.
     * 
     * Accepts either:
     * - Single: {"key": "theme", "value": "dark"}
     * - Multiple: {"preferences": {"theme": "dark", "lang": "id"}}
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['nullable', 'string', 'max:100'],
            'value' => ['nullable'],
            'preferences' => ['nullable', 'array'],
        ]);

        $userId = $request->user()->id;
        
        // Handle single preference update
        if (isset($validated['key'])) {
            UserPreference::set($userId, $validated['key'], $validated['value']);
        }
        
        // Handle batch preference updates
        if (isset($validated['preferences'])) {
            foreach ($validated['preferences'] as $key => $value) {
                UserPreference::set($userId, $key, $value);
            }
        }

        return $this->successResponse(null, 'Preferensi berhasil diperbarui.');
    }

    /**
     * Delete user preference.
     */
    public function deletePreference(Request $request, string $key): JsonResponse
    {
        UserPreference::where('user_id', $request->user()->id)
            ->where('key', $key)
            ->delete();

        return $this->successResponse(null, 'Preferensi berhasil dihapus.');
    }

    /**
     * Clear all preferences.
     */
    public function clearAllPreferences(Request $request): JsonResponse
    {
        UserPreference::where('user_id', $request->user()->id)->delete();

        return $this->successResponse(null, 'Semua preferensi berhasil direset.');
    }

    /**
     * Delete user account.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        
        $user->delete();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->successResponse([
            'message' => 'Akun berhasil dihapus.',
            'redirect' => route('login'),
        ]);
    }
}
