@extends('layouts.app')

@section('title', 'Laporan Transaksi Kantin')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-extrabold text-slate-900 text-lg flex items-center">
                <i class="fa-solid fa-receipt text-blue-600 mr-2"></i> Rekap Riwayat Transaksi Kantin
            </h3>
            <p class="text-xs text-slate-500">Laporan transaksi belanja kantin digital lengkap dengan identitas & foto siswa</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="text-right mr-2">
                <p class="text-[10px] font-bold text-slate-400 uppercase">Total Akumulasi Terfilter</p>
                <p class="text-base font-extrabold text-emerald-600 font-mono">Rp {{ number_format($totalFilteredSum, 0, ',', '.') }}</p>
            </div>

            <a href="{{ route('canteen.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
               class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                <i class="fa-solid fa-file-excel"></i>
                <span>Export Excel (.xlsx)</span>
            </a>

            <a href="{{ route('canteen.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
               class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition flex items-center space-x-1.5 border border-slate-200">
                <i class="fa-solid fa-file-csv text-emerald-600"></i>
                <span>Export CSV</span>
            </a>

            <a href="{{ route('canteen.index') }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
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
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama, NISN, atau no kwitansi..."
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
                        <th class="p-3.5">Foto & Identitas Pembeli</th>
                        <th class="p-3.5">No. Kwitansi & Waktu</th>
                        <th class="p-3.5">Tenant / Stan</th>
                        <th class="p-3.5">Item / Keterangan</th>
                        <th class="p-3.5 text-right">Nominal Belanja</th>
                        <th class="p-3.5 text-right">Sisa Saldo</th>
                        <th class="p-3.5">Kasir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($transactions as $trx)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-3.5">
                            <div class="flex items-center space-x-3">
                                <img src="{{ $trx->student ? $trx->student->photo : '' }}"
                                     alt="{{ $trx->student ? $trx->student->full_name : 'Siswa' }}"
                                     onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($trx->student ? $trx->student->full_name : 'Siswa') }}&background=0f172a&color=ffffff&size=128&bold=true';"
                                     class="w-10 h-12 rounded-lg object-cover bg-slate-200 border border-slate-200 shadow-sm flex-shrink-0">
                                <div>
                                    <p class="font-bold text-slate-900">{{ $trx->student ? $trx->student->full_name : '-' }}</p>
                                    <p class="text-[10px] text-slate-500 font-mono">
                                        {{ $trx->student ? $trx->student->class_name : '-' }} &bull; NISN: {{ $trx->student ? $trx->student->nisn : '-' }}
                                    </p>
                                    <p class="text-[10px] text-blue-600 font-mono font-bold">
                                        UID: {{ $trx->card ? $trx->card->card_uid : '-' }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="p-3.5 font-mono">
                            <p class="font-bold text-blue-600">{{ $trx->receipt_number }}</p>
                            <p class="text-[11px] text-slate-400">{{ $trx->created_at->format('d/m/Y H:i') }} WIB</p>
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
                        <td colspan="7" class="p-8 text-center text-slate-400">
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
