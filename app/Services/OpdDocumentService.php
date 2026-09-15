<?php

namespace App\Services;

use App\Models\RenjaDocument;
use App\Models\MasterOpd;
use App\Enums\DocumentStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OpdDocumentService
{
    protected DocumentTemplateService $templateService;

    public function __construct(DocumentTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Dapatkan data lengkap untuk Home Workspace Operator OPD (Modul Dokumen Saya).
     */
    public function getOpdWorkspaceData(int $opdId, array $filters = []): array
    {
        $user = Auth::user();
        $isAdminBapperida = $user && ($user->isAdmin() || $user->isVerifikator() || $user->isStaff());

        $query = RenjaDocument::with(['opd', 'template', 'updatedByUser', 'sections']);

        if (!$isAdminBapperida) {
            $query->where('opd_id', $opdId);
        }

        // 1. Filter Search (Nama Dokumen / Jenis Dokumen)
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('jenis_dokumen', 'LIKE', "%{$search}%")
                    ->orWhere('tahun_anggaran', 'LIKE', "%{$search}%");
            });
        }

        // 2. Filter Tahun Anggaran / Active Cycle
        if (!empty($filters['tahun_anggaran']) && $filters['tahun_anggaran'] !== 'all') {
            $selectedTa = (int) $filters['tahun_anggaran'];
            RenjaCycleService::applyActiveCycleFilter($query, $selectedTa);
        }

        // 3. Filter Jenis Dokumen
        if (!empty($filters['jenis_dokumen']) && $filters['jenis_dokumen'] !== 'all') {
            $query->where('jenis_dokumen', 'LIKE', "%{$filters['jenis_dokumen']}%");
        }

        // 4. Filter Status Dokumen
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $status = strtolower($filters['status']);
            if ($status === 'draft') {
                if ($isAdminBapperida) {
                    $query->whereNotIn('status', ['disetujui', 'approved', 'dikunci', 'final']);
                } else {
                    $query->whereIn('status', ['draft', 'belum_dikerjakan', 'autofix_completed', 'autofix_confirmed']);
                }
            } elseif (in_array($status, ['revisi', 'perlu_revisi', 'revision_required', 'revision'])) {
                $query->whereIn('status', ['perlu_revisi', 'revisi', 'revision']);
            } elseif (in_array($status, ['submitted', 'under_verification', 'menunggu_verifikasi', 'menunggu_pemeriksaan', 'menunggu'])) {
                $query->whereIn('status', ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang', 'sedang_diperiksa', 'sedang_direview']);
            } elseif (in_array($status, ['under_review', 'sedang_diperiksa'])) {
                $query->whereIn('status', ['sedang_diperiksa', 'sedang_direview', 'under_review']);
            } elseif (in_array($status, ['final', 'approved', 'disetujui', 'dikunci'])) {
                $query->whereIn('status', ['disetujui', 'approved', 'dikunci', 'final']);
            } elseif ($status === 'archived') {
                $query->where('status', 'archived');
            }
        }

        // 5. Sorting & Pagination
        $sort = $filters['sort'] ?? 'updated_desc';
        if ($sort === 'updated_asc') {
            $query->orderBy('updated_at', 'asc');
        } elseif ($sort === 'created_desc') {
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        $documents = $query->paginate(10)->withQueryString();

        // 6. Hitung Executive Top KPI Cards berdasarkan Active Cycle
        $selectedTa = !empty($filters['tahun_anggaran']) && is_numeric($filters['tahun_anggaran'])
            ? (int) $filters['tahun_anggaran']
            : session('active_ta', (int) date('Y'));

        $kpi = $this->calculateOpdKpi($opdId, $selectedTa);

        // 7. Dynamic Single Source of Truth Template Choices
        $availableTemplates = $this->templateService->getAvailableTemplates();

        return [
            'documents' => $documents,
            'kpi' => $kpi,
            'availableTemplates' => $availableTemplates,
            'search' => $filters['search'] ?? '',
            'tahunFilter' => $filters['tahun_anggaran'] ?? 'all',
            'jenisFilter' => $filters['jenis_dokumen'] ?? 'all',
            'statusFilter' => $filters['status'] ?? 'all',
            'sort' => $sort,
        ];
    }

    /**
     * Hitung KPI Cards berdasarkan SIKLUS AKTIF.
     */
    public function calculateOpdKpi(int $opdId, ?int $activeYear = null): array
    {
        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));
        $user = Auth::user();
        $isAdminBapperida = $user && ($user->isAdmin() || $user->isVerifikator() || $user->isStaff());

        $baseQuery = RenjaDocument::query();
        if (!$isAdminBapperida) {
            $baseQuery->where('opd_id', $opdId);
        }

        RenjaCycleService::applyActiveCycleFilter($baseQuery, $activeYear);

        $total = (clone $baseQuery)->count();

        if ($isAdminBapperida) {
            $draftCount = (clone $baseQuery)->whereNotIn('status', ['disetujui', 'approved', 'dikunci', 'final'])->count();
            $revisiCount = 0;
            $submittedCount = 0;
            $finalCount = (clone $baseQuery)->whereIn('status', ['disetujui', 'approved', 'dikunci', 'final'])->count();
        } else {
            $draftCount = (clone $baseQuery)->whereIn('status', ['draft', 'belum_dikerjakan', 'autofix_completed', 'autofix_confirmed'])->count();
            $revisiCount = (clone $baseQuery)->whereIn('status', ['perlu_revisi', 'revisi', 'revision'])->count();
            $submittedCount = (clone $baseQuery)->whereIn('status', ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang', 'sedang_diperiksa', 'sedang_direview'])->count();
            $finalCount = (clone $baseQuery)->whereIn('status', ['disetujui', 'approved', 'dikunci', 'final'])->count();
        }

        // Metrik Minggu Lalu (7 hari yang lalu) untuk siklus aktif yang sama
        $oneWeekAgo = now()->subDays(7);
        $draftLastWeek = (clone $baseQuery)->whereIn('status', ['draft', 'belum_dikerjakan', 'autofix_completed', 'autofix_confirmed'])->where('created_at', '<=', $oneWeekAgo)->count();
        $revisiLastWeek = (clone $baseQuery)->whereIn('status', ['perlu_revisi', 'revisi', 'revision'])->where('updated_at', '<=', $oneWeekAgo)->count();
        $submittedLastWeek = (clone $baseQuery)->whereIn('status', ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang', 'sedang_diperiksa', 'sedang_direview'])->where('updated_at', '<=', $oneWeekAgo)->count();
        $finalLastWeek = (clone $baseQuery)->whereIn('status', ['disetujui', 'approved', 'dikunci', 'final'])->where('updated_at', '<=', $oneWeekAgo)->count();

        return [
            'total' => $total,
            'draft' => [
                'count' => $draftCount,
                'percentage' => $total > 0 ? (int) round(($draftCount / $total) * 100) : 0,
                'diff' => $draftCount - $draftLastWeek,
            ],
            'revisi' => [
                'count' => $revisiCount,
                'percentage' => $total > 0 ? (int) round(($revisiCount / $total) * 100) : 0,
                'diff' => $revisiCount - $revisiLastWeek,
            ],
            'submitted' => [
                'count' => $submittedCount,
                'percentage' => $total > 0 ? (int) round(($submittedCount / $total) * 100) : 0,
                'diff' => $submittedCount - $submittedLastWeek,
            ],
            'final' => [
                'count' => $finalCount,
                'percentage' => $total > 0 ? (int) round(($finalCount / $total) * 100) : 0,
                'diff' => $finalCount - $finalLastWeek,
            ],
        ];
    }

    /**
     * Hapus Dokumen Renja sesuai Business Rule (Hanya DRAFT yang boleh dihapus).
     */
    public function deleteDocument(int $documentId, int $opdId): bool
    {
        $document = RenjaDocument::where('opd_id', $opdId)->findOrFail($documentId);

        // Business Rule Check: HANYA draft yang boleh dihapus
        if (!in_array($document->status, ['draft', 'belum_dikerjakan'])) {
            throw new \Exception("Dokumen dengan status '" . strtoupper($document->status) . "' tidak diperbolehkan untuk dihapus.");
        }

        // Hapus seksi terkait
        $document->sections()->delete();
        return (bool) $document->delete();
    }

    /**
     * Dapatkan data lengkap untuk Workspace RENJA Berbasis Tahun Anggaran (Role Operator OPD).
     * Memfasilitasi 3 Card Dokumen: Renja Murni, Renja Perubahan, Renja Lampiran Perbub (BR-031 s.d. BR-037).
     */
    public function getRenjaTaWorkspaceData(int $opdId, int $tahunAnggaran): array
    {
        $user = Auth::user();
        $isAdminBapperida = $user && ($user->isAdmin() || $user->isVerifikator() || $user->isStaff());

        $query = RenjaDocument::with(['opd', 'template', 'sections', 'updatedByUser']);
        if (!$isAdminBapperida) {
            $query->where('opd_id', $opdId);
        }
        $query->whereIn('tahun_anggaran', [$tahunAnggaran, $tahunAnggaran + 1]);

        $docs = $query->orderBy('updated_at', 'desc')->orderBy('id', 'desc')->get();

        // 1. Identifikasi Renja Murni (Tahun depan: $tahunAnggaran + 1)
        $renjaMurni = $docs->first(function ($d) use ($tahunAnggaran) {
            if ($d->tahun_anggaran != $tahunAnggaran + 1)
                return false;
            $jenis = strtolower($d->jenis_dokumen ?? '');
            return str_contains($jenis, 'murni') ||
                (!str_contains($jenis, 'perubahan') && !str_contains($jenis, 'lampiran') && str_contains($jenis, 'renja'));
        }) ?? $docs->first(function ($d) use ($tahunAnggaran) {
            if ($d->tahun_anggaran != $tahunAnggaran + 1)
                return false;
            $jenis = strtolower($d->jenis_dokumen ?? '');
            return !str_contains($jenis, 'perubahan') && !str_contains($jenis, 'lampiran');
        });

        // 2. Identifikasi Renja Perubahan (Tahun berjalan: $tahunAnggaran)
        $renjaPerubahan = $docs->first(function ($d) use ($tahunAnggaran) {
            if ($d->tahun_anggaran != $tahunAnggaran)
                return false;
            $jenis = strtolower($d->jenis_dokumen ?? '');
            return str_contains($jenis, 'perubahan') && !str_contains($jenis, 'lampiran') && !str_contains($jenis, 'kepbup');
        });

        // 3A. Identifikasi RENJA Lampiran Murni (Tahun depan: $tahunAnggaran + 1)
        $renjaLampiranMurni = $docs->first(function ($d) use ($tahunAnggaran) {
            if ($d->tahun_anggaran != $tahunAnggaran + 1)
                return false;
            $jenis = strtolower($d->jenis_dokumen ?? '');
            return str_contains($jenis, 'lampiran') && (str_contains($jenis, 'murni') || str_contains($jenis, 'perbup'));
        });

        // 3B. Identifikasi RENJA Lampiran Perubahan (Tahun berjalan: $tahunAnggaran)
        $renjaLampiranPerubahan = $docs->first(function ($d) use ($tahunAnggaran) {
            if ($d->tahun_anggaran != $tahunAnggaran)
                return false;
            $jenis = strtolower($d->jenis_dokumen ?? '');
            return str_contains($jenis, 'lampiran') && (str_contains($jenis, 'perubahan') || str_contains($jenis, 'kepbup'));
        });

        // Otomatis tautkan / sediakan Lampiran Perubahan jika RENJA Perubahan tersedia
        if ($renjaPerubahan && !$renjaLampiranPerubahan) {
            try {
                $renjaLampiranPerubahan = $this->generateLampiranPerbub($opdId, $tahunAnggaran, 'perubahan');
            } catch (\Throwable $e) {}
        }

        // Otomatis tautkan / sediakan Lampiran Murni jika RENJA Murni tersedia
        if ($renjaMurni && !$renjaLampiranMurni) {
            try {
                $renjaLampiranMurni = $this->generateLampiranPerbub($opdId, $tahunAnggaran + 1, 'murni');
            } catch (\Throwable $e) {}
        }

        // Business Rules Checks
        $isMurniApproved = $renjaMurni && in_array(strtolower($renjaMurni->status), ['disetujui', 'approved', 'dikunci', 'final']);
        $canCreatePerubahan = $isMurniApproved && !$renjaPerubahan;
        $canCreateLampiranMurni = (bool)$renjaMurni;
        $canCreateLampiranPerubahan = (bool)$renjaPerubahan;

        // Riwayat Aktivitas gabungan untuk TA ini
        $activityHistory = $this->getWorkspaceActivityHistory($docs);

        return [
            'tahunAnggaran' => $tahunAnggaran,
            'opd' => MasterOpd::find($opdId) ?? MasterOpd::first(),
            'renjaMurni' => $renjaMurni,
            'renjaPerubahan' => $renjaPerubahan,
            'renjaLampiranMurni' => $renjaLampiranMurni,
            'renjaLampiranPerubahan' => $renjaLampiranPerubahan,
            'renjaLampiranPerbub' => $renjaLampiranMurni ?? $renjaLampiranPerubahan,
            'isMurniApproved' => $isMurniApproved,
            'canCreatePerubahan' => $canCreatePerubahan,
            'canCreateLampiranMurni' => $canCreateLampiranMurni,
            'canCreateLampiranPerubahan' => $canCreateLampiranPerubahan,
            'canGenerateLampiran' => $canCreateLampiranMurni || $canCreateLampiranPerubahan,
            'activityHistory' => $activityHistory,
        ];
    }

    /**
     * Buat Dokumen Renja Murni baru untuk OPD dan TA tertentu (BR-033, BR-035).
     */
    public function createRenjaMurni(int $opdId, int $tahunAnggaran): RenjaDocument
    {
        // Enforce BR-035: Maksimal 1 Renja Murni per TA
        $existing = RenjaDocument::where('opd_id', $opdId)
            ->where('tahun_anggaran', $tahunAnggaran)
            ->where(function ($q) {
                $q->where('jenis_dokumen', 'Renja Murni')
                    ->orWhere('jenis_dokumen', 'LIKE', '%Murni%')
                    ->orWhere(function ($q2) {
                        $q2->where('jenis_dokumen', 'NOT LIKE', '%Perubahan%')
                            ->where('jenis_dokumen', 'NOT LIKE', '%Lampiran%');
                    });
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        $opdObj = MasterOpd::find($opdId);
        $template = \App\Models\DocumentTemplate::where('code', 'RENJA_MURNI')->first()
            ?? \App\Models\DocumentTemplate::where('code', 'RENJA')->first();

        $document = RenjaDocument::create([
            'opd_id' => $opdId,
            'tahun_anggaran' => $tahunAnggaran,
            'jenis_dokumen' => 'Renja Murni',
            'status' => 'draft',
            'template_id' => $template?->id,
            'cover_data' => [
                'judul_dokumen' => 'RENJA MURNI TAHUN ANGGARAN ' . $tahunAnggaran,
                'tahun_anggaran' => $tahunAnggaran,
                'nama_pemda' => 'PEMERINTAH KABUPATEN CIREBON',
                'nama_opd' => $opdObj?->nama_opd ?? 'BADAN PERENCANAAN PEMBANGUNAN DAERAH',
                'lokasi' => 'SUMBER',
                'tahun_terbit' => date('Y'),
            ],
            'metadata' => [
                'audit_trail' => [
                    [
                        'action' => 'DRAFT_CREATED',
                        'notes' => 'Draft Renja Murni berhasil dibuat oleh Operator OPD.',
                        'timestamp' => now()->toIso8601String(),
                        'user_name' => Auth::user()?->nama_lengkap ?? Auth::user()?->name ?? 'Operator OPD',
                    ]
                ]
            ]
        ]);

        $this->templateService->provisionDocumentSections($document, 'RENJA_MURNI');

        return $document;
    }

    /**
     * Buat Dokumen Renja Perubahan baru untuk OPD dan TA tertentu (BR-032, BR-035).
     */
    public function createRenjaPerubahan(int $opdId, int $tahunAnggaran): RenjaDocument
    {
        // Enforce BR-032: Hanya jika Renja Murni disetujui (Renja Murni is for $tahunAnggaran + 1)
        $murni = RenjaDocument::with('sections')->where('opd_id', $opdId)
            ->where('tahun_anggaran', $tahunAnggaran + 1)
            ->whereIn('status', ['disetujui', 'approved', 'dikunci', 'final'])
            ->first();

        if (!$murni) {
            throw new \Exception("BR-032: Renja Perubahan hanya dapat dibuat apabila Renja Murni Tahun Anggaran " . ($tahunAnggaran + 1) . " telah berstatus Disetujui.");
        }

        // Enforce BR-035: Maksimal 1 Renja Perubahan per TA
        $existing = RenjaDocument::where('opd_id', $opdId)
            ->where('tahun_anggaran', $tahunAnggaran)
            ->where('jenis_dokumen', 'LIKE', '%Perubahan%')
            ->first();

        if ($existing) {
            return $existing;
        }

        $opdObj = MasterOpd::find($opdId);
        $template = \App\Models\DocumentTemplate::where('code', 'RENJA_PERUBAHAN')->first()
            ?? \App\Models\DocumentTemplate::where('code', 'RENJA_MURNI')->first()
            ?? \App\Models\DocumentTemplate::where('code', 'RENJA')->first();

        $document = RenjaDocument::create([
            'opd_id' => $opdId,
            'tahun_anggaran' => $tahunAnggaran,
            'year' => $tahunAnggaran,
            'jenis_dokumen' => 'RENJA Perubahan',
            'status' => 'draft',
            'source_type' => 'template',
            'template_id' => $template?->id,
            'cover_data' => [
                'judul_dokumen' => 'RENCANA KERJA (RENJA) PERUBAHAN TAHUN ANGGARAN ' . $tahunAnggaran,
                'tahun_anggaran' => $tahunAnggaran,
                'nama_pemda' => 'PEMERINTAH KABUPATEN CIREBON',
                'nama_opd' => $opdObj?->nama_opd ?? 'BADAN PERENCANAAN PEMBANGUNAN DAERAH',
                'lokasi' => 'SUMBER',
                'tahun_terbit' => date('Y'),
            ],
            'metadata' => [
                'source_murni_id' => $murni->id,
                'audit_trail' => [
                    [
                        'action' => 'DRAFT_CREATED',
                        'notes' => 'Draft Renja Perubahan berhasil dibuat dari Renja Murni oleh Operator OPD.',
                        'timestamp' => now()->toIso8601String(),
                        'user_name' => Auth::user()?->nama_lengkap ?? Auth::user()?->name ?? 'Operator OPD',
                    ]
                ]
            ]
        ]);

        // Provision structural sections directly from master template RENJA_PERUBAHAN
        $this->templateService->provisionDocumentSections($document, 'RENJA_PERUBAHAN');

        // Pre-fill section contents from source Renja Murni
        if ($murni->sections()->count() > 0) {
            $murniSections = $murni->sections->keyBy(function ($sec) {
                return ($sec->section_type ?? '') . '_' . ($sec->sub_bab_code ?? $sec->bab_code ?? '');
            });

            // Reload document sections created by provisionDocumentSections
            $document->load('sections');
            foreach ($document->sections as $section) {
                $key = ($section->section_type ?? '') . '_' . ($section->sub_bab_code ?? $section->bab_code ?? '');
                if (isset($murniSections[$key])) {
                    $srcSec = $murniSections[$key];
                    $section->update([
                        'content' => $srcSec->content,
                        'is_completed' => $srcSec->is_completed,
                    ]);
                }
            }
        }

        return $document;
    }

    /**
     * Generate Renja Lampiran Perbub/Kepbup dari data Renja Murni atau RENJA Perubahan.
     */
    public function generateLampiranPerbub(int $opdId, int $tahunAnggaran, string $sourceType = 'murni'): RenjaDocument
    {
        $parent = null;
        if (strtolower($sourceType) === 'perubahan') {
            $parent = RenjaDocument::with('sections')->where('opd_id', $opdId)
                ->where('tahun_anggaran', $tahunAnggaran)
                ->where('jenis_dokumen', 'LIKE', '%Perubahan%')
                ->where('jenis_dokumen', 'NOT LIKE', '%Lampiran%')
                ->where('jenis_dokumen', 'NOT LIKE', '%Kepbup%')
                ->latest()
                ->first();

            if (!$parent) {
                throw new \Exception("Lampiran Kepbup Perubahan Renja hanya dapat dibuat apabila RENJA Perubahan Tahun Anggaran {$tahunAnggaran} telah tersedia.");
            }

            $templateCode = 'RENJA_LAMPIRAN_PERUBAHAN';
            $jenisDokumen = 'RENJA Lampiran Perubahan';
            $jenisOutput = "Lampiran Kepbup Perubahan Renja Tahun {$tahunAnggaran}";
        } else {
            $parent = RenjaDocument::with('sections')->where('opd_id', $opdId)
                ->where('tahun_anggaran', $tahunAnggaran)
                ->where('jenis_dokumen', 'LIKE', '%Murni%')
                ->where('jenis_dokumen', 'NOT LIKE', '%Lampiran%')
                ->where('jenis_dokumen', 'NOT LIKE', '%Perbup%')
                ->latest()
                ->first();

            if (!$parent) {
                throw new \Exception("RENJA Lampiran Murni hanya dapat dibuat apabila RENJA Murni Tahun Anggaran {$tahunAnggaran} telah tersedia.");
            }

            $templateCode = 'RENJA_LAMPIRAN_MURNI';
            $jenisDokumen = 'RENJA Lampiran Murni';
            $jenisOutput = "Lampiran Perbup Renja Tahun {$tahunAnggaran}";
        }

        $opdObj = MasterOpd::find($opdId);
        $romawiHeader = \App\Services\RenjaAutoFixService::getRomanHeaderForOpd($opdObj);

        // Maksimal 1 Lampiran per jenis per TA
        $existing = RenjaDocument::where('opd_id', $opdId)
            ->where('tahun_anggaran', $tahunAnggaran)
            ->where(function ($q) use ($jenisDokumen) {
                $q->where('jenis_dokumen', $jenisDokumen)
                    ->orWhere('jenis_dokumen', 'LIKE', '%' . (str_contains($jenisDokumen, 'Kepbup') ? 'Kepbup' : 'Perbup') . '%');
            })
            ->first();

        if ($existing) {
            $existing->update([
                'status' => $parent->status,
                'metadata' => array_merge($existing->metadata ?? [], [
                    'generated_from_parent_id' => $parent->id,
                    'romawi_header' => $romawiHeader,
                ]),
            ]);
            $existing->sections()->delete();
            return $existing;
        }

        $template = \App\Models\DocumentTemplate::where('code', $templateCode)->first();

        $document = RenjaDocument::create([
            'opd_id' => $opdId,
            'tahun_anggaran' => $tahunAnggaran,
            'year' => $tahunAnggaran,
            'jenis_dokumen' => $jenisDokumen,
            'status' => $parent->status,
            'source_type' => 'auto_lampiran',
            'template_id' => $template?->id ?? $parent->template_id,
            'cover_data' => [
                'judul_dokumen' => strtoupper($jenisDokumen),
                'jenis_output' => $jenisOutput,
                'tahun_anggaran' => $tahunAnggaran,
                'nama_pemda' => 'PEMERINTAH KABUPATEN CIREBON',
                'nama_opd' => $opdObj?->nama_opd ?? 'BADAN PERENCANAAN PEMBANGUNAN DAERAH',
                'lokasi' => 'SUMBER',
                'tahun_terbit' => date('Y'),
                'romawi_header' => $romawiHeader,
                'source_document_name' => $parent->jenis_dokumen,
            ],
            'metadata' => [
                'generated_from_parent_id' => $parent->id,
                'romawi_header' => $romawiHeader,
                'audit_trail' => [
                    [
                        'action' => 'LAMPIRAN_PERBUB_LINKED',
                        'notes' => "RENJA Lampiran ({$templateCode}) secara otomatis terhubung live ke RENJA Induk (ID: {$parent->id}) dengan penomoran {$romawiHeader}.",
                        'timestamp' => now()->toIso8601String(),
                        'user_name' => Auth::user()?->nama_lengkap ?? Auth::user()?->name ?? 'Operator OPD',
                    ]
                ]
            ]
        ]);

        // Bersihkan seksi duplikat jika ada (Single Source of Truth = RENJA Induk)
        $document->sections()->delete();

        return $document;
    }



    /**
     * Dapatkan riwayat aktivitas / audit trail terpadu untuk dokumen dalam suatu Tahun Anggaran.
     */
    public function getWorkspaceActivityHistory($documents): array
    {
        $activities = [];

        foreach ($documents as $doc) {
            $auditTrail = $doc->metadata['audit_trail'] ?? [];
            if (!empty($auditTrail) && is_array($auditTrail)) {
                foreach ($auditTrail as $item) {
                    $activities[] = [
                        'jenis_dokumen' => $doc->jenis_dokumen ?? 'RENJA',
                        'action' => $item['action'] ?? 'ACTIVITY',
                        'notes' => $item['notes'] ?? 'Aktivitas pada dokumen.',
                        'timestamp' => $item['timestamp'] ?? $doc->updated_at->toIso8601String(),
                        'user_name' => $item['user_name'] ?? 'User',
                    ];
                }
            } else {
                // Fallback dari atribut bawaan dokumen
                $activities[] = [
                    'jenis_dokumen' => $doc->jenis_dokumen ?? 'RENJA',
                    'action' => strtoupper($doc->status ?? 'draft'),
                    'notes' => "Dokumen berstatus {$doc->status_label}.",
                    'timestamp' => $doc->updated_at->toIso8601String(),
                    'user_name' => $doc->updatedByUser?->nama_lengkap ?? 'Operator OPD',
                ];
            }
        }

        // Urutkan aktivitas dari yang terbaru
        usort($activities, function ($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        return $activities;
    }
}
