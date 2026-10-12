<?php

namespace App\Enums;

/**
 * Jenis penginapan.
 */
enum AccommodationType: string
{
    case Hotel = 'hotel';
    case Homestay = 'homestay';
    case GuestHouse = 'guest_house';
    case Villa = 'villa';
    case Resort = 'resort';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Hotel => 'Hotel',
            self::Homestay => 'Homestay',
            self::GuestHouse => 'Guest House',
            self::Villa => 'Villa',
            self::Resort => 'Resort',
            self::Lainnya => 'Lainnya',
        };
    }

    /**
     * Label tampilan publik, mengikuti bahasa aktif.
     */
    public function translatedLabel(): string
    {
        return __('ui.accommodation_types.'.$this->value);
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
