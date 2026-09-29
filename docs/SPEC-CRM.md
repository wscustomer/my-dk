# SPESIFIKASI — CRM Digital Konsultan

Aplikasi CRM untuk mengelola klien jasa **pembuatan website**, **pembuatan aplikasi**, dan
layanan pendampingnya (domain, hosting, maintenance).

- Versi dokumen: 1.0
- Tanggal: 29 September 2026
- Aplikasi: `my.digitalkonsultan.com`
- Tumpukan: Laravel 13.17 + Inertia 3 + Vue 3.5 + Tailwind 4 + MariaDB (PHP 8.3-FPM)

## 0. Keputusan yang dikunci di depan

| Topik                           | Keputusan                                                                                                                        | Alasan                                                                                                    |
| ------------------------------- | -------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| Aplikasi baru, bukan modul lama | Repo `my-dk` berdiri sendiri di `my.digitalkonsultan.com`                                                                        | Marketing Tools (`app.digitalkonsultan.com`) tetap fokus marketing; CRM punya siklus rilis sendiri        |
| Peran aplikasi                  | **Pengendali operasional & proyek.** Yang mencatat siapa klien, apa yang dijanjikan, di tahap apa, kapan ditagih, apa yang rusak | WSCRM (`app.websweetstudio.com`) tetap **sumber kebenaran penagihan** (hosting, domain, invoice, renewal) |
| Login                           | Tabel `users` milik app ini, guard `web` standar, register dimatikan                                                             | Sudah terpasang di starter kit; tidak perlu SSO untuk 2–3 pengguna                                        |
| Stack tampilan                  | Inertia + Vue + Tailwind (bukan Blade)                                                                                           | Sudah jadi bentuk repo; satu bahasa UI untuk semua halaman                                                |
| Bahasa                          | Antarmuka **Indonesia**, tanpa penyingkatan jargon (Proyek, Tahapan, Tagihan, Klien)                                             | Konvensi user                                                                                             |

> **Aturan anti-duplikasi (paling penting).** Aplikasi ini **tidak** menyimpan harga domain,
> katalog hosting, invoice, atau status renewal. Itu semua milik WSCRM. Kalau CRM butuh menampilkan
> nominal tagihan, ia **menariknya** dari WSCRM lewat API `/api/dk/*`, tidak menyalinnya.
> Melanggar aturan ini = dua angka berbeda untuk satu pelanggan, dan klien ditagih salah.

---

## 1. Tujuan & ukuran berhasil

**Masalah hari ini.** Klien berada di 3 tempat sekaligus: WSCRM (`customers`, penagihan), Marketing
Tools (`customers` + `customer_services`, prospek & masa aktif), dan kepala user. Tidak ada tempat
untuk melihat "sampai mana pekerjaan klien ini" atau "siapa yang menunggak DP".

**Hasil yang diharapkan.**

1. Satu halaman bisa menjawab: klien X, paket apa, tahap apa, siapa yang mengerjakan, kapan janji
   selesai, sudah dibayar berapa, apa yang menunggu.
2. URL proyek yang bisa dibagi ke klien (read-only) untuk melihat progres.
3. Tidak ada pekerjaan yang hilang karena hanya ada di chat WhatsApp.

**Ukuran berhasil (diukur setelah 1 bulan pakai).**

- 100% proyek aktif punya tahap + tenggat di CRM.
- ≥90% tagihan punya tanggal, bukan "nanti".
- Jumlah pertanyaan "proyek A sudah sampai mana?" di WhatsApp turun.

---

## 2. Pengguna & peran

| Peran   | Siapa                     | Boleh                                                                                                                                       |
| ------- | ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| `admin` | Pemilik (user)            | Semua: klien, proyek, tagihan, pengaturan, pengguna, tarif                                                                                  |
| `staf`  | Pekerja/developer         | Lihat semua klien & proyek; catat aktivitas, ubah tahap proyek, ajukan tagihan (**tidak** bisa hapus, **tidak** bisa ubah tarif & pengguna) |
| `klien` | Pelanggan (nanti, fase 5) | Hanya proyeknya sendiri, read-only: tahap, tenggat, riwayat, komentar                                                                       |

Registrasi mandiri **dimatikan**. Akun dibuat admin lewat menu Pengguna.

---

## 3. Alur bisnis (yang harus dicerminkan model data)

```
Prospek (Marketing Tools, 511 baris)
      │  "deal"                                    ← satu tombol: "Jadikan Klien"
      ▼
Klien ──┬── Proyek (jenis: website | aplikasi | maintenance | lain)
        │        └── Tahapan (template per jenis proyek, ada tenggat)
        │        └── Aktivitas / catatan (linimasa)
        │        └── Lampiran (kontrak, brief, desain, hasil)
        ├── Tagihan (DP / pelunasan / termin / bulanan) → nominal diambil dari WSCRM
        └── Layanan aktif (domain & hosting) → dicerminkan dari WSCRM
```

