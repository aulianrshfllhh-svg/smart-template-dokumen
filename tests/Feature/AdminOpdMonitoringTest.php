<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminOpdMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;
    protected MasterOpd $opdBapperida;
    protected MasterOpd $opdDepok;
    protected MasterOpd $opdSumber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opdBapperida = MasterOpd::create([
            'nama_opd' => 'Bapperida Kabupaten Cirebon',
            'kode_opd' => 'OPD_BAPPERIDA',
            'nomor_lampiran_romawi' => 'I',
        ]);

        $this->opdDepok = MasterOpd::create([
            'nama_opd' => 'Kecamatan Depok',
            'kode_opd' => 'OPD_DEPOK',
            'nomor_lampiran_romawi' => 'III',
        ]);

        $this->opdSumber = MasterOpd::create([
            'nama_opd' => 'Kecamatan Sumber',
            'kode_opd' => 'OPD_SUMBER',
            'nomor_lampiran_romawi' => 'IV',
        ]);

        $this->admin = User::create([
            'username_nip' => '198001012005011001',
            'nama_lengkap' => 'Admin Bapperida',
            'email' => 'admin@bapperida.go.id',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'opd_id' => $this->opdBapperida->id,
        ]);

        $this->operator = User::create([
            'username_nip' => '199001012020011001',
            'nama_lengkap' => 'Operator Depok',
            'email' => 'operator_depok@example.com',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'opd_id' => $this->opdDepok->id,
        ]);

        // Seed 1 document for OPD Depok
        RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // OPD Sumber has 0 documents (Belum Mengirim)
    }

    /**
     * Test 1: Admin can access Monitoring OPD index page with dynamic KPI stats.
     */
    public function test_admin_can_access_monitoring_opd_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/monitoring-opd');

        $response->assertStatus(200);
        $response->assertSee('MONITORING OPD');
        $response->assertSee('Pantau progres penyusunan');
        $response->assertSee('Sudah Berpartisipasi');
        $response->assertSee('Belum Berpartisipasi');
    }

    /**
     * Test 2: OPD without documents appears in list with "Belum Mengirim" status.
     */
    public function test_opd_without_documents_has_belum_mengirim_status(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/monitoring-opd');

        $response->assertStatus(200);
        $response->assertSee('Kecamatan Sumber');
        $response->assertSee('0 / 4');
    }

    /**
     * Test 3: Admin can search OPD by name.
     */
    public function test_admin_can_search_opd_by_name(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/monitoring-opd?search=Depok');

        $response->assertStatus(200);
        $response->assertSee('Kecamatan Depok');
        $response->assertDontSee('Kecamatan Sumber');
    }

    /**
     * Test 4: Admin can filter OPD by status (e.g. belum_mengirim).
     */
    public function test_admin_can_filter_opd_by_status(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/monitoring-opd?status=belum_mengirim');

        $response->assertStatus(200);
        $response->assertSee('Kecamatan Sumber');
        $response->assertDontSee('Kecamatan Depok');
    }

    /**
     * Test 5: Admin can view detail OPD monitoring showing all documents for that OPD.
     */
    public function test_admin_can_view_opd_monitoring_detail(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/monitoring-opd/{$this->opdDepok->id}");

        $response->assertStatus(200);
        $response->assertSee('Kecamatan Depok');
        $response->assertSee('RENJA Murni');
        $response->assertSee('TA 2027');
        $response->assertSee('Lihat Dokumen');
        $response->assertSee('Periksa Dokumen');
    }

    /**
     * Test 6: Operator cannot access Admin Monitoring OPD module (redirected gracefully).
     */
    public function test_operator_cannot_access_admin_monitoring_opd(): void
    {
        $response = $this->actingAs($this->operator)->get('/admin/monitoring-opd');

        $response->assertRedirect(route('operator.dashboard'));
    }
}
