<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Rincian jumlah konten per modul (total vs terbit).
 */
class ContentByModuleTable extends TableWidget
{
    protected static ?string $heading = 'Konten per Modul';

    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $stats = collect(app(DashboardService::class)->contentStats())
            ->map(fn (array $item, string $key): array => [
                'module' => $item['label'],
                'total' => $item['total'],
                'published' => $item['published'],
                'draft_pending' => $item['total'] - $item['published'],
            ])
            ->values();

        return $table
            ->records(fn () => $stats)
            ->columns([
                TextColumn::make('module')
                    ->label('Modul')
                    ->weight('bold'),

                TextColumn::make('total')
                    ->label('Total')
                    ->numeric()
                    ->alignEnd(),

                TextColumn::make('published')
                    ->label('Terbit')
                    ->numeric()
                    ->alignEnd()
                    ->color('success'),

                TextColumn::make('draft_pending')
                    ->label('Belum Terbit')
                    ->numeric()
                    ->alignEnd()
                    ->color('warning'),
            ])
            ->paginated(false);
    }
}
