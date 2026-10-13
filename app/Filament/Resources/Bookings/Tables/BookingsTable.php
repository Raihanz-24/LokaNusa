<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Models\Accommodation;
use App\Support\Rupiah;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('bookable')
                    ->label('Konten')
                    ->getStateUsing(fn ($record): string => $record->bookable?->name ?? '—')
                    ->description(fn ($record): string => $record->bookable instanceof Accommodation ? 'Penginapan' : 'Produk UMKM')
                    ->wrap(),

                TextColumn::make('user.name')
                    ->label('Pemesan')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('quantity')
                    ->label('Jml')
                    ->alignCenter()
                    ->description(fn ($record): string => $record->bookable instanceof Accommodation ? 'malam' : 'unit'),

                TextColumn::make('total_price')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => Rupiah::label((int) $state)),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (BookingStatus $state): string => $state->label())
                    ->color(fn (BookingStatus $state): string => $state->color()),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(BookingStatus::options()),
            ])
            ->actions([
                DeleteAction::make(),
            ]);
    }
}
