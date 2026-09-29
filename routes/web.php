<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DasborController;
use App\Http\Controllers\DasborDataController;
use App\Http\Controllers\KlienController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProyekController;
use App\Http\Controllers\TagihanController;
use Illuminate\Support\Facades\Route;

Route::get('/masuk', [AuthController::class, 'form'])->name('login');
Route::post('/masuk', [AuthController::class, 'masuk'])->middleware('throttle:10,1');
Route::post('/keluar', [AuthController::class, 'keluar'])->name('keluar');

// Portal klien — publik, hanya baca, tanpa data keuangan.
Route::get('/p/{token}', [PortalController::class, 'lihat'])
    ->middleware('throttle:60,1')->name('portal.lihat');

Route::middleware('auth', 'peran')->group(function () {
    Route::get('/', [DasborController::class, 'index'])->name('dasbor');
    Route::get('/dasbor/data', DasborDataController::class)->name('dasbor.data');

    // Klien
    Route::get('/klien', [KlienController::class, 'index'])->name('klien');
    Route::get('/klien/unduh', [KlienController::class, 'unduh'])->name('klien.unduh');
    Route::get('/klien/baru', [KlienController::class, 'formBaru'])->name('klien.baru');
    Route::post('/klien', [KlienController::class, 'simpan'])->name('klien.simpan');
    Route::get('/klien/impor', [KlienController::class, 'formImpor'])->name('klien.impor');
    Route::post('/klien/impor', [KlienController::class, 'impor'])->name('klien.impor.simpan');
    Route::get('/klien/{klien}/detail', [KlienController::class, 'detail'])->name('klien.detail');
    Route::get('/klien/{klien}/ubah', [KlienController::class, 'formUbah'])->name('klien.ubah');
    Route::put('/klien/{klien}', [KlienController::class, 'perbarui'])->name('klien.perbarui');
    Route::delete('/klien/{klien}', [KlienController::class, 'hapus'])->name('klien.hapus');
    Route::post('/klien/{klien}/aktivitas', [KlienController::class, 'catatAktivitas'])->name('klien.aktivitas');
    Route::post('/klien/{klien}/lampiran', [KlienController::class, 'unggahLampiran'])->name('klien.lampiran');
    Route::get('/lampiran/{lampiran}/unduh', [KlienController::class, 'unduhLampiran'])->name('lampiran.unduh');
    Route::delete('/lampiran/{lampiran}', [KlienController::class, 'hapusLampiran'])->name('lampiran.hapus');

    // Proyek
    Route::get('/proyek', [ProyekController::class, 'index'])->name('proyek');
    Route::get('/proyek/unduh', [ProyekController::class, 'unduh'])->name('proyek.unduh');
    Route::get('/proyek/baru', [ProyekController::class, 'formBaru'])->name('proyek.baru');
    Route::post('/proyek', [ProyekController::class, 'simpan'])->name('proyek.simpan');
    Route::get('/proyek/{proyek}', [ProyekController::class, 'lihat'])->name('proyek.lihat');
    Route::get('/proyek/{proyek}/detail', [ProyekController::class, 'detail'])->name('proyek.detail');
    Route::get('/proyek/{proyek}/ubah', [ProyekController::class, 'formUbah'])->name('proyek.ubah');
    Route::put('/proyek/{proyek}', [ProyekController::class, 'perbarui'])->name('proyek.perbarui');
    Route::delete('/proyek/{proyek}', [ProyekController::class, 'hapus'])->name('proyek.hapus');
    Route::patch('/proyek/{proyek}/status', [ProyekController::class, 'ubahStatus'])->name('proyek.status');
    Route::patch('/proyek/{proyek}/tahapan/{tahapan}', [ProyekController::class, 'ubahTahapan'])->name('proyek.tahapan');
    Route::post('/proyek/{proyek}/aktivitas', [ProyekController::class, 'catatAktivitas'])->name('proyek.aktivitas');
    Route::post('/proyek/{proyek}/lampiran', [ProyekController::class, 'unggahLampiran'])->name('proyek.lampiran');
    Route::post('/proyek/{proyek}/portal', [ProyekController::class, 'terbitPortal'])->name('proyek.portal.terbit');
    Route::delete('/proyek/{proyek}/portal', [ProyekController::class, 'cabutPortal'])->name('proyek.portal.cabut');

    // Tagihan
    Route::get('/tagihan', [TagihanController::class, 'index'])->name('tagihan');
    Route::get('/tagihan/unduh', [TagihanController::class, 'unduh'])->name('tagihan.unduh');
    Route::get('/tagihan/baru', [TagihanController::class, 'formBaru'])->name('tagihan.baru');
    Route::post('/tagihan', [TagihanController::class, 'simpan'])->name('tagihan.simpan');
    Route::get('/tagihan/{tagihan}/ubah', [TagihanController::class, 'formUbah'])->name('tagihan.ubah');
    Route::put('/tagihan/{tagihan}', [TagihanController::class, 'perbarui'])->name('tagihan.perbarui');
    Route::delete('/tagihan/{tagihan}', [TagihanController::class, 'hapus'])->name('tagihan.hapus');
    Route::post('/tagihan/{tagihan}/bayar', [TagihanController::class, 'bayar'])->name('tagihan.bayar');
    Route::delete('/tagihan/{tagihan}/bayar/{bayar}', [TagihanController::class, 'hapusBayar'])->name('tagihan.bayar.hapus');
    Route::post('/tagihan/{tagihan}/batal', [TagihanController::class, 'batal'])->name('tagihan.batal');

    // Pengaturan — admin saja
    Route::middleware('peran:admin')->group(function () {
        Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan');
        Route::put('/pengaturan', [PengaturanController::class, 'simpan'])->name('pengaturan.simpan');
        Route::get('/pengaturan/pengguna', [PenggunaController::class, 'index'])->name('pengguna');
        Route::post('/pengaturan/pengguna', [PenggunaController::class, 'simpan'])->name('pengguna.simpan');
        Route::get('/pengaturan/pengguna/{user}/ubah', [PenggunaController::class, 'formUbah'])->name('pengguna.ubah');
        Route::put('/pengaturan/pengguna/{user}', [PenggunaController::class, 'perbarui'])->name('pengguna.perbarui');
        Route::get('/pengaturan/tahapan', [PengaturanController::class, 'tahapan'])->name('pengaturan.tahapan');
        Route::post('/pengaturan/tahapan', [PengaturanController::class, 'simpanTahapan'])->name('pengaturan.tahapan.simpan');
    });
});
