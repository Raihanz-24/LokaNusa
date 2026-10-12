<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;

/**
 * Field jam operasional yang dapat dipakai ulang (destinasi, UMKM, penginapan).
 */
class OpeningHours
{
    public static function repeater(string $name = 'opening_hours', string $label = 'Jam Operasional'): Repeater
    {
        return Repeater::make($name)
            ->label($label)
            ->schema([
                Select::make('day')
                    ->label('Hari')
                    ->options(self::dayOptions())
                    ->required()
                    ->native(false),

                TimePicker::make('open')->label('Buka')->seconds(false),
                TimePicker::make('close')->label('Tutup')->seconds(false),
                Toggle::make('closed')->label('Tutup')->inline(false),
            ])
            ->columns(4)
            ->defaultItems(0)
            ->addActionLabel('Tambah baris')
            ->columnSpanFull();
    }

    /**
     * @return array<string, string>
     */
    public static function dayOptions(): array
    {
        return [
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
            'sabtu' => 'Sabtu',
            'minggu' => 'Minggu',
        ];
    }
}
