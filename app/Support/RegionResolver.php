<?php

namespace App\Support;

use App\Enums\RegionType;
use App\Models\AppSetting;
use App\Models\Region;
use Illuminate\Support\Collection;

/**
 * Membantu resolusi wilayah berdasarkan kabupaten/kota fokus (AppSetting).
 */
class RegionResolver
{
    /**
     * Kabupaten/kota fokus saat ini.
     */
    public static function focus(): ?Region
    {
        return AppSetting::focusRegion();
    }

    /**
     * Kecamatan yang berada di dalam kabupaten/kota fokus.
     *
     * @return Collection<int, Region>
     */
    public static function kecamatanOptions(): Collection
    {
        $focus = static::focus();

        if ($focus === null) {
            return collect();
        }

        return Region::query()
            ->where('parent_id', $focus->getKey())
            ->orderBy('name')
            ->get();
    }

    /**
     * ID kecamatan valid (dalam fokus). Untuk validasi server-side.
     *
     * @return array<int, int>
     */
    public static function kecamatanIds(): array
    {
        return static::kecamatanOptions()->pluck('id')->all();
    }

    /**
     * Desa/kelurahan di dalam sebuah kecamatan.
     *
     * @return Collection<int, Region>
     */
    public static function desaOptions(?int $kecamatanId): Collection
    {
        if (blank($kecamatanId)) {
            return collect();
        }

        return Region::query()
            ->where('parent_id', $kecamatanId)
            ->where('type', RegionType::DesaKelurahan->value)
            ->orderBy('name')
            ->get();
    }

    /**
     * ID desa valid di dalam sebuah kecamatan.
     *
     * @return array<int, int>
     */
    public static function desaIds(?int $kecamatanId): array
    {
        return static::desaOptions($kecamatanId)->pluck('id')->all();
    }

    /**
     * Cari kecamatan dalam fokus berdasarkan nama (untuk reverse geocoding).
     */
    public static function findKecamatanByName(?string $name): ?Region
    {
        if (blank($name)) {
            return null;
        }

        $needle = static::normalize($name);

        return static::kecamatanOptions()
            ->first(fn (Region $region): bool => static::normalize($region->name) === $needle)
            ?? static::kecamatanOptions()
                ->first(fn (Region $region): bool => str_contains(static::normalize($region->name), $needle)
                    || str_contains($needle, static::normalize($region->name)));
    }

    /**
     * Cari desa dalam sebuah kecamatan berdasarkan nama.
     */
    public static function findDesaByName(?int $kecamatanId, ?string $name): ?Region
    {
        if (blank($name) || blank($kecamatanId)) {
            return null;
        }

        $needle = static::normalize($name);

        return static::desaOptions($kecamatanId)
            ->first(fn (Region $region): bool => static::normalize($region->name) === $needle)
            ?? static::desaOptions($kecamatanId)
                ->first(fn (Region $region): bool => str_contains(static::normalize($region->name), $needle));
    }

    /**
     * Normalisasi nama wilayah (hapus awalan administratif & tanda baca).
     */
    public static function normalize(string $name): string
    {
        $name = mb_strtolower(trim($name));

        $name = preg_replace('/\b(kecamatan|kec|kelurahan|kel|desa|dsn|dusun)\b/u', '', $name) ?? $name;

        return trim(preg_replace('/[^a-z0-9]+/u', ' ', $name) ?? $name);
    }
}
