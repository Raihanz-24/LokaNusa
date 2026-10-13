<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\AppSetting;
use App\Models\Destination;
use App\Models\Event;
use App\Models\Region;
use App\Models\User;
use App\Services\DestinationService;
use App\Services\EventService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function wisatawan(): User
    {
        return User::factory()->wisatawan()->create();
    }

    public function test_mengirim_dan_menandai_notifikasi_dibaca(): void
    {
        $user = $this->wisatawan();
        $service = app(NotificationService::class);

        $service->notify($user, AppNotification::TYPE_MODERATION, 'Halo', 'Isi pesan');

        $this->assertSame(1, $service->unreadCount($user));

        $notification = $service->recent($user)->first();

        $this->assertFalse($notification->isRead());

        $service->markRead($user, $notification->getKey());

        $this->assertSame(0, $service->unreadCount($user));
        $this->assertTrue($notification->fresh()->isRead());
    }

    public function test_notifikasi_tidak_bocor_ke_pengguna_lain(): void
    {
        $owner = $this->wisatawan();
        $other = $this->wisatawan();
        $service = app(NotificationService::class);

        $notification = $service->notify($owner, AppNotification::TYPE_EVENT, 'Event');

        // Pengguna lain tidak boleh menandai/menghapus notifikasi milik orang lain.
        $this->assertFalse($service->markRead($other, $notification->getKey()));
        $this->assertFalse($service->delete($other, $notification->getKey()));
        $this->assertSame(1, $owner->appNotifications()->count());
    }

    public function test_pengajuan_konten_memberi_tahu_moderator(): void
    {
        $moderator = User::factory()->pengelolaLokanusa()->create();
        $pengelola = User::factory()->pemilikDestinasi()->create();

        $destination = Destination::factory()->create([
            'manager_id' => $pengelola->id,
        ]);

        $this->actingAs($pengelola);

        app(DestinationService::class)->submitForReview($destination);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $moderator->id,
            'type' => AppNotification::TYPE_MODERATION,
        ]);

        // Pelaku sendiri tidak diberi tahu.
        $this->assertSame(0, $pengelola->appNotifications()->count());
    }

    public function test_hasil_moderasi_memberi_tahu_pemilik_konten(): void
    {
        $moderator = User::factory()->pengelolaLokanusa()->create();
        $pengelola = User::factory()->pemilikDestinasi()->create();

        $destination = Destination::factory()->create([
            'manager_id' => $pengelola->id,
        ]);

        $service = app(DestinationService::class);

        $service->approve($destination, $moderator);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $pengelola->id,
            'type' => AppNotification::TYPE_MODERATION,
        ]);
    }

    public function test_penolakan_memuat_alasan(): void
    {
        $moderator = User::factory()->pengelolaLokanusa()->create();
        $pengelola = User::factory()->pemilikDestinasi()->create();

        $destination = Destination::factory()->create([
            'manager_id' => $pengelola->id,
        ]);

        app(DestinationService::class)->reject($destination, 'Foto kurang jelas');

        $notification = $pengelola->appNotifications()->latest('id')->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Foto kurang jelas', (string) $notification->body);
    }

    public function test_event_terbit_memberi_tahu_seluruh_pengguna(): void
    {
        $organizer = User::factory()->pengelolaLokanusa()->create();
        $wisatawan = $this->wisatawan();

        $event = Event::factory()->create(['organizer_id' => $organizer->id]);

        app(EventService::class)->approve($event, $organizer);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $wisatawan->id,
            'type' => AppNotification::TYPE_EVENT,
        ]);

        // Pengelola event sendiri tidak menerima notifikasi event.
        $this->assertDatabaseMissing('app_notifications', [
            'user_id' => $organizer->id,
            'type' => AppNotification::TYPE_EVENT,
        ]);
    }

    public function test_menandai_rekomendasi_memberi_tahu_pemilik(): void
    {
        $pengelola = User::factory()->pemilikDestinasi()->create();
        $focus = Region::factory()->create();
        AppSetting::setFocusRegion($focus->id);

        $destination = Destination::factory()->published()->create([
            'manager_id' => $pengelola->id,
            'region_id' => $focus->id,
            'is_recommended' => false,
        ]);

        $destination->update(['is_recommended' => true]);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $pengelola->id,
            'type' => AppNotification::TYPE_HIGHLIGHT,
        ]);
    }

    public function test_balasan_itinerary_ai_membuat_notifikasi(): void
    {
        config()->set('ai.base_url', 'https://ai.test/v1');
        config()->set('ai.key', 'secret');
        config()->set('ai.model', 'deepseek');

        $plan = json_encode([
            'title' => 'Bromo 2 Hari',
            'days' => [
                ['day' => 1, 'activities' => [
                    ['start' => '05:00', 'end' => '09:00', 'title' => 'Sunrise', 'ref' => null, 'cost' => 20000],
                ]],
            ],
        ], JSON_UNESCAPED_UNICODE);

        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => $plan]]],
                'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 5, 'total_tokens' => 10],
            ]),
        ]);

        $user = $this->wisatawan();

        $this->actingAs($user)
            ->postJson('/ai-trip-planner/kirim', ['message' => 'Susun itinerary 2 hari'])
            ->assertSuccessful();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $user->id,
            'type' => AppNotification::TYPE_AI,
        ]);
    }

    public function test_endpoint_lonceng_dan_halaman_notifikasi(): void
    {
        $user = $this->wisatawan();

        app(NotificationService::class)->notify($user, AppNotification::TYPE_EVENT, 'Judul Notif', 'Isi');

        $this->actingAs($user)
            ->getJson('/notifikasi/feed')
            ->assertSuccessful()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('items.0.title', 'Judul Notif');

        $this->actingAs($user)
            ->get('/notifikasi')
            ->assertSuccessful()
            ->assertSee('Judul Notif');

        $this->actingAs($user)
            ->postJson('/notifikasi/baca-semua')
            ->assertSuccessful();

        $this->assertSame(0, app(NotificationService::class)->unreadCount($user));
    }

    public function test_lonceng_menampilkan_badge_saat_belum_dibaca(): void
    {
        $user = $this->wisatawan();

        app(NotificationService::class)->notify($user, AppNotification::TYPE_EVENT, 'Penting');

        $this->actingAs($user)
            ->get('/destinasi')
            ->assertSuccessful()
            ->assertSee('data-notif-badge', false);
    }

    public function test_tamu_tidak_dapat_mengakses_notifikasi(): void
    {
        // Tamu diarahkan ke beranda (middleware public.auth).
        $this->get('/notifikasi')->assertRedirect(route('home'));
        $this->getJson('/notifikasi/feed')->assertRedirect(route('home'));
    }
}
