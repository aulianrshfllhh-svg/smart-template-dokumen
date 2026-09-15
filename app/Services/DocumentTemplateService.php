<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\TemplateSection;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use Illuminate\Support\Facades\DB;

class DocumentTemplateService
{
    /**
     * Pastikan 3 Template Standar (RENJA, RKPD, EVALUASI_RKPD) terdaftar di database.
     */
    /**
     * Pastikan Master Template Standar (RENJA_MURNI dan RENJA) terdaftar di database.
     */
    public function ensureStandardTemplatesSeeded()
    {
        // 1. MASTER TEMPLATE RENJA MURNI (Resmi Pemerintah Kabupaten Cirebon)
        $renjaMurni = DocumentTemplate::firstOrCreate(
            ['code' => 'RENJA_MURNI'],
            [
                'name' => 'Template RENJA Murni',
                'description' => 'Master Template Resmi Dokumen Rencana Kerja (RENJA) Murni Pemerintah Kabupaten Cirebon lengkap dengan Cover, Bagian Awal, BAB I-V, dan Lampiran.',
                'format_config' => [
                    'paper_width_mm' => 215,
                    'paper_height_mm' => 330,
                    'margin_top_cm' => 2,
                    'margin_right_cm' => 2,
                    'margin_bottom_cm' => 2,
                    'margin_left_cm' => 2,
                    'font_family' => 'Bookman Old Style',
                    'font_size_pt' => 12,
                    'allow_bold' => false,
                    'has_header' => true,
                    'has_footer' => true,
                    'has_page_number' => true,
                    'has_front_sections' => true,
                    'has_cover' => true,
                ],
                'toolbar_config' => [
                    'bold' => false,
                    'italic' => true,
                    'underline' => true,
                    'align_left' => true,
                    'align_center' => true,
                    'align_right' => true,
                    'justify' => true,
                    'bullets' => true,
                    'numbering' => true,
                    'tables' => true,
                    'images' => true,
                    'captions' => true,
                    'add_subbab' => true,
                    'edit_subbab' => true,
                    'edit_bab' => true,
                    'header_footer' => true,
                    'page_break' => true,
                    'cover_editor' => true,
                    'automatic_lists' => true,
                    'update_lists' => true,
                ],
                'validation_config' => [
                    'require_cover' => true,
                    'require_preface' => true,
                    'require_toc' => true,
                    'require_all_chapters' => true,
                    'forbid_bold' => true,
                    'require_captions' => true,
                ],
                'is_active' => true,
            ]
        );

        if ($renjaMurni->wasRecentlyCreated || $renjaMurni->sections()->count() === 0) {
            $this->seedRenjaMurniSections($renjaMurni);
        }

        // 2. MASTER TEMPLATE RENJA PERUBAHAN (Resmi Pemerintah Kabupaten Cirebon)
        $renjaPerubahan = DocumentTemplate::firstOrCreate(
            ['code' => 'RENJA_PERUBAHAN'],
            [
                'name' => 'Template RENJA Perubahan',
                'description' => 'Master Template Resmi Dokumen Perubahan Rencana Kerja (RENJA Perubahan) Pemerintah Kabupaten Cirebon lengkap dengan Cover, Bagian Awal, BAB I-V, dan Lampiran.',
                'format_config' => [
                    'paper_width_mm' => 215,
                    'paper_height_mm' => 330,
                    'margin_top_cm' => 2,
                    'margin_right_cm' => 2,
                    'margin_bottom_cm' => 2,
                    'margin_left_cm' => 2,
                    'font_family' => 'Bookman Old Style',
                    'font_size_pt' => 12,
                    'allow_bold' => false,
                    'has_header' => true,
                    'has_footer' => true,
                    'has_page_number' => true,
                    'has_front_sections' => true,
                    'has_cover' => true,
                ],
                'toolbar_config' => [
                    'bold' => false,
                    'italic' => true,
                    'underline' => true,
                    'align_left' => true,
                    'align_center' => true,
                    'align_right' => true,
                    'justify' => true,
                    'bullets' => true,
                    'numbering' => true,
                    'tables' => true,
                    'images' => true,
                    'captions' => true,
                    'add_subbab' => true,
                    'edit_subbab' => true,
                    'edit_bab' => true,
                    'header_footer' => true,
                    'page_break' => true,
                    'cover_editor' => true,
                    'automatic_lists' => true,
                    'update_lists' => true,
                ],
                'validation_config' => [
                    'require_cover' => true,
                    'require_preface' => true,
                    'require_toc' => true,
                    'require_all_chapters' => true,
                    'forbid_bold' => true,
                    'require_captions' => true,
                ],
                'is_active' => true,
            ]
        );

        if ($renjaPerubahan->wasRecentlyCreated || $renjaPerubahan->sections()->count() === 0) {
            $this->seedRenjaPerubahanSections($renjaPerubahan);
        }

        // 3. TEMPLATE RENJA SKPD (Legacy alias)
        $renja = DocumentTemplate::firstOrCreate(
            ['code' => 'RENJA'],
            [
                'name' => 'Rencana Kerja Perangkat Daerah (Renja)',
                'description' => 'Dokumen Rencana Kerja Perangkat Daerah struktur lengkap standar Perbup Cirebon.',
                'format_config' => [
                    'paper_width_mm' => 215,
                    'paper_height_mm' => 330,
                    'margin_top_cm' => 2,
                    'margin_right_cm' => 2,
                    'margin_bottom_cm' => 2,
                    'margin_left_cm' => 2,
                    'font_family' => 'Bookman Old Style',
                    'font_size_pt' => 12,
                    'allow_bold' => false,
                    'has_header' => true,
                    'has_footer' => true,
                    'has_page_number' => true,
                    'has_front_sections' => true,
                    'has_cover' => true,
                ],
                'toolbar_config' => [
                    'bold' => false,
                    'italic' => true,
                    'underline' => true,
                    'align_left' => true,
                    'align_center' => true,
                    'align_right' => true,
                    'justify' => true,
                    'bullets' => true,
                    'numbering' => true,
                    'tables' => true,
                    'images' => true,
                    'captions' => true,
                    'add_subbab' => true,
                    'edit_subbab' => true,
                    'edit_bab' => true,
                    'header_footer' => true,
                    'page_break' => true,
                    'cover_editor' => true,
                    'automatic_lists' => true,
                    'update_lists' => true,
                ],
                'validation_config' => [
                    'require_cover' => true,
                    'require_preface' => true,
                    'require_toc' => true,
                    'require_all_chapters' => true,
                    'forbid_bold' => true,
                    'require_captions' => true,
                ],
                'is_active' => true,
            ]
        );

        if ($renja->wasRecentlyCreated || $renja->sections()->count() === 0) {
            $this->seedRenjaMurniSections($renja);
        }

        // 4. MASTER TEMPLATE RENJA LAMPIRAN MURNI
        $lampiranMurni = DocumentTemplate::firstOrCreate(
            ['code' => 'RENJA_LAMPIRAN_MURNI'],
            [
                'name' => 'Template Lampiran RENJA Murni',
                'description' => 'Master struktur resmi dokumen Lampiran Peraturan Bupati (Perbub) yang digunakan untuk penyusunan dan kompilasi dokumen RENJA Murni Perangkat Daerah Kabupaten Cirebon.',
                'format_config' => [
                    'paper_width_mm' => 215,
                    'paper_height_mm' => 330,
                    'margin_top_cm' => 2,
                    'margin_right_cm' => 2,
                    'margin_bottom_cm' => 2,
                    'margin_left_cm' => 2,
                    'font_family' => 'Bookman Old Style',
                    'font_size_pt' => 12,
                    'allow_bold' => false,
                    'has_header' => false,
                    'has_footer' => false,
                    'has_page_number' => true,
                    'has_front_sections' => false,
                    'has_cover' => false,
                ],
                'toolbar_config' => [
                    'bold' => false,
                    'italic' => true,
                    'underline' => true,
                    'align_left' => true,
                    'align_center' => true,
                    'align_right' => true,
                    'justify' => true,
                    'bullets' => true,
                    'numbering' => true,
                    'tables' => true,
                    'images' => true,
                    'captions' => true,
                    'add_subbab' => true,
                    'edit_subbab' => true,
                    'edit_bab' => true,
                    'header_footer' => false,
                    'page_break' => true,
                    'cover_editor' => false,
                    'automatic_lists' => true,
                    'update_lists' => true,
                ],
                'validation_config' => [
                    'require_cover' => false,
                    'require_preface' => false,
                    'require_toc' => false,
                    'require_all_chapters' => true,
                    'forbid_bold' => true,
                    'require_captions' => true,
                ],
                'is_active' => true,
            ]
        );

        if ($lampiranMurni->wasRecentlyCreated || $lampiranMurni->sections()->count() === 0) {
            $this->seedRenjaLampiranSections($lampiranMurni);
        }

        // 5. MASTER TEMPLATE RENJA LAMPIRAN PERUBAHAN
        $lampiranPerubahan = DocumentTemplate::firstOrCreate(
            ['code' => 'RENJA_LAMPIRAN_PERUBAHAN'],
            [
                'name' => 'Template Lampiran RENJA Perubahan',
                'description' => 'Master struktur resmi dokumen Lampiran Peraturan Bupati (Perbub) yang digunakan untuk penyusunan dan kompilasi dokumen RENJA Perubahan Perangkat Daerah Kabupaten Cirebon.',
                'format_config' => [
                    'paper_width_mm' => 215,
                    'paper_height_mm' => 330,
                    'margin_top_cm' => 2,
                    'margin_right_cm' => 2,
                    'margin_bottom_cm' => 2,
                    'margin_left_cm' => 2,
                    'font_family' => 'Bookman Old Style',
                    'font_size_pt' => 12,
                    'allow_bold' => false,
                    'has_header' => false,
                    'has_footer' => false,
                    'has_page_number' => true,
                    'has_front_sections' => false,
                    'has_cover' => false,
                ],
                'toolbar_config' => [
                    'bold' => false,
                    'italic' => true,
                    'underline' => true,
                    'align_left' => true,
                    'align_center' => true,
                    'align_right' => true,
                    'justify' => true,
                    'bullets' => true,
                    'numbering' => true,
                    'tables' => true,
                    'images' => true,
                    'captions' => true,
                    'add_subbab' => true,
                    'edit_subbab' => true,
                    'edit_bab' => true,
                    'header_footer' => false,
                    'page_break' => true,
                    'cover_editor' => false,
                    'automatic_lists' => true,
                    'update_lists' => true,
                ],
                'validation_config' => [
                    'require_cover' => false,
                    'require_preface' => false,
                    'require_toc' => false,
                    'require_all_chapters' => true,
                    'forbid_bold' => true,
                    'require_captions' => true,
                ],
                'is_active' => true,
            ]
        );

        if ($lampiranPerubahan->wasRecentlyCreated || $lampiranPerubahan->sections()->count() === 0) {
            $this->seedRenjaLampiranSections($lampiranPerubahan);
        }

        // Hapus template RKPD, EVALUASI_RKPD, dan RENJA_LAMPIRAN_PERBUB dari database per instruksi user
        DocumentTemplate::whereIn('code', ['RKPD', 'EVALUASI_RKPD', 'RENJA_LAMPIRAN_PERBUB'])->delete();
    }

