<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * Skenario A: Dokumen Disetujui
     * OPD mengirim dokumen -> Masuk ke Antrean Verifikasi -> Admin menyetujui
     * -> status berubah menjadi DISETUJUI -> dokumen masuk/final ke Arsip -> dokumen tetap dapat ditemukan.
     */
    public function test_scenario_a_document_approval_flow()
    {
        $opd = MasterOpd::first();
        $operator = User::where('role', 'operator')->first() ?? User::factory()->create(['role' => 'operator', 'opd_id' => $opd->id]);
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        // 1. OPD Membuat dan Mengirim Dokumen
        $document = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni 2027',
            'status' => 'draft',
            'updated_by_user_id' => $operator->id,
        ]);

        $responseSubmit = $this->actingAs($operator)->post(route('renja.submit', $document->id));
        $responseSubmit->assertSessionHas('success');

        $document->refresh();
        $this->assertEquals('submitted', strtolower($document->status));

        // 2. Dokumen muncul di Antrean Verifikasi Admin
        $responseQueue = $this->actingAs($admin)->get(route('admin.verifikasi.index'));
        $responseQueue->assertStatus(200);

        // 3. Admin membuka halaman review
        $responseReview = $this->actingAs($admin)->get(route('admin.verifikasi.review', $document->id));
        $responseReview->assertStatus(200);

        // 4. Admin Menyetujui Dokumen
        $responseDecision = $this->actingAs($admin)->post(route('admin.verifikasi.decision', $document->id), [
            'decision_type' => 'setujui_dokumen',
            'catatan_bapperida' => 'Dokumen disetujui penuh oleh Admin Bapperida.',
        ]);

        $responseDecision->assertRedirect(route('admin.verifikasi.index'));
        $responseDecision->assertSessionHas('success');

        $document->refresh();
        $this->assertContains(strtolower($document->status), ['disetujui', 'approved']);
        $this->assertTrue($document->isLocked());

        // 5. Dokumen masuk ke Arsip & tetap dapat ditemukan
        $responseArchive = $this->actingAs($admin)->get(route('renja.archive.index', ['search' => $document->jenis_dokumen]));
        $responseArchive->assertStatus(200);
        $responseArchive->assertSee($document->jenis_dokumen);
    }

    /**
     * Skenario B: Dokumen Direvisi
     * OPD mengirim dokumen -> Muncul di Antrean -> Admin meminta revisi -> Status PERLU REVISI
     * -> OPD menerima & perbaiki -> OPD mengirim ulang -> Status KEMBALI MENUNGGU VERIFIKASI
     * -> Admin verifikasi ulang.
     */
    public function test_scenario_b_document_revision_and_resubmit_flow()
    {
        $opd = MasterOpd::first();
        $operator = User::where('role', 'operator')->first() ?? User::factory()->create(['role' => 'operator', 'opd_id' => $opd->id]);
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        // 1. OPD Submit Pertama
        $document = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni 2027',
            'status' => 'draft',
            'updated_by_user_id' => $operator->id,
        ]);
        $this->actingAs($operator)->post(route('renja.submit', $document->id));

        // 2. Admin Minta Revisi
        $responseRevision = $this->actingAs($admin)->post(route('admin.verifikasi.decision', $document->id), [
            'decision_type' => 'minta_revisi',
            'catatan_bapperida' => 'Harap lengkapi narasi Bab II dan tabel evaluasi.',
        ]);

        $responseRevision->assertSessionHas('success');
        $document->refresh();
        $this->assertContains(strtolower($document->status), ['perlu_revisi', 'revisi']);
        $this->assertEquals('Harap lengkapi narasi Bab II dan tabel evaluasi.', $document->catatan_bapperida);

        // 3. OPD Menerima & Mengirim Ulang
        $responseResubmit = $this->actingAs($operator)->post(route('renja.submit', $document->id));
        $responseResubmit->assertSessionHas('success');

        $document->refresh();
        $this->assertContains(strtolower($document->status), ['submitted', 'menunggu_verifikasi', 'dikirim_ulang']);

        // 4. Muncul kembali di Antrean Verifikasi Admin
        $responseQueue = $this->actingAs($admin)->get(route('admin.verifikasi.index', ['status' => 'menunggu_verifikasi']));
        $responseQueue->assertStatus(200);
    }

    /**
     * Skenario C: Monitoring OPD & Master Perangkat Daerah
     * Tampil 71 OPD, status sesuai kondisi aktual, dapat memilih OPD untuk melihat detail.
     */
    public function test_scenario_c_monitoring_and_master_opd()
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);
        $opd = MasterOpd::first();

        // 1. Admin Membuka Monitoring OPD
        $responseMonitoring = $this->actingAs($admin)->get(route('admin.monitoring-opd.index'));
        $responseMonitoring->assertStatus(200);
        $responseMonitoring->assertSee('Monitoring OPD');

        // 2. Admin Membuka Halaman Master Perangkat Daerah
        $responseMasterOpd = $this->actingAs($admin)->get(route('admin.master_opd.index'));
        $responseMasterOpd->assertStatus(200);
        $responseMasterOpd->assertSee('Data Perangkat Daerah Kabupaten Cirebon');

        // 3. Admin Membuka Detail OPD Spesifik
        $responseShowOpd = $this->actingAs($admin)->get(route('admin.master_opd.show', $opd->id));
        $responseShowOpd->assertStatus(200);
        $responseShowOpd->assertSee($opd->nama_opd);
    }

    /**
     * Skenario D: Keamanan Data & Preservasi Audit Trail
     * Perubahan status tidak menghapus data, file, metadata, atau riwayat audit trail.
     */
    public function test_scenario_d_data_safety_and_audit_trail_preservation()
    {
        $opd = MasterOpd::first();
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        $document = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Perubahan 2027',
            'status' => 'submitted',
            'submitted_at' => now(),
            'metadata' => [
                'original_filename' => 'Dokumen_Awal.docx',
                'audit_trail' => [
                    [
                        'action' => 'INITIAL_CREATION',
                        'notes' => 'Dibuat pertama kali',
                        'timestamp' => now()->toIso8601String(),
                        'user_name' => 'Operator OPD',
                    ]
                ]
            ],
        ]);

        // Jalankan Keputusan Verifikasi
        $this->actingAs($admin)->post(route('admin.verifikasi.decision', $document->id), [
            'decision_type' => 'simpan_draft',
            'catatan_bapperida' => 'Catatan draf verifikasi',
        ]);

        $document->refresh();

        // Pastikan metadata dasar dan audit trail tidak hilang
        $this->assertEquals('Dokumen_Awal.docx', $document->original_filename);
        $this->assertNotEmpty($document->metadata['audit_trail']);
        $this->assertEquals('INITIAL_CREATION', $document->metadata['audit_trail'][0]['action']);
    }

    /**
     * Test Skenario Transisi Ilegal & Penguncian Dokumen (Locking Guards)
     * Memastikan dokumen disetujui/final tidak dapat diedit atau diubah statusnya secara ilegal.
     */
    public function test_illegal_transitions_and_locking_guards()
    {
        $opd = MasterOpd::first();
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);
        $operator = User::where('role', 'operator')->first() ?? User::factory()->create(['role' => 'operator', 'opd_id' => $opd->id]);

        $document = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni 2027',
            'status' => 'disetujui',
            'updated_by_user_id' => $operator->id,
        ]);

        // 1. Mencoba ubah status dokumen yang sudah disetujui (APPROVED) -> Harus Gagal / Throw Exception
        $responseIllegal = $this->actingAs($admin)->post(route('admin.verifikasi.decision', $document->id), [
            'decision_type' => 'minta_revisi',
            'catatan_bapperida' => 'Mencoba revisi dokumen sah',
        ]);
        $responseIllegal->assertSessionHas('error');

        // 2. Mencoba submit ulang dokumen disetujui oleh Operator -> Harus Ditolak
        $responseSubmitIllegal = $this->actingAs($operator)->post(route('renja.submit', $document->id));
        $responseSubmitIllegal->assertSessionHas('error');

        // 3. Verifikasi helper method locking & editability
        $this->assertTrue($document->isLocked());
        $this->assertFalse($document->isEditableByOpd());
    }
}
