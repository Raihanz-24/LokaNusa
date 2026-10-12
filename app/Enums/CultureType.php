<?php

namespace App\Enums;

/**
 * Jenis konten budaya (LokaCulture).
 */
enum CultureType: string
{
    case Tradisi = 'tradisi';
    case Kesenian = 'kesenian';
    case KulinerTradisional = 'kuliner_tradisional';
    case Sejarah = 'sejarah';
    case Bahasa = 'bahasa';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Tradisi => 'Tradisi & Adat',
            self::Kesenian => 'Kesenian',
            self::KulinerTradisional => 'Kuliner Tradisional',
            self::Sejarah => 'Sejarah',
            self::Bahasa => 'Bahasa & Sastra',
            self::Lainnya => 'Lainnya',
        };
    }

    /**
     * Label tampilan publik, mengikuti bahasa aktif.
     */
    public function translatedLabel(): string
    {
        return __('ui.culture_types.'.$this->value);
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
