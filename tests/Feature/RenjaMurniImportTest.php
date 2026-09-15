<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Models\DocumentTemplate;
use App\Services\RenjaMurniDocxService;
use App\Services\DocumentTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use DOMDocument;
use DOMXPath;

class TestableRenjaMurniDocxService extends RenjaMurniDocxService
{
    public ?string $mockXml = null;

    public function parseDocxStructure(string $filePath): array
    {
        if ($this->mockXml !== null) {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadXML($this->mockXml, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
            libxml_clear_errors();

            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

            $bodyElements = $xpath->query('//w:body/*[self::w:p or self::w:tbl]');
            $elementsCount = $bodyElements->length;

            $currentSectionKey = 'COVER';
            $currentSectionTitle = 'Cover Dokumen';
            $currentSectionType = 'cover';
            $currentBabCode = 'COVER';
            $currentSubBabCode = null;
            $currentContent = '';
            $isCurrentLandscape = false;

            $accumulated = [];

            for ($i = 0; $i < $elementsCount; $i++) {
                $element = $bodyElements->item($i);

                if ($element->nodeName === 'w:p') {
                    $parsed = $this->parseParagraphNode($element, $xpath);
                    $cleanPText = $parsed['text'];
                    $pHtml = $parsed['html'];

                    if (empty($cleanPText)) {
                        continue;
                    }

                    if (preg_match('/^BAB\s+([IVXLCDM\d]+)(?:\s*[:\-\.]?\s*(.*))?$/i', $cleanPText, $babMatch)) {
                        $babNum = strtoupper($babMatch[1]);
                        $babTitle = !empty($babMatch[2]) ? trim($babMatch[2]) : '';

                        if (empty($babTitle) && ($i + 1 < $elementsCount)) {
                            $nextElement = $bodyElements->item($i + 1);
                            if ($nextElement->nodeName === 'w:p') {
                                $nextParsed = $this->parseParagraphNode($nextElement, $xpath);
                                $nextText = $nextParsed['text'];
                                if (!empty($nextText) && !preg_match('/^BAB\s+/i', $nextText) && !preg_match('/^\d+\.\d+/', $nextText) && strlen($nextText) < 100) {
                                    $babTitle = $nextText;
                                    $i++;
                                }
                            }
                        }

                        if (empty($babTitle)) {
                            $babTitle = 'BAB ' . $babNum;
                        }

                        if (!empty($currentContent)) {
                            $accumulated[] = [
                                'key' => $currentSectionKey,
                                'title' => $currentSectionTitle,
                                'type' => $currentSectionType,
                                'bab_code' => $currentBabCode,
                                'sub_bab_code' => $currentSubBabCode,
                                'content' => $currentContent,
                                'is_landscape' => $isCurrentLandscape,
                            ];
                            $currentContent = '';
                            $isCurrentLandscape = false;
                        }

                        $currentBabCode = 'BAB ' . $babNum;
                        $currentSectionKey = $currentBabCode;
                        $currentSectionTitle = $babTitle;
                        $currentSectionType = 'chapter';
                        $currentSubBabCode = null;
                        continue;
                    }

                    if (preg_match('/^(\d+\.\d+(?:\.\d+)*)[.\s]+(.*)$/i', $cleanPText, $subMatch)) {
                        $subCode = $subMatch[1];
                        $subTitle = trim($subMatch[2]);

                        if (!empty($currentContent)) {
                            $accumulated[] = [
                                'key' => $currentSectionKey,
                                'title' => $currentSectionTitle,
                                'type' => $currentSectionType,
                                'bab_code' => $currentBabCode,
                                'sub_bab_code' => $currentSubBabCode,
                                'content' => $currentContent,
                                'is_landscape' => $isCurrentLandscape,
                            ];
                            $currentContent = '';
                            $isCurrentLandscape = false;
                        }

                        $currentSectionKey = $subCode;
                        $currentSectionTitle = $subTitle;
                        $currentSectionType = 'subchapter';
                        $currentSubBabCode = $subCode;
                        continue;
                    }

                    if (preg_match('/^(KATA\s+PENGANTAR|LEMBAR\s+PENGESAHAN|DAFTAR\s+ISI|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|LAMPIRAN)/i', $cleanPText, $frontMatch)) {
                        $frontTitle = strtoupper($frontMatch[1]);
                        if (!empty($currentContent)) {
                            $accumulated[] = [
                                'key' => $currentSectionKey,
                                'title' => $currentSectionTitle,
                                'type' => $currentSectionType,
                                'bab_code' => $currentBabCode,
                                'sub_bab_code' => $currentSubBabCode,
                                'content' => $currentContent,
                                'is_landscape' => $isCurrentLandscape,
                            ];
                            $currentContent = '';
                            $isCurrentLandscape = false;
                        }

                        $currentSectionKey = str_replace(' ', '_', $frontTitle);
                        $currentSectionTitle = ucwords(strtolower($frontTitle));
                        $currentSectionType = str_contains($frontTitle, 'LAMPIRAN') ? 'appendix' : (str_contains($frontTitle, 'DAFTAR') ? 'table_of_contents' : 'preface');
                        $currentBabCode = $currentSectionKey;
                        $currentSubBabCode = null;
                        continue;
                    }

                    $currentContent .= $pHtml;

                } elseif ($element->nodeName === 'w:tbl') {
                    $tableResult = $this->convertWordTableToHtml($element, $xpath);
                    $currentContent .= $tableResult['html'];
                    if ($tableResult['is_wide_table']) {
                        $isCurrentLandscape = true;
                    }
                }
            }

            if (!empty($currentContent) || !empty($currentSectionKey)) {
                $accumulated[] = [
                    'key' => $currentSectionKey,
                    'title' => $currentSectionTitle,
                    'type' => $currentSectionType,
                    'bab_code' => $currentBabCode,
                    'sub_bab_code' => $currentSubBabCode,
                    'content' => $currentContent,
                    'is_landscape' => $isCurrentLandscape,
                ];
            }

            return [
                'sections' => $accumulated,
                'raw_text' => '',
            ];
        }

        return parent::parseDocxStructure($filePath);
    }
}

class RenjaMurniImportTest extends TestCase
{
    use RefreshDatabase;

