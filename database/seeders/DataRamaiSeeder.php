<?php

namespace Database\Seeders;

use App\Models\Aktivitas;
use App\Models\Klien;
use App\Models\Proyek;
use App\Models\Tagihan;
use App\Models\TagihanBayar;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data volume untuk melihat CRM dalam kondisi ramai (§12 uji terima).
 * Deterministik lewat mt_srand — hasil sama tiap dijalankan.
 *
 * php artisan db:seed --class=DataRamaiSeeder
 */
class DataRamaiSeeder extends Seeder
{
    private const JUMLAH_KLIEN = 120;

    /** [nama, kota] */
    private const KLIEN = [
        ['Toko Bangunan Sumber Rejeki', 'Klaten'], ['CV Mitra Karya Abadi', 'Solo'],
        ['Warung Sate Pak Kumis', 'Klaten'], ['PT Bumi Tani Makmur', 'Yogyakarta'],
        ['Klinik Sehat Bersama', 'Klaten'], ['Bengkel Motor Jaya Abadi', 'Sukoharjo'],
        ['Apotek Anugerah Farma', 'Boyolali'], ['Kopi Kenangan Pagi', 'Klaten'],
        ['PT Sinar Logam Nusantara', 'Semarang'], ['Laundry Bersih Wangi', 'Klaten'],
        ['SD Islam Terpadu Al-Hikmah', 'Klaten'], ['Percetakan Cepat Digital', 'Solo'],
        ['CV Teknologi Tepat Guna', 'Yogyakarta'], ['Toko Elektronik Cahaya', 'Klaten'],
        ['Restoran Padang Sederhana Jaya', 'Magelang'], ['PT Andalan Ekspedisi', 'Semarang'],
        ['Butik Muslimah Zahra', 'Klaten'], ['Katering Dapur Ibu', 'Klaten'],
        ['PT Agro Lestari Sejahtera', 'Sragen'], ['Fotokopi & ATK Cendana', 'Klaten'],
        ['Klinik Gigi Senyum Ceria', 'Solo'], ['CV Bangun Rumah Impian', 'Klaten'],
        ['Toko Pakaian Mode Terkini', 'Yogyakarta'], ['PT Rekayasa Jembatan Utama', 'Semarang'],
        ['Warmindo Sedap Rasa', 'Klaten'], ['Travel Wisata Borobudur', 'Magelang'],
        ['CV Persada Furnitur', 'Jepara'], ['Sekolah PAUD Tunas Harapan', 'Klaten'],
        ['Barbershop Gentleman Cut', 'Klaten'], ['PT Karya Plastik Mandiri', 'Sidoarjo'],
        ['Toko Bunga Olivia', 'Klaten'], ['Pondok Pesantren Nurul Ilmi', 'Klaten'],
        ['CV Media Kreatif Digital', 'Solo'], ['PT Kimia Nusantara Lab', 'Semarang'],
        ['Agen Properti Griya Utama', 'Yogyakarta'], ['Toko Sepeda Gowes Jaya', 'Klaten'],
        ['CV Sumber Pangan Lestari', 'Klaten'], ['Hotel Pesona Klaten', 'Klaten'],
        ['PT Logistik Terpadu Andalan', 'Semarang'], ['Optik Terang Vision', 'Klaten'],
        ['Salon Risky Cantik', 'Klaten'], ['Toko Grosir Sembako Jaya', 'Klaten'],
        ['CV Desain Interior Rapi', 'Yogyakarta'], ['PT Energi Surya Terang', 'Solo'],
        ['Koperasi Simpan Pinjam Amanah', 'Klaten'], ['Toko Jam Tirta', 'Klaten'],
        ['Yayasan Panti Asuhan Kasih', 'Klaten'], ['CV Sablon Kreatif Print', 'Solo'],
        ['PT Farmasi Sehat Alami', 'Semarang'], ['Toko Burung Kicau Nusantara', 'Klaten'],
        ['Bimbel Cerdas Cermat', 'Klaten'], ['CV Konstruksi Baja Ringan', 'Klaten'],
        ['PT Tekstil Benang Emas', 'Sukoharjo'], ['Rumah Makan Sambel Ijo', 'Klaten'],
        ['Toko Peralatan Tani Makmur', 'Klaten'], ['CV Digital Marketing Pintar', 'Solo'],
        ['PT Kemasan Ramah Lingkungan', 'Semarang'], ['Toko Kue Amanda Bakery', 'Klaten'],
        ['Klinik Hewan Vet Care', 'Yogyakarta'], ['CV Solusi Air Bersih', 'Klaten'],
        ['PT Otomotif Suku Cadang Jaya', 'Semarang'], ['Toko Mainan Anak Ceria', 'Klaten'],
        ['CV Percetakan Offset Presisi', 'Klaten'], ['PT Ritel Modern Sejahtera', 'Solo'],
        ['Toko Lantai & Keramik Indah', 'Klaten'], ['CV Tour & Travel Elfahmi', 'Klaten'],
        ['PT Alat Berat Trakindo Jaya', 'Semarang'], ['Toko Roti Manis Bakery', 'Klaten'],
        ['CV Internet Service Provider', 'Klaten'], ['PT Garmen Konveksi Rapi', 'Sukoharjo'],
        ['Toko Bangunan Cahaya Abadi', 'Klaten'], ['Klinik Mata Jernih', 'Solo'],
        ['CV Penyiaran Radio Suara', 'Klaten'], ['PT Pakan Ternak Melimpah', 'Boyolali'],
        ['Toko Emas Mulia Permata', 'Klaten'], ['CV Aplikasi Pintar Teknologi', 'Yogyakarta'],
        ['PT Asuransi Mitra Sejati', 'Semarang'], ['Toko Obat Herbal Alami', 'Klaten'],
        ['CV Pengelola Parkir Sentral', 'Klaten'], ['PT Jasa Angkutan Cepat', 'Semarang'],
        ['Toko Meubel Kayu Jati', 'Klaten'], ['Panti Jompo Wreda Bhakti', 'Klaten'],
        ['CV Teknik Pendingin Sejuk', 'Solo'], ['PT Kemaritiman Bahari Jaya', 'Semarang'],
        ['Toko Aksesoris HP Gadget Zone', 'Klaten'], ['CV Pertanian Organik Subur', 'Klaten'],
        ['Katering Sehat Diet Nusantara', 'Yogyakarta'], ['PT Properti Megah Land', 'Semarang'],
        ['Toko Sayur Segar Pasar Pagi', 'Klaten'], ['CV Software Rumah Coding', 'Yogyakarta'],
        ['PT Percetakan Buku Pendidikan', 'Solo'], ['Toko Alat Kesehatan Medika', 'Klaten'],
        ['Klinik THT Suara Jernih', 'Solo'], ['CV Kebersihan Bersih Selalu', 'Klaten'],
        ['PT Perdagangan Ekspor Impor', 'Semarang'], ['Toko Beras Sumber Makmur', 'Klaten'],
        ['CV Otomasi Industri Cerdas', 'Karawang'], ['PT Perkebunan Sawit Lestari', 'Medan'],
        ['Toko Sepatu Olahraga Sporty', 'Klaten'], ['CV Pemasangan CCTV Aman', 'Klaten'],
        ['PT Manufaktur Presisi Tinggi', 'Semarang'], ['Toko Ikan Hias Aquarium Indah', 'Klaten'],
        ['CV Nasi Box Rasa Nikmat', 'Klaten'], ['PT Distribusi Farmasi Utama', 'Semarang'],
        ['Toko Bangunan Harapan Baru', 'Klaten'], ['CV Pendidikan Bahasa Inggris', 'Klaten'],
        ['PT Konsultan Pajak Terpadu', 'Semarang'], ['Toko Hijab Ayu Collection', 'Klaten'],
        ['CV Alat Musik Nada Indah', 'Solo'], ['PT Mineral Tambang Nusantara', 'Kalimantan'],
        ['Toko Panel Surya Sinar Terang', 'Klaten'], ['CV Jasa Pindahan Rumah', 'Klaten'],
        ['PT Rekayasa Perangkat Lunak', 'Yogyakarta'], ['Toko Baju Seragam Sekolah', 'Klaten'],
        ['CV Griya Taman Asri', 'Klaten'], ['PT Cold Storage Nusantara', 'Semarang'],
    ];

