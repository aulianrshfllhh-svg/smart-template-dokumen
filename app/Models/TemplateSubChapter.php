<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateSubChapter extends Model
{
    use HasFactory;

    protected $table = 'template_sub_chapters';

    protected $fillable = [
        'chapter_id',
        'kode',
        'judul',
        'tipe_konten',
        'sumber_data',
        'match_status',
        'match_score',
        'urutan',
    ];

    protected $casts = [
        'match_score' => 'integer',
        'urutan'      => 'integer',
    ];

    /**
     * BAB induk sub-bab ini.
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(TemplateChapter::class, 'chapter_id');
    }

    /**
     * Definisi kolom tabel (jika tipe_konten = tabel).
     */
    public function tableColumns(): HasMany
    {
        return $this->hasMany(TemplateTableColumn::class, 'sub_chapter_id')->orderBy('urutan');
    }

    /**
     * Apakah sub-bab ini adalah tabel?
     */
    public function isTabel(): bool
    {
        return $this->tipe_konten === 'tabel';
    }

    /**
     * Label badge match status untuk tampilan review.
     */
    public function getMatchBadgeAttribute(): array
    {
        return match ($this->match_status) {
            'auto_matched'  => ['label' => 'Auto-Match (' . $this->match_score . '%)', 'class' => 'success'],
            'perlu_review'  => ['label' => 'Perlu Review', 'class' => 'warning'],
            'no_table'      => ['label' => 'Narasi', 'class' => 'secondary'],
            default         => ['label' => '-', 'class' => 'light'],
        };
    }
}
