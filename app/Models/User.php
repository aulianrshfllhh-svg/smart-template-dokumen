<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username_nip',
        'nama_lengkap',
        'email',
        'password',
        'role',
        'opd_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(MasterOpd::class, 'opd_id');
    }

    public function getOpdAttribute()
    {
        if ($this->opd_id) {
            $opd = $this->getRelationValue('opd');
            if ($opd) {
                return $opd;
            }
        }

        if ($this->isBapperida()) {
            return MasterOpd::where(function ($q) {
                $q->where('nama_opd', 'LIKE', '%bapperida%')
                  ->orWhere('nama_opd', 'LIKE', '%Badan Perencanaan Pembangunan%');
            })->first() ?? MasterOpd::first();
        }

        return $this->getRelationValue('opd');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isVerifikator(): bool
    {
        return $this->role === 'verifikator';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator' || $this->role === 'opd';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff' || $this->role === 'staff_bapperida';
    }

    public function isBapperida(): bool
    {
        return $this->isAdmin() || $this->isVerifikator() || $this->isStaff() || in_array($this->role, ['bapperida', 'admin', 'verifikator', 'staff_bapperida']);
    }

    public function isPimpinan(): bool
    {
        return $this->role === 'pimpinan' || $this->role === 'eksekutif';
    }
}
