<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RenjaDocument extends Model
{
    use HasFactory;

    protected $table = 'renja_documents';

    protected $fillable = [
        'opd_id',
        'template_id',
        'original_document_id',
        'tahun_anggaran',
        'jenis_dokumen',
        'status',
        'catatan_bapperida',
        'cover_data',
        'metadata',
        'latar_belakang',
        'landasan_hukum',
        'maksud_tujuan',
        'sistematika',
        'evaluasi_narasi',
        'isu_strategis_narasi',
        'tujuan_sasaran_narasi',
        'program_kegiatan_narasi',
        'penutup_narasi',
        'assigned_verificator_id',
        'updated_by_user_id',
        'priority_score',
        'section_review_status',
        'submitted_at',
        'revision_count',
        'source_type',
        'year',
        'is_archived',
        'archived_at',
        'archived_by_user_id',
        'archive_notes',
    ];

    protected $casts = [
        'cover_data' => 'array',
        'metadata' => 'array',
        'section_review_status' => 'array',
        'submitted_at' => 'datetime',
        'archived_at' => 'datetime',
        'is_archived' => 'boolean',
        'year' => 'integer',
    ];

    public function archivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by_user_id');
    }

    public function assignedVerificator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_verificator_id');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(MasterOpd::class, 'opd_id');
    }

    public function originalDocument(): BelongsTo
    {
        return $this->belongsTo(RenjaDocument::class, 'original_document_id');
    }

    public function fixedDocuments(): HasMany
    {
        return $this->hasMany(RenjaDocument::class, 'original_document_id');
    }

    public function tableEvals(): HasMany
    {
        return $this->hasMany(RenjaTableEval::class, 'document_id');
    }

    public function tableUtamas(): HasMany
    {
        return $this->hasMany(RenjaTableUtama::class, 'document_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(RenjaSection::class, 'document_id')->orderBy('order_index', 'asc');
    }

    public function getProgressPercentageAttribute(): int
    {
        // Prefer withCount attributes to avoid N+1 queries
        $total = $this->attributes['sections_count'] ?? $this->sections()->count();
        if ($total === 0) {
            return 0;
        }
        $completed = $this->attributes['completed_sections_count'] ?? $this->sections()->where('is_completed', true)->count();
        return (int) round(($completed / $total) * 100);
    }

    public function getCompletedSectionsCountAttribute(): int
    {
        return $this->attributes['completed_sections_count'] ?? $this->sections()->where('is_completed', true)->count();
    }

    public function getTotalSectionsCountAttribute(): int
    {
        return $this->attributes['sections_count'] ?? $this->sections()->count();
    }

    public function getHasBeenValidatedAttribute(): bool
    {
        return (bool) ($this->metadata['has_been_validated'] ?? false);
    }

    /**
     * Apakah dokumen ini adalah upload Word asli (bukan generate dari template/editor).
     */
    public function getIsUploadWordAttribute(): bool
    {
        return $this->source_type === 'upload_word';
    }

    /**
     * Path storage dari file Word original yang diupload.
     */
    public function getOriginalFilePathAttribute(): ?string
    {
        return $this->metadata['original_file_path'] ?? null;
    }

    /**
     * Nama file Word original yang diupload oleh operator.
     */
    public function getOriginalFilenameAttribute(): ?string
    {
        return $this->metadata['original_filename']
            ?? $this->metadata['original_file_name']
            ?? $this->cover_data['source_original_filename']
            ?? null;
    }

    /**
     * SHA-256 hash dari file Word original saat upload.
     */
    public function getOriginalFileHashAttribute(): ?string
    {
        return $this->metadata['original_file_hash'] ?? null;
    }

    /**
     * Ukuran file Word original dalam bytes.
     */
    public function getOriginalFileSizeAttribute(): ?int
    {
        $size = $this->metadata['original_file_size'] ?? null;
        return $size !== null ? (int) $size : null;
    }

    /**
     * Sumber preview dokumen — 'original_docx' untuk upload_word, 'sections_db' untuk template.
     */
    public function getPreviewSourceAttribute(): string
    {
        return $this->metadata['preview_source'] ?? ($this->is_upload_word ? 'original_docx' : 'sections_db');
    }

    /**
     * Cek apakah jenis dokumen adalah RENJA Murni atau Perubahan (bukan Lampiran Perbub).
     */
    public function isRenjaMurniOrPerubahan(): bool
    {
        $jenis = strtolower($this->jenis_dokumen ?? '');
        return str_contains($jenis, 'murni') || str_contains($jenis, 'perubahan');
    }

    /**
     * Cek apakah jenis dokumen adalah Lampiran Perbub.
     */
    public function isLampiranPerbub(): bool
    {
        $jenis = strtolower($this->jenis_dokumen ?? '');
        return str_contains($jenis, 'lampiran') || str_contains($jenis, 'perbub') || in_array($this->template?->code ?? '', ['RENJA_LAMPIRAN_MURNI', 'RENJA_LAMPIRAN_PERUBAHAN']);
    }

    /**
     * Dapatkan dokumen RENJA Induk terkait jika dokumen ini adalah Lampiran.
     */
    public function getParentDocument(): ?self
    {
        if (!$this->isLampiranPerbub()) {
            return null;
        }

        // 1. Cek relasi original_document_id
        if (!empty($this->original_document_id)) {
            $parent = self::find($this->original_document_id);
            if ($parent) {
                return $parent;
            }
        }

        // 2. Cek metadata generated_from_parent_id
        $parentId = $this->metadata['generated_from_parent_id'] ?? null;
        if (!empty($parentId)) {
            $parent = self::find($parentId);
            if ($parent) {
                return $parent;
            }
        }

        // 3. Fallback: Cari RENJA Induk berdasarkan jenis (Perubahan / Murni), OPD, dan TA
        $isPerubahan = str_contains(strtolower($this->jenis_dokumen ?? ''), 'perubahan') || ($this->template?->code === 'RENJA_LAMPIRAN_PERUBAHAN');

        $query = self::where('opd_id', $this->opd_id)
            ->where('tahun_anggaran', $this->tahun_anggaran)
            ->where('id', '!=', $this->id)
            ->where('jenis_dokumen', 'NOT LIKE', '%Lampiran%');

        if ($isPerubahan) {
            $query->where('jenis_dokumen', 'LIKE', '%Perubahan%')
                  ->where('jenis_dokumen', 'NOT LIKE', '%Kepbup%');
        } else {
            $query->where(function ($q) {
                $q->where('jenis_dokumen', 'LIKE', '%Murni%')
                  ->orWhere(function ($q2) {
                      $q2->where('jenis_dokumen', 'NOT LIKE', '%Perubahan%')
                         ->where('jenis_dokumen', 'NOT LIKE', '%Lampiran%')
                         ->where('jenis_dokumen', 'NOT LIKE', '%Perbup%');
                  });
            })
            ->where('jenis_dokumen', 'NOT LIKE', '%Perbup%');
        }

        return $query->latest()->first();
    }

    /**
     * Dapatkan sections secara live dari RENJA Induk jika dokumen ini adalah Lampiran.
     * Mengeliminasi Front Matter (Cover, Pengesahan, Preface, TOC, LOT, LOF) dan
     * menyelesaikan konten narasi secara live dari renja_sections maupun kolom dokumen induk.
     */
    public function getEffectiveSections(): \Illuminate\Support\Collection
    {
        $parent = $this->isLampiranPerbub() ? $this->getParentDocument() : $this;
        if (!$parent) {
            return collect();
        }

        $rawSections = $parent->sections;
        if ($rawSections->isEmpty()) {
            return collect();
        }

        // 1. Filter Front Matter (Cover, Pengesahan, Preface, TOC, LOT, LOF, LOA)
        $filtered = $rawSections->reject(function ($sec) {
            $secType = strtolower($sec->section_type ?? '');
            $babCode = strtoupper($sec->bab_code ?? '');
            $subBabCode = strtoupper($sec->sub_bab_code ?? '');
            $babTitle = strtolower($sec->bab_title ?? '');
            $subBabTitle = strtolower($sec->sub_bab_title ?? '');

            return in_array($secType, ['cover', 'preface', 'table_of_contents', 'list_of_tables', 'list_of_figures', 'list_of_charts', 'list_of_appendices'])
                || in_array($babCode, ['COVER', 'PENGESAHAN', 'PREFACE', 'TOC', 'LOT', 'LOF', 'LOC', 'LOA'])
                || in_array($subBabCode, ['COVER', 'PENGESAHAN', 'PREFACE', 'TOC', 'LOT', 'LOF', 'LOC', 'LOA'])
                || str_contains($babTitle, 'kata pengantar') || str_contains($babTitle, 'daftar isi') || str_contains($babTitle, 'lembar pengesahan') || str_contains($babTitle, 'cover') || str_contains($babTitle, 'daftar tabel') || str_contains($babTitle, 'daftar gambar') || str_contains($babTitle, 'daftar lampiran')
                || str_contains($subBabTitle, 'kata pengantar') || str_contains($subBabTitle, 'daftar isi') || str_contains($subBabTitle, 'lembar pengesahan') || str_contains($subBabTitle, 'cover') || str_contains($subBabTitle, 'daftar tabel') || str_contains($subBabTitle, 'daftar gambar') || str_contains($subBabTitle, 'daftar lampiran');
        })->values();

        // Clone objek section di memori agar mutasi tidak mengubah instance Eloquent permanen
        $effective = $filtered->map(function ($sec) {
            return clone $sec;
        });

        // 2. Resolusi konten dari kolom dokumen induk (latar_belakang, landasan_hukum, penutup_narasi)
        foreach ($effective as $sec) {
            $code = strtoupper(trim($sec->sub_bab_code ?? $sec->code ?? ''));
            $title = strtolower(trim($sec->sub_bab_title ?? $sec->title ?? ''));
            $hasContent = !empty(trim(strip_tags($sec->content ?? '')));

            if (!$hasContent) {
                if ($code === '1.1' || str_contains($title, 'latar belakang')) {
                    if (!empty($parent->latar_belakang)) {
                        $sec->content = $parent->latar_belakang;
                    }
                } elseif ($code === '1.2' || str_contains($title, 'landasan hukum')) {
                    if (!empty($parent->landasan_hukum)) {
                        $sec->content = $parent->landasan_hukum;
                    }
                } elseif ($code === '5.1' || $code === '5.2' || str_contains($title, 'kaidah') || str_contains($title, 'kesimpulan') || str_contains($title, 'penutup')) {
                    if (!empty($parent->penutup_narasi)) {
                        $sec->content = $parent->penutup_narasi;
                    }
                }
            }
        }

        // 3. Alokasikan konten level BAB (chapter) ke sub-bab pertama jika sub-bab tersebut kosong
        $grouped = $effective->groupBy('bab_code');
        foreach ($grouped as $babCode => $bSecs) {
            $chapterSec = $bSecs->first(fn($s) => $s->section_type === 'chapter' || strtoupper(trim($s->sub_bab_code ?? '')) === strtoupper(trim($s->bab_code ?? '')));
            $subSecs = $bSecs->filter(fn($s) => $s !== $chapterSec && $s->section_type === 'subchapter');

            if ($chapterSec && !empty(trim(strip_tags($chapterSec->content ?? '')))) {
                $chapContent = $chapterSec->content;
                $firstSub = $subSecs->first();
                if ($firstSub && empty(trim(strip_tags($firstSub->content ?? '')))) {
                    $firstSub->content = $chapContent;
                    $chapterSec->content = '';
                }
            }
        }

        return $effective;
    }

    /**
     * Dapatkan tabel evaluasi secara live dari RENJA Induk jika dokumen ini adalah Lampiran.
     */
    public function getEffectiveTableEvals(): \Illuminate\Support\Collection
    {
        if ($this->isLampiranPerbub()) {
            $parent = $this->getParentDocument();
            return $parent ? $parent->tableEvals : collect();
        }
        return $this->tableEvals;
    }

    /**
     * Dapatkan tabel utama secara live dari RENJA Induk jika dokumen ini adalah Lampiran.
     */
    public function getEffectiveTableUtamas(): \Illuminate\Support\Collection
    {
        if ($this->isLampiranPerbub()) {
            $parent = $this->getParentDocument();
            return $parent ? $parent->tableUtamas : collect();
        }
        return $this->tableUtamas;
    }

    /**
     * Dapatkan status dokumen secara live dari RENJA Induk jika dokumen ini adalah Lampiran.
     */
    public function getEffectiveStatus(): string
    {
        if ($this->isLampiranPerbub()) {
            $parent = $this->getParentDocument();
            if ($parent) {
                return $parent->status;
            }
        }
        return $this->status;
    }


    public function getValidationStatusTextAttribute(): string
    {
        if (!$this->has_been_validated) {
            return 'Belum Diperiksa';
        }

        if ($this->status === 'autofix_confirmed' || in_array(strtolower($this->status), ['disetujui', 'approved', 'dikunci', 'final'])) {
            return '✓ Format Sesuai';
        }

        if ($this->status === 'autofix_completed') {
            return '✓ Masalah Diperbaiki';
        }

        // Run validation on the fly
        $valResult = app(\App\Services\RenjaMurniDocxService::class)->validateDocument($this);
        $totalIssues = $valResult['summary']['total_issues'] ?? 0;

        if ($totalIssues > 0) {
            return '⚠ ' . $totalIssues . ' Masalah Ditemukan';
        }

        return '✓ Format Sesuai';
    }

    public function getAutofixStatusTextAttribute(): string
    {
        if (!$this->has_been_validated) {
            return 'Belum Dijalankan';
        }

        if (in_array(strtolower($this->status), ['disetujui', 'approved', 'dikunci', 'final'])) {
            return '✓ Tidak Diperlukan';
        }

        if ($this->status === 'autofix_confirmed') {
            return '✓ Dikonfirmasi';
        }

        if ($this->status === 'autofix_completed') {
            return '✓ Selesai';
        }

        $valResult = app(\App\Services\RenjaMurniDocxService::class)->validateDocument($this);
        $totalIssues = $valResult['summary']['total_issues'] ?? 0;

        if ($totalIssues === 0) {
            return '✓ Tidak Diperlukan';
        }

        return '✨ Tersedia';
    }

    /**
     * Dapatkan label tampilan resmi status dokumen sesuai Business Rules & Business Process.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'belum_dikerjakan' => 'Belum Dikerjakan',
            'draft' => 'Draf',
            'autofix_completed' => 'AutoFix Selesai (Belum Ditinjau)',
            'autofix_confirmed' => 'AutoFix Dikonfirmasi (Siap Submit)',
            'menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted' => 'Menunggu Pemeriksaan',
            'sedang_diperiksa' => 'Sedang Diperiksa',
            'perlu_revisi', 'revisi', 'revision' => 'Perlu Revisi',
            'dikirim_ulang' => 'Dikirim Ulang',
            'disetujui', 'approved' => 'Disetujui',
            'dikunci', 'final' => 'Dikunci',
            default => ucfirst(str_replace('_', ' ', $this->status ?? 'Draft')),
        };
    }

    /**
     * Dapatkan CSS Class Badge Status resmi untuk UI Blade.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'disetujui', 'approved', 'autofix_confirmed' => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
            'dikunci', 'final' => 'bg-purple-100 text-purple-800 border border-purple-300',
            'menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang' => 'bg-blue-100 text-blue-800 border border-blue-300',
            'sedang_diperiksa', 'autofix_completed' => 'bg-indigo-100 text-indigo-800 border border-indigo-300',
            'perlu_revisi', 'revisi', 'revision' => 'bg-amber-100 text-amber-800 border border-amber-300',
            'belum_dikerjakan' => 'bg-slate-100 text-slate-600 border border-slate-300',
            default => 'bg-slate-100 text-slate-700 border border-slate-300',
        };
    }

    /**
     * Pengecekan apakah dokumen dapat diedit oleh User OPD (BR-18, BR-38).
     */
    public function isEditableByOpd(): bool
    {
        return in_array($this->status, ['draft', 'perlu_revisi', 'revisi', 'revision', 'belum_dikerjakan', 'autofix_completed', 'autofix_confirmed']);
    }

    /**
     * Pengecekan apakah dokumen sudah dikunci oleh sistem (BR-43, BR-44).
     */
    public function isLocked(): bool
    {
        return in_array($this->status, ['dikunci', 'final', 'disetujui', 'approved']) || $this->is_archived;
    }

    /**
     * Pengecekan status lifecycle arsip dokumen.
     * Dokumen masuk ke Arsip jika:
     * 1. Telah berstatus FIX / Disetujui Bapperida ('disetujui', 'approved', 'dikunci', 'final')
     * 2. Atau secara eksplisit diarsipkan (is_archived = true)
     * 3. Atau merupakan dokumen dari tahun anggaran terdahulu (tahun_anggaran < activeTa)
     */
    public function isArchived(int $activeTa = 2027): bool
    {
        return (bool) $this->is_archived 
            || ($this->tahun_anggaran < $activeTa)
            || in_array(strtolower($this->status), ['disetujui', 'approved', 'dikunci', 'final']);
    }

    /**
     * Scope query untuk dokumen arsip (historis, approved fix, atau archived).
     */
    public function scopeArchived($query, int $activeTa = 2027)
    {
        return $query->where(function ($q) use ($activeTa) {
            $q->where('is_archived', true)
              ->orWhere('tahun_anggaran', '<', $activeTa)
              ->orWhereIn('status', ['disetujui', 'approved', 'dikunci', 'final']);
        });
    }

    /**
     * Scope query untuk dokumen aktif (TA berjalan & belum diarsipkan).
     */
    public function scopeActive($query, int $activeTa = 2027)
    {
        return $query->where(function ($q) use ($activeTa) {
            $q->where(function ($sq) {
                $sq->where('is_archived', false)
                   ->orWhereNull('is_archived');
            })->where('tahun_anggaran', '>=', $activeTa);
        });
    }
}
