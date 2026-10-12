<?php

namespace App\Filament\Resources\Events\Pages;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Filament\Resources\Events\EventResource;
use App\Services\EventService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Ajukan Review')
                ->color('warning')
                ->visible(fn (): bool => Gate::allows(Permission::ManageEvents->value)
                    && in_array($this->record->status, [ContentStatus::Draft, ContentStatus::Rejected], true))
                ->requiresConfirmation()
                ->action(fn () => $this->moderate(fn () => app(EventService::class)->submitForReview($this->record), 'Event diajukan untuk review.')),

            Action::make('approve')
                ->label('Setujui')
                ->color('success')
                ->visible(fn (): bool => Gate::allows(Permission::ModerateContent->value)
                    && in_array($this->record->status, [ContentStatus::Pending, ContentStatus::Draft, ContentStatus::Archived], true))
                ->requiresConfirmation()
                ->action(fn () => $this->moderate(fn () => app(EventService::class)->approve($this->record, auth()->user()), 'Event diterbitkan.')),

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
