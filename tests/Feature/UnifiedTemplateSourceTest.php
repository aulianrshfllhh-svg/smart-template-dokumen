<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\DocumentTemplate;
use App\Services\DocumentTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnifiedTemplateSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * Test getAvailableTemplates() returns Renja and Dokumen Manual (RKPD & Evaluasi RKPD deleted).
     */
    public function test_service_returns_unified_template_list()
    {
        $service = app(DocumentTemplateService::class);
        $templates = $service->getAvailableTemplates();

        $codes = $templates->pluck('code')->toArray();

        $this->assertContains('RENJA', $codes);
        $this->assertContains('MANUAL', $codes);
        $this->assertNotContains('RKPD', $codes);
        $this->assertNotContains('EVALUASI_RKPD', $codes);
    }

    /**
     * Test manual creation with custom title and custom document type.
     */
    public function test_manual_document_creation_with_custom_name_and_type()
    {
        $opd = MasterOpd::first();
        $user = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'nama_lengkap' => 'Operator Manual Test',
            'username_nip' => '199001012024011005',
        ]);

        $response = $this->actingAs($user)->post(route('renja.store'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'MANUAL',
            'custom_jenis_dokumen' => 'RKPD',
            'judul_dokumen' => 'Rencana Kerja Pemerintah Daerah Tahun 2027',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('renja_documents', [
            'jenis_dokumen' => 'Rencana Kerja Pemerintah Daerah Tahun 2027',
            'opd_id' => $opd->id,
        ]);

        $doc = RenjaDocument::where('jenis_dokumen', 'Rencana Kerja Pemerintah Daerah Tahun 2027')->first();
        $this->assertEquals('Rencana Kerja Pemerintah Daerah Tahun 2027', $doc->cover_data['judul_dokumen']);
    }

    /**
     * Test adding a new custom template automatically appears in getAvailableTemplates().
     */
    public function test_newly_added_template_appears_automatically()
    {
        DocumentTemplate::create([
            'code' => 'RPJMD',
            'name' => 'Rencana Pembangunan Jangka Menengah Daerah (RPJMD)',
            'description' => 'Dokumen Perencanaan 5 Tahun Daerah',
            'is_active' => true,
        ]);

        $service = app(DocumentTemplateService::class);
        $templates = $service->getAvailableTemplates();

        $codes = $templates->pluck('code')->toArray();
        $this->assertContains('RPJMD', $codes);
    }
}
