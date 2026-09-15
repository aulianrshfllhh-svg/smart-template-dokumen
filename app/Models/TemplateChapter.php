<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateChapter extends Model
{
    use HasFactory;

    protected $table = 'template_chapters';

    protected $fillable = [
        'schema_id',
        'nomor_bab',
        'bab_code',
        'judul',
        'urutan',
    ];

    /**
     * Schema induk dokumen acuan.
     */
    public function schema(): BelongsTo
    {
        return $this->belongsTo(ReferenceDocumentSchema::class, 'schema_id');
    }

    /**
     * Daftar Sub-Bab di dalam BAB ini.
     */
    public function subChapters(): HasMany
    {
        return $this->hasMany(TemplateSubChapter::class, 'chapter_id')->orderBy('urutan');
    }

    /**
     * Label display "BAB I — Pendahuluan".
     */
    public function getLabelAttribute(): string
    {
        return $this->bab_code . ($this->judul ? ' — ' . $this->judul : '');
    }
}
