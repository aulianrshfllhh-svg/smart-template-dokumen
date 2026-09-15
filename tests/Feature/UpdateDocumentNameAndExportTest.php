<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Http\Controllers\RenjaDocumentController;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateDocumentNameAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * Test updating document name updates jenis_dokumen and cover_data['judul_dokumen'].
     */
    public function test_user_can_update_document_name()
    {
        $opd = MasterOpd::first();
        $user = User::factory()->create([
            'role' => 'admin',
            'opd_id' => $opd->id,
            'nama_lengkap' => 'Admin Rename Test',
            'username_nip' => '198801012024011009',
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Dokumen Manual (Tanpa Template)',
            'tahun_anggaran' => 2027,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->post(route('renja.updateName', $doc->id), [
            'nama_dokumen' => 'Rencana Kerja Bapperida TA 2027',
        ]);

        $response->assertRedirect();
        
        $doc->refresh();
        $this->assertEquals('Rencana Kerja Bapperida TA 2027', $doc->jenis_dokumen);
        $this->assertEquals('Rencana Kerja Bapperida TA 2027', $doc->cover_data['judul_dokumen']);
    }

    /**
     * Test export filename matches the set document name exactly without addition or omission.
     */
    public function test_export_filename_matches_exact_document_name()
    {
        $opd = MasterOpd::first();
        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'RKPD Kabupaten Cirebon 2027',
            'tahun_anggaran' => 2027,
            'status' => 'draft',
        ]);

        $controller = new RenjaDocumentController(app(\App\Services\OpdDocumentService::class));
        
        $filenameDocx = $controller->buildExportFilename($doc, 'docx');
        $filenamePdf = $controller->buildExportFilename($doc, 'pdf');

        $this->assertEquals('RKPD_Kabupaten_Cirebon_2027.docx', $filenameDocx);
        $this->assertEquals('RKPD_Kabupaten_Cirebon_2027.pdf', $filenamePdf);
    }
}
