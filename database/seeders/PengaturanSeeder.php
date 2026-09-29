<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use App\Models\TemplateTahapan;
use Illuminate\Database\Seeder;

class PengaturanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Pengaturan::BAWAAN as $kunci => [$nilai, $keterangan]) {
            Pengaturan::firstOrCreate(['kunci' => $kunci], ['nilai' => $nilai, 'keterangan' => $keterangan]);
        }

        foreach (TemplateTahapan::BAWAAN as $jenis => $daftar) {
            foreach ($daftar as $i => $nama) {
                TemplateTahapan::updateOrCreate(
                    ['jenis' => $jenis, 'urutan' => $i + 1],
                    ['nama' => $nama, 'aktif' => true]
                );
            }
        }
    }
}
