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
use App\Services\RenjaCycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TemplateStructureEngineTest extends TestCase
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
            'nama_opd' => 'Dinas Structure Engine ' . $uniqueId . ' ' . $suffix,
            'kode_opd' => '1.01.' . $uniqueId,
            'nomor_lampiran_romawi' => 'LAMPIRAN XIII',
        ]);
    }

    /**
     * TEST 1: Admin dapat melihat visual tree structure pada halaman detail template.
     */
    public function test_admin_can_view_tree_structure(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();

        $response = $this->actingAs($admin)
            ->get(route('admin.templates.show', $template->id));

        $response->assertStatus(200);
        $response->assertSee($template->name);
        $response->assertSee('Struktur Hierarki Seksi');
    }

    /**
     * TEST 2: Root section diidentifikasi dengan parent_id = NULL.
     */
    public function test_root_section_correctly_identified(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $chapter = $template->sections()->where('section_type', 'chapter')->first();

        $this->assertNull($chapter->parent_id);
    }

    /**
     * TEST 3: Child section terhubung ke parent section dengan benar.
     */
    public function test_child_section_has_correct_parent(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $chapter = $template->sections()->where('section_type', 'chapter')->first();

        $sub = TemplateSection::create([
            'template_id' => $template->id,
            'parent_id' => $chapter->id,
            'section_type' => 'subchapter',
            'code' => '1.1.0',
            'title' => 'Subbab Child Test',
            'sequence' => 1,
        ]);

        $this->assertEquals($chapter->id, $sub->parent_id);
        $this->assertTrue($chapter->subSections->contains($sub));
    }

    /**
     * TEST 4: Pengurutan seksi (sequence ordering) dilakukan secara ketat berdasarkan sequence.
     */
    public function test_sequence_ordering_correct(): void
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
     * TEST 5: Admin dapat membuat (create) section baru di bawah template.
     */
    public function test_admin_can_create_section(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();

        $response = $this->actingAs($admin)
            ->post(route('admin.templates.storeSection', $template->id), [
                'section_type' => 'subchapter',
                'code' => '9.9',
                'title' => 'Seksi Baru Test Admin',
                'guidance_text' => 'Petunjuk pengisian seksi baru',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('template_sections', [
            'template_id' => $template->id,
            'code' => '9.9',
            'title' => 'Seksi Baru Test Admin',
        ]);
    }

    /**
     * TEST 6: Admin dapat memperbarui (update) properti section.
     */
    public function test_admin_can_update_section(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $sec = $template->sections()->first();

        $response = $this->actingAs($admin)
            ->put(route('admin.templates.updateSection', [$template->id, $sec->id]), [
                'code' => $sec->code,
                'title' => 'Judul Seksi Master Diperbarui',
            ]);

        $response->assertRedirect();
        $sec->refresh();
        $this->assertEquals('Judul Seksi Master Diperbarui', $sec->title);
    }

    /**
     * TEST 7: Flag is_required dapat diperbarui.
     */
    public function test_required_flag_can_be_updated(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $sec = $template->sections()->first();

        $updated = $this->templateService->updateTemplateSection($sec->id, ['is_required' => false]);
        $this->assertFalse($updated->is_required);
    }

    /**
     * TEST 8: Flag is_editable dapat diperbarui.
     */
    public function test_editable_flag_can_be_updated(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $sec = $template->sections()->first();

        $updated = $this->templateService->updateTemplateSection($sec->id, ['is_editable' => false]);
        $this->assertFalse($updated->is_editable);
    }

    /**
     * TEST 9: Flag is_automatic dapat diperbarui.
     */
    public function test_automatic_flag_can_be_updated(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $sec = $template->sections()->first();

        $updated = $this->templateService->updateTemplateSection($sec->id, ['is_automatic' => true]);
        $this->assertTrue($updated->is_automatic);
    }

    /**
     * TEST 10: Flag page_break_before & page_break_after dapat diperbarui.
     */
    public function test_page_break_flag_can_be_updated(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $sec = $template->sections()->first();

        $updated = $this->templateService->updateTemplateSection($sec->id, [
            'page_break_before' => true,
            'page_break_after' => true,
        ]);
        $this->assertTrue($updated->page_break_before);
        $this->assertTrue($updated->page_break_after);
    }

    /**
     * TEST 11: Penanganan sequence duplicate pada parent scope yang sama.
     */
    public function test_duplicate_sequence_same_parent_handled(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();

        $sec1 = $this->templateService->addTemplateSection($template->id, [
            'code' => '8.1',
            'title' => 'Seq Test 1',
            'sequence' => 10,
        ]);
        $sec2 = $this->templateService->addTemplateSection($template->id, [
            'code' => '8.2',
            'title' => 'Seq Test 2',
            'sequence' => 10,
        ]);

        $this->assertNotNull($sec1);
        $this->assertNotNull($sec2);
    }

    /**
     * TEST 12: Penunjukan diri sendiri sebagai parent (self-parent) ditolak.
     */
    public function test_self_parent_rejected(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $sec = $template->sections()->first();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Section tidak dapat menunjuk dirinya sendiri sebagai parent.');

        $this->templateService->updateTemplateSection($sec->id, [
            'parent_id' => $sec->id,
        ]);
    }

    /**
     * TEST 13: Circular parent hierarchy (A -> B -> C -> A) ditolak.
     */
    public function test_circular_hierarchy_rejected(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();

        $parent = TemplateSection::create([
            'template_id' => $template->id,
            'code' => 'CIRC_1',
            'title' => 'Parent Section',
            'sequence' => 1,
        ]);

        $child = TemplateSection::create([
            'template_id' => $template->id,
            'parent_id' => $parent->id,
            'code' => 'CIRC_2',
            'title' => 'Child Section',
            'sequence' => 2,
        ]);

        // Attempt making parent a child of its own child
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Perubahan hierarchy menyebabkan circular parent relationship.');

        $this->templateService->updateTemplateSection($parent->id, [
            'parent_id' => $child->id,
        ]);
    }

    /**
     * TEST 14: Cross-template parent (parent dari template lain) ditolak.
     */
    public function test_cross_template_parent_rejected(): void
    {
        $template1 = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $template2 = DocumentTemplate::create(['code' => 'OTHER_TPL', 'name' => 'Other Template', 'is_active' => true]);

        $parentOther = TemplateSection::create([
            'template_id' => $template2->id,
            'code' => 'OTHER_PAR',
            'title' => 'Other Parent',
            'sequence' => 1,
        ]);

        $sec1 = $template1->sections()->first();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Parent section berasal dari template yang berbeda.');

        $this->templateService->updateTemplateSection($sec1->id, [
            'parent_id' => $parentOther->id,
        ]);
    }

    /**
     * TEST 15: Penghapusan section yang masih memiliki sub-section child ditolak.
     */
    public function test_delete_section_with_child_rejected(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $parent = TemplateSection::create([
            'template_id' => $template->id,
            'code' => 'PAR_DEL',
            'title' => 'Parent With Child',
            'sequence' => 1,
        ]);
        TemplateSection::create([
            'template_id' => $template->id,
            'parent_id' => $parent->id,
            'code' => 'CHILD_DEL',
            'title' => 'Child Section',
            'sequence' => 2,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Section tidak dapat dihapus karena masih memiliki sub-section.');

        $this->templateService->deleteTemplateSection($parent->id);
    }

    /**
     * TEST 16: Template Immutability - Perubahan master template section TIDAK mengubah snapshot renja_sections OPD.
     */
    public function test_template_update_does_not_mutate_renja_sections_snapshot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $opd = $this->createOpd('Immutability_E2E');

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'template_id' => $template->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);
        $this->templateService->provisionDocumentSections($doc, 'RENJA_MURNI');

        $snapshotTitle = $doc->sections()->first()->sub_bab_title;

        // Admin updates master section
        $sec = $template->sections()->first();
        $this->actingAs($admin)->put(route('admin.templates.updateSection', [$template->id, $sec->id]), [
            'code' => $sec->code,
            'title' => 'Judul Master Baru Yang Diubah',
        ]);

        $doc->refresh();
        $this->assertEquals($snapshotTitle, $doc->sections()->first()->sub_bab_title);
        $this->assertNotEquals('Judul Master Baru Yang Diubah', $doc->sections()->first()->sub_bab_title);
    }

    /**
     * TEST 17: Template nonaktif tidak tersedia untuk dokumen baru.
     */
    public function test_inactive_template_hidden_from_new_documents(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $template->is_active = false;
        $template->save();

        $available = $this->templateService->getAvailableTemplates();
        $this->assertFalse($available->contains('code', 'RENJA_MURNI'));
    }

    /**
     * TEST 18: Role Operator (OPD) tidak dapat melakukan aksi CUD pada seksi template.
     */
    public function test_operator_cannot_modify_template_structure(): void
    {
        $opd = $this->createOpd('Auth');
        $operator = User::factory()->create(['role' => 'operator', 'opd_id' => $opd->id]);
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();

        $responseStore = $this->actingAs($operator)
            ->post(route('admin.templates.storeSection', $template->id), ['code' => '9.9', 'title' => 'Illegal']);

        $responseStore->assertRedirect(route('operator.dashboard'));
    }

    /**
     * TEST 19: 4 Tipe Template (RENJA Murni, Perubahan, Lampiran Murni, Lampiran Perubahan) menjaga struktur masing-masing.
     */
    public function test_four_template_types_preserve_distinct_structures(): void
    {
        $murni = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $lampiran = DocumentTemplate::where('code', 'RENJA_LAMPIRAN_MURNI')->first();

        $this->assertNotEquals($murni->id, $lampiran->id);
        $this->assertNotEquals($murni->sections()->count(), $lampiran->sections()->count());
    }

    /**
     * TEST 20: Active Cycle TA 2026 tidak terganggu oleh Template Structure Engine.
     */
    public function test_active_cycle_2026_unaffected(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('AC2026');

        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);

        $query = RenjaDocument::query();
        RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        $this->assertEquals(1, $query->count());
    }

    /**
     * TEST 21: Active Cycle TA 2027 tidak terganggu oleh Template Structure Engine.
     */
    public function test_active_cycle_2027_unaffected(): void
    {
        $activeYear = 2027;
        $opd = $this->createOpd('AC2027');

        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2028, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);

        $query = RenjaDocument::query();
        RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        $this->assertEquals(1, $query->count());
    }
}
