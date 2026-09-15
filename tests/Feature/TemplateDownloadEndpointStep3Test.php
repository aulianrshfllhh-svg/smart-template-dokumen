<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\DocumentTemplate;
use App\Models\RenjaDocument;
use App\Services\DocumentTemplateService;
use App\Services\WordExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TemplateDownloadEndpointStep3Test extends TestCase
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

    private function createAdmin(): User
    {
        $opd = MasterOpd::first() ?? $this->createOpd('BAPPERIDA KABUPATEN CIREBON');
        return User::factory()->create([
            'role' => 'admin',
            'opd_id' => $opd->id,
            'nama_lengkap' => 'Admin Test Bapperida',
            'username_nip' => '19850101' . rand(1000000000, 9999999999),
        ]);
    }

    /**
     * TEST 1: Authenticated Operator dapat download RENJA_MURNI.
     */
    public function test_authenticated_operator_can_download_renja_murni(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $response = $this->actingAs($operator)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_MURNI',
                'tahun_anggaran' => 2027,
            ]));

        $response->assertStatus(200);
    }

    /**
     * TEST 2: Authenticated Operator dapat download RENJA_PERUBAHAN.
     */
    public function test_authenticated_operator_can_download_renja_perubahan(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $response = $this->actingAs($operator)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_PERUBAHAN',
                'tahun_anggaran' => 2026,
            ]));

        $response->assertStatus(200);
    }

    /**
     * TEST 3: Response adalah file DOCX dengan Content-Type yang sesuai.
     */
    public function test_response_is_docx_mime_type(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $response = $this->actingAs($operator)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_MURNI',
                'tahun_anggaran' => 2027,
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    /**
     * TEST 4: Filename benar sesuai format standar.
     */
    public function test_download_filename_format_is_correct(): void
    {
        $opd = $this->createOpd('DINAS KESEHATAN');
        $operator = $this->createOperator($opd);

        $response = $this->actingAs($operator)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_MURNI',
                'tahun_anggaran' => 2027,
            ]));

        $response->assertStatus(200);
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('RENJA_MURNI_DINAS_KESEHATAN_2027.docx', $contentDisposition);
    }

    /**
     * TEST 5: OPD pada template sesuai dengan user yang login.
     */
    public function test_opd_on_template_matches_logged_in_user_opd(): void
    {
        $opd = $this->createOpd('DINAS LINGKUNGAN HIDUP');
        $operator = $this->createOperator($opd);

        $response = $this->actingAs($operator)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_MURNI',
                'tahun_anggaran' => 2027,
            ]));

        $response->assertStatus(200);
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('DINAS_LINGKUNGAN_HIDUP', $contentDisposition);
    }

    /**
     * TEST 6: Tahun anggaran diproses dengan benar sesuai input/default.
     */
    public function test_tahun_anggaran_is_handled_correctly(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        // Custom TA 2030
        $response = $this->actingAs($operator)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_MURNI',
                'tahun_anggaran' => 2030,
            ]));

        $response->assertStatus(200);
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('2030.docx', $contentDisposition);
    }

    /**
     * TEST 7: Operator tidak dapat meminta template milik OPD lain (IDOR check -> 403).
     */
    public function test_operator_cannot_request_other_opd_template(): void
    {
        $opdMy = $this->createOpd('DINAS SAYA');
        $opdOther = $this->createOpd('DINAS LAIN');
        $operator = $this->createOperator($opdMy);

        // Operator mencoba kirim query param opd_id milik OPD lain
        $response = $this->actingAs($operator)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_MURNI',
                'opd_id' => $opdOther->id,
                'tahun_anggaran' => 2027,
            ]));

        $response->assertStatus(403);
    }

    /**
     * TEST 8: Template inactive ditolak (HTTP 404).
     */
    public function test_inactive_template_is_rejected(): void
    {
        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $template->update(['is_active' => false]);

        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $response = $this->actingAs($operator)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_MURNI',
                'tahun_anggaran' => 2027,
            ]));

        $response->assertStatus(404);
    }

    /**
     * TEST 9: Template yang tidak valid / tidak ditemukan / tidak ada di whitelist ditolak (HTTP 404).
     */
    public function test_invalid_or_non_whitelisted_template_returns_404(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        // Template tidak ada
        $res1 = $this->actingAs($operator)
            ->get(route('renja.templates.download', [
                'templateCode' => 'TEMPLATE_TIDAK_ADA',
            ]));
        $res1->assertStatus(404);

        // Template Lampiran tidak boleh diunduh via endpoint template naratif ini
        $res2 = $this->actingAs($operator)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_LAMPIRAN_MURNI',
            ]));
        $res2->assertStatus(404);
    }

    /**
     * TEST 10: User tanpa OPD tidak dapat generate template Operator (HTTP 403).
     */
    public function test_user_without_opd_is_forbidden(): void
    {
        $userWithoutOpd = User::factory()->create([
            'role' => 'operator',
            'opd_id' => null,
            'nama_lengkap' => 'Operator No OPD',
            'username_nip' => '19990101' . rand(1000000000, 9999999999),
        ]);

        $response = $this->actingAs($userWithoutOpd)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_MURNI',
            ]));

        $response->assertStatus(403);
    }

    /**
     * TEST 11: Admin sesuai permission existing dapat generate template untuk OPD tertentu.
     */
    public function test_admin_can_generate_template_for_specified_opd(): void
    {
        $admin = $this->createAdmin();
        $targetOpd = $this->createOpd('DINAS PEKERJAAN UMUM');

        $response = $this->actingAs($admin)
            ->get(route('renja.templates.download', [
                'templateCode' => 'RENJA_MURNI',
                'opd_id' => $targetOpd->id,
                'tahun_anggaran' => 2027,
            ]));

        $response->assertStatus(200);
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('DINAS_PEKERJAAN_UMUM', $contentDisposition);
    }

    /**
     * TEST 12: Existing export-word tetap PASS dan fungsional.
     */
    public function test_existing_export_word_remains_functional(): void
    {
        $opd = $this->createOpd();
        $operator = $this->createOperator($opd);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Renja Murni',
            'tahun_anggaran' => 2027,
            'status' => 'draft',
            'latar_belakang' => 'Testing latar belakang',
        ]);
        $this->templateService->provisionDocumentSections($doc, 'RENJA_MURNI');

        $response = $this->actingAs($operator)
            ->get(route('renja.exportWord', $doc->id));

        $response->assertStatus(200);
    }
}
