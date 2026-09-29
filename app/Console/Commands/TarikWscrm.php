<?php

namespace App\Console\Commands;

use App\Models\Klien;
use App\Models\Proyek;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Tarik data layanan dari WSCRM (/api/dk/*) — WSCRM tetap sumber kebenaran penagihan.
 * Tugas: cocokkan domain/layanan ke klien & proyek, simpan sebagai catatan. Tidak menulis balik.
 */
class TarikWscrm extends Command
{
    protected $signature = 'dk:tarik-wscrm {--kering : Hanya tampilkan, jangan ubah data}';

    protected $description = 'Tarik layanan WSCRM (/api/dk/*) dan cocokkan ke klien';

    public function handle(): int
    {
        $base = rtrim((string) config('dk.wscrm_url'), '/');
        $token = (string) config('dk.wscrm_token');

        if ($base === '' || $token === '') {
            $this->warn('WSCRM_URL / WSCRM_TOKEN belum diisi di .env — dilewati.');

            return self::SUCCESS;
        }

        $kering = (bool) $this->option('kering');

        try {
            $res = Http::withToken($token)->timeout(20)->acceptJson()->get("{$base}/api/dk/layanan");
            if (! $res->successful()) {
                $this->error("WSCRM balas HTTP {$res->status()}.");

                return self::FAILURE;
            }
            $layanan = $res->json('data') ?? $res->json() ?? [];
        } catch (\Throwable $e) {
            $this->error('Koneksi WSCRM gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! is_array($layanan) || $layanan === []) {
            $this->info('WSCRM tidak mengirim layanan.');

            return self::SUCCESS;
        }

        $cocok = 0;
        foreach ($layanan as $l) {
            $domain = (string) ($l['domain'] ?? $l['nama'] ?? '');
            $email = (string) ($l['email'] ?? '');
            if ($domain === '' && $email === '') {
                continue;
            }

            $klien = Klien::query()
                ->when($email !== '', fn ($q) => $q->where('email', $email))
                ->orWhere(function ($q) use ($domain) {
                    $q->where('catatan', 'like', "%{$domain}%")
                        ->orWhere('perusahaan', 'like', '%'.explode('.', $domain)[0].'%');
                })
                ->first();

            if (! $klien) {
                continue;
            }

            $teks = 'WSCRM: layanan '.$domain
                .(! empty($l['paket']) ? " ({$l['paket']})" : '')
                .(! empty($l['jatuh_tempo']) ? " jatuh tempo {$l['jatuh_tempo']}" : '');

            if ($klien->aktivitas()->where('judul', $teks)->whereDate('created_at', now()->toDateString())->exists()) {
                continue;
            }

            $this->line("  {$klien->nama} ← {$teks}");
            $cocok++;

            if (! $kering) {
                $klien->aktivitas()->create([
                    'judul' => $teks,
                    'jenis' => 'penagihan',
                    'tgl' => now()->toDateString(),
                    'user_id' => null,
                ]);
            }
        }

        $this->info($kering
            ? "Mode kering: {$cocok} kecocokan, tidak ada yang disimpan."
            : "{$cocok} catatan WSCRM disimpan ke klien.");

        return self::SUCCESS;
    }
}
