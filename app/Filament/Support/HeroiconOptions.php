<?php

namespace App\Filament\Support;

/**
 * Kumpulan opsi ikon Heroicon (nama konstanta tanpa prefiks "Outlined")
 * yang dipakai pada form shortcut beranda.
 */
class HeroiconOptions
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        return [
            'Jelajah & Wisata' => [
                'Map' => 'Peta / Destinasi',
                'MapPin' => 'Lokasi',
                'GlobeAlt' => 'Jelajah',
                'Ticket' => 'Tiket / Event',
                'CalendarDays' => 'Kalender / Event',
                'Photo' => 'Galeri',
                'Star' => 'Favorit',
                'Heart' => 'Suka',
            ],
            'Budaya & Edukasi' => [
                'BuildingLibrary' => 'Budaya / Perpustakaan',
                'AcademicCap' => 'Edukasi',
                'Sparkles' => 'AI / Rekomendasi',
                'InformationCircle' => 'Informasi',
            ],
            'UMKM & Layanan' => [
                'ShoppingBag' => 'Produk / UMKM',
                'ShoppingCart' => 'Belanja / Kuliner',
                'BuildingStorefront' => 'Toko / Restoran',
                'Truck' => 'Transportasi',
                'Wrench' => 'Jasa',
                'Briefcase' => 'Layanan',
                'Phone' => 'Kontak',
                'Gift' => 'Promo',
            ],
            'Umum' => [
                'Home' => 'Beranda',
                'Users' => 'Komunitas',
                'Bolt' => 'Cepat',
                'Clock' => 'Waktu',
            ],
        ];
    }
}
