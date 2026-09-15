<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Models\User;
use App\Services\DocumentTemplateService;
use App\Services\OpdDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenjaIndukSourceOfTruthTest extends TestCase
{
    use RefreshDatabase;

    protected User $operatorUser;
    protected MasterOpd $opd;
    protected DocumentTemplateService $templateService;
    protected OpdDocumentService $opdDocumentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateService = app(DocumentTemplateService::class);
        $this->templateService->ensureStandardTemplatesSeeded();
        $this->opdDocumentService = app(OpdDocumentService::class);

        $this->opd = MasterOpd::create([
            'kode_opd' => '1.01.01',
            'nama_opd' => 'Dinas Pendidikan',
            'lampiran_number' => 1,
            'nomor_lampiran_romawi' => 'Lampiran I',
        ]);

        $this->operatorUser = User::factory()->create([
            'username_nip' => '198501012010011001',
            'opd_id' => $this->opd->id,
            'role' => 'operator',
        ]);
    }

    /** @test */
    public function it_reads_effective_sections_live_from_renja_murni_induk()
    {
        // 1. Buat Dokumen Induk RENJA Murni
        $murni = $this->opdDocumentService->createRenjaMurni($this->opd->id, 2027);

        // Update seksi 1.1 pada Induk
        $sec11 = $murni->sections()->where('sub_bab_code', '1.1')->first();
        $sec11->update(['content' => '<p>Konten Narasi Latar Belakang Murni Original</p>']);

        // 2. Buat Lampiran Murni (Turunan)
        $lampiran = $this->opdDocumentService->generateLampiranPerbub($this->opd->id, 2027, 'murni');

        // Pastikan Lampiran membaca seksi live dari Induk
        $effective = $lampiran->getEffectiveSections();
        $effectiveSec11 = $effective->firstWhere('sub_bab_code', '1.1');
        $this->assertNotNull($effectiveSec11);
        $this->assertStringContainsString('Konten Narasi Latar Belakang Murni Original', $effectiveSec11->content);

        // 3. Mutasi seksi Induk secara live
        $sec11->update(['content' => '<p>Konten Narasi Latar Belakang yang Telah Direvisi di Induk</p>']);

        // Refresh dan cek kembali: Lampiran harus langsung membaca data terbaru tanpa generate ulang
        $effectiveUpdated = $lampiran->getEffectiveSections();
        $effectiveSec11Updated = $effectiveUpdated->firstWhere('sub_bab_code', '1.1');
        $this->assertStringContainsString('Konten Narasi Latar Belakang yang Telah Direvisi di Induk', $effectiveSec11Updated->content);
    }

    /** @test */
    public function it_reads_effective_sections_live_from_renja_perubahan_induk()
    {
        // 1. Setup Induk Murni approved & Perubahan
        $murni = $this->opdDocumentService->createRenjaMurni($this->opd->id, 2027);
        $murni->update(['status' => 'approved']);

        $perubahan = $this->opdDocumentService->createRenjaPerubahan($this->opd->id, 2026);
        $sec11 = $perubahan->sections()->where('sub_bab_code', '1.1')->first();
        $sec11->update(['content' => '<p>Konten Khusus Dokumen Perubahan TA 2026</p>']);

        // 2. Buat Lampiran Perubahan (Turunan)
        $lampiranPerubahan = $this->opdDocumentService->generateLampiranPerbub($this->opd->id, 2026, 'perubahan');

        // Pastikan Lampiran Perubahan membaca data dari Induk Perubahan
        $effective = $lampiranPerubahan->getEffectiveSections();
        $effectiveSec11 = $effective->firstWhere('sub_bab_code', '1.1');
        $this->assertNotNull($effectiveSec11);
        $this->assertStringContainsString('Konten Khusus Dokumen Perubahan TA 2026', $effectiveSec11->content);
    }

    /** @test */
    public function it_excludes_front_matter_sections_from_lampiran_effective_sections()
    {
        $murni = $this->opdDocumentService->createRenjaMurni($this->opd->id, 2027);

        // Tambah Cover / Kata Pengantar di Induk
        RenjaSection::create([
            'document_id' => $murni->id,
            'bab_code' => 'PREFACE',
            'bab_title' => 'Kata Pengantar',
            'sub_bab_code' => 'PREFACE',
            'sub_bab_title' => 'Kata Pengantar',
            'section_type' => 'preface',
            'order_index' => 1,
            'content' => '<p>Kata Pengantar</p>',
        ]);

        $lampiran = $this->opdDocumentService->generateLampiranPerbub($this->opd->id, 2027, 'murni');
        $effective = $lampiran->getEffectiveSections();

        // Tidak boleh ada front matter pada effective sections Lampiran
        $hasPreface = $effective->contains(fn($s) => $s->section_type === 'preface' || $s->bab_code === 'PREFACE');
        $this->assertFalse($hasPreface, 'Dokumen Lampiran tidak boleh mengandung seksi Kata Pengantar.');
    }

    /** @test */
    public function it_blocks_direct_editing_of_lampiran_and_redirects_to_preview()
    {
        $murni = $this->opdDocumentService->createRenjaMurni($this->opd->id, 2027);
        $lampiran = $this->opdDocumentService->generateLampiranPerbub($this->opd->id, 2027, 'murni');

        $response = $this->actingAs($this->operatorUser)
            ->get(route('renja.editor', $lampiran->id));

        $response->assertRedirect(route('renja.preview', ['id' => $lampiran->id, 'is_lampiran' => 1]));
        $response->assertSessionHas('info');
    }

    /** @test */
    public function it_synchronizes_effective_status_from_induk_to_lampiran()
    {
        $murni = $this->opdDocumentService->createRenjaMurni($this->opd->id, 2027);
        $lampiran = $this->opdDocumentService->generateLampiranPerbub($this->opd->id, 2027, 'murni');

        $this->assertEquals('draft', $lampiran->getEffectiveStatus());

        $murni->update(['status' => 'submitted']);
        $this->assertEquals('submitted', $lampiran->getEffectiveStatus());

        $murni->update(['status' => 'approved']);
        $this->assertEquals('approved', $lampiran->getEffectiveStatus());
    }

    /** @test */
    public function it_exports_lampiran_to_word_using_effective_sections_and_proper_headers()
    {
        $murni = $this->opdDocumentService->createRenjaMurni($this->opd->id, 2027);
        $sec11 = $murni->sections()->where('sub_bab_code', '1.1')->first();
        $sec11->update(['content' => '<p>Narasi Latar Belakang Induk</p>']);

        $lampiran = $this->opdDocumentService->generateLampiranPerbub($this->opd->id, 2027, 'murni');

        $response = $this->actingAs($this->operatorUser)
            ->get(route('renja.exportWord', $lampiran->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }
}
