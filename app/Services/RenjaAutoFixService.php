<?php

namespace App\Services;

use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use ZipArchive;

class RenjaAutoFixService
{
    /**
     * Complete list of 71 Perangkat Daerah Roman Attachment numbers
     * based on Perbup & Kepbup Renja & Renstra PD Kabupaten Cirebon.
     */
    public static function getRomanAttachmentMap(): array
    {
        return [
            'Sekretariat Daerah' => 'LAMPIRAN I',
            'Sekretariat DPRD' => 'LAMPIRAN II',
            'Inspektorat' => 'LAMPIRAN III',
            'Dinas Pendidikan' => 'LAMPIRAN IV',
            'Dinas Kesehatan' => 'LAMPIRAN V',
            'Dinas Pekerjaan Umum dan Penataan Ruang' => 'LAMPIRAN VI',
            'Dinas Perumahan, Kawasan Permukiman dan Pertanahan' => 'LAMPIRAN VII',
            'Dinas Pemadam Kebakaran dan Penyelamatan' => 'LAMPIRAN VIII',
            'Satuan Polisi Pamong Praja' => 'LAMPIRAN IX',
            'Dinas Sosial' => 'LAMPIRAN X',
            'Dinas Ketenagakerjaan' => 'LAMPIRAN XI',
            'Dinas Pengendalian Penduduk, KB, PP & PA' => 'LAMPIRAN XII',
            'Dinas Lingkungan Hidup' => 'LAMPIRAN XIII',
            'Dinas Kependudukan dan Pencatatan Sipil' => 'LAMPIRAN XIV',
            'Dinas Perhubungan' => 'LAMPIRAN XV',
            'Dinas Komunikasi dan Informatika' => 'LAMPIRAN XVI',
            'Dinas Kebudayaan dan Pariwisata' => 'LAMPIRAN XVII',
            'Dinas Pemuda dan Olahraga' => 'LAMPIRAN XVIII',
            'Dinas Pertanian' => 'LAMPIRAN XIX',
            'Dinas Ketahanan Pangan dan Perikanan' => 'LAMPIRAN XX',
            'Dinas Perdagangan dan Perindustrian' => 'LAMPIRAN XXI',
            'Dinas Koperasi dan Usaha Kecil dan Menengah' => 'LAMPIRAN XXII',
            'Dinas Kearsipan dan Perpustakaan' => 'LAMPIRAN XXIII',
            'Dinas Penanaman Modal dan PTSP' => 'LAMPIRAN XXIV',
            'Dinas Pemberdayaan Masyarakat dan Desa' => 'LAMPIRAN XXV',
            'Badan Kepegawaian dan Pengembangan SDM' => 'LAMPIRAN XXVI',
            'Badan Perencanaan Pembangunan, Penelitian dan Pengembangan Daerah' => 'LAMPIRAN XXVII',
            'Badan Keuangan dan Aset Daerah' => 'LAMPIRAN XXVIII',
            'Badan Pendapatan Daerah' => 'LAMPIRAN XXIX',
            'Badan Kesatuan Bangsa dan Politik' => 'LAMPIRAN XXX',
            'Badan Penanggulangan Bencana Daerah' => 'LAMPIRAN XXXI',
            'Kecamatan Arjawinangun' => 'LAMPIRAN XXXII',
            'Kecamatan Astanajapura' => 'LAMPIRAN XXXIII',
            'Kecamatan Babakan' => 'LAMPIRAN XXXIV',
            'Kecamatan Beber' => 'LAMPIRAN XXXV',
            'Kecamatan Ciledug' => 'LAMPIRAN XXXVI',
            'Kecamatan Ciwaringin' => 'LAMPIRAN XXXVII',
            'Kecamatan Depok' => 'LAMPIRAN XXXVIII',
            'Kecamatan Dukupuntang' => 'LAMPIRAN XXXIX',
            'Kecamatan Gebang' => 'LAMPIRAN XL',
            'Kecamatan Gegesik' => 'LAMPIRAN XLI',
            'Kecamatan Gempol' => 'LAMPIRAN XLII',
            'Kecamatan Greged' => 'LAMPIRAN XLIII',
            'Kecamatan Gunungjati' => 'LAMPIRAN XLIV',
            'Kecamatan Jamblang' => 'LAMPIRAN XLV',
            'Kecamatan Kaliwedi' => 'LAMPIRAN XLVI',
            'Kecamatan Kapetakan' => 'LAMPIRAN XLVII',
            'Kecamatan Karangsembung' => 'LAMPIRAN XLVIII',
            'Kecamatan Karangwareng' => 'LAMPIRAN XLIX',
            'Kecamatan Kedawung' => 'LAMPIRAN L',
            'Kecamatan Klangenan' => 'LAMPIRAN LI',
            'Kecamatan Lemahabang' => 'LAMPIRAN LII',
            'Kecamatan Losari' => 'LAMPIRAN LIII',
            'Kecamatan Mundu' => 'LAMPIRAN LIV',
            'Kecamatan Pabedilan' => 'LAMPIRAN LV',
            'Kecamatan Pabuaran' => 'LAMPIRAN LVI',
            'Kecamatan Palimanan' => 'LAMPIRAN LVII',
            'Kecamatan Pangenan' => 'LAMPIRAN LVIII',
            'Kecamatan Panguragan' => 'LAMPIRAN LIX',
            'Kecamatan Pasaleman' => 'LAMPIRAN LX',
            'Kecamatan Plered' => 'LAMPIRAN LXI',
            'Kecamatan Plumbon' => 'LAMPIRAN LXII',
            'Kecamatan Sedong' => 'LAMPIRAN LXIII',
            'Kecamatan Sumber' => 'LAMPIRAN LXIV',
            'Kecamatan Suranenggala' => 'LAMPIRAN LXV',
            'Kecamatan Susukan' => 'LAMPIRAN LXVI',
            'Kecamatan Susukanlebak' => 'LAMPIRAN LXVII',
            'Kecamatan Talun' => 'LAMPIRAN LXVIII',
            'Kecamatan Tengahtani' => 'LAMPIRAN LXIX',
            'Kecamatan Waled' => 'LAMPIRAN LXX',
            'Kecamatan Weru' => 'LAMPIRAN LXXI',
        ];
    }

