<?php

namespace App\Filament\Resources\UmkmBusinesses\Tables;

use App\Enums\BusinessType;
use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Filament\Pages\QrStats;
use App\Filament\Support\ModerationActions;
use App\Services\UmkmService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UmkmBusinessesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Usaha')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('business_type')
                    ->label('Jenis')
                    ->badge(),

                TextColumn::make('region.name')
                    ->label('Kecamatan')
                    ->placeholder('—'),

                TextColumn::make('products_count')
                    ->label('Produk')
                    ->counts('products')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->label('Pemilik')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('published_at')
                    ->label('Terbit')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('business_type')
                    ->label('Jenis Usaha')
                    ->options(BusinessType::options()),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ContentStatus::options()),
            ])
            ->recordActions([
                ...ModerationActions::make(UmkmService::class, Permission::ManageUmkm),
                EditAction::make(),

                Action::make('qr')
                    ->label('QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('gray')
                    ->url(fn ($record): string => QrStats::getUrl(['type' => 'umkm', 'id' => $record->getKey()])),
                DeleteAction::make(),
            ]);
    }
}
