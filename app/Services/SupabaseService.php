<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CanteenTransaction;
use App\Models\Parents;
use App\Models\RfidCard;
use App\Models\SppBill;
use App\Models\Student;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseService
{
    protected string $url;
    protected string $key;

    public function __construct()
    {
        $this->url = rtrim(config('services.supabase.url', env('SUPABASE_URL', 'https://crezvxwrqidteruncalw.supabase.co')), '/');
        $this->key = config('services.supabase.key', env('SUPABASE_KEY', 'sb_publishable_jO5VvDlIiwPxnOhpiqTxbg_a0WKq30M'));
    }

    protected function client()
    {
        return Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => "Bearer {$this->key}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]);
    }

    /**
     * Get records from a Supabase table
     */
    public function get(string $table, array $params = [])
    {
        $response = $this->client()->get("{$this->url}/rest/v1/{$table}", array_merge(['select' => '*'], $params));

        if ($response->successful()) {
            return $response->json();
        }

        Log::warning("Supabase GET {$table} error: " . $response->body());
        return [];
    }

    /**
     * Insert record to Supabase table
     */
    public function insert(string $table, array $data)
    {
        try {
            $response = $this->client()->post("{$this->url}/rest/v1/{$table}", $data);
            return $response->json();
        } catch (Exception $e) {
            Log::warning("Supabase Insert {$table} failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update record in Supabase
     */
    public function update(string $table, string $id, array $data)
    {
        try {
            $response = $this->client()->patch("{$this->url}/rest/v1/{$table}?id=eq.{$id}", $data);
            return $response->json();
        } catch (Exception $e) {
            Log::warning("Supabase Update {$table} failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Pull all live API data from Supabase and synchronize locally
     */
    public function syncAll(): array
    {
        $stats = [
            'parents' => 0,
            'students' => 0,
            'cards' => 0,
            'canteen_transactions' => 0,
            'attendances' => 0,
            'spp_bills' => 0,
        ];

        // 1. Sync Parents
        $apiParents = $this->get('parents');
        foreach ($apiParents as $p) {
            Parents::updateOrCreate(
                ['id' => $p['id']],
                [
                    'full_name' => $p['full_name'] ?? 'Wali Murid',
                    'email' => $p['email'] ?? null,
                    'phone_number' => $p['phone_number'] ?? null,
                ]
            );
            $stats['parents']++;
        }

        // 2. Sync Students
        $apiStudents = $this->get('students');
        foreach ($apiStudents as $s) {
            Student::updateOrCreate(
                ['id' => $s['id']],
                [
                    'parent_id' => $s['parent_id'] ?? null,
                    'nisn' => $s['nisn'],
                    'full_name' => $s['full_name'],
                    'class_name' => $s['class_name'] ?? 'Umum',
                    'school_name' => $s['school_name'] ?? 'SMA Nusantara 1',
                    'photo_url' => $s['photo_url'] ?? null,
                    'status' => 'ACTIVE',
                ]
            );
            $stats['students']++;
        }

        // 3. Sync Cards
        $apiCards = $this->get('cards');
        foreach ($apiCards as $c) {
            RfidCard::updateOrCreate(
                ['id' => $c['id']],
                [
                    'student_id' => $c['student_id'],
                    'card_uid' => $c['card_uid'],
                    'status' => $c['status'] ?? 'ACTIVE',
                    'balance' => $c['balance'] ?? 0,
                    'daily_spending_limit' => $c['daily_spending_limit'] ?? 25000,
                ]
            );
            $stats['cards']++;
        }

        // 4. Sync Canteen Transactions
        $apiCanteen = $this->get('canteen_transactions');
        foreach ($apiCanteen as $tx) {
            CanteenTransaction::updateOrCreate(
                ['id' => $tx['id']],
                [
                    'receipt_number' => $tx['receipt_number'] ?? ('RCP-' . substr($tx['id'], 0, 8)),
                    'student_id' => $tx['student_id'],
                    'card_id' => $tx['card_id'],
                    'tenant_name' => $tx['tenant_name'] ?? 'Kantin',
                    'menu_name' => $tx['menu_name'] ?? 'Pembelian Makanan/Minuman',
                    'category' => $tx['category'] ?? 'FOOD',
                    'amount' => $tx['amount'] ?? 0,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'status' => 'SUCCESS',
                    'created_at' => isset($tx['timestamp']) ? Carbon::parse($tx['timestamp']) : now(),
                ]
            );
            $stats['canteen_transactions']++;
        }

        // 5. Sync Attendances
        $apiAttendances = $this->get('attendances');
        foreach ($apiAttendances as $att) {
            Attendance::updateOrCreate(
                ['id' => $att['id']],
                [
                    'student_id' => $att['student_id'],
                    'card_id' => $att['card_id'],
                    'date' => $att['date'] ?? Carbon::today()->toDateString(),
                    'tap_in_time' => isset($att['tap_in_time']) ? Carbon::parse($att['tap_in_time']) : null,
                    'tap_out_time' => isset($att['tap_out_time']) ? Carbon::parse($att['tap_out_time']) : null,
                    'gate_name' => $att['gate_name'] ?? 'Gerbang Utama',
                    'status' => $att['status'] ?? 'HADIR',
                    'is_late' => $att['is_late'] ?? false,
                ]
            );
            $stats['attendances']++;
        }

        // 6. Sync SPP Bills
        $apiSpp = $this->get('spp_bills');
        foreach ($apiSpp as $bill) {
            SppBill::updateOrCreate(
                ['id' => $bill['id']],
                [
                    'student_id' => $bill['student_id'],
                    'month' => $bill['month'] ?? 10,
                    'year' => $bill['year'] ?? 2026,
                    'amount' => $bill['amount'] ?? 350000,
                    'status' => $bill['status'] ?? 'UNPAID',
                    'due_date' => $bill['due_date'] ?? '2026-10-10',
                    'paid_at' => isset($bill['paid_at']) ? Carbon::parse($bill['paid_at']) : null,
                    'receipt_number' => $bill['receipt_number'] ?? null,
                ]
            );
            $stats['spp_bills']++;
        }

        return $stats;
    }
}
