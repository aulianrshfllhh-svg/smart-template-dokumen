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

class RenjaLampiranPreviewTest extends TestCase
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

    /** TEST 1: RENJA Murni = draft -> Preview Lampiran Murni dapat dibuka */
    public function test_preview_accessible_when_parent_is_draft(): void
    {
        $murniDepok = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);

        $lampiranMurni = app(OpdDocumentService::class)->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        $response = $this->actingAs($this->operatorDepok)->get(route('operator.renja-lampiran.preview', $lampiranMurni->id));
        $response->assertStatus(200);
        $response->assertSee('RENJA Lampiran Murni');
        $response->assertSee('LAMPIRAN XXXVIII');
    }

    /** TEST 2: RENJA Murni = submitted / Menunggu Verifikasi -> Preview Lampiran Murni dapat dibuka */
    public function test_preview_accessible_when_parent_is_submitted_or_menunggu_verifikasi(): void
    {
        $murniDepok = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $lampiranMurni = app(OpdDocumentService::class)->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        // Test accessing via dedicated lampiran preview route with lampiran ID
        $response = $this->actingAs($this->operatorDepok)->get(route('operator.renja-lampiran.preview', $lampiranMurni->id));
        $response->assertStatus(200);
        $response->assertSee('RENJA Lampiran Murni');

        // Test accessing via dedicated lampiran preview route with parent ID (graceful handling)
        $responseParent = $this->actingAs($this->operatorDepok)->get(route('operator.renja-lampiran.preview', $murniDepok->id));
        $responseParent->assertStatus(200);
        $responseParent->assertSee('RENJA Lampiran Murni');
    }

    /** TEST 3: RENJA Murni = perlu_revisi -> Preview Lampiran Murni dapat dibuka */
    public function test_preview_accessible_when_parent_is_perlu_revisi(): void
    {
        $murniDepok = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'perlu_revisi',
        ]);

        $lampiranMurni = app(OpdDocumentService::class)->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        $response = $this->actingAs($this->operatorDepok)->get(route('operator.renja-lampiran.preview', $lampiranMurni->id));
        $response->assertStatus(200);
    }

    /** TEST 4: RENJA Murni = dikirim_ulang -> Preview Lampiran Murni dapat dibuka */
    public function test_preview_accessible_when_parent_is_dikirim_ulang(): void
    {
        $murniDepok = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'dikirim_ulang',
        ]);

        $lampiranMurni = app(OpdDocumentService::class)->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        $response = $this->actingAs($this->operatorDepok)->get(route('operator.renja-lampiran.preview', $lampiranMurni->id));
        $response->assertStatus(200);
    }

    /** TEST 5: RENJA Murni = disetujui -> Preview Lampiran Murni dapat dibuka */
    public function test_preview_accessible_when_parent_is_disetujui(): void
    {
        $murniDepok = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'disetujui',
        ]);

        $lampiranMurni = app(OpdDocumentService::class)->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        $response = $this->actingAs($this->operatorDepok)->get(route('operator.renja-lampiran.preview', $lampiranMurni->id));
        $response->assertStatus(200);
    }

    /** Front matter filtering */
    public function test_front_matter_is_filtered_out_from_lampiran_murni_effective_sections(): void
    {
        $murniDepok = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        // Add Cover & Preface
        RenjaSection::create([
            'document_id' => $murniDepok->id,
            'section_type' => 'cover',
            'bab_code' => 'COVER',
            'bab_title' => 'Cover Dokumen',
            'sub_bab_code' => 'COVER',
            'sub_bab_title' => 'Cover Dokumen',
            'content' => '<h1>Cover</h1>',
            'order_index' => 1,
        ]);
        RenjaSection::create([
            'document_id' => $murniDepok->id,
            'section_type' => 'preface',
            'bab_code' => 'PREFACE',
            'bab_title' => 'Kata Pengantar',
            'sub_bab_code' => 'PREFACE',
            'sub_bab_title' => 'Kata Pengantar',
            'content' => '<p>Kata Pengantar</p>',
            'order_index' => 2,
        ]);

        // Add BAB I
        RenjaSection::create([
            'document_id' => $murniDepok->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'content' => '<p>Latar Belakang Depok 2027</p>',
            'order_index' => 10,
        ]);

        $lampiranMurni = app(OpdDocumentService::class)->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');
        $effectiveSections = $lampiranMurni->getEffectiveSections();

        $this->assertCount(1, $effectiveSections);
        $this->assertEquals('BAB I', $effectiveSections->first()->bab_code);
        $this->assertEquals('<p>Latar Belakang Depok 2027</p>', $effectiveSections->first()->content);
    }

    /** Shared sections & tables with parent even when status is submitted */
    public function test_lampiran_murni_shares_sections_and_tables_with_parent(): void
    {
        $murniDepok = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        RenjaSection::create([
            'document_id' => $murniDepok->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB II',
            'bab_title' => 'Evaluasi',
            'sub_bab_code' => '2.1',
            'sub_bab_title' => 'Evaluasi Renja Lalu',
            'content' => '<p>Konten Evaluasi</p>',
            'order_index' => 20,
        ]);

        RenjaTableEval::create([
            'document_id' => $murniDepok->id,
            'jenis_tabel' => 'evaluasi_2.1',
            'kode_rekening' => '7.01.01',
            'nama_program_kegiatan' => 'Program Utama Kecamatan Depok',
            'indikator_kinerja' => 'Persentase Layanan Publik',
            'pagu_indikatif' => 2500000000,
        ]);

        $lampiranMurni = app(OpdDocumentService::class)->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        $this->assertEquals('<p>Konten Evaluasi</p>', $lampiranMurni->getEffectiveSections()->firstWhere('sub_bab_code', '2.1')->content);
        $this->assertCount(1, $lampiranMurni->getEffectiveTableEvals());
        $this->assertEquals('Program Utama Kecamatan Depok', $lampiranMurni->getEffectiveTableEvals()->first()->nama_program_kegiatan);
    }

    /** TEST 7: Live sync narrative changes */
    public function test_live_sync_updates_lampiran_murni_instantly(): void
    {
        $murniDepok = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $sec = RenjaSection::create([
            'document_id' => $murniDepok->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB IV',
            'bab_title' => 'Rencana Kerja',
            'sub_bab_code' => '4.1',
            'sub_bab_title' => 'Program Kegiatan',
            'content' => '<p>Versi Awal 1.0</p>',
            'order_index' => 40,
        ]);

        $lampiranMurni = app(OpdDocumentService::class)->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');
        $this->assertEquals('<p>Versi Awal 1.0</p>', $lampiranMurni->getEffectiveSections()->firstWhere('sub_bab_code', '4.1')->content);

        // Update narrative in RENJA Murni
        $sec->update(['content' => '<p>Versi Terkini 2.0 setelah pengeditan Operator</p>']);

        // Refreshed live sections must reflect the edit immediately
        $this->assertEquals('<p>Versi Terkini 2.0 setelah pengeditan Operator</p>', $lampiranMurni->getEffectiveSections()->firstWhere('sub_bab_code', '4.1')->content);
    }

    /** TEST 9: Nomor Lampiran Romawi otomatis dari mapping OPD */
    public function test_roman_header_auto_mapping_for_opd(): void
    {
        $headerDepok = RenjaAutoFixService::getRomanHeaderForOpd($this->opdDepok);
        $this->assertEquals('LAMPIRAN XXXVIII', $headerDepok);

        $headerSumber = RenjaAutoFixService::getRomanHeaderForOpd($this->opdSumber);
        $this->assertEquals('LAMPIRAN LXIV', $headerSumber);
    }

    /** TEST 6: Operator tidak dapat menyimpan sections khusus pada Lampiran */
    public function test_operator_cannot_create_standalone_sections_on_lampiran(): void
    {
        $murniDepok = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $lampiranMurni = app(OpdDocumentService::class)->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        // Lampiran document record in DB has zero own sections
        $this->assertEquals(0, RenjaSection::where('document_id', $lampiranMurni->id)->count());
    }

    /** Admin Bapperida & Operator dapat melihat Preview Lampiran */
    public function test_admin_and_operator_access_to_lampiran_preview(): void
    {
        $murniDepok = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $lampiranMurni = app(OpdDocumentService::class)->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');

        // Operator Depok can access Depok Lampiran when submitted
        $respOp = $this->actingAs($this->operatorDepok)->get(route('renja.preview', $lampiranMurni->id));
        $respOp->assertStatus(200);

        // Admin Bapperida can access Depok Lampiran when submitted
        $respAdmin = $this->actingAs($this->adminUser)->get(route('renja.preview', $lampiranMurni->id));
        $respAdmin->assertStatus(200);
    }

    /** TEST 10: Data antar OPD tidak tertukar */
    public function test_opd_data_isolation(): void
    {
        $murniDepok = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);
        RenjaSection::create([
            'document_id' => $murniDepok->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'content' => '<p>Konten Depok Only</p>',
        ]);

        $murniSumber = RenjaDocument::create([
            'opd_id' => $this->opdSumber->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);
        RenjaSection::create([
            'document_id' => $murniSumber->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'content' => '<p>Konten Sumber Only</p>',
        ]);

        $lampiranDepok = app(OpdDocumentService::class)->generateLampiranPerbub($this->opdDepok->id, 2027, 'murni');
        $lampiranSumber = app(OpdDocumentService::class)->generateLampiranPerbub($this->opdSumber->id, 2027, 'murni');

        $this->assertEquals('<p>Konten Depok Only</p>', $lampiranDepok->getEffectiveSections()->firstWhere('sub_bab_code', '1.1')->content);
        $this->assertEquals('<p>Konten Sumber Only</p>', $lampiranSumber->getEffectiveSections()->firstWhere('sub_bab_code', '1.1')->content);
    }

    /** Landing page UI displays derived status and preview button when submitted */
    public function test_renja_lampiran_index_ui_shows_derived_status_and_preview_button_when_submitted(): void
    {
        $murniDepok = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->operatorDepok)->get(route('renja.lampiran.index', ['tahun_anggaran' => 2026]));
        $response->assertStatus(200);
        $response->assertSee('Mengikuti RENJA Murni');
        $response->assertSee('Preview Lampiran');
    }
}
