<?php

namespace Tests\Feature;

use App\Models\{User, MasterOpd, RenjaDocument, RenjaSection, DocumentTemplate};
use App\Services\{DocumentTemplateService, RenjaMurniDocxService, VerificationWorkspaceService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(string $role = 'operator', string $status = 'draft'): array
    {
        $opd = MasterOpd::create(['kode_opd' => uniqid('opd'), 'nama_opd' => 'OPD Audit']);
        $user = User::factory()->create(['role' => $role, 'opd_id' => $opd->id]);
        $doc = RenjaDocument::create(['opd_id' => $opd->id, 'jenis_dokumen' => 'RENJA Murni', 'tahun_anggaran' => 2031, 'status' => $status]);
        $section = $doc->sections()->create(['bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.1', 'sub_bab_title' => 'Narasi', 'section_type' => 'subchapter', 'content' => '<p>Isi asli</p>']);
        $this->withSession(['active_ta' => 2030]);
        return [$user, $doc, $section];
    }

    public function test_operator_cannot_finalize_or_create_for_another_opd(): void
    {
        [$user, $doc] = $this->fixture();
        [$other, $otherDoc] = $this->fixture();
        $this->actingAs($user)->post(route('renja.finalize', $doc->id))->assertForbidden();
        $this->post(route('renja.store'), ['opd_id' => $other->opd_id])->assertForbidden();
        $this->assertSame('draft', $doc->fresh()->status);
    }

    public function test_opd_alias_can_open_own_editor_but_not_another_document(): void
    {
        [$user, $doc] = $this->fixture('opd');
        [, $otherDoc, $otherSection] = $this->fixture();
        $this->actingAs($user)->get(route('operator.renja.editor', $doc->id))->assertOk();
        foreach (['renja.show', 'renja.editor', 'renja.original-file', 'renja.exportWord', 'renja.exportPdf', 'renja.preview'] as $route) {
            $this->get(route($route, $otherDoc->id))->assertForbidden();
        }
        $this->postJson(route('renja.editor.updateSection', [$otherDoc->id, $otherSection->id]), ['content' => 'overwrite'])->assertForbidden();
        $this->assertSame('<p>Isi asli</p>', $otherSection->fresh()->content);
    }

    public function test_locked_and_submitted_content_cannot_be_changed_by_operator(): void
    {
        [$user, $doc, $section] = $this->fixture();
        foreach (['submitted', 'dikirim_ulang', 'sedang_diperiksa', 'disetujui', 'final'] as $status) {
            $doc->update(['status' => $status]);
            $this->actingAs($user)->postJson(route('renja.editor.updateSection', [$doc->id, $section->id]), ['content' => 'overwrite'])->assertForbidden();
        }
        $doc->update(['status' => 'draft', 'is_archived' => true]);
        $this->postJson(route('renja.editor.saveCaption', [$doc->id, $section->id]), ['element_type' => 'table', 'element_id' => 't1', 'caption' => 'Judul'])->assertForbidden();
        $this->assertSame('<p>Isi asli</p>', $section->fresh()->content);
    }

    public function test_admin_cannot_edit_final_content(): void
    {
        [$user, $doc, $section] = $this->fixture('admin', 'final');
        $this->actingAs($user)->postJson(route('renja.editor.updateSection', [$doc->id, $section->id]), ['content' => 'overwrite'])->assertForbidden();
    }

    public function test_missing_content_does_not_erase_a_section(): void
    {
        [$user, $doc, $section] = $this->fixture();
        $this->actingAs($user)->postJson(route('renja.editor.updateSection', [$doc->id, $section->id]), [])->assertUnprocessable();
        $this->assertSame('<p>Isi asli</p>', $section->fresh()->content);
    }

    public function test_dashboard_renders_array_review_notes(): void
    {
        [$user, $doc] = $this->fixture();
        $doc->update(['catatan_bapperida' => 'Perbaiki narasi']);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Perbaiki narasi');
    }

    public function test_verificator_dashboard_and_monitoring_are_accessible(): void
    {
        [$user] = $this->fixture('verifikator');
        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.monitoring-opd.index'))->assertOk();
    }

    public function test_draft_cannot_be_approved_and_final_cannot_be_reopened(): void
    {
        [$user, $doc] = $this->fixture('admin');
        $this->actingAs($user)->post(route('admin.decision', $doc->id), ['decision' => 'approved'])->assertUnprocessable();
        $doc->update(['status' => 'final']);
        $this->post(route('admin.decision', $doc->id), ['decision' => 'revisi', 'catatan_bapperida' => 'Minta perbaikan'])->assertForbidden();
        $this->assertSame('final', $doc->fresh()->status);
    }

    public function test_operator_cannot_enter_verification(): void
    {
        [$user, $doc] = $this->fixture();
        $this->actingAs($user)->post(route('admin.decision', $doc->id), ['decision' => 'approved'])->assertRedirect();
        $this->get(route('admin.verifikasi.index'))->assertRedirect();
        $this->assertSame('draft', $doc->fresh()->status);
    }

    public function test_full_revision_and_approval_workflow_preserves_content(): void
    {
        [$user, $doc, $section] = $this->fixture();
        [$admin] = $this->fixture('admin');
        $this->actingAs($user)->post(route('renja.submit', $doc->id))->assertRedirect();
        $this->assertSame('submitted', $doc->fresh()->status);
        $this->actingAs($admin)->post(route('admin.verifikasi.decision', $doc->id), ['decision_type' => 'minta_revisi', 'catatan_bapperida' => 'Perbaiki isi'])->assertRedirect();
        $this->assertSame('perlu_revisi', $doc->fresh()->status);
        $this->actingAs($user)->postJson(route('renja.editor.updateSection', [$doc->id, $section->id]), ['content' => '<p>Isi diperbaiki</p>'])->assertOk();
        $this->post(route('renja.submit', $doc->id))->assertRedirect();
        $this->assertSame('dikirim_ulang', $doc->fresh()->status);
        $this->actingAs($admin)->post(route('admin.verifikasi.decision', $doc->id), ['decision_type' => 'setujui_dokumen'])->assertRedirect();
        $this->assertSame('disetujui', $doc->fresh()->status);
        $this->assertSame('<p>Isi diperbaiki</p>', $section->fresh()->content);
    }

    public function test_declared_controller_routes_have_actions(): void
    {
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (!str_starts_with($action, 'App\\Http\\Controllers\\') || !str_contains($action, '@')) continue;
            [$class, $method] = explode('@', $action);
            $this->assertTrue(method_exists($class, $method), $route->uri().' -> '.$action);
        }
    }

    public function test_template_catalog_preserves_existing_templates(): void
    {
        $template = DocumentTemplate::create(['code' => 'RKPD', 'name' => 'Existing RKPD']);
        app(DocumentTemplateService::class)->ensureStandardTemplatesSeeded();
        $this->assertNotNull($template->fresh());
    }

    public function test_docx_entities_are_rejected(): void
    {
        $service = app(RenjaMurniDocxService::class);
        $service->mockXml = '<!DOCTYPE x [<!ENTITY secret SYSTEM "file:///not-allowed">]><x>&secret;</x>';
        $this->expectException(\RuntimeException::class);
        $service->parseDocxStructure('unused');
    }

    public function test_line_breaks_and_blockquotes_survive_bold_filter(): void
    {
        [$user, $doc, $section] = $this->fixture();
        $this->actingAs($user)->postJson(route('renja.editor.updateSection', [$doc->id, $section->id]), ['content' => '<blockquote>A<br>B</blockquote>'])->assertOk();
        $this->assertStringContainsString('<br>', $section->fresh()->content);
        $this->assertStringContainsString('<blockquote>', $section->fresh()->content);
    }

    public function test_queue_excludes_drafts_and_other_cycles_even_with_year_filter(): void
    {
        [, $doc] = $this->fixture('operator', 'submitted');
        [, $draft] = $this->fixture();
        [, $old] = $this->fixture('operator', 'submitted');
        $old->update(['tahun_anggaran' => 2029]);
        $service = app(VerificationWorkspaceService::class);
        $this->assertSame([$doc->id], $service->getFilteredWorkspaceDocuments()->pluck('id')->all());
        $this->assertCount(0, $service->getFilteredWorkspaceDocuments(null, 'all', 'all', '2029'));
    }
    public function test_formatter_rejects_another_opd_before_processing(): void
    {
        [$user] = $this->fixture();
        [$other] = $this->fixture();
        $this->actingAs($user)->post(route('formatter.process'), ['opd_id' => $other->opd_id])->assertForbidden();
        $this->get(route('formatter.download', 'unowned.docx'))->assertForbidden();
    }

    public function test_html_display_blocks_executable_markup_without_rewriting_original(): void
    {
        [, $document, $section] = $this->fixture();
        $raw = '<p onclick="alert(1)">Narasi</p><script>alert(2)</script><a href="javascript:alert(3)">Link</a><table><tr><td>123</td></tr></table>';
        $section->update(['content' => $raw]);
        $section->refresh();
        $this->assertSame($raw, $section->getRawOriginal('content'));
        $rendered = app(\App\Services\DocumentHtmlService::class)->forDocumentDisplay($document, $section->content);
        $this->assertSame($raw, $section->content);
        $this->assertStringNotContainsString('<script', $rendered);
        $this->assertStringNotContainsString('onclick', $rendered);
        $this->assertStringNotContainsString('javascript:', $rendered);
        $this->assertStringContainsString('<td>123</td>', $rendered);
    }

    public function test_document_list_defaults_to_active_cycle_and_includes_uppercase_drafts(): void
    {
        [$user, $doc] = $this->fixture();
        $doc->update(['status' => 'DRAFT']);
        RenjaDocument::create(['opd_id' => $user->opd_id, 'tahun_anggaran' => 2025, 'jenis_dokumen' => 'RENJA Murni', 'status' => 'draft']);
        $this->actingAs($user);
        $data = app(\App\Services\OpdDocumentService::class)->getOpdWorkspaceData($user->opd_id, ['status' => 'draft']);
        $this->assertSame([$doc->id], $data['documents']->pluck('id')->all());
        $this->assertSame(1, $data['kpi']['draft']['count']);
    }

    public function test_renaming_renja_does_not_change_its_cycle(): void
    {
        [$user, $doc] = $this->fixture();
        $this->actingAs($user)->post(route('renja.updateName', $doc->id), ['nama_dokumen' => 'Judul baru tanpa nama jenis'])->assertRedirect();
        $this->assertSame('RENJA Murni', $doc->fresh()->jenis_dokumen);
        $this->assertSame('Judul baru tanpa nama jenis', $doc->fresh()->cover_data['judul_dokumen']);
    }

    public function test_lampiran_rejects_parent_from_another_opd_or_year(): void
    {
        [, $parent] = $this->fixture();
        [, $lampiran] = $this->fixture();
        $lampiran->update(['jenis_dokumen' => 'Lampiran RENJA Murni', 'original_document_id' => $parent->id]);
        $this->assertNull($lampiran->getParentDocument());
        $this->assertCount(0, $lampiran->getEffectiveSections());
        $lampiran->update(['opd_id' => $parent->opd_id, 'tahun_anggaran' => 2032]);
        $this->assertNull($lampiran->getParentDocument());
        $lampiran->update(['tahun_anggaran' => 2031]);
        $this->assertSame($parent->id, $lampiran->getParentDocument()->id);
    }

    public function test_export_generates_indexes_without_modifying_saved_sections(): void
    {
        [, $doc] = $this->fixture('operator', 'final');
        $toc = $doc->sections()->create(['section_type' => 'table_of_contents', 'bab_code' => 'TOC', 'bab_title' => 'Daftar Isi', 'sub_bab_code' => 'TOC', 'sub_bab_title' => 'Daftar Isi', 'content' => '<p>Indeks tersimpan</p>']);
        $before = $toc->fresh()->getRawOriginal();
        app(\App\Services\RenjaIndexGeneratorService::class)->prepareExportSections($doc);
        $this->assertStringContainsString('1.1', $doc->sections->firstWhere('id', $toc->id)->content);
        $this->assertSame($before, $toc->fresh()->getRawOriginal());
    }

    public function test_word_export_rejects_remote_image_without_fetching_it(): void
    {
        [, $doc, $section] = $this->fixture();
        $section->update(['content' => '<img src="http://127.0.0.1/private">']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\WordExportService::class)->generateRenjaDocx($doc);
    }

    public function test_monitoring_uses_active_cycle_and_distinct_opds(): void
    {
        [$admin, $doc] = $this->fixture('admin', 'approved');
        RenjaDocument::create(['opd_id' => $admin->opd_id, 'jenis_dokumen' => 'RENJA Perubahan', 'tahun_anggaran' => 2030, 'status' => 'final']);
        [, $old] = $this->fixture('operator', 'approved');
        $old->update(['tahun_anggaran' => 2020]);
        $this->actingAs($admin)->get(route('bapperida.monitoring', ['status' => 'approved']))
            ->assertOk()->assertViewHas('stats', fn ($stats) => $stats['total_docs'] === 2 && $stats['completion_rate'] === 50.0)
            ->assertViewHas('opds', fn ($opds) => $opds->count() === 1);
    }

    public function test_sipd_form_and_display_use_actual_columns_and_realization(): void
    {
        [$user, $doc] = $this->fixture();
        $this->actingAs($user)->post(route('renja.editor.addTableEval', $doc->id), [
            'jenis_tabel' => 'evaluasi_2.1', 'kode_rekening' => '1.02.03', 'nama_program_kegiatan' => 'Program audit SIPD',
            'indikator_kinerja' => 'Jumlah', 'pagu_indikatif' => 1000, 'realisasi_pagu' => 250,
        ])->assertSessionHasNoErrors();
        $this->get(route('renja.editor', $doc->id))->assertOk()->assertSee('modal-add-sipd')
            ->assertSee('Program audit SIPD')->assertSee('1.02.03')->assertSee('25,0%');
    }

    public function test_active_year_redirect_cannot_leave_application(): void
    {
        [$user] = $this->fixture();
        $response = $this->actingAs($user)->withHeader('Referer', 'https://outside.example/documents')
            ->get(route('set-ta', ['tahun_anggaran' => 2030]));
        $response->assertRedirect(route('dashboard'))->assertSessionHas('active_ta', 2030);
    }

}
