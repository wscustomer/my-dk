<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staf tidak boleh membuka Pengaturan & Pengguna.
 * Pola: middleware peran:admin pada grup rute.
 */
class PastikanPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $user = $request->user();

        if (! $user || ! $user->aktif) {
            abort(403, 'Akun tidak aktif.');
        }

        if ($peran !== [] && ! in_array($user->peran, $peran, true)) {
            abort(403, 'Halaman ini hanya untuk ' . implode('/', $peran) . '.');
        }

        return $next($request);
    }
}
