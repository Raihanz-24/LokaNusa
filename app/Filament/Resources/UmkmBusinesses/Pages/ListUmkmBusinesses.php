<?php

namespace App\Filament\Resources\UmkmBusinesses\Pages;

use App\Filament\Resources\UmkmBusinesses\UmkmBusinessResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUmkmBusinesses extends ListRecords
{
    protected static string $resource = UmkmBusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
