<?php

namespace Tests\Feature;

use App\Filament\Resources\AiKnowledge\Pages\CreateAiKnowledgeEntry;
use App\Models\AiUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAiUsageTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_super_admin_dapat_membuka_laporan_token_ai(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/ai-usage-report')
            ->assertSuccessful();
    }

    public function test_super_admin_dapat_membuka_pengaturan(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/settings')
            ->assertSuccessful();
    }

    public function test_laporan_menampilkan_pemakaian_token_pengguna(): void
    {
        $admin = $this->superAdmin();
        $pengguna = User::factory()->wisatawan()->create(['name' => 'Budi Traveler']);

        AiUsage::create([
            'user_id' => $pengguna->id,
            'context' => 'trip_planner',
            'model' => 'deepseek-flash-4',
            'prompt_tokens' => 30,
            'completion_tokens' => 20,
            'total_tokens' => 50,
        ]);

        $this->actingAs($admin)
            ->get('/admin/ai-usage-report')
            ->assertSuccessful()
            ->assertSee('Budi Traveler');
    }

    public function test_super_admin_dapat_membuka_basis_pengetahuan_ai(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/ai-knowledge')
            ->assertSuccessful();

        $this->actingAs($this->superAdmin())
            ->get('/admin/ai-knowledge/create')
            ->assertSuccessful();
    }

    public function test_wisatawan_tidak_dapat_membuka_laporan_token_ai(): void
    {
        $wisatawan = User::factory()->wisatawan()->create();

        $this->actingAs($wisatawan)
            ->get('/admin/ai-usage-report')
            ->assertForbidden();
    }

    public function test_admin_pemerintah_tidak_dapat_membuka_basis_pengetahuan_ai(): void
    {
        $admin = User::factory()->adminPemerintah()->create();

        $this->actingAs($admin)
            ->get('/admin/ai-knowledge')
            ->assertForbidden();

        $this->actingAs($admin)
            ->get('/admin/ai-knowledge/create')
            ->assertForbidden();
    }

    public function test_admin_pemerintah_tidak_dapat_membuka_laporan_token_ai(): void
    {
        $admin = User::factory()->adminPemerintah()->create();

        $this->actingAs($admin)
            ->get('/admin/ai-usage-report')
            ->assertForbidden();
    }

    public function test_developer_dapat_membuat_entri_pengetahuan(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(CreateAiKnowledgeEntry::class)
            ->fillForm([
                'title' => 'Jam operasional',
                'keywords' => 'jam buka, jam operasional',
                'answer' => 'Buka setiap hari 08.00-17.00 WIB.',
                'is_active' => true,
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('ai_knowledge_entries', [
            'title' => 'Jam operasional',
        ]);
    }
}
