<?php

namespace App\Filament\Resources\DestinationCategories\Pages;

use App\Filament\Resources\DestinationCategories\DestinationCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDestinationCategory extends EditRecord
{
    protected static string $resource = DestinationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
