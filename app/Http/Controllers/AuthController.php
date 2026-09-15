<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login e-Renja.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Proses autentikasi user berdasarkan NIP/Username.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username_nip' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username_nip.required' => 'NIP / Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (Auth::attempt(['username_nip' => $credentials['username_nip'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'username_nip' => 'NIP/Username atau password yang dimasukkan tidak cocok.',
        ])->onlyInput('username_nip');
    }

    /**
     * Logout pengguna dari aplikasi.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah keluar dari aplikasi.');
    }
}
