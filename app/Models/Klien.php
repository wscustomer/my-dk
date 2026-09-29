<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Klien extends Model
{
    protected $table = 'klien';

    protected $fillable = [
        'nama', 'perusahaan', 'email', 'telepon', 'alamat', 'kota', 'catatan',
        'sumber', 'sumber_lain', 'status', 'aktif', 'prospek_id',
    ];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public const STATUS = [
        'prospek' => ['Prospek', '#6b7280'],
        'klien_aktif' => ['Klien Aktif', '#16a34a'],
        'pelanggan_lama' => ['Pelanggan Lama', '#64748b'],
        'tidak_aktif' => ['Tidak Aktif', '#d97706'],
    ];

    public const SUMBER = [
        'prospek' => 'Dari prospek',
        'referral' => 'Referral',
        'manual' => 'Dimasukkan manual',
        'lama' => 'Data lama',
        'lain' => 'Lain-lain',
    ];

    protected static function booted(): void
    {
        static::creating(function (Klien $k) {
            if (empty($k->kode)) {
                $k->kode = $k->kodeBaru();
            }
        });
    }

    private function kodeBaru(): string
    {
        $urut = (int) static::query()->max('id') + 1;

        return 'K' . str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }

    public function proyek(): HasMany
    {
        return $this->hasMany(Proyek::class);
    }

    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class);
    }

    public function aktivitas(): MorphMany
    {
        return $this->morphMany(Aktivitas::class, 'entitas', 'entitas', 'entitas_id');
    }

    public function lampiran(): MorphMany
    {
        return $this->morphMany(Lampiran::class, 'entitas', 'entitas', 'entitas_id');
    }

    public function getLabelStatusAttribute(): string
    {
        return self::STATUS[$this->status][0] ?? $this->status;
    }

    public function getWarnaStatusAttribute(): string
    {
        return self::STATUS[$this->status][1] ?? '#6b7280';
    }

    /** Tautan WhatsApp; nomor 021/0274 (kantor) tidak ditautkan. */
    public function getWhatsappAttribute(): ?string
    {
        $nomor = preg_replace('/[^0-9]/', '', (string) $this->telepon) ?? '';

        if (strlen($nomor) < 9) {
            return null;
        }
        if (preg_match('/^(62|0)(21|22|24|31|61|274|271|272|275|276|281|282|341|351|361|411|431|461|471|511|541|561|751|761|811|821)\d{6,}$/', $nomor)) {
            return null;
        }

        $nomor = preg_replace('/^0/', '62', $nomor) ?? $nomor;
        $nomor = preg_replace('/^620/', '62', $nomor) ?? $nomor;
        $nomor = preg_replace('/^8/', '628', $nomor) ?? $nomor;

        return 'https://wa.me/' . $nomor;
    }

    /** Nilai kontrak semua proyek yang tidak batal. */
    public function getNilaiLifetimeAttribute(): float
    {
        return (float) $this->proyek()->where('status', '!=', 'batal')->sum('nilai_kontrak');
    }

    public function getNilaiLifetimeTeksAttribute(): string
    {
        return \App\Support\Uang::rp($this->nilai_lifetime);
    }

    public function scopeCari(Builder $q, ?string $kata): Builder
    {
        $kata = trim((string) $kata);

        if ($kata === '') {
            return $q;
        }

        return $q->where(function (Builder $w) use ($kata) {
            $w->where('nama', 'like', "%{$kata}%")
                ->orWhere('perusahaan', 'like', "%{$kata}%")
                ->orWhere('email', 'like', "%{$kata}%")
                ->orWhere('telepon', 'like', "%{$kata}%")
                ->orWhere('kode', 'like', "%{$kata}%");
        });
    }
}
