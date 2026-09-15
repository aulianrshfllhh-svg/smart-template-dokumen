<?php

namespace App\Http\Controllers;

use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Models\MasterOpd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RenjaArchiveController extends Controller
{
    /**
     * Tampilkan Halaman Utama Repository File Explorer "Arsip RENJA".
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);
        $isAdminOrVerifikator = $user && ($user->isAdmin() || $user->isVerifikator() || $user->isStaff());
        $activeTa = (int) session('active_ta', 2027);

        // Parameter Filter & Search
        $selectedYear = $request->query('tahun_anggaran', $request->query('year', 'all'));
        $jenisDokumenFilter = $request->query('jenis_dokumen', 'all');
        $statusFilter = $request->query('status', 'all');
        $searchQuery = trim($request->query('search', ''));
        $sortBy = $request->query('sort', 'year_desc');
        $viewMode = $request->query('view', 'list'); // 'list' or 'grid'

        // 1. Query Dasar Dokumen Arsip:
        // Kriteria Arsip: tahun_anggaran < activeTa ATAU is_archived = true
        $query = RenjaDocument::with(['opd', 'template', 'sections', 'updatedByUser', 'archivedByUser'])
            ->archived($activeTa);

        if (!$isAdminOrVerifikator) {
            $query->where('opd_id', $opdId);
        }

        // Filter Tahun Anggaran Spesifik
        if (!empty($selectedYear) && $selectedYear !== 'all') {
            $query->where('tahun_anggaran', (int) $selectedYear);
        }

        // Filter Jenis Dokumen
        if (!empty($jenisDokumenFilter) && $jenisDokumenFilter !== 'all') {
            $query->where('jenis_dokumen', 'LIKE', "%{$jenisDokumenFilter}%");
        }

        // Filter Status
        if (!empty($statusFilter) && $statusFilter !== 'all') {
            if ($statusFilter === 'archived') {
                $query->where('is_archived', true);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        // Search Filter (mencari nama dokumen, tahun, BAB, subbab, isi konten)
        if (!empty($searchQuery)) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('jenis_dokumen', 'LIKE', "%{$searchQuery}%")
                  ->orWhere('tahun_anggaran', 'LIKE', "%{$searchQuery}%")
                  ->orWhereHas('sections', function ($sq) use ($searchQuery) {
                      $sq->where('bab_title', 'LIKE', "%{$searchQuery}%")
                         ->orWhere('sub_bab_title', 'LIKE', "%{$searchQuery}%")
                         ->orWhere('sub_bab_code', 'LIKE', "%{$searchQuery}%")
                         ->orWhere('content', 'LIKE', "%{$searchQuery}%");
                  });
            });
        }

        // Sorting
        match ($sortBy) {
            'year_asc' => $query->orderBy('tahun_anggaran', 'asc')->orderBy('jenis_dokumen', 'asc'),
            'name_asc' => $query->orderBy('jenis_dokumen', 'asc'),
            'name_desc' => $query->orderBy('jenis_dokumen', 'desc'),
            'latest' => $query->orderBy('updated_at', 'desc'),
            'oldest' => $query->orderBy('updated_at', 'asc'),
            default => $query->orderBy('tahun_anggaran', 'desc')->orderBy('jenis_dokumen', 'asc'),
        };

        $archivedDocuments = $query->get();

        // 2. Hirarki Navigasi Aktif
        $selectedDocument = null;
        $activeBab = $request->query('bab', null);
        $activeSectionId = $request->query('section', null);
        $activeSection = null;

        if ($request->filled('document_id')) {
            $selectedDocument = $this->resolveAndAuthorizeArchiveDocument((int) $request->input('document_id'), $activeTa);
        }

        if ($selectedDocument && $activeSectionId) {
            $activeSection = $selectedDocument->sections->firstWhere('id', (int) $activeSectionId);
            if ($activeSection && !$activeBab) {
                $activeBab = $activeSection->bab_code;
            }
        }

        // Kelompokkan arsip berdasarkan Tahun Anggaran untuk tampilan Root Folder TA
        $documentsByYear = $archivedDocuments->groupBy('tahun_anggaran')->sortKeysDesc();

        // Daftar tahun arsip yang tersedia untuk filter dropdown
        $availableArchiveYears = RenjaDocument::archived($activeTa)
            ->when(!$isAdminOrVerifikator, fn($q) => $q->where('opd_id', $opdId))
            ->distinct()
            ->pluck('tahun_anggaran')
            ->sortDesc()
            ->values();

        $opd = MasterOpd::find($opdId) ?? MasterOpd::first();

        return view('renja.archive.index', [
            'archivedDocuments' => $archivedDocuments,
            'documentsByYear' => $documentsByYear,
            'availableArchiveYears' => $availableArchiveYears,
            'selectedDocument' => $selectedDocument,
            'selectedYear' => $selectedYear !== 'all' ? (int) $selectedYear : null,
            'activeBab' => $activeBab,
            'activeSection' => $activeSection,
            'activeTa' => $activeTa,
            'jenisDokumenFilter' => $jenisDokumenFilter,
            'statusFilter' => $statusFilter,
            'searchQuery' => $searchQuery,
            'sortBy' => $sortBy,
            'viewMode' => $viewMode,
            'opd' => $opd,
            'totalArchiveCount' => $archivedDocuments->count(),
            'currentLevel' => $this->determineCurrentLevel($selectedDocument, $selectedYear, $activeBab, $activeSection),
        ]);
    }

    /**
     * Buka Folder Tahun Anggaran tertentu di Arsip.
     */
    public function showYear(Request $request, int $year)
    {
        return $this->index($request->merge(['tahun_anggaran' => $year]));
    }

    /**
     * Buka Folder Dokumen Arsip tertentu.
     */
    public function showDocument(Request $request, int $id)
    {
        $activeTa = (int) session('active_ta', 2027);
        $selectedDocument = $this->resolveAndAuthorizeArchiveDocument($id, $activeTa);
        $activeBab = $request->query('bab', null);
        $activeSectionId = $request->query('section', null);
        $activeSection = null;

        if ($activeSectionId) {
            $activeSection = $selectedDocument->sections->firstWhere('id', (int) $activeSectionId);
            if ($activeSection && !$activeBab) {
                $activeBab = $activeSection->bab_code;
            }
        }

        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);
        $isAdminOrVerifikator = $user && ($user->isAdmin() || $user->isVerifikator() || $user->isStaff());

        $selectedYear = $selectedDocument->tahun_anggaran;
        $searchQuery = trim($request->query('search', ''));
        $sortBy = $request->query('sort', 'name_asc');
        $viewMode = $request->query('view', 'list');

        $archivedDocuments = RenjaDocument::with(['opd', 'template', 'sections', 'archivedByUser'])
            ->archived($activeTa)
            ->when(!$isAdminOrVerifikator, fn($q) => $q->where('opd_id', $opdId))
            ->where('tahun_anggaran', $selectedYear)
            ->get();

        $documentsByYear = $archivedDocuments->groupBy('tahun_anggaran')->sortKeysDesc();
        $availableArchiveYears = RenjaDocument::archived($activeTa)
            ->when(!$isAdminOrVerifikator, fn($q) => $q->where('opd_id', $opdId))
            ->distinct()
            ->pluck('tahun_anggaran')
            ->sortDesc()
            ->values();

        return view('renja.archive.index', [
            'archivedDocuments' => $archivedDocuments,
            'documentsByYear' => $documentsByYear,
            'availableArchiveYears' => $availableArchiveYears,
            'selectedDocument' => $selectedDocument,
            'selectedYear' => $selectedYear,
            'activeBab' => $activeBab,
            'activeSection' => $activeSection,
            'activeTa' => $activeTa,
            'jenisDokumenFilter' => 'all',
            'statusFilter' => 'all',
            'searchQuery' => $searchQuery,
            'sortBy' => $sortBy,
            'viewMode' => $viewMode,
            'opd' => $selectedDocument->opd ?? MasterOpd::find($opdId),
            'totalArchiveCount' => $archivedDocuments->count(),
            'currentLevel' => $this->determineCurrentLevel($selectedDocument, (string) $selectedYear, $activeBab, $activeSection),
        ]);
    }

    /**
     * Buka Folder BAB Arsip tertentu.
     */
    public function showBab(Request $request, int $id, string $babCode)
    {
        $decodedBab = urldecode($babCode);
        return $this->showDocument($request->merge(['bab' => $decodedBab]), $id);
    }

    /**
     * Buka File Sub-bab Arsip tertentu untuk dibaca (Read-Only).
     */
    public function showSection(Request $request, int $id, int $sectionId)
    {
        return $this->showDocument($request->merge(['section' => $sectionId]), $id);
    }

    /**
     * Aksi Mengarsipkan Dokumen (Khusus Admin / Verifikator Bapperida).
     */
    public function archive(Request $request, int $id)
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isVerifikator() && !$user->isStaff())) {
            abort(403, 'Aksi Ditolak: Hanya Admin/Verifikator Bapperida yang memiliki hak mengarsipkan dokumen.');
        }

        $document = RenjaDocument::findOrFail($id);
        $notes = $request->input('notes', 'Dokumen dipindahkan ke Arsip oleh Administrator Bapperida.');

        $metadata = $document->metadata ?? [];
        $auditTrail = $metadata['audit_trail'] ?? [];

        $auditTrail[] = [
            'action' => 'DOCUMENT_ARCHIVED',
            'notes' => $notes,
            'user_id' => $user->id,
            'user_name' => $user->nama_lengkap ?? $user->name ?? 'Admin Bapperida',
            'user_role' => $user->role,
            'timestamp' => now()->toIso8601String(),
        ];

        $metadata['audit_trail'] = $auditTrail;

        $document->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by_user_id' => $user->id,
            'archive_notes' => $notes,
            'metadata' => $metadata,
        ]);

        return back()->with('success', "Dokumen {$document->jenis_dokumen} TA {$document->tahun_anggaran} berhasil dipindahkan ke Arsip.");
    }

    /**
     * Aksi Mengembalikan Dokumen dari Arsip ke Dokumen Aktif (Khusus Admin Bapperida).
     */
    public function restore(Request $request, int $id)
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isVerifikator() && !$user->isStaff())) {
            abort(403, 'Aksi Ditolak: Hanya Admin/Verifikator Bapperida yang memiliki hak mengembalikan dokumen arsip.');
        }

        $document = RenjaDocument::findOrFail($id);

        $metadata = $document->metadata ?? [];
        $auditTrail = $metadata['audit_trail'] ?? [];

        $auditTrail[] = [
            'action' => 'DOCUMENT_RESTORED_FROM_ARCHIVE',
            'notes' => 'Dokumen dikembalikan ke status dokumen aktif oleh Admin Bapperida.',
            'user_id' => $user->id,
            'user_name' => $user->nama_lengkap ?? $user->name ?? 'Admin Bapperida',
            'user_role' => $user->role,
            'timestamp' => now()->toIso8601String(),
        ];

        $metadata['audit_trail'] = $auditTrail;

        $document->update([
            'is_archived' => false,
            'archived_at' => null,
            'archived_by_user_id' => null,
            'archive_notes' => null,
            'metadata' => $metadata,
        ]);

        return back()->with('success', "Dokumen {$document->jenis_dokumen} TA {$document->tahun_anggaran} berhasil dikembalikan ke dokumen aktif.");
    }

    /**
     * Helper Otorisasi Akses Dokumen Arsip.
     */
    private function resolveAndAuthorizeArchiveDocument(int $documentId, int $activeTa): RenjaDocument
    {
        $user = Auth::user();
        $document = RenjaDocument::with(['opd', 'template', 'sections', 'tableEvals', 'tableUtamas', 'archivedByUser', 'assignedVerificator'])
            ->findOrFail($documentId);

        // Security Guard 1: Dokumen harus memenuhi kriteria Arsip (is_archived = true atau tahun_anggaran < activeTa)
        if (!$document->isArchived($activeTa)) {
            abort(403, 'Dokumen ini merupakan dokumen aktif Tahun Anggaran berjalan dan belum masuk dalam arsip.');
        }

        // Security Guard 2: Isolasi OPD jika bukan Admin / Verifikator
        if ($user && $user->isOperator() && $document->opd_id !== $user->opd_id) {
            abort(403, 'Akses Ditolak: Anda hanya berhak melihat dokumen arsip milik OPD sendiri.');
        }

        return $document;
    }

    /**
     * Tentukan level hirarki File Explorer Arsip.
     */
    private function determineCurrentLevel(?RenjaDocument $document, $selectedYear, ?string $babCode, ?RenjaSection $section): string
    {
        if ($section) {
            return 'section_viewer';
        }
        if ($document && $babCode) {
            return 'bab_folder';
        }
        if ($document) {
            return 'document_folder';
        }
        if (!empty($selectedYear) && $selectedYear !== 'all') {
            return 'year_folder';
        }
        return 'root';
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
}
