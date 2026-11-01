@extends('layouts.app')

@section('title', 'Rekap Presensi Gerbang')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-extrabold text-slate-900 text-lg flex items-center">
                <i class="fa-solid fa-clock-rotate-left text-emerald-600 mr-2"></i> Rekap Presensi Gerbang RFID
            </h3>
            <p class="text-xs text-slate-500">Log kehadiran masuk (Tap-In), pulang (Tap-Out), identitas & foto siswa</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('gate.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
               class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                <i class="fa-solid fa-file-excel"></i>
                <span>Export Excel (.xlsx)</span>
            </a>

            <a href="{{ route('gate.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
               class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition flex items-center space-x-1.5 border border-slate-200">
                <i class="fa-solid fa-file-csv text-emerald-600"></i>
                <span>Export CSV</span>
            </a>

            <a href="{{ route('gate.index') }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                <i class="fa-solid fa-door-open"></i>
                <span>Buka Terminal Gerbang</span>
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form action="{{ route('gate.history') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Cari Nama / NISN:</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama atau NISN..."
                       class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Tanggal:</label>
                <input type="date" name="date" value="{{ request('date') }}"
                       class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Pilih Kelas:</label>
                <select name="class_name" class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">Semua Kelas</option>
                    @foreach($classes as $c)
                    <option value="{{ $c }}" {{ request('class_name') === $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Status Kedisiplinan:</label>
                <select name="status" class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">Semua Status</option>
                    <option value="ONTIME" {{ request('status') === 'ONTIME' ? 'selected' : '' }}>Tepat Waktu (< 07:00)</option>
                    <option value="LATE" {{ request('status') === 'LATE' ? 'selected' : '' }}>Terlambat (>= 07:00)</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition">
                    <i class="fa-solid fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('gate.history') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition" title="Reset">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase font-semibold">
                    <tr>
                        <th class="p-3.5">Foto & Identitas Siswa</th>
                        <th class="p-3.5">Kelas & NISN</th>
                        <th class="p-3.5">Tanggal</th>
                        <th class="p-3.5">Tap Masuk</th>
                        <th class="p-3.5">Tap Pulang</th>
                        <th class="p-3.5">Gerbang</th>
                        <th class="p-3.5 text-center">Status Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($attendances as $att)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-3.5">
                            <div class="flex items-center space-x-3">
                                <img src="{{ $att->student ? $att->student->photo : '' }}"
                                     alt="{{ $att->student ? $att->student->full_name : 'Siswa' }}"
                                     onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($att->student ? $att->student->full_name : 'Siswa') }}&background=0f172a&color=ffffff&size=128&bold=true';"
                                     class="w-10 h-12 rounded-lg object-cover bg-slate-200 border border-slate-200 shadow-sm flex-shrink-0">
                                <div>
                                    <p class="font-bold text-slate-900">{{ $att->student ? $att->student->full_name : '-' }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $att->student ? $att->student->school_name : '-' }}</p>
                                    <p class="text-[10px] text-blue-600 font-mono font-bold">UID: {{ $att->card ? $att->card->card_uid : '-' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-3.5 font-mono">
                            <p class="font-bold text-slate-700">{{ $att->student ? $att->student->class_name : '-' }}</p>
                            <p class="text-[11px] text-slate-400">NISN: {{ $att->student ? $att->student->nisn : '-' }}</p>
                        </td>
                        <td class="p-3.5 font-mono text-slate-600">{{ $att->date->format('d/m/Y') }}</td>
                        <td class="p-3.5 font-mono font-bold text-slate-800">
                            {{ $att->tap_in_time ? $att->tap_in_time->format('H:i:s') . ' WIB' : '-' }}
                        </td>
                        <td class="p-3.5 font-mono text-slate-500">
                            {{ $att->tap_out_time ? $att->tap_out_time->format('H:i:s') . ' WIB' : '-' }}
                        </td>
                        <td class="p-3.5 text-slate-600">{{ $att->gate_name }}</td>
                        <td class="p-3.5 text-center">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $att->is_late ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $att->is_late ? 'TERLAMBAT' : 'TEPAT WAKTU' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400">
                            <i class="fa-solid fa-calendar-xmark text-3xl mb-2 block text-slate-300"></i>
                            Tidak ada riwayat kehadiran yang sesuai.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $attendances->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
