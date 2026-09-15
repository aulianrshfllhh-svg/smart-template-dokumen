<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleBasedWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * Test OPD user can submit document to Bapperida (Submit -> Submitted).
     */
    public function test_opd_operator_can_submit_document()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'nama_lengkap' => 'Operator OPD Test Workflow',
            'username_nip' => '199101012024011001',
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Renja',
            'tahun_anggaran' => 2027,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($operator)->post(route('renja.submit', $doc->id));
        $response->assertRedirect();

        $this->assertDatabaseHas('renja_documents', [
            'id' => $doc->id,
            'status' => 'submitted',
        ]);
    }

    /**
     * Test Admin Bapperida can edit non-final documents and finalize directly.
     */
    public function test_admin_bapperida_can_edit_and_finalize_directly()
    {
        $opd = MasterOpd::first();
        $admin = User::factory()->create([
            'role' => 'admin',
            'opd_id' => $opd->id,
            'nama_lengkap' => 'Admin Bapperida Workflow Test',
            'username_nip' => '198501012024011002',
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Renja',
            'tahun_anggaran' => 2027,
            'status' => 'submitted',
        ]);

        // Admin can access editor even if status is submitted (not read-only for admin)
        $editorRes = $this->actingAs($admin)->get(route('renja.editor', $doc->id));
        $editorRes->assertStatus(200);
        $editorRes->assertViewHas('isReadOnly', false);

        // Document list for Admin renders DRAFT label instead of MENUNGGU VERIFIKASI
        $listRes = $this->actingAs($admin)->get(route('renja.index'));
        $listRes->assertStatus(200);
        $listRes->assertSee('DRAFT');
        $listRes->assertDontSee('MENUNGGU VERIFIKASI');

        // Admin finalizes document
        $resFinal = $this->actingAs($admin)->post(route('renja.finalize', $doc->id));
        $resFinal->assertRedirect();

        $this->assertDatabaseHas('renja_documents', [
            'id' => $doc->id,
            'status' => 'final',
        ]);
    }
}
