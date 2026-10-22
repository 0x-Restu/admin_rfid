<?php

namespace App\Http\Controllers;

use App\Models\SecurityAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle authentication with brute-force rate limiting & audit logs
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Security: Rate limiting key based on IP & Email
        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            SecurityAuditLog::record(
                action: 'LOGIN_RATE_LIMITED',
                status: 'BLOCKED',
                severity: 'CRITICAL',
                targetType: 'AUTH',
                targetId: $request->input('email'),
                details: ['ip' => $request->ip(), 'seconds_remaining' => $seconds]
            );

            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login gagal. Silakan coba lagi dalam {$seconds} detik.",
            ])->onlyInput('email');
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Check if user account is active
            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                SecurityAuditLog::record(
                    action: 'LOGIN_INACTIVE_BLOCKED',
                    status: 'BLOCKED',
                    severity: 'WARNING',
                    targetType: 'USER',
                    targetId: (string) $user->id,
                    details: ['email' => $user->email]
                );

                return back()->withErrors([
                    'email' => 'Akun Anda dinonaktifkan oleh Administrator. Hubungi Super Admin.',
                ])->onlyInput('email');
            }

            // Clear throttle on success
            RateLimiter::clear($throttleKey);

            // Regenerate session to prevent session fixation attacks
            $request->session()->regenerate();

            // Update user last login
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            // Security audit log
            SecurityAuditLog::record(
                action: 'USER_LOGIN',
                status: 'SUCCESS',
                severity: 'INFO',
                targetType: 'USER',
                targetId: (string) $user->id,
                details: ['role' => $user->role, 'bidang' => $user->bidang_name]
            );

            return redirect()->intended(route('dashboard'))
                ->with('success', "Selamat datang kembali, {$user->name} ({$user->role_badge['name']})!");
        }

        // Increment failed attempts
        RateLimiter::hit($throttleKey, 60);

        SecurityAuditLog::record(
            action: 'LOGIN_FAILED',
            status: 'FAILED',
            severity: 'WARNING',
            targetType: 'AUTH',
            targetId: $request->input('email'),
            details: ['ip' => $request->ip(), 'attempts' => RateLimiter::attempts($throttleKey)]
        );

        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    /**
     * Demo one-click role switcher for fast evaluation
     */
    public function demoLogin(string $role, Request $request)
    {
        $allowedRoles = ['superadmin', 'keuangan', 'kantin', 'kesiswaan', 'akademik'];

        if (!in_array($role, $allowedRoles, true)) {
            abort(404);
        }

        $user = User::where('role', $role)->first();

        if ($user) {
            Auth::login($user);
            $request->session()->regenerate();

            SecurityAuditLog::record(
                action: 'DEMO_ROLE_SWITCH',
                status: 'SUCCESS',
                severity: 'INFO',
                targetType: 'USER',
                targetId: (string) $user->id,
                details: ['role' => $user->role]
            );

            return redirect()->route('dashboard')
                ->with('success', "Beralih ke akun Demo: {$user->name} ({$user->role_badge['name']})");
        }

        return redirect()->route('login')->withErrors(['email' => "Pengguna role {$role} belum dibuat."]);
    }

    /**
     * Secure logout with session destruction
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            SecurityAuditLog::record(
                action: 'USER_LOGOUT',
                status: 'SUCCESS',
                severity: 'INFO',
                targetType: 'USER',
                targetId: (string) $user->id
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari sistem.');
    }
}
