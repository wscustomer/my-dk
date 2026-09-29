<?php

namespace App\Http\Controllers;

use App\Models\Aktivitas;
use App\Models\Klien;
use App\Models\Pengaturan;
use App\Models\Proyek;
use App\Models\ProyekTahapan;
use App\Models\Tagihan;
use App\Support\Uang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * JSON ringkas untuk penyegaran berkala dasbor (tick 10 detik).
 *
 * Nama kolom mengikuti skema nyata:
 *   proyek_tahapan : tgl_mulai / tgl_selesai (tidak ada tgl_target)
 *   proyek         : tgl_target / tgl_serah
 *   tagihan        : total / terbayar_total_hitung
 */
class DasborDataController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $bolehNilai = (bool) $request->user()?->bolehLihatNilai();
        $hariIngat = (int) Pengaturan::nilai('hari_ingat_tagihan', 7);

        // Tahap berjalan yang tgl_selesainya sudah terlewat.
        $tahapTerlambat = ProyekTahapan::query()
            ->with('proyek:id,kode,nama')
            ->whereNotIn('status', ['selesai', 'dilewati'])
            ->whereNotNull('tgl_selesai')
            ->whereDate('tgl_selesai', '<', today())
            ->orderBy('tgl_selesai')
            ->limit(10)
            ->get()
            ->map(fn (ProyekTahapan $t) => [
                'id' => $t->id,
                'nama' => $t->nama,
                'proyek_id' => $t->proyek_id,
                'proyek' => $t->proyek?->nama,
                'kode' => $t->proyek?->kode,
                'tgl_teks' => $t->tgl_selesai?->translatedFormat('d M Y'),
                'hari_lewat' => (int) $t->tgl_selesai?->startOfDay()->diffInDays(today()),
            ]);

        $proyekTerlambat = Proyek::query()
            ->with('klien:id,nama')
            ->whereNotIn('status', ['selesai', 'batal'])
            ->whereNotNull('tgl_target')
            ->whereDate('tgl_target', '<', today())
            ->orderBy('tgl_target')
            ->limit(10)
            ->get()
            ->map(fn (Proyek $p) => [
                'id' => $p->id,
                'kode' => $p->kode,
                'nama' => $p->nama,
                'klien' => $p->klien?->nama,
                'tgl_teks' => $p->tgl_target?->translatedFormat('d M Y'),
                'hari_lewat' => (int) $p->tgl_target?->startOfDay()->diffInDays(today()),
            ]);

        $jatuhTempo = Tagihan::query()
            ->with(['klien:id,nama', 'proyek:id,nama,kode'])
            ->whereNotIn('status', ['lunas', 'batal'])
            ->whereNotNull('tgl_jatuh_tempo')
            ->whereDate('tgl_jatuh_tempo', '<=', today()->addDays($hariIngat))
            ->orderBy('tgl_jatuh_tempo')
            ->limit(10)
            ->get()
            ->map(fn (Tagihan $t) => [
                'id' => $t->id,
                'judul' => $t->judul,
                'klien' => $t->klien?->nama,
                'proyek' => $t->proyek?->nama,
                'kode' => $t->proyek?->kode,
                'tgl_teks' => $t->tgl_jatuh_tempo?->translatedFormat('d M Y'),
                'sisa_teks' => $bolehNilai ? $t->sisa_teks : null,
                'hari_lewat' => $t->tgl_jatuh_tempo?->isPast()
                    ? (int) $t->tgl_jatuh_tempo->startOfDay()->diffInDays(today())
                    : 0,
            ]);

        $linimasa = Aktivitas::query()
            ->with(['user:id,name', 'klien:id,nama', 'proyek:id,nama'])
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (Aktivitas $a) => [
                'id' => $a->id,
                'judul' => $a->judul,
                'label_jenis' => Aktivitas::JENIS[$a->jenis] ?? $a->jenis,
                'user' => $a->user?->name,
                'tgl_teks' => $a->tgl?->translatedFormat('d M Y'),
                'tautan' => $a->proyek_id
                    ? '/proyek/' . $a->proyek_id
                    : ($a->klien_id ? '/klien/' . $a->klien_id . '/detail' : null),
            ]);

        return response()->json([
            'waktu' => now()->timezone(Pengaturan::nilai('zona_waktu', 'Asia/Jakarta'))->format('H:i:s'),
            'boleh_nilai' => $bolehNilai,
            'kartu' => [
                'klien_aktif' => Klien::query()->where('status', 'aktif')->count(),
                'klien_baru_bulan' => Klien::query()->where('aktif', true)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count(),
                'proyek_jalan' => Proyek::query()->whereNotIn('status', ['selesai', 'batal'])->count(),
                'nilai_jalan_teks' => $bolehNilai
                    ? Uang::rp(Proyek::query()->whereNotIn('status', ['selesai', 'batal'])->sum('nilai_kontrak'))
                    : null,
                'tahap_lewat' => $tahapTerlambat->count(),
                'jatuh_tempo' => $jatuhTempo->count(),
                'piutang_teks' => $bolehNilai ? Uang::rp(self::piutang()) : null,
            ],
            'perlu_tindakan' => [
                'proyek' => $proyekTerlambat,
                'tahapan' => $tahapTerlambat,
                'tagihan' => $jatuhTempo,
            ],
            'linimasa' => $linimasa,
        ]);
    }

    /** Σ(total − dibayar) seluruh tagihan non-batal. */
    public static function piutang(): float
    {
        return (float) DB::table('tagihan')
            ->whereNotIn('status', ['batal'])
            ->selectRaw('COALESCE(SUM(total - terbayar_total_hitung), 0) AS jumlah')
            ->value('jumlah');
    }
}
