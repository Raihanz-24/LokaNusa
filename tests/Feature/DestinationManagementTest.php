<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Filament\Resources\Destinations\DestinationResource;
use App\Filament\Resources\Destinations\Pages\CreateDestination;
use App\Filament\Resources\Destinations\Pages\ListDestinations;
use App\Models\AppSetting;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Region;
use App\Models\User;
use App\Policies\DestinationPolicy;
use App\Services\DestinationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class DestinationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_dibuat_otomatis_dan_relasi_kategori_wilayah(): void
    {
        $category = DestinationCategory::factory()->create();
        $region = Region::factory()->create();

        $destination = Destination::factory()->create([
            'name' => 'Pantai Balekambang',
            'slug' => null,
            'destination_category_id' => $category->id,
            'region_id' => $region->id,
        ]);

        $this->assertSame('pantai-balekambang', $destination->slug);
        $this->assertTrue($destination->category->is($category));
        $this->assertTrue($destination->region->is($region));
    }

    public function test_cast_json_menyimpan_jam_operasional_fasilitas_galeri(): void
    {
        $destination = Destination::factory()->create([
            'opening_hours' => [['day' => 'senin', 'open' => '08:00', 'close' => '17:00', 'closed' => false]],
            'facilities' => ['Parkir', 'Toilet'],
            'gallery' => ['destinations/a.jpg'],
        ]);

        $fresh = $destination->fresh();

        $this->assertSame('senin', $fresh->opening_hours[0]['day']);
        $this->assertSame(['Parkir', 'Toilet'], $fresh->facilities);
        $this->assertSame(['destinations/a.jpg'], $fresh->gallery);
        $this->assertSame(ContentStatus::Draft, $fresh->status);
    }

    public function test_scope_published_hanya_menampilkan_yang_terbit(): void
    {
        Destination::factory()->create();
        Destination::factory()->published()->create();

        $this->assertSame(1, Destination::published()->count());
    }

    public function test_alur_moderasi_lengkap_dengan_service(): void
    {
        $service = app(DestinationService::class);
        $destination = Destination::factory()->create();

        $service->submitForReview($destination);
        $this->assertSame(ContentStatus::Pending, $destination->fresh()->status);

        $service->approve($destination->fresh());
        $destination->refresh();
        $this->assertSame(ContentStatus::Published, $destination->status);
        $this->assertNotNull($destination->published_at);

        $service->archive($destination);
        $this->assertSame(ContentStatus::Archived, $destination->fresh()->status);
    }

    public function test_moderasi_menolak_dengan_alasan(): void
    {
        $destination = Destination::factory()->pending()->create();

        app(DestinationService::class)->reject($destination, 'Foto kurang jelas');

        $destination->refresh();
        $this->assertSame(ContentStatus::Rejected, $destination->status);
        $this->assertSame('Foto kurang jelas', $destination->rejection_reason);
    }

    public function test_transisi_tidak_valid_ditolak(): void
    {
        $destination = Destination::factory()->published()->create();

        $this->expectException(InvalidArgumentException::class);
        app(DestinationService::class)->submitForReview($destination);
    }

    public function test_penolakan_wajib_punya_alasan(): void
    {
        $destination = Destination::factory()->pending()->create();

        $this->expectException(InvalidArgumentException::class);
        app(DestinationService::class)->reject($destination, '   ');
    }

    public function test_pengelola_hanya_boleh_mengubah_destinasi_miliknya(): void
    {
        $pengelola = User::factory()->pengelolaDestinasi()->create();
        $lain = User::factory()->pengelolaDestinasi()->create();

        $milik = Destination::factory()->create(['manager_id' => $pengelola->id]);
        $bukanMilik = Destination::factory()->create(['manager_id' => $lain->id]);

        $policy = new DestinationPolicy;

        $this->assertTrue($policy->update($pengelola, $milik));
        $this->assertFalse($policy->update($pengelola, $bukanMilik));
        $this->assertFalse($policy->delete($pengelola, $milik));
    }

    public function test_admin_boleh_mengubah_semua_destinasi(): void
    {
        $admin = User::factory()->adminPemerintah()->create();
        $destination = Destination::factory()->create();

        $policy = new DestinationPolicy;

        $this->assertTrue($policy->update($admin, $destination));
        $this->assertTrue($policy->delete($admin, $destination));
        $this->assertTrue($policy->moderate($admin, $destination));
    }

    public function test_pengelola_tidak_boleh_moderasi(): void
    {
        $pengelola = User::factory()->pengelolaDestinasi()->create();
        $destination = Destination::factory()->create();

        $this->assertFalse((new DestinationPolicy)->moderate($pengelola, $destination));
    }

    public function test_wisatawan_tidak_boleh_akses_destinasi(): void
    {
        $this->actingAs(User::factory()->wisatawan()->create());

        $this->assertFalse(DestinationResource::canViewAny());
        $this->get('/admin/destinations')->assertForbidden();
    }

    public function test_pengelola_destinasi_membuat_destinasi_baru(): void
    {
        $pengelola = User::factory()->pengelolaDestinasi()->create();
        $category = DestinationCategory::factory()->create();

        $kabupaten = Region::factory()->kabupatenKota()->create();
        $kecamatan = Region::factory()->kecamatan($kabupaten)->create();
        AppSetting::setFocusRegion($kabupaten->id);

        $this->actingAs($pengelola);

        Livewire::test(CreateDestination::class)
            ->fillForm([
                'name' => 'Wisata Goa Lawa',
                'destination_category_id' => $category->id,
                'region_id' => $kecamatan->id,
                'ticket_price' => 10000,
                'facilities' => ['Parkir'],
                'latitude' => -7.98,
                'longitude' => 112.63,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('destinations', [
            'name' => 'Wisata Goa Lawa',
            'slug' => 'wisata-goa-lawa',
            'manager_id' => $pengelola->id,
            'status' => ContentStatus::Draft->value,
        ]);
    }

    public function test_daftar_destinasi_dibatasi_untuk_pengelola(): void
    {
        $pengelola = User::factory()->pengelolaDestinasi()->create();
        Destination::factory()->count(3)->create(['manager_id' => $pengelola->id]);
        Destination::factory()->count(2)->create();

        $this->actingAs($pengelola);

        Livewire::test(ListDestinations::class)
            ->assertCanSeeTableRecords(Destination::where('manager_id', $pengelola->id)->get())
            ->assertCountTableRecords(3);
    }

    public function test_admin_wilayah_dapat_mengelola_dan_moderasi(): void
    {
        $admin = User::factory()->adminWilayah()->create();

        $this->assertTrue($admin->managesAllContent());

        $this->actingAs($admin);
        $this->assertTrue(DestinationResource::canViewAny());
        $this->get('/admin/destinations')->assertSuccessful();
    }
}
