<?php

namespace App\Filament\Resources\Destinations\Tables;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\RegionType;
use App\Filament\Resources\Destinations\Pages\DestinationQrStats;
use App\Models\Destination;
use App\Models\Region;
use App\Services\DestinationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class DestinationsTable
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
                    ->description(fn (Destination $record): ?string => $record->address)
                    ->wrap(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('region.name')
                    ->label('Wilayah')
                    ->formatStateUsing(fn (Destination $record): ?string => $record->region?->fullName())
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('ticket_price')
                    ->label('Harga Tiket')
                    ->formatStateUsing(fn (?int $state): string => $state === null || $state === 0 ? 'Gratis' : 'Rp'.number_format($state, 0, ',', '.'))
                    ->sortable(),

                TextColumn::make('manager.name')
                    ->label('Pengelola')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('published_at')
                    ->label('Terbit')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ContentStatus::options()),

                SelectFilter::make('destination_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('region_id')
                    ->label('Wilayah')
                    ->options(fn (): array => Region::query()
                        ->ofType(RegionType::KabupatenKota)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('qr')
                    ->label('QR')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->color('gray')
                    ->url(fn (Destination $record): string => DestinationQrStats::getUrl(['record' => $record])),

                Action::make('submit')
                    ->label('Ajukan')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('warning')
                    ->visible(fn (Destination $record): bool => Gate::allows(Permission::ManageDestinations->value)
                        && in_array($record->status, [ContentStatus::Draft, ContentStatus::Rejected], true))
                    ->requiresConfirmation()
                    ->action(fn (Destination $record) => self::run(fn () => app(DestinationService::class)->submitForReview($record), 'Destinasi diajukan untuk review.')),

                Action::make('approve')
                    ->label('Setujui')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Destination $record): bool => Gate::allows(Permission::ModerateContent->value)
                        && in_array($record->status, [ContentStatus::Pending, ContentStatus::Draft, ContentStatus::Archived], true))
                    ->requiresConfirmation()
                    ->action(fn (Destination $record) => self::run(fn () => app(DestinationService::class)->approve($record, auth()->user()), 'Destinasi diterbitkan.')),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (Destination $record): bool => Gate::allows(Permission::ModerateContent->value)
                        && in_array($record->status, [ContentStatus::Pending, ContentStatus::Draft], true))
                    ->schema([
                        Textarea::make('reason')
                            ->label('Alasan penolakan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(fn (Destination $record, array $data) => self::run(fn () => app(DestinationService::class)->reject($record, $data['reason']), 'Destinasi ditolak.')),

                Action::make('archive')
                    ->label('Arsipkan')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->color('gray')
                    ->visible(fn (Destination $record): bool => Gate::allows(Permission::ModerateContent->value)
                        && $record->status !== ContentStatus::Archived)
                    ->requiresConfirmation()
                    ->action(fn (Destination $record) => self::run(fn () => app(DestinationService::class)->archive($record), 'Destinasi diarsipkan.')),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Jalankan aksi moderasi & tampilkan notifikasi hasilnya.
     */
    private static function run(callable $callback, string $successMessage): void
    {
        try {
            $callback();

            Notification::make()
                ->title($successMessage)
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal memproses')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
