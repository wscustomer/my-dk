<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PengaturanSeeder::class);

        $email = (string) env('DK_ADMIN_EMAIL', 'admin@digitalkonsultan.com');
        $sandi = (string) env('DK_ADMIN_PASSWORD', '');

        if ($sandi === '') {
            $this->command?->warn('DK_ADMIN_PASSWORD kosong — akun admin dilewati.');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            ['name' => 'Admin Digital Konsultan', 'password' => Hash::make($sandi), 'peran' => 'admin', 'aktif' => true]
        );

        $this->command?->info("Akun admin siap: {$email}");
    }
}
