<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardOpdService;
use App\Services\DashboardBapperidaService;
use App\Models\RenjaDocument;

class DashboardController extends Controller
{
    protected DashboardOpdService $dashboardOpdService;

    public function __construct(DashboardOpdService $dashboardOpdService)
    {
        $this->dashboardOpdService = $dashboardOpdService;
    }

    /**
     * Dashboard Utama (Portal Hub).
     * Jika login sebagai Admin / Verifikator / Staff Bapperida:
     * -> Tampilkan langsung Dashboard Admin Bapperida dengan 4 KPI Cards & Fitur Verifikasi Dokumen 71 Perangkat Daerah.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Admin Bapperida & Verifikator -> Dashboard Monitoring & Verifikasi Admin Bapperida
        if ($user->isAdmin() || $user->isVerifikator() || $user->isStaff()) {
            $activeTa = (int) session('active_ta', (int) date('Y'));
            $search = $request->query('search');
            $statusFilter = $request->query('status');
            $jenisFilter = $request->query('jenis_dokumen');

            $bapperidaService = app(DashboardBapperidaService::class);
            $stats = $bapperidaService->getDashboardStats($activeTa);
            $opdStats = $bapperidaService->getOpdParticipationStats($activeTa);
            $documents = $bapperidaService->getFilteredDocuments($search, $statusFilter, $jenisFilter, 15, $activeTa);
            $recentActivities = $bapperidaService->getRecentActivities(5, $activeTa);
            $topOpds = $bapperidaService->getTopOpdProgress(5, $activeTa);
            $announcements = $bapperidaService->getAnnouncements();
            
            $pendingQuery = RenjaDocument::with('opd')->whereIn('status', ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang']);
            \App\Services\RenjaCycleService::applyActiveCycleFilter($pendingQuery, $activeTa);
            $pendingDoc = $pendingQuery->orderBy('updated_at', 'desc')->first();

            return view('admin.dashboard', compact(
                'documents',
                'stats',
                'opdStats',
                'recentActivities',
                'topOpds',
                'announcements',
                'pendingDoc',
                'search',
                'statusFilter',
                'jenisFilter',
                'activeTa'
            ));
        }

        if ($user->isPimpinan()) {
            return redirect()->route('pimpinan.dashboard');
        }

        // Default: OPD / Operator User
        $opdId = $user->opd_id;
        $activeTa = (int) session('active_ta', (int) date('Y'));
        $stats = $this->dashboardOpdService->getOpdStats($opdId, $activeTa);
        $overallProgress = $this->dashboardOpdService->getOverallProgress($opdId, $activeTa);
        $activeDocuments = $this->dashboardOpdService->getActiveDocuments($opdId, 5, $activeTa);
        $timeline = $this->dashboardOpdService->getSubmissionTimeline($opdId, 10, $activeTa);
        $bapperidaNotes = $this->dashboardOpdService->getBapperidaNotes($opdId, 5, $activeTa);
        $recentActivity = $this->dashboardOpdService->getRecentActivity($opdId, 8, $activeTa);
        $deadlines = $this->dashboardOpdService->getUpcomingDeadlines($opdId, $activeTa);
        $availableTemplates = app(\App\Services\DocumentTemplateService::class)->getAvailableTemplates();

        return view('dashboard', compact(
            'stats',
            'overallProgress',
            'activeDocuments',
            'timeline',
            'bapperidaNotes',
            'recentActivity',
            'deadlines',
            'availableTemplates'
        ));
    }

    /**
     * Dashboard Dedicated Role Verifikator (Terintegrasi ke Dashboard Monitoring Verifikasi Admin).
     */
    public function verifikatorDashboard(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Dashboard Dedicated Role Staff Bapperida (Terintegrasi ke Dashboard Monitoring Admin).
     */
    public function staffDashboard(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Dashboard Dedicated Role Executive Pimpinan.
     */
    public function pimpinanDashboard()
    {
        $bapperidaService = app(DashboardBapperidaService::class);
        $stats = $bapperidaService->getDashboardStats();
        $opdStats = $bapperidaService->getOpdParticipationStats();
        $topOpds = $bapperidaService->getTopOpdProgress(10);

        return view('pimpinan.dashboard', compact('stats', 'opdStats', 'topOpds'));
    }
}
