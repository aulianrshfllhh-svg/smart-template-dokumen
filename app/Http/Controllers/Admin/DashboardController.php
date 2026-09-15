<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Dashboard\DashboardAdminService;

class DashboardController extends Controller
{
    protected DashboardAdminService $dashboardService;

    public function __construct(DashboardAdminService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Halaman Utama Dashboard Control Center Admin Bapperida.
     * Menggunakan Service Layer Architecture secara murni tanpa business logic di Controller.
     */
    public function index(Request $request)
    {
        $activeTa = (int) session('active_ta', (int) date('Y'));
        $search = $request->query('search');
        $statusFilter = $request->query('status', 'all');
        $jenisFilter = $request->query('jenis_dokumen', 'all');

        $kpi = $this->dashboardService->getKpiSummary($activeTa);
        $opdStats = $this->dashboardService->getOpdParticipationStats($activeTa);
        $priorityDocs = $this->dashboardService->getPriorityDocuments(5, $activeTa);
        $documents = $this->dashboardService->getFilteredWorkspaceDocuments($search, $statusFilter, $jenisFilter, 10, $activeTa);
        $recentLogs = $this->dashboardService->getRecentVerificationLogs(6, $activeTa);

        return view('admin.dashboard', compact(
            'kpi',
            'opdStats',
            'priorityDocs',
            'documents',
            'recentLogs',
            'search',
            'statusFilter',
            'jenisFilter',
            'activeTa'
        ));
    }
}
