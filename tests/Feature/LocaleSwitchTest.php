<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_bahasa_adalah_inggris(): void
    {
        $this->get(route('login'))
            ->assertSuccessful()
            ->assertSee('Sign in to continue exploring the archipelago.')
            ->assertSee('Remember me')
            ->assertSee('Download App');

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Hello,');
    }

    public function test_dapat_beralih_ke_bahasa_indonesia(): void
    {
        $this->get(route('locale.update', 'id'))
            ->assertRedirect();

        $this->get(route('login'))
            ->assertSuccessful()
            ->assertSee('Masuk untuk melanjutkan menjelajah Nusantara.')
            ->assertSee('Ingat saya')
            ->assertSee('Unduh Aplikasi');
    }

    public function test_pilihan_bahasa_tersimpan_di_sesi(): void
    {
        $this->withSession(['locale' => 'id'])->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Halo,');
    }

    public function test_bahasa_tidak_didukung_diabaikan(): void
    {
        $this->get(route('locale.update', 'fr'))->assertRedirect();

        // Tetap bahasa Inggris (default).
        $this->get(route('login'))
            ->assertSee('Sign in to continue exploring the archipelago.');
    }

    public function test_header_publik_punya_switch_bahasa(): void
    {
        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee(route('locale.update', 'en'), false)
            ->assertSee(route('locale.update', 'id'), false);
    }
}
