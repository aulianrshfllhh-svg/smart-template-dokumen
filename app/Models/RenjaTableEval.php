<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RenjaTableEval extends Model
{
    use HasFactory;

    protected $table = 'renja_table_eval';

    protected $fillable = [
        'document_id',
        'jenis_tabel',
        'kode_rekening',
        'nama_program_kegiatan',
        'indikator_kinerja',
        'target_capaian',
        'pagu_indikatif',
        'realisasi_capaian',
        'realisasi_pagu',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(RenjaDocument::class, 'document_id');
    }
}
