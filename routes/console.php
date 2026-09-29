<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Harian 07:00 WIB — tagihan jatuh tempo & proyek telat.
Schedule::command('dk:ingatkan --hari=3')->dailyAt('07:00')->timezone('Asia/Jakarta');

// Harian 06:30 WIB — tarik layanan WSCRM.
Schedule::command('dk:tarik-wscrm')->dailyAt('06:30')->timezone('Asia/Jakarta');
