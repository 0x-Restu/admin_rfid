@extends('layouts.app')

@section('title', 'Terminal Gerbang')

@section('content')
<div class="space-y-6">

    <!-- Top KPIs -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200">
            <p class="text-[10px] font-semibold text-slate-400 uppercase">Hadir Hari Ini</p>
            <h3 class="text-xl font-extrabold text-slate-900 mt-0.5">{{ $totalHadirToday }}</h3>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200">
            <p class="text-[10px] font-semibold text-slate-400 uppercase">Tepat Waktu</p>
            <h3 class="text-xl font-extrabold text-emerald-600 mt-0.5">{{ $totalOnTimeToday }}</h3>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200">
            <p class="text-[10px] font-semibold text-slate-400 uppercase">Terlambat</p>
            <h3 class="text-xl font-extrabold text-amber-600 mt-0.5">{{ $totalLateToday }}</h3>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200">
            <p class="text-[10px] font-semibold text-slate-400 uppercase">Tap Pulang</p>
            <h3 class="text-xl font-extrabold text-slate-700 mt-0.5">{{ $totalPulangToday }}</h3>
        </div>
    </div>

    <!-- Terminal Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left: Sensor (7 cols) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 space-y-5">
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <label for="gate-selector" class="text-xs font-semibold text-slate-600">Lokasi:</label>
                        <select id="gate-selector" class="text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                            <option value="Gerbang Depan Utama">Gerbang Depan Utama</option>
                            <option value="Gerbang Belakang">Gerbang Belakang</option>
                        </select>
                    </div>

                    <div class="flex items-center space-x-1.5 text-xs text-emerald-600 font-semibold">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Aktif</span>
                    </div>
                </div>

                <!-- Radar Scan Area -->
                <div class="bg-slate-900 rounded-2xl p-8 flex flex-col items-center justify-center text-center relative overflow-hidden text-white">
                    <div class="relative w-28 h-28 flex items-center justify-center mb-3">
                        <div class="radar-beam bg-white/10"></div>
                        <div class="radar-beam bg-white/5"></div>
                        <div class="w-16 h-16 rounded-2xl bg-white text-slate-900 flex items-center justify-center text-2xl z-10 shadow-sm">
                            <i class="fa-solid fa-door-open"></i>
                        </div>
                    </div>

                    <h3 class="font-extrabold text-sm text-white">Tempelkan Kartu RFID</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Pencatatan masuk (Tap-In) & pulang (Tap-Out)</p>

                    <div class="mt-4 w-full max-w-xs">
                        <input type="text" id="gate-rfid-input" placeholder="Scan UID Kartu..." autofocus
                               class="w-full text-center text-xs font-mono uppercase bg-slate-800 text-white border border-slate-700 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-white focus:outline-none placeholder-slate-500"
                               onkeydown="if(event.key==='Enter') executeGateScan(this.value)">
                    </div>
                </div>

                <!-- Result Banner -->
                <div id="gate-result-banner" class="hidden p-4 rounded-xl bg-slate-900 text-white space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span id="gate-res-status" class="font-bold text-emerald-400">TAP IN</span>
                        <span id="gate-res-time" class="font-mono text-slate-400">--:--:--</span>
                    </div>

                    <div>
                        <h4 id="gate-res-name" class="text-base font-extrabold text-white">Nama Siswa</h4>
                        <p id="gate-res-class" class="text-xs text-slate-300 font-mono">Kelas &bull; NISN</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Right: Registered Students List (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
            
            <div class="bg-white rounded-2xl p-5 border border-slate-200">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-bold text-xs text-slate-900 uppercase tracking-wider">
                        Kartu Siswa Terdaftar
                    </h4>
                    <span class="text-[11px] text-slate-400">Klik untuk tap</span>
                </div>
                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                    @forelse($sampleStudents as $student)
                    @if($student->rfidCard)
                    <div onclick="executeGateScan('{{ $student->rfidCard->card_uid }}')"
                         class="p-2.5 rounded-xl border border-slate-200 hover:border-slate-400 transition cursor-pointer flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr($student->full_name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-xs text-slate-800">{{ $student->full_name }}</p>
                                <p class="text-[10px] text-slate-400 font-mono">{{ $student->class_name }} &bull; {{ $student->rfidCard->card_uid }}</p>
                            </div>
                        </div>
                        <div>
                            <span class="text-[10px] px-2 py-0.5 rounded font-bold {{ $student->rfidCard->status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                {{ $student->rfidCard->status }}
                            </span>
                        </div>
                    </div>
                    @endif
                    @empty
                    <p class="text-xs text-slate-400 text-center py-4">Belum ada siswa.</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Attendances -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200">
                <h4 class="font-bold text-xs text-slate-900 uppercase tracking-wider mb-3">
                    Presensi Terkini
                </h4>
                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                    @forelse($recentAttendances as $att)
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                        <div>
                            <p class="font-bold text-slate-800">{{ $att->student ? $att->student->full_name : 'Siswa' }}</p>
                            <p class="text-[10px] text-slate-400 font-mono">{{ $att->tap_in_time ? $att->tap_in_time->format('H:i') : '-' }} &bull; {{ $att->gate_name }}</p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $att->is_late ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $att->is_late ? 'Terlambat' : 'Tepat Waktu' }}
                            </span>
                            @if($att->tap_out_time)
                            <span class="block text-[9px] text-slate-400 mt-0.5">Pulang: {{ $att->tap_out_time->format('H:i') }}</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 text-center py-4">Belum ada presensi.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

@section('scripts')
<script>
    async function executeGateScan(uid) {
        if (!uid || !uid.trim()) return;
        const cleanUid = uid.trim().toUpperCase();
        const gate = document.getElementById('gate-selector').value;
        const inputElem = document.getElementById('gate-rfid-input');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch("{{ route('gate.tap') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json"
                },
                body: JSON.stringify({ card_uid: cleanUid, gate_name: gate })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                playRfidSound('error');
                alert(data.message || "Akses Ditolak.");
                if (inputElem) {
                    inputElem.value = "";
                    inputElem.focus();
                }
                return;
            }

            playRfidSound('success');
            const banner = document.getElementById('gate-result-banner');
            banner.classList.remove('hidden');

            document.getElementById('gate-res-status').innerText = data.status_message;
            document.getElementById('gate-res-time').innerText = data.time_str;
            document.getElementById('gate-res-name').innerText = data.student.full_name;
            document.getElementById('gate-res-class').innerText = `${data.student.class_name} • NISN: ${data.student.nisn}`;

            if (inputElem) {
                inputElem.value = "";
                inputElem.focus();
            }

            setTimeout(() => window.location.reload(), 2500);

        } catch (e) {
            playRfidSound('error');
            alert("Gagal memproses tap.");
        }
    }
</script>
@endsection
