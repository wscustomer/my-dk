<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aktivitas extends Model
{
    protected $table = 'aktivitas';

    protected $fillable = ['entitas', 'entitas_id', 'klien_id', 'proyek_id', 'jenis', 'judul', 'catatan', 'hasil', 'tgl', 'user_id'];

    protected function casts(): array
    {
        return ['tgl' => 'date'];
    }

    /**
     * `entitas` + `entitas_id` NOT NULL di skema, tapi pemanggil cukup mengisi
     * `proyek_id` atau `klien_id` (Lampiran B). Diisi otomatis di sini supaya
     * pemanggil tidak perlu tahu soal kolom polimorfik.
     */
    protected static function booted(): void
    {
        static::saving(function (Aktivitas $a) {
            if ($a->entitas && $a->entitas_id) {
                return;
            }

            if ($a->proyek_id) {
                $a->entitas = Proyek::class;
                $a->entitas_id = $a->proyek_id;
            } elseif ($a->klien_id) {
                $a->entitas = Klien::class;
                $a->entitas_id = $a->klien_id;
            }
        });
    }

    public const JENIS = [
        'catatan' => 'Catatan',
        'telepon' => 'Telepon',
        'wa' => 'WhatsApp',
        'email' => 'Email',
        'pertemuan' => 'Pertemuan',
        'penawaran' => 'Penawaran',
        'perubahan_status' => 'Perubahan status',
        'pembayaran' => 'Pembayaran',
        'penagihan' => 'Penagihan',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function klien(): BelongsTo
    {
        return $this->belongsTo(Klien::class);
    }

    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class);
    }

    public function getLabelJenisAttribute(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }

    public function getIkonAttribute(): string
    {
        return match ($this->jenis) {
            'telepon' => '☎',
            'wa' => '✆',
            'email' => '✉',
            'pertemuan' => '☺',
            'penawaran' => '▤',
            'perubahan_status' => '⇄',
            'pembayaran' => 'Rp',
            'penagihan' => '⧗',
            default => '•',
        };
    }
}
