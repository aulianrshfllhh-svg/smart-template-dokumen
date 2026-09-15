<?php

namespace Tests\Feature;

use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoFixRemovedTest extends TestCase
{
    use RefreshDatabase;

    protected User $operatorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $opd = MasterOpd::create([
            'kode_opd' => '9.01.038',
            'nama_opd' => 'Kecamatan Depok',
        ]);

        $this->operatorUser = User::factory()->create([
            'username_nip' => '199001012020011001',
            'role' => 'operator',
            'opd_id' => $opd->id,
        ]);
    }

    /**
     * TEST: Memastikan route AutoFix tidak lagi aktif dan mengembalikan 404 Not Found
     */
    public function test_autofix_routes_are_removed_and_return_404(): void
    {
        // 1. GET /renja-autofix/upload harus 404
        $res1 = $this->actingAs($this->operatorUser)->get('/renja-autofix/upload');
        $res1->assertStatus(404);

        // 2. POST /renja-autofix/process harus 404
        $res2 = $this->actingAs($this->operatorUser)->post('/renja-autofix/process');
        $res2->assertStatus(404);

        // 3. POST /renja-documents/1/autofix-direct harus 404
        $res3 = $this->actingAs($this->operatorUser)->post('/renja-documents/1/autofix-direct');
        $res3->assertStatus(404);
    }

    /**
     * TEST: Memastikan halaman Lampiran & Preview tidak memuat tombol atau teks AutoFix
     */
    public function test_ui_does_not_contain_autofix_buttons_or_labels(): void
    {
        $doc = RenjaDocument::create([
            'opd_id' => $this->operatorUser->opd_id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Lampiran Murni',
            'status' => 'draft',
            'metadata' => [
                'stored_filepath' => 'renja/2027/depok/test.docx',
            ]
        ]);

        // 1. Halaman Index Lampiran
        $indexRes = $this->actingAs($this->operatorUser)->get(route('renja.lampiran.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertDontSee('[AutoFix]');
        $indexRes->assertDontSee('AutoFix Format');

        // 2. Halaman Preview Dokumen
        $previewRes = $this->actingAs($this->operatorUser)->get(route('renja.preview', $doc->id));
        $previewRes->assertStatus(200);
        $previewRes->assertDontSee('[AutoFix]');
        $previewRes->assertDontSee('Jalankan AutoFix Sekarang');
        $previewRes->assertDontSee('AutoFix-v1');
    }
}
