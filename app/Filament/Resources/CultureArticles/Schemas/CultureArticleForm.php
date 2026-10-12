<?php

namespace App\Filament\Resources\CultureArticles\Schemas;

use App\Enums\CultureType;
use App\Filament\Support\RegionFormFields;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CultureArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Artikel Budaya')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('type')
                            ->label('Jenis')
                            ->options(CultureType::options())
                            ->required()
                            ->native(false),

                        Textarea::make('excerpt')
                            ->label('Ringkasan')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Ringkasan singkat untuk kartu & meta deskripsi.')
                            ->columnSpanFull(),

                        Textarea::make('content')
                            ->label('Isi Artikel')
                            ->rows(10)
                            ->required()
                            ->columnSpanFull(),

                        FileUpload::make('cover_image')
                            ->label('Gambar Sampul')
                            ->image()
                            ->disk('public')
                            ->directory('culture')
                            ->columnSpanFull(),
                    ]),

                Section::make('Wilayah')
                    ->columns(2)
                    ->schema([
                        RegionFormFields::kecamatan(villageField: 'village_id'),
                        RegionFormFields::desa(),
                    ]),
            ]);
    }
}
