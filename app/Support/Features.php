<?php

namespace App\Support;

/**
 * Feature flags ringan (dibaca dari config/lokanusa.php → .env).
 *
 * Memungkinkan menyembunyikan modul sementara tanpa menghapus kodenya.
 */
class Features
{
    /**
     * Pemesanan (booking) penginapan & pembelian produk UMKM (DEMO).
     */
    public static function booking(): bool
    {
        return (bool) config('lokanusa.features.booking', false);
    }
}
