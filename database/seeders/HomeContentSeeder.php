<?php

namespace Database\Seeders;

use App\Models\HomeBanner;
use App\Models\HomeShortcut;
use Illuminate\Database\Seeder;

/**
 * Konten bawaan beranda: banner (carousel) & shortcut fitur.
 * Idempoten — hanya mengisi bila tabel masih kosong.
 */
class HomeContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBanners();
        $this->seedShortcuts();
    }

    private function seedBanners(): void
    {
        if (HomeBanner::query()->exists()) {
            return;
        }

        $banners = [
            [
                'title' => 'Pesona Bromo Tengger Semeru',
                'subtitle' => 'Saksikan matahari terbit paling ikonik di Jawa Timur.',
                'caption' => 'Gunung Bromo & Semeru',
                'image' => '/images/auth/bromo-tengger-semeru.jpg',
                'link_url' => '/destinasi',
                'sort_order' => 1,
            ],
            [
                'title' => 'Lautan Pasir & Kawah Bromo',
                'subtitle' => 'Petualangan menembus kaldera Tengger.',
                'caption' => 'Kaldera Tengger',
                'image' => '/images/hero/hero-2.jpg',
                'link_url' => '/destinasi',
                'sort_order' => 2,
            ],
            [
                'title' => 'Jelajahi Probolinggo',
                'subtitle' => 'Destinasi, kuliner, budaya, dan penginapan dalam satu tempat.',
                'caption' => 'Probolinggo, Jawa Timur',
                'image' => '/images/hero/hero-3.jpg',
                'link_url' => '/destinasi',
                'sort_order' => 3,
            ],
        ];

        foreach ($banners as $banner) {
            HomeBanner::query()->create($banner + ['is_active' => true]);
        }
    }

    private function seedShortcuts(): void
    {
        if (HomeShortcut::query()->exists()) {
            return;
        }

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
            HomeShortcut::query()->create($shortcut + ['is_active' => true]);
        }
    }
}