### 3.1 Alur proyek (tahap default — bisa diubah, disimpan sebagai template)

Pembuatan **website**:

1. Brief & kontrak
2. Desain (mockup, revisi)
3. Bangun (development)
4. Review klien
5. Go-live (domain & hosting)
6. Selesai & serah terima

Pembuatan **aplikasi** (software, lebih iteratif):

1. Brief & kontrak
2. Analisis kebutuhan (PRD)
3. Desain UI/UX
4. Pengembangan modul inti
5. Pengujian
6. Peluncuran / deployment
7. Serah terima & pelatihan
8. [Opsional] Maintenance bulanan

Layanan **maintenance**: tidak memakai tahapan, cukup periode + jadwal kunjungan.

### 3.2 Aturan status

- **Proyek**: `penawaran → deal → berjalan → revisi → ditahan (klien) → selesai → batal`.
  `ditahan` wajib mencatat `catatan_penahanan` supaya jelas siapa menunggu siapa.
- **Klien**: `prospek → klien_aktif → tidak_aktif → pelanggan_lama`.
- **Tagihan**: `belum → sebagian → lunas → batal` (lihat §6).
- Tahap proyek boleh melompat, **tidak** boleh mundur tanpa menulis alasan.

### 3.3 Aturan bisnis yang harus ditulis di kode (bukan cuma di spec)

1. Proyek tidak bisa ditutup (`selesai`) bila masih ada tagihan status `belum` **atau**
   masih ada tahap dengan status `berjalan`. Sistem menolak dengan pesan jelas.
2. Menandai proyek `selesai` wajib menulis satu aktivitas (otomatis) berisi tanggal & pelaku.
3. Nilai kontrak proyek tidak boleh negatif; kalau kosong, dilewati ke perhitungan.
4. Persentase DP default dari pengaturan, bukan angka di kode.
5. Semua nominal disimpan sebagai `decimal`, **tidak** pakai `float` (pembulatan uang).
6. Tanggal disimpan sebagai `date`/`datetime` UTC, ditampilkan WIB (`Asia/Jakarta`). Lihat §8.7.

---

## 4. Cakupan

### 4.1 Termasuk (fase 1–4)

- Klien + impor dari prospek
- Proyek, tahapan, aktivitas, lampiran
- Tagihan (DP, termin, pelunasan) dengan pengingat jatuh tempo
- Dasbor pekerjaan & keuangan sederhana
- Ekspor CSV
- Pengaturan: template tahapan, tarif/jasa (cache dari WSCRM), pengguna

### 4.2 **Tidak** termasuk (sengaja)

| Tidak dibuat                                | Kenapa                                                 |
| ------------------------------------------- | ------------------------------------------------------ |
| Invoice legal + PDF bernomor + faktur pajak | Sudah ada di WSCRM, jangan diduplikasi                 |
| Harga domain & katalog hosting              | Milik WSCRM (`domain_prices`, `hosting_plans`)         |
| Pembayaran online / payment gateway         | Tidak diminta; transfer manual sudah jalan             |
| Tiket dukungan bergaya helpdesk             | Aktivitas proyek sudah cukup untuk skala ini           |
| Chat internal / notifikasi push ke ponsel   | WhatsApp sudah dipakai; cukup tautan WA yang sudah ada |
| Multi-mata uang, multi-cabang               | Satu usaha, rupiah                                     |
| Pelacakan waktu per menit (time tracking)   | Tidak dipakai untuk menagih                            |

---

## 5. Spesifikasi data

Konvensi: tabel `snake_case` jamak, PK `id` bigIncrements, `timestamps`, status sebagai `varchar`

- konstanta PHP (pola yang sama dipakai `customers`/`customer_services` di app lama — enologi MySQL
  yang berubah menyulitkan migrasi).

**Aturan wajib: seluruh perubahan skema lewat migrasi.** Tidak ada `ALTER` manual.

### 5.1 `users` (sudah ada — diperluas)

| Kolom    | Tipe                | Catatan                                 |
| -------- | ------------------- | --------------------------------------- |
| id       | bigint PK           |                                         |
| name     | varchar(100)        |                                         |
| email    | varchar(150) UNIQUE |                                         |
| password | varchar(255)        |                                         |
| peran    | varchar(20)         | `admin` \| `staf`, default `staf`       |
| aktif    | boolean             | default true; nonaktif tidak bisa login |

### 5.2 `klien`

