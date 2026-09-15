<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterOpd extends Model
{
    use HasFactory;

    protected $table = 'master_opd';

    protected $fillable = [
        'kode_opd',
        'nama_opd',
        'lampiran_number',
        'nomor_lampiran_romawi',
        'jenis_lampiran_default',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'opd_id');
    }

    public function renjaDocuments(): HasMany
    {
        return $this->hasMany(RenjaDocument::class, 'opd_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RenjaDocument::class, 'opd_id');
    }
}