    protected TestableRenjaMurniDocxService $docxService;
    protected DocumentTemplateService $templateService;
    protected MasterOpd $opd;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateService = $this->app->make(DocumentTemplateService::class);
        $this->templateService->ensureStandardTemplatesSeeded();
        
        $this->docxService = new TestableRenjaMurniDocxService($this->templateService);
        
        $this->opd = MasterOpd::create([
            'nama_opd' => 'Dinas Pendidikan',
            'kode_opd' => '1.01.000',
        ]);
    }

    /**
     * Test 1 — Dokumen sudah sesuai (No false validation errors, Content preserved)
     */
    public function test_docx_murni_correct_document()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
            <w:body>
                <w:p><w:r><w:t>BAB I</w:t></w:r></w:p>
                <w:p><w:r><w:t>PENDAHULUAN</w:t></w:r></w:p>
                <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
                <w:p><w:r><w:t>Ini adalah paragraf narasi isi latar belakang yang bersih.</w:t></w:r></w:p>
                <w:p><w:r><w:t>1.2 Landasan Hukum</w:t></w:r></w:p>
                <w:p><w:r><w:t>Uraian landasan hukum penyusunan.</w:t></w:r></w:p>
            </w:body>
        </w:document>';

        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027);

        $this->assertNotNull($document);
        $this->assertEquals('RENJA Murni', $document->jenis_dokumen);

        $sections = $document->sections;
        
        $bab1 = $sections->where('sub_bab_code', 'BAB I')->first();
        $this->assertNotNull($bab1);

        $sub11 = $sections->where('sub_bab_code', '1.1')->first();
        $this->assertNotNull($sub11);
        $this->assertStringContainsString('Ini adalah paragraf narasi', $sub11->content);

        // Check validation
        $validation = $this->docxService->validateDocument($document);
        $boldCheck = collect($validation['format'])->where('item', 'Ketentuan Huruf Bold')->first();
        $this->assertNull($boldCheck);
    }

    /**
     * Test 2 — Margin salah (Margin detected, AutoFix fixes it, Content unchanged)
     */
    public function test_docx_murni_margin_issues_and_autofix()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
            <w:body>
                <w:p><w:r><w:t>BAB I</w:t></w:r></w:p>
                <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
                <w:p><w:r><w:t>Konten paragraf.</w:t></w:r></w:p>
            </w:body>
        </w:document>';

        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027);

        $section = $document->sections()->first();
        $meta = $section->metadata;
        $meta['margin_top_cm'] = 3.0;
        $section->metadata = $meta;
        $section->save();

        // Run auto fix
        $result = $this->docxService->autoFixDocument($document);
        $this->assertTrue($result['success']);
        
        $freshSection = $section->fresh();
        $this->assertEquals(2.0, (float)$freshSection->metadata['margin_top_cm']);
    }

    /**
     * Test 3 — Subbab memiliki isi tetapi style berbeda (Section tetap dianggap memiliki isi)
     */
    public function test_docx_murni_subchapter_different_style_has_content()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
            <w:body>
                <w:p><w:r><w:t>BAB I</w:t></w:r></w:p>
                <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
                <w:p>
                    <w:r>
                        <w:rPr>
                            <w:i/>
                        </w:rPr>
                        <w:t>Paragraf dengan style miring (italic) tapi bermakna.</w:t>
                    </w:r>
                </w:p>
            </w:body>
        </w:document>';

        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027);

        $sec = $document->sections->where('sub_bab_code', '1.1')->first();
        $this->assertNotNull($sec);
        $this->assertTrue($sec->is_completed);
    }

    /**
     * Test 4 — Subbab benar-benar kosong (Section dianggap belum diisi)
     */
    public function test_docx_murni_subchapter_empty_is_uncompleted()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
            <w:body>
                <w:p><w:r><w:t>BAB I</w:t></w:r></w:p>
                <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
                <w:p><w:r><w:t>1.2 Landasan Hukum</w:t></w:r></w:p>
            </w:body>
        </w:document>';

        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027);

        $sec = $document->sections->where('sub_bab_code', '1.1')->first();
        $this->assertNotNull($sec);
        $this->assertFalse($sec->is_completed);
    }

    /**
     * Test 5 — Tabel memiliki isi tetapi tidak memiliki caption (Caption issue terdeteksi)
     */
    public function test_docx_murni_table_without_caption_triggers_warning()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
            <w:body>
                <w:p><w:r><w:t>BAB I</w:t></w:r></w:p>
                <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
                <w:tbl>
                    <w:tr>
                        <w:tc><w:p><w:r><w:t>Kolom A</w:t></w:r></w:p></w:tc>
                    </w:tr>
                </w:tbl>
            </w:body>
        </w:document>';

        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027);

        $validation = $this->docxService->validateDocument($document);
        $captionCheck = collect($validation['tables'])->where('item', 'Caption Tabel & Gambar')->first();
        
        $this->assertNotNull($captionCheck);
        $this->assertEquals('warning', $captionCheck['status']);
        $this->assertStringContainsString('belum memiliki caption', $captionCheck['message']);
    }

    /**
     * Test 6 — Tidak ada tabel (Tidak ada tabel ditemukan)
     */
    public function test_docx_murni_no_tables_found()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
            <w:body>
                <w:p><w:r><w:t>BAB I</w:t></w:r></w:p>
                <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
                <w:p><w:r><w:t>Tidak ada tabel sama sekali di sini.</w:t></w:r></w:p>
            </w:body>
        </w:document>';

        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027);

        $validation = $this->docxService->validateDocument($document);
        $captionCheck = collect($validation['tables'])->where('item', 'Caption Tabel & Gambar')->first();
        
        $this->assertNotNull($captionCheck);
        $this->assertEquals('valid', $captionCheck['status']);
        $this->assertEquals('Tidak ditemukan tabel atau gambar pada dokumen.', $captionCheck['message']);
    }

    /**
     * Test 7 — RENJA Murni (Tidak memuat Lampiran Perbup)
     */
    public function test_docx_murni_does_not_mix_with_lampiran_perbup()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
            <w:body>
                <w:p><w:r><w:t>BAB I</w:t></w:r></w:p>
                <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
            </w:body>
        </w:document>';

        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027);

        $sections = $document->sections;
        $this->assertCount(26, $sections);
        
        $templateCodes = $sections->pluck('sub_bab_code')->toArray();
        $this->assertNotContains('LAMPIRAN XXXVIII', $templateCodes);
    }

    /**
     * Test: RENJA Murni dengan bold pada narasi tidak menghasilkan validation warning.
     */
    public function test_docx_murni_with_bold_narrative_has_no_validation_warning()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
            <w:body>
                <w:p><w:r><w:t>BAB I</w:t></w:r></w:p>
                <w:p><w:r><w:t>1.1 Latar Belakang</w:t></w:r></w:p>
                <w:p>
                    <w:r>
                        <w:rPr>
                            <w:b/>
                        </w:rPr>
                        <w:t>Teks narasi tebal di sini.</w:t>
                    </w:r>
                </w:p>
            </w:body>
        </w:document>';

        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027);

        $validation = $this->docxService->validateDocument($document);
        $boldCheck = collect($validation['format'])->where('item', 'Ketentuan Huruf Bold')->first();
        
        $this->assertNull($boldCheck);
    }

    /**
     * TEST 1: Upload RENJA Murni -> jenis_dokumen = RENJA Murni
     */
    public function test_mandatory_upload_renja_murni_type()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>BAB I</w:t></w:r></w:p></w:body></w:document>';
        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027, 'RENJA Murni');
        $this->assertEquals('RENJA Murni', $document->jenis_dokumen);
    }

    /**
     * TEST 2: Upload RENJA Murni -> template yang digunakan = Master Template RENJA Murni
     */
    public function test_mandatory_upload_renja_murni_template()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>BAB I</w:t></w:r></w:p></w:body></w:document>';
        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027, 'RENJA Murni');
        $this->assertNotNull($document->template);
        $this->assertEquals('RENJA_MURNI', $document->template->code);
    }

    /**
     * TEST 3: Preview RENJA Murni -> tidak terdapat "LAMPIRAN XXXVIII"
     */
    public function test_mandatory_preview_renja_murni_has_no_lampiran_number()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>BAB I</w:t></w:r></w:p></w:body></w:document>';
        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027, 'RENJA Murni');
        
        $view = $this->view('renja.print', [
            'document' => $document,
            'groupedSections' => $document->sections->groupBy('bab_code')
        ]);
        
        $view->assertDontSee('LAMPIRAN');
    }

    /**
     * TEST 4: Preview RENJA Murni -> tidak terdapat "PERATURAN BUPATI CIREBON" sebagai header Lampiran
     */
    public function test_mandatory_preview_renja_murni_has_no_perbup_header()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>BAB I</w:t></w:r></w:p></w:body></w:document>';
        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027, 'RENJA Murni');
        
        $view = $this->view('renja.print', [
            'document' => $document,
            'groupedSections' => $document->sections->groupBy('bab_code')
        ]);
        
        $view->assertDontSee('PERATURAN BUPATI CIREBON');
    }

    /**
     * TEST 5: Preview RENJA Murni -> tidak terdapat metadata "NOMOR LAMPIRAN"
     */
    public function test_mandatory_preview_renja_murni_has_no_nomor_lampiran_metadata()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>BAB I</w:t></w:r></w:p></w:body></w:document>';
        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027, 'RENJA Murni');
        
        $view = $this->view('renja.print', [
            'document' => $document,
            'groupedSections' => $document->sections->groupBy('bab_code')
        ]);
        
        $view->assertDontSee('Nomor Lampiran');
    }



    /**
     * TEST: exportWord returns original uploaded file when source_type is upload_word
     */
    public function test_original_docx_download_returns_identical_file()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>BAB I</w:t></w:r></w:p></w:body></w:document>';
        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027, 'RENJA Murni');
        
        // Mock a physical file using local storage disk
        $document->source_type = 'upload_word';
        $meta = $document->metadata;
        \Illuminate\Support\Facades\Storage::disk('local')->put('test_docx.docx', 'fake word binary contents');
        $meta['original_file_path'] = 'test_docx.docx';
        $meta['original_filename'] = 'my_uploaded_doc.docx';
        $document->metadata = $meta;
        $document->save();

        $response = $this->actingAs(\App\Models\User::factory()->create([
            'role' => 'operator',
            'opd_id' => $this->opd->id,
            'username_nip' => '123456789',
            'nama_lengkap' => 'Operator Depok'
        ]))->get(route('renja.exportWord', $document->id));

        $response->assertStatus(200);
        $this->assertEquals('fake word binary contents', $response->streamedContent());
        
        \Illuminate\Support\Facades\Storage::disk('local')->delete('test_docx.docx');
    }

    /**
     * TEST: print preview shows docx-preview-container when source_type is upload_word
     */
    public function test_original_docx_print_view_renders_preview_container()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>BAB I</w:t></w:r></w:p></w:body></w:document>';
        $this->docxService->mockXml = $xml;
        $document = $this->docxService->importDocx('dummy_path.docx', $this->opd->id, 2027, 'RENJA Murni');
        $document->source_type = 'upload_word';
        $document->save();

        $view = $this->view('renja.print', [
            'document' => $document,
            'groupedSections' => $document->sections->groupBy('bab_code')
        ]);

        $view->assertSee('id="docx-preview-container"', false);
        $view->assertSee('ORIGINAL DOCUMENT');
    }
}