| Kolom                  | Tipe         | Null | Catatan                                                         |
| ---------------------- | ------------ | ---- | --------------------------------------------------------------- |
| id                     | bigint PK    | no   |                                                                 |
| nama                   | varchar(150) | no   | orang/institusi penanggung jawab                                |
| usaha                  | varchar(150) | yes  | nama usaha/brand                                                |
| tipe                   | varchar(20)  | no   | `perusahaan` \| `umkm` \| `perorangan` \| `instansi`            |
| telp                   | varchar(30)  | yes  | nomor kantor bukan WA (jangan dibuat tautan WA)                 |
| wa                     | varchar(20)  | yes  | format 62…; tombol WA hanya kalau ini terisi                    |
| email                  | varchar(150) | yes  |                                                                 |
| alamat                 | varchar(255) | yes  |                                                                 |
| wilayah                | varchar(80)  | yes  | kabupaten/kota                                                  |
| sumber                 | varchar(30)  | no   | `prospek` \| `referral` \| `manual` \| `lama`                   |
| ref_asal               | varchar(100) | yes  | siapa yang mereferensikan                                       |
| prospek_id             | bigint       | yes  | penaut ke `prospects.id` (lintas app, tanpa FK)                 |
| status                 | varchar(20)  | no   | `prospek` \| `klien_aktif` \| `tidak_aktif` \| `pelanggan_lama` |
| sejak                  | date         | yes  | tanggal jadi klien                                              |
| catatan                | text         | yes  |                                                                 |
| created_at, updated_at | timestamp    | yes  |                                                                 |

Index: `status`, `nama`, `prospek_id`.

### 5.3 `proyek`

| Kolom                  | Tipe          | Null | Catatan                                            |
| ---------------------- | ------------- | ---- | -------------------------------------------------- |
| id                     | bigint PK     | no   |                                                    |
| klien_id               | bigint        | no   | FK → `klien.id`, `cascadeOnDelete`                 |
| kode                   | varchar(30)   | no   | UNIQUE, format `DK-YYYY-NNN` (mis. `DK-2026-014`)  |
| nama                   | varchar(150)  | no   |                                                    |
| jenis                  | varchar(20)   | no   | `website` \| `aplikasi` \| `maintenance` \| `lain` |
| deskripsi              | text          | yes  | ringkas; detail di `brief`                         |
| brief                  | longtext      | yes  | kebutuhan dari klien                               |
| nilai_kontrak          | decimal(14,2) | no   | default 0                                          |
| status                 | varchar(20)   | no   | lihat §3.2                                         |
| prioritas              | varchar(10)   | no   | `rendah` \| `normal` \| `tinggi`, default `normal` |
| penanggung_jawab       | bigint        | yes  | FK → `users.id`, `nullOnDelete`                    |
| mulai_at               | date          | yes  |                                                    |
| tenggat_at             | date          | yes  | janji ke klien                                     |
| selesai_at             | date          | yes  |                                                    |
| ditahan_alasan         | varchar(255)  | yes  | wajib bila status `ditahan`                        |
| domain                 | varchar(150)  | yes  | domain utama hasil pekerjaan                       |
| repo                   | varchar(200)  | yes  | tautan git (bila aplikasi)                         |
| staging_url            | varchar(200)  | yes  |                                                    |
| produksi_url           | varchar(200)  | yes  |                                                    |
| wscrm_order_id         | bigint        | yes  | tautan order di WSCRM (tanpa FK)                   |
| created_at, updated_at | timestamp     | yes  |                                                    |

Index: `klien_id`, `status`, `tenggat_at`, UNIQUE `kode`.

### 5.4 `proyek_tahapan`

| Kolom      | Tipe         | Null | Catatan                                          |
| ---------- | ------------ | ---- | ------------------------------------------------ |
| id         | bigint PK    | no   |                                                  |
| proyek_id  | bigint       | no   | FK → `proyek.id`, cascade                        |
| urutan     | smallint     | no   | 1..n                                             |
| nama       | varchar(100) | no   | dari template                                    |
| status     | varchar(20)  | no   | `belum` \| `berjalan` \| `selesai` \| `dilewati` |
| mulai_at   | date         | yes  |                                                  |
| tenggat_at | date         | yes  |                                                  |
| selesai_at | date         | yes  |                                                  |
| siapa      | bigint       | yes  | FK → `users.id`                                  |
| catatan    | varchar(500) | yes  |                                                  |

Index: (`proyek_id`, `urutan`) UNIQUE.

### 5.5 `aktivitas` (linimasa semua entitas)

