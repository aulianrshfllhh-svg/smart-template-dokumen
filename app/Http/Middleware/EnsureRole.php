<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request with role-based access control & graceful redirects.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $userRole = strtolower($user->role);

        // Admin (Superadmin) memiliki akses penuh ke seluruh fitur & dashboard
        if ($userRole === 'admin') {
            return $next($request);
        }

        // Cek wewenang role
        if (!in_array($userRole, $roles)) {
            // Redirect otomatis ke Dashboard pengguna sesuai role daripada memunculkan 403 error
            $dashboardRoute = match ($userRole) {
                'admin' => 'admin.dashboard',
                'verifikator' => 'verifikator.dashboard',
                default => 'operator.dashboard',
            };

            return redirect()->route($dashboardRoute)
                ->with('error', 'Akses dialihkan secara otomatis ke Dashboard sesuai hak akses Anda (' . strtoupper($userRole) . ').');
        }

        return $next($request);
    }
}
