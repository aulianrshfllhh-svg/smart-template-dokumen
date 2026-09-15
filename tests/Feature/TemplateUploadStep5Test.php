<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Models\DocumentTemplate;
use App\Services\DocumentTemplateService;
use App\Services\RenjaMurniDocxService;
use App\Services\TemplatePersonalizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class TemplateUploadStep5Test extends TestCase
{
    use RefreshDatabase;

    protected DocumentTemplateService $templateService;
    protected RenjaMurniDocxService $docxService;
    protected TemplatePersonalizerService $personalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateService = app(DocumentTemplateService::class);
        $this->templateService->ensureStandardTemplatesSeeded();
        $this->docxService = app(RenjaMurniDocxService::class);
        $this->personalizer = app(TemplatePersonalizerService::class);
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

    private function createOperator(MasterOpd $opd): User
    {
        return User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'nama_lengkap' => 'Operator Test OPD',
            'username_nip' => '19900101' . rand(1000000000, 9999999999),
        ]);
    }

    /**
     * Helper to build a valid binary DOCX file with custom XML content.
     */
    private function createDocxFile(string $documentXmlContent, string $filename = 'test_document.docx'): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'docx_test_') . '.docx';
        $tempPath = str_replace('\\', '/', $tempPath);

        $xmlTemp = tempnam(sys_get_temp_dir(), 'xml_test_') . '.xml';
        file_put_contents($xmlTemp, $documentXmlContent);
        $xmlTemp = str_replace('\\', '/', $xmlTemp);

        $pyFile = tempnam(sys_get_temp_dir(), 'make_docx_') . '.py';
        $pyCode = <<<PY
import zipfile

content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>'
rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>'

with zipfile.ZipFile(r'{$tempPath}', 'w', zipfile.ZIP_DEFLATED) as z:
    z.writestr('[Content_Types].xml', content_types)
    z.writestr('_rels/.rels', rels)
    z.write(r'{$xmlTemp}', 'word/document.xml')
