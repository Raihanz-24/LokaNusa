<?php

namespace App\Filament\Resources\RecommendedItems\Pages;

use App\Filament\Resources\RecommendedItems\RecommendedItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecommendedItem extends CreateRecord
{
    protected static string $resource = RecommendedItemResource::class;
}
