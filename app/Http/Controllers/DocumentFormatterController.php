<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Str;
use App\Models\MasterOpd;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Services\RenjaAutoFixService;

class DocumentFormatterController extends Controller
{
    /**
     * Tampilkan halaman Form Upload Mesin Cuci Dokumen Smart Template (Auto-Formatter).
     */
    public function index()
    {
        $opds = MasterOpd::all();
        $templates = \App\Models\DocumentTemplate::where('is_active', true)->get();
        return view('renja.formatter', compact('opds', 'templates'));
    }

    /**
     * Handle unggah file .docx, jalankan script Python formatter.py presisi tinggi,
     * simpan ke database dashboard, dan redirect dengan URL pratinjau & unduh file baku.
     */
    public function process(Request $request)
    {
        $request->validate([
            'opd_id' => ['required', 'exists:master_opd,id'],
            'tahun_anggaran' => ['required', 'integer'],
            'template_code' => ['required', 'string', 'exists:document_templates,code'],
            'document_file' => ['required', 'file', 'mimes:docx', 'max:20480'], // max 20MB
        ]);

        $opd = MasterOpd::findOrFail($request->opd_id);
        $template = \App\Models\DocumentTemplate::where('code', $request->template_code)->firstOrFail();
        $tahunAnggaran = (int) $request->tahun_anggaran;
        $uploadedFile = $request->file('document_file');

        // Siapkan direktori storage
        $tempDir = storage_path('app/temp_formatter');
        $publicDir = storage_path('app/public/renja_formatted');

        if (!file_exists($tempDir)) mkdir($tempDir, 0755, true);
        if (!file_exists($publicDir)) mkdir($publicDir, 0755, true);

        $uniqueId = Str::random(12);
        $inputPath = $tempDir . DIRECTORY_SEPARATOR . 'input_' . $uniqueId . '.docx';

        // Pindahkan file input ke folder temporary
        $uploadedFile->move($tempDir, 'input_' . $uniqueId . '.docx');

        // Path script python
        $scriptPath = base_path('scripts' . DIRECTORY_SEPARATOR . 'formatter.py');
        $pythonBinary = env('PYTHON_BINARY_PATH', 'python');

        try {
            // Jalankan script python dengan argumen template_code
            $env = array_merge($_SERVER, $_ENV, [
                'APPDATA' => getenv('APPDATA') ?: ('C:\\Users\\' . (getenv('USERNAME') ?: 'Aulia Nur Shafa') . '\\AppData\\Roaming'),
                'PATH' => getenv('PATH'),
            ]);

            $process = new Process([
                $pythonBinary,
                $scriptPath,
                $inputPath,
                $publicDir,
                $opd->nomor_lampiran_romawi ?? 'LAMPIRAN_LIII',
                str_replace(' ', '_', $opd->nama_opd ?? 'Kecamatan_Suranenggala'),
                (string) $tahunAnggaran,
                strtoupper($template?->code ?? 'RENJA')
            ], null, $env);

            $process->setTimeout(120); // 2 menit timeout
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $outputLog = $process->getOutput();
            $generatedFilePath = null;

            if (preg_match('/OUTPUT_FILE:(.+)/', $outputLog, $matches)) {
                $generatedFilePath = trim($matches[1]);
            }

            // Extract Python Auto Fix Diagnostic output
            $diagnosticLogs = [];
            if (preg_match('/AUTO_FIX_DIAGNOSTIC_START\s*(.*?)\s*AUTO_FIX_DIAGNOSTIC_END/s', $outputLog, $diagMatches)) {
                $rawDiag = trim($diagMatches[1]);
                $diagnosticLogs = array_values(array_filter(array_map('trim', explode("=== AUTO FIX DIAGNOSTIC", $rawDiag))));
            }

            $downloadFilename = $generatedFilePath ? basename($generatedFilePath) : (($opd->nomor_lampiran_romawi ?? 'Lampiran XII') . '_' . ($opd->nama_opd ?? 'Kecamatan Suranenggala') . '.docx');

            // Simpan dokumen hasil pencucian ke database agar muncul di Dashboard
            $autoFixService = new RenjaAutoFixService();
            $fileToParse = ($generatedFilePath && file_exists($generatedFilePath)) ? $generatedFilePath : $inputPath;
            $rawContent = $autoFixService->extractContentFromFile($fileToParse);
            $parsed = $autoFixService->processAutoFix($rawContent, $opd, $tahunAnggaran);

            $document = RenjaDocument::create([
                'opd_id' => $opd->id,
                'template_id' => $template->id,
                'tahun_anggaran' => $tahunAnggaran,
                'jenis_dokumen' => $template->name,
                'status' => 'draft',
                'latar_belakang' => $parsed['sections'][0]['content'] ?? '',
                'landasan_hukum' => $parsed['sections'][1]['content'] ?? '',
                'maksud_tujuan' => $parsed['sections'][2]['content'] ?? '',
                'sistematika' => $parsed['sections'][3]['content'] ?? '',
                'penutup_narasi' => end($parsed['sections'])['content'] ?? '',
            ]);

            foreach ($parsed['sections'] as $sec) {
                $sec['document_id'] = $document->id;
                RenjaSection::create($sec);
            }

            // Hapus file input temporary
            if (file_exists($inputPath)) @unlink($inputPath);

            session([
                'autofix_diagnostic_logs_' . $document->id => $diagnosticLogs,
                'autofix_audit_logs_' . $document->id => $parsed['audit_logs'],
            ]);

            // Redirect ke halaman index dengan pesan sukses, URL preview F4 & URL download khusus
            return redirect()->route('formatter.index')
                ->with('success', 'Dokumen Renja ' . $opd->nama_opd . ' berhasil dicuci & disimpan ke Dashboard!')
                ->with('preview_url', route('renja.print', $document->id))
                ->with('editor_url', route('renja.editor', $document->id))
                ->with('download_url', route('formatter.download', $downloadFilename))
                ->with('download_filename', $downloadFilename)
                ->with('document_id', $document->id)
                ->with('opd_nama', $opd->nama_opd)
                ->with('opd_romawi', $opd->nomor_lampiran_romawi);

        } catch (\Exception $e) {
            // Hapus file temporary jika terjadi kegagalan
            if (file_exists($inputPath)) @unlink($inputPath);

            return back()->with('error', 'Gagal memproses dokumen: ' . $e->getMessage());
        }
    }

    /**
     * Force download file .docx terformat dari storage public.
     */
    public function downloadFile($filename)
    {
        $filePath = storage_path('app/public/renja_formatted/' . $filename);
        if (!file_exists($filePath)) {
            return redirect()->route('formatter.index')->with('error', 'File hasil pencucian tidak ditemukan atau sudah kedaluwarsa.');
        }

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
