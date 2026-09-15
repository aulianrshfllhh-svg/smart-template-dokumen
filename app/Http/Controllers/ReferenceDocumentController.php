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

        $catalogs = $this->schemaService->getCatalogTables();

        // Dokumen Renja aktif yang bisa diterapkan schema ini
        $renjaDocuments = RenjaDocument::latest()->limit(50)->get();

        return view('reference_documents.show', compact('schema', 'catalogs', 'renjaDocuments'));
    }

    /**
     * Update detail/mapping sub-bab (misal ganti tipe_konten atau sumber_data tabel).
     */
    public function updateSubChapter(Request $request, $id)
    {
        $request->validate([
            'sub_chapter_id' => 'required|exists:template_sub_chapters,id',
            'tipe_konten'    => 'required|in:rich_text,tabel',
            'sumber_data'    => 'nullable|string|max:100',
        ]);

        $subChapter = TemplateSubChapter::findOrFail($request->sub_chapter_id);
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
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $schema = ReferenceDocumentSchema::findOrFail($id);

        $schema->update([
            'status'              => 'rejected',
            'parse_error_message' => 'Ditolak Admin: ' . $request->reason,
        ]);

        Log::info('[ReferenceDocumentController] Schema ditolak.', ['schema_id' => $schema->id]);

        return redirect()->route('reference-documents.show', $schema->id)
            ->with('warning', 'Schema Dokumen Acuan ditolak.');
    }

    /**
     * Terpakan schema ke dokumen Renja tertentu.
     */
    public function applyToDocument(Request $request, $id)
    {
        $request->validate([
            'renja_document_id' => 'required|exists:renja_documents,id',
        ]);

        $schema        = ReferenceDocumentSchema::findOrFail($id);
        $renjaDocument = RenjaDocument::findOrFail($request->renja_document_id);

        if (!$schema->isApproved()) {
            return redirect()->back()
                ->with('error', 'Hanya Schema yang berstatus Disetujui (Approved) yang dapat diterapkan ke Dokumen Renja.');
        }

        $appliedCount = $this->schemaService->applySchemaToDocument($renjaDocument, $schema);

        return redirect()->route('renja.editor', $renjaDocument->id)
            ->with('success', "Berhasil menerapkan {$appliedCount} komponen BAB & Sub-Bab ke editor Dokumen Renja.");
    }

    /**
     * Hapus schema dokumen acuan.
     */
    public function destroy($id)
    {
        $schema = ReferenceDocumentSchema::findOrFail($id);

        if ($schema->stored_path && Storage::exists($schema->stored_path)) {
            Storage::delete($schema->stored_path);
        }

        $schema->delete();

        return redirect()->route('reference-documents.index')
            ->with('success', 'Dokumen acuan berhasil dihapus.');
    }
}
