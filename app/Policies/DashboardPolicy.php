<?php

namespace App\Policies;

use App\Models\User;

class DashboardPolicy
{
    /**
     * Tentukan apakah pengguna berhak mengakses Admin Dashboard Control Center.
     * Hanya Admin Bapperida, Verifikator Bapperida, dan Staff Bapperida yang diizinkan.
     */
    public function viewAdminDashboard(User $user): bool
    {
        return $user->isAdmin() || $user->isVerifikator() || $user->isStaff();
    }
}
