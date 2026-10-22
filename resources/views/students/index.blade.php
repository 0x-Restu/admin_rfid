@extends('layouts.app')

@section('title', 'Siswa & Kartu')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200">
        <div>
            <h3 class="font-extrabold text-slate-900 text-base">Data Siswa & Kartu RFID</h3>
            <p class="text-xs text-slate-400">Pengelolaan identitas siswa, saldo kartu, dan status akses</p>
        </div>

        @if(auth()->user()->hasRole('kesiswaan', 'superadmin'))
        <button type="button" onclick="openAddStudentModal()"
                class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold transition flex items-center space-x-1.5">
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Tambah Siswa</span>
        </button>
        @endif
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200">
            <p class="text-[11px] font-semibold text-slate-400 uppercase">Kartu Aktif</p>
            <h4 class="text-xl font-extrabold text-slate-900 mt-1">{{ $activeCardsCount }}</h4>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200">
            <p class="text-[11px] font-semibold text-slate-400 uppercase">Kartu Diblokir</p>
            <h4 class="text-xl font-extrabold text-rose-600 mt-1">{{ $blockedCardsCount }}</h4>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200">
            <p class="text-[11px] font-semibold text-slate-400 uppercase">Total Saldo e-Money</p>
            <h4 class="text-xl font-extrabold text-slate-900 font-mono mt-1">Rp {{ number_format($totalBalanceSum, 0, ',', '.') }}</h4>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200">
        <form action="{{ route('students.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / NISN / UID..."
                       class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:outline-none">
            </div>

            <div>
                <select name="class_name" class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    <option value="">Semua Kelas</option>
                    @foreach($classes as $c)
                    <option value="{{ $c }}" {{ request('class_name') === $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="card_status" class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    <option value="">Semua Status Kartu</option>
                    <option value="ACTIVE" {{ request('card_status') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE (Aktif)</option>
                    <option value="BLOCKED" {{ request('card_status') === 'BLOCKED' ? 'selected' : '' }}>BLOCKED (Diblokir)</option>
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold transition">
                    Filter
                </button>
                <a href="{{ route('students.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 border-b border-slate-200 font-medium">
                    <tr>
                        <th class="p-3.5">Nama Siswa</th>
                        <th class="p-3.5">Kelas / NISN</th>
                        <th class="p-3.5">UID Kartu</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-right">Saldo</th>
                        <th class="p-3.5 text-right">Limit Harian</th>
                        <th class="p-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($students as $st)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-3.5">
                            <p class="font-bold text-slate-900">{{ $st->full_name }}</p>
                            <p class="text-[11px] text-slate-400">{{ $st->school_name }}</p>
                        </td>
                        <td class="p-3.5 font-mono">
                            <p class="text-slate-800">{{ $st->class_name }}</p>
                            <p class="text-[10px] text-slate-400">NISN: {{ $st->nisn }}</p>
                        </td>
                        <td class="p-3.5 font-mono">
                            @if($st->rfidCard)
                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-bold border border-slate-200">
                                {{ $st->rfidCard->card_uid }}
                            </span>
                            @else
                            <span class="text-slate-400 italic">Belum Ada</span>
                            @endif
                        </td>
                        <td class="p-3.5">
                            @if($st->rfidCard)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $st->rfidCard->status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                {{ $st->rfidCard->status }}
                            </span>
                            @else
                            -
                            @endif
                        </td>
                        <td class="p-3.5 text-right font-mono font-bold text-slate-900">
                            {{ $st->rfidCard ? $st->rfidCard->formatted_balance : 'Rp 0' }}
                        </td>
                        <td class="p-3.5 text-right font-mono text-slate-500">
                            {{ $st->rfidCard ? $st->rfidCard->formatted_limit : 'Rp 0' }}
                        </td>
                        <td class="p-3.5 text-center">
                            <div class="flex items-center justify-center space-x-1">
                                @if($st->rfidCard && auth()->user()->hasRole('keuangan', 'kesiswaan', 'superadmin'))
                                <button type="button" onclick="openTopupModal('{{ $st->rfidCard->id }}', '{{ $st->full_name }}', '{{ $st->rfidCard->formatted_balance }}')"
                                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-semibold transition" title="Isi Saldo">
                                    Top-Up
                                </button>
                                @endif

                                @if($st->rfidCard && auth()->user()->hasRole('kesiswaan', 'superadmin'))
                                <form action="{{ route('students.toggle-status', $st->rfidCard->id) }}" method="POST" class="inline" onsubmit="return confirm('Ubah status kartu?')">
                                    @csrf
                                    <button type="submit" class="p-1 text-slate-500 hover:text-slate-900 transition"
                                            title="{{ $st->rfidCard->status === 'ACTIVE' ? 'Blokir' : 'Aktifkan' }}">
                                        <i class="fa-solid {{ $st->rfidCard->status === 'ACTIVE' ? 'fa-lock' : 'fa-lock-open' }}"></i>
                                    </button>
                                </form>
                                @endif

                                @if(auth()->user()->hasRole('kesiswaan', 'superadmin'))
                                <form action="{{ route('students.destroy', $st->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus siswa?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 transition" title="Hapus">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400">Tidak ada data siswa.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $students->links() }}
        </div>
        @endif
    </div>

    <!-- MODAL TAMBAH SISWA -->
    <div id="add-student-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-slate-900 text-sm">Tambah Siswa & Kartu</h3>
                <button type="button" onclick="closeAddStudentModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('students.store') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="full_name" required placeholder="Nama siswa"
                           class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">NISN *</label>
                        <input type="text" name="nisn" required placeholder="Nomor NISN"
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kelas *</label>
                        <input type="text" name="class_name" required placeholder="Contoh: XII IPA 1"
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">UID Kartu RFID *</label>
                    <div class="flex space-x-2">
                        <input type="text" id="new-card-uid" name="card_uid" required placeholder="UID Kartu"
                               class="w-full text-xs font-mono uppercase bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                        <button type="button" onclick="generateRandomUID()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">
                            Acak
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Saldo Awal (Rp)</label>
                        <input type="number" name="initial_balance" value="50000" min="0" step="5000"
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Limit Harian (Rp)</label>
                        <input type="number" name="daily_limit" value="25000" min="5000" step="5000"
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeAddStudentModal()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL TOP-UP -->
    <div id="topup-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-sm w-full p-6 border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-slate-900 text-sm">Top-Up Saldo Kartu</h3>
                <button type="button" onclick="closeTopupModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form id="topup-form" method="POST" action="" class="space-y-3">
                @csrf
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                    <p class="font-bold text-slate-900" id="topup-student-name">Nama Siswa</p>
                    <p class="text-slate-400 mt-0.5">Saldo: <span class="font-mono font-bold text-emerald-600" id="topup-current-balance">Rp 0</span></p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal (Rp) *</label>
                    <input type="number" id="topup-amount-input" name="amount" required min="5000" step="5000" placeholder="50000"
                           class="w-full text-sm font-bold font-mono bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Metode *</label>
                    <select name="method" class="w-full text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                        <option value="CASH">Tunai (Kasir / Loket)</option>
                        <option value="TRANSFER">Transfer Bank / QRIS</option>
                        <option value="EDC">Mesin EDC Debit</option>
                    </select>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeTopupModal()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold">Top-Up</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    function openAddStudentModal() {
        generateRandomUID();
        document.getElementById('add-student-modal').classList.remove('hidden');
    }
    function closeAddStudentModal() {
        document.getElementById('add-student-modal').classList.add('hidden');
    }

    function generateRandomUID() {
        const hex = '0123456789ABCDEF';
        let uid = '';
        for (let i = 0; i < 7; i++) {
            uid += hex[Math.floor(Math.random() * 16)];
        }
        document.getElementById('new-card-uid').value = uid;
    }

    function openTopupModal(cardId, studentName, curBalance) {
        document.getElementById('topup-form').action = `/students/${cardId}/topup`;
        document.getElementById('topup-student-name').innerText = studentName;
        document.getElementById('topup-current-balance').innerText = curBalance;
        document.getElementById('topup-amount-input').value = "50000";
        document.getElementById('topup-modal').classList.remove('hidden');
    }
    function closeTopupModal() {
        document.getElementById('topup-modal').classList.add('hidden');
    }
</script>
@endsection
