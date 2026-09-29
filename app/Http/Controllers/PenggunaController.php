<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PenggunaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Pengaturan/Pengguna', [
            'daftar' => User::query()->orderByDesc('aktif')->orderBy('name')->get()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'peran' => $u->peran,
                'label_peran' => $u->label_peran,
                'aktif' => $u->aktif,
                'inisial' => $u->inisial,
                'dibuat' => $u->created_at?->translatedFormat('d M Y'),
            ]),
            'peran' => User::PERAN,
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', Password::min(10)],
            'peran' => ['required', Rule::in(array_keys(User::PERAN))],
        ]);

        User::create($data + ['aktif' => true]);

        return back()->with('ok', "Pengguna {$data['name']} dibuat.");
    }

    public function formUbah(User $user): Response
    {
        return Inertia::render('Pengaturan/PenggunaForm', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'peran' => $user->peran,
                'aktif' => $user->aktif,
            ],
            'peran' => User::PERAN,
        ]);
    }

    public function perbarui(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', Password::min(10)],
            'peran' => ['required', Rule::in(array_keys(User::PERAN))],
            'aktif' => ['boolean'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Admin terakhir yang aktif tidak boleh diturunkan/dinonaktifkan.
        if (($data['peran'] !== 'admin' || ! $request->boolean('aktif', true)) && $user->peran === 'admin') {
            $adminAktif = User::query()->where('peran', 'admin')->where('aktif', true)->whereKeyNot($user->id)->count();
            if ($adminAktif === 0) {
                return back()->with('galat', 'Ini admin aktif terakhir — jangan diturunkan/dinonaktifkan.');
            }
        }

        $user->update($data + ['aktif' => $request->boolean('aktif')]);

        return redirect()->route('pengguna')->with('ok', 'Pengguna diperbarui.');
    }
}