| Kolom                  | Tipe         | Null | Catatan                                                                                    |
| ---------------------- | ------------ | ---- | ------------------------------------------------------------------------------------------ |
| id                     | bigint PK    | no   |                                                                                            |
| jenis_entitas          | varchar(20)  | no   | `klien` \| `proyek` \| `tahapan` \| `tagihan`                                              |
| entitas_id             | bigint       | no   |                                                                                            |
| tipe                   | varchar(30)  | no   | `catatan`, `panggilan`, `wa`, `email`, `telepon`, `pertemuan`, `mengubah_status`, `unggah` |
| judul                  | varchar(150) | yes  |                                                                                            |
| isi                    | text         | yes  |                                                                                            |
| user_id                | bigint       | yes  | pelaku                                                                                     |
| pada                   | datetime     | no   | default now                                                                                |
| created_at, updated_at | timestamp    | yes  |                                                                                            |

Index: (`jenis_entitas`, `entitas_id`, `pada`).

### 5.6 `tagihan` (catatan operasional, **bukan** invoice pajak)

| Kolom                  | Tipe          | Null | Catatan                                                    |
| ---------------------- | ------------- | ---- | ---------------------------------------------------------- |
| id                     | bigint PK     | no   |                                                            |
| proyek_id              | bigint        | yes  | FK → `proyek.id`, cascade; null = tagihan non-proyek       |
| klien_id               | bigint        | no   | FK → `klien.id`, cascade                                   |
| jenis                  | varchar(20)   | no   | `dp` \| `pelunasan` \| `termin` \| `bulanan` \| `tambahan` |
| termin_ke              | tinyint       | yes  | urutan termin                                              |
| uraian                 | varchar(200)  | no   |                                                            |
| nominal                | decimal(14,2) | no   |                                                            |
| jatuh_tempo            | date          | no   |                                                            |
| status                 | varchar(20)   | no   | `belum` \| `sebagian` \| `lunas` \| `batal`                |
| dibayar_total          | decimal(14,2) | no   | default 0, dihitung dari `tagihan_bayar`                   |
| metode                 | varchar(20)   | yes  | `transfer` \| `tunai` \| `lain`                            |
| wscrm_invoice_id       | bigint        | yes  | tautan invoice WSCRM (tanpa FK)                            |
| catatan                | text          | yes  |                                                            |
| created_at, updated_at | timestamp     | yes  |                                                            |

Index: `klien_id`, `proyek_id`, `status`, `jatuh_tempo`.

### 5.7 `tagihan_bayar`

| Kolom                  | Tipe          | Null | Catatan                                     |
| ---------------------- | ------------- | ---- | ------------------------------------------- |
| id                     | bigint PK     | no   |                                             |
| tagihan_id             | bigint        | no   | FK → `tagihan.id`, cascade                  |
| tanggal                | date          | no   |                                             |
| nominal                | decimal(14,2) | no   |                                             |
| metode                 | varchar(20)   | no   |                                             |
| bukti_path             | varchar(255)  | yes  | path relatif di `storage/app/public/bukti/` |
| catatan                | varchar(255)  | yes  |                                             |
| created_at, updated_at | timestamp     | yes  |                                             |

### 5.8 `lampiran`

| Kolom                  | Tipe         | Null | Catatan                                       |
| ---------------------- | ------------ | ---- | --------------------------------------------- |
| id                     | bigint PK    | no   |                                               |
| entitas                | varchar(20)  | no   | `klien` \| `proyek` \| `tagihan`              |
| entitas_id             | bigint       | no   |                                               |
| nama_asli              | varchar(200) | no   |                                               |
| path                   | varchar(255) | no   | `storage/app/public/lampiran/<entitas>/<id>/` |
| ukuran                 | int unsigned | no   | byte                                          |
| mime                   | varchar(100) | no   |                                               |
| user_id                | bigint       | yes  | pengunggah                                    |
| created_at, updated_at | timestamp    | yes  |                                               |

### 5.9 `template_tahapan`

| Kolom  | Tipe         | Null | Catatan                                            |
| ------ | ------------ | ---- | -------------------------------------------------- |
| id     | bigint PK    | no   |                                                    |
| jenis  | varchar(20)  | no   | `website` \| `aplikasi` \| `maintenance` \| `lain` |
| urutan | smallint     | no   |                                                    |
| nama   | varchar(100) | no   |                                                    |
| aktif  | boolean      | no   | default true                                       |

Diseeder dari §3.1. Dipakai saat proyek baru dibuat; sesudah itu proyek menyimpan salinannya sendiri
(mengubah template tidak mengubah proyek yang sudah jalan).

### 5.10 `pengaturan` (key-value)

| Kolom      | Tipe           | Catatan                |
| ---------- | -------------- | ---------------------- |
| kunci      | varchar(60) PK |                        |
| nilai      | text           |                        |
| keterangan | varchar(200)   | untuk layar pengaturan |

Kunci awal: `nama_usaha`, `alamat_usaha`, `telp_usaha`, `email_usaha`, `bank_nama`, `bank_rekening`,
`bank_atas_nama`, `dp_persen_default` (default 50), `termin_default` (default 3),
`hari_ingat_tagihan` (default 7), `zona_waktu` (`Asia/Jakarta`).

