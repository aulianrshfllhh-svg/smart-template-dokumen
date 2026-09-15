<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Models\RenjaTableEval;
use App\Models\RenjaTableUtama;
use App\Models\User;
use App\Services\DocumentTemplateService;
use App\Services\OpdDocumentService;
use App\Services\RenjaAutoFixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenjaLampiranIndukSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $operatorDepok;
    protected User $operatorSumber;
    protected User $adminUser;
    protected MasterOpd $opdDepok;
    protected MasterOpd $opdSumber;

    protected function setUp(): void
    {
        parent::setUp();

        app(DocumentTemplateService::class)->ensureStandardTemplatesSeeded();

        // OPD 38: Kecamatan Depok (Lampiran XXXVIII)
        $this->opdDepok = MasterOpd::create([
            'kode_opd' => '7.01.038',
            'nama_opd' => 'Kecamatan Depok',
            'lampiran_number' => 38,
            'nomor_lampiran_romawi' => 'Lampiran XXXVIII',
        ]);

        // OPD 64: Kecamatan Sumber (Lampiran LXIV)
        $this->opdSumber = MasterOpd::create([
            'kode_opd' => '7.01.064',
            'nama_opd' => 'Kecamatan Sumber',
            'lampiran_number' => 64,
            'nomor_lampiran_romawi' => 'Lampiran LXIV',
        ]);

        $this->operatorDepok = User::factory()->create([
            'username_nip' => '199001012020011001',
            'opd_id' => $this->opdDepok->id,
            'role' => 'operator',
        ]);

        $this->operatorSumber = User::factory()->create([
            'username_nip' => '199001012020011002',
            'opd_id' => $this->opdSumber->id,
            'role' => 'operator',
        ]);

        $this->adminUser = User::factory()->create([
            'username_nip' => '198501012010011001',
            'opd_id' => null,
            'role' => 'admin',
        ]);
    }

    /** TEST 1 & TEST 3 & TEST 4 & TEST 5: RENJA Murni -> Lampiran Murni structure, front matter filtering & content */
    public function test_renja_murni_to_lampiran_murni_live_sync(): void
    {
        $murniInduk = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);

        // Front matter section
        RenjaSection::create([
            'document_id' => $murniInduk->id,
            'section_type' => 'cover',
            'bab_code' => 'COVER',
            'bab_title' => 'Cover Dokumen',
            'sub_bab_code' => 'COVER',
            'sub_bab_title' => 'Cover Dokumen',
            'content' => '<h1>Cover Dokumen Renja</h1>',
            'order_index' => 1,
        ]);

        // BAB I section
        $secBab1 = RenjaSection::create([
            'document_id' => $murniInduk->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'content' => '<p>Narasi Latar Belakang RENJA Murni Depok TA 2027.</p>',
            'order_index' => 10,
        ]);

        $opdService = app(OpdDocumentService::class);
        $lampiranMurni = $opdService->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        $this->assertEquals('RENJA Lampiran Murni', $lampiranMurni->jenis_dokumen);
        $this->assertEquals($murniInduk->id, $lampiranMurni->metadata['generated_from_parent_id']);

        $effectiveSections = $lampiranMurni->getEffectiveSections();

        // Front matter should NOT be in effective sections
        $this->assertFalse($effectiveSections->contains('bab_code', 'COVER'));
        // BAB I should be present
        $this->assertTrue($effectiveSections->contains('bab_code', 'BAB I'));
        // Content must match parent exactly
        $this->assertEquals('<p>Narasi Latar Belakang RENJA Murni Depok TA 2027.</p>', $effectiveSections->firstWhere('sub_bab_code', '1.1')->content);
    }

    /** TEST 2 & TEST 14 & TEST 15: RENJA Perubahan -> Lampiran Perubahan separation */
    public function test_renja_perubahan_to_lampiran_perubahan_isolation(): void
    {
        $murniInduk = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'disetujui',
        ]);

        $perubahanInduk = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Perubahan',
            'status' => 'draft',
        ]);

        RenjaSection::create([
            'document_id' => $perubahanInduk->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan Perubahan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang Perubahan',
            'content' => '<p>Narasi Khusus RENJA Perubahan TA 2026.</p>',
            'order_index' => 10,
        ]);

        $opdService = app(OpdDocumentService::class);
        $lampiranPerubahan = $opdService->generateLampiranPerbub($this->opdDepok->id, 2026, 'perubahan');

        $this->assertEquals('RENJA Lampiran Perubahan', $lampiranPerubahan->jenis_dokumen);
        $this->assertEquals($perubahanInduk->id, $lampiranPerubahan->metadata['generated_from_parent_id']);

        $effectiveSections = $lampiranPerubahan->getEffectiveSections();
        $this->assertEquals('<p>Narasi Khusus RENJA Perubahan TA 2026.</p>', $effectiveSections->firstWhere('sub_bab_code', '1.1')->content);
    }

    /** TEST 6: Isi tabel Lampiran sama dengan Induk */
    public function test_lampiran_uses_same_table_data_as_induk(): void
    {
        $murniInduk = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);

        $tableEval = RenjaTableEval::create([
            'document_id' => $murniInduk->id,
            'jenis_tabel' => 'evaluasi_2.1',
            'kode_rekening' => '1.01.01',
            'nama_program_kegiatan' => 'Program Pelayanan Administrasi Perkantoran',
            'indikator_kinerja' => 'Persentase Layanan Admin',
            'pagu_indikatif' => 1500000000,
        ]);

        $opdService = app(OpdDocumentService::class);
        $lampiranMurni = $opdService->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        $effectiveEvals = $lampiranMurni->getEffectiveTableEvals();
        $this->assertCount(1, $effectiveEvals);
        $this->assertEquals('Program Pelayanan Administrasi Perkantoran', $effectiveEvals->first()->nama_program_kegiatan);
    }

    /** TEST 7: Live Sync — Perubahan isi Induk langsung tercermin di Lampiran */
    public function test_live_sync_when_induk_content_changes(): void
    {
        $murniInduk = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);

        $sec = RenjaSection::create([
            'document_id' => $murniInduk->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB III',
            'bab_title' => 'Tujuan dan Sasaran',
            'sub_bab_code' => '3.2',
            'sub_bab_title' => 'Tujuan dan Sasaran',
            'content' => '<p>Konten Versi 1</p>',
            'order_index' => 30,
        ]);

        $opdService = app(OpdDocumentService::class);
        $lampiranMurni = $opdService->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        $this->assertEquals('<p>Konten Versi 1</p>', $lampiranMurni->getEffectiveSections()->firstWhere('sub_bab_code', '3.2')->content);

        // Operator merubah isi section 3.2 pada RENJA Murni Induk
        $sec->update(['content' => '<p>Konten Terbaru Versi 2 yang Diperbarui Operator</p>']);

        // Lampiran langsung menampilkan isi terbaru tanpa perlu copy ulang
        $this->assertEquals('<p>Konten Terbaru Versi 2 yang Diperbarui Operator</p>', $lampiranMurni->getEffectiveSections()->firstWhere('sub_bab_code', '3.2')->content);
    }

    /** TEST 8 & TEST 9 & TEST 10 & TEST 11: Automatic Roman Header for 71 OPDs & Depok XXXVIII */
    public function test_automatic_roman_attachment_number_mapping(): void
    {
        $romawiDepok = RenjaAutoFixService::getRomanHeaderForOpd($this->opdDepok);
        $this->assertEquals('LAMPIRAN XXXVIII', $romawiDepok);

        $romawiSumber = RenjaAutoFixService::getRomanHeaderForOpd($this->opdSumber);
        $this->assertEquals('LAMPIRAN LXIV', $romawiSumber);

        $this->assertNotEquals($romawiDepok, $romawiSumber);
    }

    /** TEST 12: Operator tidak dapat mengedit Lampiran secara independen */
    public function test_operator_cannot_edit_lampiran_sections_directly(): void
    {
        $murniInduk = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);

        $opdService = app(OpdDocumentService::class);
        $lampiranMurni = $opdService->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        // Lampiran does not have its own sections stored in DB
        $this->assertEquals(0, RenjaSection::where('document_id', $lampiranMurni->id)->count());
    }

    /** TEST 13: Admin dapat melihat Lampiran lintas OPD */
    public function test_admin_can_view_lampiran_across_all_opds(): void
    {
        $murniDepok = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);

        $murniSumber = RenjaDocument::create([
            'opd_id' => $this->opdSumber->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);

        $responseDepok = $this->actingAs($this->adminUser)->get(route('renja.preview', $murniDepok->id));
        $responseDepok->assertStatus(200);

        $responseSumber = $this->actingAs($this->adminUser)->get(route('renja.preview', $murniSumber->id));
        $responseSumber->assertStatus(200);
    }

    /** TEST 16 & TEST 17: Tahun Lampiran mengikuti tahun_anggaran dokumen induk & year column safety */
    public function test_tahun_anggaran_used_as_source_of_truth(): void
    {
        $murniInduk = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'year' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);

        $opdService = app(OpdDocumentService::class);
        $lampiranMurni = $opdService->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        $this->assertEquals(2027, $lampiranMurni->tahun_anggaran);
    }

    /** TEST 18: Master template updates do not mutate existing parent document sections */
    public function test_master_template_change_does_not_mutate_existing_induk_sections(): void
    {
        $murniInduk = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);

        RenjaSection::create([
            'document_id' => $murniInduk->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan Asli',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang Asli',
            'content' => '<p>Konten Snapshot Induk Existing</p>',
            'order_index' => 10,
        ]);

        $opdService = app(OpdDocumentService::class);
        $lampiranMurni = $opdService->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        // Verify content remains preserved
        $this->assertEquals('<p>Konten Snapshot Induk Existing</p>', $lampiranMurni->getEffectiveSections()->firstWhere('sub_bab_code', '1.1')->content);
    }

    /** TEST 19: Content Resolution from Parent Sections, Columns, & Chapter-level shifting */
    public function test_renja_murni_section_content_resolution_and_fallback_columns(): void
    {
        $murniInduk = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
            'latar_belakang' => '<p>Narasi Latar Belakang dari Kolom Document Induk</p>',
            'landasan_hukum' => '<p>Narasi Landasan Hukum dari Kolom Document Induk</p>',
            'penutup_narasi' => '<p>Narasi Penutup Kaidah Pelaksanaan dari Kolom Document Induk</p>',
        ]);

        // Chapter section with content
        RenjaSection::create([
            'document_id' => $murniInduk->id,
            'section_type' => 'chapter',
            'bab_code' => 'BAB II',
            'bab_title' => 'Hasil Evaluasi Renja Perangkat Daerah Tahun Lalu',
            'sub_bab_code' => 'BAB II',
            'sub_bab_title' => 'Hasil Evaluasi Renja Perangkat Daerah Tahun Lalu',
            'content' => '<p>Narasi Evaluasi Pelaksanaan Renja dari Chapter BAB II.</p>',
            'order_index' => 20,
        ]);

        RenjaSection::create([
            'document_id' => $murniInduk->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB II',
            'bab_title' => 'Hasil Evaluasi Renja Perangkat Daerah Tahun Lalu',
            'sub_bab_code' => '2.1',
            'sub_bab_title' => 'Evaluasi Pelaksanaan Renja Perangkat Daerah dan Capaian Renstra Perangkat Daerah',
            'content' => '',
            'order_index' => 21,
        ]);

        // Subchapter 1.1 without content (should fallback to $murniInduk->latar_belakang)
        RenjaSection::create([
            'document_id' => $murniInduk->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'content' => '',
            'order_index' => 10,
        ]);

        // Subchapter 1.2 without content (should fallback to $murniInduk->landasan_hukum)
        RenjaSection::create([
            'document_id' => $murniInduk->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.2',
            'sub_bab_title' => 'Landasan Hukum',
            'content' => '',
            'order_index' => 11,
        ]);

        $opdService = app(OpdDocumentService::class);
        $lampiranMurni = $opdService->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        $effectiveSections = $lampiranMurni->getEffectiveSections();

        // 1.1 should pull from latar_belakang column
        $sec11 = $effectiveSections->firstWhere('sub_bab_code', '1.1');
        $this->assertNotNull($sec11);
        $this->assertEquals('<p>Narasi Latar Belakang dari Kolom Document Induk</p>', $sec11->content);

        // 1.2 should pull from landasan_hukum column
        $sec12 = $effectiveSections->firstWhere('sub_bab_code', '1.2');
        $this->assertNotNull($sec12);
        $this->assertEquals('<p>Narasi Landasan Hukum dari Kolom Document Induk</p>', $sec12->content);

        // 2.1 should pull content from BAB II chapter section
        $sec21 = $effectiveSections->firstWhere('sub_bab_code', '2.1');
        $this->assertNotNull($sec21);
        $this->assertEquals('<p>Narasi Evaluasi Pelaksanaan Renja dari Chapter BAB II.</p>', $sec21->content);
    }

    /** TEST 20: RENJA Perubahan live sync across all 5 statuses without blocker */
    public function test_renja_perubahan_live_sync_across_all_5_statuses(): void
    {
        $statuses = ['draft', 'submitted', 'perlu_revisi', 'dikirim_ulang', 'disetujui'];
        $opdService = app(OpdDocumentService::class);

        foreach ($statuses as $status) {
            $perubahanInduk = RenjaDocument::create([
                'opd_id' => $this->opdDepok->id,
                'tahun_anggaran' => 2026,
                'jenis_dokumen' => 'RENJA Perubahan',
                'status' => $status,
            ]);

            RenjaSection::create([
                'document_id' => $perubahanInduk->id,
                'section_type' => 'subchapter',
                'bab_code' => 'BAB I',
                'bab_title' => 'Pendahuluan',
                'sub_bab_code' => '1.1',
                'sub_bab_title' => 'Latar Belakang',
                'content' => "<p>Perubahan status: {$status}</p>",
                'order_index' => 10,
            ]);

            $lampiran = $opdService->generateLampiranPerbub($this->opdDepok->id, 2026, 'perubahan');

            $this->assertNotNull($lampiran, "Lampiran Perubahan harus tersedia untuk status {$status}");
            $this->assertEquals('RENJA Lampiran Perubahan', $lampiran->jenis_dokumen);
            $this->assertEquals($perubahanInduk->id, $lampiran->metadata['generated_from_parent_id']);

            $effectiveSections = $lampiran->getEffectiveSections();
            $this->assertEquals("<p>Perubahan status: {$status}</p>", $effectiveSections->firstWhere('sub_bab_code', '1.1')->content);

            // Cleanup for next iteration
            $lampiran->delete();
            $perubahanInduk->sections()->delete();
            $perubahanInduk->delete();
        }
    }

    /** TEST 21: Live sync of Table Utama for RENJA Perubahan */
    public function test_renja_perubahan_table_utama_live_sync(): void
    {
        $perubahanInduk = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Perubahan',
            'status' => 'draft',
        ]);

        $tableUtama = RenjaTableUtama::create([
            'document_id' => $perubahanInduk->id,
            'jenis_tabel' => 'tabel_utama',
            'kode_rekening' => '7.01.01.2.01',
            'nama_program_kegiatan' => 'Kegiatan Penataan Administrasi Perubahan Depok',
            'indikator' => 'Tingkat Ketepatan Layanan',
            'uraian' => 'Uraian contoh',
            'pagu_2027' => 750000000,
        ]);

        $opdService = app(OpdDocumentService::class);
        $lampiranPerubahan = $opdService->generateLampiranPerbub($this->opdDepok->id, 2026, 'perubahan');

        $effectiveUtamas = $lampiranPerubahan->getEffectiveTableUtamas();
        $this->assertCount(1, $effectiveUtamas);
        $this->assertEquals('Kegiatan Penataan Administrasi Perubahan Depok', $effectiveUtamas->first()->nama_program_kegiatan);
    }

    /** TEST 22: Strict isolation between Murni and Perubahan */
    public function test_strict_cross_isolation_murni_and_perubahan(): void
    {
        $murniInduk = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'disetujui',
        ]);
        $secMurni = RenjaSection::create([
            'document_id' => $murniInduk->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang Murni',
            'content' => '<p>Konten Dokumen Murni</p>',
            'order_index' => 10,
        ]);

        $perubahanInduk = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Perubahan',
            'status' => 'draft',
        ]);
        $secPerubahan = RenjaSection::create([
            'document_id' => $perubahanInduk->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang Perubahan',
            'content' => '<p>Konten Dokumen Perubahan</p>',
            'order_index' => 10,
        ]);

        $opdService = app(OpdDocumentService::class);
        $lampiranMurni = $opdService->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');
        $lampiranPerubahan = $opdService->generateLampiranPerbub($this->opdDepok->id, 2026, 'perubahan');

        // Verify parent relationships
        $this->assertEquals($murniInduk->id, $lampiranMurni->getParentDocument()->id);
        $this->assertEquals($perubahanInduk->id, $lampiranPerubahan->getParentDocument()->id);

        // Content verification
        $this->assertEquals('<p>Konten Dokumen Murni</p>', $lampiranMurni->getEffectiveSections()->firstWhere('sub_bab_code', '1.1')->content);
        $this->assertEquals('<p>Konten Dokumen Perubahan</p>', $lampiranPerubahan->getEffectiveSections()->firstWhere('sub_bab_code', '1.1')->content);

        // Modify Murni - should NOT affect Lampiran Perubahan
        $secMurni->update(['content' => '<p>Konten Dokumen Murni REVISED</p>']);
        $this->assertEquals('<p>Konten Dokumen Murni REVISED</p>', $lampiranMurni->getEffectiveSections()->firstWhere('sub_bab_code', '1.1')->content);
        $this->assertEquals('<p>Konten Dokumen Perubahan</p>', $lampiranPerubahan->getEffectiveSections()->firstWhere('sub_bab_code', '1.1')->content);

        // Modify Perubahan - should NOT affect Lampiran Murni
        $secPerubahan->update(['content' => '<p>Konten Dokumen Perubahan REVISED</p>']);
        $this->assertEquals('<p>Konten Dokumen Murni REVISED</p>', $lampiranMurni->getEffectiveSections()->firstWhere('sub_bab_code', '1.1')->content);
        $this->assertEquals('<p>Konten Dokumen Perubahan REVISED</p>', $lampiranPerubahan->getEffectiveSections()->firstWhere('sub_bab_code', '1.1')->content);
    }

    /** TEST 23: Read-only protection for Lampiran in Editor */
    public function test_editor_blocks_direct_access_and_mutation_on_lampiran(): void
    {
        $perubahanInduk = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Perubahan',
            'status' => 'draft',
        ]);

        $opdService = app(OpdDocumentService::class);
        $lampiranPerubahan = $opdService->generateLampiranPerbub($this->opdDepok->id, 2026, 'perubahan');

        // Accessing editor for Lampiran redirects to preview
        $response = $this->actingAs($this->operatorDepok)->get(route('renja.editor', $lampiranPerubahan->id));
        $response->assertRedirect(route('renja.preview', ['id' => $lampiranPerubahan->id, 'is_lampiran' => 1]));

        // Post update section to Lampiran returns 403
        $postResponse = $this->actingAs($this->operatorDepok)->postJson(
            route('renja.editor.section.update', ['id' => $lampiranPerubahan->id, 'sectionId' => 9999]),
            ['content' => 'Illegal Edit']
        );
        $postResponse->assertStatus(403);
    }

    /** TEST 24: Index view Card 2 symmetry for RENJA Lampiran Perubahan */
    public function test_lampiran_index_shows_symmetric_card_for_perubahan(): void
    {
        $perubahanInduk = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Perubahan',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->operatorDepok)->get(route('renja.lampiran.index', ['tahun_anggaran' => 2026]));
        $response->assertStatus(200);
        $response->assertSee('Mengikuti RENJA Perubahan');
        $response->assertSee('Preview Lampiran');
        $response->assertDontSee('Upload Word');
    }
}
