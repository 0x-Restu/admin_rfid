@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Top Greeting Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-5 rounded-2xl border border-slate-200">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Selamat Datang, {{ auth()->user()->name }}</h2>
            <p class="text-xs text-slate-400 mt-0.5">{{ auth()->user()->role_badge['name'] }} &bull; {{ auth()->user()->bidang_name }}</p>
        </div>

        <div class="flex items-center space-x-2">
            @if(auth()->user()->hasRole('kantin'))
            <a href="{{ route('canteen.index') }}" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold transition">
                Buka POS
            </a>
            @endif

            @if(auth()->user()->hasRole('kesiswaan'))
            <a href="{{ route('gate.index') }}" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold transition">
                Terminal Gerbang
            </a>
            @endif

            @if(auth()->user()->hasRole('keuangan'))
            <a href="{{ route('finance.index') }}" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold transition">
                Keuangan & SPP
            </a>
            @endif
        </div>
    </div>

    <!-- KPI Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Kartu Aktif</p>
                <h3 class="text-2xl font-extrabold text-slate-900 mt-1">{{ $activeCardsCount }} <span class="text-xs font-normal text-slate-400">/ {{ $totalStudents }} siswa</span></h3>
                <p class="text-xs text-emerald-600 font-medium mt-0.5">Tersambung</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-base">
                <i class="fa-solid fa-id-card"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Saldo Beredar</p>
                <h3 class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">Rp {{ number_format($totalCirculatingBalance, 0, ',', '.') }}</h3>
                <p class="text-xs text-slate-400 font-medium mt-0.5">e-Money Siswa</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-base">
                <i class="fa-solid fa-wallet"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Presensi Hari Ini</p>
                <h3 class="text-2xl font-extrabold text-slate-900 mt-1">{{ $gateTodayCount }} <span class="text-xs font-normal text-slate-400">Tap</span></h3>
                <p class="text-xs text-slate-400 font-medium mt-0.5">{{ $gateLateCount }} Terlambat</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-base">
                <i class="fa-solid fa-door-open"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Penjualan Kantin</p>
                <h3 class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">Rp {{ number_format($canteenTodaySum, 0, ',', '.') }}</h3>
                <p class="text-xs text-slate-400 font-medium mt-0.5">{{ $canteenTodayCount }} Transaksi</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-base">
                <i class="fa-solid fa-calculator"></i>
            </div>
        </div>
    </div>

    <!-- Live Feeds Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Canteen Feed -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider">
                    Transaksi Kantin Terbaru
                </h4>
                @if(auth()->user()->hasRole('kantin'))
                <a href="{{ route('canteen.transactions') }}" class="text-xs text-slate-500 hover:text-slate-900 font-medium">
                    Lihat Semua
                </a>
                @endif
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-400 border-b border-slate-200 font-medium">
                        <tr>
                            <th class="p-3">Waktu</th>
                            <th class="p-3">Siswa</th>
                            <th class="p-3">Tenant</th>
                            <th class="p-3 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentCanteen as $trx)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 font-mono text-slate-400">{{ $trx->created_at->format('H:i') }}</td>
                            <td class="p-3 font-semibold text-slate-800">{{ $trx->student ? $trx->student->full_name : 'Siswa' }}</td>
                            <td class="p-3 text-slate-500">{{ $trx->tenant_name }}</td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900">Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-slate-400">Belum ada transaksi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Gate Feed -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider">
                    Presensi Gerbang Terbaru
                </h4>
                @if(auth()->user()->hasRole('kesiswaan', 'akademik'))
                <a href="{{ route('gate.history') }}" class="text-xs text-slate-500 hover:text-slate-900 font-medium">
                    Lihat Semua
                </a>
                @endif
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-400 border-b border-slate-200 font-medium">
                        <tr>
                            <th class="p-3">Waktu</th>
                            <th class="p-3">Siswa</th>
                            <th class="p-3">Lokasi</th>
                            <th class="p-3 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentGate as $att)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 font-mono text-slate-400">{{ $att->tap_in_time ? $att->tap_in_time->format('H:i') : '-' }}</td>
                            <td class="p-3 font-semibold text-slate-800">{{ $att->student ? $att->student->full_name : 'Siswa' }}</td>
                            <td class="p-3 text-slate-500">{{ $att->gate_name }}</td>
                            <td class="p-3 text-right">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $att->is_late ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $att->is_late ? 'Terlambat' : 'Tepat Waktu' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-slate-400">Belum ada presensi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Security Audit Logs Stream (For Super Admin) -->
    @if(auth()->user()->isSuperAdmin())
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
            <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider">
                Log Aktivitas & Forensik
            </h4>
            <a href="{{ route('admin.security.index') }}" class="text-xs text-slate-500 hover:text-slate-900 font-medium">
                Log Lengkap
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 border-b border-slate-200 font-medium">
                    <tr>
                        <th class="p-3">Waktu</th>
                        <th class="p-3">Aksi</th>
                        <th class="p-3">Pelaku</th>
                        <th class="p-3">Risiko</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                    @forelse($recentSecurityLogs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="p-3 text-slate-400 font-sans">{{ $log->created_at->format('d/m H:i') }}</td>
                        <td class="p-3 font-semibold text-slate-800">{{ $log->action }}</td>
                        <td class="p-3 font-sans text-slate-600">{{ $log->user ? $log->user->name : 'Sistem' }}</td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-sans font-bold {{ $log->severity === 'CRITICAL' ? 'bg-rose-100 text-rose-700' : ($log->severity === 'WARNING' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                                {{ $log->severity }}
                            </span>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-sans font-bold {{ $log->status === 'SUCCESS' ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $log->status }}
                            </span>
                        </td>
                        <td class="p-3 text-slate-400">{{ $log->ip_address }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-slate-400 font-sans">Tidak ada catatan aktivitas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
