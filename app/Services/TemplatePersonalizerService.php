<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\TemplateSection;
use App\Models\MasterOpd;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TemplatePersonalizerService
{
    protected DocumentTemplateService $templateService;

    public function __construct(DocumentTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Generate Blank Official Template DOCX for RENJA Murni or RENJA Perubahan.
     *
     * @param string $templateCode Code template (RENJA_MURNI, RENJA_PERUBAHAN)
     * @param array $payload ['opd_id' => int|null, 'opd_name' => string|null, 'tahun_anggaran' => int|string|null, 'logo_path' => string|null]
     * @return array ['success' => bool, 'file_path' => string, 'filename' => string, 'file_size' => int, 'template_code' => string, 'opd_name' => string, 'tahun_anggaran' => mixed]
     */
    public function generateBlankTemplateDocx(string $templateCode, array $payload = []): array
    {
        // 1. Pastikan template standar ter-seed
        $this->templateService->ensureStandardTemplatesSeeded();

        // 2. Normalisasi & Cari Master Template dari database (Single Source of Truth)
        $normalizedCode = strtoupper(trim($templateCode));
        if ($normalizedCode === 'RENJA') {
            $normalizedCode = 'RENJA_MURNI';
        }

        $template = DocumentTemplate::with(['sections' => function ($q) {
            $q->orderBy('sequence');
        }])->where('code', $normalizedCode)->where('is_active', true)->first();

        if (!$template) {
            // Fallback ke LIKE jika pencarian exact tidak ketemu
            $template = DocumentTemplate::with(['sections' => function ($q) {
                $q->orderBy('sequence');
            }])->where('code', 'LIKE', "%{$normalizedCode}%")->where('is_active', true)->first();
        }

        if (!$template) {
            throw new \InvalidArgumentException("Master Template dengan kode '{$templateCode}' tidak ditemukan atau tidak aktif.");
        }

        // 3. Resolusi Data Personalisasi OPD, Tahun Anggaran, dan Logo
        $opd = null;
        if (!empty($payload['opd_id'])) {
            $opd = MasterOpd::find($payload['opd_id']);
        }

        $opdName = $payload['opd_name'] ?? ($opd?->nama_opd ?? '{{NAMA_OPD}}');
        $tahunAnggaran = $payload['tahun_anggaran'] ?? '{{TAHUN_ANGGARAN}}';
        $logoPath = $payload['logo_path'] ?? null;

        // Validasi keberadaan logo file
        if ($logoPath && !file_exists($logoPath)) {
            $logoPath = null;
        }

        $isPerubahan = str_contains($normalizedCode, 'PERUBAHAN');
        $jenisJudul = $isPerubahan
            ? 'RENCANA KERJA (RENJA) PERUBAHAN'
            : 'RENCANA KERJA (RENJA) MURNI';

        $tahunText = ($tahunAnggaran !== '{{TAHUN_ANGGARAN}}') ? (string)$tahunAnggaran : '{{TAHUN_ANGGARAN}}';

        // 4. Bangun Cover Data
        $coverData = [
            'judul_dokumen' => "{$jenisJudul} TAHUN ANGGARAN {$tahunText}",
            'nama_opd' => $opdName,
            'tahun_anggaran' => $tahunText,
            'nama_pemda' => 'PEMERINTAH KABUPATEN CIREBON',
            'lokasi' => 'SUMBER',
            'tahun_terbit' => date('Y'),
        ];

        // 5. Bangun Payload Seksi dari Master Template (Database)
        $sectionsPayload = $this->buildSectionsPayload($template, [
            'opd_name' => $opdName,
            'tahun_anggaran' => $tahunText,
            'is_perubahan' => $isPerubahan,
            'jenis_judul' => $jenisJudul,
            'tahun_terbit' => date('Y'),
        ]);

        // 6. Siapkan Direktori Output Temporary
        $tempDir = storage_path('app/temp_docs');
        if (!file_exists($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        $filename = $this->buildFilename($normalizedCode, $opdName, $tahunAnggaran);
        $docxTempPath = $tempDir . '/' . uniqid('template_') . '_' . $filename;

        // 7. Generate DOCX File
        $fullPayload = [
            'document_type' => $template->code,
            'title' => $template->name,
            'opd_name' => $opdName,
            'tahun_anggaran' => $tahunText,
            'is_blank_template' => true,
            'logo_path' => $logoPath,
            'cover_data' => $coverData,
            'margins' => [
                'top_mm' => 20,
                'bottom_mm' => 20,
                'left_mm' => 20,
                'right_mm' => 20,
            ],
            'sections' => $sectionsPayload,
        ];

        $generated = $this->renderDocx($fullPayload, $docxTempPath);

        if (!$generated || !file_exists($docxTempPath) || filesize($docxTempPath) < 500) {
            throw new \RuntimeException("Gagal membuat file template DOCX untuk '{$template->code}'.");
        }

        return [
            'success' => true,
            'file_path' => $docxTempPath,
            'filename' => $filename,
            'file_size' => filesize($docxTempPath),
            'template_code' => $template->code,
            'opd_id' => $opd?->id,
            'opd_name' => $opdName,
            'tahun_anggaran' => $tahunAnggaran,
        ];
    }

    /**
     * Bangun daftar seksi untuk generator dari database master template.
     */
    protected function buildSectionsPayload(DocumentTemplate $template, array $context): array
    {
        $opdName = $context['opd_name'];
        $tahunAnggaran = $context['tahun_anggaran'];
        $isPerubahan = $context['is_perubahan'];
        $jenisJudul = $context['jenis_judul'];
        $tahunTerbit = $context['tahun_terbit'];

        $sectionsPayload = [];

        foreach ($template->sections as $sec) {
            $type = strtolower($sec->section_type ?? '');
            $code = strtoupper(trim($sec->code ?? ''));
            $title = $sec->title ?? '';

            // 1. Seksi Cover (sudah dihandle via cover_data)
            if ($type === 'cover' || $code === 'COVER') {
                continue;
            }

            // 2. Seksi Lembar Pengesahan
            if ($code === 'PENGESAHAN') {
                $content = $this->renderPengesahanHtml($opdName, $tahunAnggaran, $jenisJudul, $tahunTerbit);
                $sectionsPayload[] = [
                    'section_type' => 'preface',
                    'bab_code' => 'PENGESAHAN',
                    'bab_title' => 'Lembar Pengesahan',
                    'sub_bab_code' => '',
                    'sub_bab_title' => '',
                    'page_break_before' => true,
                    'content' => $content,
                ];
                continue;
            }

            // 3. Seksi Kata Pengantar
            if ($code === 'PREFACE') {
                $content = $this->renderKataPengantarHtml($opdName, $tahunAnggaran, $jenisJudul, $tahunTerbit);
                $sectionsPayload[] = [
                    'section_type' => 'preface',
                    'bab_code' => 'PREFACE',
                    'bab_title' => 'Kata Pengantar',
                    'sub_bab_code' => '',
                    'sub_bab_title' => '',
                    'page_break_before' => true,
                    'content' => $content,
                ];
                continue;
            }

            // 4. Daftar Isi, Daftar Tabel, Daftar Gambar (Auto / Index Sections)
            if (in_array($type, ['table_of_contents', 'list_of_tables', 'list_of_figures', 'list_of_charts', 'list_of_appendices']) ||
                in_array($code, ['TOC', 'LOT', 'LOF', 'LOC', 'LOA'])) {
                $content = '<p style="text-align: center; color: #64748B;"><i>[Daftar ini akan diperbarui secara otomatis pada Microsoft Word melalui menu References &gt; Update Table]</i></p>';
                $sectionsPayload[] = [
                    'section_type' => $type,
                    'bab_code' => $code,
                    'bab_title' => $title,
                    'sub_bab_code' => '',
                    'sub_bab_title' => '',
                    'page_break_before' => true,
                    'content' => $content,
                ];
                continue;
            }

            // 5. Seksi BAB (Chapter)
            if ($type === 'chapter' || str_starts_with($code, 'BAB ')) {
                $sectionsPayload[] = [
                    'section_type' => 'chapter',
                    'bab_code' => $code,
                    'bab_title' => $title,
                    'sub_bab_code' => '',
                    'sub_bab_title' => '',
                    'page_break_before' => true,
                    'content' => '',
                ];
                continue;
            }

            // 6. Seksi Sub-Bab (Subchapter)
            if ($type === 'subchapter' || $type === 'sub_chapter' || preg_match('/^\d+(\.\d+)+$/', $code)) {
                // Tentukan parent BAB code dari kode sub-bab (e.g. 1.1 -> BAB I, 4.2 -> BAB IV)
                $parentBabCode = $this->resolveParentBabCode($code);
                $parentBabTitle = $this->resolveParentBabTitle($parentBabCode, $isPerubahan);

                // Cek apakah sub-bab ini adalah tabel matriks (4.2 Matriks Rencana Kerja dan Pendanaan)
                if ($code === '4.2' || str_contains(strtolower($title), 'matriks')) {
                    $content = $this->renderMatriksTableHtml($opdName, $tahunAnggaran, $isPerubahan);
                } else {
                    $placeholderText = "[Isi uraian " . e($title) . "...]";
                    $guidance = !empty($sec->guidance_text) ? "<p style=\"color: #64748B;\"><i>Petunjuk: " . e($sec->guidance_text) . "</i></p>" : '';
                    $content = $guidance . "<p style=\"text-align: justify;\">{$placeholderText}</p>";
                }

                $sectionsPayload[] = [
                    'section_type' => 'subchapter',
                    'bab_code' => $parentBabCode,
                    'bab_title' => $parentBabTitle,
                    'sub_bab_code' => $code,
                    'sub_bab_title' => $title,
                    'page_break_before' => (bool)$sec->page_break_before,
                    'content' => $content,
                ];
                continue;
            }

            // 7. Seksi Lampiran (Appendix)
            if ($type === 'appendix' || str_contains($code, 'LAMPIRAN')) {
                $content = '<p style="text-align: justify;">[Lampiran tabel matriks pendukung dan dokumen lainnya...]</p>';
                $sectionsPayload[] = [
                    'section_type' => 'appendix',
                    'bab_code' => 'LAMPIRAN',
                    'bab_title' => $title,
                    'sub_bab_code' => '',
                    'sub_bab_title' => '',
                    'page_break_before' => true,
                    'content' => $content,
                ];
                continue;
            }
        }

        return $sectionsPayload;
    }

    /**
     * Render HTML untuk Lembar Pengesahan.
     */
    protected function renderPengesahanHtml(string $opdName, string $tahunAnggaran, string $jenisJudul, string $tahunTerbit): string
    {
        return <<<HTML
<p style="text-align: center;"><b>LEMBAR PENGESAHAN</b></p>
<p style="text-align: center;"><b>DOKUMEN {$jenisJudul}</b></p>
<p style="text-align: center;"><b>{$opdName}</b></p>
<p style="text-align: center;"><b>TAHUN ANGGARAN {$tahunAnggaran}</b></p>
<p><br></p>
<p style="text-align: justify;">Dokumen {$jenisJudul} {$opdName} Kabupaten Cirebon Tahun Anggaran {$tahunAnggaran} telah disusun sesuai dengan ketentuan peraturan perundang-undangan yang berlaku dan disahkan untuk dipergunakan sebagaimana mestinya.</p>
<p><br></p>
<p style="margin-left: 10cm; text-align: center;">Sumber, ................................. {$tahunTerbit}</p>
<p style="margin-left: 10cm; text-align: center;"><b>Kepala {$opdName}</b></p>
<p style="margin-left: 10cm; text-align: center;"><b>Kabupaten Cirebon</b></p>
<p><br><br><br></p>
<p style="margin-left: 10cm; text-align: center;"><b><u>(Nama Kepala Perangkat Daerah)</u></b></p>
<p style="margin-left: 10cm; text-align: center;">NIP. ...................................................</p>
HTML;
    }

    /**
     * Render HTML untuk Kata Pengantar.
     */
    protected function renderKataPengantarHtml(string $opdName, string $tahunAnggaran, string $jenisJudul, string $tahunTerbit): string
    {
        return <<<HTML
<p style="text-align: center;"><b>KATA PENGANTAR</b></p>
<p><br></p>
<p style="text-align: justify;">Puji dan syukur kami panjatkan ke hadirat Allah SWT, Tuhan Yang Maha Esa, karena atas rahmat dan karunia-Nya, Dokumen {$jenisJudul} {$opdName} Kabupaten Cirebon Tahun Anggaran {$tahunAnggaran} dapat diselesaikan dengan baik.</p>
<p style="text-align: justify;">[Isi narasi pengantar, uraian singkat rencana kerja, serta apresiasi kepada seluruh pihak yang terlibat dalam penyusunan dokumen ini...]</p>
<p style="text-align: justify;">Semoga dokumen ini dapat menjadi pedoman yang efektif dalam pelaksanaan program, kegiatan, dan sub kegiatan serta pencapaian sasaran pembangunan daerah Kabupaten Cirebon.</p>
<p><br></p>
<p style="margin-left: 10cm; text-align: center;">Sumber, ................................. {$tahunTerbit}</p>
<p style="margin-left: 10cm; text-align: center;"><b>Kepala {$opdName}</b></p>
<p><br><br><br></p>
<p style="margin-left: 10cm; text-align: center;"><b><u>(Nama Kepala Perangkat Daerah)</u></b></p>
<p style="margin-left: 10cm; text-align: center;">NIP. ...................................................</p>
HTML;
    }

    /**
     * Render HTML untuk Tabel Matriks Rencana Kerja dan Pendanaan (4.2).
     */
    protected function renderMatriksTableHtml(string $opdName, string $tahunAnggaran, bool $isPerubahan): string
    {
        $judulTabel = $isPerubahan
            ? "Tabel 4.1 Matriks Perubahan Rencana Kerja dan Pendanaan {$opdName} Tahun Anggaran {$tahunAnggaran}"
            : "Tabel 4.1 Matriks Rencana Kerja dan Pendanaan {$opdName} Tahun Anggaran {$tahunAnggaran}";

        return <<<HTML
<p style="text-align: justify;"><b>{$judulTabel}</b></p>
<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse;">
  <thead>
    <tr style="background-color: #E2E8F0; text-align: center; font-weight: bold;">
      <th style="width: 5%;">No</th>
      <th style="width: 15%;">Kode Nomenklatur</th>
      <th style="width: 30%;">Program / Kegiatan / Sub Kegiatan</th>
      <th style="width: 20%;">Indikator Kinerja</th>
      <th style="width: 10%;">Target</th>
      <th style="width: 10%;">Satuan</th>
      <th style="width: 10%;">Pagu Indikatif (Rp)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="text-align: center;">1</td>
      <td>X.XX.XX.X.XX.XXXX</td>
      <td>[Program / Kegiatan / Sub Kegiatan]</td>
      <td>[Indikator Kinerja Output]</td>
      <td style="text-align: center;">-</td>
      <td style="text-align: center;">-</td>
      <td style="text-align: right;">0</td>
    </tr>
  </tbody>
</table>
<p><br></p>
HTML;
    }

    /**
     * Tentukan parent BAB code dari kode sub-bab (misal 1.1 -> BAB I).
     */
    protected function resolveParentBabCode(string $subCode): string
    {
        $firstDigit = explode('.', $subCode)[0] ?? '1';
        return match ($firstDigit) {
            '1' => 'BAB I',
            '2' => 'BAB II',
            '3' => 'BAB III',
            '4' => 'BAB IV',
            '5' => 'BAB V',
            '6' => 'BAB VI',
            '7' => 'BAB VII',
            default => 'BAB I',
        };
    }

    /**
     * Tentukan parent BAB Title standar.
     */
    protected function resolveParentBabTitle(string $babCode, bool $isPerubahan): string
    {
        return match ($babCode) {
            'BAB I' => 'Pendahuluan',
            'BAB II' => 'Hasil Evaluasi Renja Perangkat Daerah Tahun Lalu',
            'BAB III' => 'Tujuan dan Sasaran Perangkat Daerah',
            'BAB IV' => 'Rencana Kerja dan Pendanaan Perangkat Daerah',
            'BAB V' => 'Penutup',
            default => 'Bagian Dokumen',
        };
    }

    /**
     * Render file DOCX via Python Engine atau fallback PHPWord.
     */
    protected function renderDocx(array $payload, string $outputPath): bool
    {
        // 1. PRIMARY ENGINE: scripts/generate_renja_docx.py
        $pythonScript = base_path('scripts/generate_renja_docx.py');
        if (file_exists($pythonScript)) {
            $tempDir = storage_path('app/temp_docs');
            $jsonTempPath = $tempDir . '/payload_tpl_' . uniqid() . '_' . time() . '.json';
            file_put_contents($jsonTempPath, json_encode($payload, JSON_UNESCAPED_UNICODE));

            $cmd = sprintf('python %s %s %s 2>&1', escapeshellarg($pythonScript), escapeshellarg($jsonTempPath), escapeshellarg($outputPath));
            $pyOut = [];
            $pyReturn = 1;
            exec($cmd, $pyOut, $pyReturn);

            @unlink($jsonTempPath);

            if ($pyReturn === 0 && file_exists($outputPath) && filesize($outputPath) > 500) {
                return true;
            }

            Log::warning("[TemplatePersonalizerService] Python generator fallback triggered. Out: " . implode("\n", $pyOut));
        }

        // 2. SECONDARY ENGINE: Native PHPWord Fallback
        if (class_exists(\PhpOffice\PhpWord\PhpWord::class) && class_exists(\ZipArchive::class)) {
            try {
                $phpWord = new \PhpOffice\PhpWord\PhpWord();

                $section = $phpWord->addSection([
                    'paperSize' => 'Folio',
                    'marginTop' => 1134,
                    'marginLeft' => 1134,
                    'marginRight' => 1134,
                    'marginBottom' => 1134,
                ]);

                // Cover
                if (!empty($payload['cover_data'])) {
                    $section->addTitle($payload['cover_data']['judul_dokumen'] ?? 'DOKUMEN RENJA', 1);
                    $section->addText($payload['opd_name'] ?? '{{NAMA_OPD}}', ['bold' => true, 'size' => 14]);
                    $section->addText('TAHUN ANGGARAN ' . ($payload['tahun_anggaran'] ?? '2027'), ['bold' => true, 'size' => 12]);
                    $section->addPageBreak();
                }

                // Sections
                foreach ($payload['sections'] as $sec) {
                    if (!empty($sec['bab_title'])) {
                        $section->addTitle(($sec['bab_code'] ?? '') . ' ' . $sec['bab_title'], 1);
                    }
                    if (!empty($sec['sub_bab_title'])) {
                        $section->addTitle(($sec['sub_bab_code'] ?? '') . '. ' . $sec['sub_bab_title'], 2);
                    }
                    if (!empty($sec['content'])) {
                        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $sec['content'], false, false);
                    }
                }

                $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save($outputPath);

                return file_exists($outputPath) && filesize($outputPath) > 500;
            } catch (\Throwable $e) {
                Log::error("[TemplatePersonalizerService] PHPWord fallback error: " . $e->getMessage());
            }
        }

        return false;
    }

    /**
     * Bangun nama file template yang aman dan terstandarisasi.
     * Contoh: RENJA_MURNI_DINAS_KOMUNIKASI_DAN_INFORMATIKA_2027.docx
     */
    public function buildFilename(string $templateCode, string $opdName, $tahunAnggaran): string
    {
        $prefix = strtoupper(str_replace('-', '_', $templateCode));
        if ($prefix === 'RENJA') {
            $prefix = 'RENJA_MURNI';
        }

        $cleanOpd = trim($opdName);
        if ($cleanOpd === '{{NAMA_OPD}}' || empty($cleanOpd)) {
            $safeOpd = 'TEMPLATE';
        } else {
            $safeOpd = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $cleanOpd));
            $safeOpd = trim($safeOpd, '_');
        }

        $cleanTahun = (string)$tahunAnggaran;
        if ($cleanTahun === '{{TAHUN_ANGGARAN}}' || empty($cleanTahun)) {
            $safeTahun = 'TAHUN';
        } else {
            $safeTahun = preg_replace('/[^A-Za-z0-9]+/', '_', $cleanTahun);
        }

        return "{$prefix}_{$safeOpd}_{$safeTahun}.docx";
    }
}
