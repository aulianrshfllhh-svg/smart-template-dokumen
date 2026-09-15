<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RenjaTaSwitchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    private function createOperator(): User
    {
        $uniqueId = rand(1000, 9999);
        $opd = MasterOpd::create([
            'nama_opd' => 'Kecamatan Depok ' . $uniqueId,
            'kode_opd' => '7.01.' . $uniqueId,
            'nomor_lampiran_romawi' => 'LAMPIRAN I',
        ]);

        return User::factory()->create([
            'role' => 'operator',
            'opd_id' => $opd->id,
            'username_nip' => '19900101' . $uniqueId . '1001',
            'nama_lengkap' => 'Operator Depok',
        ]);
    }

    /**
     * Test 1: Ganti Tahun Anggaran via route set-ta mengalihkan URL dengan query parameter TA yang diperbarui.
     */
    public function test_switching_ta_updates_session_and_referer_query_params(): void
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)
            ->from(route('renja.workspace', ['tahun_anggaran' => 2027]))
            ->post(route('set-ta'), [
                'tahun_anggaran' => 2028,
            ]);

        $response->assertRedirect(route('renja.workspace', ['tahun_anggaran' => 2028]));
        $this->assertEquals(2028, session('active_ta'));
    }

    /**
     * Test 2: Ketika membuka Workspace untuk TA yang belum memiliki dokumen, halaman menampilkan status 'Belum Ada Dokumen'.
     */
    public function test_empty_ta_workspace_displays_clear_empty_state(): void
    {
        $operator = $this->createOperator();

        // TA 2028 belum ada dokumen sama sekali
        $response = $this->actingAs($operator)->get(route('renja.workspace', ['tahun_anggaran' => 2028]));

        $response->assertStatus(200);
        $response->assertSee('Workspace RENJA');
        $response->assertSee('2028–2029');
        $response->assertSee('Belum Dibuat');
        $response->assertSee('Buat / Upload RENJA Murni');
    }

    /**
     * Test 3: TA dengan dokumen disetujui (2027) dan TA kosong (2029) dapat diakses bergantian tanpa tertahan.
     */
    public function test_can_switch_between_populated_and_empty_ta(): void
    {
        $operator = $this->createOperator();

        // Buat dokumen di TA 2028 (Murni untuk periode 2027-2028)
        RenjaDocument::create([
            'opd_id' => $operator->opd_id,
            'tahun_anggaran' => 2028,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'disetujui',
        ]);

        // 1. Buka TA 2027 -> Ada dokumen & banner fix
        $res2027 = $this->actingAs($operator)->get(route('renja.workspace', ['tahun_anggaran' => 2027]));
        $res2027->assertStatus(200);
        $res2027->assertSee('DOKUMEN RESMI (FIX)');
        $res2027->assertSee('2027–2028');

        // 2. Buka TA 2029 -> Kosong & tampil 'Belum Dibuat'
        $res2029 = $this->actingAs($operator)->get(route('renja.workspace', ['tahun_anggaran' => 2029]));
        $res2029->assertStatus(200);
        $res2029->assertSee('2029–2030');
        $res2029->assertSee('Belum Dibuat');
        $res2029->assertDontSee('DOKUMEN RESMI (FIX)');
    }
}
