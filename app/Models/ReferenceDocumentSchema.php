<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferenceDocumentSchema extends Model
{
    use HasFactory;

    protected $table = 'reference_document_schemas';

    protected $fillable = [
        'jenis_dokumen',
        'original_filename',
        'stored_path',
        'status',
        'parsed_json',
        'parsed_at',
        'parse_error_message',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'parsed_json' => 'array',
        'parsed_at'   => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * User yang mengupload dokumen acuan ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User yang menyetujui schema ini.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Daftar BAB yang terdeteksi dari dokumen ini.
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(TemplateChapter::class, 'schema_id')->orderBy('urutan');
    }

    /**
     * Cek apakah schema sudah disetujui.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Cek apakah schema masih dalam proses (draft).
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Cek apakah schema ditolak.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Label badge status untuk tampilan.
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'draft'    => ['label' => 'Menunggu Review', 'class' => 'warning'],
            'approved' => ['label' => 'Disetujui', 'class' => 'success'],
            'rejected' => ['label' => 'Ditolak', 'class' => 'danger'],
            default    => ['label' => 'Unknown', 'class' => 'secondary'],
        };
    }

    /**
     * Jumlah total Sub-Bab di seluruh BAB dalam schema ini.
     */
    public function getTotalSubChaptersCountAttribute(): int
    {
        return $this->chapters()
            ->withCount('subChapters')
            ->get()
            ->sum('sub_chapters_count');
    }
}
