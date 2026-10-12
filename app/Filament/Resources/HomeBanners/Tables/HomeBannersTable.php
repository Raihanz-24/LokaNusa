<?php

namespace App\Filament\Resources\HomeBanners\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HomeBannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label('Gambar')
                    ->disk('public')
                    ->height(48)
                    ->width(80)
                    ->defaultImageUrl(url('/images/auth/bromo-tengger-semeru.jpg')),

                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('caption')
                    ->label('Tulisan')
                    ->placeholder('—')
                    ->wrap(),

                TextColumn::make('link_url')
                    ->label('Tautan')
                    ->placeholder('—')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