### 5.11 Di luar database

| Hal                     | Keputusan                                                                                                                                      |
| ----------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| Soft delete             | Tidak. Hapus = hapus, kecuali `klien` yang ditolak bila masih punya proyek aktif (`restrict`). Butuh arsip → pakai status, bukan `deleted_at`. |
| Riwayat perubahan kolom | Tidak ada tabel auditori. Yang penting (ubah status, ubah nominal) ditulis ke `aktivitas`.                                                     |
| Nomor invoice           | **Tidak dibuat di CRM.** Kalau nanti butuh, ambil dari WSCRM.                                                                                  |
| Pencarian teks penuh    | Fase 1 pakai `LIKE`. Bila lambat (>5.000 baris), baru pertimbangkan FULLTEXT.                                                                  |

---

## 6. Aturan keuangan (ringkas, eksplisit)

1. Sisa tagihan = `nominal − dibayar_total`.
2. `status` tagihan dihitung ulang setiap ada baris `tagihan_bayar` baru:
   `dibayar_total = 0` → `belum`; `0 < dibayar_total < nominal` → `sebagian`; `>= nominal` → `lunas`.
   Status tidak diisi manual (kecuali `batal`).
3. `tagihan.dibayar_total` adalah kolom cache yang **ditulis ulang** dari `tagihan_bayar`
   (bukan ditambah), supaya tidak ada selisih karena dua orang membuka halaman bersamaan.
4. Pembayaran tidak boleh melebihi sisa tagihan; kalau lebih, sistem menolak dan menyarankan
   membuat tagihan baru (mencegah angka lunas yang salah).
5. Ringkasan yang ditampilkan: nilai kontrak aktif, sudah ditagih, sudah dibayar, **belum dibayar
   (piutang)**, dan yang jatuh tempo ≤ 7 hari.
6. Nominal ditampilkan `Rp 12.500.000`, titik sebagai pemisah ribuan, **tanpa** desimal bila bulat.
7. Uang **tidak pernah** dikirim ke aplikasi lain. CRM menampilkan; WSCRM menagih.

---

## 7. Halaman & spesifikasi layar

Pola UI mengikuti Marketing Tools: sidebar kiri (menu + nama pengguna + Keluar), judul halaman di
atas, daftar panjang di tabel, **detail di modal** yang dimuat lewat `fetch` ke rute `*/detail`
(konvensi sudah berjalan, lihat `layouts/app.blade.php` padanannya). Halaman baru di app ini memakai
komponen Vue, bukan Blade.

Tabel wajib: header **tidak** `position:sticky` (menutupi baris pertama dan menahan klik).
Form yang memuat berkas **wajib** `forceFormData: true`.

### 7.1 `/` Dasbor

- Kartu: klien aktif, proyek berjalan, **tahap yang lewat tenggat** (merah), tagihan jatuh tempo ≤7
  hari, piutang total.
- Daftar "Perlu tindakan hari ini": proyek lewat tenggat, tagihan jatuh tempo, klien `ditahan`.
- Linimasa 10 aktivitas terakhir.
- Tidak ada tombol "segarkan": halaman harus **menyegarkan sendiri** (tick 10 detik untuk bagian
  angka), sesuai preferensi user.

### 7.2 `/klien`

- Filter: pencarian (nama/usaha/telp/email/domain), status, wilayah; urut berdasarkan aktivitas
  terakhir.
- Kolom: nama, usaha, kontak (WA/telepon sesuai aturan), proyek berjalan, nilai belum dibayar,
  status, terakhir dihubungi.
- Aksi baris: klik → modal detail (profil, proyek, tagihan, lampiran, linimasa, tombol WhatsApp).
- Tombol: **Impor dari Prospek**, Unduh CSV.

### 7.3 `/proyek`

- Papan **Kanban** per status + tombol "Daftar" (tabel) untuk melihat tenggat menumpuk.
- Kartu: kode, klien, jenis, tenggat (warna bila lewat), penanggung jawab, progres tahap (mis. 3/6).
- Filter: jenis, status, penanggung jawab, tenggat (minggu ini / lewat / tanpa tenggat).

### 7.4 `/proyek/{kode}` — halaman kerja utama

- Header: kode, nama, klien, badges status/tenggat.
- Bilah progres + daftar tahapan (ubah status, tenggat, catatan, pelaku).
- Kotak tindakan cepat: catat aktivitas, unggah berkas, buat tagihan, catat pembayaran.
- Tab: **Ringkasan**, **Tahapan**, **Tagihan**, **Lampiran**, **Linimasa**.
- Tautan keluar: repo, staging, produksi, order WSCRM (kalau ada).

