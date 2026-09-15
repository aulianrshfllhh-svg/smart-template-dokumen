<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Http\Controllers\RenjaDocumentController;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StrictDocumentTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * BR-DOC-01 & BR-DOC-02: Jenis dokumen disimpan sesuai pilihan pengguna ke basis data (RKPD, RENJA, EVALUASI_RKPD, MANUAL).
     */
    public function test_document_creation_stores_user_selected_jenis_dokumen()
    {
        $opd = MasterOpd::first();
        $operator = User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '199601012024013001',
            'nama_lengkap' => 'Operator DocType Test 1'
        ]);

        $resRkpd = $this->actingAs($operator)->post(route('renja.store'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RKPD',
        ]);
        $resRkpd->assertRedirect();
        $this->assertDatabaseHas('renja_documents', [
            'jenis_dokumen' => 'RKPD',
        ]);

        $resEval = $this->actingAs($operator)->post(route('renja.store'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'EVALUASI_RKPD',
        ]);
        $resEval->assertRedirect();
        $this->assertDatabaseHas('renja_documents', [
            'jenis_dokumen' => 'EVALUASI_RKPD',
        ]);

        $resManual = $this->actingAs($operator)->post(route('renja.store'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'MANUAL',
        ]);
        $resManual->assertRedirect();
        $this->assertDatabaseHas('renja_documents', [
            'jenis_dokumen' => 'Dokumen Manual (Tanpa Template)',
        ]);
    }

    /**
     * BR-DOC-03: Filename ekspor dibentuk dari <JENIS_DOKUMEN>_<NAMA_OPD>_<TAHUN>.<ext> dan tidak memaksakan RENJA_.
     */
    public function test_export_filename_generation_follows_document_metadata()
    {
        $opd = MasterOpd::create([
            'kode_opd' => '1.01.01',
            'nama_opd' => 'Dinas Kesehatan',
            'alias' => 'DINKES'
        ]);

        $controller = app(RenjaDocumentController::class);

        // 1. Dokumen RKPD
        $docRkpd = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'RKPD Dinas Kesehatan 2027',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);
        $filenameRkpd = $controller->buildExportFilename($docRkpd, 'docx');
        $this->assertEquals('RKPD_Dinas_Kesehatan_2027.docx', $filenameRkpd);

        // 2. Dokumen Renja
        $docRenja = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'RENJA Dinas Kesehatan 2027',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);
        $filenameRenja = $controller->buildExportFilename($docRenja, 'docx');
        $this->assertEquals('RENJA_Dinas_Kesehatan_2027.docx', $filenameRenja);

        // 3. Dokumen Evaluasi RKPD
        $docEval = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'EVALUASI_RKPD Dinas Kesehatan 2027',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);
        $filenameEval = $controller->buildExportFilename($docEval, 'docx');
        $this->assertEquals('EVALUASI_RKPD_Dinas_Kesehatan_2027.docx', $filenameEval);

        // 4. Dokumen Manual
        $docManual = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'DOKUMEN MANUAL Dinas Kesehatan 2027',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);
        $filenameManual = $controller->buildExportFilename($docManual, 'docx');
        $this->assertEquals('DOKUMEN_MANUAL_Dinas_Kesehatan_2027.docx', $filenameManual);

        // 5. Dokumen Manual dengan judul kustom
        $docManualCustom = RenjaDocument::create([
            'opd_id' => $opd->id,
            'jenis_dokumen' => 'Analisis Pembangunan Daerah',
            'tahun_anggaran' => 2027,
            'status' => 'draft',
            'cover_data' => ['judul_dokumen' => 'Analisis Pembangunan Daerah']
        ]);
        $filenameCustom = $controller->buildExportFilename($docManualCustom, 'docx');
        $this->assertEquals('Analisis_Pembangunan_Daerah.docx', $filenameCustom);
    }
}
