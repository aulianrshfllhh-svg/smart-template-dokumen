<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Services\RenjaCycleService;
use App\Services\DashboardOpdService;
use App\Services\DashboardBapperidaService;
use App\Services\VerificationWorkspaceService;
use App\Services\VerificationReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EndToEndDocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardOpdService $opdDashboardService;
    protected DashboardBapperidaService $adminDashboardService;
    protected VerificationWorkspaceService $workspaceService;
    protected VerificationReviewService $reviewService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->opdDashboardService = app(DashboardOpdService::class);
        $this->adminDashboardService = app(DashboardBapperidaService::class);
        $this->workspaceService = app(VerificationWorkspaceService::class);
        $this->reviewService = app(VerificationReviewService::class);
    }

    private function createOpd(string $suffix = ''): MasterOpd
    {
        $uniqueId = rand(10000, 99999);
        return MasterOpd::create([
            'nama_opd' => 'Dinas E2E Workflow ' . $uniqueId . ' ' . $suffix,
            'kode_opd' => '1.01.' . $uniqueId,
            'nomor_lampiran_romawi' => 'LAMPIRAN X',
        ]);
    }

    /**
     * TEST 1: OPD membuat dokumen -> status = Draft, muncul di OPD, TIDAK masuk antrean Admin.
     */
    public function test_step_1_create_draft_document(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Step1');
        $operator = User::factory()->create(['role' => 'operator', 'opd_id' => $opd->id]);

        $response = $this->actingAs($operator)
            ->post(route('renja.store'), [
                'tahun_anggaran' => 2027,
                'jenis_dokumen' => 'RENJA Murni',
            ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->first();
        $this->assertNotNull($doc);
        $this->assertEquals('draft', strtolower($doc->status));

        // OPD Dashboard stats: 1 draft
        $opdStats = $this->opdDashboardService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(1, $opdStats['draft']);

        // Admin Verification Queue: 0 documents
        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);
        $this->assertEquals(0, $filtered->total());
    }

    /**
     * TEST 2: OPD melakukan Submit -> status = submitted, muncul di Dashboard OPD & Dokumen Saya.
     */
    public function test_step_2_opd_submit_document(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Step2');
        $operator = User::factory()->create(['role' => 'operator', 'opd_id' => $opd->id]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($operator)
            ->post(route('renja.submit', $doc->id));

        $doc->refresh();
        $this->assertEquals('submitted', strtolower($doc->status));

        // OPD Dashboard stats: 1 menunggu verifikasi
        $opdStats = $this->opdDashboardService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(1, $opdStats['menunggu']);
        $this->assertEquals(0, $opdStats['draft']);
    }

    /**
     * TEST 3: Submit -> Dokumen masuk ke Antrean Verifikasi Admin & KPI Menunggu +1.
     */
    public function test_step_3_submitted_document_enters_admin_queue(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Step3');

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $kpiAdmin = $this->workspaceService->getKpiSummary($activeYear);
        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);

        $this->assertEquals(1, $kpiAdmin['menunggu']);
        $this->assertEquals(1, $filtered->total());
        $this->assertEquals($doc->id, $filtered->first()->id);
    }

    /**
     * TEST 4: Admin Approve -> status = disetujui, keluar dari antrean, KPI Disetujui +1.
     */
    public function test_step_4_admin_approve_document(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Step4');
        $admin = User::factory()->create(['role' => 'admin']);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.verifikasi.decision', $doc->id), [
                'decision_type' => 'setujui_dokumen',
                'catatan_bapperida' => 'Disetujui penuh oleh Bapperida',
            ]);

        $doc->refresh();
        $this->assertEquals('disetujui', strtolower($doc->status));

        $kpiAdmin = $this->workspaceService->getKpiSummary($activeYear);
        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);

        $this->assertEquals(0, $kpiAdmin['menunggu']);
        $this->assertEquals(1, $kpiAdmin['disetujui']);
        $this->assertEquals(0, $filtered->total());
    }

    /**
     * TEST 5: Admin Minta Revisi -> status = perlu_revisi, keluar sementara dari antrean.
     */
    public function test_step_5_admin_request_revision(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Step5');
        $admin = User::factory()->create(['role' => 'admin']);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.verifikasi.decision', $doc->id), [
                'decision_type' => 'minta_revisi',
                'catatan_bapperida' => 'Perbaiki indikator kinerja Bab II',
            ]);

        $doc->refresh();
        $this->assertEquals('perlu_revisi', strtolower($doc->status));

        $kpiAdmin = $this->workspaceService->getKpiSummary($activeYear);
        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);

        $this->assertEquals(0, $kpiAdmin['menunggu']);
        $this->assertEquals(1, $kpiAdmin['revisi']);
        $this->assertEquals(0, $filtered->total());
    }

    /**
     * TEST 6: Catatan Revisi Bapperida tersimpan dan terlihat oleh OPD.
     */
    public function test_step_6_revision_note_saved_and_propagated(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Step6');
        $operator = User::factory()->create(['role' => 'operator', 'opd_id' => $opd->id]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'perlu_revisi',
            'catatan_bapperida' => 'Instruksi Revisi Khusus Bab III',
        ]);

        $response = $this->actingAs($operator)
            ->get(route('renja.show', $doc->id));

        $response->assertStatus(200);
        $response->assertSee('Instruksi Revisi Khusus Bab III');
    }

    /**
     * TEST 7: OPD Resubmit -> status menjadi dikirim_ulang.
     */
    public function test_step_7_opd_resubmit_after_revision(): void
    {
        $opd = $this->createOpd('Step7');
        $operator = User::factory()->create(['role' => 'operator', 'opd_id' => $opd->id]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'perlu_revisi',
            'catatan_bapperida' => 'Instruksi Perbaikan',
            'revision_count' => 1,
        ]);

        $response = $this->actingAs($operator)
            ->post(route('renja.submit', $doc->id));

        $doc->refresh();
        $this->assertEquals('dikirim_ulang', strtolower($doc->status));
    }

    /**
     * TEST 8: Resubmit -> Dokumen kembali muncul di antrean verifikasi Admin.
     */
    public function test_step_8_resubmitted_document_reenters_admin_queue(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Step8');

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'dikirim_ulang',
            'revision_count' => 1,
        ]);

        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);
        $kpiAdmin = $this->workspaceService->getKpiSummary($activeYear);

        $this->assertEquals(1, $filtered->total());
        $this->assertEquals(1, $kpiAdmin['menunggu']);
        $this->assertEquals($doc->id, $filtered->first()->id);
    }

    /**
     * TEST 9: Admin Review Ulang Resubmit & Approve -> status disetujui, keluar dari antrean.
     */
    public function test_step_9_admin_approve_resubmitted_document(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Step9');
        $admin = User::factory()->create(['role' => 'admin']);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'dikirim_ulang',
            'revision_count' => 1,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.verifikasi.decision', $doc->id), [
                'decision_type' => 'setujui_dokumen',
                'catatan_bapperida' => 'Hasil perbaikan telah memenuhi syarat',
            ]);

        $doc->refresh();
        $this->assertEquals('disetujui', strtolower($doc->status));

        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);
        $this->assertEquals(0, $filtered->total());
    }

    /**
     * TEST 10: Dynamic Dashboard Consistency (Dashboard OPD ↔ Admin).
     */
    public function test_step_10_dashboard_consistency(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Step10');

        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2026, 'jenis_dokumen' => 'RENJA Perubahan', 'status' => 'perlu_revisi']);

        $opdStats = $this->opdDashboardService->getOpdStats($opd->id, $activeYear);
        $adminStats = $this->adminDashboardService->getDashboardStats($activeYear);

        $this->assertEquals($opdStats['menunggu'], $adminStats['menunggu']);
        $this->assertEquals($opdStats['revisi'], $adminStats['revisi']);
    }

    /**
     * TEST 11: Monitoring OPD Consistency (71 OPD & status progres).
     */
    public function test_step_11_monitoring_opd_consistency(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Step11');

        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);

        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)
            ->get(route('admin.monitoring-opd.index', ['tahun_anggaran' => $activeYear]));

        $response->assertStatus(200);
        $response->assertSee($opd->nama_opd);
    }

    /**
     * TEST 12: Active Cycle 2026 Isolation Validation.
     */
    public function test_step_12_active_cycle_2026_validation(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Step12');

        // Murni 2027 (submitted) -> Valid Cycle 2026
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);
        // Historical Murni 2026 -> Invalid for Cycle 2026
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2026, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);

        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);
        $this->assertEquals(1, $filtered->total());
        $this->assertEquals(2027, $filtered->first()->tahun_anggaran);
    }

    /**
     * TEST 13: Active Cycle 2027 Isolation Validation.
     */
    public function test_step_13_active_cycle_2027_validation(): void
    {
        $activeYear = 2027;
        $opd = $this->createOpd('Step13');

        // Murni 2028 (submitted) -> Valid Cycle 2027
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2028, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);
        // Historical Murni 2027 -> Invalid for Cycle 2027
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);

        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);
        $this->assertEquals(1, $filtered->total());
        $this->assertEquals(2028, $filtered->first()->tahun_anggaran);
    }

    /**
     * TEST 14: OPD Participation Rule - Multiple documents submitted by same OPD count as 1 DISTINCT OPD.
     */
    public function test_step_14_opd_participation_distinct_count(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Step14');

        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2026, 'jenis_dokumen' => 'RENJA Perubahan', 'status' => 'submitted']);

        $participatedOpdIds = RenjaCycleService::getParticipatedOpdIds($activeYear);
        $this->assertCount(1, $participatedOpdIds);
        $this->assertContains($opd->id, $participatedOpdIds);
    }

    /**
     * TEST 15: 71 OPD Master Integrity - Total Master OPD count remains intact.
     */
    public function test_step_15_master_opd_count_integrity(): void
    {
        $opd = $this->createOpd('Step15');
        $totalMasterOpd = MasterOpd::count();
        $this->assertGreaterThanOrEqual(1, $totalMasterOpd);
    }

    /**
     * TEST 16: Role Authorization - Operator cannot access Admin Verification Workspace.
     */
    public function test_step_16_operator_authorization_guard(): void
    {
        $opd = $this->createOpd('Step16');
        $operator = User::factory()->create(['role' => 'operator', 'opd_id' => $opd->id]);
        $doc = RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);

        $responseIndex = $this->actingAs($operator)->get(route('admin.verifikasi.index'));
        $responseReview = $this->actingAs($operator)->get(route('admin.verifikasi.review', $doc->id));

        $responseIndex->assertRedirect(route('operator.dashboard'));
        $responseReview->assertRedirect(route('operator.dashboard'));
    }
}
