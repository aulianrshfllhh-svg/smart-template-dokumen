<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DynamicPageBreakTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * BR-PAGE-02: Dokumen Renja TIDAK menyisipkan page break otomatis sebelum BAB baru.
     */
    public function test_renja_document_editor_and_pdf_does_not_force_page_break()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199601012024012001',
            'nama_lengkap' => 'Operator Renja Test'
        ]);

        $renjaDoc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Rencana Kerja (Renja)',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        RenjaSection::create([
            'document_id' => $renjaDoc->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'content' => '<p>Konten Bab I</p>',
            'order_index' => 1
        ]);

        RenjaSection::create([
            'document_id' => $renjaDoc->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB II',
            'bab_title' => 'Evaluasi',
            'sub_bab_code' => '2.1',
            'sub_bab_title' => 'Hasil Evaluasi',
            'content' => '<p>Konten Bab II</p>',
            'order_index' => 2
        ]);

        $res = $this->actingAs($operator)->get(route('renja.editor', $renjaDoc->id));
        $res->assertStatus(200);
        $res->assertDontSee('PAGE BREAK — HALAMAN BARU');
    }

    /**
     * BR-PAGE-01 & BR-PAGE-05: Dokumen selain Renja (misal: RPJMD/RKPD) menampilkan Visual Page Break di editor preview.
     */
    public function test_non_renja_document_editor_shows_visual_page_break()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199601012024012002',
            'nama_lengkap' => 'Operator Non-Renja Test'
        ]);

        $rpjmdDoc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Rencana Pembangunan Jangka Menengah Daerah (RPJMD)',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        RenjaSection::create([
            'document_id' => $rpjmdDoc->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'content' => '<p>Konten RPJMD Bab I</p>',
            'order_index' => 1
        ]);

        $res = $this->actingAs($operator)->get(route('renja.editor', $rpjmdDoc->id));
        $res->assertStatus(200);
        $res->assertSee('PAGE BREAK — HALAMAN BARU');
    }

    /**
     * BR-PAGE-04: Saat BAB dihapus, seksi dan page break yang terkait otomatis terhapus tanpa menyisakan seksi kosong.
     */
    public function test_deleting_bab_removes_sections_and_associated_breaks()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199601012024012003',
            'nama_lengkap' => 'Operator Delete BAB Test'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Rencana Kerja Pemerintah Daerah (RKPD)',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        RenjaSection::create([
            'document_id' => $doc->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'content' => '<p>Konten Bab I</p>',
            'order_index' => 1
        ]);

        RenjaSection::create([
            'document_id' => $doc->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB II',
            'bab_title' => 'Gambaran Umum',
            'sub_bab_code' => '2.1',
            'sub_bab_title' => 'Kondisi Daerah',
            'content' => '<p>Konten Bab II</p>',
            'order_index' => 2
        ]);

        // Hapus BAB II
        $this->actingAs($operator)->delete(route('renja.editor.deleteBab', [$doc->id, 'BAB II']));

        $this->assertEquals(0, RenjaSection::where('document_id', $doc->id)->where('bab_code', 'BAB II')->count());
        $this->assertEquals(1, RenjaSection::where('document_id', $doc->id)->count());
    }
}
