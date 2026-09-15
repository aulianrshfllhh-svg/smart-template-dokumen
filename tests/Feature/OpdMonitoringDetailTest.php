<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OpdMonitoringDetailTest extends TestCase
{
    use RefreshDatabase;

    private function createOpd(): MasterOpd
    {
        $uniqueId = rand(10000, 99999);
        return MasterOpd::create([
            'nama_opd' => 'Dinas Detail Monitoring ' . $uniqueId,
            'kode_opd' => '1.01.' . $uniqueId,
            'nomor_lampiran_romawi' => 'LAMPIRAN V',
        ]);
    }

    /**
     * TEST 1: Admin dapat membuka Halaman Detail Monitoring OPD dan melihat 4 Dokumen Siklus Aktif.
     */
    public function test_admin_can_view_opd_monitoring_detail_with_active_cycle_documents(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd();

        // Document 1: RENJA Murni 2027 (submitted)
        $docMurni = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        // Document 2: RENJA Perubahan 2026 (perlu_revisi)
        $docPerubahan = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Perubahan',
            'status' => 'perlu_revisi',
            'catatan_bapperida' => 'Tolong perbaiki Bab II',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.monitoring-opd.show', [
                'opd' => $opd->id,
                'tahun_anggaran' => $activeYear
            ]));

        $response->assertStatus(200);
        $response->assertSee($opd->nama_opd);
        $response->assertSee('RENJA Murni');
        $response->assertSee('RENJA Perubahan');
        $response->assertSee('Lampiran RENJA Murni');
        $response->assertSee('Lampiran RENJA Perubahan');

        // Check review action link presence for submitted document
        $response->assertSee(route('admin.review', $docMurni->id));
        $response->assertSee('Tolong perbaiki Bab II');
    }

    /**
     * TEST 2: Halaman Detail OPD tanpa dokumen menampilkan status "Belum Mulai" dan badge "Belum Dibuat".
     */
    public function test_opd_without_documents_shows_uncreated_badges(): void
    {
        $activeYear = 2026;
        $opd = $this->createOpd();
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.monitoring-opd.show', [
                'opd' => $opd->id,
                'tahun_anggaran' => $activeYear
            ]));

        $response->assertStatus(200);
        $response->assertSee($opd->nama_opd);
        $response->assertSee('Belum Mulai');
        $response->assertSee('Belum Dibuat');
    }
}
