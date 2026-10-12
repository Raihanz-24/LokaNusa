<?php

namespace App\Filament\Resources\CultureArticles\Pages;

use App\Enums\ContentStatus;
use App\Filament\Resources\CultureArticles\CultureArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCultureArticle extends CreateRecord
{
    protected static string $resource = CultureArticleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user !== null && blank($data['author_id'] ?? null)) {
            $data['author_id'] = $user->getKey();
        }

        $data['status'] ??= ContentStatus::Draft->value;

        return $data;
    }
}
