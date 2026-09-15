<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateTableColumn extends Model
{
    use HasFactory;

    protected $table = 'template_table_columns';

    protected $fillable = [
        'sub_chapter_id',
        'urutan',
        'nama_kolom',
        'kolom_key',
    ];

    protected $casts = [
        'urutan' => 'integer',
    ];

    /**
     * Sub-Bab induk dari kolom tabel ini.
     */
    public function subChapter(): BelongsTo
    {
        return $this->belongsTo(TemplateSubChapter::class, 'sub_chapter_id');
    }
}
