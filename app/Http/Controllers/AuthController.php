<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function form(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dasbor');
        }

        return Inertia::render('Masuk');
    }

    public function masuk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($data, $request->boolean('ingat'))) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi salah.']);
        }

        if (! $request->user()->aktif) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Akun tidak aktif. Hubungi admin.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dasbor'));
    }

    public function keluar(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
