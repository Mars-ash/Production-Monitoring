<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Tampilkan daftar user.
     */
    public function index(): View
    {
        $users = User::orderBy('id', 'desc')->paginate(10);

        return view('users.index', compact('users'));
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $correlationId = uniqid('user_create_', true);

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:5', 'confirmed'],
            'role' => ['required', 'in:admin,viewer'],
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        Log::info('UserController: User baru dibuat', [
            'correlationId' => $correlationId,
            'actor_id' => $request->user()?->id,
            'new_username' => $user->username,
            'role' => $user->role,
        ]);

        return redirect()->route('users.index')
            ->with('success', "User {$user->username} berhasil ditambahkan!");
    }

    /**
     * Hapus user secara permanen.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $correlationId = uniqid('user_del_', true);

        // Cegah user menghapus dirinya sendiri
        if ($request->user()->id === $user->id) {
            return redirect()->route('users.index')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $username = $user->username;
        $user->delete();

        Log::info('UserController: User dihapus', [
            'correlationId' => $correlationId,
            'actor_id' => $request->user()?->id,
            'deleted_username' => $username,
        ]);

        return redirect()->route('users.index')
            ->with('success', "User {$username} berhasil dihapus.");
    }
}
