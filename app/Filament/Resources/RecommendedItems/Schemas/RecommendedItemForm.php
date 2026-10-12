<?php

namespace App\Filament\Resources\RecommendedItems\Schemas;

use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\UmkmBusiness;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RecommendedItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item Rekomendasi')
                    ->columns(2)
                    ->schema([
                        MorphToSelect::make('recommendable')
                            ->label('Konten')
                            ->types([
                                Type::make(Destination::class)
                                    ->titleAttribute('name')
                                    ->label('Destinasi'),
                                Type::make(UmkmBusiness::class)
                                    ->titleAttribute('name')
                                    ->label('UMKM / Kuliner'),
                                Type::make(Accommodation::class)
                                    ->titleAttribute('name')
                                    ->label('Penginapan'),
                            ])
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('note')
                            ->label('Catatan')
                            ->maxLength(255)
                            ->helperText('Opsional, mis. alasan item ini direkomendasikan.'),

                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->helperText('Kecil tampil lebih dulu.'),
                    ]),
            ]);
    }
}
