<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Enums\ContentStatus;
use App\Services\ReviewService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

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

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ContentStatus $state): string => $state->label())
                    ->color(fn (ContentStatus $state): string => $state->color()),

                TextColumn::make('created_at')
                    ->label('Dikirim')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ContentStatus::options()),

                SelectFilter::make('rating')
                    ->options([
                        5 => '5 bintang', 4 => '4 bintang', 3 => '3 bintang',
                        2 => '2 bintang', 1 => '1 bintang',
                    ]),
            ])
            ->recordActions([
                Action::make('hide')
                    ->label('Sembunyikan')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->visible(fn ($record): bool => $record->status === ContentStatus::Published)
                    ->schema([
                        Textarea::make('reason')
                            ->label('Alasan penyembunyian')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Alasan ini dicatat pada ulasan (tidak ditampilkan ke publik).'),
                    ])
                    ->action(function ($record, array $data): void {
                        app(ReviewService::class)->hide($record, (string) $data['reason']);

                        Notification::make()->title('Ulasan disembunyikan.')->success()->send();
                    }),

                Action::make('restore')
                    ->label('Terbitkan lagi')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn ($record): bool => $record->status !== ContentStatus::Published)
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        app(ReviewService::class)->publish($record);

                        Notification::make()->title('Ulasan diterbitkan kembali.')->success()->send();
                    }),

                DeleteAction::make()
                    ->label('Hapus')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus ulasan ini?')
                    ->modalDescription('Gunakan untuk menghapus ulasan yang melanggar (mis. mengandung unsur SARA).')
                    ->action(function ($record): void {
                        app(ReviewService::class)->delete($record);

                        Notification::make()->title('Ulasan dihapus.')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('hideSelected')
                        ->label('Sembunyikan terpilih')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->schema([
                            Textarea::make('reason')
                                ->label('Alasan penyembunyian')
                                ->required()
                                ->rows(3)
                                ->maxLength(500),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $service = app(ReviewService::class);

                            $records->each(fn ($review) => $service->hide($review, (string) $data['reason']));

                            Notification::make()
                                ->title($records->count().' ulasan disembunyikan.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->label('Hapus terpilih'),
                ]),
            ])
            ->emptyStateHeading('Belum ada ulasan')
            ->emptyStateDescription('Ulasan wisatawan akan muncul di sini untuk ditinjau moderator.');
    }
}
