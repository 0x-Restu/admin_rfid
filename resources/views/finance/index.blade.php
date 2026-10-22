@extends('layouts.app')

@section('title', 'Keuangan SPP & Tabungan Siswa')

@section('content')
<div class="space-y-6">

    <!-- Header & KPIs -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-extrabold text-slate-900 text-lg flex items-center">
                <i class="fa-solid fa-wallet text-amber-600 mr-2"></i> Keuangan Sekolah: SPP & Tabungan Siswa
            </h3>
            <p class="text-xs text-slate-500">Manajemen tagihan SPP, pembayaran via Smart Card RFID / Tunai, dan tabungan siswa</p>
        </div>

        <button type="button" onclick="openCreateSppModal()"
                class="px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-md shadow-amber-500/20">
            <i class="fa-solid fa-file-circle-plus"></i>
            <span>Buat Tagihan SPP Baru</span>
        </button>
    </div>

    <!-- Financial KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500">Tagihan SPP Belum Terbayar</p>
                <h4 class="text-xl font-extrabold text-amber-600 font-mono mt-0.5">Rp {{ number_format($totalSppUnpaid, 0, ',', '.') }}</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500">Total SPP Lunas Terkumpul</p>
                <h4 class="text-xl font-extrabold text-emerald-600 font-mono mt-0.5">Rp {{ number_format($totalSppPaid, 0, ',', '.') }}</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500">Total Saldo Tabungan Siswa</p>
                <h4 class="text-xl font-extrabold text-blue-600 font-mono mt-0.5">Rp {{ number_format($netSavingsPool, 0, ',', '.') }}</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-piggy-bank"></i>
            </div>
        </div>
    </div>

    <!-- Main Grid: SPP on Left (8 cols), Tabungan Mutasi on Right (4 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left: SPP Bills Table (8 Cols) -->
        <div class="lg:col-span-8 space-y-4">
            
            <!-- Filter Bar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <form action="{{ route('finance.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Cari Nama Siswa / NISN:</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama siswa..."
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Status Pembayaran:</label>
                        <select name="status" class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <option value="ALL">Semua Status</option>
                            <option value="UNPAID" {{ request('status') === 'UNPAID' ? 'selected' : '' }}>BELUM BAYAR (UNPAID)</option>
                            <option value="PAID" {{ request('status') === 'PAID' ? 'selected' : '' }}>LUNAS (PAID)</option>
                        </select>
                    </div>

                    <div class="flex items-end space-x-2">
                        <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition">
                            <i class="fa-solid fa-filter mr-1"></i> Filter
                        </button>
                        <a href="{{ route('finance.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition" title="Reset">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- SPP Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <h4 class="font-extrabold text-xs text-slate-800 uppercase tracking-wider">
                        Daftar Tagihan SPP Siswa
                    </h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase font-semibold">
                            <tr>
                                <th class="p-3.5">Siswa</th>
                                <th class="p-3.5">Bulan / Tahun</th>
                                <th class="p-3.5 text-right">Nominal</th>
                                <th class="p-3.5">Jatuh Tempo</th>
                                <th class="p-3.5 text-center">Status</th>
                                <th class="p-3.5 text-center">Aksi / Kwitansi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($sppBills as $bill)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-3.5">
                                    <p class="font-bold text-slate-900">{{ $bill->student->full_name }}</p>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ $bill->student->class_name }} • NISN: {{ $bill->student->nisn }}</p>
                                </td>
                                <td class="p-3.5 font-bold text-slate-700">
                                    {{ $bill->month_name }} {{ $bill->year }}
                                </td>
                                <td class="p-3.5 text-right font-mono font-bold text-slate-900">
                                    {{ $bill->formatted_amount }}
                                </td>
                                <td class="p-3.5 font-mono text-slate-500">
                                    {{ $bill->due_date->format('d/m/Y') }}
                                </td>
                                <td class="p-3.5 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $bill->status === 'PAID' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $bill->status === 'PAID' ? 'LUNAS' : 'BELUM BAYAR' }}
                                    </span>
                                </td>
                                <td class="p-3.5 text-center">
                                    @if($bill->status === 'PAID')
                                    <div class="text-[11px] font-mono text-emerald-600 font-bold">
                                        <i class="fa-solid fa-check mr-0.5"></i> {{ $bill->receipt_number }}
                                        <span class="block text-[9px] text-slate-400 font-sans">via {{ $bill->paid_via }}</span>
                                    </div>
                                    @else
                                    <button type="button" onclick="openPaySppModal('{{ $bill->id }}', '{{ $bill->student->full_name }}', '{{ $bill->month_name }} {{ $bill->year }}', '{{ $bill->formatted_amount }}', '{{ $bill->student->rfidCard ? $bill->student->rfidCard->formatted_balance : 'Rp 0' }}')"
                                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                                        Bayar Sekarang
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    Tidak ada tagihan SPP ditemukan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($sppBills->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $sppBills->links() }}
                </div>
                @endif
            </div>

        </div>

        <!-- Right: Tabungan Siswa (Form Setor/Tarik + Ledger) (4 Cols) -->
        <div class="lg:col-span-4 space-y-4">
            
            <!-- Mutation Form -->
            <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm space-y-4">
                <h4 class="font-extrabold text-xs text-slate-800 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-piggy-bank text-blue-600 mr-1.5"></i> Transaksi Tabungan Siswa
                </h4>

                <form action="{{ route('finance.savings.mutate') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Pilih Siswa *</label>
                        <select name="student_id" required class="w-full text-xs font-medium bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="">-- Pilih Siswa --</option>
                            @foreach($allStudents as $st)
                            <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->class_name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Jenis Transaksi *</label>
                            <select name="type" class="w-full text-xs font-bold bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <option value="DEPOSIT">Setoran (+)</option>
                                <option value="WITHDRAWAL">Penarikan (-)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Nominal (Rp) *</label>
                            <input type="number" name="amount" required min="1000" step="5000" placeholder="20000"
                                   class="w-full text-xs font-bold font-mono bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Keterangan (Opsional)</label>
                        <input type="text" name="note" placeholder="Contoh: Sisa uang saku"
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition">
                        Simpan Transaksi Tabungan
                    </button>
                </form>
            </div>

            <!-- Savings Ledger List -->
            <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm space-y-3">
                <h4 class="font-extrabold text-xs text-slate-800 uppercase tracking-wider flex items-center justify-between">
                    <span><i class="fa-solid fa-list-check text-emerald-600 mr-1.5"></i> Mutasi Tabungan Terkini</span>
                </h4>

                <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                    @forelse($savingsMutations as $mut)
                    <div class="p-2.5 rounded-xl border border-slate-200 bg-slate-50/70 flex justify-between items-center text-xs">
                        <div>
                            <p class="font-bold text-slate-800">{{ $mut->student->full_name }}</p>
                            <p class="text-[10px] text-slate-400">{{ $mut->note }} • {{ $mut->created_at->format('d/m/y H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="font-mono font-bold {{ $mut->type === 'DEPOSIT' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $mut->type === 'DEPOSIT' ? '+' : '-' }} Rp {{ number_format($mut->amount, 0, ',', '.') }}
                            </span>
                            <p class="text-[10px] text-slate-400 font-mono">Saldo: Rp {{ number_format($mut->balance_after, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 text-center py-4">Belum ada mutasi tabungan.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

    <!-- ==================== MODAL BUAT TAGIHAN SPP ==================== -->
    <div id="create-spp-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-slate-900 text-base">Buat Tagihan SPP Baru</h3>
                <button type="button" onclick="closeCreateSppModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('finance.spp.create') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Siswa *</label>
                    <select name="student_id" required class="w-full text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <option value="">-- Pilih Siswa --</option>
                        @foreach($allStudents as $st)
                        <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->class_name }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Bulan *</label>
                        <select name="month" class="w-full text-xs font-bold bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $m == date('n') ? 'selected' : '' }}>Bulan ke-{{ $m }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tahun *</label>
                        <input type="number" name="year" value="{{ date('Y') }}" required
                               class="w-full text-xs font-bold bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nominal Tagihan (Rp) *</label>
                    <input type="number" name="amount" value="350000" min="10000" step="10000" required
                           class="w-full text-xs font-bold font-mono bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Jatuh Tempo *</label>
                    <input type="date" name="due_date" value="{{ date('Y-m-10') }}" required
                           class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeCreateSppModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-md shadow-amber-500/20">
                        Buat Tagihan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL BAYAR SPP ==================== -->
    <div id="pay-spp-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-slate-900 text-base">Pembayaran Tagihan SPP</h3>
                <button type="button" onclick="closePaySppModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="pay-spp-form" method="POST" action="" class="space-y-4">
                @csrf
                <div class="p-3 bg-amber-50 border border-amber-200 rounded-2xl space-y-1 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Nama Siswa:</span>
                        <span class="font-extrabold text-slate-900" id="pay-spp-student">Nama Siswa</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Periode Tagihan:</span>
                        <span class="font-bold text-slate-800" id="pay-spp-period">Oktober 2026</span>
                    </div>
                    <div class="flex justify-between border-t border-amber-200/60 pt-1 text-sm">
                        <span class="font-bold text-slate-800">Total Biaya:</span>
                        <span class="font-extrabold font-mono text-amber-700" id="pay-spp-amount">Rp 350.000</span>
                    </div>
                    <div class="flex justify-between text-[11px] text-blue-700 pt-1">
                        <span>Saldo Smart Card Siswa:</span>
                        <span class="font-bold font-mono" id="pay-spp-card-balance">Rp 0</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Saluran Pembayaran *</label>
                    <select name="paid_via" required class="w-full text-xs font-bold bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <option value="CASH">Tunai di Loket (CASH)</option>
                        <option value="RFID_BALANCE">Potong Langsung Saldo Kartu RFID (e-Money Siswa)</option>
                        <option value="TRANSFER">Transfer Bank / Virtual Account</option>
                    </select>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closePaySppModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-500/20">
                        Konfirmasi Lunas & Cetak Kwitansi
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    function openCreateSppModal() {
        document.getElementById('create-spp-modal').classList.remove('hidden');
    }
    function closeCreateSppModal() {
        document.getElementById('create-spp-modal').classList.add('hidden');
    }

    function openPaySppModal(id, studentName, period, amount, cardBal) {
        const form = document.getElementById('pay-spp-form');
        form.action = `/finance/spp/${id}/pay`;

        document.getElementById('pay-spp-student').innerText = studentName;
        document.getElementById('pay-spp-period').innerText = period;
        document.getElementById('pay-spp-amount').innerText = amount;
        document.getElementById('pay-spp-card-balance').innerText = cardBal;

        document.getElementById('pay-spp-modal').classList.remove('hidden');
    }
    function closePaySppModal() {
        document.getElementById('pay-spp-modal').classList.add('hidden');
    }
</script>
@endsection
