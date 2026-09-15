<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'sedang_diperiksa';
    case REVISION_NEEDED = 'perlu_revisi';
    case RESUBMITTED = 'dikirim_ulang';
    case APPROVED = 'disetujui';
    case FINAL = 'final';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf OPD',
            self::SUBMITTED => 'Menunggu Verifikasi',
            self::UNDER_REVIEW => 'Sedang Direview',
            self::REVISION_NEEDED => 'Perlu Perbaikan OPD',
            self::RESUBMITTED => 'Dikirim Ulang Perbaikan',
            self::APPROVED => 'Disetujui Sah',
            self::FINAL => 'Dikunci & Final',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-amber-50 text-amber-800 border-amber-200',
            self::SUBMITTED => 'bg-blue-50 text-blue-800 border-blue-200',
            self::UNDER_REVIEW => 'bg-purple-50 text-purple-800 border-purple-200',
            self::REVISION_NEEDED => 'bg-amber-100 text-amber-900 border-amber-300 font-black',
            self::RESUBMITTED => 'bg-cyan-50 text-cyan-900 border-cyan-300 font-bold',
            self::APPROVED => 'bg-emerald-50 text-emerald-800 border-emerald-200 font-black',
            self::FINAL => 'bg-indigo-50 text-indigo-800 border-indigo-200 font-black',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'fa-pen-to-square',
            self::SUBMITTED => 'fa-clock-rotate-left',
            self::UNDER_REVIEW => 'fa-magnifying-glass-chart',
            self::REVISION_NEEDED => 'fa-triangle-exclamation',
            self::RESUBMITTED => 'fa-rotate-right',
            self::APPROVED => 'fa-circle-check',
            self::FINAL => 'fa-lock',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::APPROVED, self::FINAL]);
    }

    public function canBeReviewed(): bool
    {
        return in_array($this, [self::SUBMITTED, self::UNDER_REVIEW, self::REVISION_NEEDED, self::RESUBMITTED]);
    }
}
