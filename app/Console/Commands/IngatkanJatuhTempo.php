<?php

namespace App\Console\Commands;

use App\Models\Pengaturan;
use App\Models\Proyek;
use App\Models\Tagihan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class IngatkanJatuhTempo extends Command
{
    protected $signature = 'dk:ingatkan {--hari=3 : Hari sebelum jatuh tempo} {--kering : Cetak saja, jangan kirim}';

    protected $description = 'Kirim pengingat tagihan jatuh tempo & lewat tempo ke email pengaturan';

    public function handle(): int
    {
        $tujuan = (string) Pengaturan::nilai('email_pengingat', '');
        $hari = max(0, (int) $this->option('hari'));
        $kering = (bool) $this->option('kering');

        $lewat = Tagihan::query()->with(['klien:id,nama', 'proyek:id,nama'])
            ->where('status', 'terkirim')
            ->whereDate('tgl_jatuh_tempo', '<', now()->toDateString())
            ->get()->filter(fn (Tagihan $t) => $t->sisa > 0);

        $dekat = Tagihan::query()->with(['klien:id,nama', 'proyek:id,nama'])
            ->where('status', 'terkirim')
            ->whereBetween('tgl_jatuh_tempo', [now()->toDateString(), now()->addDays($hari)->toDateString()])
            ->get()->filter(fn (Tagihan $t) => $t->sisa > 0);

        $proyek = Proyek::query()->with('klien:id,nama')
            ->whereNotIn('status', ['selesai', 'batal'])
            ->whereNotNull('tgl_target')
            ->whereDate('tgl_target', '<=', now()->addDays($hari)->toDateString())
            ->get();

        if ($lewat->isEmpty() && $dekat->isEmpty() && $proyek->isEmpty()) {
            $this->info('Tidak ada yang perlu diingatkan.');

            return self::SUCCESS;
        }

        $baris = [];
        foreach ($lewat as $t) {
            $baris[] = "LEWAT TEMPO — {$t->judul} | {$t->klien?->nama} | sisa {$t->sisa_teks} | jatuh tempo {$t->tgl_jatuh_tempo?->format('d/m/Y')}";
        }
        foreach ($dekat as $t) {
            $baris[] = "SEGERA — {$t->judul} | {$t->klien?->nama} | sisa {$t->sisa_teks} | jatuh tempo {$t->tgl_jatuh_tempo?->format('d/m/Y')}";
        }
        foreach ($proyek as $p) {
            $baris[] = "PROYEK — {$p->nama} | {$p->klien?->nama} | target {$p->tgl_target?->format('d/m/Y')} | {$p->persen}% ({$p->tahap_sekarang})";
        }

        $teks = 'Ringkasan pengingat '.Pengaturan::nilai('nama_perusahaan', 'Digital Konsultan')."\n".now()->format('d/m/Y H:i')."\n\n".implode("\n", $baris);
        $this->line($teks);

        if ($kering) {
            $this->warn('Mode kering — email tidak dikirim.');

            return self::SUCCESS;
        }

        if ($tujuan === '') {
            $this->warn('email_pengingat belum diisi di Pengaturan — email tidak dikirim.');

            return self::SUCCESS;
        }

        try {
            Mail::raw($teks, fn ($m) => $m->to($tujuan)->subject('[CRM DK] '.count($baris).' pengingat'));
            $this->info("Email terkirim ke {$tujuan}.");
        } catch (\Throwable $e) {
            $this->error('Gagal kirim email: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
