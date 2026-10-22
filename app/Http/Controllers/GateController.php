<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\RfidCard;
use App\Models\SecurityAuditLog;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GateController extends Controller
{
    /**
     * Display Live Gate Presensi Terminal
     */
    public function index()
    {
        $today = Carbon::today()->toDateString();

        $totalHadirToday = Attendance::whereDate('date', $today)->count();
        $totalLateToday = Attendance::whereDate('date', $today)->where('is_late', true)->count();
        $totalOnTimeToday = $totalHadirToday - $totalLateToday;
        $totalPulangToday = Attendance::whereDate('date', $today)->whereNotNull('tap_out_time')->count();

        // Sample students for instant gate tap testing
        $sampleStudents = Student::with('rfidCard')
            ->whereHas('rfidCard')
            ->take(8)
            ->get();

        $recentAttendances = Attendance::with('student', 'card')
            ->whereDate('date', $today)
            ->latest('updated_at')
            ->take(15)
            ->get();

        return view('gate.terminal', compact(
            'totalHadirToday',
            'totalLateToday',
            'totalOnTimeToday',
            'totalPulangToday',
            'sampleStudents',
            'recentAttendances'
        ));
    }

    /**
     * Handle RFID Gate Tap (Hardware or Web Scanner)
     */
    public function tap(Request $request)
    {
        $request->validate([
            'card_uid' => ['required', 'string', 'max:50'],
            'gate_name' => ['nullable', 'string', 'max:100'],
        ]);

        $uid = strtoupper(trim($request->input('card_uid')));
        $gateName = $request->input('gate_name') ?: 'Gerbang Depan Utama';

        $card = RfidCard::with('student')->where('card_uid', $uid)->first();

        if (!$card) {
            SecurityAuditLog::record(
                action: 'GATE_UNREGISTERED_CARD_TAP',
                status: 'FAILED',
                severity: 'WARNING',
                targetType: 'RFID_CARD',
                targetId: $uid,
                details: ['gate' => $gateName]
            );

            return response()->json([
                'success' => false,
                'message' => "Kartu RFID UID [{$uid}] tidak terdaftar di sistem!",
            ], 404);
        }

        if ($card->status !== 'ACTIVE') {
            SecurityAuditLog::record(
                action: 'GATE_BLOCKED_CARD_TAP',
                status: 'BLOCKED',
                severity: 'CRITICAL',
                targetType: 'RFID_CARD',
                targetId: $uid,
                details: [
                    'student_name' => $card->student->full_name,
                    'card_status' => $card->status,
                    'gate' => $gateName,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => "AKSES DITOLAK: Kartu berstatus '{$card->status}'! " . ($card->blocked_reason ? "({$card->blocked_reason})" : "Hubungi Bagian Kesiswaan."),
                'card_status' => $card->status,
            ], 403);
        }

        $student = $card->student;
        $now = Carbon::now();
        $today = $now->toDateString();

        // Late policy: After 07:00 AM WIB is marked as Late
        $lateThreshold = Carbon::createFromTime(7, 0, 0);
        $isLate = $now->greaterThan($lateThreshold);

        // Check if attendance already exists for today
        $attendance = Attendance::where('student_id', $student->id)
            ->whereDate('date', $today)
            ->first();

        $actionType = '';
        $statusMessage = '';

        if (!$attendance) {
            // TAP IN
            $attendance = Attendance::create([
                'student_id' => $student->id,
                'card_id' => $card->id,
                'date' => $today,
                'tap_in_time' => $now,
                'tap_out_time' => null,
                'gate_name' => $gateName,
                'status' => 'HADIR',
                'is_late' => $isLate,
            ]);

            $actionType = 'TAP_IN';
            $statusMessage = $isLate ? "TAP-IN (TERLAMBAT: Masuk Pukul {$now->format('H:i:s')} WIB)" : "TAP-IN (TEPAT WAKTU: Masuk Pukul {$now->format('H:i:s')} WIB)";

            SecurityAuditLog::record(
                action: 'GATE_TAP_IN',
                status: 'SUCCESS',
                severity: 'INFO',
                targetType: 'ATTENDANCE',
                targetId: (string) $attendance->id,
                details: [
                    'student_name' => $student->full_name,
                    'nisn' => $student->nisn,
                    'is_late' => $isLate,
                    'gate' => $gateName,
                ]
            );
        } else {
            // Already tapped in, now TAP OUT (Pulang)
            $attendance->update([
                'tap_out_time' => $now,
            ]);

            $actionType = 'TAP_OUT';
            $statusMessage = "TAP-OUT (PULANG: Pukul {$now->format('H:i:s')} WIB)";

            SecurityAuditLog::record(
                action: 'GATE_TAP_OUT',
                status: 'SUCCESS',
                severity: 'INFO',
                targetType: 'ATTENDANCE',
                targetId: (string) $attendance->id,
                details: [
                    'student_name' => $student->full_name,
                    'nisn' => $student->nisn,
                    'gate' => $gateName,
                ]
            );
        }

        // Update card last tapped
        $card->update(['last_tapped_at' => $now]);

        return response()->json([
            'success' => true,
            'action_type' => $actionType,
            'is_late' => $attendance->is_late,
            'status_message' => $statusMessage,
            'time_str' => $now->format('H:i:s') . ' WIB',
            'gate_name' => $gateName,
            'student' => [
                'full_name' => $student->full_name,
                'nisn' => $student->nisn,
                'class_name' => $student->class_name,
                'school_name' => $student->school_name,
                'photo_url' => $student->photo,
            ],
            'card' => [
                'card_uid' => $card->card_uid,
                'balance_formatted' => 'Rp ' . number_format($card->balance, 0, ',', '.'),
            ],
        ]);
    }

    /**
     * Full Attendance History & Reports Table
     */
    public function history(Request $request)
    {
        $query = Attendance::with(['student.parent', 'card'])->latest('date');

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('class_name')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('class_name', $request->class_name);
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'LATE') {
                $query->where('is_late', true);
            } elseif ($request->status === 'ONTIME') {
                $query->where('is_late', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $attendances = $query->paginate(20)->withQueryString();
        $classes = Student::select('class_name')->distinct()->pluck('class_name');

        return view('gate.history', compact('attendances', 'classes'));
    }
}
