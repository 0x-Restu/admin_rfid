@extends('layouts.app')

@section('title', 'Audit Keamanan & Forensik')

@section('content')
<div class="space-y-6">

    <!-- Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 text-white p-5 rounded-2xl shadow-sm border border-slate-800">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse mr-1"></span> Keamanan 100% Aktif
                </span>
                <span class="text-xs text-slate-400">• Forensic Audit Trail</span>
            </div>
            <h3 class="font-extrabold text-xl mt-1 tracking-tight flex items-center">
                <i class="fa-solid fa-shield-halved text-rose-500 mr-2"></i> Pusat Audit Keamanan & Log Forensik Sistem
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Pemantauan real-time terhadap autentikasi, transaksi saldo, akses gerbang, dan percobaan akses terlarang</p>
        </div>

        <div class="flex items-center space-x-2">
            <span class="px-3 py-1.5 rounded-xl bg-slate-800 border border-slate-700 text-xs text-slate-300 font-mono">
                <i class="fa-solid fa-server text-emerald-400 mr-1.5"></i> IP Logging Enabled
            </span>
        </div>
    </div>

    <!-- Security Architecture Checklist Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-900">Pessimistic Row Lock</h4>
                <p class="text-[11px] text-slate-500">Cegah race condition & double spending pada saldo e-Money.</p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-shield-virus"></i>
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-900">CSRF & Header Shield</h4>
                <p class="text-[11px] text-slate-500">X-Frame-Options, nosniff, anti-clickjacking & XSS filter.</p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-gauge-high"></i>
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-900">Rate Limiter / Throttle</h4>
                <p class="text-[11px] text-slate-500">Blokir otomatis serangan brute force pada login & transaksi.</p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-900">Strict RBAC Isolation</h4>
                <p class="text-[11px] text-slate-500">Setiap bidang hanya dapat mengakses modul yang diizinkan.</p>
            </div>
        </div>
    </div>

    <!-- Forensic Metric Counters -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-xs font-bold text-slate-500 uppercase">Total Log Tercatat</p>
            <h3 class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $stats['total'] }}</h3>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-xs font-bold text-rose-600 uppercase">Critical Severity</p>
            <h3 class="text-2xl font-extrabold text-rose-600 mt-1 font-mono">{{ $stats['critical'] }}</h3>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-xs font-bold text-amber-600 uppercase">Warning Alerts</p>
            <h3 class="text-2xl font-extrabold text-amber-600 mt-1 font-mono">{{ $stats['warning'] }}</h3>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-xs font-bold text-purple-600 uppercase">Akses Diblokir (Blocked)</p>
            <h3 class="text-2xl font-extrabold text-purple-600 mt-1 font-mono">{{ $stats['blocked'] }}</h3>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form action="{{ route('admin.security.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Cari Keyword / IP / ID:</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari IP, aksi, atau user..."
                       class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Tingkat Risiko (Severity):</label>
                <select name="severity" class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    <option value="ALL">Semua Severity</option>
                    <option value="CRITICAL" {{ request('severity') === 'CRITICAL' ? 'selected' : '' }}>CRITICAL (Bahaya Tinggi)</option>
                    <option value="WARNING" {{ request('severity') === 'WARNING' ? 'selected' : '' }}>WARNING (Peringatan)</option>
                    <option value="NOTICE" {{ request('severity') === 'NOTICE' ? 'selected' : '' }}>NOTICE (Perhatian)</option>
                    <option value="INFO" {{ request('severity') === 'INFO' ? 'selected' : '' }}>INFO (Normal)</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Status Eksekusi:</label>
                <select name="status" class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    <option value="ALL">Semua Status</option>
                    <option value="SUCCESS" {{ request('status') === 'SUCCESS' ? 'selected' : '' }}>SUCCESS (Berhasil)</option>
                    <option value="FAILED" {{ request('status') === 'FAILED' ? 'selected' : '' }}>FAILED (Gagal)</option>
                    <option value="BLOCKED" {{ request('status') === 'BLOCKED' ? 'selected' : '' }}>BLOCKED (Diblokir)</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition">
                    <i class="fa-solid fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('admin.security.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition" title="Reset">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Forensic Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase font-semibold">
                    <tr>
                        <th class="p-3.5">Waktu (WIB)</th>
                        <th class="p-3.5">Aksi (Action)</th>
                        <th class="p-3.5">Tingkat Risiko</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5">Pelaku / Petugas</th>
                        <th class="p-3.5">IP Address</th>
                        <th class="p-3.5">Rincian Forensik</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-3.5 text-slate-500 font-sans whitespace-nowrap">
                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="p-3.5 font-bold text-slate-900">
                            {{ $log->action }}
                        </td>
                        <td class="p-3.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold font-sans {{ $log->severity === 'CRITICAL' ? 'bg-rose-100 text-rose-700 border border-rose-200' : ($log->severity === 'WARNING' ? 'bg-amber-100 text-amber-700 border border-amber-200' : 'bg-blue-100 text-blue-700 border border-blue-200') }}">
                                {{ $log->severity }}
                            </span>
                        </td>
                        <td class="p-3.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold font-sans {{ $log->status === 'SUCCESS' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                {{ $log->status }}
                            </span>
                        </td>
                        <td class="p-3.5 font-sans text-slate-800">
                            {{ $log->user ? $log->user->name : 'Sistem / Guest' }}
                        </td>
                        <td class="p-3.5 text-slate-600 font-mono">
                            {{ $log->ip_address }}
                        </td>
                        <td class="p-3.5 font-sans text-slate-600 max-w-xs truncate" title="{{ json_encode($log->details) }}">
                            @if($log->details)
                            <code class="text-[10px] bg-slate-100 px-1 py-0.5 rounded text-slate-700">{{ json_encode($log->details) }}</code>
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400 font-sans">
                            <i class="fa-solid fa-shield-cat text-3xl mb-2 block text-slate-300"></i>
                            Tidak ada data log yang cocok.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="p-4 border-t border-slate-200 font-sans">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
