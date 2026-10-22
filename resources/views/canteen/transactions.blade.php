@extends('layouts.app')

@section('title', 'Laporan Transaksi Kantin')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-extrabold text-slate-900 text-lg flex items-center">
                <i class="fa-solid fa-receipt text-blue-600 mr-2"></i> Rekap Riwayat Transaksi Kantin
            </h3>
            <p class="text-xs text-slate-500">Laporan transaksi penjualan kantin digital per tenant dan siswa</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="text-right">
                <p class="text-[11px] font-bold text-slate-400 uppercase">Total Akumulasi Terfilter</p>
                <p class="text-lg font-extrabold text-emerald-600 font-mono">Rp {{ number_format($totalFilteredSum, 0, ',', '.') }}</p>
            </div>
            <a href="{{ route('canteen.index') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                <i class="fa-solid fa-calculator"></i>
                <span>Buka Kasir POS</span>
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form action="{{ route('canteen.transactions') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Cari No. Kwitansi / Siswa:</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama atau no kwitansi..."
                       class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Filter Tenant:</label>
                <select name="tenant" class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">Semua Tenant / Stan</option>
                    @foreach($tenants as $t)
                    <option value="{{ $t }}" {{ request('tenant') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Tanggal Transaksi:</label>
                <input type="date" name="date" value="{{ request('date') }}"
                       class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition">
                    <i class="fa-solid fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('canteen.transactions') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition" title="Reset">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase font-semibold">
                    <tr>
                        <th class="p-3.5">No. Kwitansi</th>
                        <th class="p-3.5">Waktu</th>
                        <th class="p-3.5">Siswa</th>
                        <th class="p-3.5">Tenant / Stan</th>
                        <th class="p-3.5">Item / Keterangan</th>
                        <th class="p-3.5 text-right">Nominal</th>
                        <th class="p-3.5 text-right">Sisa Saldo</th>
                        <th class="p-3.5">Kasir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($transactions as $trx)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-3.5 font-mono font-bold text-blue-600">{{ $trx->receipt_number }}</td>
                        <td class="p-3.5 font-mono text-slate-500">{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-3.5">
                            <p class="font-bold text-slate-900">{{ $trx->student->full_name }}</p>
                            <p class="text-[10px] text-slate-400 font-mono">{{ $trx->student->class_name }} • NISN: {{ $trx->student->nisn }}</p>
                        </td>
                        <td class="p-3.5 text-slate-700 font-semibold">{{ $trx->tenant_name }}</td>
                        <td class="p-3.5 text-slate-600">{{ $trx->menu_name }}</td>
                        <td class="p-3.5 text-right font-mono font-bold text-emerald-600">
                            Rp {{ number_format($trx->amount, 0, ',', '.') }}
                        </td>
                        <td class="p-3.5 text-right font-mono text-slate-500">
                            Rp {{ number_format($trx->balance_after, 0, ',', '.') }}
                        </td>
                        <td class="p-3.5 text-slate-600 text-[11px]">
                            {{ $trx->cashier ? $trx->cashier->name : 'Sistem' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-400">
                            <i class="fa-solid fa-receipt text-3xl mb-2 block text-slate-300"></i>
                            Tidak ada transaksi yang cocok dengan filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