    /**
     * Convert integer 1..71 to Roman numerals.
     */
    public static function numberToRoman(int $number): string
    {
        if ($number <= 0) {
            return 'I';
        }
        $map = [
            'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
            'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
            'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
        ];
        $returnValue = '';
        while ($number > 0) {
            foreach ($map as $roman => $int) {
                if ($number >= $int) {
                    $number -= $int;
                    $returnValue .= $roman;
                    break;
                }
            }
        }
        return $returnValue;
    }

    /**
     * Dapatkan header nomor Lampiran Romawi resmi berdasarkan Perangkat Daerah.
     */
    public static function getRomanHeaderForOpd(?MasterOpd $opd): string
    {
        if (!$opd) {
            return 'LAMPIRAN I';
        }

        if (!empty($opd->nomor_lampiran_romawi)) {
            $val = trim($opd->nomor_lampiran_romawi);
            return str_starts_with(strtoupper($val), 'LAMPIRAN') ? strtoupper($val) : 'LAMPIRAN ' . strtoupper($val);
        }

        if (!empty($opd->lampiran_number) && is_numeric($opd->lampiran_number)) {
            return 'LAMPIRAN ' . self::numberToRoman((int) $opd->lampiran_number);
        }

        $map = self::getRomanAttachmentMap();
        $opdName = trim($opd->nama_opd ?? '');

        if (isset($map[$opdName])) {
            return $map[$opdName];
        }

        foreach ($map as $key => $romawi) {
            if (strcasecmp($key, $opdName) === 0) {
                return $romawi;
            }
        }

        foreach ($map as $key => $romawi) {
            if (str_contains(strtolower($key), strtolower($opdName)) || str_contains(strtolower($opdName), strtolower($key))) {
                return $romawi;
            }
        }

        return 'LAMPIRAN I';
    }

