<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class OpdMonitoringController extends Controller
{
    /**
     * Halaman Utama Monitoring OPD (Seluruh OPD & Status Dokumen).
     */
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $tahunAnggaran = (int) $request->input('tahun_anggaran', session('active_ta', (int) date('Y')));
        $statusFilter = strtolower(trim($request->input('status', 'all')));

        // 1. Ambil seluruh data Master OPD beserta relasi dokumen pada SIKLUS AKTIF
        $allOpds = MasterOpd::with(['renjaDocuments' => function ($q) use ($tahunAnggaran) {
            \App\Services\RenjaCycleService::applyActiveCycleFilter($q, $tahunAnggaran);
        }])
        ->when(!empty($search), function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_opd', 'LIKE', "%{$search}%")
                  ->orWhere('kode_opd', 'LIKE', "%{$search}%");
            });
        })
        ->orderBy('kode_opd', 'asc')
        ->orderBy('id', 'asc')
        ->get();

        // 2. Hitung statistik KPI global secara dinamis dari DB
        $totalOpdCount = MasterOpd::count();

        // Hitung OPD yang berpartisipasi (minimal Memiliki 1 dokumen disubmit di SIKLUS AKTIF)
        $opdWithDocsIds = \App\Services\RenjaCycleService::getParticipatedOpdIds($tahunAnggaran);
        $sudahMengirimCount = count($opdWithDocsIds);
        $belumMengirimCount = max(0, $totalOpdCount - $sudahMengirimCount);

        // OPD Sedang Diproses (dokumen dalam antrean verifikasi/pemeriksaan pada SIKLUS AKTIF)
        $sedangDiprosesOpdQuery = RenjaDocument::select('opd_id')
            ->whereIn('status', ['submitted', 'menunggu_pemeriksaan', 'menunggu_verifikasi', 'sedang_diperiksa', 'sedang_direview', 'dikirim_ulang'])
            ->distinct();
        \App\Services\RenjaCycleService::applyActiveCycleFilter($sedangDiprosesOpdQuery, $tahunAnggaran);
        $sedangDiprosesCount = count($sedangDiprosesOpdQuery->pluck('opd_id')->toArray());

        // OPD Sudah Disetujui (dokumen disetujui/final pada SIKLUS AKTIF)
        $disetujuiOpdQuery = RenjaDocument::select('opd_id')
            ->whereIn('status', ['disetujui', 'approved', 'dikunci', 'final'])
            ->distinct();
        \App\Services\RenjaCycleService::applyActiveCycleFilter($disetujuiOpdQuery, $tahunAnggaran);
        $disetujuiCount = count($disetujuiOpdQuery->pluck('opd_id')->toArray());

        // OPD Perlu Revisi (dokumen perlu revisi pada SIKLUS AKTIF)
        $perluRevisiOpdQuery = RenjaDocument::select('opd_id')
            ->whereIn('status', ['perlu_revisi', 'revisi', 'revision'])
            ->distinct();
        \App\Services\RenjaCycleService::applyActiveCycleFilter($perluRevisiOpdQuery, $tahunAnggaran);
        $perluRevisiCount = count($perluRevisiOpdQuery->pluck('opd_id')->toArray());

        // 3. Olah data setiap OPD dengan status progres dinamis
        $processedOpds = $allOpds->map(function ($opd) {
            $docs = $opd->renjaDocuments;
            $docCount = $docs->count();
            $submittedCount = $docs->filter(fn($d) => in_array($d->status, ['submitted', 'menunggu_pemeriksaan', 'menunggu_verifikasi', 'sedang_diperiksa', 'sedang_direview', 'dikirim_ulang', 'perlu_revisi', 'revisi', 'disetujui', 'approved', 'dikunci', 'final']))->count();

            if ($docCount === 0) {
                $opdStatusKey = 'belum_mengirim';
                $opdStatusLabel = 'Belum Mulai';
                $opdBadgeClass = 'bg-slate-100 text-slate-600 border border-slate-300';
                $lastUpdate = null;
            } else {
                $latestDoc = $docs->sortByDesc('updated_at')->first();
                $lastUpdate = $latestDoc?->updated_at;

                $statuses = $docs->pluck('status')->map(fn($s) => strtolower($s))->toArray();

                if (array_intersect($statuses, ['perlu_revisi', 'revisi', 'revision'])) {
                    $opdStatusKey = 'perlu_revisi';
                    $opdStatusLabel = 'Perlu Revisi';
                    $opdBadgeClass = 'bg-rose-100 text-rose-800 border border-rose-300 font-bold';
                } elseif (array_intersect($statuses, ['submitted', 'menunggu_pemeriksaan', 'menunggu_verifikasi', 'sedang_diperiksa', 'sedang_direview', 'dikirim_ulang'])) {
                    $opdStatusKey = 'sedang_diproses';
                    $opdStatusLabel = 'Mengirim / Diproses';
                    $opdBadgeClass = 'bg-blue-100 text-blue-800 border border-blue-300 font-bold';
                } elseif (array_intersect($statuses, ['disetujui', 'approved', 'dikunci', 'final'])) {
                    $opdStatusKey = 'disetujui';
                    $opdStatusLabel = 'Selesai / Approved';
                    $opdBadgeClass = 'bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold';
                } else {
                    $opdStatusKey = 'draft';
                    $opdStatusLabel = 'Draft';
                    $opdBadgeClass = 'bg-amber-100 text-amber-900 border border-amber-300 font-bold';
                }
            }

            $opd->doc_count = $docCount;
            $opd->submitted_count = $submittedCount;
            $opd->opd_status_key = $opdStatusKey;
            $opd->opd_status_label = $opdStatusLabel;
            $opd->opd_badge_class = $opdBadgeClass;
            $opd->last_update = $lastUpdate;
            $opd->latest_document = $docs->sortByDesc('updated_at')->first();

            return $opd;
        });

        // 4. Filter berdasarkan status jika diminta
        if ($statusFilter && $statusFilter !== 'all') {
            $processedOpds = $processedOpds->filter(function ($opd) use ($statusFilter) {
                if ($statusFilter === 'belum_mengirim') {
                    return $opd->doc_count === 0;
                }
                if ($statusFilter === 'sudah_mengirim') {
                    return $opd->doc_count > 0;
                }
                if ($statusFilter === 'sedang_diproses' || $statusFilter === 'sedang_diverifikasi') {
                    return $opd->opd_status_key === 'sedang_diproses';
                }
                if ($statusFilter === 'perlu_revisi') {
                    return $opd->opd_status_key === 'perlu_revisi';
                }
                if ($statusFilter === 'disetujui' || $statusFilter === 'final') {
                    return $opd->opd_status_key === 'disetujui';
                }
                return true;
            });
        }

        // 5. Pagination manual untuk Collection agar tetap mempertahankan urutan & query parameters
        $perPage = 15;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $processedOpds->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $opds = new LengthAwarePaginator(
            $currentItems,
            $processedOpds->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $kpi = [
            'total_opd' => $totalOpdCount,
            'sudah_mengirim' => $sudahMengirimCount,
            'belum_mengirim' => $belumMengirimCount,
            'sedang_diproses' => $sedangDiprosesCount,
            'disetujui' => $disetujuiCount,
            'perlu_revisi' => $perluRevisiCount,
        ];

        return view('admin.monitoring_opd.index', compact(
            'opds',
            'kpi',
            'search',
            'tahunAnggaran',
            'statusFilter'
        ));
    }

    /**
     * Detail Monitoring OPD (Lihat Progres & Seluruh Dokumen Milik OPD Tertentu dalam SIKLUS AKTIF).
     */
    public function show(Request $request, $id)
    {
        $opd = MasterOpd::with(['users'])->findOrFail($id);
        $activeYear = (int) $request->input('tahun_anggaran', session('active_ta', (int) date('Y')));
        $tahunAnggaran = $activeYear;

        // Base Year $activeYear (misal 2026):
        // TA Murni = activeYear + 1 (2027)
        // TA Perubahan = activeYear (2026)
        $taMurni = $activeYear + 1;
        $taPerubahan = $activeYear;

        // Dokumen 1: RENJA Murni ($taMurni)
        $docMurni = RenjaDocument::with(['template', 'sections', 'updatedByUser', 'assignedVerificator'])
            ->where('opd_id', $opd->id)
            ->where('tahun_anggaran', $taMurni)
            ->where('jenis_dokumen', 'LIKE', '%Murni%')
            ->where('jenis_dokumen', 'NOT LIKE', '%Lampiran%')
            ->latest()
            ->first();

        // Dokumen 2: RENJA Perubahan ($taPerubahan)
        $docPerubahan = RenjaDocument::with(['template', 'sections', 'updatedByUser', 'assignedVerificator'])
            ->where('opd_id', $opd->id)
            ->where('tahun_anggaran', $taPerubahan)
            ->where('jenis_dokumen', 'LIKE', '%Perubahan%')
            ->where('jenis_dokumen', 'NOT LIKE', '%Lampiran%')
            ->latest()
            ->first();

        // Dokumen 3: Lampiran RENJA Murni ($taMurni)
        $lampiranMurni = RenjaDocument::with(['template', 'sections', 'updatedByUser', 'assignedVerificator'])
            ->where('opd_id', $opd->id)
            ->where('tahun_anggaran', $taMurni)
            ->where(function ($q) {
                $q->where('jenis_dokumen', 'LIKE', '%Lampiran%')
                  ->orWhere('jenis_dokumen', 'LIKE', '%Perbup%');
            })
            ->latest()
            ->first();

        // Dokumen 4: Lampiran RENJA Perubahan ($taPerubahan)
        $lampiranPerubahan = RenjaDocument::with(['template', 'sections', 'updatedByUser', 'assignedVerificator'])
            ->where('opd_id', $opd->id)
            ->where('tahun_anggaran', $taPerubahan)
            ->where(function ($q) {
                $q->where('jenis_dokumen', 'LIKE', '%Lampiran%')
                  ->orWhere('jenis_dokumen', 'LIKE', '%Kepbup%');
            })
            ->latest()
            ->first();

        $cycleDocuments = [
            [
                'key' => 'murni',
                'title' => 'RENJA Murni',
                'tahun_anggaran' => $taMurni,
                'document' => $docMurni,
            ],
            [
                'key' => 'perubahan',
                'title' => 'RENJA Perubahan',
                'tahun_anggaran' => $taPerubahan,
                'document' => $docPerubahan,
            ],
            [
                'key' => 'lampiran_murni',
                'title' => 'Lampiran RENJA Murni',
                'tahun_anggaran' => $taMurni,
                'document' => $lampiranMurni,
            ],
            [
                'key' => 'lampiran_perubahan',
                'title' => 'Lampiran RENJA Perubahan',
                'tahun_anggaran' => $taPerubahan,
                'document' => $lampiranPerubahan,
            ],
        ];

        // Filter dokumen yang benar-benar ada
        $existingDocs = collect([$docMurni, $docPerubahan, $lampiranMurni, $lampiranPerubahan])->filter();

        $totalDocs = $existingDocs->count();
        $draftCount = $existingDocs->filter(fn($d) => in_array($d->status, ['draft', 'belum_dikerjakan']))->count();
        $diprosesCount = $existingDocs->filter(fn($d) => in_array($d->status, ['submitted', 'menunggu_pemeriksaan', 'menunggu_verifikasi', 'sedang_diperiksa', 'sedang_direview', 'dikirim_ulang']))->count();
        $revisiCount = $existingDocs->filter(fn($d) => in_array($d->status, ['perlu_revisi', 'revisi', 'revision']))->count();
        $disetujuiCount = $existingDocs->filter(fn($d) => in_array($d->status, ['disetujui', 'approved', 'dikunci', 'final']))->count();

        // Hitung OPD Progress Status untuk active cycle
        $statuses = $existingDocs->pluck('status')->map(fn($s) => strtolower($s))->toArray();
        if ($totalDocs === 0) {
            $opdStatusKey = 'belum_mulai';
            $opdStatusLabel = 'Belum Mulai';
            $opdBadgeClass = 'bg-slate-100 text-slate-600 border border-slate-300';
        } elseif (array_intersect($statuses, ['perlu_revisi', 'revisi', 'revision'])) {
            $opdStatusKey = 'perlu_revisi';
            $opdStatusLabel = 'Perlu Revisi';
            $opdBadgeClass = 'bg-rose-100 text-rose-800 border border-rose-300 font-bold';
        } elseif (array_intersect($statuses, ['submitted', 'menunggu_pemeriksaan', 'menunggu_verifikasi', 'sedang_diperiksa', 'sedang_direview', 'dikirim_ulang'])) {
            $opdStatusKey = 'sedang_diproses';
            $opdStatusLabel = 'Mengirim / Diproses';
            $opdBadgeClass = 'bg-blue-100 text-blue-800 border border-blue-300 font-bold';
        } elseif (array_intersect($statuses, ['disetujui', 'approved', 'dikunci', 'final'])) {
            $opdStatusKey = 'disetujui';
            $opdStatusLabel = 'Selesai / Approved';
            $opdBadgeClass = 'bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold';
        } else {
            $opdStatusKey = 'draft';
            $opdStatusLabel = 'Sedang Menyusun (Draft)';
            $opdBadgeClass = 'bg-amber-100 text-amber-900 border border-amber-300 font-bold';
        }

        $opdStats = [
            'total_docs' => $totalDocs,
            'submitted_count' => $existingDocs->filter(fn($d) => !in_array($d->status, ['draft', 'belum_dikerjakan']))->count(),
            'draft' => $draftCount,
            'diproses' => $diprosesCount,
            'revisi' => $revisiCount,
            'disetujui' => $disetujuiCount,
        ];

        return view('admin.monitoring_opd.show', compact(
            'opd',
            'cycleDocuments',
            'activeYear',
            'tahunAnggaran',
            'opdStats',
            'opdStatusLabel',
            'opdBadgeClass'
        ));
    }
}
