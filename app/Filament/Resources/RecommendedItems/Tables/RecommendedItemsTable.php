<?php

namespace App\Filament\Resources\RecommendedItems\Tables;

use App\Models\RecommendationItem;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RecommendedItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('recommendable.name')
                    ->label('Konten')
                    ->getStateUsing(fn (RecommendationItem $record): string => $record->recommendable?->name ?? '—')
                    ->description(fn (RecommendationItem $record): string => class_basename((string) $record->recommendable_type))
                    ->wrap(),

                TextColumn::make('recommendable.status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (RecommendationItem $record) => $record->recommendable?->status)
                    ->placeholder('—'),

                TextColumn::make('note')
                    ->label('Catatan')
                    ->placeholder('—')
                    ->wrap(),

                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
