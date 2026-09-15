<?php

namespace Tests\Feature;

use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\User;
use App\Services\OpdDocumentService;
use App\Services\RenjaFormatCheckerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenjaFormatCheckerTest extends TestCase
{
    use RefreshDatabase;

    protected User $operatorUser;
    protected MasterOpd $opdDepok;
    protected RenjaFormatCheckerService $checkerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opdDepok = MasterOpd::create([
            'kode_opd' => '9.01.038',
            'nama_opd' => 'Kecamatan Depok',
            'nomor_lampiran_romawi' => 'LAMPIRAN XXXVIII',
        ]);

        $this->operatorUser = User::factory()->create([
            'username_nip' => '199001012020011001',
            'opd_id' => $this->opdDepok->id,
            'role' => 'operator',
        ]);

        $this->checkerService = app(RenjaFormatCheckerService::class);
    }

    /**
     * TEST 1: Rute Format Checker Lampiran telah dihapus dan mengembalikan 404
     */
    public function test_format_checker_routes_are_removed_and_return_404(): void
    {
        $responseUpload = $this->actingAs($this->operatorUser)
            ->get('/renja-format-checker');
        $responseUpload->assertStatus(404);

        $responseCheck = $this->actingAs($this->operatorUser)
            ->post('/renja-format-checker/check', [
                'opd_id' => $this->opdDepok->id,
                'tahun_anggaran' => 2027,
            ]);
        $responseCheck->assertStatus(404);

        $responseReport = $this->actingAs($this->operatorUser)
            ->get('/renja-format-checker/report');
        $responseReport->assertStatus(404);
    }

    /**
     * TEST 2: UI Preview Dokumen tidak memuat tombol [Format Check]
     */
    public function test_preview_ui_does_not_contain_format_check_button(): void
    {
        $doc = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
            'latar_belakang' => 'Latar belakang renja murni depok.',
        ]);

        $response = $this->actingAs($this->operatorUser)
            ->get(route('renja.preview', $doc->id));

        $response->assertStatus(200);
        $response->assertDontSee('[Format Check]');
        $response->assertDontSee('/renja-format-checker');
    }

    /**
     * TEST 3: UI Dashboard Lampiran tidak memuat form upload DOCX atau format checker manual
     */
    public function test_lampiran_index_does_not_have_manual_format_checker_or_upload_form(): void
    {
        $response = $this->actingAs($this->operatorUser)
            ->get(route('renja.lampiran.index', ['tahun_anggaran' => 2027]));

        $response->assertStatus(200);
        $response->assertDontSee('Jalankan Format Checker');
        $response->assertDontSee('Pilih File Dokumen DOCX');
        $response->assertDontSee('Format Checker RENJA Lampiran');
    }

    /**
     * TEST 4: Format Checker Service internal inspection tetap valid
     */
    public function test_format_checker_service_detects_non_compliant_violations(): void
    {
        $dirtyText = '<h1>COVER DOKUMEN RENJA</h1><p><b>Paragraf Bold dengan font Arial 11pt</b></p><p>BAB I Pendahuluan</p>';
        $report = $this->checkerService->inspectContentText($dirtyText, 'RENJA_LAMPIRAN_MURNI');

        $this->assertEquals('Tidak Sesuai', $report['status']);
        $this->assertEquals('NON_COMPLIANT', $report['status_code']);
        $this->assertFalse($report['is_all_valid']);

        $boldItem = collect($report['items'])->firstWhere('id', 'bold');
        $this->assertNotNull($boldItem);
        $this->assertFalse($boldItem['is_valid']);
    }

    /**
     * TEST 5: RENJA Lampiran tetap menjadi output otomatis (live derived) dari dokumen Induk
     */
    public function test_lampiran_remains_automatic_live_output_from_induk(): void
    {
        $murni = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
            'latar_belakang' => 'Konten Induk Murni Asli',
        ]);

        $murni->sections()->create([
            'bab_code' => 'BAB I',
            'bab_title' => 'PENDAHULUAN',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'content' => 'Narasi dari Induk yang harus live sync ke Lampiran',
            'section_type' => 'content',
            'sort_order' => 1,
        ]);

        $opdService = app(OpdDocumentService::class);
        $lampiran = $opdService->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        $this->assertEquals('RENJA Lampiran Murni', $lampiran->jenis_dokumen);
        $this->assertEquals($murni->id, $lampiran->getParentDocument()->id);

        $effectiveSections = $lampiran->getEffectiveSections();
        $this->assertCount(1, $effectiveSections);
        $this->assertEquals('Narasi dari Induk yang harus live sync ke Lampiran', $effectiveSections->first()->content);
    }
}
