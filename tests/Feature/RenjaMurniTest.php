<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\DocumentTemplate;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RenjaMurniTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    private function createOperator(string $username = 'operator_test_1', ?int $opdId = null): User
    {
        $opd = $opdId ? MasterOpd::find($opdId) : MasterOpd::first();
        if (!$opd) {
            $opd = MasterOpd::create([
                'nama_opd' => 'Dinas Pendidikan',
                'singkatan_opd' => 'Disdik',
                'kepala_opd' => 'Dr. H. Kepala, M.Pd.',
                'nip_kepala_opd' => '197501012000011001',
                'jabatan_kepala' => 'Kepala Dinas Pendidikan',
                'nomor_lampiran_romawi' => 'LAMPIRAN IV',
            ]);
        }

        return User::create([
            'username_nip' => $username,
            'nama_lengkap' => 'Operator ' . $opd->nama_opd,
            'email' => $username . '@cirebonkab.go.id',
            'password' => bcrypt('password123'),
            'role' => 'operator',
            'opd_id' => $opd->id,
        ]);
    }

    /**
     * Test 1: Operator dapat membuka halaman daftar RENJA Murni.
     */
    public function test_operator_can_view_renja_murni_index(): void
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->get(route('operator.renja-murni.index'));

        $response->assertStatus(200);
        $response->assertSee('Dokumen RENJA Murni');
        $response->assertSee('+ Tambah RENJA Murni');
    }

    /**
     * Test 2: Master Template RENJA Lampiran Perbub terdaftar lengkap dengan seksi.
     */
    public function test_master_template_renja_lampiran_perbub_is_seeded(): void
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->get(route('operator.templates.renja-murni'));

        $response->assertStatus(200);
        $response->assertSee('Template Lampiran');
        $response->assertSee('BAB I');
        $response->assertSee('BAB II');
        $response->assertSee('BAB III');
        $response->assertSee('BAB IV');
        $response->assertSee('BAB V');
    }

    /**
     * Test 3: Operator dapat membuat RENJA Murni dari Master Template.
     */
    public function test_operator_can_create_renja_murni_from_template(): void
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->post(route('operator.renja-murni.store-template'), [
            'tahun_anggaran' => 2027,
        ]);

        $this->assertDatabaseHas('renja_documents', [
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2027,
            'source_type' => 'template',
            'status' => 'draft',
        ]);

        $doc = RenjaDocument::where('opd_id', $operator->opd_id)->latest()->first();
        $this->assertNotNull($doc);
        $this->assertGreaterThan(0, $doc->sections()->count());

        $response->assertRedirect(route('renja.editor', $doc->id));
    }

    /**
     * Test 3b: Operator dapat membuat RENJA Lampiran Murni dari Master Template.
     */
    public function test_operator_can_create_renja_lampiran_perbub_from_template(): void
    {
        $operator = $this->createOperator();
        $currentYear = (int) date('Y');

        $response = $this->actingAs($operator)->post(route('operator.renja-murni.store-template'), [
            'template_code' => 'RENJA_LAMPIRAN_MURNI',
        ]);

        $this->assertDatabaseHas('renja_documents', [
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => $currentYear + 1,
            'jenis_dokumen' => 'RENJA Lampiran Murni',
            'source_type' => 'template',
            'status' => 'draft',
        ]);

        $doc = RenjaDocument::where('opd_id', $operator->opd_id)->latest()->first();
        $this->assertNotNull($doc);
        $this->assertGreaterThan(0, $doc->sections()->count());

        $response->assertRedirect(route('renja.editor', $doc->id));
    }

    /**
     * Test 4: Operator dapat mengunggah file Word untuk membuat RENJA Murni.
     */
    public function test_operator_can_upload_word_document(): void
    {
        $operator = $this->createOperator();
        Storage::fake('local');

        // Buat file valid docx menggunakan Python pack
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_docx_' . uniqid();
        mkdir($tmpDir . DIRECTORY_SEPARATOR . 'word', 0777, true);
        mkdir($tmpDir . DIRECTORY_SEPARATOR . '_rels', 0777, true);

        file_put_contents($tmpDir . DIRECTORY_SEPARATOR . '[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>'
        );
        file_put_contents($tmpDir . DIRECTORY_SEPARATOR . '_rels' . DIRECTORY_SEPARATOR . '.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>'
        );
        file_put_contents($tmpDir . DIRECTORY_SEPARATOR . 'word' . DIRECTORY_SEPARATOR . 'document.xml',
            '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>BAB I PENDAHULUAN</w:t></w:r></w:p></w:body></w:document>'
        );

        $tempPath = $tmpDir . '.docx';
        $pyZipPath = str_replace('\\', '/', $tempPath);
        $pyTmpDir = str_replace('\\', '/', $tmpDir);
        $pyScript = "import zipfile, os\nwith zipfile.ZipFile(r'{$pyZipPath}', 'w', zipfile.ZIP_DEFLATED) as z:\n    for root, dirs, files in os.walk(r'{$pyTmpDir}'):\n        for file in files:\n            full_path = os.path.join(root, file)\n            arcname = os.path.relpath(full_path, r'{$pyTmpDir}')\n            z.write(full_path, arcname)\n";

        $pyFile = $tmpDir . '_pack.py';
        file_put_contents($pyFile, $pyScript);
        shell_exec("python \"{$pyFile}\" 2>&1");
        @unlink($pyFile);

        $file = new UploadedFile($tempPath, 'renja_dinas_2027.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $response = $this->actingAs($operator)->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'document_file' => $file,
        ]);

        $doc = RenjaDocument::where('opd_id', $operator->opd_id)->latest()->first();
        $this->assertNotNull($doc);
        $this->assertEquals('upload_word', $doc->source_type);

        $response->assertRedirect(route('renja.workspace', ['tahun_anggaran' => 2026]));
        @unlink($tempPath);
    }

    /**
     * Test 5: Operator dapat melihat halaman validasi dokumen.
     */
    public function test_operator_can_validate_document(): void
    {
        $operator = $this->createOperator();

        $doc = RenjaDocument::create([
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2026,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
            'source_type' => 'upload_word',
        ]);

        RenjaSection::create([
            'document_id' => $doc->id,
            'section_type' => 'subchapter',
            'bab_code' => 'BAB IV',
            'bab_title' => 'Rencana Kerja dan Pendanaan',
            'sub_bab_code' => '4.2',
            'sub_bab_title' => 'Matriks Rencana Kerja',
            'content' => '<p>Teks Matriks Rencana Kerja</p>',
            'order_index' => 1,
            'metadata' => ['orientation' => 'portrait'],
        ]);

        // Cek halaman validasi
        $valResponse = $this->actingAs($operator)->get(route('operator.renja-murni.validate', $doc->id));
        $valResponse->assertStatus(200);
    }

    /**
     * Test 6: Operator dapat submit dokumen RENJA Murni ke Admin Bapperida.
     */
    public function test_operator_can_submit_renja_murni(): void
    {
        $operator = $this->createOperator();

        $doc = RenjaDocument::create([
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
            'source_type' => 'template',
        ]);

        $response = $this->actingAs($operator)->post(route('renja.submit', $doc->id));

        $doc->refresh();
        $this->assertEquals('submitted', $doc->status);
        $this->assertNotNull($doc->submitted_at);
    }

    /**
     * Test Operator dapat submit dokumen dengan status autofix_confirmed atau autofix_completed.
     */
    public function test_operator_can_submit_autofix_confirmed_renja_murni(): void
    {
        $operator = $this->createOperator();

        $doc = RenjaDocument::create([
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'autofix_confirmed',
            'source_type' => 'upload_word',
        ]);

        $response = $this->actingAs($operator)->post(route('renja.submit', $doc->id));

        $doc->refresh();
        $this->assertEquals('submitted', $doc->status);
        $this->assertNotNull($doc->submitted_at);
    }

    /**
     * Test 7: Isolasi OPD - Operator tidak dapat mengakses dokumen OPD lain.
     */
    public function test_opd_isolation_security(): void
    {
        $allOpds = MasterOpd::all();
        $opd1 = $allOpds->first();
        $opd2 = $allOpds->skip(1)->first() ?? MasterOpd::create([
            'nama_opd' => 'Dinas Kesehatan',
            'singkatan_opd' => 'Dinkes',
            'kepala_opd' => 'dr. H. Kepala Dinkes',
            'nip_kepala_opd' => '198001012005011002',
            'jabatan_kepala' => 'Kepala Dinas Kesehatan',
            'nomor_lampiran_romawi' => 'LAMPIRAN V',
        ]);

        $operator1 = $this->createOperator('operator_disdik', $opd1->id);
        $operator2 = $this->createOperator('operator_dinkes', $opd2->id);

        $docOpd2 = RenjaDocument::create([
            'opd_id' => $opd2->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
            'source_type' => 'template',
        ]);

        // Operator 1 mencoba membuka validasi dokumen OPD 2 -> 403 Forbidden
        $response = $this->actingAs($operator1)->get(route('operator.renja-murni.validate', $docOpd2->id));
        $response->assertStatus(403);
    }

    /**
     * Test 8: Operator dapat membuat RENJA Perubahan apabila RENJA Murni telah disetujui.
     */
    public function test_operator_can_create_renja_perubahan_when_murni_approved(): void
    {
        $operator = $this->createOperator();

        // 1. Buat RENJA Murni berstatus 'disetujui'
        $murni = RenjaDocument::create([
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'disetujui',
            'source_type' => 'template',
        ]);

        RenjaSection::create([
            'document_id' => $murni->id,
            'section_type' => 'chapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => 'BAB I',
            'sub_bab_title' => 'Pendahuluan',
            'content' => '<p>Konten Pendahuluan Murni Disetujui</p>',
            'order_index' => 1,
        ]);

        // 2. Operator submit buat RENJA Perubahan
        $response = $this->actingAs($operator)->post(route('renja.storePerubahan'), [
            'tahun_anggaran' => 2026,
        ]);

        $response->assertRedirect(route('renja.workspace', ['tahun_anggaran' => 2026]));
        $response->assertSessionHas('success');

        // 3. Verifikasi dokumen Perubahan dibuat dan seksi disalin
        $perubahan = RenjaDocument::where('opd_id', $operator->opd_id)
            ->where('tahun_anggaran', 2026)
            ->where('jenis_dokumen', 'LIKE', '%Perubahan%')
            ->first();

        $this->assertNotNull($perubahan);
        $this->assertEquals('draft', $perubahan->status);
        $this->assertGreaterThan(0, $perubahan->sections()->count());
    }



    /**
     * Test 10: Operator dapat melihat fitur dokumen RENJA FIX (Disetujui) pada Workspace dan Viewer Dokumen.
     */
    public function test_operator_can_view_fixed_approved_document_in_workspace_and_viewer(): void
    {
        $operator = $this->createOperator();

        // 1. Buat RENJA Murni yang telah disetujui (FIX)
        $murni = RenjaDocument::create([
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'disetujui',
            'source_type' => 'template',
        ]);

        RenjaSection::create([
            'document_id' => $murni->id,
            'section_type' => 'chapter',
            'bab_code' => 'BAB I',
            'bab_title' => 'Pendahuluan',
            'sub_bab_code' => 'BAB I',
            'sub_bab_title' => 'Pendahuluan',
            'content' => '<p>Isi Dokumen Renja Murni yang Sah</p>',
            'order_index' => 1,
        ]);

        // 2. Buka Workspace RENJA -> Pastikan ada tombol dan banner 'Lihat Dokumen Fix'
        $workspaceRes = $this->actingAs($operator)->get(route('renja.workspace', ['tahun_anggaran' => 2026]));
        $workspaceRes->assertStatus(200);
        $workspaceRes->assertSee('Lihat Dokumen Fix');
        $workspaceRes->assertSee('DOKUMEN RESMI (FIX)');

        // 3. Buka Viewer Cetak/Print Dokumen Fix -> Pastikan status FIX RESMI dan Bookman Old Style F4 tampil
        $firstSection = $murni->sections->first();
        $viewerRes = $this->followingRedirects()->actingAs($operator)->get(route('renja.fix.show', ['id' => $murni->id, 'section' => $firstSection->id]));
        $viewerRes->assertStatus(200);
        $viewerRes->assertSee('Isi Dokumen Renja Murni yang Sah');
    }

    /**
     * Test 11: Kecamatan Depok (OPD 38) otomatis menghasilkan Lampiran XXXVIII
     */
    public function test_operator_depok_auto_maps_to_xxxviii_lampiran(): void
    {
        $depokOpd = MasterOpd::updateOrCreate(
            ['kode_opd' => '1.38.0.00.0.00.01.0000'],
            [
                'nama_opd' => 'Kecamatan Depok',
                'lampiran_number' => 38,
                'nomor_lampiran_romawi' => 'XXXVIII',
            ]
        );

        $operator = User::create([
            'name' => 'Operator Depok',
            'username_nip' => 'operator_depok_test',
            'nama_lengkap' => 'Operator Depok',
            'email' => 'operator.depok.test@cirebonkab.go.id',
            'password' => bcrypt('password123'),
            'role' => 'operator',
            'opd_id' => $depokOpd->id,
        ]);

        $this->actingAs($operator)->post(route('operator.renja-murni.store-template'), [
            'template_code' => 'RENJA_LAMPIRAN_MURNI',
        ]);

        $doc = RenjaDocument::where('opd_id', $depokOpd->id)->latest()->first();
        $this->assertNotNull($doc);
        $this->assertEquals($depokOpd->id, $doc->opd_id);
        $this->assertEquals('XXXVIII', $doc->opd->nomor_lampiran_romawi);
    }

    /**
     * Test 12: Aturan tahun dinamis (currentYear + 1 untuk Murni, currentYear untuk Perubahan)
     */
    public function test_dynamic_years_for_lampiran_murni_and_perubahan(): void
    {
        $operator = $this->createOperator();
        $currentYear = (int) date('Y');

        // Murni -> currentYear + 1
        $this->actingAs($operator)->post(route('operator.renja-murni.store-template'), [
            'template_code' => 'RENJA_LAMPIRAN_MURNI',
        ]);
        $docMurni = RenjaDocument::where('opd_id', $operator->opd_id)->where('jenis_dokumen', 'RENJA Lampiran Murni')->latest()->first();
        $this->assertNotNull($docMurni);
        $this->assertEquals($currentYear + 1, $docMurni->tahun_anggaran);

        // Perubahan -> currentYear
        $this->actingAs($operator)->post(route('operator.renja-murni.store-template'), [
            'template_code' => 'RENJA_LAMPIRAN_PERUBAHAN',
        ]);
        $docPerubahan = RenjaDocument::where('opd_id', $operator->opd_id)->where('jenis_dokumen', 'RENJA Lampiran Perubahan')->latest()->first();
        $this->assertNotNull($docPerubahan);
        $this->assertEquals($currentYear, $docPerubahan->tahun_anggaran);
    }

    /**
     * Test 13: Keamanan request override opd_id dan nomor lampiran romawi
     */
    public function test_security_operator_cannot_override_opd_id_or_romawi(): void
    {
        $operator = $this->createOperator();
        $otherOpd = MasterOpd::create([
            'kode_opd' => '1.06.0.00.0.00.01.9999',
            'nama_opd' => 'Dinas Pekerjaan Umum dan Tata Ruang Dummy',
            'lampiran_number' => 99,
            'nomor_lampiran_romawi' => 'XCIX',
        ]);

        $this->actingAs($operator)->post(route('operator.renja-murni.store-template'), [
            'template_code' => 'RENJA_LAMPIRAN_MURNI',
            'opd_id' => $otherOpd->id,
            'nomor_lampiran_romawi' => 'VI',
        ]);

        $doc = RenjaDocument::where('template_id', DocumentTemplate::where('code', 'RENJA_LAMPIRAN_MURNI')->first()->id)->latest()->first();
        $this->assertNotNull($doc);
        // Harus tetap menggunakan OPD asli sang operator
        $this->assertEquals($operator->opd_id, $doc->opd_id);
        $this->assertNotEquals($otherOpd->id, $doc->opd_id);
    }
}
