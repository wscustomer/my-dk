<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Lampiran extends Model
{
    protected $table = 'lampiran';

    protected $fillable = ['entitas', 'entitas_id', 'nama_asli', 'path', 'mime', 'ukuran', 'user_id'];

    public function entitas(): MorphTo
    {
        return $this->morphTo('entitas', 'entitas', 'entitas_id');
    }

    public function getUkuranTeksAttribute(): string
    {
        $b = (int) $this->ukuran;

        return match (true) {
            $b >= 1048576 => round($b / 1048576, 1).' MB',
            $b >= 1024 => round($b / 1024).' KB',
            default => $b.' B',
        };
    }

    public function getJenisAttribute(): string
    {
        return match (true) {
            str_contains((string) $this->mime, 'pdf') => 'PDF',
            str_contains((string) $this->mime, 'image') => 'Gambar',
            str_contains((string) $this->mime, 'zip') => 'Arsip',
            str_contains((string) $this->mime, 'sheet'), str_contains((string) $this->mime, 'excel') => 'Spreadsheet',
            default => 'Dokumen',
        };
    }
}
