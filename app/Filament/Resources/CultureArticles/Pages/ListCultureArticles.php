<?php

namespace App\Filament\Resources\CultureArticles\Pages;

use App\Filament\Resources\CultureArticles\CultureArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCultureArticles extends ListRecords
{
    protected static string $resource = CultureArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
