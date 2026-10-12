<?php

namespace App\Filament\Resources\Events\Tables;

use App\Enums\ContentStatus;
use App\Enums\EventType;
use App\Enums\Permission;
use App\Filament\Support\ModerationActions;
use App\Services\EventService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Nama Event')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('event_type')
                    ->label('Jenis')
                    ->badge(),

                TextColumn::make('starts_at')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('location_name')
                    ->label('Lokasi')
                    ->placeholder('—')
                    ->wrap(),

                TextColumn::make('region.name')
                    ->label('Kecamatan')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                IconColumn::make('is_recommended')
                    ->label('Rekomendasi')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Jenis')
                    ->options(EventType::options()),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ContentStatus::options()),
            ])
            ->recordActions([
                ...ModerationActions::make(EventService::class, Permission::ManageEvents),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
