<?php

namespace App\Filament\Resources\Accommodations\Schemas;

use App\Enums\AccommodationType;
use App\Filament\Forms\Components\MapPicker;
use App\Filament\Support\RegionFormFields;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccommodationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Penginapan')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Penginapan')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('type')
                            ->label('Jenis')
                            ->options(AccommodationType::options())
                            ->required()
                            ->native(false),

                        TextInput::make('phone')
                            ->label('Nomor Kontak')
                            ->tel()
                            ->maxLength(30),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Lokasi')
                    ->columns(2)
                    ->schema([
                        RegionFormFields::kecamatan(villageField: 'village_id'),
                        RegionFormFields::desa(),
                        Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->rows(2)
                            ->columnSpanFull(),
                        MapPicker::make('map')
                            ->reverseGeocode()
                            ->columnSpanFull(),
                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->minValue(-90)
                            ->maxValue(90)
                            ->helperText('Klik peta untuk mengisi otomatis.'),
                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->minValue(-180)
                            ->maxValue(180)
                            ->helperText('Klik peta untuk mengisi otomatis.'),
                    ]),

                Section::make('Fasilitas & Harga')
                    ->columns(2)
                    ->schema([
                        TagsInput::make('amenities')
                            ->label('Fasilitas')
                            ->placeholder('Tambah fasilitas lalu Enter')
                            ->helperText('Contoh: WiFi, AC, Sarapan, Kolam Renang')
                            ->columnSpanFull(),

                        TextInput::make('price_per_night')
                            ->label('Harga per Malam')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0),

                        TextInput::make('check_in_time')
                            ->label('Check-in'),

                        TextInput::make('check_out_time')
                            ->label('Check-out'),
                    ]),

                Section::make('Galeri')
                    ->schema([
                        FileUpload::make('gallery')
                            ->label('Foto')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('accommodations')
                            ->columnSpanFull(),
                    ]),

                Section::make('Rekomendasi Beranda')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_recommended')
                            ->label('Tampilkan di "Rekomendasi untukmu"')
                            ->helperText('Muncul di beranda bila belum dikurasi manual.'),

                        TextInput::make('recommendation_order')
                            ->label('Urutan rekomendasi')
                            ->numeric()
                            ->default(0)
                            ->helperText('Kecil tampil lebih dulu.'),
                    ]),
            ]);
    }
}
