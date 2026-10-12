<?php

namespace App\Enums;

/**
 * Tingkat wilayah administratif Indonesia.
 *
 * Wilayah bersifat bertingkat (self-referencing) melalui `parent_id`,
 * dari provinsi → kabupaten/kota → kecamatan → desa/kelurahan.
 */
enum RegionType: string
{
    case Provinsi = 'provinsi';
    case KabupatenKota = 'kabupaten_kota';
    case Kecamatan = 'kecamatan';
    case DesaKelurahan = 'desa_kelurahan';

    public function label(): string
    {
        return match ($this) {
            self::Provinsi => 'Provinsi',
            self::KabupatenKota => 'Kabupaten/Kota',
            self::Kecamatan => 'Kecamatan',
            self::DesaKelurahan => 'Desa/Kelurahan',
        };
    }

    /**
     * Tingkat induk yang valid (null = tidak punya induk / level teratas).
     */
    public function parentType(): ?self
    {
        return match ($this) {
            self::Provinsi => null,
            self::KabupatenKota => self::Provinsi,
            self::Kecamatan => self::KabupatenKota,
            self::DesaKelurahan => self::Kecamatan,
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

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
