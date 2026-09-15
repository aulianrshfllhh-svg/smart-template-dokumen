<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterNomenklatur extends Model
{
    use HasFactory;

    protected $table = 'master_nomenklatur';

    protected $fillable = [
        'kode_rekening',
        'nama_nomenklatur',
        'level',
        'tahun_anggaran',
    ];
}
