<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExportMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * 1. Test Export PDF Folio F4 (215 x 330 mm)
     */
    public function test_user_can_export_pdf_document()
    {
        $opd = MasterOpd::first() ?? MasterOpd::create(['nama_opd' => 'Dinas Pendidikan', 'kode_opd' => '1.01.000']);
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199001012022019001',
            'nama_lengkap' => 'Operator Export Test'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Renja SKPD',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($operator)->get(route('renja.exportPdf', $doc->id));

        $response->assertStatus(200);
        $response->assertSee('215mm 330mm'); // Folio F4 size in CSS
        $response->assertSee('PERATURAN BUPATI CIREBON');
    }

    /**
     * 2. Test Export Word (.docx)
     */
    public function test_user_can_export_word_document()
    {
        $opd = MasterOpd::first() ?? MasterOpd::create(['nama_opd' => 'Dinas Pendidikan', 'kode_opd' => '1.01.000']);
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199001012022019002',
            'nama_lengkap' => 'Operator Word Test'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Renja SKPD',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($operator)->get(route('renja.exportWord', $doc->id));

        $response->assertStatus(200);
        $contentType = $response->headers->get('content-type');
        $this->assertTrue(
            in_array($contentType, ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword']),
            "Unexpected Content-Type: {$contentType}"
        );
    }

    /**
     * 3. Test Access Monitoring Matrix Bapperida Workspace (/bapperida/monitoring)
     */
    public function test_admin_can_access_bapperida_monitoring_matrix_workspace()
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create([
            'role' => 'admin',
            'username_nip' => '198001012005011099',
            'nama_lengkap' => 'Admin Monitoring Test'
        ]);

        $response = $this->actingAs($admin)->get(route('bapperida.monitoring'));

        $response->assertStatus(200);
        $response->assertSee('MONITORING MATRIX BAPPERIDA');
        $response->assertSee('Pengawasan Status 71 Perangkat Daerah');
    }

    /**
     * 4. Test Filtering Monitoring Matrix by TA and Search Query
     */
    public function test_bapperida_monitoring_filters_by_ta_and_search()
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create([
            'role' => 'admin',
            'username_nip' => '198001012005011099',
            'nama_lengkap' => 'Admin Filter Test'
        ]);

        $response = $this->actingAs($admin)->get(route('bapperida.monitoring', [
            'tahun_anggaran' => 2029,
            'search' => 'Kesehatan'
        ]));

        $response->assertStatus(200);
        $response->assertSee('2029');
    }
}
