<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CanteenTransaction;
use App\Models\Parents;
use App\Models\RfidCard;
use App\Models\SecurityAuditLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class EduCardSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('admin@educard.sch.id|127.0.0.1');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $canteen = $this->get('/canteen');
        $canteen->assertRedirect('/login');

        $admin = $this->get('/admin/users');
        $admin->assertRedirect('/login');
    }

    public function test_successful_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@educard.sch.id',
            'password' => Hash::make('Educard123!'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@educard.sch.id',
            'password' => 'Educard123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // Verify audit log
        $this->assertDatabaseHas('security_audit_logs', [
            'action' => 'USER_LOGIN',
            'status' => 'SUCCESS',
        ]);
    }

    public function test_login_rate_limiter_blocks_brute_force_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'target@educard.sch.id',
            'password' => Hash::make('CorrectPassword123!'),
            'is_active' => true,
        ]);

        // Attempt 5 wrong logins
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'target@educard.sch.id',
                'password' => 'WrongPassword!',
            ]);
        }

        // 6th attempt should be throttled
        $response = $this->post('/login', [
            'email' => 'target@educard.sch.id',
            'password' => 'WrongPassword!',
        ]);

        $response->assertSessionHasErrors('email');
        $errorMsg = session('errors')->first('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login gagal', $errorMsg);
    }

    public function test_kantin_role_cannot_access_finance_or_admin_users(): void
    {
        $kantinUser = User::factory()->create([
            'email' => 'kasir@educard.sch.id',
            'role' => 'kantin',
            'is_active' => true,
        ]);

        // Can access canteen
        $this->actingAs($kantinUser)->get('/canteen')->assertStatus(200);

        // Cannot access finance -> 403 Forbidden
        $this->actingAs($kantinUser)->get('/finance')->assertStatus(403);

        // Cannot access admin users -> 403 Forbidden
        $this->actingAs($kantinUser)->get('/admin/users')->assertStatus(403);

        // Cannot access security audit logs -> 403 Forbidden
        $this->actingAs($kantinUser)->get('/admin/security')->assertStatus(403);

        // Verify that unauthorized access was recorded in Security Audit Log
        $this->assertDatabaseHas('security_audit_logs', [
            'action' => 'UNAUTHORIZED_ACCESS_ATTEMPT',
            'severity' => 'CRITICAL',
            'status' => 'BLOCKED',
        ]);
    }

    public function test_canteen_pos_payment_with_rfid_deducts_balance_atomically(): void
    {
        $cashier = User::factory()->create(['role' => 'kantin', 'is_active' => true]);

        $student = Student::create([
            'nisn' => '0011223344',
            'full_name' => 'Budi Pratama',
            'gender' => 'L',
            'class_name' => 'XII IPA 1',
            'status' => 'ACTIVE',
        ]);

        $card = RfidCard::create([
            'student_id' => $student->id,
            'card_uid' => 'CARD999',
            'status' => 'ACTIVE',
            'balance' => 50000,
            'daily_spending_limit' => 30000,
        ]);

        // Scan card first
        $scanResponse = $this->actingAs($cashier)->postJson('/canteen/scan', [
            'card_uid' => 'CARD999',
            'amount' => 15000,
        ]);
        $scanResponse->assertStatus(200);
        $scanResponse->assertJson(['success' => true]);

        // Execute payment
        $payResponse = $this->actingAs($cashier)->postJson('/canteen/pay', [
            'card_uid' => 'CARD999',
            'amount' => 15000,
            'tenant_name' => 'Kantin Barokah',
            'menu_name' => 'Nasi Kuning Komplit',
        ]);

        $payResponse->assertStatus(200);
        $payResponse->assertJson(['success' => true]);

        // Check card balance deducted in database
        $card->refresh();
        $this->assertEquals(35000, $card->balance);

        // Check transaction record
        $this->assertDatabaseHas('canteen_transactions', [
            'student_id' => $student->id,
            'amount' => 15000,
            'status' => 'SUCCESS',
        ]);
    }

    public function test_canteen_payment_rejects_blocked_card(): void
    {
        $cashier = User::factory()->create(['role' => 'kantin', 'is_active' => true]);

        $student = Student::create([
            'nisn' => '0088776655',
            'full_name' => 'Siswa Kartu Hilang',
            'gender' => 'L',
            'class_name' => 'XI IPS 1',
            'status' => 'ACTIVE',
        ]);

        $card = RfidCard::create([
            'student_id' => $student->id,
            'card_uid' => 'LOST123',
            'status' => 'BLOCKED',
            'blocked_reason' => 'Kartu hilang dilaporkan',
            'balance' => 50000,
            'daily_spending_limit' => 30000,
        ]);

        $response = $this->actingAs($cashier)->postJson('/canteen/scan', [
            'card_uid' => 'LOST123',
            'amount' => 10000,
        ]);

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }

    public function test_canteen_payment_rejects_insufficient_balance(): void
    {
        $cashier = User::factory()->create(['role' => 'kantin', 'is_active' => true]);

        $student = Student::create([
            'nisn' => '0077889911',
            'full_name' => 'Siswa Saldo Tipis',
            'gender' => 'L',
            'class_name' => 'X MIPA 2',
            'status' => 'ACTIVE',
        ]);

        $card = RfidCard::create([
            'student_id' => $student->id,
            'card_uid' => 'POOR001',
            'status' => 'ACTIVE',
            'balance' => 5000,
            'daily_spending_limit' => 30000,
        ]);

        $response = $this->actingAs($cashier)->postJson('/canteen/scan', [
            'card_uid' => 'POOR001',
            'amount' => 20000,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_gate_rfid_tap_records_tap_in_and_tap_out(): void
    {
        $guard = User::factory()->create(['role' => 'kesiswaan', 'is_active' => true]);

        $student = Student::create([
            'nisn' => '0055443322',
            'full_name' => 'Siswa Gerbang',
            'gender' => 'L',
            'class_name' => 'XII MIPA 1',
            'status' => 'ACTIVE',
        ]);

        $card = RfidCard::create([
            'student_id' => $student->id,
            'card_uid' => 'GATE777',
            'status' => 'ACTIVE',
            'balance' => 25000,
            'daily_spending_limit' => 25000,
        ]);

        // Tap 1: Tap In
        $tapInResponse = $this->actingAs($guard)->postJson('/gate/tap', [
            'card_uid' => 'GATE777',
            'gate_name' => 'Gerbang Depan Utama',
        ]);

        $tapInResponse->assertStatus(200);
        $tapInResponse->assertJson([
            'success' => true,
            'action_type' => 'TAP_IN',
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'status' => 'HADIR',
        ]);

        // Tap 2: Tap Out (Pulang)
        $tapOutResponse = $this->actingAs($guard)->postJson('/gate/tap', [
            'card_uid' => 'GATE777',
            'gate_name' => 'Gerbang Depan Utama',
        ]);

        $tapOutResponse->assertStatus(200);
        $tapOutResponse->assertJson([
            'success' => true,
            'action_type' => 'TAP_OUT',
        ]);
    }

    public function test_superadmin_has_access_to_all_modules(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)->get('/dashboard')->assertStatus(200);
        $this->actingAs($superadmin)->get('/canteen')->assertStatus(200);
        $this->actingAs($superadmin)->get('/gate')->assertStatus(200);
        $this->actingAs($superadmin)->get('/students')->assertStatus(200);
        $this->actingAs($superadmin)->get('/finance')->assertStatus(200);
        $this->actingAs($superadmin)->get('/admin/users')->assertStatus(200);
        $this->actingAs($superadmin)->get('/admin/security')->assertStatus(200);
    }
}
