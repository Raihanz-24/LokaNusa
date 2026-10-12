<?php

namespace App\Filament\Resources\CultureArticles\Tables;

use App\Enums\ContentStatus;
use App\Enums\CultureType;
use App\Enums\Permission;
use App\Filament\Support\ModerationActions;
use App\Services\CultureArticleService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CultureArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge(),

                TextColumn::make('region.name')
                    ->label('Kecamatan')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('author.name')
                    ->label('Kontributor')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('published_at')
                    ->label('Terbit')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options(CultureType::options()),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ContentStatus::options()),
            ])
            ->recordActions([
                ...ModerationActions::make(CultureArticleService::class, Permission::ManageCulture),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
