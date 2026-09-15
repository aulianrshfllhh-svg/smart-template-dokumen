<?php

namespace App\Services;

use App\Models\RenjaDocument;
use App\Models\MasterOpd;
use App\Enums\DocumentStatus;
use Illuminate\Pagination\LengthAwarePaginator;

class VerificationWorkspaceService
{
    /**
     * Ambil 4 KPI Summary Metrics Workspace Verifikasi dalam SIKLUS AKTIF.
     *
     * @param int|null $activeYear
     * @return array
     */
    public function getKpiSummary(?int $activeYear = null): array
    {
        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));

        $countByStatus = function (array $statuses) use ($activeYear) {
            $query = RenjaDocument::whereIn('status', $statuses);
            \App\Services\RenjaCycleService::applyActiveCycleFilter($query, $activeYear);
            return $query->count();
        };

        $totalOpd = MasterOpd::count();
        $submittedOpdIds = \App\Services\RenjaCycleService::getParticipatedOpdIds($activeYear);

        return [
            'menunggu' => $countByStatus(['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang']),
            'direview' => $countByStatus(['sedang_diperiksa', 'under_review', 'review']),
            'revisi' => $countByStatus(['perlu_revisi', 'revisi', 'revision']),
            'disetujui' => $countByStatus(['disetujui', 'approved', 'dikunci', 'final']),
            'total_opd' => $totalOpd > 0 ? $totalOpd : 71,
            'sudah_menyusun' => count($submittedOpdIds),
        ];
    }

    /**
     * Ambil Statistik Cepat (Hari ini, Minggu ini, Bulan ini) - Fitur Langkah 3D.
     */
    public function getQuickStats(): array
    {
        return [
            'today' => RenjaDocument::whereDate('updated_at', now()->today())->count(),
            'this_week' => RenjaDocument::whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => RenjaDocument::whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count(),
        ];
    }

    /**
     * Algoritma Skor Prioritas (Priority Scoring Engine).
     * Priority Score = (Hari Menunggu * 15) + (Jumlah Revisi * 10) + (Bobot OPD Strategis) + (Progress Completeness * 0.2)
     */
    public function calculatePriorityScore(RenjaDocument $document): int
    {
        $submittedAt = $document->submitted_at ?? $document->created_at;
        $daysWaiting = max(0, (int) now()->diffInDays($submittedAt));
        $revisionCount = $document->revision_count ?? 0;

        // Bobot OPD Strategis (misal Dinas PUPR, Dinkes, Disdik, Bapperida memuat pagu/dampak besar)
        $opdName = strtolower($document->opd->nama_opd ?? '');
        $opdWeight = 0;
        if (str_contains($opdName, 'pekerjaan umum') || str_contains($opdName, 'kesehatan') || str_contains($opdName, 'pendidikan') || str_contains($opdName, 'sekretariat daerah')) {
            $opdWeight = 25;
        }

        $progressPercentage = $document->progress_percentage;

        $score = ($daysWaiting * 15) + ($revisionCount * 10) + $opdWeight + (int) ($progressPercentage * 0.2);

        return max(10, $score);
    }

    /**
     * Ambil 5 Dokumen Skor Prioritas Tertinggi untuk Priority Verification Panel dalam SIKLUS AKTIF.
     *
     * @param int $limit
     * @param int|null $activeYear
     * @return \Illuminate\Support\Collection
     */
    public function getPriorityDocuments(int $limit = 5, ?int $activeYear = null)
    {
        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));
        $query = RenjaDocument::with(['opd', 'assignedVerificator', 'updatedByUser'])
            ->whereIn('status', ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang', 'sedang_diperiksa']);
        \App\Services\RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        $documents = $query->get();

        // Hitung skor & urutkan dari skor tertinggi
        foreach ($documents as $doc) {
            $computedScore = $this->calculatePriorityScore($doc);
            if ($doc->priority_score !== $computedScore) {
                $doc->priority_score = $computedScore;
                $doc->saveQuietly();
            }
        }

        return $documents->sortByDesc('priority_score')->take($limit);
    }

    /**
     * Query Filtered Workspace Documents dengan Paginasi & Dynamic Sorting dalam SIKLUS AKTIF.
     *
     * @param string|null $search
     * @param string|null $statusFilter
     * @param string|null $jenisFilter
     * @param string|null $tahunFilter
     * @param string|null $sort
     * @param int $perPage
     * @param int|null $activeYear
     * @return LengthAwarePaginator
     */
    public function getFilteredWorkspaceDocuments(string $search = null, string $statusFilter = 'all', string $jenisFilter = 'all', ?string $tahunFilter = null, ?string $sort = 'priority_desc', int $perPage = 10, ?int $activeYear = null): LengthAwarePaginator
    {
        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));
        $query = RenjaDocument::with(['opd', 'assignedVerificator', 'updatedByUser', 'sections']);

        if (empty($tahunFilter) || $tahunFilter === 'all') {
            \App\Services\RenjaCycleService::applyActiveCycleFilter($query, $activeYear);
        }

        // Filter Search (Nama OPD / Kode OPD / Jenis / TA)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('jenis_dokumen', 'LIKE', "%{$search}%")
                  ->orWhere('tahun_anggaran', 'LIKE', "%{$search}%")
                  ->orWhereHas('opd', function ($opdQuery) use ($search) {
                      $opdQuery->where('nama_opd', 'LIKE', "%{$search}%")
                               ->orWhere('kode_opd', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter Status Enum
        if (!empty($statusFilter) && $statusFilter !== 'all') {
            if (in_array($statusFilter, ['menunggu_verifikasi', 'menunggu_pemeriksaan', 'submitted', 'dikirim_ulang'])) {
                $query->whereIn('status', ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang']);
            } elseif (in_array($statusFilter, ['sedang_direview', 'sedang_diperiksa', 'under_review'])) {
                $query->whereIn('status', ['sedang_diperiksa', 'under_review', 'review']);
            } elseif (in_array($statusFilter, ['perlu_revisi', 'revisi', 'revision'])) {
                $query->whereIn('status', ['perlu_revisi', 'revisi', 'revision']);
            } elseif (in_array($statusFilter, ['disetujui', 'approved', 'dikunci', 'final'])) {
                $query->whereIn('status', ['disetujui', 'approved', 'dikunci', 'final']);
            } elseif ($statusFilter === 'riwayat') {
                $query->whereIn('status', ['disetujui', 'approved', 'dikunci', 'final', 'perlu_revisi', 'revisi']);
            } else {
                $query->where('status', $statusFilter);
            }
        } else {
            // Default Queue View (all): Hanya tampilkan dokumen yang membutuhkan tindakan verifikasi Admin
            $query->whereIn('status', [
                'submitted',
                'menunggu_pemeriksaan',
                'menunggu_verifikasi',
                'dikirim_ulang',
                'sedang_diperiksa',
                'sedang_direview',
                'under_review'
            ]);
        }

        // Filter Jenis Dokumen
        if (!empty($jenisFilter) && $jenisFilter !== 'all') {
            $query->where('jenis_dokumen', 'LIKE', "%{$jenisFilter}%");
        }

        // Filter Tahun Anggaran
        if (!empty($tahunFilter) && $tahunFilter !== 'all') {
            $query->where('tahun_anggaran', $tahunFilter);
        }

        // Sorting Dynamic
        switch ($sort) {
            case 'updated_asc':
                $query->orderBy('updated_at', 'asc');
                break;
            case 'updated_desc':
                $query->orderBy('updated_at', 'desc');
                break;
            case 'priority_desc':
            default:
                $query->orderBy('priority_score', 'desc')->orderBy('updated_at', 'desc');
                break;
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Audit Log Feed untuk Activity Stream Workspace.
     */
    public function getRecentVerificationLogs(int $limit = 6): array
    {
        $documents = RenjaDocument::with(['opd', 'updatedByUser', 'assignedVerificator'])
            ->whereNotNull('metadata')
            ->orderBy('updated_at', 'desc')
            ->get();

        $logs = [];
        foreach ($documents as $doc) {
            $auditTrail = $doc->metadata['audit_trail'] ?? [];
            foreach ($auditTrail as $item) {
                $logs[] = [
                    'document_id' => $doc->id,
                    'opd_nama' => $doc->opd->nama_opd ?? 'SKPD',
                    'action' => $item['action'] ?? 'VERIFICATION_UPDATE',
                    'notes' => $item['notes'] ?? '',
                    'user_name' => $item['user_name'] ?? 'Admin Bapperida',
                    'user_nip' => $item['user_nip'] ?? '',
                    'timestamp' => $item['timestamp'] ?? now()->toIso8601String(),
                ];
            }
        }

        usort($logs, fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));

        return array_slice($logs, 0, $limit);
    }
}
