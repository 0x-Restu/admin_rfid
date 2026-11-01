@extends('layouts.app')

@section('title', 'POS Kantin')

@section('content')
@php
    $latestTx = $recentTransactions->first();
@endphp
<div class="space-y-6">

    <!-- Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-extrabold text-slate-900 text-base">Kasir POS Kantin Digital</h3>
            <p class="text-xs text-slate-400">Pembayaran via Tap Kartu RFID dengan Verifikasi Identitas & Foto Siswa</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="text-right">
                <p class="text-[10px] text-slate-400 font-semibold uppercase">Penjualan Hari Ini ({{ $todayTrxCount }} Trx)</p>
                <p class="text-base font-extrabold text-emerald-600 font-mono">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</p>
            </div>
            <a href="{{ route('canteen.transactions') }}" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition flex items-center space-x-1.5">
                <i class="fa-solid fa-receipt"></i>
                <span>Riwayat & Export</span>
            </a>
        </div>
    </div>

    <!-- POS Register Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left: Keypad, Stan & Active Buyer Identity Card (7 Cols) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-4">
                
                <!-- Tenant & Item Input -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="pos-tenant" class="block text-[11px] font-bold text-slate-500 mb-1">Stan / Tenant Kantin:</label>
                        <select id="pos-tenant" class="w-full text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                            <option value="Kantin Bu Ani (Stand 01)">Kantin Bu Ani (Stand 01)</option>
                            <option value="Kantin Mas Joko">Kantin Mas Joko</option>
                            <option value="Kedai Minuman">Kedai Minuman</option>
                            <option value="Koperasi Siswa">Koperasi Siswa</option>
                        </select>
                    </div>
                    <div>
                        <label for="pos-menu-name" class="block text-[11px] font-bold text-slate-500 mb-1">Menu / Keterangan Belanja:</label>
                        <input type="text" id="pos-menu-name" placeholder="Contoh: Nasi Goreng + Es Teh"
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                </div>

                <!-- Display -->
                <div class="bg-slate-900 rounded-2xl p-5 text-right text-white">
                    <div class="text-xs text-slate-400 mb-1">Total Tagihan Belanja</div>
                    <div class="text-3xl sm:text-4xl font-extrabold tracking-tight font-mono text-emerald-400" id="calc-display">
                        Rp 0
                    </div>
                </div>

                <!-- Quick Amount Buttons -->
                <div class="grid grid-cols-4 gap-2">
                    <button type="button" onclick="calcAddQuick(5000)" class="calc-btn py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold text-xs">+5.000</button>
                    <button type="button" onclick="calcAddQuick(10000)" class="calc-btn py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold text-xs">+10.000</button>
                    <button type="button" onclick="calcAddQuick(15000)" class="calc-btn py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold text-xs">+15.000</button>
                    <button type="button" onclick="calcAddQuick(20000)" class="calc-btn py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold text-xs">+20.000</button>
                </div>

                <!-- Keypad -->
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="calcInput('7')" class="calc-btn py-3 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-xl font-bold text-base border border-slate-200">7</button>
                    <button type="button" onclick="calcInput('8')" class="calc-btn py-3 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-xl font-bold text-base border border-slate-200">8</button>
                    <button type="button" onclick="calcInput('9')" class="calc-btn py-3 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-xl font-bold text-base border border-slate-200">9</button>

                    <button type="button" onclick="calcInput('4')" class="calc-btn py-3 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-xl font-bold text-base border border-slate-200">4</button>
                    <button type="button" onclick="calcInput('5')" class="calc-btn py-3 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-xl font-bold text-base border border-slate-200">5</button>
                    <button type="button" onclick="calcInput('6')" class="calc-btn py-3 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-xl font-bold text-base border border-slate-200">6</button>

                    <button type="button" onclick="calcInput('1')" class="calc-btn py-3 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-xl font-bold text-base border border-slate-200">1</button>
                    <button type="button" onclick="calcInput('2')" class="calc-btn py-3 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-xl font-bold text-base border border-slate-200">2</button>
                    <button type="button" onclick="calcInput('3')" class="calc-btn py-3 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-xl font-bold text-base border border-slate-200">3</button>

                    <button type="button" onclick="calcClear()" class="calc-btn py-3 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl font-bold text-xs border border-rose-200">C</button>
                    <button type="button" onclick="calcInput('0')" class="calc-btn py-3 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-xl font-bold text-base border border-slate-200">0</button>
                    <button type="button" onclick="calcInput('000')" class="calc-btn py-3 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-xl font-bold text-xs border border-slate-200">000</button>
                </div>

                <!-- Action Button -->
                <button type="button" onclick="openPaymentModal()"
                        class="w-full py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm transition flex items-center justify-center space-x-2 shadow-sm">
                    <i class="fa-solid fa-satellite-dish text-xs"></i>
                    <span>Tap Kartu RFID & Verifikasi Identitas Pembeli</span>
                </button>
            </div>

            <!-- KARTU IDENTITAS & FOTO PEMBELI TERAKHIR -->
            <div id="pos-last-buyer-card" class="{{ $latestTx && $latestTx->student ? '' : 'hidden' }} bg-white rounded-2xl border-2 border-emerald-500 overflow-hidden shadow-sm">
                <div class="bg-slate-900 px-4 py-2.5 text-white flex items-center justify-between text-xs">
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-0.5 rounded bg-emerald-500 text-slate-950 font-extrabold text-[10px] uppercase">TRANSAKSI SUKSES</span>
                        <span id="last-buyer-receipt" class="font-mono text-emerald-400 font-bold">{{ $latestTx ? $latestTx->receipt_number : '-' }}</span>
                    </div>
                    <span id="last-buyer-time" class="font-mono text-[11px] text-slate-400">
                        {{ $latestTx ? $latestTx->created_at->format('d/m/Y H:i') . ' WIB' : '-' }}
                    </span>
                </div>
                <div class="p-4 flex flex-col sm:flex-row items-center sm:items-start gap-4">
                    <img id="last-buyer-photo"
                         src="{{ $latestTx && $latestTx->student ? $latestTx->student->photo : '' }}"
                         alt="Foto Pembeli"
                         onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=Siswa&background=0f172a&color=ffffff&size=256&bold=true';"
                         class="w-20 h-24 rounded-xl object-cover border-2 border-slate-200 shadow-sm bg-slate-100 flex-shrink-0">
                    <div class="flex-1 min-w-0 w-full text-center sm:text-left space-y-2">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Identitas Pembeli Terverifikasi</p>
                            <h4 id="last-buyer-name" class="text-base font-extrabold text-slate-900">
                                {{ $latestTx && $latestTx->student ? $latestTx->student->full_name : '-' }}
                            </h4>
                            <p id="last-buyer-info" class="text-xs text-slate-500 font-mono">
                                {{ $latestTx && $latestTx->student ? $latestTx->student->class_name . ' • NISN: ' . $latestTx->student->nisn : '-' }}
                                &bull; UID: <span id="last-buyer-uid" class="text-blue-600 font-bold">{{ $latestTx && $latestTx->card ? $latestTx->card->card_uid : '-' }}</span>
                            </p>
                        </div>
                        <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100 text-xs">
                            <div class="bg-slate-50 p-2 rounded-lg">
                                <p class="text-[10px] text-slate-400 uppercase">Stan / Menu</p>
                                <p id="last-buyer-tenant" class="font-bold text-slate-800 truncate">{{ $latestTx ? $latestTx->tenant_name : '-' }}</p>
                            </div>
                            <div class="bg-rose-50 p-2 rounded-lg">
                                <p class="text-[10px] text-rose-500 uppercase">Total Belanja</p>
                                <p id="last-buyer-amount" class="font-mono font-extrabold text-rose-600">
                                    Rp {{ $latestTx ? number_format($latestTx->amount, 0, ',', '.') : '0' }}
                                </p>
                            </div>
                            <div class="bg-emerald-50 p-2 rounded-lg">
                                <p class="text-[10px] text-emerald-600 uppercase">Sisa Saldo</p>
                                <p id="last-buyer-balance" class="font-mono font-extrabold text-emerald-700">
                                    Rp {{ $latestTx ? number_format($latestTx->balance_after, 0, ',', '.') : '0' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Registered Student Cards & Recent Transactions (5 Cols) -->
        <div class="lg:col-span-5 space-y-4">
            
            <!-- Registered Cards with Photo for fast tap -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-bold text-xs text-slate-900 uppercase tracking-wider">
                        Kartu Siswa Terdaftar (Klik untuk Tap)
                    </h4>
                    <span class="text-[11px] text-slate-400">{{ $sampleStudents->count() }} Kartu</span>
                </div>
                <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    @forelse($sampleStudents as $student)
                    @if($student->rfidCard)
                    <div onclick="selectCardForPayment('{{ $student->rfidCard->card_uid }}')"
                         class="p-2.5 rounded-xl border border-slate-200 hover:border-slate-900 hover:bg-slate-50 transition cursor-pointer flex items-center justify-between">
                        <div class="flex items-center space-x-3 min-w-0">
                            <img src="{{ $student->photo }}"
                                 alt="{{ $student->full_name }}"
                                 onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($student->full_name) }}&background=0f172a&color=ffffff&size=128&bold=true';"
                                 class="w-10 h-12 rounded-lg object-cover border border-slate-200 bg-slate-100 flex-shrink-0">
                            <div class="min-w-0">
                                <p class="font-bold text-xs text-slate-900 truncate">{{ $student->full_name }}</p>
                                <p class="text-[10px] text-slate-500 font-mono">{{ $student->class_name }} &bull; NISN: {{ $student->nisn }}</p>
                                <p class="text-[10px] text-blue-600 font-mono font-bold">UID: {{ $student->rfidCard->card_uid }}</p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 ml-2">
                            <span class="text-xs font-extrabold text-emerald-600 font-mono block">{{ $student->rfidCard->formatted_balance }}</span>
                            <span class="text-[9px] text-slate-400">Limit: {{ $student->rfidCard->formatted_limit }}</span>
                        </div>
                    </div>
                    @endif
                    @empty
                    <p class="text-xs text-slate-400 text-center py-4">Belum ada kartu terdaftar.</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Transactions Feed with Photo & Identity -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
                <h4 class="font-bold text-xs text-slate-900 uppercase tracking-wider mb-3">
                    Transaksi Belanja Terakhir
                </h4>
                <div id="pos-recent-tx-list" class="space-y-2.5 max-h-64 overflow-y-auto pr-1">
                    @forelse($recentTransactions as $tx)
                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs gap-2">
                        <div class="flex items-center space-x-3 min-w-0">
                            <img src="{{ $tx->student ? $tx->student->photo : '' }}"
                                 alt="{{ $tx->student ? $tx->student->full_name : 'Siswa' }}"
                                 onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($tx->student ? $tx->student->full_name : 'Siswa') }}&background=0f172a&color=ffffff&size=128&bold=true';"
                                 class="w-10 h-12 rounded-lg object-cover border border-slate-200 bg-white flex-shrink-0">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900 truncate">{{ $tx->student ? $tx->student->full_name : 'Siswa' }}</p>
                                <p class="text-[10px] text-slate-500 font-mono">
                                    {{ $tx->student ? $tx->student->class_name : '-' }} &bull; NISN: {{ $tx->student ? $tx->student->nisn : '-' }}
                                </p>
                                <p class="text-[10px] text-slate-400 font-mono">{{ $tx->receipt_number }} &bull; {{ $tx->created_at->format('H:i') }}</p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="font-extrabold font-mono text-slate-900">Rp {{ number_format($tx->amount, 0, ',', '.') }}</p>
                            <p class="text-[10px] font-mono text-emerald-600">Sisa: Rp {{ number_format($tx->balance_after, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    @empty
                    <p id="empty-tx-msg" class="text-xs text-slate-400 text-center py-4">Belum ada transaksi hari ini.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

    <!-- ==================== MODAL PEMBAYARAN & VERIFIKASI IDENTITAS + FOTO ==================== -->
    <div id="payment-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 border border-slate-200 space-y-4 relative shadow-2xl">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-slate-900 text-sm" id="payment-modal-title">Tempelkan Kartu RFID</h3>
                <button type="button" onclick="closePaymentModal()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- STEP 1: SCAN -->
            <div id="payment-step-scan" class="flex flex-col items-center py-4 space-y-4">
                <div class="relative w-28 h-28 flex items-center justify-center">
                    <div class="radar-beam bg-slate-900/10"></div>
                    <div class="radar-beam bg-slate-900/5"></div>
                    <div class="w-16 h-16 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-2xl z-10">
                        <i class="fa-solid fa-id-card-clip"></i>
                    </div>
                </div>

                <div class="text-center">
                    <p class="font-bold text-slate-800 text-xs">Menunggu Tap Kartu RFID Siswa...</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Identitas & Foto Siswa akan muncul untuk diverifikasi</p>
                </div>

                <div class="w-full">
                    <div class="flex space-x-2">
                        <input type="text" id="rfid-hw-input" placeholder="Scan UID Kartu..." autofocus
                               class="w-full text-xs font-mono uppercase bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none"
                               onkeydown="if(event.key==='Enter') handleHardwareScan(this.value)">
                        <button type="button" onclick="handleHardwareScan(document.getElementById('rfid-hw-input').value)" class="px-4 py-2.5 bg-slate-900 text-white rounded-xl text-xs font-bold">
                            OK
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 2: CONFIRM WITH STUDENT PHOTO & FULL IDENTITY -->
            <div id="payment-step-detected" class="space-y-4 hidden">
                <div class="bg-slate-900 rounded-2xl p-4 text-white">
                    <div class="flex items-center justify-between text-[11px] text-slate-400 mb-3 pb-2 border-b border-slate-800">
                        <span id="modal-card-uid" class="font-mono font-bold text-blue-400">UID: -</span>
                        <span id="modal-student-status" class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold text-[10px]">ACTIVE</span>
                    </div>

                    <div class="flex items-start space-x-3.5">
                        <img id="modal-student-photo"
                             src=""
                             alt="Foto Siswa"
                             onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=Siswa&background=1e293b&color=ffffff&size=256&bold=true';"
                             class="w-20 h-24 rounded-xl object-cover border-2 border-emerald-400 shadow-md bg-slate-800 flex-shrink-0">

                        <div class="min-w-0 flex-1 space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 block">Identitas Siswa Pembeli</span>
                            <h4 id="modal-student-name" class="font-extrabold text-base text-white leading-snug">Nama Siswa</h4>
                            <p id="modal-student-info" class="text-xs text-slate-300 font-mono">Kelas &bull; NISN</p>
                            <p id="modal-student-school" class="text-[11px] text-slate-400">Sekolah &bull; Gender</p>
                            <p id="modal-student-parent" class="text-[10px] text-slate-400">Wali: -</p>
                        </div>
                    </div>
                </div>

                <!-- Balance & Bill Breakdown -->
                <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200 space-y-1.5 text-xs">
                    <div class="flex justify-between text-slate-500">
                        <span>Saldo Kartu Saat Ini:</span>
                        <span id="modal-cur-balance" class="font-mono font-bold text-slate-800">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>Sisa Limit Belanja Hari Ini:</span>
                        <span id="modal-rem-limit" class="font-mono font-semibold text-slate-700">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-rose-600 font-bold pt-1 border-t border-slate-200/70">
                        <span>Nominal Tagihan Belanja:</span>
                        <span id="modal-bill-deduct" class="font-mono text-sm">- Rp 0</span>
                    </div>
                    <div class="border-t border-slate-200 pt-1.5 flex justify-between font-extrabold text-slate-900">
                        <span>Estimasi Sisa Saldo:</span>
                        <span id="modal-rem-balance" class="font-mono text-emerald-600 text-sm">Rp 0</span>
                    </div>
                </div>

                <div id="modal-warning-box" class="p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs hidden">
                    <span id="modal-warning-text" class="font-semibold"></span>
                </div>

                <div class="flex items-center space-x-2 pt-1">
                    <button type="button" onclick="resetModalToScan()" class="w-1/3 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                        Ganti Kartu
                    </button>
                    <button type="button" id="btn-confirm-payment" onclick="executePaymentTransaction()"
                            class="w-2/3 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm">
                        <span id="btn-confirm-text">Konfirmasi Bayar</span>
                    </button>
                </div>
            </div>

            <!-- STEP 3: POP-UP TRANSAKSI BERHASIL -->
            <div id="payment-step-success" class="space-y-4 hidden text-center">
                <div class="bg-emerald-600 rounded-2xl p-4 text-white flex items-center space-x-3 text-left">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-xl flex-shrink-0">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-100 block">PEMBAYARAN BERHASIL</span>
                        <h4 id="success-receipt-no" class="font-mono font-extrabold text-sm">RCP-XXXX</h4>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center space-x-4 text-left">
                    <img id="success-student-photo"
                         src=""
                         alt="Foto Pembeli"
                         onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=Siswa&background=0f172a&color=ffffff&size=256&bold=true';"
                         class="w-20 h-24 rounded-xl object-cover border-2 border-emerald-500 shadow-sm bg-white flex-shrink-0">
                    <div class="min-w-0 flex-1 space-y-1 text-xs">
                        <span class="text-[10px] font-bold uppercase text-emerald-600 block">Identitas Pembeli</span>
                        <h4 id="success-student-name" class="font-extrabold text-base text-slate-900 truncate">-</h4>
                        <p id="success-student-info" class="text-slate-500 font-mono text-[11px]">-</p>
                        <div class="pt-1.5 mt-1.5 border-t border-slate-200 flex justify-between">
                            <span class="text-slate-500">Dibayar:</span>
                            <span id="success-paid-amount" class="font-mono font-extrabold text-rose-600">Rp 0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Sisa Saldo:</span>
                            <span id="success-rem-balance" class="font-mono font-extrabold text-emerald-600">Rp 0</span>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="closePaymentModal()"
                        class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold">
                    Selesai & Tutup
                </button>
            </div>

        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    let calcVal = "0";
    let pendingScanData = null;
    let autoConfirmTimer = null;

    function updateCalcDisplay() {
        const num = parseInt(calcVal, 10) || 0;
        document.getElementById('calc-display').innerText = "Rp " + num.toLocaleString('id-ID');
    }

    function calcInput(char) {
        if (calcVal === "0" && char !== "000") {
            calcVal = char;
        } else {
            calcVal += char;
        }
        updateCalcDisplay();
    }

    function calcClear() {
        calcVal = "0";
        updateCalcDisplay();
    }

    function calcAddQuick(amount) {
        let current = parseInt(calcVal, 10) || 0;
        current += amount;
        calcVal = current.toString();
        updateCalcDisplay();
    }

    function openPaymentModal() {
        const bill = parseInt(calcVal, 10) || 0;
        if (bill <= 0) {
            alert("Masukkan nominal tagihan belanja terlebih dahulu.");
            return;
        }
        resetModalToScan();
        document.getElementById('payment-modal').classList.remove('hidden');
        setTimeout(() => {
            const hwInput = document.getElementById('rfid-hw-input');
            if (hwInput) hwInput.focus();
        }, 100);
    }

    function closePaymentModal() {
        if (autoConfirmTimer) clearInterval(autoConfirmTimer);
        document.getElementById('payment-modal').classList.add('hidden');
    }

    function resetModalToScan() {
        if (autoConfirmTimer) clearInterval(autoConfirmTimer);
        pendingScanData = null;
        document.getElementById('payment-step-scan').classList.remove('hidden');
        document.getElementById('payment-step-detected').classList.add('hidden');
        document.getElementById('payment-step-success').classList.add('hidden');
        document.getElementById('payment-modal-title').innerText = "Tempelkan Kartu RFID";
        document.getElementById('rfid-hw-input').value = "";
    }

    function selectCardForPayment(uid) {
        let bill = parseInt(calcVal, 10) || 0;
        if (bill <= 0) {
            calcVal = "15000";
            updateCalcDisplay();
        }
        openPaymentModal();
        setTimeout(() => handleHardwareScan(uid), 150);
    }

    async function handleHardwareScan(uid) {
        if (!uid || !uid.trim()) return;
        const cleanUid = uid.trim().toUpperCase();
        const bill = parseInt(calcVal, 10) || 0;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch("{{ route('canteen.scan') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json"
                },
                body: JSON.stringify({ card_uid: cleanUid, amount: bill })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                playRfidSound('error');
                speakVoice(data.message || "Kartu tidak valid");
                alert(data.message || "Kartu tidak valid.");
                return;
            }

            playRfidSound('success');
            speakVoice(`Kartu terdeteksi, ${data.student.full_name}`);
            pendingScanData = { uid: cleanUid, data: data };

            document.getElementById('payment-step-scan').classList.add('hidden');
            document.getElementById('payment-step-success').classList.add('hidden');
            document.getElementById('payment-step-detected').classList.remove('hidden');
            document.getElementById('payment-modal-title').innerText = "Verifikasi Identitas & Konfirmasi Bayar";

            document.getElementById('modal-student-photo').src = data.student.photo_url;
            document.getElementById('modal-student-name').innerText = data.student.full_name;
            document.getElementById('modal-student-info').innerText = `${data.student.class_name} • NISN: ${data.student.nisn}`;
            document.getElementById('modal-student-school').innerText = `${data.student.school_name} • ${data.student.gender_label || ''}`;
            document.getElementById('modal-student-parent').innerText = `Orang Tua/Wali: ${data.student.parent_name || '-'}`;
            document.getElementById('modal-card-uid').innerText = `UID: ${data.card.card_uid}`;

            const statusBadge = document.getElementById('modal-student-status');
            statusBadge.innerText = data.card.status;
            statusBadge.className = data.card.status === 'ACTIVE'
                ? "px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold text-[10px]"
                : "px-2 py-0.5 rounded bg-rose-500/20 text-rose-400 font-bold text-[10px]";

            document.getElementById('modal-cur-balance').innerText = data.card.balance_formatted;
            document.getElementById('modal-rem-limit').innerText = data.card.remaining_limit_formatted || data.card.daily_limit_formatted;
            document.getElementById('modal-bill-deduct').innerText = "- " + data.bill.amount_formatted;
            document.getElementById('modal-rem-balance').innerText = data.bill.balance_after_formatted;

            const warningBox = document.getElementById('modal-warning-box');
            const warningText = document.getElementById('modal-warning-text');
            const confirmBtn = document.getElementById('btn-confirm-payment');

            if (data.card.status !== 'ACTIVE') {
                warningBox.classList.remove('hidden');
                warningText.innerText = `Kartu ${data.card.status}. Transaksi ditolak.`;
                confirmBtn.disabled = true;
                return;
            }

            warningBox.classList.add('hidden');
            confirmBtn.disabled = false;

            let countdown = 5;
            const confirmText = document.getElementById('btn-confirm-text');
            confirmText.innerText = `Konfirmasi Bayar (${countdown}s)`;

            if (autoConfirmTimer) clearInterval(autoConfirmTimer);
            autoConfirmTimer = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    confirmText.innerText = `Konfirmasi Bayar (${countdown}s)`;
                } else {
                    clearInterval(autoConfirmTimer);
                    executePaymentTransaction();
                }
            }, 1000);

        } catch (e) {
            playRfidSound('error');
            alert("Koneksi gagal.");
        }
    }

    async function executePaymentTransaction() {
        if (autoConfirmTimer) clearInterval(autoConfirmTimer);
        if (!pendingScanData) return;

        const confirmBtn = document.getElementById('btn-confirm-payment');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = `<span id="btn-confirm-text">Memproses...</span>`;

        const bill = parseInt(calcVal, 10) || 0;
        const tenant = document.getElementById('pos-tenant').value;
        const menuName = document.getElementById('pos-menu-name').value;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch("{{ route('canteen.pay') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    card_uid: pendingScanData.uid,
                    amount: bill,
                    tenant_name: tenant,
                    menu_name: menuName
                })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                playRfidSound('error');
                speakVoice(data.message || "Pembayaran gagal");
                alert(data.message || "Gagal.");
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = `<span id="btn-confirm-text">Konfirmasi Bayar</span>`;
                return;
            }

            playRfidSound('success');
            speakVoice(`Pembayaran berhasil, ${data.student.full_name}`);

            // Show Step 3 Pop-up Inside Modal
            document.getElementById('payment-step-detected').classList.add('hidden');
            document.getElementById('payment-step-success').classList.remove('hidden');
            document.getElementById('payment-modal-title').innerText = "Transaksi Belanja Berhasil";
            document.getElementById('success-receipt-no').innerText = data.receipt_number;
            document.getElementById('success-student-photo').src = data.student.photo_url;
            document.getElementById('success-student-name').innerText = data.student.full_name;
            document.getElementById('success-student-info').innerText = `${data.student.class_name} • NISN: ${data.student.nisn}`;
            document.getElementById('success-paid-amount').innerText = data.amount_formatted;
            document.getElementById('success-rem-balance').innerText = data.balance_after_formatted;

            // Update Last Buyer Identity & Photo Card on POS screen
            const buyerCard = document.getElementById('pos-last-buyer-card');
            buyerCard.classList.remove('hidden');
            document.getElementById('last-buyer-receipt').innerText = data.receipt_number;
            document.getElementById('last-buyer-time').innerText = data.time_str;
            document.getElementById('last-buyer-photo').src = data.student.photo_url;
            document.getElementById('last-buyer-name').innerText = data.student.full_name;
            document.getElementById('last-buyer-info').innerHTML = `${data.student.class_name} • NISN: ${data.student.nisn} &bull; UID: <span id="last-buyer-uid" class="text-blue-600 font-bold">${data.card.card_uid}</span>`;
            document.getElementById('last-buyer-tenant').innerText = data.tenant_name;
            document.getElementById('last-buyer-amount').innerText = data.amount_formatted;
            document.getElementById('last-buyer-balance').innerText = data.balance_after_formatted;

            // Prepend to Recent Transactions list
            const txList = document.getElementById('pos-recent-tx-list');
            const emptyMsg = document.getElementById('empty-tx-msg');
            if (emptyMsg) emptyMsg.remove();

            const txHtml = `
                <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-300 flex items-center justify-between text-xs gap-2">
                    <div class="flex items-center space-x-3 min-w-0">
                        <img src="${data.student.photo_url}"
                             alt="${data.student.full_name}"
                             class="w-10 h-12 rounded-lg object-cover border border-slate-200 bg-white flex-shrink-0">
                        <div class="min-w-0">
                            <p class="font-bold text-slate-900 truncate">${data.student.full_name}</p>
                            <p class="text-[10px] text-slate-500 font-mono">
                                ${data.student.class_name} &bull; NISN: ${data.student.nisn}
                            </p>
                            <p class="text-[10px] text-emerald-700 font-mono font-bold">${data.receipt_number} &bull; BARU SAJA</p>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-extrabold font-mono text-slate-900">${data.amount_formatted}</p>
                        <p class="text-[10px] font-mono text-emerald-600">Sisa: ${data.balance_after_formatted}</p>
                    </div>
                </div>
            `;
            txList.insertAdjacentHTML('afterbegin', txHtml);

            calcClear();
            document.getElementById('pos-menu-name').value = "";
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = `<span id="btn-confirm-text">Konfirmasi Bayar</span>`;

            setTimeout(() => {
                closePaymentModal();
            }, 3000);

        } catch (e) {
            playRfidSound('error');
            alert("Gagal memproses pembayaran.");
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = `<span id="btn-confirm-text">Konfirmasi Bayar</span>`;
        }
    }
</script>
@endsection
