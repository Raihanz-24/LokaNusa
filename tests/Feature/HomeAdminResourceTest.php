<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAdminResourceTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_super_admin_dapat_membuka_banner_beranda(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/home-banners')
            ->assertSuccessful();

        $this->actingAs($this->superAdmin())
            ->get('/admin/home-banners/create')
            ->assertSuccessful();
    }

    public function test_super_admin_dapat_membuka_shortcut_beranda(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/home-shortcuts')
            ->assertSuccessful();

        $this->actingAs($this->superAdmin())
            ->get('/admin/home-shortcuts/create')
            ->assertSuccessful();
    }

    public function test_super_admin_dapat_membuka_rekomendasi_beranda(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/recommended-items')
            ->assertSuccessful();

        $this->actingAs($this->superAdmin())
            ->get('/admin/recommended-items/create')
            ->assertSuccessful();
    }

    public function test_wisatawan_tidak_dapat_membuka_resource_beranda(): void
    {
        $wisatawan = User::factory()->wisatawan()->create();

        $this->actingAs($wisatawan)
            ->get('/admin/home-banners')
            ->assertForbidden();
    }
}
