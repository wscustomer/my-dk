<?php

namespace App\Models;

use App\Support\Uang;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tagihan extends Model
{
    protected $table = 'tagihan';

    protected $fillable = [
        'klien_id', 'proyek_id', 'judul', 'total', 'terbayar_total_hitung',
        'status', 'tgl_terbit', 'tgl_jatuh_tempo', 'tgl_bayar', 'catatan', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'terbayar_total_hitung' => 'decimal:2',
            'tgl_terbit' => 'date',
            'tgl_jatuh_tempo' => 'date',
            'tgl_bayar' => 'date',
        ];
    }

    public const STATUS = [
        'draft' => 'Draft',
        'terkirim' => 'Terkirim',
        'lunas' => 'Lunas',
        'batal' => 'Batal',
    ];

    /** Status tampilan yang memperhitungkan pembayaran & waktu. */
    public const STATE = [
        'draft' => ['Draft', '#71717a'],
        'terkirim' => ['Belum dibayar', '#dc2626'],
        'sebagian' => ['Sebagian', '#d97706'],
        'lewat' => ['Lewat tempo', '#b91c1c'],
        'lunas' => ['Lunas', '#16a34a'],
        'batal' => ['Batal', '#a1a1aa'],
    ];

    public function klien(): BelongsTo
    {
        return $this->belongsTo(Klien::class);
    }

    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class);
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(TagihanBayar::class)->orderBy('tgl');
    }

    public function getTerbayarAttribute(): float
    {
        if ($this->relationLoaded('pembayaran')) {
            return (float) $this->pembayaran->sum('nominal');
        }

        return (float) $this->pembayaran()->sum('nominal');
    }

    public function setTerbayarAttribute(float $nilai): void
    {
        $this->attributes['terbayar_total_hitung'] = $nilai;
    }



    public function getStateAttribute(): string
    {
        if ($this->status === 'batal') {
            return 'batal';
        }
        if ($this->status === 'draft') {
            return 'draft';
        }

        $terbayar = $this->terbayar;

        // Tanda lunas eksplisit (status/tgl_bayar) menang atas hitungan pembayaran —
        // tagihan lama / impor bisa lunas tanpa baris pembayaran.
        $ditandaiLunas = $this->status === 'lunas'
            || ($this->tgl_bayar !== null && $terbayar + 0.005 >= (float) $this->total);

        if ($ditandaiLunas || $terbayar + 0.005 >= (float) $this->total) {
            return 'lunas';
        }

        if ($terbayar > 0.004) {
            return $this->tgl_jatuh_tempo?->isPast() ? 'lewat' : 'sebagian';
        }

        return $this->tgl_jatuh_tempo?->isPast() ? 'lewat' : 'terkirim';
    }

    /** Lunas bila status/tgl_bayar bilang lunas, atau pembayaran menutup penuh total. */
    public function getLunasAttribute(): bool
    {
        return in_array($this->state, ['lunas', 'batal'], true);
    }

    /** Sisa tagihan; 0 kalau ditandai lunas walau baris pembayaran belum dicatat. */
    public function getSisaAttribute(): float
    {
        if ($this->lunas) {
            return 0.0;
        }

        return max(0, (float) $this->total - $this->terbayar);
    }

    public function getLabelStateAttribute(): string
    {
        return self::STATE[$this->state][0] ?? $this->state;
    }

    public function getWarnaStateAttribute(): string
    {
        return self::STATE[$this->state][1] ?? '#71717a';
    }

    public function getTotalTeksAttribute(): string
    {
        return Uang::rp($this->total);
    }

    public function getTerbayarTeksAttribute(): string
    {
        return Uang::rp($this->terbayar);
    }

    public function getSisaTeksAttribute(): string
    {
        return Uang::rp($this->sisa);
    }

    /**
     * Hitung ulang status + total terbayar dari baris pembayaran.
     * Satu-satunya tempat status tagihan berubah setelah dibuat.
     */
    public function hitungStatus(): void
    {
        if ($this->status === 'batal') {
            return;
        }

        $total = (float) $this->pembayaran()->sum('nominal');
        $lunas = $total + 0.005 >= (float) $this->total;

        $this->forceFill([
            'terbayar_total_hitung' => $total,
            'status' => $total <= 0 ? ($this->status === 'draft' ? 'draft' : 'terkirim') : ($lunas ? 'lunas' : 'terkirim'),
            'tgl_bayar' => $lunas ? ($this->tgl_bayar ?? now()->toDateString()) : null,
        ])->save();
    }
}
