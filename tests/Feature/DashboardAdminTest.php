<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Services\Dashboard\DashboardAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Pastikan seeder berjalan jika database disetup ulang untuk testing
        $this->artisan('db:seed');
    }

    /**
     * Test bahwa Service Layer DashboardAdminService menghasilkan struktur KPI yang valid.
     */
    public function test_dashboard_admin_service_kpi_summary_returns_valid_structure()
    {
        $service = app(DashboardAdminService::class);
        $kpi = $service->getKpiSummary();

        $this->assertIsArray($kpi);
        $this->assertArrayHasKey('menunggu', $kpi);
        $this->assertArrayHasKey('direview', $kpi);
        $this->assertArrayHasKey('revisi', $kpi);
        $this->assertArrayHasKey('disetujui', $kpi);
        $this->assertArrayHasKey('total', $kpi);
    }

    /**
     * Test bahwa rute Admin Dashboard menolak pengguna OPD yang tidak memiliki akses (RBAC HTTP 302/403).
     */
    public function test_opd_user_cannot_access_admin_dashboard()
    {
        $opdUser = User::where('role', 'operator')->first();

        if ($opdUser) {
            $response = $this->actingAs($opdUser)->get('/admin/dashboard');
            $this->assertTrue(in_array($response->status(), [302, 403]));
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * Test bahwa Admin Bapperida dapat membuka Admin Dashboard dengan sukses (HTTP 200).
     */
    public function test_admin_user_can_access_admin_dashboard()
    {
        $adminUser = User::where('role', 'admin')->first();

        if ($adminUser) {
            $response = $this->actingAs($adminUser)->get('/admin/dashboard');
            $response->assertStatus(200);
        } else {
            $this->assertTrue(true);
        }
    }
}
