<?php

namespace App\Services;

use App\Models\RenjaDocument;
use App\Models\RenjaSectionCaption;

/**
 * PdfExportService — Generate dokumen PDF dari konten Renja.
 * Menggunakan barryvdh/laravel-dompdf untuk konversi HTML→PDF.
 *
 * Strategy: Render view renja.pdf_export ke HTML, lalu konversi ke PDF.
 * Paper F4 (215mm x 330mm), margin 20mm semua sisi.
 * Font utama: DejaVu Serif (fallback Bookman Old Style yang tidak tersedia di dompdf).
 */
class PdfExportService
{
    /**
     * Generate PDF dari dokumen Renja dan kembalikan instance Dompdf.
     *
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateRenjaPdf(RenjaDocument $document)
    {
        // Load sections dengan eager loading untuk efisiensi
        if ($document->isLampiranPerbub()) {
            $document->load(['opd', 'template', 'sections', 'sections.captions']);
        } else {
            $document->loadMissing(['opd', 'template', 'sections', 'sections.captions']);
            app(RenjaIndexGeneratorService::class)->prepareExportSections($document);
        }

        $babOrder = ['BAB I', 'BAB II', 'BAB III', 'BAB IV', 'BAB V', 'BAB VI', 'BAB VII', 'BAB VIII', 'BAB IX', 'BAB X'];

        $sections = $document->sections;
        $frontSections = $sections->whereIn('section_type', ['cover', 'preface', 'table_of_contents', 'list_of_tables', 'list_of_figures', 'list_of_appendices', 'list_of_charts'])->sortBy('order_index');
        $mainSections = $sections->whereIn('section_type', ['chapter', 'subchapter'])->sortBy('order_index');
        $appendixSections = $sections->where('section_type', 'appendix')->sortBy('order_index');

        $groupedBabs = $mainSections->groupBy('bab_code')->sortBy(function ($secs, $key) use ($babOrder) {
            $idx = array_search(strtoupper(trim($key)), $babOrder);
            return $idx !== false ? $idx : 99;
        });

        $babTitleDefaults = [
            'BAB I' => 'PENDAHULUAN',
            'BAB II' => 'HASIL EVALUASI RENJA PERANGKAT DAERAH TAHUN LALU',
            'BAB III' => 'TUJUAN DAN SASARAN PERANGKAT DAERAH',
            'BAB IV' => 'RENCANA KERJA DAN PENDANAAN PERANGKAT DAERAH',
            'BAB V' => 'PENUTUP',
        ];

        $data = [
            'document' => $document,
            'frontSections' => $frontSections,
            'groupedBabs' => $groupedBabs,
            'appendixSections' => $appendixSections,
            'babTitleDefaults' => $babTitleDefaults,
        ];

        // Render HTML view
        $html = view('renja.pdf_export', $data)->render();

        // Konfigurasi dompdf
        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html);
        $pdf->setPaper([0, 0, 609.4488, 936.0], 'portrait'); // F4: 215mm x 330mm dalam point (1mm = 2.8346pt)
        $pdf->setOptions([
            'defaultFont' => 'DejaVu Serif',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => false,
            'isRemoteEnabled' => false,
            'dpi' => 96,
            'defaultPaperSize' => 'A4',
        ]);

        return $pdf;
    }

    /**
     * Hitung estimasi nomor halaman per section berdasarkan panjang konten.
     * Digunakan untuk TOC dengan estimasi halaman.
     *
     * Asumsi: ~2000 karakter teks per halaman F4 dengan font 12pt.
     *
     * @return array<int, int> section_id => estimated_page_number
     */
    public function estimatePageNumbers(RenjaDocument $document): array
    {
        $sections = $document->sections()
            ->whereIn('section_type', ['chapter', 'subchapter'])
            ->orderBy('order_index')
            ->get();

        $CHARS_PER_PAGE = 1800; // Estimasi konservatif karakter per halaman
        $currentPage = 3; // Asumsi cover + kata pengantar = 2 halaman pertama
        $pageMap = [];

        foreach ($sections as $sec) {
            $pageMap[$sec->id] = $currentPage;

            // Hitung panjang konten
            $contentLength = mb_strlen(strip_tags($sec->content ?? ''));
            // Tambah ruang untuk heading sub-bab (~1 baris)
            $contentLength += 200;

            // Hitung berapa halaman yang dibutuhkan konten ini
            $pagesNeeded = max(1, (int) ceil($contentLength / $CHARS_PER_PAGE));
            $currentPage += $pagesNeeded;
        }

        return $pageMap;
    }
}
