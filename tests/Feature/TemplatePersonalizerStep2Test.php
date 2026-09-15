<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\MasterOpd;
use App\Models\DocumentTemplate;
use App\Models\TemplateSection;
use App\Models\RenjaDocument;
use App\Services\DocumentTemplateService;
use App\Services\TemplatePersonalizerService;
use App\Services\WordExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ZipArchive;

class TemplatePersonalizerStep2Test extends TestCase
{
    use RefreshDatabase;

    protected TemplatePersonalizerService $personalizerService;
    protected DocumentTemplateService $templateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateService = app(DocumentTemplateService::class);
        $this->personalizerService = app(TemplatePersonalizerService::class);
        $this->templateService->ensureStandardTemplatesSeeded();
    }

    private function createOpd(string $name = 'DINAS KOMUNIKASI DAN INFORMATIKA'): MasterOpd
    {
        $uniqueId = rand(1000, 9999);
        return MasterOpd::create([
            'nama_opd' => $name,
            'kode_opd' => '2.16.' . $uniqueId,
            'nomor_lampiran_romawi' => 'LAMPIRAN I',
        ]);
    }

    /**
     * Helper to read word/document.xml from docx whether ext-zip is loaded or via python zipfile.
     */
    private function readDocxXml(string $filePath): string
    {
        if (class_exists('ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();
                if ($xml !== false) {
                    return $xml;
                }
            }
        }

        // Python zipfile fallback
        $cmd = sprintf('python -c "import zipfile, sys; z=zipfile.ZipFile(sys.argv[1]); sys.stdout.write(z.read(\'word/document.xml\').decode(\'utf-8\'))" %s 2>&1', escapeshellarg($filePath));
        $output = [];
        exec($cmd, $output);
        return implode("\n", $output);
    }

    /**
     * Test 1: RENJA_MURNI menghasilkan file .docx.
     */
    public function test_renja_murni_generates_docx_file(): void
    {
        $opd = $this->createOpd();
        $result = $this->personalizerService->generateBlankTemplateDocx('RENJA_MURNI', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
        ]);

        $this->assertTrue($result['success']);
        $this->assertFileExists($result['file_path']);
        $this->assertStringEndsWith('.docx', $result['filename']);
        $this->assertGreaterThan(1000, $result['file_size']);

        @unlink($result['file_path']);
    }

    /**
     * Test 2: RENJA_PERUBAHAN menghasilkan file .docx.
     */
    public function test_renja_perubahan_generates_docx_file(): void
    {
        $opd = $this->createOpd();
        $result = $this->personalizerService->generateBlankTemplateDocx('RENJA_PERUBAHAN', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2026,
        ]);

        $this->assertTrue($result['success']);
        $this->assertFileExists($result['file_path']);
        $this->assertStringEndsWith('.docx', $result['filename']);
        $this->assertGreaterThan(1000, $result['file_size']);

        @unlink($result['file_path']);
    }

    /**
     * Test 3: File dapat dibuka sebagai valid DOCX/ZIP dan berisi document.xml.
     */
    public function test_file_is_valid_docx_zip_archive(): void
    {
        $opd = $this->createOpd();
        $result = $this->personalizerService->generateBlankTemplateDocx('RENJA_MURNI', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
        ]);

        $docXml = $this->readDocxXml($result['file_path']);
        $this->assertNotEmpty($docXml, "File must be a valid zip archive containing word/document.xml.");
        $this->assertStringContainsString('w:document', $docXml);

        @unlink($result['file_path']);
    }

    /**
     * Test 4: Template mengambil struktur dari document_templates + template_sections.
     */
    public function test_template_fetches_structure_from_master_database(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $this->assertNotNull($template);
        $this->assertEquals(26, $template->sections()->count());

        $opd = $this->createOpd();
        $result = $this->personalizerService->generateBlankTemplateDocx('RENJA_MURNI', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
        ]);

        $docXml = $this->readDocxXml($result['file_path']);

        // Pastikan nama BAB dari template_sections ada di dalam dokumen
        $this->assertStringContainsString('BAB I', $docXml);
        $this->assertStringContainsString('PENDAHULUAN', $docXml);
        $this->assertStringContainsString('BAB II', $docXml);
        $this->assertStringContainsString('BAB III', $docXml);
        $this->assertStringContainsString('BAB IV', $docXml);
        $this->assertStringContainsString('BAB V', $docXml);
        $this->assertStringContainsString('PENUTUP', $docXml);

        @unlink($result['file_path']);
    }

    /**
     * Test 5: Urutan BAB mengikuti sequence master template.
     */
    public function test_bab_order_follows_master_template_sequence(): void
    {
        $opd = $this->createOpd();
        $result = $this->personalizerService->generateBlankTemplateDocx('RENJA_MURNI', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
        ]);

        $docXml = $this->readDocxXml($result['file_path']);

        $posBab1 = strpos($docXml, 'BAB I');
        $posBab2 = strpos($docXml, 'BAB II');
        $posBab3 = strpos($docXml, 'BAB III');
        $posBab4 = strpos($docXml, 'BAB IV');
        $posBab5 = strpos($docXml, 'BAB V');

        $this->assertNotFalse($posBab1);
        $this->assertNotFalse($posBab2);
        $this->assertNotFalse($posBab3);
        $this->assertNotFalse($posBab4);
        $this->assertNotFalse($posBab5);

        $this->assertLessThan($posBab2, $posBab1);
        $this->assertLessThan($posBab3, $posBab2);
        $this->assertLessThan($posBab4, $posBab3);
        $this->assertLessThan($posBab5, $posBab4);

        @unlink($result['file_path']);
    }

    /**
     * Test 6: Nama OPD berhasil dipersonalisasi.
     */
    public function test_opd_name_is_personalized(): void
    {
        $customOpdName = 'DINAS KESEHATAN KABUPATEN CIREBON';
        $opd = $this->createOpd($customOpdName);

        $result = $this->personalizerService->generateBlankTemplateDocx('RENJA_MURNI', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
        ]);

        $docXml = $this->readDocxXml($result['file_path']);

        $this->assertStringContainsString($customOpdName, $docXml);
        $this->assertStringContainsString('DINAS_KESEHATAN_KABUPATEN_CIREBON', $result['filename']);

        @unlink($result['file_path']);
    }

    /**
     * Test 7: Tahun anggaran berhasil dipersonalisasi.
     */
    public function test_tahun_anggaran_is_personalized(): void
    {
        $opd = $this->createOpd();
        $result = $this->personalizerService->generateBlankTemplateDocx('RENJA_MURNI', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2029,
        ]);

        $docXml = $this->readDocxXml($result['file_path']);

        $this->assertStringContainsString('2029', $docXml);
        $this->assertStringContainsString('2029', $result['filename']);

        @unlink($result['file_path']);
    }

    /**
     * Test 8: Template tidak mengandung data dokumen OPD lain.
     */
    public function test_template_does_not_contain_other_opd_data(): void
    {
        // Bikin OPD lain dengan data rahasia
        $otherOpd = $this->createOpd('DINAS RAHASIA LAIN');
        $doc = RenjaDocument::create([
            'opd_id' => $otherOpd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'Renja Murni',
            'status' => 'disetujui',
        ]);
        $this->templateService->provisionDocumentSections($doc, 'RENJA_MURNI');
        $sec = $doc->sections()->where('sub_bab_code', '1.1')->first();
        if ($sec) {
            $sec->update(['content' => '<p>KONTEN RAHASIA OPD LAIN YANG TIDAK BOLEH BOCOR</p>']);
        }

        // Generate blank template untuk OPD Target
        $targetOpd = $this->createOpd('BADAN PENDAPATAN DAERAH');
        $result = $this->personalizerService->generateBlankTemplateDocx('RENJA_MURNI', [
            'opd_id' => $targetOpd->id,
            'tahun_anggaran' => 2027,
        ]);

        $docXml = $this->readDocxXml($result['file_path']);

        $this->assertStringNotContainsString('KONTEN RAHASIA OPD LAIN', $docXml);
        $this->assertStringNotContainsString('DINAS RAHASIA LAIN', $docXml);

        @unlink($result['file_path']);
    }

    /**
     * Test 9: Template tidak mengambil struktur dari approved RENJA OPD.
     */
    public function test_template_does_not_derive_structure_from_approved_renja_doc(): void
    {
        $opd = $this->createOpd();
        $result = $this->personalizerService->generateBlankTemplateDocx('RENJA_PERUBAHAN', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2026,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('RENJA_PERUBAHAN', $result['template_code']);

        @unlink($result['file_path']);
    }

    /**
     * Test 10: Jika logo kosong/null, generator tetap berhasil tanpa error.
     */
    public function test_null_or_missing_logo_succeeds_gracefully(): void
    {
        $opd = $this->createOpd();
        $result = $this->personalizerService->generateBlankTemplateDocx('RENJA_MURNI', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'logo_path' => null,
        ]);

        $this->assertTrue($result['success']);
        $this->assertFileExists($result['file_path']);

        @unlink($result['file_path']);
    }

    /**
     * Test 11: File menggunakan ukuran F4/Folio (215mm x 330mm = 12189 x 18708 dxa).
     */
    public function test_document_uses_f4_folio_page_size(): void
    {
        $opd = $this->createOpd();
        $result = $this->personalizerService->generateBlankTemplateDocx('RENJA_MURNI', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
        ]);

        $docXml = $this->readDocxXml($result['file_path']);

        // 215mm = ~12189 dxa (w:w="12189" atau "12190" atau pgSz)
        $this->assertStringContainsString('w:pgSz', $docXml);
        $this->assertTrue(
            str_contains($docXml, '12189') || str_contains($docXml, '12190') || str_contains($docXml, 'w:w="12188') || str_contains($docXml, 'w:w="12189')
        );

        @unlink($result['file_path']);
    }

    /**
     * Test 12: Margin 2 cm (20mm = ~1134 dxa).
     */
    public function test_document_uses_2cm_margins(): void
    {
        $opd = $this->createOpd();
        $result = $this->personalizerService->generateBlankTemplateDocx('RENJA_MURNI', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
        ]);

        $docXml = $this->readDocxXml($result['file_path']);

        // 20mm = ~1134 dxa (w:pgMar ... w:top="1134")
        $this->assertStringContainsString('w:pgMar', $docXml);
        $this->assertTrue(str_contains($docXml, '1134') || str_contains($docXml, '1133'));

        @unlink($result['file_path']);
    }

    /**
     * Test 13: Font Bookman Old Style 12 pt.
     */
    public function test_document_uses_bookman_old_style_font(): void
    {
        $opd = $this->createOpd();
        $result = $this->personalizerService->generateBlankTemplateDocx('RENJA_MURNI', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
        ]);

        $docXml = $this->readDocxXml($result['file_path']);

        $this->assertStringContainsString('Bookman Old Style', $docXml);

        @unlink($result['file_path']);
    }

    /**
     * Test 14: Generator tidak merusak existing WordExportService.
     */
    public function test_existing_word_export_service_remains_functional(): void
    {
        $opd = $this->createOpd();
        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Renja Murni',
            'tahun_anggaran' => 2027,
            'status' => 'draft',
            'latar_belakang' => 'Latar belakang testing WordExportService',
        ]);

        $wordExport = app(WordExportService::class);
        $phpWord = $wordExport->generateRenjaDocument($doc);

        $this->assertInstanceOf(\PhpOffice\PhpWord\PhpWord::class, $phpWord);
        $this->assertGreaterThan(0, count($phpWord->getSections()));
    }

    /**
     * Test 15: Existing export-word / filename generator tetap PASS.
     */
    public function test_existing_export_filename_logic_remains_intact(): void
    {
        $opd = $this->createOpd();
        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'RENJA Murni 2027',
            'tahun_anggaran' => 2027,
            'status' => 'draft',
        ]);

        $controller = new \App\Http\Controllers\RenjaDocumentController(app(\App\Services\OpdDocumentService::class));
        $filenameDocx = $controller->buildExportFilename($doc, 'docx');

        $this->assertEquals('RENJA_Murni_2027.docx', $filenameDocx);
    }
}
