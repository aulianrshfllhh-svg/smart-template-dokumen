<?php

namespace App\Jobs;

use App\Models\ReferenceDocumentSchema;
use App\Models\TemplateChapter;
use App\Models\TemplateSubChapter;
use App\Models\TemplateTableColumn;
use App\Services\TemplateSchemaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ParseReferenceDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan jika job gagal.
     */
    public int $tries = 1;

    /**
     * Timeout dalam detik.
     */
    public int $timeout = 120;

    public function __construct(
        protected int $schemaId
    ) {}

    /**
     * Jalankan job: parse .docx acuan via Python CLI, simpan hasil ke DB.
     */
    public function handle(TemplateSchemaService $templateService): void
    {
        $schema = ReferenceDocumentSchema::find($this->schemaId);

        if (!$schema) {
            Log::error('[ParseReferenceDocumentJob] Schema ID tidak ditemukan.', [
                'schema_id' => $this->schemaId,
            ]);
            return;
        }

        Log::info('[ParseReferenceDocumentJob] Mulai parsing dokumen acuan.', [
            'schema_id'         => $schema->id,
            'original_filename' => $schema->original_filename,
            'stored_path'       => $schema->stored_path,
        ]);

        // --- Resolusi path absolut file .docx ---
        $absolutePath = storage_path('app/' . $schema->stored_path);

        if (!file_exists($absolutePath)) {
            $errorMsg = "File tidak ditemukan di storage: {$absolutePath}";
            Log::error('[ParseReferenceDocumentJob] ' . $errorMsg, ['schema_id' => $schema->id]);
            $schema->update([
                'status'              => 'rejected',
                'parse_error_message' => $errorMsg,
            ]);
            return;
        }

        // --- Jalankan Python CLI parser ---
        $scriptPath   = base_path('scripts' . DIRECTORY_SEPARATOR . 'parse_reference_doc.py');
        $pythonBinary = env('PYTHON_BINARY_PATH', 'python');

        $env = array_merge($_SERVER, $_ENV, [
            'APPDATA' => getenv('APPDATA') ?: ('C:\\Users\\' . (getenv('USERNAME') ?: 'Aulia Nur Shafa') . '\\AppData\\Roaming'),
            'PATH'    => getenv('PATH'),
        ]);

        Log::info('[ParseReferenceDocumentJob] Memanggil Python parser.', [
            'script' => $scriptPath,
            'file'   => $absolutePath,
        ]);

        $process = new Process(
            [$pythonBinary, $scriptPath, $absolutePath],
            null,
            $env
        );
        $process->setTimeout(90);

        try {
            $process->run();
        } catch (\Exception $e) {
            $errorMsg = 'Gagal menjalankan Python parser: ' . $e->getMessage();
            Log::error('[ParseReferenceDocumentJob] ' . $errorMsg, ['schema_id' => $schema->id]);
            $schema->update([
                'status'              => 'rejected',
                'parse_error_message' => $errorMsg,
            ]);
            return;
        }

        // --- Cek exit code Python ---
        if (!$process->isSuccessful()) {
            // Coba ambil pesan error dari stderr (JSON atau plain text)
            $stderr    = $process->getErrorOutput();
            $errorData = json_decode($stderr, true);
            $errorMsg  = $errorData['message'] ?? $stderr ?: 'Python parser gagal tanpa pesan error.';

            Log::warning('[ParseReferenceDocumentJob] Python parser mengembalikan error.', [
                'schema_id' => $schema->id,
                'exit_code' => $process->getExitCode(),
                'stderr'    => $stderr,
            ]);

            $schema->update([
                'status'              => 'rejected',
                'parse_error_message' => $errorMsg,
            ]);
            return;
        }

        // --- Decode JSON output Python ---
        $jsonOutput = trim($process->getOutput());
        $parsed     = json_decode($jsonOutput, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($parsed['bab'])) {
            $errorMsg = 'Output Python parser bukan JSON valid: ' . json_last_error_msg();
            Log::error('[ParseReferenceDocumentJob] ' . $errorMsg, [
                'schema_id' => $schema->id,
                'output'    => substr($jsonOutput, 0, 500),
            ]);
            $schema->update([
                'status'              => 'rejected',
                'parse_error_message' => $errorMsg,
            ]);
            return;
        }

        // --- Validasi: minimal 1 BAB ---
        if (empty($parsed['bab'])) {
            $errorMsg = 'Struktur tidak terdeteksi: tidak ada BAB ditemukan dalam dokumen. Isi manual via editor.';
            Log::warning('[ParseReferenceDocumentJob] ' . $errorMsg, ['schema_id' => $schema->id]);
            $schema->update([
                'status'              => 'rejected',
                'parse_error_message' => $errorMsg,
            ]);
            return;
        }

        Log::info('[ParseReferenceDocumentJob] JSON berhasil di-decode.', [
            'schema_id'      => $schema->id,
            'jumlah_bab'     => count($parsed['bab']),
            'jumlah_tabel'   => count($parsed['tabel_terdeteksi'] ?? []),
        ]);

        // --- Hapus chapter lama jika ada (idempotent re-run) ---
        TemplateChapter::where('schema_id', $schema->id)->delete();

        // --- Jalankan fuzzy matching tabel ke katalog SIPD ---
        $detectedTables = $parsed['tabel_terdeteksi'] ?? [];
        $matchedTables  = $templateService->matchTablesFromCatalog($detectedTables);

        Log::info('[ParseReferenceDocumentJob] Fuzzy matching tabel selesai.', [
            'schema_id'    => $schema->id,
            'total_tabel'  => count($detectedTables),
            'matched'      => count(array_filter($matchedTables, fn($t) => $t['match_status'] === 'auto_matched')),
        ]);

        // --- Simpan BAB dan Sub-Bab ke database ---
        $urutanBab = 0;

        foreach ($parsed['bab'] as $babData) {
            $urutanBab++;

            $chapter = TemplateChapter::create([
                'schema_id' => $schema->id,
                'nomor_bab' => $babData['nomor'] ?? '?',
                'bab_code'  => $babData['bab_code'] ?? ('BAB ' . ($babData['nomor'] ?? '?')),
                'judul'     => $babData['judul'] ?? '',
                'urutan'    => $urutanBab,
            ]);

            Log::info('[ParseReferenceDocumentJob] BAB disimpan.', [
                'chapter_id' => $chapter->id,
                'bab_code'   => $chapter->bab_code,
                'judul'      => $chapter->judul,
            ]);

            $urutanSubBab = 0;

            foreach (($babData['sub_bab'] ?? []) as $subBabData) {
                $urutanSubBab++;
                $kode = $subBabData['kode'] ?? '';

                // Cari tabel yang cocok untuk sub-bab ini berdasarkan lokasi BAB
                // Kita periksa apakah ada tabel yang di-match ke bab_code ini
                $tableMatch = $this->findMatchForSubBab(
                    $babData['bab_code'] ?? '',
                    $kode,
                    $matchedTables,
                    $detectedTables
                );

                $tipeKonten  = $tableMatch['tipe_konten'] ?? 'rich_text';
                $sumberData  = $tableMatch['sumber_data'] ?? null;
                $matchStatus = $tableMatch['match_status'] ?? 'no_table';
                $matchScore  = $tableMatch['match_score'] ?? 0;

                $subChapter = TemplateSubChapter::create([
                    'chapter_id'   => $chapter->id,
                    'kode'         => $kode,
                    'judul'        => $subBabData['judul'] ?? '',
                    'tipe_konten'  => $tipeKonten,
                    'sumber_data'  => $sumberData,
                    'match_status' => $matchStatus,
                    'match_score'  => $matchScore,
                    'urutan'       => $urutanSubBab,
                ]);

                // Jika sub-bab adalah tabel, simpan definisi kolomnya
                if ($tipeKonten === 'tabel' && isset($tableMatch['header'])) {
                    foreach ($tableMatch['header'] as $colIdx => $namaKolom) {
                        TemplateTableColumn::create([
                            'sub_chapter_id' => $subChapter->id,
                            'urutan'         => $colIdx + 1,
                            'nama_kolom'     => $namaKolom,
                            'kolom_key'      => $this->generateKolomKey($namaKolom),
                        ]);
                    }
                }

                Log::info('[ParseReferenceDocumentJob] Sub-Bab disimpan.', [
                    'sub_chapter_id' => $subChapter->id,
                    'kode'           => $kode,
                    'tipe_konten'    => $tipeKonten,
                    'match_status'   => $matchStatus,
                    'match_score'    => $matchScore,
                ]);
            }
        }

        // --- Update schema: parsing selesai, status → draft (menunggu approve admin) ---
        $schema->update([
            'status'      => 'draft',
            'parsed_json' => $parsed,
            'parsed_at'   => now(),
        ]);

        Log::info('[ParseReferenceDocumentJob] Parsing selesai. Schema siap untuk direview admin.', [
            'schema_id' => $schema->id,
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[ParseReferenceDocumentJob] Job gagal dengan exception.', [
            'schema_id' => $this->schemaId,
            'exception' => $exception->getMessage(),
            'trace'     => $exception->getTraceAsString(),
        ]);

        $schema = ReferenceDocumentSchema::find($this->schemaId);
        if ($schema) {
            $schema->update([
                'status'              => 'rejected',
                'parse_error_message' => 'Internal error: ' . $exception->getMessage(),
            ]);
        }
    }

    /**
     * Cari match tabel untuk sub-bab tertentu.
     * Logika: Sub-bab di BAB tertentu dikaitkan ke tabel yang lokasinya di BAB yang sama.
     * Jika ada lebih dari satu tabel di BAB yang sama, ambil yang pertama belum diklaim.
     */
    private function findMatchForSubBab(
        string $babCode,
        string $kode,
        array $matchedTables,
        array $detectedTables
    ): array {
        // Cari tabel yang berlokasi di BAB ini
        foreach ($detectedTables as $idx => $tabel) {
            if (($tabel['lokasi_bab'] ?? '') === $babCode) {
                return $matchedTables[$idx] ?? ['tipe_konten' => 'rich_text', 'match_status' => 'no_table', 'match_score' => 0];
            }
        }

        return ['tipe_konten' => 'rich_text', 'match_status' => 'no_table', 'match_score' => 0];
    }

    /**
     * Generate kolom_key dari nama kolom (snake_case, non-alphanumeric dihapus).
     * "Kode Rekening" → "kode_rekening"
     */
    private function generateKolomKey(string $namaKolom): string
    {
        $key = strtolower($namaKolom);
        $key = preg_replace('/[^a-z0-9\s]/', '', $key);
        $key = preg_replace('/\s+/', '_', trim($key));
        return substr($key, 0, 50);
    }
}
