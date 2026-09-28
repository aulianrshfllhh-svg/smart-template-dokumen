<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RenjaSectionCaption extends Model
{
    protected $table = 'renja_section_captions';

    protected $fillable = [
        'section_id',
        'element_type',   // 'table' atau 'figure'
        'element_id',     // data-elem-id di HTML
        'caption',        // Teks judul caption
        'display_number', // "Tabel 3.1", "Gambar 2.1", dsb.
        'order_index',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];

    /**
     * Section yang memiliki caption ini.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(RenjaSection::class, 'section_id');
    }

    /**
     * Apakah elemen ini adalah tabel?
     */
    public function isTable(): bool
    {
        return $this->element_type === 'table';
    }

    /**
     * Apakah elemen ini adalah gambar/figure?
     */
    public function isFigure(): bool
    {
        return $this->element_type === 'figure';
    }
}