    private const JENIS_NAMA = [
        'website' => ['Website Profil', 'Landing Page', 'Company Profile', 'Toko Online', 'Katalog Produk', 'Website Sekolah', 'Portal Berita', 'Microsite Event'],
        'aplikasi' => ['Sistem Kasir', 'Aplikasi Inventaris', 'Sistem HRIS', 'Dashboard Penjualan', 'Aplikasi Pemesanan', 'Sistem Absensi', 'Aplikasi Keuangan', 'Portal Member'],
        'maintenance' => ['Maintenance Bulanan', 'Perawatan Server', 'Update Konten', 'Optimasi Keamanan', 'Backup & Monitoring'],
        'lain' => ['Migrasi Hosting', 'Audit SEO', 'Reknologi Email', 'Sertifikat SSL', 'Konsultasi Digital'],
    ];

    /** Bobot status proyek — mengikuti papan Kanban. */
    private const BOBOT_STATUS = [
        'penawaran' => 18, 'deal' => 12, 'desain' => 14, 'bangun' => 22,
        'kualitas' => 8, 'revisi' => 10, 'selesai' => 46, 'ditahan' => 5, 'batal' => 4,
    ];

    private const AKTIVITAS_JENIS = ['catatan', 'telepon', 'wa', 'email', 'pertemuan', 'penawaran', 'penagihan'];

