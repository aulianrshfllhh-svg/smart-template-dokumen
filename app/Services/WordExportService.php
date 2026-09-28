<?php

namespace App\Services;

use App\Models\RenjaDocument;
use App\Models\RenjaSectionCaption;
use Illuminate\Support\Collection;

/**
 * WordExportService — Konversi dokumen Renja dari renja_sections ke file .docx
 * menggunakan phpoffice/phpword.
 *
 * Strategi:
 * 1. Baca seluruh sections dari database (bukan hardcoded).
 * 2. Konversi HTML konten setiap section ke PhpWord paragraph/table.
 * 3. Setiap BAB dimulai di halaman baru (addSection baru).
 * 4. Tabel HTML (termasuk colspan/rowspan dari paste Word) dikonversi ke PhpWord Table.
 * 5. Header berisi nama OPD, footer berisi nomor halaman.
 * 6. Daftar Isi dibuat sebagai TOC field (update di MS Word dengan F9).
 */
class WordExportService
{
    // Konversi cm ke twip (1cm = 567 twip)
    private const CM_TO_TWIP = 567;

    /**
     * Generate file .docx dari dokumen Renja dan kembalikan path file sementara.
     */
    public function generateRenjaDocx(RenjaDocument $document): string
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Bookman Old Style');
        $phpWord->setDefaultFontSize(12);

        // Setting halaman F4 Portrait (215mm x 330mm), margin 20mm
        $f4Portrait = $this->getF4PortraitSettings();
        $f4Landscape = $this->getF4LandscapeSettings();

        // Style teks standar
        $phpWord->addParagraphStyle('Normal', [
            'spaceAfter' => 120,
            'lineHeight' => 1.5,
            'alignment' => 'both', // justify
        ]);
        $phpWord->addParagraphStyle('BabTitle', [
            'alignment' => 'center',
            'spaceAfter' => 280,
            'spaceBefore' => 280,
        ]);
        $phpWord->addParagraphStyle('SubBabTitle', [
            'spaceAfter' => 140,
            'spaceBefore' => 140,
            'indentation' => ['left' => 0],
        ]);

        // === COVER (jika ada) ===
        $coverData = $document->cover_data ?? [];
        if (!empty($coverData)) {
            $sectionCover = $phpWord->addSection($f4Portrait);
            $this->renderCover($sectionCover, $document, $coverData);
        }

        // === FRONT MATTER (Kata Pengantar, Daftar Isi, Daftar Tabel, Daftar Gambar) ===
        $frontSections = $document->sections()
            ->whereIn('section_type', ['preface', 'table_of_contents', 'list_of_tables', 'list_of_figures', 'list_of_appendices'])
            ->orderBy('order_index')
            ->get();

        foreach ($frontSections as $frontSec) {
            $sectionFront = $phpWord->addSection($f4Portrait);
            $this->addHeaderFooter($sectionFront, $document);
            $this->renderFrontSection($sectionFront, $frontSec);
        }

        // === BAB UTAMA ===
        $babOrder = ['BAB I', 'BAB II', 'BAB III', 'BAB IV', 'BAB V', 'BAB VI', 'BAB VII', 'BAB VIII', 'BAB IX', 'BAB X'];
        $mainSections = $document->sections()
            ->whereIn('section_type', ['chapter', 'subchapter'])
            ->orderBy('order_index')
            ->get();

        // Legacy non-Lampiran documents may still keep narratives in document columns.
        // These transient sections are used for this export only; nothing is provisioned/saved.
        if ($mainSections->isEmpty() && !app(DocumentHtmlService::class)->preservesLampiran($document)
            && !$document->sections()->exists()) {
            $legacyFields = [
                'latar_belakang' => ['BAB I', '1.1', 'Latar Belakang'],
                'landasan_hukum' => ['BAB I', '1.2', 'Landasan Hukum'],
                'maksud_tujuan' => ['BAB I', '1.3', 'Maksud dan Tujuan'],
                'sistematika' => ['BAB I', '1.4', 'Sistematika Penulisan'],
                'evaluasi_narasi' => ['BAB II', '2.1', 'Evaluasi Pelaksanaan Renja'],
                'isu_strategis_narasi' => ['BAB II', '2.3', 'Isu Strategis'],
                'tujuan_sasaran_narasi' => ['BAB III', '3.1', 'Tujuan dan Sasaran'],
                'program_kegiatan_narasi' => ['BAB IV', '4.1', 'Program dan Kegiatan'],
                'penutup_narasi' => ['BAB V', '5.1', 'Penutup'],
            ];
            foreach ($legacyFields as $field => [$bab, $code, $title]) {
                if (!empty($document->{$field})) {
                    $mainSections->push(new \App\Models\RenjaSection([
                        'section_type' => 'subchapter', 'bab_code' => $bab,
                        'sub_bab_code' => $code, 'sub_bab_title' => $title,
                        'content' => $document->{$field},
                    ]));
                }
            }
        }

