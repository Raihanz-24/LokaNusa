<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Filament\Pages\ModerationQueue;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Models\Accommodation;
use App\Models\CultureArticle;
use App\Models\Destination;
use App\Models\Event;
use App\Models\Review;
use App\Models\UmkmBusiness;
use App\Models\User;
use App\Services\ReviewService;
use App\Support\ModerationRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Moderasi terpusat (D-051): antrean lintas modul, aksi tolak, dan
 * moderasi ulasan oleh Pengelola LokaNusa (bukan hanya Developer).
 */
class ModerationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengelola_lokanusa_bisa_membuka_menu_ulasan(): void
    {
        $this->actingAs(User::factory()->pengelolaLokanusa()->create());
        $this->assertTrue(ReviewResource::canViewAny());

        $this->actingAs(User::factory()->adminPemerintah()->create());
        $this->assertTrue(ReviewResource::canViewAny());

        $this->actingAs(User::factory()->wisatawan()->create());
        $this->assertFalse(ReviewResource::canViewAny());

        $this->actingAs(User::factory()->pemilikDestinasi()->create());
        $this->assertFalse(ReviewResource::canViewAny());
    }

    public function test_developer_tetap_bisa_membuka_ulasan(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->assertTrue(ReviewResource::canViewAny());
    }

    public function test_antrean_moderasi_hanya_dapat_diakses_moderator(): void
    {
        $this->actingAs(User::factory()->pengelolaLokanusa()->create());
        $this->assertTrue(ModerationQueue::canAccess());

        $this->actingAs(User::factory()->pemilikUmkm()->create());
        $this->assertFalse(ModerationQueue::canAccess());

        $this->actingAs(User::factory()->wisatawan()->create());
        $this->assertFalse(ModerationQueue::canAccess());
    }

    public function test_antrean_mengumpulkan_konten_pending_dari_semua_modul(): void
    {
        Destination::factory()->create(['status' => ContentStatus::Pending]);
        UmkmBusiness::factory()->create(['status' => ContentStatus::Pending]);
        Accommodation::factory()->create(['status' => ContentStatus::Pending]);
        CultureArticle::factory()->create(['status' => ContentStatus::Pending]);
        Event::factory()->create(['status' => ContentStatus::Pending]);

        // Konten terbit tidak boleh muncul di antrean.
        Destination::factory()->published()->create();

        $items = ModerationRegistry::pending();

        $this->assertCount(5, $items);
        $this->assertSame(5, ModerationRegistry::pendingCount());
        $this->assertEqualsCanonicalizing(
            ['destination', 'umkm', 'accommodation', 'culture', 'event'],
            $items->pluck('type')->all(),
        );
    }

    public function test_setujui_lewat_registry_menerbitkan_konten(): void
    {
        $moderator = User::factory()->pengelolaLokanusa()->create();
        $destination = Destination::factory()->create(['status' => ContentStatus::Pending]);

        $this->actingAs($moderator);

        $result = ModerationRegistry::approve('destination', $destination->getKey());

        $this->assertSame(ContentStatus::Published, $result->status);
        $this->assertNotNull($result->published_at);
        $this->assertSame(0, ModerationRegistry::pendingCount());
    }

    public function test_tolak_lewat_registry_menyimpan_alasan(): void
    {
        $moderator = User::factory()->pengelolaLokanusa()->create();
        $umkm = UmkmBusiness::factory()->create(['status' => ContentStatus::Pending]);

        $this->actingAs($moderator);

        $result = ModerationRegistry::reject('umkm', $umkm->getKey(), 'Foto produk kurang jelas.');

        $this->assertSame(ContentStatus::Rejected, $result->status);
        $this->assertSame('Foto produk kurang jelas.', $result->rejection_reason);
    }

    public function test_tolak_tanpa_alasan_ditolak_service(): void
    {
        $moderator = User::factory()->pengelolaLokanusa()->create();
        $umkm = UmkmBusiness::factory()->create(['status' => ContentStatus::Pending]);

        $this->actingAs($moderator);

        $this->expectException(\InvalidArgumentException::class);

        ModerationRegistry::reject('umkm', $umkm->getKey(), '   ');
    }

    public function test_moderator_tidak_bisa_menyetujui_tanpa_permission(): void
    {
        $owner = User::factory()->pemilikDestinasi()->create();
        $destination = Destination::factory()->create(['status' => ContentStatus::Pending]);

        $this->actingAs($owner);

        $this->expectException(AuthorizationException::class);

        ModerationRegistry::approve('destination', $destination->getKey());
    }

    public function test_halaman_antrean_menampilkan_konten_pending(): void
    {
        $moderator = User::factory()->pengelolaLokanusa()->create();
        $destination = Destination::factory()->create([
            'name' => 'Pantai Kondang Merak',
            'status' => ContentStatus::Pending,
        ]);

        $this->actingAs($moderator)
            ->get(ModerationQueue::getUrl())
            ->assertOk()
            ->assertSee('Antrean Moderasi')
            ->assertSee('Pantai Kondang Merak');

        $this->assertSame(ContentStatus::Pending, $destination->fresh()->status);
    }

    public function test_halaman_antrean_menampilkan_pesan_kosong(): void
    {
        $this->actingAs(User::factory()->pengelolaLokanusa()->create())
            ->get(ModerationQueue::getUrl())
            ->assertOk()
            ->assertSee('Antrean kosong');
    }

    public function test_moderator_dapat_menyetujui_dari_halaman_antrean(): void
    {
        $moderator = User::factory()->pengelolaLokanusa()->create();
        $destination = Destination::factory()->create(['status' => ContentStatus::Pending]);

        $this->actingAs($moderator);

        Livewire::test(ModerationQueue::class)
            ->call('approve', 'destination', $destination->getKey())
            ->assertHasNoErrors();

        $this->assertSame(ContentStatus::Published, $destination->fresh()->status);
    }

    public function test_moderator_dapat_menolak_dengan_alasan_dari_halaman_antrean(): void
    {
        $moderator = User::factory()->pengelolaLokanusa()->create();
        $destination = Destination::factory()->create(['status' => ContentStatus::Pending]);

        $this->actingAs($moderator);

        Livewire::test(ModerationQueue::class)
            ->call('startReject', 'destination', $destination->getKey())
            ->set('reason', 'Koordinat lokasi belum tepat.')
            ->call('reject')
            ->assertHasNoErrors();

        $fresh = $destination->fresh();
        $this->assertSame(ContentStatus::Rejected, $fresh->status);
        $this->assertSame('Koordinat lokasi belum tepat.', $fresh->rejection_reason);
    }

    public function test_tolak_tanpa_alasan_menampilkan_error_validasi(): void
    {
        $moderator = User::factory()->pengelolaLokanusa()->create();
        $destination = Destination::factory()->create(['status' => ContentStatus::Pending]);

        $this->actingAs($moderator);

        Livewire::test(ModerationQueue::class)
            ->call('startReject', 'destination', $destination->getKey())
            ->set('reason', '')
            ->call('reject')
            ->assertHasErrors(['reason']);

        $this->assertSame(ContentStatus::Pending, $destination->fresh()->status);
    }

    public function test_moderator_dapat_menyembunyikan_ulasan_melanggar(): void
    {
        $review = Review::factory()->published()->for(Destination::factory(), 'reviewable')->create(['rating' => 1]);

        app(ReviewService::class)->hide($review, 'Mengandung ujaran kebencian.');

        $fresh = $review->fresh();
        $this->assertSame(ContentStatus::Rejected, $fresh->status);
        $this->assertSame('Mengandung ujaran kebencian.', $fresh->rejection_reason);
        $this->assertNull($fresh->published_at);

        // Ulasan tersembunyi tidak lagi muncul di daftar terbit.
        $this->assertSame(0, Review::query()->published()->count());
    }

    public function test_ulasan_tersembunyi_dapat_diterbitkan_lagi(): void
    {
        $review = Review::factory()->published()->for(Destination::factory(), 'reviewable')->create();

        $service = app(ReviewService::class);

        $service->hide($review, 'Salah laporkan.');
        $service->publish($review);

        $fresh = $review->fresh();
        $this->assertSame(ContentStatus::Published, $fresh->status);
        $this->assertNull($fresh->rejection_reason);
        $this->assertNotNull($fresh->published_at);
    }

    public function test_penyembunyian_ulasan_wajib_alasan(): void
    {
        $review = Review::factory()->published()->for(Destination::factory(), 'reviewable')->create();

        $this->expectException(\InvalidArgumentException::class);

        app(ReviewService::class)->hide($review, '  ');
    }

    public function test_pemilik_konten_tidak_bisa_menghapus_ulasan(): void
    {
        $review = Review::factory()->published()->for(Destination::factory(), 'reviewable')->create();

        $this->actingAs(User::factory()->pemilikDestinasi()->create());

        $this->assertFalse(ReviewResource::canDelete($review));
        $this->assertFalse(ReviewResource::canEdit($review));
        $this->assertFalse(ReviewResource::canCreate());
    }

    public function test_moderator_bisa_menghapus_ulasan(): void
    {
        $review = Review::factory()->published()->for(Destination::factory(), 'reviewable')->create();

        $this->actingAs(User::factory()->pengelolaLokanusa()->create());

        $this->assertTrue(ReviewResource::canDelete($review));
    }
}
