<?php

namespace App\Filament\Resources\HomeShortcuts\Schemas;

use App\Filament\Support\HeroiconOptions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HomeShortcutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Shortcut')
                    ->columns(2)
                    ->schema([
                        TextInput::make('label')
                            ->label('Label')
                            ->required()
                            ->maxLength(50),

                        Select::make('icon')
                            ->label('Ikon')
                            ->options(HeroiconOptions::grouped())
                            ->searchable()
                            ->native(false)
                            ->helperText('Ikon otomatis dari Heroicons.'),

                        TextInput::make('url')
                            ->label('Tautan')
                            ->maxLength(255)
                            ->helperText('Contoh: /destinasi, /umkm, /budaya, /penginapan, /peta.')
                            ->columnSpanFull(),

                        TextInput::make('key')
                            ->label('Kode')
                            ->maxLength(50)
                            ->helperText('Opsional, penanda fitur (mis. ai-trip-planner).'),

                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
