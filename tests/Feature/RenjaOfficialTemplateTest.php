<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\TemplateSection;
use App\Models\User;
use App\Services\DocumentTemplateService;
use App\Services\OpdDocumentService;
use App\Services\TemplatePersonalizerService;
use App\Services\RenjaMurniDocxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RenjaOfficialTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected User $operatorUser;
    protected MasterOpd $opd;
    protected MasterOpd $otherOpd;
    protected DocumentTemplateService $templateService;
    protected TemplatePersonalizerService $personalizerService;
    protected RenjaMurniDocxService $docxService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateService = app(DocumentTemplateService::class);
        $this->templateService->ensureStandardTemplatesSeeded();
        $this->personalizerService = app(TemplatePersonalizerService::class);
        $this->docxService = app(RenjaMurniDocxService::class);

        $this->opd = MasterOpd::create([
            'kode_opd' => '1.02.01',
            'nama_opd' => 'Dinas Kesehatan Kabupaten Cirebon',
            'lampiran_number' => 2,
            'nomor_lampiran_romawi' => 'Lampiran II',
        ]);

        $this->otherOpd = MasterOpd::create([
            'kode_opd' => '1.03.01',
            'nama_opd' => 'Dinas Pekerjaan Umum dan Penataan Ruang',
            'lampiran_number' => 3,
            'nomor_lampiran_romawi' => 'Lampiran III',
        ]);

        $this->operatorUser = User::factory()->create([
            'username_nip' => '198801012015011002',
            'opd_id' => $this->opd->id,
            'role' => 'operator',
        ]);
    }

    /** @test */
    public function it_provides_official_renja_murni_and_perubahan_templates_for_download()
    {
        // 1. Download Template RENJA Murni
        $initialDocCount = RenjaDocument::count();

        $responseMurni = $this->actingAs($this->operatorUser)
            ->get(route('renja.templates.download', ['templateCode' => 'RENJA_MURNI', 'tahun_anggaran' => 2027]));

        $responseMurni->assertStatus(200);
        $responseMurni->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        // Pastikan download TIDAK membuat record dokumen di database
        $this->assertEquals($initialDocCount, RenjaDocument::count(), 'Download template tidak boleh membuat record dokumen di database.');

        // 2. Download Template RENJA Perubahan
        $responsePerubahan = $this->actingAs($this->operatorUser)
            ->get(route('renja.templates.download', ['templateCode' => 'RENJA_PERUBAHAN', 'tahun_anggaran' => 2026]));

        $responsePerubahan->assertStatus(200);
        $responsePerubahan->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertEquals($initialDocCount, RenjaDocument::count(), 'Download template tidak boleh membuat record dokumen di database.');
    }

    /** @test */
    public function it_protects_against_cross_opd_tampering_during_template_download()
    {
        // Operator OPD A mencoba mendownload template dengan parameter opd_id milik OPD B
        $response = $this->actingAs($this->operatorUser)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_MURNI',
                'opd_id' => $this->otherOpd->id,
            ]));

        // Harus ditolak dengan status 403 Forbidden
        $response->assertStatus(403);
    }

    /** @test */
    public function it_personalizes_template_with_authenticated_opd_data_without_modifying_master_template()
    {
        $masterMurni = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $initialMasterSectionCount = $masterMurni->sections()->count();

        $result = $this->personalizerService->generateBlankTemplateDocx('RENJA_MURNI', [
            'opd_id' => $this->opd->id,
            'opd_name' => $this->opd->nama_opd,
            'tahun_anggaran' => 2027,
        ]);

        $this->assertTrue($result['success']);
        $this->assertFileExists($result['file_path']);
        $this->assertGreaterThan(5000, $result['file_size']);

        // Pastikan Master Template tetap bersih dan tidak bertambah seksi
        $masterMurni->refresh();
        $this->assertEquals($initialMasterSectionCount, $masterMurni->sections()->count());

        if (file_exists($result['file_path'])) {
            @unlink($result['file_path']);
        }
    }

    /** @test */
    public function it_imports_docx_preserving_narrative_markers_tables_and_bab_structure()
    {
        Storage::fake('local');

        $tempDir = storage_path('app/temp_docs');
        if (!file_exists($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        $tempDocx = $tempDir . '/test_renja_import_' . time() . '.docx';
        $jsonPayloadPath = $tempDir . '/test_payload_' . time() . '.json';

        $payload = [
            'document_type' => 'RENJA_MURNI',
            'title' => 'RENJA Murni',
            'opd_name' => $this->opd->nama_opd,
            'tahun_anggaran' => 2027,
            'nomor_lampiran_romawi' => 'Lampiran II',
            'cover_data' => [
                'judul_dokumen' => 'RENCANA KERJA (RENJA) MURNI TAHUN ANGGARAN 2027',
                'nama_opd' => $this->opd->nama_opd,
                'tahun_anggaran' => 2027,
            ],
            'sections' => [
                ['bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.1', 'sub_bab_title' => 'Latar Belakang', 'content' => '<p>MARKER_NARASI_001: Uraian Latar Belakang Dinas Kesehatan TA 2027.</p>'],
                ['bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.2', 'sub_bab_title' => 'Landasan Hukum', 'content' => '<p>MARKER_NARASI_002: Landasan hukum UU No. 17 Tahun 2023 tentang Kesehatan.</p>'],
                ['bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.3', 'sub_bab_title' => 'Maksud dan Tujuan', 'content' => '<p>Maksud dan tujuan penyusunan Renja.</p>'],
                ['bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.4', 'sub_bab_title' => 'Sistematika Penulisan', 'content' => '<p>Sistematika penulisan dokumen.</p>'],
                ['bab_code' => 'BAB II', 'bab_title' => 'Hasil Evaluasi Renja Perangkat Daerah Tahun Lalu', 'sub_bab_code' => '2.1', 'sub_bab_title' => 'Evaluasi Pelaksanaan Renja', 'content' => '<p>TABLE_MARKER_001: Evaluasi Kinerja dan Realisasi Program Kesehatan.</p>'],
                ['bab_code' => 'BAB III', 'bab_title' => 'Tujuan dan Sasaran Perangkat Daerah', 'sub_bab_code' => '3.1', 'sub_bab_title' => 'Telaahan Kebijakan', 'content' => '<p>Tujuan dan sasaran strategis bidang kesehatan.</p>'],
                ['bab_code' => 'BAB IV', 'bab_title' => 'Rencana Kerja dan Pendanaan', 'sub_bab_code' => '4.1', 'sub_bab_title' => 'Rencana Kerja', 'content' => '<p>Rencana program dan alokasi pagu tahun 2027.</p>'],
                ['bab_code' => 'BAB V', 'bab_title' => 'Penutup', 'sub_bab_code' => '5.1', 'sub_bab_title' => 'Kaidah Pelaksanaan', 'content' => '<p>Kaidah penutup pelaksanaan Renja.</p>'],
            ],
        ];

        file_put_contents($jsonPayloadPath, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $pythonScript = base_path('scripts/generate_renja_docx.py');
        $cmd = sprintf('python %s %s %s 2>&1', escapeshellarg($pythonScript), escapeshellarg($jsonPayloadPath), escapeshellarg($tempDocx));
        exec($cmd);

        @unlink($jsonPayloadPath);

        $this->assertFileExists($tempDocx, 'File test docx harus berhasil dibuat via python generator.');

        $uploadedFile = new UploadedFile($tempDocx, 'RENJA_Murni_Dinkes_2027.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        // Upload via Controller
        $response = $this->actingAs($this->operatorUser)
            ->post(route('operator.renja-murni.store-upload'), [
                'tahun_anggaran' => 2027,
                'jenis_dokumen' => 'RENJA Murni',
                'document_file' => $uploadedFile,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Ambil dokumen yang terbuat
        $document = RenjaDocument::where('opd_id', $this->opd->id)
            ->where('tahun_anggaran', 2027)
            ->where('jenis_dokumen', 'RENJA Murni')
            ->first();

        $this->assertNotNull($document, 'Dokumen hasil import harus tersimpan di database.');
        $this->assertEquals('upload_word', $document->source_type);

        // Cek bahwa seksi tersimpan dan marker terpelihara
        $sections = $document->sections;
        $this->assertGreaterThanOrEqual(5, $sections->count(), 'Harus memiliki minimal 5 seksi BAB/Sub-BAB.');

        $sec11 = $sections->first(fn($s) => $s->sub_bab_code === '1.1' || str_contains(strtolower($s->sub_bab_title ?? ''), 'latar belakang'));
        $this->assertNotNull($sec11, 'Seksi 1.1 Latar Belakang harus berhasil dipetakan.');
        $this->assertStringContainsString('MARKER_NARASI_001', $sec11->content);

        $sec12 = $sections->first(fn($s) => $s->sub_bab_code === '1.2' || str_contains(strtolower($s->sub_bab_title ?? ''), 'landasan hukum'));
        $this->assertNotNull($sec12, 'Seksi 1.2 Landasan Hukum harus berhasil dipetakan.');
        $this->assertStringContainsString('MARKER_NARASI_002', $sec12->content);

        // Cek bahwa Tabel dan Marker Tabel terpelihara
        $sec21 = $sections->first(fn($s) => $s->sub_bab_code === '2.1');
        $this->assertNotNull($sec21, 'Seksi 2.1 Evaluasi Kinerja harus berhasil dipetakan.');
        $this->assertStringContainsString('TABLE_MARKER_001', $sec21->content);

        // Pastikan BAB I sampai BAB V semua ada
        $babCodes = $sections->pluck('bab_code')->unique()->map(fn($b) => strtoupper(trim($b)))->toArray();
        $this->assertContains('BAB I', $babCodes);
        $this->assertContains('BAB II', $babCodes);
        $this->assertContains('BAB III', $babCodes);
        $this->assertContains('BAB IV', $babCodes);
        $this->assertContains('BAB V', $babCodes);

        // Pastikan Master Template tetap bersih dan tidak termutasi
        $masterTemplate = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $this->assertNotNull($masterTemplate);
        $this->assertEquals('Template RENJA Murni', $masterTemplate->name);

        if (file_exists($tempDocx)) {
            @unlink($tempDocx);
        }
    }
}
