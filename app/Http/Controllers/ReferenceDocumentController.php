<?php

namespace App\Http\Controllers;

use App\Jobs\ParseReferenceDocumentJob;
use App\Models\ReferenceDocumentSchema;
use App\Models\RenjaDocument;
use App\Models\TemplateSubChapter;
use App\Services\TemplateSchemaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReferenceDocumentController extends Controller
{
    public function __construct(
        protected TemplateSchemaService $schemaService
    ) {}

    /**
     * Tampilkan daftar dokumen acuan (schema).
     */
    public function index(Request $request)
    {
        $query = ReferenceDocumentSchema::with(['creator', 'approver', 'chapters.subChapters'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('jenis')) {
            $query->where('jenis_dokumen', $request->jenis);
        }

        $schemas = $query->paginate(10)->withQueryString();

        $stats = [
            'total'    => ReferenceDocumentSchema::count(),
            'draft'    => ReferenceDocumentSchema::where('status', 'draft')->count(),
            'approved' => ReferenceDocumentSchema::where('status', 'approved')->count(),
            'rejected' => ReferenceDocumentSchema::where('status', 'rejected')->count(),
        ];

        return view('reference_documents.index', compact('schemas', 'stats'));
    }

    /**
     * Form upload dokumen acuan baru.
     */
    public function create()
    {
        $jenisDokumenList = [
            'LAMPIRAN Renja OPD'     => 'LAMPIRAN Renja OPD',
            'SOP Pembentukan Renja'   => 'SOP Pembentukan Renja',
            'Pedoman Penyusunan Renja' => 'Pedoman Penyusunan Renja',
            'Lainnya'                => 'Dokumen Acuan Lainnya',
        ];

        return view('reference_documents.create', compact('jenisDokumenList'));
    }

    /**
     * Simpan file upload dan jadwalkan job parsing background/sync.
     */
    public function store(Request $request)
    {
        $request->validate([
            'jenis_dokumen' => 'required|string|max:100',
            'document_file' => 'required|file|mimes:docx|max:20480', // max 20MB .docx
        ], [
            'jenis_dokumen.required' => 'Jenis dokumen wajib dipilih.',
            'document_file.required' => 'File dokumen acuan (.docx) wajib diupload.',
            'document_file.mimes'    => 'File harus berformat Microsoft Word (.docx).',
            'document_file.max'      => 'Ukuran file maksimal 20 MB.',
        ]);

        $file     = $request->file('document_file');
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());

        // Simpan file di storage/app/reference_docs
        $storedPath = $file->storeAs('reference_docs', $filename);

        $schema = ReferenceDocumentSchema::create([
            'jenis_dokumen'     => $request->jenis_dokumen,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path'       => $storedPath,
            'status'            => 'draft',
            'created_by'        => Auth::id() ?? 1,
        ]);

        Log::info('[ReferenceDocumentController] Dokumen acuan diupload.', [
            'schema_id' => $schema->id,
            'path'      => $storedPath,
        ]);

        // Dispatch job parsing. Eksekusi secara synchronous untuk feedback langsung jika di local
        if (config('queue.default') === 'sync') {
            ParseReferenceDocumentJob::dispatchSync($schema->id);
        } else {
            ParseReferenceDocumentJob::dispatch($schema->id);
        }

        return redirect()->route('reference-documents.show', $schema->id)
            ->with('success', 'Dokumen acuan berhasil diupload dan diproses. Silakan selesaikan review struktur BAB & Sub-Bab.');
    }

    /**
     * Tampilkan detail & preview struktur BAB/Sub-Bab terdeteksi.
     */
    public function show($id)
    {
        $schema = ReferenceDocumentSchema::with([
            'creator',
            'approver',
            'chapters.subChapters.tableColumns'
        ])->findOrFail($id);
        $this->authorize('view', $schema);

        $catalogs = $this->schemaService->getCatalogTables();

        // Dokumen Renja aktif yang bisa diterapkan schema ini (hanya dokumen non-lampiran dan dalam scope akses user)
        $user = Auth::user();
        $query = RenjaDocument::with('opd')
            ->where('jenis_dokumen', 'NOT LIKE', '%lampiran%')
            ->whereIn('status', ['draft', 'belum_dikerjakan', 'perlu_revisi', 'revisi', 'revision'])
            ->latest();

        if ($user && $user->isOperator()) {
            $query->where('opd_id', $user->opd_id);
        }

        $renjaDocuments = $query->limit(50)->get();

        return view('reference_documents.show', compact('schema', 'catalogs', 'renjaDocuments'));
    }

    /**
     * Update detail/mapping sub-bab (misal ganti tipe_konten atau sumber_data tabel).
     */
    public function updateSubChapter(Request $request, $id)
    {
        $this->authorizeAdminAccess();

        $request->validate([
            'sub_chapter_id' => 'required|exists:template_sub_chapters,id',
            'tipe_konten'    => 'required|in:rich_text,tabel',
            'sumber_data'    => 'nullable|string|max:100',
        ]);

        $subChapter = TemplateSubChapter::whereHas('chapter', fn ($q) => $q->where('schema_id', $id))->findOrFail($request->sub_chapter_id);
        $subChapter->update([
            'tipe_konten'  => $request->tipe_konten,
            'sumber_data'  => $request->sumber_data,
            'match_status' => 'auto_matched', // Manual edit dikonfirmasi
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Sub-Bab berhasil diperbarui.',
            'data'    => $subChapter,
        ]);
    }

    /**
     * Approve schema dokumen acuan (Admin setujui).
     */
    public function approve(Request $request, $id)
    {
        $this->authorizeAdminAccess();

        $schema = ReferenceDocumentSchema::findOrFail($id);

        $schema->update([
            'status'      => 'approved',
            'approved_by' => Auth::id() ?? 1,
            'approved_at' => now(),
        ]);

        Log::info('[ReferenceDocumentController] Schema disetujui.', ['schema_id' => $schema->id]);

        return redirect()->route('reference-documents.show', $schema->id)
            ->with('success', 'Schema Dokumen Acuan telah DISETUJUI. Schema ini dapat diterapkan ke template editor Renja.');
    }

    /**
     * Reject schema dokumen acuan.
     */
    public function reject(Request $request, $id)
    {
        $this->authorizeAdminAccess();

        $request->validate([
            'rejection_notes' => 'nullable|string|max:500',
            'reason'          => 'nullable|string|max:500',
        ]);

        $reason = $request->input('rejection_notes') ?? $request->input('reason') ?? 'Ditolak Admin';

        $schema = ReferenceDocumentSchema::findOrFail($id);

        $schema->update([
            'status'              => 'rejected',
            'parse_error_message' => 'Ditolak Admin: ' . $reason,
        ]);

        Log::info('[ReferenceDocumentController] Schema ditolak.', ['schema_id' => $schema->id]);

        return redirect()->route('reference-documents.show', $schema->id)
            ->with('warning', 'Schema Dokumen Acuan ditolak.');
    }

    /**
     * Terapkan schema ke dokumen Renja tertentu.
     */
    public function applyToDocument(Request $request, $id)
    {
        // Load schema dahulu (satu query saja)
        $schema = ReferenceDocumentSchema::findOrFail($id);

        // IDOR & Role Guard: hanya Bapperida internal yang diizinkan apply
        $this->authorize('apply', $schema);

        $request->validate([
            'renja_document_id' => 'required|exists:renja_documents,id',
        ]);

        $renjaDocument = RenjaDocument::findOrFail($request->renja_document_id);

        // Security: Validasi ownership dokumen sasaran (IDOR Protection)
        $this->authorize('update', $renjaDocument);

        // Validasi: Hanya schema approved yang dapat diterapkan
        if (!$schema->isApproved()) {
            return redirect()->back()
                ->with('error', 'Hanya Schema yang berstatus Disetujui (Approved) yang dapat diterapkan ke Dokumen Renja.');
        }

        // Validasi: Dokumen Lampiran Perbub tidak boleh ditimpa struktur mandiri
        if ($renjaDocument->isLampiranPerbub()) {
            return redirect()->back()
                ->with('error', 'Schema dokumen acuan tidak dapat diterapkan ke Dokumen Lampiran. Dokumen Lampiran merupakan turunan otomatis dari Dokumen Induk.');
        }

        // Validasi status dokumen target (hanya dokumen editable/draft yang boleh menerima schema baru)
        $nonEditableStatuses = ['disetujui', 'approved', 'dikunci', 'final', 'submitted', 'menunggu_verifikasi', 'menunggu_pemeriksaan'];
        if (in_array(strtolower($renjaDocument->status), $nonEditableStatuses)) {
            return redirect()->back()
                ->with('error', 'Dokumen Renja sasaran berstatus ' . strtoupper($renjaDocument->status) . ' sehingga tidak dapat dimodifikasi strukturnya.');
        }

        $appliedCount = $this->schemaService->applySchemaToDocument($renjaDocument, $schema);

        // AUDIT LOG – rekam setiap penerapan schema ke dokumen Renja
        Log::info('[AcuanDokumen] Schema diterapkan ke Renja.', [
            'user_id'            => Auth::id(),
            'user_name'          => Auth::user()?->name,
            'schema_id'          => $schema->id,
            'schema_jenis'       => $schema->jenis_dokumen,
            'renja_document_id'  => $renjaDocument->id,
            'renja_opd'          => $renjaDocument->opd?->nama_opd ?? '-',
            'applied_count'      => $appliedCount,
            'timestamp'          => now()->toDateTimeString(),
        ]);

        return redirect()->route('renja.editor', $renjaDocument->id)
            ->with('success', "Berhasil menerapkan {$appliedCount} komponen BAB & Sub-Bab ke editor Dokumen Renja.");
    }

    /**
     * Hapus schema dokumen acuan.
     */
    public function destroy($id)
    {
        $this->authorizeAdminAccess();

        $schema = ReferenceDocumentSchema::findOrFail($id);
        $this->authorize('delete', $schema);

        if ($schema->stored_path && Storage::exists($schema->stored_path)) {
            Storage::delete($schema->stored_path);
        }

        $schema->delete();

        return redirect()->route('reference-documents.index')
            ->with('success', 'Dokumen acuan berhasil dihapus.');
    }

    /**
     * Pastikan pengguna memiliki wewenang administratif Bapperida.
     * Digunakan oleh method-method yang belum dijadwalkan untuk policy penuh.
     */
    protected function authorizeAdminAccess(): void
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isVerifikator() && !$user->isStaff())) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola Dokumen Acuan.');
        }
    }

    /**
     * Validasi otorisasi & ownership dokumen sasaran untuk mencegah IDOR.
     */
    protected function authorizeDocumentAccess(RenjaDocument $document): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        // Operator hanya boleh menyentuh dokumen OPD mereka sendiri
        if ($user->isOperator() && (int)$document->opd_id !== (int)$user->opd_id) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang menerapkan schema ke dokumen milik Perangkat Daerah lain.');
        }
    }
}
