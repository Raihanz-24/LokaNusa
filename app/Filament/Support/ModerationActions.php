<?php

namespace App\Filament\Support;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Services\Concerns\ModeratesContent;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Kumpulan aksi moderasi untuk tabel resource (ajukan/setujui/tolak/arsipkan).
 */
class ModerationActions
{
    /**
     * @param  class-string<ModeratesContent>  $service
     * @return array<Action>
     */
    public static function make(string $service, Permission $managePermission): array
    {
        return [
            Action::make('submit')
                ->label('Ajukan')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('warning')
                ->visible(fn (Model $record): bool => Gate::allows($managePermission->value))
                ->hidden(fn (Model $record): bool => ! in_array($record->status, [ContentStatus::Draft, ContentStatus::Rejected], true))
                ->requiresConfirmation()
                ->action(fn (Model $record) => self::run(
                    fn () => app($service)->submitForReview($record),
                    'Konten diajukan untuk review.'
                )),

            Action::make('approve')
                ->label('Setujui')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (Model $record): bool => Gate::allows(Permission::ModerateContent->value))
                ->hidden(fn (Model $record): bool => ! in_array($record->status, [ContentStatus::Pending, ContentStatus::Draft, ContentStatus::Archived], true))
                ->requiresConfirmation()
                ->action(fn (Model $record) => self::run(
                    fn () => app($service)->approve($record, auth()->user()),
                    'Konten diterbitkan.'
                )),

            Action::make('reject')
                ->label('Tolak')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (Model $record): bool => Gate::allows(Permission::ModerateContent->value))
                ->hidden(fn (Model $record): bool => ! in_array($record->status, [ContentStatus::Pending, ContentStatus::Draft], true))
                ->schema([
                    Textarea::make('reason')->label('Alasan penolakan')->required()->rows(3),
                ])
                ->action(fn (Model $record, array $data) => self::run(
                    fn () => app($service)->reject($record, $data['reason']),
                    'Konten ditolak.'
                )),

            Action::make('archive')
                ->label('Arsipkan')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->color('gray')
                ->visible(fn (Model $record): bool => Gate::allows(Permission::ModerateContent->value))
                ->hidden(fn (Model $record): bool => $record->status === ContentStatus::Archived)
                ->requiresConfirmation()
                ->action(fn (Model $record) => self::run(
                    fn () => app($service)->archive($record),
                    'Konten diarsipkan.'
                )),
        ];
    }

    private static function run(callable $callback, string $successMessage): void
    {
        try {
            $callback();

            Notification::make()->title($successMessage)->success()->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal memproses')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
