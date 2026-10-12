<?php

namespace App\Services;

use App\Models\HomeShortcut;

/**
 * Logika shortcut beranda yang terpisah dari Filament.
 */
class HomeShortcutService
{
    /**
     * Daftar shortcut siap tampil (ikon Heroicon sudah diresolusi).
     *
     * @return array<int, array{label: string, url: string, icon: mixed}>
     */
    public static function resolved(): array
    {
        return HomeShortcut::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (HomeShortcut $shortcut): array => [
                'label' => $shortcut->translatedLabel(),
                'url' => $shortcut->resolvedUrl(),
                'icon' => $shortcut->heroicon(),
            ])
            ->all();
    }
}
