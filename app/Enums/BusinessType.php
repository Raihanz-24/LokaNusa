<?php

namespace App\Enums;

/**
 * Jenis usaha UMKM. Kuliner/restaurant termasuk di sini sebagai
 * salah satu jenis usaha (keputusan D-008).
 */
enum BusinessType: string
{
    case Produk = 'produk';
    case Kuliner = 'kuliner';
    case Jasa = 'jasa';

    public function label(): string
    {
        return match ($this) {
            self::Produk => 'Produk',
            self::Kuliner => 'Kuliner / Restoran',
            self::Jasa => 'Jasa',
        };
    }

    /**
     * Label tampilan publik, mengikuti bahasa aktif.
     */
    public function translatedLabel(): string
    {
        return __('ui.business_types.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
