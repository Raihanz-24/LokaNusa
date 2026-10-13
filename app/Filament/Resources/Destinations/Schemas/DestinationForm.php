<?php

namespace App\Filament\Resources\Destinations\Schemas;

use App\Filament\Forms\Components\MapPicker;
use App\Filament\Support\OpeningHours;
use App\Filament\Support\RegionFormFields;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class DestinationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dasar')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Destinasi')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255)
                            ->helperText('Kosongkan untuk otomatis dari nama.'),

                        Select::make('destination_category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Select::make('manager_id')
                            ->label('Pengelola')
                            ->relationship('manager', 'name')
                            ->searchable()
                            ->native(false)
                            ->visible(fn (): bool => Auth::user()?->managesAllContent() ?? false)
                            ->helperText('Pengguna yang bertanggung jawab (opsional).'),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Lokasi & Koordinat')
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

                Section::make('Operasional & Fasilitas')
                    ->columns(2)
                    ->schema([
                        TextInput::make('ticket_price')
                            ->label('Harga Tiket')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->helperText('Isi 0 jika gratis.'),

                        TagsInput::make('facilities')
                            ->label('Fasilitas')
                            ->placeholder('Tambah fasilitas lalu Enter')
                            ->helperText('Contoh: Parkir, Toilet, Musholla'),

                        OpeningHours::repeater(),
                    ]),

                Section::make('Galeri Foto')
                    ->schema([
                        FileUpload::make('gallery')
                            ->label('Foto')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('destinations')
                            ->columnSpanFull(),
                    ]),

                Section::make('Rekomendasi Beranda')
                    ->columns(2)
                    ->visible(fn (): bool => Auth::user()?->managesAllContent() ?? false)
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
