<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use App\Models\TemplateTahapan;
use App\Support\Uang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PengaturanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Pengaturan/Index', [
            'nilai' => Pengaturan::query()->orderBy('kunci')->get()
                ->sortBy([fn (Pengaturan $p) => $p->grup, fn (Pengaturan $p) => $p->kunci])
                ->values()->map(fn (Pengaturan $p) => [
                    'kunci' => $p->kunci,
                    'nilai' => $p->nilai,
                    'nilai_teks' => $p->nilai_format,
                    'keterangan' => $p->keterangan,
                    'grup' => $p->grup,
                    'tipe' => $p->tipe,
                ]),
            'persen_dp' => (float) Pengaturan::nilai('dp_persen_default', 50),
            'persen_dp_teks' => Pengaturan::nilai('dp_persen_default', 50).'%',
            'contoh_harga' => Uang::format(12500000),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_perusahaan' => ['required', 'string', 'max:120'],
            'email_perusahaan' => ['nullable', 'email', 'max:180'],
            'telepon_perusahaan' => ['nullable', 'string', 'max:40'],
            'warna_utama' => ['required', 'string', 'max:9'],
            'dp_persen_default' => ['required', 'numeric', 'min:0', 'max:100'],
            'peran_boleh_lihat_nilai' => ['required', Rule::in(['admin', 'staf', 'semua'])],
        ]);

        Pengaturan::simpanBanyak($data);

        return back()->with('ok', 'Pengaturan tersimpan.');
    }

    public function tahapan(): Response
    {
        return Inertia::render('Pengaturan/Tahapan', [
            'tahapan' => TemplateTahapan::query()->orderBy('jenis')->orderBy('urutan')->get()
                ->map(fn (TemplateTahapan $t) => [
                    'id' => $t->id,
                    'jenis' => $t->jenis,
                    'label_jenis' => TemplateTahapan::JENIS[$t->jenis] ?? $t->jenis,
                    'urutan' => $t->urutan,
                    'nama' => $t->nama,
                    'aktif' => $t->aktif,
                ]),
            'jenis' => TemplateTahapan::JENIS,
        ]);
    }

    public function simpanTahapan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'jenis' => ['required', Rule::in(array_keys(TemplateTahapan::JENIS))],
            'urutan' => ['required', 'integer', 'min:1', 'max:60'],
            'nama' => ['required', 'string', 'max:120'],
            'aktif' => ['boolean'],
            'hapus' => ['nullable', 'integer'],
        ]);

        if (! empty($data['hapus'])) {
            TemplateTahapan::query()->whereKey($data['hapus'])->delete();
        } elseif (! empty($data['id'])) {
            TemplateTahapan::query()->whereKey($data['id'])->update([
                'jenis' => $data['jenis'], 'urutan' => $data['urutan'],
                'nama' => $data['nama'], 'aktif' => $request->boolean('aktif', true),
            ]);
        } else {
            TemplateTahapan::updateOrCreate(
                ['jenis' => $data['jenis'], 'urutan' => $data['urutan']],
                ['nama' => $data['nama'], 'aktif' => $request->boolean('aktif', true)]
            );
        }

        return back()->with('ok', 'Template tahapan tersimpan.');
    }
}
