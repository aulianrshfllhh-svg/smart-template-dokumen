<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardNavigationStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $operatorA;
    protected User $operatorB;
    protected MasterOpd $opdA;
    protected MasterOpd $opdB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create OPDs
        $this->opdA = MasterOpd::create(['nama_opd' => 'Kecamatan Depok', 'kode_opd' => 'OPD_DEPOK']);
        $this->opdB = MasterOpd::create(['nama_opd' => 'Kecamatan Sumber', 'kode_opd' => 'OPD_SUMBER']);

        // Create Operators
        $this->operatorA = User::create([
            'username_nip' => '199001012020011001',
            'nama_lengkap' => 'Operator Depok',
            'email' => 'operator_depok@example.com',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'opd_id' => $this->opdA->id,
        ]);

        $this->operatorB = User::create([
            'username_nip' => '199001012020011002',
            'nama_lengkap' => 'Operator Sumber',
            'email' => 'operator_sumber@example.com',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'opd_id' => $this->opdB->id,
        ]);

        // Seed OPD A documents with various statuses
        RenjaDocument::create([
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni Draft',
            'status' => 'draft',
        ]);

        RenjaDocument::create([
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni Revisi',
            'status' => 'perlu_revisi',
            'catatan_bapperida' => 'Harap perbaiki Bab II',
        ]);

        RenjaDocument::create([
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni Submitted',
            'status' => 'submitted',
        ]);

        RenjaDocument::create([
            'opd_id' => $this->opdA->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni Approved',
            'status' => 'disetujui',
        ]);

        // Seed OPD B documents (to verify OPD isolation)
        RenjaDocument::create([
            'opd_id' => $this->opdB->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Sumber Draft',
            'status' => 'draft',
        ]);
    }

    /**
     * Test 1: Operator dashboard displays status cards pointing to correct filter routes.
     */
    public function test_operator_dashboard_status_card_links(): void
    {
        $response = $this->actingAs($this->operatorA)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee(route('renja.index', ['status' => 'draft']), false);
        $response->assertSee(route('renja.index', ['status' => 'revision_required']), false);
        $response->assertSee(route('renja.index', ['status' => 'under_verification']), false);
        $response->assertSee(route('renja.index', ['status' => 'approved']), false);
    }

    /**
     * Test 2: Clicking Draft filter shows ONLY draft documents of logged-in OPD.
     */
    public function test_draft_filter_returns_only_draft_documents(): void
    {
        $response = $this->actingAs($this->operatorA)->get('/renja-documents?status=draft');

        $response->assertStatus(200);
        $response->assertSee('RENJA Murni Draft');
        $response->assertDontSee('RENJA Murni Revisi');
        $response->assertDontSee('RENJA Murni Submitted');
        $response->assertDontSee('RENJA Murni Approved');
        $response->assertDontSee('RENJA Sumber Draft'); // OPD B document must not appear
    }

    /**
     * Test 3: Clicking Revision Required filter shows ONLY revision documents of logged-in OPD with revision notes.
     */
    public function test_revision_required_filter_returns_only_revision_documents(): void
    {
        $response = $this->actingAs($this->operatorA)->get('/renja-documents?status=revision_required');

        $response->assertStatus(200);
        $response->assertSee('RENJA Murni Revisi');
        $response->assertSee('Ada catatan revisi');
        $response->assertSee('Lihat & Perbaiki', false);
        $response->assertDontSee('RENJA Murni Draft');
        $response->assertDontSee('RENJA Murni Submitted');
    }

    /**
     * Test 4: Clicking Under Verification filter shows ONLY under verification documents of logged-in OPD.
     */
    public function test_under_verification_filter_returns_only_submitted_documents(): void
    {
        $response = $this->actingAs($this->operatorA)->get('/renja-documents?status=under_verification');

        $response->assertStatus(200);
        $response->assertSee('RENJA Murni Submitted');
        $response->assertDontSee('RENJA Murni Draft');
        $response->assertDontSee('RENJA Murni Approved');
    }

    /**
     * Test 5: Clicking Approved/Final filter shows ONLY approved/final documents of logged-in OPD.
     */
    public function test_approved_filter_returns_only_approved_documents(): void
    {
        $response = $this->actingAs($this->operatorA)->get('/renja-documents?status=approved');

        $response->assertStatus(200);
        $response->assertSee('RENJA Murni Approved');
        $response->assertDontSee('RENJA Murni Draft');
        $response->assertDontSee('RENJA Murni Submitted');
    }

    /**
     * Test 6: Operator B cannot see Operator A documents when filtered.
     */
    public function test_opd_isolation_on_document_list(): void
    {
        $response = $this->actingAs($this->operatorB)->get('/renja-documents?status=draft');

        $response->assertStatus(200);
        $response->assertSee('RENJA Sumber Draft');
        $response->assertDontSee('RENJA Murni Draft');
    }

    /**
     * Test 7: Operator A cannot access Operator B document via direct URL.
     */
    public function test_cross_opd_direct_access_is_forbidden(): void
    {
        $docB = RenjaDocument::where('opd_id', $this->opdB->id)->first();

        $response = $this->actingAs($this->operatorA)->get("/renja-documents/{$docB->id}/editor");

        $response->assertStatus(403);
    }
}
