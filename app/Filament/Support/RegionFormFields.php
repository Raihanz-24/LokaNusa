<?php

namespace App\Filament\Support;

use App\Support\RegionResolver;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class RegionFormFields
{
    /**
     * Dropdown kecamatan dalam kabupaten/kota fokus.
     *
     * Bila diberi `$villageField`, memilih kecamatan akan mengosongkan desa.
     */
    public static function kecamatan(string $name = 'region_id', string $label = 'Kecamatan', ?string $villageField = null): Select
    {
        $select = Select::make($name)
            ->label($label)
            ->options(fn (): array => RegionResolver::kecamatanOptions()
                ->pluck('name', 'id')
                ->all())
            ->searchable()
            ->native(false)
            ->live()
            ->placeholder(fn (): string => RegionResolver::focus() === null
                ? 'Kabupaten fokus belum ditetapkan'
                : 'Pilih kecamatan')
            ->disabled(fn (): bool => RegionResolver::focus() === null)
            // Validasi server-side: hanya menerima kecamatan dalam kabupaten fokus.
            ->rule(fn (): \Closure => function (string $attribute, $value, \Closure $fail): void {
                $focus = RegionResolver::focus();

                if ($focus === null) {
                    $fail('Developer belum menetapkan kabupaten/kota fokus.');

                    return;
                }

                if (filled($value) && ! in_array((int) $value, RegionResolver::kecamatanIds(), true)) {
                    $fail('Kecamatan tidak berada dalam kabupaten/kota fokus.');
                }
            })
            ->helperText(function (): ?string {
                $focus = RegionResolver::focus();

                return $focus === null
                    ? 'Developer belum menetapkan kabupaten/kota fokus. Hubungi admin.'
                    : "Dalam lingkup {$focus->name}.";
            });

        if ($villageField !== null) {
            $select->afterStateUpdated(function (Set $set) use ($villageField): void {
                $set($villageField, null);
            });
        }

        return $select;
    }

    /**
     * Dropdown desa/kelurahan, bergantung pada kecamatan terpilih.
     */
    public static function desa(string $name = 'village_id', string $kecamatanField = 'region_id', string $label = 'Desa/Kelurahan'): Select
    {
        return Select::make($name)
            ->label($label)
            ->options(fn (Get $get): array => RegionResolver::desaOptions(
                filled($get($kecamatanField)) ? (int) $get($kecamatanField) : null
            )->pluck('name', 'id')->all())
            ->searchable()
            ->native(false)
            ->disabled(fn (Get $get): bool => blank($get($kecamatanField)))
            ->placeholder(fn (Get $get): string => blank($get($kecamatanField))
                ? 'Pilih kecamatan dulu'
                : 'Pilih desa/kelurahan')
            ->helperText('Bila desa tidak ada di daftar, jalankan: php artisan wilayah:import-villages.')
            // Validasi server-side: desa harus berada dalam kecamatan terpilih.
            ->rule(fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $kecamatanField): void {
                if (filled($value) && ! in_array((int) $value, RegionResolver::desaIds(
                    filled($get($kecamatanField)) ? (int) $get($kecamatanField) : null
                ), true)) {
                    $fail('Desa/kelurahan tidak berada dalam kecamatan terpilih.');
                }
            });
    }
}
