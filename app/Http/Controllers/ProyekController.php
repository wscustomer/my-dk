<?php

namespace App\Http\Controllers;

use App\Models\Aktivitas;
use App\Models\Klien;
use App\Models\Lampiran;
use App\Models\Proyek;
use App\Models\TemplateTahapan;
use App\Models\User;
use App\Support\Uang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProyekController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $jenis = (string) $request->query('jenis', '');
        $klienId = (int) $request->query('klien', 0);
        $papan = $request->boolean('papan');
        $urutan = (string) $request->query('urutan', 'target');

        $query = Proyek::query()
            ->with(['klien:id,nama', 'tahapan:id,proyek_id,nama,urutan,status'])
            ->when($q !== '', fn ($qb) => $qb->where(function ($w) use ($q) {
                $w->where('nama', 'like', "%{$q}%")->orWhere('kode', 'like', "%{$q}%");
            }))
            ->when($status !== '', fn ($qb) => $qb->where('status', $status))
            ->when($jenis !== '', fn ($qb) => $qb->where('jenis', $jenis))
            ->when($klienId > 0, fn ($qb) => $qb->where('klien_id', $klienId))
            ->when(! $papan, fn ($qb) => $qb->when(
                $urutan === 'nilai',
                fn ($w) => $w->orderByDesc('nilai_kontrak'),
                fn ($w) => $w->orderByRaw('tgl_target is null, tgl_target asc')
            ));

        $daftar = $papan
            ? $query->orderBy('urutan_papan')->orderByDesc('updated_at')->get()
            : $query->paginate(25)->withQueryString();

        $isi = fn (Proyek $p) => [
            'id' => $p->id,
            'kode' => $p->kode,
            'nama' => $p->nama,
            'jenis' => $p->jenis,
            'label_jenis' => $p->label_jenis,
            'status' => $p->status,
            'label_status' => $p->label_status,
            'klien_id' => $p->klien_id,
            'klien' => $p->klien?->nama,
            'nilai_kontrak' => (float) $p->nilai_kontrak,
            'nilai_teks' => $p->nilai_teks,
            'persen' => $p->persen,
            'tahap' => $p->tahap_sekarang,
            'tahapan_jumlah' => $p->tahapan->count(),
            'tahapan_selesai' => $p->tahapan->where('status', 'selesai')->count(),
            'tgl_mulai' => $p->tgl_mulai?->toDateString(),
            'tgl_target' => $p->tgl_target?->toDateString(),
            'tgl_target_teks' => $p->tgl_target?->translatedFormat('d M Y'),
            'telat' => $p->tgl_target?->isPast() && ! in_array($p->status, ['selesai', 'batal'], true),
            'papan' => collect(Proyek::PAPAN)->get($p->status),
        ];

        return Inertia::render('Proyek/Index', [
            'daftar' => $papan ? $isi2($query->get(), $isi) : $daftar->through($isi),
            'papan' => $papan,
            'kolom' => collect(Proyek::PAPAN)->map(fn ($label, $kunci) => ['kunci' => $kunci, 'label' => $label])->values(),
            'filter' => ['q' => $q, 'status' => $status, 'jenis' => $jenis, 'klien' => $klienId, 'papan' => $papan, 'urutan' => $urutan],
            'opsi' => [
                'status' => Proyek::STATUS,
                'jenis' => Proyek::JENIS,
                'klien' => Klien::query()->orderBy('nama')->get(['id', 'nama']),
                'staf' => User::query()->where('aktif', true)->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function lihat(Proyek $proyek): Response
    {
        $proyek->load([
            'klien', 'tahapan' => fn ($q) => $q->orderBy('urutan'),
            'tagihan' => fn ($q) => $q->orderByDesc('created_at'),
            'aktivitas' => fn ($q) => $q->orderByDesc('tgl')->orderByDesc('id')->with('user:id,name'),
            'lampiran', 'pemilik:id,name',
        ]);

        return Inertia::render('Proyek/Lihat', [
            'proyek' => [
                'id' => $proyek->id,
                'kode' => $proyek->kode,
                'nama' => $proyek->nama,
                'deskripsi' => $proyek->deskripsi,
                'jenis' => $proyek->jenis,
                'label_jenis' => $proyek->label_jenis,
                'status' => $proyek->status,
                'label_status' => $proyek->label_status,
                'klien' => $proyek->klien?->nama,
                'klien_id' => $proyek->klien_id,
                'pemilik' => $proyek->pemilik?->name,
                'nilai_teks' => $proyek->nilai_teks,
                'nilai_kontrak' => (float) $proyek->nilai_kontrak,
                'dp_nominal' => (float) $proyek->dp_nominal,
                'dp_teks' => Uang::format($proyek->dp_nominal),
                'tgl_mulai' => $proyek->tgl_mulai?->toDateString(),
                'tgl_target' => $proyek->tgl_target?->toDateString(),
                'tgl_serah' => $proyek->tgl_serah?->toDateString(),
                'persen' => $proyek->persen,
                'tahap' => $proyek->tahap_sekarang,
                'portal_token' => $proyek->portal_token,
                'portal_aktif' => $proyek->portal_aktif,
                'catatan' => $proyek->catatan,
                'nilai_tertagih' => $proyek->nilai_tertagih_teks,
                'terbayar' => $proyek->terbayar_teks,
                'sisa_tagihan' => $proyek->sisa_tagihan_teks,
                'tahapan' => $proyek->tahapan->map(fn ($t) => [
                    'id' => $t->id,
                    'urutan' => $t->urutan,
                    'nama' => $t->nama,
                    'status' => $t->status,
                    'label_status' => $t->label_status,
                    'tgl_mulai' => $t->tgl_mulai?->toDateString(),
                    'tgl_selesai' => $t->tgl_selesai?->toDateString(),
                    'tgl_teks' => ($t->tgl_selesai ?? $t->tgl_mulai)?->translatedFormat('d M Y'),
                    'catatan' => $t->catatan,
                ])->values(),
                'tagihan' => $proyek->tagihan->map(fn ($t) => [
                    'id' => $t->id,
                    'judul' => $t->judul,
                    'total_teks' => $t->total_teks,
                    'terbayar_teks' => $t->terbayar_teks,
                    'sisa_teks' => $t->sisa_teks,
                    'state' => $t->state,
                    'label_state' => $t->label_state,
                    'warna_state' => $t->warna_state,
                    'lunas' => $t->lunas,
                    'tgl_teks' => $t->tgl_jatuh_tempo?->translatedFormat('d M Y'),
                ])->values(),
                'aktivitas' => $proyek->aktivitas->map(fn (Aktivitas $a) => [
                    'id' => $a->id,
                    'judul' => $a->judul,
                    'catatan' => $a->catatan,
                    'jenis' => $a->jenis,
                    'label_jenis' => $a->label_jenis,
                    'tgl' => $a->tgl?->toDateString(),
                    'tgl_teks' => $a->tgl?->translatedFormat('d M Y'),
                    'hasil' => $a->hasil,
                    'user' => $a->user?->name,
                ])->values(),
                'lampiran' => $proyek->lampiran->map(fn (Lampiran $l) => [
                    'id' => $l->id,
                    'nama' => $l->nama_asli,
                    'ukuran' => $l->ukuran_teks,
                    'jenis' => $l->jenis,
                    'diunggah' => $l->created_at?->translatedFormat('d M Y H:i'),
                ])->values(),
            ],
            'opsi' => [
                'status' => Proyek::STATUS,
                'jenis' => Proyek::JENIS,
                'jenis_aktivitas' => Aktivitas::JENIS,
                'staf' => User::query()->where('aktif', true)->orderBy('name')->get(['id', 'name']),
                'template' => TemplateTahapan::query()->orderBy('jenis')->orderBy('urutan')
                    ->get(['id', 'jenis', 'urutan', 'nama'])->groupBy('jenis'),
            ],
        ]);
    }

    public function detail(Proyek $proyek): Response
    {
        return $this->lihat($proyek);
    }

    public function formBaru(Request $request): Response
    {
        return Inertia::render('Proyek/Form', [
            'proyek' => null,
            'awal' => ['klien_id' => (int) $request->query('klien', 0) ?: null],
            'opsi' => [
                'status' => Proyek::STATUS,
                'jenis' => Proyek::JENIS,
                'klien' => Klien::query()->where('aktif', true)->orderBy('nama')->get(['id', 'nama']),
                'staf' => User::query()->where('aktif', true)->orderBy('name')->get(['id', 'name']),
                'template' => TemplateTahapan::query()->orderBy('jenis')->orderBy('urutan')
                    ->get(['id', 'jenis', 'urutan', 'nama'])->groupBy('jenis'),
            ],
        ]);
    }

    public function formUbah(Proyek $proyek): Response
    {
        return Inertia::render('Proyek/Form', [
            'proyek' => [
                'id' => $proyek->id,
                'klien_id' => $proyek->klien_id,
                'nama' => $proyek->nama,
                'jenis' => $proyek->jenis,
                'deskripsi' => $proyek->deskripsi,
                'status' => $proyek->status,
                'nilai_kontrak' => (float) $proyek->nilai_kontrak,
                'nilai_teks' => $proyek->nilai_teks,
                'dp_nominal' => (float) $proyek->dp_nominal,
                'tgl_mulai' => $proyek->tgl_mulai?->toDateString(),
                'tgl_target' => $proyek->tgl_target?->toDateString(),
                'pemilik_id' => $proyek->pemilik_id,
                'catatan' => $proyek->catatan,
            ],
            'awal' => [],
            'opsi' => [
                'status' => Proyek::STATUS,
                'jenis' => Proyek::JENIS,
                'klien' => Klien::query()->orderBy('nama')->get(['id', 'nama']),
                'staf' => User::query()->where('aktif', true)->orderBy('name')->get(['id', 'name']),
                'template' => [],
            ],
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $data['dibuat_oleh'] = $request->user()->id;

        $proyek = DB::transaction(function () use ($data) {
            $proyek = Proyek::create($data);
            $this->salinTahapan($proyek);

            return $proyek;
        });

        $proyek->aktivitas()->create([
            'judul' => 'Proyek dibuat',
            'jenis' => 'catatan',
            'tgl' => now()->toDateString(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('proyek.lihat', $proyek)->with('ok', "Proyek {$proyek->nama} dibuat.");
    }

    public function perbarui(Request $request, Proyek $proyek): RedirectResponse
    {
        $proyek->update($this->validasi($request));

        return redirect()->route('proyek.lihat', $proyek)->with('ok', 'Proyek diperbarui.');
    }

    public function hapus(Proyek $proyek): RedirectResponse
    {
        if ($proyek->tagihan()->exists()) {
            return back()->with('galat', 'Proyek punya tagihan. Hapus tagihannya dulu atau ubah status ke batal.');
        }

        $nama = $proyek->nama;
        $proyek->delete();

        return redirect()->route('proyek')->with('ok', "Proyek {$nama} dihapus.");
    }

    public function ubahStatus(Request $request, Proyek $proyek): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Proyek::STATUS))],
            'urutan_papan' => ['nullable', 'integer', 'min:0'],
            'alasan_batal' => ['nullable', 'string', 'max:500'],
        ]);

        $lama = $proyek->status;
        $ubah = ['status' => $data['status']];

        if (array_key_exists('urutan_papan', $data) && $data['urutan_papan'] !== null) {
            $ubah['urutan_papan'] = $data['urutan_papan'];
        }
        if (! empty($data['alasan_batal'])) {
            $ubah['catatan'] = trim(($proyek->catatan ? $proyek->catatan . "\n" : '') . 'Batal: ' . $data['alasan_batal']);
        }
        if ($data['status'] === 'selesai' && $proyek->tgl_serah === null) {
            $ubah['tgl_serah'] = now()->toDateString();
        }

        $proyek->update($ubah);

        if ($lama !== $data['status']) {
            $proyek->aktivitas()->create([
                'judul' => 'Status: ' . (Proyek::STATUS[$lama] ?? $lama) . ' → ' . Proyek::STATUS[$data['status']],
                'jenis' => 'perubahan_status',
                'tgl' => now()->toDateString(),
                'user_id' => $request->user()->id,
            ]);
        }

        return back()->with('ok', 'Status proyek diperbarui.');
    }

    public function ubahTahapan(Request $request, Proyek $proyek, \App\Models\ProyekTahapan $tahapan): RedirectResponse
    {
        abort_unless($tahapan->proyek_id === $proyek->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['belum', 'jalan', 'selesai', 'dilewati'])],
            'tgl_mulai' => ['nullable', 'date'],
            'tgl_selesai' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] === 'jalan' && empty($data['tgl_mulai']) && $tahapan->tgl_mulai === null) {
            $data['tgl_mulai'] = now()->toDateString();
        }
        if ($data['status'] === 'selesai' && empty($data['tgl_selesai'])) {
            $data['tgl_selesai'] = now()->toDateString();
        }

        $tahapan->update($data);

        return back()->with('ok', 'Tahapan diperbarui.');
    }

    public function catatAktivitas(Request $request, Proyek $proyek): RedirectResponse
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'jenis' => ['required', Rule::in(array_keys(Aktivitas::JENIS))],
            'tgl' => ['required', 'date'],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ]);

        $proyek->aktivitas()->create($data + [
            'user_id' => $request->user()->id,
            'klien_id' => $proyek->klien_id,
        ]);

        return back()->with('ok', 'Aktivitas dicatat.');
    }

    public function unggahLampiran(Request $request, Proyek $proyek): RedirectResponse
    {
        $request->validate([
            'berkas' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,webp,zip'],
        ]);

        $berkas = $request->file('berkas');
        $path = $berkas->store("lampiran/proyek/{$proyek->id}", 'local');

        $proyek->lampiran()->create([
            'user_id' => $request->user()->id,
            'nama_asli' => $berkas->getClientOriginalName(),
            'path' => $path,
            'mime' => $berkas->getClientMimeType(),
            'ukuran' => $berkas->getSize(),
        ]);

        return back()->with('ok', 'Lampiran diunggah.');
    }

    public function terbitPortal(Proyek $proyek): RedirectResponse
    {
        $proyek->update(['portal_token' => $proyek->portal_token ?: \Illuminate\Support\Str::random(40)]);

        return back()->with('ok', 'Portal klien aktif.');
    }

    public function cabutPortal(Proyek $proyek): RedirectResponse
    {
        $proyek->update(['portal_token' => null]);

        return back()->with('ok', 'Portal klien dicabut.');
    }

    public function unduh(Request $request): StreamedResponse
    {
        $daftar = Proyek::query()->with('klien:id,nama')->orderByDesc('created_at')->get();

        return response()->streamDownload(function () use ($daftar) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Kode', 'Nama', 'Klien', 'Jenis', 'Status', 'Nilai Kontrak', 'DP', 'Progress %', 'Tahap Kini', 'Mulai', 'Target', 'Serah']);
            foreach ($daftar as $p) {
                fputcsv($out, [
                    $p->kode, $p->nama, $p->klien?->nama, $p->label_jenis, $p->label_status,
                    $p->nilai_kontrak, $p->dp_nominal, $p->persen, $p->tahap_sekarang,
                    $p->tgl_mulai?->toDateString(), $p->tgl_target?->toDateString(), $p->tgl_serah?->toDateString(),
                ]);
            }
            fclose($out);
        }, 'proyek-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'klien_id' => ['required', Rule::exists('klien', 'id')],
            'nama' => ['required', 'string', 'max:180'],
            'jenis' => ['required', Rule::in(array_keys(Proyek::JENIS))],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(array_keys(Proyek::STATUS))],
            'nilai_kontrak' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'dp_nominal' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
            'tgl_mulai' => ['nullable', 'date'],
            'tgl_target' => ['nullable', 'date'],
            'pemilik_id' => ['nullable', Rule::exists('users', 'id')],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function salinTahapan(Proyek $proyek): void
    {
        $proyek->salinTahapan();
    }
}

/** Peta koleksi jadi array respons. */
function isi2(iterable $items, callable $fn): array
{
    $hasil = [];
    foreach ($items as $item) {
        $hasil[] = $fn($item);
    }

    return $hasil;
}
