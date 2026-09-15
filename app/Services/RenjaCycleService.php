<?php

namespace App\Services;

use App\Models\RenjaDocument;
use Illuminate\Database\Eloquent\Builder;

class RenjaCycleService
{
    /**
     * Dapatkan daftar status yang mengindikasikan dokumen sudah pernah disubmit/masuk proses verifikasi.
     * Dokumen dengan status ini dihitung sebagai partisipasi OPD dan status aktif/dikirim/revisi/disetujui.
     *
     * @return array
     */
    public static function getParticipatedStatusList(): array
    {
        return [
            'submitted',
            'menunggu_pemeriksaan',
            'menunggu_verifikasi',
            'sedang_diperiksa',
            'under_review',
            'sedang_direview',
            'perlu_revisi',
            'revisi',
            'revision',
            'dikirim_ulang',
            'disetujui',
            'approved',
            'dikunci',
            'final',
        ];
    }

    /**
     * Cek apakah sebuah status mengindikasikan dokumen telah disubmit/berpartisipasi.
     *
     * @param string|null $status
     * @return bool
     */
    public static function isSubmittedOrParticipatedStatus(?string $status): bool
    {
        if (!$status) {
            return false;
        }

        return in_array(strtolower($status), self::getParticipatedStatusList(), true);
    }

    /**
     * Terapkan filter SQL ke Query Builder agar hanya mengambil dokumen yang termasuk dalam SIKLUS AKTIF $activeYear.
     *
     * Aturan Siklus Aktif (e.g. TA 2026):
     * - RENJA Murni & Lampiran RENJA Murni: tahun_anggaran = $activeYear + 1 (e.g. 2027)
     * - RENJA Perubahan & Lampiran RENJA Perubahan: tahun_anggaran = $activeYear (e.g. 2026)
     *
     * @param Builder|\Illuminate\Database\Query\Builder $query
     * @param int $activeYear
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    public static function applyActiveCycleFilter($query, int $activeYear)
    {
        $murniYear = $activeYear + 1;
        $perubahanYear = $activeYear;

        return $query->where(function ($q) use ($murniYear, $perubahanYear) {
            // 1. Dokumen Murni (Murni & Lampiran Murni) untuk tahun_anggaran = activeYear + 1
            $q->where(function ($q1) use ($murniYear) {
                $q1->where('tahun_anggaran', $murniYear)
                    ->where(function ($sub) {
                        $sub->whereRaw("LOWER(jenis_dokumen) NOT LIKE '%perubahan%'")
                            ->whereRaw("LOWER(jenis_dokumen) NOT LIKE '%kepbup%'");
                    });
            })
            // 2. Dokumen Perubahan (Perubahan & Lampiran Perubahan) untuk tahun_anggaran = activeYear
            ->orWhere(function ($q2) use ($perubahanYear) {
                $q2->where('tahun_anggaran', $perubahanYear)
                    ->where(function ($sub) {
                        $sub->whereRaw("LOWER(jenis_dokumen) LIKE '%perubahan%'")
                            ->orWhereRaw("LOWER(jenis_dokumen) LIKE '%kepbup%'");
                    });
            });
        });
    }

    /**
     * Filter query untuk status yang sudah disubmit / berpartisipasi.
     *
     * @param Builder|\Illuminate\Database\Query\Builder $query
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    public static function applyParticipatedStatusFilter($query)
    {
        return $query->whereIn('status', self::getParticipatedStatusList());
    }

    /**
     * Pengecekan apakah sebuah model RenjaDocument termasuk dalam SIKLUS AKTIF $activeYear.
     *
     * @param RenjaDocument $doc
     * @param int $activeYear
     * @return bool
     */
    public static function isDocumentInCycle(RenjaDocument $doc, int $activeYear): bool
    {
        $jenis = strtolower($doc->jenis_dokumen ?? '');
        $ta = (int) $doc->tahun_anggaran;

        if ($ta === $activeYear + 1) {
            return !str_contains($jenis, 'perubahan') && !str_contains($jenis, 'kepbup');
        }

        if ($ta === $activeYear) {
            return str_contains($jenis, 'perubahan') || str_contains($jenis, 'kepbup');
        }

        return false;
    }

    /**
     * Dapatkan daftar ID OPD yang telah BERPARTISIPASI pada siklus aktif tertentu.
     * Partisipasi = Memiliki minimal 1 dokumen pada SIKLUS AKTIF yang sudah disubmit/berstatus diproses/revisi/disetujui.
     *
     * @param int $activeYear
     * @return array
     */
    public static function getParticipatedOpdIds(int $activeYear): array
    {
        $query = RenjaDocument::query();
        self::applyActiveCycleFilter($query, $activeYear);
        self::applyParticipatedStatusFilter($query);

        return $query->distinct('opd_id')->pluck('opd_id')->toArray();
    }
}
