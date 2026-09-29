<?php

namespace App\Http\Controllers;

use App\Models\Proyek;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman dasbor. Semua angka dihitung di DasborDataController supaya
 * tampilan awal dan penyegaran berkala (tick 10 detik) tidak pernah berbeda.
 */
class DasborController extends Controller
{
    public function index(Request $request): Response
    {
        $muatan = app(DasborDataController::class)->__invoke($request)->getData(true);

        return Inertia::render('Dasbor', [
            'kartu' => $muatan['kartu'],
            'perlu' => $muatan['perlu_tindakan'],
            'linimasa' => $muatan['linimasa'],
            'waktu' => $muatan['waktu'],
            'status' => Proyek::STATUS,
            'statusJumlah' => $muatan['status_jumlah'],
        ]);
    }
}
