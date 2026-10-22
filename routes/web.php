<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CanteenController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\SecurityAuditController;
use App\Http\Controllers\StudentCardController;
use Illuminate\Support\Facades\Route;

// ==================== AUTHENTICATION & DEMO ROUTES ====================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/demo-login/{role}', [AuthController::class, 'demoLogin'])->name('demo.login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ==================== MAIN DASHBOARD (ALL ROLES) ====================
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ==================== 1. BIDANG KANTIN & POS KASIR ====================
    Route::middleware('role:kantin,superadmin')->prefix('canteen')->name('canteen.')->group(function () {
        Route::get('/', [CanteenController::class, 'index'])->name('index');
        Route::post('/scan', [CanteenController::class, 'scan'])->name('scan');
        Route::post('/pay', [CanteenController::class, 'executePayment'])->name('pay');
        Route::get('/transactions', [CanteenController::class, 'transactions'])->name('transactions');
    });

    // ==================== 2. BIDANG KESISWAAN & GERBANG RFID ====================
    Route::middleware('role:kesiswaan,superadmin,akademik')->prefix('gate')->name('gate.')->group(function () {
        Route::get('/', [GateController::class, 'index'])->name('index');
        Route::post('/tap', [GateController::class, 'tap'])->name('tap');
        Route::get('/history', [GateController::class, 'history'])->name('history');
    });

    // ==================== 3. DATA SISWA & KARTU RFID ====================
    Route::prefix('students')->name('students.')->group(function () {
        Route::middleware('role:kesiswaan,superadmin,akademik')->group(function () {
            Route::get('/', [StudentCardController::class, 'index'])->name('index');
        });

        Route::middleware('role:kesiswaan,superadmin')->group(function () {
            Route::post('/', [StudentCardController::class, 'store'])->name('store');
            Route::post('/{id}/toggle-status', [StudentCardController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{id}', [StudentCardController::class, 'destroy'])->name('destroy');
        });

        // Top up card balance (Accessible by Keuangan, Kesiswaan, & Superadmin)
        Route::middleware('role:keuangan,kesiswaan,superadmin')->group(function () {
            Route::post('/{id}/topup', [StudentCardController::class, 'topup'])->name('topup');
        });
    });

    // ==================== 4. BIDANG KEUANGAN (SPP & TABUNGAN) ====================
    Route::middleware('role:keuangan,superadmin')->prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
        Route::post('/spp/create', [FinanceController::class, 'createSpp'])->name('spp.create');
        Route::post('/spp/{id}/pay', [FinanceController::class, 'paySpp'])->name('spp.pay');
        Route::post('/savings/mutate', [FinanceController::class, 'mutateSavings'])->name('savings.mutate');
    });

    // ==================== 5. SUPERADMINISTRATOR (USER MANAGEMENT & SECURITY) ====================
    Route::middleware('role:superadmin')->prefix('admin')->name('admin.')->group(function () {
        // User & Role Management
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::post('/users/{id}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{id}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        // Security Audit Logs
        Route::get('/security', [SecurityAuditController::class, 'index'])->name('security.index');
    });
});
