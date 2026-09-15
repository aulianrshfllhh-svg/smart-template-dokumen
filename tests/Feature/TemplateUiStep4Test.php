<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Services\DocumentTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TemplateUiStep4Test extends TestCase
{
    use RefreshDatabase;

    protected DocumentTemplateService $templateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateService = app(DocumentTemplateService::class);
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
     * TEST 1: Operator dapat membuka Renja Workspace (HTTP 200).
     */
    public function test_1_operator_can_open_renja_workspace(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $response = $this->actingAs($operator)
            ->get(route('renja.workspace', ['tahun_anggaran' => 2026]));

        $response->assertStatus(200);
        $response->assertSee('Workspace RENJA');
        $response->assertSee($opd->nama_opd);
    }

    /**
     * TEST 2: Modal "Pilih Metode Pembuatan RENJA" tetap muncul di view.
     */
    public function test_2_modal_pilih_metode_pembuatan_renja_exists(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $response = $this->actingAs($operator)
            ->get(route('renja.workspace', ['tahun_anggaran' => 2026]));

        $response->assertStatus(200);
        $response->assertSee('Template Resmi RENJA');
        $response->assertSee('Upload Dokumen RENJA');
        $response->assertSee('Gunakan Template Resmi');
    }

    /**
     * TEST 3: RENJA Murni memiliki tombol/link download template Word.
     */
    public function test_3_renja_murni_has_template_download_link(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $response = $this->actingAs($operator)
            ->get(route('renja.workspace', ['tahun_anggaran' => 2026]));

        $response->assertStatus(200);
        
        $murniDownloadUrl = route('renja.templates.download', [
            'templateCode' => 'RENJA_MURNI',
            'tahun_anggaran' => 2027,
        ]);

        $response->assertSee($murniDownloadUrl, false);
        $response->assertSee('Download Template Word');
    }

    /**
     * TEST 4: RENJA Perubahan memiliki tombol/link download template Word.
     */
    public function test_4_renja_perubahan_has_template_download_link(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        // Approve Murni so Perubahan is active
        RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'RENJA Murni',
            'tahun_anggaran' => 2026,
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($operator)
            ->get(route('renja.workspace', ['tahun_anggaran' => 2026]));

        $response->assertStatus(200);

        $perubahanDownloadUrl = route('renja.templates.download', [
            'templateCode' => 'RENJA_PERUBAHAN',
            'tahun_anggaran' => 2026,
        ]);

        $response->assertSee($perubahanDownloadUrl, false);
    }

    /**
     * TEST 5: Button mengarah ke route download yang benar dan mengembalikan file DOCX.
     */
    public function test_5_button_routes_to_correct_endpoint_and_downloads_docx(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        // Request Murni template
        $responseMurni = $this->actingAs($operator)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_MURNI',
                'tahun_anggaran' => 2027,
            ]));

        $responseMurni->assertStatus(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            (string) $responseMurni->headers->get('content-type')
        );

        // Request Perubahan template
        $responsePerubahan = $this->actingAs($operator)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_PERUBAHAN',
                'tahun_anggaran' => 2026,
            ]));

        $responsePerubahan->assertStatus(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            (string) $responsePerubahan->headers->get('content-type')
        );
    }

    /**
     * TEST 6: Operator tidak diberikan pilihan OPD (tidak ada <select name="opd_id">).
     */
    public function test_6_operator_does_not_have_opd_selector_in_modal(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $response = $this->actingAs($operator)
            ->get(route('renja.workspace', ['tahun_anggaran' => 2026]));

        $response->assertStatus(200);
        $response->assertDontSee('name="opd_id"', false);
        $response->assertDontSee('Pilih OPD', false);
    }

    /**
     * TEST 7: Tahun anggaran yang digunakan sesuai context workspace.
     */
    public function test_7_tahun_anggaran_matches_workspace_context(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $ta = 2028;
        $response = $this->actingAs($operator)
            ->get(route('renja.workspace', ['tahun_anggaran' => $ta]));

        $response->assertStatus(200);

        // RENJA Murni for TA 2028 is for TA 2029
        $expectedMurniUrl = route('renja.templates.download', [
            'templateCode' => 'RENJA_MURNI',
            'tahun_anggaran' => $ta + 1,
        ]);
        $response->assertSee($expectedMurniUrl, false);

        // RENJA Perubahan for TA 2028 is for TA 2028
        $expectedPerubahanUrl = route('renja.templates.download', [
            'templateCode' => 'RENJA_PERUBAHAN',
            'tahun_anggaran' => $ta,
        ]);
        $response->assertSee($expectedPerubahanUrl, false);
    }

    /**
     * TEST 8: Upload dokumen existing tetap tersedia.
     */
    public function test_8_upload_dokumen_existing_remains_available(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $response = $this->actingAs($operator)
            ->get(route('renja.workspace', ['tahun_anggaran' => 2026]));

        $response->assertStatus(200);
        $response->assertSee(route('operator.renja-murni.store-upload'));
        $response->assertSee('document_file');
        $response->assertSee('Upload Dokumen');
    }

    /**
     * TEST 9: Existing workspace tests/routes remain valid and functional.
     */
    public function test_9_existing_workspace_navigation_and_actions(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $response = $this->actingAs($operator)
            ->get(route('renja.index'));

        $response->assertStatus(200);
    }

    /**
     * TEST 10: Existing export/download test tetap PASS.
     */
    public function test_10_existing_export_download_route_intact(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'RENJA Murni',
            'tahun_anggaran' => 2027,
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($operator)
            ->get(route('renja.exportWord', $doc->id));

        $response->assertStatus(200);
    }
}