        $groupedBabs = $mainSections->groupBy('bab_code')->sortBy(function ($secs, $key) use ($babOrder) {
            $idx = array_search(strtoupper(trim($key)), $babOrder);
            return $idx !== false ? $idx : 99;
        });

        foreach ($groupedBabs as $babCode => $babSections) {
            // Setiap BAB di halaman baru
            $currentSection = $phpWord->addSection($f4Portrait);
            $this->addHeaderFooter($currentSection, $document);

            // Judul BAB
            $firstSec = $babSections->first();
            $babTitle = strtoupper($firstSec->bab_title ?? '');
            if (empty($babTitle) || $babTitle === strtoupper($babCode)) {
                $defaults = [
                    'BAB I' => 'PENDAHULUAN',
                    'BAB II' => 'HASIL EVALUASI RENJA PERANGKAT DAERAH TAHUN LALU',
                    'BAB III' => 'TUJUAN DAN SASARAN PERANGKAT DAERAH',
                    'BAB IV' => 'RENCANA KERJA DAN PENDANAAN PERANGKAT DAERAH',
                    'BAB V' => 'PENUTUP',
                ];
                $babTitle = $defaults[$babCode] ?? '';
            }

            $this->addCenteredText($currentSection, strtoupper($babCode), ['bold' => false, 'size' => 12, 'name' => 'Bookman Old Style'], 'BabTitle');
            if (!empty($babTitle)) {
                $this->addCenteredText($currentSection, $babTitle, ['bold' => false, 'size' => 12, 'name' => 'Bookman Old Style'], 'BabTitle');
            }

            // Sub-bab dalam BAB ini
            foreach ($babSections as $sec) {
                if ($sec->section_type === 'chapter') {
                    // Konten langsung di bawah judul BAB (tanpa sub-bab header)
                    $this->renderHtmlContent($currentSection, $sec->content ?? '', $document);
                    continue;
                }

                // Judul Sub-Bab
                $cleanTitle = preg_replace('/^\d+(\.\d+)*\s*/', '', $sec->sub_bab_title ?? '');
                $subTitle = ($sec->sub_bab_code ?? '') . ' ' . $cleanTitle;
                $para = $currentSection->addTextRun(['spaceAfter' => 120, 'spaceBefore' => 200]);
                $para->addText(trim($subTitle), ['name' => 'Bookman Old Style', 'size' => 12, 'bold' => false]);

                // Konten sub-bab
                $this->renderHtmlContent($currentSection, $sec->content ?? '', $document);
            }
        }

        // === LAMPIRAN ===
        $appendixSections = $document->sections()
            ->where('section_type', 'appendix')
            ->orderBy('order_index')
            ->get();

        foreach ($appendixSections as $ap) {
            $sectionAp = $phpWord->addSection($f4Portrait);
            $this->addHeaderFooter($sectionAp, $document);
            $this->addCenteredText($sectionAp, strtoupper($ap->sub_bab_code ?? 'LAMPIRAN'), ['size' => 12, 'bold' => false, 'name' => 'Bookman Old Style'], 'BabTitle');
            if (!empty($ap->sub_bab_title)) {
                $this->addCenteredText($sectionAp, strtoupper($ap->sub_bab_title), ['size' => 12, 'bold' => false, 'name' => 'Bookman Old Style'], 'BabTitle');
            }
            $this->renderHtmlContent($sectionAp, $ap->content ?? '', $document);
        }

