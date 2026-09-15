<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RenjaLampiranWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $operatorUser;
    protected User $adminUser;
    protected MasterOpd $opdDepok;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $this->opdDepok = MasterOpd::create([
            'kode_opd' => '9.01.038',
            'nama_opd' => 'Kecamatan Depok',
            'nomor_lampiran_romawi' => 'LAMPIRAN XXXVIII',
        ]);

        $this->operatorUser = User::factory()->create([
            'name' => 'Operator Depok',
            'username_nip' => '199001012020011001',
            'role' => 'operator',
            'opd_id' => $this->opdDepok->id,
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin Bapperida',
            'role' => 'admin',
            'opd_id' => null,
        ]);

        DocumentTemplate::create([
            'code' => 'RENJA_LAMPIRAN_MURNI',
            'name' => 'Template RENJA Lampiran Murni Perbup',
            'type' => 'renja_lampiran_murni',
            'file_path' => 'templates/renja_lampiran_murni.docx',
            'metadata' => [
                'has_cover' => false,
                'has_preface' => false,
                'has_toc' => false,
                'default_font' => 'Bookman Old Style',
                'paper_size' => 'F4',
                'margin_cm' => 2.0,
            ],
        ]);

        DocumentTemplate::create([
            'code' => 'RENJA_LAMPIRAN_PERUBAHAN',
            'name' => 'Template RENJA Lampiran Perubahan Kepbup',
            'type' => 'renja_lampiran_perubahan',
            'file_path' => 'templates/renja_lampiran_perubahan.docx',
            'metadata' => [
                'has_cover' => false,
                'has_preface' => false,
                'has_toc' => false,
                'default_font' => 'Bookman Old Style',
                'paper_size' => 'F4',
                'margin_cm' => 2.0,
            ],
        ]);
    }

    /**
     * Helper untuk membuat file DOCX test menggunakan python-docx
     */
    protected function createTestDocxFile(string $outputPath, array $dirtyOptions = []): void
    {
        $pythonBinary = trim(shell_exec('where python') ?: 'python');
        $pythonBinary = explode("\n", str_replace("\r", "", $pythonBinary))[0];

        $hasBold = $dirtyOptions['has_bold'] ?? false;
        $hasHeader = $dirtyOptions['has_header'] ?? false;
        $hasCover = $dirtyOptions['has_cover'] ?? false;

        $script = sprintf("
import docx
from docx.shared import Mm, Pt

doc = docx.Document()
sec = doc.sections[0]
sec.page_width = Mm(%f)
sec.page_height = Mm(%f)
sec.top_margin = Mm(%f)
sec.bottom_margin = Mm(%f)
sec.left_margin = Mm(%f)
sec.right_margin = Mm(%f)

if %s:
    sec.header.is_linked_to_previous = False
    hp = sec.header.paragraphs[0] if sec.header.paragraphs else sec.header.add_paragraph()
    hp.text = 'Header Bawaan Tidak Standar'

if %s:
    p_cov = doc.add_paragraph('COVER DOKUMEN RENJA PERANGKAT DAERAH')
    p_cov.runs[0].font.name = 'Arial'

def add_clean_p(text):
    p = doc.add_paragraph()
    r = p.add_run(text)
    r.font.name = '%s'
    r.font.size = Pt(%d)
    r.bold = %s
    return p

add_clean_p('BAB I. PENDAHULUAN')
add_clean_p('Latar Belakang Renja Kecamatan Depok TA 2027 dengan pagu Rp 1.500.000.000.')

tbl = doc.add_table(rows=2, cols=2)
tbl.autofit = False
tbl.columns[0].width = Mm(75)
tbl.columns[1].width = Mm(80)

for row_idx, row in enumerate(tbl.rows):
    row.cells[0].width = Mm(75)
    row.cells[1].width = Mm(80)
    
    p0 = row.cells[0].paragraphs[0]
    p0.text = ''
    r0 = p0.add_run('Program Pelayanan' if row_idx == 1 else 'Program / Kegiatan')
    r0.font.name = '%s'
    r0.font.size = Pt(%d)
    r0.bold = False

    p1 = row.cells[1].paragraphs[0]
    p1.text = ''
    r1 = p1.add_run('Rp 1.500.000.000' if row_idx == 1 else 'Pagu Anggaran')
    r1.font.name = '%s'
    r1.font.size = Pt(%d)
    r1.bold = False

doc.save(r'%s')
",
            $dirtyOptions['width_mm'] ?? 215.0,
            $dirtyOptions['height_mm'] ?? 330.0,
            ($dirtyOptions['top_cm'] ?? 2.0) * 10.0,
            ($dirtyOptions['bottom_cm'] ?? 2.0) * 10.0,
            ($dirtyOptions['left_cm'] ?? 2.0) * 10.0,
            ($dirtyOptions['right_cm'] ?? 2.0) * 10.0,
            $hasHeader ? 'True' : 'False',
            $hasCover ? 'True' : 'False',
            $dirtyOptions['font_name'] ?? 'Bookman Old Style',
            $dirtyOptions['font_size_pt'] ?? 12,
            $hasBold ? 'True' : 'False',
            $dirtyOptions['font_name'] ?? 'Bookman Old Style',
            $dirtyOptions['font_size_pt'] ?? 12,
            $dirtyOptions['font_name'] ?? 'Bookman Old Style',
            $dirtyOptions['font_size_pt'] ?? 12,
            addslashes($outputPath)
        );

        $pyScriptPath = storage_path('app/temp_gen_workflow_' . uniqid() . '.py');
        file_put_contents($pyScriptPath, $script);
        shell_exec(sprintf('%s "%s"', $pythonBinary, $pyScriptPath));
        if (file_exists($pyScriptPath)) {
            @unlink($pyScriptPath);
        }
    }

    /**
     * TEST 1: Workflow RENJA Lampiran Murni End-to-End (Upload -> Format Check -> Preview -> Submit)
     */
    public function test_renja_lampiran_murni_workflow_end_to_end(): void
    {
        $response = $this->actingAs($this->operatorUser)
            ->get(route('renja.lampiran.index', ['tahun_anggaran' => 2027]));

        $response->assertStatus(200);
        $response->assertSee('RENJA Lampiran Murni');

        // 1. Upload DOCX Compliant
        $tempPath = storage_path('app/test_murni_compliant.docx');
        $this->createTestDocxFile($tempPath, [
            'font_name' => 'Bookman Old Style',
            'font_size_pt' => 12,
            'has_bold' => false,
            'has_header' => false,
            'has_cover' => false,
        ]);

        $uploadedFile = UploadedFile::fake()->createWithContent('Renja_Lampiran_Murni_Depok.docx', file_get_contents($tempPath));

        $uploadRes = $this->actingAs($this->operatorUser)
            ->post(route('renja.lampiran.uploadMurni'), [
                'tahun_anggaran' => 2027,
                'document_file' => $uploadedFile,
            ]);

        $uploadRes->assertRedirect(route('renja.lampiran.index', ['tahun_anggaran' => 2026]));

        $doc = RenjaDocument::first();
        $this->assertNotNull($doc, 'Dokumen harus berhasil dibuat');
        $this->assertEquals('RENJA Lampiran Murni', $doc->jenis_dokumen);
        $this->assertEquals('draft', $doc->status);

        // 2. Buka Halaman Preview Validasi Final
        $previewRes = $this->actingAs($this->operatorUser)
            ->get(route('renja.preview', $doc->id));

        $previewRes->assertStatus(200);
        $previewRes->assertSee('RENJA Lampiran Murni');
        $previewRes->assertSee('Lampiran Perbup Renja');

        // 3. Submit Dokumen yang Sudah Sesuai
        $submitRes = $this->actingAs($this->operatorUser)
            ->post(route('renja.submit', $doc->id));

        $submitRes->assertSessionHas('success');
        $doc->refresh();
        $this->assertEquals('submitted', $doc->status);
    }

    /**
     * TEST 2: Workflow RENJA Lampiran Perubahan End-to-End
     */
    public function test_renja_lampiran_perubahan_workflow_end_to_end(): void
    {
        $tempPath = storage_path('app/test_perubahan_compliant.docx');
        $this->createTestDocxFile($tempPath, [
            'font_name' => 'Bookman Old Style',
            'font_size_pt' => 12,
            'has_bold' => false,
            'has_header' => false,
            'has_cover' => false,
        ]);

        $uploadedFile = UploadedFile::fake()->createWithContent('Renja_Lampiran_Perubahan_Depok.docx', file_get_contents($tempPath));

        $uploadRes = $this->actingAs($this->operatorUser)
            ->post(route('renja.lampiran.uploadPerubahan'), [
                'tahun_anggaran' => 2027,
                'document_file' => $uploadedFile,
            ]);

        $uploadRes->assertRedirect(route('renja.lampiran.index', ['tahun_anggaran' => 2027]));

        $doc = RenjaDocument::where('jenis_dokumen', 'RENJA Lampiran Perubahan')->first();
        $this->assertNotNull($doc);
        $this->assertEquals('draft', $doc->status);

        // Preview Perubahan
        $previewRes = $this->actingAs($this->operatorUser)
            ->get(route('renja.preview', $doc->id));

        $previewRes->assertStatus(200);
        $previewRes->assertSee('RENJA Lampiran Perubahan');
        $previewRes->assertSee('Lampiran Kepbup Renja');

        // Submit Perubahan Valid
        $submitRes = $this->actingAs($this->operatorUser)
            ->post(route('renja.submit', $doc->id));

        $submitRes->assertSessionHas('success');
        $doc->refresh();
        $this->assertEquals('submitted', $doc->status);
    }
}
