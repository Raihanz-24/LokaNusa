<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Dashboard admin (default). Pemilik usaha diarahkan ke Dasbor Usaha
 * ({@see OwnerDashboard}); dasbor global hanya untuk pengelola seluruh konten.
 */
class Dashboard extends BaseDashboard
{
    public static function shouldRegisterNavigation(): bool
    {
        return ! (auth()->user()?->role?->isOwner() ?? false);
    }

    public function mount(): void
    {
        if (auth()->user()?->role?->isOwner() ?? false) {
            $this->redirect(OwnerDashboard::getUrl());
        }
    }
}
