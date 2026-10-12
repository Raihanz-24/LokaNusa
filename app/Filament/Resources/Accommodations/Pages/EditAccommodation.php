<?php

namespace App\Filament\Resources\Accommodations\Pages;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Filament\Concerns\ReverseGeocodesAddress;
use App\Filament\Resources\Accommodations\AccommodationResource;
use App\Services\AccommodationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditAccommodation extends EditRecord
{
    use ReverseGeocodesAddress;

    protected static string $resource = AccommodationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Ajukan Review')
                ->color('warning')
                ->visible(fn (): bool => Gate::allows(Permission::ManageAccommodations->value)
                    && in_array($this->record->status, [ContentStatus::Draft, ContentStatus::Rejected], true))
                ->requiresConfirmation()
                ->action(fn () => $this->moderate(fn () => app(AccommodationService::class)->submitForReview($this->record), 'Penginapan diajukan untuk review.')),

            Action::make('approve')
                ->label('Setujui')
                ->color('success')
                ->visible(fn (): bool => Gate::allows(Permission::ModerateContent->value)
                    && in_array($this->record->status, [ContentStatus::Pending, ContentStatus::Draft, ContentStatus::Archived], true))
                ->requiresConfirmation()
                ->action(fn () => $this->moderate(fn () => app(AccommodationService::class)->approve($this->record, auth()->user()), 'Penginapan diterbitkan.')),

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
