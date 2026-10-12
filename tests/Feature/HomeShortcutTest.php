<?php

namespace Tests\Feature;

use App\Models\HomeShortcut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeShortcutTest extends TestCase
{
    use RefreshDatabase;

    public function test_shortcut_ai_trip_planner_mengarah_ke_halaman_chat(): void
    {
        $shortcut = HomeShortcut::query()->create([
            'label' => 'AI Trip Planner',
            'key' => 'ai-trip-planner',
            'url' => '/peta', // data lama/changed
            'icon' => 'Sparkles',
            'is_active' => true,
        ]);

        $this->assertSame('/ai-trip-planner', $shortcut->resolvedUrl());
    }

    public function test_resolved_url_memakai_url_tersimpan_bila_tanpa_key_fitur(): void
    {
        $shortcut = HomeShortcut::query()->create([
            'label' => 'UMKM',
            'url' => '/umkm',
            'icon' => 'ShoppingBag',
            'is_active' => true,
        ]);

        $this->assertSame('/umkm', $shortcut->resolvedUrl());
    }

    public function test_shortcut_beranda_menampilkan_tautan_ai_trip_planner(): void
    {
        HomeShortcut::query()->create([
            'label' => 'AI Trip Planner',
            'key' => 'ai-trip-planner',
            'url' => '/peta',
            'icon' => 'Sparkles',
            'is_active' => true,
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('/ai-trip-planner', escape: false);
    }

    public function test_shortcut_beranda_mengarah_ke_chat_untuk_pengguna_login(): void
    {
        HomeShortcut::query()->create([
            'label' => 'AI Trip Planner',
            'key' => 'ai-trip-planner',
            'url' => '/peta',
            'icon' => 'Sparkles',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->wisatawan()->create())
            ->get('/')
            ->assertSuccessful()
            ->assertSee('/ai-trip-planner', escape: false);
    }
}