    private const CATATAN = [
        'Klien minta revisi warna header.', 'Sudah kirim proposal, tunggu balasan.',
        'Via WA: besok dikirim draf desain.', 'Pertemuan di kantor klien, bahas fitur tambahan.',
        'Klien minta tambah halaman kontak.', 'Sudah bayar DP, lanjut ke desain.',
        'Kendala domain belum diperpanjang.', 'Klien tanya estimasi pengerjaan.',
        'Perlu rapat internal soal teknis.', 'Klien apresiasi hasil, minta lanjut maintenance.',
        'Menunggu konten dari klien.', 'Revisi ketiga, klien minta layout diubah.',
    ];

    public function run(): void
    {
        mt_srand(20260929);

        $this->bersihkan();

        $admin = User::query()->orderBy('id')->first();
        $pemilik = User::query()->pluck('id')->all() ?: [null];

        $klien = $this->seederKlien();
        $proyek = $this->seederProyek($klien, $pemilik, $admin);
        $this->seederTagihan($klien, $proyek, $admin);
        $this->seederAktivitas($klien, $proyek, $pemilik);

        $this->command?->info(sprintf(
            'Data ramai: %d klien, %d proyek, %d tagihan, %d aktivitas',
            Klien::count(), Proyek::count(), Tagihan::count(), Aktivitas::count()
        ));
    }

    /** Hapus data seeder lama supaya aman dijalankan berulang. */
    private function bersihkan(): void
    {
        $klien = Klien::query()->where('sumber_lain', 'Seeder')->pluck('id');

        if ($klien->isEmpty()) {
            return;
        }

        $proyek = Proyek::query()->whereIn('klien_id', $klien)->pluck('id');
        TagihanBayar::query()->whereIn('tagihan_id', Tagihan::query()->whereIn('klien_id', $klien)->pluck('id'))->delete();
        Tagihan::query()->whereIn('klien_id', $klien)->delete();
        Aktivitas::query()->whereIn('klien_id', $klien)->delete();
        Proyek::query()->whereIn('klien_id', $klien)->delete();
        Klien::query()->whereIn('id', $klien)->delete();
    }

