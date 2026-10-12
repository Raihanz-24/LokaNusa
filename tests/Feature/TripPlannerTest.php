<?php

namespace Tests\Feature;

use App\Models\AiKnowledgeEntry;
use App\Models\AiUsage;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TripPlannerTest extends TestCase
{
    use RefreshDatabase;

    private function wisatawan(): User
    {
        return User::factory()->wisatawan()->create();
    }

    public function test_tamu_diarahkan_ke_beranda_dengan_popup(): void
    {
        $response = $this->get('/ai-trip-planner');

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('auth_prompt', true);
    }

    public function test_pengguna_dapat_membuka_halaman_chat(): void
    {
        $this->actingAs($this->wisatawan())
            ->get('/ai-trip-planner')
            ->assertSuccessful()
            ->assertSee('AI Trip Planner')
            ->assertSee('data-chat', false)
            // Sapaan pembuka tampil saat percakapan kosong (default: Inggris).
            ->assertSee('LokaNusa trip assistant', false);
    }

    public function test_halaman_chat_setinggi_layar_dengan_kolom_ketik_di_bawah(): void
    {
        $this->actingAs($this->wisatawan())
            ->get('/ai-trip-planner')
            ->assertSuccessful()
            // Area pesan punya wadah khusus untuk menggulir sendiri.
            ->assertSee('data-chat-scroll', false)
            // Body dikunci setinggi layar agar hanya area pesan yang menggulir
            // (kolom ketik & alat mengambang tetap terlihat saat scroll ke atas).
            ->assertSee('h-dvh', false)
            // Bottom navbar tetap tampil di halaman chat agar navigasi tersedia.
            ->assertSee('Navigasi bawah', false)
            // Footer publik tetap disembunyikan pada layout full-height.
            ->assertDontSee('&copy;', false);
    }

    public function test_mengirim_pesan_mendapat_balasan_asisten(): void
    {
        $response = $this->actingAs($this->wisatawan())
            ->postJson('/ai-trip-planner/kirim', ['message' => 'Halo']);

        $response->assertSuccessful()
            ->assertJsonStructure(['messages' => [['role', 'text', 'time']]]);

        $messages = $response->json('messages');

        // Sapaan pembuka + pesan pengguna + balasan asisten.
        $this->assertSame('assistant', $messages[0]['role']);
        $this->assertSame('user', $messages[1]['role']);
        $this->assertSame('Halo', $messages[1]['text']);
        $this->assertSame('assistant', end($messages)['role']);
    }

    public function test_pesan_kosong_ditolak(): void
    {
        $this->actingAs($this->wisatawan())
            ->postJson('/ai-trip-planner/kirim', ['message' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function test_balasan_kata_kunci_kuliner(): void
    {
        $response = $this->actingAs($this->wisatawan())
            ->postJson('/ai-trip-planner/kirim', ['message' => 'Rekomendasi kuliner dong']);

        $response->assertSuccessful();

        $messages = $response->json('messages');
        $this->assertStringContainsString('culinary', end($messages)['text']);
    }

    public function test_mengosongkan_percakapan(): void
    {
        $user = $this->wisatawan();

        $this->actingAs($user)->postJson('/ai-trip-planner/kirim', ['message' => 'Halo']);
        $this->actingAs($user)->deleteJson('/ai-trip-planner')
            ->assertSuccessful()
            ->assertJsonPath('messages', []);

        // Halaman kembali menampilkan sapaan pembuka (riwayat kosong).
        $this->actingAs($user)
            ->get('/ai-trip-planner')
            ->assertSuccessful()
            ->assertSee('LokaNusa trip assistant', false);
    }

    public function test_halaman_menyertakan_meta_csrf_untuk_ajax(): void
    {
        $this->actingAs($this->wisatawan())
            ->get('/ai-trip-planner')
            ->assertSuccessful()
            // Tanpa meta ini, fetch() chat mengirim token kosong → 419.
            ->assertSee('name="csrf-token"', false);
    }

    public function test_pesan_tersimpan_dan_tidak_hilang_saat_pindah_halaman(): void
    {
        $user = $this->wisatawan();

        $this->actingAs($user)->postJson('/ai-trip-planner/kirim', ['message' => 'Halo']);

        // Kunjungi halaman lain dulu, lalu balik ke chat: pesan tetap ada.
        $this->actingAs($user)->get('/')->assertSuccessful();

        $this->actingAs($user)
            ->get('/ai-trip-planner')
            ->assertSuccessful()
            ->assertSee('data-chat', false)
            // Pesan 'Halo' dari kunjungan sebelumnya masih tersimpan.
            ->assertSee('Halo', false);

        $this->assertDatabaseHas('trip_planner_messages', [
            'user_id' => $user->id,
            'role' => 'user',
            'content' => 'Halo',
        ]);
    }

    public function test_alat_mengambang_tersedia(): void
    {
        $this->actingAs($this->wisatawan())
            ->get('/ai-trip-planner')
            ->assertSuccessful()
            // Tombol kembali ke atas & reset ada di panel alat mengambang.
            ->assertSee('data-chat-top', false)
            ->assertSee('data-chat-bottom', false)
            ->assertSee('data-chat-clear', false);
    }

    public function test_tanpa_batas_secara_bawaan(): void
    {
        // Default: tidak ada batas, jadi riwayat panjang pun tidak dipangkas.
        $user = $this->wisatawan();

        for ($i = 0; $i < 150; $i++) {
            $user->tripPlannerMessages()->create(['role' => 'assistant', 'content' => 'lama-'.$i]);
        }

        $this->actingAs($user)->postJson('/ai-trip-planner/kirim', ['message' => 'Halo'])
            ->assertSuccessful();

        // 150 pesan lama + sapaan? (sudah ada) + pesan user + balasan.
        $this->assertGreaterThanOrEqual(150, $user->tripPlannerMessages()->count());
        $this->assertDatabaseHas('trip_planner_messages', [
            'user_id' => $user->id,
            'content' => 'lama-0',
        ]);
    }

    public function test_kuota_harian_mengikuti_pengaturan_admin(): void
    {
        AppSetting::set('ai_daily_prompt_limit', '1');

        $user = $this->wisatawan();

        // Prompt pertama boleh.
        $this->actingAs($user)->postJson('/ai-trip-planner/kirim', ['message' => 'Halo'])
            ->assertSuccessful();

        // Prompt kedua melebihi batas.
        $this->actingAs($user)->postJson('/ai-trip-planner/kirim', ['message' => 'Halo lagi'])
            ->assertStatus(429)
            ->assertJsonPath('quota_exceeded', true);
    }

    public function test_riwayat_dipangkas_saat_batas_pesan_diatur(): void
    {
        AppSetting::set('ai_max_messages', '10');

        $user = $this->wisatawan();

        for ($i = 0; $i < 10; $i++) {
            $user->tripPlannerMessages()->create(['role' => 'assistant', 'content' => 'lama-'.$i]);
        }

        $this->actingAs($user)->postJson('/ai-trip-planner/kirim', ['message' => 'Halo']);

        // Jumlah pesan tidak melebihi batas; yang paling lama terhapus.
        $this->assertSame(10, $user->tripPlannerMessages()->count());
        $this->assertDatabaseMissing('trip_planner_messages', [
            'user_id' => $user->id,
            'content' => 'lama-0',
        ]);
    }

    public function test_menggunakan_ai_nyata_dan_mencatat_token(): void
    {
        config()->set('ai.base_url', 'https://ai.test/v1');
        config()->set('ai.key', 'secret');
        config()->set('ai.model', 'deepseek-flash-4');

        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => 'Halo dari AI!']]],
                'usage' => ['prompt_tokens' => 12, 'completion_tokens' => 8, 'total_tokens' => 20],
            ]),
        ]);

        $user = $this->wisatawan();

        $response = $this->actingAs($user)->postJson('/ai-trip-planner/kirim', ['message' => 'Halo']);

        $response->assertSuccessful();
        $messages = $response->json('messages');
        $this->assertSame('Halo dari AI!', end($messages)['text']);

        $this->assertDatabaseHas('ai_usages', [
            'user_id' => $user->id,
            'context' => 'trip_planner',
            'model' => 'deepseek-flash-4',
            'total_tokens' => 20,
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/chat/completions'));
    }

    public function test_batas_token_per_pengguna_menolak_prompt(): void
    {
        config()->set('ai.base_url', 'https://ai.test/v1');
        config()->set('ai.key', 'secret');

        $user = $this->wisatawan();
        $user->forceFill(['ai_token_limit' => 10])->save();

        AiUsage::create([
            'user_id' => $user->id,
            'context' => 'trip_planner',
            'prompt_tokens' => 10,
            'completion_tokens' => 0,
            'total_tokens' => 10,
        ]);

        Http::fake();

        $this->actingAs($user)
            ->postJson('/ai-trip-planner/kirim', ['message' => 'Halo'])
            ->assertStatus(429)
            ->assertJsonPath('quota_exceeded', true);

        Http::assertNothingSent();
    }

    public function test_basis_pengetahuan_menjawab_tanpa_memanggil_ai(): void
    {
        config()->set('ai.base_url', 'https://ai.test/v1');
        config()->set('ai.key', 'secret');

        AiKnowledgeEntry::create([
            'title' => 'Tentang developer',
            'keywords' => 'siapa developer, siapa yang membuat',
            'answer' => 'Dikembangkan oleh M. Raihan Ramadhan.',
            'is_active' => true,
        ]);

        Http::fake();

        $response = $this->actingAs($this->wisatawan())
            ->postJson('/ai-trip-planner/kirim', ['message' => 'Siapa developer aplikasi ini?']);

        $response->assertSuccessful();
        $messages = $response->json('messages');
        $this->assertStringContainsString('M. Raihan Ramadhan', end($messages)['text']);

        // Basis pengetahuan dijawab lokal -> AI tidak dipanggil.
        Http::assertNothingSent();
    }

    public function test_balasan_mengikuti_bahasa_inggris_bawaan(): void
    {
        $response = $this->actingAs($this->wisatawan())
            ->postJson('/ai-trip-planner/kirim', ['message' => 'Halo']);

        $messages = $response->json('messages');

        $this->assertStringContainsString('LokaNusa trip assistant', $messages[0]['text']);
    }

    public function test_balasan_mengikuti_bahasa_indonesia(): void
    {
        $response = $this->withSession(['locale' => 'id'])
            ->actingAs($this->wisatawan())
            ->postJson('/ai-trip-planner/kirim', ['message' => 'Halo']);

        $messages = $response->json('messages');

        $this->assertStringContainsString('asisten perjalanan LokaNusa', $messages[0]['text']);
    }
}