        // Simpan ke file sementara
        $tmpFile = tempnam(sys_get_temp_dir(), 'renja_') . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpFile);

        return $tmpFile;
    }

    /**
     * Render konten HTML ke dalam section PhpWord.
     * Memisahkan paragraf, tabel, dan gambar.
     */
    private function renderHtmlContent(\PhpOffice\PhpWord\Element\Section $section, string $html, RenjaDocument $document): void
    {
        $htmlService = app(DocumentHtmlService::class);
        $preserveLampiran = $htmlService->preservesLampiran($document);
        if (empty(trim(strip_tags($html))) && ($preserveLampiran || !preg_match('/<(img|table)\b/i', $html))) {
            return;
        }
        if (!$preserveLampiran) {
            $html = $htmlService->forWord($html);
        }

        // Gunakan HTML konverter bawaan phpword
        try {
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $this->sanitizeHtmlForPhpWord($html), false, false);
        } catch (\Throwable $e) {
            if (!$preserveLampiran && preg_match('/<(table|img)\b/i', $html)) {
                report($e);
                throw \Illuminate\Validation\ValidationException::withMessages(['export' => 'Tabel atau gambar belum dapat dikonversi. Periksa format bagian tersebut lalu coba lagi.']);
            }
            // Fallback: strip HTML dan tambahkan sebagai teks biasa
            $plainText = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));
            if (!empty($plainText)) {
                foreach (explode("\n", $plainText) as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $section->addText(
                            htmlspecialchars_decode($line),
                            ['name' => 'Bookman Old Style', 'size' => 12, 'bold' => false],
                            ['spaceAfter' => 120, 'lineHeight' => 1.5, 'alignment' => 'both']
                        );
                    }
                }
            }
        }
    }

    /**
     * Sanitasi HTML agar kompatibel dengan phpword HTML parser.
     * Menghapus class/style Word yang tidak dikenal, menstandarkan border tabel.
     */
    private function sanitizeHtmlForPhpWord(string $html): string
    {
        // Hapus atribut class dan id yang tidak perlu
        $html = preg_replace('/\s+class="[^"]*"/', '', $html);

        // Normalkan style border tabel
        $html = preg_replace('/border:\s*[^;"]+;/i', 'border:1px solid #000000;', $html);

        // Hilangkan tag yang tidak didukung phpword
        $html = preg_replace('/<(script|style|svg)[^>]*>.*?<\/(script|style|svg)>/si', '', $html);

        // Bungkus konten yang tidak ada wrapper dalam <div>
        if (!preg_match('/^<(div|p|table|ul|ol|h[1-6])/i', trim($html))) {
            $html = '<div>' . $html . '</div>';
        }

        return $html;
    }

    /**
     * Render cover dokumen ke dalam section.
     */
    private function renderCover(\PhpOffice\PhpWord\Element\Section $section, RenjaDocument $document, array $coverData): void
    {
        // Spacer atas
        for ($i = 0; $i < 5; $i++) {
            $section->addTextBreak();
        }

        $judulCover = strtoupper($coverData['judul_dokumen'] ?? $document->jenis_dokumen ?? 'RENCANA KERJA');
        $this->addCenteredText($section, $judulCover, ['name' => 'Bookman Old Style', 'size' => 14, 'bold' => false], 'BabTitle');
        $this->addCenteredText($section, 'TAHUN ANGGARAN ' . ($coverData['tahun_anggaran'] ?? $document->tahun_anggaran), ['name' => 'Bookman Old Style', 'size' => 12, 'bold' => false], 'BabTitle');

        for ($i = 0; $i < 8; $i++) {
            $section->addTextBreak();
        }

        $this->addCenteredText($section, strtoupper($coverData['nama_opd'] ?? $document->opd->nama_opd ?? ''), ['name' => 'Bookman Old Style', 'size' => 12, 'bold' => false], 'Normal');
        $this->addCenteredText($section, strtoupper($coverData['nama_pemda'] ?? 'PEMERINTAH KABUPATEN CIREBON'), ['name' => 'Bookman Old Style', 'size' => 12, 'bold' => false], 'Normal');
        $this->addCenteredText($section, strtoupper($coverData['lokasi'] ?? 'SUMBER'), ['name' => 'Bookman Old Style', 'size' => 12, 'bold' => false], 'Normal');
        $this->addCenteredText($section, $coverData['tahun_terbit'] ?? date('Y'), ['name' => 'Bookman Old Style', 'size' => 12, 'bold' => false], 'Normal');
    }

    /**
     * Render halaman front matter (Kata Pengantar, Daftar Isi, Daftar Tabel, Daftar Gambar).
     */
    private function renderFrontSection(\PhpOffice\PhpWord\Element\Section $section, $frontSec): void
    {
        // Judul halaman
        $this->addCenteredText($section, strtoupper($frontSec->sub_bab_title ?? ''), ['name' => 'Bookman Old Style', 'size' => 12, 'bold' => false], 'BabTitle');
        $section->addTextBreak();

        // Konten
        $this->renderHtmlContent($section, $frontSec->content ?? '', $frontSec->document ?? new RenjaDocument());
    }

    /**
     * Tambahkan header (nama OPD) dan footer (nomor halaman) ke section.
     */
    private function addHeaderFooter(\PhpOffice\PhpWord\Element\Section $section, RenjaDocument $document): void
    {
        $namaOpd = strtoupper($document->opd->nama_opd ?? 'PERANGKAT DAERAH');
        $jenisDok = $document->jenis_dokumen ?? 'RENJA';
        $ta = $document->tahun_anggaran ?? date('Y');

        // Header
        $header = $section->addHeader();
        $headerTable = $header->addTable(['borderSize' => 0, 'cellMargin' => 80]);
        $headerRow = $headerTable->addRow();
        $headerRow->addCell(null, ['borderSize' => 0])->addText(
            $namaOpd . ' — ' . $jenisDok . ' TA ' . $ta,
            ['name' => 'Bookman Old Style', 'size' => 9, 'bold' => false],
            ['alignment' => 'left']
        );
        $headerRow->addCell(null, ['borderSize' => 0, 'alignment' => 'right'])->addText(
            date('Y'),
            ['name' => 'Bookman Old Style', 'size' => 9],
            ['alignment' => 'right']
        );

        // Footer dengan nomor halaman
        $footer = $section->addFooter();
        $footer->addPreserveText('- {PAGE} -', ['name' => 'Bookman Old Style', 'size' => 10], ['alignment' => 'center']);
    }

    /**
     * Helper: tambahkan teks rata tengah.
     */
    private function addCenteredText(\PhpOffice\PhpWord\Element\Section $section, string $text, array $fontStyle, string $paraStyle = 'Normal'): void
    {
        $section->addText(
            $text,
            $fontStyle,
            ['alignment' => 'center', 'spaceAfter' => 120]
        );
    }

    /**
     * Setting halaman F4 Portrait (215mm x 330mm, margin 20mm semua sisi).
     */
    private function getF4PortraitSettings(): array
    {
        $cm = self::CM_TO_TWIP;
        return [
            'pageSizeW' => (int)(21.5 * $cm),
            'pageSizeH' => (int)(33.0 * $cm),
            'marginTop' => (int)(2.0 * $cm),
            'marginBottom' => (int)(2.0 * $cm),
            'marginLeft' => (int)(2.0 * $cm),
            'marginRight' => (int)(2.0 * $cm),
            'orientation' => 'portrait',
        ];
    }

    /**
     * Setting halaman F4 Landscape (33.0mm x 21.5mm, margin 20mm semua sisi).
     */
    private function getF4LandscapeSettings(): array
    {
        $cm = self::CM_TO_TWIP;
        return [
            'pageSizeW' => (int)(33.0 * $cm),
            'pageSizeH' => (int)(21.5 * $cm),
            'marginTop' => (int)(2.0 * $cm),
            'marginBottom' => (int)(2.0 * $cm),
            'marginLeft' => (int)(2.0 * $cm),
            'marginRight' => (int)(2.0 * $cm),
            'orientation' => 'landscape',
        ];
    }

    /**
     * @deprecated Gunakan generateRenjaDocx() sebagai gantinya.
     * Dipertahankan untuk backward compatibility dengan kode lama.
     */
    public const XML_PAGE_BREAK = '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';

    /**
     * @deprecated
     */
    public function generateRenjaDocument(RenjaDocument $document): \PhpOffice\PhpWord\PhpWord
    {
        // Proxy ke implementasi baru
        $tmpFile = $this->generateRenjaDocx($document);
        return \PhpOffice\PhpWord\IOFactory::load($tmpFile);
    }

    /**
     * @deprecated
     */
    public function injectPageBreaksInTemplate(\PhpOffice\PhpWord\TemplateProcessor $templateProcessor, array $babContents): void
    {
        foreach ($babContents as $key => $content) {
            $contentWithPageBreak = $content . self::XML_PAGE_BREAK;
            $templateProcessor->setValue($key, $contentWithPageBreak);
        }
    }
}
