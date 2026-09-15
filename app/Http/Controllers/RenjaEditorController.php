<?php

namespace App\Http\Controllers;

use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Models\DocumentTemplate;
use App\Services\DocumentTemplateService;
use App\Services\RenjaIndexGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RenjaEditorController extends Controller
{
    protected DocumentTemplateService $templateService;
    protected RenjaIndexGeneratorService $indexGeneratorService;

    public function __construct(
        DocumentTemplateService $templateService,
        RenjaIndexGeneratorService $indexGeneratorService
    ) {
        $this->templateService = $templateService;
        $this->indexGeneratorService = $indexGeneratorService;
    }

    /**
     * Tampilkan Smart Template Editor Dokumen Interaktif (Multi-Template Shared Editor).
     */
    public function index(Request $request, $id)
    {
        $document = RenjaDocument::with(['opd', 'template', 'template.sections', 'sections', 'tableEvals', 'tableUtamas'])->find($id);
        if (!$document) {
            return redirect()->route('renja.index')->with('warning', 'Dokumen tidak ditemukan atau telah dibersihkan. Silakan buat atau buka dokumen baru dari daftar dokumen.');
        }

        // Security check isolasi OPD untuk operator
        $this->authorizeDocumentOwnership($document);

        // Dokumen Lampiran adalah read-only live presentation dari dokumen induk
        if ($document->isLampiranPerbub()) {
            return redirect()->route('renja.preview', ['id' => $document->id, 'is_lampiran' => 1])
                ->with('info', 'Dokumen Lampiran merupakan dokumen turunan otomatis yang mengambil isi langsung dari dokumen induk secara live.');
        }

        $availableTemplates = $this->templateService->getAvailableTemplates();
        $allTemplates = $availableTemplates;
        $activeTemplate = $document->template;

        $formatConfig = $activeTemplate?->format_config ?? [
            'paper_width_mm' => 215,
            'paper_height_mm' => 330,
            'margin_top_cm' => 2,
            'margin_right_cm' => 2,
            'margin_bottom_cm' => 2,
            'margin_left_cm' => 2,
            'font_family' => 'Bookman Old Style',
            'font_size_pt' => 12,
            'allow_bold' => true,
            'has_header' => false,
            'has_footer' => false,
            'has_page_number' => false,
            'has_front_sections' => false,
            'has_cover' => false,
        ];
        $toolbarConfig = $activeTemplate?->toolbar_config ?? [
            'bold' => true, 'italic' => true, 'underline' => true, 'align_left' => true,
            'align_center' => true, 'align_right' => true, 'justify' => true, 'bullets' => true,
            'numbering' => true, 'tables' => true, 'images' => true, 'captions' => true,
            'add_subbab' => true, 'edit_subbab' => true, 'edit_bab' => true,
        ];

        // Tentukan Seksi / BAB Aktif dari query 'bab' atau 'section'
        $sections = $document->sections;
        
        $babOrder = ['BAB I', 'BAB II', 'BAB III', 'BAB IV', 'BAB V', 'BAB VI', 'BAB VII', 'BAB VIII', 'BAB IX', 'BAB X'];
        
        // Kelompokkan Bagian Awal, Bagian Utama (BAB), dan Lampiran
        $isLampiranPerbub = str_contains(strtolower($document->jenis_dokumen ?? ''), 'lampiran') || in_array($document->template?->code ?? '', ['RENJA_LAMPIRAN_MURNI', 'RENJA_LAMPIRAN_PERUBAHAN']);
        $frontSections = $isLampiranPerbub ? collect() : $sections->whereIn('section_type', ['cover', 'preface', 'table_of_contents', 'list_of_tables', 'list_of_figures', 'list_of_charts', 'list_of_appendices'])->sortBy('order_index');
        $mainSections = $sections->whereIn('section_type', ['chapter', 'subchapter'])->sortBy('order_index');
        $appendixSections = $sections->where('section_type', 'appendix')->sortBy('order_index');

        $groupedBabs = $mainSections->groupBy('bab_code')->sortBy(function ($secs, $key) use ($babOrder) {
            $idx = array_search(strtoupper(trim($key)), $babOrder);
            return $idx !== false ? $idx : 99;
        });

        // Tentukan Apakah Sedang Melihat Bagian Awal (Front Matter)
        $queryBab = strtoupper(trim($request->query('bab', '')));
        $matchedFrontSection = null;
        if (!empty($queryBab)) {
            $matchedFrontSection = $frontSections->first(function($fs) use ($queryBab) {
                return strtoupper(trim($fs->sub_bab_code)) === $queryBab 
                    || strtoupper(trim($fs->section_type)) === $queryBab
                    || strtoupper(trim($fs->sub_bab_title)) === $queryBab;
            });
        }

        $isFrontView = $queryBab === 'FRONT' || $request->has('bagian_awal') || !is_null($matchedFrontSection) || ($request->query('section') && $frontSections->contains('id', (int)$request->query('section')));

        // Tentukan Bab Aktif
        $activeBabCode = strtoupper($request->query('bab', $groupedBabs->keys()->first() ?? ''));
        if ($isFrontView) {
            $activeBabCode = 'FRONT';
            $activeSubBabs = collect();
        } elseif (!$groupedBabs->has($activeBabCode) && $groupedBabs->count() > 0) {
            $activeBabCode = $groupedBabs->keys()->first();
            $activeSubBabs = $groupedBabs->get($activeBabCode, collect());
        } else {
            $activeSubBabs = $groupedBabs->get($activeBabCode, collect());
        }

        // Section aktif
        $activeSectionId = $request->query('section');
        $activeSection = null;
        if ($activeSectionId) {
            $activeSection = $sections->firstWhere('id', $activeSectionId);
        }
        if (!$activeSection) {
            if ($matchedFrontSection) {
                $activeSection = $matchedFrontSection;
            } elseif ($isFrontView && $frontSections->count() > 0) {
                $activeSection = $frontSections->first();
            } elseif ($activeSubBabs->count() > 0) {
                $activeSection = $activeSubBabs->first();
            }
        }

        // Jalankan engine validasi dinamis
        $validationResult = $this->runDynamicValidation($document);

        // Hitung statistik kelengkapan per BAB (Sprint 6.0)
        $babStats = [];
        foreach ($groupedBabs as $bCode => $bSections) {
            $totalSecs = $bSections->count();
            $completedSecs = $bSections->filter(function ($s) {
                return $this->hasMeaningfulContent($s->content ?? '');
            })->count();

            $pct = $totalSecs > 0 ? (int) round(($completedSecs / $totalSecs) * 100) : 0;
            $statusLabel = $pct === 100 ? 'Lengkap' : ($pct > 0 ? "{$pct}%" : 'Belum Diisi');

            $babStats[$bCode] = [
                'total' => $totalSecs,
                'completed' => $completedSecs,
                'percentage' => $pct,
                'status_label' => $statusLabel,
            ];
        }

        // Tentukan aturan Read-Only ketat (Dikunci hanya jika status final untuk Admin Bapperida, atau submitted/final untuk OPD)
        $user = Auth::user();
        if ($user->isAdmin() || $user->isVerifikator() || $user->isStaff()) {
            $isReadOnly = in_array(strtolower($document->status), ['disetujui', 'approved', 'dikunci', 'final']);
        } else {
            $isReadOnly = in_array(strtolower($document->status), ['submitted', 'menunggu_pemeriksaan', 'menunggu_verifikasi', 'sedang_diperiksa', 'sedang_direview', 'under_review', 'disetujui', 'approved', 'dikunci', 'final']);
        }

        $lastSavedFormatted = $document->updated_at ? $document->updated_at->diffForHumans() : 'Belum pernah disimpan';

        return view('renja.editor', compact(
            'document',
            'allTemplates',
            'availableTemplates',
            'activeTemplate',
            'formatConfig',
            'toolbarConfig',
            'sections',
            'frontSections',
            'mainSections',
            'appendixSections',
            'groupedBabs',
            'activeBabCode',
            'activeSubBabs',
            'activeSection',
            'isFrontView',
            'validationResult',
            'babStats',
            'isReadOnly',
            'lastSavedFormatted'
        ));
    }

    /**
     * Ganti Jenis Dokumen Template (Renja, RKPD, Evaluasi RKPD).
     */
    public function switchTemplate(Request $request, $id)
    {
        $document = RenjaDocument::findOrFail($id);
        $this->authorizeDocumentOwnership($document);
        
        $validated = $request->validate([
            'template_code' => ['required', 'string'],
        ]);

        $this->templateService->provisionDocumentSections($document, $validated['template_code']);

        return redirect()->route('renja.editor', $document->id)
            ->with('success', 'Jenis Dokumen berhasil diubah menjadi ' . $document->jenis_dokumen . '. Struktur dan toolbar telah disesuaikan.');
    }

    /**
     * Update Data Cover Dokumen (RKPD / Evaluasi RKPD).
     */
    public function updateCover(Request $request, $id)
    {
        $document = RenjaDocument::findOrFail($id);
        $this->authorizeDocumentOwnership($document);

        $validated = $request->validate([
            'judul_dokumen' => ['required', 'string'],
            'tahun_anggaran' => ['required', 'integer'],
            'nama_pemda' => ['required', 'string'],
            'nama_opd' => ['required', 'string'],
            'lokasi' => ['required', 'string'],
            'tahun_terbit' => ['required', 'string'],
            'nomor_dokumen' => ['nullable', 'string'],
        ]);

        $document->update([
            'cover_data' => $validated,
        ]);

        return redirect()->route('renja.editor', $document->id)
            ->with('success', 'Data Cover Dokumen berhasil diperbarui.');
    }

    /**
     * Update isi narasi seksi melalui AJAX.
     */
    public function updateSection(Request $request, $id, $sectionId)
    {
        $document = RenjaDocument::findOrFail($id);
        $this->authorizeDocumentOwnership($document);

        if ($document->isLampiranPerbub()) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen Lampiran bersifat read-only dan tersinkronisasi langsung dari dokumen induk.',
            ], 403);
        }
        $section = RenjaSection::where('document_id', $document->id)->findOrFail($sectionId);

        $content = $request->input('content');
        
        // Jika format template melarang Bold (seperti RENJA), sanitasi tag bold
        $isRenja = str_contains(strtoupper($document->jenis_dokumen), 'RENJA');
        $allowBold = $document->template?->format_config['allow_bold'] ?? (!$isRenja);
        if (!$allowBold) {
            $content = preg_replace('/<b\b[^>]*>(.*?)<\/b>/is', '$1', $content);
            $content = preg_replace('/<strong\b[^>]*>(.*?)<\/strong>/is', '$1', $content);
            $content = preg_replace('/font-weight\s*:\s*(bold|700|600);?/i', '', $content);
        }

        $section->update([
            'content' => $content,
            'is_completed' => $this->hasMeaningfulContent($content),
        ]);

        $docMeta = $document->metadata ?? [];
        $docMeta['editor_modified'] = true;
        $docMeta['preview_source'] = 'editor_sections';
        $document->update([
            'metadata' => $docMeta,
        ]);
        $document->touch();

        // Simpan snapshot revisi dengan pembatasan FIFO maksimal 5 entri (Sprint 6.1)
        $user = Auth::user();
        $revisions = $section->addRevisionSnapshot(
            $content, 
            $request->input('note', 'Auto-save snapshot'), 
            $user?->nama_lengkap ?? $user?->name ?? 'Operator OPD'
        );

        // Sinkronisasi otomatis halaman indeks Front Matter (Daftar Isi, Gambar, Tabel, Lampiran) jika ada
        $this->indexGeneratorService->syncDocumentFrontIndexes($document);

        return response()->json([
            'success' => true,
            'message' => 'Tersimpan otomatis',
            'progress' => [
                'completed' => $document->completed_sections_count,
                'total' => $document->total_sections_count,
                'percentage' => $document->progress_percentage,
            ],
            'revisions_count' => count($revisions),
            'revisions' => $revisions,
        ]);
    }

    /**
     * Autofix / Mesin Cuci V2 untuk seksi tertentu.
     */
    public function autofixSection(Request $request, $id, $sectionId)
    {
        $document = RenjaDocument::findOrFail($id);
        $this->authorizeDocumentOwnership($document);

        if ($document->isLampiranPerbub()) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen Lampiran bersifat read-only dan tersinkronisasi langsung dari dokumen induk.',
            ], 403);
        }
        $section = RenjaSection::where('document_id', $document->id)->findOrFail($sectionId);

        $autoFixService = app(\App\Services\RenjaAutoFixService::class);
        $isRenja = str_contains(strtoupper($document->jenis_dokumen), 'RENJA');
        $allowBold = $document->template?->format_config['allow_bold'] ?? (!$isRenja);

        $result = $autoFixService->autofixSectionContent($section, $allowBold);

        return response()->json([
            'success' => true,
            'message' => 'Autofix seksi berhasil dijalankan.',
            'section_id' => $section->id,
            'cleaned_content' => $section->content,
            'is_completed' => $section->is_completed,
        ]);
    }

    /**
     * Update Judul BAB secara manual.
     */
    public function updateBabTitle(Request $request, $id)
    {
        $document = RenjaDocument::findOrFail($id);
        $this->authorizeDocumentOwnership($document);

        $validated = $request->validate([
            'bab_code' => ['required', 'string'],
            'bab_title' => ['required', 'string'],
        ]);

        $babCode = strtoupper($validated['bab_code']);
        $newBabTitle = trim($validated['bab_title']);

        $officialBabs = ['BAB I', 'BAB II', 'BAB III', 'BAB IV', 'BAB V'];
        if (in_array($babCode, $officialBabs)) {
            return back()->with('error', 'Struktur resmi ' . $babCode . ' bersifat baku dan tidak dapat diubah namanya.');
        }

        RenjaSection::where('document_id', $document->id)
            ->where('bab_code', $babCode)
            ->update(['bab_title' => $newBabTitle]);

        $this->indexGeneratorService->syncDocumentFrontIndexes($document);

        return redirect()->route('renja.editor', [$document->id, 'bab' => $babCode])
            ->with('success', 'Judul ' . $babCode . ' berhasil diperbarui menjadi "' . $newBabTitle . '".');
    }

    /**
     * Hapus seluruh BAB beserta seluruh Sub-Bab di dalamnya.
     */
    public function deleteBab(Request $request, $id, $babCode)
    {
        $document = RenjaDocument::findOrFail($id);
        $this->authorizeDocumentOwnership($document);

        $babCodeUpper = strtoupper($babCode);
        $officialBabs = ['BAB I', 'BAB II', 'BAB III', 'BAB IV', 'BAB V'];
        $hasTemplateSection = RenjaSection::where('document_id', $document->id)
            ->where('bab_code', $babCodeUpper)
            ->whereNotNull('template_section_id')
            ->exists();

        if (in_array($babCodeUpper, $officialBabs) || $hasTemplateSection) {
            return back()->with('error', 'BAB resmi dari template (' . $babCodeUpper . ') tidak dapat dihapus.');
        }

        RenjaSection::where('document_id', $document->id)
            ->where('bab_code', $babCodeUpper)
            ->delete();

        $this->indexGeneratorService->syncDocumentFrontIndexes($document);

        $nextBab = RenjaSection::where('document_id', $document->id)
            ->whereIn('section_type', ['chapter', 'subchapter'])
            ->first()?->bab_code ?? 'BAB I';

        return redirect()->route('renja.editor', [$document->id, 'bab' => $nextBab])
            ->with('success', $babCodeUpper . ' dan seluruh Sub-Bab di dalamnya berhasil dihapus.');
    }

    /**
     * Tambah BAB Baru secara manual (Custom BAB Tanpa Terikat Template Default).
     */
    public function addBab(Request $request, $id)
    {
        $document = RenjaDocument::findOrFail($id);
        $this->authorizeDocumentOwnership($document);

        $validated = $request->validate([
            'bab_code' => ['required', 'string'],
            'bab_title' => ['required', 'string'],
            'sub_bab_title' => ['nullable', 'string'],
        ]);

        $babCode = strtoupper(trim($validated['bab_code']));
        $babTitle = trim($validated['bab_title']);
        $subBabTitle = trim($validated['sub_bab_title'] ?? 'Uraian Umum');

        $romanMap = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6, 'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10];
        $cleanBab = trim(str_replace('BAB', '', strtoupper($babCode)));
        $babNum = $romanMap[$cleanBab] ?? (preg_replace('/[^\d]/', '', $babCode) ?: ($document->sections()->whereIn('section_type', ['chapter', 'subchapter'])->distinct('bab_code')->count() + 1));
        $subBabCode = $babNum . '.1';

        $section = RenjaSection::create([
            'document_id' => $document->id,
            'section_type' => 'subchapter',
            'bab_code' => $babCode,
            'bab_title' => $babTitle,
            'sub_bab_code' => $subBabCode,
            'sub_bab_title' => $subBabTitle,
            'order_index' => $document->sections()->count() + 1,
            'content' => '',
            'is_completed' => false,
        ]);

        $this->indexGeneratorService->syncDocumentFrontIndexes($document);

        return redirect()->route('renja.editor', [$document->id, 'bab' => $section->bab_code])
            ->with('success', $babCode . ' (' . $babTitle . ') berhasil ditambahkan.');
    }

    /**
     * Tambah Sub-Bab Baru.
     */
    public function addSubBab(Request $request, $id)
    {
        $document = RenjaDocument::findOrFail($id);
        $this->authorizeDocumentOwnership($document);

        $validated = $request->validate([
            'bab_code' => ['required', 'string'],
            'sub_bab_code' => ['nullable', 'string'],
            'title' => ['required', 'string'],
        ]);

        $babCode = strtoupper($validated['bab_code']);
        $cleanTitle = preg_replace('/^\d+(\.\d+)*\s*/', '', trim($validated['title']));

        $subBabCode = trim($validated['sub_bab_code'] ?? '');
        if (empty($subBabCode)) {
            $lastSec = RenjaSection::where('document_id', $document->id)
                ->where('bab_code', $babCode)
                ->orderBy('id', 'desc')
                ->first();
            
            $babNum = preg_replace('/[^\d]/', '', $babCode) ?: '1';
            if ($lastSec && preg_match('/^\d+\.(\d+)/', $lastSec->sub_bab_code, $m)) {
                $subBabCode = $babNum . '.' . ((int)$m[1] + 1);
            } else {
                $existingCount = RenjaSection::where('document_id', $document->id)->where('bab_code', $babCode)->count();
                $subBabCode = $babNum . '.' . ($existingCount + 1);
            }
        }

        $firstSec = RenjaSection::where('document_id', $document->id)->where('bab_code', $babCode)->first();
        $babTitle = $firstSec->bab_title ?? $babCode;

        $section = RenjaSection::create([
            'document_id' => $document->id,
            'section_type' => 'subchapter',
            'bab_code' => $babCode,
            'bab_title' => $babTitle,
            'sub_bab_code' => $subBabCode,
            'sub_bab_title' => $cleanTitle ?: $validated['title'],
            'order_index' => $document->sections()->count() + 1,
            'content' => '',
            'is_completed' => false,
        ]);

        $this->indexGeneratorService->syncDocumentFrontIndexes($document);

        return redirect()->route('renja.editor', [$document->id, 'bab' => $section->bab_code, 'section' => $section->id])
            ->with('success', 'Sub-Bab ' . $section->sub_bab_code . ' ' . $section->sub_bab_title . ' berhasil ditambahkan.');
    }

    /**
     * Hapus Section Sub-Bab.
     */
    public function deleteSection(Request $request, $id, $sectionId)
    {
        $document = RenjaDocument::findOrFail($id);
        $this->authorizeDocumentOwnership($document);

        $section = RenjaSection::where('document_id', $document->id)->findOrFail($sectionId);

        $officialCodes = ['1.1', '1.2', '1.3', '1.4', '2.1', '2.2', '2.3', '3.1', '3.2', '3.3', '4.1', '4.2', '5.1', '5.2', 'COVER', 'PENGESAHAN', 'PREFACE', 'TOC', 'LOT', 'LOF'];
        if ($section->template_section_id !== null || in_array($section->sub_bab_code, $officialCodes)) {
            return back()->with('error', 'Seksi resmi template (' . ($section->sub_bab_code ?? $section->bab_code) . ') bersifat baku dan tidak dapat dihapus.');
        }

        $babCode = $section->bab_code;
        $subBabName = $section->sub_bab_code . ' ' . $section->sub_bab_title;
        $section->delete();

        $this->indexGeneratorService->syncDocumentFrontIndexes($document);

        return redirect()->route('renja.editor', [$document->id, 'bab' => $babCode])
            ->with('success', 'Sub-Bab ' . $subBabName . ' berhasil dihapus.');
    }

    /**
     * Tambah Halaman Awal / Front Matter secara manual (+ Halaman Awal).
     */
    public function addFrontMatter(Request $request, $id)
    {
        $document = RenjaDocument::findOrFail($id);
        $this->authorizeDocumentOwnership($document);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:cover,preface,table_of_contents,list_of_figures,list_of_tables,list_of_appendices'],
        ]);

        $type = $validated['type'];

        $config = match ($type) {
            'cover' => ['code' => 'COVER', 'title' => 'Cover Dokumen', 'content' => '<p>Data Cover Dokumen</p>'],
            'preface' => ['code' => 'PREFACE', 'title' => 'Kata Pengantar', 'content' => '<p>Isikan narasi Kata Pengantar di sini...</p>'],
            'table_of_contents' => ['code' => 'TOC', 'title' => 'Daftar Isi', 'content' => $this->indexGeneratorService->generateTableOfContentsHtml($document)],
            'list_of_figures' => ['code' => 'LOF', 'title' => 'Daftar Gambar', 'content' => $this->indexGeneratorService->generateListOfFiguresHtml($document)],
            'list_of_tables' => ['code' => 'LOT', 'title' => 'Daftar Tabel', 'content' => $this->indexGeneratorService->generateListOfTablesHtml($document)],
            'list_of_appendices' => ['code' => 'LOA', 'title' => 'Daftar Lampiran', 'content' => $this->indexGeneratorService->generateListOfAppendicesHtml($document)],
        };

        // Buat atau perbarui seksi front matter
        $section = RenjaSection::updateOrCreate(
            [
                'document_id' => $document->id,
                'section_type' => $type,
            ],
            [
                'bab_code' => 'FRONT',
                'bab_title' => 'BAGIAN AWAL',
                'sub_bab_code' => $config['code'],
                'sub_bab_title' => $config['title'],
                'content' => $config['content'],
                'is_completed' => true,
                'order_index' => 0,
            ]
        );

        $this->indexGeneratorService->syncDocumentFrontIndexes($document);

        return redirect()->route('renja.editor', [$document->id, 'section' => $section->id])
            ->with('success', 'Halaman ' . $config['title'] . ' berhasil ditambahkan ke Bagian Awal.');
    }

    /**
     * Engine Validasi Dinamis berbasis Konfigurasi Template.
     */
    public function runDynamicValidation(RenjaDocument $document): array
    {
        $template = $document->template;
        $valConfig = $template->validation_config ?? [];
        $formatConfig = $template->format_config ?? [];

        $checks = [];

        // Check 1: Cover completeness
        if ($valConfig['require_cover'] ?? false) {
            $cover = $document->cover_data ?? [];
            $isComplete = !empty($cover['judul_dokumen']) && !empty($cover['nama_opd']);
            $checks[] = [
                'type' => $isComplete ? 'success' : 'warning',
                'message' => $isComplete ? 'Cover dokumen sudah terisi lengkap' : 'Data Cover dokumen belum diisi secara lengkap',
            ];
        }

        // Check 2: Seluruh Seksi BAB Utama
        $sections = $document->sections;
        $emptySections = $sections->filter(fn($s) => !$this->hasMeaningfulContent($s->content));
        
        $unmappedCount = 0;
        $trulyEmptyCount = 0;
        foreach ($emptySections as $s) {
            $mStatus = $s->metadata['mapping_status'] ?? 'belum_diisi';
            if ($mStatus === 'mapping_perlu_diperiksa') {
                $unmappedCount++;
            } else {
                $trulyEmptyCount++;
            }
        }

        if ($emptySections->count() === 0) {
            $checks[] = [
                'type' => 'success',
                'message' => 'Seluruh BAB dan Sub-Bab telah terisi narasi',
            ];
        } else {
            $msg = '';
            if ($trulyEmptyCount > 0) {
                $msg .= $trulyEmptyCount . ' Sub-Bab belum diisi narasi. ';
            }
            if ($unmappedCount > 0) {
                $msg .= $unmappedCount . ' Sub-Bab terdeteksi memiliki isi pada dokumen Word tetapi gagal dipetakan otomatis (Mapping Perlu Diperiksa).';
            }
            
            $checks[] = [
                'type' => 'warning',
                'message' => trim($msg),
            ];
        }

        // Check 3: Aturan Bold (Hanya untuk Lampiran Perbub)
        $isLampiranPerbub = in_array($document->template?->code ?? '', ['RENJA_LAMPIRAN_MURNI', 'RENJA_LAMPIRAN_PERUBAHAN']);
        if ($isLampiranPerbub && ($formatConfig['allow_bold'] ?? true) === false) {
            $hasBoldViolation = false;
            foreach ($sections as $s) {
                $sType = strtolower($s->section_type ?? '');
                if (in_array($sType, ['cover', 'preface', 'table_of_contents', 'list_of_tables', 'list_of_figures', 'chapter'])) {
                    continue;
                }
                
                // Clean tables and headings from bold check
                $cleanContent = $s->content ?? '';
                $cleanContent = preg_replace('/<table\b[^>]*>.*?<\/table>/is', '', $cleanContent);
                $cleanContent = preg_replace('/<h[1-6]\b[^>]*>.*?<\/h[1-6]>/is', '', $cleanContent);
                
                $subBabCode = $s->sub_bab_code ?? '';
                if (!empty($subBabCode)) {
                    $escapedSubBab = preg_quote($subBabCode, '/');
                    $escapedSubBab = str_replace('\.', '\s*\.\s*', $escapedSubBab);
                    $cleanContent = preg_replace('/<p\b[^>]*>\s*(?:<strong>|<b>|<span[^>]*>)?\s*' . $escapedSubBab . '\b.*?<\/p>/is', '', $cleanContent);
                }
                $cleanContent = preg_replace('/<p\b[^>]*>\s*(?:<strong>|<b>|<span[^>]*>)?\s*\d+\.\d+(?:\.\d+)*\b.*?<\/p>/is', '', $cleanContent);

                if (preg_match('/<b\b|<strong\b|font-weight\s*:\s*(bold|700)/i', $cleanContent)) {
                    $hasBoldViolation = true;
                    break;
                }
            }
            if ($hasBoldViolation) {
                $checks[] = [
                    'type' => 'warning',
                    'message' => 'Terdeteksi format Bold pada paragraf biasa/narasi isi dokumen. Ketentuan ' . $template->name . ' mewajibkan paragraf biasa bebas dari format Bold (Zero Bold).',
                ];
            } else {
                $checks[] = [
                    'type' => 'success',
                    'message' => 'Zero Bold terpenuhi (tidak ada huruf tebal pada paragraf biasa/narasi).',
                ];
            }
        }

        // Check 4: Caption Gambar / Tabel
        $totalTables = 0;
        $totalImages = 0;
        $missingTableCaptions = 0;
        $missingImageCaptions = 0;

        foreach ($sections as $s) {
            // Hitung tabel
            $tablesCount = preg_match_all('/<table\b[^>]*>/is', $s->content);
            if ($tablesCount > 0) {
                $totalTables += $tablesCount;
                if (!preg_match('/<caption>|Caption:/i', $s->content)) {
                    $missingTableCaptions += $tablesCount;
                }
            }

            // Hitung gambar
            $imagesCount = preg_match_all('/<img\b[^>]*>/is', $s->content);
            if ($imagesCount > 0) {
                $totalImages += $imagesCount;
                if (!preg_match('/<figcaption\b|alt=|title=|Caption:/i', $s->content)) {
                    $missingImageCaptions += $imagesCount;
                }
            }
        }

        $totalMedia = $totalTables + $totalImages;
        $missingCaptions = $missingTableCaptions + $missingImageCaptions;

        if ($totalMedia === 0) {
            $checks[] = [
                'type' => 'success',
                'message' => 'Tidak ditemukan tabel atau gambar pada dokumen.',
            ];
        } else {
            $msgParts = [];
            if ($totalTables > 0) {
                $hasCap = $totalTables - $missingTableCaptions;
                $msgParts[] = "{$totalTables} tabel ditemukan ({$hasCap} memiliki caption, {$missingTableCaptions} belum memiliki caption)";
            }
            if ($totalImages > 0) {
                $hasCap = $totalImages - $missingImageCaptions;
                $msgParts[] = "{$totalImages} gambar ditemukan ({$hasCap} memiliki caption, {$missingImageCaptions} belum memiliki caption)";
            }
            $msg = implode(' dan ', $msgParts) . '.';
            $checks[] = [
                'type' => $missingCaptions > 0 ? 'warning' : 'success',
                'message' => $msg,
            ];
        }

        return $checks;
    }

    /**
     * Tambah Template Dokumen Kustom / Lainnya secara manual.
     */
    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:document_templates,code',
            'description' => 'nullable|string',
        ]);

        $template = $this->templateService->createCustomTemplate($request->all());

        return back()->with('success', 'Template dokumen baru "' . $template->name . '" berhasil ditambahkan secara manual.');
    }

    /**
     * Memeriksa apakah konten memiliki isi bermakna (teks, tabel, gambar, list, dll).
     */
    private function hasMeaningfulContent(?string $content): bool
    {
        if (empty($content)) {
            return false;
        }
        $stripped = trim(strip_tags($content));
        $stripped = str_replace('&nbsp;', '', $stripped);
        $stripped = trim($stripped);
        
        return !empty($stripped) 
            || str_contains($content, '<table') 
            || str_contains($content, '<img') 
            || str_contains($content, '<ul') 
            || str_contains($content, '<ol') 
            || str_contains($content, '<li')
            || str_contains($content, '<hr');
    }

    /**
     * Memastikan hak akses dokumen operator OPD terisolasi sesuai instansinya.
     */
    protected function authorizeDocumentOwnership(RenjaDocument $document): void
    {
        $user = Auth::user();
        if ($user && $user->isOperator() && (int)$document->opd_id !== (int)$user->opd_id) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang mengakses atau mengubah dokumen Perangkat Daerah lain.');
        }
    }
}
