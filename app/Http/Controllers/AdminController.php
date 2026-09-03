<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'total_users'          => User::count(),
            'admin_users'          => User::where('is_admin', true)->count(),
            'total_sessions'       => ChatSession::count(),
            'total_messages'       => ChatMessage::count(),
            'new_users_today'      => User::whereDate('created_at', today())->count(),
            'active_sessions_today'=> ChatSession::whereDate('updated_at', today())->count(),
        ];

        $recent_users = User::latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recent_users'));
    }

    public function users(Request $request): View
    {
        $query = User::withCount(['chatSessions', 'preferences']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->get('filter') === 'admin') {
            $query->where('is_admin', true);
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function createUser(): View
    {
        return view('admin.users.create');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'is_admin' => ['nullable', 'boolean'],
        ], [
            'name.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.unique'       => 'Email sudah terdaftar.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $data['is_admin'] = $request->boolean('is_admin');
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('admin.users')
            ->with('success', "Akun \"{$data['name']}\" berhasil dibuat.");
    }

    public function editUser(User $user): View
    {
        $sessionCount = $user->chatSessions()->count();
        $messageCount = ChatMessage::whereIn('chat_session_id', $user->chatSessions()->pluck('id'))->count();

        return view('admin.users.edit', compact('user', 'sessionCount', 'messageCount'));
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'is_admin' => ['nullable', 'boolean'],
        ];

        $messages = [
            'name.required'  => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique'   => 'Email sudah digunakan oleh akun lain.',
        ];

        if ($request->filled('password')) {
            $rules['password']              = ['min:8', 'confirmed'];
            $messages['password.min']       = 'Password minimal 8 karakter.';
            $messages['password.confirmed'] = 'Konfirmasi password tidak cocok.';
        }

        $data = $request->validate($rules, $messages);

        $data['is_admin'] = $request->boolean('is_admin');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users')
            ->with('success', "Akun \"{$user->name}\" berhasil diperbarui.");
    }

    public function deleteUser(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;

        foreach ($user->chatSessions as $session) {
            $session->messages()->delete();
        }
        $user->chatSessions()->delete();
        $user->preferences()->delete();
        $user->delete();

        return redirect()->route('admin.users')
            ->with('success', "Akun \"{$name}\" berhasil dihapus.");
    }

    public function toggleAdmin(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat mengubah status admin Anda sendiri.');
        }

        $user->update(['is_admin' => ! $user->is_admin]);

        $status = $user->is_admin ? 'Admin' : 'User';

        return back()->with('success', "\"{$user->name}\" sekarang berstatus {$status}.");
    }

    public function sessions(Request $request): View
    {
        $query = ChatSession::with('user')->withCount('messages');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%")
                                                     ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        $sessions = $query->latest()->paginate(20)->withQueryString();
        $users    = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.sessions.index', compact('sessions', 'users'));
    }

    public function deleteSession(ChatSession $session): RedirectResponse
    {
        $title = $session->title ?? 'Untitled';
        $session->messages()->delete();
        $session->delete();

        return back()->with('success', "Sesi \"{$title}\" berhasil dihapus.");
    }
}
