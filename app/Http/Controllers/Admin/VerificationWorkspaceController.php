<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VerificationWorkspaceService;
use App\Services\VerificationReviewService;

class VerificationWorkspaceController extends Controller
{
    protected VerificationWorkspaceService $workspaceService;
    protected VerificationReviewService $reviewService;

    public function __construct(
        VerificationWorkspaceService $workspaceService,
        VerificationReviewService $reviewService
    ) {
        $this->workspaceService = $workspaceService;
        $this->reviewService = $reviewService;
    }

    /**
     * Workspace Antrean Verifikasi Admin Bapperida.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $statusFilter = $request->query('status', 'all');
        $jenisFilter = $request->query('jenis_dokumen', 'all');
        $tahunFilter = $request->query('tahun_anggaran', 'all');
        $sort = $request->query('sort', 'priority_desc');

        $kpi = $this->workspaceService->getKpiSummary();
        $quickStats = $this->workspaceService->getQuickStats();
        $priorityDocs = $this->workspaceService->getPriorityDocuments(5);
        $documents = $this->workspaceService->getFilteredWorkspaceDocuments($search, $statusFilter, $jenisFilter, $tahunFilter, $sort, 10);
        $recentLogs = $this->workspaceService->getRecentVerificationLogs(6);

        return view('admin.verifikasi.index', compact(
            'kpi',
            'quickStats',
            'priorityDocs',
            'documents',
            'recentLogs',
            'search',
            'statusFilter',
            'jenisFilter',
            'tahunFilter',
            'sort'
        ));
    }

    /**
     * Halaman PR-Style Detail Review Dokumen per Bab/Seksi.
     */
    public function review($id)
    {
        $data = $this->reviewService->getReviewDetailData($id);

        return view('admin.verifikasi.review', $data);
    }

    /**
     * Memproses Keputusan PR-Style Review (Draft, Revisi, Setujui).
     */
    public function processDecision(Request $request, $id)
    {
        $request->validate([
            'decision_type' => 'required|in:simpan_draft,minta_revisi,setujui_dokumen',
            'sections' => 'nullable|array',
            'assigned_verificator_id' => 'nullable|exists:users,id',
            'catatan_bapperida' => 'nullable|string',
        ]);

        $sectionsData = $request->input('sections', []);
        $assignedVerificatorId = $request->input('assigned_verificator_id');
        $decisionType = $request->input('decision_type');
        $catatanBapperida = $request->input('catatan_bapperida');

        try {
            $document = $this->reviewService->processReviewDecision(
                $id,
                $sectionsData,
                $assignedVerificatorId,
                $decisionType,
                $catatanBapperida
            );

            $message = match ($decisionType) {
                'setujui_dokumen' => 'Dokumen Rencana Kerja (Renja) berhasil DISETUJUI (SAH) & dikunci.',
                'minta_revisi' => 'Dokumen berhasil dikembalikan ke OPD dengan status PERLU REVISI.',
                default => 'Draft hasil review per bab berhasil disimpan.',
            };

            return redirect()->route('admin.verifikasi.index')->with('success', $message);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delegasi Penugasan Verifikator Staff Bapperida.
     */
    public function assignVerificator(Request $request, $id)
    {
        $request->validate([
            'assigned_verificator_id' => 'required|exists:users,id',
        ]);

        $this->reviewService->assignVerificator($id, $request->input('assigned_verificator_id'));

        return back()->with('success', 'Tugas verifikasi dokumen berhasil didelegasikan.');
    }
}
