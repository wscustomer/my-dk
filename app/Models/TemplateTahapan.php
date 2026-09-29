<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateTahapan extends Model
{
    protected $table = 'template_tahapan';

    protected $fillable = ['jenis', 'urutan', 'nama', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public const JENIS = [
        'website' => 'Pembuatan Website',
        'aplikasi' => 'Pembuatan Aplikasi',
        'maintenance' => 'Maintenance',
    ];

    public const BAWAAN = [
        'website' => [
            'Kumpulkan kebutuhan',
            'Desain tampilan',
            'Bangun halaman',
            'Isi konten',
            'Uji & periksa',
            'Serah terima & pelatihan',
        ],
        'aplikasi' => [
            'Kumpulkan kebutuhan',
            'Rancang alur & skema data',
            'Desain tampilan',
            'Bangun backend & API',
            'Bangun tampilan aplikasi',
            'Uji fungsi',
            'Uji coba pengguna',
            'Serah terima & pelatihan',
        ],
        'maintenance' => [
            'Periksa berkala',
            'Perbaikan',
            'Laporan',
        ],
    ];
}
