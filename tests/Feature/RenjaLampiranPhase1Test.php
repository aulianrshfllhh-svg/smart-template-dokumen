<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\User;
use App\Services\DocumentTemplateService;
use App\Services\RenjaAutoFixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RenjaLampiranPhase1Test extends TestCase
{
    use RefreshDatabase;

    protected User $operatorUser;
    protected MasterOpd $opdDepok;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default templates
        $templateService = app(DocumentTemplateService::class);
        $templateService->ensureStandardTemplatesSeeded();

        // Create OPD Kecamatan Depok (Lampiran XXXVIII)
        $this->opdDepok = MasterOpd::create([
            'kode_opd' => '7.01.01',
            'nama_opd' => 'Kecamatan Depok',
            'lampiran_number' => 38,
            'nomor_lampiran_romawi' => 'Lampiran XXXVIII',
        ]);

        // Create Operator User
        $this->operatorUser = User::factory()->create([
            'username_nip' => '199001012020011001',
            'opd_id' => $this->opdDepok->id,
            'role' => 'operator',
        ]);
    }

    /** @test */
    public function it_can_render_renja_lampiran_dedicated_workspace()
    {
        $response = $this->actingAs($this->operatorUser)
            ->get(route('renja.lampiran.index', ['tahun_anggaran' => 2027]));

        $response->assertStatus(200);
        $response->assertSee('RENJA Lampiran (Perbup & Kepbup)', false);
        $response->assertSee('RENJA Lampiran Murni');
        $response->assertSee('RENJA Lampiran Perubahan');
        $response->assertSee('LAMPIRAN XXXVIII');
    }

    /** @test */
    public function it_automatically_assigns_roman_attachment_number_from_opd()
    {
        $romawiHeader = RenjaAutoFixService::getRomanHeaderForOpd($this->opdDepok);
        $this->assertEquals('LAMPIRAN XXXVIII', $romawiHeader);
    }

    /** @test */
    public function it_can_create_renja_lampiran_murni_separately()
    {
        // Setup parent RENJA Murni
        $parentMurni = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'year' => 2027,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->operatorUser)
            ->post(route('renja.lampiran.storeMurni'), [
                'tahun_anggaran' => 2027,
            ]);

        $response->assertRedirect(route('renja.lampiran.index', ['tahun_anggaran' => 2027]));
        $response->assertSessionHas('success');

        $tmplMurni = DocumentTemplate::where('code', 'RENJA_LAMPIRAN_MURNI')->first();

        $this->assertDatabaseHas('renja_documents', [
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Lampiran Murni',
            'template_id' => $tmplMurni->id,
        ]);
    }

    /** @test */
    public function it_can_create_renja_lampiran_perubahan_separately()
    {
        // Setup parent RENJA Perubahan
        $parentPerubahan = RenjaDocument::create([
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'year' => 2027,
            'jenis_dokumen' => 'RENJA Perubahan',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->operatorUser)
            ->post(route('renja.lampiran.storePerubahan'), [
                'tahun_anggaran' => 2027,
            ]);

        $response->assertRedirect(route('renja.lampiran.index', ['tahun_anggaran' => 2027]));
        $response->assertSessionHas('success');

        $tmplPerubahan = DocumentTemplate::where('code', 'RENJA_LAMPIRAN_PERUBAHAN')->first();

        $this->assertDatabaseHas('renja_documents', [
            'opd_id' => $this->opdDepok->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'RENJA Lampiran Perubahan',
            'template_id' => $tmplPerubahan->id,
        ]);
    }

    /** @test */
    public function it_can_upload_original_docx_for_renja_lampiran_murni_and_perubahan()
    {
        Storage::fake('private');

        $fileMurni = UploadedFile::fake()->create('Lampiran_Perbup_Depok.docx', 500, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $responseMurni = $this->actingAs($this->operatorUser)
            ->post(route('renja.lampiran.uploadMurni'), [
                'tahun_anggaran' => 2027,
                'document_file' => $fileMurni,
            ]);

        $responseMurni->assertRedirect(route('renja.lampiran.index', ['tahun_anggaran' => 2026]));
        $responseMurni->assertSessionHas('success');

        $docMurni = RenjaDocument::where('jenis_dokumen', 'RENJA Lampiran Murni')->first();
        $this->assertNotNull($docMurni);
        $this->assertEquals('upload_word', $docMurni->source_type);
        $this->assertNotNull($docMurni->metadata['stored_filepath']);
        Storage::disk('private')->assertExists($docMurni->metadata['stored_filepath']);

        $filePerubahan = UploadedFile::fake()->create('Lampiran_Kepbup_Depok.docx', 600, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $responsePerubahan = $this->actingAs($this->operatorUser)
            ->post(route('renja.lampiran.uploadPerubahan'), [
                'tahun_anggaran' => 2027,
                'document_file' => $filePerubahan,
            ]);

        $responsePerubahan->assertRedirect(route('renja.lampiran.index', ['tahun_anggaran' => 2027]));
        $responsePerubahan->assertSessionHas('success');

        $docPerubahan = RenjaDocument::where('jenis_dokumen', 'RENJA Lampiran Perubahan')->first();
        $this->assertNotNull($docPerubahan);
        $this->assertNotEquals($docMurni->id, $docPerubahan->id);
        $this->assertEquals('upload_word', $docPerubahan->source_type);
        $this->assertNotNull($docPerubahan->metadata['stored_filepath']);
        Storage::disk('private')->assertExists($docPerubahan->metadata['stored_filepath']);
    }
}
