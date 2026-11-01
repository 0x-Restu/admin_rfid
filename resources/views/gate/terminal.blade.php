@extends('layouts.app')

@section('title', 'Monitor Gerbang RFID')

@section('styles')
<style>
    /* Mode Monitor Fullscreen: Clean Light Mode (Biru & Putih) */
    body.gate-monitor-mode {
        background-color: #eff6ff !important;
        color: #1e3a8a !important;
    }
    body.gate-monitor-mode #main-sidebar,
    body.gate-monitor-mode header,
    body.gate-monitor-mode footer {
        display: none !important;
    }
    body.gate-monitor-mode main {
        padding: 0 !important;
        background-color: #eff6ff !important;
    }
    body.gate-monitor-mode #gate-terminal-wrapper {
        min-height: 100vh;
        background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
        padding: 1.5rem 2rem;
    }
    @keyframes popupScaleIn {
        0% { opacity: 0; transform: scale(0.92); }
        100% { opacity: 1; transform: scale(1); }
    }
    .popup-animate {
        animation: popupScaleIn 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
</style>
@endsection

@section('content')
@php
    $latestAtt = $recentAttendances->first();
    $isPublic = !empty($isPublicMonitor);
    $tapEndpoint = $isPublic ? route('gate.monitor.tap') : route('gate.tap');
@endphp
<div id="gate-terminal-wrapper" class="space-y-6 transition-all duration-200">

    <!-- Top Monitor Bar (Blue & White Light Theme) -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-4 sm:p-5 rounded-2xl border border-blue-200 shadow-sm">
        <div class="flex items-center space-x-3.5">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-xl shadow-sm flex-shrink-0">
                <i class="fa-solid fa-id-card-clip"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h2 class="font-extrabold text-blue-950 text-base sm:text-lg tracking-tight">
                        Monitor Presensi Gerbang RFID
                    </h2>
                    <span class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-600 text-white">
                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                        <span>LIVE MONITOR</span>
                    </span>
                </div>
                <p id="live-date-clock" class="text-xs text-blue-600 font-semibold font-mono mt-0.5">
                    Memuat waktu...
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Gate Selector -->
            <div class="flex items-center space-x-2 bg-blue-50 px-3 py-2 rounded-xl border border-blue-200">
                <i class="fa-solid fa-location-dot text-blue-600 text-xs"></i>
                <select id="gate-selector" class="text-xs font-bold bg-transparent text-blue-900 focus:outline-none">
                    <option value="Gerbang Depan Utama">Gerbang Depan Utama</option>
                    <option value="Gerbang Belakang">Gerbang Belakang</option>
                </select>
            </div>

            <!-- Voice Toggle Button -->
            <button type="button" id="btn-voice-toggle" onclick="toggleVoiceAnnouncement()"
                    class="px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs font-bold transition flex items-center space-x-1.5"
                    title="Aktifkan/Matikan Suara Panggilan Bahasa Indonesia">
                <i id="icon-voice-toggle" class="fa-solid fa-volume-high"></i>
                <span id="text-voice-toggle">Suara: Aktif</span>
            </button>

            <!-- Simulator Drawer Toggle -->
            <button type="button" onclick="toggleSimulatorPanel()"
                    class="px-3.5 py-2 rounded-xl bg-white hover:bg-blue-50 text-blue-700 border border-blue-200 text-xs font-bold transition flex items-center space-x-1.5">
                <i class="fa-solid fa-id-badge"></i>
                <span>Simulasi Tap</span>
            </button>

            <!-- Copy Public No-Login Link -->
            <button type="button" onclick="copyPublicMonitorLink()"
                    class="px-3.5 py-2 rounded-xl bg-white hover:bg-blue-50 text-blue-700 border border-blue-200 text-xs font-bold transition flex items-center space-x-1.5"
                    title="Salin Link Khusus Monitor Gerbang Tanpa Login">
                <i class="fa-solid fa-link"></i>
                <span id="copy-link-label">Link Tanpa Login</span>
            </button>

            @auth
            <!-- Rekap Link (Hidden in Fullscreen Mode) -->
            <a href="{{ route('gate.history') }}" id="btn-rekap-link"
               class="px-3.5 py-2 rounded-xl bg-white hover:bg-blue-50 text-blue-700 border border-blue-200 text-xs font-bold transition flex items-center space-x-1.5 {{ $isPublic ? 'hidden' : '' }}">
                <i class="fa-solid fa-table-list"></i>
                <span>Rekap & Export</span>
            </a>
            @endauth

            <!-- Fullscreen Monitor Toggle Button -->
            <button type="button" id="btn-fullscreen-monitor" onclick="toggleMonitorFullscreen()"
                    class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold transition flex items-center space-x-2 shadow-sm">
                <i id="icon-fullscreen-monitor" class="fa-solid fa-expand"></i>
                <span id="text-fullscreen-monitor">Mode Monitor Full</span>
            </button>
        </div>
    </div>

    <!-- Top KPIs (Blue & White Light Theme) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-blue-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-wider">Hadir Hari Ini</p>
                <h3 id="kpi-hadir" class="text-2xl font-extrabold text-blue-950 mt-0.5">{{ $totalHadirToday }}</h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-sm shadow-sm">
                <i class="fa-solid fa-user-check"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-blue-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-wider">Tepat Waktu</p>
                <h3 id="kpi-ontime" class="text-2xl font-extrabold text-blue-700 mt-0.5">{{ $totalOnTimeToday }}</h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-sm">
                <i class="fa-solid fa-clock"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-blue-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-wider">Catatan Terlambat</p>
                <h3 id="kpi-late" class="text-2xl font-extrabold text-blue-600 mt-0.5">{{ $totalLateToday }}</h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center text-sm">
                <i class="fa-solid fa-bell"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-blue-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-wider">Tap Pulang</p>
                <h3 id="kpi-pulang" class="text-2xl font-extrabold text-blue-800 mt-0.5">{{ $totalPulangToday }}</h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-sm">
                <i class="fa-solid fa-house-chimney-user"></i>
            </div>
        </div>
    </div>

    <!-- Collapsible Quick Simulator Bar (Hidden in Fullscreen Monitor Mode unless clicked) -->
    <div id="simulator-panel" class="bg-white rounded-2xl p-4 border border-blue-200 shadow-sm {{ $isPublic ? 'hidden' : '' }}">
        <div class="flex items-center justify-between mb-2.5">
            <h4 class="font-extrabold text-xs text-blue-950 uppercase tracking-wider flex items-center gap-1.5">
                <i class="fa-solid fa-id-card text-blue-600"></i>
                <span>Daftar Kartu Siswa Terdaftar (Klik Kartu untuk Simulasi Scan & Suara)</span>
            </h4>
            <button type="button" onclick="toggleSimulatorPanel()" class="text-xs font-bold text-blue-600 hover:text-blue-800">
                Tutup Panel
            </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2.5">
            @forelse($sampleStudents as $student)
            @if($student->rfidCard)
            <div onclick="executeGateScan('{{ $student->rfidCard->card_uid }}')"
                 class="p-2.5 rounded-xl border border-blue-100 hover:border-blue-600 hover:bg-blue-50/70 transition cursor-pointer flex items-center space-x-2.5 bg-blue-50/30">
                <img src="{{ $student->photo }}"
                     alt="{{ $student->full_name }}"
                     onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($student->full_name) }}&background=2563eb&color=ffffff&size=128&bold=true';"
                     class="w-10 h-12 rounded-lg object-cover border border-blue-200 bg-white flex-shrink-0">
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-xs text-blue-950 truncate">{{ $student->full_name }}</p>
                    <p class="text-[10px] text-blue-600 font-mono truncate">{{ $student->class_name }} &bull; {{ $student->rfidCard->card_uid }}</p>
                </div>
            </div>
            @endif
            @empty
            <p class="text-xs text-blue-400 col-span-5 text-center py-2">Belum ada siswa terdaftar.</p>
            @endforelse
        </div>
    </div>

    <!-- Main Monitor Display Grid (Blue & White Clean Layout) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left: Scanner & Active Student Identity Display (7 cols) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-white rounded-2xl p-5 border border-blue-200 shadow-sm space-y-5">

                <!-- Blue & White Radar Scan Area -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-6 text-white shadow-sm">
                    <div class="flex items-center space-x-5">
                        <div class="relative w-20 h-20 flex items-center justify-center flex-shrink-0">
                            <div class="radar-beam bg-white/25"></div>
                            <div class="radar-beam bg-white/15"></div>
                            <div class="w-14 h-14 rounded-2xl bg-white text-blue-600 flex items-center justify-center text-2xl z-10 shadow-md">
                                <i class="fa-solid fa-wifi"></i>
                            </div>
                        </div>
                        <div class="text-left">
                            <span class="px-2 py-0.5 rounded bg-white/20 text-[10px] font-extrabold uppercase tracking-widest text-white">
                                Sensor RFID Siap
                            </span>
                            <h3 class="font-extrabold text-base sm:text-lg text-white mt-1">Tempelkan Kartu Pelajar RFID</h3>
                            <p class="text-xs text-blue-100 mt-0.5">Pop-up identitas, foto & sapaan suara otomatis tampil saat scan</p>
                        </div>
                    </div>

                    <div class="w-full sm:w-56 flex-shrink-0">
                        <input type="text" id="gate-rfid-input" placeholder="Scan UID Kartu..." autofocus
                               class="w-full text-center text-xs font-mono font-bold uppercase bg-white text-blue-950 border-2 border-blue-300 rounded-xl px-3 py-3 focus:ring-2 focus:ring-white focus:outline-none placeholder-blue-300 shadow-inner"
                               onkeydown="if(event.key==='Enter') executeGateScan(this.value)">
                    </div>
                </div>

                <!-- KARTU IDENTITAS & FOTO SISWA TERAKHIR DI MONITOR (BIRU & PUTIH) -->
                <div id="gate-result-banner" class="{{ $latestAtt && $latestAtt->student ? '' : 'hidden' }} rounded-2xl border-2 border-blue-500 bg-white overflow-hidden shadow-sm transition-all">
                    <!-- Banner Header -->
                    <div class="bg-blue-600 px-5 py-3 text-white flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div class="flex items-center space-x-2.5">
                            <span class="px-2.5 py-1 rounded-lg bg-white text-blue-700 font-extrabold text-[11px] uppercase tracking-wider" id="gate-res-action-badge">
                                {{ $latestAtt ? ($latestAtt->tap_out_time ? 'TAP-OUT PULANG' : 'TAP-IN MASUK') : 'IDENTITAS PRESENSI' }}
                            </span>
                            <span id="gate-res-status" class="font-bold text-blue-50">
                                @if($latestAtt)
                                    {{ $latestAtt->is_late ? 'TERCATAT TERLAMBAT' : 'HADIR TEPAT WAKTU' }}
                                @else
                                    TERVERIFIKASI
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center space-x-2 font-mono text-xs text-blue-100">
                            <span id="gate-res-gate">{{ $latestAtt ? $latestAtt->gate_name : 'Gerbang Depan Utama' }}</span>
                            <span>&bull;</span>
                            <span id="gate-res-time">{{ $latestAtt && $latestAtt->tap_in_time ? $latestAtt->tap_in_time->format('H:i:s') . ' WIB' : '--:--:--' }}</span>
                        </div>
                    </div>

                    <!-- Identity Body with Large Student Photo -->
                    <div class="p-5 sm:p-6 flex flex-col sm:flex-row items-center sm:items-start gap-6 bg-white">
                        <div class="relative flex-shrink-0">
                            <img id="gate-res-photo"
                                 src="{{ $latestAtt && $latestAtt->student ? $latestAtt->student->photo : '' }}"
                                 alt="Foto Siswa"
                                 onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=Siswa&background=2563eb&color=ffffff&size=256&bold=true';"
                                 class="w-32 h-40 sm:w-36 sm:h-44 rounded-2xl object-cover border-4 border-blue-500 shadow-md bg-blue-50">
                            <span id="gate-res-discipline-badge"
                                  class="absolute -bottom-2.5 left-1/2 -translate-x-1/2 whitespace-nowrap px-3.5 py-0.5 rounded-full text-[11px] font-extrabold shadow bg-blue-600 text-white">
                                {{ $latestAtt && $latestAtt->is_late ? 'TERLAMBAT' : 'TEPAT WAKTU' }}
                            </span>
                        </div>

                        <div class="flex-1 min-w-0 w-full text-center sm:text-left space-y-3">
                            <div>
                                <p class="text-[11px] font-extrabold uppercase tracking-wider text-blue-600">Identitas Siswa Terverifikasi</p>
                                <h4 id="gate-res-name" class="text-xl sm:text-2xl font-extrabold text-blue-950 leading-tight mt-0.5">
                                    {{ $latestAtt && $latestAtt->student ? $latestAtt->student->full_name : '-' }}
                                </h4>
                                <p id="gate-res-school" class="text-xs text-blue-600 font-medium">
                                    {{ $latestAtt && $latestAtt->student ? $latestAtt->student->school_name : '-' }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 pt-2 border-t border-blue-100 text-xs">
                                <div class="bg-blue-50/70 p-2.5 rounded-xl border border-blue-100">
                                    <p class="text-[10px] text-blue-500 uppercase font-bold">NISN</p>
                                    <p id="gate-res-nisn" class="font-mono font-extrabold text-blue-950 text-sm">
                                        {{ $latestAtt && $latestAtt->student ? $latestAtt->student->nisn : '-' }}
                                    </p>
                                </div>
                                <div class="bg-blue-50/70 p-2.5 rounded-xl border border-blue-100">
                                    <p class="text-[10px] text-blue-500 uppercase font-bold">Kelas</p>
                                    <p id="gate-res-class" class="font-extrabold text-blue-950 text-sm">
                                        {{ $latestAtt && $latestAtt->student ? $latestAtt->student->class_name : '-' }}
                                    </p>
                                </div>
                                <div class="bg-blue-50/70 p-2.5 rounded-xl border border-blue-100">
                                    <p class="text-[10px] text-blue-500 uppercase font-bold">Gender</p>
                                    <p id="gate-res-gender" class="font-extrabold text-blue-950 text-sm">
                                        {{ $latestAtt && $latestAtt->student ? $latestAtt->student->gender_label : '-' }}
                                    </p>
                                </div>
                                <div class="bg-blue-50/70 p-2.5 rounded-xl border border-blue-100">
                                    <p class="text-[10px] text-blue-500 uppercase font-bold">UID Kartu</p>
                                    <p id="gate-res-uid" class="font-mono font-extrabold text-blue-600 text-sm">
                                        {{ $latestAtt && $latestAtt->card ? $latestAtt->card->card_uid : '-' }}
                                    </p>
                                </div>
                                <div class="bg-blue-50/70 p-2.5 rounded-xl border border-blue-100">
                                    <p class="text-[10px] text-blue-500 uppercase font-bold">Masuk / Pulang</p>
                                    <p id="gate-res-tap-times" class="font-mono font-extrabold text-blue-950 text-xs">
                                        {{ $latestAtt && $latestAtt->tap_in_time ? $latestAtt->tap_in_time->format('H:i') : '-' }}
                                        /
                                        {{ $latestAtt && $latestAtt->tap_out_time ? $latestAtt->tap_out_time->format('H:i') : '-' }}
                                    </p>
                                </div>
                                <div class="bg-blue-50/70 p-2.5 rounded-xl border border-blue-100">
                                    <p class="text-[10px] text-blue-500 uppercase font-bold">Orang Tua / Wali</p>
                                    <p id="gate-res-parent" class="font-bold text-blue-900 truncate text-xs">
                                        {{ $latestAtt && $latestAtt->student && $latestAtt->student->parent ? $latestAtt->student->parent->full_name : '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State if no attendance yet today -->
                <div id="gate-waiting-placeholder" class="{{ $latestAtt && $latestAtt->student ? 'hidden' : '' }} py-12 text-center border-2 border-dashed border-blue-200 bg-blue-50/30 rounded-2xl">
                    <i class="fa-solid fa-id-card text-4xl text-blue-300 mb-2 block"></i>
                    <p class="font-extrabold text-sm text-blue-900">Menunggu Siswa Tap Kartu RFID</p>
                    <p class="text-xs text-blue-500 mt-0.5">Identitas dan foto siswa akan tampil secara penuh di layar ini</p>
                </div>

            </div>
        </div>

        <!-- Right: Live Attendance Feed (5 cols, Blue & White) -->
        <div class="lg:col-span-5">
            <div class="bg-white rounded-2xl p-5 border border-blue-200 shadow-sm h-full flex flex-col">
                <div class="flex items-center justify-between mb-3 pb-2.5 border-b border-blue-100">
                    <h4 class="font-extrabold text-xs text-blue-950 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-blue-600"></i>
                        <span>Log Presensi Terkini (Live Feed)</span>
                    </h4>
                    <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 font-bold text-[10px]">Hari Ini</span>
                </div>
                <div id="recent-attendances-list" class="space-y-2.5 max-h-[460px] overflow-y-auto pr-1 flex-1">
                    @forelse($recentAttendances as $att)
                    <div class="p-3 rounded-xl bg-blue-50/50 border border-blue-100 flex items-center justify-between text-xs gap-3">
                        <div class="flex items-center space-x-3 min-w-0">
                            <img src="{{ $att->student ? $att->student->photo : '' }}"
                                 alt="{{ $att->student ? $att->student->full_name : 'Siswa' }}"
                                 onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($att->student ? $att->student->full_name : 'Siswa') }}&background=2563eb&color=ffffff&size=128&bold=true';"
                                 class="w-11 h-14 rounded-xl object-cover border border-blue-200 bg-white flex-shrink-0">
                            <div class="min-w-0">
                                <p class="font-extrabold text-blue-950 text-sm truncate">{{ $att->student ? $att->student->full_name : 'Siswa' }}</p>
                                <p class="text-[11px] text-blue-700 font-mono">
                                    {{ $att->student ? $att->student->class_name : '-' }} &bull; NISN: {{ $att->student ? $att->student->nisn : '-' }}
                                </p>
                                <p class="text-[10px] text-blue-500 font-mono mt-0.5">
                                    Masuk: {{ $att->tap_in_time ? $att->tap_in_time->format('H:i:s') : '-' }}
                                    @if($att->tap_out_time)
                                    &bull; Pulang: {{ $att->tap_out_time->format('H:i:s') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold {{ $att->is_late ? 'bg-blue-100 text-blue-800 border border-blue-300' : 'bg-blue-600 text-white' }}">
                                {{ $att->is_late ? 'Terlambat' : 'Tepat Waktu' }}
                            </span>
                            @if($att->tap_out_time)
                            <span class="block text-[10px] font-extrabold text-blue-600 mt-1">SUDAH PULANG</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p id="empty-attendance-msg" class="text-xs text-blue-400 text-center py-8">Belum ada presensi hari ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    <!-- ==================== POP-UP MODAL SAAT SCAN ABSENSI BERHASIL (TEMA BIRU & PUTIH) ==================== -->
    <div id="gate-scan-popup" onclick="closeGatePopup()" class="fixed inset-0 z-50 bg-blue-950/40 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div onclick="event.stopPropagation()" class="popup-animate bg-white rounded-3xl max-w-xl w-full overflow-hidden border-2 border-blue-500 shadow-2xl">
            
            <!-- Top Status Banner (Blue & White) -->
            <div id="popup-header-bar" class="bg-blue-600 px-6 py-4 text-white flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-11 h-11 rounded-2xl bg-white text-blue-600 flex items-center justify-center text-xl shadow-sm">
                        <i id="popup-header-icon" class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <span id="popup-action-type" class="text-[11px] font-extrabold uppercase tracking-widest text-blue-100 block">
                            ABSENSI MASUK BERHASIL
                        </span>
                        <h3 id="popup-status-title" class="text-lg font-extrabold leading-tight text-white">
                            Selamat Datang! Presensi Tercatat
                        </h3>
                    </div>
                </div>
                <button type="button" onclick="closeGatePopup()" class="w-8 h-8 rounded-xl bg-white/15 hover:bg-white/25 flex items-center justify-center text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Main Student Photo & Identity Content -->
            <div class="p-6 sm:p-7 flex flex-col sm:flex-row items-center gap-6 bg-white text-blue-950">
                <div class="relative flex-shrink-0">
                    <img id="popup-student-photo"
                         src=""
                         alt="Foto Siswa"
                         onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=Siswa&background=2563eb&color=ffffff&size=256&bold=true';"
                         class="w-36 h-44 rounded-2xl object-cover border-4 border-blue-600 shadow-lg bg-blue-50">
                    <span id="popup-discipline-pill"
                          class="absolute -bottom-3 left-1/2 -translate-x-1/2 whitespace-nowrap px-4 py-1 rounded-full text-xs font-extrabold shadow-md bg-blue-600 text-white">
                        TEPAT WAKTU
                    </span>
                </div>

                <div class="flex-1 min-w-0 w-full text-center sm:text-left space-y-3">
                    <div>
                        <span class="px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-extrabold text-[10px] uppercase tracking-wider">
                            Identitas Siswa Terverifikasi
                        </span>
                        <h2 id="popup-student-name" class="text-2xl font-extrabold text-blue-950 mt-1.5 leading-snug">
                            Nama Lengkap Siswa
                        </h2>
                        <p id="popup-student-school" class="text-xs text-blue-600 font-semibold">
                            SMA Negeri 1 Prestasi
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5 pt-2 border-t border-blue-100 text-xs">
                        <div class="bg-blue-50/70 p-2.5 rounded-xl border border-blue-100">
                            <p class="text-[10px] text-blue-500 uppercase font-bold">NISN</p>
                            <p id="popup-student-nisn" class="font-mono font-extrabold text-blue-950 text-sm">-</p>
                        </div>
                        <div class="bg-blue-50/70 p-2.5 rounded-xl border border-blue-100">
                            <p class="text-[10px] text-blue-500 uppercase font-bold">Kelas</p>
                            <p id="popup-student-class" class="font-extrabold text-blue-950 text-sm">-</p>
                        </div>
                        <div class="bg-blue-50/70 p-2.5 rounded-xl border border-blue-100">
                            <p class="text-[10px] text-blue-500 uppercase font-bold">Waktu Tap</p>
                            <p id="popup-tap-time" class="font-mono font-extrabold text-blue-700 text-sm">--:--:-- WIB</p>
                        </div>
                        <div class="bg-blue-50/70 p-2.5 rounded-xl border border-blue-100">
                            <p class="text-[10px] text-blue-500 uppercase font-bold">UID Kartu RFID</p>
                            <p id="popup-card-uid" class="font-mono font-extrabold text-blue-600 text-sm">-</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-blue-500 font-semibold pt-1">
                        <span id="popup-gate-location"><i class="fa-solid fa-location-dot mr-1 text-blue-600"></i>Gerbang Depan Utama</span>
                        <span id="popup-parent-info"><i class="fa-solid fa-user-shield mr-1 text-blue-600"></i>Wali: -</span>
                    </div>
                </div>
            </div>

            <!-- Auto-close Footer -->
            <div class="bg-blue-50 px-6 py-2.5 border-t border-blue-100 flex items-center justify-between text-[11px] text-blue-600 font-semibold">
                <span><i class="fa-solid fa-volume-high mr-1"></i>Sapaan suara Bahasa Indonesia aktif</span>
                <span>Menutup otomatis...</span>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    let popupTimer = null;
    let kpiHadirCount = {{ (int) $totalHadirToday }};
    let kpiOntimeCount = {{ (int) $totalOnTimeToday }};
    let kpiLateCount = {{ (int) $totalLateToday }};
    let kpiPulangCount = {{ (int) $totalPulangToday }};
    const isPublicMonitor = {{ $isPublic ? 'true' : 'false' }};
    const publicMonitorUrl = "{{ route('gate.monitor') }}";

    // Automatically activate Clean Light Mode Monitor if accessed via /gate-monitor
    if (isPublicMonitor) {
        document.body.classList.add('gate-monitor-mode');
        const icon = document.getElementById('icon-fullscreen-monitor');
        const text = document.getElementById('text-fullscreen-monitor');
        if (icon) icon.className = "fa-solid fa-expand";
        if (text) text.innerText = "Layar Penuh (F11)";
    }

    // Copy Public No-Login Monitor Link
    function copyPublicMonitorLink() {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(publicMonitorUrl).then(() => {
                const lbl = document.getElementById('copy-link-label');
                lbl.innerText = "Link Tersalin!";
                setTimeout(() => {
                    lbl.innerText = "Link Tanpa Login";
                }, 2000);
            });
        } else {
            prompt("Salin link khusus Monitor Gerbang (Tanpa Login):", publicMonitorUrl);
        }
    }

    // Live Digital Clock for Monitor Mode
    function updateGateClock() {
        const now = new Date();
        const dateOpts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const dateStr = now.toLocaleDateString('id-ID', dateOpts);
        const timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
        const el = document.getElementById('live-date-clock');
        if (el) {
            el.innerText = `${dateStr} • ${timeStr} WIB`;
        }
    }
    setInterval(updateGateClock, 1000);
    updateGateClock();

    // Keep RFID Input focused when clicking background
    document.addEventListener('click', function (e) {
        const tag = (e.target.tagName || '').toLowerCase();
        if (tag !== 'select' && tag !== 'button' && tag !== 'input' && tag !== 'a') {
            const inputElem = document.getElementById('gate-rfid-input');
            if (inputElem) inputElem.focus();
        }
    });

    // Toggle Voice Announcement On/Off
    function toggleVoiceAnnouncement() {
        window.educardVoiceEnabled = !window.educardVoiceEnabled;
        const btn = document.getElementById('btn-voice-toggle');
        const icon = document.getElementById('icon-voice-toggle');
        const text = document.getElementById('text-voice-toggle');

        if (window.educardVoiceEnabled) {
            btn.className = "px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs font-bold transition flex items-center space-x-1.5";
            icon.className = "fa-solid fa-volume-high";
            text.innerText = "Suara: Aktif";
            speakVoice("Sapaan suara bahasa Indonesia diaktifkan.");
        } else {
            if (window.educardCurrentAudio) {
                window.educardCurrentAudio.pause();
            }
            if ('speechSynthesis' in window) window.speechSynthesis.cancel();
            btn.className = "px-3.5 py-2 rounded-xl bg-white hover:bg-blue-50 text-blue-400 border border-blue-200 text-xs font-bold transition flex items-center space-x-1.5";
            icon.className = "fa-solid fa-volume-xmark";
            text.innerText = "Suara: Nonaktif";
        }
    }

    // Toggle Quick Simulator Panel
    function toggleSimulatorPanel() {
        const panel = document.getElementById('simulator-panel');
        panel.classList.toggle('hidden');
    }

    // Toggle Clean Fullscreen Monitor Mode (Light Mode - Biru & Putih)
    function toggleMonitorFullscreen() {
        const icon = document.getElementById('icon-fullscreen-monitor');
        const text = document.getElementById('text-fullscreen-monitor');
        const simPanel = document.getElementById('simulator-panel');
        const rekapLink = document.getElementById('btn-rekap-link');

        if (isPublicMonitor) {
            // In public mode, body is always clean light mode; button toggles native browser fullscreen
            if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen().catch(() => {});
                icon.className = "fa-solid fa-compress";
                text.innerText = "Keluar Layar Penuh";
            } else if (document.fullscreenElement && document.exitFullscreen) {
                document.exitFullscreen().catch(() => {});
                icon.className = "fa-solid fa-expand";
                text.innerText = "Layar Penuh (F11)";
            }
            return;
        }

        const isMonitor = document.body.classList.toggle('gate-monitor-mode');

        if (isMonitor) {
            icon.className = "fa-solid fa-compress";
            text.innerText = "Keluar Mode Full";
            simPanel.classList.add('hidden');
            if (rekapLink) rekapLink.classList.add('hidden');

            if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen().catch(() => {});
            }
        } else {
            icon.className = "fa-solid fa-expand";
            text.innerText = "Mode Monitor Full";
            simPanel.classList.remove('hidden');
            if (rekapLink) rekapLink.classList.remove('hidden');

            if (document.fullscreenElement && document.exitFullscreen) {
                document.exitFullscreen().catch(() => {});
            }
        }

        const inputElem = document.getElementById('gate-rfid-input');
        if (inputElem) inputElem.focus();
    }

    document.addEventListener('fullscreenchange', () => {
        if (!isPublicMonitor && !document.fullscreenElement && document.body.classList.contains('gate-monitor-mode')) {
            toggleMonitorFullscreen();
        }
    });

    function showGatePopup(data) {
        if (popupTimer) clearTimeout(popupTimer);

        const popup = document.getElementById('gate-scan-popup');
        const headerBar = document.getElementById('popup-header-bar');
        const actionType = document.getElementById('popup-action-type');
        const statusTitle = document.getElementById('popup-status-title');
        const discPill = document.getElementById('popup-discipline-pill');

        const isTapOut = data.action_type === 'TAP_OUT';
        const isLate = !!data.is_late;

        // Strictly Blue & White theme in all pop-up states
        headerBar.className = "bg-blue-600 px-6 py-4 text-white flex items-center justify-between";

        if (isTapOut) {
            actionType.innerText = "ABSENSI PULANG BERHASIL";
            statusTitle.innerText = `Sampai Jumpa, ${data.student.full_name}!`;
            discPill.innerText = "TAP PULANG";
            discPill.className = "absolute -bottom-3 left-1/2 -translate-x-1/2 whitespace-nowrap px-4 py-1 rounded-full text-xs font-extrabold shadow-md bg-blue-700 text-white";
        } else if (isLate) {
            actionType.innerText = "ABSENSI MASUK (TERLAMBAT)";
            statusTitle.innerText = `Absensi Berhasil, ${data.student.full_name}`;
            discPill.innerText = "TERLAMBAT";
            discPill.className = "absolute -bottom-3 left-1/2 -translate-x-1/2 whitespace-nowrap px-4 py-1 rounded-full text-xs font-extrabold shadow-md bg-blue-500 text-white";
        } else {
            actionType.innerText = "ABSENSI MASUK BERHASIL";
            statusTitle.innerText = `Absensi Berhasil, ${data.student.full_name}!`;
            discPill.innerText = "TEPAT WAKTU";
            discPill.className = "absolute -bottom-3 left-1/2 -translate-x-1/2 whitespace-nowrap px-4 py-1 rounded-full text-xs font-extrabold shadow-md bg-blue-600 text-white";
        }

        document.getElementById('popup-student-photo').src = data.student.photo_url;
        document.getElementById('popup-student-name').innerText = data.student.full_name;
        document.getElementById('popup-student-school').innerText = `${data.student.school_name} • ${data.student.gender_label || ''}`;
        document.getElementById('popup-student-nisn').innerText = data.student.nisn;
        document.getElementById('popup-student-class').innerText = data.student.class_name;
        document.getElementById('popup-tap-time').innerText = data.time_str;
        document.getElementById('popup-card-uid').innerText = data.card.card_uid;
        document.getElementById('popup-gate-location').innerHTML = `<i class="fa-solid fa-location-dot mr-1 text-blue-600"></i>${data.gate_name}`;
        document.getElementById('popup-parent-info').innerHTML = `<i class="fa-solid fa-user-shield mr-1 text-blue-600"></i>Wali: ${data.student.parent_name || '-'}`;

        popup.classList.remove('hidden');

        popupTimer = setTimeout(() => {
            closeGatePopup();
        }, 3800);
    }

    function closeGatePopup() {
        if (popupTimer) clearTimeout(popupTimer);
        document.getElementById('gate-scan-popup').classList.add('hidden');
        const inputElem = document.getElementById('gate-rfid-input');
        if (inputElem) inputElem.focus();
    }

    async function executeGateScan(uid) {
        if (!uid || !uid.trim()) return;
        const cleanUid = uid.trim().toUpperCase();
        const gate = document.getElementById('gate-selector').value;
        const inputElem = document.getElementById('gate-rfid-input');

        if (inputElem) {
            inputElem.value = "";
            inputElem.focus();
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch("{{ $tapEndpoint }}", {
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
                const failVoice = data.student
                    ? `Mohon maaf, kartu atas nama ${data.student.full_name} tidak dapat digunakan.`
                    : `Mohon maaf, kartu tidak terdaftar di sistem.`;
                speakVoice(failVoice);
                alert(data.message || "Akses Ditolak.");
                return;
            }

            // 1. Play Chime & Speak Natural Indonesian Greeting
            playRfidSound('success');
            if (data.action_type === 'TAP_OUT') {
                speakVoice(`Absensi pulang berhasil, ${data.student.full_name}. Hati-hati di jalan.`);
                kpiPulangCount++;
                document.getElementById('kpi-pulang').innerText = kpiPulangCount;
            } else {
                const lateNote = data.is_late ? ', tercatat terlambat.' : '. Selamat belajar.';
                speakVoice(`Absensi berhasil, ${data.student.full_name}${lateNote}`);
                kpiHadirCount++;
                document.getElementById('kpi-hadir').innerText = kpiHadirCount;
                if (data.is_late) {
                    kpiLateCount++;
                    document.getElementById('kpi-late').innerText = kpiLateCount;
                } else {
                    kpiOntimeCount++;
                    document.getElementById('kpi-ontime').innerText = kpiOntimeCount;
                }
            }

            // 2. Show Automatic Pop-up Modal (Blue & White Theme)
            showGatePopup(data);

            // 3. Update Persistent Identity Banner on Monitor
            const placeholder = document.getElementById('gate-waiting-placeholder');
            if (placeholder) placeholder.classList.add('hidden');

            const banner = document.getElementById('gate-result-banner');
            banner.classList.remove('hidden');

            document.getElementById('gate-res-action-badge').innerText = data.action_type === 'TAP_OUT' ? 'TAP-OUT PULANG' : 'TAP-IN MASUK';
            document.getElementById('gate-res-status').innerText = data.status_message;
            document.getElementById('gate-res-time').innerText = data.time_str;
            document.getElementById('gate-res-gate').innerText = data.gate_name;

            document.getElementById('gate-res-photo').src = data.student.photo_url;
            document.getElementById('gate-res-name').innerText = data.student.full_name;
            document.getElementById('gate-res-school').innerText = data.student.school_name;
            document.getElementById('gate-res-nisn').innerText = data.student.nisn;
            document.getElementById('gate-res-class').innerText = data.student.class_name;
            document.getElementById('gate-res-gender').innerText = data.student.gender_label || data.student.gender;
            document.getElementById('gate-res-uid').innerText = data.card.card_uid;
            document.getElementById('gate-res-tap-times').innerText = `${data.tap_in_str.replace(' WIB', '')} / ${data.tap_out_str.replace(' WIB', '')}`;
            document.getElementById('gate-res-parent').innerText = data.student.parent_name || '-';

            const discBadge = document.getElementById('gate-res-discipline-badge');
            discBadge.innerText = data.is_late ? 'TERLAMBAT' : 'TEPAT WAKTU';

            // 4. Prepend to Live Feed (Blue & White)
            const listElem = document.getElementById('recent-attendances-list');
            const emptyMsg = document.getElementById('empty-attendance-msg');
            if (emptyMsg) emptyMsg.remove();

            const itemHtml = `
                <div class="p-3 rounded-xl bg-blue-50 border border-blue-300 flex items-center justify-between text-xs gap-3">
                    <div class="flex items-center space-x-3 min-w-0">
                        <img src="${data.student.photo_url}"
                             alt="${data.student.full_name}"
                             class="w-11 h-14 rounded-xl object-cover border border-blue-200 bg-white flex-shrink-0">
                        <div class="min-w-0">
                            <p class="font-extrabold text-blue-950 text-sm truncate">${data.student.full_name}</p>
                            <p class="text-[11px] text-blue-700 font-mono">
                                ${data.student.class_name} &bull; NISN: ${data.student.nisn}
                            </p>
                            <p class="text-[10px] text-blue-500 font-mono mt-0.5">
                                Masuk: ${data.tap_in_str} ${data.action_type === 'TAP_OUT' ? '&bull; Pulang: ' + data.tap_out_str : ''}
                            </p>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold ${data.is_late ? 'bg-blue-100 text-blue-800 border border-blue-300' : 'bg-blue-600 text-white'}">
                            ${data.is_late ? 'Terlambat' : 'Tepat Waktu'}
                        </span>
                        <span class="block text-[10px] font-extrabold text-blue-600 mt-1">${data.action_type === 'TAP_OUT' ? 'BARU PULANG' : 'BARU MASUK'}</span>
                    </div>
                </div>
            `;
            listElem.insertAdjacentHTML('afterbegin', itemHtml);

        } catch (e) {
            playRfidSound('error');
            speakVoice("Mohon maaf, gagal memproses presensi.");
        }
    }
</script>
@endsection
