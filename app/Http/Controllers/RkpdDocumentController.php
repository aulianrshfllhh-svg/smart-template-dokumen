<?php

namespace App\Http\Controllers;

use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Services\DocumentRegistryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RkpdDocumentController extends Controller
{
    protected DocumentRegistryService $documentRegistryService;

    public function __construct(DocumentRegistryService $documentRegistryService)
    {
        $this->documentRegistryService = $documentRegistryService;
    }

    /**
     * Halaman Utama Modul Dokumen RKPD
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $activeTa = session('active_ta', (int) date('Y'));
        $murniTa = $activeTa + 1;
        $perubahanTa = $activeTa;

        $opdId = $user ? ($user->opd_id ?? MasterOpd::first()?->id) : null;
        $userOpd = $opdId ? MasterOpd::find($opdId) : null;

        // Cek dokumen RENJA milik OPD login untuk integrasi RKPD
        $opdRenjaMurni = $opdId ? $this->documentRegistryService->checkExistingDocument($opdId, 'RENJA_MURNI', $activeTa) : null;
        $opdRenjaPerubahan = $opdId ? $this->documentRegistryService->checkExistingDocument($opdId, 'RENJA_PERUBAHAN', $activeTa) : null;

        // Statistik agregasi RKPD seluruh Kabupaten
        $totalOpd = MasterOpd::count();
        $murniSubmittedCount = RenjaDocument::where('jenis_dokumen', 'like', '%Murni%')
            ->where('tahun_anggaran', $murniTa)
            ->whereIn('status', ['submitted', 'verified', 'approved'])
            ->distinct('opd_id')
            ->count('opd_id');

        $murniApprovedCount = RenjaDocument::where('jenis_dokumen', 'like', '%Murni%')
            ->where('tahun_anggaran', $murniTa)
            ->where('status', 'approved')
            ->distinct('opd_id')
            ->count('opd_id');

        $perubahanSubmittedCount = RenjaDocument::where('jenis_dokumen', 'like', '%Perubahan%')
            ->where('tahun_anggaran', $perubahanTa)
            ->whereIn('status', ['submitted', 'verified', 'approved'])
            ->distinct('opd_id')
            ->count('opd_id');

        return view('rkpd.index', compact(
            'user',
            'userOpd',
            'activeTa',
            'murniTa',
            'perubahanTa',
            'opdRenjaMurni',
            'opdRenjaPerubahan',
            'totalOpd',
            'murniSubmittedCount',
            'murniApprovedCount',
            'perubahanSubmittedCount'
        ));
    }

    /**
     * Workspace / Draft & Agregasi RKPD
     */
    public function workspace(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Arsip Dokumen RKPD
     */
    public function archive(Request $request)
    {
        $user = Auth::user();
        $activeTa = session('active_ta', (int) date('Y'));

        return view('rkpd.archive', compact('user', 'activeTa'));
    }
}
