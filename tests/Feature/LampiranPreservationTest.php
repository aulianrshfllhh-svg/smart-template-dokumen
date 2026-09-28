<?php

namespace Tests\Feature;

use App\Models\{MasterOpd, RenjaDocument, User};
use App\Services\RenjaIndexGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LampiranPreservationTest extends TestCase
{
    use RefreshDatabase;

    private function documents(string $variant): array
    {
        $opd = MasterOpd::create(['kode_opd' => uniqid('keep'), 'nama_opd' => 'OPD Lampiran']);
        $user = User::factory()->create(['role' => 'operator', 'opd_id' => $opd->id]);
        $parent = RenjaDocument::create(['opd_id' => $opd->id, 'jenis_dokumen' => 'RENJA '.$variant, 'tahun_anggaran' => 2027, 'status' => 'draft']);
        $lampiran = RenjaDocument::create(['opd_id' => $opd->id, 'jenis_dokumen' => 'RENJA Lampiran '.$variant, 'tahun_anggaran' => 2027, 'status' => 'draft', 'original_document_id' => $parent->id, 'source_type' => 'upload_word']);
        return [$user, $parent, $lampiran];
    }

    public function test_both_variants_keep_parent_html_numbering_and_order_exactly(): void
    {
        foreach (['Murni', 'Perubahan'] as $variant) {
            [, $parent, $lampiran] = $this->documents($variant);
            $html = '<p data-word="original" style="font-weight:700"><u>Isi khusus</u><br></p><custom-word>Marker</custom-word><table><tr><td rowspan="2">Angka 42</td></tr></table>';
            foreach ([['2.3', 30], ['1.1', 10]] as [$code, $order]) {
                $parent->sections()->create(['section_type' => 'subchapter', 'bab_code' => 'BAB KHUSUS', 'bab_title' => 'Judul asli', 'sub_bab_code' => $code, 'sub_bab_title' => 'Bagian '.$code, 'order_index' => $order, 'content' => $html]);
            }
            $before = $parent->sections()->get()->map->getRawOriginal()->all();
            $effective = $lampiran->getEffectiveSections();
            $this->assertSame($parent->sections->pluck('sub_bab_code')->all(), $effective->pluck('sub_bab_code')->all());
            foreach ($effective as $section) {
                $this->assertSame($html, $section->content);
                $this->assertSame('BAB KHUSUS', $section->bab_code);
            }
            $this->assertSame($before, $parent->sections()->get()->map->getRawOriginal()->all());
            $this->assertSame(0, $lampiran->sections()->count());
        }
    }

    public function test_legacy_narrative_fallback_is_not_sanitized_by_new_accessor(): void
    {
        foreach (['Murni', 'Perubahan'] as $variant) {
            [, $parent, $lampiran] = $this->documents($variant);
            $html = '<custom-word><p style="font-weight:700">Narasi asli</p></custom-word>';
            $parent->update(['latar_belakang' => $html]);
            $section = $parent->sections()->create(['section_type' => 'subchapter', 'bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.1', 'sub_bab_title' => 'Latar Belakang', 'content' => '']);
            $this->assertSame($html, $lampiran->getEffectiveSections()->first()->content);
            $this->assertSame('', $section->fresh()->getRawOriginal('content'));
            $this->assertSame($html, $parent->fresh()->getRawOriginal('latar_belakang'));
        }
    }

    public function test_new_index_preparation_does_not_touch_lampiran_or_replace_its_relations(): void
    {
        foreach (['Murni', 'Perubahan'] as $variant) {
            [, , $lampiran] = $this->documents($variant);
            $section = $lampiran->sections()->create(['section_type' => 'table_of_contents', 'bab_code' => 'TOC', 'bab_title' => 'Indeks khusus', 'sub_bab_code' => 'TOC', 'sub_bab_title' => 'Indeks khusus', 'content' => '<p>Urutan manual asli</p>']);
            $lampiran->load('sections');
            $relation = $lampiran->sections;
            $before = $section->fresh()->getRawOriginal();
            app(RenjaIndexGeneratorService::class)->prepareExportSections($lampiran);
            $this->assertSame($relation, $lampiran->sections);
            $this->assertSame($before, $section->fresh()->getRawOriginal());
        }
    }

    public function test_uploaded_lampiran_still_redirects_to_preview_before_bab_selection(): void
    {
        foreach (['Murni', 'Perubahan'] as $variant) {
            [$user, , $lampiran] = $this->documents($variant);
            $this->actingAs($user)->get(route('renja.editor', $lampiran->id))
                ->assertRedirect(route('renja.preview', ['id' => $lampiran->id, 'is_lampiran' => 1]));
            $this->assertSame(0, $lampiran->sections()->count());
        }
    }

    public function test_word_service_preserves_its_existing_lampiran_section_source_and_data(): void
    {
        foreach (['Murni', 'Perubahan'] as $variant) {
            [, $parent, $lampiran] = $this->documents($variant);
            $attributes = ['section_type' => 'subchapter', 'bab_code' => 'BAB II', 'bab_title' => 'Bagian khusus', 'sub_bab_code' => '2.9', 'sub_bab_title' => 'Tabel asli', 'order_index' => 29];
            $parent->sections()->create($attributes + ['content' => '<p>MARKER_INDUK</p>']);
            $lampiran->sections()->create($attributes + ['content' => '<p>MARKER_LAMPIRAN</p><table><tr><td>ANGKA_123</td></tr></table>']);
            $before = $lampiran->sections()->get()->map->getRawOriginal()->all();
            $file = app(\App\Services\WordExportService::class)->generateRenjaDocx($lampiran);
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($file));
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            $this->assertStringContainsString('MARKER_LAMPIRAN', $xml);
            $this->assertStringContainsString('ANGKA_123', $xml);
            $this->assertStringContainsString('<w:tbl>', $xml);
            $this->assertStringNotContainsString('MARKER_INDUK', $xml);
            $this->assertSame($before, $lampiran->sections()->get()->map->getRawOriginal()->all());
        }
    }
}
