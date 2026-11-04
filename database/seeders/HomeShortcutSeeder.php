<?php

namespace Database\Seeders;

use App\Models\HomeShortcut;
use Illuminate\Database\Seeder;

/**
 * Ikon menu (shortcut) bawaan beranda.
 *
 * Idempoten & tidak merusak data: hanya MENAMBAH shortcut yang belum ada
 * (dicocokkan lewat URL). Label/urutan yang sudah diubah admin tidak ditimpa.
 *
 * Jalankan kapan saja di server:
 *   php artisan db:seed --class=HomeShortcutSeeder --force
 */
class HomeShortcutSeeder extends Seeder
{
    public function run(): void
    {
        $shortcuts = [
            ['label' => 'Destinasi', 'url' => '/destinasi', 'icon' => 'Map', 'sort_order' => 1],
            ['label' => 'Budaya', 'url' => '/budaya', 'icon' => 'BuildingLibrary', 'sort_order' => 2],
            ['label' => 'AI Trip Planner', 'key' => 'ai-trip-planner', 'url' => '/ai-trip-planner', 'icon' => 'Sparkles', 'sort_order' => 3],
            ['label' => 'Restoran', 'url' => '/umkm?tipe=kuliner', 'icon' => 'BuildingStorefront', 'sort_order' => 4],
            ['label' => 'UMKM', 'url' => '/umkm', 'icon' => 'ShoppingBag', 'sort_order' => 5],
            ['label' => 'Penginapan', 'url' => '/penginapan', 'icon' => 'Home', 'sort_order' => 6],
            ['label' => 'Peta', 'url' => '/peta', 'icon' => 'MapPin', 'sort_order' => 7],
            ['label' => 'Event', 'key' => 'event', 'url' => '/event', 'icon' => 'CalendarDays', 'sort_order' => 8],
        ];

        foreach ($shortcuts as $shortcut) {
            HomeShortcut::query()->firstOrCreate(
                ['url' => $shortcut['url']],
                $shortcut + ['is_active' => true],
            );
        }
    }
}
