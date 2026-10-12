<?php

namespace App\Filament\Resources\RecommendedItems\Pages;

use App\Filament\Resources\RecommendedItems\RecommendedItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecommendedItems extends ListRecords
{
    protected static string $resource = RecommendedItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
