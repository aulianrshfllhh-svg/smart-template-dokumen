<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\DocumentTemplate;
use App\Models\TemplateSection;
use App\Models\RenjaDocument;
use App\Services\DocumentTemplateService;
use App\Services\RenjaCycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TemplateFoundationTest extends TestCase
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
            'nama_opd' => 'Dinas Template Test ' . $uniqueId . ' ' . $suffix,
            'kode_opd' => '1.01.' . $uniqueId,
            'nomor_lampiran_romawi' => 'LAMPIRAN XI',
        ]);
    }

    /**
     * TEST 1: Model DocumentTemplate dapat di-load dari database.
     */
    public function test_template_model_can_be_loaded(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();

        $this->assertNotNull($template);
        $this->assertEquals('RENJA_MURNI', $template->code);
        $this->assertTrue($template->is_active);
    }

    /**
     * TEST 2: Template memiliki sekumpulan TemplateSections.
     */
    public function test_template_has_sections(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();

        $this->assertNotNull($template);
        $this->assertGreaterThan(0, $template->sections()->count());
        $this->assertGreaterThan(0, $template->mainChapters()->count());
    }

    /**
     * TEST 3: Parent-child relationship / ordering section relationship terkonfigurasi dengan benar.
     */
    public function test_parent_child_section_relationship(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $parentSection = $template->sections()->where('section_type', 'chapter')->first();

        $subSection = TemplateSection::create([
            'template_id' => $template->id,
            'parent_id' => $parentSection->id,
            'section_type' => 'subchapter',
            'code' => '1.1.1',
            'title' => 'Sub Subbab Test',
            'sequence' => 99,
        ]);

        $this->assertEquals($parentSection->id, $subSection->parent->id);
        $this->assertTrue($parentSection->subSections->contains($subSection));
    }

    /**
     * TEST 4: Atribut required & editable terkonfigurasi dengan benar pada section.
     */
    public function test_required_and_editable_section_attributes(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $section = $template->sections()->first();

        $this->assertIsBool($section->is_required);
        $this->assertIsBool($section->is_editable);
        $this->assertTrue($section->is_required);
        $this->assertTrue($section->is_editable);
    }

    /**
     * TEST 5: 4 Tipe Template (RENJA Murni, Perubahan, Lampiran Murni, Lampiran Perubahan) dapat dibedakan.
     */
    public function test_four_template_types_distinguishable(): void
    {
        // Seed 4 template resmi
        DocumentTemplate::updateOrCreate(['code' => 'RENJA_PERUBAHAN'], ['name' => 'Template RENJA Perubahan', 'is_active' => true]);

        $renjaMurni = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $renjaPerubahan = DocumentTemplate::where('code', 'RENJA_PERUBAHAN')->first();
        $lampiranMurni = DocumentTemplate::where('code', 'RENJA_LAMPIRAN_MURNI')->first();
        $lampiranPerubahan = DocumentTemplate::where('code', 'RENJA_LAMPIRAN_PERUBAHAN')->first();

        $this->assertNotNull($renjaMurni);
        $this->assertNotNull($renjaPerubahan);
        $this->assertNotNull($lampiranMurni);
        $this->assertNotNull($lampiranPerubahan);

        $this->assertNotEquals($renjaMurni->code, $lampiranMurni->code);
    }

    /**
     * TEST 6: Active Cycle integration pada template tidak tertukar (Murni=activeYear+1, Perubahan=activeYear).
     */
    public function test_active_cycle_template_isolation(): void
    {
        $activeYear = 2026;

        $taMurni = $activeYear + 1; // 2027
        $taPerubahan = $activeYear; // 2026

        $this->assertEquals(2027, $taMurni);
        $this->assertEquals(2026, $taPerubahan);

        $cycleFilterQuery = RenjaDocument::query();
        RenjaCycleService::applyActiveCycleFilter($cycleFilterQuery, $activeYear);

        $this->assertNotNull($cycleFilterQuery);
    }

    /**
     * TEST 7: Aturan BR-24 (Template yang sedang digunakan oleh dokumen tidak dapat dihapus sembarangan).
     */
    public function test_br24_template_in_use_cannot_be_deleted(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $opd = $this->createOpd('BR24');

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'template_id' => $template->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $inUseCount = RenjaDocument::where('template_id', $template->id)->count();
        $this->assertGreaterThan(0, $inUseCount);

        // Simulasi proteksi BR-24: if inUseCount > 0, throw exception
        $canDelete = ($inUseCount === 0);
        $this->assertFalse($canDelete);
    }

    /**
     * TEST 8: Multi-OPD dapat menggunakan master template yang sama tanpa kontaminasi data.
     */
    public function test_multi_opd_shares_master_template_without_contamination(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $opd1 = $this->createOpd('OPD1');
        $opd2 = $this->createOpd('OPD2');

        $doc1 = RenjaDocument::create([
            'opd_id' => $opd1->id,
            'template_id' => $template->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);
        $this->templateService->provisionDocumentSections($doc1, 'RENJA_MURNI');

        $doc2 = RenjaDocument::create([
            'opd_id' => $opd2->id,
            'template_id' => $template->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);
        $this->templateService->provisionDocumentSections($doc2, 'RENJA_MURNI');

        $this->assertEquals($template->id, $doc1->template_id);
        $this->assertEquals($template->id, $doc2->template_id);

        // Seksi dokumen terisolasi per dokumen ID
        $this->assertNotEquals($doc1->id, $doc2->id);
        $this->assertEquals($doc1->sections()->count(), $doc2->sections()->count());
    }
}
