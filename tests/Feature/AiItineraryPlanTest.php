<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Destination;
use App\Models\Itinerary;
use App\Models\ItineraryItem;
use App\Models\Region;
use App\Models\TripPlannerMessage;
use App\Models\User;
use App\Services\Ai\ItineraryPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiItineraryPlanTest extends TestCase
{
    use RefreshDatabase;

    private function wisatawan(): User
    {
        return User::factory()->wisatawan()->create();
    }

    private function jsonPlan(): string
    {
        return json_encode([
            'intro' => 'Ini usulan perjalanan tiga hari.',
            'title' => 'Bromo 3 Hari',
            'days' => [
                ['day' => 1, 'activities' => [
                    ['start' => '05:00', 'end' => '09:00', 'title' => 'Menikmati matahari terbit', 'ref' => 'Kawah Bromo', 'cost' => 50000, 'cost_note' => 'per orang'],
                    ['start' => '12:00', 'end' => '13:00', 'title' => 'Makan siang lokal', 'ref' => null, 'note' => 'Coba rawon', 'cost' => 25000],
                ]],
                ['day' => 2, 'activities' => [
                    ['start' => '08:00', 'end' => '11:00', 'title' => 'Wisata kawah', 'ref' => 'Kawah Bromo'],
                ]],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function test_mendeteksi_permintaan_itinerary(): void
    {
        $planner = app(ItineraryPlanner::class);

        $this->assertTrue($planner->wantsItinerary('Susun itinerary 3 hari di Probolinggo'));
        $this->assertTrue($planner->wantsItinerary('buatkan rencana perjalanan 2 hari'));
        $this->assertFalse($planner->wantsItinerary('Rekomendasi kuliner dong'));
    }

    public function test_parse_menolak_teks_bukan_json(): void
    {
        $planner = app(ItineraryPlanner::class);

        $this->assertNull($planner->parse('Maaf saya tidak bisa.'));
    }

    public function test_parse_menormalkan_jam_dan_membuang_pagar_kode(): void
    {
        $planner = app(ItineraryPlanner::class);

        $wrapped = "```json\n".$this->jsonPlan()."\n```";
        $plan = $planner->parse($wrapped);

        $this->assertNotNull($plan);
        $this->assertSame('Bromo 3 Hari', $plan['title']);
        $this->assertCount(2, $plan['days']);
        $this->assertSame('05:00', $plan['days'][0]['activities'][0]['start']);
        $this->assertSame(50000, $plan['days'][0]['activities'][0]['cost']);
        $this->assertSame(25000, $plan['days'][0]['activities'][1]['cost']);
    }

    public function test_balasan_itinerary_menyimpan_plan_pada_pesan(): void
    {
        config()->set('ai.base_url', 'https://ai.test/v1');
        config()->set('ai.key', 'secret');
        config()->set('ai.model', 'deepseek');

        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => $this->jsonPlan()]]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
            ]),
        ]);

        $user = $this->wisatawan();

        $response = $this->actingAs($user)
            ->postJson('/ai-trip-planner/kirim', ['message' => 'Susun itinerary 3 hari di Probolinggo']);

        $response->assertSuccessful();
        $messages = $response->json('messages');
        $last = end($messages);

        $this->assertTrue($last['has_plan']);

        $stored = TripPlannerMessage::query()->where('role', 'assistant')->latest('id')->first();
        $this->assertSame('Bromo 3 Hari', $stored->plan['title']);

        $this->assertDatabaseHas('trip_planner_messages', [
            'user_id' => $user->id,
            'role' => 'assistant',
        ]);

        $this->assertNotNull($stored->plan);
    }

    public function test_menyimpan_itinerary_ai_ke_rencana_baru(): void
    {
        $user = $this->wisatawan();

        $message = $user->tripPlannerMessages()->create([
            'role' => 'assistant',
            'content' => 'Itinerary',
            'plan' => json_decode($this->jsonPlan(), true),
        ]);

        $response = $this->actingAs($user)
            ->postJson("/ai-trip-planner/simpan-rencana/{$message->id}", ['itinerary_id' => null]);

        $response->assertSuccessful();
        $this->assertSame(3, $response->json('added'));

        $itinerary = Itinerary::query()->firstOrFail();
        $this->assertSame('Bromo 3 Hari', $itinerary->title);

        // Total estimasi biaya dihitung dari kegiatan.
        $this->assertSame(75000, $itinerary->fresh()->load('items')->totalCost());
    }

    public function test_biaya_diturunkan_dari_harga_konten_terkait(): void
    {
        $focus = Region::factory()->create();
        AppSetting::setFocusRegion($focus->id);

        $destination = Destination::factory()->published()->create([
            'name' => 'Kawah Bromo',
            'region_id' => $focus->id,
            'ticket_price' => 320000,
        ]);

        $user = $this->wisatawan();
        $itinerary = Itinerary::factory()->for($user)->create();

        $this->actingAs($user)
            ->post("/rencana/{$itinerary->slug}/butir/destination/{$destination->id}", ['day' => 1]);

        $item = ItineraryItem::query()->firstOrFail();

        // Biaya mengikuti harga tiket destinasi walau kolom cost kosong.
        $this->assertNull($item->cost);
        $this->assertSame(320000, $item->effectiveCost());
    }

    public function test_kegiatan_menautkan_konten_publik_yang_ada(): void
    {
        $focus = Region::factory()->create();
        AppSetting::setFocusRegion($focus->id);

        Destination::factory()->published()->create([
            'name' => 'Kawah Bromo',
            'region_id' => $focus->id,
        ]);

        $user = $this->wisatawan();

        $message = $user->tripPlannerMessages()->create([
            'role' => 'assistant',
            'content' => 'Itinerary',
            'plan' => json_decode($this->jsonPlan(), true),
        ]);

        $this->actingAs($user)
            ->postJson("/ai-trip-planner/simpan-rencana/{$message->id}")
            ->assertSuccessful();

        // "Kawah Bromo" dirujuk dua kali -> ditautkan sebagai butir konten
        // (idempoten: hanya sekali tersimpan), bukan kegiatan bebas.
        $this->assertDatabaseHas('itinerary_items', [
            'item_type' => Destination::class,
        ]);

        $this->assertSame(1, ItineraryItem::query()
            ->whereNotNull('item_type')
            ->count());
    }

    public function test_pengguna_lain_tidak_boleh_menyimpan_plan(): void
    {
        $owner = $this->wisatawan();
        $other = $this->wisatawan();

        $message = $owner->tripPlannerMessages()->create([
            'role' => 'assistant',
            'content' => 'Itinerary',
            'plan' => json_decode($this->jsonPlan(), true),
        ]);

        $this->actingAs($other)
            ->postJson("/ai-trip-planner/simpan-rencana/{$message->id}")
            ->assertForbidden();
    }

    public function test_tanpa_ai_mengembalikan_balasan_cadangan(): void
    {
        // AI tidak dikonfigurasi pada pengujian (phpunit.xml) -> fallback.
        $response = $this->actingAs($this->wisatawan())
            ->postJson('/ai-trip-planner/kirim', ['message' => 'Susun itinerary 3 hari']);

        $response->assertSuccessful();
        $messages = $response->json('messages');
        $last = end($messages);

        $this->assertFalse($last['has_plan']);
    }

    public function test_halaman_chat_menampilkan_tombol_simpan_untuk_pesan_berplan(): void
    {
        $user = $this->wisatawan();

        $user->tripPlannerMessages()->create([
            'role' => 'assistant',
            'content' => 'Itinerary usulan',
            'plan' => json_decode($this->jsonPlan(), true),
        ]);

        $this->actingAs($user)
            ->get('/ai-trip-planner')
            ->assertSuccessful()
            ->assertSee('data-chat-save-plan', false)
            ->assertSee('Save to Plan');
    }
}
