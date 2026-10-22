<?php

namespace App\Http\Controllers;

use App\Models\CanteenTransaction;
use App\Models\RfidCard;
use App\Models\SecurityAuditLog;
use App\Models\Student;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CanteenController extends Controller
{
    /**
     * Display POS Register Calculator Page
     */
    public function index()
    {
        $today = Carbon::today()->toDateString();

        $todayRevenue = CanteenTransaction::whereDate('created_at', $today)
            ->where('status', 'SUCCESS')
            ->sum('amount');

        $todayTrxCount = CanteenTransaction::whereDate('created_at', $today)
            ->where('status', 'SUCCESS')
            ->count();

        // Sample active student cards for quick simulator testing
        $sampleStudents = Student::with('rfidCard')
            ->whereHas('rfidCard')
            ->take(8)
            ->get();

        $recentTransactions = CanteenTransaction::with('student', 'card')
            ->whereDate('created_at', $today)
            ->latest()
            ->take(10)
            ->get();

        return view('canteen.pos', compact(
            'todayRevenue',
            'todayTrxCount',
            'sampleStudents',
            'recentTransactions'
        ));
    }

    /**
     * Step 1: Scan & Lookup Card UID for Payment Confirmation
     */
    public function scan(Request $request)
    {
        $request->validate([
            'card_uid' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:100'],
        ]);

        $uid = strtoupper(trim($request->input('card_uid')));
        $billAmount = (float) $request->input('amount');

        $card = RfidCard::with(['student.parent'])->where('card_uid', $uid)->first();

        if (!$card) {
            SecurityAuditLog::record(
                action: 'RFID_CARD_UNREGISTERED_SCAN',
                status: 'FAILED',
                severity: 'WARNING',
                targetType: 'RFID_CARD',
                targetId: $uid,
                details: ['bill_amount' => $billAmount, 'terminal' => 'CANTEEN_POS']
            );

            return response()->json([
                'success' => false,
                'message' => "Kartu RFID dengan UID [{$uid}] tidak terdaftar di sistem!",
            ], 404);
        }

        // Check Card Status
        if ($card->status !== 'ACTIVE') {
            SecurityAuditLog::record(
                action: 'RFID_BLOCKED_CARD_PAYMENT_ATTEMPT',
                status: 'BLOCKED',
                severity: 'CRITICAL',
                targetType: 'RFID_CARD',
                targetId: $uid,
                details: [
                    'student_name' => $card->student->full_name,
                    'card_status' => $card->status,
                    'bill_amount' => $billAmount,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => "Transaksi DITOLAK: Kartu ini berstatus '{$card->status}'! " . ($card->blocked_reason ? "({$card->blocked_reason})" : ""),
                'card_status' => $card->status,
            ], 403);
        }

        // Check Daily Spending Limit
        $today = Carbon::today()->toDateString();
        $todaySpent = CanteenTransaction::where('card_id', $card->id)
            ->whereDate('created_at', $today)
            ->where('status', 'SUCCESS')
            ->sum('amount');

        $remainingLimit = max(0, $card->daily_spending_limit - $todaySpent);

        if (($todaySpent + $billAmount) > $card->daily_spending_limit) {
            return response()->json([
                'success' => false,
                'message' => "Batas Belanja Harian Terlampaui! Limit: Rp " . number_format($card->daily_spending_limit, 0, ',', '.') . " / hari. Hari ini sudah belanja: Rp " . number_format($todaySpent, 0, ',', '.') . ". Sisa limit: Rp " . number_format($remainingLimit, 0, ',', '.'),
                'card_status' => $card->status,
            ], 422);
        }

        // Check Balance
        if ($card->balance < $billAmount) {
            return response()->json([
                'success' => false,
                'message' => "Saldo Tidak Cukup! Saldo saat ini: Rp " . number_format($card->balance, 0, ',', '.') . ", Tagihan: Rp " . number_format($billAmount, 0, ',', '.'),
                'balance' => $card->balance,
                'bill_amount' => $billAmount,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'card' => [
                'id' => $card->id,
                'card_uid' => $card->card_uid,
                'status' => $card->status,
                'balance' => $card->balance,
                'balance_formatted' => 'Rp ' . number_format($card->balance, 0, ',', '.'),
                'daily_limit' => $card->daily_spending_limit,
                'daily_limit_formatted' => 'Rp ' . number_format($card->daily_spending_limit, 0, ',', '.'),
                'today_spent' => $todaySpent,
                'remaining_limit' => $remainingLimit,
            ],
            'student' => [
                'id' => $card->student->id,
                'full_name' => $card->student->full_name,
                'nisn' => $card->student->nisn,
                'class_name' => $card->student->class_name,
                'school_name' => $card->student->school_name,
                'photo_url' => $card->student->photo,
                'parent_name' => $card->student->parent ? $card->student->parent->full_name : '-',
            ],
            'bill' => [
                'amount' => $billAmount,
                'amount_formatted' => 'Rp ' . number_format($billAmount, 0, ',', '.'),
                'balance_after' => $card->balance - $billAmount,
                'balance_after_formatted' => 'Rp ' . number_format($card->balance - $billAmount, 0, ',', '.'),
            ],
        ]);
    }

    /**
     * Step 2: Atomic Execution of POS Payment (Pessimistic Lock & Zero-Race-Condition)
     */
    public function executePayment(Request $request)
    {
        $request->validate([
            'card_uid' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:100'],
            'tenant_name' => ['required', 'string', 'max:100'],
            'menu_name' => ['nullable', 'string', 'max:255'],
        ]);

        $uid = strtoupper(trim($request->input('card_uid')));
        $billAmount = (float) $request->input('amount');
        $tenantName = $request->input('tenant_name');
        $menuName = $request->input('menu_name') ?: "Pembelian Kasir POS (Rp " . number_format($billAmount, 0, ',', '.') . ")";

        try {
            $transaction = DB::transaction(function () use ($uid, $billAmount, $tenantName, $menuName) {
                // High Security: Pessimistic Row Lock on Card to prevent Double Spending
                $card = RfidCard::where('card_uid', $uid)->lockForUpdate()->first();

                if (!$card) {
                    throw new Exception("Kartu tidak ditemukan.");
                }

                if ($card->status !== 'ACTIVE') {
                    throw new Exception("Kartu tidak aktif (Status: {$card->status}).");
                }

                if ($card->balance < $billAmount) {
                    throw new Exception("Saldo tidak mencukupi (Saldo: Rp " . number_format($card->balance, 0, ',', '.') . ").");
                }

                // Check Daily Limit atomically
                $today = Carbon::today()->toDateString();
                $todaySpent = CanteenTransaction::where('card_id', $card->id)
                    ->whereDate('created_at', $today)
                    ->where('status', 'SUCCESS')
                    ->sum('amount');

                if (($todaySpent + $billAmount) > $card->daily_spending_limit) {
                    throw new Exception("Melebihi batas transaksi harian siswa.");
                }

                $balanceBefore = $card->balance;
                $balanceAfter = $balanceBefore - $billAmount;

                // Update Card Balance & Last Tapped
                $card->balance = $balanceAfter;
                $card->last_tapped_at = now();
                $card->save();

                // Generate Unique Receipt Number
                $receiptNumber = 'RCP-' . date('Ymd') . '-' . strtoupper(Str::random(6));

                // Create Canteen Transaction Record
                $trx = CanteenTransaction::create([
                    'receipt_number' => $receiptNumber,
                    'student_id' => $card->student_id,
                    'card_id' => $card->id,
                    'user_id' => Auth::id(),
                    'tenant_name' => $tenantName,
                    'menu_name' => $menuName,
                    'category' => 'FOOD',
                    'amount' => $billAmount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'status' => 'SUCCESS',
                    'payment_method' => 'RFID_CARD',
                ]);

                // Record Audit Log
                SecurityAuditLog::record(
                    action: 'RFID_CANTEEN_PAYMENT',
                    status: 'SUCCESS',
                    severity: 'INFO',
                    targetType: 'CANTEEN_TRANSACTION',
                    targetId: $receiptNumber,
                    details: [
                        'student_id' => $card->student_id,
                        'card_uid' => $uid,
                        'amount' => $billAmount,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'tenant' => $tenantName,
                        'cashier_id' => Auth::id(),
                    ]
                );

                return $trx;
            });

            return response()->json([
                'success' => true,
                'message' => "Pembayaran berhasil sebesar Rp " . number_format($billAmount, 0, ',', '.'),
                'receipt_number' => $transaction->receipt_number,
                'balance_after' => $transaction->balance_after,
                'balance_after_formatted' => 'Rp ' . number_format($transaction->balance_after, 0, ',', '.'),
            ]);
        } catch (Exception $e) {
            SecurityAuditLog::record(
                action: 'RFID_PAYMENT_ERROR',
                status: 'FAILED',
                severity: 'WARNING',
                targetType: 'RFID_CARD',
                targetId: $uid,
                details: ['error' => $e->getMessage(), 'amount' => $billAmount]
            );

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * History & Reports of Canteen Sales
     */
    public function transactions(Request $request)
    {
        $query = CanteenTransaction::with(['student', 'card', 'cashier'])->latest();

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('tenant')) {
            $query->where('tenant_name', $request->tenant);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($sq) use ($search) {
                      $sq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('nisn', 'like', "%{$search}%");
                  });
            });
        }

        $transactions = $query->paginate(20)->withQueryString();
        $tenants = CanteenTransaction::select('tenant_name')->distinct()->pluck('tenant_name');

        $totalFilteredSum = $query->sum('amount');

        return view('canteen.transactions', compact('transactions', 'tenants', 'totalFilteredSum'));
    }
}
