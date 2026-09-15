<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Services\DashboardBapperidaService;

class BapperidaMonitoringController extends Controller
{
    protected DashboardBapperidaService $dashboardService;

    public function __construct(DashboardBapperidaService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Panel Monitoring & Verifikasi Pengawasan Status 71 OPD Kabupaten Cirebon.
     */
    public function index(Request $request)
    {
        $tahunAnggaran = (int) $request->input('tahun_anggaran', session('active_ta', 2027));
        $search = $request->input('search');
        $statusFilter = $request->input('status');

        $query = MasterOpd::query();

        if ($search) {
            $query->where('nama_opd', 'like', "%{$search}%")
                  ->orWhere('kode_opd', 'like', "%{$search}%");
        }

        $opds = $query->with(['documents' => function ($q) use ($tahunAnggaran) {
            $q->where('tahun_anggaran', $tahunAnggaran);
        }])->paginate(15)->withQueryString();

        // Calculate KPI summary for the selected year
        $totalOpd = MasterOpd::count();
        $totalDocsYear = RenjaDocument::where('tahun_anggaran', $tahunAnggaran)->count();
        $draftCount = RenjaDocument::where('tahun_anggaran', $tahunAnggaran)->whereIn('status', ['draft', 'belum_dikerjakan'])->count();
        $submittedCount = RenjaDocument::where('tahun_anggaran', $tahunAnggaran)->whereIn('status', ['submitted', 'menunggu_verifikasi'])->count();
        $underReviewCount = RenjaDocument::where('tahun_anggaran', $tahunAnggaran)->whereIn('status', ['sedang_direview', 'sedang_diperiksa'])->count();
        $approvedCount = RenjaDocument::where('tahun_anggaran', $tahunAnggaran)->whereIn('status', ['disetujui', 'approved', 'final'])->count();
        
        $completionRate = $totalOpd > 0 ? round(($approvedCount / $totalOpd) * 100, 1) : 0;

        $stats = [
            'total_opd' => $totalOpd,
            'total_docs' => $totalDocsYear,
            'draft' => $draftCount,
            'submitted' => $submittedCount,
            'under_review' => $underReviewCount,
            'approved' => $approvedCount,
            'completion_rate' => $completionRate,
            'tahun_anggaran' => $tahunAnggaran,
        ];

        return view('bapperida.monitoring', compact('opds', 'stats', 'tahunAnggaran', 'search', 'statusFilter'));
    }
}