    /**
     * Struktur Lengkap Master Template RENJA Lampiran Kabupaten Cirebon
     */
    public function seedRenjaLampiranSections(DocumentTemplate $template)
    {
        $template->sections()->delete();

        $sections = [
            // BAB I PENDAHULUAN
            ['type' => 'chapter', 'code' => 'BAB I', 'title' => 'Pendahuluan', 'seq' => 1, 'pbb' => false, 'auto' => false, 'guidance' => 'Bab I menguraikan latar belakang, landasan hukum, maksud dan tujuan, serta sistematika penulisan.'],
            ['type' => 'subchapter', 'code' => '1.1', 'title' => 'Latar Belakang', 'seq' => 2, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraikan latar belakang penyusunan Lampiran Perbub Perangkat Daerah.'],
            ['type' => 'subchapter', 'code' => '1.2', 'title' => 'Landasan Hukum', 'seq' => 3, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraikan peraturan perundang-undangan yang melandasi penyusunan Lampiran Perbub.'],
            ['type' => 'subchapter', 'code' => '1.3', 'title' => 'Maksud dan Tujuan', 'seq' => 4, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraikan maksud dan tujuan penyusunan dokumen Lampiran Perbub.'],
            ['type' => 'subchapter', 'code' => '1.4', 'title' => 'Sistematika Penulisan', 'seq' => 5, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraikan pokok bahasan dari Bab I sampai dengan Bab V.'],

            // BAB II HASIL EVALUASI RENJA
            ['type' => 'chapter', 'code' => 'BAB II', 'title' => 'Hasil Evaluasi Renja Perangkat Daerah Tahun Lalu', 'seq' => 6, 'pbb' => true, 'auto' => false, 'guidance' => 'Evaluasi capaian kinerja dan realisasi program/kegiatan tahun sebelumnya.'],
            ['type' => 'subchapter', 'code' => '2.1', 'title' => 'Evaluasi Pelaksanaan Renja Perangkat Daerah dan Capaian Renstra Perangkat Daerah', 'seq' => 7, 'pbb' => false, 'auto' => false, 'guidance' => 'Evaluasi kinerja pelaksanaan Renja tahun lalu beserta analisis capaian target Renstra.'],
            ['type' => 'subchapter', 'code' => '2.2', 'title' => 'Analisis Kinerja Pelayanan Perangkat Daerah', 'seq' => 8, 'pbb' => false, 'auto' => false, 'guidance' => 'Analisis pencapaian indikator kinerja pelayanan perangkat daerah terhadap SPM dan IKU.'],
            ['type' => 'subchapter', 'code' => '2.3', 'title' => 'Isu Penting Penyelenggaraan Tugas dan Fungsi Perangkat Daerah', 'seq' => 9, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraian permasalahan, hambatan, tantangan, dan peluang dalam pelaksanaan tupoksi.'],

            // BAB III TUJUAN DAN SASARAN
            ['type' => 'chapter', 'code' => 'BAB III', 'title' => 'Tujuan dan Sasaran Perangkat Daerah', 'seq' => 10, 'pbb' => true, 'auto' => false, 'guidance' => 'Perumusan tujuan dan sasaran strategis perangkat daerah untuk tahun anggaran bersangkutan.'],
            ['type' => 'subchapter', 'code' => '3.1', 'title' => 'Telaahan Terhadap Kebijakan Daerah', 'seq' => 11, 'pbb' => false, 'auto' => false, 'guidance' => 'Telaahan terhadap arah kebijakan pembangunan daerah dalam RPJMD / RPD dan RKPD Kabupaten Cirebon.'],
            ['type' => 'subchapter', 'code' => '3.2', 'title' => 'Tujuan dan Sasaran Renja Perangkat Daerah', 'seq' => 12, 'pbb' => false, 'auto' => false, 'guidance' => 'Tujuan dan sasaran yang hendak dicapai oleh perangkat daerah beserta indikator kinerjanya.'],
            ['type' => 'subchapter', 'code' => '3.3', 'title' => 'Program dan Kegiatan', 'seq' => 13, 'pbb' => false, 'auto' => false, 'guidance' => 'Daftar program, kegiatan, dan sub kegiatan yang mendukung pencapaian sasaran.'],

            // BAB IV RENCANA KERJA DAN PENDANAAN
            ['type' => 'chapter', 'code' => 'BAB IV', 'title' => 'Rencana Kerja dan Pendanaan Perangkat Daerah', 'seq' => 14, 'pbb' => true, 'auto' => false, 'guidance' => 'Rincian rencana program, kegiatan, indikator kinerja output/outcome, target capaian, dan pagu indikatif.'],
            ['type' => 'subchapter', 'code' => '4.1', 'title' => 'Rencana Kerja dan Pendanaan Perangkat Daerah', 'seq' => 15, 'pbb' => false, 'auto' => false, 'guidance' => 'Penjelasan naratif rencana kerja dan alokasi pagu pendanaan perangkat daerah.'],
            ['type' => 'subchapter', 'code' => '4.2', 'title' => 'Matriks Rencana Kerja dan Pendanaan Perangkat Daerah', 'seq' => 16, 'pbb' => false, 'auto' => false, 'guidance' => 'Tabel Matriks Rencana Kerja dan Pendanaan (Tabel 4.1 SIPD/e-Renja). Dapat diformat secara Landscape untuk tabel lebar.'],

            // BAB V PENUTUP
            ['type' => 'chapter', 'code' => 'BAB V', 'title' => 'Penutup', 'seq' => 17, 'pbb' => true, 'auto' => false, 'guidance' => 'Kaidah pelaksanaan dan kesimpulan akhir penyusunan Renja.'],
            ['type' => 'subchapter', 'code' => '5.1', 'title' => 'Kaidah Pelaksanaan', 'seq' => 18, 'pbb' => false, 'auto' => false, 'guidance' => 'Pedoman dan kaidah yang harus dipedomani dalam pelaksanaan program/kegiatan.'],
            ['type' => 'subchapter', 'code' => '5.2', 'title' => 'Kesimpulan', 'seq' => 19, 'pbb' => false, 'auto' => false, 'guidance' => 'Kesimpulan umum dokumen Renja Perangkat Daerah.'],

            // LAMPIRAN
            ['type' => 'appendix', 'code' => 'LAMPIRAN', 'title' => 'Lampiran Rencana Kerja Perangkat Daerah', 'seq' => 20, 'pbb' => true, 'auto' => false, 'guidance' => 'Lampiran matriks tabel dan dokumen pendukung lainnya.'],
        ];

        $currentChapterId = null;
        foreach ($sections as $s) {
            $created = TemplateSection::create([
                'template_id' => $template->id,
                'parent_id' => ($s['type'] === 'subchapter') ? $currentChapterId : null,
                'section_type' => $s['type'],
                'code' => $s['code'],
                'title' => $s['title'],
                'sequence' => $s['seq'],
                'page_break_before' => $s['pbb'],
                'is_automatic' => $s['auto'] ?? false,
                'is_editable' => false,
                'is_required' => true,
                'guidance_text' => $s['guidance'] ?? null,
            ]);

            if ($s['type'] === 'chapter') {
                $currentChapterId = $created->id;
            }
        }
    }

    /**
     * Struktur Lengkap Master Template RENJA Murni Kabupaten Cirebon
     */
    public function seedRenjaMurniSections(DocumentTemplate $template)
    {
        $template->sections()->delete();

        $sections = [
            // BAGIAN AWAL
            ['type' => 'cover', 'code' => 'COVER', 'title' => 'Cover Dokumen RENJA Murni', 'seq' => 1, 'pbb' => false, 'auto' => false, 'guidance' => 'Halaman sampul resmi dokumen RENJA Murni Perangkat Daerah.'],
            ['type' => 'preface', 'code' => 'PENGESAHAN', 'title' => 'Lembar Pengesahan', 'seq' => 2, 'pbb' => true, 'auto' => false, 'guidance' => 'Lembar pengesahan resmi yang ditandatangani Kepala Perangkat Daerah.'],
            ['type' => 'preface', 'code' => 'PREFACE', 'title' => 'Kata Pengantar', 'seq' => 3, 'pbb' => true, 'auto' => false, 'guidance' => 'Kata pengantar pimpinan perangkat daerah.'],
            ['type' => 'table_of_contents', 'code' => 'TOC', 'title' => 'Daftar Isi', 'seq' => 4, 'pbb' => true, 'auto' => true, 'guidance' => 'Daftar isi seluruh bab dan sub-bab dokumen.'],
            ['type' => 'list_of_tables', 'code' => 'LOT', 'title' => 'Daftar Tabel', 'seq' => 5, 'pbb' => true, 'auto' => true, 'guidance' => 'Daftar seluruh tabel dalam dokumen.'],
            ['type' => 'list_of_figures', 'code' => 'LOF', 'title' => 'Daftar Gambar', 'seq' => 6, 'pbb' => true, 'auto' => true, 'guidance' => 'Daftar seluruh gambar dan diagram dalam dokumen.'],

            // BAB I PENDAHULUAN
            ['type' => 'chapter', 'code' => 'BAB I', 'title' => 'Pendahuluan', 'seq' => 7, 'pbb' => true, 'auto' => false, 'guidance' => 'Bab I menguraikan latar belakang, landasan hukum, maksud dan tujuan, serta sistematika penulisan.'],
            ['type' => 'subchapter', 'code' => '1.1', 'title' => 'Latar Belakang', 'seq' => 8, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraikan latar belakang penyusunan Rencana Kerja (Renja) Perangkat Daerah.'],
            ['type' => 'subchapter', 'code' => '1.2', 'title' => 'Landasan Hukum', 'seq' => 9, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraikan peraturan perundang-undangan yang melandasi penyusunan Renja.'],
            ['type' => 'subchapter', 'code' => '1.3', 'title' => 'Maksud dan Tujuan', 'seq' => 10, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraikan maksud dan tujuan penyusunan dokumen Renja.'],
            ['type' => 'subchapter', 'code' => '1.4', 'title' => 'Sistematika Penulisan', 'seq' => 11, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraikan pokok bahasan dari Bab I sampai dengan Bab V.'],

            // BAB II HASIL EVALUASI RENJA
            ['type' => 'chapter', 'code' => 'BAB II', 'title' => 'Hasil Evaluasi Renja Perangkat Daerah Tahun Lalu', 'seq' => 12, 'pbb' => true, 'auto' => false, 'guidance' => 'Evaluasi capaian kinerja dan realisasi program/kegiatan tahun sebelumnya.'],
            ['type' => 'subchapter', 'code' => '2.1', 'title' => 'Evaluasi Pelaksanaan Renja Perangkat Daerah dan Capaian Renstra Perangkat Daerah', 'seq' => 13, 'pbb' => false, 'auto' => false, 'guidance' => 'Evaluasi kinerja pelaksanaan Renja tahun lalu beserta analisis capaian target Renstra.'],
            ['type' => 'subchapter', 'code' => '2.2', 'title' => 'Analisis Kinerja Pelayanan Perangkat Daerah', 'seq' => 14, 'pbb' => false, 'auto' => false, 'guidance' => 'Analisis pencapaian indikator kinerja pelayanan perangkat daerah terhadap SPM dan IKU.'],
            ['type' => 'subchapter', 'code' => '2.3', 'title' => 'Isu Penting Penyelenggaraan Tugas dan Fungsi Perangkat Daerah', 'seq' => 15, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraian permasalahan, hambatan, tantangan, dan peluang dalam pelaksanaan tupoksi.'],

            // BAB III TUJUAN DAN SASARAN
            ['type' => 'chapter', 'code' => 'BAB III', 'title' => 'Tujuan dan Sasaran Perangkat Daerah', 'seq' => 16, 'pbb' => true, 'auto' => false, 'guidance' => 'Perumusan tujuan dan sasaran strategis perangkat daerah untuk tahun anggaran bersangkutan.'],
            ['type' => 'subchapter', 'code' => '3.1', 'title' => 'Telaahan Terhadap Kebijakan Daerah', 'seq' => 17, 'pbb' => false, 'auto' => false, 'guidance' => 'Telaahan terhadap arah kebijakan pembangunan daerah dalam RPJMD / RPD dan RKPD Kabupaten Cirebon.'],
            ['type' => 'subchapter', 'code' => '3.2', 'title' => 'Tujuan dan Sasaran Renja Perangkat Daerah', 'seq' => 18, 'pbb' => false, 'auto' => false, 'guidance' => 'Tujuan dan sasaran yang hendak dicapai oleh perangkat daerah beserta indikator kinerjanya.'],
            ['type' => 'subchapter', 'code' => '3.3', 'title' => 'Program dan Kegiatan', 'seq' => 19, 'pbb' => false, 'auto' => false, 'guidance' => 'Daftar program, kegiatan, dan sub kegiatan yang mendukung pencapaian sasaran.'],

            // BAB IV RENCANA KERJA DAN PENDANAAN
            ['type' => 'chapter', 'code' => 'BAB IV', 'title' => 'Rencana Kerja dan Pendanaan Perangkat Daerah', 'seq' => 20, 'pbb' => true, 'auto' => false, 'guidance' => 'Rincian rencana program, kegiatan, indikator kinerja output/outcome, target capaian, dan pagu indikatif.'],
            ['type' => 'subchapter', 'code' => '4.1', 'title' => 'Rencana Kerja dan Pendanaan Perangkat Daerah', 'seq' => 21, 'pbb' => false, 'auto' => false, 'guidance' => 'Penjelasan naratif rencana kerja dan alokasi pagu pendanaan perangkat daerah.'],
            ['type' => 'subchapter', 'code' => '4.2', 'title' => 'Matriks Rencana Kerja dan Pendanaan Perangkat Daerah', 'seq' => 22, 'pbb' => false, 'auto' => false, 'guidance' => 'Tabel Matriks Rencana Kerja dan Pendanaan (Tabel 4.1 SIPD/e-Renja). Dapat diformat secara Landscape untuk tabel lebar.'],

            // BAB V PENUTUP
            ['type' => 'chapter', 'code' => 'BAB V', 'title' => 'Penutup', 'seq' => 23, 'pbb' => true, 'auto' => false, 'guidance' => 'Kaidah pelaksanaan dan kesimpulan akhir penyusunan Renja.'],
            ['type' => 'subchapter', 'code' => '5.1', 'title' => 'Kaidah Pelaksanaan', 'seq' => 24, 'pbb' => false, 'auto' => false, 'guidance' => 'Pedoman dan kaidah yang harus dipedomani dalam pelaksanaan program/kegiatan.'],
            ['type' => 'subchapter', 'code' => '5.2', 'title' => 'Kesimpulan', 'seq' => 25, 'pbb' => false, 'auto' => false, 'guidance' => 'Kesimpulan umum dokumen Renja Perangkat Daerah.'],

            // LAMPIRAN
            ['type' => 'appendix', 'code' => 'LAMPIRAN', 'title' => 'Lampiran Rencana Kerja Perangkat Daerah', 'seq' => 26, 'pbb' => true, 'auto' => false, 'guidance' => 'Lampiran matriks tabel dan dokumen pendukung lainnya.'],
        ];

        $currentChapterId = null;
        foreach ($sections as $s) {
            $created = TemplateSection::create([
                'template_id' => $template->id,
                'parent_id' => ($s['type'] === 'subchapter') ? $currentChapterId : null,
                'section_type' => $s['type'],
                'code' => $s['code'],
                'title' => $s['title'],
                'sequence' => $s['seq'],
                'page_break_before' => $s['pbb'],
                'is_automatic' => $s['auto'] ?? false,
                'is_editable' => false, // Struktur canonical locked
                'is_required' => true,
                'guidance_text' => $s['guidance'] ?? null,
            ]);

            if ($s['type'] === 'chapter') {
                $currentChapterId = $created->id;
            }
        }
    }

    private function seedRkpdSections(DocumentTemplate $template)
    {
        $template->sections()->delete();

        $sections = [
            // BAGIAN AWAL
            ['type' => 'cover', 'code' => 'COVER', 'title' => 'Cover Dokumen RKPD', 'seq' => 1, 'pbb' => false, 'auto' => false],
            ['type' => 'preface', 'code' => 'PREFACE', 'title' => 'Kata Pengantar', 'seq' => 2, 'pbb' => true, 'auto' => false],
            ['type' => 'table_of_contents', 'code' => 'TOC', 'title' => 'Daftar Isi', 'seq' => 3, 'pbb' => true, 'auto' => true],
            ['type' => 'list_of_tables', 'code' => 'LOT', 'title' => 'Daftar Tabel', 'seq' => 4, 'pbb' => true, 'auto' => true],
            ['type' => 'list_of_figures', 'code' => 'LOF', 'title' => 'Daftar Gambar', 'seq' => 5, 'pbb' => true, 'auto' => true],
            ['type' => 'list_of_charts', 'code' => 'LOC', 'title' => 'Daftar Grafik', 'seq' => 6, 'pbb' => true, 'auto' => true],
            ['type' => 'list_of_appendices', 'code' => 'LOA', 'title' => 'Daftar Lampiran', 'seq' => 7, 'pbb' => true, 'auto' => true],

            // BAGIAN UTAMA
            ['type' => 'chapter', 'code' => 'BAB I', 'title' => 'Pendahuluan', 'seq' => 8, 'pbb' => true, 'auto' => false],
            ['type' => 'subchapter', 'code' => '1.1', 'title' => 'Latar Belakang', 'seq' => 9, 'pbb' => false, 'auto' => false],
            ['type' => 'subchapter', 'code' => '1.2', 'title' => 'Dasar Hukum', 'seq' => 10, 'pbb' => false, 'auto' => false],
            ['type' => 'subchapter', 'code' => '1.3', 'title' => 'Maksud dan Tujuan', 'seq' => 11, 'pbb' => false, 'auto' => false],
            ['type' => 'subchapter', 'code' => '1.4', 'title' => 'Tahapan Penyusunan RKPD', 'seq' => 12, 'pbb' => false, 'auto' => false],
            ['type' => 'subchapter', 'code' => '1.5', 'title' => 'Sistematika Dokumen RKPD', 'seq' => 13, 'pbb' => false, 'auto' => false],

            ['type' => 'chapter', 'code' => 'BAB II', 'title' => 'Gambaran Umum Kondisi Daerah', 'seq' => 14, 'pbb' => true, 'auto' => false],
            ['type' => 'subchapter', 'code' => '2.1', 'title' => 'Kondisi Geografis dan Demografi', 'seq' => 15, 'pbb' => false, 'auto' => false],
            ['type' => 'subchapter', 'code' => '2.2', 'title' => 'Evaluasi Capaian Kinerja Pembangunan Daerah', 'seq' => 16, 'pbb' => false, 'auto' => false],
            ['type' => 'subchapter', 'code' => '2.3', 'title' => 'Isu Strategis dan Permasalahan Daerah', 'seq' => 17, 'pbb' => false, 'auto' => false],

            ['type' => 'chapter', 'code' => 'BAB III', 'title' => 'Kerangka Ekonomi dan Keuangan Daerah', 'seq' => 18, 'pbb' => true, 'auto' => false],
            ['type' => 'subchapter', 'code' => '3.1', 'title' => 'Arah Kebijakan Ekonomi Daerah', 'seq' => 19, 'pbb' => false, 'auto' => false],
            ['type' => 'subchapter', 'code' => '3.2', 'title' => 'Proyeksi Pendapatan, Belanja, dan Pembiayaan Daerah', 'seq' => 20, 'pbb' => false, 'auto' => false],

            ['type' => 'chapter', 'code' => 'BAB IV', 'title' => 'Sasaran dan Prioritas Pembangunan Daerah', 'seq' => 21, 'pbb' => true, 'auto' => false],
            ['type' => 'subchapter', 'code' => '4.1', 'title' => 'Prioritas Pembangunan Kabupaten Cirebon', 'seq' => 22, 'pbb' => false, 'auto' => false],
            ['type' => 'subchapter', 'code' => '4.2', 'title' => 'Sasaran Strategis RKPD', 'seq' => 23, 'pbb' => false, 'auto' => false],

            ['type' => 'chapter', 'code' => 'BAB V', 'title' => 'Rencana Kerja dan Pendanaan Daerah', 'seq' => 24, 'pbb' => true, 'auto' => false],
            ['type' => 'subchapter', 'code' => '5.1', 'title' => 'Program dan Pagu Indikatif Perangkat Daerah', 'seq' => 25, 'pbb' => false, 'auto' => false],

            ['type' => 'chapter', 'code' => 'BAB VI', 'title' => 'Kinerja Penyelenggaraan Pemerintahan Daerah', 'seq' => 26, 'pbb' => true, 'auto' => false],
            ['type' => 'subchapter', 'code' => '6.1', 'title' => 'Indikator Kinerja Utama (IKU) Daerah', 'seq' => 27, 'pbb' => false, 'auto' => false],

            ['type' => 'chapter', 'code' => 'BAB VII', 'title' => 'Penutup', 'seq' => 28, 'pbb' => true, 'auto' => false],
            ['type' => 'subchapter', 'code' => '7.1', 'title' => 'Kaidah Pelaksanaan dan Penutup', 'seq' => 29, 'pbb' => false, 'auto' => false],

            // BAGIAN LAMPIRAN
            ['type' => 'appendix', 'code' => 'LAMPIRAN 1', 'title' => 'Matriks Program dan Pagu Indikatif RKPD', 'seq' => 30, 'pbb' => true, 'auto' => false],
            ['type' => 'appendix', 'code' => 'LAMPIRAN 2', 'title' => 'Matriks Usulan Musrenbang Kecamatan', 'seq' => 31, 'pbb' => true, 'auto' => false],
        ];

        $currentChapterId = null;
        foreach ($sections as $s) {
            $created = TemplateSection::create([
                'template_id' => $template->id,
                'parent_id' => ($s['type'] === 'subchapter') ? $currentChapterId : null,
                'section_type' => $s['type'],
                'code' => $s['code'],
                'title' => $s['title'],
                'sequence' => $s['seq'],
                'page_break_before' => $s['pbb'],
                'is_automatic' => $s['auto'] ?? false,
                'is_editable' => false,
                'is_required' => true,
            ]);

            if ($s['type'] === 'chapter') {
                $currentChapterId = $created->id;
            }
        }
    }

    private function seedEvaluasiRkpdSections(DocumentTemplate $template)
    {
        $template->sections()->delete();

        $sections = [
            // BAGIAN AWAL
            ['type' => 'cover', 'code' => 'COVER', 'title' => 'Cover Evaluasi RKPD', 'seq' => 1, 'pbb' => false, 'auto' => false],
            ['type' => 'preface', 'code' => 'PREFACE', 'title' => 'Kata Pengantar', 'seq' => 2, 'pbb' => true, 'auto' => false],
            ['type' => 'table_of_contents', 'code' => 'TOC', 'title' => 'Daftar Isi', 'seq' => 3, 'pbb' => true, 'auto' => true],
            ['type' => 'list_of_tables', 'code' => 'LOT', 'title' => 'Daftar Tabel', 'seq' => 4, 'pbb' => true, 'auto' => true],
            ['type' => 'list_of_figures', 'code' => 'LOF', 'title' => 'Daftar Gambar', 'seq' => 5, 'pbb' => true, 'auto' => true],
            ['type' => 'list_of_charts', 'code' => 'LOC', 'title' => 'Daftar Grafik', 'seq' => 6, 'pbb' => true, 'auto' => true],
            ['type' => 'list_of_appendices', 'code' => 'LOA', 'title' => 'Daftar Lampiran', 'seq' => 7, 'pbb' => true, 'auto' => true],

            // BAGIAN UTAMA
            ['type' => 'chapter', 'code' => 'BAB I', 'title' => 'Pendahuluan', 'seq' => 8, 'pbb' => true, 'auto' => false],
            ['type' => 'subchapter', 'code' => '1.1', 'title' => 'Latar Belakang Evaluasi RKPD', 'seq' => 9, 'pbb' => false, 'auto' => false],
            ['type' => 'subchapter', 'code' => '1.2', 'title' => 'Maksud dan Tujuan Evaluasi', 'seq' => 10, 'pbb' => false, 'auto' => false],
            ['type' => 'subchapter', 'code' => '1.3', 'title' => 'Ruang Lingkup dan Metodologi Evaluasi', 'seq' => 11, 'pbb' => false, 'auto' => false],

            ['type' => 'chapter', 'code' => 'BAB II', 'title' => 'Evaluasi Hasil RKPD Menurut Perangkat Daerah', 'seq' => 12, 'pbb' => true, 'auto' => false],
            ['type' => 'subchapter', 'code' => '2.1', 'title' => 'Capaian Target Realisasi Keuangan', 'seq' => 13, 'pbb' => false, 'auto' => false],
            ['type' => 'subchapter', 'code' => '2.2', 'title' => 'Capaian Indikator Kinerja Output dan Outcome', 'seq' => 14, 'pbb' => false, 'auto' => false],

            ['type' => 'chapter', 'code' => 'BAB III', 'title' => 'Analisis Permasalahan dan Faktor Penghambat', 'seq' => 15, 'pbb' => true, 'auto' => false],
            ['type' => 'subchapter', 'code' => '3.1', 'title' => 'Kendala Pelaksanaan Program dan Kegiatan', 'seq' => 16, 'pbb' => false, 'auto' => false],
            ['type' => 'subchapter', 'code' => '3.2', 'title' => 'Faktor Kunci Keberhasilan Pelaksanaan RKPD', 'seq' => 17, 'pbb' => false, 'auto' => false],

            ['type' => 'chapter', 'code' => 'BAB IV', 'title' => 'Penutup dan Rekomendasi', 'seq' => 18, 'pbb' => true, 'auto' => false],
            ['type' => 'subchapter', 'code' => '4.1', 'title' => 'Kesimpulan Evaluasi', 'seq' => 19, 'pbb' => false, 'auto' => false],
            ['type' => 'subchapter', 'code' => '4.2', 'title' => 'Rekomendasi Tindak Lanjut untuk RKPD Tahun Berikutnya', 'seq' => 20, 'pbb' => false, 'auto' => false],

            // BAGIAN LAMPIRAN
            ['type' => 'appendix', 'code' => 'LAMPIRAN 1', 'title' => 'Matriks Evaluasi Realisasi RKPD', 'seq' => 21, 'pbb' => true, 'auto' => false],
        ];

        $currentChapterId = null;
        foreach ($sections as $s) {
            $created = TemplateSection::create([
                'template_id' => $template->id,
                'parent_id' => ($s['type'] === 'subchapter') ? $currentChapterId : null,
                'section_type' => $s['type'],
                'code' => $s['code'],
                'title' => $s['title'],
                'sequence' => $s['seq'],
                'page_break_before' => $s['pbb'],
                'is_automatic' => $s['auto'] ?? false,
                'is_editable' => false,
                'is_required' => true,
            ]);

            if ($s['type'] === 'chapter') {
                $currentChapterId = $created->id;
            }
        }
    }

    /**
     * Struktur Lengkap Master Template RENJA Perubahan Kabupaten Cirebon
     */
    public function seedRenjaPerubahanSections(DocumentTemplate $template)
    {
        $template->sections()->delete();

        $sections = [
            // BAGIAN AWAL
            ['type' => 'cover', 'code' => 'COVER', 'title' => 'Cover Dokumen RENJA Perubahan', 'seq' => 1, 'pbb' => false, 'auto' => false, 'guidance' => 'Halaman sampul resmi dokumen RENJA Perubahan Perangkat Daerah.'],
            ['type' => 'preface', 'code' => 'PENGESAHAN', 'title' => 'Lembar Pengesahan', 'seq' => 2, 'pbb' => true, 'auto' => false, 'guidance' => 'Lembar pengesahan resmi yang ditandatangani Kepala Perangkat Daerah.'],
            ['type' => 'preface', 'code' => 'PREFACE', 'title' => 'Kata Pengantar', 'seq' => 3, 'pbb' => true, 'auto' => false, 'guidance' => 'Kata pengantar pimpinan perangkat daerah.'],
            ['type' => 'table_of_contents', 'code' => 'TOC', 'title' => 'Daftar Isi', 'seq' => 4, 'pbb' => true, 'auto' => true, 'guidance' => 'Daftar isi seluruh bab dan sub-bab dokumen.'],
            ['type' => 'list_of_tables', 'code' => 'LOT', 'title' => 'Daftar Tabel', 'seq' => 5, 'pbb' => true, 'auto' => true, 'guidance' => 'Daftar seluruh tabel dalam dokumen.'],
            ['type' => 'list_of_figures', 'code' => 'LOF', 'title' => 'Daftar Gambar', 'seq' => 6, 'pbb' => true, 'auto' => true, 'guidance' => 'Daftar seluruh gambar dan diagram dalam dokumen.'],

            // BAB I PENDAHULUAN
            ['type' => 'chapter', 'code' => 'BAB I', 'title' => 'Pendahuluan', 'seq' => 7, 'pbb' => true, 'auto' => false, 'guidance' => 'Bab I menguraikan latar belakang, landasan hukum, maksud dan tujuan, serta sistematika penulisan pergeseran/perubahan Renja.'],
            ['type' => 'subchapter', 'code' => '1.1', 'title' => 'Latar Belakang', 'seq' => 8, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraikan latar belakang penyusunan Perubahan Rencana Kerja (Renja) Perangkat Daerah.'],
            ['type' => 'subchapter', 'code' => '1.2', 'title' => 'Landasan Hukum', 'seq' => 9, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraikan peraturan perundang-undangan yang melandasi penyusunan Perubahan Renja.'],
            ['type' => 'subchapter', 'code' => '1.3', 'title' => 'Maksud dan Tujuan', 'seq' => 10, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraikan maksud dan tujuan penyusunan dokumen Perubahan Renja.'],
            ['type' => 'subchapter', 'code' => '1.4', 'title' => 'Sistematika Penulisan', 'seq' => 11, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraikan pokok bahasan dari Bab I sampai dengan Bab V.'],

            // BAB II HASIL EVALUASI RENJA
            ['type' => 'chapter', 'code' => 'BAB II', 'title' => 'Hasil Evaluasi Renja Perangkat Daerah Tahun Lalu', 'seq' => 12, 'pbb' => true, 'auto' => false, 'guidance' => 'Evaluasi capaian kinerja dan realisasi program/kegiatan tahun sebelumnya serta perkembangan semester I tahun berjalan.'],
            ['type' => 'subchapter', 'code' => '2.1', 'title' => 'Evaluasi Pelaksanaan Renja Perangkat Daerah dan Capaian Renstra Perangkat Daerah', 'seq' => 13, 'pbb' => false, 'auto' => false, 'guidance' => 'Evaluasi kinerja pelaksanaan Renja tahun lalu beserta analisis capaian target Renstra.'],
            ['type' => 'subchapter', 'code' => '2.2', 'title' => 'Analisis Kinerja Pelayanan Perangkat Daerah', 'seq' => 14, 'pbb' => false, 'auto' => false, 'guidance' => 'Analisis pencapaian indikator kinerja pelayanan perangkat daerah terhadap SPM dan IKU.'],
            ['type' => 'subchapter', 'code' => '2.3', 'title' => 'Isu Penting Penyelenggaraan Tugas dan Fungsi Perangkat Daerah', 'seq' => 15, 'pbb' => false, 'auto' => false, 'guidance' => 'Uraian permasalahan, hambatan, tantangan, dan peluang dalam pelaksanaan tupoksi pada Perubahan Renja.'],

            // BAB III TUJUAN DAN SASARAN
            ['type' => 'chapter', 'code' => 'BAB III', 'title' => 'Tujuan dan Sasaran Perangkat Daerah', 'seq' => 16, 'pbb' => true, 'auto' => false, 'guidance' => 'Perumusan tujuan dan sasaran strategis perangkat daerah untuk perubahan tahun anggaran bersangkutan.'],
            ['type' => 'subchapter', 'code' => '3.1', 'title' => 'Telaahan Terhadap Kebijakan Daerah', 'seq' => 17, 'pbb' => false, 'auto' => false, 'guidance' => 'Telaahan terhadap arah kebijakan pembangunan daerah dalam RPJMD / RPD dan Perubahan RKPD Kabupaten Cirebon.'],
            ['type' => 'subchapter', 'code' => '3.2', 'title' => 'Tujuan dan Sasaran Renja Perangkat Daerah', 'seq' => 18, 'pbb' => false, 'auto' => false, 'guidance' => 'Tujuan dan sasaran yang hendak dicapai oleh perangkat daerah beserta indikator kinerjanya pada Perubahan Renja.'],
            ['type' => 'subchapter', 'code' => '3.3', 'title' => 'Program dan Kegiatan', 'seq' => 19, 'pbb' => false, 'auto' => false, 'guidance' => 'Daftar program, kegiatan, dan sub kegiatan yang disesuaikan dalam perubahan anggaran.'],

            // BAB IV RENCANA KERJA DAN PENDANAAN
            ['type' => 'chapter', 'code' => 'BAB IV', 'title' => 'Rencana Kerja dan Pendanaan Perangkat Daerah', 'seq' => 20, 'pbb' => true, 'auto' => false, 'guidance' => 'Rincian rencana program, kegiatan, indikator kinerja output/outcome, target capaian, dan pergeseran/perubahan pagu indikatif.'],
            ['type' => 'subchapter', 'code' => '4.1', 'title' => 'Rencana Kerja dan Pendanaan Perangkat Daerah', 'seq' => 21, 'pbb' => false, 'auto' => false, 'guidance' => 'Penjelasan naratif rencana kerja dan alokasi pagu pendanaan perangkat daerah setelah perubahan.'],
            ['type' => 'subchapter', 'code' => '4.2', 'title' => 'Matriks Rencana Kerja dan Pendanaan Perangkat Daerah', 'seq' => 22, 'pbb' => false, 'auto' => false, 'guidance' => 'Tabel Matriks Rencana Kerja dan Pendanaan Perubahan (Tabel Perubahan SIPD/e-Renja). Dapat diformat secara Landscape untuk tabel lebar.'],

            // BAB V PENUTUP
            ['type' => 'chapter', 'code' => 'BAB V', 'title' => 'Penutup', 'seq' => 23, 'pbb' => true, 'auto' => false, 'guidance' => 'Kaidah pelaksanaan dan kesimpulan akhir penyusunan Perubahan Renja.'],
            ['type' => 'subchapter', 'code' => '5.1', 'title' => 'Kaidah Pelaksanaan', 'seq' => 24, 'pbb' => false, 'auto' => false, 'guidance' => 'Pedoman dan kaidah yang harus dipedomani dalam pelaksanaan program/kegiatan perubahan.'],
            ['type' => 'subchapter', 'code' => '5.2', 'title' => 'Kesimpulan', 'seq' => 25, 'pbb' => false, 'auto' => false, 'guidance' => 'Kesimpulan umum dokumen Perubahan Renja Perangkat Daerah.'],

            // LAMPIRAN
            ['type' => 'appendix', 'code' => 'LAMPIRAN', 'title' => 'Lampiran Rencana Kerja Perangkat Daerah', 'seq' => 26, 'pbb' => true, 'auto' => false, 'guidance' => 'Lampiran matriks tabel dan dokumen pendukung perubahan lainnya.'],
        ];

        $currentChapterId = null;
        foreach ($sections as $s) {
            $created = TemplateSection::create([
                'template_id' => $template->id,
                'parent_id' => ($s['type'] === 'subchapter') ? $currentChapterId : null,
                'section_type' => $s['type'],
                'code' => $s['code'],
                'title' => $s['title'],
                'sequence' => $s['seq'],
                'page_break_before' => $s['pbb'],
                'is_automatic' => $s['auto'] ?? false,
                'is_editable' => false, // Struktur canonical locked
                'is_required' => true,
                'guidance_text' => $s['guidance'] ?? null,
            ]);

            if ($s['type'] === 'chapter') {
                $currentChapterId = $created->id;
            }
        }
    }

    /**
     * Inisialisasi seksi dokumen berdasarkan template yang dipilih.
     */
    public function provisionDocumentSections(RenjaDocument $document, string $templateCode = 'RENJA')
    {
        $templateCode = strtoupper($templateCode);
        if ($templateCode === 'MANUAL' || str_contains($templateCode, 'TANPA TEMPLATE')) {
            $document->update([
                'template_id' => null,
                'jenis_dokumen' => 'Dokumen Manual (Tanpa Template)',
            ]);
            return;
        }

        $template = DocumentTemplate::where('code', $templateCode)->first();
        if (!$template) {
            $this->ensureStandardTemplatesSeeded();
            $template = DocumentTemplate::where('code', $templateCode)->first();
            if (!$template && in_array($templateCode, ['RENJA_LAMPIRAN_MURNI', 'RENJA_LAMPIRAN_PERUBAHAN'])) {
                throw new \Exception("Master Template Lampiran Perbub ({$templateCode}) belum tersedia.");
            }
            $template = $template ?? DocumentTemplate::where('code', 'RENJA_MURNI')->first() ?? DocumentTemplate::where('code', 'RENJA')->first();
        }

        if ($template) {
            $updateData = [
                'template_id' => $template->id,
            ];
            if (empty($document->jenis_dokumen)) {
                $updateData['jenis_dokumen'] = $template->name;
            }
            $document->update($updateData);

            // Berikan data Cover default jika belum ada dan template membutuhkan Cover
            if (empty($document->cover_data) && ($template->format_config['has_cover'] ?? false)) {
                $document->update([
                    'cover_data' => [
                        'judul_dokumen' => strtoupper($template->name),
                        'tahun_anggaran' => $document->tahun_anggaran,
                        'nama_pemda' => 'PEMERINTAH KABUPATEN CIREBON',
                        'nama_opd' => $document->opd?->nama_opd ?? 'BADAN PERENCANAAN PEMBANGUNAN DAERAH',
                        'lokasi' => 'SUMBER',
                        'tahun_terbit' => date('Y'),
                        'nomor_dokumen' => 'PERBUP NO. ' . rand(10, 99) . ' TAHUN ' . date('Y'),
                    ]
                ]);
            }

            // Inisialisasi sections jika belum ada
            if ($document->sections()->count() === 0) {
                $templateSections = $template->sections()->get();
                if ($templateSections->count() === 0) {
                    if (in_array($template->code, ['RENJA_LAMPIRAN_MURNI', 'RENJA_LAMPIRAN_PERUBAHAN'])) {
                        $this->seedRenjaLampiranSections($template);
                    } elseif ($template->code === 'RENJA_PERUBAHAN') {
                        $this->seedRenjaPerubahanSections($template);
                    } else {
                        $this->seedRenjaMurniSections($template);
                    }
                    $templateSections = $template->sections()->get();
                }

                $currentBabCode = 'BAB I';
                $currentBabTitle = 'Pendahuluan';

                foreach ($templateSections as $ts) {
                    if ($ts->section_type === 'chapter') {
                        $currentBabCode = $ts->code;
                        $currentBabTitle = $ts->title;
                    }

                    RenjaSection::create([
                        'document_id' => $document->id,
                        'template_section_id' => $ts->id,
                        'section_type' => $ts->section_type,
                        'bab_code' => in_array($ts->section_type, ['chapter', 'subchapter']) ? $currentBabCode : $ts->code,
                        'bab_title' => in_array($ts->section_type, ['chapter', 'subchapter']) ? $currentBabTitle : $ts->title,
                        'sub_bab_code' => $ts->code,
                        'sub_bab_title' => $ts->title,
                        'content' => '',
                        'guidance_text' => $ts->guidance_text,
                        'order_index' => $ts->sequence,
                        'is_completed' => false,
                        'metadata' => [
                            'page_break_before' => $ts->page_break_before,
                            'is_automatic' => $ts->is_automatic,
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * Buat Template Dokumen Kustom / Lainnya secara manual.
     */
    public function createCustomTemplate(array $data)
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', $data['code'] ?? ('CUSTOM_' . time())));
        $hasCover = isset($data['has_cover']) ? (bool)$data['has_cover'] : true;
        $hasFrontSections = isset($data['has_front_sections']) ? (bool)$data['has_front_sections'] : true;
        $allowBold = isset($data['allow_bold']) ? (bool)$data['allow_bold'] : true;

        $template = DocumentTemplate::create([
            'code' => $code,
            'name' => $data['name'] ?? 'Dokumen Kustom',
            'description' => $data['description'] ?? 'Dokumen kustom yang dikonfigurasi secara manual.',
            'format_config' => [
                'paper_width_mm' => 215,
                'paper_height_mm' => 330,
                'margin_top_cm' => 2,
                'margin_right_cm' => 2,
                'margin_bottom_cm' => 2,
                'margin_left_cm' => 2,
                'font_family' => 'Bookman Old Style',
                'font_size_pt' => 12,
                'allow_bold' => $allowBold,
                'has_header' => true,
                'has_footer' => true,
                'has_page_number' => true,
                'has_front_sections' => $hasFrontSections,
                'has_cover' => $hasCover,
            ],
            'toolbar_config' => [
                'bold' => $allowBold,
                'italic' => true,
                'underline' => true,
                'align_left' => true,
                'align_center' => true,
                'align_right' => true,
                'justify' => true,
                'bullets' => true,
                'numbering' => true,
                'tables' => true,
                'images' => true,
                'captions' => true,
                'add_subbab' => true,
                'edit_subbab' => true,
                'edit_bab' => true,
                'header_footer' => true,
                'page_break' => true,
                'cover_editor' => $hasCover,
                'automatic_lists' => $hasFrontSections,
                'update_lists' => $hasFrontSections,
            ],
            'validation_config' => [
                'require_cover' => $hasCover,
                'require_preface' => $hasFrontSections,
                'require_toc' => $hasFrontSections,
                'require_all_chapters' => true,
                'forbid_bold' => !$allowBold,
                'require_captions' => true,
            ],
            'is_active' => true,
        ]);

        $this->seedCustomSections($template);

        return $template;
    }

    private function seedCustomSections(DocumentTemplate $template)
    {
        $hasCover = $template->format_config['has_cover'] ?? true;
        $hasFront = $template->format_config['has_front_sections'] ?? true;

        $seq = 1;
        if ($hasCover) {
            TemplateSection::create(['template_id' => $template->id, 'section_type' => 'cover', 'code' => 'COVER', 'title' => 'Cover Dokumen', 'sequence' => $seq++, 'page_break_before' => false]);
        }

        if ($hasFront) {
            TemplateSection::create(['template_id' => $template->id, 'section_type' => 'preface', 'code' => 'PREFACE', 'title' => 'Kata Pengantar', 'sequence' => $seq++, 'page_break_before' => true]);
            TemplateSection::create(['template_id' => $template->id, 'section_type' => 'table_of_contents', 'code' => 'TOC', 'title' => 'Daftar Isi', 'sequence' => $seq++, 'page_break_before' => true]);
        }

        // BAB I & BAB II standar untuk template kustom
        TemplateSection::create(['template_id' => $template->id, 'section_type' => 'chapter', 'code' => 'BAB I', 'title' => 'Pendahuluan', 'sequence' => $seq++, 'page_break_before' => true]);
        TemplateSection::create(['template_id' => $template->id, 'section_type' => 'subchapter', 'code' => '1.1', 'title' => 'Latar Belakang', 'sequence' => $seq++, 'page_break_before' => false]);
        TemplateSection::create(['template_id' => $template->id, 'section_type' => 'subchapter', 'code' => '1.2', 'title' => 'Maksud dan Tujuan', 'sequence' => $seq++, 'page_break_before' => false]);

        TemplateSection::create(['template_id' => $template->id, 'section_type' => 'chapter', 'code' => 'BAB II', 'title' => 'Gambaran Umum Dokumen', 'sequence' => $seq++, 'page_break_before' => true]);
        TemplateSection::create(['template_id' => $template->id, 'section_type' => 'subchapter', 'code' => '2.1', 'title' => 'Uraian Pokok Program dan Kinerja', 'sequence' => $seq++, 'page_break_before' => false]);
    }

    /**
     * Ambil daftar seluruh template aktif sebagai Single Source of Truth (SSOT)
     * untuk Halaman Pembuatan Dokumen Baru dan Modal Editor Ganti Template.
     */
    public function getAvailableTemplates(): \Illuminate\Support\Collection
    {
        $this->ensureStandardTemplatesSeeded();

        $dbTemplates = DocumentTemplate::where('is_active', true)
            ->orderBy('id', 'asc')
            ->get();

        $manualOption = new DocumentTemplate([
            'id' => null,
            'code' => 'MANUAL',
            'name' => 'Dokumen Manual (Tanpa Template)',
            'description' => 'Dokumen Kosong tanpa struktur bawaan. Seluruh BAB dan Sub-Bab dibuat secara manual.',
            'is_active' => true,
        ]);

        if (!$dbTemplates->contains('code', 'MANUAL')) {
            $dbTemplates->push($manualOption);
        }

        return $dbTemplates;
    }

    /**
     * Ambil seluruh master template.
     */
    public function getAllTemplates()
    {
        $this->ensureStandardTemplatesSeeded();
        return DocumentTemplate::with('sections')->orderBy('id', 'asc')->get();
    }

    /**
     * Toggle status is_active template (BR-030).
     */
    public function toggleTemplateStatus(int $templateId): DocumentTemplate
    {
        $template = DocumentTemplate::findOrFail($templateId);
        $template->is_active = !$template->is_active;
        $template->save();

        return $template;
    }

    /**
     * Update Konfigurasi Format Kertas F4 & Options Template.
     */
    public function updateTemplateConfig(int $templateId, array $data): DocumentTemplate
    {
        $template = DocumentTemplate::findOrFail($templateId);

        $formatConfig = array_merge($template->format_config ?? [], [
            'paper_width_mm' => (int) ($data['paper_width_mm'] ?? 215),
            'paper_height_mm' => (int) ($data['paper_height_mm'] ?? 330),
            'margin_top_cm' => (float) ($data['margin_top_cm'] ?? 2.0),
            'margin_bottom_cm' => (float) ($data['margin_bottom_cm'] ?? 2.0),
            'margin_left_cm' => (float) ($data['margin_left_cm'] ?? 2.0),
            'margin_right_cm' => (float) ($data['margin_right_cm'] ?? 2.0),
            'font_family' => $data['font_family'] ?? 'Calibri',
            'font_size_pt' => (int) ($data['font_size_pt'] ?? 11),
        ]);

        $template->name = $data['name'] ?? $template->name;
        $template->description = $data['description'] ?? $template->description;
        $template->format_config = $formatConfig;
        $template->save();

        return $template;
    }

    /**
     * Tambahkan Seksi Bab/Subbab Baru ke Template Master.
     */
    public function addTemplateSection(int $templateId, array $data): TemplateSection
    {
        $template = DocumentTemplate::findOrFail($templateId);

        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;

        if ($parentId !== null) {
            $parentSection = TemplateSection::find($parentId);
            if (!$parentSection || $parentSection->template_id !== $template->id) {
                throw new \Exception("Parent section berasal dari template yang berbeda.");
            }
        }

        $maxSeq = TemplateSection::where('template_id', $template->id)
            ->where('parent_id', $parentId)
            ->max('sequence') ?? 0;

        return DB::transaction(function () use ($template, $parentId, $maxSeq, $data) {
            return TemplateSection::create([
                'template_id' => $template->id,
                'parent_id' => $parentId,
                'section_type' => $data['section_type'] ?? 'subchapter',
                'code' => $data['code'] ?? '1.0',
                'title' => $data['title'] ?? 'Judul Seksi Baru',
                'sequence' => isset($data['sequence']) ? (int) $data['sequence'] : ($maxSeq + 1),
                'is_required' => isset($data['is_required']) ? (bool) $data['is_required'] : true,
                'is_editable' => isset($data['is_editable']) ? (bool) $data['is_editable'] : true,
                'is_automatic' => isset($data['is_automatic']) ? (bool) $data['is_automatic'] : false,
                'page_break_before' => isset($data['page_break_before']) ? (bool) $data['page_break_before'] : false,
                'page_break_after' => isset($data['page_break_after']) ? (bool) $data['page_break_after'] : false,
                'format_config' => isset($data['guidance_text']) ? ['guidance_text' => $data['guidance_text']] : null,
            ]);
        });
    }

    /**
     * Perbarui Seksi Bab/Subbab Template Master dengan Validasi Tree Engine.
     */
    public function updateTemplateSection(int $sectionId, array $data): TemplateSection
    {
        $section = TemplateSection::findOrFail($sectionId);

        if (array_key_exists('parent_id', $data)) {
            $newParentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;

            if ($newParentId !== null) {
                if ($newParentId === $section->id) {
                    throw new \Exception("Section tidak dapat menunjuk dirinya sendiri sebagai parent.");
                }

                $parentSection = TemplateSection::find($newParentId);
                if (!$parentSection || $parentSection->template_id !== $section->template_id) {
                    throw new \Exception("Parent section berasal dari template yang berbeda.");
                }

                if ($parentSection->isDescendantOf($section->id)) {
                    throw new \Exception("Perubahan hierarchy menyebabkan circular parent relationship.");
                }
            }

            $section->parent_id = $newParentId;
        }

        if (isset($data['code'])) {
            $section->code = $data['code'];
        }
        if (isset($data['title'])) {
            $section->title = $data['title'];
        }
        if (isset($data['sequence'])) {
            $section->sequence = (int) $data['sequence'];
        }
        if (isset($data['is_required'])) {
            $section->is_required = (bool) $data['is_required'];
        }
        if (isset($data['is_editable'])) {
            $section->is_editable = (bool) $data['is_editable'];
        }
        if (isset($data['is_automatic'])) {
            $section->is_automatic = (bool) $data['is_automatic'];
        }
        if (isset($data['page_break_before'])) {
            $section->page_break_before = (bool) $data['page_break_before'];
        }
        if (isset($data['page_break_after'])) {
            $section->page_break_after = (bool) $data['page_break_after'];
        }

        if (isset($data['guidance_text'])) {
            $config = $section->format_config ?? [];
            $config['guidance_text'] = $data['guidance_text'];
            $section->format_config = $config;
        }

        return DB::transaction(function () use ($section) {
            $section->save();
            return $section;
        });
    }

    /**
     * Hapus Master Template jika belum digunakan dokumen (BR-24 Protection).
     */
    public function deleteTemplate(int $templateId): bool
    {
        $template = DocumentTemplate::findOrFail($templateId);
        $usageCount = RenjaDocument::where('template_id', $template->id)->count();

        if ($usageCount > 0) {
            throw new \Exception("Template tidak dapat dihapus karena sudah digunakan oleh {$usageCount} dokumen.");
        }

        return DB::transaction(function () use ($template) {
            $template->sections()->delete();
            return $template->delete();
        });
    }

    /**
     * Hapus Seksi Bab/Subbab dari Template Master (Proteksi Sub-section Child).
     */
    public function deleteTemplateSection(int $sectionId): bool
    {
        $section = TemplateSection::findOrFail($sectionId);

        if ($section->subSections()->count() > 0) {
            throw new \Exception("Section tidak dapat dihapus karena masih memiliki sub-section.");
        }

        return DB::transaction(function () use ($section) {
            return $section->delete();
        });
    }
}
