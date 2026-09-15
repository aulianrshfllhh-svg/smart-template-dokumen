<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Models\User;
use App\Services\RenjaMurniDocxService;
use App\Services\TemplatePersonalizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemplateRenjaE2EHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected $operatorDinkes;
    protected $operatorDisdik;
    protected $adminUser;
    protected $opdDinkes;
    protected $opdDisdik;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $templateService = app(\App\Services\DocumentTemplateService::class);
        $templateService->ensureStandardTemplatesSeeded();

        $this->opdDinkes = MasterOpd::create([
            'kode_opd' => '1.02.01',
            'nama_opd' => 'Dinas Kesehatan',
            'singkatan_opd' => 'Dinkes',
            'nomor_lampiran_romawi' => 'LAMPIRAN I',
        ]);

        $this->opdDisdik = MasterOpd::create([
            'kode_opd' => '1.01.01',
            'nama_opd' => 'Dinas Pendidikan',
            'singkatan_opd' => 'Disdik',
            'nomor_lampiran_romawi' => 'LAMPIRAN II',
        ]);

        $this->operatorDinkes = User::factory()->create([
            'nama_lengkap' => 'Operator Dinkes',
            'username_nip' => '198501012010011001',
            'email' => 'operator.dinkes@cirebonkab.go.id',
            'role' => 'operator',
            'opd_id' => $this->opdDinkes->id,
        ]);

        $this->operatorDisdik = User::factory()->create([
            'nama_lengkap' => 'Operator Disdik',
            'username_nip' => '198501012010011002',
            'email' => 'operator.disdik@cirebonkab.go.id',
            'role' => 'operator',
            'opd_id' => $this->opdDisdik->id,
        ]);

        $this->adminUser = User::factory()->create([
            'nama_lengkap' => 'Admin Bapperida',
            'username_nip' => '198001012005011001',
            'email' => 'admin.bapperida@cirebonkab.go.id',
            'role' => 'admin',
        ]);
    }

    private function createDocxWithXml(string $xmlContent): string
    {
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'e2e_docx_' . uniqid();
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

        return $zipPath;
    }

    /** @test */
    public function test_1_full_operator_to_admin_workflow_for_renja_murni()
    {
        // 1. Operator opens Workspace
        $response = $this->actingAs($this->operatorDinkes)
            ->get(route('renja.workspace', ['tahun_anggaran' => 2026]));
        $response->assertStatus(200);

        // 2. Download template RENJA_MURNI
        $downloadRes = $this->actingAs($this->operatorDinkes)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_MURNI',
                'tahun_anggaran' => 2027,
            ]));
        $downloadRes->assertStatus(200);
        $downloadRes->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        // 3. Simulate filled docx with custom content
        $xmlContent = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' .
            '<w:body>' .
            '<w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>ISI TEST OPD 2027 — JANGAN DIHAPUS. LATAR BELAKANG TEST 123.</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>BAB II HASIL EVALUASI RENJA PERANGKAT DAERAH TAHUN LALU</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>2.1 Evaluasi Pelaksanaan Renja Perangkat Daerah dan Capaian Renstra Perangkat Daerah</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>EVALUASI TEST 456.</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>BAB III TUJUAN DAN SASARAN PERANGKAT DAERAH</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>3.1 Telaahan Terhadap Kebijakan Daerah</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>TUJUAN TEST 789.</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>BAB IV RENCANA KERJA DAN PENDANAAN PERANGKAT DAERAH</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>4.1 Rencana Kerja dan Pendanaan Perangkat Daerah</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>PENDANAAN TEST 000.</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>BAB V PENUTUP</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>5.1 Kaidah Pelaksanaan</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>PENUTUP TEST 999.</w:t></w:r></w:p>' .
            '</w:body></w:document>';

        $docxPath = $this->createDocxWithXml($xmlContent);
        $file = new UploadedFile($docxPath, 'RENJA_MURNI_Dinkes_2027.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        // 4. Operator uploads document
        $uploadRes = $this->actingAs($this->operatorDinkes)
            ->post(route('operator.renja-murni.store-upload'), [
                'document_file' => $file,
                'jenis_dokumen' => 'RENJA Murni',
                'tahun_anggaran' => 2027,
            ]);
        $uploadRes->assertRedirect();

        $doc = RenjaDocument::where('opd_id', $this->opdDinkes->id)
            ->where('tahun_anggaran', 2027)
            ->latest()
            ->first();

        $this->assertNotNull($doc);
        $this->assertEquals('draft', $doc->status);

        // Verify content in database section
        $sec11 = $doc->sections()->where('sub_bab_code', '1.1')->first();
        $this->assertNotNull($sec11);
        $this->assertStringContainsString('ISI TEST OPD 2027 — JANGAN DIHAPUS', $sec11->content);

        // 5. Operator previews document (high-fidelity viewer page loads)
        $previewRes = $this->actingAs($this->operatorDinkes)
            ->get(route('renja.preview', $doc->id));
        $previewRes->assertStatus(200);

        // 6. Operator submits document
        $submitRes = $this->actingAs($this->operatorDinkes)
            ->post(route('renja.submit', $doc->id));
        $submitRes->assertRedirect();

        $doc->refresh();
        $this->assertEquals('submitted', $doc->status);

        // 7. Admin/Verifikator views verification queue
        $adminRes = $this->actingAs($this->adminUser)
            ->get(route('admin.verifikasi.index'));
        $adminRes->assertStatus(200);
        $adminRes->assertSee('Dinas Kesehatan');

        // 8. Admin reviews document
        $reviewRes = $this->actingAs($this->adminUser)
            ->get(route('admin.verifikasi.review', $doc->id));
        $reviewRes->assertStatus(200);
        $reviewRes->assertSee('LATAR BELAKANG TEST 123');

        @unlink($docxPath);
    }

    /** @test */
    public function test_2_full_operator_to_admin_workflow_for_renja_perubahan()
    {
        // 1. Download template RENJA_PERUBAHAN
        $downloadRes = $this->actingAs($this->operatorDinkes)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_PERUBAHAN',
                'tahun_anggaran' => 2027,
            ]));
        $downloadRes->assertStatus(200);

        // 2. Simulate filled docx for Perubahan
        $xmlContent = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' .
            '<w:body>' .
            '<w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>PERUBAHAN LATAR BELAKANG 2027 TEST.</w:t></w:r></w:p>' .
            '</w:body></w:document>';

        $docxPath = $this->createDocxWithXml($xmlContent);
        $file = new UploadedFile($docxPath, 'RENJA_PERUBAHAN_Dinkes_2027.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        // 3. Operator uploads Perubahan document
        $uploadRes = $this->actingAs($this->operatorDinkes)
            ->post(route('operator.renja-murni.store-upload'), [
                'document_file' => $file,
                'jenis_dokumen' => 'RENJA Perubahan',
                'tahun_anggaran' => 2027,
            ]);
        $uploadRes->assertRedirect();

        $doc = RenjaDocument::where('opd_id', $this->opdDinkes->id)
            ->where('tahun_anggaran', 2027)
            ->latest()
            ->first();

        $this->assertNotNull($doc);
        $this->assertEquals('draft', $doc->status);

        // 4. Submit Perubahan document
        $submitRes = $this->actingAs($this->operatorDinkes)
            ->post(route('renja.submit', $doc->id));
        $submitRes->assertRedirect();

        $doc->refresh();
        $this->assertEquals('submitted', $doc->status);

        @unlink($docxPath);
    }

    /** @test */
    public function test_3_content_preservation_across_all_chapters_without_loss()
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' .
            '<w:body>' .
            '<w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>LATAR BELAKANG TEST 123</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>BAB II HASIL EVALUASI RENJA PERANGKAT DAERAH TAHUN LALU</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>2.1 Evaluasi Pelaksanaan Renja Perangkat Daerah dan Capaian Renstra Perangkat Daerah</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>EVALUASI TEST 456</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>BAB III TUJUAN DAN SASARAN PERANGKAT DAERAH</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>3.1 Telaahan Terhadap Kebijakan Daerah</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>TUJUAN TEST 789</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>BAB IV RENCANA KERJA DAN PENDANAAN PERANGKAT DAERAH</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>4.1 Rencana Kerja dan Pendanaan Perangkat Daerah</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>PENDANAAN TEST 000</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>BAB V PENUTUP</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>5.1 Kaidah Pelaksanaan</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>PENUTUP TEST 999</w:t></w:r></w:p>' .
            '</w:body></w:document>';

        $docxPath = $this->createDocxWithXml($xmlContent);
        $file = new UploadedFile($docxPath, 'RENJA_ALL_BAB_TEST.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $this->actingAs($this->operatorDinkes)
            ->post(route('operator.renja-murni.store-upload'), [
                'document_file' => $file,
                'jenis_dokumen' => 'RENJA Murni',
                'tahun_anggaran' => 2027,
            ]);

        $doc = RenjaDocument::where('opd_id', $this->opdDinkes->id)->first();
        $this->assertNotNull($doc);

        $sections = RenjaSection::where('document_id', $doc->id)->get();

        $sec1 = $sections->firstWhere('sub_bab_code', '1.1');
        $this->assertNotNull($sec1);
        $this->assertStringContainsString('LATAR BELAKANG TEST 123', $sec1->content);

        $sec2 = $sections->firstWhere('sub_bab_code', '2.1');
        $this->assertNotNull($sec2);
        $this->assertStringContainsString('EVALUASI TEST 456', $sec2->content);

        $sec3 = $sections->firstWhere('sub_bab_code', '3.1');
        $this->assertNotNull($sec3);
        $this->assertStringContainsString('TUJUAN TEST 789', $sec3->content);

        $sec4 = $sections->firstWhere('sub_bab_code', '4.1');
        $this->assertNotNull($sec4);
        $this->assertStringContainsString('PENDANAAN TEST 000', $sec4->content);

        $sec5 = $sections->firstWhere('sub_bab_code', '5.1');
        $this->assertNotNull($sec5);
        $this->assertStringContainsString('PENUTUP TEST 999', $sec5->content);

        @unlink($docxPath);
    }

    /** @test */
    public function test_4_table_data_preservation_with_exact_values()
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' .
            '<w:body>' .
            '<w:p><w:r><w:t>BAB IV RENCANA KERJA DAN PENDANAAN PERANGKAT DAERAH</w:t></w:r></w:p>' .
            '<w:p><w:r><w:t>4.2 Matriks Rencana Kerja dan Pendanaan Perangkat Daerah</w:t></w:r></w:p>' .
            '<w:tbl>' .
            '<w:tr>' .
            '<w:tc><w:p><w:r><w:t>Program</w:t></w:r></w:p></w:tc>' .
            '<w:tc><w:p><w:r><w:t>Kegiatan</w:t></w:r></w:p></w:tc>' .
            '<w:tc><w:p><w:r><w:t>Pagu Anggaran</w:t></w:r></w:p></w:tc>' .
            '</w:tr>' .
            '<w:tr>' .
            '<w:tc><w:p><w:r><w:t>PROGRAM TEST A</w:t></w:r></w:p></w:tc>' .
            '<w:tc><w:p><w:r><w:t>KEGIATAN TEST B</w:t></w:r></w:p></w:tc>' .
            '<w:tc><w:p><w:r><w:t>123456789</w:t></w:r></w:p></w:tc>' .
            '</w:tr>' .
            '</w:tbl>' .
            '</w:body></w:document>';

        $docxPath = $this->createDocxWithXml($xmlContent);
        $file = new UploadedFile($docxPath, 'RENJA_TABLE_TEST.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $this->actingAs($this->operatorDinkes)
            ->post(route('operator.renja-murni.store-upload'), [
                'document_file' => $file,
                'jenis_dokumen' => 'RENJA Murni',
                'tahun_anggaran' => 2027,
            ]);

        $doc = RenjaDocument::where('opd_id', $this->opdDinkes->id)->first();
        $this->assertNotNull($doc);

        $sec42 = RenjaSection::where('document_id', $doc->id)
            ->where('sub_bab_code', '4.2')
            ->first();

        $this->assertNotNull($sec42);
        $this->assertStringContainsString('PROGRAM TEST A', $sec42->content);
        $this->assertStringContainsString('KEGIATAN TEST B', $sec42->content);
        $this->assertStringContainsString('123456789', $sec42->content);
        $this->assertStringContainsString('<table', $sec42->content);

        @unlink($docxPath);
    }

    /** @test */
    public function test_5_opd_isolation_and_idor_protection()
    {
        // Operator Dinkes tries to upload by passing Disdik's opd_id in payload
        $xmlContent = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' .
            '<w:body><w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p></w:body></w:document>';

        $docxPath = $this->createDocxWithXml($xmlContent);
        $file = new UploadedFile($docxPath, 'RENJA_SPOOF_TEST.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $this->actingAs($this->operatorDinkes)
            ->post(route('operator.renja-murni.store-upload'), [
                'document_file' => $file,
                'jenis_dokumen' => 'RENJA Murni',
                'tahun_anggaran' => 2027,
                'opd_id' => $this->opdDisdik->id, // Attempt to inject Disdik OPD ID
            ]);

        // Verify document is strictly created under Dinkes (auth user), NOT Disdik
        $docDinkes = RenjaDocument::where('opd_id', $this->opdDinkes->id)->first();
        $docDisdik = RenjaDocument::where('opd_id', $this->opdDisdik->id)->first();

        $this->assertNotNull($docDinkes, 'Document must be bound to auth user OPD');
        $this->assertNull($docDisdik, 'Document must NEVER be bound to other OPD via request param');

        @unlink($docxPath);
    }

    /** @test */
    public function test_6_revision_workflow_perlu_revisi()
    {
        // 1. Create and submit document
        $doc = RenjaDocument::create([
            'opd_id' => $this->opdDinkes->id,
            'jenis_dokumen' => 'RENJA Murni',
            'tahun_anggaran' => 2027,
            'status' => 'submitted',
        ]);

        // 2. Admin rejects/requests revision
        $reviewRes = $this->actingAs($this->adminUser)
            ->post(route('admin.verifikasi.decision', $doc->id), [
                'decision_type' => 'minta_revisi',
                'catatan_bapperida' => 'Mohon lengkapi BAB II Evaluasi.',
            ]);
        $reviewRes->assertRedirect(route('admin.verifikasi.index'));

        $doc->refresh();
        $this->assertEquals('perlu_revisi', $doc->status);

        // 3. Operator views workspace
        $workspaceRes = $this->actingAs($this->operatorDinkes)
            ->get(route('renja.workspace', ['tahun_anggaran' => 2026]));
        $workspaceRes->assertStatus(200);
    }

    /** @test */
    public function test_7_error_handling_for_non_docx_file()
    {
        $txtFile = UploadedFile::fake()->create('document.txt', 100, 'text/plain');
        $resTxt = $this->actingAs($this->operatorDinkes)
            ->from(route('renja.workspace', ['tahun_anggaran' => 2026]))
            ->post(route('operator.renja-murni.store-upload'), [
                'document_file' => $txtFile,
                'jenis_dokumen' => 'RENJA Murni',
                'tahun_anggaran' => 2027,
            ]);
        $resTxt->assertSessionHas('error');
    }

    /** @test */
    public function test_8_error_handling_for_corrupt_docx_file_uses_resilient_fallback()
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'corrupt_') . '.docx';
        file_put_contents($tmpFile, 'This is definitely not a valid zip archive');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File DOCX tidak valid atau rusak');

        try {
            $docxService = app(RenjaMurniDocxService::class);
            $docxService->importDocx($tmpFile, $this->opdDinkes->id, 2027, 'RENJA Murni');
        } finally {
            @unlink($tmpFile);
        }
    }
}
