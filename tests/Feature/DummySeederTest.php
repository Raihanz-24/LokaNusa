<?php

namespace Tests\Feature;

use App\Models\Accommodation;
use App\Models\AppSetting;
use App\Models\Destination;
use App\Models\Event;
use App\Models\Region;
use App\Models\UmkmBusiness;
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DummySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_dummy_seeder_mandiri_menjalankan_region_dan_menetapkan_fokus(): void
    {
        // Belum ada wilayah & fokus.
        $this->assertSame(0, Region::query()->count());
        $this->assertNull(AppSetting::get('focus_region_id'));

        $this->seed(DummySeeder::class);

        // Fokus otomatis ditetapkan (default Probolinggo 35.13).
        $this->assertNotNull(AppSetting::get('focus_region_id'));

        // Data dummy publik terisi.
        $this->assertSame(10, Destination::query()->count());
        $this->assertSame(10, UmkmBusiness::query()->count());
        $this->assertSame(10, Accommodation::query()->count());
        $this->assertSame(10, Event::query()->count());
    }

    public function test_dummy_seeder_idempoten(): void
    {
        $this->seed(DummySeeder::class);
        $destinations = Destination::query()->count();

        $this->seed(DummySeeder::class);

        $this->assertSame($destinations, Destination::query()->count());
    }
}
