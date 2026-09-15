<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RenjaArchiveRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    private function createOperatorWithOpd(string $namaOpd = 'Dinas Kesehatan'): array
    {
        $uniqueId = rand(1000, 9999);
        $opd = MasterOpd::create([
            'nama_opd' => $namaOpd . ' ' . $uniqueId,
            'kode_opd' => '1.02.' . $uniqueId,
            'nomor_lampiran_romawi' => 'LAMPIRAN II',
        ]);

        $user = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '19850101' . $uniqueId . '1002',
            'nama_lengkap' => 'Operator ' . $namaOpd,
        ]);

        return [$user, $opd];
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'username_nip' => 'admin_bapperida_' . rand(1000, 9999),
            'nama_lengkap' => 'Administrator Bapperida',
        ]);
    }

    /**
     * Test 1: Operator OPD dapat membuka repositori Arsip RENJA (HTTP 200).
     */
    public function test_operator_can_open_archive_repository(): void
    {
        [$operator, $opd] = $this->createOperatorWithOpd('Dinas Kominfo');

        $response = $this->actingAs($operator)->get(route('renja.archive.index'));

        $response->assertStatus(200);
        $response->assertSee('Arsip RENJA');
        $response->assertSee('Dokumen RENJA yang telah disetujui');
    }

    /**
     * Test 2: Dokumen Draft / Sedang Proses TA aktif (2027) tidak muncul di Arsip, tetapi dokumen FIX (Disetujui) masuk ke Arsip.
     */
    public function test_unapproved_draft_does_not_appear_in_archive_but_approved_fix_appears(): void
    {
        [$operator, $opd] = $this->createOperatorWithOpd('Dinas Pendidikan');

        // 1. Dokumen draft TA 2027 belum disetujui -> tidak boleh muncul di Arsip
        $draftDoc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
            'is_archived' => false,
        ]);

        $responseDraft = $this->actingAs($operator)->get(route('renja.archive.index'));
        $responseDraft->assertStatus(200);
        $responseDraft->assertDontSee('Folder Arsip TA 2027');

        // 2. Ketika dokumen telah disetujui (FIX) -> otomatis masuk ke Arsip
        $draftDoc->update(['status' => 'disetujui']);

        $responseApproved = $this->actingAs($operator)->get(route('renja.archive.index'));
        $responseApproved->assertStatus(200);
        $responseApproved->assertSee('Folder Arsip TA 2027');
    }

    /**
     * Test 3: Dokumen dari tahun sebelumnya (e.g. TA 2026, TA 2025) otomatis muncul di Arsip.
     */
    public function test_prior_year_documents_appear_in_archive(): void
    {
        [$operator, $opd] = $this->createOperatorWithOpd('Dinas Perhubungan');

        // Dokumen historis TA 2026
        $doc2026 = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'disetujui',
            'is_archived' => false,
        ]);

        $response = $this->actingAs($operator)->get(route('renja.archive.index'));

        $response->assertStatus(200);
        $response->assertSee('Folder Arsip TA 2026');
        $response->assertSee('Total');
    }

    /**
     * Test 4: Dokumen yang dieksplisitkan diarsipkan (is_archived = true) muncul di Arsip.
     */
    public function test_explicitly_archived_documents_appear_in_archive(): void
    {
        [$operator, $opd] = $this->createOperatorWithOpd('Dinas Pariwisata');

        // Dokumen aktif yang diarsipkan oleh Admin
        $docArchived = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Perubahan',
            'status' => 'disetujui',
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        $response = $this->actingAs($operator)->get(route('renja.archive.index'));

        $response->assertStatus(200);
        $response->assertSee('Folder Arsip TA 2027');
    }

    /**
     * Test 5: Dokumen arsip bersifat Read-Only dan mempertahankan status approval asli.
     */
    public function test_archived_documents_retain_original_approval_status_and_are_read_only(): void
    {
        [$operator, $opd] = $this->createOperatorWithOpd('Bapenda');

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2025,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'disetujui',
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        $sec = RenjaSection::create([
            'document_id' => $doc->id,
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang Historis',
            'content' => '<p>Konten historis rencana kerja tahun 2025.</p>',
            'order_index' => 1,
            'is_completed' => true,
        ]);

        // 1. Buka Level 3: Folder Dokumen Arsip
        $responseDoc = $this->actingAs($operator)->get(route('renja.archive.document', $doc->id));
        $responseDoc->assertStatus(200);
        $responseDoc->assertSee('DOKUMEN ARSIP');
        $responseDoc->assertSee('BAB I: Pendahuluan');
        $responseDoc->assertDontSee('Buka Smart Editor');
        $responseDoc->assertDontSee('Simpan Perubahan');

        // 2. Buka Level 4: Folder BAB I
        $responseBab = $this->actingAs($operator)->get(route('renja.archive.bab', ['id' => $doc->id, 'babCode' => urlencode('BAB I')]));
        $responseBab->assertStatus(200);
        $responseBab->assertSee('1.1');
        $responseBab->assertSee('Latar Belakang Historis');
        $responseBab->assertSee('READ-ONLY');

        // 3. Buka Level 5: Viewer Section
        $responseSec = $this->actingAs($operator)->get(route('renja.archive.section', ['id' => $doc->id, 'sectionId' => $sec->id]));
        $responseSec->assertStatus(200);
        $responseSec->assertSee('Konten historis rencana kerja tahun 2025');
    }

    /**
     * Test 6: Operator OPD tidak dapat mengakses arsip milik OPD lain (Isolasi OPD & Security Guard).
     */
    public function test_operator_cannot_access_other_opd_archive(): void
    {
        [$operatorA, $opdA] = $this->createOperatorWithOpd('Dinas A');
        [$operatorB, $opdB] = $this->createOperatorWithOpd('Dinas B');

        $docB = RenjaDocument::create([
            'opd_id' => $opdB->id,
            'tahun_anggaran' => 2025,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'disetujui',
        ]);

        // Operator A mencoba membuka dokumen arsip milik OPD B
        $response = $this->actingAs($operatorA)->get(route('renja.archive.document', $docB->id));

        $response->assertStatus(403);
    }

    /**
     * Test 7: Admin Bapperida dapat melakukan arsip dan restore dokumen.
     */
    public function test_admin_can_archive_and_restore_document(): void
    {
        $admin = $this->createAdminUser();
        [$operator, $opd] = $this->createOperatorWithOpd('Dinas Sosial');

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'disetujui',
            'is_archived' => false,
        ]);

        // 1. Admin arsipkan dokumen
        $archiveRes = $this->actingAs($admin)->post(route('renja.archive.archive', $doc->id), [
            'notes' => 'Diarsipkan untuk keperluan referensi audit.',
        ]);

        $archiveRes->assertSessionHas('success');
        $this->assertTrue($doc->fresh()->is_archived);
        $this->assertNotNull($doc->fresh()->archived_at);

        // 2. Admin restore dokumen
        $restoreRes = $this->actingAs($admin)->post(route('renja.archive.restore', $doc->id));

        $restoreRes->assertSessionHas('success');
        $this->assertFalse($doc->fresh()->is_archived);
        $this->assertNull($doc->fresh()->archived_at);
    }

    /**
     * Test 8: Operator tidak memiliki hak untuk mengarsipkan atau merestore dokumen (HTTP 403).
     */
    public function test_operator_cannot_archive_or_restore_document(): void
    {
        [$operator, $opd] = $this->createOperatorWithOpd('Dinas Lingkungan Hidup');

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'disetujui',
        ]);

        // Operator coba arsipkan
        $resArchive = $this->actingAs($operator)->post(route('renja.archive.archive', $doc->id));
        $resArchive->assertStatus(403);

        // Operator coba restore
        $resRestore = $this->actingAs($operator)->post(route('renja.archive.restore', $doc->id));
        $resRestore->assertStatus(403);
    }

    /**
     * Test 9: Navigasi File Explorer berjenjang (Tahun -> Dokumen -> BAB -> Subbab).
     */
    public function test_file_explorer_hierarchy_navigation_in_archive(): void
    {
        [$operator, $opd] = $this->createOperatorWithOpd('Dinas PUPR');

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2024,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'disetujui',
        ]);

        $sec = RenjaSection::create([
            'document_id' => $doc->id,
            'bab_code' => 'BAB II',
            'bab_title' => 'Evaluasi Kinerja',
            'sub_bab_code' => '2.1',
            'sub_bab_title' => 'Capaian Renja Lalu',
            'content' => '<p>Evaluasi capaian kinerja PUPR tahun 2024.</p>',
            'order_index' => 10,
            'is_completed' => true,
        ]);

        // 1. Buka Folder TA 2024
        $resYear = $this->actingAs($operator)->get(route('renja.archive.year', 2024));
        $resYear->assertStatus(200);
        $resYear->assertSee('RENJA Murni TA 2024');

        // 2. Buka Folder Dokumen
        $resDoc = $this->actingAs($operator)->get(route('renja.archive.document', $doc->id));
        $resDoc->assertStatus(200);
        $resDoc->assertSee('BAB II: Evaluasi Kinerja');

        // 3. Buka Folder BAB II
        $resBab = $this->actingAs($operator)->get(route('renja.archive.bab', ['id' => $doc->id, 'babCode' => urlencode('BAB II')]));
        $resBab->assertStatus(200);
        $resBab->assertSee('2.1');
        $resBab->assertSee('Capaian Renja Lalu');
    }

    /**
     * Test 10: Pencarian dan filter dalam repositori Arsip.
     */
    public function test_search_and_filter_in_archive_repository(): void
    {
        [$operator, $opd] = $this->createOperatorWithOpd('Bappelitbangda');

        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2023,
            'jenis_dokumen' => 'RENJA Perubahan',
            'status' => 'disetujui',
        ]);

        RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2024,
            'jenis_dokumen' => 'Lampiran Perbub',
            'status' => 'disetujui',
        ]);

        // Search: 'Perubahan'
        $searchRes = $this->actingAs($operator)->get(route('renja.archive.index', ['search' => 'Perubahan']));
        $searchRes->assertStatus(200);
        $searchRes->assertSee('Folder Arsip TA 2023');
        $searchRes->assertDontSee('Folder Arsip TA 2024');
    }
}
