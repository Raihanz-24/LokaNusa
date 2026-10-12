<?php

namespace App\Filament\Resources\HomeShortcuts\Pages;

use App\Filament\Resources\HomeShortcuts\HomeShortcutResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHomeShortcut extends EditRecord
{
    protected static string $resource = HomeShortcutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
