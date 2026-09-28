<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ReferenceDocumentSchema;

/**
 * Policy: Acuan Dokumen (ReferenceDocumentSchema)
 *
 * Hanya Admin, Verifikator, dan Staff Bapperida yang diizinkan mengelola
 * atau menerapkan Acuan Dokumen. Operator OPD tidak punya akses sama sekali.
 */
class ReferenceDocumentPolicy
{
    /**
     * Pengecekan umum: hanya role internal Bapperida (admin/verifikator/staff).
     */
    private function isBapperidaStaff(User $user): bool
    {
        return $user->isAdmin() || $user->isVerifikator() || $user->isStaff();
    }

    /**
     * Lihat daftar semua acuan dokumen.
     */
    public function viewAny(User $user): bool
    {
        return $this->isBapperidaStaff($user);
    }

    /**
     * Lihat detail acuan dokumen tertentu.
     */
    public function view(User $user, ReferenceDocumentSchema $schema): bool
    {
        return $this->isBapperidaStaff($user);
    }

    /**
     * Upload / buat acuan dokumen baru.
     */
    public function create(User $user): bool
    {
        return $this->isBapperidaStaff($user);
    }

    /**
     * Update sub-chapter mapping, approve, reject.
     */
    public function update(User $user, ReferenceDocumentSchema $schema): bool
    {
        return $this->isBapperidaStaff($user);
    }

    /**
     * Hapus acuan dokumen.
     */
    public function delete(User $user, ReferenceDocumentSchema $schema): bool
    {
        return $user->isAdmin(); // hanya admin yang bisa hapus
    }

    /**
     * Terapkan schema ke Dokumen Renja (applyToDocument).
     * Operator dilarang, hanya Bapperida Internal.
     */
    public function apply(User $user, ReferenceDocumentSchema $schema): bool
    {
        return $this->isBapperidaStaff($user);
    }

    /**
     * Digunakan oleh authorizeAdminAccess() legacy via Gate::check('admin').
     * Model diisi null saat pengecekan tanpa instansi spesifik.
     */
    public function admin(User $user): bool
    {
        return $this->isBapperidaStaff($user);
    }
}
