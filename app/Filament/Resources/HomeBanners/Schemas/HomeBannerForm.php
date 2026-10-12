<?php

namespace App\Filament\Resources\HomeBanners\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HomeBannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Banner')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('subtitle')
                            ->label('Subjudul')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        FileUpload::make('image')
                            ->label('Gambar Banner')
                            ->image()
                            ->disk('public')
                            ->directory('home/banners')
                            ->imageEditor()
                            ->helperText('Rasio disarankan 16:9. Bila kosong, dipakai gambar bawaan.')
                            ->columnSpanFull(),

                        TextInput::make('caption')
                            ->label('Tulisan pada Gambar')
                            ->maxLength(255)
                            ->helperText('Teks yang muncul di atas gambar (opsional).'),

                        TextInput::make('link_url')
                            ->label('Tautan Tujuan')
                            ->maxLength(255)
                            ->helperText('Contoh: /destinasi atau https://... (opsional).'),

                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->helperText('Kecil tampil lebih dulu.'),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
