<?php

namespace App\Filament\Resources\Destinations\Pages;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Filament\Concerns\ReverseGeocodesAddress;
use App\Filament\Resources\Destinations\DestinationResource;
use App\Services\DestinationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditDestination extends EditRecord
{
    use ReverseGeocodesAddress;

    protected static string $resource = DestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Ajukan Review')
                ->color('warning')
                ->visible(fn (): bool => Gate::allows(Permission::ManageDestinations->value)
                    && in_array($this->record->status, [ContentStatus::Draft, ContentStatus::Rejected], true))
                ->requiresConfirmation()
                ->action(fn () => $this->moderate(fn () => app(DestinationService::class)->submitForReview($this->record), 'Destinasi diajukan untuk review.')),

            Action::make('approve')
                ->label('Setujui')
                ->color('success')
                ->visible(fn (): bool => Gate::allows(Permission::ModerateContent->value)
                    && in_array($this->record->status, [ContentStatus::Pending, ContentStatus::Draft, ContentStatus::Archived], true))
                ->requiresConfirmation()
                ->action(fn () => $this->moderate(fn () => app(DestinationService::class)->approve($this->record, auth()->user()), 'Destinasi diterbitkan.')),

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
