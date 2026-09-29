<?php

namespace App\Models;

use App\Support\Uang;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagihanBayar extends Model
{
    protected $table = 'tagihan_bayar';

    protected $fillable = ['tagihan_id', 'tgl', 'nominal', 'metode', 'referensi', 'catatan', 'user_id'];

    protected function casts(): array
    {
        return ['tgl' => 'date', 'nominal' => 'decimal:2'];
    }

    public const METODE = [
        'transfer' => 'Transfer',
        'tunai' => 'Tunai',
        'qris' => 'QRIS',
        'lain' => 'Lain-lain',
    ];

    /**
     * `tagihan.terbayar_total_hitung` + `status` adalah cache yang ditulis ulang
     * dari baris di sini (SPEC §6.2–6.3). Disinkronkan otomatis supaya tak ada
     * jalur — controller, tinker, atau skrip — yang bisa meninggalkannya basi.
     */
    protected static function booted(): void
    {
        $sinkron = fn (TagihanBayar $b) => Tagihan::find($b->tagihan_id)?->hitungStatus();

        static::created($sinkron);
        static::updated($sinkron);
        static::deleted($sinkron);
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLabelMetodeAttribute(): string
    {
        return self::METODE[$this->metode] ?? $this->metode;
    }

    public function getNominalTeksAttribute(): string
    {
        return Uang::rp($this->nominal);
    }
}