    /** @return array<int, Klien> */
    private function seederKlien(): array
    {
        $status = ['prospek' => 20, 'klien_aktif' => 55, 'pelanggan_lama' => 30, 'tidak_aktif' => 15];
        $sumber = ['prospek', 'referral', 'manual', 'lama', 'lain'];
        $daftar = [];

        for ($i = 0; $i < self::JUMLAH_KLIEN; $i++) {
            [$nama, $kota] = self::KLIEN[$i % count(self::KLIEN)];
            $suffix = intdiv($i, count(self::KLIEN));
            $nama = $suffix > 0 ? "{$nama} ".(chr(65 + $suffix - 1)) : $nama;

            $daftar[] = Klien::create([
                'nama' => $nama,
                'perusahaan' => str_contains($nama, 'CV ') || str_contains($nama, 'PT ') ? $nama : null,
                'email' => 'klien'.($i + 1).'@'.strtolower(preg_replace('/[^a-z]/', '', strstr($nama, ' ', true) ?: 'bisnis')).'.co.id',
                'telepon' => '08'.mt_rand(11, 88).mt_rand(1000000, 9999999),
                'alamat' => 'Jl. '.['Diponegoro', 'Sudirman', 'Ahmad Yani', 'Merdeka', 'Pemuda', 'Veteran', 'Kartini'][mt_rand(0, 6)].' No. '.mt_rand(1, 200),
                'kota' => $kota,
                'status' => $this->ambilStatus($status),
                'sumber' => $sumber[mt_rand(0, count($sumber) - 1)],
                'sumber_lain' => 'Seeder',
                'aktif' => true,
                'catatan' => mt_rand(0, 3) === 0 ? 'Prospek dari '.$sumber[mt_rand(0, count($sumber) - 1)].'.' : null,
            ]);
        }

        return $daftar;
    }

    /**
     * @param  array<int, Klien>  $klien
     * @param  array<int, int|null>  $pemilik
     * @return array<int, Proyek>
     */
    private function seederProyek(array $klien, array $pemilik, ?User $admin): array
    {
        $status = self::BOBOT_STATUS;
        $jenisKunci = array_keys(self::JENIS_NAMA);
        $daftar = [];

        foreach ($klien as $i => $k) {
            // 4 dari 5 klien punya proyek; sisanya murni prospek.
            $jumlah = mt_rand(0, 100) < 20 ? 0 : mt_rand(1, 3);

            for ($n = 0; $n < $jumlah; $n++) {
                $jenis = $jenisKunci[mt_rand(0, count($jenisKunci) - 1)];
                $namaJenis = self::JENIS_NAMA[$jenis];
                $st = $this->ambilStatus($status);

                $mulai = now()->subDays(mt_rand(10, 150));
                $target = $mulai->copy()->addDays(mt_rand(20, 90));

                $nilai = match ($jenis) {
                    'website' => mt_rand(35, 250) * 100000,
                    'aplikasi' => mt_rand(150, 900) * 100000,
                    'maintenance' => mt_rand(8, 45) * 100000,
                    default => mt_rand(15, 120) * 100000,
                };

                $selesai = $st === 'selesai';

                $p = Proyek::create([
                    'klien_id' => $k->id,
                    'nama' => $namaJenis[mt_rand(0, count($namaJenis) - 1)].' '.($k->perusahaan ?: $k->nama),
                    'jenis' => $jenis,
                    'deskripsi' => 'Proyek '.$jenis.' untuk '.$k->nama.'. Lingkup mencakup perancangan, pengerjaan, dan serah terima.',
                    'status' => $st,
                    'nilai_kontrak' => $nilai,
                    'dp_nominal' => $st === 'penawaran' ? 0 : (int) round($nilai * [0.3, 0.4, 0.5][mt_rand(0, 2)]),
                    'tgl_mulai' => $mulai->toDateString(),
                    'tgl_target' => $target->toDateString(),
                    'tgl_serah' => $selesai ? $target->copy()->addDays(mt_rand(-10, 20))->toDateString() : null,
                    'pemilik_id' => $pemilik[mt_rand(0, count($pemilik) - 1)],
                    'urutan_papan' => $n,
                    'revisi' => $st === 'revisi' ? mt_rand(1, 4) : 0,
                    'tautan_hasil' => $selesai ? 'https://'.$k->kota.'.'.$i.'.contoh.id' : null,
                    'catatan' => $st === 'ditahan' ? 'Ditahan: menunggu konfirmasi anggaran klien.' : null,
                    'dibuat_oleh' => $admin?->id,
                ]);

                $this->seederTahapan($p, $st);
                $daftar[] = $p;
            }
        }

        return $daftar;
    }

