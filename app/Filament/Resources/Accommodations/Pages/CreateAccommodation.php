<?php

namespace App\Filament\Resources\Accommodations\Pages;

use App\Enums\ContentStatus;
use App\Filament\Concerns\ReverseGeocodesAddress;
use App\Filament\Resources\Accommodations\AccommodationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccommodation extends CreateRecord
{
    use ReverseGeocodesAddress;

    protected static string $resource = AccommodationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user !== null && ! $user->managesAllContent() && blank($data['manager_id'] ?? null)) {
            $data['manager_id'] = $user->getKey();
        }

        $data['status'] ??= ContentStatus::Draft->value;

        return $data;
    }
}
