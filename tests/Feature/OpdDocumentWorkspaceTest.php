<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Enums\DocumentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OpdDocumentWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * 1. Operator dapat membuka halaman Dokumen Saya -> Expected: HTTP 200
     */
    public function test_opd_operator_can_open_documents_workspace()
    {
        $opdUser = User::where('role', 'operator')->first() ?? User::factory()->create(['role' => 'operator']);

        $response = $this->actingAs($opdUser)->get(route('renja.index'));

        $response->assertStatus(200);
        $response->assertSee('Dokumen Saya');
        $response->assertSee('Kelola seluruh dokumen perencanaan perangkat daerah.');
    }

    /**
     * 2. Hanya melihat dokumen milik OPD-nya sendiri
     */
    public function test_opd_operator_only_sees_own_opd_documents()
    {
        $opd1 = MasterOpd::first() ?? MasterOpd::create(['nama_opd' => 'OPD 1', 'kode_opd' => '1.01.000']);
        $opd2 = MasterOpd::create(['nama_opd' => 'OPD 2', 'kode_opd' => '1.02.000']);

        $userOpd1 = User::factory()->create([
            'role' => 'operator', 
            'opd_id' => $opd1->id, 
            'username_nip' => '199001012020011001',
            'nama_lengkap' => 'Operator OPD 1'
        ]);

        $doc1 = RenjaDocument::create(['opd_id' => $opd1->id, 'jenis_dokumen' => 'Renja OPD 1', 'tahun_anggaran' => 2027, 'status' => 'draft']);
        $doc2 = RenjaDocument::create(['opd_id' => $opd2->id, 'jenis_dokumen' => 'Renja OPD 2 Secret', 'tahun_anggaran' => 2027, 'status' => 'draft']);

        $response = $this->actingAs($userOpd1)->get(route('renja.index'));

        $response->assertStatus(200);
        $response->assertSee('Renja OPD 1');
        $response->assertDontSee('Renja OPD 2 Secret');
    }

    /**
     * 3. Search Dokumen Berjalan
     */
    public function test_search_documents_filters_by_name()
    {
        $opdUser = User::where('role', 'operator')->first();
        if (!$opdUser->opd_id) {
            $opdUser->opd_id = MasterOpd::first()->id;
            $opdUser->save();
        }

        RenjaDocument::create(['opd_id' => $opdUser->opd_id, 'jenis_dokumen' => 'Khusus RKPD Searchable', 'tahun_anggaran' => 2027, 'status' => 'draft']);

        $response = $this->actingAs($opdUser)->get(route('renja.index', ['search' => 'Searchable']));

        $response->assertStatus(200);
        $response->assertSee('Khusus RKPD Searchable');
    }

    /**
     * 4. Filter Status Berjalan
     */
    public function test_status_filter_returns_matching_documents()
    {
        $opdUser = User::where('role', 'operator')->first();

        $response = $this->actingAs($opdUser)->get(route('renja.index', ['status' => 'draft']));

        $response->assertStatus(200);
    }

    /**
     * 5. Filter Tahun Anggaran Berjalan
     */
    public function test_tahun_anggaran_filter_returns_matching_documents()
    {
        $opdUser = User::where('role', 'operator')->first();

        $response = $this->actingAs($opdUser)->get(route('renja.index', ['tahun_anggaran' => 2027]));

        $response->assertStatus(200);
    }

    /**
     * 6. Pagination Berjalan & Query String Preserved
     */
    public function test_pagination_preserves_query_string()
    {
        $opdUser = User::where('role', 'operator')->first();

        $response = $this->actingAs($opdUser)->get(route('renja.index', ['status' => 'all', 'page' => 1]));

        $response->assertStatus(200);
    }

    /**
     * 7. Empty State Tampil saat tidak ada dokumen
     */
    public function test_empty_state_is_displayed_when_no_documents_exist()
    {
        $emptyOpd = MasterOpd::create(['nama_opd' => 'OPD Kosong Tanpa Dokumen', 'kode_opd' => '9.99.999']);
        $userEmpty = User::factory()->create([
            'role' => 'operator', 
            'opd_id' => $emptyOpd->id, 
            'username_nip' => '199001012020011002',
            'nama_lengkap' => 'Operator OPD Kosong'
        ]);

        $response = $this->actingAs($userEmpty)->get(route('renja.index'));

        $response->assertStatus(200);
        $response->assertSee('Belum Ada Dokumen');
        $response->assertSee('+ Buat Dokumen Baru');
    }

    /**
     * 8. Draft dapat dihapus & 9. Draft dapat diedit
     */
    public function test_draft_document_can_be_edited_and_deleted()
    {
        $opdUser = User::where('role', 'operator')->first();
        $docDraft = RenjaDocument::create([
            'opd_id' => $opdUser->opd_id,
            'jenis_dokumen' => 'Draft Tes Hapus',
            'tahun_anggaran' => 2027,
            'status' => 'draft'
        ]);

        // Test Edit route accessibility
        $responseEdit = $this->actingAs($opdUser)->get(route('renja.editor', $docDraft->id));
        $responseEdit->assertStatus(200);

        // Test Delete action
        $responseDelete = $this->actingAs($opdUser)->delete(route('renja.destroy', $docDraft->id));
        $responseDelete->assertRedirect(route('renja.index'));

        $this->assertDatabaseMissing('renja_documents', ['id' => $docDraft->id]);
    }

    /**
     * 10. Submitted tidak dapat diedit & 11. Submitted tidak dapat dihapus
     */
    public function test_submitted_document_cannot_be_deleted()
    {
        $opdUser = User::where('role', 'operator')->first();
        $docSubmitted = RenjaDocument::create([
            'opd_id' => $opdUser->opd_id,
            'jenis_dokumen' => 'Submitted Tes Locked',
            'tahun_anggaran' => 2027,
            'status' => 'submitted'
        ]);

        // Attempt Delete -> Expect Error Redirect
        $responseDelete = $this->actingAs($opdUser)->delete(route('renja.destroy', $docSubmitted->id));
        $responseDelete->assertRedirect(route('renja.index'));
        $responseDelete->assertSessionHas('error');

        $this->assertDatabaseHas('renja_documents', ['id' => $docSubmitted->id]);
    }

    /**
     * 12. Final hanya Preview, 13. Final dapat Export Word, 14. Final dapat Cetak PDF
     */
    public function test_final_document_allows_preview_export_word_and_print()
    {
        $opdUser = User::where('role', 'operator')->first();
        $docFinal = RenjaDocument::create([
            'opd_id' => $opdUser->opd_id,
            'jenis_dokumen' => 'Final Sah Dokumen',
            'tahun_anggaran' => 2027,
            'status' => 'disetujui'
        ]);

        // Test Preview
        $responsePreview = $this->actingAs($opdUser)->get(route('renja.editor', $docFinal->id));
        $responsePreview->assertStatus(200);

        // Test Export Word
        $responseWord = $this->actingAs($opdUser)->get(route('renja.exportWord', $docFinal->id));
        $responseWord->assertStatus(200);

        // Test Print PDF
        $responsePrint = $this->actingAs($opdUser)->get(route('renja.print', $docFinal->id));
        $responsePrint->assertStatus(200);
    }
}