    private function seederTahapan(Proyek $p, string $statusProyek): void
    {
        $p->salinTahapan();

        $tahapan = $p->tahapan()->orderBy('urutan')->get();
        $selesaiSampai = match ($statusProyek) {
            'penawaran' => 0, 'deal' => 1, 'desain' => 2, 'bangun' => 3,
            'kualitas' => 4, 'revisi' => 4, 'selesai' => $tahapan->count(),
            default => mt_rand(1, max(1, $tahapan->count() - 1)),
        };

        foreach ($tahapan as $i => $t) {
            $lewat = $i < $selesaiSampai;
            $t->update([
                'status' => $lewat ? 'selesai' : ($i === $selesaiSampai ? 'jalan' : 'belum'),
                'tgl_mulai' => $lewat || $i === $selesaiSampai ? $p->tgl_mulai?->copy()->addDays($i * 5)->toDateString() : null,
                'tgl_selesai' => $lewat ? $p->tgl_mulai?->copy()->addDays($i * 5 + 4)->toDateString() : null,
            ]);
        }
    }

    /**
     * @param  array<int, Klien>  $klien
     * @param  array<int, Proyek>  $proyek
     */
    private function seederTagihan(array $klien, array $proyek, ?User $admin): void
    {
        $perKlien = [];
        foreach ($proyek as $p) {
            $perKlien[$p->klien_id][] = $p;
        }

        foreach ($klien as $k) {
            $daftar = $perKlien[$k->id] ?? [];

            // Klien prospek belum ditagih.
            if ($k->status === 'prospek' || $daftar === []) {
                continue;
            }

            foreach ($daftar as $p) {
                if (in_array($p->status, ['penawaran', 'batal'], true)) {
                    continue;
                }

                $this->buatTagihan($k, $p, $admin, 'termin_1');
                $this->buatTagihan($k, $p, $admin, 'termin_2');
                $this->buatTagihan($k, $p, $admin, 'termin_3');
            }
        }
    }

    private function buatTagihan(Klien $k, Proyek $p, ?User $admin, string $termin): void
    {
        $totalKontrak = (float) $p->nilai_kontrak;
        $nominal = (float) $p->dp_nominal;

        if ($nominal <= 0) {
            return;
        }

        $lewatTempo = mt_rand(0, 100) < 22;

        // Termin 1 sudah ditagih sejak awal; termin 2/3 hanya kalau proyek jalan jauh.
        $mulai = $p->tgl_mulai?->copy() ?? now();
        $sejak = match ($termin) {
            'termin_1' => $mulai,
            'termin_2' => $mulai->copy()->addDays(30),
            default => $mulai->copy()->addDays(60),
        };

        if ($sejak->isFuture()) {
            return;
        }

        $jumlah = match ($termin) {
            'termin_1' => $nominal,
            'termin_2' => (int) round($totalKontrak * 0.4),
            default => (int) round($totalKontrak * 0.3),
        };

        $terbit = $sejak->copy();
        $jatuh = $terbit->copy()->addDays(14);

        // Termin 3 dilewati untuk sebagian proyek agar ada variasi piutang.
        if ($termin === 'termin_3' && mt_rand(0, 100) < 40) {
            return;
        }

        $statusAkhir = match (true) {
            // Termin 1 lunas hanya kalau ada DP tercatat; sisanya masih piutang.
            $termin === 'termin_1' => mt_rand(0, 100) < 70 ? 'lunas' : 'terkirim',
            $p->status === 'selesai' => mt_rand(0, 100) < 85 ? 'lunas' : 'terkirim',
            $lewatTempo && $jatuh->isPast() => 'terkirim',
            default => mt_rand(0, 100) < 30 ? 'lunas' : 'terkirim',
        };

        $tagihan = Tagihan::create([
            'klien_id' => $k->id,
            'proyek_id' => $p->id,
            'judul' => str_replace('_', ' ', 'Termin '.substr($termin, -1)).' — '.$p->nama,
            'total' => $jumlah,
            'status' => $statusAkhir === 'lunas' ? 'lunas' : 'terkirim',
            'tgl_terbit' => $terbit->toDateString(),
            'tgl_jatuh_tempo' => $jatuh->toDateString(),
            'tgl_bayar' => $statusAkhir === 'lunas' ? $jatuh->copy()->subDays(mt_rand(-5, 10))->toDateString() : null,
            'dibuat_oleh' => $admin?->id,
        ]);

        if ($statusAkhir === 'lunas') {
            TagihanBayar::create([
                'tagihan_id' => $tagihan->id,
                'tgl' => $tagihan->tgl_bayar,
                'nominal' => $jumlah,
                'metode' => ['transfer', 'transfer', 'transfer', 'cash'][mt_rand(0, 3)],
                'referensi' => 'TRF'.mt_rand(100000, 999999),
                'catatan' => 'Pelunasan termin '.substr($termin, -1),
                'user_id' => $admin?->id,
            ]);
        } elseif (mt_rand(0, 100) < 35) {
            // Sebagian: klien bayar separuh dulu.
            $bayar = (int) round($jumlah / 2);
            TagihanBayar::create([
                'tagihan_id' => $tagihan->id,
                'tgl' => $terbit->copy()->addDays(mt_rand(2, 20))->toDateString(),
                'nominal' => $bayar,
                'metode' => 'transfer',
                'referensi' => 'TRF'.mt_rand(100000, 999999),
                'catatan' => 'Pembayaran sebagian',
                'user_id' => $admin?->id,
            ]);
        } elseif (mt_rand(0, 100) < 20) {
            // Cicilan kecil: nyicil tanpa lunas.
            TagihanBayar::create([
                'tagihan_id' => $tagihan->id,
                'tgl' => $terbit->copy()->addDays(mt_rand(2, 25))->toDateString(),
                'nominal' => (int) round($jumlah * 0.25),
                'metode' => 'transfer',
                'referensi' => 'TRF'.mt_rand(100000, 999999),
                'catatan' => 'Angsuran pertama',
                'user_id' => $admin?->id,
            ]);
        }
    }

