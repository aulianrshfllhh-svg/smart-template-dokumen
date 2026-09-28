<?php

namespace App\Services;

use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Models\RenjaSectionCaption;

class RenjaIndexGeneratorService
{
    /**
     * Hasilkan konten HTML Daftar Isi dinamis berdasarkan hierarki BAB dan Sub-BAB.
     * Dengan estimasi nomor halaman dan dot-leaders.
     */
    public function generateTableOfContentsHtml(RenjaDocument $document): string
    {
        $sections = $document->sections()
            ->whereIn('section_type', ['chapter', 'subchapter'])
            ->orderBy('order_index')
            ->get();

        if ($sections->isEmpty()) {
            return '<div class="smart-index-toc font-serif select-none" style="font-family: \'Bookman Old Style\', \'Bookman\', serif; font-size: 12pt; color: #000000;">'
                . '<h2 style="text-align: center; text-transform: uppercase; font-size: 12pt; font-weight: normal; margin-bottom: 24pt;">DAFTAR ISI</h2>'
                . '<p style="text-align: center; color: #6b7280; font-size: 11pt; font-style: italic;">(Belum ada BAB atau Sub-Bab pada dokumen ini)</p>'
                . '</div>';
        }

        $babOrder = ['BAB I', 'BAB II', 'BAB III', 'BAB IV', 'BAB V', 'BAB VI', 'BAB VII', 'BAB VIII', 'BAB IX', 'BAB X'];
        $grouped = $sections->groupBy('bab_code')->sortBy(function ($secs, $key) use ($babOrder) {
            $idx = array_search(strtoupper(trim($key)), $babOrder);
            return $idx !== false ? $idx : 99;
        });

        // Hitung estimasi nomor halaman
        $pageEstimates = $this->estimatePageNumbers($document);

        // Cek apakah ada front sections untuk estimasi offset
        $frontCount = $document->sections()
            ->whereIn('section_type', ['cover', 'preface', 'table_of_contents', 'list_of_tables', 'list_of_figures'])
            ->count();
        $pageOffset = max(2, $frontCount); // minimal 2 halaman (cover + kata pengantar) sebelum BAB I

        $html = '<div class="smart-index-toc font-serif" style="font-family: \'Bookman Old Style\', \'Bookman\', serif; font-size: 12pt; color: #000000;">';
        $html .= '<h2 style="text-align: center; text-transform: uppercase; font-size: 12pt; font-weight: normal; margin-bottom: 24pt;">DAFTAR ISI</h2>';
        $html .= '<table style="width: 100%; border-collapse: collapse; line-height: 2.0; color: #000000;">';

        $babTitleDefaults = [
            'BAB I'   => 'PENDAHULUAN',
            'BAB II'  => 'EVALUASI PELAKSANAAN RENJA PERANGKAT DAERAH TAHUN LALU',
            'BAB III' => 'TUJUAN DAN SASARAN PERANGKAT DAERAH',
            'BAB IV'  => 'RENCANA KERJA DAN PENDANAAN PERANGKAT DAERAH',
            'BAB V'   => 'PENUTUP',
        ];

        foreach ($grouped as $babCode => $subSecs) {
            $firstSec = $subSecs->first();
            $rawTitle = $firstSec->bab_title ?? '';
            if (empty($rawTitle) || strtoupper(trim($rawTitle)) === strtoupper(trim($babCode))) {
                $babTitle = $babTitleDefaults[$babCode] ?? '';
            } else {
                $babTitle = strtoupper($rawTitle);
            }

            $babFullLabel = e($babCode) . (!empty($babTitle) ? ' ' . e($babTitle) : '');
            $babPage = $pageOffset + ($pageEstimates[$firstSec->id] ?? 0);

            // Baris BAB
            $html .= '<tr>';
            $html .= '<td style="font-weight: normal; text-transform: uppercase; padding-top: 10pt; padding-bottom: 2pt;">' . $babFullLabel . '</td>';
            $html .= '<td style="text-align: right; white-space: nowrap; padding-top: 10pt; width: 50pt; font-weight: normal;">' . $babPage . '</td>';
            $html .= '</tr>';

            // Loop Sub-BAB
            foreach ($subSecs as $s) {
                if ($s->section_type === 'chapter') continue;
                $cleanSubTitle = preg_replace('/^\d+(\.\d+)*\s*/', '', $s->sub_bab_title ?? '');
                $subFullLabel = e($s->sub_bab_code) . ' ' . e($cleanSubTitle);
                $subPage = $pageOffset + ($pageEstimates[$s->id] ?? 0);

                $html .= '<tr>';
                $html .= '<td style="padding-left: 24pt; font-weight: normal;">' . $subFullLabel . '</td>';
                $html .= '<td style="text-align: right; white-space: nowrap; width: 50pt; font-weight: normal;">' . $subPage . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</table></div>';
        return $html;
    }

    /**
     * Hasilkan konten HTML Daftar Gambar otomatis.
     * Membaca dari renja_section_captions (element_type = 'figure') terlebih dahulu,
     * jika tidak ada fallback ke parsing tag <img> di konten.
     */
    public function generateListOfFiguresHtml(RenjaDocument $document): string
    {
        // 1. Coba ambil dari caption database
        $captionFigures = RenjaSectionCaption::whereIn('section_id', function ($q) use ($document) {
            $q->select('id')
              ->from('renja_sections')
              ->where('document_id', $document->id)
              ->whereIn('section_type', ['chapter', 'subchapter']);
        })
        ->where('element_type', 'figure')
        ->orderBy('order_index')
        ->get();

        $figures = [];
        $counter = 1;

        if ($captionFigures->isNotEmpty()) {
            foreach ($captionFigures as $cap) {
                $figures[] = [
                    'number' => $cap->display_number ?? 'Gambar ' . $counter,
                    'title'  => $cap->caption,
                ];
                $counter++;
            }
        } else {
            // Fallback: parse <img> dari konten
            $sections = $document->sections()
                ->whereIn('section_type', ['chapter', 'subchapter'])
                ->get();

            foreach ($sections as $sec) {
                if (preg_match_all('/<img\b[^>]*>/i', $sec->content ?? '', $matches)) {
                    foreach ($matches[0] as $imgTag) {
                        $caption = '';
                        if (preg_match('/alt=["\']([^"\']+)["\']/i', $imgTag, $altMatch)) {
                            $caption = trim($altMatch[1]);
                        } elseif (preg_match('/title=["\']([^"\']+)["\']/i', $imgTag, $titleMatch)) {
                            $caption = trim($titleMatch[1]);
                        }

                        if (empty($caption)) {
                            $caption = 'Gambar pada ' . ($sec->sub_bab_code ?? $sec->bab_code) . ' ' . ($sec->sub_bab_title ?? $sec->bab_title ?? '');
                        }

                        $figures[] = [
                            'number' => 'Gambar ' . $counter++,
                            'title'  => $caption,
                        ];
                    }
                }
            }
        }

        $html = '<div class="smart-index-figures font-serif" style="font-family: \'Bookman Old Style\', \'Bookman\', serif; font-size: 12pt; color: #000000;">';
        $html .= '<h2 style="text-align: center; text-transform: uppercase; font-size: 12pt; font-weight: normal; margin-bottom: 24pt;">DAFTAR GAMBAR</h2>';

        if (empty($figures)) {
            $html .= '<p style="text-align: center; color: #6b7280; font-size: 11pt; font-style: italic;">(Belum ada gambar yang memiliki caption/judul pada dokumen ini)</p>';
        } else {
            $html .= '<table style="width: 100%; border-collapse: collapse; line-height: 2.0; color: #000000;">';
            foreach ($figures as $fig) {
                $html .= '<tr>';
                $html .= '<td style="width: 120pt; font-weight: normal;">' . e($fig['number']) . '</td>';
                $html .= '<td style="font-weight: normal;">' . e($fig['title']) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Hasilkan konten HTML Daftar Tabel otomatis.
     * Membaca dari renja_section_captions (element_type = 'table') terlebih dahulu,
     * jika tidak ada fallback ke deteksi <table> di konten.
     */
    public function generateListOfTablesHtml(RenjaDocument $document): string
    {
        // 1. Coba ambil dari caption database
        $captionTables = RenjaSectionCaption::whereIn('section_id', function ($q) use ($document) {
            $q->select('id')
              ->from('renja_sections')
              ->where('document_id', $document->id)
              ->whereIn('section_type', ['chapter', 'subchapter']);
        })
        ->where('element_type', 'table')
        ->orderBy('order_index')
        ->get();

        $tables = [];
        $counter = 1;

        if ($captionTables->isNotEmpty()) {
            foreach ($captionTables as $cap) {
                $tables[] = [
                    'number' => $cap->display_number ?? 'Tabel ' . $counter,
                    'title'  => $cap->caption,
                ];
                $counter++;
            }
        } else {
            // Fallback: deteksi <table> di konten + ambil <caption>
            $sections = $document->sections()
                ->whereIn('section_type', ['chapter', 'subchapter'])
                ->get();

            foreach ($sections as $sec) {
                if (str_contains($sec->content ?? '', '<table')) {
                    // Cari semua caption tags
                    preg_match_all('/<table[^>]*>(.*?)<\/table>/is', $sec->content, $tableMatches);
                    foreach ($tableMatches[0] as $tableHtml) {
                        $caption = '';
                        if (preg_match('/<caption[^>]*>(.*?)<\/caption>/is', $tableHtml, $capMatch)) {
                            $caption = trim(strip_tags($capMatch[1]));
                        }
                        if (empty($caption)) {
                            $caption = 'Tabel Narasi pada ' . ($sec->sub_bab_code ?? $sec->bab_code) . ' ' . ($sec->sub_bab_title ?? $sec->bab_title ?? '');
                        }

                        $tables[] = [
                            'number' => 'Tabel ' . $counter++,
                            'title'  => $caption,
                        ];
                    }
                }
            }
        }

        $html = '<div class="smart-index-tables font-serif" style="font-family: \'Bookman Old Style\', \'Bookman\', serif; font-size: 12pt; color: #000000;">';
        $html .= '<h2 style="text-align: center; text-transform: uppercase; font-size: 12pt; font-weight: normal; margin-bottom: 24pt;">DAFTAR TABEL</h2>';

        if (empty($tables)) {
            $html .= '<p style="text-align: center; color: #6b7280; font-size: 11pt; font-style: italic;">(Belum ada tabel narasi pada dokumen ini)</p>';
        } else {
            $html .= '<table style="width: 100%; border-collapse: collapse; line-height: 2.0; color: #000000;">';
            foreach ($tables as $tbl) {
                $html .= '<tr>';
                $html .= '<td style="width: 120pt; font-weight: normal;">' . e($tbl['number']) . '</td>';
                $html .= '<td style="font-weight: normal;">' . e($tbl['title']) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Hasilkan konten HTML Daftar Lampiran otomatis berdasarkan data lampiran dokumen.
     */
    public function generateListOfAppendicesHtml(RenjaDocument $document): string
    {
        $appendixSections = $document->sections()
            ->where('section_type', 'appendix')
            ->orderBy('order_index')
            ->get();

        $html = '<div class="smart-index-appendices font-serif" style="font-family: \'Bookman Old Style\', \'Bookman\', serif; font-size: 12pt; color: #000000;">';
        $html .= '<h2 style="text-align: center; text-transform: uppercase; font-size: 12pt; font-weight: normal; margin-bottom: 24pt;">DAFTAR LAMPIRAN</h2>';

        if ($appendixSections->isEmpty()) {
            $html .= '<p style="text-align: center; color: #6b7280; font-size: 11pt; font-style: italic;">(Belum ada lampiran pada dokumen ini)</p>';
        } else {
            $html .= '<table style="width: 100%; border-collapse: collapse; line-height: 2.0; color: #000000;">';
            foreach ($appendixSections as $ap) {
                $html .= '<tr>';
                $html .= '<td style="width: 140pt; font-weight: normal;">' . e($ap->sub_bab_code) . '</td>';
                $html .= '<td style="font-weight: normal;">' . e($ap->sub_bab_title) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Sinkronisasikan seluruh halaman indeks Front Matter yang eksis di database.
     * Jika halaman indeks belum dibuat manual oleh pengguna, method ini TIDAK membuat seksi baru.
     */
    public function prepareExportSections(RenjaDocument $document): void
    {
        // This new preparation step must never process Lampiran content.
        if ($document->isLampiranPerbub()) {
            return;
        }
        $sections = $document->sections;
        $methods = ['table_of_contents' => 'generateTableOfContentsHtml', 'list_of_figures' => 'generateListOfFiguresHtml', 'list_of_tables' => 'generateListOfTablesHtml', 'list_of_appendices' => 'generateListOfAppendicesHtml'];
        $document->setRelation('sections', new \Illuminate\Database\Eloquent\Collection($sections->map(function ($section) use ($document, $methods) {
            $copy = clone $section;
            if (isset($methods[$copy->section_type])) $copy->content = $this->{$methods[$copy->section_type]}($document);
            return $copy;
        })->all()));
    }

    public function syncDocumentFrontIndexes(RenjaDocument $document): void
    {
        // 1. Sinkronisasi Daftar Isi (TOC)
        $tocSection = RenjaSection::where('document_id', $document->id)
            ->where('section_type', 'table_of_contents')
            ->first();

        if ($tocSection) {
            $tocSection->update([
                'content' => $this->generateTableOfContentsHtml($document),
                'is_completed' => true,
            ]);
        }

        // 2. Sinkronisasi Daftar Gambar
        $figSection = RenjaSection::where('document_id', $document->id)
            ->where('section_type', 'list_of_figures')
            ->first();

        if ($figSection) {
            $figSection->update([
                'content' => $this->generateListOfFiguresHtml($document),
                'is_completed' => true,
            ]);
        }

        // 3. Sinkronisasi Daftar Tabel
        $tblSection = RenjaSection::where('document_id', $document->id)
            ->where('section_type', 'list_of_tables')
            ->first();

        if ($tblSection) {
            $tblSection->update([
                'content' => $this->generateListOfTablesHtml($document),
                'is_completed' => true,
            ]);
        }

        // 4. Sinkronisasi Daftar Lampiran
        $appSection = RenjaSection::where('document_id', $document->id)
            ->where('section_type', 'list_of_appendices')
            ->first();

        if ($appSection) {
            $appSection->update([
                'content' => $this->generateListOfAppendicesHtml($document),
                'is_completed' => true,
            ]);
        }
    }

    /**
     * Estimasi nomor halaman per section berdasarkan panjang konten.
     * Mengembalikan map: section_id => halaman relatif (dimulai dari 1).
     */
    private function estimatePageNumbers(RenjaDocument $document): array
    {
        $sections = $document->sections()
            ->whereIn('section_type', ['chapter', 'subchapter'])
            ->orderBy('order_index')
            ->get();

        $CHARS_PER_PAGE = 1800;
        $currentPage = 1;
        $pageMap = [];

        foreach ($sections as $sec) {
            $pageMap[$sec->id] = $currentPage;
            $contentLength = mb_strlen(strip_tags($sec->content ?? '')) + 150; // +150 untuk heading
            $pagesNeeded = max(1, (int) ceil($contentLength / $CHARS_PER_PAGE));
            $currentPage += $pagesNeeded;
        }

        return $pageMap;
    }
}
