<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $primaryKey = 'kunci';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['kunci', 'nilai', 'keterangan'];

    /** Kunci + nilai bawaan + keterangan + grup. */
    public const BAWAAN = [
        'nama_perusahaan' => ['Digital Konsultan', 'Nama perusahaan di portal & ekspor', 'umum'],
        'nama_perusahaan_pendek' => ['DK', 'Singkatan untuk kop/berkas', 'umum'],
        'email_perusahaan' => ['', 'Email perusahaan', 'umum'],
        'telepon_perusahaan' => ['', 'Telepon/WA perusahaan', 'umum'],
        'alamat_perusahaan' => ['Klaten, Jawa Tengah', 'Alamat perusahaan', 'umum'],
        'warna_utama' => ['#2563eb', 'Warna aksen tampilan & portal', 'umum'],
        'rekening_bank' => ['', 'Bank — nomor rekening — atas nama', 'penagihan'],
        'email_pengingat' => ['', 'Tujuan email pengingat jatuh tempo', 'penagihan'],
        'hari_ingat_tagihan' => ['7', 'Hari sebelum jatuh tempo mulai diingatkan', 'penagihan'],
        'dp_persen_default' => ['50', 'Persentase DP default proyek baru', 'penagihan'],
        'termin_default' => ['3', 'Jumlah termin default proyek aplikasi', 'penagihan'],
        'peran_boleh_lihat_nilai' => ['admin', 'Siapa yang boleh lihat nilai kontrak (admin/staf/semua)', 'akses'],
        'zona_waktu' => ['Asia/Jakarta', 'Zona waktu tampilan', 'umum'],
    ];

    protected static ?array $cache = null;

    protected static function booted(): void
    {
        static::saved(fn () => static::$cache = null);
        static::deleted(fn () => static::$cache = null);
    }

    public static function semua(): array
    {
        if (static::$cache === null) {
            static::$cache = static::query()->pluck('nilai', 'kunci')->all();
        }

        return static::$cache;
    }

    public static function nilai(string $kunci, mixed $bawaan = null): mixed
    {
        $nilai = static::semua()[$kunci] ?? null;

        if ($nilai === null || $nilai === '') {
            return $bawaan ?? (self::BAWAAN[$kunci][0] ?? null);
        }

        return $nilai;
    }

    public static function simpanBanyak(array $pasangan): void
    {
        foreach ($pasangan as $kunci => $nilai) {
            static::updateOrCreate(
                ['kunci' => $kunci],
                [
                    'nilai' => is_bool($nilai) ? ($nilai ? '1' : '0') : (string) $nilai,
                    'keterangan' => self::BAWAAN[$kunci][1] ?? null,
                ]
            );
        }

        static::$cache = null;
    }

    public function getGrupAttribute(): string
    {
        return self::BAWAAN[$this->kunci][2] ?? 'lain';
    }

    public function getTipeAttribute(): string
    {
        return match ($this->kunci) {
            'dp_persen_default', 'hari_ingat_tagihan', 'termin_default' => 'angka',
            'peran_boleh_lihat_nilai' => 'pilihan',
            'warna_utama' => 'warna',
            default => 'teks',
        };
    }

    public function getNilaiFormatAttribute(): string
    {
        return match ($this->kunci) {
            'dp_persen_default' => $this->nilai . '%',
            default => (string) $this->nilai,
        };
    }
}
