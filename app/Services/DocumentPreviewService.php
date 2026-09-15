<?php

namespace App\Services;

use App\Models\RenjaDocument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentPreviewService
{
    /**
     * Cache directory relative to storage/app
     */
    protected string $cacheDir = 'preview_cache';

    /**
     * Dapatkan path PDF hasil render dokumen. Jika belum ada di cache, lakukan rendering server-side.
     *
     * @param RenjaDocument $document
     * @param bool $forceReconvert
     * @return array ['success' => bool, 'pdf_path' => string, 'cache_hit' => bool, 'error' => ?string]
     */
    public function getOrGeneratePdf(RenjaDocument $document, bool $forceReconvert = false): array
    {
        @set_time_limit(300);

        $cachePath = $this->getCachePdfPath($document);

        // 1. Cek Cache Hasil Render Sebelumnya
        if (!$forceReconvert && file_exists($cachePath) && filesize($cachePath) > 1000) {
            return [
                'success' => true,
                'pdf_path' => $cachePath,
                'file_size' => filesize($cachePath),
                'cache_hit' => true,
                'error' => null,
            ];
        }

        // 2. Pastikan direktori cache tersedia
        $cacheDirFull = dirname($cachePath);
        if (!file_exists($cacheDirFull)) {
            @mkdir($cacheDirFull, 0777, true);
        }

        // 3. Identifikasi sumber dokumen (.docx atau .pdf asli)
        $isUploadWord = ($document->source_type === 'upload_word') && !$document->isLampiranPerbub() && empty($document->metadata['editor_modified']);
        $tempFilesToDelete = [];

        try {
            $sourceDocxPath = null;

            if ($isUploadWord) {
                // Dokumen berasal dari Word Upload Asli
                $origPath = $document->metadata['stored_filepath'] ?? ($document->metadata['original_file_path'] ?? $document->file_path);
                
                if (!empty($origPath)) {
                    if (Storage::disk('private')->exists($origPath)) {
                        $sourceDocxPath = Storage::disk('private')->path($origPath);
                    } elseif (Storage::disk('local')->exists($origPath)) {
                        $sourceDocxPath = Storage::disk('local')->path($origPath);
                    } elseif (file_exists(storage_path('app/' . $origPath))) {
                        $sourceDocxPath = storage_path('app/' . $origPath);
                    } elseif (file_exists(storage_path('app/private/' . $origPath))) {
                        $sourceDocxPath = storage_path('app/private/' . $origPath);
                    }
                }

                // Jika file asli ternyata sudah berformat PDF
                if ($sourceDocxPath && strtolower(pathinfo($sourceDocxPath, PATHINFO_EXTENSION)) === 'pdf') {
                    @copy($sourceDocxPath, $cachePath);
                    return [
                        'success' => true,
                        'pdf_path' => $cachePath,
                        'file_size' => filesize($cachePath),
                        'cache_hit' => false,
                        'error' => null,
                    ];
                }
            } else {
                // Dokumen disusun dari Template / Smart Editor -> Generate .docx dulu
                $sourceDocxPath = $this->generateDocxFromSections($document);
                if ($sourceDocxPath) {
                    $tempFilesToDelete[] = $sourceDocxPath;
                }
            }

            if (!$sourceDocxPath || !file_exists($sourceDocxPath)) {
                return [
                    'success' => false,
                    'pdf_path' => null,
                    'cache_hit' => false,
                    'error' => 'File sumber dokumen tidak ditemukan atau belum pernah diunggah.',
                ];
            }

            // 4. Lakukan Document Rendering: DOCX -> PDF Engine
            $renderSuccess = $this->renderDocxToPdf($sourceDocxPath, $cachePath);

            if ($renderSuccess && file_exists($cachePath) && filesize($cachePath) > 1000) {
                return [
                    'success' => true,
                    'pdf_path' => $cachePath,
                    'file_size' => filesize($cachePath),
                    'cache_hit' => false,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'pdf_path' => null,
                'cache_hit' => false,
                'error' => 'Gagal merender dokumen DOCX ke PDF. Pastikan format file valid.',
            ];
        } catch (\Throwable $e) {
            Log::error("[DocumentPreviewService] Rendering error: " . $e->getMessage(), [
                'doc_id' => $document->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'pdf_path' => null,
                'cache_hit' => false,
                'error' => 'Terjadi kesalahan sistem saat merender pratinjau dokumen: ' . $e->getMessage(),
            ];
        } finally {
            // Bersihkan file sementara
            foreach ($tempFilesToDelete as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
        }
    }

    /**
     * Hitung path file PDF cache berdasarkan hash file atau updated_at dokumen.
     */
    public function getCachePdfPath(RenjaDocument $document): string
    {
        $docId = $document->id;

        if ($document->isLampiranPerbub()) {
            $parent = $document->getParentDocument();
            $timestamp = $document->updated_at ? $document->updated_at->timestamp : time();
            if ($parent && $parent->updated_at) {
                $timestamp = max($timestamp, $parent->updated_at->timestamp);
            }
            $filename = "preview_lampiran_murni_{$docId}_{$timestamp}_v16.pdf";
            return storage_path('app/' . $this->cacheDir . '/' . $filename);
        }

        $isUploadWord = ($document->source_type === 'upload_word') && empty($document->metadata['editor_modified']);

        if ($isUploadWord) {
            $hash = $document->metadata['original_file_hash'] ?? md5(($document->original_filename ?? 'doc') . ($document->updated_at ?? time()));
            $safeHash = substr(preg_replace('/[^a-zA-Z0-9]/', '', $hash), 0, 20);
            $filename = "preview_upload_{$docId}_{$safeHash}_v16.pdf";
        } else {
            $timestamp = $document->updated_at ? $document->updated_at->timestamp : time();
            $filename = "preview_template_{$docId}_{$timestamp}_v16.pdf";
        }

        return storage_path('app/' . $this->cacheDir . '/' . $filename);
    }

    /**
     * Render file DOCX menjadi PDF dengan multi-engine fallback:
     * 1. Microsoft Word COM Automation (Pixel-Perfect Native Rendering pada Windows)
     * 2. LibreOffice / Soffice Headless
     * 3. Python-docx2pdf jika tersedia
     */
    protected function renderDocxToPdf(string $inputDocx, string $outputPdf): bool
    {
        $inputDocx = str_replace('/', DIRECTORY_SEPARATOR, $inputDocx);
        $outputPdf = str_replace('/', DIRECTORY_SEPARATOR, $outputPdf);

        // --- ENGINE 1: MS Word COM Automation via PowerShell ---
        $psScript = base_path('scripts/convert_docx_to_pdf.ps1');
        if (file_exists($psScript)) {
            $cmd = sprintf(
                'powershell -NoProfile -ExecutionPolicy Bypass -File %s -InputDocx %s -OutputPdf %s 2>&1',
                escapeshellarg($psScript),
                escapeshellarg($inputDocx),
                escapeshellarg($outputPdf)
            );

            $output = [];
            $returnCode = 1;
            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && file_exists($outputPdf) && filesize($outputPdf) > 1000) {
                Log::info("[DocumentPreviewService] DOCX rendered via MS Word COM: {$outputPdf}");
                return true;
            }

            Log::warning("[DocumentPreviewService] MS Word COM failed, output: " . implode("\n", $output));
        }

        // --- ENGINE 2: LibreOffice / soffice fallback ---
        $libreOfficePaths = [
            'soffice',
            'libreoffice',
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
        ];

        $outDir = dirname($outputPdf);

        foreach ($libreOfficePaths as $loPath) {
            $testCmd = sprintf('"%s" --headless --convert-to pdf --outdir %s %s 2>&1', $loPath, escapeshellarg($outDir), escapeshellarg($inputDocx));
            $loOut = [];
            $loReturn = 1;
            @exec($testCmd, $loOut, $loReturn);

            $expectedLoPdf = $outDir . DIRECTORY_SEPARATOR . pathinfo($inputDocx, PATHINFO_FILENAME) . '.pdf';
            if (file_exists($expectedLoPdf) && filesize($expectedLoPdf) > 1000) {
                if ($expectedLoPdf !== $outputPdf) {
                    @rename($expectedLoPdf, $outputPdf);
                }
                Log::info("[DocumentPreviewService] DOCX rendered via LibreOffice: {$outputPdf}");
                return true;
            }
        }

        // --- ENGINE 3: Python docx2pdf script fallback ---
        $pyScript = base_path('scripts/convert_docx_to_pdf.py');
        if (file_exists($pyScript)) {
            $pyCmd = sprintf('python %s %s %s 2>&1', escapeshellarg($pyScript), escapeshellarg($inputDocx), escapeshellarg($outputPdf));
            $pyOut = [];
            $pyReturn = 1;
            @exec($pyCmd, $pyOut, $pyReturn);

            if ($pyReturn === 0 && file_exists($outputPdf) && filesize($outputPdf) > 1000) {
                Log::info("[DocumentPreviewService] DOCX rendered via Python: {$outputPdf}");
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve path file DOCX asli dari metadata dokumen (upload_word).
     */
    protected function resolveUploadedDocxPath(RenjaDocument $document): ?string
    {
        $meta = $document->metadata ?? [];
        $candidates = array_filter([
            $meta['stored_filepath'] ?? null,
            $meta['original_file_path'] ?? null,
            $document->file_path ?? null,
        ]);

        foreach ($candidates as $relPath) {
            $fullPaths = [
                Storage::disk('private')->path($relPath),
                Storage::disk('local')->path($relPath),
                storage_path('app/' . $relPath),
                storage_path('app/private/' . $relPath),
                $relPath,
            ];
            foreach ($fullPaths as $fp) {
                if (file_exists($fp) && filesize($fp) > 1000) {
                    return $fp;
                }
            }
        }
        return null;
    }

    /**
     * Generate Lampiran DOCX dari file DOCX RENJA Murni yang di-upload.
     * Proses: buang front matter (Cover, Kata Pengantar, Daftar Isi, dst)
     * → tambah header Lampiran → apply format F4/Bookman/no-bold.
     */
    protected function generateLampiranFromUploadedParent(
        RenjaDocument $lampiranDoc,
        RenjaDocument $parentDoc,
        string $parentDocxPath,
        string $outputDocxPath
    ): bool {
        $stripScript = base_path('scripts/strip_lampiran.py');
        if (!file_exists($stripScript)) {
            return false;
        }

        $tempDir = storage_path('app/temp_docs');
        $romawiHeader = \App\Services\RenjaAutoFixService::getRomanHeaderForOpd($lampiranDoc->opd);
        $isPerubahan = str_contains(strtolower($lampiranDoc->jenis_dokumen ?? ''), 'perubahan')
            || ($lampiranDoc->template?->code === 'RENJA_LAMPIRAN_PERUBAHAN');

        $meta = [
            'source_docx_path'      => $parentDocxPath,
            'nomor_lampiran_romawi' => $romawiHeader,
            'tahun_anggaran'        => $lampiranDoc->tahun_anggaran ?? $parentDoc->tahun_anggaran ?? date('Y'),
            'is_perubahan'          => $isPerubahan,
            'opd_name'              => $lampiranDoc->opd?->nama_opd ?? 'OPD',
        ];

        $metaJsonPath = $tempDir . '/meta_lampiran_' . $lampiranDoc->id . '_' . time() . '.json';
        file_put_contents($metaJsonPath, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $cmd = sprintf(
            'python %s %s %s 2>&1',
            escapeshellarg($stripScript),
            escapeshellarg($metaJsonPath),
            escapeshellarg($outputDocxPath)
        );

        $pyOut = [];
        $pyReturn = 1;
        exec($cmd, $pyOut, $pyReturn);
        @unlink($metaJsonPath);

        \Illuminate\Support\Facades\Log::info('strip_lampiran.py output', [
            'return' => $pyReturn,
            'output' => implode("\n", $pyOut),
        ]);

        return $pyReturn === 0 && file_exists($outputDocxPath) && filesize($outputDocxPath) > 5000;
    }

    /**
     * Generate temporary high-fidelity DOCX for template-based documents before rendering to PDF.
     */
    protected function generateDocxFromSections(RenjaDocument $document): ?string
    {
        $tempDir = storage_path('app/temp_docs');
        if (!file_exists($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        $filenameDocx = 'temp_render_' . $document->id . '_' . time() . '.docx';
        $docxTempPath = $tempDir . '/' . $filenameDocx;

        // ─── Jalur Khusus: Lampiran dengan RENJA Murni bertipe upload_word ───
        // Sections di database kosong → konten ada di file DOCX sumber.
        // Gunakan strip_lampiran.py untuk membaca DOCX asli dan buang front matter.
        $isLampiranDoc = $document->isLampiranPerbub();
        if ($isLampiranDoc) {
            $parent = $document->getParentDocument();
            if ($parent && $parent->source_type === 'upload_word') {
                $parentDocxPath = $this->resolveUploadedDocxPath($parent);
                if ($parentDocxPath) {
                    $ok = $this->generateLampiranFromUploadedParent(
                        $document, $parent, $parentDocxPath, $docxTempPath
                    );
                    if ($ok) {
                        return $docxTempPath;
                    }
                }
            }
        }

        // Gunakan Python Generator Script Resmi F4 (sections-based)
        $pythonScript = base_path('scripts/generate_renja_docx.py');
        if (file_exists($pythonScript)) {
            $effectiveSections = $document->getEffectiveSections();

            $romawiHeader = \App\Services\RenjaAutoFixService::getRomanHeaderForOpd($document->opd);

            $payload = [
                'document_type' => $document->template?->code ?? $document->jenis_dokumen,
                'title' => $document->jenis_dokumen ?? 'RENJA Lampiran Perbub',
                'opd_name' => $document->opd?->nama_opd ?? 'OPD',
                'tahun_anggaran' => $document->tahun_anggaran ?? 2027,
                'nomor_lampiran_romawi' => $romawiHeader,
                'cover_data' => $isLampiranDoc ? null : $document->cover_data,
                'sections' => $effectiveSections->map(fn($s) => [
                    'bab_code' => $s->bab_code,
                    'bab_title' => $s->bab_title,
                    'sub_bab_code' => $s->sub_bab_code,
                    'sub_bab_title' => $s->sub_bab_title,
                    'content' => $isLampiranDoc ? \App\Services\RenjaAutoFixService::stripBoldForLampiran($s->content ?? '') : ($s->content ?? ''),
                ])->values()->toArray(),
            ];

            $jsonTempPath = $tempDir . '/payload_' . $document->id . '_' . time() . '.json';
            file_put_contents($jsonTempPath, json_encode($payload, JSON_UNESCAPED_UNICODE));

            $cmd = sprintf('python %s %s %s 2>&1', escapeshellarg($pythonScript), escapeshellarg($jsonTempPath), escapeshellarg($docxTempPath));
            $pyOut = [];
            $pyReturn = 1;
            exec($cmd, $pyOut, $pyReturn);

            @unlink($jsonTempPath);

            if ($pyReturn === 0 && file_exists($docxTempPath) && filesize($docxTempPath) > 500) {
                return $docxTempPath;
            }
        }

        // Fallback: Generate via PHPWord jika ada
        if (class_exists(\PhpOffice\PhpWord\PhpWord::class) && class_exists(\ZipArchive::class)) {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection([
                'paperSize' => 'Folio',
                'marginTop' => 1134,
                'marginLeft' => 1134,
                'marginRight' => 1134,
                'marginBottom' => 1134,
            ]);

            foreach ($document->sections as $sec) {
                if (!empty($sec->bab_title)) {
                    $section->addTitle($sec->bab_code . ' ' . $sec->bab_title, 1);
                }
                if (!empty($sec->sub_bab_title)) {
                    $section->addTitle($sec->sub_bab_code . '. ' . $sec->sub_bab_title, 2);
                }
                if (!empty($sec->content)) {
                    \PhpOffice\PhpWord\Shared\Html::addHtml($section, $sec->content, false, false);
                }
            }

            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($docxTempPath);

            if (file_exists($docxTempPath) && filesize($docxTempPath) > 500) {
                return $docxTempPath;
            }
        }

        return null;
    }

    /**
     * Invalidate preview cache untuk dokumen tertentu.
     */
    public function invalidateCache(int $documentId): void
    {
        $cacheDirFull = storage_path('app/' . $this->cacheDir);
        if (!file_exists($cacheDirFull)) {
            return;
        }

        $pattern = $cacheDirFull . "/preview_*_{$documentId}_*.pdf";
        $files = glob($pattern);
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
}
