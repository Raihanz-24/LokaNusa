<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Services\ReviewService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reviewable')
                    ->label('Konten')
                    ->getStateUsing(fn ($record): string => $record->reviewable?->name ?? $record->reviewable?->title ?? '—')
                    ->description(fn ($record): string => class_basename((string) $record->reviewable_type))
                    ->wrap(),

                TextColumn::make('user.name')
                    ->label('Pengulas')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('rating')
                    ->label('Rating')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state))
                    ->color(fn (int $state): string => $state >= 4 ? 'success' : ($state >= 3 ? 'warning' : 'danger')),

                TextColumn::make('comment')
                    ->label('Komentar')
                    ->wrap()
                    ->limit(160)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Dikirim')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->options([
                        5 => '5 bintang', 4 => '4 bintang', 3 => '3 bintang',
                        2 => '2 bintang', 1 => '1 bintang',
                    ]),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Hapus')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus ulasan ini?')
                    ->modalDescription('Gunakan untuk menghapus ulasan yang melanggar (mis. mengandung unsur SARA).')
                    ->action(function ($record): void {
                        app(ReviewService::class)->delete($record);

                        Notification::make()->title('Ulasan dihapus.')->success()->send();
                    }),
            ]);
    }
}
