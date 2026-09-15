<?php

namespace App\Services\Dashboard;

use App\Models\RenjaDocument;
use App\Models\MasterOpd;
use App\Enums\DocumentStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DashboardAdminService
{
    /**
     * Dapatkan ringkasan 4 KPI Metrics Admin Bapperida secara efisien via SQL Aggregation dalam SIKLUS AKTIF.
     *
     * @param int|null $activeYear
     * @return array
     */
    public function getKpiSummary(?int $activeYear = null): array
    {
        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));
        $query = RenjaDocument::query();
        \App\Services\RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        $rawCounts = $query->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $menungguKeys = ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang'];
        $direviewKeys = ['sedang_diperiksa', 'under_review', 'review'];
        $disetujuiKeys = ['disetujui', 'approved', 'dikunci', 'final'];
        $revisiKeys = ['perlu_revisi', 'revisi', 'revision'];

        $menunggu = 0;
        $direview = 0;
        $disetujui = 0;
        $revisi = 0;
        $draft = 0;
        $total = 0;

        foreach ($rawCounts as $status => $count) {
            $total += $count;
            if (in_array($status, $menungguKeys)) {
                $menunggu += $count;
            } elseif (in_array($status, $direviewKeys)) {
                $direview += $count;
            } elseif (in_array($status, $disetujuiKeys)) {
                $disetujui += $count;
            } elseif (in_array($status, $revisiKeys)) {
                $revisi += $count;
            } else {
                $draft += $count;
            }
        }

        return [
            'menunggu' => $menunggu,
            'direview' => $direview,
            'disetujui' => $disetujui,
            'revisi' => $revisi,
            'draft' => $draft,
            'total' => $total,
        ];
    }

    /**
     * Dapatkan statistik partisipasi 71 Perangkat Daerah (OPD) dalam SIKLUS AKTIF.
     */
    public function getOpdParticipationStats(?int $activeYear = null): array
    {
        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));
        $totalOpd = MasterOpd::count();
        $submittedOpdIds = \App\Services\RenjaCycleService::getParticipatedOpdIds($activeYear);
        $sudahMenyusun = count($submittedOpdIds);
        $belumMenyusun = max(0, $totalOpd - $sudahMenyusun);

        return [
            'total_opd' => $totalOpd > 0 ? $totalOpd : 71,
            'sudah_menyusun' => $sudahMenyusun,
            'belum_menyusun' => $belumMenyusun,
            'percentage' => round(($sudahMenyusun / max($totalOpd, 1)) * 100),
        ];
    }

    /**
     * Algoritma Skor Prioritas (Priority Scoring Engine).
     */
    public function calculatePriorityScore(RenjaDocument $document): int
    {
        $submittedAt = $document->submitted_at ?? $document->created_at;
        $daysWaiting = max(0, (int) now()->diffInDays($submittedAt));
        $revisionCount = $document->revision_count ?? 0;

        // Bobot OPD Strategis
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
     * Menggunakan Eager Loading untuk mencegah N+1 query problem.
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
     * Query Optimasi Paginasi Dokumen Workspace dengan Multidimensi Filtering & Eager Loading dalam SIKLUS AKTIF.
     *
     * @param string|null $search
     * @param string|null $statusFilter
     * @param string|null $jenisFilter
     * @param int $perPage
     * @param int|null $activeYear
     * @return LengthAwarePaginator
     */
    public function getFilteredWorkspaceDocuments(?string $search, ?string $statusFilter, ?string $jenisFilter, int $perPage = 10, ?int $activeYear = null): LengthAwarePaginator
    {
        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));
        $query = RenjaDocument::with(['opd', 'assignedVerificator', 'updatedByUser', 'sections']);
        \App\Services\RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        // Filter Search (Pencarian Nama OPD, Kode OPD, Jenis Dokumen, TA)
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
        }

        // Filter Jenis Dokumen
        if (!empty($jenisFilter) && $jenisFilter !== 'all') {
            $query->where('jenis_dokumen', 'LIKE', "%{$jenisFilter}%");
        }

        return $query->orderBy('priority_score', 'desc')
                     ->orderBy('updated_at', 'desc')
                     ->paginate($perPage)
                     ->withQueryString();
    }

    /**
     * Dapatkan Log Aktivitas Verifikasi Real-time untuk Audit Feed (FR-011) dalam SIKLUS AKTIF.
     *
     * @param int $limit
     * @param int|null $activeYear
     * @return array
     */
    public function getRecentVerificationLogs(int $limit = 6, ?int $activeYear = null): array
    {
        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));
        $query = RenjaDocument::with(['opd', 'updatedByUser', 'assignedVerificator'])
            ->whereNotNull('metadata');
        \App\Services\RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        $documents = $query->orderBy('updated_at', 'desc')
            ->take(20)
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
