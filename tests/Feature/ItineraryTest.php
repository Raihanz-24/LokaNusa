<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Itinerary;
use App\Models\ItineraryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItineraryTest extends TestCase
{
    use RefreshDatabase;

    private function wisatawan(): User
    {
        return User::factory()->wisatawan()->create();
    }

    public function test_wisatawan_dapat_membuat_rencana(): void
    {
        $user = $this->wisatawan();

        $response = $this->actingAs($user)->post('/rencana', [
            'title' => 'Liburan Bromo 3 Hari',
        ]);

        $response->assertRedirect();

        $itinerary = Itinerary::query()->first();
        $this->assertNotNull($itinerary);
        $this->assertSame($user->getKey(), $itinerary->user_id);
        $this->assertSame('liburan-bromo-3-hari', $itinerary->slug);
    }

    public function test_menambah_konten_ke_rencana_dan_mengelompokkan_per_hari(): void
    {
        $user = $this->wisatawan();
        $destination = Destination::factory()->published()->create(['name' => 'Kawah Bromo']);

        $itinerary = Itinerary::factory()->for($user)->create(['title' => 'Trip Uji']);

        $this->actingAs($user)
            ->post("/rencana/{$itinerary->slug}/butir/destination/{$destination->id}", ['day' => 2])
            ->assertRedirect();

        $this->assertDatabaseHas('itinerary_items', [
            'itinerary_id' => $itinerary->id,
            'item_type' => Destination::class,
            'item_id' => $destination->id,
            'day' => 2,
        ]);

        $this->actingAs($user)->get("/rencana/{$itinerary->slug}")
            ->assertSuccessful()
            ->assertSee('Hari 2')
            ->assertSee('Kawah Bromo');
    }

    public function test_tidak_dapat_menambah_konten_draf(): void
    {
        $user = $this->wisatawan();
        $draft = Destination::factory()->create(['name' => 'Destinasi Draf']);
        $itinerary = Itinerary::factory()->for($user)->create();

        $this->actingAs($user)
            ->post("/rencana/{$itinerary->slug}/butir/destination/{$draft->id}")
            ->assertNotFound();
    }

    public function test_pengguna_lain_tidak_boleh_melihat_atau_mengubah_rencana(): void
    {
        $owner = $this->wisatawan();
        $other = $this->wisatawan();
        $itinerary = Itinerary::factory()->for($owner)->create();

        $this->actingAs($other)->get("/rencana/{$itinerary->slug}")->assertForbidden();
        $this->actingAs($other)->delete("/rencana/{$itinerary->slug}")->assertForbidden();
    }

    public function test_quick_add_membuat_rencana_otomatis(): void
    {
        $user = $this->wisatawan();
        $destination = Destination::factory()->published()->create(['name' => 'Pantai Uji']);

        $this->actingAs($user)
            ->post("/rencana/cepat/destination/{$destination->id}")
            ->assertRedirect();

        $this->assertDatabaseCount('itineraries', 1);
        $this->assertDatabaseHas('itineraries', ['user_id' => $user->id, 'title' => 'Rencana Saya']);

        $item = ItineraryItem::query()->first();
        $this->assertSame($destination->id, $item->item_id);
    }

    public function test_mengubah_dan_menghapus_butir(): void
    {
        $user = $this->wisatawan();
        $destination = Destination::factory()->published()->create();
        $itinerary = Itinerary::factory()->for($user)->create();

        $this->actingAs($user)->post("/rencana/{$itinerary->slug}/butir/destination/{$destination->id}", ['day' => 1]);

        $item = ItineraryItem::query()->firstOrFail();

        $this->actingAs($user)
            ->patch("/rencana/butir/{$item->id}", ['day' => 3, 'note' => 'Pagi'])
            ->assertRedirect();

        $this->assertDatabaseHas('itinerary_items', ['id' => $item->id, 'day' => 3, 'note' => 'Pagi']);

        $this->actingAs($user)->delete("/rencana/butir/{$item->id}")->assertRedirect();
        $this->assertDatabaseMissing('itinerary_items', ['id' => $item->id]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/rencana')->assertRedirect();
    }

    public function test_quick_add_ke_rencana_tertentu(): void
    {
        $user = $this->wisatawan();
        $destination = Destination::factory()->published()->create();
        $itinerary = Itinerary::factory()->for($user)->create(['title' => 'Rencana A']);

        $this->actingAs($user)->post("/rencana/cepat/destination/{$destination->id}", [
            'itinerary_id' => $itinerary->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('itinerary_items', [
            'itinerary_id' => $itinerary->id,
            'item_id' => $destination->id,
        ]);
    }

    public function test_quick_add_membuat_rencana_baru_dari_judul(): void
    {
        $user = $this->wisatawan();
        $destination = Destination::factory()->published()->create();

        $this->actingAs($user)->post("/rencana/cepat/destination/{$destination->id}", [
            'title' => 'Trip Baru Saya',
        ])->assertRedirect();

        $itinerary = Itinerary::query()->firstOrFail();
        $this->assertSame('Trip Baru Saya', $itinerary->title);
        $this->assertDatabaseHas('itinerary_items', ['itinerary_id' => $itinerary->id]);
    }

    public function test_tidak_bisa_menambahkan_konten_yang_sama_dua_kali(): void
    {
        $user = $this->wisatawan();
        $destination = Destination::factory()->published()->create();
        $itinerary = Itinerary::factory()->for($user)->create();

        $this->actingAs($user)->post("/rencana/cepat/destination/{$destination->id}", [
            'itinerary_id' => $itinerary->id,
        ])->assertRedirect();

        // Percobaan kedua tidak menambah duplikat.
        $this->actingAs($user)->post("/rencana/cepat/destination/{$destination->id}", [
            'itinerary_id' => $itinerary->id,
        ])->assertRedirect()
            ->assertSessionHas('status', 'Konten ini sudah ada di rencana "'.$itinerary->title.'".');

        $this->assertSame(1, ItineraryItem::query()
            ->where('itinerary_id', $itinerary->id)
            ->where('item_id', $destination->id)
            ->count());
    }
}
