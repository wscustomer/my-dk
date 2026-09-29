<?php

namespace App\Http\Controllers;

use App\Models\Klien;
use App\Models\Pengaturan;
use App\Models\Proyek;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Portal klien — publik, hanya baca, token acak, tanpa data internal/keuangan.
 */
class PortalController extends Controller
{
    public function lihat(string $token): Response
    {
        $proyek = Proyek::query()
            ->with(['klien:id,nama,perusahaan', 'tahapan' => fn ($q) => $q->orderBy('urutan')])
            ->where('portal_token', $token)->first();

        abort_if($proyek === null, 404);

        $tahapan = $proyek->tahapan->map(fn ($t) => [
            'nama' => $t->nama,
            'urutan' => $t->urutan,
            'status' => $t->status,
            'label_status' => $t->label_status,
            'tgl_teks' => ($t->tgl_selesai ?? $t->tgl_mulai)?->translatedFormat('d M Y'),
            'catatan' => $t->catatan,
        ])->values();

        return Inertia::render('Portal', [
            'perusahaan' => Pengaturan::nilai('nama_perusahaan', 'Digital Konsultan'),
            'warna' => Pengaturan::nilai('warna_utama', '#2196f3'),
            'proyek' => [
                'nama' => $proyek->nama,
                'jenis' => $proyek->label_jenis,
                'status' => $proyek->label_status,
                'bersih' => in_array($proyek->status, ['kualitas', 'revisi', 'selesai'], true),
                'klien' => $proyek->klien?->nama,
                'tgl_serah' => $proyek->tgl_serah?->translatedFormat('d M Y'),
                'persen' => $proyek->persen,
                'tahap' => $proyek->tahap_sekarang,
                'tahapan' => $tahapan,
                'revisi' => $proyek->revisi,
                'tautan' => $proyek->status === 'selesai' ? $proyek->tautan_hasil : null,
            ],
        ]);
    }
}
