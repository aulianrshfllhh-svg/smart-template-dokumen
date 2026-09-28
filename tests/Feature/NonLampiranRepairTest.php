<?php

namespace Tests\Feature;

use App\Models\{MasterOpd, RenjaDocument, User};
use App\Services\{DocumentHtmlService, WordExportService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NonLampiranRepairTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(string $type = 'RENJA Murni'): array
    {
        $opd = MasterOpd::create(['kode_opd' => uniqid('safe'), 'nama_opd' => 'OPD Test']);
        $user = User::factory()->create(['role' => 'operator', 'opd_id' => $opd->id]);
        $doc = RenjaDocument::create(['opd_id' => $opd->id, 'jenis_dokumen' => $type, 'tahun_anggaran' => 2027, 'status' => 'draft']);
        $section = $doc->sections()->create(['section_type' => 'subchapter', 'bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.1', 'sub_bab_title' => 'Latar Belakang', 'content' => '<p>Isi</p>']);
        return [$user, $doc, $section];
    }

    public function test_read_only_detail_removes_active_html_without_changing_parent_or_lampiran(): void
    {
        [$user, $parent, $section] = $this->fixture();
        $lampiran = RenjaDocument::create(['opd_id' => $parent->opd_id, 'jenis_dokumen' => 'RENJA Lampiran Murni', 'tahun_anggaran' => 2027, 'status' => 'draft', 'original_document_id' => $parent->id]);
        $raw = '<p onclick="window.__auditClick=1">Narasi</p><script>window.__auditPayload=1</script><table><tr><td>VALUE_42</td></tr></table>';
        $section->update(['content' => $raw]);
        $before = $section->fresh()->getRawOriginal();
        $this->actingAs($user)->get(route('renja.show', $parent->id))->assertOk()
            ->assertDontSee('window.__auditClick', false)->assertDontSee('window.__auditPayload', false)->assertSee('VALUE_42');
        $this->assertSame($before, $section->fresh()->getRawOriginal());
        $this->assertSame($raw, $lampiran->getEffectiveSections()->first()->content);
    }

    public function test_lampiran_display_returns_exact_original_html_for_both_variants_and_aliases(): void
    {
        $service = app(DocumentHtmlService::class);
        $raw = '<custom-word data-original="yes"><p onclick="legacy()" style="font-weight:700">Asli</p></custom-word>';
        foreach (['RENJA Lampiran Murni', 'RENJA Lampiran Perubahan', 'Perbub Renja Murni', 'Perbup Renja Murni', 'Kepbup Renja Perubahan'] as $type) {
            [, $doc] = $this->fixture($type);
            $this->assertSame($raw, $service->forDocumentDisplay($doc, $raw));
        }
    }

    public function test_non_lampiran_word_service_rejects_remote_images_without_changing_content(): void
    {
        [, $doc, $section] = $this->fixture();
        $raw = '<img src="http://127.0.0.1/private">';
        $section->update(['content' => $raw]);
        try {
            app(WordExportService::class)->generateRenjaDocx($doc);
            $this->fail('Remote image must be rejected before any fetch.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('export', $e->errors());
        }
        $this->assertSame($raw, $section->fresh()->content);
    }

    public function test_non_lampiran_legacy_export_endpoint_rejects_remote_images(): void
    {
        [$user, $doc, $section] = $this->fixture();
        $section->update(['content' => '<p>Narasi</p><img src="http://127.0.0.1/private">']);
        $this->actingAs($user)->getJson(route('renja.exportWord', $doc->id))->assertUnprocessable()->assertJsonValidationErrors('export');
    }

    public function test_word_service_still_skips_image_only_lampiran_as_before(): void
    {
        foreach (['RENJA Lampiran Murni', 'RENJA Lampiran Perubahan'] as $type) {
            [, $doc, $section] = $this->fixture($type);
            $raw = '<img src="http://127.0.0.1/not-fetched">';
            $section->update(['content' => $raw]);
            $file = app(WordExportService::class)->generateRenjaDocx($doc);
            $this->assertFileExists($file);
            $this->assertSame($raw, $section->fresh()->content);
        }
    }

    public function test_legacy_narrative_exports_without_creating_sections_or_changing_linked_lampiran(): void
    {
        [$user] = $this->fixture();
        $parent = RenjaDocument::create(['opd_id' => $user->opd_id, 'jenis_dokumen' => 'RENJA Murni', 'tahun_anggaran' => 2027, 'status' => 'draft', 'latar_belakang' => '<p>LEGACY_NARRATIVE</p>']);
        $lampiran = RenjaDocument::create(['opd_id' => $user->opd_id, 'jenis_dokumen' => 'RENJA Lampiran Murni', 'tahun_anggaran' => 2027, 'original_document_id' => $parent->id, 'status' => 'draft']);
        $before = $parent->fresh()->getRawOriginal();
        $file = app(WordExportService::class)->generateRenjaDocx($parent);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($file));
        $this->assertStringContainsString('LEGACY_NARRATIVE', $zip->getFromName('word/document.xml'));
        $zip->close();
        $this->assertSame($before, $parent->fresh()->getRawOriginal());
        $this->assertSame(0, $parent->sections()->count());
        $this->assertCount(0, $lampiran->getEffectiveSections());
        $this->assertSame(0, $lampiran->sections()->count());
    }

    public function test_non_lampiran_image_only_section_is_included_in_word(): void
    {
        [, $doc, $section] = $this->fixture();
        $raw = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a/e8AAAAASUVORK5CYII=">';
        $section->update(['content' => $raw]);
        $file = app(WordExportService::class)->generateRenjaDocx($doc);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($file));
        $this->assertStringContainsString('<v:imagedata', $zip->getFromName('word/document.xml'));
        $media = array_filter(range(0, $zip->numFiles - 1), fn ($index) => str_starts_with($zip->getNameIndex($index), 'word/media/'));
        $this->assertNotEmpty($media);
        $zip->close();
        $this->assertSame($raw, $section->fresh()->content);
    }

    public function test_print_and_pdf_views_filter_only_non_lampiran_content(): void
    {
        foreach (['RENJA Murni', 'RENJA Perubahan', 'RENJA Lampiran Murni', 'RENJA Lampiran Perubahan'] as $type) {
            [$user, $doc, $section] = $this->fixture($type);
            $raw = '<p onclick="window.__viewPayload=1">KEEP_NARRATIVE</p>';
            $section->update(['content' => $raw]);
            $this->actingAs($user);
            $groups = $doc->sections->groupBy('bab_code');
            $data = ['document' => $doc, 'frontSections' => collect(), 'groupedBabs' => $groups,
                'groupedSections' => $groups, 'appendixSections' => collect(), 'babTitleDefaults' => []];
            foreach (['renja.print', 'renja.pdf_export'] as $view) {
                $html = view($view, $data)->render();
                $this->assertStringContainsString('KEEP_NARRATIVE', $html);
                if (str_contains($type, 'Lampiran')) {
                    $this->assertStringContainsString($raw, $html);
                } else {
                    $this->assertStringNotContainsString('window.__viewPayload', $html);
                }
            }
            $this->assertSame($raw, $section->fresh()->content);
        }
    }

    public function test_lampiran_main_export_does_not_enter_new_html_or_image_processing(): void
    {
        $this->partialMock(DocumentHtmlService::class, function ($mock) {
            $mock->shouldNotReceive('forWord');
            $mock->shouldNotReceive('assertEmbeddedImages');
            $mock->shouldNotReceive('forDisplay');
        });
        foreach (['Murni', 'Perubahan'] as $variant) {
            [$user, $parent, $section] = $this->fixture('RENJA '.$variant);
            $section->update(['content' => '<p>ORIGINAL_LAMPIRAN_EXPORT</p>']);
            $before = $section->fresh()->getRawOriginal();
            $lampiran = RenjaDocument::create(['opd_id' => $user->opd_id, 'jenis_dokumen' => 'RENJA Lampiran '.$variant,
                'tahun_anggaran' => 2027, 'status' => 'draft', 'original_document_id' => $parent->id]);
            $response = $this->actingAs($user)->get(route('renja.exportWord', $lampiran->id));
            $response->assertOk();
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
            $this->assertStringContainsString('ORIGINAL_LAMPIRAN_EXPORT', $zip->getFromName('word/document.xml'));
            $zip->close();
            $this->assertSame($before, $section->fresh()->getRawOriginal());
            $this->assertSame(0, $lampiran->sections()->count());
        }
    }
}