### 7.5 `/tagihan`

- Filter: status, bulan jatuh tempo, jenis.
- Kolom: klien, proyek, uraian, nominal, dibayar, sisa, jatuh tempo (merah bila lewat), status.
- Aksi: catat pembayaran (modal, boleh sebagian, boleh unggah bukti), tandai batal, unduh CSV.
- Ringkasan atas: belum dibayar, jatuh tempo 7 hari, sudah dibayar bulan ini.

### 7.6 `/pengaturan`

- Profil usaha (dipakai di kop ekspor & halaman klien), rekening, DP default, jumlah termin,
  hari pengingat tagihan.
- Pengguna (admin): tambah/ubah/nonaktifkan, set peran.
- Template tahapan per jenis proyek.

### 7.7 Fase 5 — `/p/{token}` Portal Klien (read-only)

- Halaman publik, tanpa login, token acak 32 karakter di `proyek.token_portal`.
- Isi: nama proyek, tahap + progres, tenggat, linimasa versi ringkas, tombol WhatsApp ke user.
- **Tidak** menampilkan: nilai kontrak, tagihan, catatan internal, berkas internal.
- Token bisa dicabut (set null) dan kedaluwarsa sendiri bila proyek `selesai` > 60 hari.

---

## 8. Aturan teknis

### 8.1 Rute

Semua di dalam `Route::middleware('auth')`, kecuali `/masuk` dan portal klien. Pola nama rute
Indonesia mengikuti app lama: `klien`, `klien.detail`, `proyek`, `proyek.tahapan.ubah`, `tagihan.bayar`.
Pisahkan rute `*/detail` yang mengembalikan **partial HTML/JSON untuk modal** dari rute halaman penuh.

### 8.2 Penamaan kode di tampilan

- Format kode proyek: `DK-<tahun>-<3 digit>`, dihitung dari proyek terakhir tahun itu, dibuat di
  dalam transaksi DB (bukan `count()+1` di luar transaksi).
- Nama menu: Dasbor, Klien, Proyek, Tagihan, Pengaturan.

### 8.3 Tempat berkas

Pola flat-deploy yang sudah dipakai app ini: web root menunjuk ke `public/`, sumber ada di dalamnya,
`storage:link` dipakai untuk lampiran. Batas unggah: **20 MB per berkas**, hanya
`pdf, jpg, jpeg, png, webp, docx, xlsx, zip`. Nama berkas disimpan sebagai hash + nama asli di DB.

### 8.4 Keamanan

- Semua halaman `noindex, nofollow` (sudah di layout induk).
- Setiap handler yang menerima `{id}` wajib memeriksa kepemilikan/kaitan lewat relasi
  (`abort_if($x->klien_id !== $y->id, 404)` — pola yang sudah dipakai di app lama).
- Unggahan: validasi MIME + ekstensi, disimpan di luar web root, tidak pernah dieksekusi.
- Portal klien: token acak `Str::random(48)`, dibandingkan dengan `hash_equals`; rate limit 60/menit.
- Tanpa `APP_DEBUG` di produksi; tanpa data klien di berkas log.

### 8.5 Kinerja

VM ini RAM 3,6 GB dan dipakai bersama Hermes, WSD Shield, mail, DNS, dan dua app Laravel lain.

- Pool PHP-FPM `dkapp` dipakai bersama: `pm=ondemand`, `max_children=3`. Jangan tambah pool baru.
- Semua daftar wajib `paginate()` (25/halaman), jangan `get()` seluruh tabel.
- Eager loading relasi di setiap daftar (`with([...])`), jangan biarkan N+1.
- Indeks sesuai §5; setiap tambah filter baru, cek `EXPLAIN` pada data ≥5.000 baris.

### 8.6 Integrasi

| Sumber                      | Arah               | Cara                                                                                                                                                     |
| --------------------------- | ------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| WSCRM `/api/dk/*`           | Masuk, baca saja   | Token mesin di `.env` (`WSCRM_API_TOKEN`), filter klien lewat `DK_KLIEN_IDS`. Dipakai untuk menampilkan layanan aktif & invoice terkait. **Cache 1 jam** |
| Marketing Tools `prospects` | Masuk sekali jalan | Impor manual "Jadikan Klien"; menyimpan `prospek_id`, tidak menyinkron terus-menerus                                                                     |
| WhatsApp                    | Keluar             | Hanya `https://wa.me/62…` dengan teks terisi. Tidak ada API WhatsApp                                                                                     |
| Email pengingat             | Keluar             | Fase 4. Mailer `log` dulu sampai user menyetujui pengiriman nyata                                                                                        |

**Larangan**: CRM tidak pernah menulis ke database WSCRM.

### 8.7 Waktu

