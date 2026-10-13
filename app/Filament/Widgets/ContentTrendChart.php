<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

/**
 * Grafik tren konten terbit per bulan (6 bulan terakhir).
 */
class ContentTrendChart extends ChartWidget
{
    protected ?string $heading = 'Tren Konten Terbit (6 Bulan)';

    protected static ?int $sort = -4;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return auth()->user()?->managesAllContent() ?? false;
    }

    protected function getData(): array
    {
        $trend = app(DashboardService::class)->contentTrend(6);

        return [
            'datasets' => collect($trend['series'])
                ->map(fn (array $series): array => [
                    'label' => $series['label'],
                    'data' => $series['data'],
                ])
                ->all(),
            'labels' => $trend['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
