<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\CultureType;
use App\Enums\Permission;
use App\Filament\Resources\CultureArticles\CultureArticleResource;
use App\Models\AppSetting;
use App\Models\CultureArticle;
use App\Models\Region;
use App\Models\User;
use App\Policies\CultureArticlePolicy;
use App\Services\CultureArticleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CultureTest extends TestCase
{
    use RefreshDatabase;

    private function wisatawan(): User
    {
        return User::factory()->wisatawan()->create();
    }

    private function setupFocus(): Region
    {
        $kabupaten = Region::factory()->kabupatenKota()->create(['name' => 'Kabupaten Budaya']);
        AppSetting::setFocusRegion($kabupaten->id);

        return $kabupaten;
    }

    public function test_daftar_budaya_hanya_menampilkan_yang_terbit(): void
    {
        CultureArticle::factory()->published()->create(['title' => 'Tari Gandrung Terbit']);
        CultureArticle::factory()->create(['title' => 'Tradisi Draf']);

        $this->actingAs($this->wisatawan())->get('/budaya')
            ->assertSuccessful()
            ->assertSee('Tari Gandrung Terbit')
            ->assertDontSee('Tradisi Draf');
    }

    public function test_pencarian_dan_filter_jenis_budaya(): void
    {
        CultureArticle::factory()->published()->create([
            'title' => 'Upacara Kasada',
            'type' => CultureType::Tradisi,
        ]);
        CultureArticle::factory()->published()->create([
            'title' => 'Ludruk Surabaya',
            'type' => CultureType::Kesenian,
        ]);

        $this->actingAs($this->wisatawan())->get('/budaya?q=Kasada')
            ->assertSuccessful()
            ->assertSee('Upacara Kasada')
            ->assertDontSee('Ludruk Surabaya');

        $this->actingAs($this->wisatawan())->get('/budaya?jenis=kesenian')
            ->assertSuccessful()
            ->assertSee('Ludruk Surabaya')
            ->assertDontSee('Upacara Kasada');
    }

    public function test_detail_budaya_tampil(): void
    {
        $article = CultureArticle::factory()->published()->create([
            'title' => 'Legenda Roro Jonggrang',
            'excerpt' => 'Kisah legenda Jawa.',
        ]);

        $this->actingAs($this->wisatawan())->get('/budaya/'.$article->slug)
            ->assertSuccessful()
            ->assertSee('Legenda Roro Jonggrang')
            ->assertSee('Kisah legenda Jawa');
    }

    public function test_artikel_draf_tidak_dapat_diakses_publik(): void
    {
        $article = CultureArticle::factory()->create(['title' => 'Belum Tayang Budaya']);

        $this->actingAs($this->wisatawan())->get('/budaya/'.$article->slug)->assertNotFound();
    }

    public function test_kontributor_budaya_hanya_mengelola_artikelnya(): void
    {
        $kontributor = User::factory()->kontributorBudaya()->create();
        $lain = User::factory()->kontributorBudaya()->create();

        $milik = CultureArticle::factory()->create(['author_id' => $kontributor->id]);
        $bukanMilik = CultureArticle::factory()->create(['author_id' => $lain->id]);

        $policy = new CultureArticlePolicy;

        $this->assertTrue($policy->update($kontributor, $milik));
        $this->assertFalse($policy->update($kontributor, $bukanMilik));
        $this->assertFalse($policy->moderate($kontributor, $milik));

        $this->actingAs($kontributor);
        $this->assertTrue(CultureArticleResource::canViewAny());
        $this->assertSame(1, CultureArticleResource::getEloquentQuery()->count());

        $this->assertTrue($kontributor->hasPermission(Permission::ManageCulture));
        $this->assertTrue($kontributor->canAccessPanel(filament()->getDefaultPanel()));
    }

    public function test_admin_moderasi_semua_artikel_budaya(): void
    {
        $admin = User::factory()->adminPemerintah()->create();
        $artikel = CultureArticle::factory()->create();

        $policy = new CultureArticlePolicy;
        $this->assertTrue($policy->view($admin, $artikel));
        $this->assertTrue($policy->moderate($admin, $artikel));

        $service = app(CultureArticleService::class);
        $service->submitForReview($artikel);
        $this->assertSame(ContentStatus::Pending, $artikel->fresh()->status);

        $service->approve($artikel->fresh(), $admin);
        $this->assertSame(ContentStatus::Published, $artikel->fresh()->status);
        $this->assertNotNull($artikel->fresh()->published_at);
    }

    public function test_wisatawan_tidak_boleh_akses_budaya(): void
    {
        $this->actingAs(User::factory()->wisatawan()->create());

        $this->assertFalse(CultureArticleResource::canViewAny());
    }
}
