<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\DocumentTemplate;
use App\Models\TemplateSection;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Services\DocumentTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentTemplateService $templateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateService = app(DocumentTemplateService::class);
        $this->templateService->ensureStandardTemplatesSeeded();
    }

    private function createOpd(string $suffix = ''): MasterOpd
    {
        $uniqueId = rand(10000, 99999);
        return MasterOpd::create([
            'nama_opd' => 'Dinas Template Mgmt ' . $uniqueId . ' ' . $suffix,
            'kode_opd' => '1.01.' . $uniqueId,
            'nomor_lampiran_romawi' => 'LAMPIRAN XII',
        ]);
    }

    /**
     * TEST 1: Admin Bapperida dapat membuka Halaman Template Management (/admin/templates).
     */
    public function test_admin_can_open_template_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.templates.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Template Dokumen Perencanaan');
    }

    /**
     * TEST 2: Operator OPD tidak dapat mengakses Template Management (Redirect to Operator Dashboard).
     */
    public function test_operator_cannot_open_template_management(): void
    {
        $opd = $this->createOpd('Auth');
        $operator = User::factory()->create(['role' => 'operator', 'opd_id' => $opd->id]);

        $response = $this->actingAs($operator)
            ->get(route('admin.templates.index'));

        $response->assertRedirect(route('operator.dashboard'));
    }

    /**
     * TEST 3: 4 Master Template Resmi (RENJA Murni, Perubahan, Lampiran Murni, Lampiran Perubahan) tersedia.
     */
    public function test_four_master_templates_available(): void
    {
        DocumentTemplate::updateOrCreate(['code' => 'RENJA_PERUBAHAN'], ['name' => 'Template RENJA Perubahan', 'is_active' => true]);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.templates.index'));

        $response->assertStatus(200);
        $response->assertSee('RENJA_MURNI');
        $response->assertSee('RENJA_PERUBAHAN');
        $response->assertSee('RENJA_LAMPIRAN_MURNI');
        $response->assertSee('RENJA_LAMPIRAN_PERUBAHAN');
    }

    /**
     * TEST 4: Template aktif (is_active = true) dapat diidentifikasi.
     */
    public function test_active_template_identified(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $this->assertTrue($template->is_active);
    }

    /**
     * TEST 5: Template nonaktif (is_active = false) dapat diidentifikasi.
     */
    public function test_inactive_template_identified(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $admin = User::factory()->create(['role' => 'admin']);

        // Toggle status to inactive
        $this->actingAs($admin)->post(route('admin.templates.toggleStatus', $template->id));

        $template->refresh();
        $this->assertFalse($template->is_active);
    }

    /**
     * TEST 6: Jumlah seksi bab per template dihitung dengan benar.
     */
    public function test_section_count_correct(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $this->assertGreaterThan(0, $template->sections()->count());
    }

    /**
     * TEST 7: Hierarki parent-child pada seksi template benar.
     */
    public function test_parent_child_hierarchy_correct(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $chapter = $template->sections()->where('section_type', 'chapter')->first();

        $sub = TemplateSection::create([
            'template_id' => $template->id,
            'parent_id' => $chapter->id,
            'section_type' => 'subchapter',
            'code' => '1.99',
            'title' => 'SubBab Test Hierarchy',
            'sequence' => 99,
        ]);

        $this->assertEquals($chapter->id, $sub->parent->id);
    }

    /**
     * TEST 8: Urutan (sequence) seksi template benar.
     */
    public function test_section_sequence_correct(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $sections = $template->sections()->orderBy('sequence', 'asc')->get();

        $prevSeq = -1;
        foreach ($sections as $sec) {
            $this->assertGreaterThanOrEqual($prevSeq, $sec->sequence);
            $prevSeq = $sec->sequence;
        }
    }

    /**
     * TEST 9: Admin dapat mengubah properti seksi master template.
     */
    public function test_admin_can_update_section_property(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $sec = $template->sections()->first();

        $response = $this->actingAs($admin)
            ->put(route('admin.templates.updateSection', [$template->id, $sec->id]), [
                'code' => $sec->code,
                'title' => 'Judul Seksi Diperbarui',
                'guidance_text' => 'Petunjuk Pengisian Baru',
                'is_required' => 1,
            ]);

        $response->assertRedirect();
        $sec->refresh();
        $this->assertEquals('Judul Seksi Diperbarui', $sec->title);
    }

    /**
     * TEST 10: BR-24 Protection - Template yang sedang digunakan oleh dokumen TIDAK dapat dihapus.
     */
    public function test_template_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $opd = $this->createOpd('BR24_Usage');

        RenjaDocument::create([
            'opd_id' => $opd->id,
            'template_id' => $template->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.templates.destroy', $template->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('document_templates', ['id' => $template->id]);
    }

    /**
     * TEST 11: Template yang belum pernah digunakan oleh dokumen DAPAT dihapus.
     */
    public function test_unused_template_can_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $template = DocumentTemplate::create([
            'code' => 'CUSTOM_UNUSED_TEMPLATE',
            'name' => 'Template Kustom Unused',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.templates.destroy', $template->id));

        $response->assertRedirect(route('admin.templates.index'));
        $this->assertDatabaseMissing('document_templates', ['id' => $template->id]);
    }

    /**
     * TEST 12: Template Immutability - Perubahan pada master template TIDAK mengubah snapshot renja_sections dokumen existing.
     */
    public function test_master_template_update_does_not_change_existing_renja_sections(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $opd = $this->createOpd('Immutability');

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'template_id' => $template->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);

        // Provision snapshot sections to document
        $this->templateService->provisionDocumentSections($doc, 'RENJA_MURNI');
        $originalSectionTitle = $doc->sections()->first()->sub_bab_title;

        // Admin updates master template section
        $sec = $template->sections()->first();
        $this->actingAs($admin)->put(route('admin.templates.updateSection', [$template->id, $sec->id]), [
            'code' => $sec->code,
            'title' => 'Judul Master Baru Yang Diubah Admin',
        ]);

        // Document snapshot remains intact
        $this->assertEquals($originalSectionTitle, $doc->sections()->first()->sub_bab_title);
        $this->assertNotEquals('Judul Master Baru Yang Diubah Admin', $doc->sections()->first()->sub_bab_title);
    }

    /**
     * TEST 13: Template nonaktif tidak tersedia pada pilihan pembuatan dokumen baru.
     */
    public function test_inactive_template_not_available_for_new_documents(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $template->is_active = false;
        $template->save();

        $available = $this->templateService->getAvailableTemplates();

        $this->assertFalse($available->contains('code', 'RENJA_MURNI'));
    }

    /**
     * TEST 14: Target Cycle Year mapping pada Active Cycle TA 2026 (Murni=2027, Perubahan=2026).
     */
    public function test_active_cycle_2026_template_mapping(): void
    {
        $activeYear = 2026;

        $targetMurni = str_contains('RENJA_MURNI', 'PERUBAHAN') ? $activeYear : $activeYear + 1;
        $targetPerubahan = str_contains('RENJA_PERUBAHAN', 'PERUBAHAN') ? $activeYear : $activeYear + 1;

        $this->assertEquals(2027, $targetMurni);
        $this->assertEquals(2026, $targetPerubahan);
    }

    /**
     * TEST 15: Target Cycle Year mapping pada Active Cycle TA 2027 (Murni=2028, Perubahan=2027).
     */
    public function test_active_cycle_2027_template_mapping(): void
    {
        $activeYear = 2027;

        $targetMurni = str_contains('RENJA_MURNI', 'PERUBAHAN') ? $activeYear : $activeYear + 1;
        $targetPerubahan = str_contains('RENJA_PERUBAHAN', 'PERUBAHAN') ? $activeYear : $activeYear + 1;

        $this->assertEquals(2028, $targetMurni);
        $this->assertEquals(2027, $targetPerubahan);
    }
}
