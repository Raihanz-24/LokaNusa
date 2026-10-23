<?php

namespace App\Filament\Resources\Accommodations\Tables;

use App\Enums\AccommodationType;
use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Filament\Pages\QrStats;
use App\Filament\Support\ModerationActions;
use App\Services\AccommodationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AccommodationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge(),

                TextColumn::make('region.name')
                    ->label('Kecamatan')
                    ->placeholder('—'),

                TextColumn::make('price_per_night')
                    ->label('Harga/Malam')
                    ->money('IDR', locale: 'id')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('manager.name')
                    ->label('Pengelola')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options(AccommodationType::options()),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ContentStatus::options()),
            ])
            ->recordActions([
                ...ModerationActions::make(AccommodationService::class, Permission::ManageAccommodations),
                EditAction::make(),

                Action::make('qr')
                    ->label('QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('gray')
                    ->url(fn ($record): string => QrStats::getUrl(['type' => 'penginapan', 'id' => $record->getKey()])),
                DeleteAction::make(),
            ]);
    }
}
