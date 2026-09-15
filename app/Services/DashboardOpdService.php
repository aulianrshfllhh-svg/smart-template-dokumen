<?php

namespace App\Services;

use App\Models\RenjaDocument;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DashboardOpdService
{
    /**
     * Dapatkan statistik ringkasan dokumen khusus Perangkat Daerah (OPD) + WoW comparison
     * yang difilter secara ketat berdasarkan SIKLUS TAHUN AKTIF.
     *
     * @param int|null $opdId
     * @param int|null $activeYear
     * @return array
     */
    public function getOpdStats(?int $opdId, ?int $activeYear = null): array
    {
        $empty = [
            'draft' => 0,
            'menunggu' => 0,
            'revisi' => 0,
            'disetujui' => 0,
            'total' => 0,
            'wow_draft' => 0,
            'wow_menunggu' => 0,
            'wow_revisi' => 0,
            'wow_disetujui' => 0,
        ];

        if (!$opdId) {
            return $empty;
        }

        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));

        $draftKeys = ['draft', 'belum_dikerjakan', 'autofix_completed', 'autofix_confirmed'];
        $menungguKeys = ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang', 'sedang_diperiksa', 'sedang_direview'];
        $revisiKeys = ['perlu_revisi', 'revisi', 'revision'];
        $disetujuiKeys = ['disetujui', 'approved', 'dikunci', 'final'];

        // Current counts (filtered by Active Cycle)
        $query = RenjaDocument::where('opd_id', $opdId);
        RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        $rawCounts = $query->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $stats = $this->aggregateStats($rawCounts, $draftKeys, $menungguKeys, $revisiKeys, $disetujuiKeys);

        // WoW: counts from 7 days ago for the same Active Cycle
        $oneWeekAgo = Carbon::now()->subDays(7);
        $queryLastWeek = RenjaDocument::where('opd_id', $opdId)
            ->where('created_at', '<', $oneWeekAgo);
        RenjaCycleService::applyActiveCycleFilter($queryLastWeek, $activeYear);

        $rawCountsLastWeek = $queryLastWeek->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statsLastWeek = $this->aggregateStats($rawCountsLastWeek, $draftKeys, $menungguKeys, $revisiKeys, $disetujuiKeys);

        return [
            'draft' => $stats['draft'],
            'menunggu' => $stats['menunggu'],
            'revisi' => $stats['revisi'],
            'disetujui' => $stats['disetujui'],
            'total' => $stats['total'],
            'wow_draft' => $stats['draft'] - $statsLastWeek['draft'],
            'wow_menunggu' => $stats['menunggu'] - $statsLastWeek['menunggu'],
            'wow_revisi' => $stats['revisi'] - $statsLastWeek['revisi'],
            'wow_disetujui' => $stats['disetujui'] - $statsLastWeek['disetujui'],
        ];
    }

    /**
     * Aggregate raw status counts into standard buckets.
     */
    private function aggregateStats($rawCounts, array $draftKeys, array $menungguKeys, array $revisiKeys, array $disetujuiKeys): array
    {
        $draft = 0;
        $menunggu = 0;
        $revisi = 0;
        $disetujui = 0;
        $total = 0;

        foreach ($rawCounts as $status => $count) {
            $total += $count;
            if (in_array($status, $draftKeys)) {
                $draft += $count;
            } elseif (in_array($status, $menungguKeys)) {
                $menunggu += $count;
            } elseif (in_array($status, $revisiKeys)) {
                $revisi += $count;
            } elseif (in_array($status, $disetujuiKeys)) {
                $disetujui += $count;
            } else {
                $draft += $count;
            }
        }

        return compact('draft', 'menunggu', 'revisi', 'disetujui', 'total');
    }

    /**
     * Dapatkan daftar dokumen milik OPD dengan pagination, eager loading, dan filter SIKLUS AKTIF.
     */
    public function getOpdDocuments(?int $opdId, int $perPage = 10, ?int $activeYear = null)
    {
        if (!$opdId) {
            return RenjaDocument::whereRaw('1 = 0')->paginate($perPage);
        }

        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));

        $query = RenjaDocument::with(['opd', 'sections'])
            ->withCount(['sections', 'sections as completed_sections_count' => fn($q) => $q->where('is_completed', true)])
            ->where('opd_id', $opdId);

        RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        return $query->orderBy('updated_at', 'desc')->paginate($perPage);
    }

    /**
     * Dapatkan progress penyusunan global OPD dalam SIKLUS AKTIF.
     */
    public function getOverallProgress(?int $opdId, ?int $activeYear = null): array
    {
        if (!$opdId) {
            return ['percentage' => 0, 'completed' => 0, 'total' => 0];
        }

        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));

        $query = RenjaDocument::where('opd_id', $opdId)
            ->withCount(['sections', 'sections as completed_sections_count' => fn($q) => $q->where('is_completed', true)]);

        RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        $documents = $query->get();

        $totalDocs = $documents->count();
        if ($totalDocs === 0) {
            return ['percentage' => 0, 'completed' => 0, 'total' => 0];
        }

        $totalSections = $documents->sum('sections_count');
        $completedSections = $documents->sum('completed_sections_count');
        $percentage = $totalSections > 0 ? (int) round(($completedSections / $totalSections) * 100) : 0;

        $finalDocs = $documents->filter(fn($d) => in_array($d->status, ['disetujui', 'approved', 'dikunci', 'final']))->count();

        return [
            'percentage' => $percentage,
            'completed' => $finalDocs,
            'total' => $totalDocs,
            'total_sections' => $totalSections,
            'completed_sections' => $completedSections,
        ];
    }

    /**
     * Dapatkan dokumen yang sedang aktif dikerjakan dalam SIKLUS AKTIF.
     */
    public function getActiveDocuments(?int $opdId, int $limit = 5, ?int $activeYear = null)
    {
        if (!$opdId) {
            return collect();
        }

        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));

        $query = RenjaDocument::with(['opd'])
            ->withCount(['sections', 'sections as completed_sections_count' => fn($q) => $q->where('is_completed', true)])
            ->where('opd_id', $opdId)
            ->whereIn('status', ['draft', 'belum_dikerjakan', 'perlu_revisi', 'revisi', 'revision', 'autofix_completed', 'autofix_confirmed']);

        RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        return $query->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Dapatkan timeline pengajuan dokumen SIKLUS AKTIF (histori status changes).
     */
    public function getSubmissionTimeline(?int $opdId, int $limit = 10, ?int $activeYear = null)
    {
        if (!$opdId) {
            return collect();
        }

        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));

        $query = RenjaDocument::with(['opd'])
            ->where('opd_id', $opdId)
            ->whereNotNull('submitted_at');

        RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        return $query->orderBy('submitted_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($doc) {
                $events = [];

                if ($doc->submitted_at) {
                    $events[] = [
                        'type' => 'submitted',
                        'label' => 'Dikirim ke Bapperida',
                        'icon' => 'fa-paper-plane',
                        'color' => 'blue',
                        'date' => $doc->submitted_at,
                    ];
                }

                if (in_array($doc->status, ['perlu_revisi', 'revisi', 'revision'])) {
                    $events[] = [
                        'type' => 'revision',
                        'label' => 'Perlu Revisi',
                        'icon' => 'fa-rotate-left',
                        'color' => 'amber',
                        'date' => $doc->updated_at,
                    ];
                }

                if (in_array($doc->status, ['disetujui', 'approved', 'dikunci', 'final'])) {
                    $events[] = [
                        'type' => 'approved',
                        'label' => 'Disetujui / Final',
                        'icon' => 'fa-circle-check',
                        'color' => 'emerald',
                        'date' => $doc->updated_at,
                    ];
                }

                if (in_array($doc->status, ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'sedang_diperiksa'])) {
                    $events[] = [
                        'type' => 'review',
                        'label' => 'Sedang Direview',
                        'icon' => 'fa-magnifying-glass',
                        'color' => 'indigo',
                        'date' => $doc->updated_at,
                    ];
                }

                if ($doc->status === 'dikirim_ulang') {
                    $events[] = [
                        'type' => 'resubmitted',
                        'label' => 'Dikirim Ulang',
                        'icon' => 'fa-arrow-rotate-right',
                        'color' => 'sky',
                        'date' => $doc->updated_at,
                    ];
                }

                return [
                    'document' => $doc,
                    'events' => $events,
                ];
            });
    }

    /**
     * Dapatkan catatan terbaru dari Bapperida (revisi notes) SIKLUS AKTIF.
     */
    public function getBapperidaNotes(?int $opdId, int $limit = 5, ?int $activeYear = null)
    {
        if (!$opdId) {
            return collect();
        }

        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));

        $query = RenjaDocument::where('opd_id', $opdId)
            ->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNotNull('catatan_bapperida')
                        ->where('catatan_bapperida', '!=', '');
                })
                ->orWhereNotNull('section_review_status');
            });

        RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        return $query->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($doc) {
                $notes = [];

                if (!empty($doc->catatan_bapperida)) {
                    $notes[] = [
                        'bab' => 'Umum',
                        'catatan' => $doc->catatan_bapperida,
                        'waktu' => $doc->updated_at,
                    ];
                }

                if (!empty($doc->section_review_status) && is_array($doc->section_review_status)) {
                    foreach ($doc->section_review_status as $babCode => $rStatus) {
                        if (($rStatus['status'] ?? '') === 'NEEDS_REVISION' && !empty($rStatus['notes'])) {
                            $notes[] = [
                                'bab' => $babCode,
                                'catatan' => $rStatus['notes'],
                                'waktu' => $doc->updated_at,
                            ];
                        }
                    }
                }

                return [
                    'document' => $doc,
                    'notes' => $notes,
                ];
            })
            ->filter(fn($item) => count($item['notes']) > 0)
            ->values();
    }

    /**
     * Dapatkan aktivitas terbaru operator sendiri SIKLUS AKTIF.
     */
    public function getRecentActivity(?int $opdId, int $limit = 8, ?int $activeYear = null)
    {
        if (!$opdId) {
            return collect();
        }

        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));

        $query = RenjaDocument::with(['opd'])
            ->where('opd_id', $opdId)
            ->whereNotNull('updated_at');

        RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        return $query->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($doc) {
                $action = match (true) {
                    in_array($doc->status, ['draft', 'belum_dikerjakan']) => 'Menyusun dokumen',
                    in_array($doc->status, ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted']) => 'Mengirim dokumen',
                    in_array($doc->status, ['dikirim_ulang']) => 'Mengirim ulang dokumen',
                    in_array($doc->status, ['perlu_revisi', 'revisi', 'revision']) => 'Menerima revisi',
                    in_array($doc->status, ['disetujui', 'approved']) => 'Dokumen disetujui',
                    in_array($doc->status, ['dikunci', 'final']) => 'Dokumen dikunci',
                    default => 'Aktivitas dokumen',
                };

                $iconMap = [
                    'Menyusun dokumen' => ['icon' => 'fa-pen-to-square', 'color' => 'amber'],
                    'Mengirim dokumen' => ['icon' => 'fa-paper-plane', 'color' => 'blue'],
                    'Mengirim ulang dokumen' => ['icon' => 'fa-arrow-rotate-right', 'color' => 'sky'],
                    'Menerima revisi' => ['icon' => 'fa-rotate-left', 'color' => 'amber'],
                    'Dokumen disetujui' => ['icon' => 'fa-circle-check', 'color' => 'emerald'],
                    'Dokumen dikunci' => ['icon' => 'fa-lock', 'color' => 'purple'],
                    'Aktivitas dokumen' => ['icon' => 'fa-file', 'color' => 'slate'],
                ];

                $meta = $iconMap[$action] ?? $iconMap['Aktivitas dokumen'];

                return [
                    'document' => $doc,
                    'action' => $action,
                    'icon' => $meta['icon'],
                    'color' => $meta['color'],
                    'time' => $doc->updated_at,
                ];
            });
    }

    /**
     * Dapatkan deadline dokumen (configurable, fallback to static) SIKLUS AKTIF.
     */
    public function getUpcomingDeadlines(?int $opdId, ?int $activeYear = null): array
    {
        if (!$opdId) {
            return [];
        }

        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));

        $query = RenjaDocument::where('opd_id', $opdId)
            ->whereNotIn('status', ['disetujui', 'approved', 'dikunci', 'final']);

        RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        $activeDocs = $query->orderBy('tahun_anggaran', 'asc')->get();

        return $activeDocs->map(function ($doc) {
            $taYear = $doc->tahun_anggaran;
            $deadlineDate = Carbon::create($taYear - 1, 9, 30);
            $sisaHari = max(0, (int) Carbon::now()->diffInDays($deadlineDate, false));

            return [
                'document' => $doc,
                'nama' => ($doc->jenis_dokumen ?? 'Renja') . ' TA ' . $taYear,
                'tanggal' => $deadlineDate->format('d M Y'),
                'sisa_hari' => $sisaHari,
                'is_overdue' => $sisaHari <= 0,
                'is_urgent' => $sisaHari > 0 && $sisaHari <= 14,
            ];
        })->toArray();
    }
}
