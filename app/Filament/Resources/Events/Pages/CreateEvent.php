<?php

namespace App\Filament\Resources\Events\Pages;

use App\Enums\ContentStatus;
use App\Filament\Resources\Events\EventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user !== null && ! $user->managesAllContent() && blank($data['organizer_id'] ?? null)) {
            $data['organizer_id'] = $user->getKey();
        }

        $data['status'] ??= ContentStatus::Draft->value;

        return $data;
    }
}
