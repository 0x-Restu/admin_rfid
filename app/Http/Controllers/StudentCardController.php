<?php

namespace App\Http\Controllers;

use App\Models\CardTopup;
use App\Models\Parents;
use App\Models\RfidCard;
use App\Models\SecurityAuditLog;
use App\Models\Student;
use App\Services\SpreadsheetService;
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
        $query = $this->buildFilteredQuery($request);

        $students = $query->paginate(15)->withQueryString();
        $classes = Student::select('class_name')->distinct()->orderBy('class_name')->pluck('class_name');

        $classCounts = Student::select('class_name', DB::raw('count(*) as total'))
            ->groupBy('class_name')
            ->orderBy('class_name')
            ->get();

        $studentsByClass = Student::select('id', 'full_name', 'nisn', 'class_name')
            ->orderBy('class_name')
            ->orderBy('full_name')
            ->get()
            ->groupBy('class_name');

        $activeCardsCount = RfidCard::where('status', 'ACTIVE')->count();
        $blockedCardsCount = RfidCard::where('status', 'BLOCKED')->count();
        $unactivatedCardsCount = Student::whereDoesntHave('rfidCard')->count();
        $totalBalanceSum = RfidCard::sum('balance');

        return view('students.index', compact(
            'students',
            'classes',
            'classCounts',
            'studentsByClass',
            'activeCardsCount',
            'blockedCardsCount',
            'unactivatedCardsCount',
            'totalBalanceSum'
        ));
    }

    /**
     * Build filtered query for index and spreadsheet export
     */
    private function buildFilteredQuery(Request $request)
    {
        $query = Student::with(['parent', 'rfidCard'])->latest();

        if ($request->filled('class_name')) {
            $query->where('class_name', $request->class_name);
        }

        if ($request->filled('card_status')) {
            if ($request->card_status === 'UNACTIVATED') {
                $query->whereDoesntHave('rfidCard');
            } else {
                $query->whereHas('rfidCard', function ($q) use ($request) {
                    $q->where('status', $request->card_status);
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('class_name', 'like', "%{$search}%")
                    ->orWhereHas('rfidCard', function ($rq) use ($search) {
                        $rq->where('card_uid', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * Helper to save uploaded student photo file to public/uploads/students
     */
    private function handlePhotoUpload(Request $request, string $nisn, ?string $fallbackUrl = null): string
    {
        if ($request->hasFile('photo_file') && $request->file('photo_file')->isValid()) {
            $file = $request->file('photo_file');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $cleanNisn = preg_replace('/[^a-zA-Z0-9_-]/', '', $nisn);
            $filename = 'student_' . $cleanNisn . '_' . time() . '.' . $ext;
            $uploadDir = public_path('uploads/students');

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $file->move($uploadDir, $filename);
            return 'uploads/students/' . $filename;
        }

        if (!empty($fallbackUrl)) {
            return trim($fallbackUrl);
        }

        return '';
    }

    /**
     * Store new student and optionally assign RFID card (can be left blank for later activation)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'nisn' => ['required', 'string', 'max:30', 'unique:students,nisn'],
            'gender' => ['nullable', 'in:L,P'],
            'class_name' => ['required', 'string', 'max:50'],
            'school_name' => ['nullable', 'string', 'max:100'],
            'photo_url' => ['nullable', 'string', 'max:500'],
            'photo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'card_uid' => ['nullable', 'string', 'max:50', 'unique:rfid_cards,card_uid'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
            'daily_limit' => ['nullable', 'numeric', 'min:5000'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            $photoPath = $this->handlePhotoUpload($request, $validated['nisn'], $validated['photo_url'] ?? null);
            $cardUid = !empty($validated['card_uid']) ? strtoupper(trim($validated['card_uid'])) : '';

            DB::transaction(function () use ($validated, $photoPath, $cardUid) {
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
                    'gender' => $validated['gender'] ?? 'L',
                    'class_name' => trim($validated['class_name']),
                    'school_name' => !empty($validated['school_name']) ? $validated['school_name'] : 'SMA Negeri 1 Prestasi',
                    'photo_url' => $photoPath ?: null,
                    'status' => 'ACTIVE',
                ]);

                $initialBalance = (float) ($validated['initial_balance'] ?? 0);
                $dailyLimit = (float) ($validated['daily_limit'] ?? 25000);

                // Only create RFID Card if card_uid was provided; otherwise status stays "Belum Aktivasi"
                if ($cardUid !== '') {
                    $card = RfidCard::create([
                        'student_id' => $student->id,
                        'card_uid' => $cardUid,
                        'status' => 'ACTIVE',
                        'balance' => $initialBalance,
                        'daily_spending_limit' => $dailyLimit,
                        'issued_at' => now(),
                    ]);

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
                }

                SecurityAuditLog::record(
                    action: 'STUDENT_REGISTERED',
                    status: 'SUCCESS',
                    severity: 'INFO',
                    targetType: 'STUDENT',
                    targetId: (string) $student->id,
                    details: [
                        'student_name' => $student->full_name,
                        'nisn' => $student->nisn,
                        'card_uid' => $cardUid ?: 'BELUM_AKTIVASI',
                        'has_photo' => !empty($photoPath),
                    ]
                );
            });

            $msg = $cardUid !== ''
                ? "Data siswa {$validated['full_name']} beserta Smart Card RFID berhasil ditambahkan!"
                : "Data siswa {$validated['full_name']} berhasil ditambahkan (Status Kartu: Belum Aktivasi).";

            return redirect()->route('students.index')->with('success', $msg);
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['general' => "Gagal menambahkan siswa: " . $e->getMessage()]);
        }
    }

    /**
     * Activate & Pair RFID Card for an unactivated student
     */
    public function activateCard(Request $request, $id)
    {
        $student = Student::with('rfidCard')->findOrFail($id);

        $validated = $request->validate([
            'card_uid' => ['required', 'string', 'max:50'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
            'daily_limit' => ['nullable', 'numeric', 'min:5000'],
        ]);

        $cleanUid = strtoupper(trim($validated['card_uid']));

        $uidUsed = RfidCard::where('card_uid', $cleanUid)
            ->where('student_id', '!=', $student->id)
            ->exists();

        if ($uidUsed) {
            return back()->withErrors(['general' => "Gagal aktivasi: Nomor RFID [{$cleanUid}] sudah terdaftar pada siswa lain!"]);
        }

        try {
            DB::transaction(function () use ($student, $cleanUid, $validated) {
                $initialBalance = (float) ($validated['initial_balance'] ?? 0);
                $dailyLimit = (float) ($validated['daily_limit'] ?? 25000);

                if ($student->rfidCard) {
                    $student->rfidCard->update([
                        'card_uid' => $cleanUid,
                        'status' => 'ACTIVE',
                        'daily_spending_limit' => $dailyLimit,
                        'issued_at' => $student->rfidCard->issued_at ?: now(),
                    ]);
                    $card = $student->rfidCard;
                } else {
                    $card = RfidCard::create([
                        'student_id' => $student->id,
                        'card_uid' => $cleanUid,
                        'status' => 'ACTIVE',
                        'balance' => $initialBalance,
                        'daily_spending_limit' => $dailyLimit,
                        'issued_at' => now(),
                    ]);

                    if ($initialBalance > 0) {
                        CardTopup::create([
                            'card_id' => $card->id,
                            'amount' => $initialBalance,
                            'balance_before' => 0,
                            'balance_after' => $initialBalance,
                            'method' => 'CASH',
                            'receipt_number' => 'TOP-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                            'processed_by' => Auth::id(),
                            'notes' => 'Saldo awal saat Aktivasi Kartu RFID',
                        ]);
                    }
                }

                SecurityAuditLog::record(
                    action: 'STUDENT_RFID_CARD_ACTIVATED',
                    status: 'SUCCESS',
                    severity: 'INFO',
                    targetType: 'RFID_CARD',
                    targetId: $cleanUid,
                    details: [
                        'student_id' => $student->id,
                        'student_name' => $student->full_name,
                        'nisn' => $student->nisn,
                        'class_name' => $student->class_name,
                        'initial_balance' => $initialBalance,
                    ]
                );
            });

            return redirect()->route('students.index')->with(
                'success',
                "Kartu RFID [{$cleanUid}] berhasil diaktivasi untuk siswa {$student->full_name} ({$student->class_name})!"
            );
        } catch (Exception $e) {
            return back()->withErrors(['general' => 'Gagal mengaktivasi kartu: ' . $e->getMessage()]);
        }
    }

    /**
     * Promote / Move Class (Naik Kelas / Pindah Kelas Satu Angkatan atau Per Kelas)
     * Example: "X RPL A" -> "XI RPL B"
     */
    public function promoteClass(Request $request)
    {
        $mode = $request->input('mode', 'single');

        try {
            if ($mode === 'batch') {
                $mappings = $request->input('mappings', []);
                if (!is_array($mappings) || empty($mappings)) {
                    return back()->withErrors(['general' => 'Tidak ada pemetaan kelas yang diisi.']);
                }

                // 1. Snapshot student IDs per source class first to prevent chain overwrite collisions (e.g. X -> XI and XI -> XII)
                $plan = [];
                foreach ($mappings as $sourceClass => $targetClass) {
                    $sourceClass = trim((string) $sourceClass);
                    $targetClass = trim((string) $targetClass);

                    if ($sourceClass === '' || $targetClass === '' || $sourceClass === $targetClass) {
                        continue;
                    }

                    $ids = Student::where('class_name', $sourceClass)->pluck('id')->all();
                    if (!empty($ids)) {
                        $plan[] = [
                            'from' => $sourceClass,
                            'to' => $targetClass,
                            'ids' => $ids,
                        ];
                    }
                }

                if (empty($plan)) {
                    return back()->withErrors(['general' => 'Masukkan minimal satu kelas tujuan baru yang berbeda dari kelas asal.']);
                }

                $totalMoved = 0;
                $summaryLog = [];

                DB::transaction(function () use ($plan, &$totalMoved, &$summaryLog) {
                    foreach ($plan as $item) {
                        $count = count($item['ids']);
                        if (strtoupper($item['to']) === 'LULUS' || strtoupper($item['to']) === 'GRADUATED') {
                            Student::whereIn('id', $item['ids'])->update([
                                'class_name' => 'ALUMNI (' . $item['from'] . ')',
                                'status' => 'GRADUATED',
                            ]);
                        } else {
                            Student::whereIn('id', $item['ids'])->update([
                                'class_name' => $item['to'],
                            ]);
                        }
                        $totalMoved += $count;
                        $summaryLog[] = "{$item['from']} → {$item['to']} ({$count} siswa)";
                    }

                    SecurityAuditLog::record(
                        action: 'BATCH_CLASS_PROMOTION',
                        status: 'SUCCESS',
                        severity: 'INFO',
                        targetType: 'STUDENT',
                        targetId: 'BATCH_PROMOTE',
                        details: [
                            'total_students_moved' => $totalMoved,
                            'transitions' => $summaryLog,
                        ]
                    );
                });

                return redirect()->route('students.index')->with(
                    'success',
                    "Naik/Pindah Kelas Angkatan Berhasil! Total {$totalMoved} siswa dipindahkan: " . implode(', ', $summaryLog)
                );
            }

            // Single Class / Selected Students Mode (e.g., X RPL A -> XI RPL B)
            $validated = $request->validate([
                'source_class' => ['required', 'string', 'max:50'],
                'target_class' => ['required', 'string', 'max:50'],
                'student_ids' => ['nullable', 'array'],
                'student_ids.*' => ['string'],
            ]);

            $sourceClass = trim($validated['source_class']);
            $targetClass = trim($validated['target_class']);

            if (strcasecmp($sourceClass, $targetClass) === 0) {
                return back()->withErrors(['general' => 'Kelas tujuan baru harus berbeda dengan kelas asal.']);
            }

            $query = Student::where('class_name', $sourceClass);
            if (!empty($validated['student_ids'])) {
                $query->whereIn('id', $validated['student_ids']);
            }

            $studentIds = $query->pluck('id')->all();
            if (empty($studentIds)) {
                return back()->withErrors(['general' => "Tidak ada siswa yang dipilih dari kelas {$sourceClass}."]);
            }

            $movedCount = count($studentIds);

            DB::transaction(function () use ($studentIds, $sourceClass, $targetClass, $movedCount) {
                if (strtoupper($targetClass) === 'LULUS' || strtoupper($targetClass) === 'GRADUATED') {
                    Student::whereIn('id', $studentIds)->update([
                        'class_name' => 'ALUMNI (' . $sourceClass . ')',
                        'status' => 'GRADUATED',
                    ]);
                } else {
                    Student::whereIn('id', $studentIds)->update([
                        'class_name' => $targetClass,
                    ]);
                }

                SecurityAuditLog::record(
                    action: 'SINGLE_CLASS_PROMOTION',
                    status: 'SUCCESS',
                    severity: 'INFO',
                    targetType: 'STUDENT',
                    targetId: $sourceClass . '_TO_' . $targetClass,
                    details: [
                        'from_class' => $sourceClass,
                        'to_class' => $targetClass,
                        'students_moved' => $movedCount,
                    ]
                );
            });

            return redirect()->route('students.index', ['class_name' => $targetClass])->with(
                'success',
                "Berhasil memindahkan {$movedCount} siswa dari kelas [{$sourceClass}] ke kelas baru [{$targetClass}]!"
            );
        } catch (Exception $e) {
            return back()->withErrors(['general' => 'Gagal memproses pindah/naik kelas: ' . $e->getMessage()]);
        }
    }

    /**
     * Update student identity, photo, and RFID card details
     */
    public function update(Request $request, $id)
    {
        $student = Student::with(['rfidCard', 'parent'])->findOrFail($id);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'nisn' => ['required', 'string', 'max:30', 'unique:students,nisn,' . $student->id],
            'gender' => ['nullable', 'in:L,P'],
            'class_name' => ['required', 'string', 'max:50'],
            'school_name' => ['nullable', 'string', 'max:100'],
            'photo_url' => ['nullable', 'string', 'max:500'],
            'photo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'card_uid' => ['nullable', 'string', 'max:50'],
            'daily_limit' => ['nullable', 'numeric', 'min:5000'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            $newPhotoPath = $this->handlePhotoUpload($request, $validated['nisn'], $validated['photo_url'] ?? $student->photo_url);

            DB::transaction(function () use ($student, $validated, $newPhotoPath) {
                $cleanUid = !empty($validated['card_uid']) ? strtoupper(trim($validated['card_uid'])) : '';

                if ($cleanUid !== '') {
                    $existingCard = RfidCard::where('card_uid', $cleanUid)
                        ->where('student_id', '!=', $student->id)
                        ->first();

                    if ($existingCard) {
                        throw new Exception("UID Kartu [{$cleanUid}] sudah digunakan oleh siswa lain.");
                    }
                }

                // Parent update or create
                $parentId = $student->parent_id;
                if (!empty($validated['parent_name'])) {
                    if ($student->parent) {
                        $student->parent->update([
                            'full_name' => $validated['parent_name'],
                            'phone_number' => $validated['parent_phone'] ?? $student->parent->phone_number,
                        ]);
                    } else {
                        $parent = Parents::create([
                            'full_name' => $validated['parent_name'],
                            'phone_number' => $validated['parent_phone'] ?? null,
                            'email' => $validated['nisn'] . '@sekolah.id',
                        ]);
                        $parentId = $parent->id;
                    }
                }

                $student->update([
                    'parent_id' => $parentId,
                    'nisn' => $validated['nisn'],
                    'full_name' => $validated['full_name'],
                    'gender' => $validated['gender'] ?? $student->gender,
                    'class_name' => trim($validated['class_name']),
                    'school_name' => !empty($validated['school_name']) ? $validated['school_name'] : $student->school_name,
                    'photo_url' => $newPhotoPath ?: $student->photo_url,
                ]);

                if ($cleanUid !== '') {
                    if ($student->rfidCard) {
                        $student->rfidCard->update([
                            'card_uid' => $cleanUid,
                            'daily_spending_limit' => (float) ($validated['daily_limit'] ?? $student->rfidCard->daily_spending_limit),
                        ]);
                    } else {
                        RfidCard::create([
                            'student_id' => $student->id,
                            'card_uid' => $cleanUid,
                            'status' => 'ACTIVE',
                            'balance' => 0,
                            'daily_spending_limit' => (float) ($validated['daily_limit'] ?? 25000),
                            'issued_at' => now(),
                        ]);
                    }
                } elseif ($student->rfidCard && isset($validated['daily_limit'])) {
                    $student->rfidCard->update([
                        'daily_spending_limit' => (float) $validated['daily_limit'],
                    ]);
                }

                SecurityAuditLog::record(
                    action: 'STUDENT_AND_PHOTO_UPDATED',
                    status: 'SUCCESS',
                    severity: 'INFO',
                    targetType: 'STUDENT',
                    targetId: (string) $student->id,
                    details: [
                        'student_name' => $student->full_name,
                        'nisn' => $student->nisn,
                        'class_name' => $student->class_name,
                        'card_uid' => $cleanUid ?: 'BELUM_AKTIVASI',
                    ]
                );
            });

            return redirect()->route('students.index')->with('success', "Identitas & foto siswa {$validated['full_name']} berhasil diperbarui!");
        } catch (Exception $e) {
            return back()->withErrors(['general' => "Gagal memperbarui data siswa: " . $e->getMessage()]);
        }
    }

    /**
     * Export Students & Cards to Spreadsheet (.xlsx or .csv) including photo URL
     */
    public function export(Request $request)
    {
        $format = strtolower($request->query('format', 'xlsx')) === 'csv' ? 'csv' : 'xlsx';
        $students = $this->buildFilteredQuery($request)->get();

        $headers = [
            'No',
            'NISN',
            'Nama Lengkap',
            'Jenis Kelamin (L/P)',
            'Kelas',
            'Sekolah',
            'UID Kartu RFID',
            'Status Kartu',
            'Saldo Kartu (Rp)',
            'Limit Harian (Rp)',
            'Nama Orang Tua',
            'No HP Orang Tua',
            'Foto URL / Path',
        ];

        $rows = [];
        foreach ($students as $idx => $st) {
            $rows[] = [
                $idx + 1,
                $st->nisn,
                $st->full_name,
                $st->gender,
                $st->class_name,
                $st->school_name,
                $st->rfidCard ? $st->rfidCard->card_uid : '',
                $st->rfidCard ? $st->rfidCard->status : 'BELUM AKTIVASI',
                $st->rfidCard ? (float) $st->rfidCard->balance : 0,
                $st->rfidCard ? (float) $st->rfidCard->daily_spending_limit : 25000,
                $st->parent ? $st->parent->full_name : '',
                $st->parent ? ($st->parent->phone_number ?? '') : '',
                $st->photo,
            ];
        }

        $filename = 'Data_Siswa_Kartu_RFID_' . date('Ymd_His');

        return SpreadsheetService::download($filename, $headers, $rows, $format, 'Data Siswa RFID');
    }

    /**
     * Download Spreadsheet Template (.xlsx or .csv) for Importing Students & Photos
     * Note: card_uid can be left blank during initial import ("Belum Aktivasi")
     */
    public function downloadTemplate(Request $request)
    {
        $format = strtolower($request->query('format', 'xlsx')) === 'csv' ? 'csv' : 'xlsx';

        $headers = [
            'nisn',
            'full_name',
            'gender',
            'class_name',
            'school_name',
            'card_uid',
            'initial_balance',
            'daily_limit',
            'parent_name',
            'parent_phone',
            'photo_url',
        ];

        $sampleRows = [
            [
                '0081122334',
                'Ahmad Fauzan Ramadhan',
                'L',
                'X RPL A',
                'SMK Negeri 1 Nusantara',
                '', // Kosongkan jika kartu RFID belum diaktivasi saat import awal
                0,
                25000,
                'Bapak Hendra Ramadhan',
                '081234567891',
                'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=400&q=80',
            ],
            [
                '0081122335',
                'Siti Aisyah Putri',
                'P',
                'X RPL A',
                'SMK Negeri 1 Nusantara',
                '', // Kosongkan jika kartu RFID belum diaktivasi saat import awal
                0,
                25000,
                'Ibu Ratna Sari',
                '081298765432',
                'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=400&q=80',
            ],
        ];

        return SpreadsheetService::download(
            'Template_Import_Siswa_EduCard',
            $headers,
            $sampleRows,
            $format,
            'Template Import Siswa'
        );
    }

    /**
     * Import Students & Optional RFID Cards from Spreadsheet (.xlsx or .csv) + Optional Batch Photos
     * If card_uid is empty in spreadsheet, student is saved with status "Belum Aktivasi" (no RfidCard yet).
     */
    public function import(Request $request)
    {
        $request->validate([
            'spreadsheet_file' => ['required', 'file', 'max:10240'],
            'photo_files.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        try {
            $file = $request->file('spreadsheet_file');
            $ext = strtolower($file->getClientOriginalExtension());

            if (!in_array($ext, ['xlsx', 'csv', 'txt'])) {
                return back()->withErrors(['general' => 'Format file harus berupa Spreadsheet (.xlsx atau .csv).']);
            }

            $rows = SpreadsheetService::parseUploadedFile($file->getRealPath(), $ext);

            if (empty($rows)) {
                return back()->withErrors(['general' => 'File spreadsheet kosong atau format kolom tidak sesuai.']);
            }

            // Map any uploaded photo files by base filename
            $uploadedPhotosMap = [];
            if ($request->hasFile('photo_files')) {
                $uploadDir = public_path('uploads/students');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                foreach ($request->file('photo_files') as $photoFile) {
                    if ($photoFile && $photoFile->isValid()) {
                        $origName = $photoFile->getClientOriginalName();
                        $baseName = strtolower(pathinfo($origName, PATHINFO_FILENAME));
                        $photoExt = strtolower($photoFile->getClientOriginalExtension() ?: 'jpg');
                        $savedName = 'student_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $baseName) . '_' . time() . '_' . Str::random(3) . '.' . $photoExt;
                        $photoFile->move($uploadDir, $savedName);
                        $relPath = 'uploads/students/' . $savedName;

                        $uploadedPhotosMap[$baseName] = $relPath;
                        $uploadedPhotosMap[strtolower($origName)] = $relPath;
                    }
                }
            }

            $importedCount = 0;
            $updatedCount = 0;
            $unactivatedCount = 0;

            DB::transaction(function () use ($rows, $uploadedPhotosMap, &$importedCount, &$updatedCount, &$unactivatedCount) {
                foreach ($rows as $row) {
                    $nisn = trim($row['nisn'] ?? '');
                    $fullName = trim($row['full_name'] ?? $row['nama_lengkap'] ?? $row['nama_siswa'] ?? $row['nama'] ?? '');

                    if ($nisn === '' || $fullName === '') {
                        continue;
                    }

                    $genderRaw = strtoupper(trim($row['gender'] ?? $row['jenis_kelamin_l_p'] ?? $row['jenis_kelamin'] ?? $row['l_p'] ?? 'L'));
                    $gender = (str_starts_with($genderRaw, 'P') || str_starts_with($genderRaw, 'F') || str_starts_with($genderRaw, 'W')) ? 'P' : 'L';

                    $className = trim($row['class_name'] ?? $row['kelas'] ?? 'X RPL A') ?: 'X RPL A';
                    $schoolName = trim($row['school_name'] ?? $row['sekolah'] ?? 'SMA Negeri 1 Prestasi') ?: 'SMA Negeri 1 Prestasi';

                    // Keep card_uid empty if not provided in spreadsheet!
                    $cardUid = strtoupper(trim($row['card_uid'] ?? $row['uid_kartu_rfid'] ?? $row['uid_kartu'] ?? $row['uid'] ?? $row['no_rfid'] ?? ''));
                    if ($cardUid === '-' || $cardUid === 'BELUM AKTIVASI' || $cardUid === 'UNASSIGNED') {
                        $cardUid = '';
                    }

                    $balanceRaw = preg_replace('/[^0-9.]/', '', (string) ($row['initial_balance'] ?? $row['saldo_kartu_rp'] ?? $row['saldo_awal'] ?? $row['saldo'] ?? '0'));
                    $limitRaw = preg_replace('/[^0-9.]/', '', (string) ($row['daily_limit'] ?? $row['limit_harian_rp'] ?? $row['limit_harian'] ?? '25000'));

                    $initialBalance = (float) ($balanceRaw !== '' ? $balanceRaw : 0);
                    $dailyLimit = (float) ($limitRaw !== '' ? $limitRaw : 25000);
                    if ($dailyLimit < 5000) {
                        $dailyLimit = 25000;
                    }

                    $parentName = trim($row['parent_name'] ?? $row['nama_orang_tua'] ?? $row['orang_tua'] ?? '');
                    $parentPhone = trim($row['parent_phone'] ?? $row['no_hp_orang_tua'] ?? $row['hp_orang_tua'] ?? '');
                    $photoCol = trim($row['photo_url'] ?? $row['foto_url_path'] ?? $row['foto_url'] ?? $row['foto'] ?? '');

                    // Resolve photo
                    $resolvedPhoto = null;
                    $nisnKey = strtolower($nisn);
                    $uidKey = strtolower($cardUid);
                    $photoColKey = strtolower($photoCol);
                    $photoColBase = strtolower(pathinfo($photoCol, PATHINFO_FILENAME));

                    if (isset($uploadedPhotosMap[$nisnKey])) {
                        $resolvedPhoto = $uploadedPhotosMap[$nisnKey];
                    } elseif ($uidKey !== '' && isset($uploadedPhotosMap[$uidKey])) {
                        $resolvedPhoto = $uploadedPhotosMap[$uidKey];
                    } elseif ($photoColKey !== '' && isset($uploadedPhotosMap[$photoColKey])) {
                        $resolvedPhoto = $uploadedPhotosMap[$photoColKey];
                    } elseif ($photoColBase !== '' && isset($uploadedPhotosMap[$photoColBase])) {
                        $resolvedPhoto = $uploadedPhotosMap[$photoColBase];
                    } elseif ($photoCol !== '') {
                        $resolvedPhoto = $photoCol;
                    }

                    $existingStudent = Student::with(['rfidCard', 'parent'])->where('nisn', $nisn)->first();

                    if ($existingStudent) {
                        $parentId = $existingStudent->parent_id;
                        if ($parentName !== '') {
                            if ($existingStudent->parent) {
                                $existingStudent->parent->update([
                                    'full_name' => $parentName,
                                    'phone_number' => $parentPhone ?: $existingStudent->parent->phone_number,
                                ]);
                            } else {
                                $newParent = Parents::create([
                                    'full_name' => $parentName,
                                    'phone_number' => $parentPhone ?: null,
                                    'email' => $nisn . '@sekolah.id',
                                ]);
                                $parentId = $newParent->id;
                            }
                        }

                        $existingStudent->update([
                            'parent_id' => $parentId,
                            'full_name' => $fullName,
                            'gender' => $gender,
                            'class_name' => $className,
                            'school_name' => $schoolName,
                            'photo_url' => $resolvedPhoto ?: $existingStudent->photo_url,
                        ]);

                        // Only create/update RFID card if card_uid is present in spreadsheet
                        if ($cardUid !== '') {
                            $uidTaken = RfidCard::where('card_uid', $cardUid)
                                ->where('student_id', '!=', $existingStudent->id)
                                ->exists();

                            if ($existingStudent->rfidCard) {
                                $existingStudent->rfidCard->update([
                                    'card_uid' => $uidTaken ? $existingStudent->rfidCard->card_uid : $cardUid,
                                    'daily_spending_limit' => $dailyLimit,
                                ]);
                            } elseif (!$uidTaken) {
                                RfidCard::create([
                                    'student_id' => $existingStudent->id,
                                    'card_uid' => $cardUid,
                                    'status' => 'ACTIVE',
                                    'balance' => $initialBalance,
                                    'daily_spending_limit' => $dailyLimit,
                                    'issued_at' => now(),
                                ]);
                            }
                        } elseif (!$existingStudent->rfidCard) {
                            $unactivatedCount++;
                        }

                        $updatedCount++;
                    } else {
                        $parentId = null;
                        if ($parentName !== '') {
                            $newParent = Parents::create([
                                'full_name' => $parentName,
                                'phone_number' => $parentPhone ?: null,
                                'email' => $nisn . '@sekolah.id',
                            ]);
                            $parentId = $newParent->id;
                        }

                        $newStudent = Student::create([
                            'parent_id' => $parentId,
                            'nisn' => $nisn,
                            'full_name' => $fullName,
                            'gender' => $gender,
                            'class_name' => $className,
                            'school_name' => $schoolName,
                            'photo_url' => $resolvedPhoto,
                            'status' => 'ACTIVE',
                        ]);

                        // Only create RFID Card if card_uid was filled in spreadsheet; otherwise leave as "Belum Aktivasi"
                        if ($cardUid !== '' && !RfidCard::where('card_uid', $cardUid)->exists()) {
                            $card = RfidCard::create([
                                'student_id' => $newStudent->id,
                                'card_uid' => $cardUid,
                                'status' => 'ACTIVE',
                                'balance' => $initialBalance,
                                'daily_spending_limit' => $dailyLimit,
                                'issued_at' => now(),
                            ]);

                            if ($initialBalance > 0) {
                                CardTopup::create([
                                    'card_id' => $card->id,
                                    'amount' => $initialBalance,
                                    'balance_before' => 0,
                                    'balance_after' => $initialBalance,
                                    'method' => 'CASH',
                                    'receipt_number' => 'TOP-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                                    'processed_by' => Auth::id(),
                                    'notes' => 'Saldo awal dari Import Spreadsheet',
                                ]);
                            }
                        } else {
                            $unactivatedCount++;
                        }

                        $importedCount++;
                    }
                }

                SecurityAuditLog::record(
                    action: 'STUDENTS_SPREADSHEET_IMPORTED',
                    status: 'SUCCESS',
                    severity: 'INFO',
                    targetType: 'STUDENT',
                    targetId: 'BATCH_IMPORT',
                    details: [
                        'imported_new' => $importedCount,
                        'updated_existing' => $updatedCount,
                        'unactivated_rfid_count' => $unactivatedCount,
                    ]
                );
            });

            if ($importedCount === 0 && $updatedCount === 0) {
                return back()->withErrors(['general' => 'Tidak ada baris valid yang ditemukan. Pastikan kolom "nisn" dan "full_name" terisi.']);
            }

            $extraNote = $unactivatedCount > 0
                ? " ({$unactivatedCount} siswa berstatus Belum Aktivasi RFID — klik tombol 'Belum Aktivasi' untuk scan kartu)."
                : "";

            return redirect()->route('students.index')->with(
                'success',
                "Import Spreadsheet berhasil! {$importedCount} siswa baru ditambahkan dan {$updatedCount} data diperbarui.{$extraNote}"
            );
        } catch (Exception $e) {
            return back()->withErrors(['general' => 'Gagal mengimpor spreadsheet: ' . $e->getMessage()]);
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
