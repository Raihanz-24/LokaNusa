<?php

namespace App\Filament\Resources\UmkmBusinesses\Pages;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Filament\Concerns\ModeratesContentActions;
use App\Filament\Concerns\ReverseGeocodesAddress;
use App\Filament\Resources\UmkmBusinesses\UmkmBusinessResource;
use App\Services\UmkmService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditUmkmBusiness extends EditRecord
{
    use ModeratesContentActions;
    use ReverseGeocodesAddress;

    protected static string $resource = UmkmBusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Ajukan Review')
                ->color('warning')
                ->visible(fn (): bool => Gate::allows(Permission::ManageUmkm->value)
                    && in_array($this->record->status, [ContentStatus::Draft, ContentStatus::Rejected], true))
                ->requiresConfirmation()
                ->action(fn () => $this->moderate(fn () => app(UmkmService::class)->submitForReview($this->record), 'UMKM diajukan untuk review.')),

            Action::make('approve')
                ->label('Setujui')
                ->color('success')
                ->visible(fn (): bool => Gate::allows(Permission::ModerateContent->value)
                    && in_array($this->record->status, [ContentStatus::Pending, ContentStatus::Draft, ContentStatus::Archived], true))
                ->requiresConfirmation()
                ->action(fn () => $this->moderate(fn () => app(UmkmService::class)->approve($this->record, auth()->user()), 'UMKM diterbitkan.')),

            $this->moderationAction(UmkmService::class, 'UMKM'),

            DeleteAction::make(),
        ];
    }

    private function moderate(callable $callback, string $message): void
    {
        $callback();

        Notification::make()->title($message)->success()->send();

        $this->refreshFormData(['status', 'published_at', 'rejection_reason']);
    }
}
