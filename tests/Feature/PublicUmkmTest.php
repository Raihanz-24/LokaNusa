<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\UmkmBusiness;
use App\Models\UmkmProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicUmkmTest extends TestCase
{
    use RefreshDatabase;

    private function wisatawan(): User
    {
        return User::factory()->wisatawan()->create();
    }

    public function test_daftar_umkm_hanya_menampilkan_yang_terbit(): void
    {
        UmkmBusiness::factory()->published()->create(['name' => 'Batik Terbit']);
        UmkmBusiness::factory()->create(['name' => 'Batik Draf']);

        $this->actingAs($this->wisatawan())->get('/umkm')
            ->assertSuccessful()
            ->assertSee('Batik Terbit')
            ->assertDontSee('Batik Draf');
    }

    public function test_pencarian_umkm_berdasarkan_nama(): void
    {
        UmkmBusiness::factory()->published()->create(['name' => 'Sate Madura Cak Nur']);
        UmkmBusiness::factory()->published()->create(['name' => 'Toko Roti Manis']);

        $this->actingAs($this->wisatawan())->get('/umkm?q=Sate')
            ->assertSuccessful()
            ->assertSee('Sate Madura Cak Nur')
            ->assertDontSee('Toko Roti Manis');
    }

    public function test_filter_umkm_berdasarkan_jenis_kuliner(): void
    {
        UmkmBusiness::factory()->published()->kuliner()->create(['name' => 'Warung Nasi Goreng']);
        UmkmBusiness::factory()->published()->create(['name' => 'Bengkel Las Jaya']);

        $this->actingAs($this->wisatawan())->get('/umkm?tipe=kuliner')
            ->assertSuccessful()
            ->assertSee('Warung Nasi Goreng')
            ->assertDontSee('Bengkel Las Jaya');
    }

    public function test_detail_umkm_menampilkan_produk_terbit(): void
    {
        $business = UmkmBusiness::factory()->published()->create([
            'name' => 'Kopi Robusta Nusantara',
            'description' => 'Kedai kopi lokal.',
        ]);

        UmkmProduct::factory()->create([
            'umkm_business_id' => $business->id,
            'name' => 'Kopi Susu Gula Aren',
            'price' => 18000,
        ]);

        UmkmProduct::factory()->create([
            'umkm_business_id' => $business->id,
            'name' => 'Produk Belum Tayang',
            'status' => ContentStatus::Draft,
        ]);

        $this->actingAs($this->wisatawan())->get('/umkm/'.$business->slug)
            ->assertSuccessful()
            ->assertSee('Kopi Robusta Nusantara')
            ->assertSee('Kopi Susu Gula Aren')
            ->assertSee('18.000')
            ->assertDontSee('Produk Belum Tayang');
    }

    public function test_umkm_draf_tidak_dapat_diakses_publik(): void
    {
        $business = UmkmBusiness::factory()->create(['name' => 'Belum Tayang UMKM']);

        $this->actingAs($this->wisatawan())->get('/umkm/'.$business->slug)->assertNotFound();
    }

    public function test_beranda_menampilkan_umkm_yang_direkomendasikan(): void
    {
        UmkmBusiness::factory()->published()->create([
            'name' => 'Keripik Singkong Bu Yati',
            'is_recommended' => true,
        ]);

        $this->get('/')->assertSuccessful()->assertSee('Keripik Singkong Bu Yati');
    }
}
