<?php

namespace App\Http\Controllers;

use App\Models\Klien;
use App\Models\Pengaturan;
use App\Models\Proyek;
use App\Models\Tagihan;
use App\Models\TagihanBayar;
use App\Support\Uang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TagihanController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $klienId = (int) $request->query('klien', 0);
        $proyekId = (int) $request->query('proyek', 0);
        $urutan = (string) $request->query('urutan', 'tempo');

        $daftar = Tagihan::query()
            ->with(['klien:id,nama', 'proyek:id,nama', 'pembayaran'])
            ->when($q !== '', fn ($qb) => $qb->where('judul', 'like', "%{$q}%"))
            ->when($klienId > 0, fn ($qb) => $qb->where('klien_id', $klienId))
            ->when($proyekId > 0, fn ($qb) => $qb->where('proyek_id', $proyekId))
            ->when(in_array($status, ['draft', 'terkirim', 'lunas', 'batal'], true), fn ($qb) => $qb->where('status', $status))
            ->when($status === 'sebagian', fn ($qb) => $qb->where('status', 'terkirim')->whereHas('pembayaran'))
            ->when($status === 'lewat', fn ($qb) => $qb->whereIn('status', ['terkirim'])
                ->whereDate('tgl_jatuh_tempo', '<', now()->toDateString())
                ->whereRaw('total > (select coalesce(sum(nominal),0) from tagihan_bayar where tagihan_bayar.tagihan_id = tagihan.id)'))
            ->when($status === 'piutang', fn ($qb) => $qb->whereIn('status', ['terkirim'])
                ->whereRaw('total > (select coalesce(sum(nominal),0) from tagihan_bayar where tagihan_bayar.tagihan_id = tagihan.id)'))
            ->orderByRaw($urutan === 'nilai' ? 'total desc' : 'tgl_jatuh_tempo is null, tgl_jatuh_tempo asc')
            ->paginate(25)->withQueryString();

        $semua = Tagihan::query()->where('status', '!=', 'batal')->with('pembayaran')->get();
        $ringkas = [
            'total_nilai' => Uang::format($semua->sum(fn (Tagihan $t) => (float) $t->total)),
            'terbayar' => Uang::format($semua->sum(fn (Tagihan $t) => (float) $t->terbayar)),
            'piutang' => Uang::format($semua->sum(fn (Tagihan $t) => $t->sisa)),
            'lewat' => $semua->filter(fn (Tagihan $t) => $t->state === 'lewat')->count(),
        ];

        return Inertia::render('Tagihan/Index', [
            'daftar' => $daftar->through(fn (Tagihan $t) => $this->isi($t)),
            'ringkas' => $ringkas,
            'filter' => ['q' => $q, 'status' => $status, 'klien' => $klienId, 'proyek' => $proyekId, 'urutan' => $urutan],
            'opsi' => [
                'klien' => Klien::query()->orderBy('nama')->get(['id', 'nama']),
                'proyek' => Proyek::query()->orderBy('nama')->get(['id', 'nama', 'klien_id']),
            ],
            'rekening' => Pengaturan::nilai('rekening_bank'),
        ]);
    }

    public function formBaru(Request $request): Response
    {
        return Inertia::render('Tagihan/Form', [
            'tagihan' => null,
            'awal' => [
                'klien_id' => (int) $request->query('klien', 0) ?: null,
                'proyek_id' => (int) $request->query('proyek', 0) ?: null,
            ],
            'opsi' => $this->opsi(),
        ]);
    }

    public function formUbah(Tagihan $tagihan): Response
    {
        return Inertia::render('Tagihan/Form', [
            'tagihan' => $this->isi($tagihan->load('pembayaran')),
            'awal' => [],
            'opsi' => $this->opsi(),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $data['dibuat_oleh'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'draft';

        $tagihan = Tagihan::create($data);

        return redirect()->route('tagihan', ['klien' => $tagihan->klien_id])
            ->with('ok', 'Tagihan dibuat.');
    }

    public function perbarui(Request $request, Tagihan $tagihan): RedirectResponse
    {
        $data = $this->validasi($request);

        if ((float) $data['total'] < $tagihan->terbayar) {
            return back()->with('galat', 'Total lebih kecil dari yang sudah dibayar ('.$tagihan->terbayar_teks.').');
        }

        $tagihan->update($data);

        return redirect()->route('tagihan')->with('ok', 'Tagihan diperbarui.');
    }

    public function bayar(Request $request, Tagihan $tagihan): RedirectResponse
    {
        $data = $request->validate([
            'nominal' => ['required', 'numeric', 'min:1', 'max:99999999999'],
            'tgl' => ['required', 'date'],
            'metode' => ['required', Rule::in(array_keys(TagihanBayar::METODE))],
            'referensi' => ['nullable', 'string', 'max:120'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        $sisa = $tagihan->sisa;
        if ((float) $data['nominal'] > $sisa + 0.001) {
            return back()->with('galat', 'Nominal melebihi sisa tagihan ('.Uang::format($sisa).').');
        }

        DB::transaction(function () use ($tagihan, $data, $request) {
            // TagihanBayar::booted() sudah menyinkronkan cache + status tagihan.
            $tagihan->pembayaran()->create($data + ['user_id' => $request->user()->id]);
        });

        return back()->with('ok', 'Pembayaran dicatat.');
    }

    public function hapusBayar(Tagihan $tagihan, TagihanBayar $bayar): RedirectResponse
    {
        abort_unless($bayar->tagihan_id === $tagihan->id, 404);

        $bayar->delete();
        $tagihan->refresh()->hitungStatus();

        return back()->with('ok', 'Pembayaran dibatalkan.');
    }

    public function batal(Tagihan $tagihan): RedirectResponse
    {
        if ($tagihan->terbayar > 0) {
            return back()->with('galat', 'Tagihan sudah ada pembayaran. Batalkan pembayarannya dulu.');
        }

        $tagihan->update(['status' => 'batal']);

        return back()->with('ok', 'Tagihan dibatalkan.');
    }

    public function hapus(Tagihan $tagihan): RedirectResponse
    {
        if ($tagihan->terbayar > 0) {
            return back()->with('galat', 'Tagihan sudah ada pembayaran — tidak bisa dihapus.');
        }

        $tagihan->delete();

        return back()->with('ok', 'Tagihan dihapus.');
    }

    public function unduh(Request $request): StreamedResponse
    {
        $daftar = Tagihan::query()->with(['klien:id,nama', 'proyek:id,nama', 'pembayaran'])
            ->orderByDesc('created_at')->get();

        return response()->streamDownload(function () use ($daftar) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Judul', 'Klien', 'Proyek', 'Total', 'Terbayar', 'Sisa', 'Status', 'Jatuh Tempo', 'Tgl Bayar Lunas']);
            foreach ($daftar as $t) {
                fputcsv($out, [
                    $t->judul, $t->klien?->nama, $t->proyek?->nama, $t->total, $t->terbayar,
                    $t->sisa, $t->label_state, $t->tgl_jatuh_tempo?->toDateString(), $t->tgl_bayar?->toDateString(),
                ]);
            }
            fclose($out);
        }, 'tagihan-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function opsi(): array
    {
        return [
            'klien' => Klien::query()->orderBy('nama')->get(['id', 'nama']),
            'proyek' => Proyek::query()->orderBy('nama')->get(['id', 'nama', 'klien_id', 'nilai_kontrak']),
            'status' => Tagihan::STATUS,
            'metode' => TagihanBayar::METODE,
        ];
    }

    private function isi(Tagihan $t): array
    {
        return [
            'id' => $t->id,
            'judul' => $t->judul,
            'klien_id' => $t->klien_id,
            'klien' => $t->klien?->nama,
            'proyek_id' => $t->proyek_id,
            'proyek' => $t->proyek?->nama,
            'total' => (float) $t->total,
            'total_teks' => $t->total_teks,
            'terbayar' => (float) $t->terbayar,
            'terbayar_teks' => $t->terbayar_teks,
            'sisa' => $t->sisa,
            'sisa_teks' => $t->sisa_teks,
            'status' => $t->status,
            'state' => $t->state,
            'label_state' => $t->label_state,
            'lunas' => $t->lunas,
            'warna_state' => $t->warna_state,
            'tgl_jatuh_tempo' => $t->tgl_jatuh_tempo?->toDateString(),
            'tgl_teks' => $t->tgl_jatuh_tempo?->translatedFormat('d M Y'),
            'tgl_bayar' => $t->tgl_bayar?->toDateString(),
            'catatan' => $t->catatan,
            'hari_lewat' => $t->state === 'lewat' ? (int) $t->tgl_jatuh_tempo?->diffInDays(now()) : 0,
            'pembayaran' => $t->pembayaran->sortBy('tgl')->values()->map(fn (TagihanBayar $b) => [
                'id' => $b->id,
                'nominal' => (float) $b->nominal,
                'nominal_teks' => $b->nominal_teks,
                'tgl' => $b->tgl?->toDateString(),
                'tgl_teks' => $b->tgl?->translatedFormat('d M Y'),
                'metode' => $b->metode,
                'label_metode' => $b->label_metode,
                'referensi' => $b->referensi,
                'catatan' => $b->catatan,
            ])->values(),
        ];
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'klien_id' => ['required', Rule::exists('klien', 'id')],
            'proyek_id' => ['nullable', Rule::exists('proyek', 'id')],
            'judul' => ['required', 'string', 'max:180'],
            'total' => ['required', 'numeric', 'min:0.01', 'max:99999999999'],
            'tgl_jatuh_tempo' => ['nullable', 'date'],
            'tgl_terbit' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['draft', 'terkirim', 'batal'])],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
