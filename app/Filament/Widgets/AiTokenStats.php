<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Models\AiUsage;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * Ringkasan pemakaian token AI (semua pengguna).
 *
 * Sengaja "live" (tanpa cache) agar admin melihat total terbaru saat menguji.
 */
class AiTokenStats extends StatsOverviewWidget
{
    protected static ?int $sort = -3;

    public static function canView(): bool
    {
        return auth()->user()?->hasPermission(Permission::ManageAiKnowledge) ?? false;
    }

    protected function getStats(): array
    {
        $total = (int) AiUsage::query()->sum('total_tokens');
        $today = (int) AiUsage::query()->where('created_at', '>=', Carbon::today())->sum('total_tokens');
        $requests = (int) AiUsage::query()->count();
        $users = (int) AiUsage::query()->whereNotNull('user_id')->distinct('user_id')->count('user_id');

        return [
            Stat::make('Total Token AI', number_format($total))
                ->description('Seluruh pengguna, sepanjang waktu')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('primary'),

            Stat::make('Token Hari Ini', number_format($today))
                ->description('Sejak 00:00 hari ini')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('success'),

            Stat::make('Total Permintaan', number_format($requests))
                ->description('Jumlah panggilan AI tercatat')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),

            Stat::make('Pengguna Memakai AI', number_format($users))
                ->description('Pengguna unik dengan pemakaian')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }
}
