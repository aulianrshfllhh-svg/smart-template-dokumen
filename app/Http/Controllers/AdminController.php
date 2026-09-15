<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RenjaDocument;
use App\Models\MasterOpd;
use App\Services\DashboardBapperidaService;

class AdminController extends Controller
{
    protected DashboardBapperidaService $dashboardService;

    public function __construct(DashboardBapperidaService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Dashboard Monitoring & Verifikasi Dokumen Admin Bapperida.
     */
    public function adminDashboard(Request $request)
    {
        $activeTa = (int) session('active_ta', (int) date('Y'));
        $search = $request->query('search');
        $statusFilter = $request->query('status');
        $jenisFilter = $request->query('jenis_dokumen');

        $stats = $this->dashboardService->getDashboardStats($activeTa);
        $opdStats = $this->dashboardService->getOpdParticipationStats($activeTa);
        $documents = $this->dashboardService->getFilteredDocuments($search, $statusFilter, $jenisFilter, 15, $activeTa);
        $recentActivities = $this->dashboardService->getRecentActivities(5, $activeTa);
        $topOpds = $this->dashboardService->getTopOpdProgress(5, $activeTa);
        $announcements = $this->dashboardService->getAnnouncements();

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

    /**
     * Halaman Review Dokumen F4 Read-Only oleh Bapperida.
     */
    public function reviewDocument($id)
    {
        $document = RenjaDocument::with(['opd', 'sections'])->findOrFail($id);

        $groupedBabs = $document->sections->groupBy('bab_code');

        return view('admin.review', compact('document', 'groupedBabs'));
    }

    /**
     * Proses Keputusan Verifikasi Bapperida (Setujui atau Minta Revisi).
     */
    public function processDecision(Request $request, $id)
    {
        $document = RenjaDocument::findOrFail($id);

        $decision = $request->input('decision'); // 'disetujui' / 'revisi'

        if ($decision === 'disetujui' || $decision === 'approved') {
            $document->status = \App\Enums\DocumentStatus::APPROVED->value;
            $document->catatan_bapperida = null;
            $document->save();

            return redirect()->route('admin.dashboard')
                ->with('success', "Dokumen Renja {$document->opd->nama_opd} telah BERHASIL DISETUJU & DIVERIFIKASI.");
        } 
        
        if ($decision === 'revisi' || $decision === 'revision' || $decision === 'rejected') {
            $request->validate([
                'catatan_bapperida' => 'required|string|min:5',
            ], [
                'catatan_bapperida.required' => 'Catatan revisi wajib diisi agar OPD mengetahui poin perbaikan.',
            ]);

            $document->status = \App\Enums\DocumentStatus::REVISION_NEEDED->value;
            $document->catatan_bapperida = $request->input('catatan_bapperida');
            $document->save();

            return redirect()->route('admin.dashboard')
                ->with('success', "Dokumen Renja {$document->opd->nama_opd} telah DIKEMBALIKAN KE OPD untuk perbaikan.");
        }

        return redirect()->back()->with('error', 'Keputusan verifikasi tidak valid.');
    }
}
