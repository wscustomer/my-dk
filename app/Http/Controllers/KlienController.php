<?php

namespace App\Http\Controllers;

use App\Models\Aktivitas;
use App\Models\Klien;
use App\Models\Lampiran;
use App\Models\Proyek;
use App\Models\Tagihan;
use App\Support\Uang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KlienController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $urutan = (string) $request->query('urutan', 'baru');

        $daftar = Klien::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nama', 'like', "%{$q}%")
                        ->orWhere('perusahaan', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('telepon', 'like', "%{$q}%")
                        ->orWhere('kode', 'like', "%{$q}%");
                });
            })
            ->when($status === 'aktif', fn ($query) => $query->where('aktif', true))
            ->when($status === 'nonaktif', fn ($query) => $query->where('aktif', false))
            ->when($status === 'prospek', fn ($query) => $query->where('status', 'prospek'))
            ->withCount([
                'proyek as proyek_aktif_count' => fn ($query) => $query->whereNotIn('status', ['selesai', 'batal']),
            ])
            ->withSum(['tagihan as piutang' => fn ($query) => $query->where('status', '!=', 'batal')], 'total')
            ->orderByRaw($urutan === 'nama' ? 'nama asc' : 'created_at desc')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Klien/Index', [
            'daftar' => $daftar->through(fn (Klien $k) => [
                'id' => $k->id,
                'kode' => $k->kode,
                'nama' => $k->nama,
                'perusahaan' => $k->perusahaan,
                'email' => $k->email,
                'telepon' => $k->telepon,
                'kota' => $k->kota,
                'status' => $k->status,
                'aktif' => $k->aktif,
                'proyek_aktif' => $k->proyek_aktif_count,
                'nilai_lifetime' => Uang::format($k->nilai_lifetime),
                'sumber' => $k->sumber,
                'dibuat' => $k->created_at?->translatedFormat('d M Y'),
            ]),
            'filter' => ['q' => $q, 'status' => $status, 'urutan' => $urutan],
            'opsi' => [
                'status' => Klien::STATUS,
                'sumber' => Klien::SUMBER,
            ],
        ]);
    }

    public function detail(Klien $klien): Response
    {
        $klien->load([
            'proyek' => fn ($q) => $q->orderByDesc('created_at'),
            'tagihan' => fn ($q) => $q->orderByDesc('created_at'),
            'aktivitas' => fn ($q) => $q->orderByDesc('tgl')->orderByDesc('id'),
            'aktivitas.user:id,name',
        ]);

        return Inertia::render('Klien/Detail', [
            'klien' => [
                'id' => $klien->id,
                'kode' => $klien->kode,
                'nama' => $klien->nama,
                'perusahaan' => $klien->perusahaan,
                'email' => $klien->email,
                'telepon' => $klien->telepon,
                'alamat' => $klien->alamat,
                'kota' => $klien->kota,
                'catatan' => $klien->catatan,
                'status' => $klien->status,
                'label_status' => $klien->label_status,
                'aktif' => $klien->aktif,
                'sumber' => $klien->sumber,
                'sumber_lain' => $klien->sumber_lain,
                'whatsapp' => $klien->whatsapp,
                'nilai_lifetime' => $klien->nilai_lifetime_teks,
                'dibuat' => $klien->created_at?->translatedFormat('d M Y'),
                'proyek' => $klien->proyek->map(fn (Proyek $p) => [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'jenis' => $p->jenis,
                    'label_jenis' => $p->label_jenis,
                    'status' => $p->status,
                    'label_status' => $p->label_status,
                    'persen' => $p->persen,
                    'nilai_teks' => $p->nilai_teks,
                    'tgl_target' => $p->tgl_target?->toDateString(),
                ])->values(),
                'tagihan' => $klien->tagihan->map(fn (Tagihan $t) => [
                    'id' => $t->id,
                    'judul' => $t->judul,
                    'total_teks' => $t->total_teks,
                    'sisa_teks' => $t->sisa_teks,
                    'state' => $t->state,
                    'label_state' => $t->label_state,
                    'warna_state' => $t->warna_state,
                    'lunas' => $t->lunas,
                    'tgl_teks' => $t->tgl_jatuh_tempo?->translatedFormat('d M Y'),
                ])->values(),
                'aktivitas' => $klien->aktivitas->map(fn (Aktivitas $a) => [
                    'id' => $a->id,
                    'judul' => $a->judul,
                    'catatan' => $a->catatan,
                    'jenis' => $a->jenis,
                    'label_jenis' => $a->label_jenis,
                    'tgl' => $a->tgl?->toDateString(),
                    'tgl_teks' => $a->tgl?->translatedFormat('d M Y'),
                    'hasil' => $a->hasil,
                    'user' => $a->user?->name,
                    'proyek_id' => $a->proyek_id,
                ])->values(),
                'lampiran' => $klien->lampiran->map(fn (Lampiran $l) => [
                    'id' => $l->id,
                    'nama' => $l->nama_asli,
                    'ukuran' => $l->ukuran_teks,
                    'jenis' => $l->jenis,
                    'diunggah' => $l->created_at?->translatedFormat('d M Y H:i'),
                ])->values(),
            ],
            'opsi' => [
                'jenis_aktivitas' => Aktivitas::JENIS,
                'status' => Klien::STATUS,
                'sumber' => Klien::SUMBER,
                'proyek' => Proyek::query()->where('klien_id', $klien->id)->get(['id', 'nama']),
            ],
        ]);
    }

    public function formBaru(Request $request): Response
    {
        return Inertia::render('Klien/Form', [
            'klien' => null,
            'opsi' => [
                'status' => Klien::STATUS,
                'sumber' => Klien::SUMBER,
            ],
            'kembali' => $request->query('kembali'),
        ]);
    }

    public function formUbah(Klien $klien): Response
    {
        return Inertia::render('Klien/Form', [
            'klien' => [
                'id' => $klien->id,
                'nama' => $klien->nama,
                'perusahaan' => $klien->perusahaan,
                'email' => $klien->email,
                'telepon' => $klien->telepon,
                'alamat' => $klien->alamat,
                'kota' => $klien->kota,
                'catatan' => $klien->catatan,
                'status' => $klien->status,
                'sumber' => $klien->sumber,
                'sumber_lain' => $klien->sumber_lain,
                'aktif' => $klien->aktif,
            ],
            'opsi' => ['status' => Klien::STATUS, 'sumber' => Klien::SUMBER],
            'kembali' => null,
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $klien = Klien::create($data);
        $this->catat($klien, 'Klien dibuat');

        return redirect()->route('klien.detail', $klien)->with('ok', "Klien {$klien->nama} tersimpan.");
    }

    public function perbarui(Request $request, Klien $klien): RedirectResponse
    {
        $data = $this->validasi($request, $klien);
        $klien->update($data);

        return redirect()->route('klien.detail', $klien)->with('ok', 'Perubahan klien tersimpan.');
    }

    public function hapus(Klien $klien): RedirectResponse
    {
        if ($klien->proyek()->exists() || $klien->tagihan()->exists()) {
            return back()->with('galat', 'Klien punya proyek/tagihan. Nonaktifkan saja, jangan dihapus — riwayat tetap perlu.');
        }

        $nama = $klien->nama;
        $klien->delete();

        return redirect()->route('klien')->with('ok', "Klien {$nama} dihapus.");
    }

    public function catatAktivitas(Request $request, Klien $klien): RedirectResponse
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'jenis' => ['required', Rule::in(array_keys(Aktivitas::JENIS))],
            'tgl' => ['required', 'date'],
            'catatan' => ['nullable', 'string', 'max:5000'],
            'proyek_id' => ['nullable', Rule::exists('proyek', 'id')->where('klien_id', $klien->id)],
        ]);

        $klien->aktivitas()->create($data + ['user_id' => $request->user()->id]);

        return back()->with('ok', 'Aktivitas dicatat.');
    }

    public function unggahLampiran(Request $request, Klien $klien): RedirectResponse
    {
        $request->validate([
            'berkas' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,webp,zip'],
        ]);

        $berkas = $request->file('berkas');
        $path = $berkas->store("lampiran/klien/{$klien->id}", 'local');

        $klien->lampiran()->create([
            'user_id' => $request->user()->id,
            'nama_asli' => $berkas->getClientOriginalName(),
            'path' => $path,
            'mime' => $berkas->getClientMimeType(),
            'ukuran' => $berkas->getSize(),
        ]);

        return back()->with('ok', 'Lampiran diunggah.');
    }

    public function unduhLampiran(Lampiran $lampiran): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($lampiran->path), 404);

        return Storage::disk('local')->download($lampiran->path, $lampiran->nama_asli);
    }

    public function hapusLampiran(Lampiran $lampiran): RedirectResponse
    {
        Storage::disk('local')->delete($lampiran->path);
        $lampiran->delete();

        return back()->with('ok', 'Lampiran dihapus.');
    }

    /** Impor prospek dari dkmarketing.prospects (sumber Marketing Tools). */
    public function formImpor(): Response
    {
        $prospek = [];
        try {
            $prospek = DB::connection('dkmarketing')->table('prospects')
                ->select('id', 'nama', 'kategori', 'telp', 'wa', 'wilayah', 'kecamatan', 'situs', 'status', 'created_at')
                ->orderByDesc('created_at')->limit(300)->get()->toArray();
        } catch (\Throwable $e) {
            $prospek = [];
        }

        $ada = Klien::query()->pluck('nama')->map(fn ($n) => mb_strtolower(trim($n)))->all();

        return Inertia::render('Klien/Impor', [
            'prospek' => collect($prospek)->map(fn ($p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'perusahaan' => $p->kategori,
                'email' => null,
                'telepon' => $p->wa ?: $p->telp,
                'kota' => $p->wilayah ?: $p->kecamatan,
                'situs' => $p->situs,
                'sumber' => 'Marketing Tools',
                'status' => $p->status,
                'sudah' => in_array(mb_strtolower(trim((string) $p->nama)), $ada, true),
            ])->values(),
            'sumber' => Klien::SUMBER,
        ]);
    }

    public function impor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['required', 'array'],
            'id.*' => ['integer'],
        ]);

        $baris = DB::connection('dkmarketing')->table('prospects')
            ->whereIn('id', $data['id'])->get();

        $masuk = 0;
        foreach ($baris as $p) {
            $nama = trim((string) $p->nama);
            if ($nama === '') {
                continue;
            }
            if (Klien::query()->whereRaw('lower(nama) = ?', [mb_strtolower($nama)])->exists()) {
                continue;
            }

            // Skema Marketing Tools: telp/wa (bukan telepon), wilayah (bukan kota),
            // tak ada perusahaan/email. Kategori masuk ke catatan.
            $situs = trim((string) ($p->situs ?? ''));

            Klien::create([
                'nama' => $nama,
                'perusahaan' => $p->kategori ?: null,
                'email' => null,
                'telepon' => $p->wa ?: ($p->telp ?: null),
                'kota' => $p->wilayah ?: ($p->kecamatan ?: null),
                'status' => 'prospek',
                'sumber' => 'lain',
                'sumber_lain' => 'Impor Marketing Tools',
                'catatan' => trim(implode(' | ', array_filter([
                    $p->catatan ?: null,
                    $situs !== '' ? "Situs: $situs" : null,
                    $p->alamat ?: null,
                    $p->maps_url ?: null,
                ]))) ?: null,
            ]);
            $masuk++;
        }

        return redirect()->route('klien', ['status' => 'prospek'])
            ->with('ok', "{$masuk} prospek masuk jadi klien.");
    }

    public function unduh(Request $request): StreamedResponse
    {
        $daftar = Klien::query()->orderBy('nama')->get();

        return response()->streamDownload(function () use ($daftar) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Kode', 'Nama', 'Perusahaan', 'Email', 'Telepon', 'Kota', 'Status', 'Sumber', 'Nilai Lifetime', 'Dibuat']);
            foreach ($daftar as $k) {
                fputcsv($out, [
                    $k->kode, $k->nama, $k->perusahaan, $k->email, $k->telepon, $k->kota,
                    $k->label_status, $k->sumber, $k->nilai_lifetime, $k->created_at?->toDateString(),
                ]);
            }
            fclose($out);
        }, 'klien-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function validasi(Request $request, ?Klien $klien = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:180'],
            'perusahaan' => ['nullable', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:180'],
            'telepon' => ['nullable', 'string', 'max:40'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'kota' => ['nullable', 'string', 'max:80'],
            'catatan' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(array_keys(Klien::STATUS))],
            'sumber' => ['required', Rule::in(array_keys(Klien::SUMBER))],
            'sumber_lain' => ['nullable', 'string', 'max:80'],
            'aktif' => ['boolean'],
        ]);
    }

    private function catat(Klien $klien, string $judul): void
    {
        $klien->aktivitas()->create([
            'judul' => $judul,
            'jenis' => 'catatan',
            'tgl' => now()->toDateString(),
            'user_id' => auth()->id(),
        ]);
    }
}
