<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\MasterOpd;
use App\Models\DocumentTemplate;
use App\Models\TemplateSection;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Services\DocumentTemplateService;
use App\Services\OpdDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RenjaMasterTemplateStep1Test extends TestCase
{
    use RefreshDatabase;

    protected DocumentTemplateService $templateService;
    protected OpdDocumentService $opdDocumentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateService = app(DocumentTemplateService::class);
        $this->opdDocumentService = app(OpdDocumentService::class);
        $this->templateService->ensureStandardTemplatesSeeded();
    }

    private function createOpd(): MasterOpd
    {
        return MasterOpd::create([
            'nama_opd' => 'DINAS KOMUNIKASI DAN INFORMATIKA',
            'kode_opd' => '2.16.01',
            'nomor_lampiran_romawi' => 'LAMPIRAN I',
        ]);
    }

    /**
     * TEST 1: Pastikan keempat master template terdaftar secara eksplisit.
     */
    public function test_all_four_master_templates_exist(): void
    {
        $expectedTemplates = [
            'RENJA_MURNI' => 'Rencana Kerja (RENJA) Murni Perangkat Daerah',
            'RENJA_PERUBAHAN' => 'Rencana Kerja (RENJA) Perubahan Perangkat Daerah',
            'RENJA_LAMPIRAN_MURNI' => 'Lampiran Renja Murni (Format Perbup)',
            'RENJA_LAMPIRAN_PERUBAHAN' => 'Lampiran Renja Perubahan (Format Kepbup)',
        ];

        foreach ($expectedTemplates as $code => $name) {
            $template = DocumentTemplate::where('code', $code)->first();
            $this->assertNotNull($template, "Template with code {$code} must exist.");
            $this->assertTrue($template->is_active);
            $this->assertIsArray($template->format_config);
        }
    }

    /**
     * TEST 2: RENJA_MURNI dan RENJA_PERUBAHAN memiliki 26 seksi lengkap dengan hierarki valid.
     */
    public function test_renja_murni_and_perubahan_have_26_standard_sections(): void
    {
        foreach (['RENJA_MURNI', 'RENJA_PERUBAHAN'] as $templateCode) {
            $template = DocumentTemplate::where('code', $templateCode)->first();
            $this->assertNotNull($template);
            
            $sections = $template->sections()->orderBy('sequence')->get();
            $this->assertCount(26, $sections, "{$templateCode} must have exactly 26 template sections.");

            // Verify Front Sections (Cover, Pengesahan, Kata Pengantar, TOC, LOT, LOF)
            $frontTypes = ['cover', 'preface', 'table_of_contents', 'list_of_tables', 'list_of_figures', 'list_of_charts', 'list_of_appendices'];
            $frontSections = $sections->filter(fn($s) => in_array($s->section_type, $frontTypes));
            $this->assertCount(6, $frontSections, "{$templateCode} must have 6 front sections.");

            // Verify BAB I to BAB V
            $chapters = $sections->where('section_type', 'chapter');
            $this->assertCount(5, $chapters, "{$templateCode} must have 5 chapters (BAB I - V).");

            // Verify Sub-babs (1.1-1.4, 2.1-2.3, 3.1-3.3, 4.1-4.2, 5.1-5.2)
            $subBabs = $sections->where('section_type', 'subchapter');
            $this->assertCount(14, $subBabs, "{$templateCode} must have 14 sub-chapters.");

            // Verify Lampiran
            $appendices = $sections->where('section_type', 'appendix');
            $this->assertCount(1, $appendices, "{$templateCode} must have 1 appendix section.");
        }
    }

    /**
     * TEST 3: Idempotensi - ensureStandardTemplatesSeeded() tidak menduplikasi template atau section saat dijalankan ulang.
     */
    public function test_seeding_is_idempotent(): void
    {
        $murniSectionsCountBefore = DocumentTemplate::where('code', 'RENJA_MURNI')->first()->sections()->count();
        $perubahanSectionsCountBefore = DocumentTemplate::where('code', 'RENJA_PERUBAHAN')->first()->sections()->count();
        $totalTemplatesBefore = DocumentTemplate::count();

        // Run seed standard templates again
        $this->templateService->ensureStandardTemplatesSeeded();

        $this->assertEquals($totalTemplatesBefore, DocumentTemplate::count(), "Template count should not change on re-seeding.");
        $this->assertEquals($murniSectionsCountBefore, DocumentTemplate::where('code', 'RENJA_MURNI')->first()->sections()->count());
        $this->assertEquals($perubahanSectionsCountBefore, DocumentTemplate::where('code', 'RENJA_PERUBAHAN')->first()->sections()->count());
    }

    /**
     * TEST 4: provisionDocumentSections() mengkloning seksi master template ke dokumen RENJA_PERUBAHAN secara mandiri.
     */
    public function test_provision_document_sections_for_renja_perubahan(): void
    {
        $opd = $this->createOpd();
        $template = DocumentTemplate::where('code', 'RENJA_PERUBAHAN')->first();

        $document = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Perubahan',
            'status' => 'draft',
            'template_id' => $template->id,
        ]);

        $this->templateService->provisionDocumentSections($document, 'RENJA_PERUBAHAN');

        $docSections = $document->sections()->orderBy('order_index')->get();
        $this->assertCount(26, $docSections, "Provisioned document must have 26 sections.");
        
        // Ensure section titles and codes match master template
        $firstSection = $docSections->first();
        $this->assertEquals('cover', $firstSection->section_type);
        $this->assertEquals('Cover Dokumen RENJA Perubahan', $firstSection->bab_title);
    }

    /**
     * TEST 5: OpdDocumentService::createRenjaPerubahan menggunakan master template RENJA_PERUBAHAN dan pre-fill konten dari Murni.
     */
    public function test_create_renja_perubahan_uses_master_template_and_prefills_content(): void
    {
        $opd = $this->createOpd();

        // 1. Create and approve Renja Murni (TA 2027)
        $murni = $this->opdDocumentService->createRenjaMurni($opd->id, 2027);
        $this->assertNotNull($murni);

        // Fill some content in Murni
        $murniLatarBelakang = $murni->sections()->where('sub_bab_code', '1.1')->first();
        $this->assertNotNull($murniLatarBelakang);
        $murniLatarBelakang->update([
            'content' => '<p>Konten Latar Belakang Renja Murni Diskominfo 2027.</p>',
            'is_completed' => true,
        ]);

        // Approve Murni
        $murni->update(['status' => 'disetujui']);

        // 2. Create Renja Perubahan (TA 2026)
        $perubahan = $this->opdDocumentService->createRenjaPerubahan($opd->id, 2026);

        $this->assertNotNull($perubahan);
        $perubahanTemplate = DocumentTemplate::find($perubahan->template_id);
        $this->assertEquals('RENJA_PERUBAHAN', $perubahanTemplate->code);
        $this->assertCount(26, $perubahan->sections);

        // Verify pre-filled content from Murni
        $perubahanLatarBelakang = $perubahan->sections()->where('sub_bab_code', '1.1')->first();
        $this->assertNotNull($perubahanLatarBelakang);
        $this->assertEquals('<p>Konten Latar Belakang Renja Murni Diskominfo 2027.</p>', $perubahanLatarBelakang->content);
        $this->assertTrue((bool)$perubahanLatarBelakang->is_completed);
    }
}