PY;
        file_put_contents($pyFile, $pyCode);

        $pythonBinary = env('PYTHON_BINARY_PATH', 'python');
        shell_exec("{$pythonBinary} \"{$pyFile}\"");
        @unlink($pyFile);
        @unlink($xmlTemp);

        return new UploadedFile(
            $tempPath,
            $filename,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );
    }

    /**
     * TEST 1: Upload Template Word Resmi (hasil generator STEP 2) berhasil di-import dan dipetakan.
     */
    public function test_1_official_template_upload_imports_successfully(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        // Generate official template from STEP 2 service
        $genResult = $this->personalizer->generateBlankTemplateDocx('RENJA_MURNI', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
        ]);
        $templatePath = $genResult['file_path'];
        $this->assertFileExists($templatePath);

        $uploadedFile = new UploadedFile(
            $templatePath,
            'Template_RENJA_Murni_2027.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $response = $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $uploadedFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document = RenjaDocument::where('opd_id', $opd->id)
            ->where('tahun_anggaran', 2027)
            ->where('jenis_dokumen', 'RENJA Murni')
            ->first();

        $this->assertNotNull($document);
        $this->assertEquals('upload_word', $document->source_type);
        $this->assertEquals('draft', $document->status);
        $this->assertGreaterThan(0, $document->sections()->count());
    }

    /**
     * TEST 2: Existing Word RENJA dengan variasi penulisan heading (Fuzzy / Tolerant) berhasil diparse.
     */
    public function test_2_fuzzy_tolerant_heading_parsing(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I</w:t></w:r></w:p>
        <w:p><w:r><w:t>PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1. Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>Teks narasi latar belakang dengan titik di akhir kode nomor.</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.2 LANDASAN HUKUM</w:t></w:r></w:p>
        <w:p><w:r><w:t>Teks landasan hukum huruf kapital semua.</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'variasi_heading.docx');

        $document = $this->docxService->importDocx($file, $opd->id, 2027, 'RENJA Murni');

        $this->assertNotNull($document);
        $sec11 = $document->sections()->where('sub_bab_code', '1.1')->first();
        $this->assertNotNull($sec11);
        $this->assertStringContainsString('Teks narasi latar belakang', $sec11->content);

        $sec12 = $document->sections()->where('sub_bab_code', '1.2')->first();
        $this->assertNotNull($sec12);
        $this->assertStringContainsString('Teks landasan hukum huruf kapital', $sec12->content);
    }

    /**
     * TEST 3: CRITICAL TEST — Content Preservation (Isi asli operator tidak di-rewrite atau hilang).
     */
    public function test_3_critical_content_preservation_no_rewriting(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $customContent = 'Ini adalah isi asli yang dibuat oleh Operator OPD tanpa reformat.';

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>' . $customContent . '</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'content_preservation.docx');

        $document = $this->docxService->importDocx($file, $opd->id, 2027, 'RENJA Murni');

        $sec11 = $document->sections()->where('sub_bab_code', '1.1')->first();
        $this->assertNotNull($sec11);
        
        // Assert exact text is preserved
        $this->assertStringContainsString($customContent, $sec11->content);
        $this->assertStringNotContainsString('[Isi Latar Belakang]', $sec11->content);
        $this->assertNotNull($sec11->content);
    }

    /**
     * TEST 4: Unmapped Content — Paragraf di luar template standar tidak dihapus dan tersimpan.
     */
    public function test_4_unmapped_content_is_preserved_not_deleted(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $unmappedText = 'Catatan tambahan khusus dari OPD yang tidak ada di standar seksi.';

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>Isi 1.1</w:t></w:r></w:p>
        <w:p><w:r><w:t>9.9 Seksi Khusus Non-Standar</w:t></w:r></w:p>
        <w:p><w:r><w:t>' . $unmappedText . '</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'unmapped_test.docx');

        $document = $this->docxService->importDocx($file, $opd->id, 2027, 'RENJA Murni');

        $this->assertNotNull($document);

        // Cari seksi unmapped
        $unmappedSection = $document->sections()->where('content', 'LIKE', '%' . $unmappedText . '%')->first();
        $this->assertNotNull($unmappedSection, 'Unmapped section must be saved in renja_sections.');
        $this->assertEquals('unmapped_section', $unmappedSection->metadata['mapping_status'] ?? null);
    }

    /**
     * TEST 5: Table Import — Tabel dalam Word terbaca dengan baris/kolom lengkap dan tidak hilang.
     */
    public function test_5_table_import_preserves_structure_and_cells(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB IV RENCANA KERJA DAN PENDANAAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>4.2 Matriks Program dan Kegiatan</w:t></w:r></w:p>
        <w:tbl>
            <w:tr>
                <w:tc><w:p><w:r><w:t>No</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>Program / Kegiatan</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>Indikator</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>Target</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>Pagu Anggaran (Rp)</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:t>1</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>Program Pelayanan Informasi Publik</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>Persentase Layanan Terpenuhi</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>100%</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>500.000.000</w:t></w:r></w:p></w:tc>
            </w:tr>
        </w:tbl>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'table_test.docx');

        $document = $this->docxService->importDocx($file, $opd->id, 2027, 'RENJA Murni');

        $sec42 = $document->sections()->where('sub_bab_code', '4.2')->first();
        $this->assertNotNull($sec42);
        $this->assertStringContainsString('<table', $sec42->content);
        $this->assertStringContainsString('Program Pelayanan Informasi Publik', $sec42->content);
        $this->assertStringContainsString('500.000.000', $sec42->content);
        $this->assertEquals('landscape', $sec42->metadata['orientation'] ?? 'portrait');
    }

    /**
     * TEST 6: AutoFix TIDAK dipanggil selama upload/import template.
     */
    public function test_6_autofix_is_not_called_during_upload(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>Isi dokumen normal.</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'no_autofix.docx');

        $document = $this->docxService->importDocx($file, $opd->id, 2027, 'RENJA Murni');

        $metadata = $document->metadata ?? [];
        $this->assertTrue($metadata['autofix_disabled'] ?? false);
        $this->assertArrayNotHasKey('autofix_changes', $metadata);

        $auditTrail = $metadata['audit_trail'] ?? [];
        $actions = array_column($auditTrail, 'action');
        $this->assertNotContains('AUTO_FIX_APPLIED', $actions);
    }

    /**
     * TEST 7: Operator Authorization & IDOR — Operator OPD A tidak bisa mengunggah untuk OPD B.
     */
    public function test_7_operator_cannot_upload_for_other_opd(): void
    {
        $opdA = $this->createOpd('OPD A');
        $opdB = $this->createOpd('OPD B');
        $operatorA = $this->createOperator($opdA);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>Konten OPD A.</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'idor_test.docx');

        // Operator A attempts to upload specifying opd_id of OPD B
        $response = $this->actingAs($operatorA)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'opd_id' => $opdB->id, // Malicious override attempt
            'document_file' => $file,
        ]);

        $response->assertRedirect();

        // Must be saved under OPD A, NEVER OPD B
        $docA = RenjaDocument::where('opd_id', $opdA->id)->where('tahun_anggaran', 2027)->first();
        $docB = RenjaDocument::where('opd_id', $opdB->id)->where('tahun_anggaran', 2027)->first();

        $this->assertNotNull($docA, 'Document must be bound to authenticated operator OPD');
        $this->assertNull($docB, 'Operator A must NOT create document for OPD B');
    }

    /**
     * TEST 8: Database Transaction — Kegagalan memicu rollback bersih tanpa dokumen setengah jadi.
     */
    public function test_8_database_transaction_rolls_back_on_failure(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $countBeforeDocs = RenjaDocument::count();
        $countBeforeSections = RenjaSection::count();

        // Pass invalid non-existent file path
        try {
            $this->docxService->importDocx('completely_non_existent_path_xyz.invalid', $opd->id, 2027);
        } catch (\Throwable $e) {
            // Expected failure
        }

        $this->assertEquals($countBeforeDocs, RenjaDocument::count());
        $this->assertEquals($countBeforeSections, RenjaSection::count());
    }

    /**
     * TEST 9: Lifecycle Dokumen — Dokumen berstatus draft dan masuk ke alur existing.
     */
    public function test_9_document_lifecycle_initial_status(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>Latar belakang dokumen draf.</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'lifecycle_test.docx');

        $document = $this->docxService->importDocx($file, $opd->id, 2027, 'RENJA Murni');

        $this->assertEquals('draft', $document->status);
        $this->assertNotEquals('disetujui', $document->status);
        $this->assertNotEquals('approved', $document->status);
    }

    /**
     * TEST 10: Validation Diagnostic — Validasi bersifat diagnostik dan tidak menghapus isi.
     */
    public function test_10_validation_diagnostic_does_not_mutate_content(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>Isi 1.1 tetap utuh.</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'validation_test.docx');

        $document = $this->docxService->importDocx($file, $opd->id, 2027, 'RENJA Murni');

        $contentBefore = $document->sections()->where('sub_bab_code', '1.1')->value('content');

        $diagnostics = $this->docxService->validateDocument($document);

        $this->assertIsArray($diagnostics);
        $this->assertArrayHasKey('structure', $diagnostics);
        $this->assertArrayHasKey('format', $diagnostics);
        $this->assertArrayHasKey('summary', $diagnostics);

        $contentAfter = $document->sections()->where('sub_bab_code', '1.1')->value('content');

        // Content must be 100% identical before and after validation
        $this->assertEquals($contentBefore, $contentAfter);
    }
}
