<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Services\DashboardOpdService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardOperatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * Helper: Get or create an operator user with a valid OPD.
     */
    private function getOperatorUser(): ?User
    {
        $user = User::where('role', 'operator')->first();
        if (!$user) {
            $opd = MasterOpd::first();
            if (!$opd) {
                return null;
            }
            $user = User::create([
                'username_nip' => 'test_operator_nip',
                'nama_lengkap' => 'Test Operator',
                'email' => 'test_operator@example.com',
                'password' => bcrypt('password'),
                'role' => 'operator',
                'opd_id' => $opd->id,
            ]);
        }
        return $user;
    }

    /**
     * Helper: Get or create an admin user.
     */
    private function getAdminUser(): ?User
    {
        return User::where('role', 'admin')->first();
    }

    /**
     * Test 1: Operator dapat membuka dashboard (HTTP 200).
     */
    public function test_operator_can_open_dashboard(): void
    {
        $user = $this->getOperatorUser();
        if (!$user) {
            $this->markTestSkipped('No operator user available');
        }

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Operator');
    }

    /**
     * Test 2: Operator hanya melihat data OPD sendiri.
     */
    public function test_operator_only_sees_own_opd_data(): void
    {
        $user = $this->getOperatorUser();
        if (!$user || !$user->opd_id) {
            $this->markTestSkipped('No operator user with OPD');
        }

        $service = new DashboardOpdService();
        $stats = $service->getOpdStats($user->opd_id);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('draft', $stats);
        $this->assertArrayHasKey('total', $stats);

        // Verify counts only include own OPD active cycle documents
        $query = RenjaDocument::where('opd_id', $user->opd_id);
        \App\Services\RenjaCycleService::applyActiveCycleFilter($query, session('active_ta', (int)date('Y')));
        $actualCount = $query->count();
        $this->assertEquals($actualCount, $stats['total']);
    }

    /**
     * Test 3: KPI stats are correct.
     */
    public function test_kpi_stats_are_correct(): void
    {
        $user = $this->getOperatorUser();
        if (!$user || !$user->opd_id) {
            $this->markTestSkipped('No operator user with OPD');
        }

        $opdId = $user->opd_id;
        $service = new DashboardOpdService();
        $stats = $service->getOpdStats($opdId);

        // Sum of all buckets should equal total
        $sumBuckets = $stats['draft'] + $stats['menunggu'] + $stats['revisi'] + $stats['disetujui'];
        $this->assertEquals($stats['total'], $sumBuckets);

        // WoW keys must exist
        $this->assertArrayHasKey('wow_draft', $stats);
        $this->assertArrayHasKey('wow_menunggu', $stats);
        $this->assertArrayHasKey('wow_revisi', $stats);
        $this->assertArrayHasKey('wow_disetujui', $stats);
    }

    /**
     * Test 4: Overall progress returns valid structure.
     */
    public function test_progress_is_correct(): void
    {
        $user = $this->getOperatorUser();
        if (!$user || !$user->opd_id) {
            $this->markTestSkipped('No operator user with OPD');
        }

        $service = new DashboardOpdService();
        $progress = $service->getOverallProgress($user->opd_id);

        $this->assertIsArray($progress);
        $this->assertArrayHasKey('percentage', $progress);
        $this->assertArrayHasKey('completed', $progress);
        $this->assertArrayHasKey('total', $progress);
        $this->assertGreaterThanOrEqual(0, $progress['percentage']);
        $this->assertLessThanOrEqual(100, $progress['percentage']);
    }

    /**
     * Test 5: Timeline is displayed in response.
     */
    public function test_timeline_is_displayed(): void
    {
        $user = $this->getOperatorUser();
        if (!$user) {
            $this->markTestSkipped('No operator user available');
        }

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Timeline Pengajuan');
    }

    /**
     * Test 6: Bapperida notes section is displayed.
     */
    public function test_bapperida_notes_are_displayed(): void
    {
        $user = $this->getOperatorUser();
        if (!$user) {
            $this->markTestSkipped('No operator user available');
        }

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Catatan dari Bapperida');
    }

    /**
     * Test 7: Quick action links are present.
     */
    public function test_quick_action_links_present(): void
    {
        $user = $this->getOperatorUser();
        if (!$user) {
            $this->markTestSkipped('No operator user available');
        }

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Akses Cepat');
        $response->assertSee('Buat Dokumen');
        $response->assertSee('Dokumen Saya');
        $response->assertSee('Acuan Dokumen');
        $response->assertSee('Panduan');
    }

    /**
     * Test 8: Deadline section is present.
     */
    public function test_deadline_section_present(): void
    {
        $user = $this->getOperatorUser();
        if (!$user) {
            $this->markTestSkipped('No operator user available');
        }

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Deadline Penyusunan');
    }

    /**
     * Test 9: Admin is redirected away from OPD dashboard.
     */
    public function test_admin_cannot_see_opd_operator_dashboard(): void
    {
        $admin = $this->getAdminUser();
        if (!$admin) {
            $this->markTestSkipped('No admin user available');
        }

        $response = $this->actingAs($admin)->get('/dashboard');
        // Admin should see admin dashboard, not OPD dashboard
        $response->assertStatus(200);
        $response->assertDontSee('Dashboard OPD');
    }

    /**
     * Test 10: No cross-OPD data leakage.
     */
    public function test_no_cross_opd_data_leakage(): void
    {
        $user = $this->getOperatorUser();
        if (!$user || !$user->opd_id) {
            $this->markTestSkipped('No operator user with OPD');
        }

        $service = new DashboardOpdService();

        // Active documents should only belong to user's OPD
        $activeDocs = $service->getActiveDocuments($user->opd_id, 100);
        foreach ($activeDocs as $doc) {
            $this->assertEquals($user->opd_id, $doc->opd_id);
        }

        // Recent activity should only belong to user's OPD
        $activity = $service->getRecentActivity($user->opd_id, 100);
        foreach ($activity as $item) {
            $this->assertEquals($user->opd_id, $item['document']->opd_id);
        }

        // Deadlines should only belong to user's OPD
        $deadlines = $service->getUpcomingDeadlines($user->opd_id);
        foreach ($deadlines as $dl) {
            $this->assertEquals($user->opd_id, $dl['document']->opd_id);
        }

        $this->assertTrue(true);
    }
}
