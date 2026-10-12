<?php

namespace App\Enums;

/**
 * Jenis event/agenda wisata (Step 12 — Event & Calendar).
 */
enum EventType: string
{
    case Budaya = 'budaya';
    case Kesenian = 'kesenian';
    case Musik = 'musik';
    case Olahraga = 'olahraga';
    case Kuliner = 'kuliner';
    case Pameran = 'pameran';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Budaya => 'Budaya & Adat',
            self::Kesenian => 'Kesenian',
            self::Musik => 'Musik & Hiburan',
            self::Olahraga => 'Olahraga',
            self::Kuliner => 'Kuliner',
            self::Pameran => 'Pameran & Bazar',
            self::Lainnya => 'Lainnya',
        };
    }

    /**
     * Label tampilan publik, mengikuti bahasa aktif.
     */
    public function translatedLabel(): string
    {
        return __('ui.event_types.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Budaya => 'violet',
            self::Kesenian => 'rose',
            self::Musik => 'sky',
            self::Olahraga => 'emerald',
            self::Kuliner => 'amber',
            self::Pameran => 'indigo',
            self::Lainnya => 'gray',
        };
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
