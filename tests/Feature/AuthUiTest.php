<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_masuk_menampilkan_kartu_gelap_transparan_dan_tombol_unduh(): void
    {
        $this->get(route('login'))
            ->assertSuccessful()
            ->assertSee('bg-slate-900/55', false)   // kartu dark transparan
            ->assertSee('backdrop-blur', false)      // efek blur
            ->assertSee('Download App')
            ->assertSee('data-app-install', false);
    }

    public function test_validasi_gagal_menampilkan_peringatan_di_bawah_kolom(): void
    {
        User::factory()->wisatawan()->create([
            'email' => 'ada@example.com',
            'password' => 'rahasia123',
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'ada@example.com',
            'password' => 'salah-sekali',
        ]);

        $response->assertRedirect(route('login'));

        // Setelah redirect, halaman login menampilkan pesan error di bawah kolom
        // (blok aria-live ber-id email-error), bukan popup bawaan browser.
        $this->get(route('login'))
            ->assertSuccessful()
            ->assertSee('id="email-error"', false);

        $this->followingRedirects()
            ->from(route('login'))
            ->post(route('login'), [
                'email' => 'ada@example.com',
                'password' => 'salah-sekali',
            ])
            ->assertSee('email-error', false)
            ->assertSee('These credentials do not match our records.', false);
    }

    public function test_halaman_daftar_memakai_tema_gelap(): void
    {
        $this->get(route('register'))
            ->assertSuccessful()
            ->assertSee('bg-slate-900/55', false)
            ->assertSee('Start your journey');
    }

    public function test_ingat_saya_menyimpan_token_remember(): void
    {
        $user = User::factory()->wisatawan()->create([
            'email' => 'ingat@example.com',
            'password' => 'rahasia123',
            'remember_token' => null,
        ]);

        $this->assertNull($user->remember_token);

        $this->post(route('login'), [
            'email' => 'ingat@example.com',
            'password' => 'rahasia123',
            'remember' => '1',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_tanpa_ingat_saya_token_remember_tetap_kosong(): void
    {
        $user = User::factory()->wisatawan()->create([
            'email' => 'tanpa@example.com',
            'password' => 'rahasia123',
            'remember_token' => null,
        ]);

        $this->post(route('login'), [
            'email' => 'tanpa@example.com',
            'password' => 'rahasia123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_kolom_auth_memakai_kelas_anti_autofill(): void
    {
        // Kolom login memakai kelas .auth-input, yang di CSS di-override
        // agar warna autofill Gmail/Chrome tidak mengubah kolom jadi putih.
        $this->get(route('login'))
            ->assertSuccessful()
            ->assertSee('auth-input', false);

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString(':-webkit-autofill', $css);
    }

    public function test_halaman_masuk_menampilkan_logo_brand(): void
    {
        // Emblem logo (tanpa tulisan) + teks brand terpisah.
        $this->get(route('login'))
            ->assertSuccessful()
            ->assertSee('images/brand/logo-mark-sm.png', false)
            ->assertSee('LokaNusa');
    }

    public function test_aset_logo_dan_ikon_pwa_tersedia(): void
    {
        $assets = [
            'images/brand/logo.png',
            'images/brand/logo-full.png',
            'images/brand/logo-mark.png',
            'images/brand/logo-mark-sm.png',
            'favicon.ico',
            'favicon.png',
            'icons/icon-192.png',
            'icons/icon-512.png',
            'icons/icon-maskable-512.png',
            'icons/apple-touch-icon.png',
        ];

        foreach ($assets as $asset) {
            $this->assertFileExists(public_path($asset), "Aset {$asset} tidak ditemukan.");
        }
    }
}
