<?php

namespace App\Services;

use App\Models\ReferenceDocumentSchema;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TemplateSchemaService
{
    /**
     * Katalog struktur tabel standar e-Renja SIPD.
     */
    public function getCatalogTables(): array
    {
        return [
            [
                'key'         => 'evaluasi_renja_tahun_lalu',
                'name'        => 'Tabel Evaluasi Renja OPD Tahun Lalu',
                'keywords'    => ['evaluasi', 'tahun lalu', 'realisasi', 'capaian', 'pagu', 'rkpd'],
                'min_headers' => ['kode', 'program', 'indikator', 'target', 'realisasi'],
            ],
            [
                'key'         => 'target_program_kegiatan',
                'name'        => 'Tabel Rencana Program, Kegiatan & Pendanaan OPD',
                'keywords'    => ['rencana', 'program', 'kegiatan', 'sub kegiatan', 'pagu indikatif', 'target', 'pendanaan'],
                'min_headers' => ['kode', 'nomenklatur', 'indikator', 'pagu', 'lokasi'],
            ],
            [
                'key'         => 'kerangka_pendanaan',
                'name'        => 'Tabel Kerangka Pendanaan & Kelompok Sasaran',
                'keywords'    => ['kerangka', 'pendanaan', 'sasaran', 'kelompok sasaran', 'n+1', 'pagu n+1'],
                'min_headers' => ['program', 'pagu', 'sasaran'],
            ],
        ];
    }

    /**
     * Match tabel-tabel hasil parser Python dengan katalog e-Renja.
     * Menggunakan kombinasi matching nama header & kemiripan kata kunci.
     *
     * @param array $detectedTables List tabel terdeteksi dari Python
     * @return array List hasil matching dengan metadata tipe_konten, match_status, match_score, sumber_data
     */
    public function matchTablesFromCatalog(array $detectedTables): array
    {
        $catalogs = $this->getCatalogTables();
        $results  = [];

        foreach ($detectedTables as $idx => $tabel) {
            $headers     = array_map('mb_strtolower', $tabel['header'] ?? []);
            $headerString = implode(' ', $headers);
            $bestMatch   = null;
            $bestScore   = 0;

            foreach ($catalogs as $cat) {
                $score = 0;

                // 1. Check keyword match pada header
                foreach ($cat['keywords'] as $kw) {
                    if (str_contains($headerString, $kw)) {
                        $score += 20;
                    }
                }

                // 2. Check header individual overlap
                foreach ($cat['min_headers'] as $mh) {
                    foreach ($headers as $h) {
                        similar_text($mh, $h, $percent);
                        if ($percent > 65 || str_contains($h, $mh)) {
                            $score += 15;
                            break;
                        }
                    }
                }

                if ($score > $bestScore) {
                    $bestScore = min(100, $score);
                    $bestMatch = $cat;
                }
            }

            // Batas ambang (threshold) auto match
            if ($bestScore >= 50 && $bestMatch) {
                $results[] = [
                    'tabel_index'  => $idx,
                    'tipe_konten'  => 'tabel',
                    'sumber_data'  => $bestMatch['key'],
                    'match_status' => 'auto_matched',
                    'match_score'  => $bestScore,
                    'header'       => $tabel['header'] ?? [],
                    'num_rows'     => $tabel['jumlah_baris'] ?? 0,
                ];
            } elseif ($bestScore >= 25 && $bestMatch) {
                $results[] = [
                    'tabel_index'  => $idx,
                    'tipe_konten'  => 'tabel',
                    'sumber_data'  => $bestMatch['key'],
                    'match_status' => 'perlu_review',
                    'match_score'  => $bestScore,
                    'header'       => $tabel['header'] ?? [],
                    'num_rows'     => $tabel['jumlah_baris'] ?? 0,
                ];
            } else {
                $results[] = [
                    'tabel_index'  => $idx,
                    'tipe_konten'  => 'tabel',
                    'sumber_data'  => null,
                    'match_status' => 'perlu_review',
                    'match_score'  => 0,
                    'header'       => $tabel['header'] ?? [],
                    'num_rows'     => $tabel['jumlah_baris'] ?? 0,
                ];
            }
        }

        return $results;
    }

    /**
     * Terapkan struktur BAB & Sub-Bab dari Schema Dokumen Acuan yang disetujui
     * ke dalam RenjaDocument tertentu (membuat slot section di editor).
     *
     * @param RenjaDocument $document Dokumen sasaran
     * @param ReferenceDocumentSchema $schema Schema yang sudah disetujui (approved)
     * @return int Jumlah section yang berhasil digenerate/diupdate
     */
    public function applySchemaToDocument(RenjaDocument $document, ReferenceDocumentSchema $schema): int
    {
        Log::info('[TemplateSchemaService] Menerapkan schema ke dokumen Renja.', [
            'document_id' => $document->id,
            'schema_id'   => $schema->id,
        ]);

        $schema->loadMissing(['chapters.subChapters.tableColumns']);

        $createdCount = 0;

        DB::transaction(function () use ($document, $schema, &$createdCount) {
            $orderIndex = 0;

            foreach ($schema->chapters as $chapter) {
                foreach ($chapter->subChapters as $subChapter) {
                    $orderIndex++;

                    // Cari atau buat RenjaSection berdasarkan bab_code dan sub_bab_code
                    $section = RenjaSection::firstOrNew([
                        'document_id'  => $document->id,
                        'bab_code'     => $chapter->bab_code,
                        'sub_bab_code' => $subChapter->kode,
                    ]);

                    $section->bab_title        = $chapter->judul;
                    $section->sub_bab_title    = $subChapter->judul;
                    $section->content_type     = $subChapter->tipe_konten;
                    $section->source_schema_id = $schema->id;
                    $section->order_index      = $orderIndex;

                    // Petunjuk pengisian otomatis & Auto-Generate Tabel HTML jika tipe_konten = tabel
                    if ($subChapter->tipe_konten === 'tabel') {
                        $section->guidance_text = "Komponen Tabel SIPD Auto-Generated (" . ($subChapter->sumber_data ?? 'Standar') . "). Data disesuaikan dari struktur dokumen acuan.";
                        if (empty($section->content)) {
                            $section->content = $this->generateTableHtml($subChapter);
                        }
                    } else {
                        if (empty($section->content)) {
                            $section->guidance_text = "Tulis narasi untuk Sub-Bab {$subChapter->kode} {$subChapter->judul} di sini.";
                        }
                    }

                    $section->save();
                    $createdCount++;
                }
            }
        });

        Log::info('[TemplateSchemaService] Schema berhasil diterapkan.', [
            'document_id' => $document->id,
            'total_sections' => $createdCount,
        ]);

        return $createdCount;
    }

    /**
     * Generate komponen tabel HTML otomatis dari kolom terdeteksi / katalog SIPD.
     */
    public function generateTableHtml($subChapter): string
    {
        $headers = [];

        // 1. Ambil dari kolom terdeteksi jika ada
        if ($subChapter->tableColumns && $subChapter->tableColumns->count() > 0) {
            $headers = $subChapter->tableColumns->pluck('nama_kolom')->toArray();
        }

        // 2. Jika tidak ada kolom terdeteksi, gunakan katalog standar e-Renja
        if (empty($headers)) {
            $headers = match ($subChapter->sumber_data) {
                'evaluasi_renja_tahun_lalu' => [
                    'No', 'Kode', 'Urusan / Bidang / Program / Kegiatan', 'Indikator Kinerja', 
                    'Target Renja (T-1)', 'Realisasi Renja (T-1)', 'Tingkat Capaian (%)'
                ],
                'target_program_kegiatan' => [
                    'No', 'Kode Program', 'Nomenklatur Program / Kegiatan / Sub Kegiatan', 
                    'Indikator Kinerja Output', 'Target Kinerja', 'Pagu Indikatif (Rp)', 'Lokasi'
                ],
                'kerangka_pendanaan' => [
                    'No', 'Program / Kegiatan / Sub Kegiatan', 'Pagu Indikatif Tahun N (Rp)', 
                    'Target Kinerja N', 'Pagu Indikatif Tahun N+1 (Rp)', 'Kelompok Sasaran'
                ],
                default => [
                    'No', 'Kode', 'Program / Kegiatan / Sub-Kegiatan', 'Indikator Kinerja', 
                    'Target Kinerja', 'Pagu Indikatif (Rp)', 'Keterangan'
                ],
            };
        }

        $html  = '<div class="overflow-x-auto my-4 border border-slate-300 rounded-lg shadow-sm">';
        $html .= '<table class="w-full text-left text-xs border-collapse border border-slate-300 bg-white">';
        $html .= '<thead><tr class="bg-slate-800 text-white font-bold uppercase text-[11px] tracking-wider divide-x divide-slate-700">';
        
        foreach ($headers as $h) {
            $html .= '<th class="p-2.5 text-center border-b border-slate-300 bg-slate-800 text-white font-bold">' . e($h) . '</th>';
        }

        $html .= '</tr></thead>';
        $html .= '<tbody class="divide-y divide-slate-200 text-slate-700 font-medium">';

        // 3. Generate 3 baris sampel/kosong yang dapat langsung diedit di editor
        for ($r = 1; $r <= 3; $r++) {
            $html .= '<tr class="hover:bg-slate-50 transition divide-x divide-slate-200">';
            foreach ($headers as $idx => $h) {
                if ($idx === 0) {
                    $html .= '<td class="p-2 text-center font-bold text-slate-500 bg-slate-50/50">' . $r . '</td>';
                } else {
                    $html .= '<td class="p-2 text-slate-700">[' . e($h) . ' ' . $r . ']</td>';
                }
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';
        $html .= '<p class="text-[11px] text-slate-400 italic mt-1">* Tabel di atas dibuat otomatis dari dokumen acuan. Anda dapat mengedit isinya langsung di editor.</p>';

        return $html;
    }
}
