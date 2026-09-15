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
use App\Services\OpdDocumentService;
use App\Services\TemplatePersonalizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AutomatedUatStep10Test extends TestCase
{
    use RefreshDatabase;

    protected DocumentTemplateService $templateService;
    protected RenjaMurniDocxService $docxService;
    protected OpdDocumentService $opdDocService;
    protected TemplatePersonalizerService $personalizer;

    protected MasterOpd $opdA;
    protected MasterOpd $opdB;
    protected User $operatorA;
    protected User $operatorB;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->templateService = app(DocumentTemplateService::class);
        $this->templateService->ensureStandardTemplatesSeeded();
        $this->docxService = app(RenjaMurniDocxService::class);
        $this->opdDocService = app(OpdDocumentService::class);
        $this->personalizer = app(TemplatePersonalizerService::class);

        // Create OPD A & Operator A
        $this->opdA = MasterOpd::create([
            'kode_opd' => '1.01.01',
            'nama_opd' => 'Dinas Pendidikan dan Kebudayaan',
            'singkatan_opd' => 'Disdikbud',
            'nomor_lampiran_romawi' => 'LAMPIRAN I',
        ]);

        $this->operatorA = User::factory()->create([
            'nama_lengkap' => 'Operator Disdikbud',
            'username_nip' => '198801012011011001',
            'email' => 'operator.disdikbud@cirebonkab.go.id',
            'role' => 'operator',
            'opd_id' => $this->opdA->id,
        ]);

        // Create OPD B & Operator B
        $this->opdB = MasterOpd::create([
            'kode_opd' => '1.02.01',
            'nama_opd' => 'Dinas Kesehatan Kabupaten Cirebon',
            'singkatan_opd' => 'Dinkes',
            'nomor_lampiran_romawi' => 'LAMPIRAN II',
        ]);

        $this->operatorB = User::factory()->create([
            'nama_lengkap' => 'Operator Dinkes',
            'username_nip' => '198902022012022002',
            'email' => 'operator.dinkes@cirebonkab.go.id',
            'role' => 'operator',
            'opd_id' => $this->opdB->id,
        ]);

        // Create Admin Bapperida
        $this->adminUser = User::factory()->create([
            'nama_lengkap' => 'Admin Bapperida Verifikator',
            'username_nip' => '198003032005011003',
            'email' => 'admin.bapperida@cirebonkab.go.id',
            'role' => 'admin',
        ]);
    }

    /**
     * Helper to create a binary DOCX file with custom XML content.
     */
    private function createDocxWithXml(string $xmlContent, string $filename = 'uat_test_doc.docx'): UploadedFile
    {
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uat_docx_' . uniqid();
        mkdir($tmpDir . DIRECTORY_SEPARATOR . 'word', 0777, true);
        mkdir($tmpDir . DIRECTORY_SEPARATOR . '_rels', 0777, true);

        file_put_contents($tmpDir . DIRECTORY_SEPARATOR . '[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>' .
            '</Types>'
        );

        file_put_contents($tmpDir . DIRECTORY_SEPARATOR . '_rels' . DIRECTORY_SEPARATOR . '.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>' .
            '</Relationships>'
        );

        file_put_contents($tmpDir . DIRECTORY_SEPARATOR . 'word' . DIRECTORY_SEPARATOR . 'document.xml', $xmlContent);

        $zipPath = $tmpDir . '.docx';
        $pyZipPath = str_replace('\\', '/', $zipPath);
        $pyTmpDir = str_replace('\\', '/', $tmpDir);
        $pyScript = "import zipfile, os\n" .
            "with zipfile.ZipFile(r'{$pyZipPath}', 'w', zipfile.ZIP_DEFLATED) as z:\n" .
            "    for root, dirs, files in os.walk(r'{$pyTmpDir}'):\n" .
            "        for file in files:\n" .
            "            full_path = os.path.join(root, file)\n" .
            "            arcname = os.path.relpath(full_path, r'{$pyTmpDir}')\n" .
            "            z.write(full_path, arcname)\n";

        $pyFile = $tmpDir . '_pack.py';
        file_put_contents($pyFile, $pyScript);
        shell_exec("python \"{$pyFile}\" 2>&1");
        @unlink($pyFile);

        return new UploadedFile(
            $zipPath,
            $filename,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );
    }

    /**
     * Helper XML with complete Renja Chapters, Tables, and unique Markers.
     */
    private function getFullStructuredXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' .
            '<w:body>' .
            '<w:p><w:r><w:t>KATA PENGANTAR</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>Puji syukur kami panjatkan ke hadirat Tuhan Yang Maha Esa.</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>UAT_MARKER_BAB_I_001 — Narasi Latar Belakang Resmi OPD Disdikbud TA 2027.</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>1.2 Landasan Hukum</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>Landasan Hukum Permendagri 86 Tahun 2017.</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>BAB II HASIL EVALUASI RENJA PERANGKAT DAERAH TAHUN LALU</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>2.1 Evaluasi Pelaksanaan Renja Perangkat Daerah dan Capaian Renstra Perangkat Daerah</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>UAT_MARKER_BAB_II_002 — Hasil Evaluasi Kinerja Pendidikan Kabupaten Cirebon.</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>BAB III TUJUAN DAN SASARAN PERANGKAT DAERAH</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>3.1 Telaahan Terhadap Kebijakan Daerah</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>Telaahan Kebijakan RPJMD dan Prioritas Daerah.</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>BAB IV RENCANA KERJA DAN PENDANAAN PERANGKAT DAERAH</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>4.1 Rencana Kerja dan Pendanaan Perangkat Daerah</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>UAT_MARKER_BAB_IV_003 — Rincian Program Strategis Peningkatan Mutu Guru.</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>4.2 Matriks Rencana Kerja dan Pendanaan</w:t></w:r></w:p>' .
            '<w:tbl>' .
            '<w:tr>' .
            '<w:tc><w:p><w:r><w:t>Kode Program</w:t></w:r></w:p></w:tc>' .
            '<w:tc><w:p><w:r><w:t>Nama Program</w:t></w:r></w:p></w:tc>' .
            '<w:tc><w:p><w:r><w:t>Pagu Indikatif (Rp)</w:t></w:r></w:p></w:tc>' .
            '</w:tr>' .
            '<w:tr>' .
            '<w:tc><w:p><w:r><w:t>1.01.02</w:t></w:r></w:p></w:tc>' .
            '<w:tc><w:p><w:r><w:t>PROGRAM PENGELOLAAN PENDIDIKAN DASAR</w:t></w:r></w:p></w:tc>' .
            '<w:tc><w:p><w:r><w:t>45000000000</w:t></w:r></w:p></w:tc>' .
            '</w:tr>' .
            '</w:tbl>' .
            '<w:p><w:r><w:t>BAB V PENUTUP</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>5.1 Kaidah Pelaksanaan</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>Kaidah penutup dan konsistensi pelaksanaan Renja.</w:t></w:r></w:p>' .
            '</w:body></w:document>';
    }

    /**
     * UAT 1: Real Template Download for RENJA_MURNI and RENJA_PERUBAHAN.
     * Must return HTTP 200, valid docx, non-empty, and NOT create RenjaDocument in database.
     */
    public function test_uat_1_real_template_download_murni_and_perubahan(): void
    {
        $initialDocCount = RenjaDocument::count();
        $initialSecCount = RenjaSection::count();

        // 1. Download RENJA_MURNI
        $resMurni = $this->actingAs($this->operatorA)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_MURNI',
                'tahun_anggaran' => 2027,
            ]));

        $resMurni->assertStatus(200);
        $resMurni->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertFileExists($resMurni->getFile()->getPathname());
        $this->assertGreaterThan(500, filesize($resMurni->getFile()->getPathname()));

        // 2. Download RENJA_PERUBAHAN
        $resPerubahan = $this->actingAs($this->operatorA)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_PERUBAHAN',
                'tahun_anggaran' => 2026,
            ]));

        $resPerubahan->assertStatus(200);
        $resPerubahan->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertFileExists($resPerubahan->getFile()->getPathname());
        $this->assertGreaterThan(500, filesize($resPerubahan->getFile()->getPathname()));

        // Verify NO phantom RenjaDocument or RenjaSection was created
        $this->assertEquals($initialDocCount, RenjaDocument::count(), 'Template download must never create RenjaDocument records');
        $this->assertEquals($initialSecCount, RenjaSection::count(), 'Template download must never create RenjaSection records');
    }

    /**
     * UAT 2: Repeated Downloads Prevent Duplication.
     */
    public function test_uat_2_repeated_downloads_do_not_duplicate_records(): void
    {
        $initialDocCount = RenjaDocument::count();

        for ($i = 0; $i < 3; $i++) {
            $res = $this->actingAs($this->operatorA)
                ->get(route('renja.templates.download', [
                    'templateCode' => 'RENJA_MURNI',
                    'tahun_anggaran' => 2027,
                ]));
            $res->assertStatus(200);
        }

        $this->assertEquals($initialDocCount, RenjaDocument::count());
    }

    /**
     * UAT 3: Real Word Import with Structured Markers and Table Preservation.
     */
    public function test_uat_3_real_word_import_and_marker_preservation(): void
    {
        $uploadedFile = $this->createDocxWithXml($this->getFullStructuredXml(), 'RENJA_MURNI_Disdikbud_2027.docx');

        // Operator A uploads document via HTTP endpoint
        $res = $this->actingAs($this->operatorA)
            ->post(route('operator.renja-murni.store-upload'), [
                'document_file' => $uploadedFile,
                'jenis_dokumen' => 'RENJA Murni',
                'tahun_anggaran' => 2027,
            ]);

        $res->assertRedirect();

        // Verify document created in database
        $doc = RenjaDocument::where('opd_id', $this->opdA->id)
            ->where('tahun_anggaran', 2027)
            ->latest()
            ->first();

        $this->assertNotNull($doc, 'Imported document must exist in database');
        $this->assertEquals('draft', $doc->status);
        $this->assertEquals($this->opdA->id, $doc->opd_id);
        $this->assertEquals(2027, $doc->tahun_anggaran);

        // Verify Markers in Sections
        $sec11 = RenjaSection::where('document_id', $doc->id)->where('sub_bab_code', '1.1')->first();
        $this->assertNotNull($sec11, 'BAB 1.1 must exist');
        $this->assertStringContainsString('UAT_MARKER_BAB_I_001', $sec11->content);

        $sec21 = RenjaSection::where('document_id', $doc->id)->where('sub_bab_code', '2.1')->first();
        $this->assertNotNull($sec21, 'BAB 2.1 must exist');
        $this->assertStringContainsString('UAT_MARKER_BAB_II_002', $sec21->content);

        $sec41 = RenjaSection::where('document_id', $doc->id)->where('sub_bab_code', '4.1')->first();
        $this->assertNotNull($sec41, 'BAB 4.1 must exist');
        $this->assertStringContainsString('UAT_MARKER_BAB_IV_003', $sec41->content);

        // Verify Table in BAB 4.2
        $sec42 = RenjaSection::where('document_id', $doc->id)->where('sub_bab_code', '4.2')->first();
        $this->assertNotNull($sec42, 'BAB 4.2 must exist');
        $this->assertStringContainsString('<table', $sec42->content);
        $this->assertStringContainsString('PROGRAM PENGELOLAAN PENDIDIKAN DASAR', $sec42->content);
        $this->assertStringContainsString('45000000000', $sec42->content);
    }

    /**
     * UAT 4: AutoFix MUST NOT BE CALLED during Word Upload & Import.
     */
    public function test_uat_4_autofix_is_not_called_during_upload_and_import(): void
    {
        $mockService = $this->partialMock(RenjaMurniDocxService::class, function ($mock) {
            $mock->shouldNotReceive('autoFixDocument');
        });

        $uploadedFile = $this->createDocxWithXml($this->getFullStructuredXml(), 'RENJA_NO_AUTOFIX.docx');

        $this->actingAs($this->operatorA)
            ->post(route('operator.renja-murni.store-upload'), [
                'document_file' => $uploadedFile,
                'jenis_dokumen' => 'RENJA Murni',
                'tahun_anggaran' => 2027,
            ]);

        $this->assertTrue(true, 'Assertion passed: autoFixDocument was not invoked');
    }

    /**
     * UAT 5: Real Document Preview and Content Editor Contains Imported Markers & Tables.
     */
    public function test_uat_5_preview_and_editor_contains_all_imported_markers_and_tables(): void
    {
        $uploadedFile = $this->createDocxWithXml($this->getFullStructuredXml(), 'RENJA_PREVIEW_TEST.docx');

        $this->actingAs($this->operatorA)
            ->post(route('operator.renja-murni.store-upload'), [
                'document_file' => $uploadedFile,
                'jenis_dokumen' => 'RENJA Murni',
                'tahun_anggaran' => 2027,
            ]);

        $doc = RenjaDocument::where('opd_id', $this->opdA->id)->latest()->first();
        $this->assertNotNull($doc);

        // 1. HTTP GET Preview Viewer loads successfully
        $previewRes = $this->actingAs($this->operatorA)
            ->get(route('renja.preview', $doc->id));
        $previewRes->assertStatus(200);
        $previewRes->assertSee('Dinas Pendidikan dan Kebudayaan');

        // 2. HTTP GET Editor interface contains all imported markers and tables
        $editorRes = $this->actingAs($this->operatorA)
            ->get(route('renja.editor', $doc->id));
        $editorRes->assertStatus(200);

        // Verify all sections in database contain the exact markers
        $this->assertStringContainsString('UAT_MARKER_BAB_I_001', $doc->sections()->where('sub_bab_code', '1.1')->first()->content);
        $this->assertStringContainsString('UAT_MARKER_BAB_II_002', $doc->sections()->where('sub_bab_code', '2.1')->first()->content);
        $this->assertStringContainsString('UAT_MARKER_BAB_IV_003', $doc->sections()->where('sub_bab_code', '4.1')->first()->content);
        $this->assertStringContainsString('PROGRAM PENGELOLAAN PENDIDIKAN DASAR', $doc->sections()->where('sub_bab_code', '4.2')->first()->content);
    }

    /**
     * UAT 6: Real HTTP End-to-End Workflow: Submit -> Revision Request -> Resubmit -> Approve.
     * STRICT: No direct model updates like $doc->update(['status' => ...]).
     */
    public function test_uat_6_real_http_workflow_submit_revision_resubmit_approve(): void
    {
        // 1. Create Draft Document via Upload
        $uploadedFile = $this->createDocxWithXml($this->getFullStructuredXml(), 'RENJA_WORKFLOW_TEST.docx');
        $this->actingAs($this->operatorA)
            ->post(route('operator.renja-murni.store-upload'), [
                'document_file' => $uploadedFile,
                'jenis_dokumen' => 'RENJA Murni',
                'tahun_anggaran' => 2027,
            ]);

        $doc = RenjaDocument::where('opd_id', $this->opdA->id)->latest()->first();
        $this->assertEquals('draft', $doc->status);

        // 2. Operator submits document via HTTP POST
        $submitRes = $this->actingAs($this->operatorA)
            ->post(route('renja.submit', $doc->id));
        $submitRes->assertRedirect();

        $doc->refresh();
        $this->assertEquals('submitted', $doc->status, 'Status must transition to submitted');

        // 3. Admin views verification queue and reviews document
        $queueRes = $this->actingAs($this->adminUser)->get(route('admin.verifikasi.index'));
        $queueRes->assertStatus(200);
        $queueRes->assertSee($this->opdA->nama_opd);

        // 4. Admin requests revision via HTTP POST decision
        $revisionRes = $this->actingAs($this->adminUser)
            ->post(route('admin.verifikasi.decision', $doc->id), [
                'decision_type' => 'minta_revisi',
                'catatan_bapperida' => 'UAT Revision Request: Mohon perbaiki Bab II data evaluasi.',
            ]);
        $revisionRes->assertRedirect(route('admin.verifikasi.index'));

        $doc->refresh();
        $this->assertEquals('perlu_revisi', $doc->status, 'Status must transition to perlu_revisi');
        $this->assertStringContainsString('UAT Revision Request', $doc->catatan_bapperida);

        // 5. Operator resubmits document via HTTP POST
        $resubmitRes = $this->actingAs($this->operatorA)
            ->post(route('renja.submit', $doc->id));
        $resubmitRes->assertRedirect();

        $doc->refresh();
        $this->assertEquals('dikirim_ulang', $doc->status, 'Status must transition to dikirim_ulang');

        // 6. Admin approves document via HTTP POST decision
        $approveRes = $this->actingAs($this->adminUser)
            ->post(route('admin.verifikasi.decision', $doc->id), [
                'decision_type' => 'setujui_dokumen',
                'catatan_bapperida' => 'UAT Approval: Dokumen Renja Disetujui Penuh.',
            ]);
        $approveRes->assertRedirect(route('admin.verifikasi.index'));

        $doc->refresh();
        $this->assertEquals('disetujui', $doc->status, 'Status must transition to disetujui');
    }

    /**
     * UAT 7: Lampiran Live Sync & Isolation.
     */
    public function test_uat_7_lampiran_live_sync_and_isolation(): void
    {
        // 1. Create Parent Renja Murni via standard service
        $parentDoc = $this->opdDocService->createRenjaMurni($this->opdA->id, 2027);

        // 2. Generate Lampiran
        $lampiranDoc = $this->opdDocService->generateLampiranPerbub($this->opdA->id, 2027, 'murni');
        $this->assertNotNull($lampiranDoc);
        $this->assertEquals('RENJA_LAMPIRAN_MURNI', $lampiranDoc->template?->code);

        // 3. Mutate Parent Section via HTTP Editor Update
        $parentSec = $parentDoc->sections()->where('sub_bab_code', '1.1')->first();
        $this->actingAs($this->operatorA)
            ->post(route('renja.editor.updateSection', ['id' => $parentDoc->id, 'sectionId' => $parentSec->id]), [
                'content' => '<p>UAT_LIVE_SYNC_TEST_001 — Sinkronisasi Konten Dinamis.</p>',
            ]);

        // 4. Reload Lampiran sections and assert dynamic reflection from parent
        $effectiveSections = $lampiranDoc->getEffectiveSections();
        $syncedSec = $effectiveSections->firstWhere('sub_bab_code', '1.1');
        $this->assertNotNull($syncedSec);
        $this->assertStringContainsString('UAT_LIVE_SYNC_TEST_001', $syncedSec->content);

        // 5. Submit & Approve parent Murni through real HTTP workflow so Perubahan is permitted under BR-032
        $this->actingAs($this->operatorA)->post(route('renja.submit', $parentDoc->id));
        $this->actingAs($this->adminUser)->post(route('admin.verifikasi.decision', $parentDoc->id), [
            'decision_type' => 'setujui_dokumen',
            'catatan_bapperida' => 'Disetujui untuk membuka siklus Perubahan',
        ]);
        $parentDoc->refresh();
        $this->assertEquals('disetujui', $parentDoc->status);

        // 6. Test Lampiran Isolation: Murni -> Lampiran Murni, Perubahan -> Lampiran Perubahan
        $parentPerubahan = $this->opdDocService->createRenjaPerubahan($this->opdA->id, 2026);
        $lampiranPerubahan = $this->opdDocService->generateLampiranPerbub($this->opdA->id, 2026, 'perubahan');
        $this->assertEquals('RENJA_LAMPIRAN_PERUBAHAN', $lampiranPerubahan->template?->code);
    }

    /**
     * UAT 8: Security and IDOR Protection via Actual HTTP Requests.
     */
    public function test_uat_8_cross_opd_security_and_idor_protection_via_http(): void
    {
        // Create Document for Operator A
        $docA = $this->opdDocService->createRenjaMurni($this->opdA->id, 2027);

        // Operator B attempts to view docA -> 403
        $this->actingAs($this->operatorB)
            ->get(route('renja.show', $docA->id))
            ->assertStatus(403);

        // Operator B attempts to preview docA -> 403
        $this->actingAs($this->operatorB)
            ->get(route('renja.preview', $docA->id))
            ->assertStatus(403);

        // Operator B attempts to open editor for docA -> 403
        $this->actingAs($this->operatorB)
            ->get(route('renja.editor', $docA->id))
            ->assertStatus(403);

        // Operator B attempts to export Word docA -> 403
        $this->actingAs($this->operatorB)
            ->get(route('renja.exportWord', $docA->id))
            ->assertStatus(403);

        // Operator B attempts to submit docA -> denied (404/403)
        $submitDeniedRes = $this->actingAs($this->operatorB)
            ->post(route('renja.submit', $docA->id));
        $this->assertTrue(in_array($submitDeniedRes->status(), [403, 404]));

        // Operator B attempts to delete docA -> denied
        $deleteRes = $this->actingAs($this->operatorB)
            ->delete(route('renja.destroy', $docA->id));
        $this->assertTrue(in_array($deleteRes->status(), [302, 403, 404]));
        $docA->refresh();
        $this->assertNotNull($docA, 'Document of Operator A must not be deleted by Operator B');
    }

    /**
     * UAT 9: Operator Forbidden from Admin Verification Endpoints.
     */
    public function test_uat_9_operator_forbidden_from_admin_verification_endpoints(): void
    {
        $docA = $this->opdDocService->createRenjaMurni($this->opdA->id, 2027);

        // Operator A attempts to access admin verification queue -> Redirected/Denied
        $queueRes = $this->actingAs($this->operatorA)
            ->get(route('admin.verifikasi.index'));
        $queueRes->assertRedirect(route('operator.dashboard'));
        $queueRes->assertSessionHas('error');

        // Operator A attempts to access review detail -> Redirected/Denied
        $reviewRes = $this->actingAs($this->operatorA)
            ->get(route('admin.verifikasi.review', $docA->id));
        $reviewRes->assertRedirect(route('operator.dashboard'));
        $reviewRes->assertSessionHas('error');

        // Operator A attempts to approve / decision -> Redirected/Denied
        $decisionRes = $this->actingAs($this->operatorA)
            ->post(route('admin.verifikasi.decision', $docA->id), [
                'decision_type' => 'setujui_dokumen',
                'catatan_bapperida' => 'Illegal operator approval attempt',
            ]);
        $decisionRes->assertRedirect(route('operator.dashboard'));
        $decisionRes->assertSessionHas('error');
    }

    /**
     * UAT 10: Invalid, Corrupted, and Non-DOCX File Handling.
     */
    public function test_uat_10_invalid_and_corrupt_file_handling(): void
    {
        // 1. Non-DOCX (.txt)
        $txtFile = UploadedFile::fake()->create('malicious.txt', 100, 'text/plain');
        $resTxt = $this->actingAs($this->operatorA)
            ->from(route('renja.workspace', ['tahun_anggaran' => 2027]))
            ->post(route('operator.renja-murni.store-upload'), [
                'document_file' => $txtFile,
                'jenis_dokumen' => 'RENJA Murni',
                'tahun_anggaran' => 2027,
            ]);
        $resTxt->assertSessionHas('error');

        // 2. Corrupt DOCX content
        $tmpCorrupt = tempnam(sys_get_temp_dir(), 'corrupt_') . '.docx';
        file_put_contents($tmpCorrupt, 'Corrupted binary content that is not zip');

        $docCorrupt = $this->docxService->importDocx($tmpCorrupt, $this->opdA->id, 2027, 'RENJA Murni');
        $this->assertNotNull($docCorrupt, 'Resilient parser generates structured fallback rather than crashing');
        $this->assertEquals('draft', $docCorrupt->status);
        $this->assertGreaterThan(0, $docCorrupt->sections()->count());

        @unlink($tmpCorrupt);

        // 3. Wrong Template Code in Download Endpoint returns 404
        $this->actingAs($this->operatorA)
            ->get('/renja-templates/INVALID_UNKNOWN_TEMPLATE/download')
            ->assertStatus(404);
    }

    /**
     * UAT 11: Data Integrity Verification (No duplicate documents, no lost sections).
     */
    public function test_uat_11_data_integrity_and_clean_state(): void
    {
        $doc = $this->opdDocService->createRenjaMurni($this->opdA->id, 2027);

        $sections = RenjaSection::where('document_id', $doc->id)->get();
        $this->assertEquals(26, $sections->count(), 'Standard Renja Murni must contain exactly 26 structured sections');

        $this->assertEquals($this->opdA->id, $doc->opd_id);
        $this->assertEquals(2027, $doc->tahun_anggaran);
        $this->assertEquals('draft', $doc->status);
    }
}
