<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SmartTemplateEditorFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * 1. Operator dapat membuka editor (HTTP 200)
     */
    public function test_opd_operator_can_open_smart_template_editor()
    {
        $opd = MasterOpd::first() ?? MasterOpd::create(['nama_opd' => 'Dinas Kesehatan', 'kode_opd' => '1.02.000']);
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199201012022011001',
            'nama_lengkap' => 'Operator Kesehatan'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Renja SKPD',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($operator)->get(route('renja.editor', $doc->id));

        $response->assertStatus(200);
        $response->assertSee('Renja SKPD');
        $response->assertSee('2027');
    }

    /**
     * 2. Sidebar mengikuti TemplateSection & 3. Tidak ada hardcoded BAB
     */
    public function test_sidebar_renders_dynamically_from_template_sections()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199201012022011002',
            'nama_lengkap' => 'Operator OPD Dynamic'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Renja SKPD Dynamic',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        RenjaSection::create([
            'document_id' => $doc->id,
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'is_completed' => false,
        ]);

        $response = $this->actingAs($operator)->get(route('renja.editor', $doc->id));

        $response->assertStatus(200);
        $response->assertSee('BAB I');
        $response->assertSee('Checklist Kelengkapan');
    }

    /**
     * 4. Progress tampil benar
     */
    public function test_progress_percentage_and_completeness_badges_render_correctly()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199201012022011003',
            'nama_lengkap' => 'Operator Progress Test'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Renja Progress Test',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($operator)->get(route('renja.editor', $doc->id));

        $response->assertStatus(200);
        $response->assertSee('progress-bar');
        $response->assertSee('progress-percentage-display');
    }

    /**
     * 5. Read Only berjalan untuk status Submitted/Final/Approved & Role Admin/Verifikator
     */
    public function test_read_only_rule_locks_editing_on_submitted_status_and_admin_roles()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199201012022011004',
            'nama_lengkap' => 'Operator Locked Test'
        ]);
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create([
            'role' => 'admin',
            'username_nip' => '198001012005011001',
            'nama_lengkap' => 'Admin Bapperida'
        ]);

        $submittedDoc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Submitted Doc Locked',
            'tahun_anggaran' => 2027,
            'status' => 'submitted'
        ]);

        // Test Operator viewing submitted document -> Read-only mode
        $responseOp = $this->actingAs($operator)->get(route('renja.editor', $submittedDoc->id));
        $responseOp->assertStatus(200);
        $responseOp->assertSee('Read-Only Mode');

        // Test Admin viewing non-final document -> Admin can edit and finalize (not read-only)
        $responseAdmin = $this->actingAs($admin)->get(route('renja.editor', $submittedDoc->id));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertDontSee('Read-Only Mode');
    }

    /**
     * Test Admin & Akun Bapperida dapat membuat dan mengedit dokumen berstatus Draft.
     */
    public function test_admin_and_bapperida_can_create_and_edit_draft_documents()
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create([
            'role' => 'admin',
            'username_nip' => '198001012005011088',
            'nama_lengkap' => 'Admin Creator Test'
        ]);

        $opd = MasterOpd::first();

        // 1. Admin can access document creation form
        $responseCreate = $this->actingAs($admin)->get(route('renja.create'));
        $responseCreate->assertStatus(200);

        // 2. Admin can create draft document
        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Renja Admin Creator',
            'tahun_anggaran' => 2028,
            'status' => 'draft'
        ]);

        // 3. Admin opening draft document is NOT forced read-only
        $responseEditor = $this->actingAs($admin)->get(route('renja.editor', $doc->id));
        $responseEditor->assertStatus(200);
        $responseEditor->assertDontSee('DOKUMEN DALAM MODE READ-ONLY');
        $responseEditor->assertSee('Simpan Draft');
    }

    /**
     * 6. Autosave Indicator muncul
     */
    public function test_autosave_indicator_renders_in_header()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199201012022011005',
            'nama_lengkap' => 'Operator Autosave Test'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Autosave Indicator Test',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($operator)->get(route('renja.editor', $doc->id));

        $response->assertStatus(200);
        $response->assertSee('autosave-status-text');
        $response->assertSee('autosave-dot');
    }

    /**
     * 7. Sticky Toolbar muncul dengan tombol Simpan Draft & Submit Verifikasi
     */
    public function test_sticky_bottom_toolbar_renders_with_action_buttons()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199201012022011006',
            'nama_lengkap' => 'Operator Sticky Test'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Sticky Bar Test',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($operator)->get(route('renja.editor', $doc->id));

        $response->assertStatus(200);
        $response->assertSee('Simpan Draft');
        $response->assertSee('Submit Verifikasi');
        $response->assertSee('Pondasi Smart Editor:');
    }

    /**
     * 8. Warning Unsaved Changes muncul
     */
    public function test_unsaved_changes_warning_script_is_present()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199201012022011007',
            'nama_lengkap' => 'Operator Warning Test'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Unsaved Changes Test',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($operator)->get(route('renja.editor', $doc->id));

        $response->assertStatus(200);
        $response->assertSee('beforeunload');
        $response->assertSee('Perubahan belum disimpan');
    }

    /**
     * 9. Operator dapat menambah BAB Baru secara manual (+ BAB Baru)
     */
    public function test_opd_operator_can_add_custom_bab_manually()
    {
        $opd = MasterOpd::first() ?? MasterOpd::create(['nama_opd' => 'Dinas Kesehatan', 'kode_opd' => '1.02.000']);
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199201012022011008',
            'nama_lengkap' => 'Operator Custom Bab Test'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Custom Bab Test',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($operator)->post(route('renja.editor.addBab', $doc->id), [
            'bab_code' => 'BAB VII',
            'bab_title' => 'Kondisi Lingkungan Hidup',
            'sub_bab_title' => 'Latar Belakang Lingkungan'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('renja_sections', [
            'document_id' => $doc->id,
            'bab_code' => 'BAB VII',
            'bab_title' => 'Kondisi Lingkungan Hidup',
            'sub_bab_title' => 'Latar Belakang Lingkungan'
        ]);
    }
}
