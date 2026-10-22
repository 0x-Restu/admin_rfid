<?php

namespace App\Http\Middleware;

use App\Models\SecurityAuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleHasAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda dinonaktifkan oleh Administrator.',
            ]);
        }

        // Superadmin bypass
        if ($user->role === 'superadmin') {
            return $next($request);
        }

        // Check if user has one of the required roles
        if (!in_array($user->role, $roles, true)) {
            // Log security unauthorized access attempt
            SecurityAuditLog::record(
                action: 'UNAUTHORIZED_ACCESS_ATTEMPT',
                status: 'BLOCKED',
                severity: 'CRITICAL',
                targetType: 'ROUTE',
                targetId: $request->path(),
                details: [
                    'user_email' => $user->email,
                    'user_role' => $user->role,
                    'required_roles' => $roles,
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                ]
            );

            abort(403, "Akses Ditolak (403 Forbidden): Peran '{$user->role}' tidak memiliki otorisasi untuk mengakses modul Bidang ini.");
        }

        return $next($request);
    }
}
