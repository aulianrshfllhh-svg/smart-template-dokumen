<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Models\RenjaSectionCaption;
use App\Models\RenjaTableEval;
use App\Models\RenjaTableUtama;
use App\Models\User;
use App\Services\OpdDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenjaClonePreviousYearTest extends TestCase
{
    use RefreshDatabase;

    protected MasterOpd $opdA;
    protected MasterOpd $opdB;
    protected User $operatorA;
    protected User $operatorB;
    protected User $adminUser;
    protected DocumentTemplate $templateMurni;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->opdA = MasterOpd::first() ?? MasterOpd::create([
            'kode_opd' => '1.01.01',
            'nama_opd' => 'Dinas Pendidikan',
            'singkatan_opd' => 'Disdik',
        ]);

        $this->opdB = MasterOpd::where('id', '!=', $this->opdA->id)->first() ?? MasterOpd::create([
            'kode_opd' => '1.02.01',
            'nama_opd' => 'Dinas Kesehatan',
            'singkatan_opd' => 'Dinkes',
        ]);

        $this->operatorA = User::create([
            'username_nip' => 'operator_disdik',
            'nama_lengkap' => 'Operator Disdik',
            'email' => 'disdik@cirebonkab.go.id',
            'password' => bcrypt('password123'),
            'role' => 'operator',
            'opd_id' => $this->opdA->id,
        ]);

        $this->operatorB = User::create([
            'username_nip' => 'operator_dinkes',
            'nama_lengkap' => 'Operator Dinkes',
            'email' => 'dinkes@cirebonkab.go.id',
            'password' => bcrypt('password123'),
            'role' => 'operator',
            'opd_id' => $this->opdB->id,
        ]);

        $this->adminUser = User::where('role', 'admin')->first() ?? User::create([
            'username_nip' => 'admin_bapperida',
            'nama_lengkap' => 'Admin Bapperida',
            'email' => 'admin@cirebonkab.go.id',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'opd_id' => null,
        ]);

        $this->templateMurni = DocumentTemplate::where('code', 'RENJA_MURNI')->first()
            ?? DocumentTemplate::create([
                'code' => 'RENJA_MURNI',
                'name' => 'Template RENJA Murni',
                'category' => 'renja',
                'is_active' => true,
            ]);
    }

    /**
     * Helper membuat dokumen sumber lengkap dengan Section, Caption, Table Eval, dan Table Utama.
     */
    private function createSourceDocument(int $opdId, int $tahun = 2026, string $status = 'disetujui'): RenjaDocument
    {
        $doc = RenjaDocument::create([
            'opd_id' => $opdId,
            'template_id' => $this->templateMurni->id,
            'tahun_anggaran' => $tahun,
            'year' => $tahun,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => $status,
            'catatan_bapperida' => 'Catatan revisi lama TA 2026',
            'cover_data' => [
                'judul_dokumen' => 'RENCANA KERJA (RENJA) MURNI TAHUN ANGGARAN ' . $tahun,
                'tahun_anggaran' => $tahun,
                'nama_opd' => 'Dinas Pendidikan',
            ],
            'metadata' => [
                'old_audit' => 'some_legacy_audit',
                'audit_trail' => [
                    ['action' => 'APPROVED_2026', 'timestamp' => '2026-01-01']
                ]
            ]
        ]);

        // 1. Sections
        $sec1 = RenjaSection::create([
            'document_id' => $doc->id,
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'content' => '<p>MARKER_CLONE_NARASI_001 Konten Bab 1</p>',
            'section_type' => 'subchapter',
            'order_index' => 1,
            'is_completed' => true,
        ]);

        // Caption pada section 1
        RenjaSectionCaption::create([
            'section_id' => $sec1->id,
            'element_type' => 'table',
            'element_id' => 'table_1',
            'caption' => 'Tabel Indikator Makro',
            'display_number' => 'Tabel 1.1',
            'order_index' => 1,
        ]);

        $sec2 = RenjaSection::create([
            'document_id' => $doc->id,
            'bab_code' => 'BAB II',
            'bab_title' => 'Evaluasi',
            'sub_bab_code' => '2.1',
            'sub_bab_title' => 'Evaluasi Kinerja Renja',
            'content' => '<p>MARKER_CLONE_NARASI_002 Konten Bab 2</p>',
            'section_type' => 'subchapter',
            'order_index' => 2,
            'is_completed' => true,
        ]);

        // 2. Table Eval
        RenjaTableEval::create([
            'document_id' => $doc->id,
            'jenis_tabel' => 'evaluasi_2.1',
            'kode_rekening' => '1.01.01.1.01',
            'nama_program_kegiatan' => 'MARKER_CLONE_TABLE_EVAL_001 Program Pengelolaan Pendidikan',
            'indikator_kinerja' => 'Persentase Sekolah Terakreditasi A',
            'target_capaian' => '85%',
            'pagu_indikatif' => 1500000000,
            'realisasi_capaian' => '84%',
            'realisasi_pagu' => 1480000000,
        ]);

        // 3. Table Utama
        RenjaTableUtama::create([
            'document_id' => $doc->id,
            'kode_rekening' => '1.01.01.1.01.0001',
            'uraian' => 'MARKER_CLONE_TABLE_UTAMA_001 Penyediaan BOS Daerah',
            'nama_program_kegiatan' => 'Program Sekolah',
            'indikator' => 'Jumlah Siswa Terbantu',
            'lokasi' => 'Kabupaten Cirebon',
            'target_2027' => '5000 Siswa',
            'pagu_2027' => 2500000000,
            'prakiraan_maju_target_2028' => '5500 Siswa',
            'prakiraan_maju_pagu_2028' => 2700000000,
        ]);

        return $doc;
    }

    /**
     * TEST 1: Operator berhasil melakukan clone dokumen RENJA Murni miliknya sendiri.
     */
    public function test_operator_can_clone_own_renja_murni(): void
    {
        $sourceDoc = $this->createSourceDocument($this->opdA->id, 2026);

        $response = $this->actingAs($this->operatorA)
            ->post(route('operator.renja-murni.clone-previous'), [
                'source_document_id' => $sourceDoc->id,
                'tahun_anggaran' => 2027,
            ]);

        $response->assertRedirect();
        
        $cloned = RenjaDocument::where('opd_id', $this->opdA->id)
            ->where('tahun_anggaran', 2027)
            ->first();

        $this->assertNotNull($cloned);
        $this->assertEquals('draft', $cloned->status);
        $this->assertEquals($this->opdA->id, $cloned->opd_id);
        $this->assertEquals(2027, $cloned->tahun_anggaran);
        $this->assertEquals(2027, $cloned->year);
        $this->assertNull($cloned->catatan_bapperida);
        $this->assertNull($cloned->original_document_id);
        $this->assertNull($cloned->assigned_verificator_id);
        $this->assertNull($cloned->submitted_at);
        $this->assertEquals(0, $cloned->revision_count);
        $this->assertEquals('clone', $cloned->source_type);
        $this->assertEquals($sourceDoc->id, $cloned->metadata['cloned_from_document_id']);
        $this->assertEquals(2026, $cloned->metadata['cloned_from_tahun_anggaran']);
    }

    /**
     * TEST 2: IDOR Protection: Operator ditolak (403) saat mencoba clone dokumen OPD lain.
     */
    public function test_operator_cannot_clone_other_opd_document(): void
    {
        // Source document milik OPD B (Dinkes)
        $sourceDocB = $this->createSourceDocument($this->opdB->id, 2026);

        // Operator A (Disdik) mencoba mengklon dokumen OPD B
        $response = $this->actingAs($this->operatorA)
            ->post(route('operator.renja-murni.clone-previous'), [
                'source_document_id' => $sourceDocB->id,
                'tahun_anggaran' => 2027,
            ]);

        $response->assertStatus(403);

        // Pastikan tidak ada dokumen baru yang tercipta untuk OPD A di TA 2027
        $this->assertDatabaseMissing('renja_documents', [
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2027,
        ]);
    }

    /**
     * TEST 3: Manipulasi source_document_id dengan ID acak/tidak valid ditolak (422 validation).
     */
    public function test_invalid_source_document_id_is_rejected(): void
    {
        $response = $this->actingAs($this->operatorA)
            ->post(route('operator.renja-murni.clone-previous'), [
                'source_document_id' => 999999,
                'tahun_anggaran' => 2027,
            ]);

        $response->assertSessionHasErrors('source_document_id');
    }

    /**
     * TEST 4: Manipulasi Tahun: Target year <= source year ditolak.
     */
    public function test_target_year_must_be_greater_than_source_year(): void
    {
        $sourceDoc = $this->createSourceDocument($this->opdA->id, 2026);

        $response = $this->actingAs($this->operatorA)
            ->post(route('operator.renja-murni.clone-previous'), [
                'source_document_id' => $sourceDoc->id,
                'tahun_anggaran' => 2026, // Sama dengan source
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('renja_documents', [
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2026,
            'source_type' => 'clone',
        ]);
    }

    /**
     * TEST 5: Dokumen Lampiran TIDAK BOLEH dijadikan sebagai dokumen acuan (source).
     */
    public function test_lampiran_document_cannot_be_used_as_clone_source(): void
    {
        $lampiranDoc = RenjaDocument::create([
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Lampiran Murni',
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($this->operatorA)
            ->post(route('operator.renja-murni.clone-previous'), [
                'source_document_id' => $lampiranDoc->id,
                'tahun_anggaran' => 2027,
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('renja_documents', [
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2027,
        ]);
    }

    /**
     * TEST 6: Unauthenticated user ditolak dari route clone.
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $sourceDoc = $this->createSourceDocument($this->opdA->id, 2026);

        $response = $this->post(route('operator.renja-murni.clone-previous'), [
            'source_document_id' => $sourceDoc->id,
            'tahun_anggaran' => 2027,
        ]);

        $response->assertRedirect(route('login'));
    }

    /**
     * TEST 7: Admin dapat melakukan clone dokumen OPD mana saja.
     */
    public function test_admin_can_clone_for_opd(): void
    {
        $sourceDoc = $this->createSourceDocument($this->opdA->id, 2026);

        $response = $this->actingAs($this->adminUser)
            ->post(route('renja.clonePrevious'), [
                'source_document_id' => $sourceDoc->id,
                'tahun_anggaran' => 2027,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('renja_documents', [
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2027,
            'source_type' => 'clone',
        ]);
    }

    /**
     * TEST 8: Data Section, Hierarchy, Caption, Table Eval, dan Table Utama ikut tersalin secara lengkap.
     */
    public function test_sections_captions_and_tables_are_faithfully_copied(): void
    {
        $sourceDoc = $this->createSourceDocument($this->opdA->id, 2026);

        $service = app(OpdDocumentService::class);
        $cloned = $service->cloneFromPreviousYear($sourceDoc->id, 2027, $this->opdA->id, $this->operatorA->id);

        // Sections
        $this->assertEquals(2, $cloned->sections()->count());
        $sec1 = $cloned->sections()->where('sub_bab_code', '1.1')->first();
        $this->assertNotNull($sec1);
        $this->assertNotEquals($sourceDoc->sections->first()->id, $sec1->id);
        $this->assertEquals($cloned->id, $sec1->document_id);
        $this->assertStringContainsString('MARKER_CLONE_NARASI_001', $sec1->content);

        // Captions
        $this->assertEquals(1, $sec1->captions()->count());
        $caption = $sec1->captions->first();
        $this->assertEquals('Tabel Indikator Makro', $caption->caption);
        $this->assertEquals($sec1->id, $caption->section_id);

        // Table Eval
        $this->assertEquals(1, $cloned->tableEvals()->count());
        $eval = $cloned->tableEvals->first();
        $this->assertNotEquals($sourceDoc->tableEvals->first()->id, $eval->id);
        $this->assertEquals($cloned->id, $eval->document_id);
        $this->assertStringContainsString('MARKER_CLONE_TABLE_EVAL_001', $eval->nama_program_kegiatan);

        // Table Utama
        $this->assertEquals(1, $cloned->tableUtamas()->count());
        $utama = $cloned->tableUtamas->first();
        $this->assertNotEquals($sourceDoc->tableUtamas->first()->id, $utama->id);
        $this->assertEquals($cloned->id, $utama->document_id);
        $this->assertStringContainsString('MARKER_CLONE_TABLE_UTAMA_001', $utama->uraian);
    }

    /**
     * TEST 9: Content Integrity & Independence: Mutasi pada dokumen baru tidak mempengaruhi dokumen lama.
     */
    public function test_mutations_on_cloned_document_do_not_affect_source_document(): void
    {
        $sourceDoc = $this->createSourceDocument($this->opdA->id, 2026);

        $service = app(OpdDocumentService::class);
        $cloned = $service->cloneFromPreviousYear($sourceDoc->id, 2027, $this->opdA->id, $this->operatorA->id);

        // Ubah konten di dokumen baru
        $sec1Cloned = $cloned->sections()->where('sub_bab_code', '1.1')->first();
        $sec1Cloned->update(['content' => '<p>MARKER_CLONE_NARASI_001_UPDATED</p>']);

        // Ubah tabel di dokumen baru
        $tblUtamaCloned = $cloned->tableUtamas->first();
        $tblUtamaCloned->update(['uraian' => 'MARKER_CLONE_TABLE_UTAMA_001_UPDATED']);

        // Refresh source document
        $sourceDoc->refresh();
        $sourceSec1 = $sourceDoc->sections()->where('sub_bab_code', '1.1')->first();
        $sourceTblUtama = $sourceDoc->tableUtamas->first();

        // Verifikasi dokumen lama TETAP memuat marker lama, bukan yang diupdate
        $this->assertStringContainsString('MARKER_CLONE_NARASI_001', $sourceSec1->content);
        $this->assertStringNotContainsString('MARKER_CLONE_NARASI_001_UPDATED', $sourceSec1->content);
        $this->assertEquals('MARKER_CLONE_TABLE_UTAMA_001 Penyediaan BOS Daerah', $sourceTblUtama->uraian);
    }

    /**
     * TEST 10: Duplicate Prevention: Mencegah pembuatan dokumen ganda untuk TA yang sama.
     */
    public function test_duplicate_prevention_blocks_second_clone(): void
    {
        $sourceDoc = $this->createSourceDocument($this->opdA->id, 2026);

        // Klon pertama berhasil
        $service = app(OpdDocumentService::class);
        $cloned1 = $service->cloneFromPreviousYear($sourceDoc->id, 2027, $this->opdA->id, $this->operatorA->id);
        $this->assertNotNull($cloned1);

        // Klon kedua untuk TA yang sama harus melempar Exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah tersedia');

        $service->cloneFromPreviousYear($sourceDoc->id, 2027, $this->opdA->id, $this->operatorA->id);
    }

    /**
     * TEST 11: Lampiran Live Sync & Independence:
     * Lampiran 2027 mengikuti RENJA 2027 secara live, dan Lampiran 2026 tetap mengacu pada RENJA 2026.
     */
    public function test_lampiran_live_sync_resolves_to_correct_parent(): void
    {
        $sourceDoc2026 = $this->createSourceDocument($this->opdA->id, 2026);

        // Buat Lampiran 2026
        $lampiran2026 = RenjaDocument::create([
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Lampiran Murni',
            'status' => 'draft',
        ]);

        // Clone ke 2027
        $service = app(OpdDocumentService::class);
        $cloned2027 = $service->cloneFromPreviousYear($sourceDoc2026->id, 2027, $this->opdA->id, $this->operatorA->id);

        // Buat Lampiran 2027
        $lampiran2027 = RenjaDocument::create([
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Lampiran Murni',
            'status' => 'draft',
        ]);

        // Update narasi pada RENJA 2027
        $sec2027 = $cloned2027->sections()->where('sub_bab_code', '1.1')->first();
        $sec2027->update(['content' => '<p>MARKER_NEW_2027_CONTENT</p>']);

        // Periksa effective sections lampiran
        $effective2027 = $lampiran2027->getEffectiveSections();
        $effective2026 = $lampiran2026->getEffectiveSections();

        $secInLampiran2027 = $effective2027->firstWhere('sub_bab_code', '1.1');
        $secInLampiran2026 = $effective2026->firstWhere('sub_bab_code', '1.1');

        $this->assertNotNull($secInLampiran2027);
        $this->assertNotNull($secInLampiran2026);
        $this->assertStringContainsString('MARKER_NEW_2027_CONTENT', $secInLampiran2027->content);
        $this->assertStringContainsString('MARKER_CLONE_NARASI_001', $secInLampiran2026->content);
        $this->assertStringNotContainsString('MARKER_NEW_2027_CONTENT', $secInLampiran2026->content);
    }

    /**
     * TEST 12: Master Template Immutability: Template master tidak berubah sedikitpun sebelum dan sesudah klon.
     */
    public function test_master_template_remains_immutable_after_cloning(): void
    {
        $templateBefore = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $sourceDoc = $this->createSourceDocument($this->opdA->id, 2026);

        $service = app(OpdDocumentService::class);
        $cloned = $service->cloneFromPreviousYear($sourceDoc->id, 2027, $this->opdA->id, $this->operatorA->id);

        $templateAfter = DocumentTemplate::where('code', 'RENJA_MURNI')->first();
        $this->assertEquals($templateBefore->name, $templateAfter->name);
        $this->assertEquals($templateBefore->updated_at->toIso8601String(), $templateAfter->updated_at->toIso8601String());
    }

    /**
     * TEST 13: Reset Audit Trail, Workflow, & Review Data pada dokumen baru.
     */
    public function test_workflow_and_audit_trail_are_strictly_reset(): void
    {
        $sourceDoc = $this->createSourceDocument($this->opdA->id, 2026, 'final');
        $sourceDoc->update([
            'submitted_at' => now()->subMonths(6),
            'priority_score' => 95,
            'assigned_verificator_id' => $this->adminUser->id,
            'section_review_status' => ['1.1' => 'approved', '2.1' => 'approved'],
        ]);

        $service = app(OpdDocumentService::class);
        $cloned = $service->cloneFromPreviousYear($sourceDoc->id, 2027, $this->opdA->id, $this->operatorA->id);

        $this->assertEquals('draft', $cloned->status);
        $this->assertNull($cloned->submitted_at);
        $this->assertEquals(0, $cloned->priority_score);
        $this->assertNull($cloned->assigned_verificator_id);
        $this->assertNull($cloned->section_review_status);
        $this->assertNull($cloned->catatan_bapperida);
        $this->assertNull($cloned->original_document_id);

        // Metadata baru memuat audit trail clone yang segar
        $this->assertArrayHasKey('audit_trail', $cloned->metadata);
        $trail = $cloned->metadata['audit_trail'];
        $this->assertCount(1, $trail);
        $this->assertEquals('CLONED_FROM_PREVIOUS_YEAR', $trail[0]['action']);
        $this->assertArrayNotHasKey('old_audit', $cloned->metadata);
    }

    /**
     * TEST 14: Dokumen RENJA Perubahan ditolak bila dicoba diklon menjadi acuan RENJA Murni.
     */
    public function test_cannot_clone_renja_perubahan_as_murni(): void
    {
        $perubahanDoc = RenjaDocument::create([
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Perubahan',
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($this->operatorA)
            ->post(route('operator.renja-murni.clone-previous'), [
                'source_document_id' => $perubahanDoc->id,
                'tahun_anggaran' => 2027,
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('renja_documents', [
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2027,
        ]);
    }
}
