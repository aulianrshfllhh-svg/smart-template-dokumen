<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Services\DashboardBapperidaService;
use App\Services\Dashboard\DashboardAdminService;
use App\Services\DashboardOpdService;
use App\Services\RenjaCycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminDashboardActiveCycleTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardBapperidaService $bapperidaService;
    protected DashboardAdminService $adminService;
    protected DashboardOpdService $opdService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bapperidaService = app(DashboardBapperidaService::class);
        $this->adminService = app(DashboardAdminService::class);
        $this->opdService = app(DashboardOpdService::class);
    }

    private function createOpd(string $nameSuffix = ''): MasterOpd
    {
        $uniqueId = rand(10000, 99999);
        return MasterOpd::create([
            'nama_opd' => 'Dinas Test Admin ' . $uniqueId . ' ' . $nameSuffix,
            'kode_opd' => '1.01.' . $uniqueId,
            'nomor_lampiran_romawi' => 'LAMPIRAN I',
        ]);
    }

    /**
     * TEST 1: Admin Dashboard KPI & Antrean terisolasi 100% pada Active Cycle 2026.
     */
    public function test_admin_dashboard_active_cycle_2026(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('2026');

        // Document in active cycle 2026 (RENJA Murni 2027 = submitted)
        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        // Historical Document (RENJA Murni 2026 = draft from cycle 2025)
        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);

        // Future Document (RENJA Murni 2028 = submitted from cycle 2027)
        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2028,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $stats = $this->bapperidaService->getDashboardStats($activeYear);
        $kpiAdmin = $this->adminService->getKpiSummary($activeYear);
        $partStats = $this->bapperidaService->getOpdParticipationStats($activeYear);

        // Active cycle 2026 should only count RENJA Murni 2027 (submitted)
        $this->assertEquals(1, $stats['menunggu']);
        $this->assertEquals(1, $kpiAdmin['menunggu']);
        $this->assertEquals(0, $stats['draft']);
        $this->assertEquals(1, $partStats['sudah_menyusun']);

        // Verify filtered workspace documents only contains 1 document for active cycle 2026
        $filtered = $this->bapperidaService->getFilteredDocuments(null, 'all', 'all', 15, $activeYear);
        $this->assertEquals(1, $filtered->total());
        $this->assertEquals(2027, $filtered->first()->tahun_anggaran);
    }

    /**
     * TEST 2: Admin Dashboard KPI & Antrean terisolasi 100% pada Active Cycle 2027.
     */
    public function test_admin_dashboard_active_cycle_2027(): void
    {
        $activeYear = 2027;
        $opd = $this->createOpd('2027');

        // Document in active cycle 2027 (RENJA Murni 2028 = disetujui)
        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2028,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'disetujui',
        ]);

        // Historical Document (RENJA Murni 2027 = submitted from cycle 2026)
        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $stats = $this->bapperidaService->getDashboardStats($activeYear);
        $partStats = $this->bapperidaService->getOpdParticipationStats($activeYear);

        // Active cycle 2027 should only count RENJA Murni 2028 (disetujui)
        $this->assertEquals(1, $stats['disetujui']);
        $this->assertEquals(0, $stats['menunggu']);
        $this->assertEquals(1, $partStats['sudah_menyusun']);
    }

    /**
     * TEST 3: Master OPD 71 selalu terlihat pada monitoring meski 0 dokumen.
     */
    public function test_admin_monitoring_opd_without_documents_remains_visible(): void
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

        // Access Monitoring OPD index
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)
            ->get(route('admin.monitoring-opd.index', ['tahun_anggaran' => $activeYear]));

        $response->assertStatus(200);
        $response->assertSee($opdWithDoc->nama_opd);
        $response->assertSee($opdWithoutDoc->nama_opd);
        $response->assertSee('Belum Mulai');
    }

    /**
     * TEST 4: Aturan partisipasi OPD menghitung DISTINCT OPD yang telah submit.
     */
    public function test_opd_participation_rule_distinct_opd(): void
    {
        $activeYear = 2026;

        // OPD 1: Draft only -> Partisipasi = NO
        $opd1 = $this->createOpd('Part1');
        RenjaDocument::create(['opd_id' => $opd1->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'draft']);

        // OPD 2: Submitted -> Partisipasi = YES
        $opd2 = $this->createOpd('Part2');
        RenjaDocument::create(['opd_id' => $opd2->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);

        // OPD 3: Revision -> Partisipasi = YES
        $opd3 = $this->createOpd('Part3');
        RenjaDocument::create(['opd_id' => $opd3->id, 'tahun_anggaran' => 2026, 'jenis_dokumen' => 'RENJA Perubahan', 'status' => 'perlu_revisi']);

        // OPD 4: Approved -> Partisipasi = YES
        $opd4 = $this->createOpd('Part4');
        RenjaDocument::create(['opd_id' => $opd4->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Lampiran Murni', 'status' => 'disetujui']);

        // OPD 5: Uncreated -> Partisipasi = NO
        $opd5 = $this->createOpd('Part5');

        $partStats = $this->bapperidaService->getOpdParticipationStats($activeYear);
        $participatedIds = RenjaCycleService::getParticipatedOpdIds($activeYear);

        $this->assertEquals(3, $partStats['sudah_menyusun']);
        $this->assertNotContains($opd1->id, $participatedIds);
        $this->assertContains($opd2->id, $participatedIds);
        $this->assertContains($opd3->id, $participatedIds);
        $this->assertContains($opd4->id, $participatedIds);
        $this->assertNotContains($opd5->id, $participatedIds);
    }

    /**
     * TEST 5: Mencegah Double Counting jika 1 OPD memiliki multiple dokumen disubmit.
     */
    public function test_no_double_counting_for_multiple_documents_same_opd(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('MultiDocs');

        // OPD memiliki 3 dokumen disubmit/approved dalam 1 siklus aktif 2026
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2026, 'jenis_dokumen' => 'RENJA Perubahan', 'status' => 'perlu_revisi']);
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Lampiran Murni', 'status' => 'disetujui']);

        $partStats = $this->bapperidaService->getOpdParticipationStats($activeYear);
        $participatedIds = RenjaCycleService::getParticipatedOpdIds($activeYear);

        // Partisipasi OPD tersebut harus tetap dihitung 1 (DISTINCT OPD)
        $this->assertEquals(1, $partStats['sudah_menyusun']);
        $this->assertCount(1, $participatedIds);
    }

    /**
     * TEST 6: Konsistensi data KPI antara Dashboard OPD dan Dashboard Admin.
     */
    public function test_dashboard_opd_and_admin_kpi_consistency(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd('Consistency');

        // OPD Dataset:
        // RENJA Murni 2027 -> Submitted
        // RENJA Perubahan 2026 -> Draft
        // Lampiran Murni 2027 -> Disetujui
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'submitted']);
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2026, 'jenis_dokumen' => 'RENJA Perubahan', 'status' => 'draft']);
        RenjaDocument::create(['opd_id' => $opd->id, 'tahun_anggaran' => 2027, 'jenis_dokumen' => 'RENJA Lampiran Murni', 'status' => 'disetujui']);

        $opdStats = $this->opdService->getOpdStats($opd->id, $activeYear);
        $adminStats = $this->bapperidaService->getDashboardStats($activeYear);

        $this->assertEquals($opdStats['menunggu'], $adminStats['menunggu']);
        $this->assertEquals($opdStats['disetujui'], $adminStats['disetujui']);
        $this->assertEquals($opdStats['revisi'], $adminStats['revisi']);
        $this->assertEquals($opdStats['draft'], $adminStats['draft']);
    }
}
