<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'bidang_name',
        'phone',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Role checking helper. Superadmin always has full access.
     */
    public function hasRole(...$roles): bool
    {
        if ($this->role === 'superadmin') {
            return true;
        }

        // Flatten array if passed as array
        $flatRoles = [];
        foreach ($roles as $role) {
            if (is_array($role)) {
                $flatRoles = array_merge($flatRoles, $role);
            } else {
                $flatRoles[] = $role;
            }
        }

        return in_array($this->role, $flatRoles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isKeuangan(): bool
    {
        return $this->role === 'keuangan' || $this->role === 'superadmin';
    }

    public function isKantin(): bool
    {
        return $this->role === 'kantin' || $this->role === 'superadmin';
    }

    public function isKesiswaan(): bool
    {
        return $this->role === 'kesiswaan' || $this->role === 'superadmin';
    }

    public function isAkademik(): bool
    {
        return $this->role === 'akademik' || $this->role === 'superadmin';
    }

    public function getRoleBadgeAttribute(): array
    {
        return match ($this->role) {
            'superadmin' => [
                'name' => 'Super Administrator',
                'bidang' => 'Semua Bidang (Full Access)',
                'badge_class' => 'bg-purple-100 text-purple-700 border-purple-200',
                'icon' => 'fa-crown',
            ],
            'keuangan' => [
                'name' => 'Bidang Keuangan',
                'bidang' => 'SPP, Tabungan & Kas',
                'badge_class' => 'bg-amber-100 text-amber-700 border-amber-200',
                'icon' => 'fa-wallet',
            ],
            'kantin' => [
                'name' => 'Bidang Kantin',
                'bidang' => 'POS & Transaksi Kasir',
                'badge_class' => 'bg-blue-100 text-blue-700 border-blue-200',
                'icon' => 'fa-calculator',
            ],
            'kesiswaan' => [
                'name' => 'Bidang Kesiswaan',
                'bidang' => 'Presensi Gerbang & Kartu RFID',
                'badge_class' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                'icon' => 'fa-shield-halved',
            ],
            'akademik' => [
                'name' => 'Bidang Akademik',
                'bidang' => 'Kurikulum & Rekap Kehadiran',
                'badge_class' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                'icon' => 'fa-graduation-cap',
            ],
            default => [
                'name' => 'Staff Umum',
                'bidang' => 'Bidang Umum',
                'badge_class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'fa-user',
            ],
        };
    }

    public function auditLogs()
    {
        return $this->hasMany(SecurityAuditLog::class);
    }
}
