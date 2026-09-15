<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DocumentWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * Test OPD operator creating document via Wizard form.
     */
    public function test_opd_operator_can_create_document_via_wizard()
    {
        $opd = MasterOpd::first() ?? MasterOpd::create(['nama_opd' => 'Dinas Perhubungan', 'kode_opd' => '1.05.000']);
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199501012023011001',
            'nama_lengkap' => 'Operator Perhubungan'
        ]);

        $response = $this->actingAs($operator)->post(route('renja.store'), [
            'tahun_anggaran' => 2028,
            'jenis_dokumen' => 'Rencana Kerja (Renja)',
        ]);

        // Harus redirect ke editor workspace
        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2028)->first();
        $this->assertNotNull($doc);
        $this->assertEquals('draft', $doc->status);

        $response->assertRedirect(route('renja.editor', $doc->id));
    }

    /**
     * Test Creation Modal UI elements present in documents workspace view.
     */
    public function test_creation_modal_elements_rendered_in_opd_documents_workspace()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199501012023011002',
            'nama_lengkap' => 'Operator View Test'
        ]);

        $response = $this->actingAs($operator)->get(route('renja.index'));

        $response->assertStatus(200);
        $response->assertSee('Buat Dokumen Perencanaan Baru');
        $response->assertSee('Dokumen Manual (Tanpa Template)');
        $response->assertSee('Buat Dokumen Sekarang');
    }

    /**
     * Test user creating manual document (empty canvas without default BAB template).
     */
    public function test_user_can_create_manual_document_without_template()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199501012023011003',
            'nama_lengkap' => 'Operator Manual Test'
        ]);

        $response = $this->actingAs($operator)->post(route('renja.store'), [
            'tahun_anggaran' => 2029,
            'jenis_dokumen' => 'Dokumen Manual (Tanpa Template)',
        ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2029)->first();
        $this->assertNotNull($doc);

        $response->assertRedirect(route('renja.editor', $doc->id));

        // Open editor for manual document
        $editorRes = $this->actingAs($operator)->get(route('renja.editor', $doc->id));
        $editorRes->assertStatus(200);
        $this->assertEquals(0, $doc->sections()->count());
    }
}
