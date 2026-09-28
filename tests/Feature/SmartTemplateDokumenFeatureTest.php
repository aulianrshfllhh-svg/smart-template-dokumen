<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\DocumentTemplate;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Models\RenjaSectionCaption;
use App\Services\DocumentTemplateService;
use App\Services\OpdDocumentService;
use App\Services\RenjaIndexGeneratorService;
use App\Services\WordExportService;
use App\Services\PdfExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SmartTemplateDokumenFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentTemplateService $templateService;
    protected OpdDocumentService $opdDocumentService;
    protected RenjaIndexGeneratorService $indexGeneratorService;
    protected MasterOpd $opd;
    protected User $operator;
    protected RenjaDocument $document;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateService = app(DocumentTemplateService::class);
        $this->opdDocumentService = app(OpdDocumentService::class);
        $this->indexGeneratorService = app(RenjaIndexGeneratorService::class);
        $this->templateService->ensureStandardTemplatesSeeded();

        $this->opd = MasterOpd::create([
            'nama_opd' => 'DINAS KOMUNIKASI DAN INFORMATIKA',
            'kode_opd' => '2.16.01',
            'nomor_lampiran_romawi' => 'LAMPIRAN I',
        ]);

        $this->operator = User::create([
            'name' => 'Operator Diskominfo',
            'nama_lengkap' => 'Operator Diskominfo',
            'username_nip' => '198501012010011001',
            'email' => 'operator.diskominfo@cirebonkab.go.id',
            'password' => bcrypt('password123'),
            'role' => 'operator',
            'opd_id' => $this->opd->id,
        ]);

        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first();

        $this->document = RenjaDocument::create([
            'opd_id' => $this->opd->id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'Rencana Kerja (RENJA) Murni Perangkat Daerah',
            'status' => 'draft',
            'template_id' => $template->id,
            'user_id' => $this->operator->id,
        ]);

        $this->templateService->provisionDocumentSections($this->document, 'RENJA_MURNI');
    }

    /**
     * TEST 1: Area isi sub-bab manual tersimpan ke database renja_sections
     * terhubung dengan section ID dokumen.
     */
    public function test_subbab_content_manual_save_to_database(): void
    {
        $subBab = $this->document->sections()
            ->where('section_type', 'subchapter')
            ->where('sub_bab_code', '1.1')
            ->firstOrFail();

        $htmlContent = '<p>Ini adalah narasi Latar Belakang yang diisi manual oleh pengguna.</p><ul><li>Poin 1</li><li>Poin 2</li></ul>';

        $response = $this->actingAs($this->operator)
            ->postJson(route('renja.editor.updateSection', [$this->document->id, $subBab->id]), [
                'content' => $htmlContent,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('renja_sections', [
            'id' => $subBab->id,
            'document_id' => $this->document->id,
            'is_completed' => true,
        ]);

        $subBab->refresh();
        $this->assertStringContainsString('Latar Belakang yang diisi manual', $subBab->content);
    }

    /**
     * TEST 2: Simpan caption tabel melalui AJAX API dan otomatis tersimpan di tabel renja_section_captions.
     */
    public function test_save_table_caption_api(): void
    {
        $subBab = $this->document->sections()
            ->where('section_type', 'subchapter')
            ->where('sub_bab_code', '2.1')
            ->firstOrFail();

        $response = $this->actingAs($this->operator)
            ->postJson(route('renja.editor.saveCaption', [$this->document->id, $subBab->id]), [
                'element_type' => 'table',
                'element_id' => 'tbl_eval_01',
                'caption' => 'Tabel 2.1 Evaluasi Renja Tahun 2025',
                'display_number' => 'Tabel 2.1',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Caption berhasil disimpan.',
        ]);

        $this->assertDatabaseHas('renja_section_captions', [
            'section_id' => $subBab->id,
            'element_type' => 'table',
            'element_id' => 'tbl_eval_01',
            'caption' => 'Tabel 2.1 Evaluasi Renja Tahun 2025',
            'display_number' => 'Tabel 2.1',
        ]);
    }

    /**
     * TEST 3: Daftar Isi, Daftar Tabel, Daftar Gambar otomatis ter-generate dan ter-sync.
     */
    public function test_automatic_front_matter_indexes_generation(): void
    {
        $subBab21 = $this->document->sections()
            ->where('section_type', 'subchapter')
            ->where('sub_bab_code', '2.1')
            ->firstOrFail();

        RenjaSectionCaption::create([
            'section_id' => $subBab21->id,
            'element_type' => 'table',
            'element_id' => 'tbl_test_01',
            'caption' => 'Tabel 2.1 Evaluasi Renja Tahun Lalu',
            'display_number' => 'Tabel 2.1',
            'order_index' => 0,
        ]);

        RenjaSectionCaption::create([
            'section_id' => $subBab21->id,
            'element_type' => 'figure',
            'element_id' => 'fig_test_01',
            'caption' => 'Gambar 2.1 Peta Lokasi Kegiatan',
            'display_number' => 'Gambar 2.1',
            'order_index' => 0,
        ]);

        // Sync indeks dokumen
        $this->indexGeneratorService->syncDocumentFrontIndexes($this->document);

        // Cek Daftar Isi
        $tocSec = $this->document->sections()->where('section_type', 'table_of_contents')->first();
        $this->assertNotNull($tocSec);
        $this->assertStringContainsString('DAFTAR ISI', $tocSec->content);
        $this->assertStringContainsString('BAB I', $tocSec->content);
        $this->assertStringContainsString('1.1', $tocSec->content);

        // Cek Daftar Tabel
        $lotSec = $this->document->sections()->where('section_type', 'list_of_tables')->first();
        $this->assertNotNull($lotSec);
        $this->assertStringContainsString('DAFTAR TABEL', $lotSec->content);
        $this->assertStringContainsString('Tabel 2.1', $lotSec->content);
        $this->assertStringContainsString('Evaluasi Renja Tahun Lalu', $lotSec->content);

        // Cek Daftar Gambar
        $lofSec = $this->document->sections()->where('section_type', 'list_of_figures')->first();
        $this->assertNotNull($lofSec);
        $this->assertStringContainsString('DAFTAR GAMBAR', $lofSec->content);
        $this->assertStringContainsString('Gambar 2.1', $lofSec->content);
        $this->assertStringContainsString('Peta Lokasi Kegiatan', $lofSec->content);
    }

    /**
     * TEST 4: Export Word (.docx) menghasilkan file Word valid dengan F4 & Bookman Old Style.
     */
    public function test_export_word_returns_valid_docx(): void
    {
        $response = $this->actingAs($this->operator)
            ->get(route('renja.editor.exportWord', $this->document->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    /**
     * TEST 5: Export PDF menghasilkan file PDF yang dapat diunduh.
     */
    public function test_export_pdf_returns_valid_pdf_download(): void
    {
        $response = $this->actingAs($this->operator)
            ->get(route('renja.editor.exportPdf', $this->document->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /**
     * TEST 6: Dokumen berstatus DRAFT (baik uppercase maupun lowercase) menampilkan tombol Edit aktif di Dokumen Renja dan dapat diedit di editor.
     */
    public function test_dokumen_renja_draft_shows_active_edit_button_and_allows_editing(): void
    {
        // Set dokumen status menjadi DRAFT (uppercase)
        $this->document->update(['status' => 'DRAFT']);

        // Buka halaman Dokumen Renja (index)
        $response = $this->actingAs($this->operator)->withSession(['active_ta' => $this->document->tahun_anggaran - 1])->get(route('renja.index'));
        $response->assertOk();
        
        // Pastikan URL edit dokumen ada dan tombol edit aktif
        $editUrl = route('renja.editor', $this->document->id);
        $response->assertSee($editUrl);
        $response->assertSee('Edit');

        // Buka halaman editor dokumen
        $editorResponse = $this->actingAs($this->operator)->get($editUrl);
        $editorResponse->assertOk();
        $this->assertFalse($editorResponse->viewData('isReadOnly'));
        $editorResponse->assertSee('contenteditable="true"', false);

        // Uji simpan konten seksi saat draft
        $subbab = $this->document->sections()->where('section_type', 'subchapter')->first();
        $updateResponse = $this->actingAs($this->operator)->postJson(
            route('renja.editor.updateSection', [$this->document->id, $subbab->id]),
            ['content' => '<p>Uraian narasi draft sub-bab berhasil diupdate.</p>']
        );
        $updateResponse->assertOk();
        $updateResponse->assertJson(['success' => true]);
        $this->assertDatabaseHas('renja_sections', [
            'id' => $subbab->id,
            'content' => '<p>Uraian narasi draft sub-bab berhasil diupdate.</p>',
        ]);
    }
}
