<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\Favorite;
use App\Models\Review;
use App\Models\UmkmBusiness;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngagementTest extends TestCase
{
    use RefreshDatabase;

    private function wisatawan(): User
    {
        return User::factory()->wisatawan()->create();
    }

    public function test_wisatawan_dapat_menyimpan_dan_menghapus_favorit(): void
    {
        $user = $this->wisatawan();
        $destination = Destination::factory()->published()->create();

        $this->actingAs($user)
            ->post(route('favorites.toggle', ['type' => 'destination', 'id' => $destination->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => Destination::class,
            'favoritable_id' => $destination->id,
        ]);

        // Toggle lagi → terhapus.
        $this->actingAs($user)
            ->post(route('favorites.toggle', ['type' => 'destination', 'id' => $destination->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'favoritable_id' => $destination->id,
        ]);
    }

    public function test_konten_tidak_terbit_tidak_dapat_difavoritkan(): void
    {
        $user = $this->wisatawan();
        $draft = Destination::factory()->create(['status' => ContentStatus::Draft]);

        $this->actingAs($user)
            ->post(route('favorites.toggle', ['type' => 'destination', 'id' => $draft->id]))
            ->assertNotFound();
    }

    public function test_halaman_favorit_menampilkan_konten_tersimpan_dan_wajib_login(): void
    {
        $this->get('/favorit')->assertRedirect(route('home'));

        $user = $this->wisatawan();
        $umkm = UmkmBusiness::factory()->published()->create(['name' => 'Kopi Tengger Enak']);
        Favorite::query()->create([
            'user_id' => $user->id,
            'favoritable_type' => UmkmBusiness::class,
            'favoritable_id' => $umkm->id,
        ]);

        $this->actingAs($user)->get('/favorit')
            ->assertSuccessful()
            ->assertSee('Kopi Tengger Enak');
    }

    public function test_ulasan_langsung_tampil_tanpa_moderasi(): void
    {
        $user = $this->wisatawan();
        $destination = Destination::factory()->published()->create();

        $this->actingAs($user)->post(
            route('reviews.store', ['type' => 'destination', 'id' => $destination->id]),
            ['rating' => 5, 'comment' => 'Pemandangan luar biasa!']
        )->assertRedirect();

        $review = Review::query()->firstOrFail();
        $this->assertSame(ContentStatus::Published, $review->status);
        $this->assertSame(5, $review->rating);
        $this->assertNotNull($review->published_at);

        // Langsung tampil di halaman detail (walau dilihat pengguna lain).
        $this->actingAs($this->wisatawan())->get(route('destinations.show', $destination->slug))
            ->assertSuccessful()
            ->assertSee('Pemandangan luar biasa!');
    }

    public function test_ulasan_mengandung_sara_dapat_dihapus_super_admin(): void
    {
        $author = $this->wisatawan();
        $destination = Destination::factory()->published()->create();

        $this->actingAs($author)->post(
            route('reviews.store', ['type' => 'destination', 'id' => $destination->id]),
            ['rating' => 1, 'comment' => 'Konten bermuatan SARA']
        )->assertRedirect();

        $review = Review::query()->firstOrFail();
        app(ReviewService::class)->delete($review);

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);

        $this->actingAs($author)->get(route('destinations.show', $destination->slug))
            ->assertDontSee('Konten bermuatan SARA');
    }

    public function test_rating_harus_antara_1_dan_5(): void
    {
        $user = $this->wisatawan();
        $destination = Destination::factory()->published()->create();

        $this->actingAs($user)->post(
            route('reviews.store', ['type' => 'destination', 'id' => $destination->id]),
            ['rating' => 6]
        )->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_rata_rata_dan_jumlah_rating_dihitung_dari_ulasan_terbit(): void
    {
        $destination = Destination::factory()->published()->create();

        Review::factory()->published()->create([
            'reviewable_type' => Destination::class,
            'reviewable_id' => $destination->id,
            'rating' => 5,
        ]);
        Review::factory()->published()->create([
            'reviewable_type' => Destination::class,
            'reviewable_id' => $destination->id,
            'rating' => 3,
        ]);

        $this->assertSame(2, $destination->reviewCount());
        $this->assertSame(4.0, $destination->averageRating());
    }

    public function test_moderator_dan_developer_dapat_mengakses_panel_ulasan(): void
    {
        // D-051: moderasi ulasan mengikuti permission ModerateContent,
        // sehingga Pengelola LokaNusa & Admin Pemerintah ikut berwenang.
        $this->actingAs(User::factory()->adminPemerintah()->create());
        $this->assertTrue(ReviewResource::canViewAny());

        $this->actingAs(User::factory()->pengelolaLokanusa()->create());
        $this->assertTrue(ReviewResource::canViewAny());

        $this->actingAs(User::factory()->pemilikUmkm()->create());
        $this->assertFalse(ReviewResource::canViewAny());

        $this->actingAs(User::factory()->wisatawan()->create());
        $this->assertFalse(ReviewResource::canViewAny());

        $this->actingAs(User::factory()->superAdmin()->create());
        $this->assertTrue(ReviewResource::canViewAny());
    }

    public function test_setiap_jenis_konten_dapat_diulas(): void
    {
        $user = $this->wisatawan();

        $targets = [
            'destination' => Destination::factory()->published()->create(),
            'umkm' => UmkmBusiness::factory()->published()->create(),
            'penginapan' => Accommodation::factory()->published()->create(),
        ];

        foreach ($targets as $type => $model) {
            $this->actingAs($user)->post(
                route('reviews.store', ['type' => $type, 'id' => $model->id]),
                ['rating' => 4]
            )->assertRedirect();
        }

        $this->assertSame(3, Review::query()->count());
    }

    public function test_wisatawan_tidak_punya_permission_moderasi(): void
    {
        $user = $this->wisatawan();

        $this->assertFalse($user->hasPermission(Permission::ModerateContent));
    }
}
