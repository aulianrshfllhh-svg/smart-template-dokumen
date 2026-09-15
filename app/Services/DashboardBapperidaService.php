<?php

namespace App\Services;

use App\Models\RenjaDocument;
use App\Models\MasterOpd;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DashboardBapperidaService
{
    /**
     * Dapatkan statistik ringkasan status dokumen dalam SIKLUS AKTIF.
     *
     * @param int|null $activeYear
     * @return array
     */
    public function getDashboardStats(?int $activeYear = null): array
    {
        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));
        $query = RenjaDocument::query();
        \App\Services\RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        $rawCounts = $query->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $menungguKeys = ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang', 'sedang_diperiksa'];
        $disetujuiKeys = ['disetujui', 'approved', 'dikunci', 'final'];
        $revisiKeys = ['perlu_revisi', 'revisi', 'revision'];

        $menunggu = 0;
        $disetujui = 0;
        $revisi = 0;
        $draft = 0;
        $total = 0;

        foreach ($rawCounts as $status => $count) {
            $total += $count;
            if (in_array($status, $menungguKeys)) {
                $menunggu += $count;
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
            'disetujui' => $disetujui,
            'revisi' => $revisi,
            'draft' => $draft,
            'total' => $total,
        ];
    }

    /**
     * Dapatkan statistik partisipasi 71 Perangkat Daerah (OPD) dalam SIKLUS AKTIF.
     *
     * @param int|null $activeYear
     * @return array
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
        ];
    }

    /**
     * Dapatkan antrean dokumen terfilter untuk dashboard Bapperida dengan pagination & active cycle.
     *
     * @param string|null $search
     * @param string|null $statusFilter
     * @param string|null $jenisFilter
     * @param int $perPage
     * @param int|null $activeYear
     * @return LengthAwarePaginator
     */
    public function getFilteredDocuments(?string $search, ?string $statusFilter, ?string $jenisFilter, int $perPage = 15, ?int $activeYear = null): LengthAwarePaginator
    {
        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));
        $query = RenjaDocument::with(['opd', 'sections']);
        \App\Services\RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        // Filter Search (Nama Dokumen / Nama OPD / Kode OPD)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('jenis_dokumen', 'LIKE', "%{$search}%")
                  ->orWhere('tahun_anggaran', 'LIKE', "%{$search}%")
                  ->orWhereHas('opd', function ($opdQuery) use ($search) {
                      $opdQuery->where('nama_opd', 'LIKE', "%{$search}%")
                               ->orWhere('kode_opd', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter Status sesuai Antrean Verifikasi Tree (6 Kategori)
        if ($statusFilter && $statusFilter !== 'all') {
            if (in_array($statusFilter, ['menunggu_verifikasi', 'menunggu_pemeriksaan', 'submitted', 'dikirim_ulang'])) {
                $query->whereIn('status', ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang']);
            } elseif (in_array($statusFilter, ['sedang_diperiksa', 'sedang_direview', 'under_review'])) {
                $query->whereIn('status', ['sedang_diperiksa', 'under_review', 'review']);
            } elseif (in_array($statusFilter, ['disetujui', 'approved', 'dikunci', 'final'])) {
                $query->whereIn('status', ['disetujui', 'approved', 'dikunci', 'final']);
            } elseif (in_array($statusFilter, ['perlu_revisi', 'revisi', 'revision'])) {
                $query->whereIn('status', ['perlu_revisi', 'revisi', 'revision']);
            } elseif ($statusFilter === 'riwayat') {
                $query->whereIn('status', ['disetujui', 'approved', 'dikunci', 'final', 'perlu_revisi', 'revisi']);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        // Filter Jenis Dokumen
        if ($jenisFilter && $jenisFilter !== 'all') {
            $query->where('jenis_dokumen', 'LIKE', "%{$jenisFilter}%");
        }

        // Priority Order: 'menunggu_pemeriksaan' / 'submitted' / 'dikirim_ulang' first, then revisi, then draft, then final/dikunci
        return $query->orderByRaw("
            CASE 
                WHEN status IN ('menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang', 'sedang_diperiksa') THEN 1 
                WHEN status IN ('perlu_revisi', 'revisi', 'revision') THEN 2 
                WHEN status IN ('draft', 'belum_dikerjakan') THEN 3 
                WHEN status IN ('disetujui', 'approved', 'dikunci', 'final') THEN 4
                ELSE 5 
            END ASC
        ")->orderBy('updated_at', 'desc')
          ->paginate($perPage)
          ->withQueryString();
    }

    /**
     * Dapatkan aktivitas pengajuan/perubahan dokumen terbaru (FR-011) dalam SIKLUS AKTIF.
     *
     * @param int $limit
     * @param int|null $activeYear
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentActivities(int $limit = 5, ?int $activeYear = null)
    {
        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));
        $query = RenjaDocument::with('opd')->whereNotNull('updated_at');
        \App\Services\RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        return $query->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Dapatkan Progres Penyusunan Dokumen per OPD (Top 5) dalam SIKLUS AKTIF.
     *
     * @param int $limit
     * @param int|null $activeYear
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopOpdProgress(int $limit = 5, ?int $activeYear = null)
    {
        $activeYear = $activeYear ?? session('active_ta', (int) date('Y'));
        $query = RenjaDocument::with(['opd', 'sections']);
        \App\Services\RenjaCycleService::applyActiveCycleFilter($query, $activeYear);

        return $query->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Dapatkan pengumuman terbaru untuk dashboard.
     *
     * @return array
     */
    public function getAnnouncements(): array
    {
        return [
            [
                'icon' => 'fa-bullhorn text-blue-600 bg-blue-50 border-blue-200',
                'title' => 'Batas akhir pengajuan Renja OPD 2027',
                'date' => '30 Mei 2026',
                'time_ago' => '2 hari yang lalu',
                'link' => '#',
            ],
            [
                'icon' => 'fa-file-lines text-emerald-600 bg-emerald-50 border-emerald-200',
                'title' => 'Template Renja 2027 telah diperbarui',
                'date' => '25 Mei 2026',
                'time_ago' => '5 hari yang lalu',
                'link' => '#',
            ],
            [
                'icon' => 'fa-book text-purple-600 bg-purple-50 border-purple-200',
                'title' => 'Pedoman Penyusunan RKPD 2027',
                'date' => '18 Mei 2026',
                'time_ago' => '1 minggu yang lalu',
                'link' => '#',
            ],
        ];
    }
}
