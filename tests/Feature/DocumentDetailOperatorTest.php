<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DocumentDetailOperatorTest extends TestCase
{
    use RefreshDatabase;

    protected User $operatorA;
    protected User $operatorB;
    protected MasterOpd $opdA;
    protected MasterOpd $opdB;
    protected RenjaDocument $docDraftA;
    protected RenjaDocument $docRevisiA;
    protected RenjaDocument $docSubmittedA;
    protected RenjaDocument $docFinalA;
    protected RenjaDocument $docDraftB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create OPDs
        $this->opdA = MasterOpd::create(['nama_opd' => 'Kecamatan Depok', 'kode_opd' => 'OPD_DEPOK', 'nomor_lampiran_romawi' => 'III']);
        $this->opdB = MasterOpd::create(['nama_opd' => 'Kecamatan Sumber', 'kode_opd' => 'OPD_SUMBER', 'nomor_lampiran_romawi' => 'IV']);

        // Create Operators
        $this->operatorA = User::create([
            'username_nip' => '199001012020011001',
            'nama_lengkap' => 'Operator Depok',
            'email' => 'operator_depok@example.com',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'opd_id' => $this->opdA->id,
        ]);

        $this->operatorB = User::create([
            'username_nip' => '199001012020011002',
            'nama_lengkap' => 'Operator Sumber',
            'email' => 'operator_sumber@example.com',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'opd_id' => $this->opdB->id,
        ]);

        // Dokumen A - Draft
        $this->docDraftA = RenjaDocument::create([
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
            'revision_count' => 0,
        ]);

        // Dokumen B - Perlu Revisi
        $this->docRevisiA = RenjaDocument::create([
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'perlu_revisi',
            'catatan_bapperida' => 'Harap sesuaikan narasi Bab II paragraf 3',
            'revision_count' => 1,
        ]);

        // Dokumen C - Sedang Diverifikasi
        $this->docSubmittedA = RenjaDocument::create([
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
            'submitted_at' => now(),
            'revision_count' => 0,
        ]);

        // Dokumen D - Final / Disetujui
        $this->docFinalA = RenjaDocument::create([
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'disetujui',
            'submitted_at' => now()->subDays(2),
            'revision_count' => 2,
        ]);

        // Dokumen OPD B (Isolasi)
        $this->docDraftB = RenjaDocument::create([
            'opd_id' => $this->opdB->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Sumber Murni',
            'status' => 'draft',
            'revision_count' => 0,
        ]);
    }

    /**
     * Test 1: Operator can view own DRAFT document detail with identity and draft actions.
     */
    public function test_operator_can_view_own_draft_document_detail(): void
    {
        $response = $this->actingAs($this->operatorA)->get("/renja-documents/{$this->docDraftA->id}");

        $response->assertStatus(200);
        $response->assertSee('RENJA Murni TA 2027');
        $response->assertSee('Kecamatan Depok');
        $response->assertSee('Draf');
        $response->assertSee('Versi 1');
        $response->assertSee('Lanjutkan Penyusunan');
        $response->assertSee('Ajukan Verifikasi');
    }

    /**
     * Test 2: Operator sees revision notes and "Lihat & Perbaiki" button for REVISI document.
     */
    public function test_operator_sees_revision_notes_and_fix_button(): void
    {
        $response = $this->actingAs($this->operatorA)->get("/renja-documents/{$this->docRevisiA->id}");

        $response->assertStatus(200);
        $response->assertSee('Perlu Revisi');
        $response->assertSee('Versi 2');
        $response->assertSee('Catatan Verifikasi Bapperida');
        $response->assertSee('Harap sesuaikan narasi Bab II paragraf 3');
        $response->assertSee('Lihat & Perbaiki', false);
        $response->assertSee(route('renja.editor', $this->docRevisiA->id), false);
    }

    /**
     * Test 3: Document under verification is Read-Only with informative alert banner.
     */
    public function test_under_verification_document_detail_is_read_only(): void
    {
        $response = $this->actingAs($this->operatorA)->get("/renja-documents/{$this->docSubmittedA->id}");

        $response->assertStatus(200);
        $response->assertSee('Dokumen Sedang Diverifikasi');
        $response->assertSee('Lihat Preview Dokumen');
        $response->assertDontSee('Lanjutkan Penyusunan');
    }

    /**
     * Test 4: Final document shows download options and read-only notice.
     */
    public function test_final_document_detail_shows_downloads(): void
    {
        $response = $this->actingAs($this->operatorA)->get("/renja-documents/{$this->docFinalA->id}");

        $response->assertStatus(200);
        $response->assertSee('Versi 3');
        $response->assertSee('Cetak PDF F4');
        $response->assertSee('Unduh Word (.docx)');
        $response->assertDontSee('Lanjutkan Penyusunan');
    }

    /**
     * Test 5: Operator cannot view another OPD's document detail (Security URL guard).
     */
    public function test_operator_cannot_access_other_opd_document_detail(): void
    {
        $response = $this->actingAs($this->operatorA)->get("/renja-documents/{$this->docDraftB->id}");

        $response->assertStatus(403);
    }

    /**
     * Test 6: Back button preserves return_status filter query parameter.
     */
    public function test_back_button_preserves_filter_parameter(): void
    {
        $response = $this->actingAs($this->operatorA)->get("/renja-documents/{$this->docRevisiA->id}?return_status=revision_required");

        $response->assertStatus(200);
        $response->assertSee(route('renja.index', ['status' => 'revision_required']), false);
    }
}