`config('app.timezone')` = `UTC`; semua tampilan dikonversi eksplisit ke `Asia/Jakarta`. Nilai waktu
dari API luar yang sudah berformat lokal **wajib** `->utc()` sebelum disimpan (kalau tidak, tampil
+7 jam — bug ini pernah terjadi di WSCRM).

### 8.8 Ekspor

CSV (UTF-8 BOM, pemisah koma, tanpa desimal pada rupiah) untuk: klien, proyek, tagihan, pembayaran.
Nama berkas: `<entitas>-digitalkonsultan-<YYYYMMDD>.csv`.

---

## 9. Rencana fase

| Fase                 | Isi                                                                                                | Hasil yang bisa dilihat                               |
| -------------------- | -------------------------------------------------------------------------------------------------- | ----------------------------------------------------- |
| **1 — Inti**         | Login + peran, `klien`, impor dari prospek, daftar & detail klien                                  | Klien bisa dicatat & dilihat; prospek bisa jadi klien |
| **2 — Proyek**       | `proyek`, `proyek_tahapan` + template, aktivitas, lampiran, halaman kerja `/proyek/{kode}`, Kanban | "Proyek sudah sampai mana" terjawab                   |
| **3 — Uang**         | `tagihan`, `tagihan_bayar`, halaman tagihan, ringkasan piutang, ekspor                             | Tagihan & pembayaran terkendali                       |
| **4 — Otomatisasi**  | Pengingat jatuh tempo (cron), penarikan layanan/invoice dari WSCRM + cache, pengaturan lengkap     | Tidak ada tagihan yang terlewat                       |
| **5 — Portal klien** | `/p/{token}`, penulisan aktivitas, pelaporan mingguan                                              | Klien bisa memantau sendiri                           |

Setiap fase berhenti di titik yang bisa dipakai. Tidak ada fase "setengah jadi" di produksi.

---

## 10. Kriteria penerimaan (uji terima per fase)

**Fase 1**

1. `/klien` memuat ≤2 detik dengan 500 klien; paginasi jalan.
2. "Jadikan Klien" dari prospek mengisi nama/usaha/telp/wa/email/wilayah dan menyimpan `prospek_id`.
3. Staf **tidak** bisa membuka Pengaturan (403/redirect), admin bisa.
4. Nomor kantor `0274…` muncul sebagai tautan telepon, bukan tautan WA.
5. CSV klien terunduh dan bisa dibuka di Excel tanpa karakter rusak.

**Fase 2**

1. Proyek baru otomatis punya tahapan sesuai template jenisnya, dalam urutan benar.
2. Menandai tahap `selesai` mengisi `selesai_at` + menulis satu aktivitas.
3. Status proyek `ditahan` tanpa alasan ditolak dengan pesan yang bisa dibaca.
4. Tahap lewat tenggat muncul merah di Kanban dan di dasbor.
5. Unggah PDF 5 MB berhasil dan berkasnya bisa diunduh ulang oleh admin (bukti: baris `lampiran` + berkas ada di disk).

**Fase 3**

1. DP 50% dari nilai kontrak membuat tepat 2 tagihan (DP + pelunasan).
2. Pembayaran sebagian mengubah status `belum → sebagian` tanpa intervensi manual.
3. Pembayaran melebihi sisa ditolak, pesan menyebut sisa yang benar.
4. Piutang di dasbor = Σ(nominal − dibayar_total) untuk semua tagihan non-batal. Angka ini dicek
   manual dengan `SELECT SUM(...)`.
5. Menutup proyek yang masih punya tagihan `belum` ditolak.

**Fase 4**

1. Cron mengirim daftar tagihan jatuh tempo 7 hari ke email user (mode `log` saat uji).
2. Penarikan data WSCRM gagal → halaman tetap tampil dengan penanda "data per <waktu cache>",
   bukan error 500.

**Fase 5**

1. Buka `/p/{token}` di mode incognito → tidak ada nilai kontrak/tagihan/catatan internal di HTML.
2. Token dicabut → 404.
3. Portal memuat ≤2 detik di jaringan seluler.

---

## 11. Risiko & jebakan yang sudah diketahui

