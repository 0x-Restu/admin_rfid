<?php

namespace App\Http\Controllers;

use App\Models\SecurityAuditLog;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * List all system users with roles and bidang
     */
    public function index(Request $request)
    {
        $query = User::latest();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();

        $roleCounts = [
            'total' => User::count(),
            'superadmin' => User::where('role', 'superadmin')->count(),
            'keuangan' => User::where('role', 'keuangan')->count(),
            'kantin' => User::where('role', 'kantin')->count(),
            'kesiswaan' => User::where('role', 'kesiswaan')->count(),
            'akademik' => User::where('role', 'akademik')->count(),
        ];

        return view('admin.users.index', compact('users', 'roleCounts'));
    }

    /**
     * Store new system user with assigned role & bidang
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:superadmin,keuangan,kantin,kesiswaan,akademik'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $bidangMap = [
            'superadmin' => 'Administrator Utama (Semua Bidang)',
            'keuangan' => 'Bidang Keuangan & SPP',
            'kantin' => 'Bidang Pengelola Kantin & POS',
            'kesiswaan' => 'Bidang Kesiswaan & Presensi Gerbang',
            'akademik' => 'Bidang Akademik & Kurikulum',
        ];

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'bidang_name' => $bidangMap[$validated['role']] ?? 'Bidang Khusus',
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        SecurityAuditLog::record(
            action: 'ADMIN_USER_CREATED',
            status: 'SUCCESS',
            severity: 'NOTICE',
            targetType: 'USER',
            targetId: (string) $user->id,
            details: [
                'created_user_email' => $user->email,
                'role' => $user->role,
                'bidang' => $user->bidang_name,
            ]
        );

        return redirect()->route('admin.users.index')
            ->with('success', "Akun petugas {$user->name} ({$user->role_badge['name']}) berhasil dibuat!");
    }

    /**
     * Toggle active status of a user
     */
    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['general' => 'Anda tidak dapat menonaktifkan akun Anda sendiri!']);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        SecurityAuditLog::record(
            action: 'ADMIN_USER_STATUS_TOGGLED',
            status: 'SUCCESS',
            severity: 'WARNING',
            targetType: 'USER',
            targetId: (string) $user->id,
            details: [
                'target_email' => $user->email,
                'new_status' => $user->is_active ? 'ACTIVE' : 'INACTIVE',
            ]
        );

        return redirect()->route('admin.users.index')
            ->with('success', "Status akun {$user->name} berhasil diubah menjadi: " . ($user->is_active ? 'Aktif' : 'Nonaktif'));
    }

    /**
     * Reset password of a user
     */
    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::findOrFail($id);
        $user->password = Hash::make($request->new_password);
        $user->save();

        SecurityAuditLog::record(
            action: 'ADMIN_PASSWORD_RESET',
            status: 'SUCCESS',
            severity: 'WARNING',
            targetType: 'USER',
            targetId: (string) $user->id,
            details: ['target_email' => $user->email]
        );

        return redirect()->route('admin.users.index')
            ->with('success', "Kata sandi untuk {$user->name} berhasil diperbarui.");
    }

    /**
     * Delete user
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['general' => 'Anda tidak dapat menghapus akun Anda sendiri!']);
        }

        $userName = $user->name;
        $userEmail = $user->email;

        SecurityAuditLog::record(
            action: 'ADMIN_USER_DELETED',
            status: 'SUCCESS',
            severity: 'CRITICAL',
            targetType: 'USER',
            targetId: (string) $id,
            details: ['name' => $userName, 'email' => $userEmail]
        );

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Akun pengguna {$userName} ({$userEmail}) telah dihapus.");
    }
}
