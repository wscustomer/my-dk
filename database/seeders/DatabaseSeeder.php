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

        $email = (string) config('dk.admin_email');
        $sandi = (string) config('dk.admin_password');

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
