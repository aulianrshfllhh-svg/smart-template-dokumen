<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Models\DocumentTemplate;
use App\Services\DocumentRegistryService;
use App\Services\DocumentTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DocumentCreationArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentRegistryService $registryService;
    protected MasterOpd $opdDinkes;
    protected MasterOpd $opdDisdik;
    protected User $userDinkes;
    protected User $userDisdik;

    protected function setUp(): void
    {
        parent::setUp();

        app(DocumentTemplateService::class)->ensureStandardTemplatesSeeded();
        $this->registryService = app(DocumentRegistryService::class);

        $this->opdDinkes = MasterOpd::create([
            'kode_opd' => '1.02.01',
            'nama_opd' => 'Dinas Kesehatan',
            'singkatan_opd' => 'Dinkes',
            'nomor_lampiran_romawi' => 'LAMPIRAN I',
        ]);

        $this->opdDisdik = MasterOpd::create([
            'kode_opd' => '1.01.01',
            'nama_opd' => 'Dinas Pendidikan',
            'singkatan_opd' => 'Disdik',
            'nomor_lampiran_romawi' => 'LAMPIRAN II',
        ]);

        $this->userDinkes = User::factory()->create([
            'nama_lengkap' => 'Operator Dinkes',
            'username_nip' => '198501012010011001',
            'email' => 'operator.dinkes@cirebonkab.go.id',
            'role' => 'operator',
            'opd_id' => $this->opdDinkes->id,
        ]);

        $this->userDisdik = User::factory()->create([
            'nama_lengkap' => 'Operator Disdik',
            'username_nip' => '198501012010011002',
            'email' => 'operator.disdik@cirebonkab.go.id',
            'role' => 'operator',
            'opd_id' => $this->opdDisdik->id,
        ]);
    }

    /**
     * Test 1: DocumentRegistryService provides RENJA family with Murni (TA+1) and Perubahan (TA), and extensible RKPD.
     */
    public function test_document_registry_service_definitions()
    {
        $families = $this->registryService->getFamilies(2026);
        $this->assertArrayHasKey('RENJA', $families);
        $this->assertArrayHasKey('RKPD', $families);
        $this->assertTrue($families['RENJA']['is_active']);
        $this->assertFalse($families['RKPD']['is_active']);

        $renjaVariants = $this->registryService->getVariants('RENJA', 2026);
        $this->assertArrayHasKey('RENJA_MURNI', $renjaVariants);
        $this->assertArrayHasKey('RENJA_PERUBAHAN', $renjaVariants);

        // Murni must be TA + 1 = 2027
        $this->assertEquals(2027, $renjaVariants['RENJA_MURNI']['tahun_anggaran']);
        // Perubahan must be TA = 2026
        $this->assertEquals(2026, $renjaVariants['RENJA_PERUBAHAN']['tahun_anggaran']);

        // Lampiran must NEVER appear as creatable manual variants
        $this->assertArrayNotHasKey('RENJA_LAMPIRAN_MURNI', $renjaVariants);
        $this->assertArrayNotHasKey('RENJA_LAMPIRAN_PERUBAHAN', $renjaVariants);
    }

    /**
     * Test 2: Creating RENJA Murni creates document with TA = activeTa + 1 and provisions template.
     */
    public function test_create_renja_murni_sets_correct_ta_and_template()
    {
        $response = $this->actingAs($this->userDinkes)
            ->withSession(['active_ta' => 2026])
            ->post(route('renja.store'), [
                'variant_key' => 'RENJA_MURNI',
                'document_family' => 'RENJA',
            ]);

        $createdDoc = RenjaDocument::where('opd_id', $this->opdDinkes->id)
            ->where('tahun_anggaran', 2027)
            ->latest('id')
            ->first();

        $this->assertNotNull($createdDoc);
        $this->assertEquals(2027, $createdDoc->tahun_anggaran);
        $this->assertStringContainsString('Murni', $createdDoc->jenis_dokumen);
        $response->assertRedirect(route('renja.editor', $createdDoc->id));
    }

    /**
     * Test 3: Duplicate Document Prevention for same OPD & cycle.
     */
    public function test_duplicate_document_prevention()
    {
        // First creation
        $this->actingAs($this->userDinkes)
            ->withSession(['active_ta' => 2026])
            ->post(route('renja.store'), [
                'variant_key' => 'RENJA_MURNI',
                'document_family' => 'RENJA',
            ]);

        $firstDoc = RenjaDocument::where('opd_id', $this->opdDinkes->id)
            ->where('tahun_anggaran', 2027)
            ->first();

        $countBefore = RenjaDocument::where('opd_id', $this->opdDinkes->id)
            ->where('tahun_anggaran', 2027)
            ->count();

        // Attempt second creation of same variant
        $duplicateResponse = $this->actingAs($this->userDinkes)
            ->withSession(['active_ta' => 2026])
            ->post(route('renja.store'), [
                'variant_key' => 'RENJA_MURNI',
                'document_family' => 'RENJA',
            ]);

        $countAfter = RenjaDocument::where('opd_id', $this->opdDinkes->id)
            ->where('tahun_anggaran', 2027)
            ->count();

        // Count must remain the same (no duplicate created)
        $this->assertEquals($countBefore, $countAfter);
        $duplicateResponse->assertRedirect(route('renja.editor', $firstDoc->id));
        $duplicateResponse->assertSessionHas('info');
    }

    /**
     * Test 4: RKPD creation attempt is gracefully rejected.
     */
    public function test_rkpd_creation_is_blocked()
    {
        $response = $this->actingAs($this->userDinkes)
            ->post(route('renja.store'), [
                'document_family' => 'RKPD',
            ]);

        $response->assertSessionHas('error');
    }

    /**
     * Test 5: IDOR Protection on Editor Mutating Endpoints.
     */
    public function test_idor_protection_blocks_cross_opd_section_mutations()
    {
        // Create doc belonging to OPD Disdik
        $docDisdik = RenjaDocument::create([
            'opd_id' => $this->opdDisdik->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
            'cover_data' => [],
        ]);

        $sectionDisdik = RenjaSection::create([
            'document_id' => $docDisdik->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'content' => '<p>Original Content</p>',
            'order_index' => 1,
            'is_completed' => true,
        ]);

        // User Dinkes tries to edit OPD Disdik's section -> must receive 403 Forbidden
        $response = $this->actingAs($this->userDinkes)
            ->postJson(route('renja.editor.updateSection', ['id' => $docDisdik->id, 'sectionId' => $sectionDisdik->id]), [
                'content' => '<p>Hacked Content</p>',
            ]);

        $response->assertStatus(403);
    }
}