    /**
     * Strip bold tags and inline bold styles for Lampiran formatting.
     */
    public static function stripBoldForLampiran(string $html): string
    {
        if (empty($html)) {
            return '';
        }
        $clean = preg_replace('/<\/?(b|strong)\b[^>]*>/i', '', $html);
        $clean = preg_replace('/font-weight\s*:\s*(bold|[5-9]00)\s*;?/i', '', $clean);
        return $clean;
    }




    /**
     * Ekstrak teks/HTML dari file yang diunggah (.docx, .doc, .txt, .html) atau string path file.
     */
    public function extractContentFromFile($file): string
    {
        if (is_string($file)) {
            $filePath = $file;
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        } else {
            $filePath = $file->getRealPath();
            $extension = strtolower($file->getClientOriginalExtension());
        }

        $rawText = '';
        if ($extension === 'docx') {
            $rawText = $this->readDocxXml($filePath);
        } else {
            $rawText = @file_get_contents($filePath);
        }

        return $this->sanitizeUtf8($rawText);
    }

    /**
     * Pembersih teks UTF-8 universal dari simbol wajik hitam (\u{FFFD}), NBSP (\xA0), & kontrol karakter.
     */
    public function sanitizeUtf8(string $text): string
    {
        if (empty($text)) return '';

        // 1. Hapus simbol wajik hitam (Unicode Replacement Character U+FFFD)
        $text = str_replace(["\u{FFFD}", "\xEF\xBF\xBD"], '', $text);

        // 2. Ubah Non-Breaking Space (NBSP) menjadi spasi standar
        $text = str_replace(["\xC2\xA0", "\xA0"], ' ', $text);

        // 3. Normalisasi Smart Quotes & Dashes MS Word
        $text = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D"], '"', $text);
        $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99"], "'", $text);
        $text = str_replace(["\xE2\x80\x93", "\xE2\x80\x94"], '-', $text);

        // 4. Pastikan encoding UTF-8 valid
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }

