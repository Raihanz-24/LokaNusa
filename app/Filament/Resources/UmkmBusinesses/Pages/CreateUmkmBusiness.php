<?php

namespace App\Filament\Resources\UmkmBusinesses\Pages;

use App\Enums\ContentStatus;
use App\Filament\Concerns\ReverseGeocodesAddress;
use App\Filament\Resources\UmkmBusinesses\UmkmBusinessResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUmkmBusiness extends CreateRecord
{
    use ReverseGeocodesAddress;

    protected static string $resource = UmkmBusinessResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user !== null && ! $user->managesAllContent() && blank($data['owner_id'] ?? null)) {
            $data['owner_id'] = $user->getKey();
        }

        $data['status'] ??= ContentStatus::Draft->value;

        return $data;
    }
}
