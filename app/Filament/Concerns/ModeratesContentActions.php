<?php

namespace App\Filament\Concerns;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

/**
 * Aksi moderasi seragam untuk halaman EditRecord seluruh modul konten.
 *
 * Menyediakan tombol "Setujui" dan "Tolak" (dengan alasan wajib) yang
 * memanggil service modul terkait, sehingga transisi status & notifikasi
 * konsisten dengan Antrean Moderasi dan tidak ditulis langsung ke model.
 */
trait ModeratesContentActions
{
    /**
     * @param  class-string  $service  Service yang memakai trait ModeratesContent.
     */
    protected function moderationAction(string $service, string $label): Action
    {
        return Action::make('reject')
            ->label('Tolak')
            ->color('danger')
            ->icon('heroicon-o-x-mark')
            ->visible(fn (): bool => Gate::allows(Permission::ModerateContent->value)
                && in_array($this->record->status, [ContentStatus::Pending, ContentStatus::Draft], true))
            ->schema([
                Textarea::make('reason')
                    ->label('Alasan penolakan')
                    ->required()
                    ->rows(3)
                    ->maxLength(500)
                    ->helperText('Alasan akan dikirim ke pemilik konten lewat notifikasi.'),
            ])
            ->action(function (array $data) use ($service, $label): void {
                app($service)->reject($this->record, (string) $data['reason']);

                Notification::make()
                    ->title("{$label} ditolak & pemilik diberi tahu.")
                    ->success()
                    ->send();

                $this->refreshFormData(['status', 'published_at', 'rejection_reason']);
            });
    }
}
