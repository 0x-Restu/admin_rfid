<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Orang Tua Siswa (Parents)
        Schema::create('parents', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('full_name');
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // 2. Data Siswa (Students)
        Schema::create('students', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('parent_id', 50)->nullable()->index();
            $table->string('nisn', 30)->unique();
            $table->string('full_name');
            $table->enum('gender', ['L', 'P'])->default('L');
            $table->string('class_name', 50);
            $table->string('school_name')->default('SMA Nusantara 1');
            $table->text('photo_url')->nullable();
            $table->enum('status', ['ACTIVE', 'GRADUATED', 'SUSPENDED'])->default('ACTIVE');
            $table->timestamps();
        });

        // 3. Smart RFID Cards (Table name 'cards' matching Supabase)
        Schema::create('rfid_cards', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('student_id', 50)->unique()->index();
            $table->string('card_uid', 50)->unique()->index();
            $table->string('pin_hash')->nullable();
            $table->enum('status', ['ACTIVE', 'BLOCKED', 'LOST', 'SUSPENDED'])->default('ACTIVE');
            $table->decimal('balance', 14, 2)->default(0);
            $table->decimal('daily_spending_limit', 14, 2)->default(25000);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('last_tapped_at')->nullable();
            $table->string('blocked_reason')->nullable();
            $table->timestamps();
        });

        // 4. Transaksi POS Kantin Digital
        Schema::create('canteen_transactions', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('receipt_number', 50)->unique()->index();
            $table->string('student_id', 50)->index();
            $table->string('card_id', 50)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tenant_name', 100)->default('Kantin');
            $table->string('menu_name')->default('Pembelian POS Kantin');
            $table->enum('category', ['FOOD', 'DRINK', 'SNACK', 'STATIONERY', 'OTHER'])->default('FOOD');
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_before', 14, 2)->default(0);
            $table->decimal('balance_after', 14, 2)->default(0);
            $table->enum('status', ['SUCCESS', 'FAILED', 'REFUNDED'])->default('SUCCESS');
            $table->string('payment_method', 30)->default('RFID_CARD');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Presensi Gerbang RFID (Gate Access Control)
        Schema::create('attendances', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('student_id', 50)->index();
            $table->string('card_id', 50)->index();
            $table->date('date')->index();
            $table->timestamp('tap_in_time')->nullable();
            $table->timestamp('tap_out_time')->nullable();
            $table->string('gate_name', 100)->default('Gerbang Utama');
            $table->enum('status', ['HADIR', 'TERLAMBAT', 'PULANG', 'IZIN', 'SAKIT', 'ALPA'])->default('HADIR');
            $table->boolean('is_late')->default(false);
            $table->timestamps();
        });

        // 6. Tagihan & Pembayaran SPP
        Schema::create('spp_bills', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('student_id', 50)->index();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('amount', 14, 2);
            $table->enum('status', ['UNPAID', 'PAID', 'OVERDUE', 'CANCELLED'])->default('UNPAID');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('receipt_number', 50)->nullable()->unique();
            $table->enum('paid_via', ['RFID_BALANCE', 'CASH', 'TRANSFER'])->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'year', 'month']);
        });

        // 7. Tabungan Siswa (Student Savings)
        Schema::create('student_savings', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('student_id', 50)->index();
            $table->enum('type', ['DEPOSIT', 'WITHDRAWAL']);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->string('note')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 8. Riwayat Top-Up Saldo Kartu RFID
        Schema::create('card_topups', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('card_id', 50)->index();
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_before', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->enum('method', ['CASH', 'TRANSFER', 'EDC'])->default('CASH');
            $table->string('receipt_number', 50)->unique();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        // 9. Sistem Audit Trail & Log Keamanan Tinggi
        Schema::create('security_audit_logs', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80)->index();
            $table->enum('severity', ['INFO', 'NOTICE', 'WARNING', 'CRITICAL'])->default('INFO')->index();
            $table->enum('status', ['SUCCESS', 'FAILED', 'BLOCKED'])->default('SUCCESS')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('target_type', 80)->nullable();
            $table->string('target_id', 50)->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_audit_logs');
        Schema::dropIfExists('card_topups');
        Schema::dropIfExists('student_savings');
        Schema::dropIfExists('spp_bills');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('canteen_transactions');
        Schema::dropIfExists('rfid_cards');
        Schema::dropIfExists('students');
        Schema::dropIfExists('parents');
    }
};
