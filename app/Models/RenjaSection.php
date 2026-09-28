<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RenjaSection extends Model
{
    use HasFactory;

    protected $table = 'renja_sections';

    protected $fillable = [
        'document_id',
        'template_section_id',
        'section_type',
        'bab_code',
        'bab_title',
        'sub_bab_code',
        'sub_bab_title',
        'content',
        'guidance_text',
        'content_type',
        'source_schema_id',
        'is_completed',
        'order_index',
        'metadata',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'metadata' => 'array',
    ];

    public function templateSection(): BelongsTo
    {
        return $this->belongsTo(TemplateSection::class, 'template_section_id');
    }

    /**
     * Caption untuk tabel dan gambar pada section ini.
     */
    public function captions(): HasMany
    {
        return $this->hasMany(RenjaSectionCaption::class, 'section_id')->orderBy('order_index');
    }

    /**
     * Caption khusus tabel.
     */
    public function tableCaptions(): HasMany
    {
        return $this->hasMany(RenjaSectionCaption::class, 'section_id')
            ->where('element_type', 'table')
            ->orderBy('order_index');
    }

    /**
     * Caption khusus gambar/figure.
     */
    public function figureCaptions(): HasMany
    {
        return $this->hasMany(RenjaSectionCaption::class, 'section_id')
            ->where('element_type', 'figure')
            ->orderBy('order_index');
    }

    public function getTitleAttribute()
    {
        return $this->sub_bab_title ?? $this->bab_title;
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(RenjaDocument::class, 'document_id');
    }

    /**
     * Tambah riwayat revisi dan pangkas hingga maksimal 5 entri (FIFO Revision Cap 5).
     */
    public function addRevisionSnapshot(string $content, ?string $note = null, ?string $userName = null): array
    {
        $metadata = $this->metadata ?? [];
        $revisions = $metadata['revisions'] ?? [];

        $newSnapshot = [
            'id' => uniqid('rev_'),
            'timestamp' => now()->toIso8601String(),
            'content_length' => strlen(trim(strip_tags($content))),
            'note' => $note ?? 'Auto-save snapshot',
            'user_name' => $userName ?? 'Operator OPD',
        ];

        // Tambahkan di urutan teratas (terbaru)
        array_unshift($revisions, $newSnapshot);

        // Pangkas entri revisi jika lebih dari 5 entri (FIFO Cap 5)
        if (count($revisions) > 5) {
            $revisions = array_slice($revisions, 0, 5);
        }

        $metadata['revisions'] = $revisions;
        $this->metadata = $metadata;
        $this->save();

        return $revisions;
    }
}
