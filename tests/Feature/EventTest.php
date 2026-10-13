<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\EventType;
use App\Enums\Permission;
use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use App\Models\User;
use App\Policies\EventPolicy;
use App\Services\EventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    private function wisatawan(): User
    {
        return User::factory()->wisatawan()->create();
    }

    public function test_daftar_event_hanya_menampilkan_yang_terbit_dan_akan_datang(): void
    {
        Event::factory()->published()->upcoming()->create(['title' => 'Festival Terbit']);
        Event::factory()->create(['title' => 'Agenda Draf']);
        Event::factory()->published()->past()->create(['title' => 'Agenda Lampau']);

        $this->actingAs($this->wisatawan())->get('/event')
            ->assertSuccessful()
            ->assertSee('Festival Terbit')
            ->assertDontSee('Agenda Draf')
            ->assertDontSee('Agenda Lampau');
    }

    public function test_filter_jenis_dan_pencarian_event(): void
    {
        Event::factory()->published()->upcoming()->create([
            'title' => 'Karnaval Budaya',
            'event_type' => EventType::Budaya,
        ]);
        Event::factory()->published()->upcoming()->create([
            'title' => 'Lomba Lari Bromo',
            'event_type' => EventType::Olahraga,
        ]);

        $this->actingAs($this->wisatawan())->get('/event?q=Karnaval')
            ->assertSuccessful()
            ->assertSee('Karnaval Budaya')
            ->assertDontSee('Lomba Lari Bromo');

        $this->actingAs($this->wisatawan())->get('/event?jenis=olahraga')
            ->assertSuccessful()
            ->assertSee('Lomba Lari Bromo')
            ->assertDontSee('Karnaval Budaya');
    }

    public function test_filter_bulan_dan_arsip(): void
    {
        Event::factory()->published()->create([
            'title' => 'Agenda Bulan Depan',
            'starts_at' => now()->addMonth()->startOfMonth()->addDays(3),
            'ends_at' => now()->addMonth()->startOfMonth()->addDays(5),
        ]);
        Event::factory()->published()->past()->create(['title' => 'Agenda Lampau']);

        $bulan = now()->addMonth()->format('Y-m');

        $this->actingAs($this->wisatawan())->get("/event?masa=semua&bulan={$bulan}")
            ->assertSuccessful()
            ->assertSee('Agenda Bulan Depan')
            ->assertDontSee('Agenda Lampau');

        $this->actingAs($this->wisatawan())->get('/event?masa=arsip')
            ->assertSuccessful()
            ->assertSee('Agenda Lampau')
            ->assertDontSee('Agenda Bulan Depan');
    }

    public function test_detail_event_tampil(): void
    {
        $event = Event::factory()->published()->upcoming()->create([
            'title' => 'Festival Bromo',
            'description' => 'Pesta rakyat di kaki Bromo.',
            'location_name' => 'Alun-Alun Probolinggo',
        ]);

        $this->actingAs($this->wisatawan())->get('/event/'.$event->slug)
            ->assertSuccessful()
            ->assertSee('Festival Bromo')
            ->assertSee('Pesta rakyat di kaki Bromo.')
            ->assertSee('Alun-Alun Probolinggo');
    }

    public function test_event_draf_tidak_dapat_diakses_publik(): void
    {
        $event = Event::factory()->create(['title' => 'Belum Tayang Event']);

        $this->actingAs($this->wisatawan())->get('/event/'.$event->slug)->assertNotFound();
    }

    public function test_halaman_event_wajib_login(): void
    {
        $this->get('/event')->assertRedirect(route('home'));

        Event::factory()->published()->upcoming()->create(['title' => 'Festival Publik']);

        $this->get('/event')->assertRedirect(route('home'));
    }

    public function test_pengelola_event_hanya_mengelola_eventnya(): void
    {
        // Event kini tidak punya peran pemilik khusus; hanya admin/pengelola
        // lokanusa yang mengelolanya.
        $pemilik = User::factory()->pemilikDestinasi()->create();

        $this->assertFalse($pemilik->hasPermission(Permission::ManageEvents));
        $this->assertFalse(EventResource::canViewAny());

        $this->actingAs($pemilik);
        $this->assertFalse(EventResource::canViewAny());
    }

    public function test_pengelola_lokanusa_dapat_mengelola_semua_event(): void
    {
        $pengelola = User::factory()->pengelolaLokanusa()->create();
        $lain = User::factory()->pengelolaLokanusa()->create();

        $milik = Event::factory()->create(['organizer_id' => $pengelola->id]);
        $bukanMilik = Event::factory()->create(['organizer_id' => $lain->id]);

        $policy = new EventPolicy;

        $this->assertTrue($policy->update($pengelola, $milik));
        $this->assertTrue($policy->update($pengelola, $bukanMilik));
        $this->assertTrue($policy->moderate($pengelola, $milik));

        $this->actingAs($pengelola);
        $this->assertTrue(EventResource::canViewAny());
        $this->assertSame(2, EventResource::getEloquentQuery()->count());

        $this->assertTrue($pengelola->hasPermission(Permission::ManageEvents));
        $this->assertTrue($pengelola->canAccessPanel(filament()->getDefaultPanel()));
    }

    public function test_admin_dapat_moderasi_event(): void
    {
        $admin = User::factory()->adminPemerintah()->create();
        $event = Event::factory()->create();

        $policy = new EventPolicy;
        $this->assertTrue($policy->view($admin, $event));
        $this->assertTrue($policy->moderate($admin, $event));

        $service = app(EventService::class);
        $service->submitForReview($event);
        $this->assertSame(ContentStatus::Pending, $event->fresh()->status);

        $service->approve($event->fresh(), $admin);
        $this->assertSame(ContentStatus::Published, $event->fresh()->status);
        $this->assertNotNull($event->fresh()->published_at);
    }

    public function test_wisatawan_tidak_boleh_akses_manajemen_event(): void
    {
        $this->actingAs(User::factory()->wisatawan()->create());

        $this->assertFalse(EventResource::canViewAny());
    }
}
