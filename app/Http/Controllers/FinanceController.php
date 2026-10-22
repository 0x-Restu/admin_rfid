<?php

namespace App\Http\Controllers;

use App\Models\RfidCard;
use App\Models\SecurityAuditLog;
use App\Models\SppBill;
use App\Models\Student;
use App\Models\StudentSaving;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinanceController extends Controller
{
    /**
     * Display Finance & SPP Management Dashboard
     */
    public function index(Request $request)
    {
        $querySpp = SppBill::with(['student.parent', 'processor'])->latest();

        if ($request->filled('status') && $request->status !== 'ALL') {
            $querySpp->where('status', $request->status);
        }

        if ($request->filled('month')) {
            $querySpp->where('month', $request->month);
        }

        if ($request->filled('year')) {
            $querySpp->where('year', $request->year);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $querySpp->whereHas('student', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $sppBills = $querySpp->paginate(15, ['*'], 'spp_page')->withQueryString();

        // Financial Metrics
        $totalSppUnpaid = SppBill::where('status', 'UNPAID')->sum('amount');
        $totalSppPaid = SppBill::where('status', 'PAID')->sum('amount');
        $totalSavingsDeposited = StudentSaving::where('type', 'DEPOSIT')->sum('amount');
        $totalSavingsWithdrawn = StudentSaving::where('type', 'WITHDRAWAL')->sum('amount');
        $netSavingsPool = $totalSavingsDeposited - $totalSavingsWithdrawn;

        // Savings mutations list
        $savingsMutations = StudentSaving::with(['student', 'processor'])
            ->latest()
            ->take(12)
            ->get();

        // Students for dropdown selectors
        $allStudents = Student::with('rfidCard')->orderBy('full_name')->get();

        return view('finance.index', compact(
            'sppBills',
            'totalSppUnpaid',
            'totalSppPaid',
            'netSavingsPool',
            'savingsMutations',
            'allStudents'
        ));
    }

    /**
     * Create SPP Bill for a student
     */
    public function createSpp(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2035'],
            'amount' => ['required', 'numeric', 'min:10000'],
            'due_date' => ['required', 'date'],
        ]);

        // Check if bill already exists for that student, month, and year
        $existing = SppBill::where('student_id', $validated['student_id'])
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->first();

        if ($existing) {
            return back()->withErrors(['general' => "Tagihan SPP untuk siswa ini pada bulan {$validated['month']}/{$validated['year']} sudah dibuat sebelumnya!"]);
        }

        $bill = SppBill::create([
            'student_id' => $validated['student_id'],
            'month' => $validated['month'],
            'year' => $validated['year'],
            'amount' => $validated['amount'],
            'status' => 'UNPAID',
            'due_date' => $validated['due_date'],
        ]);

        SecurityAuditLog::record(
            action: 'SPP_BILL_CREATED',
            status: 'SUCCESS',
            severity: 'INFO',
            targetType: 'SPP_BILL',
            targetId: (string) $bill->id,
            details: [
                'student_id' => $bill->student_id,
                'month' => $bill->month,
                'year' => $bill->year,
                'amount' => $bill->amount,
            ]
        );

        return redirect()->route('finance.index')->with('success', 'Tagihan SPP baru berhasil dibuat!');
    }

    /**
     * Pay SPP Bill (Via Cash, Transfer, or RFID Card Balance)
     */
    public function paySpp(Request $request, $id)
    {
        $request->validate([
            'paid_via' => ['required', 'in:CASH,TRANSFER,RFID_BALANCE'],
        ]);

        $paidVia = $request->input('paid_via');

        try {
            $bill = DB::transaction(function () use ($id, $paidVia) {
                $bill = SppBill::with('student.rfidCard')->lockForUpdate()->findOrFail($id);

                if ($bill->status === 'PAID') {
                    throw new Exception("Tagihan SPP ini sudah lunas sebelumnya.");
                }

                // If paying via student's RFID Card Balance
                if ($paidVia === 'RFID_BALANCE') {
                    $card = RfidCard::where('student_id', $bill->student_id)->lockForUpdate()->first();

                    if (!$card) {
                        throw new Exception("Siswa tidak memiliki Smart Card RFID terdaftar!");
                    }

                    if ($card->status !== 'ACTIVE') {
                        throw new Exception("Kartu siswa berstatus '{$card->status}', pembayaran ditolak.");
                    }

                    if ($card->balance < $bill->amount) {
                        throw new Exception("Saldo Smart Card tidak mencukupi! Saldo: Rp " . number_format($card->balance, 0, ',', '.') . ", Tagihan: Rp " . number_format($bill->amount, 0, ',', '.'));
                    }

                    // Deduct from card balance
                    $card->balance -= $bill->amount;
                    $card->last_tapped_at = now();
                    $card->save();
                }

                $receiptNumber = 'KW-SPP-' . date('Ymd') . '-' . strtoupper(Str::random(5));

                $bill->update([
                    'status' => 'PAID',
                    'paid_at' => now(),
                    'receipt_number' => $receiptNumber,
                    'paid_via' => $paidVia,
                    'processed_by' => Auth::id(),
                ]);

                SecurityAuditLog::record(
                    action: 'SPP_BILL_PAID',
                    status: 'SUCCESS',
                    severity: 'INFO',
                    targetType: 'SPP_BILL',
                    targetId: $receiptNumber,
                    details: [
                        'student_id' => $bill->student_id,
                        'amount' => $bill->amount,
                        'paid_via' => $paidVia,
                        'month' => $bill->month,
                        'year' => $bill->year,
                    ]
                );

                return $bill;
            });

            return redirect()->route('finance.index')
                ->with('success', "Pembayaran SPP Bulan {$bill->month_name} {$bill->year} Berhasil! Kwitansi: {$bill->receipt_number}");
        } catch (Exception $e) {
            return back()->withErrors(['general' => "Gagal memproses pembayaran SPP: " . $e->getMessage()]);
        }
    }

    /**
     * Record Student Savings Mutation (Deposit / Withdrawal)
     */
    public function mutateSavings(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'type' => ['required', 'in:DEPOSIT,WITHDRAWAL'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $studentId = $validated['student_id'];
        $type = $validated['type'];
        $amount = (float) $validated['amount'];
        $note = $validated['note'] ?: ($type === 'DEPOSIT' ? 'Setoran tunai tabungan' : 'Penarikan tabungan');

        try {
            $mutation = DB::transaction(function () use ($studentId, $type, $amount, $note) {
                // Get last saving record to compute balance
                $lastSaving = StudentSaving::where('student_id', $studentId)->latest()->first();
                $currentBalance = $lastSaving ? $lastSaving->balance_after : 0;

                if ($type === 'WITHDRAWAL' && $currentBalance < $amount) {
                    throw new Exception("Saldo tabungan siswa tidak mencukupi untuk ditarik! Saldo saat ini: Rp " . number_format($currentBalance, 0, ',', '.'));
                }

                $newBalance = $type === 'DEPOSIT' ? ($currentBalance + $amount) : ($currentBalance - $amount);

                $saving = StudentSaving::create([
                    'student_id' => $studentId,
                    'type' => $type,
                    'amount' => $amount,
                    'balance_after' => $newBalance,
                    'note' => $note,
                    'processed_by' => Auth::id(),
                ]);

                SecurityAuditLog::record(
                    action: $type === 'DEPOSIT' ? 'SAVINGS_DEPOSIT' : 'SAVINGS_WITHDRAWAL',
                    status: 'SUCCESS',
                    severity: 'INFO',
                    targetType: 'SAVINGS',
                    targetId: (string) $saving->id,
                    details: [
                        'student_id' => $studentId,
                        'amount' => $amount,
                        'new_balance' => $newBalance,
                    ]
                );

                return $saving;
            });

            return redirect()->route('finance.index')
                ->with('success', "Pencatatan " . ($type === 'DEPOSIT' ? 'Setoran' : 'Penarikan') . " Tabungan Rp " . number_format($amount, 0, ',', '.') . " berhasil!");
        } catch (Exception $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }
}
