<?php

namespace App\Services;

use App\Models\RenjaDocument;
use App\Models\DocumentTemplate;
use App\Models\MasterOpd;
use Illuminate\Support\Facades\Auth;

class DocumentRegistryService
{
    /**
     * Dapatkan seluruh Document Family yang terdaftar di sistem.
     *
     * @param int|null $activeTa
     * @return array
     */
    public function getFamilies(?int $activeTa = null): array
    {
        $activeTa = $activeTa ?? session('active_ta', (int) date('Y'));

        return [
            'RENJA' => [
                'code' => 'RENJA',
                'name' => 'Rencana Kerja Perangkat Daerah (Renja)',
                'short_name' => 'RENJA',
                'description' => 'Dokumen perencanaan tahunan resmi Perangkat Daerah Kabupaten Cirebon.',
                'icon' => 'fa-file-invoice',
                'badge_color' => 'bg-amber-500 text-slate-950',
                'is_active' => true,
                'status_label' => 'Tersedia',
                'variants' => $this->getVariants('RENJA', $activeTa),
            ],
            'RKPD' => [
                'code' => 'RKPD',
                'name' => 'Rencana Kerja Pemerintah Daerah (RKPD)',
                'short_name' => 'RKPD',
                'description' => 'Dokumen perencanaan tahunan Pemerintah Daerah Kabupaten Cirebon.',
                'icon' => 'fa-landmark',
                'badge_color' => 'bg-emerald-600 text-white',
                'is_active' => false,
                'status_label' => 'Tingkat Daerah',
                'note' => 'Format & agregasi RKPD dikompilasi dari dokumen RENJA seluruh Perangkat Daerah.',
                'variants' => $this->getVariants('RKPD', $activeTa),
            ],
        ];
    }

    /**
     * Dapatkan daftar varian untuk Document Family tertentu.
     *
     * @param string $familyCode
     * @param int|null $activeTa
     * @return array
     */
    public function getVariants(string $familyCode, ?int $activeTa = null): array
    {
        $activeTa = $activeTa ?? session('active_ta', (int) date('Y'));

        if (strtoupper($familyCode) === 'RENJA') {
            return [
                'RENJA_MURNI' => [
                    'key' => 'RENJA_MURNI',
                    'family' => 'RENJA',
                    'name' => 'RENJA Murni',
                    'full_name' => 'Rencana Kerja (RENJA) Murni',
                    'tahun_anggaran' => $activeTa + 1,
                    'year_label' => 'Tahun Dokumen: ' . ($activeTa + 1),
                    'year_description' => 'Rencana Kerja Perangkat Daerah untuk tahun berikutnya (TA berjalan + 1).',
                    'description' => 'Dokumen Rencana Kerja awal untuk tahun anggaran ' . ($activeTa + 1) . '.',
                    'template_code' => 'RENJA_MURNI',
                    'derived_lampiran_code' => 'RENJA_LAMPIRAN_MURNI',
                    'is_primary' => true,
                    'badge_class' => 'bg-amber-100 text-amber-900 border border-amber-300',
                ],
                'RENJA_PERUBAHAN' => [
                    'key' => 'RENJA_PERUBAHAN',
                    'family' => 'RENJA',
                    'name' => 'RENJA Perubahan',
                    'full_name' => 'Perubahan Rencana Kerja (RENJA Perubahan)',
                    'tahun_anggaran' => $activeTa,
                    'year_label' => 'Tahun Dokumen: ' . $activeTa,
                    'year_description' => 'Perubahan Rencana Kerja Perangkat Daerah tahun berjalan (TA ' . $activeTa . ').',
                    'description' => 'Dokumen penyesuaian target kinerja dan pagu anggaran untuk tahun anggaran ' . $activeTa . '.',
                    'template_code' => 'RENJA_PERUBAHAN',
                    'derived_lampiran_code' => 'RENJA_LAMPIRAN_PERUBAHAN',
                    'is_primary' => false,
                    'badge_class' => 'bg-purple-100 text-purple-900 border border-purple-300',
                ],
            ];
        }

        if (strtoupper($familyCode) === 'RKPD') {
            return [
                'RKPD_MURNI' => [
                    'key' => 'RKPD_MURNI',
                    'family' => 'RKPD',
                    'name' => 'RKPD Murni',
                    'full_name' => 'Rencana Kerja Pemerintah Daerah (RKPD) Murni',
                    'tahun_anggaran' => $activeTa + 1,
                    'year_label' => 'Tahun Dokumen: ' . ($activeTa + 1),
                    'year_description' => 'Rencana Kerja Pemerintah Daerah Kabupaten Cirebon untuk tahun berikutnya (TA berjalan + 1).',
                    'description' => 'Dokumen Rencana Kerja Pemerintah Daerah Kabupaten Cirebon untuk tahun anggaran ' . ($activeTa + 1) . '.',
                    'template_code' => 'RKPD_MURNI',
                    'derived_lampiran_code' => null,
                    'is_primary' => true,
                    'badge_class' => 'bg-emerald-100 text-emerald-900 border border-emerald-300',
                ],
                'RKPD_PERUBAHAN' => [
                    'key' => 'RKPD_PERUBAHAN',
                    'family' => 'RKPD',
                    'name' => 'RKPD Perubahan',
                    'full_name' => 'Perubahan Rencana Kerja Pemerintah Daerah (RKPD Perubahan)',
                    'tahun_anggaran' => $activeTa,
                    'year_label' => 'Tahun Dokumen: ' . $activeTa,
                    'year_description' => 'Perubahan Rencana Kerja Pemerintah Daerah tahun berjalan (TA ' . $activeTa . ').',
                    'description' => 'Dokumen penyesuaian arah kebijakan dan sasaran pembangunan daerah tahun anggaran ' . $activeTa . '.',
                    'template_code' => 'RKPD_PERUBAHAN',
                    'derived_lampiran_code' => null,
                    'is_primary' => false,
                    'badge_class' => 'bg-indigo-100 text-indigo-900 border border-indigo-300',
                ],
            ];
        }

        return [];
    }

