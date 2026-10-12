<?php

namespace App\Filament\Resources\DestinationCategories\Pages;

use App\Filament\Resources\DestinationCategories\DestinationCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDestinationCategories extends ListRecords
{
    protected static string $resource = DestinationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
