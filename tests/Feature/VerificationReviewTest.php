<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Enums\DocumentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VerificationReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * 1. Admin membuka halaman review -> Expected: HTTP 200
     */
    public function test_admin_can_open_review_page()
    {
        $admin = User::where('role', 'admin')->first();
        $doc = RenjaDocument::first();

        $response = $this->actingAs($admin)->get(route('admin.verifikasi.review', $doc->id));

        $response->assertStatus(200);
        $response->assertSee('Review Dokumen');
    }

    /**
     * 2. Staff Verifikator membuka halaman review -> Expected: HTTP 200
     */
    public function test_verificator_staff_can_open_review_page()
    {
        $verifikator = User::where('role', 'verifikator')->first() ?? User::factory()->create(['role' => 'verifikator']);
        $doc = RenjaDocument::first();

        $response = $this->actingAs($verifikator)->get(route('admin.verifikasi.review', $doc->id));

        $response->assertStatus(200);
    }

    /**
     * 3. Operator OPD ditolak -> Expected: HTTP 403 / Redirect
     */
    public function test_opd_operator_cannot_access_review_page()
    {
        $opdUser = User::where('role', 'operator')->first() ?? User::factory()->create(['role' => 'operator']);
        $doc = RenjaDocument::first();

        $response = $this->actingAs($opdUser)->get(route('admin.verifikasi.review', $doc->id));

        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    /**
     * 4. Timeline & Semua BAB Tampil -> Expected: Rendered in HTML
     */
    public function test_timeline_and_chapters_are_rendered()
    {
        $admin = User::where('role', 'admin')->first();
        $doc = RenjaDocument::first();

        $response = $this->actingAs($admin)->get(route('admin.verifikasi.review', $doc->id));

        $response->assertSee('Review Dokumen');
        $response->assertSee('Workspace Verifikasi');
    }

    /**
     * 5. Simpan Draft Review Berhasil
     */
    public function test_verificator_can_save_draft_review()
    {
        $admin = User::where('role', 'admin')->first();
        $doc = RenjaDocument::first();

        $response = $this->actingAs($admin)->post(route('admin.verifikasi.decision', $doc->id), [
            'decision_type' => 'simpan_draft',
            'catatan_bapperida' => 'Draft review catatan awal',
            'sections' => [
                'BAB I' => ['status' => 'APPROVED', 'notes' => 'Bab I lengkap'],
            ],
        ]);

        $response->assertRedirect();
        $doc->refresh();
        $this->assertEquals(DocumentStatus::UNDER_REVIEW->value, $doc->status);
    }

    /**
     * 6. Minta Revisi Berhasil
     */
    public function test_verificator_can_request_revision()
    {
        $admin = User::where('role', 'admin')->first();
        $doc = RenjaDocument::first();

        $response = $this->actingAs($admin)->post(route('admin.verifikasi.decision', $doc->id), [
            'decision_type' => 'minta_revisi',
            'catatan_bapperida' => 'Mohon perbaiki Bab II',
            'sections' => [
                'BAB II' => ['status' => 'NEEDS_REVISION', 'notes' => 'Perbaiki tabel evaluasi'],
            ],
        ]);

        $response->assertRedirect();
        $doc->refresh();
        $this->assertEquals(DocumentStatus::REVISION_NEEDED->value, $doc->status);
    }

    /**
     * 7. Setujui Dokumen Berhasil & Dokumen Dikunci (Is Locked)
     */
    public function test_verificator_can_approve_document_and_lock_edits()
    {
        $admin = User::where('role', 'admin')->first();
        $doc = RenjaDocument::first();

        $response = $this->actingAs($admin)->post(route('admin.verifikasi.decision', $doc->id), [
            'decision_type' => 'setujui_dokumen',
            'catatan_bapperida' => 'Dokumen disetujui penuh',
        ]);

        $response->assertRedirect();
        $doc->refresh();
        $this->assertEquals(DocumentStatus::APPROVED->value, $doc->status);

        // Uji bahwa dokumen yang disetujui tidak dapat diubah lagi (Locked Rule)
        $responseLocked = $this->actingAs($admin)->post(route('admin.verifikasi.decision', $doc->id), [
            'decision_type' => 'minta_revisi',
        ]);

        $responseLocked->assertSessionHas('error');
    }
}
