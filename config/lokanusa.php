<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature Flags LokaNusa
    |--------------------------------------------------------------------------
    |
    | Mengaktifkan/mematikan modul tanpa menghapus kodenya. Atur lewat `.env`.
    | Semua default NONAKTIF (modul disembunyikan) sampai sengaja dinyalakan.
    |
    */

    'features' => [
        // Pemesanan (booking) penginapan & pembelian produk UMKM — DEMO.
        // Saat nonaktif: rute /pesanan mengembalikan 404, form & tombol di
        // halaman publik disembunyikan, menu & resource panel tidak tampil.
        'booking' => (bool) env('FEATURE_BOOKING', false),
    ],

];
