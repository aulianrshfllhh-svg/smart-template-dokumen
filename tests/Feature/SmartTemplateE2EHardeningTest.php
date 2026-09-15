<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Enums\DocumentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SmartTemplateE2EHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * Test Complete End-to-End Lifecycle of Renja Document:
     * Wizard -> Editor -> Mesin Cuci V2 -> Submit -> Bapperida Review -> Approval -> Read-Only Lock
     */
    public function test_complete_end_to_end_renja_document_lifecycle()
    {
        // 1. Setup Actor OPD Operator & Verifikator Bapperida
        $opd = MasterOpd::first() ?? MasterOpd::create(['nama_opd' => 'Dinas Pekerjaan Umum', 'kode_opd' => '1.03.000']);
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199101012022011099',
            'nama_lengkap' => 'Operator PUPR E2E'
        ]);

        $verifikator = User::factory()->create([
            'role' => 'verifikator',
            'username_nip' => '198501012009011099',
            'nama_lengkap' => 'Verifikator Bapperida E2E'
        ]);

        // 2. Step 1: Operator creates document via Wizard Store Action
        $responseCreate = $this->actingAs($operator)->post(route('renja.store'), [
            'tahun_anggaran' => 2028,
            'jenis_dokumen' => 'Rencana Kerja (Renja)',
        ]);

        $doc = RenjaDocument::where('opd_id', $opd->id)->where('tahun_anggaran', 2028)->first();
        $this->assertNotNull($doc);
        $this->assertEquals('draft', $doc->status);
        $responseCreate->assertRedirect(route('renja.editor', $doc->id));

        // 3. Step 2: Operator adds custom BAB manually and opens Smart Editor Canvas
        $this->actingAs($operator)->post(route('renja.editor.addBab', $doc->id), [
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_title' => 'Latar Belakang'
        ]);

        $responseEditor = $this->actingAs($operator)->get(route('renja.editor', $doc->id));
        $responseEditor->assertStatus(200);
        $responseEditor->assertSee('BAB I');

        $section = RenjaSection::where('document_id', $doc->id)->first();
        $this->assertNotNull($section);

        // 4. Step 3: Operator updates section content via Auto-save AJAX
        $responseSave = $this->actingAs($operator)->post(route('renja.editor.updateSection', [$doc->id, $section->id]), [
            'content' => '<p><b>Draf Awal</b> Narasi Pendahuluan Renja 2028.</p>',
            'note' => 'Update Draf Pertama'
        ]);
        $responseSave->assertStatus(200);
        $responseSave->assertJson(['success' => true]);

        // 5. Step 4: Operator runs Mesin Cuci V2 (Autofix)
        $responseAutofix = $this->actingAs($operator)->post(route('renja.section.autofix', [$doc->id, $section->id]));
        $responseAutofix->assertStatus(200);
        $responseAutofix->assertJson(['success' => true]);

        // Verify bold tags stripped by Mesin Cuci V2
        $freshSection = $section->fresh();
        $this->assertStringNotContainsString('<b>', $freshSection->content);

        // 6. Step 5: Operator Submits Document to Bapperida
        $responseSubmit = $this->actingAs($operator)->post(route('renja.submit', $doc->id));
        $responseSubmit->assertRedirect();
        
        $docSubmit = $doc->fresh();
        $this->assertEquals('submitted', $docSubmit->status);

        // 7. Step 6: Verifikator Bapperida opens Review Workspace
        $responseReviewPage = $this->actingAs($verifikator)->get(route('admin.verifikasi.review', $doc->id));
        $responseReviewPage->assertStatus(200);
        $responseReviewPage->assertSee($doc->opd->nama_opd);

        // 8. Step 7: Verifikator approves document
        $responseApprove = $this->actingAs($verifikator)->post(route('admin.verifikasi.decision', $doc->id), [
            'decision_type' => 'setujui_dokumen',
            'catatan_bapperida' => 'Dokumen Renja TA 2028 telah memenuhi standar teknis Bapperida.'
        ]);
        $responseApprove->assertRedirect();

        $docApproved = $doc->fresh();
        $this->assertEquals(DocumentStatus::APPROVED->value, $docApproved->status);

        // 9. Step 8: Read-Only Verification on Editor Workspace
        $responseLocked = $this->actingAs($operator)->get(route('renja.editor', $doc->id));
        $responseLocked->assertStatus(200);
        $responseLocked->assertSee('Read-Only Mode');
    }
}
