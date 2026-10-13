<?php

namespace App\Filament\Resources\UmkmBusinesses\Schemas;

use App\Enums\BusinessType;
use App\Filament\Forms\Components\MapPicker;
use App\Filament\Support\RegionFormFields;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UmkmBusinessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Usaha')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Usaha')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('business_type')
                            ->label('Jenis Usaha')
                            ->options(BusinessType::options())
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

                Section::make('Operasional')
                    ->schema([
                        Repeater::make('opening_hours')
                            ->label('Jam Buka')
                            ->schema([
                                Select::make('day')->label('Hari')->options(self::dayOptions())->required()->native(false),
                                TimePicker::make('open')->label('Buka')->seconds(false),
                                TimePicker::make('close')->label('Tutup')->seconds(false),
                                Toggle::make('closed')->label('Tutup')->inline(false),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('Tambah baris')
                            ->columnSpanFull(),
                    ]),

                Section::make('Galeri')
                    ->schema([
                        FileUpload::make('gallery')
                            ->label('Foto')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('umkm')
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

    /**
     * @return array<string, string>
     */
    private static function dayOptions(): array
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
