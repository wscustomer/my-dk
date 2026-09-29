<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'peran' => $user->peran,
                    'inisial' => $user->inisial,
                    'label_peran' => $user->label_peran,
                ] : null,
                'admin' => $user?->peran === 'admin',
            ],
            'menu' => $this->menu($request),
            'flash' => [
                'ok' => fn () => $request->session()->get('ok'),
                'galat' => fn () => $request->session()->get('galat'),
            ],
        ];
    }

    /** Menu sidebar beserta penghitungnya — dipakai layout. */
    private function menu(Request $request): array
    {
        if (! $request->user()) {
            return [];
        }

        return [
            ['label' => 'Dasbor', 'ikon' => '◎', 'rute' => 'dasbor', 'cocok' => 'dasbor'],
            ['label' => 'Klien', 'ikon' => '★', 'rute' => 'klien', 'cocok' => 'klien*'],
            ['label' => 'Proyek', 'ikon' => '▣', 'rute' => 'proyek', 'cocok' => 'proyek*'],
            ['label' => 'Tagihan', 'ikon' => '▤', 'rute' => 'tagihan', 'cocok' => 'tagihan*'],
            ['label' => 'Pengaturan', 'ikon' => '⚙', 'rute' => 'pengaturan', 'cocok' => 'pengaturan*', 'admin' => true],
        ];
    }
}
