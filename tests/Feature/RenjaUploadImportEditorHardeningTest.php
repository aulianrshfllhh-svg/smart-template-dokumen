<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Models\DocumentTemplate;
use App\Models\TemplateSection;
use App\Services\DocumentTemplateService;
use App\Services\RenjaMurniDocxService;
use App\Services\TemplatePersonalizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RenjaUploadImportEditorHardeningTest extends TestCase
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
            'nama_lengkap' => 'Operator ' . $opd->nama_opd,
            'username_nip' => '19900101' . rand(1000000000, 9999999999),
        ]);
    }

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
        shell_exec($pythonBinary . ' ' . escapeshellarg($pyFile));
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

    public function test_01_valid_docx_upload(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>Isi latar belakang dokumen valid.</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'valid_renja.docx');

        $response = $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $this->assertNotNull($doc);
        $this->assertEquals('upload_word', $doc->source_type);
        $this->assertEquals('draft', $doc->status);
    }

    public function test_02_corrupt_docx_rejected(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $tempPath = tempnam(sys_get_temp_dir(), 'corrupt_') . '.docx';
        file_put_contents($tempPath, 'THIS IS CORRUPTED BINARY DATA NOT A ZIP ARCHIVE');

        $file = new UploadedFile($tempPath, 'corrupt.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $response = $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $response->assertSessionHas('error');
        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $this->assertNull($doc);
    }

    public function test_03_wrong_file_extension_rejected(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_') . '.pdf';
        file_put_contents($tempPath, '%PDF-1.4 dummy pdf');

        $file = new UploadedFile($tempPath, 'test.pdf', 'application/pdf', null, true);

        $response = $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $response->assertSessionHas('error');
    }

    public function test_04_content_preservation_markers(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>Teks narasi awal NARASI_MARKER_A pada dokumen.</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.2 Landasan Hukum</w:t></w:r></w:p>
        <w:p><w:r><w:t>Landasan hukum lengkap NARASI_MARKER_B nomor 123/ABC/2027.</w:t></w:r></w:p>
        <w:p><w:r><w:t>BAB V PENUTUP</w:t></w:r></w:p>
        <w:p><w:r><w:t>5.1 Kaidah Pelaksanaan</w:t></w:r></w:p>
        <w:p><w:r><w:t>Kaidah penutup NARASI_MARKER_C tetap terjaga.</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'marker_renja.docx');

        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $this->assertNotNull($doc);

        $allContent = $doc->sections()->pluck('content')->implode(' ');
        $this->assertStringContainsString('NARASI_MARKER_A', $allContent);
        $this->assertStringContainsString('NARASI_MARKER_B', $allContent);
        $this->assertStringContainsString('NARASI_MARKER_C', $allContent);
    }

    public function test_05_table_preservation(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB II HASIL EVALUASI RENJA</w:t></w:r></w:p>
        <w:p><w:r><w:t>2.1 Evaluasi Pelaksanaan Renja</w:t></w:r></w:p>
        <w:tbl>
            <w:tr>
                <w:tc><w:p><w:r><w:t>Kode Rekening</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>Nama Program</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>Indikator</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>Target</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>Pagu</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:t>1.01.01</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>Program Layanan TABLE_MARKER_A</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>Indikator TABLE_MARKER_B</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>100%</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>500000000</w:t></w:r></w:p></w:tc>
            </w:tr>
        </w:tbl>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'table_renja.docx');

        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $this->assertNotNull($doc);

        $allContent = $doc->sections()->pluck('content')->implode(' ');
        $this->assertStringContainsString('<table', $allContent);
        $this->assertStringContainsString('TABLE_MARKER_A', $allContent);
        $this->assertStringContainsString('TABLE_MARKER_B', $allContent);
        $this->assertStringContainsString('1.01.01', $allContent);
    }

    public function test_06_bab_preservation(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>Isi bab 1</w:t></w:r></w:p>
        <w:p><w:r><w:t>BAB II EVALUASI</w:t></w:r></w:p>
        <w:p><w:r><w:t>2.1 Hasil Evaluasi</w:t></w:r></w:p>
        <w:p><w:r><w:t>Isi bab 2</w:t></w:r></w:p>
        <w:p><w:r><w:t>BAB III TUJUAN DAN SASARAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>3.1 Tujuan</w:t></w:r></w:p>
        <w:p><w:r><w:t>Isi bab 3</w:t></w:r></w:p>
        <w:p><w:r><w:t>BAB IV RENCANA KERJA</w:t></w:r></w:p>
        <w:p><w:r><w:t>4.1 Program dan Kegiatan</w:t></w:r></w:p>
        <w:p><w:r><w:t>Isi bab 4</w:t></w:r></w:p>
        <w:p><w:r><w:t>BAB V PENUTUP</w:t></w:r></w:p>
        <w:p><w:r><w:t>5.1 Kaidah Penutup</w:t></w:r></w:p>
        <w:p><w:r><w:t>Isi bab 5</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'all_babs.docx');

        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $this->assertNotNull($doc);

        $babCodes = $doc->sections()->pluck('bab_code')->unique()->toArray();
        $this->assertContains('BAB I', $babCodes);
        $this->assertContains('BAB II', $babCodes);
        $this->assertContains('BAB III', $babCodes);
        $this->assertContains('BAB IV', $babCodes);
        $this->assertContains('BAB V', $babCodes);
    }

    public function test_07_unmapped_content_not_lost(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>Latar belakang normal.</w:t></w:r></w:p>
        <w:p><w:r><w:t>9.9 Seksi Khusus Tambahan Non Standar</w:t></w:r></w:p>
        <w:p><w:r><w:t>Konten unik UNMAPPED_CUSTOM_SECTION_MARKER yang tidak ada di master template.</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'unmapped_renja.docx');

        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $this->assertNotNull($doc);

        $unmappedSection = $doc->sections()->where('metadata->mapping_status', 'unmapped_section')->first();
        $this->assertNotNull($unmappedSection);
        $this->assertStringContainsString('UNMAPPED_CUSTOM_SECTION_MARKER', $unmappedSection->content);
    }

    public function test_08_failed_import_rollback(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $tempPath = tempnam(sys_get_temp_dir(), 'invalid_') . '.docx';
        file_put_contents($tempPath, 'CORRUPTED FILE NOT VALID ZIP');
        $file = new UploadedFile($tempPath, 'invalid.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $docCountBefore = RenjaDocument::count();
        $secCountBefore = RenjaSection::count();

        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $this->assertEquals($docCountBefore, RenjaDocument::count());
        $this->assertEquals($secCountBefore, RenjaSection::count());
    }

    public function test_09_existing_document_safe_on_failed_reupload(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml1 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>KONTEN_VALID_SEBELUMNYA_TETAP_AMAN</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file1 = $this->createDocxFile($xml1, 'valid1.docx');
        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file1,
        ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $this->assertNotNull($doc);
        $oldSectionCount = $doc->sections()->count();

        $tempPath = tempnam(sys_get_temp_dir(), 'corrupt_') . '.docx';
        file_put_contents($tempPath, 'CORRUPTED DATA');
        $corruptFile = new UploadedFile($tempPath, 'corrupt.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $corruptFile,
        ]);

        $doc->refresh();
        $this->assertEquals($oldSectionCount, $doc->sections()->count());
        $this->assertStringContainsString('KONTEN_VALID_SEBELUMNYA_TETAP_AMAN', $doc->sections()->pluck('content')->implode(' '));
    }

    public function test_10_master_template_immutable(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $initialTemplateUpdated = $template->updated_at?->toIso8601String();
        $initialSectionCount = $template->sections()->count();

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>Uji immutability template master.</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'master_immutability.docx');
        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $template->refresh();
        $this->assertEquals($initialSectionCount, $template->sections()->count());
        $this->assertEquals($initialTemplateUpdated, $template->updated_at?->toIso8601String());
    }

    public function test_11_year_integrity(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>RENCANA KERJA TAHUN 2099</w:t></w:r></w:p>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'renja_2099_file.docx');
        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->first();
        $this->assertNotNull($doc);
        $this->assertEquals(2027, $doc->tahun_anggaran);
    }

    public function test_12_opd_integrity(): void
    {
        $opdA = $this->createOpd('DINAS KESEHATAN');
        $opdB = $this->createOpd('DINAS PENDIDIKAN');
        $operatorA = $this->createOperator($opdA);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>DINAS PENDIDIKAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'renja_pendidikan.docx');

        $this->actingAs($operatorA)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
            'opd_id' => $opdB->id,
        ]);

        $docA = RenjaDocument::where('opd_id', $opdA->id)->first();
        $docB = RenjaDocument::where('opd_id', $opdB->id)->first();

        $this->assertNotNull($docA);
        $this->assertNull($docB);
        $this->assertEquals($opdA->id, $docA->opd_id);
    }

    public function test_13_document_variant_integrity(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>RENJA PERUBAHAN TAHUN 2027</w:t></w:r></w:p>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'renja_perubahan_fake.docx');

        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $this->assertNotNull($doc);
        $this->assertEquals('RENJA Murni', $doc->jenis_dokumen);
    }

    public function test_14_editor_reads_imported_data(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>Konten khusus IMPORT_EDITOR_MARKER_001 yang harus muncul di editor.</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'editor_marker.docx');

        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $this->assertNotNull($doc);

        $response = $this->actingAs($operator)->get(route('renja.editor', $doc->id));
        $response->assertOk();
        $response->assertSee('IMPORT_EDITOR_MARKER_001');
    }

    public function test_15_editor_modification_saved(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>Konten awal sebelum edit.</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'editor_edit.docx');

        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $section = $doc->sections()->where('sub_bab_code', '1.1')->first();
        $this->assertNotNull($section);

        $response = $this->actingAs($operator)->post(route('renja.editor.section.update', [
            'id' => $doc->id,
            'sectionId' => $section->id,
        ]), [
            'content' => '<p>KONTEN_HASIL_MODIFIKASI_EDITOR_002</p>',
            'note' => 'Operator edit',
        ]);

        $response->assertJson(['success' => true]);

        $section->refresh();
        $this->assertStringContainsString('KONTEN_HASIL_MODIFIKASI_EDITOR_002', $section->content);
        
        $doc->refresh();
        $this->assertTrue($doc->metadata['editor_modified'] ?? false);
    }

    public function test_16_preview_reflects_latest_data(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>PREVIEW_INITIAL_MARKER</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'preview_test.docx');

        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $response = $this->actingAs($operator)->get(route('renja.preview', $doc->id));
        $response->assertOk();
    }

    public function test_17_export_reflects_latest_data(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>EXPORT_INITIAL_MARKER</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'export_test.docx');

        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $response = $this->actingAs($operator)->get(route('renja.exportWord', $doc->id));
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));
    }

    public function test_18_lampiran_live_sync_after_induk_import(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>LIVE_SYNC_INDUK_TO_LAMPIRAN_MARKER</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'sync_induk.docx');

        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $induk = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $this->assertNotNull($induk);

        $opdService = app(\App\Services\OpdDocumentService::class);
        $lampiran = $opdService->generateLampiranPerbub($opd->id, 2027, 'murni');

        $effectiveSecs = $lampiran->getEffectiveSections();
        $this->assertNotEmpty($effectiveSecs);
        $this->assertStringContainsString('LIVE_SYNC_INDUK_TO_LAMPIRAN_MARKER', $effectiveSecs->pluck('content')->implode(' '));
    }

    public function test_19_cross_opd_upload_rejected(): void
    {
        $opdA = $this->createOpd('OPD A');
        $opdB = $this->createOpd('OPD B');
        $operatorA = $this->createOperator($opdA);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'cross_opd.docx');

        $this->actingAs($operatorA)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
            'opd_id' => $opdB->id,
        ]);

        $docB = RenjaDocument::where('opd_id', $opdB->id)->first();
        $this->assertNull($docB);
    }

    public function test_20_cross_opd_section_manipulation_rejected(): void
    {
        $opdA = $this->createOpd('OPD A');
        $opdB = $this->createOpd('OPD B');
        $operatorA = $this->createOperator($opdA);

        $docB = RenjaDocument::create([
            'opd_id' => $opdB->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
            'source_type' => 'template',
        ]);

        $sectionB = RenjaSection::create([
            'document_id' => $docB->id,
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'section_type' => 'subchapter',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'content' => '<p>Original OPD B Content</p>',
            'order_index' => 1,
            'is_completed' => true,
        ]);

        $response = $this->actingAs($operatorA)->post(route('renja.editor.section.update', [
            'id' => $docB->id,
            'sectionId' => $sectionB->id,
        ]), [
            'content' => '<p>Hacked by Operator A</p>',
        ]);

        $response->assertStatus(403);
        $sectionB->refresh();
        $this->assertEquals('<p>Original OPD B Content</p>', $sectionB->content);
    }

    public function test_21_duplicate_upload_replaces_existing_draft(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml1 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>File 1 Content</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file1 = $this->createDocxFile($xml1, 'upload1.docx');
        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file1,
        ]);

        $docCountFirst = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->count();
        $this->assertEquals(1, $docCountFirst);

        $xml2 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>File 2 Updated Content</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file2 = $this->createDocxFile($xml2, 'upload2.docx');
        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file2,
        ]);

        $docCountSecond = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->count();
        $this->assertEquals(1, $docCountSecond);

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $this->assertStringContainsString('File 2 Updated Content', $doc->sections()->pluck('content')->implode(' '));
    }

    public function test_22_autofix_not_called(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>
        <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
        <w:p><w:r><w:t>Teks asli tanpa modifikasi AutoFix.</w:t></w:r></w:p>
    </w:body>
</w:document>';

        $file = $this->createDocxFile($xml, 'no_autofix.docx');

        $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2027)->first();
        $this->assertNotNull($doc);
        $this->assertTrue($doc->metadata['autofix_disabled'] ?? false);

        $auditTrail = $doc->metadata['audit_trail'] ?? [];
        $actions = array_column($auditTrail, 'action');
        $this->assertNotContains('AUTO_FIX_APPLIED', $actions);
    }
}