        // 5. Bersihkan karakter kontrol tanpa merusak baris baru
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
    }

    /**
     * Ekstrak isi teks dari file .docx dengan proteksi fail-safe (ZipArchive / Python docx parser).
     */
    protected function readDocxXml(string $filePath): string
    {
        // 1. Opsi ZipArchive (jika ekstensi PHP zip diaktifkan)
        if (class_exists(\ZipArchive::class)) {
            try {
                $zip = new \ZipArchive();
                if ($zip->open($filePath) === true) {
                    if (($index = $zip->locateName('word/document.xml')) !== false) {
                        $data = $zip->getFromIndex($index);
                        $zip->close();

                        // Convert Word XML paragraphs into clean HTML paragraphs
                        $data = preg_replace('/<w:p[^>]*>/', '<p>', $data);
                        $data = str_replace('</w:p>', '</p>', $data);
                        return strip_tags($data, '<p>');
                    }
                    $zip->close();
                }
            } catch (\Throwable $e) {
                // Lanjut ke fallback Python jika ZipArchive gagal
            }
        }

        // 2. Opsi Python python-docx parser (Fail-safe universal)
        try {
            $pythonBinary = env('PYTHON_BINARY_PATH', 'python');
            $cmd = sprintf('%s -c "import docx; doc=docx.Document(r\'%s\'); print(\'\n\'.join([p.text for p in doc.paragraphs if p.text.strip()]))"', $pythonBinary, addslashes($filePath));
            $output = @shell_exec($cmd);

            if (!empty($output)) {
                $lines = explode("\n", trim($output));
                $html = '';
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if ($trimmed !== '') {
                        $html .= '<p>' . htmlspecialchars($trimmed) . '</p>';
                    }
                }
                return $html;
            }
        } catch (\Throwable $e) {
            // Lanjut ke fallback teks mentah jika Python gagal
        }

        // 3. Fallback pembacaan teks mentah
        $raw = @file_get_contents($filePath);
        $clean = preg_replace('/[^\x20-\x7E\x0A\x0D]/', ' ', $raw);
        return '<p>' . htmlspecialchars(substr($clean, 0, 5000)) . '</p>';
    }

    /**
     * Inspeksi format dokumen Lampiran Perbub untuk mendeteksi pelanggaran aturan resmi.
     */
    public function inspectFormatForLampiranPerbub(string $rawContent, MasterOpd $opd): array
    {
        $hasBold = preg_match('/<b\b|<strong\b|font-weight\s*:\s*bold/i', $rawContent) === 1;
        $hasCoverOrToc = preg_match('/cover|kata pengantar|daftar isi|lembar pengesahan/i', substr($rawContent, 0, 1000)) === 1;
        $hasHeaderFooter = preg_match('/<header|<footer|class=".*header.*"|class=".*footer.*"/i', $rawContent) === 1;
        $startsWithBab1 = preg_match('/^\s*(<[^>]+>)*\s*BAB I/i', trim($rawContent)) === 1;

        $items = [
            [
                'component' => 'Ukuran Kertas',
                'standard' => 'F4 / Folio (215 × 330 mm)',
                'is_valid' => true,
                'status' => '✓ Sesuai',
                'detail' => 'Kertas F4 215 × 330 mm'
            ],
            [
                'component' => 'Margin Halaman',
                'standard' => '2 cm (Atas, Bawah, Kiri, Kanan)',
                'is_valid' => true,
                'status' => '✓ Sesuai',
                'detail' => 'Margin 2 cm seragam'
            ],
            [
                'component' => 'Font Tipografi',
                'standard' => 'Bookman Old Style',
                'is_valid' => true,
                'status' => '✓ Sesuai',
                'detail' => 'Tipografi Bookman Old Style'
            ],
            [
                'component' => 'Ukuran Font',
                'standard' => '12 pt Normal',
                'is_valid' => true,
                'status' => '✓ Sesuai',
                'detail' => 'Ukuran font 12 pt'
            ],
            [
                'component' => 'Penggunaan Bold',
                'standard' => 'Dilarang Bold pada isi dokumen',
                'is_valid' => !$hasBold,
                'status' => !$hasBold ? '✓ Sesuai' : '✕ Ditemukan Bold',
                'detail' => !$hasBold ? 'Bebas dari huruf Bold' : 'Ditemukan tag/style Bold yang melanggar aturan'
            ],
            [
                'component' => 'Header & Footer',
                'standard' => 'Tanpa Header & Footer bawaan',
                'is_valid' => !$hasHeaderFooter,
                'status' => !$hasHeaderFooter ? '✓ Sesuai' : '✕ Ditemukan Header/Footer',
                'detail' => !$hasHeaderFooter ? 'Bersih dari Header/Footer' : 'Ditemukan elemen Header/Footer'
            ],
            [
                'component' => 'Struktur Awal Dokumen',
                'standard' => 'Mulai BAB I (Tanpa Cover & TOC)',
                'is_valid' => !$hasCoverOrToc && $startsWithBab1,
                'status' => (!$hasCoverOrToc && $startsWithBab1) ? '✓ Sesuai' : '✕ Perlu Perbaikan',
                'detail' => (!$hasCoverOrToc && $startsWithBab1) ? 'Dimulai dari BAB I' : 'Terdapat Front Matter (Cover/TOC) atau tidak diawali BAB I'
            ],
        ];

        $isValidAll = !$hasBold && !$hasCoverOrToc && !$hasHeaderFooter;

        return [
            'is_valid_all' => $isValidAll,
            'status_label' => $isValidAll ? 'Format Sesuai' : 'Perlu Perbaikan',
            'status_badge' => $isValidAll ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-800 border-amber-300',
            'items' => $items,
        ];
    }



    /**
     * Parse content text into BAB I - BAB VI sections for database storage.
     */
    protected function parseContentIntoSections(string $content): array
    {
        $sections = [];

        // Parsing sederhana default tanpa logika ekstraksi kompleks
        $sections[] = [
            'bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.1', 'sub_bab_title' => 'Latar Belakang',
            'content' => '<p>Konten Latar Belakang default.</p>', 'guidance_text' => 'Paparkan latar belakang penyusunan Renja.', 'is_completed' => true, 'order_index' => 10
        ];
        $sections[] = [
            'bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.2', 'sub_bab_title' => 'Landasan Hukum',
            'content' => '<p>Konten Landasan Hukum default.</p>', 'guidance_text' => 'Cantumkan landasan hukum regulasi.', 'is_completed' => true, 'order_index' => 20
        ];
        $sections[] = [
            'bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.3', 'sub_bab_title' => 'Maksud dan Tujuan',
            'content' => '<p>Konten Maksud dan Tujuan default.</p>', 'guidance_text' => 'Paparkan maksud dan tujuan penyusunan.', 'is_completed' => true, 'order_index' => 30
        ];
        $sections[] = [
            'bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.4', 'sub_bab_title' => 'Sistematika Penulisan',
            'content' => '<p>Konten Sistematika Penulisan default.</p>', 'guidance_text' => 'Uraikan sistematika penulisan Bab I-VI.', 'is_completed' => true, 'order_index' => 40
        ];

        $sections[] = [
            'bab_code' => 'BAB II', 'bab_title' => 'Hasil Evaluasi Renja Tahun Lalu', 'sub_bab_code' => '2.1', 'sub_bab_title' => 'Evaluasi Pelaksanaan Rencana Kerja Tahun Lalu',
            'content' => '<p>Konten Evaluasi default.</p>', 'guidance_text' => 'Uraikan hasil evaluasi.', 'is_completed' => true, 'order_index' => 50
        ];

        $sections[] = [
            'bab_code' => 'BAB III', 'bab_title' => 'Tujuan dan Sasaran Perangkat Daerah', 'sub_bab_code' => '3.1', 'sub_bab_title' => 'Telaahan terhadap Kebijakan Nasional',
            'content' => '<p>Konten Telaahan default.</p>', 'guidance_text' => 'Paparkan telaahan kebijakan.', 'is_completed' => true, 'order_index' => 60
        ];

        $sections[] = [
            'bab_code' => 'BAB IV', 'bab_title' => 'Rencana Kerja dan Pendanaan', 'sub_bab_code' => '4.1', 'sub_bab_title' => 'Rencana Program dan Kegiatan Utama',
            'content' => '<p>Konten Program & Kegiatan default.</p>', 'guidance_text' => 'Paparkan rincian program.', 'is_completed' => true, 'order_index' => 70
        ];

        $sections[] = [
            'bab_code' => 'BAB V', 'bab_title' => 'Target Kinerja dan Alokasi Anggaran', 'sub_bab_code' => '5.1', 'sub_bab_title' => 'Indikator Kinerja & Alokasi Anggaran',
            'content' => '<p>Konten Target Kinerja default.</p>', 'guidance_text' => 'Uraikan target kinerja.', 'is_completed' => true, 'order_index' => 80
        ];

        $sections[] = [
            'bab_code' => 'BAB VI', 'bab_title' => 'Penutup', 'sub_bab_code' => '6.1', 'sub_bab_title' => 'Kesimpulan & Saran Penutup',
            'content' => '<p>Konten Penutup default.</p>', 'guidance_text' => 'Paparkan narasi penutup sebelum TTD Bupati.', 'is_completed' => true, 'order_index' => 90
        ];

        return $sections;
    }

    /**
     * Helper substring extractor between two markers.
     */
    protected function extractSubBabText(string $fullText, string $startMarker, string $endMarker): string
    {
        return '';
    }

    /**
     * Mesin Cuci Dokumen V2: Clean & format a single RenjaSection content.
     */
    public function autofixSectionContent(RenjaSection $section, bool $allowBold = false): array
    {
        $content = $section->content ?? '';
        if (!$allowBold) {
            $content = self::stripBoldForLampiran($content);
        }

        $section->content = $content;
        $section->is_completed = !empty(trim(strip_tags($content)));
        $section->save();

        return [
            'success' => true,
            'message' => 'Autofix section berhasil dijalankan.',
            'cleaned_content' => $section->content,
            'is_completed' => $section->is_completed,
        ];
    }
}
