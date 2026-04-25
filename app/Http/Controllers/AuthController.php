<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Tampilkan form login.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Proses login.
     */
    public function login(Request $request): RedirectResponse
    {
        $correlationId = uniqid('auth_', true);

        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            Log::info('AuthController: Login berhasil', [
                'correlationId' => $correlationId,
                'operation' => 'login',
                'userId' => Auth::id(),
                'username' => $credentials['username'],
            ]);

            return redirect()->intended(route('dashboard'));
        }

        Log::warning('AuthController: Login gagal', [
            'correlationId' => $correlationId,
            'operation' => 'login',
            'username' => $credentials['username'],
        ]);

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    /**
     * Proses logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        $correlationId = uniqid('auth_', true);
        $userId = Auth::id();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('AuthController: Logout', [
            'correlationId' => $correlationId,
            'operation' => 'logout',
            'userId' => $userId,
        ]);

        return redirect()->route('login');
    }
}
