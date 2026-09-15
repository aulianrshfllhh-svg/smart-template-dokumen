<?php

namespace App\Services;

use App\Models\RenjaDocument;
use App\Models\User;
use App\Enums\DocumentStatus;
use Illuminate\Support\Facades\Auth;

class VerificationReviewService
{
    /**
     * Dapatkan seluruh data untuk Halaman PR-Style Detail Review Dokumen.
     */
    public function getReviewDetailData(int $documentId): array
    {
        $document = RenjaDocument::with(['opd', 'sections', 'assignedVerificator', 'updatedByUser'])->findOrFail($documentId);

        // Jika status Submitted atau Dikirim Ulang, ubah otomatis menjadi Sedang Direview
        if (in_array($document->status, ['submitted', DocumentStatus::RESUBMITTED->value, 'menunggu_pemeriksaan', 'menunggu_verifikasi'])) {
            $document->status = DocumentStatus::UNDER_REVIEW->value;
            $document->updated_by_user_id = Auth::id();
            $document->saveQuietly();
        }

        // Kelompokkan Seksi per Bab (Bab I s/d VI)
        $groupedSections = $document->sections->groupBy('bab_code');

        // Status Seksi Review (JSON)
        $sectionStatuses = $document->section_review_status ?? [];

        // Hitung statistik BAB yang sudah vs belum direview
        $totalBabs = $groupedSections->count();
        $reviewedBabsCount = 0;
        $approvedBabsCount = 0;
        $revisionBabsCount = 0;

        foreach ($groupedSections as $babCode => $sections) {
            if (!isset($sectionStatuses[$babCode])) {
                $sectionStatuses[$babCode] = [
                    'status' => 'PENDING', // PENDING, APPROVED, NEEDS_REVISION
                    'notes' => '',
                    'updated_at' => null,
                ];
            } else {
                $st = $sectionStatuses[$babCode]['status'] ?? 'PENDING';
                if (in_array($st, ['APPROVED', 'NEEDS_REVISION'])) {
                    $reviewedBabsCount++;
                }
                if ($st === 'APPROVED') {
                    $approvedBabsCount++;
                }
                if ($st === 'NEEDS_REVISION') {
                    $revisionBabsCount++;
                }
            }
        }

        $reviewProgressPercentage = $totalBabs > 0 ? (int) round(($reviewedBabsCount / $totalBabs) * 100) : 0;

        // Daftar Seluruh Verifikator & Staff Bapperida untuk Delegasi Tugas
        $availableVerificators = User::whereIn('role', ['admin', 'verifikator', 'staff', 'bapperida'])
            ->orderBy('name', 'asc')
            ->get();

        // Audit Trail History
        $auditTrail = $document->metadata['audit_trail'] ?? [];

        return [
            'document' => $document,
            'groupedSections' => $groupedSections,
            'sectionStatuses' => $sectionStatuses,
            'availableVerificators' => $availableVerificators,
            'auditTrail' => $auditTrail,
            'totalBabs' => $totalBabs,
            'reviewedBabsCount' => $reviewedBabsCount,
            'unreviewedBabsCount' => max(0, $totalBabs - $reviewedBabsCount),
            'approvedBabsCount' => $approvedBabsCount,
            'revisionBabsCount' => $revisionBabsCount,
            'reviewProgressPercentage' => $reviewProgressPercentage,
            'isLocked' => in_array($document->status, ['disetujui', 'approved', 'dikunci', 'final']),
        ];
    }

