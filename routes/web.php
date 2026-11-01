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

// ==================== PUBLIC MONITOR GERBANG (TANPA LOGIN) & NATURAL TTS ====================
Route::get('/gate-monitor', [GateController::class, 'publicMonitor'])->name('gate.monitor');
Route::post('/gate-monitor/tap', [GateController::class, 'tap'])->name('gate.monitor.tap');
Route::get('/tts-voice', [GateController::class, 'tts'])->name('tts.voice');

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
        Route::get('/export', [CanteenController::class, 'export'])->name('export');
    });

    // ==================== 2. BIDANG KESISWAAN & GERBANG RFID ====================
    Route::middleware('role:kesiswaan,superadmin,akademik')->prefix('gate')->name('gate.')->group(function () {
        Route::get('/', [GateController::class, 'index'])->name('index');
        Route::post('/tap', [GateController::class, 'tap'])->name('tap');
        Route::get('/history', [GateController::class, 'history'])->name('history');
        Route::get('/export', [GateController::class, 'export'])->name('export');
    });

    // ==================== 3. DATA SISWA & KARTU RFID ====================
    Route::prefix('students')->name('students.')->group(function () {
        Route::middleware('role:kesiswaan,superadmin,akademik,keuangan')->group(function () {
            Route::get('/', [StudentCardController::class, 'index'])->name('index');
            Route::get('/export', [StudentCardController::class, 'export'])->name('export');
            Route::get('/template', [StudentCardController::class, 'downloadTemplate'])->name('template');
        });

        Route::middleware('role:kesiswaan,superadmin')->group(function () {
            Route::post('/', [StudentCardController::class, 'store'])->name('store');
            Route::post('/import', [StudentCardController::class, 'import'])->name('import');
            Route::post('/promote-class', [StudentCardController::class, 'promoteClass'])->name('promote-class');
            Route::post('/{id}/activate-card', [StudentCardController::class, 'activateCard'])->name('activate-card');
            Route::put('/{id}', [StudentCardController::class, 'update'])->name('update');
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
