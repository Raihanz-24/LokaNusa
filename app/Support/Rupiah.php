<?php

namespace App\Support;

/**
 * Pemformat mata uang Rupiah (tanpa desimal).
 */
class Rupiah
{
    /**
     * Format angka menjadi "Rp1.500.000".
     */
    public static function format(?int $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        return 'Rp'.number_format($amount, 0, ',', '.');
    }

    /**
     * Format untuk rentang/estimasi; mengembalikan "Gratis" untuk nol.
     */
    public static function label(?int $amount): string
    {
        if ($amount === null) {
            return __('ui.cost_unknown');
        }

        if ($amount === 0) {
            return __('ui.free');
        }

        return self::format($amount);
    }
}
