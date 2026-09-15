<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RenjaFixRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    private function createOperator(string $opdName = 'Dinas Kesehatan'): User
    {
        $uniqueId = rand(1000, 9999);
        $opd = MasterOpd::create([
            'nama_opd' => $opdName . ' ' . $uniqueId,
            'kode_opd' => '1.02.' . $uniqueId,
            'nomor_lampiran_romawi' => 'LAMPIRAN I',
        ]);

        return User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '19950101' . $uniqueId . '1001',
            'nama_lengkap' => 'Operator ' . $opdName,
        ]);
    }

    /**
     * Test 1: Redirect dari route lama Dokumen Fix ke Arsip RENJA berjalan dengan sukses.
     */
    public function test_operator_can_open_dokumen_fix_repository(): void
    {
        $operator = $this->createOperator();

        $response = $this->followingRedirects()->actingAs($operator)->get(route('renja.fix.index'));

        $response->assertStatus(200);
        $response->assertSee('Arsip RENJA');
        $response->assertSee('Dokumen RENJA yang telah disetujui');
        $response->assertSee('REPOSITORY FINAL');
    }

    /**
     * Test 2: Hanya dokumen APPROVED / FINAL yang muncul di repository Arsip.
     */
    public function test_only_approved_documents_appear_in_fix_repository(): void
    {
        $operator = $this->createOperator();

        // 1. Dokumen Approved / Fix
        $docApproved = RenjaDocument::create([
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni Disetujui Sah',
            'status' => 'disetujui',
        ]);

        // 2. Dokumen Draft (Tidak Boleh Muncul)
        $docDraft = RenjaDocument::create([
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni Draft Rahasia',
            'status' => 'draft',
        ]);

        // 3. Dokumen Perlu Revisi (Tidak Boleh Muncul)
        $docRevisi = RenjaDocument::create([
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni Perlu Revisi',
            'status' => 'perlu_revisi',
        ]);

        $response = $this->followingRedirects()->actingAs($operator)->get(route('renja.fix.index', ['tahun_anggaran' => 2027]));

        $response->assertStatus(200);
        $response->assertSee('RENJA Murni Disetujui Sah');
        $response->assertDontSee('RENJA Murni Draft Rahasia');
        $response->assertDontSee('RENJA Murni Perlu Revisi');
    }

    /**
     * Test 3: Membuka dokumen draft melalui route dokumen-fix ditolak (403 Forbidden).
     */
    public function test_accessing_draft_document_via_fix_route_is_forbidden(): void
    {
        $operator = $this->createOperator();

        $docDraft = RenjaDocument::create([
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Draft Ilegal',
            'status' => 'draft',
        ]);

        $response = $this->followingRedirects()->actingAs($operator)->get(route('renja.fix.show', $docDraft->id));

        $response->assertStatus(403);
    }

    /**
     * Test 4: Operator OPD lain tidak dapat mengakses dokumen milik OPD berbeda.
     */
    public function test_operator_cannot_access_other_opd_fix_document(): void
    {
        $operator1 = $this->createOperator('Dinas Kesehatan');
        $operator2 = $this->createOperator('Dinas Pendidikan');

        $docOpd1 = RenjaDocument::create([
            'opd_id' => $operator1->opd_id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Dinkes Approved',
            'status' => 'disetujui',
        ]);

        // Operator 2 mencoba membuka dokumen fix milik Operator 1 -> 403 Forbidden
        $response = $this->followingRedirects()->actingAs($operator2)->get(route('renja.fix.show', $docOpd1->id));

        $response->assertStatus(403);
    }

    /**
     * Test 5: File Explorer - Membuka folder Dokumen & folder BAB melalui Arsip.
     */
    public function test_file_explorer_folder_and_bab_navigation(): void
    {
        $operator = $this->createOperator();

        $murni = RenjaDocument::create([
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni TA 2027',
            'status' => 'disetujui',
        ]);

        $sec1 = RenjaSection::create([
            'document_id' => $murni->id,
            'section_type' => 'chapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'content' => '<p>Isi Latar Belakang Resmi</p>',
            'order_index' => 1,
        ]);

        $sec2 = RenjaSection::create([
            'document_id' => $murni->id,
            'section_type' => 'chapter',
            'bab_code' => 'BAB II',
            'bab_title' => 'Evaluasi',
            'sub_bab_code' => '2.1',
            'sub_bab_title' => 'Evaluasi Kinerja',
            'content' => '<p>Isi Evaluasi Kinerja</p>',
            'order_index' => 2,
        ]);

        // 1. Buka Folder Dokumen
        $docFolderRes = $this->followingRedirects()->actingAs($operator)->get(route('renja.fix.show', $murni->id));
        $docFolderRes->assertStatus(200);
        $docFolderRes->assertSee('BAB I: Pendahuluan');
        $docFolderRes->assertSee('BAB II: Evaluasi');
        $docFolderRes->assertSee('Disetujui Bapperida');

        // 2. Buka Folder BAB I
        $babFolderRes = $this->followingRedirects()->actingAs($operator)->get(route('renja.fix.bab', [
            'id' => $murni->id,
            'babCode' => urlencode('BAB I'),
        ]));
        $babFolderRes->assertStatus(200);
        $babFolderRes->assertSee('1.1. Latar Belakang');
        $babFolderRes->assertSee('Isi Latar Belakang Resmi');
    }

    /**
     * Test 6: Sifat Dokumen Arsip adalah READ ONLY (Tidak ada tombol Edit, Hapus, Auto Fix).
     */
    public function test_dokumen_fix_is_strictly_read_only(): void
    {
        $operator = $this->createOperator();

        $murni = RenjaDocument::create([
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni Final',
            'status' => 'disetujui',
        ]);

        $response = $this->followingRedirects()->actingAs($operator)->get(route('renja.fix.show', $murni->id));

        $response->assertStatus(200);
        $response->assertDontSee('form action="' . route('renja.destroy', $murni->id) . '"', false);
        $response->assertDontSee('Validasi & Auto Fix');
        $response->assertDontSee('route(\'renja.editor\'', false);
        $response->assertDontSee('+ Tambah BAB');
        $response->assertDontSee('Simpan Perubahan');
    }

    /**
     * Test 7: Fitur Search dalam Repository Dokumen Arsip berfungsi.
     */
    public function test_search_within_fix_repository(): void
    {
        $operator = $this->createOperator();

        $murni1 = RenjaDocument::create([
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni Kesehatan Alpha',
            'status' => 'disetujui',
        ]);

        $murni2 = RenjaDocument::create([
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni Lingkungan Beta',
            'status' => 'disetujui',
        ]);

        $response = $this->followingRedirects()->actingAs($operator)->get(route('renja.fix.index', [
            'tahun_anggaran' => 2027,
            'search' => 'Alpha',
        ]));

        $response->assertStatus(200);
        $response->assertSee('RENJA Murni Kesehatan Alpha');
        $response->assertDontSee('RENJA Murni Lingkungan Beta');
    }
}
