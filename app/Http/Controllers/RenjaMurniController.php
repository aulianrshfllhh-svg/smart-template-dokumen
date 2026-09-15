<?php

namespace App\Http\Controllers;

use App\Models\RenjaDocument;
use App\Models\DocumentTemplate;
use App\Models\MasterOpd;
use App\Services\DocumentTemplateService;
use App\Services\RenjaMurniDocxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RenjaMurniController extends Controller
{
    protected DocumentTemplateService $templateService;
    protected RenjaMurniDocxService $docxService;

    public function __construct(
        DocumentTemplateService $templateService,
        RenjaMurniDocxService $docxService
    ) {
        $this->templateService = $templateService;
        $this->docxService = $docxService;
    }

    /**
     * Tampilkan Halaman Daftar Dokumen RENJA Murni Milik OPD Operator (Role Operator).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);
        $opd = MasterOpd::find($opdId) ?? MasterOpd::first();

        // Pastikan template terdaftar
        $this->templateService->ensureStandardTemplatesSeeded();
        $masterTemplate = DocumentTemplate::where('code', 'RENJA_MURNI')->first() 
            ?? DocumentTemplate::where('code', 'RENJA')->first();

        // Query Dokumen RENJA Murni milik OPD login
        $query = RenjaDocument::with(['opd', 'template', 'sections', 'updatedByUser'])
            ->where(function ($q) {
                $q->where('jenis_dokumen', 'LIKE', '%Murni%')
                  ->orWhere('jenis_dokumen', 'LIKE', '%RENJA%');
            });

        if (!$user->isAdmin() && !$user->isVerifikator() && !$user->isStaff()) {
            $query->where('opd_id', $opdId);
        }

        // Filter Search
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('jenis_dokumen', 'LIKE', "%{$search}%")
                  ->orWhere('tahun_anggaran', 'LIKE', "%{$search}%");
            });
        }

        // Filter Tahun Anggaran (Definisikan activeTa)
        $activeTa = session('active_ta', (int) date('Y'));
        if ($request->filled('tahun_anggaran') && $request->tahun_anggaran !== 'all') {
            $query->where('tahun_anggaran', (int) $request->tahun_anggaran);
        } else {
            // Default filter ke RENJA Murni TA ini (activeTa + 1)
            $query->where('tahun_anggaran', $activeTa + 1);
        }

        // Filter Sumber Dokumen (Template vs Upload Word)
        if ($request->filled('source_type') && $request->source_type !== 'all') {
            $query->where('source_type', $request->source_type);
        }

        // Filter Status
        if ($request->filled('status') && $request->status !== 'all') {
            $status = $request->status;
            if ($status === 'draft') {
                $query->whereIn('status', ['draft', 'belum_dikerjakan', 'autofix_completed', 'autofix_confirmed']);
            } elseif ($status === 'revisi') {
                $query->whereIn('status', ['perlu_revisi', 'revisi', 'revision']);
            } elseif ($status === 'submitted') {
                $query->whereIn('status', ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang']);
            } elseif ($status === 'final') {
                $query->whereIn('status', ['disetujui', 'approved', 'dikunci', 'final']);
            }
        }

        $documents = $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();

        // Hitung KPI khusus RENJA Murni untuk active TA (tahun_anggaran = activeTa + 1)
        $baseKpiQuery = RenjaDocument::where(function ($q) {
                $q->where('jenis_dokumen', 'LIKE', '%Murni%')
                  ->orWhere('jenis_dokumen', 'LIKE', '%RENJA%');
            })->where('tahun_anggaran', $request->filled('tahun_anggaran') && $request->tahun_anggaran !== 'all' ? (int)$request->tahun_anggaran : ($activeTa + 1));

        if (!$user->isAdmin() && !$user->isVerifikator() && !$user->isStaff()) {
            $baseKpiQuery->where('opd_id', $opdId);
        }

        $allDocs = $baseKpiQuery->get();
        $totalCount = $allDocs->count();
        $draftCount = $allDocs->whereIn('status', ['draft', 'belum_dikerjakan', 'autofix_completed', 'autofix_confirmed'])->count();
        $revisiCount = $allDocs->whereIn('status', ['perlu_revisi', 'revisi', 'revision'])->count();
        $submittedCount = $allDocs->whereIn('status', ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang', 'sedang_diperiksa', 'sedang_direview'])->count();
        $approvedCount = $allDocs->whereIn('status', ['disetujui', 'approved', 'dikunci', 'final'])->count();
        $templateSourceCount = $allDocs->where('source_type', 'template')->count();
        $uploadSourceCount = $allDocs->where('source_type', 'upload_word')->count();

        $kpi = [
            'total' => $totalCount,
            'draft' => $draftCount,
            'revisi' => $revisiCount,
            'submitted' => $submittedCount,
            'approved' => $approvedCount,
            'template_source' => $templateSourceCount,
            'upload_source' => $uploadSourceCount,
        ];

        return view('operator.renja_murni.index', compact(
            'documents',
            'kpi',
            'opd',
            'masterTemplate'
        ));
    }

    /**
     * Buat Dokumen RENJA Murni Baru Berdasarkan Master Template Resmi.
     */
    public function storeFromTemplate(Request $request)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);
        $opd = MasterOpd::find($opdId) ?? MasterOpd::first();

        $validated = $request->validate([
            'tahun_anggaran' => ['nullable', 'integer', 'min:2020', 'max:2099'],
            'template_code' => ['nullable', 'string'],
        ]);

        $ta = (int) ($validated['tahun_anggaran'] ?? date('Y'));
        $templateCode = $request->input('template_code', 'RENJA_MURNI');

        $this->templateService->ensureStandardTemplatesSeeded();
        
        if ($templateCode === 'RENJA_LAMPIRAN_MURNI' || $templateCode === 'RENJA_LAMPIRAN_PERUBAHAN') {
            $template = DocumentTemplate::where('code', $templateCode)->first();
            $currentYear = (int) date('Y');

            if ($templateCode === 'RENJA_LAMPIRAN_MURNI') {
                $ta = $currentYear + 1;
                $jenis = 'RENJA Lampiran Murni';
            } else {
                $ta = $currentYear;
                $jenis = 'RENJA Lampiran Perubahan';
            }

            $coverData = [
                'judul_dokumen' => strtoupper($jenis) . ' TAHUN ANGGARAN ' . $ta,
                'tahun_anggaran' => $ta,
                'nama_pemda' => 'PEMERINTAH KABUPATEN CIREBON',
                'nama_opd' => $opd?->nama_opd ?? 'PERANGKAT DAERAH',
                'lokasi' => 'SUMBER',
                'tahun_terbit' => $currentYear,
                'nomor_dokumen' => 'PERBUP NO. ' . rand(10, 99) . ' TAHUN ' . $currentYear,
            ];

            $document = RenjaDocument::create([
                'opd_id' => $opdId,
                'template_id' => $template?->id,
                'tahun_anggaran' => $ta,
                'year' => $ta,
                'jenis_dokumen' => $jenis,
                'status' => 'draft',
                'source_type' => 'template',
                'cover_data' => $coverData,
                'metadata' => [
                    'created_from' => 'master_template_' . strtolower($templateCode),
                    'created_at_iso' => now()->toIso8601String(),
                    'audit_trail' => [
                        [
                            'action' => 'CREATED_FROM_TEMPLATE',
                            'notes' => 'Dokumen ' . $jenis . ' dibuat dari Master Template resmi.',
                            'timestamp' => now()->toIso8601String(),
                        ]
                    ]
                ]
            ]);

            if ($template) {
                $this->templateService->provisionDocumentSections($document, $template->code);
            }

            return redirect()->route('renja.editor', $document->id)
                ->with('success', "Dokumen " . $jenis . " TA " . $ta . " berhasil dibuat dari Template Resmi.");
        }

        $template = DocumentTemplate::where('code', 'RENJA_MURNI')->first() 
            ?? DocumentTemplate::where('code', 'RENJA')->first();

        $coverData = [
            'judul_dokumen' => 'RENCANA KERJA (RENJA) MURNI',
            'tahun_anggaran' => $ta,
            'nama_pemda' => 'PEMERINTAH KABUPATEN CIREBON',
            'nama_opd' => $opd?->nama_opd ?? 'PERANGKAT DAERAH',
            'lokasi' => 'SUMBER',
            'tahun_terbit' => date('Y'),
            'nomor_dokumen' => 'PERBUP NO. ' . rand(10, 99) . ' TAHUN ' . date('Y'),
        ];

        $document = RenjaDocument::create([
            'opd_id' => $opdId,
            'template_id' => $template?->id,
            'tahun_anggaran' => $ta,
            'year' => $ta,
            'jenis_dokumen' => 'RENJA Murni',
            'status' => 'draft',
            'source_type' => 'template',
            'cover_data' => $coverData,
            'metadata' => [
                'created_from' => 'master_template_renja_murni',
                'created_at_iso' => now()->toIso8601String(),
                'audit_trail' => [
                    [
                        'action' => 'CREATED_FROM_TEMPLATE',
                        'notes' => 'Dokumen RENJA Murni dibuat dari Master Template resmi.',
                        'timestamp' => now()->toIso8601String(),
                    ]
                ]
            ]
        ]);

        // Inisialisasi struktur seksi dari master template
        if ($template) {
            $this->templateService->provisionDocumentSections($document, $template->code);
        }

        return redirect()->route('renja.editor', $document->id)
            ->with('success', "Dokumen RENJA Murni TA {$ta} berhasil dibuat dari Template Resmi.");
    }

    /**
     * Upload Dokumen Word (.docx), Parse, Mapping Struktur & Simpan sebagai RENJA Murni OPD.
     * Setelah upload, redirect ke halaman validasi (alur lama, untuk editor-based workflow).
     */
    public function storeUpload(Request $request)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);

        $request->validate([
            'tahun_anggaran' => ['required', 'integer', 'min:2020', 'max:2099'],
            'document_file' => ['required', 'file', 'max:20480'], // max 20MB
            'jenis_dokumen' => ['nullable', 'string'],
        ], [
            'document_file.required' => 'File dokumen Word (.docx) wajib dipilih.',
            'document_file.max' => 'Ukuran file maksimal 20 MB.',
        ]);

        $file = $request->file('document_file');
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext !== 'docx') {
            return back()->with('error', 'Format file harus berformat Microsoft Word (.docx).');
        }

        $ta = (int) $request->input('tahun_anggaran');
        $jenisDokumen = $request->input('jenis_dokumen', 'RENJA Murni');

        // Cek apakah sudah ada RENJA yang masih aktif (draft/revisi) untuk TA dan jenis ini
        $existingDraft = RenjaDocument::where('opd_id', $opdId)
            ->where('tahun_anggaran', $ta)
            ->where('jenis_dokumen', $jenisDokumen)
            ->whereIn('status', ['draft', 'belum_dikerjakan', 'perlu_revisi', 'revisi', 'revision'])
            ->orderBy('updated_at', 'desc')
            ->first();

        try {
            $document = $this->docxService->importDocx($file, $opdId, $ta, $jenisDokumen, $existingDraft);

            // Jika ada draft lama, hapus
            if ($existingDraft && $existingDraft->id !== $document->id) {
                $existingDraft->sections()->delete();
                $existingDraft->tableEvals()->delete();
                $existingDraft->tableUtamas()->delete();
                $existingDraft->delete();
            }

            $redirectTa = (str_contains(strtolower($jenisDokumen), 'murni') || str_contains(strtolower($jenisDokumen), 'lampiran')) ? $ta - 1 : $ta;

            return redirect()->route('renja.workspace', ['tahun_anggaran' => $redirectTa])
                ->with('success', "File Word berhasil diunggah dan disimpan sebagai draf untuk Dokumen {$jenisDokumen} TA {$ta}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses file Word: ' . $e->getMessage());
        }
    }

    /**
     * Upload Dokumen Word (.docx) dan langsung kirimkan ke Bapperida untuk ditinjau.
     * Ini adalah alur baru yang menggantikan Auto Fix:
     *   - OPD membuat RENJA Murni di Word → Upload → Langsung dikirim ke Bapperida
     *   - Status dokumen langsung menjadi 'menunggu_pemeriksaan'
     *   - Dokumen tidak dapat diedit setelah dikirim (sampai ada permintaan revisi dari Bapperida)
     */
    public function uploadAndSubmit(Request $request)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);

        $request->validate([
            'tahun_anggaran'  => ['required', 'integer', 'min:2020', 'max:2099'],
            'document_file'   => ['required', 'file', 'max:30720'], // max 30MB
            'catatan_pengirim' => ['nullable', 'string', 'max:1000'],
        ], [
            'document_file.required' => 'File dokumen Word (.docx) wajib dipilih.',
            'document_file.max'      => 'Ukuran file maksimal 30 MB.',
        ]);

        $file = $request->file('document_file');
        $ext  = strtolower($file->getClientOriginalExtension());
        if ($ext !== 'docx') {
            return back()->with('error', 'Format file harus berformat Microsoft Word (.docx). Pastikan file Anda disimpan dalam format .docx.');
        }

        $ta = (int) $request->input('tahun_anggaran');
        $catatan = trim($request->input('catatan_pengirim', ''));

        // Cek apakah sudah ada RENJA Murni yang masih aktif (draft/revisi) untuk TA ini
        $existingDraft = RenjaDocument::where('opd_id', $opdId)
            ->where('tahun_anggaran', $ta)
            ->where(function ($q) {
                $q->where('jenis_dokumen', 'LIKE', '%Murni%')
                  ->orWhere('jenis_dokumen', 'LIKE', '%RENJA%')
                  ->orWhere('jenis_dokumen', 'NOT LIKE', '%Perubahan%')
                  ->orWhere('jenis_dokumen', 'NOT LIKE', '%Lampiran%');
            })
            ->whereIn('status', ['draft', 'belum_dikerjakan', 'perlu_revisi', 'revisi', 'revision'])
            ->orderBy('updated_at', 'desc')
            ->first();

        try {
            // Import dan parse file Word menjadi dokumen terstruktur
            $document = $this->docxService->importDocx($file, $opdId, $ta, 'RENJA Murni', $existingDraft);

            // Jika ada draft lama, hapus — dokumen baru dari Word menggantikannya
            if ($existingDraft && $existingDraft->id !== $document->id) {
                $existingDraft->sections()->delete();
                $existingDraft->tableEvals()->delete();
                $existingDraft->tableUtamas()->delete();
                $existingDraft->delete();
            }

            // Ubah status langsung menjadi "menunggu_pemeriksaan" (submitted ke Bapperida)
            $metadata = $document->metadata ?? [];
            $auditTrail = $metadata['audit_trail'] ?? [];
            $auditTrail[] = [
                'action'    => 'UPLOADED_AND_SUBMITTED',
                'notes'     => 'Dokumen Word diunggah dan langsung dikirimkan ke Bapperida untuk ditinjau.'
                               . (!empty($catatan) ? ' Catatan pengirim: ' . $catatan : ''),
                'timestamp' => now()->toIso8601String(),
                'user_id'   => $user?->id,
                'user_name' => $user?->name,
            ];

            $document->update([
                'status'           => \App\Enums\DocumentStatus::SUBMITTED->value,
                'submitted_at'     => now(),
                'catatan_bapperida' => !empty($catatan) ? $catatan : $document->catatan_bapperida,
                'metadata'         => array_merge($metadata, ['audit_trail' => $auditTrail]),
            ]);

            return redirect()
                ->route('renja.workspace', ['tahun_anggaran' => $ta])
                ->with('success', "✅ Dokumen RENJA Murni TA {$ta} berhasil diunggah dan dikirimkan ke Bapperida Kabupaten Cirebon untuk ditinjau. Silakan tunggu hasil pemeriksaan.");

        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses file Word: ' . $e->getMessage() . '. Pastikan file berformat .docx yang valid.');
        }
    }

    /**
     * Tampilkan Halaman Validasi Dokumen RENJA Murni Terhadap Master Template Resmi.
     */
    public function showValidation($id)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);

        $document = RenjaDocument::with(['opd', 'template', 'sections'])->findOrFail($id);

        if (!$user->isAdmin() && !$user->isVerifikator() && !$user->isStaff() && $document->opd_id !== $opdId) {
            abort(403, 'Akses Ditolak: Dokumen milik Perangkat Daerah lain.');
        }

        $validationResult = $this->docxService->validateDocument($document);

        return view('operator.renja_murni.validation', compact(
            'document',
            'validationResult'
        ));
    }

    /**
     * Jalankan Auto Fix cerdas pada dokumen RENJA Murni (Sanitasi Bold & Section-level Landscape).
     */
    public function applyAutoFix($id)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);

        $document = RenjaDocument::with(['opd', 'sections'])->findOrFail($id);

        if (!$user->isAdmin() && !$user->isVerifikator() && !$user->isStaff() && $document->opd_id !== $opdId) {
            abort(403, 'Akses Ditolak: Dokumen milik Perangkat Daerah lain.');
        }

        // AutoFix hanya diperbolehkan untuk Lampiran Perbub — BUKAN untuk RENJA Murni/Perubahan
        if ($document->isRenjaMurniOrPerubahan()) {
            abort(403, 'AutoFix tidak tersedia untuk RENJA Murni dan RENJA Perubahan. AutoFix hanya berlaku pada Lampiran Perbub.');
        }

        // Cek apakah ada masalah mapping yang belum dipastikan
        $hasMappingIssues = $document->sections->contains(function ($sec) {
            return ($sec->metadata['mapping_status'] ?? '') === 'mapping_perlu_diperiksa';
        });

        if ($hasMappingIssues) {
            return back()->with('error', 'Dokumen berhasil diunggah, tetapi sistem belum dapat memastikan seluruh struktur dokumen telah terbaca dengan benar. Dokumen tidak diubah. Silakan periksa hasil import terlebih dahulu.');
        }

        try {
            $targetDoc = \DB::transaction(function () use ($document) {
                // Jika ini adalah dokumen orisinal (belum punya original_document_id)
                if (empty($document->original_document_id)) {
                    // 1. Simpan dokumen asli
                    $originalMeta = $document->metadata ?? [];
                    $originalMeta['is_original'] = true;
                    $document->status = 'belum_dikerjakan'; // status raw original
                    $document->metadata = $originalMeta;
                    $document->save();

                    // 2. Klon dokumen untuk perbaikan
                    $fixedDoc = $document->replicate();
                    $fixedDoc->original_document_id = $document->id;
                    $fixedDoc->status = 'autofix_completed';
                    $fixedDoc->save();

                    foreach ($document->sections as $sec) {
                        $newSec = $sec->replicate();
                        $newSec->document_id = $fixedDoc->id;
                        $newSec->save();
                    }

                    $target = $fixedDoc;
                } else {
                    // Jika memang sudah versi klon perbaikan
                    $document->status = 'autofix_completed';
                    $document->save();
                    $target = $document;
                }

                // 3. Jalankan AutoFix Engine
                $this->docxService->autoFixDocument($target);
                return $target;
            });

            $meta = $targetDoc->metadata ?? [];
            $fixesCount = count($meta['autofix_changes'] ?? []);
            $warningsCount = count($meta['autofix_warnings'] ?? []);

            $msg = "AutoFix RENJA Murni selesai dijalankan. {$fixesCount} masalah format berhasil diperbaiki, {$warningsCount} masalah membutuhkan penyesuaian manual.";

            return redirect()->route('operator.renja-murni.validate', $targetDoc->id)
                ->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses AutoFix: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan Halaman Template Saya → Template RENJA Murni (Read-Only Preview untuk Operator).
     */
    public function templatePreview(Request $request)
    {
        $this->templateService->ensureStandardTemplatesSeeded();
        $code = $request->input('code', 'RENJA_LAMPIRAN_MURNI');
        if (!in_array($code, ['RENJA_LAMPIRAN_MURNI', 'RENJA_LAMPIRAN_PERUBAHAN'])) {
            $code = 'RENJA_LAMPIRAN_MURNI';
        }
        $template = DocumentTemplate::with('sections')->where('code', $code)->first();

        $sections = $template ? $template->sections : collect();
        $frontSections = $sections->whereIn('section_type', ['cover', 'preface', 'table_of_contents', 'list_of_tables', 'list_of_figures', 'list_of_charts', 'list_of_appendices']);
        $mainSections = $sections->whereIn('section_type', ['chapter', 'subchapter']);
        $appendixSections = $sections->where('section_type', 'appendix');

        return view('operator.renja_murni.template_preview', compact(
            'template',
            'sections',
            'frontSections',
            'mainSections',
            'appendixSections'
        ));
    }

    /**
     * Helper to get effective OPD ID for current user.
     */
    protected function getEffectiveOpdIdForUser($user)
    {
        return $user->opd_id ?? MasterOpd::first()->id;
    }
}
