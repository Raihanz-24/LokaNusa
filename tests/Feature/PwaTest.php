<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    public function test_manifest_pwa_tersedia_dan_valid(): void
    {
        $path = public_path('manifest.webmanifest');
        $this->assertFileExists($path);

        $manifest = json_decode((string) file_get_contents($path), true);

        $this->assertIsArray($manifest);
        $this->assertSame('LokaNusa', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertNotEmpty($manifest['icons']);

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')), "Ikon {$icon['src']} tidak ditemukan.");
        }
    }

    public function test_service_worker_dan_offline_tersedia(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));

        $sw = (string) file_get_contents(public_path('sw.js'));

        // Tidak menangani POST & tidak meng-cache respons ber-sesi.
        $this->assertStringContainsString("request.method !== 'GET'", $sw);
        $this->assertStringContainsString('Set-Cookie', $sw);
        $this->assertStringContainsString('/offline.html', $sw);
    }

    public function test_layout_publik_menautkan_manifest_dan_tombol_pasang(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('/manifest.webmanifest', false)
            ->assertSee('apple-touch-icon', false)
            ->assertSee('data-pwa', false)
            ->assertSee("navigator.serviceWorker.register('/sw.js')", false);
    }
}
