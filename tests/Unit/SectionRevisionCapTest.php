<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SectionRevisionCapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test single revision snapshot creation.
     */
    public function test_add_single_revision_snapshot()
    {
        $opd = MasterOpd::create(['nama_opd' => 'Dinas Pendidikan', 'kode_opd' => '1.01.000']);
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
            'content' => 'Isi Awal Bab I',
            'is_completed' => true
        ]);

        $revisions = $section->addRevisionSnapshot('Isi Revisi Pertama', 'Snapshot 1', 'Operator Test');

        $this->assertCount(1, $revisions);
        $this->assertEquals('Snapshot 1', $revisions[0]['note']);
        $this->assertEquals('Operator Test', $revisions[0]['user_name']);
    }

    /**
     * Test FIFO revision capping (Max 5 entries).
     */
    public function test_fifo_revision_capping_limits_to_maximum_5_entries()
    {
        $opd = MasterOpd::create(['nama_opd' => 'Dinas Pekerjaan Umum', 'kode_opd' => '1.03.000']);
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
            'content' => 'Isi Versi 0',
            'is_completed' => true
        ]);

        // Simpan 7 snapshot berturut-turut
        for ($i = 1; $i <= 7; $i++) {
            $section->addRevisionSnapshot("Konten Versi $i", "Catatan Snapshot Versi $i", "Operator Unit $i");
        }

        $freshSection = $section->fresh();
        $revisions = $freshSection->metadata['revisions'] ?? [];

        // Harus tepat 5 entri (max 5 cap)
        $this->assertCount(5, $revisions);

        // Entri paling atas adalah entri terbaru (Versi 7)
        $this->assertEquals('Catatan Snapshot Versi 7', $revisions[0]['note']);
        
        // Entri paling bawah adalah entri Versi 3 (Versi 1 dan 2 terhapus via FIFO)
        $this->assertEquals('Catatan Snapshot Versi 3', $revisions[4]['note']);
    }
}
