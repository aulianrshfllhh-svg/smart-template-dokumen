<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use Illuminate\Http\Request;

class MasterOpdController extends Controller
{
    /**
     * Tampilkan Daftar Master Perangkat Daerah (OPD / Dinas / Kecamatan).
     */
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $tahunAnggaran = (int) $request->input('tahun_anggaran', session('active_ta', 2027));

        $opds = MasterOpd::with(['users'])
            ->withCount(['renjaDocuments' => function ($q) use ($tahunAnggaran) {
                if ($tahunAnggaran && $tahunAnggaran !== 'all') {
                    $q->where('tahun_anggaran', $tahunAnggaran);
                }
            }])
            ->when(!empty($search), function ($query) use ($search) {
                $query->where('nama_opd', 'LIKE', "%{$search}%")
                      ->orWhere('kode_opd', 'LIKE', "%{$search}%");
            })
            ->orderBy('kode_opd', 'asc')
            ->paginate(15)
            ->withQueryString();

        $totalOpdCount = MasterOpd::count();

        return view('admin.master_opd', compact(
            'opds',
            'search',
            'tahunAnggaran',
            'totalOpdCount'
        ));
    }

    /**
     * Tampilkan Detail Master 1 OPD Spesifik (Identitas, Operator, Dokumen, & Riwayat Verifikasi).
     */
    public function show(Request $request, $id)
    {
        $opd = MasterOpd::with(['users'])->findOrFail($id);
        $tahunAnggaran = $request->input('tahun_anggaran', 'all');

        $documentsQuery = RenjaDocument::with([
            'template',
            'updatedByUser',
            'assignedVerificator',
            'sections'
        ])
        ->where('opd_id', $opd->id);

        if ($tahunAnggaran && $tahunAnggaran !== 'all') {
            $documentsQuery->where('tahun_anggaran', (int) $tahunAnggaran);
        }

        $documents = $documentsQuery->orderBy('tahun_anggaran', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();

        $availableYears = RenjaDocument::where('opd_id', $opd->id)
            ->distinct()
            ->pluck('tahun_anggaran')
            ->sortDesc()
            ->values();

        return view('admin.master_opd_show', compact(
            'opd',
            'documents',
            'tahunAnggaran',
            'availableYears'
        ));
    }
}
