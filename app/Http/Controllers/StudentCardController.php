<?php

namespace App\Http\Controllers;

use App\Models\CardTopup;
use App\Models\Parents;
use App\Models\RfidCard;
use App\Models\SecurityAuditLog;
use App\Models\Student;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentCardController extends Controller
{
    /**
     * Display Student & Card Management Table
     */
    public function index(Request $request)
    {
        $query = Student::with(['parent', 'rfidCard'])->latest();

        if ($request->filled('class_name')) {
            $query->where('class_name', $request->class_name);
        }

        if ($request->filled('card_status')) {
            $query->whereHas('rfidCard', function ($q) use ($request) {
                $q->where('status', $request->card_status);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhereHas('rfidCard', function ($rq) use ($search) {
                      $rq->where('card_uid', 'like', "%{$search}%");
                  });
            });
        }

        $students = $query->paginate(15)->withQueryString();
        $classes = Student::select('class_name')->distinct()->pluck('class_name');

        $activeCardsCount = RfidCard::where('status', 'ACTIVE')->count();
        $blockedCardsCount = RfidCard::where('status', 'BLOCKED')->count();
        $totalBalanceSum = RfidCard::sum('balance');

        return view('students.index', compact(
            'students',
            'classes',
            'activeCardsCount',
            'blockedCardsCount',
            'totalBalanceSum'
        ));
    }

    /**
     * Store new student and assign RFID card
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'nisn' => ['required', 'string', 'max:30', 'unique:students,nisn'],
            'gender' => ['required', 'in:L,P'],
            'class_name' => ['required', 'string', 'max:50'],
            'school_name' => ['nullable', 'string', 'max:100'],
            'photo_url' => ['nullable', 'string', 'max:500'],
            'card_uid' => ['required', 'string', 'max:50', 'unique:rfid_cards,card_uid'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
            'daily_limit' => ['nullable', 'numeric', 'min:5000'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                // Parent creation
                $parentId = null;
                if (!empty($validated['parent_name'])) {
                    $parent = Parents::create([
                        'full_name' => $validated['parent_name'],
                        'phone_number' => $validated['parent_phone'] ?? null,
                        'email' => $validated['nisn'] . '@sekolah.id',
                    ]);
                    $parentId = $parent->id;
                }

                // Student creation
                $student = Student::create([
                    'parent_id' => $parentId,
                    'nisn' => $validated['nisn'],
                    'full_name' => $validated['full_name'],
                    'gender' => $validated['gender'],
                    'class_name' => $validated['class_name'],
                    'school_name' => $validated['school_name'] ?: 'SMA Negeri 1 Prestasi',
                    'photo_url' => $validated['photo_url'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb',
                    'status' => 'ACTIVE',
                ]);

                $initialBalance = (float) ($validated['initial_balance'] ?? 0);
                $dailyLimit = (float) ($validated['daily_limit'] ?? 25000);

                // RFID Card Creation
                $card = RfidCard::create([
                    'student_id' => $student->id,
                    'card_uid' => strtoupper(trim($validated['card_uid'])),
                    'status' => 'ACTIVE',
                    'balance' => $initialBalance,
                    'daily_spending_limit' => $dailyLimit,
                    'issued_at' => now(),
                ]);

                // Initial Topup record if balance > 0
                if ($initialBalance > 0) {
                    CardTopup::create([
                        'card_id' => $card->id,
                        'amount' => $initialBalance,
                        'balance_before' => 0,
                        'balance_after' => $initialBalance,
                        'method' => 'CASH',
                        'receipt_number' => 'TOP-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                        'processed_by' => Auth::id(),
                        'notes' => 'Saldo awal saat pendaftaran kartu',
                    ]);
                }

                SecurityAuditLog::record(
                    action: 'STUDENT_AND_CARD_REGISTERED',
                    status: 'SUCCESS',
                    severity: 'INFO',
                    targetType: 'STUDENT',
                    targetId: (string) $student->id,
                    details: [
                        'student_name' => $student->full_name,
                        'nisn' => $student->nisn,
                        'card_uid' => $card->card_uid,
                        'balance' => $initialBalance,
                    ]
                );
            });

            return redirect()->route('students.index')->with('success', "Data siswa {$validated['full_name']} dan Smart Card RFID berhasil ditambahkan!");
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['general' => "Gagal menambahkan siswa: " . $e->getMessage()]);
        }
    }

    /**
     * Top-up RFID Card Balance
     */
    public function topup(Request $request, $id)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:5000', 'max:5000000'],
            'method' => ['required', 'in:CASH,TRANSFER,EDC'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = (float) $request->input('amount');
        $method = $request->input('method');
        $notes = $request->input('notes');

        try {
            $topup = DB::transaction(function () use ($id, $amount, $method, $notes) {
                // Pessimistic lock on Card
                $card = RfidCard::where('id', $id)->lockForUpdate()->firstOrFail();

                $balanceBefore = $card->balance;
                $balanceAfter = $balanceBefore + $amount;

                $card->balance = $balanceAfter;
                $card->save();

                $receiptNumber = 'TOP-' . date('Ymd') . '-' . strtoupper(Str::random(6));

                $log = CardTopup::create([
                    'card_id' => $card->id,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'method' => $method,
                    'receipt_number' => $receiptNumber,
                    'processed_by' => Auth::id(),
                    'notes' => $notes,
                ]);

                SecurityAuditLog::record(
                    action: 'CARD_TOPUP_SUCCESS',
                    status: 'SUCCESS',
                    severity: 'INFO',
                    targetType: 'RFID_CARD',
                    targetId: $card->card_uid,
                    details: [
                        'student_id' => $card->student_id,
                        'amount' => $amount,
                        'receipt_number' => $receiptNumber,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                    ]
                );

                return $log;
            });

            return redirect()->route('students.index')
                ->with('success', "Top-Up Berhasil! Saldo kartu bertambah Rp " . number_format($amount, 0, ',', '.') . " (Kwitansi: {$topup->receipt_number})");
        } catch (Exception $e) {
            return back()->withErrors(['general' => "Gagal melakukan top-up: " . $e->getMessage()]);
        }
    }

    /**
     * Toggle Card Status (ACTIVE <-> BLOCKED / LOST)
     */
    public function toggleStatus(Request $request, $id)
    {
        $card = RfidCard::with('student')->findOrFail($id);

        $newStatus = $card->status === 'ACTIVE' ? 'BLOCKED' : 'ACTIVE';
        $reason = $request->input('reason', $newStatus === 'BLOCKED' ? 'Dinonaktifkan oleh Petugas Kesiswaan' : null);

        $card->update([
            'status' => $newStatus,
            'blocked_reason' => $reason,
        ]);

        SecurityAuditLog::record(
            action: 'CARD_STATUS_CHANGED',
            status: 'SUCCESS',
            severity: $newStatus === 'BLOCKED' ? 'WARNING' : 'INFO',
            targetType: 'RFID_CARD',
            targetId: $card->card_uid,
            details: [
                'student_name' => $card->student->full_name,
                'old_status' => $card->getOriginal('status'),
                'new_status' => $newStatus,
                'reason' => $reason,
            ]
        );

        return redirect()->route('students.index')
            ->with('success', "Status kartu siswa {$card->student->full_name} berhasil diubah menjadi: {$newStatus}");
    }

    /**
     * Delete student and associated card
     */
    public function destroy($id)
    {
        $student = Student::with('rfidCard')->findOrFail($id);
        $studentName = $student->full_name;

        SecurityAuditLog::record(
            action: 'STUDENT_DELETED',
            status: 'SUCCESS',
            severity: 'WARNING',
            targetType: 'STUDENT',
            targetId: (string) $id,
            details: ['name' => $studentName, 'nisn' => $student->nisn]
        );

        $student->delete();

        return redirect()->route('students.index')->with('success', "Data siswa {$studentName} berhasil dihapus.");
    }
}