    /**
     * Memproses PR-Style Section Review & Keputusan Final Verifikasi.
     */
    public function processReviewDecision(int $documentId, array $sectionsReviewData, ?int $assignedVerificatorId, string $decisionType, ?string $generalNotes): RenjaDocument
    {
        $document = RenjaDocument::findOrFail($documentId);
        $user = Auth::user();

        // Aturan Bisnis: Dokumen yang sudah APPROVED tidak dapat diubah lagi
        if (in_array($document->status, ['disetujui', 'approved', 'dikunci', 'final'])) {
            throw new \Exception('Dokumen yang telah disetujui (APPROVED) telah dikunci dan tidak dapat diedit lagi.');
        }

        // 1. Update PR-Style Section Review Statuses
        $existingSectionStatuses = $document->section_review_status ?? [];
        $hasAnyRevisionNeeded = false;
        $allSectionsApproved = true;

        foreach ($sectionsReviewData as $babCode => $review) {
            $status = $review['status'] ?? 'PENDING';
            $notes = trim($review['notes'] ?? '');

            if ($status === 'NEEDS_REVISION') {
                $hasAnyRevisionNeeded = true;
                $allSectionsApproved = false;
            } elseif ($status !== 'APPROVED') {
                $allSectionsApproved = false;
            }

            $existingSectionStatuses[$babCode] = [
                'status' => $status,
                'notes' => $notes,
                'updated_at' => now()->toIso8601String(),
                'reviewer_id' => $user->id,
                'reviewer_name' => $user->name ?? $user->nama_lengkap,
            ];
        }

        $document->section_review_status = $existingSectionStatuses;

        // 2. Update Verifikator Assigned
        if ($assignedVerificatorId) {
            $document->assigned_verificator_id = $assignedVerificatorId;
        }

        $document->updated_by_user_id = $user->id;

        // 3. Proses Keputusan Verifikator
        $actionLog = 'PR_SECTION_REVIEW_UPDATE';
        $logNote = $generalNotes ?? 'Pembaruan review seksi dokumen';

        if ($decisionType === 'minta_revisi') {
            $document->status = DocumentStatus::REVISION_NEEDED->value;
            $document->catatan_bapperida = $generalNotes ?? 'Dokumen dikembalikan untuk perbaikan bab/seksi.';
            $document->revision_count = ($document->revision_count ?? 0) + 1;
            $actionLog = 'REVISION_REQUESTED';
        } elseif ($decisionType === 'setujui_dokumen') {
            $document->status = DocumentStatus::APPROVED->value;
            $document->catatan_bapperida = $generalNotes ?? 'Dokumen disetujui penuh oleh Bapperida.';
            $actionLog = 'DOCUMENT_APPROVED';
        } elseif ($decisionType === 'simpan_draft') {
            // Status tetap sedang_diperiksa
            $document->status = DocumentStatus::UNDER_REVIEW->value;
            if (!empty($generalNotes)) {
                $document->catatan_bapperida = $generalNotes;
            }
            $actionLog = 'DRAFT_REVIEW_SAVED';
        }

        // 4. Catat Audit Log Detail
        $metadata = $document->metadata ?? [];
        $auditTrail = $metadata['audit_trail'] ?? [];

        $auditTrail[] = [
            'action' => $actionLog,
            'decision' => $decisionType,
            'notes' => $logNote,
            'user_id' => $user->id,
            'user_name' => $user->name ?? $user->nama_lengkap,
            'user_nip' => $user->username_nip ?? '-',
            'user_role' => $user->role,
            'timestamp' => now()->toIso8601String(),
        ];

        $metadata['audit_trail'] = $auditTrail;
        $document->metadata = $metadata;

        $document->save();

        return $document;
    }

    /**
     * Tentukan Delegasi Verifikator Khusus.
     */
    public function assignVerificator(int $documentId, int $verificatorId): RenjaDocument
    {
        $document = RenjaDocument::findOrFail($documentId);
        $verificator = User::findOrFail($verificatorId);
        $user = Auth::user();

        $document->assigned_verificator_id = $verificator->id;
        $document->updated_by_user_id = $user->id;

        // Log audit
        $metadata = $document->metadata ?? [];
        $auditTrail = $metadata['audit_trail'] ?? [];

        $auditTrail[] = [
            'action' => 'VERIFICATOR_ASSIGNED',
            'notes' => "Tugas verifikasi didelegasikan kepada {$verificator->name} ({$verificator->role})",
            'user_id' => $user->id,
            'user_name' => $user->name ?? $user->nama_lengkap,
            'user_nip' => $user->username_nip ?? '-',
            'user_role' => $user->role,
            'timestamp' => now()->toIso8601String(),
        ];

        $metadata['audit_trail'] = $auditTrail;
        $document->metadata = $metadata;

        $document->save();

        return $document;
    }
}