    /**
     * @param  array<int, Klien>  $klien
     * @param  array<int, Proyek>  $proyek
     * @param  array<int, int|null>  $pemilik
     */
    private function seederAktivitas(array $klien, array $proyek, array $pemilik): void
    {
        foreach ($proyek as $p) {
            $jumlah = mt_rand(1, 6);

            for ($i = 0; $i < $jumlah; $i++) {
                $jenis = self::AKTIVITAS_JENIS[mt_rand(0, count(self::AKTIVITAS_JENIS) - 1)];

                Aktivitas::create([
                    'klien_id' => $p->klien_id,
                    'proyek_id' => $p->id,
                    'jenis' => $jenis,
                    'judul' => self::CATATAN[mt_rand(0, count(self::CATATAN) - 1)],
                    'catatan' => $jenis === 'penagihan' ? 'Pengingat jatuh tempo terkirim.' : null,
                    'hasil' => in_array($jenis, ['telepon', 'wa', 'pertemuan'], true) ? ['terhubung', 'tidak_dijawab', 'dijadwalkan'][mt_rand(0, 2)] : null,
                    'tgl' => now()->subDays(mt_rand(0, 90))->toDateString(),
                    'user_id' => $pemilik[mt_rand(0, count($pemilik) - 1)],
                ]);
            }
        }

        foreach ($klien as $k) {
            if (mt_rand(0, 100) < 60) {
                continue;
            }

            Aktivitas::create([
                'klien_id' => $k->id,
                'jenis' => ['telepon', 'wa', 'pertemuan'][mt_rand(0, 2)],
                'judul' => self::CATATAN[mt_rand(0, count(self::CATATAN) - 1)],
                'tgl' => now()->subDays(mt_rand(0, 120))->toDateString(),
                'user_id' => $pemilik[mt_rand(0, count($pemilik) - 1)],
            ]);
        }
    }

    /** @param array<string, int> $bobot */
    private function ambilStatus(array $bobot): string
    {
        $total = array_sum($bobot);
        $undi = mt_rand(1, $total);

        foreach ($bobot as $status => $b) {
            $undi -= $b;
            if ($undi <= 0) {
                return $status;
            }
        }

        return array_key_first($bobot);
    }
}
