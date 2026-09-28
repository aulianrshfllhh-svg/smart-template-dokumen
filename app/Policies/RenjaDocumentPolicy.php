<?php

namespace App\Policies;

use App\Models\RenjaDocument;
use App\Models\User;

class RenjaDocumentPolicy
{
    public function view(User $user, RenjaDocument $document): bool
    {
        return $user->isAdmin() || $user->isVerifikator() || $user->isStaff() || $user->isPimpinan()
            || ($user->isOperator() && $user->opd_id !== null && (int) $user->opd_id === (int) $document->opd_id);
    }

    public function update(User $user, RenjaDocument $document): bool
    {
        if (!$this->view($user, $document) || $document->isLocked() || $document->isLampiranPerbub()) return false;
        return $user->isAdmin() || $user->isVerifikator() || $user->isStaff()
            || ($user->isOperator() && $document->isEditableByOpd());
    }

    public function finalize(User $user, RenjaDocument $document): bool
    {
        return ($user->isAdmin() || $user->isVerifikator() || $user->isStaff())
            && !$document->is_archived && !$document->isLampiranPerbub();
    }

    public function review(User $user, RenjaDocument $document): bool
    {
        return $user->isAdmin() || $user->isVerifikator() || $user->isStaff();
    }
}
