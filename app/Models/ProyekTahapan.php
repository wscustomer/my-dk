<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProyekTahapan extends Model
{
    protected $table = 'proyek_tahapan';

    protected $fillable = ['proyek_id', 'nama', 'urutan', 'status', 'tgl_mulai', 'tgl_selesai', 'catatan'];

    protected function casts(): array
    {
        return ['tgl_mulai' => 'date', 'tgl_selesai' => 'date'];
    }

    public const STATUS = [
        'belum' => ['Belum', '#9ca3af'],
        'jalan' => ['Jalan', '#2196f3'],
        'selesai' => ['Selesai', '#16a34a'],
        'dilewati' => ['Dilewati', '#a1a1aa'],
    ];

    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class);
    }

    public function getLabelStatusAttribute(): string
    {
        return self::STATUS[$this->status][0] ?? $this->status;
    }

    public function getWarnaStatusAttribute(): string
    {
        return self::STATUS[$this->status][1] ?? '#9ca3af';
    }
}
