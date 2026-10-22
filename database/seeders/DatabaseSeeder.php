<?php

namespace Database\Seeders;

use App\Models\SecurityAuditLog;
use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database using real data from the API.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('Educard123!');

        // 1. Seed Real Operational Accounts for Each Department
        $admin = User::firstOrCreate(
            ['email' => 'admin@educard.sch.id'],
            [
                'name' => 'Administrator Utama',
                'role' => 'superadmin',
                'bidang_name' => 'Administrator',
                'phone' => '081234567890',
                'is_active' => true,
                'password' => $defaultPassword,
            ]
        );

        User::firstOrCreate(
            ['email' => 'keuangan@educard.sch.id'],
            [
                'name' => 'Petugas Keuangan',
                'role' => 'keuangan',
                'bidang_name' => 'Keuangan & SPP',
                'phone' => '081398765432',
                'is_active' => true,
                'password' => $defaultPassword,
            ]
        );

        User::firstOrCreate(
            ['email' => 'kantin@educard.sch.id'],
            [
                'name' => 'Kasir Kantin',
                'role' => 'kantin',
                'bidang_name' => 'Kantin POS',
                'phone' => '081511223344',
                'is_active' => true,
                'password' => $defaultPassword,
            ]
        );

        User::firstOrCreate(
            ['email' => 'kesiswaan@educard.sch.id'],
            [
                'name' => 'Petugas Kesiswaan',
                'role' => 'kesiswaan',
                'bidang_name' => 'Kesiswaan & Gerbang',
                'phone' => '081677889900',
                'is_active' => true,
                'password' => $defaultPassword,
            ]
        );

        User::firstOrCreate(
            ['email' => 'akademik@educard.sch.id'],
            [
                'name' => 'Petugas Akademik',
                'role' => 'akademik',
                'bidang_name' => 'Akademik & Kurikulum',
                'phone' => '081788990011',
                'is_active' => true,
                'password' => $defaultPassword,
            ]
        );

        // 2. Fetch & Synchronize Live Data from Supabase API (No fake demo data)
        try {
            $supabase = new SupabaseService();
            $stats = $supabase->syncAll();
            $this->command->info("Data tersinkronisasi dari Supabase API: " . json_encode($stats));
        } catch (\Exception $e) {
            $this->command->warn("Sinkronisasi Supabase API: " . $e->getMessage());
        }

        SecurityAuditLog::record(
            action: 'SYSTEM_INITIALIZED',
            status: 'SUCCESS',
            severity: 'INFO',
            targetType: 'SYSTEM',
            targetId: 'API_SYNC',
            details: ['source' => 'Supabase Live API'],
            userId: $admin->id
        );
    }
}
