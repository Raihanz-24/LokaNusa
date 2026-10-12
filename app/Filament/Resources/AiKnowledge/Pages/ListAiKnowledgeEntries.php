<?php

namespace App\Filament\Resources\AiKnowledge\Pages;

use App\Filament\Resources\AiKnowledge\AiKnowledgeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiKnowledgeEntries extends ListRecords
{
    protected static string $resource = AiKnowledgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Entri Pengetahuan Baru'),
        ];
    }
}