    /**
     * Dapatkan detail varian berdasarkan kuncinya.
     *
     * @param string $variantKey
     * @param int|null $activeTa
     * @return array|null
     */
    public function getVariant(string $variantKey, ?int $activeTa = null): ?array
    {
        $activeTa = $activeTa ?? session('active_ta', (int) date('Y'));
        $variants = $this->getVariants('RENJA', $activeTa);
        $normalizedKey = strtoupper(trim(str_replace('-', '_', $variantKey)));

        if ($normalizedKey === 'RENJA' || $normalizedKey === 'MURNI') {
            $normalizedKey = 'RENJA_MURNI';
        } elseif ($normalizedKey === 'PERUBAHAN') {
            $normalizedKey = 'RENJA_PERUBAHAN';
        }

        return $variants[$normalizedKey] ?? null;
    }

    /**
     * Cek apakah dokumen untuk varian dan tahun tertentu sudah ada pada OPD (Pencegahan Dokumen Duplikat).
     *
     * @param int $opdId
     * @param string $variantKey
     * @param int|null $activeTa
     * @return RenjaDocument|null
     */
    public function checkExistingDocument(int $opdId, string $variantKey, ?int $activeTa = null): ?RenjaDocument
    {
        $variant = $this->getVariant($variantKey, $activeTa);
        if (!$variant) {
            return null;
        }

        $targetTa = $variant['tahun_anggaran'];
        $isPerubahan = ($variant['key'] === 'RENJA_PERUBAHAN');

        $query = RenjaDocument::where('opd_id', $opdId)
            ->where('tahun_anggaran', $targetTa)
            ->where('jenis_dokumen', 'NOT LIKE', '%Lampiran%')
            ->where('jenis_dokumen', 'NOT LIKE', '%Perbup%')
            ->where('jenis_dokumen', 'NOT LIKE', '%Kepbup%');

        if ($isPerubahan) {
            $query->where('jenis_dokumen', 'LIKE', '%Perubahan%');
        } else {
            $query->where(function ($q) {
                $q->where('jenis_dokumen', 'LIKE', '%Murni%')
                  ->orWhere(function ($q2) {
                      $q2->where('jenis_dokumen', 'NOT LIKE', '%Perubahan%');
                  });
            });
        }

        return $query->latest()->first();
    }

    /**
     * Cek status dokumen yang sudah ada untuk seluruh varian RENJA pada OPD.
     *
     * @param int $opdId
     * @param int|null $activeTa
     * @return array
     */
    public function getExistingDocumentsSummary(int $opdId, ?int $activeTa = null): array
    {
        $activeTa = $activeTa ?? session('active_ta', (int) date('Y'));
        $variants = $this->getVariants('RENJA', $activeTa);
        $summary = [];

        foreach ($variants as $key => $var) {
            $doc = $this->checkExistingDocument($opdId, $key, $activeTa);
            $summary[$key] = [
                'exists' => $doc !== null,
                'document' => $doc,
                'variant' => $var,
            ];
        }

        return $summary;
    }

    /**
     * Resolve template model untuk varian tertentu.
     *
     * @param string $variantKey
     * @return DocumentTemplate|null
     */
    public function resolveTemplate(string $variantKey): ?DocumentTemplate
    {
        $variant = $this->getVariant($variantKey);
        $templateCode = $variant['template_code'] ?? 'RENJA_MURNI';

        return DocumentTemplate::where('code', $templateCode)->where('is_active', true)->first()
            ?? DocumentTemplate::where('code', 'RENJA_MURNI')->first()
            ?? DocumentTemplate::where('code', 'RENJA')->first();
    }

    /**
     * Cari Master Template berdasarkan variant key.
     *
     * @param string $variantKey
     * @return DocumentTemplate|null
     */
    public function findMasterTemplate(string $variantKey): ?DocumentTemplate
    {
        return $this->resolveTemplate($variantKey);
    }
}
