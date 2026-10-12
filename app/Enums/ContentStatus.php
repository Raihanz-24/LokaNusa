<?php

namespace App\Enums;

/**
 * Status publikasi & moderasi generik untuk seluruh konten
 * (destinasi, UMKM, produk, kuliner, penginapan).
 *
 * Alur: draft → pending → published (atau rejected), published → archived.
 */
enum ContentStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Pending => 'Menunggu Review',
            self::Published => 'Terbit',
            self::Rejected => 'Ditolak',
            self::Archived => 'Diarsipkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Published => 'success',
            self::Rejected => 'danger',
            self::Archived => 'gray',
        };
    }

    /**
     * Apakah konten terlihat di sisi publik.
     */
    public function isPublic(): bool
    {
        return $this === self::Published;
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
