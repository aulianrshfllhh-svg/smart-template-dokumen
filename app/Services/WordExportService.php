<?php

namespace App\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use App\Models\RenjaDocument;

class WordExportService
{
    /**
     * XML Page Break universal untuk PhpWord TemplateProcessor
     */
    public const XML_PAGE_BREAK = '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';

    /**
     * Export Dokumen Renja menggunakan PhpWord Object Murni dengan Aturan Technical Specs:
     * 1. Section baru / addPageBreak() untuk setiap Bab (BAB I - BAB V).
     * 2. Section Landscape untuk tabel matriks lebar (Tabel Renja / Evaluasi).
     * 3. Kembalikan ke Section Portrait setelah tabel matriks selesai dirender.
     */
    public function generateRenjaDocument(RenjaDocument $document): PhpWord
    {
        $phpWord = new PhpWord();

        // -------------------------------------------------------------
        // SETTING UMUM PAGE & MARGIN F4 PORTRAIT (215mm x 330mm)
        // -------------------------------------------------------------
        $f4PortraitSettings = [
            'pageSizeW' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(21.5),
            'pageSizeH' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(33.0),
            'marginTop' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.0),
            'marginBottom' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.0),
            'marginLeft' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.0),
            'marginRight' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.0),
            'orientation' => 'portrait',
        ];

        $f4LandscapeSettings = [
            'pageSizeW' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(33.0),
            'pageSizeH' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(21.5),
            'marginTop' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.0),
            'marginBottom' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.0),
            'marginLeft' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.0),
            'marginRight' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.0),
            'orientation' => 'landscape',
        ];

        // =============================================================
        // BAB I PENDAHULUAN (Portrait)
        // =============================================================
        $sectionBab1 = $phpWord->addSection($f4PortraitSettings);
        $sectionBab1->addTitle('BAB I PENDAHULUAN', 1);
        $sectionBab1->addText($document->latar_belakang ?? 'Latar belakang...');
        $sectionBab1->addText($document->landasan_hukum ?? 'Landasan hukum...');

        // =============================================================
        // BAB II HASIL EVALUASI (Page Break & Section Baru)
        // =============================================================
        $sectionBab2 = $phpWord->addSection($f4PortraitSettings);
        // Atau: $sectionBab1->addPageBreak();
        $sectionBab2->addTitle('BAB II HASIL EVALUASI RENJA TAHUN LALU', 1);
        $sectionBab2->addText('Berikut adalah narasi evaluasi kinerja...');

        // -------------------------------------------------------------
        // TABEL MATRIKS RENJA (Orientasi LANDSCAPE)
        // -------------------------------------------------------------
        $sectionLandscape = $phpWord->addSection($f4LandscapeSettings);
        $sectionLandscape->addText('Tabel 2.1 Evaluasi Kinerja Renja Perangkat Daerah', ['bold' => true]);

        $table = $sectionLandscape->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
        ]);
        
        // Repeat Header Row
        $table->addRow(null, ['tblHeader' => true]);
        $table->addCell(1000)->addText('No');
        $table->addCell(4000)->addText('Program / Kegiatan');
        $table->addCell(2000)->addText('Target');
        $table->addCell(2000)->addText('Realisasi');

        // =============================================================
        // KEMBALI KE PORTRAIT UNTUK BAB III & KONTEN SELANJUTNYA
        // =============================================================
        $sectionBab3 = $phpWord->addSection($f4PortraitSettings);
        $sectionBab3->addTitle('BAB III TUJUAN DAN SASARAN', 1);
        $sectionBab3->addText('Narasi tujuan dan sasaran perangkat daerah...');

        return $phpWord;
    }

    /**
     * Export via TemplateProcessor (Injeksi XML Page Break pada variabel pengganti)
     */
    public function injectPageBreaksInTemplate(TemplateProcessor $templateProcessor, array $babContents): void
    {
        foreach ($babContents as $key => $content) {
            // Jika konten ini adalah akhir dari sebuah Bab, tambahkan XML Page Break di ujungnya
            $contentWithPageBreak = $content . self::XML_PAGE_BREAK;
            $templateProcessor->setValue($key, $contentWithPageBreak);
        }
    }
}
