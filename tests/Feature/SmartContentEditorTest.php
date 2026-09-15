<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SmartContentEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * Test autofix section endpoint cleans content via Mesin Cuci V2.
     */
    public function test_autofix_endpoint_strips_unallowed_bold_and_formats_tables()
    {
        $opd = MasterOpd::first() ?? MasterOpd::create(['nama_opd' => 'Dinas Komunikasi', 'kode_opd' => '1.06.000']);
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199001012022011010',
            'nama_lengkap' => 'Operator Kominfo'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Renja SKPD',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        $section = RenjaSection::create([
            'document_id' => $doc->id,
            'section_type' => 'chapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => '1.1',
            'sub_bab_title' => 'Latar Belakang',
            'content' => '<p><b>Teks Tebal</b> yang harus <strong>dibersihkan</strong>.</p><table><tr><td>Data 1</td></tr></table>',
            'is_completed' => true
        ]);

        $response = $this->actingAs($operator)->post(route('renja.section.autofix', [$doc->id, $section->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        $freshSection = $section->fresh();
        // Bold tags <b> and <strong> should be stripped for RENJA template
        $this->assertStringNotContainsString('<b>', $freshSection->content);
        $this->assertStringNotContainsString('<strong>', $freshSection->content);
        $this->assertStringContainsString('Teks Tebal', $freshSection->content);
    }

    /**
     * Test UI elements for Mesin Cuci V2 & Auto-Sum in editor view.
     */
    public function test_mesin_cuci_v2_and_auto_sum_rendered_in_smart_editor()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199001012022011011',
            'nama_lengkap' => 'Operator UI Test'
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Renja SKPD UI',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($operator)->get(route('renja.editor', $doc->id));

        $response->assertStatus(200);
        $response->assertSee('Mesin Cuci V2');
        $response->assertSee('Auto-Sum');
        $response->assertSee('triggerActiveSectionAutoFix');
    }
}
