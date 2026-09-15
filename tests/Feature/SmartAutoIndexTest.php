<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SmartAutoIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * BR-AI-01 & BR-AI-02: Dokumen baru TIDAK membuat halaman indeks/Front Matter secara otomatis.
     */
    public function test_new_document_does_not_auto_create_front_matter_indexes()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199601012024011001',
            'nama_lengkap' => 'Operator Index Test 1'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Rencana Kerja (Renja)',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        // Verifikasi tidak ada seksi table_of_contents, list_of_figures, dsb.
        $this->assertEquals(0, $doc->sections()->whereIn('section_type', ['table_of_contents', 'list_of_figures', 'list_of_tables', 'list_of_appendices'])->count());
    }

    /**
     * BR-AI-01 & BR-AI-03 & BR-AI-04: User menambah Daftar Isi manual (+ Halaman Awal -> Daftar Isi), isi terisi otomatis dan tersinkronisasi.
     */
    public function test_user_can_add_table_of_contents_manually_and_it_auto_syncs()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199601012024011002',
            'nama_lengkap' => 'Operator Index Test 2'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Rencana Kerja (Renja)',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        // Tambah BAB I & BAB II
        $this->actingAs($operator)->post(route('renja.editor.addBab', $doc->id), [
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_title' => 'Latar Belakang',
        ]);

        $this->actingAs($operator)->post(route('renja.editor.addBab', $doc->id), [
            'bab_code' => 'BAB II',
            'bab_title' => 'Gambaran Umum',
            'sub_bab_title' => 'Kondisi Daerah',
        ]);

        // Tambah Daftar Isi via + Halaman Awal
        $res = $this->actingAs($operator)->post(route('renja.editor.addFrontMatter', $doc->id), [
            'type' => 'table_of_contents',
        ]);

        $res->assertRedirect();

        $toc = $doc->sections()->where('section_type', 'table_of_contents')->first();
        $this->assertNotNull($toc);
        $this->assertStringContainsString('DAFTAR ISI', $toc->content);
        $this->assertStringContainsString('BAB I PENDAHULUAN', $toc->content);
        $this->assertStringContainsString('1.1 Latar Belakang', $toc->content);
        $this->assertStringContainsString('BAB II GAMBARAN UMUM', $toc->content);
        $this->assertStringContainsString('2.1 Kondisi Daerah', $toc->content);

        // Tambah BAB III dan verifikasi Daftar Isi tersinkronisasi otomatis (BR-AI-03)
        $this->actingAs($operator)->post(route('renja.editor.addBab', $doc->id), [
            'bab_code' => 'BAB III',
            'bab_title' => 'Kerangka Ekonomi',
            'sub_bab_title' => 'Arah Kebijakan',
        ]);

        $tocFresh = $toc->fresh();
        $this->assertStringContainsString('BAB III KERANGKA EKONOMI', $tocFresh->content);
        $this->assertStringContainsString('3.1 Arah Kebijakan', $tocFresh->content);
    }

    /**
     * BR-AI-05, BR-AI-06, BR-AI-07: Penambahan Daftar Gambar, Daftar Tabel, dan Daftar Lampiran opsional.
     */
    public function test_user_can_add_other_indexes_manually()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199601012024011003',
            'nama_lengkap' => 'Operator Index Test 3'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Rencana Kerja (Renja)',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        // Tambah Daftar Gambar
        $this->actingAs($operator)->post(route('renja.editor.addFrontMatter', $doc->id), [
            'type' => 'list_of_figures',
        ]);
        $this->assertNotNull($doc->sections()->where('section_type', 'list_of_figures')->first());

        // Tambah Daftar Tabel
        $this->actingAs($operator)->post(route('renja.editor.addFrontMatter', $doc->id), [
            'type' => 'list_of_tables',
        ]);
        $this->assertNotNull($doc->sections()->where('section_type', 'list_of_tables')->first());

        // Tambah Daftar Lampiran
        $this->actingAs($operator)->post(route('renja.editor.addFrontMatter', $doc->id), [
            'type' => 'list_of_appendices',
        ]);
        $this->assertNotNull($doc->sections()->where('section_type', 'list_of_appendices')->first());
    }
}
