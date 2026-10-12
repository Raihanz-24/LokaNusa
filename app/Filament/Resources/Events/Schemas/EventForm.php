<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventType;
use App\Filament\Forms\Components\MapPicker;
use App\Filament\Support\RegionFormFields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Event')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Nama Event')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255)
                            ->helperText('Kosongkan untuk otomatis dari nama.'),

                        Select::make('event_type')
                            ->label('Jenis Event')
                            ->options(EventType::options())
                            ->required()
                            ->native(false),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(4)
                            ->columnSpanFull(),

                        FileUpload::make('cover_image')
                            ->label('Gambar Sampul')
                            ->image()
                            ->disk('public')
                            ->directory('events')
                            ->columnSpanFull(),
                    ]),

                Section::make('Waktu Pelaksanaan')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Mulai')
                            ->required()
                            ->seconds(false)
                            ->native(false),

                        DateTimePicker::make('ends_at')
                            ->label('Selesai')
                            ->seconds(false)
                            ->native(false)
                            ->afterOrEqual('starts_at')
                            ->helperText('Opsional. Kosongkan bila hanya sehari.'),
                    ]),

                Section::make('Lokasi')
                    ->columns(2)
                    ->schema([
                        TextInput::make('location_name')
                            ->label('Nama Lokasi')
                            ->maxLength(255)
                            ->placeholder('Mis. Alun-Alun Probolinggo')
                            ->columnSpanFull(),

                        RegionFormFields::kecamatan(villageField: 'village_id'),
                        RegionFormFields::desa(),

                        MapPicker::make('map')
                            ->reverseGeocode()
                            ->columnSpanFull(),

                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->minValue(-90)
                            ->maxValue(90)
                            ->helperText('Klik peta untuk mengisi otomatis (opsional).'),

                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->minValue(-180)
                            ->maxValue(180)
                            ->helperText('Klik peta untuk mengisi otomatis (opsional).'),
                    ]),

                Section::make('Penyelenggara & Tiket')
                    ->columns(2)
                    ->schema([
                        TextInput::make('organizer_name')
                            ->label('Nama Penyelenggara')
                            ->maxLength(255),

                        TextInput::make('contact')
                            ->label('Kontak')
                            ->maxLength(255)
                            ->placeholder('No. HP / email'),

                        Toggle::make('is_free')
                            ->label('Gratis')
                            ->default(true)
                            ->live(),

                        TextInput::make('ticket_price')
                            ->label('Harga Tiket')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->visible(fn (Get $get): bool => ! $get('is_free')),
                    ]),

            ]);
    }
}
