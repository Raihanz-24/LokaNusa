<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Ringkasan KPI utama dashboard (data aktual).
 */
class DashboardStats extends StatsOverviewWidget
{
    protected static ?int $sort = -5;

    protected int|string|array $columnSpan = 'full';

    public function getPollingInterval(): ?string
    {
        return null;
    }

    protected function getStats(): array
    {
        $service = app(DashboardService::class);

        $content = $service->contentStats();
        $users = $service->userStats();
        $engagement = $service->engagementStats();

        $totalContent = array_sum(array_column($content, 'total'));
        $totalPublished = array_sum(array_column($content, 'published'));
        $pending = $service->pendingModerationCount();
        $recommended = $service->recommendedCount();

        return [
            Stat::make('Total Konten', number_format($totalContent))
                ->description($totalPublished.' terbit · '.$recommended.' direkomendasikan')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),

            Stat::make('Menunggu Moderasi', number_format($pending))
                ->description('Status "pending" lintas modul')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'success'),

            Stat::make('Pengguna', number_format($users['total']))
                ->description($users['wisatawan'].' wisatawan · +'.$users['new_this_month'].' bulan ini')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Favorit', number_format($engagement['favorites']))
                ->description('Konten disimpan pengguna')
                ->descriptionIcon('heroicon-m-heart')
                ->color('danger'),
        ];
    }
}
