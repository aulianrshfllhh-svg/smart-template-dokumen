<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Services\VerificationWorkspaceService;
use App\Services\RenjaCycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VerificationWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected VerificationWorkspaceService $workspaceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workspaceService = app(VerificationWorkspaceService::class);
    }

    private function createOpd(string $suffix = ''): MasterOpd
    {
        $uniqueId = rand(10000, 99999);
        return MasterOpd::create([
            'nama_opd' => 'Dinas Verifikasi ' . $uniqueId . ' ' . $suffix,
            'kode_opd' => '1.01.' . $uniqueId,
            'nomor_lampiran_romawi' => 'LAMPIRAN IX',
        ]);
    }

    /**
     * TEST 1: Active Cycle TA 2026 - Hanya dokumen Active Cycle yang muncul di Antrean Verifikasi.
     */
    public function test_active_cycle_2026_verification_isolation(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('2026');

        // Valid Active Cycle 2026: RENJA Murni 2027 (submitted)
        $docMurni2027 = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        // Valid Active Cycle 2026: RENJA Perubahan 2026 (submitted)
        $docPerubahan2026 = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Perubahan',
            'status' => 'submitted',
        ]);

        // Historical Document: RENJA Murni 2026 (submitted from cycle 2025)
        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        // Future Document: RENJA Murni 2028 (submitted from cycle 2027)
        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2028,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);

        $this->assertEquals(2, $filtered->total());
        $documentIds = $filtered->pluck('id')->toArray();
        $this->assertContains($docMurni2027->id, $documentIds);
        $this->assertContains($docPerubahan2026->id, $documentIds);
    }

    /**
     * TEST 2: Active Cycle TA 2027 - Hanya dokumen Active Cycle 2027 yang muncul di Antrean Verifikasi.
     */
    public function test_active_cycle_2027_verification_isolation(): void
    {
        $activeYear = 2027;
        $opd = $this->createOpd('2027');

        // Valid Active Cycle 2027: RENJA Murni 2028 (submitted)
        $docMurni2028 = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2028,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        // Historical Document: RENJA Murni 2027 (submitted from cycle 2026)
        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);

        $this->assertEquals(1, $filtered->total());
        $this->assertEquals($docMurni2028->id, $filtered->first()->id);
    }

    /**
     * TEST 3: Dokumen status DRAFT tidak masuk ke antrean verifikasi.
     */
    public function test_draft_document_does_not_appear_in_verification_queue(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Draft');

        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);

        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);
        $kpi = $this->workspaceService->getKpiSummary($activeYear);

        $this->assertEquals(0, $filtered->total());
        $this->assertEquals(0, $kpi['menunggu']);
    }

    /**
     * TEST 4: Dokumen status SUBMITTED masuk ke antrean verifikasi.
     */
    public function test_submitted_document_appears_in_verification_queue(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Submitted');

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);
        $kpi = $this->workspaceService->getKpiSummary($activeYear);

        $this->assertEquals(1, $filtered->total());
        $this->assertEquals(1, $kpi['menunggu']);
        $this->assertEquals($doc->id, $filtered->first()->id);
    }

    /**
     * TEST 5: Keputusan APPROVE mengubah status menjadi disetujui dan keluar dari antrean.
     */
    public function test_approve_decision_locks_document_and_removes_from_queue(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Approve');
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

        $response->assertRedirect(route('admin.verifikasi.index'));

        $doc->refresh();
        $this->assertEquals('disetujui', strtolower($doc->status));
        $this->assertEquals('Disetujui penuh oleh Bapperida', $doc->catatan_bapperida);

        // Dokumen approved harus keluar dari antrean default (status=all)
        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);
        $this->assertEquals(0, $filtered->total());
    }

    /**
     * TEST 6: Keputusan REVISION mengubah status menjadi perlu_revisi, menyimpan catatan, dan keluar sementara dari antrean.
     */
    public function test_revision_decision_updates_status_and_saves_notes(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Revision');
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
                'catatan_bapperida' => 'Tolong perbaiki indikator sasaran Bab III',
            ]);

        $response->assertRedirect(route('admin.verifikasi.index'));

        $doc->refresh();
        $this->assertEquals('perlu_revisi', strtolower($doc->status));
        $this->assertEquals('Tolong perbaiki indikator sasaran Bab III', $doc->catatan_bapperida);
        $this->assertEquals(1, $doc->revision_count);

        // Dokumen perlu_revisi tidak boleh berada di antrean default (menunggu verifikasi)
        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);
        $this->assertEquals(0, $filtered->total());
    }

    /**
     * TEST 7: OPD mengirim ulang (RESUBMIT) dokumen setelah revisi -> kembali ke antrean verifikasi.
     */
    public function test_resubmit_after_revision_reenters_queue(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Resubmit');
        $operator = User::factory()->create(['role' => 'operator', 'opd_id' => $opd->id]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'perlu_revisi',
            'catatan_bapperida' => 'Perbaiki Bab I',
            'revision_count' => 1,
        ]);

        // Operator OPD submit ulang dokumen
        $response = $this->actingAs($operator)
            ->post(route('renja.submit', $doc->id));

        $doc->refresh();
        $this->assertEquals('dikirim_ulang', strtolower($doc->status));

        // Dokumen dikirim_ulang harus kembali muncul di antrean verifikasi Admin
        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);
        $this->assertEquals(1, $filtered->total());
        $this->assertEquals($doc->id, $filtered->first()->id);
    }

    /**
     * TEST 8: Verifikasi tidak merusak integritas Monitoring 71 OPD.
     */
    public function test_verification_preserves_71_opd_monitoring_integrity(): void
    {
        $activeYear = 2026;
        $opdWithDoc = $this->createOpd('WithDoc');
        $opdWithoutDoc = $this->createOpd('WithoutDoc');

        RenjaDocument::create([
            'opd_id' => $opdWithDoc->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.monitoring-opd.index', ['tahun_anggaran' => $activeYear]));

        $response->assertStatus(200);
        $response->assertSee($opdWithDoc->nama_opd);
        $response->assertSee($opdWithoutDoc->nama_opd);
    }

    /**
     * TEST 9: Pencegahan Double Count Partisipasi OPD pada multiple dokumen disubmit.
     */
    public function test_multiple_submitted_documents_same_opd_counted_once_for_participation(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('MultiSub');

        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2026, 'jenis_dokumen' => 'RENJA Perubahan', 'status' => 'submitted']);

        $filtered = $this->workspaceService->getFilteredWorkspaceDocuments(null, 'all', 'all', null, 'priority_desc', 10, $activeYear);
        $participatedOpdIds = RenjaCycleService::getParticipatedOpdIds($activeYear);

        // Queue contains 2 documents
        $this->assertEquals(2, $filtered->total());

        // Participation contains ONLY 1 distinct OPD ID
        $this->assertCount(1, $participatedOpdIds);
        $this->assertContains($opd->id, $participatedOpdIds);
    }

    /**
     * TEST 10: Authorization Guard - Operator OPD tidak dapat mengakses Workspace Verifikasi Admin (403/Forbidden).
     */
    public function test_operator_cannot_access_admin_verification_workspace(): void
    {
        $opd = $this->createOpd('Auth');
        $operator = User::factory()->create(['role' => 'operator', 'opd_id' => $opd->id]);
        $doc = RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);

        // Attempt accessing index
        $responseIndex = $this->actingAs($operator)
            ->get(route('admin.verifikasi.index'));

        // Attempt accessing review
        $responseReview = $this->actingAs($operator)
            ->get(route('admin.verifikasi.review', $doc->id));

        $responseIndex->assertRedirect(route('operator.dashboard'));
        $responseReview->assertRedirect(route('operator.dashboard'));
    }
}
