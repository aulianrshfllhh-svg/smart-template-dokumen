<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RenjaTableUtama extends Model
{
    use HasFactory;

    protected $table = 'renja_table_utama';

    protected $fillable = [
        'document_id',
        'kode_rekening',
        'uraian',
        'nama_program_kegiatan',
        'indikator',
        'lokasi',
        'target_2027',
        'pagu_2027',
        'prakiraan_maju_target_2028',
        'prakiraan_maju_pagu_2028',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(RenjaDocument::class, 'document_id');
    }
}
