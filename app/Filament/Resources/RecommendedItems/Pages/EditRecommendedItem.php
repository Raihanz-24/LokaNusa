<?php

namespace App\Filament\Resources\RecommendedItems\Pages;

use App\Filament\Resources\RecommendedItems\RecommendedItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRecommendedItem extends EditRecord
{
    protected static string $resource = RecommendedItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
