<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Services\DashboardOpdService;
use App\Services\OpdDocumentService;
use App\Services\RenjaCycleService;
use App\Services\DashboardBapperidaService;
use App\Services\Dashboard\DashboardAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActiveCycleAndKpiTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardOpdService $dashboardOpdService;
    protected OpdDocumentService $opdDocumentService;
    protected DashboardBapperidaService $dashboardBapperidaService;
    protected DashboardAdminService $dashboardAdminService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardOpdService = app(DashboardOpdService::class);
        $this->opdDocumentService = app(OpdDocumentService::class);
        $this->dashboardBapperidaService = app(DashboardBapperidaService::class);
        $this->dashboardAdminService = app(DashboardAdminService::class);
    }

    private function createOperator(string $nameSuffix = ''): array
    {
        $uniqueId = rand(10000, 99999);
        $opd = MasterOpd::create([
            'nama_opd' => 'Dinas Testing ' . $uniqueId . ' ' . $nameSuffix,
            'kode_opd' => '1.01.' . $uniqueId,
            'nomor_lampiran_romawi' => 'LAMPIRAN I',
        ]);

        $user = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '19950101' . $uniqueId,
            'nama_lengkap' => 'Operator ' . $nameSuffix,
        ]);

        return [$user, $opd];
    }

    /**
     * TEST SCENARIO TA 2026:
     * Active Cycle = 2026
     * Expected Cycle Documents:
     * - RENJA Murni -> 2027
     * - RENJA Perubahan -> 2026
     * - Lampiran Murni -> 2027
     * - Lampiran Perubahan -> 2026
     */
    public function test_active_cycle_2026_scenarios(): void
    {
        [$user, $opd] = $this->createOperator('2026');
        $activeYear = 2026;

        // Test Case 1: Semua dokumen belum dibuat
        $stats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(0, $stats['draft']);
        $this->assertEquals(0, $stats['revisi']);
        $this->assertEquals(0, $stats['menunggu']);
        $this->assertEquals(0, $stats['disetujui']);
        $this->assertCount(0, RenjaCycleService::getParticipatedOpdIds($activeYear));

        // Test Case 2: RENJA Murni 2027 = DRAFT
        $doc1 = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);
        $stats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(1, $stats['draft']);
        $this->assertEquals(0, $stats['menunggu']);
        $this->assertNotContains($opd->id, RenjaCycleService::getParticipatedOpdIds($activeYear));

        // Test Case 3: RENJA Murni 2027 = SUBMITTED / UNDER REVIEW
        $doc1->update(['status' => 'submitted', 'submitted_at' => now()]);
        $stats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(0, $stats['draft']);
        $this->assertEquals(1, $stats['menunggu']);
        $this->assertContains($opd->id, RenjaCycleService::getParticipatedOpdIds($activeYear));

        // Test Case 4: RENJA Murni 2027 = REVISION
        $doc1->update(['status' => 'perlu_revisi']);
        $stats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(1, $stats['revisi']);
        $this->assertContains($opd->id, RenjaCycleService::getParticipatedOpdIds($activeYear));

        // Test Case 5: RENJA Murni 2027 = APPROVED / FINAL
        $doc1->update(['status' => 'disetujui']);
        $stats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(1, $stats['disetujui']);
        $this->assertContains($opd->id, RenjaCycleService::getParticipatedOpdIds($activeYear));

        // Test Case 6: Dokumen Historical (RENJA Murni 2026) tidak dihitung dalam Cycle 2026
        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);
        $stats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        // HANYA doc1 (disetujui) yang masuk cycle 2026; RENJA Murni 2026 dianggap historis dari cycle 2025
        $this->assertEquals(0, $stats['draft']);
        $this->assertEquals(1, $stats['disetujui']);

        // Test Case 7: Dokumen Future (RENJA Murni 2028) tidak dihitung dalam Cycle 2026
        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2028,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);
        $stats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(0, $stats['menunggu']);
        $this->assertEquals(1, $stats['disetujui']);
    }

    /**
     * TEST SCENARIO TA 2027:
     * Active Cycle = 2027
     * Expected Cycle Documents:
     * - RENJA Murni -> 2028
     * - RENJA Perubahan -> 2027
     * - Lampiran Murni -> 2028
     * - Lampiran Perubahan -> 2027
     */
    public function test_active_cycle_2027_scenarios(): void
    {
        [$user, $opd] = $this->createOperator('2027');
        $activeYear = 2027;

        // Test Case 1: Empty state
        $stats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(0, $stats['draft']);
        $this->assertEquals(0, $stats['revisi']);
        $this->assertEquals(0, $stats['menunggu']);
        $this->assertEquals(0, $stats['disetujui']);

        // Test Case 2: RENJA Murni 2028 = DRAFT
        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2028,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);
        $stats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(1, $stats['draft']);
        $this->assertNotContains($opd->id, RenjaCycleService::getParticipatedOpdIds($activeYear));

        // Test Case 3: RENJA Murni 2028 = SUBMITTED
        $doc->update(['status' => 'submitted', 'submitted_at' => now()]);
        $stats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(1, $stats['menunggu']);
        $this->assertContains($opd->id, RenjaCycleService::getParticipatedOpdIds($activeYear));

        // Test Case 4: RENJA Murni 2028 = REVISION
        $doc->update(['status' => 'perlu_revisi']);
        $stats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(1, $stats['revisi']);

        // Test Case 5: RENJA Murni 2028 = APPROVED
        $doc->update(['status' => 'disetujui']);
        $stats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(1, $stats['disetujui']);

        // Test Case 6: Historical Document (RENJA Murni 2027) tidak masuk ke Cycle 2027
        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);
        $stats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(0, $stats['menunggu']); // Murni 2027 bukan bagian dari cycle 2027 (karena cycle 2027 butuh Murni 2028)

        // Test Case 7: Future Document (RENJA Murni 2029) tidak masuk ke Cycle 2027
        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2029,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);
        $stats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        $this->assertEquals(0, $stats['draft']);
    }

    /**
     * TEST CROSS CYCLE:
     * Dataset dengan dokumen campuran TA 2025, 2026, 2027, 2028.
     * Menguji bahwa perubahan activeYear secara akurat mengubah hasil KPI.
     */
    public function test_cross_cycle_dataset(): void
    {
        [$user, $opd] = $this->createOperator('CrossCycle');

        // Cycle 2026 Documents:
        // RENJA Murni 2027 (submitted)
        // RENJA Perubahan 2026 (draft)
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2026, 'jenis_dokumen' => 'RENJA Perubahan', 'status' => 'draft']);

        // Cycle 2027 Documents:
        // RENJA Murni 2028 (disetujui)
        // RENJA Perubahan 2027 (perlu_revisi)
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2028, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'disetujui']);
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Perubahan', 'status' => 'perlu_revisi']);

        // Active Year = 2026:
        $stats2026 = $this->dashboardOpdService->getOpdStats($opd->id, 2026);
        $this->assertEquals(1, $stats2026['draft']);    // RENJA Perubahan 2026
        $this->assertEquals(1, $stats2026['menunggu']); // RENJA Murni 2027
        $this->assertEquals(0, $stats2026['revisi']);
        $this->assertEquals(0, $stats2026['disetujui']);

        // Active Year = 2027:
        $stats2027 = $this->dashboardOpdService->getOpdStats($opd->id, 2027);
        $this->assertEquals(0, $stats2027['draft']);
        $this->assertEquals(0, $stats2027['menunggu']);
        $this->assertEquals(1, $stats2027['revisi']);   // RENJA Perubahan 2027
        $this->assertEquals(1, $stats2027['disetujui']);// RENJA Murni 2028
    }

    /**
     * TEST KONSISTENSI DASHBOARD OPD ↔ DOKUMEN SAYA:
     * Menguji bahwa data KPI pada Dashboard OPD dan Dokumen Saya 100% identik.
     */
    public function test_dashboard_and_dokumen_saya_consistency(): void
    {
        [$user, $opd] = $this->createOperator('Consistency');
        $activeYear = 2026;

        // Dataset untuk Active Cycle 2026:
        // RENJA Murni 2027 -> Submitted
        // RENJA Perubahan 2026 -> Draft
        // Lampiran Murni 2027 -> Disetujui
        // Lampiran Perubahan 2026 -> Belum dibuat
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2026, 'jenis_dokumen' => 'RENJA Perubahan', 'status' => 'draft']);
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Lampiran Murni', 'status' => 'disetujui']);

        $dashboardStats = $this->dashboardOpdService->getOpdStats($opd->id, $activeYear);
        $dokumenSayaKpi = $this->opdDocumentService->calculateOpdKpi($opd->id, $activeYear);

        // Verifikasi Konsistensi Nilai:
        $this->assertEquals($dashboardStats['draft'], $dokumenSayaKpi['draft']['count']);
        $this->assertEquals(1, $dashboardStats['draft']);

        $this->assertEquals($dashboardStats['menunggu'], $dokumenSayaKpi['submitted']['count']);
        $this->assertEquals(1, $dashboardStats['menunggu']);

        $this->assertEquals($dashboardStats['disetujui'], $dokumenSayaKpi['final']['count']);
        $this->assertEquals(1, $dashboardStats['disetujui']);

        $this->assertEquals($dashboardStats['revisi'], $dokumenSayaKpi['revisi']['count']);
        $this->assertEquals(0, $dashboardStats['revisi']);
    }

    /**
     * TEST PARTISIPASI OPD:
     * Menguji bahwa partisipasi OPD dihitung berdasarkan DISTINCT OPD yang telah submit (bukan draft/belum dibuat).
     */
    public function test_opd_participation_scenarios(): void
    {
        $activeYear = 2026;

        // OPD 1: Draft saja -> Tidak Berpartisipasi
        [$user1, $opd1] = $this->createOperator('Part1');
        RenjaDocument::create(['opd_id' => $opd1->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'draft']);

        // OPD 2: Submitted -> Berpartisipasi
        [$user2, $opd2] = $this->createOperator('Part2');
        RenjaDocument::create(['opd_id' => $opd2->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);

        // OPD 3: Revision -> Berpartisipasi
        [$user3, $opd3] = $this->createOperator('Part3');
        RenjaDocument::create(['opd_id' => $opd3->id, 'tahun_anggaran' => 2026, 'jenis_dokumen' => 'RENJA Perubahan', 'status' => 'perlu_revisi']);

        // OPD 4: Approved -> Berpartisipasi
        [$user4, $opd4] = $this->createOperator('Part4');
        RenjaDocument::create(['opd_id' => $opd4->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Lampiran Murni', 'status' => 'disetujui']);

        // OPD 5: Belum dibuat -> Tidak Berpartisipasi
        [$user5, $opd5] = $this->createOperator('Part5');

        $participatedIds = RenjaCycleService::getParticipatedOpdIds($activeYear);

        $this->assertNotContains($opd1->id, $participatedIds);
        $this->assertContains($opd2->id, $participatedIds);
        $this->assertContains($opd3->id, $participatedIds);
        $this->assertContains($opd4->id, $participatedIds);
        $this->assertNotContains($opd5->id, $participatedIds);

        $statsBapperida = $this->dashboardBapperidaService->getOpdParticipationStats($activeYear);
        $statsAdmin = $this->dashboardAdminService->getOpdParticipationStats($activeYear);

        // minimal 3 OPD ini berpartisipasi
        $this->assertGreaterThanOrEqual(3, $statsBapperida['sudah_menyusun']);
        $this->assertGreaterThanOrEqual(3, $statsAdmin['sudah_menyusun']);
    }
}
