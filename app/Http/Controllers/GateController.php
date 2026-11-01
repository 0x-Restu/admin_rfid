<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\RfidCard;
use App\Models\SecurityAuditLog;
use App\Models\Student;
use App\Services\SpreadsheetService;
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
        $sampleStudents = Student::with(['rfidCard', 'parent'])
            ->whereHas('rfidCard')
            ->take(10)
            ->get();

        $recentAttendances = Attendance::with(['student.parent', 'card'])
            ->whereDate('date', $today)
            ->latest('updated_at')
            ->take(15)
            ->get();

        $isPublicMonitor = false;

        return view('gate.terminal', compact(
            'totalHadirToday',
            'totalLateToday',
            'totalOnTimeToday',
            'totalPulangToday',
            'sampleStudents',
            'recentAttendances',
            'isPublicMonitor'
        ));
    }

    /**
     * Display Public Fullscreen Gate Monitor (No Login Required)
     */
    public function publicMonitor()
    {
        $today = Carbon::today()->toDateString();

        $totalHadirToday = Attendance::whereDate('date', $today)->count();
        $totalLateToday = Attendance::whereDate('date', $today)->where('is_late', true)->count();
        $totalOnTimeToday = $totalHadirToday - $totalLateToday;
        $totalPulangToday = Attendance::whereDate('date', $today)->whereNotNull('tap_out_time')->count();

        $sampleStudents = Student::with(['rfidCard', 'parent'])
            ->whereHas('rfidCard')
            ->take(10)
            ->get();

        $recentAttendances = Attendance::with(['student.parent', 'card'])
            ->whereDate('date', $today)
            ->latest('updated_at')
            ->take(15)
            ->get();

        $isPublicMonitor = true;

        return view('gate.terminal', compact(
            'totalHadirToday',
            'totalLateToday',
            'totalOnTimeToday',
            'totalPulangToday',
            'sampleStudents',
            'recentAttendances',
            'isPublicMonitor'
        ));
    }

    /**
     * Stream Natural Indonesian Voice TTS Audio (Cached Locally)
     */
    public function tts(Request $request)
    {
        $text = trim((string) $request->query('text', ''));
        if ($text === '') {
            abort(400);
        }

        $text = mb_substr($text, 0, 180);
        $cacheDir = storage_path('app/tts_cache');
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $hash = md5('id_natural_v1_' . $text);
        $cacheFile = $cacheDir . '/' . $hash . '.mp3';

        if (file_exists($cacheFile) && filesize($cacheFile) > 200) {
            return response()->file($cacheFile, [
                'Content-Type' => 'audio/mpeg',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        $url = 'https://translate.googleapis.com/translate_tts?client=gtx&ie=UTF-8&tl=id&ttsspeed=1&q=' . rawurlencode($text);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: audio/webm,audio/ogg,audio/wav,audio/*;q=0.9,*/*;q=0.8',
                'Accept-Language: id-ID,id;q=0.9',
            ],
        ]);

        $audioBytes = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $audioBytes && strlen($audioBytes) > 200) {
            @file_put_contents($cacheFile, $audioBytes);
            return response($audioBytes, 200, [
                'Content-Type' => 'audio/mpeg',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        abort(503);
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

        $card = RfidCard::with(['student.parent'])->where('card_uid', $uid)->first();

        if (!$card || !$card->student) {
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
                'student' => [
                    'full_name' => $card->student->full_name,
                    'nisn' => $card->student->nisn,
                    'gender' => $card->student->gender,
                    'gender_label' => $card->student->gender_label,
                    'class_name' => $card->student->class_name,
                    'school_name' => $card->student->school_name,
                    'photo_url' => $card->student->photo,
                    'parent_name' => $card->student->parent ? $card->student->parent->full_name : '-',
                ],
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
            $statusMessage = $isLate
                ? "TAP-IN MASUK (TERLAMBAT: {$now->format('H:i:s')} WIB)"
                : "TAP-IN MASUK (TEPAT WAKTU: {$now->format('H:i:s')} WIB)";

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
            $statusMessage = "TAP-OUT PULANG (Pukul {$now->format('H:i:s')} WIB)";

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
            'date_str' => $now->format('d/m/Y'),
            'tap_in_str' => $attendance->tap_in_time ? $attendance->tap_in_time->format('H:i:s') . ' WIB' : '-',
            'tap_out_str' => $attendance->tap_out_time ? $attendance->tap_out_time->format('H:i:s') . ' WIB' : '-',
            'gate_name' => $gateName,
            'student' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'nisn' => $student->nisn,
                'gender' => $student->gender,
                'gender_label' => $student->gender_label,
                'class_name' => $student->class_name,
                'school_name' => $student->school_name,
                'photo_url' => $student->photo,
                'parent_name' => $student->parent ? $student->parent->full_name : '-',
                'parent_phone' => $student->parent ? ($student->parent->phone_number ?? '-') : '-',
                'status' => $student->status,
            ],
            'card' => [
                'card_uid' => $card->card_uid,
                'status' => $card->status,
                'balance_formatted' => 'Rp ' . number_format($card->balance, 0, ',', '.'),
            ],
        ]);
    }

    /**
     * Build filtered query for attendance history and export
     */
    private function buildHistoryQuery(Request $request)
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

        return $query;
    }

    /**
     * Full Attendance History & Reports Table
     */
    public function history(Request $request)
    {
        $query = $this->buildHistoryQuery($request);
        $attendances = $query->paginate(20)->withQueryString();
        $classes = Student::select('class_name')->distinct()->pluck('class_name');

        return view('gate.history', compact('attendances', 'classes'));
    }

    /**
     * Export Attendance History to Spreadsheet (.xlsx or .csv)
     */
    public function export(Request $request)
    {
        $format = strtolower($request->query('format', 'xlsx')) === 'csv' ? 'csv' : 'xlsx';
        $attendances = $this->buildHistoryQuery($request)->get();

        $headers = [
            'No',
            'Tanggal',
            'NISN',
            'Nama Lengkap Siswa',
            'Jenis Kelamin',
            'Kelas',
            'Sekolah',
            'UID Kartu RFID',
            'Tap Masuk (WIB)',
            'Tap Pulang (WIB)',
            'Lokasi Gerbang',
            'Status Kedisiplinan',
            'Foto URL',
        ];

        $rows = [];
        foreach ($attendances as $idx => $att) {
            $st = $att->student;
            $rows[] = [
                $idx + 1,
                $att->date ? $att->date->format('Y-m-d') : '-',
                $st ? $st->nisn : '-',
                $st ? $st->full_name : '-',
                $st ? $st->gender_label : '-',
                $st ? $st->class_name : '-',
                $st ? $st->school_name : '-',
                $att->card ? $att->card->card_uid : '-',
                $att->tap_in_time ? $att->tap_in_time->format('H:i:s') : '-',
                $att->tap_out_time ? $att->tap_out_time->format('H:i:s') : '-',
                $att->gate_name,
                $att->is_late ? 'TERLAMBAT' : 'TEPAT WAKTU',
                $st ? $st->photo : '',
            ];
        }

        return SpreadsheetService::download(
            'Rekap_Presensi_Gerbang_' . date('Ymd_His'),
            $headers,
            $rows,
            $format,
            'Rekap Presensi'
        );
    }
}
