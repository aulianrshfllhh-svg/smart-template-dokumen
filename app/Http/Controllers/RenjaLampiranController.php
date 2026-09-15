<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Services\OpdDocumentService;
use App\Services\RenjaAutoFixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RenjaLampiranController extends Controller
{
    protected OpdDocumentService $opdDocumentService;

    public function __construct(OpdDocumentService $opdDocumentService)
    {
        $this->opdDocumentService = $opdDocumentService;
    }

    protected function getEffectiveOpdIdForUser($user): int
    {
        if (($user->isAdmin() || $user->isVerifikator() || $user->isStaff()) && session()->has('effective_opd_id')) {
            return (int) session('effective_opd_id');
        }
        return (int) ($user->opd_id ?? 1);
    }

    /**
     * Dedicated landing page RENJA Lampiran:
     * Menampilkan 2 Card terpisah: RENJA Lampiran Murni & RENJA Lampiran Perubahan.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);
        $opd = MasterOpd::find($opdId);

        $sessionTa = session('active_ta');
        $baseTa = ($sessionTa && is_numeric($sessionTa) && (int)$sessionTa >= 2020) ? (int)$sessionTa : (int)date('Y');

        $queryTa = $request->query('tahun_anggaran');
        if ($queryTa && is_numeric($queryTa) && (int)$queryTa >= 2020) {
            $ta = (int)$queryTa;
            session(['active_ta' => $ta]);
        } else {
            $ta = $baseTa;
        }

        $romawiNumber = RenjaAutoFixService::getRomanHeaderForOpd($opd);

        // Base Year $ta = 2026: TA Murni = 2027 ($ta + 1), TA Perubahan = 2026 ($ta)
        $taMurni = $ta + 1;
        $taPerubahan = $ta;

        // Dokumen Lampiran Murni (TA Murni = $ta + 1)
        $tmplMurni = DocumentTemplate::where('code', 'RENJA_LAMPIRAN_MURNI')->first();
        $renjaLampiranMurni = RenjaDocument::where('opd_id', $opdId)
            ->where('tahun_anggaran', $taMurni)
            ->where(function ($q) use ($tmplMurni) {
                $q->where('jenis_dokumen', 'RENJA Lampiran Murni')
                    ->orWhere('jenis_dokumen', 'LIKE', '%Perbup%');
                if ($tmplMurni) {
                    $q->orWhere('template_id', $tmplMurni->id);
                }
            })
            ->latest()
            ->first();

        // Dokumen Lampiran Perubahan (TA Perubahan = $ta)
        $tmplPerubahan = DocumentTemplate::where('code', 'RENJA_LAMPIRAN_PERUBAHAN')->first();
        $renjaLampiranPerubahan = RenjaDocument::where('opd_id', $opdId)
            ->where('tahun_anggaran', $taPerubahan)
            ->where(function ($q) use ($tmplPerubahan) {
                $q->where('jenis_dokumen', 'RENJA Lampiran Perubahan')
                    ->orWhere('jenis_dokumen', 'LIKE', '%Kepbup%');
                if ($tmplPerubahan) {
                    $q->orWhere('template_id', $tmplPerubahan->id);
                }
            })
            ->latest()
            ->first();

        // Syarat ketersediaan Lampiran Murni: Harus ada RENJA Murni TA Murni (berlaku untuk semua status)
        $parentMurni = RenjaDocument::where('opd_id', $opdId)
            ->where('tahun_anggaran', $taMurni)
            ->where('jenis_dokumen', 'LIKE', '%Murni%')
            ->where('jenis_dokumen', 'NOT LIKE', '%Lampiran%')
            ->where('jenis_dokumen', 'NOT LIKE', '%Perbup%')
            ->where('jenis_dokumen', 'NOT LIKE', '%Kepbup%')
            ->latest()
            ->first();
        $canCreateLampiranMurni = $parentMurni !== null;

        if ($parentMurni && !$renjaLampiranMurni) {
            try {
                $renjaLampiranMurni = $this->opdDocumentService->generateLampiranPerbub($opdId, $taMurni, 'murni');
            } catch (\Throwable $e) {}
        }

        // Syarat ketersediaan Lampiran Perubahan: Harus ada RENJA Perubahan TA Perubahan (berlaku untuk semua status)
        $parentPerubahan = RenjaDocument::where('opd_id', $opdId)
            ->where('tahun_anggaran', $taPerubahan)
            ->where('jenis_dokumen', 'LIKE', '%Perubahan%')
            ->where('jenis_dokumen', 'NOT LIKE', '%Lampiran%')
            ->where('jenis_dokumen', 'NOT LIKE', '%Perbup%')
            ->where('jenis_dokumen', 'NOT LIKE', '%Kepbup%')
            ->latest()
            ->first();
        $canCreateLampiranPerubahan = $parentPerubahan !== null;

        if ($parentPerubahan && !$renjaLampiranPerubahan) {
            try {
                $renjaLampiranPerubahan = $this->opdDocumentService->generateLampiranPerbub($opdId, $taPerubahan, 'perubahan');
            } catch (\Throwable $e) {}
        }

        return view('renja.lampiran.index', compact(
            'opd',
            'ta',
            'taMurni',
            'taPerubahan',
            'romawiNumber',
            'renjaLampiranMurni',
            'renjaLampiranPerubahan',
            'canCreateLampiranMurni',
            'canCreateLampiranPerubahan',
            'parentMurni',
            'parentPerubahan'
        ));
    }

    /**
     * Buat Dokumen RENJA Lampiran Murni dari Template/Parent
     */
    public function storeMurni(Request $request)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);
        $ta = (int) $request->input('tahun_anggaran', session('active_ta', (int)date('Y') + 1));

        try {
            $doc = $this->opdDocumentService->generateLampiranPerbub($opdId, $ta, 'murni');
            return redirect()->route('renja.lampiran.index', ['tahun_anggaran' => $ta])
                ->with('success', "Dokumen RENJA Lampiran Murni TA {$ta} berhasil dibuat.");
        } catch (\Throwable $e) {
            return redirect()->route('renja.lampiran.index', ['tahun_anggaran' => $ta])
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Buat Dokumen RENJA Lampiran Perubahan dari Template/Parent
     */
    public function storePerubahan(Request $request)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);
        $ta = (int) $request->input('tahun_anggaran', session('active_ta', (int)date('Y')));

        try {
            $doc = $this->opdDocumentService->generateLampiranPerbub($opdId, $ta, 'perubahan');
            return redirect()->route('renja.lampiran.index', ['tahun_anggaran' => $ta])
                ->with('success', "Dokumen RENJA Lampiran Perubahan TA {$ta} berhasil dibuat.");
        } catch (\Throwable $e) {
            return redirect()->route('renja.lampiran.index', ['tahun_anggaran' => $ta])
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Upload File DOCX untuk RENJA Lampiran Murni
     */
    public function uploadMurni(Request $request)
    {
        $request->validate([
            'tahun_anggaran' => ['required', 'integer', 'min:2020', 'max:2099'],
            'document_file' => ['required', 'file', 'mimes:docx,doc', 'max:20480'],
        ]);

        return $this->handleUpload($request, 'murni');
    }

    /**
     * Upload File DOCX untuk RENJA Lampiran Perubahan
     */
    public function uploadPerubahan(Request $request)
    {
        $request->validate([
            'tahun_anggaran' => ['required', 'integer', 'min:2020', 'max:2099'],
            'document_file' => ['required', 'file', 'mimes:docx,doc', 'max:20480'],
        ]);

        return $this->handleUpload($request, 'perubahan');
    }

    /**
     * Centralized upload processing for Lampiran Murni / Perubahan.
     * Guaranteed 100% automatic Roman Attachment number from OPD, zero manual override payload.
     */
    protected function handleUpload(Request $request, string $type)
    {
        $user = Auth::user();
        $opdId = $this->getEffectiveOpdIdForUser($user);
        $opd = MasterOpd::find($opdId);
        $ta = (int) $request->input('tahun_anggaran');

        $isPerubahan = ($type === 'perubahan');
        $templateCode = $isPerubahan ? 'RENJA_LAMPIRAN_PERUBAHAN' : 'RENJA_LAMPIRAN_MURNI';
        $jenisDokumen = $isPerubahan ? 'RENJA Lampiran Perubahan' : 'RENJA Lampiran Murni';
        $jenisOutput = $isPerubahan ? "Lampiran Kepbup Perubahan Renja Tahun {$ta}" : "Lampiran Perbup Renja Tahun {$ta}";

        $romawiHeader = RenjaAutoFixService::getRomanHeaderForOpd($opd);

        $file = $request->file('document_file');
        $filename = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension();

        $opdSlug = Str::slug($opd?->nama_opd ?? "opd-{$opdId}");
        $jenisSlug = $isPerubahan ? 'lampiran-kepbup' : 'lampiran-perbub';
        $subFolder = "renja/{$ta}/{$opdSlug}/{$jenisSlug}/original";
        $hashPrefix = substr(md5(uniqid(microtime(true), true)), 0, 12);
        $storedFilename = "{$hashPrefix}_{$filename}";

        $storedPath = $file->storeAs($subFolder, $storedFilename, 'private');

        $template = DocumentTemplate::where('code', $templateCode)->first();

        $parentInduk = RenjaDocument::where('opd_id', $opdId)
            ->where('tahun_anggaran', $ta)
            ->where('jenis_dokumen', 'LIKE', $isPerubahan ? '%Perubahan%' : '%Murni%')
            ->where('jenis_dokumen', 'NOT LIKE', '%Lampiran%')
            ->where('jenis_dokumen', 'NOT LIKE', '%Perbup%')
            ->where('jenis_dokumen', 'NOT LIKE', '%Kepbup%')
            ->latest()
            ->first();

        // Cari atau buat entri dokumen terpisah
        $doc = RenjaDocument::where('opd_id', $opdId)
            ->where('tahun_anggaran', $ta)
            ->where('jenis_dokumen', $jenisDokumen)
            ->first();

        if (!$doc) {
            $doc = new RenjaDocument();
            $doc->opd_id = $opdId;
            $doc->tahun_anggaran = $ta;
            $doc->year = $ta;
            $doc->jenis_dokumen = $jenisDokumen;
            $doc->status = $parentInduk ? $parentInduk->status : 'draft';
        }

        $doc->template_id = $template?->id;
        $doc->source_type = 'upload_word';

        $coverData = $doc->cover_data ?? [];
        $coverData['judul_dokumen'] = strtoupper($jenisDokumen);
        $coverData['jenis_output'] = $jenisOutput;
        $coverData['tahun_anggaran'] = $ta;
        $coverData['nama_pemda'] = 'PEMERINTAH KABUPATEN CIREBON';
        $coverData['nama_opd'] = $opd?->nama_opd ?? 'PERANGKAT DAERAH';
        $coverData['lokasi'] = 'SUMBER';
        $coverData['romawi_header'] = $romawiHeader;
        $doc->cover_data = $coverData;

        $meta = $doc->metadata ?? [];
        if ($parentInduk) {
            $meta['generated_from_parent_id'] = $parentInduk->id;
        }
        $meta['original_filename'] = $filename;
        $meta['stored_filepath'] = $storedPath;
        $meta['uploaded_at'] = now()->toIso8601String();
        $meta['romawi_header'] = $romawiHeader;
        
        $auditTrail = $meta['audit_trail'] ?? [];
        $auditTrail[] = [
            'action' => 'ORIGINAL_DOCX_UPLOADED',
            'notes' => "File original Word '{$filename}' berhasil diunggah untuk {$jenisDokumen} dengan penomoran {$romawiHeader}.",
            'timestamp' => now()->toIso8601String(),
            'user_name' => $user->nama_lengkap ?? $user->name ?? 'Operator OPD',
        ];
        $meta['audit_trail'] = $auditTrail;
        $doc->metadata = $meta;

        $doc->save();

        $redirectTa = $isPerubahan ? $ta : ($ta - 1);
        return redirect()->route('renja.lampiran.index', ['tahun_anggaran' => $redirectTa])
            ->with('success', "File original Word untuk {$jenisDokumen} TA {$ta} berhasil diunggah.");
    }
}
