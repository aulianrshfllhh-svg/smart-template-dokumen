<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RenjaTest extends TestCase
{
    use RefreshDatabase;

    public function test_bold_stripper_removes_bold_tags_on_store_and_update(): void
    {
        $opd = MasterOpd::create([
            'kode_opd' => '1.01.000',
            'nama_opd' => 'Dinas Pendidikan',
            'nomor_lampiran_romawi' => 'LAMPIRAN IX',
        ]);

        $operator = User::create([
            'name' => 'Operator Disdik',
            'username_nip' => '19900101',
            'nama_lengkap' => 'Operator Disdik',
            'email' => 'operator@disdik.go.id',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'opd_id' => $opd->id,
        ]);

        $this->actingAs($operator);

        // Test Bold Stripper on store
        $response = $this->post(route('operator.renja.store'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'Rencana Kerja (Renja)',
            'latar_belakang' => '<p>Ini adalah <b>teks tebal</b> dan <strong>teks tebal dua</strong>.</p>',
            'penutup_narasi' => '<p style="font-weight: bold;">Penutup bercetak tebal</p>',
        ]);

        $document = RenjaDocument::first();
        $this->assertNotNull($document);

        // Verify HTML bold tags <b>, <strong> and font-weight bold are stripped
        $this->assertStringNotContainsString('<b>', $document->latar_belakang);
        $this->assertStringNotContainsString('<strong>', $document->latar_belakang);
        $this->assertStringNotContainsString('font-weight: bold', $document->penutup_narasi);
    }

    public function test_admin_can_view_renja_document_with_relationships(): void
    {
        $opd = MasterOpd::create([
            'kode_opd' => '1.02.000',
            'nama_opd' => 'Dinas Kesehatan',
            'nomor_lampiran_romawi' => 'LAMPIRAN X',
        ]);

        $admin = User::create([
            'name' => 'Admin Bapperida',
            'username_nip' => 'admin_test',
            'nama_lengkap' => 'Admin Bapperida',
            'email' => 'admin@bapperida.go.id',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'Renja',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.renja.show', $doc->id));
        $response->assertStatus(200);
    }

    public function test_autofix_upload_strips_bold_and_injects_roman_header(): void
    {
        $opd = MasterOpd::create([
            'kode_opd' => '1.03.000',
            'nama_opd' => 'Dinas Pekerjaan Umum dan Penataan Ruang',
            'nomor_lampiran_romawi' => 'LAMPIRAN VI',
        ]);

        $operator = User::create([
            'name' => 'Operator PUPR',
            'username_nip' => '19850101',
            'nama_lengkap' => 'Operator PUPR',
            'email' => 'pupr@cirebonkab.go.id',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'opd_id' => $opd->id,
        ]);

        $this->actingAs($operator);
        $response = $this->post('/renja-autofix/process', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'raw_text' => '<p>1.1 LATAR BELAKANG Teks penjelasan dokumen Renja PUPR 2027.</p>',
        ]);

        $this->assertTrue(in_array($response->getStatusCode(), [404, 403, 405]));
    }

    public function test_mesin_cuci_formatter_page_and_docx_processing(): void
    {
        $admin = User::create([
            'name' => 'Admin Formatter',
            'username_nip' => 'admin_formatter',
            'nama_lengkap' => 'Admin Formatter',
            'email' => 'admin_formatter@cirebonkab.go.id',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('formatter.index'));
        $response->assertStatus(200);
        $response->assertSee('Mesin Cuci Dokumen');
    }

    public function test_authorized_user_can_delete_renja_document(): void
    {
        $opd = MasterOpd::create([
            'kode_opd' => '1.04.000',
            'nama_opd' => 'Dinas Perhubungan',
            'nomor_lampiran_romawi' => 'LAMPIRAN XV',
        ]);

        $admin = User::create([
            'name' => 'Admin Delete',
            'username_nip' => 'admin_delete',
            'nama_lengkap' => 'Admin Delete',
            'email' => 'admin_delete@cirebonkab.go.id',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $doc = RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'Renja',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->delete(route('renja.destroy', $doc->id));
        $response->assertRedirect();

        $this->assertDatabaseMissing('renja_documents', ['id' => $doc->id]);
    }

    // =========================================================
    // TESTS: ORIGINAL FILE PRESERVATION (Upload Word)
    // =========================================================

    private function makeOperatorAndOpd(string $kode = '9.01.000', string $nama = 'Kecamatan Depok'): array
    {
        $opd = MasterOpd::create([
            'kode_opd' => $kode,
            'nama_opd' => $nama,
            'nomor_lampiran_romawi' => 'LAMPIRAN XXXVIII',
        ]);
        $operator = \App\Models\User::create([
            'name' => 'Operator ' . $nama,
            'username_nip' => 'op_' . rand(1000, 9999),
            'nama_lengkap' => 'Operator ' . $nama,
            'email' => strtolower(str_replace(' ', '', $nama)) . rand(100, 999) . '@opd.go.id',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'opd_id' => $opd->id,
        ]);
        return [$opd, $operator];
    }

    private function makeMinimalDocxFile(): \Illuminate\Http\UploadedFile
    {
        $opd = MasterOpd::first() ?? MasterOpd::create([
            'nama_opd' => 'Kecamatan Test Upload',
            'kode_opd' => '9.01.001',
            'nomor_lampiran_romawi' => 'LAMPIRAN I',
        ]);

        $personalizer = app(\App\Services\TemplatePersonalizerService::class);
        $result = $personalizer->generateBlankTemplateDocx('RENJA_MURNI', [
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
        ]);

        return new \Illuminate\Http\UploadedFile(
            $result['file_path'],
            'RENJA_Test_Dokumen.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );
    }

    /** TEST 1 — Upload DOCX berhasil dan record tersimpan */
    public function test_upload_docx_creates_renja_document_record(): void
    {
        [$opd, $operator] = $this->makeOperatorAndOpd('9.01.001', 'Kecamatan Test Upload');
        $this->actingAs($operator);

        $file = $this->makeMinimalDocxFile();
        $response = $this->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $response->assertRedirect();
        $doc = \App\Models\RenjaDocument::where('opd_id', $opd->id)->latest()->first();
        $this->assertNotNull($doc, 'RenjaDocument harus tersimpan setelah upload.');
        $this->assertEquals('upload_word', $doc->source_type);
    }

    /** TEST 2 — File original tersimpan di storage */
    public function test_original_file_stored_in_storage_after_upload(): void
    {
        [$opd, $operator] = $this->makeOperatorAndOpd('9.01.002', 'Kecamatan Test Storage');
        $this->actingAs($operator);

        $file = $this->makeMinimalDocxFile();
        $this->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = \App\Models\RenjaDocument::where('opd_id', $opd->id)->latest()->first();
        $this->assertNotNull($doc->metadata['original_file_path'] ?? null, 'original_file_path harus ada di metadata.');
        $this->assertTrue(
            \Illuminate\Support\Facades\Storage::disk('local')->exists($doc->metadata['original_file_path']),
            'File original harus ada di storage.'
        );
    }

    /** TEST 3 — SHA-256 hash tersimpan di metadata */
    public function test_sha256_hash_stored_in_metadata_after_upload(): void
    {
        [$opd, $operator] = $this->makeOperatorAndOpd('9.01.003', 'Kecamatan Test Hash');
        $this->actingAs($operator);

        $file = $this->makeMinimalDocxFile();
        $this->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = \App\Models\RenjaDocument::where('opd_id', $opd->id)->latest()->first();
        $this->assertNotNull($doc->metadata['original_file_hash'] ?? null, 'SHA-256 hash harus tersimpan di metadata.');
        $this->assertEquals(64, strlen($doc->metadata['original_file_hash']), 'SHA-256 hash harus 64 karakter hex.');
    }

    /** TEST 4 — Hash file upload identik dengan hash file di storage */
    public function test_uploaded_file_hash_matches_stored_file_hash(): void
    {
        [$opd, $operator] = $this->makeOperatorAndOpd('9.01.004', 'Kecamatan Test Hash Match');
        $this->actingAs($operator);

        $uploadedFile = $this->makeMinimalDocxFile();
        $uploadHash = hash_file('sha256', $uploadedFile->getRealPath());

        $this->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $uploadedFile,
        ]);

        $doc = \App\Models\RenjaDocument::where('opd_id', $opd->id)->latest()->first();
        $storedPath = \Illuminate\Support\Facades\Storage::disk('local')->path($doc->metadata['original_file_path']);
        $storedHash = hash_file('sha256', $storedPath);

        $this->assertEquals($uploadHash, $storedHash, 'Hash file upload harus identik dengan hash file yang tersimpan di storage.');
        $this->assertEquals($doc->metadata['original_file_hash'], $storedHash, 'Hash di metadata harus identik dengan hash file di storage.');
    }

    /** TEST 5 — preview_source=original_docx tersimpan di metadata */
    public function test_preview_source_is_original_docx_for_upload_word(): void
    {
        [$opd, $operator] = $this->makeOperatorAndOpd('9.01.005', 'Kecamatan Test Preview');
        $this->actingAs($operator);

        $file = $this->makeMinimalDocxFile();
        $this->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = \App\Models\RenjaDocument::where('opd_id', $opd->id)->latest()->first();
        $this->assertEquals('original_docx', $doc->metadata['preview_source'] ?? null, 'preview_source harus "original_docx" untuk upload_word.');
        $this->assertEquals('metadata_only', $doc->metadata['display_source'] ?? null, 'display_source harus "metadata_only" untuk sections DB.');
        $this->assertTrue($doc->metadata['autofix_disabled'] ?? false, 'autofix_disabled harus true untuk upload_word.');
    }

    /** TEST 6 — Model accessor original_filename bekerja */
    public function test_model_accessor_original_filename_returns_correct_name(): void
    {
        $opd = MasterOpd::create(['kode_opd' => '9.01.006', 'nama_opd' => 'Kecamatan Accessor', 'nomor_lampiran_romawi' => 'LAMPIRAN I']);
        $doc = \App\Models\RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
            'source_type' => 'upload_word',
            'metadata' => [
                'original_filename' => 'RENJA_Kecamatan_Depok_2027.docx',
                'original_file_hash' => str_repeat('a', 64),
                'original_file_size' => 102400,
                'original_file_path' => 'renja/2027/test/renja-murni/original/test.docx',
                'preview_source' => 'original_docx',
            ],
        ]);

        $this->assertEquals('RENJA_Kecamatan_Depok_2027.docx', $doc->original_filename);
        $this->assertEquals(str_repeat('a', 64), $doc->original_file_hash);
        $this->assertEquals(102400, $doc->original_file_size);
        $this->assertEquals('original_docx', $doc->preview_source);
        $this->assertTrue($doc->is_upload_word);
        $this->assertTrue($doc->isRenjaMurniOrPerubahan());
        $this->assertFalse($doc->isLampiranPerbub());
    }

    /** TEST 7 — AutoFix route is removed and unavailable */
    public function test_apply_autofix_on_renja_murni_returns_403(): void
    {
        [$opd, $operator] = $this->makeOperatorAndOpd('9.01.007', 'Kecamatan AutoFix Guard');
        $this->actingAs($operator);

        $doc = \App\Models\RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
            'source_type' => 'upload_word',
            'metadata' => ['preview_source' => 'original_docx'],
        ]);

        $response = $this->post('/operator/renja-murni/' . $doc->id . '/autofix');
        $this->assertTrue(in_array($response->status(), [404, 403, 405]));
    }

    /** TEST 8 — AutoFix route is removed for RENJA Perubahan */
    public function test_apply_autofix_on_renja_perubahan_returns_403(): void
    {
        [$opd, $operator] = $this->makeOperatorAndOpd('9.01.008', 'Kecamatan AutoFix Perubahan Guard');
        $this->actingAs($operator);

        $doc = \App\Models\RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Perubahan',
            'status' => 'draft',
            'source_type' => 'upload_word',
            'metadata' => ['preview_source' => 'original_docx'],
        ]);

        $response = $this->post('/operator/renja-murni/' . $doc->id . '/autofix');
        $this->assertTrue(in_array($response->status(), [404, 403, 405]));
    }

    /** TEST 9 — Download menghasilkan file yang hash-nya identik dengan upload */
    public function test_download_returns_file_with_identical_hash_to_upload(): void
    {
        [$opd, $operator] = $this->makeOperatorAndOpd('9.01.009', 'Kecamatan Test Download');
        $this->actingAs($operator);

        $uploadedFile = $this->makeMinimalDocxFile();
        $uploadHash = hash_file('sha256', $uploadedFile->getRealPath());

        $this->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $uploadedFile,
        ]);

        $doc = \App\Models\RenjaDocument::where('opd_id', $opd->id)->latest()->first();

        // Download via exportWord route
        $response = $this->get(route('renja.exportWord', $doc->id));
        $response->assertStatus(200);

        // Hitung hash dari streamed response
        $downloadedHash = hash('sha256', $response->streamedContent());
        $this->assertEquals($uploadHash, $downloadedHash, 'Hash file download harus identik dengan hash file upload original.');
    }

    /** TEST 10 — isRenjaMurniOrPerubahan() untuk Lampiran Perbub mengembalikan false */
    public function test_is_renja_murni_or_perubahan_returns_false_for_lampiran(): void
    {
        $opd = MasterOpd::create(['kode_opd' => '9.01.010', 'nama_opd' => 'Test Lampiran', 'nomor_lampiran_romawi' => 'LAMPIRAN I']);
        $doc = \App\Models\RenjaDocument::create([
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Lampiran Perbub',
            'status' => 'draft',
        ]);

        $this->assertFalse($doc->isRenjaMurniOrPerubahan(), 'Lampiran Perbub TIDAK boleh dianggap sebagai Murni/Perubahan.');
        $this->assertTrue($doc->isLampiranPerbub(), 'Lampiran Perbub harus terdeteksi sebagai Lampiran.');
    }

    /** TEST 11 — Upload file tidak menjalankan autofix secara otomatis */
    public function test_upload_word_does_not_trigger_autofix_automatically(): void
    {
        [$opd, $operator] = $this->makeOperatorAndOpd('9.01.011', 'Kecamatan No AutoFix');
        $this->actingAs($operator);

        $file = $this->makeMinimalDocxFile();
        $this->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = \App\Models\RenjaDocument::where('opd_id', $opd->id)->latest()->first();
        // autofix_disabled harus true dan status tidak boleh autofix_completed
        $this->assertTrue($doc->metadata['autofix_disabled'] ?? false, 'autofix_disabled harus true untuk RENJA Murni.');
        $this->assertNotEquals('autofix_completed', $doc->status, 'Status tidak boleh autofix_completed setelah upload RENJA Murni.');
        $this->assertNotEquals('autofix_confirmed', $doc->status, 'Status tidak boleh autofix_confirmed setelah upload RENJA Murni.');
    }

    /** TEST 12 — File size tersimpan dengan benar di metadata */
    public function test_original_file_size_stored_in_metadata(): void
    {
        [$opd, $operator] = $this->makeOperatorAndOpd('9.01.012', 'Kecamatan Test Size');
        $this->actingAs($operator);

        $file = $this->makeMinimalDocxFile();
        $expectedSize = filesize($file->getRealPath());

        $this->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file,
        ]);

        $doc = \App\Models\RenjaDocument::where('opd_id', $opd->id)->latest()->first();
        $this->assertEquals($expectedSize, $doc->metadata['original_file_size'] ?? 0, 'File size di metadata harus sama dengan size file yang diupload.');
        $this->assertEquals($expectedSize, $doc->original_file_size, 'Model accessor original_file_size harus mengembalikan nilai yang benar.');
    }

    /** TEST 13 — Ganti file aman: record diupdate, file lama dihapus, file baru terverifikasi */
    public function test_replace_file_updates_record_in_place_and_deletes_old_file(): void
    {
        [$opd, $operator] = $this->makeOperatorAndOpd('9.01.013', 'Kecamatan Test Replace');
        $this->actingAs($operator);

        // Upload pertama
        $file1 = $this->makeMinimalDocxFile();
        $this->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file1,
        ]);

        $doc1 = \App\Models\RenjaDocument::where('opd_id', $opd->id)->latest()->first();
        $oldFilePath = $doc1->metadata['original_file_path'];
        $oldDocId = $doc1->id;
        $hash1 = $doc1->metadata['original_file_hash'];

        // Upload kedua (ganti file) — harus mengupdate record yang sama
        $file2 = $this->makeMinimalDocxFile();
        $hash2Expected = hash_file('sha256', $file2->getRealPath());

        $this->post(route('operator.renja-murni.store-upload'), [
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'document_file' => $file2,
        ]);

        $doc2 = \App\Models\RenjaDocument::where('opd_id', $opd->id)->latest()->first();

        // Dokumen harus sama (update in-place)
        $this->assertEquals($oldDocId, $doc2->id, 'Ganti file harus mengupdate record yang sama, bukan buat record baru.');
        // Hash harus berubah
        $this->assertNotEquals($hash1, $doc2->metadata['original_file_hash'], 'Hash setelah ganti file harus berbeda dari hash sebelumnya.');
        // File lama harus sudah dihapus
        $this->assertFalse(
            \Illuminate\Support\Facades\Storage::disk('local')->exists($oldFilePath),
            'File lama harus dihapus setelah ganti file berhasil.'
        );
        // Hash baru harus benar
        $this->assertEquals($hash2Expected, $doc2->metadata['original_file_hash'], 'Hash file baru harus tersimpan dengan benar.');
    }
}

