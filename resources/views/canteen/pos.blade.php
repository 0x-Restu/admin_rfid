@extends('layouts.app')

@section('title', 'POS Kantin')

@section('content')
<div class="space-y-6">

    <!-- Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200">
        <div>
            <h3 class="font-extrabold text-slate-900 text-base">Kasir POS Kantin</h3>
            <p class="text-xs text-slate-400">Pembayaran via Tap Kartu RFID</p>
        </div>

        <div class="flex items-center space-x-4">
            <div class="text-right">
                <p class="text-[10px] text-slate-400 font-semibold uppercase">Penjualan Hari Ini</p>
                <p class="text-base font-extrabold text-slate-900 font-mono">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</p>
            </div>
            <a href="{{ route('canteen.transactions') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                Riwayat Transaksi
            </a>
        </div>
    </div>

    <!-- POS Register Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left: Keypad & Stan (7 Cols) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 space-y-4">
                
                <!-- Tenant Selector -->
                <div class="flex items-center space-x-3">
                    <label for="pos-tenant" class="text-xs font-semibold text-slate-600 whitespace-nowrap">Stan / Tenant:</label>
                    <select id="pos-tenant" class="w-full text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                        <option value="Kantin Bu Ani (Stand 01)">Kantin Bu Ani (Stand 01)</option>
                        <option value="Kantin Mas Joko">Kantin Mas Joko</option>
                        <option value="Kedai Minuman">Kedai Minuman</option>
                        <option value="Koperasi Siswa">Koperasi Siswa</option>
                    </select>
                </div>

                <!-- Display -->
                <div class="bg-slate-900 rounded-2xl p-5 text-right text-white">
                    <div class="text-xs text-slate-400 mb-1">Total Tagihan</div>
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
                        class="w-full py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm transition flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-satellite-dish text-xs"></i>
                    <span>Tap Kartu RFID</span>
                </button>
            </div>
        </div>

        <!-- Right: Registered Student Cards (5 Cols) -->
        <div class="lg:col-span-5 space-y-4">
            
            <!-- Registered Cards for fast tap -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-bold text-xs text-slate-900 uppercase tracking-wider">
                        Kartu Siswa Terdaftar
                    </h4>
                    <span class="text-[11px] text-slate-400">{{ $sampleStudents->count() }} Kartu</span>
                </div>
                <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    @forelse($sampleStudents as $student)
                    @if($student->rfidCard)
                    <div onclick="selectCardForPayment('{{ $student->rfidCard->card_uid }}')"
                         class="p-2.5 rounded-xl border border-slate-200 hover:border-slate-400 transition cursor-pointer flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr($student->full_name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-xs text-slate-800">{{ $student->full_name }}</p>
                                <p class="text-[10px] text-slate-400 font-mono">{{ $student->class_name }} &bull; UID: {{ $student->rfidCard->card_uid }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-emerald-600 font-mono">{{ $student->rfidCard->formatted_balance }}</span>
                        </div>
                    </div>
                    @endif
                    @empty
                    <p class="text-xs text-slate-400 text-center py-4">Belum ada kartu terdaftar.</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Transactions Feed -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200">
                <h4 class="font-bold text-xs text-slate-900 uppercase tracking-wider mb-3">
                    Transaksi Terakhir
                </h4>
                <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                    @forelse($recentTransactions as $tx)
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                        <div>
                            <p class="font-bold text-slate-800">{{ $tx->student ? $tx->student->full_name : 'Siswa' }}</p>
                            <p class="text-[10px] text-slate-400 font-mono">{{ $tx->receipt_number }} &bull; {{ $tx->created_at->format('H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold font-mono text-slate-900">Rp {{ number_format($tx->amount, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 text-center py-4">Belum ada transaksi hari ini.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

    <!-- ==================== MODAL PEMBAYARAN ==================== -->
    <div id="payment-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-sm w-full p-6 border border-slate-200 space-y-4 relative shadow-xl">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-900 text-sm" id="payment-modal-title">Tempelkan Kartu</h3>
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
                    <p class="font-bold text-slate-800 text-xs">Menunggu Tap Kartu RFID...</p>
                </div>

                <div class="w-full">
                    <div class="flex space-x-2">
                        <input type="text" id="rfid-hw-input" placeholder="Scan UID Kartu..." autofocus
                               class="w-full text-xs font-mono uppercase bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:outline-none"
                               onkeydown="if(event.key==='Enter') handleHardwareScan(this.value)">
                        <button type="button" onclick="handleHardwareScan(document.getElementById('rfid-hw-input').value)" class="px-3 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold">
                            OK
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 2: CONFIRM -->
            <div id="payment-step-detected" class="space-y-4 hidden">
                <div class="bg-slate-900 rounded-xl p-4 text-white space-y-1">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span id="modal-card-uid" class="font-mono">UID: -</span>
                        <span id="modal-student-status" class="font-bold text-emerald-400">ACTIVE</span>
                    </div>
                    <h4 id="modal-student-name" class="font-extrabold text-sm text-white">Nama Siswa</h4>
                    <p id="modal-student-info" class="text-xs text-slate-300">Kelas &bull; NISN</p>
                </div>

                <div class="bg-slate-50 rounded-xl p-3 border border-slate-200 space-y-1.5 text-xs">
                    <div class="flex justify-between text-slate-500">
                        <span>Saldo Awal:</span>
                        <span id="modal-cur-balance" class="font-mono font-bold text-slate-800">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-rose-600 font-bold">
                        <span>Tagihan:</span>
                        <span id="modal-bill-deduct" class="font-mono">- Rp 0</span>
                    </div>
                    <div class="border-t border-slate-200 pt-1.5 flex justify-between font-bold text-slate-900">
                        <span>Sisa Saldo:</span>
                        <span id="modal-rem-balance" class="font-mono text-emerald-600">Rp 0</span>
                    </div>
                </div>

                <div id="modal-warning-box" class="p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs hidden">
                    <span id="modal-warning-text" class="font-semibold"></span>
                </div>

                <div class="flex items-center space-x-2 pt-1">
                    <button type="button" onclick="resetModalToScan()" class="w-1/3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                        Ganti
                    </button>
                    <button type="button" id="btn-confirm-payment" onclick="executePaymentTransaction()"
                            class="w-2/3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold">
                        <span id="btn-confirm-text">Konfirmasi Bayar</span>
                    </button>
                </div>
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
            alert("Masukkan nominal tagihan.");
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
        document.getElementById('payment-modal-title').innerText = "Tempelkan Kartu";
        document.getElementById('rfid-hw-input').value = "";
    }

    function selectCardForPayment(uid) {
        let bill = parseInt(calcVal, 10) || 0;
        if (bill <= 0) {
            calcVal = "15000";
            updateCalcDisplay();
        }
        openPaymentModal();
        setTimeout(() => handleHardwareScan(uid), 200);
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
                alert(data.message || "Kartu tidak valid.");
                return;
            }

            playRfidSound('success');
            pendingScanData = { uid: cleanUid, data: data };

            document.getElementById('payment-step-scan').classList.add('hidden');
            document.getElementById('payment-step-detected').classList.remove('hidden');
            document.getElementById('payment-modal-title').innerText = "Konfirmasi Pembayaran";

            document.getElementById('modal-student-name').innerText = data.student.full_name;
            document.getElementById('modal-student-info').innerText = `${data.student.class_name} • NISN: ${data.student.nisn}`;
            document.getElementById('modal-card-uid').innerText = `UID: ${data.card.card_uid}`;

            const statusBadge = document.getElementById('modal-student-status');
            statusBadge.innerText = data.card.status;
            statusBadge.className = data.card.status === 'ACTIVE' ? "font-bold text-emerald-400" : "font-bold text-rose-400";

            document.getElementById('modal-cur-balance').innerText = data.card.balance_formatted;
            document.getElementById('modal-bill-deduct').innerText = "- " + data.bill.amount_formatted;
            document.getElementById('modal-rem-balance').innerText = data.bill.balance_after_formatted;

            const warningBox = document.getElementById('modal-warning-box');
            const warningText = document.getElementById('modal-warning-text');
            const confirmBtn = document.getElementById('btn-confirm-payment');

            if (data.card.status !== 'ACTIVE') {
                warningBox.classList.remove('hidden');
                warningText.innerText = `Kartu ${data.card.status}. Ditolak.`;
                confirmBtn.disabled = true;
                return;
            }

            warningBox.classList.add('hidden');
            confirmBtn.disabled = false;

            let countdown = 4;
            const confirmText = document.getElementById('btn-confirm-text');
            confirmText.innerText = `Bayar (${countdown}s)`;

            if (autoConfirmTimer) clearInterval(autoConfirmTimer);
            autoConfirmTimer = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    confirmText.innerText = `Bayar (${countdown}s)`;
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
        confirmBtn.innerText = "Memproses...";

        const bill = parseInt(calcVal, 10) || 0;
        const tenant = document.getElementById('pos-tenant').value;

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
                    tenant_name: tenant
                })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                playRfidSound('error');
                alert(data.message || "Gagal.");
                confirmBtn.disabled = false;
                confirmBtn.innerText = "Konfirmasi Bayar";
                return;
            }

            playRfidSound('success');
            alert(`Pembayaran Berhasil: Rp ${bill.toLocaleString('id-ID')}\nKwitansi: ${data.receipt_number}`);

            calcClear();
            closePaymentModal();
            window.location.reload();

        } catch (e) {
            playRfidSound('error');
            alert("Gagal memproses pembayaran.");
            confirmBtn.disabled = false;
            confirmBtn.innerText = "Konfirmasi Bayar";
        }
    }
</script>
@endsection
