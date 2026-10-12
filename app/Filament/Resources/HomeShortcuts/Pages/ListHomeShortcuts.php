<?php

namespace App\Filament\Resources\HomeShortcuts\Pages;

use App\Filament\Resources\HomeShortcuts\HomeShortcutResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomeShortcuts extends ListRecords
{
    protected static string $resource = HomeShortcutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
