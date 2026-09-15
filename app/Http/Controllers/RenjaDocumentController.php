<?php

namespace App\Http\Controllers;

use App\Models\RenjaDocument;
use App\Models\MasterOpd;
use App\Models\DocumentTemplate;
use App\Services\TemplatePersonalizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RenjaDocumentController extends Controller
{
    protected \App\Services\OpdDocumentService $opdDocumentService;
    protected \App\Services\DocumentRegistryService $documentRegistryService;
    protected \App\Services\DocumentTemplateService $templateService;

    public function __construct(
        \App\Services\OpdDocumentService $opdDocumentService,
        \App\Services\DocumentRegistryService $documentRegistryService,
        \App\Services\DocumentTemplateService $templateService
    ) {
        $this->opdDocumentService = $opdDocumentService;
        $this->documentRegistryService = $documentRegistryService;
        $this->templateService = $templateService;
    }

    /**
     * Tampilkan Workspace RENJA Berbasis Tahun Anggaran untuk Role Operator OPD (BR-031 s.d. BR-037).
     */
    public function workspace(Request $request)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);

        $queryTa = $request->query('tahun_anggaran');
        if ($queryTa && is_numeric($queryTa) && (int)$queryTa >= 2020) {
            $selectedTa = (int)$queryTa;
        } else {
            $sessionTa = session('active_ta');
            $selectedTa = ($sessionTa && is_numeric($sessionTa) && (int)$sessionTa >= 2020) ? (int)$sessionTa : (int)date('Y');
        }
        session(['active_ta' => $selectedTa]);

        $workspaceData = $this->opdDocumentService->getRenjaTaWorkspaceData($opdId, $selectedTa);
        $workspaceData['docFamilies'] = $this->documentRegistryService->getFamilies($selectedTa);

        return view('renja.workspace', $workspaceData);
    }

    /**
     * Tampilkan daftar dokumen Renja sesuai hak akses role (Modul Dokumen Saya Operator OPD).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);

        $filters = [
            'search' => $request->query('search'),
            'tahun_anggaran' => $request->query('tahun_anggaran'),
            'jenis_dokumen' => $request->query('jenis_dokumen'),
            'status' => $request->query('status'),
            'sort' => $request->query('sort'),
        ];

        $workspaceData = $this->opdDocumentService->getOpdWorkspaceData($opdId, $filters);
        $selectedTa = !empty($filters['tahun_anggaran']) && is_numeric($filters['tahun_anggaran'])
            ? (int) $filters['tahun_anggaran']
            : session('active_ta', (int) date('Y'));
        $workspaceData['docFamilies'] = $this->documentRegistryService->getFamilies($selectedTa);

        return view('renja.index', $workspaceData);
    }

    /**
     * Buat Dokumen Renja Murni baru pada Tahun Anggaran aktif.
     */
    public function storeMurni(Request $request)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);
        $ta = (int) $request->input('tahun_anggaran', session('active_ta', (int)date('Y') + 1));

        try {
            $doc = $this->opdDocumentService->createRenjaMurni($opdId, $ta);
            return redirect()->route('renja.workspace', ['tahun_anggaran' => $ta])
                ->with('success', "Dokumen Renja Murni TA {$ta} berhasil dibuat.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Buat Dokumen Renja Perubahan baru pada Tahun Anggaran aktif (BR-032).
     */
    public function storePerubahan(Request $request)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);
        
        $queryTa = $request->input('tahun_anggaran');
        if ($queryTa && is_numeric($queryTa) && (int)$queryTa >= 2020) {
            $ta = (int)$queryTa;
        } else {
            $sessionTa = session('active_ta');
            $ta = ($sessionTa && is_numeric($sessionTa) && (int)$sessionTa >= 2020) ? (int)$sessionTa : (int)date('Y');
        }

        try {
            $doc = $this->opdDocumentService->createRenjaPerubahan($opdId, $ta);
            return redirect()->route('renja.workspace', ['tahun_anggaran' => $ta])
                ->with('success', "Dokumen Renja Perubahan TA {$ta} berhasil dibuat.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Generate Dokumen Renja Lampiran (Perbup dari Murni atau Kepbup dari Perubahan).
     */
    public function generateLampiran(Request $request)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);
        $sourceType = $request->input('source_type', 'murni');
        
        $queryTa = $request->input('tahun_anggaran');
        if ($queryTa && is_numeric($queryTa) && (int)$queryTa >= 2020) {
            $ta = (int)$queryTa;
        } else {
            $sessionTa = session('active_ta');
            $ta = ($sessionTa && is_numeric($sessionTa) && (int)$sessionTa >= 2020) ? (int)$sessionTa : (int)date('Y') + 1;
        }

        try {
            $doc = $this->opdDocumentService->generateLampiranPerbub($opdId, $ta, $sourceType);
            return redirect()->route('renja.workspace', ['tahun_anggaran' => $ta])
                ->with('success', "Dokumen {$doc->jenis_dokumen} berhasil digenerate.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Form pembuatan Dokumen Renja baru untuk Operator OPD.
     */
    public function create()
    {
        return redirect()->route('renja.index', ['open_modal' => 1]);
    }

    /**
     * Simpan dokumen perencanaan baru ke database berdasarkan arsitektur Document Family & Variant.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $opdId = $request->input('opd_id') ? (int)$request->input('opd_id') : $this->getEffectiveOpdIdForUser($user);

        $validated = $request->validate([
            'variant_key' => ['nullable', 'string'],
            'document_family' => ['nullable', 'string'],
            'tahun_anggaran' => ['nullable', 'integer', 'min:2020', 'max:2099'],
            'jenis_dokumen' => ['nullable', 'string'],
            'custom_jenis_dokumen' => ['nullable', 'string'],
            'judul_dokumen' => ['nullable', 'string'],
            'opd_id' => ['nullable', 'exists:master_opd,id'],
            'latar_belakang' => ['nullable', 'string'],
            'landasan_hukum' => ['nullable', 'string'],
            'maksud_tujuan' => ['nullable', 'string'],
            'sistematika' => ['nullable', 'string'],
            'evaluasi_narasi' => ['nullable', 'string'],
            'isu_strategis_narasi' => ['nullable', 'string'],
            'tujuan_sasaran_narasi' => ['nullable', 'string'],
            'program_kegiatan_narasi' => ['nullable', 'string'],
            'penutup_narasi' => ['nullable', 'string'],
        ]);

        $activeTa = session('active_ta', (int)date('Y'));
        
        // Resolusi Document Family
        $familyCode = strtoupper(trim($validated['document_family'] ?? ''));
        if (empty($familyCode) && !empty($validated['variant_key'])) {
            $vInfo = $this->documentRegistryService->getVariant($validated['variant_key'], $activeTa);
            $familyCode = $vInfo['family'] ?? 'RENJA';
        }
        $familyCode = $familyCode ?: 'RENJA';

        // Pastikan family aktif
        if ($familyCode === 'RKPD') {
            return back()->with('error', 'Dokumen RKPD belum dibuka untuk penyusunan saat ini.');
        }

        // Resolusi Variant Key
        $variantKey = $validated['variant_key'] ?? null;
        if (!$variantKey && !empty($validated['jenis_dokumen'])) {
            $inputUpper = strtoupper(trim($validated['jenis_dokumen']));
            if (str_contains($inputUpper, 'PERUBAHAN')) {
                $variantKey = 'RENJA_PERUBAHAN';
            } elseif (str_contains($inputUpper, 'MURNI') || str_contains($inputUpper, 'RENJA')) {
                $variantKey = 'RENJA_MURNI';
            }
        }
        $variantKey = $variantKey ?: 'RENJA_MURNI';

        $variantInfo = $this->documentRegistryService->getVariant($variantKey, $activeTa);

        // Tentukan Tahun Anggaran resmi berdasarkan aturan varian
        $ta = $validated['tahun_anggaran'] ?? ($variantInfo['tahun_anggaran'] ?? $activeTa);

        // Pencegahan Duplikasi Dokumen per OPD & Siklus
        $existing = $this->documentRegistryService->checkExistingDocument($opdId, $variantKey, $activeTa);
        if ($existing) {
            return redirect()->route('renja.editor', $existing->id)
                ->with('info', "Dokumen {$existing->jenis_dokumen} TA {$existing->tahun_anggaran} sudah ada untuk Perangkat Daerah Anda.");
        }

        // Cari Master Template
        $masterTemplate = $this->documentRegistryService->findMasterTemplate($variantKey);

        $opdObj = MasterOpd::find($opdId);
        $finalJenis = $variantInfo['name'] ?? ($masterTemplate?->name ?? 'RENJA Murni');
        $finalJudul = !empty($validated['judul_dokumen']) ? trim($validated['judul_dokumen']) : $finalJenis;

        $coverData = [
            'judul_dokumen' => $finalJudul,
            'tahun_anggaran' => $ta,
            'nama_pemda' => 'PEMERINTAH KABUPATEN CIREBON',
            'nama_opd' => $opdObj?->nama_opd ?? 'BADAN PERENCANAAN PEMBANGUNAN DAERAH',
            'lokasi' => 'SUMBER',
            'tahun_terbit' => date('Y'),
        ];

        $document = RenjaDocument::create([
            'opd_id' => $opdId,
            'tahun_anggaran' => $ta,
            'jenis_dokumen' => $finalJenis,
            'template_id' => $masterTemplate?->id,
            'status' => 'draft',
            'cover_data' => $coverData,
            'metadata' => [
                'document_family' => $familyCode,
                'variant_key' => $variantKey,
                'creation_method' => $request->input('creation_method', 'template_official'),
                'created_by_user_id' => $user->id,
            ],
            'latar_belakang' => $validated['latar_belakang'] ?? null,
            'landasan_hukum' => $validated['landasan_hukum'] ?? null,
            'maksud_tujuan' => $validated['maksud_tujuan'] ?? null,
            'sistematika' => $validated['sistematika'] ?? null,
            'evaluasi_narasi' => $validated['evaluasi_narasi'] ?? null,
            'isu_strategis_narasi' => $validated['isu_strategis_narasi'] ?? null,
            'tujuan_sasaran_narasi' => $validated['tujuan_sasaran_narasi'] ?? null,
            'program_kegiatan_narasi' => $validated['program_kegiatan_narasi'] ?? null,
            'penutup_narasi' => $validated['penutup_narasi'] ?? null,
        ]);

        // Provision sections jika master template memiliki sections
        if ($masterTemplate) {
            $this->templateService->provisionDocumentSections($document, $masterTemplate->code);
        }

        return redirect()->route('renja.editor', $document->id)
            ->with('success', "Dokumen {$finalJenis} TA {$ta} berhasil dibuat.");
    }

    /**
     * Tampilkan detail dokumen Renja.
     */
    public function show(Request $request, $id)
    {
        $document = RenjaDocument::with([
            'opd', 
            'template', 
            'sections', 
            'updatedByUser', 
            'archivedByUser', 
            'assignedVerificator', 
            'tableEvals', 
            'tableUtamas'
        ])->findOrFail($id);

        // Isolasi Akses Operator OPD
        $this->authorizeDocumentAccess($document);

        // Dynamic Timeline Steps
        $timeline = [
            [
                'step' => 1,
                'title' => 'Dokumen Dibuat',
                'description' => 'Draft dokumen perencanaan dibuat oleh operator OPD',
                'timestamp' => $document->created_at,
                'status' => 'completed',
                'icon' => 'fa-pen-to-square',
                'color' => 'amber',
            ],
            [
                'step' => 2,
                'title' => 'Pengajuan Verifikasi',
                'description' => $document->submitted_at ? 'Dokumen dikirim ke Bapperida untuk verifikasi' : 'Belum diajukan ke Bapperida',
                'timestamp' => $document->submitted_at,
                'status' => $document->submitted_at ? 'completed' : ($document->status === 'draft' ? 'pending' : 'completed'),
                'icon' => 'fa-paper-plane',
                'color' => 'blue',
            ],
            [
                'step' => 3,
                'title' => 'Pemeriksaan & Verifikasi',
                'description' => match (true) {
                    in_array($document->status, ['perlu_revisi', 'revisi', 'revision']) => 'Pemeriksaan selesai: Ditemukan catatan perbaikan',
                    in_array($document->status, ['disetujui', 'approved', 'dikunci', 'final']) => 'Pemeriksaan selesai: Dokumen disetujui',
                    in_array($document->status, ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'sedang_diperiksa', 'dikirim_ulang']) => 'Sedang diverifikasi oleh Tim Bapperida',
                    default => 'Menunggu pengajuan dokumen',
                },
                'timestamp' => in_array($document->status, ['draft']) ? null : $document->updated_at,
                'status' => match (true) {
                    in_array($document->status, ['perlu_revisi', 'revisi', 'revision', 'disetujui', 'approved', 'dikunci', 'final']) => 'completed',
                    in_array($document->status, ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'sedang_diperiksa', 'dikirim_ulang']) => 'active',
                    default => 'pending',
                },
                'icon' => match (true) {
                    in_array($document->status, ['perlu_revisi', 'revisi', 'revision']) => 'fa-rotate-left',
                    in_array($document->status, ['disetujui', 'approved', 'dikunci', 'final']) => 'fa-circle-check',
                    default => 'fa-magnifying-glass',
                },
                'color' => match (true) {
                    in_array($document->status, ['perlu_revisi', 'revisi', 'revision']) => 'rose',
                    in_array($document->status, ['disetujui', 'approved', 'dikunci', 'final']) => 'emerald',
                    default => 'indigo',
                },
            ],
            [
                'step' => 4,
                'title' => 'Dokumen Disetujui / Final',
                'description' => in_array($document->status, ['disetujui', 'approved', 'dikunci', 'final']) ? 'Dokumen telah disetujui dan dikunci (Read-Only)' : 'Belum difinalisasi',
                'timestamp' => in_array($document->status, ['disetujui', 'approved', 'dikunci', 'final']) ? $document->updated_at : null,
                'status' => in_array($document->status, ['disetujui', 'approved', 'dikunci', 'final']) ? 'completed' : 'pending',
                'icon' => 'fa-lock',
                'color' => 'emerald',
            ],
        ];

        // Format return back URL preserving filters
        $returnStatus = $request->query('return_status');
        $backUrl = $returnStatus && $returnStatus !== 'all'
            ? route('renja.index', ['status' => $returnStatus])
            : session('last_documents_url', route('renja.index'));

        return view('renja.show', compact('document', 'timeline', 'backUrl'));
    }

    /**
     * Form edit dokumen dengan WYSIWYG Quill Editor.
     */
    public function edit($id)
    {
        $document = RenjaDocument::with('opd')->findOrFail($id);
        $this->authorizeDocumentAccess($document);

        // Conditional Editing Guardrail: Hanya ijinkan edit jika status DRAFT atau REVISION
        if (in_array(strtolower($document->status), ['submitted', 'approved']) && Auth::user()->isOperator()) {
            return redirect()->route('renja.show', $id)
                ->with('error', 'Dokumen berstatus ' . strtoupper($document->status) . ' bersifat Read-Only dan tidak dapat diubah.');
        }

        return view('renja.edit', compact('document'));
    }

    /**
     * Update isi narasi dokumen Renja.
     * Note: Middleware StripBoldTags otomatis menghapus <b>, <strong>, dan style bold sebelum data disimpan.
     */
    public function update(Request $request, $id)
    {
        $document = RenjaDocument::findOrFail($id);
        $this->authorizeDocumentAccess($document);

        // Guardrail status
        if (in_array(strtolower($document->status), ['submitted', 'approved']) && Auth::user()->isOperator()) {
            return redirect()->route('renja.show', $id)
                ->with('error', 'Dokumen berstatus ' . strtoupper($document->status) . ' terkunci (Read-Only).');
        }

        $validated = $request->validate([
            'tahun_anggaran' => ['required', 'integer'],
            'jenis_dokumen' => ['required', 'string'],
            'latar_belakang' => ['nullable', 'string'],
            'landasan_hukum' => ['nullable', 'string'],
            'maksud_tujuan' => ['nullable', 'string'],
            'sistematika' => ['nullable', 'string'],
            'evaluasi_narasi' => ['nullable', 'string'],
            'isu_strategis_narasi' => ['nullable', 'string'],
            'tujuan_sasaran_narasi' => ['nullable', 'string'],
            'program_kegiatan_narasi' => ['nullable', 'string'],
            'penutup_narasi' => ['nullable', 'string'],
        ]);

        $document->update($validated);

        return redirect()->route('renja.show', $document->id)
            ->with('success', 'Perubahan narasi dokumen Renja berhasil diperbarui.');
    }

    /**
     * Verifikasi & Penguncian Dokumen (khusus Verifikator & Admin Bapperida).
     */
    /**
     * Submit dokumen dari OPD ke Bapperida untuk diverifikasi.
     */
    /**
     * Submit dokumen dari OPD ke Bapperida untuk diverifikasi (Khusus Akun OPD).
     */
    public function submit($id)
    {
        $user = Auth::user();

        // Jika akun Bapperida (Admin/Verifikator/Staff), langsung alihkan ke Finalisasi
        if ($user->isAdmin() || $user->isVerifikator() || $user->isStaff()) {
            return $this->finalize($id);
        }

        $opdId = $user->opd_id ?? MasterOpd::first()?->id;

        $document = RenjaDocument::where('opd_id', $opdId)->findOrFail($id);

        if (!in_array(strtolower($document->status), ['draft', 'belum_dikerjakan', 'perlu_revisi', 'revisi', 'revision', 'autofix_completed', 'autofix_confirmed'])) {
            return back()->with('error', 'Dokumen dengan status ' . strtoupper($document->status) . ' tidak dapat dikirim ulang.');
        }

        // Format Checker Guardrail: Hanya ijinkan submit jika format valid dan bebas blocking violations
        $isLampiranDoc = str_contains(strtolower($document->jenis_dokumen ?? ''), 'lampiran') || in_array($document->template?->code ?? '', ['RENJA_LAMPIRAN_MURNI', 'RENJA_LAMPIRAN_PERUBAHAN']);
        if ($isLampiranDoc) {
            $formatChecker = app(\App\Services\RenjaFormatCheckerService::class);
            $storedPath = $document->metadata['stored_filepath'] ?? null;
            $filePath = $storedPath ? (\Illuminate\Support\Facades\Storage::disk('private')->exists($storedPath) ? \Illuminate\Support\Facades\Storage::disk('private')->path($storedPath) : storage_path('app/' . $storedPath)) : null;
            
            $report = null;
            if ($filePath && file_exists($filePath)) {
                $report = $formatChecker->checkDocxFile($filePath, $document->template?->code);
            } else {
                $content = ($document->latar_belakang ?? '') . ' ' . ($document->landasan_hukum ?? '') . ' ' . ($document->penutup_narasi ?? '');
                $report = $formatChecker->inspectContentText($content, $document->template?->code);
            }

            if (!empty($report['table_issues'])) {
                return back()->with('error', 'Ditemukan ' . count($report['table_issues']) . ' tabel yang melampaui margin. Perbaiki format tabel terlebih dahulu.');
            }
        }

        $isRevision = in_array(strtolower($document->status), ['perlu_revisi', 'revisi', 'revision']);
        $targetStatus = $isRevision ? \App\Enums\DocumentStatus::RESUBMITTED->value : \App\Enums\DocumentStatus::SUBMITTED->value;
        $auditAction = $isRevision ? 'RESUBMITTED_TO_BAPPERIDA' : 'SUBMITTED_TO_BAPPERIDA';
        $auditNote = $isRevision ? 'Dokumen perbaikan berhasil dikirim ulang oleh OPD ke Admin Bapperida.' : 'Dokumen berhasil dikirim oleh OPD ke Admin Bapperida untuk verifikasi.';

        $document->update([
            'status' => $targetStatus,
            'submitted_at' => now(),
            'metadata' => array_merge($document->metadata ?? [], [
                'audit_trail' => array_merge($document->metadata['audit_trail'] ?? [], [[
                    'action' => $auditAction,
                    'notes' => $auditNote,
                    'timestamp' => now()->toIso8601String(),
                    'user_name' => $user->nama_lengkap ?? $user->name ?? 'Operator OPD',
                ]])
            ])
        ]);

        return back()->with('success', 'Dokumen berhasil dikirim ke Admin Bapperida untuk proses verifikasi.');
    }

    /**
     * Finalisasi Dokumen (Khusus Akun Admin Bapperida).
     */
    public function finalize($id)
    {
        $user = Auth::user();
        $document = RenjaDocument::findOrFail($id);

        $this->authorizeDocumentAccess($document);

        $document->update([
            'status' => 'final',
            'metadata' => array_merge($document->metadata ?? [], [
                'audit_trail' => array_merge($document->metadata['audit_trail'] ?? [], [[
                    'action' => 'FINALIZED_BY_ADMIN_BAPPERIDA',
                    'notes' => 'Dokumen telah difinalisasi dan dikunci oleh Admin Bapperida.',
                    'timestamp' => now()->toIso8601String(),
                    'user_name' => $user->nama_lengkap ?? $user->name ?? 'Admin Bapperida',
                ]])
            ])
        ]);

        return back()->with('success', 'Dokumen berhasil difinalisasi dan dikunci (Status FINAL).');
    }

    /**
     * Tampilan Pratinjau Dokumen Tingkat Tinggi (High-Fidelity Document Rendering PDF Viewer).
     */
    public function preview($id)
    {
        $document = RenjaDocument::with(['opd', 'template', 'sections', 'updatedByUser'])->find($id);
        if (!$document) {
            return redirect()->route('renja.index')->with('warning', 'Dokumen tidak ditemukan atau telah dibersihkan.');
        }
        $this->authorizeDocumentAccess($document);

        $isLampiranDoc = $document->isLampiranPerbub();
        $isLampiranRequest = request()->is('*lampiran*') || request()->routeIs('*lampiran*') || request()->boolean('is_lampiran') || $isLampiranDoc;

        // Jika rute/request memanggil preview Lampiran tetapi ID yang diberikan adalah ID RENJA Induk
        if ($isLampiranRequest) {
            if (!$isLampiranDoc) {
                $opdService = app(\App\Services\OpdDocumentService::class);
                $jenisSource = str_contains(strtolower($document->jenis_dokumen ?? ''), 'perubahan') ? 'perubahan' : 'murni';
                $document = $opdService->generateLampiranPerbub($document->opd_id, $document->tahun_anggaran, $jenisSource);
                $document->load(['opd', 'template', 'sections', 'updatedByUser']);
            }
            $isLampiranDoc = true;

            $correctTemplateCode = str_contains(strtolower($document->jenis_dokumen ?? ''), 'perubahan') ? 'RENJA_LAMPIRAN_PERUBAHAN' : 'RENJA_LAMPIRAN_MURNI';
            $correctTemplate = \App\Models\DocumentTemplate::where('code', $correctTemplateCode)->first();
            if ($correctTemplate && $document->template_id !== $correctTemplate->id) {
                $document->update(['template_id' => $correctTemplate->id]);
                $document->load('template');
            }

            // Clean legacy front matter sections from database for this document
            $document->sections()->where(function($query) {
                $query->whereIn('section_type', ['cover', 'preface', 'table_of_contents', 'list_of_tables', 'list_of_figures', 'list_of_charts', 'list_of_appendices'])
                      ->orWhereIn('bab_code', ['COVER', 'PENGESAHAN', 'PREFACE', 'TOC', 'LOT', 'LOF', 'LOC', 'LOA'])
                      ->orWhere('bab_title', 'like', '%kata pengantar%')
                      ->orWhere('bab_title', 'like', '%daftar isi%')
                      ->orWhere('bab_title', 'like', '%lembar pengesahan%')
                      ->orWhere('bab_title', 'like', '%cover%');
            })->delete();
            
            $document->unsetRelation('sections');
            $document->load('sections');
        }

        $pdfStreamUrl = route('renja.preview-pdf', ['id' => $document->id, 'is_lampiran' => $isLampiranDoc ? 1 : 0]);
        $downloadWordUrl = route('renja.exportWord', $document->id);
        $downloadPdfUrl = route('renja.preview-pdf', ['id' => $document->id, 'download' => 1, 'is_lampiran' => $isLampiranDoc ? 1 : 0]);
        
        $originalFilename = $document->metadata['original_filename'] ?? ($document->original_filename ?? ($document->jenis_dokumen . ' TA ' . $document->tahun_anggaran . '.docx'));
        $originalFileSize = $document->metadata['original_file_size'] ?? ($document->original_file_size ?? null);
        $originalFileHash = $document->metadata['original_file_hash'] ?? ($document->original_file_hash ?? null);
        $originalUploadedAt = $document->metadata['uploaded_at'] ?? $document->created_at;

        $romawiHeader = \App\Services\RenjaAutoFixService::getRomanHeaderForOpd($document->opd);

        return view('renja.preview', compact(
            'document',
            'romawiHeader',
            'pdfStreamUrl',
            'downloadWordUrl',
            'downloadPdfUrl',
            'originalFilename',
            'originalFileSize',
            'originalFileHash',
            'originalUploadedAt'
        ));
    }

    /**
     * Endpoint Streaming File PDF Hasil Render Server-Side (High-Fidelity).
     */
    public function previewPdf(Request $request, $id)
    {
        @set_time_limit(300);

        try {
            $document = RenjaDocument::with(['opd', 'template', 'sections'])->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $user = Auth::user();
            $ta = date('Y');
            return redirect()->route('renja.workspace', ['tahun_anggaran' => $ta])
                ->with('warning', 'Dokumen draf lama telah diperbarui dengan file baru. Silakan klik Pratinjau pada workspace.');
        }
        $this->authorizeDocumentAccess($document);

        $previewService = app(\App\Services\DocumentPreviewService::class);
        $forceReconvert = $request->boolean('force', false);

        $isLampiranDoc = $document->isLampiranPerbub();
        $isLampiranRequest = $request->is('*lampiran*') 
            || $request->routeIs('*lampiran*') 
            || $request->boolean('is_lampiran') 
            || $isLampiranDoc;

        if ($isLampiranRequest) {
            if (!$isLampiranDoc) {
                $opdService = app(\App\Services\OpdDocumentService::class);
                $jenisSource = str_contains(strtolower($document->jenis_dokumen ?? ''), 'perubahan') ? 'perubahan' : 'murni';
                $document = $opdService->generateLampiranPerbub($document->opd_id, $document->tahun_anggaran, $jenisSource);
                $document->load(['opd', 'template', 'sections']);
            }
            $isLampiranDoc = true;

            $correctTemplateCode = str_contains(strtolower($document->jenis_dokumen ?? ''), 'perubahan') ? 'RENJA_LAMPIRAN_PERUBAHAN' : 'RENJA_LAMPIRAN_MURNI';
            $correctTemplate = \App\Models\DocumentTemplate::where('code', $correctTemplateCode)->first();
            if ($correctTemplate && $document->template_id !== $correctTemplate->id) {
                $document->update(['template_id' => $correctTemplate->id]);
                $document->load('template');
                $previewService->invalidateCache($document->id);
            }

            // Clean legacy front matter sections from database for this document
            $document->sections()->where(function($query) {
                $query->whereIn('section_type', ['cover', 'preface', 'table_of_contents', 'list_of_tables', 'list_of_figures', 'list_of_charts', 'list_of_appendices'])
                      ->orWhereIn('bab_code', ['COVER', 'PENGESAHAN', 'PREFACE', 'TOC', 'LOT', 'LOF', 'LOC', 'LOA'])
                      ->orWhere('bab_title', 'like', '%kata pengantar%')
                      ->orWhere('bab_title', 'like', '%daftar isi%')
                      ->orWhere('bab_title', 'like', '%lembar pengesahan%')
                      ->orWhere('bab_title', 'like', '%cover%');
            })->delete();
            
            $document->unsetRelation('sections');
            $document->load('sections');
        }

        $result = $previewService->getOrGeneratePdf($document, $forceReconvert);

        if (!$result['success'] || empty($result['pdf_path']) || !file_exists($result['pdf_path'])) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Gagal merender file PDF.',
                ], 500);
            }
            return back()->with('error', $result['error'] ?? 'Pratinjau dokumen belum dapat ditampilkan.');
        }

        $pdfPath = $result['pdf_path'];
        $downloadFilename = $this->buildExportFilename($document, 'pdf');
        $disposition = $request->boolean('download', false) ? 'attachment' : 'inline';

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$downloadFilename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Tampilan Cetak / Pratinjau Dokumen Resmi (Terintegrasi ke High-Fidelity PDF Viewer).
     */
    public function print($id)
    {
        return $this->preview($id);
    }

    /**
     * Download original uploaded DOCX binary directly.
     */
    public function originalFile($id)
    {
        $document = RenjaDocument::findOrFail($id);
        $this->authorizeDocumentAccess($document);

        if ($document->source_type === 'upload_word' && !empty($document->metadata['original_file_path'])) {
            $filePath = $document->metadata['original_file_path'];
            if (\Illuminate\Support\Facades\Storage::disk('local')->exists($filePath)) {
                $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($filePath);
                return response()->file($fullPath, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"'
                ]);
            }
        }

        abort(404, 'File original tidak ditemukan.');
    }

    /**
     * Export PDF Dokumen Perencanaan Resmi Folio F4 (215 x 330 mm).
     */
    public function exportPdf($id)
    {
        try {
            $document = RenjaDocument::with(['opd', 'template', 'sections'])->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('renja.workspace', ['tahun_anggaran' => date('Y')])
                ->with('warning', 'Dokumen tidak ditemukan atau telah diperbarui. Silakan gunakan pratinjau dari workspace.');
        }
        $this->authorizeDocumentAccess($document);

        if ($document->source_type === 'upload_word') {
            return redirect()->route('renja.print', $document->id);
        }

        $isLampiranPerbub = $document->isLampiranPerbub();
        $effectiveSections = $document->getEffectiveSections();
        $groupedBabs = $effectiveSections->groupBy('bab_code');
        $frontSections = $isLampiranPerbub ? collect() : $document->sections->whereIn('section_type', ['cover', 'preface', 'table_of_contents', 'list_of_tables', 'list_of_figures', 'list_of_charts', 'list_of_appendices']);
        $isRenja = str_contains(strtoupper($document->jenis_dokumen), 'RENJA');
        $romawiHeader = \App\Services\RenjaAutoFixService::getRomanHeaderForOpd($document->opd);

        return view('renja.pdf', compact('document', 'groupedBabs', 'frontSections', 'isRenja', 'romawiHeader', 'effectiveSections'));
    }

    /**
     * Export Dokumen Renja Lengkap ke MS Word (.docx) Format F4 Folio.
     */
    public function exportWord($id)
    {
        $document = RenjaDocument::with(['opd', 'template', 'sections'])->findOrFail($id);
        $this->authorizeDocumentAccess($document);

        if ($document->source_type === 'upload_word' && !empty($document->metadata['original_file_path']) && empty($document->metadata['editor_modified'])) {
            $filePath = $document->metadata['original_file_path'];
            if (\Illuminate\Support\Facades\Storage::disk('local')->exists($filePath)) {
                return \Illuminate\Support\Facades\Storage::disk('local')->download($filePath, $document->metadata['original_filename'] ?? 'document.docx');
            }
        }

        // Jika template terhubung, gunakan konfigurasinya. Jika tanpa template (MANUAL), gunakan null.
        $template = $document->template;
        
        $formatConfig = $template->format_config ?? [];
        $isRenja = str_contains(strtoupper($document->jenis_dokumen), 'RENJA');
        $allowBold = $formatConfig['allow_bold'] ?? (!$isRenja);
        $fontFamily = $formatConfig['font_family'] ?? 'Bookman Old Style';
        $fontSize = $formatConfig['font_size_pt'] ?? 12;

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName($fontFamily);
        $phpWord->setDefaultFontSize($fontSize);

        // F4 Page Settings (Folio 21.5cm x 33.0cm, Margin 2.0cm seragam)
        $paperWidthMm = $formatConfig['paper_width_mm'] ?? 215;
        $paperHeightMm = $formatConfig['paper_height_mm'] ?? 330;
        $paperWidthCm = $paperWidthMm / 10;
        $paperHeightCm = $paperHeightMm / 10;
        $marginTop = $formatConfig['margin_top_cm'] ?? 2.0;
        $marginBottom = $formatConfig['margin_bottom_cm'] ?? 2.0;
        $marginLeft = $formatConfig['margin_left_cm'] ?? 2.0;
        $marginRight = $formatConfig['margin_right_cm'] ?? 2.0;
        $orientation = $formatConfig['orientation'] ?? 'portrait';

        $sectionStyle = [
            'pageSizeW' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip($paperWidthCm),
            'pageSizeH' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip($paperHeightCm),
            'marginTop' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip($marginTop),
            'marginBottom' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip($marginBottom),
            'marginLeft' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip($marginLeft),
            'marginRight' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip($marginRight),
        ];

        $section = $phpWord->addSection($sectionStyle);

        // 1. COVER DOKUMEN (Hanya Jika Template Menggunakan Cover dan BUKAN Dokumen Lampiran)
        $isLampiran = $document->isLampiranPerbub();
        if (!$isLampiran && ($formatConfig['has_cover'] ?? false) && !empty($document->cover_data)) {
            $cover = $document->cover_data;
            $section->addTextBreak(3);
            $section->addText(strtoupper($cover['judul_dokumen'] ?? ($template?->name ?? 'DOKUMEN PERENCANAAN')), ['size' => 16, 'name' => $fontFamily, 'bold' => true], ['align' => 'center']);
            $section->addText(strtoupper($cover['nama_opd'] ?? $document->opd?->nama_opd ?? ''), ['size' => 14, 'name' => $fontFamily, 'bold' => true], ['align' => 'center']);
            $section->addText('TAHUN ANGGARAN ' . ($cover['tahun_anggaran'] ?? $document->tahun_anggaran), ['size' => 12, 'name' => $fontFamily, 'bold' => true], ['align' => 'center']);
            $section->addTextBreak(6);

            $section->addText(strtoupper($cover['nama_pemda'] ?? 'PEMERINTAH KABUPATEN CIREBON'), ['size' => 14, 'name' => $fontFamily, 'bold' => true], ['align' => 'center']);
            $section->addText(strtoupper($cover['lokasi'] ?? 'SUMBER') . ' - ' . ($cover['tahun_terbit'] ?? date('Y')), ['size' => 12, 'name' => $fontFamily, 'bold' => false], ['align' => 'center']);
            $section->addPageBreak();
        }

        // 2. HEADER RESMI RENJA (HANYA BERLAKU UNTUK DOKUMEN RENJA / LAMPIRAN)
        if ($isRenja) {
            $romawiNo = strtoupper($document->opd?->nomor_lampiran_romawi ?? 'LAMPIRAN I');
            if (!str_contains($romawiNo, 'LAMPIRAN')) {
                $romawiNo = 'LAMPIRAN ' . $romawiNo;
            }
            $taRenja = (string) ($document->tahun_anggaran ?? '2027');
            $isPerubahan = str_contains(strtolower($document->jenis_dokumen ?? ''), 'perubahan');
            $taPeraturan = $isPerubahan ? $taRenja : (((int)$taRenja > 2000) ? (string)((int)$taRenja - 1) : '2026');

            // Left Indent 9.5 cm (Posisi Blok di separuh kanan kertas F4), Teks Rata Kiri (Left-aligned)
            $leftIndentTwip = \PhpOffice\PhpWord\Shared\Converter::cmToTwip(9.5);
            $headerParagraphStyle = [
                'align' => 'left',
                'leftIndent' => $leftIndentTwip,
                'spaceBefore' => 0,
                'spaceAfter' => 0,
                'lineSpacing' => 1.15
            ];
            $headerFontStyle = ['size' => 12, 'name' => $fontFamily, 'bold' => false];

            $jenisPeraturan = $isPerubahan ? 'KEPUTUSAN BUPATI CIREBON' : 'PERATURAN BUPATI CIREBON';
            $judulRenja = $isPerubahan ? 'PERUBAHAN RENCANA KERJA PERANGKAT DAERAH TAHUN ' . $taRenja : 'RENCANA KERJA PERANGKAT DAERAH TAHUN ' . $taRenja;

            $section->addText($romawiNo, $headerFontStyle, $headerParagraphStyle);
            $section->addText($jenisPeraturan, $headerFontStyle, $headerParagraphStyle);
            $section->addText('NOMOR           TAHUN ' . $taPeraturan, $headerFontStyle, $headerParagraphStyle);
            $section->addText('TENTANG', $headerFontStyle, $headerParagraphStyle);
            $section->addText($judulRenja, $headerFontStyle, $headerParagraphStyle);
            $section->addTextBreak(1);
        }

        // 3. BAGIAN AWAL & BAGIAN UTAMA SECTIONS (Gunakan Live Effective Sections jika Lampiran)
        $sections = $isLampiran ? $document->getEffectiveSections() : $document->sections;
        $groupedSections = $sections->groupBy('bab_code');
        $frontSections = $isLampiran ? collect() : $sections->whereIn('section_type', ['cover', 'preface', 'table_of_contents', 'list_of_tables', 'list_of_figures', 'list_of_charts', 'list_of_appendices']);
        if ($isLampiran) {
            $allowBold = false;
        }
        $isRenja = str_contains(strtoupper($document->jenis_dokumen), 'RENJA');

        $isFirstBab = true;
        foreach ($groupedSections as $babCode => $secs) {
            $firstSec = $secs->first();
            $babTitle = $firstSec->bab_title ?? '';

            // Halaman Baru untuk setiap BAB (Page Break) HANYA untuk dokumen SELAIN Renja (BR-PAGE-01 & BR-PAGE-02)
            if (!$isRenja && (!$isFirstBab || $frontSections->count() > 0 || ($formatConfig['has_cover'] ?? false))) {
                $section->addPageBreak();
            }
            $isFirstBab = false;

            // Title Bab (Centered)
            if (!empty($babCode) && str_contains(strtoupper($babCode), 'BAB')) {
                $section->addText(strtoupper($babCode), ['size' => $fontSize, 'name' => $fontFamily, 'bold' => $allowBold], ['align' => 'center']);
                if (!empty($babTitle)) {
                    $section->addText(strtoupper($babTitle), ['size' => $fontSize, 'name' => $fontFamily, 'bold' => $allowBold], ['align' => 'center']);
                }
                $section->addTextBreak(1);
            }

            foreach ($secs as $sec) {
                if ($sec->section_type === 'chapter') {
                    continue;
                }

                $cleanTitle = preg_replace('/^\d+(\.\d+)*\s*/', '', $sec->sub_bab_title ?? '');
                if (!empty($sec->sub_bab_code)) {
                    $section->addText($sec->sub_bab_code . '. ' . strtoupper($cleanTitle), ['size' => $fontSize, 'name' => $fontFamily, 'bold' => $allowBold]);
                } else {
                    $section->addText(strtoupper($cleanTitle), ['size' => $fontSize, 'name' => $fontFamily, 'bold' => $allowBold], ['align' => 'center']);
                }

                if (!empty($sec->content)) {
                    $cleanHtml = $allowBold ? $sec->content : $this->sanitizeHtmlForRenja($sec->content);
                    try {
                        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $cleanHtml, false, false);
                    } catch (\Exception $e) {
                        $section->addText(strip_tags($sec->content), ['size' => $fontSize, 'name' => $fontFamily, 'bold' => $allowBold]);
                    }
                }
                $section->addTextBreak(1);
            }
        }

        $filenameDocx = $this->buildExportFilename($document, 'docx');
        $storageDir = storage_path('app/public');
        if (!file_exists($storageDir)) {
            @mkdir($storageDir, 0777, true);
        }

        // 1. PRIMARY NATIVE DOCX EXPORTER (MENGGUNAKAN PYTHON-DOCX DENGAN LAYOUT RESMI F4 FOLIO)
        $pythonScript = base_path('scripts/generate_renja_docx.py');
        if (file_exists($pythonScript)) {
            $payload = [
                'document_type' => $document->template?->code,
                'title' => $document->jenis_dokumen ?? 'RENJA Murni',
                'opd_name' => $document->opd?->nama_opd ?? 'OPD',
                'tahun_anggaran' => $document->tahun_anggaran ?? 2027,
                'nomor_lampiran_romawi' => $document->opd?->nomor_lampiran_romawi ?? 'LAMPIRAN I',
                'cover_data' => $document->cover_data,
                'sections' => $document->sections->map(fn($s) => [
                    'bab_code' => $s->bab_code,
                    'bab_title' => $s->bab_title,
                    'sub_bab_code' => $s->sub_bab_code,
                    'sub_bab_title' => $s->sub_bab_title,
                    'content' => $s->content,
                ])->toArray(),
            ];

            $jsonTempPath = $storageDir . '/doc_payload_' . $document->id . '_' . time() . '.json';
            $docxTempPath = $storageDir . '/' . $filenameDocx;

            file_put_contents($jsonTempPath, json_encode($payload, JSON_UNESCAPED_UNICODE));

            $cmd = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg($jsonTempPath) . " " . escapeshellarg($docxTempPath);
            @exec($cmd, $pyOut, $pyReturn);

            @unlink($jsonTempPath);

            if ($pyReturn === 0 && file_exists($docxTempPath) && filesize($docxTempPath) > 500) {
                return response()->download($docxTempPath, $filenameDocx, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'Content-Disposition' => 'attachment; filename="' . $filenameDocx . '"',
                ])->deleteFileAfterSend(true);
            }
        }

        // 2. JIKA ZIPARCHIVE PHP TERSEDIA: GENERATE .DOCX DENGAN PHPWORD WORD2007
        if (class_exists('ZipArchive')) {
            $tempPath = $storageDir . '/' . $filenameDocx;

            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempPath);

            return response()->download($tempPath, $filenameDocx, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="' . $filenameDocx . '"',
            ])->deleteFileAfterSend(true);
        }

        // 3. FALLBACK AMAN: GENERATE MS WORD COMPATIBLE (.DOC)
        $filenameDoc = $this->buildExportFilename($document, 'doc');
        $tempPathDoc = storage_path('app/public/' . $filenameDoc);

        $htmlOutput = '
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
        <meta charset="utf-8">
        <title>' . ($document->opd->nama_opd ?? 'Dokumen') . '</title>
        <!--[if gte mso 9]>
        <xml>
         <w:WordDocument>
          <w:View>Print</w:View>
          <w:Zoom>100</w:Zoom>
          <w:DoNotOptimizeForBrowser/>
         </w:WordDocument>
        </xml>
        <![endif]-->
        <style>
         @page {
             size: ' . ($paperWidthCm) . 'cm ' . ($paperHeightCm) . 'cm;
             margin: ' . ($marginTop) . 'cm ' . ($marginRight) . 'cm ' . ($marginBottom) . 'cm ' . ($marginLeft) . 'cm;
             mso-header-margin: 1.0cm;
             mso-footer-margin: 1.0cm;
         }
         @page Section1 {
             size: ' . ($paperWidthMm) . 'mm ' . ($paperHeightMm) . 'mm;
             margin: ' . ($marginTop * 10) . 'mm ' . ($marginRight * 10) . 'mm ' . ($marginBottom * 10) . 'mm ' . ($marginLeft * 10) . 'mm;
             mso-page-orientation: ' . $orientation . ';
             mso-header-margin: 1.0cm;
             mso-footer-margin: 1.0cm;
         }
         div.Section1 {
             page: Section1;
         }
         body {
             font-family: "' . $fontFamily . '", serif;
             font-size: ' . $fontSize . 'pt;
             line-height: 1.5;
             margin: 0;
             padding: 0;
         }
         .page-break {
             page-break-before: always;
             mso-break-type: page;
         }
         table {
             border-collapse: collapse;
             width: 100%;
          }
         table, th, td {
             border: 1px solid black;
             padding: 4px 6px;
             font-size: ' . $fontSize . 'pt;
             font-family: "' . $fontFamily . '", serif;
         }
        </style>
        </head>
        <body class="Section1">
        <div class="Section1">';

        // 1. Cover HTML
        if (($formatConfig['has_cover'] ?? false) && !empty($document->cover_data)) {
            $cover = $document->cover_data;
            $htmlOutput .= '<div style="text-align:center; font-family:\'' . $fontFamily . '\', serif;">';
            $htmlOutput .= '<br><br><br>';
            $htmlOutput .= '<h1 style="font-size:16pt; font-weight:bold;">' . strtoupper($cover['judul_dokumen'] ?? $template->name) . '</h1>';
            $htmlOutput .= '<h2 style="font-size:14pt; font-weight:bold;">' . strtoupper($cover['nama_opd'] ?? $document->opd->nama_opd ?? '') . '</h2>';
            $htmlOutput .= '<h3 style="font-size:12pt; font-weight:bold;">TAHUN ANGGARAN ' . ($cover['tahun_anggaran'] ?? $document->tahun_anggaran) . '</h3>';
            $htmlOutput .= '<br><br><br><br><br>';
            $htmlOutput .= '<h2 style="font-size:14pt; font-weight:bold;">' . strtoupper($cover['nama_pemda'] ?? 'PEMERINTAH KABUPATEN CIREBON') . '</h2>';
            $htmlOutput .= '<h3 style="font-size:12pt;">' . strtoupper($cover['lokasi'] ?? 'SUMBER') . ' - ' . ($cover['tahun_terbit'] ?? date('Y')) . '</h3>';
            $htmlOutput .= '</div>';
            $htmlOutput .= '<br style="page-break-before:always; clear:both; mso-break-type:page;" />';
        }

        // 2. Header Resmi Renja (Hanya untuk Dokumen Renja)
        if (str_contains(strtoupper($document->jenis_dokumen ?? ''), 'RENJA')) {
            $romawiNo = strtoupper($document->opd->nomor_lampiran_romawi ?? 'LAMPIRAN I');
            if (!str_contains($romawiNo, 'LAMPIRAN')) {
                $romawiNo = 'LAMPIRAN ' . $romawiNo;
            }
            $taRenja = $document->tahun_anggaran ?? '2027';
            $taPerbup = ((int)$taRenja > 2000) ? ((int)$taRenja - 1) : '2026';

            $htmlOutput .= '<div style="margin-left: 9.5cm; text-align: left; font-size: 12pt; font-family: \'Bookman Old Style\', serif; line-height: 1.15;">';
            $htmlOutput .= '<div>' . $romawiNo . '</div>';
            $htmlOutput .= '<div>PERATURAN BUPATI CIREBON</div>';
            $htmlOutput .= '<div>NOMOR &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; TAHUN ' . $taPerbup . '</div>';
            $htmlOutput .= '<div>TENTANG</div>';
            $htmlOutput .= '<div>RENCANA KERJA PERANGKAT DAERAH TAHUN ' . $taRenja . '</div>';
            $htmlOutput .= '</div><br>';
        }

        // 3. Sections
        $isFirstBab = true;
        foreach ($groupedSections as $babCode => $secs) {
            $firstSec = $secs->first();
            $babTitle = $firstSec->bab_title ?? '';

            if (!$isFirstBab || ($formatConfig['has_cover'] ?? false)) {
                $htmlOutput .= '<br style="page-break-before:always; clear:both; mso-break-type:page;" />';
            }
            $isFirstBab = false;

            if (!empty($babCode) && str_contains(strtoupper($babCode), 'BAB')) {
                $htmlOutput .= '<div style="text-align:center; font-size:' . $fontSize . 'pt; font-weight:' . ($allowBold ? 'bold' : 'normal') . '; text-transform:uppercase; margin-bottom:12pt;">';
                $htmlOutput .= '<div>' . strtoupper($babCode) . '</div>';
                if (!empty($babTitle)) {
                    $htmlOutput .= '<div>' . strtoupper($babTitle) . '</div>';
                }
                $htmlOutput .= '</div>';
            }

            foreach ($secs as $sec) {
                if ($sec->section_type === 'chapter') {
                    continue;
                }

                $cleanTitle = preg_replace('/^\d+(\.\d+)*\s*/', '', $sec->sub_bab_title);
                if (!empty($sec->sub_bab_code)) {
                    $htmlOutput .= '<div style="font-weight:' . ($allowBold ? 'bold' : 'normal') . '; font-size:' . $fontSize . 'pt; margin-top:12pt; margin-bottom:6pt;">' . $sec->sub_bab_code . '. ' . strtoupper($cleanTitle) . '</div>';
                } else {
                    $htmlOutput .= '<div style="font-weight:' . ($allowBold ? 'bold' : 'normal') . '; font-size:' . $fontSize . 'pt; margin-top:12pt; margin-bottom:6pt; text-align:center;">' . strtoupper($cleanTitle) . '</div>';
                }

                if (!empty($sec->content)) {
                    $cleanContent = $allowBold ? $sec->content : $this->sanitizeHtmlForRenja($sec->content);
                    $htmlOutput .= '<div>' . $cleanContent . '</div>';
                }
            }
        }

        $htmlOutput .= '</div></body></html>';

        file_put_contents($tempPathDoc, $htmlOutput);

        return response()->download($tempPathDoc, $filenameDoc, [
            'Content-Type' => 'application/msword',
            'Content-Disposition' => 'attachment; filename="' . $filenameDoc . '"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Hapus Dokumen Renja beserta seluruh section & tabel terkait.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $opdId = $user->opd_id ?? MasterOpd::first()?->id;

        try {
            $this->opdDocumentService->deleteDocument((int)$id, (int)$opdId);
            return redirect()->route('renja.index')->with('success', 'Dokumen berhasil dihapus dari workspace.');
        } catch (\Throwable $e) {
            return redirect()->route('renja.index')->with('error', $e->getMessage());
        }
    }

    /**
     * Download Blank Word Template (.docx) for RENJA Murni or RENJA Perubahan.
     */
    public function downloadTemplate(Request $request, string $templateCode, TemplatePersonalizerService $personalizerService)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        // Whitelist template codes allowed for download
        $normalizedCode = strtoupper(trim($templateCode));
        $allowedTemplates = ['RENJA_MURNI', 'RENJA_PERUBAHAN'];

        if (!in_array($normalizedCode, $allowedTemplates)) {
            abort(404, 'Template dokumen tidak ditemukan atau tidak diperbolehkan untuk diunduh.');
        }

        // Validate template exists and is active in database
        $template = DocumentTemplate::where('code', $normalizedCode)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            abort(404, 'Master Template tidak ditemukan atau sedang tidak aktif.');
        }

        // Resolve OPD with Strict Authorization
        $isOperator = $user->isOperator();
        $isAdminOrStaff = $user->isAdmin() || $user->isVerifikator() || $user->isStaff();

        $opdId = null;
        if ($isOperator) {
            // Operator MUST strictly use their assigned OPD
            if (!$user->opd_id) {
                abort(403, 'Akses Ditolak: Akun Operator Anda belum terasosiasi dengan Perangkat Daerah.');
            }

            // IDOR Protection: If operator attempts to request another OPD explicitly, deny access
            if ($request->has('opd_id') && (int)$request->query('opd_id') !== (int)$user->opd_id) {
                abort(403, 'Akses Ditolak: Operator hanya berhak mengunduh template untuk Perangkat Daerah sendiri.');
            }

            $opdId = (int)$user->opd_id;
        } elseif ($isAdminOrStaff) {
            // Admin/Verifikator can specify target OPD or fallback to assigned OPD/default
            if ($request->has('opd_id') && !empty($request->query('opd_id'))) {
                $requestedOpd = MasterOpd::find($request->query('opd_id'));
                if (!$requestedOpd) {
                    abort(404, 'Data Perangkat Daerah tidak ditemukan.');
                }
                $opdId = (int)$requestedOpd->id;
            } else {
                $opdId = $user->opd_id ?? MasterOpd::first()?->id;
            }
        } else {
            // Other user role without OPD
            if (!$user->opd_id) {
                abort(403, 'Akses Ditolak: User tidak terasosiasi dengan Perangkat Daerah.');
            }
            $opdId = (int)$user->opd_id;
        }

        $opd = MasterOpd::find($opdId);
        if (!$opd) {
            abort(404, 'Data Perangkat Daerah tidak ditemukan.');
        }

        // Resolve Tahun Anggaran
        $activeYear = (int)session('active_ta', (int)date('Y'));
        $defaultYear = ($normalizedCode === 'RENJA_PERUBAHAN') ? $activeYear : ($activeYear + 1);

        $tahunInput = $request->query('tahun_anggaran', $defaultYear);
        if (!is_numeric($tahunInput) || (int)$tahunInput < 2020 || (int)$tahunInput > 2099) {
            $tahunAnggaran = $defaultYear;
        } else {
            $tahunAnggaran = (int)$tahunInput;
        }

        try {
            $result = $personalizerService->generateBlankTemplateDocx($normalizedCode, [
                'opd_id' => $opd->id,
                'opd_name' => $opd->nama_opd,
                'tahun_anggaran' => $tahunAnggaran,
                'logo_path' => null,
            ]);

            return response()->download($result['file_path'], $result['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            Log::error("[RenjaDocumentController::downloadTemplate] Error: " . $e->getMessage(), [
                'template_code' => $normalizedCode,
                'opd_id' => $opdId,
                'tahun' => $tahunAnggaran,
            ]);
            abort(500, 'Terjadi kesalahan sistem saat membuat template dokumen.');
        }
    }

    /**
     * Sanitasi HTML Ketat Dokumen Renja (Eradikasi Zero Bold):
     * 1. Hapus tag <b>, </b>, <strong>, </strong> total.
     * 2. Ubah <th> menjadi <td> (karena PhpWord merender <th> sebagai bold).
     * 3. Hapus seluruh inline style font-weight:bold, font-weight:700, font-weight:600, dll.
     * 4. Pertahankan italic, underline, alignment, numbering, dan struktur tabel.
     */
    private function sanitizeHtmlForRenja(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Hapus tag bold (b & strong)
        $clean = preg_replace('/<\/?(b|strong)\b[^>]*>/i', '', $html);

        // Ubah th & /th menjadi td & /td
        $clean = preg_replace('/<th\b[^>]*>/i', '<td>', $clean);
        $clean = preg_replace('/<\/th>/i', '</td>', $clean);

        // Netralkan inline style font-weight tebal (bold, 600, 700, 800, 900)
        $clean = preg_replace('/font-weight\s*:\s*(bold|bolder|[5-9]\d{2})/i', 'font-weight:normal', $clean);

        // Strip tag tidak diizinkan
        return strip_tags($clean, '<p><br><table><tr><td><ul><ol><li><i><u><em><span><div>');
    }

    /**
     * Helper Resolusi OPD ID berdasarkan Akun Login.
     */
    private function getEffectiveOpdIdForUser($user): int
    {
        if ($user->opd_id) {
            return $user->opd_id;
        }

        if ($user->opd) {
            return $user->opd->id;
        }

        $bapperida = MasterOpd::where('nama_opd', 'LIKE', '%bapperida%')
            ->orWhere('nama_opd', 'LIKE', '%Badan Perencanaan Pembangunan%')
            ->first();

        return $bapperida?->id ?? MasterOpd::first()?->id ?? 1;
    }

    /**
     * Helper Isolasi Dokumen untuk Operator OPD.
     */
    private function authorizeDocumentAccess(RenjaDocument $document): void
    {
        $user = Auth::user();
        if ($user && $user->isOperator() && (int)$document->opd_id !== (int)$user->opd_id) {
            abort(403, 'Akses Ditolak: Anda hanya berhak mengelola dokumen Renja milik OPD sendiri.');
        }
    }

    /**
     * Ubah Nama Dokumen (Jenis Dokumen & Judul Dokumen).
     */
    public function updateDocumentName(Request $request, $id)
    {
        $request->validate([
            'nama_dokumen' => ['required', 'string', 'max:255'],
        ]);

        $document = RenjaDocument::findOrFail($id);
        $this->authorizeDocumentAccess($document);

        $newTitle = trim($request->input('nama_dokumen'));

        $coverData = $document->cover_data ?? [];
        $coverData['judul_dokumen'] = $newTitle;

        $document->update([
            'jenis_dokumen' => $newTitle,
            'cover_data' => $coverData,
            'metadata' => array_merge($document->metadata ?? [], [
                'audit_trail' => array_merge($document->metadata['audit_trail'] ?? [], [[
                    'action' => 'DOCUMENT_NAME_UPDATED',
                    'notes' => "Nama dokumen diubah menjadi: {$newTitle}",
                    'timestamp' => now()->toIso8601String(),
                    'user_name' => Auth::user()->nama_lengkap ?? Auth::user()->name ?? 'User',
                ]])
            ])
        ]);

        return back()->with('success', "Nama dokumen berhasil diubah menjadi: {$newTitle}");
    }

    /**
     * Hasilkan Nama File Ekspor Standar sesuai Jenis Dokumen + Nama OPD + Tahun Anggaran.
     * Format: <JENIS_DOKUMEN>_<NAMA_OPD>_TA_<TAHUN>.<ext>
     * Contoh: RENJA_Murni_Kecamatan_Depok_TA_2027.docx
     */
    public function buildExportFilename(RenjaDocument $document, string $extension = 'docx'): string
    {
        // Jenis dokumen — ambil kata kunci singkat (RENJA Murni / RENJA Perubahan / Lampiran Perbub)
        $rawTitle = $document->jenis_dokumen ?? ($document->cover_data['judul_dokumen'] ?? 'Dokumen Perencanaan');

        // Buang teks dalam tanda kurung, misalnya "(Renja)", "(RENJA)" agar tidak terlalu panjang
        $rawTitle = preg_replace('/\s*\([^)]*\)/u', '', $rawTitle);

        // Nama OPD
        $rawOpd = $document->opd?->nama_opd ?? ($document->cover_data['nama_opd'] ?? '');

        // Tahun Anggaran
        $ta = $document->tahun_anggaran ?? date('Y');

        // Bersihkan karakter terlarang sistem file, spasi → underscore
        $cleanTitle = preg_replace('/[^\w\s\-]/u', '', trim($rawTitle));
        $cleanTitle = preg_replace('/\s+/', '_', $cleanTitle);
        $cleanTitle = preg_replace('/_+/', '_', trim($cleanTitle, '_'));

        $cleanOpd = preg_replace('/[^\w\s\-]/u', '', trim($rawOpd));
        $cleanOpd = preg_replace('/\s+/', '_', $cleanOpd);
        $cleanOpd = preg_replace('/_+/', '_', trim($cleanOpd, '_'));

        if (empty($cleanTitle)) {
            $cleanTitle = 'Dokumen_Perencanaan';
        }

        // Tentukan apakah title adalah jenis dokumen standar pendek
        $titleLower = strtolower(str_replace('_', ' ', $cleanTitle));
        $standardTypes = [
            'renja murni', 
            'renja perubahan', 
            'renja lampiran perbub',
            'rencana kerja murni',
            'rencana kerja perubahan',
            'rencana kerja lampiran perbub',
            'dokumen perencanaan'
        ];
        
        $isStandard = false;
        foreach ($standardTypes as $type) {
            if ($titleLower === $type || $titleLower === str_replace(' ', '', $type)) {
                $isStandard = true;
                break;
            }
        }

        if (!$isStandard) {
            // Jika judul kustom/lengkap, cukup bersihkan dan gunakan langsung
            return "{$cleanTitle}.{$extension}";
        }

        // Gabungkan: RENJA_Murni_Kecamatan_Depok_TA_2027.docx
        $parts = [$cleanTitle];
        if (!empty($cleanOpd)) {
            $parts[] = $cleanOpd;
        }
        $parts[] = 'TA_' . $ta;

        $filename = implode('_', $parts);

        return "{$filename}.{$extension}";
    }
}
