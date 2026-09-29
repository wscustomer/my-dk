<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class Uang
{
    /** "Rp 12.500.000" — tanpa desimal bila bulat. */
    public static function rp(float|int|string|null $nominal): string
    {
        $angka = (float) ($nominal ?? 0);
        $bulat = abs($angka - round($angka)) < 0.005;

        return 'Rp ' . number_format($angka, $bulat ? 0 : 2, ',', '.');
    }

    /** Alias biar tidak ada dua nama untuk satu hal. */
    public static function format(float|int|string|null $nominal): string
    {
        return self::rp($nominal);
    }

    /** Bersihkan input "12.500.000" / "Rp 12.500.000" jadi float. */
    public static function bersih(string|float|int|null $teks): float
    {
        if ($teks === null || $teks === '') {
            return 0.0;
        }

        $teks = (string) $teks;
        $teks = preg_replace('/[^0-9,.\-]/', '', $teks) ?? '';

        // 12.500.000,50 (id) → 12500000.50
        if (str_contains($teks, ',') && str_contains($teks, '.')) {
            $teks = str_replace('.', '', $teks);
            $teks = str_replace(',', '.', $teks);
        } elseif (str_contains($teks, ',')) {
            $teks = str_replace(',', '.', $teks);
        } elseif (substr_count($teks, '.') > 1) {
            $teks = str_replace('.', '', $teks);
        }

        return round((float) $teks, 2);
    }
}