| Risiko                                                               | Mitigasi                                                                                                          |
| -------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| Dua sumber data klien (WSCRM vs CRM) → klien ganda                   | CRM tidak menulis ke WSCRM; `wscrm_order_id` sebagai penaut; saat impor, cocokkan nama + domain dulu              |
| Nominal uang bergeser karena `float`                                 | `decimal(14,2)`, uji dengan angka besar (Rp999.999.999)                                                           |
| Data masuk hanya dari kepala user, riwayat hilang                    | Impor awal dari prospek + CSV lama sebelum fase 2 dimulai                                                         |
| RAM habis karena pool PHP baru                                       | Pakai pool `dkapp` yang ada; `pm=ondemand`                                                                        |
| Berkas milik `www-data` → `write_file` agen kena _Permission denied_ | Tulis ke `/tmp` lalu `sudo cp` + `sudo chown www-data:www-data`                                                   |
| Artisan gagal karena `HOME`                                          | `sudo -u www-data HOME=/var/www php artisan …`                                                                    |
| Bayar sebelum ada uang masuk tercatat sebagai lunas                  | Bukti pembayaran opsional tapi **disarankan**; status lunas tanpa bukti tetap boleh, tercatat siapa yang menandai |
| Perubahan `users` (kolom `peran`) memengaruhi starter kit            | Migrasi terpisah + default `staf`; uji login admin & staf setelah deploy                                          |

---

## 12. Pertanyaan terbuka (butuh keputusan user sebelum fase terkait)

1. **Fase 3** — DP default tetap 50%? Termin untuk aplikasi: 3 termin (30/40/30) atau bebas?
2. **Fase 4** — pengingat dikirim ke email mana, dan apakah perlu juga muncul di Telegram?
3. **Fase 5** — portal klien benar-benar dipakai, atau lebih baik cukup kirim tautan laporan
   mingguan (seperti laporan pemeliharaan yang sudah jalan)?
4. Perlukah kolom **biaya/modal per proyek** (untuk melihat laba per proyek), atau nilai kontrak saja?
5. Impor data klien lama: dari CSV lama, dari WSCRM lewat API, atau diketik manual?
6. Apakah staf perlu bisa melihat nilai kontrak, atau disembunyikan untuk mereka?

---

## Lampiran A — Rute (rencana)

```
GET    /                          dasbor
GET    /masuk                     login
POST   /masuk

GET    /klien                     klien              (daftar, filter, paginasi)
GET    /klien/unduh               klien.unduh        (CSV)
GET    /klien/baru                klien.baru
POST   /klien                     klien.simpan
GET    /klien/{klien}/detail      klien.detail       (modal)
GET    /klien/{klien}/ubah        klien.ubah
PUT    /klien/{klien}             klien.perbarui
DELETE /klien/{klien}             klien.hapus
POST   /klien/impor-prospek       klien.impor

GET    /proyek                    proyek
GET    /proyek/baru               proyek.baru
POST   /proyek                    proyek.simpan
GET    /proyek/{kode}             proyek.lihat
PUT    /proyek/{kode}             proyek.perbarui
PATCH  /proyek/{kode}/status      proyek.status
PATCH  /proyek/{kode}/tahapan/{t} proyek.tahapan.ubah
POST   /proyek/{kode}/aktivitas   proyek.aktivitas.simpan
POST   /proyek/{kode}/lampiran    proyek.lampiran.simpan
GET    /proyek/{kode}/lampiran/{l} proyek.lampiran.unduh
DELETE /proyek/{kode}/lampiran/{l} proyek.lampiran.hapus
POST   /proyek/{kode}/portal      proyek.portal.terbit
DELETE /proyek/{kode}/portal      proyek.portal.cabut

GET    /tagihan                   tagihan
POST   /tagihan                   tagihan.simpan
PUT    /tagihan/{tagihan}         tagihan.perbarui
POST   /tagihan/{tagihan}/bayar   tagihan.bayar
DELETE /tagihan/{tagihan}/bayar/{b} tagihan.bayar.hapus
GET    /tagihan/unduh             tagihan.unduh

GET    /pengaturan                pengaturan
PUT    /pengaturan                pengaturan.simpan
GET    /pengaturan/pengguna       pengguna
POST   /pengaturan/pengguna       pengguna.simpan

GET    /p/{token}                 portal.lihat       (publik, read-only)
```

## Lampiran B — Model & relasi

```
User            hasMany Proyek (penanggung_jawab), hasMany Aktivitas
Klien           hasMany Proyek, hasMany Tagihan, morphMany Aktivitas/Lampiran
Proyek          belongsTo Klien, belongsTo User, hasMany Tahapan, hasMany Tagihan,
                morphMany Aktivitas/Lampiran
ProyekTahapan   belongsTo Proyek
Tagihan         belongsTo Klien, belongsTo Proyek, hasMany Pembayaran
TagihanBayar    belongsTo Tagihan
Lampiran        morphTo entitas
Pengaturan      kunci/nilai (static helper: Pengaturan::ambil('dp_persen_default'))
```

## Lampiran C — Perubahan skema di app lama (tidak dilakukan di sini)

Kolom `prospek_id` mengarah ke `prospects.id` milik Marketing Tools (DB `dkmarketing`). Bila nanti
kedua app disatukan, `prospek_id` bisa dijadikan foreign key sungguhan — untuk sekarang dibiarkan
tanpa FK, dan integritasnya dijaga saat impor.
