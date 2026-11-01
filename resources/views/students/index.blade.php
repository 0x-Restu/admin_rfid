@extends('layouts.app')

@section('title', 'Siswa & Kartu')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-extrabold text-slate-900 text-base flex items-center gap-2">
                <i class="fa-solid fa-id-card text-slate-800"></i>
                <span>Data Identitas Siswa, Foto & Aktivasi Kartu RFID</span>
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Kelola identitas & foto siswa, aktivasi kartu RFID pasca-import spreadsheet, dan kenaikan/pindah kelas angkatan</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Quick Filter Button: Belum Aktivasi -->
            <a href="{{ route('students.index', ['card_status' => 'UNACTIVATED']) }}"
               class="px-3.5 py-2 {{ request('card_status') === 'UNACTIVATED' ? 'bg-amber-600 text-white' : 'bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300' }} rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                <i class="fa-solid fa-rss"></i>
                <span>Belum Aktivasi ({{ $unactivatedCardsCount }})</span>
            </a>

            @if(auth()->user()->hasRole('kesiswaan', 'superadmin'))
            <!-- Tombol Naik / Pindah Kelas -->
            <button type="button" onclick="openPromoteClassModal()"
                    class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                <i class="fa-solid fa-graduation-cap"></i>
                <span>Naik / Pindah Kelas</span>
            </button>
            @endif

            <!-- Export Spreadsheet Buttons -->
            <a href="{{ route('students.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
               class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                <i class="fa-solid fa-file-excel"></i>
                <span>Export Excel</span>
            </a>

            <a href="{{ route('students.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
               class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition flex items-center space-x-1.5 border border-slate-200">
                <i class="fa-solid fa-file-csv text-emerald-600"></i>
                <span>CSV</span>
            </a>

            @if(auth()->user()->hasRole('kesiswaan', 'superadmin'))
            <button type="button" onclick="openImportModal()"
                    class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                <i class="fa-solid fa-file-import"></i>
                <span>Import Spreadsheet</span>
            </button>

            <button type="button" onclick="openAddStudentModal()"
                    class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah Siswa</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Quick Stats (4 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <a href="{{ route('students.index', ['card_status' => 'ACTIVE']) }}" class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-emerald-400 transition block">
            <p class="text-[11px] font-semibold text-slate-400 uppercase">Kartu Aktif</p>
            <h4 class="text-xl font-extrabold text-emerald-600 mt-1">{{ $activeCardsCount }}</h4>
        </a>

        <a href="{{ route('students.index', ['card_status' => 'UNACTIVATED']) }}" class="bg-white p-4 rounded-2xl border {{ request('card_status') === 'UNACTIVATED' ? 'border-amber-500 ring-2 ring-amber-200' : 'border-slate-200 hover:border-amber-400' }} transition block">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-amber-600 uppercase">Belum Aktivasi RFID</p>
                <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[9px] font-bold">Klik Filter</span>
            </div>
            <h4 class="text-xl font-extrabold text-amber-600 mt-1">{{ $unactivatedCardsCount }}</h4>
        </a>

        <a href="{{ route('students.index', ['card_status' => 'BLOCKED']) }}" class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-rose-400 transition block">
            <p class="text-[11px] font-semibold text-slate-400 uppercase">Kartu Diblokir</p>
            <h4 class="text-xl font-extrabold text-rose-600 mt-1">{{ $blockedCardsCount }}</h4>
        </a>

        <div class="bg-white p-4 rounded-2xl border border-slate-200">
            <p class="text-[11px] font-semibold text-slate-400 uppercase">Total Saldo e-Money</p>
            <h4 class="text-xl font-extrabold text-slate-900 font-mono mt-1">Rp {{ number_format($totalBalanceSum, 0, ',', '.') }}</h4>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200">
        <form action="{{ route('students.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / NISN / Kelas / UID..."
                       class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:outline-none">
            </div>

            <div>
                <select name="class_name" class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    <option value="">Semua Kelas ({{ $classes->count() }} Kelas)</option>
                    @foreach($classes as $c)
                    <option value="{{ $c }}" {{ request('class_name') === $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="card_status" class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    <option value="">Semua Status Kartu</option>
                    <option value="UNACTIVATED" {{ request('card_status') === 'UNACTIVATED' ? 'selected' : '' }}>BELUM AKTIVASI (Belum Ada No RFID)</option>
                    <option value="ACTIVE" {{ request('card_status') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE (Kartu Aktif)</option>
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
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 border-b border-slate-200 font-semibold uppercase">
                    <tr>
                        <th class="p-3.5">Foto & Identitas Siswa</th>
                        <th class="p-3.5">Kelas & NISN</th>
                        <th class="p-3.5">UID Kartu RFID</th>
                        <th class="p-3.5">Status Kartu</th>
                        <th class="p-3.5 text-right">Saldo</th>
                        <th class="p-3.5 text-right">Limit Harian</th>
                        <th class="p-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($students as $st)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-3.5">
                            <div class="flex items-center space-x-3">
                                <img src="{{ $st->photo }}"
                                     alt="{{ $st->full_name }}"
                                     onclick="openIdentityPreview({{ Js::from([
                                         'full_name' => $st->full_name,
                                         'nisn' => $st->nisn,
                                         'gender_label' => $st->gender_label,
                                         'class_name' => $st->class_name,
                                         'school_name' => $st->school_name,
                                         'photo' => $st->photo,
                                         'card_uid' => $st->rfidCard ? $st->rfidCard->card_uid : 'BELUM AKTIVASI',
                                         'status' => $st->rfidCard ? $st->rfidCard->status : 'BELUM AKTIVASI',
                                         'balance' => $st->rfidCard ? $st->rfidCard->formatted_balance : 'Rp 0',
                                         'parent_name' => $st->parent ? $st->parent->full_name : '-',
                                         'parent_phone' => $st->parent ? ($st->parent->phone_number ?? '-') : '-',
                                     ]) }})"
                                     onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($st->full_name) }}&background=0f172a&color=ffffff&size=128&bold=true';"
                                     class="w-11 h-14 rounded-xl object-cover bg-slate-200 border border-slate-200 shadow-sm cursor-pointer hover:ring-2 hover:ring-slate-900 transition flex-shrink-0"
                                     title="Klik untuk lihat Kartu Identitas Siswa">
                                <div>
                                    <div class="flex items-center space-x-1.5">
                                        <p class="font-bold text-slate-900">{{ $st->full_name }}</p>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $st->gender === 'P' ? 'bg-pink-50 text-pink-700' : 'bg-blue-50 text-blue-700' }}">
                                            {{ $st->gender }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-400">{{ $st->school_name }}</p>
                                    @if($st->parent)
                                    <p class="text-[10px] text-slate-400 mt-0.5">
                                        <i class="fa-solid fa-user-shield mr-1"></i>Wali: {{ $st->parent->full_name }}
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="p-3.5 font-mono">
                            <span class="inline-block px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold text-[11px] border border-indigo-100">
                                {{ $st->class_name }}
                            </span>
                            <p class="text-[11px] text-slate-500 mt-1">NISN: {{ $st->nisn }}</p>
                        </td>
                        <td class="p-3.5 font-mono">
                            @if($st->rfidCard)
                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-800 font-bold border border-slate-200">
                                {{ $st->rfidCard->card_uid }}
                            </span>
                            @else
                            <button type="button"
                                    onclick="openActivateCardModal({{ Js::from([
                                        'id' => $st->id,
                                        'full_name' => $st->full_name,
                                        'nisn' => $st->nisn,
                                        'class_name' => $st->class_name,
                                        'photo' => $st->photo,
                                    ]) }})"
                                    class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 font-sans font-bold text-[11px] transition inline-flex items-center space-x-1">
                                <i class="fa-solid fa-rss text-[10px]"></i>
                                <span>Belum Aktivasi</span>
                            </button>
                            @endif
                        </td>
                        <td class="p-3.5">
                            @if($st->rfidCard)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $st->rfidCard->status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                {{ $st->rfidCard->status }}
                            </span>
                            @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                BELUM AKTIVASI
                            </span>
                            @endif
                        </td>
                        <td class="p-3.5 text-right font-mono font-bold text-slate-900">
                            {{ $st->rfidCard ? $st->rfidCard->formatted_balance : 'Rp 0' }}
                        </td>
                        <td class="p-3.5 text-right font-mono text-slate-500">
                            {{ $st->rfidCard ? $st->rfidCard->formatted_limit : '-' }}
                        </td>
                        <td class="p-3.5 text-center">
                            <div class="flex items-center justify-center space-x-1">
                                @if(!$st->rfidCard && auth()->user()->hasRole('kesiswaan', 'superadmin'))
                                <button type="button"
                                        onclick="openActivateCardModal({{ Js::from([
                                            'id' => $st->id,
                                            'full_name' => $st->full_name,
                                            'nisn' => $st->nisn,
                                            'class_name' => $st->class_name,
                                            'photo' => $st->photo,
                                        ]) }})"
                                        class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold transition shadow-sm flex items-center space-x-1"
                                        title="Tap & Aktivasi Kartu RFID">
                                    <i class="fa-solid fa-id-card-clip text-[10px]"></i>
                                    <span>Aktivasi RFID</span>
                                </button>
                                @endif

                                @if($st->rfidCard && auth()->user()->hasRole('keuangan', 'kesiswaan', 'superadmin'))
                                <button type="button" onclick="openTopupModal('{{ $st->rfidCard->id }}', '{{ addslashes($st->full_name) }}', '{{ $st->rfidCard->formatted_balance }}')"
                                        class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-lg text-xs font-semibold transition" title="Isi Saldo">
                                    Top-Up
                                </button>
                                @endif

                                @if(auth()->user()->hasRole('kesiswaan', 'superadmin'))
                                <button type="button"
                                        onclick="openEditStudentModal({{ Js::from([
                                            'id' => $st->id,
                                            'full_name' => $st->full_name,
                                            'nisn' => $st->nisn,
                                            'gender' => $st->gender,
                                            'class_name' => $st->class_name,
                                            'school_name' => $st->school_name,
                                            'photo_url' => $st->photo_url,
                                            'photo_preview' => $st->photo,
                                            'card_uid' => $st->rfidCard ? $st->rfidCard->card_uid : '',
                                            'daily_limit' => $st->rfidCard ? (float) $st->rfidCard->daily_spending_limit : 25000,
                                            'parent_name' => $st->parent ? $st->parent->full_name : '',
                                            'parent_phone' => $st->parent ? ($st->parent->phone_number ?? '') : '',
                                        ]) }})"
                                        class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                        title="Edit Identitas, Kelas & Foto Siswa">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                @endif

                                @if($st->rfidCard && auth()->user()->hasRole('kesiswaan', 'superadmin'))
                                <form action="{{ route('students.toggle-status', $st->rfidCard->id) }}" method="POST" class="inline" onsubmit="return confirm('Ubah status kartu?')">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition"
                                            title="{{ $st->rfidCard->status === 'ACTIVE' ? 'Blokir Kartu' : 'Aktifkan Kartu' }}">
                                        <i class="fa-solid {{ $st->rfidCard->status === 'ACTIVE' ? 'fa-lock' : 'fa-lock-open' }}"></i>
                                    </button>
                                </form>
                                @endif

                                @if(auth()->user()->hasRole('kesiswaan', 'superadmin'))
                                <form action="{{ route('students.destroy', $st->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus siswa ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400">Tidak ada data siswa yang sesuai filter.</td>
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

    <!-- ==================== MODAL AKTIVASI KARTU RFID (PASCA IMPORT SPREADSHEET) ==================== -->
    <div id="activate-card-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 border border-slate-200 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center text-xs">
                        <i class="fa-solid fa-rss"></i>
                    </span>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-sm">Aktivasi Kartu RFID Siswa</h3>
                        <p class="text-[11px] text-slate-400">Tempelkan kartu RFID fisik ke alat pembaca (scanner)</p>
                    </div>
                </div>
                <button type="button" onclick="closeActivateCardModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form id="activate-card-form" method="POST" action="" class="space-y-4">
                @csrf

                <!-- Student Identity Preview -->
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex items-center space-x-3.5">
                    <img id="activate-student-photo" src="" alt="Foto Siswa"
                         class="w-14 h-18 rounded-xl object-cover border border-slate-300 bg-white flex-shrink-0">
                    <div class="min-w-0 flex-1 text-xs">
                        <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-bold text-[10px]">BELUM AKTIVASI</span>
                        <h4 id="activate-student-name" class="font-extrabold text-slate-900 text-sm mt-1 truncate">Nama Siswa</h4>
                        <p id="activate-student-info" class="text-slate-500 font-mono text-[11px]">Kelas • NISN</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-slate-800 mb-1">
                        Nomor / UID Kartu RFID *
                    </label>
                    <div class="flex space-x-2">
                        <input type="text" id="activate-card-uid" name="card_uid" required placeholder="Scan / Tempelkan Kartu RFID..."
                               class="w-full text-sm font-mono font-bold uppercase bg-amber-50/40 border-2 border-amber-400 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                        <button type="button" onclick="generateRandomUIDFor('activate-card-uid')"
                                class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold whitespace-nowrap">
                            Acak UID
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Kursor otomatis aktif di kolom ini — langsung tap kartu RFID pada USB Reader.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Saldo Awal (Rp)</label>
                        <input type="number" name="initial_balance" value="0" min="0" step="5000"
                               class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Limit Belanja Harian (Rp)</label>
                        <input type="number" name="daily_limit" value="25000" min="5000" step="5000"
                               class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeActivateCardModal()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-sm flex items-center space-x-1.5">
                        <i class="fa-solid fa-check-circle"></i>
                        <span>Aktifkan Kartu Sekarang</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL NAIK KELAS / PINDAH KELAS ANGKATAN ==================== -->
    <div id="promote-class-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 border border-slate-200 space-y-4 my-8 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-sm">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-sm">Naik Kelas & Pindah Kelas Angkatan</h3>
                        <p class="text-[11px] text-slate-400">Pindahkan satu kelas penuh, siswa terpilih, atau seluruh angkatan sekaligus (Contoh: X RPL A → XI RPL B)</p>
                    </div>
                </div>
                <button type="button" onclick="closePromoteClassModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- Mode Switcher Tabs -->
            <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-xl text-xs font-bold">
                <button type="button" id="tab-btn-single" onclick="switchPromoteTab('single')"
                        class="py-2 px-3 rounded-lg bg-white text-slate-900 shadow-sm transition">
                    1. Pindah Per Kelas / Pilih Siswa (X RPL A → XI RPL B)
                </button>
                <button type="button" id="tab-btn-batch" onclick="switchPromoteTab('batch')"
                        class="py-2 px-3 rounded-lg text-slate-600 hover:text-slate-900 transition">
                    2. Naik Kelas Satu Angkatan Sekaligus
                </button>
            </div>

            <!-- TAB 1: SINGLE CLASS / SELECTED STUDENTS PROMOTION -->
            <form id="promote-form-single" action="{{ route('students.promote-class') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="mode" value="single">

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end bg-indigo-50/60 p-4 rounded-xl border border-indigo-100">
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Dari Kelas Asal *</label>
                        <select id="promote-source-class" name="source_class" required onchange="onSourceClassChange(this.value)"
                                class="w-full text-xs font-bold bg-white border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-indigo-600 focus:outline-none">
                            <option value="">-- Pilih Kelas Asal --</option>
                            @foreach($classCounts as $cc)
                            <option value="{{ $cc->class_name }}">{{ $cc->class_name }} ({{ $cc->total }} Siswa)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2 text-center hidden sm:flex items-center justify-center pb-2 text-indigo-600 font-extrabold">
                        <i class="fa-solid fa-arrow-right text-lg"></i>
                    </div>

                    <div class="sm:col-span-5">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700">Ke Kelas Tujuan Baru *</label>
                            <button type="button" onclick="autoSuggestTargetClass()" class="text-[10px] font-bold text-indigo-600 hover:underline">
                                Otomatis Naik Tingkat
                            </button>
                        </div>
                        <input type="text" id="promote-target-class" name="target_class" required placeholder="Contoh: XI RPL B"
                               list="existing-classes-datalist"
                               class="w-full text-xs font-bold bg-white border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-indigo-600 focus:outline-none">
                        <datalist id="existing-classes-datalist">
                            @foreach($classes as $c)
                            <option value="{{ $c }}">
                            @endforeach
                        </datalist>
                    </div>
                </div>

                <!-- Student Checklist in Selected Source Class -->
                <div id="promote-student-list-box" class="border border-slate-200 rounded-xl overflow-hidden hidden">
                    <div class="bg-slate-50 px-4 py-2.5 border-b border-slate-200 flex items-center justify-between text-xs">
                        <label class="flex items-center space-x-2 font-bold text-slate-700 cursor-pointer">
                            <input type="checkbox" id="promote-check-all" checked onchange="toggleAllPromoteStudents(this.checked)"
                                   class="rounded text-indigo-600 focus:ring-indigo-500">
                            <span>Pilih Semua Siswa di Kelas Ini (<span id="promote-selected-count">0</span> siswa)</span>
                        </label>
                        <span class="text-[11px] text-slate-400">Hapus centang jika ada siswa yang tidak ikut pindah</span>
                    </div>
                    <div id="promote-students-container" class="max-h-52 overflow-y-auto divide-y divide-slate-100 p-2 grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closePromoteClassModal()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm flex items-center space-x-1.5">
                        <i class="fa-solid fa-people-arrows"></i>
                        <span>Pindahkan Kelas Sekarang</span>
                    </button>
                </div>
            </form>

            <!-- TAB 2: BATCH ALL CLASSES MAPPING -->
            <form id="promote-form-batch" action="{{ route('students.promote-class') }}" method="POST" class="space-y-4 hidden">
                @csrf
                <input type="hidden" name="mode" value="batch">

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs">
                    <div>
                        <p class="font-bold text-slate-800">Pemetaan Kenaikan Kelas Satu Angkatan / Sekolah</p>
                        <p class="text-[11px] text-slate-500">Isi kelas baru di kolom kanan. Kosongkan jika kelas tersebut tidak ingin diubah.</p>
                    </div>
                    <button type="button" onclick="autoFillBatchPromotion()"
                            class="px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 text-indigo-800 rounded-lg text-xs font-bold transition whitespace-nowrap">
                        <i class="fa-solid fa-wand-magic-sparkles mr-1"></i>Otomatis X→XI & XI→XII
                    </button>
                </div>

                <div class="max-h-64 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100">
                    @forelse($classCounts as $cc)
                    <div class="p-3 flex items-center justify-between gap-3 text-xs hover:bg-slate-50">
                        <div class="w-5/12">
                            <span class="font-bold text-slate-900 font-mono">{{ $cc->class_name }}</span>
                            <span class="ml-1.5 px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-semibold">
                                {{ $cc->total }} siswa
                            </span>
                        </div>
                        <div class="w-1/12 text-center text-slate-400">
                            <i class="fa-solid fa-arrow-right"></i>
                        </div>
                        <div class="w-6/12">
                            <input type="text"
                                   name="mappings[{{ $cc->class_name }}]"
                                   data-source-class="{{ $cc->class_name }}"
                                   placeholder="Ketik kelas baru (misal: XI RPL B)..."
                                   class="batch-class-input w-full text-xs font-bold bg-white border border-slate-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-indigo-600 focus:outline-none">
                        </div>
                    </div>
                    @empty
                    <p class="p-6 text-center text-xs text-slate-400">Belum ada data kelas siswa.</p>
                    @endforelse
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closePromoteClassModal()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm flex items-center space-x-1.5">
                        <i class="fa-solid fa-check-double"></i>
                        <span>Proses Kenaikan Kelas Massal</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL TAMBAH SISWA & FOTO ==================== -->
    <div id="add-student-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 border border-slate-200 space-y-4 my-8 shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm">Tambah Identitas Siswa & Foto</h3>
                    <p class="text-[11px] text-slate-400">UID Kartu RFID boleh dikosongkan jika kartu belum diaktivasi</p>
                </div>
                <button type="button" onclick="closeAddStudentModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf

                <!-- Photo Upload & Preview -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center space-x-3.5">
                    <img id="add-photo-preview"
                         src="https://ui-avatars.com/api/?name=Siswa+Baru&background=0f172a&color=ffffff&size=128&bold=true"
                         alt="Preview Foto"
                         class="w-16 h-20 rounded-xl object-cover border border-slate-300 bg-white flex-shrink-0">
                    <div class="flex-1 space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Foto Siswa (Upload File atau Link URL)</label>
                        <input type="file" name="photo_file" accept="image/*"
                               onchange="previewImageFile(this, 'add-photo-preview')"
                               class="block w-full text-[11px] text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800">
                        <input type="text" name="photo_url" placeholder="Atau tempel URL foto (https://...)"
                               oninput="previewImageUrl(this.value, 'add-photo-preview')"
                               class="w-full text-[11px] bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                        <input type="text" name="full_name" required placeholder="Nama lengkap siswa"
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Kelamin *</label>
                        <select name="gender" class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">NISN *</label>
                        <input type="text" name="nisn" required placeholder="Nomor NISN"
                               class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kelas *</label>
                        <input type="text" name="class_name" required placeholder="Contoh: X RPL A"
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Orang Tua / Wali</label>
                        <input type="text" name="parent_name" placeholder="Nama orang tua"
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">No. HP Orang Tua</label>
                        <input type="text" name="parent_phone" placeholder="0812xxxx"
                               class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">UID Kartu RFID (Opsional)</label>
                        <div class="flex space-x-1.5">
                            <input type="text" id="new-card-uid" name="card_uid" placeholder="Kosongkan jika belum ada"
                                   class="w-full text-xs font-mono uppercase bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                            <button type="button" onclick="generateRandomUIDFor('new-card-uid')" class="px-2.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">
                                Acak
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Sekolah</label>
                        <input type="text" name="school_name" value="SMA Negeri 1 Prestasi"
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Saldo Awal (Rp)</label>
                        <input type="number" name="initial_balance" value="0" min="0" step="5000"
                               class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Limit Harian (Rp)</label>
                        <input type="number" name="daily_limit" value="25000" min="5000" step="5000"
                               class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeAddStudentModal()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold">Simpan Siswa</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL EDIT SISWA & FOTO ==================== -->
    <div id="edit-student-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 border border-slate-200 space-y-4 my-8 shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm">Edit Identitas, Kelas & Foto Siswa</h3>
                    <p class="text-[11px] text-slate-400">Perbarui foto profil, kelas, identitas, atau UID Kartu RFID</p>
                </div>
                <button type="button" onclick="closeEditStudentModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form id="edit-student-form" action="" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                @method('PUT')

                <!-- Photo Upload & Preview -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center space-x-3.5">
                    <img id="edit-photo-preview"
                         src=""
                         alt="Preview Foto"
                         class="w-16 h-20 rounded-xl object-cover border border-slate-300 bg-white flex-shrink-0">
                    <div class="flex-1 space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Ganti Foto Siswa (Upload File / URL)</label>
                        <input type="file" name="photo_file" accept="image/*"
                               onchange="previewImageFile(this, 'edit-photo-preview')"
                               class="block w-full text-[11px] text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800">
                        <input type="text" id="edit-photo-url" name="photo_url" placeholder="Atau tempel URL foto (https://...)"
                               oninput="previewImageUrl(this.value, 'edit-photo-preview')"
                               class="w-full text-[11px] bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                        <input type="text" id="edit-full-name" name="full_name" required
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Kelamin *</label>
                        <select id="edit-gender" name="gender" class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">NISN *</label>
                        <input type="text" id="edit-nisn" name="nisn" required
                               class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kelas *</label>
                        <input type="text" id="edit-class-name" name="class_name" required
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Orang Tua / Wali</label>
                        <input type="text" id="edit-parent-name" name="parent_name"
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">No. HP Orang Tua</label>
                        <input type="text" id="edit-parent-phone" name="parent_phone"
                               class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">UID Kartu RFID</label>
                        <input type="text" id="edit-card-uid" name="card_uid" placeholder="Belum Aktivasi"
                               class="w-full text-xs font-mono uppercase bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Limit Harian (Rp)</label>
                        <input type="number" id="edit-daily-limit" name="daily_limit" min="5000" step="5000"
                               class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Sekolah</label>
                        <input type="text" id="edit-school-name" name="school_name"
                               class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeEditStudentModal()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL IMPORT SPREADSHEET & FOTO ==================== -->
    <div id="import-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 hidden overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 border border-slate-200 space-y-4 my-8 shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm flex items-center gap-2">
                        <i class="fa-solid fa-file-import text-blue-600"></i>
                        <span>Import Siswa & Foto dari Spreadsheet</span>
                    </h3>
                    <p class="text-[11px] text-slate-400">Kolom <code class="font-mono">card_uid</code> boleh dikosongkan (otomatis berstatus <b>Belum Aktivasi</b>)</p>
                </div>
                <button type="button" onclick="closeImportModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- Step 1: Download Template -->
            <div class="p-3.5 bg-blue-50/70 rounded-xl border border-blue-200 space-y-2">
                <p class="text-xs font-bold text-slate-800">1. Unduh Template Spreadsheet</p>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                    Saat awal import data siswa baru, kolom <code class="font-mono bg-white px-1 rounded">card_uid</code> (Nomor RFID) <b>boleh dikosongkan</b>. Setelah diimport, siswa akan masuk daftar <b>Belum Aktivasi</b> dan kartunya dapat di-scan satu per satu melalui tombol <b>Aktivasi RFID</b>.
                </p>
                <div class="flex items-center space-x-2 pt-1">
                    <a href="{{ route('students.template', ['format' => 'xlsx']) }}"
                       class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition inline-flex items-center space-x-1.5">
                        <i class="fa-solid fa-download"></i>
                        <span>Template Excel (.xlsx)</span>
                    </a>
                    <a href="{{ route('students.template', ['format' => 'csv']) }}"
                       class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold transition inline-flex items-center space-x-1.5">
                        <i class="fa-solid fa-file-csv text-emerald-600"></i>
                        <span>Template CSV (.csv)</span>
                    </a>
                </div>
            </div>

            <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <!-- Step 2: Upload Spreadsheet -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">2. Pilih File Spreadsheet (.xlsx / .csv) *</label>
                    <input type="file" name="spreadsheet_file" required accept=".xlsx,.csv,.txt"
                           class="block w-full text-xs text-slate-700 bg-slate-50 border border-slate-300 rounded-xl p-2 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-white hover:file:bg-slate-800">
                </div>

                <!-- Step 3: Optional Batch Photo Files -->
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">
                        3. Upload File Foto Siswa Sekaligus (Opsional)
                    </label>
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        Anda dapat memilih banyak file foto sekaligus (beri nama file sesuai <b>NISN</b> seperti <code class="font-mono bg-white px-1 rounded">0081122334.jpg</code> atau isi link di kolom <code class="font-mono bg-white px-1 rounded">photo_url</code>).
                    </p>
                    <input type="file" name="photo_files[]" multiple accept="image/*"
                           class="block w-full text-xs text-slate-600 pt-1 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeImportModal()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold flex items-center space-x-1.5">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Proses Import Data</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL PREVIEW KARTU IDENTITAS SISWA ==================== -->
    <div id="identity-preview-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden border border-slate-200 shadow-2xl">
            <div class="bg-slate-900 p-4 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-id-card-clip text-emerald-400"></i>
                    <span class="font-extrabold text-xs uppercase tracking-wider">Kartu Identitas Smart RFID Siswa</span>
                </div>
                <button type="button" onclick="closeIdentityPreview()" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-5 flex items-start space-x-4">
                <img id="preview-card-photo" src="" alt="Foto Siswa"
                     class="w-24 h-32 rounded-xl object-cover border-2 border-slate-200 shadow-sm flex-shrink-0 bg-slate-100">
                <div class="space-y-2 flex-1 min-w-0 text-xs">
                    <div>
                        <span id="preview-card-status" class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">ACTIVE</span>
                        <h4 id="preview-card-name" class="font-extrabold text-base text-slate-900 mt-1 truncate">Nama Siswa</h4>
                        <p id="preview-card-school" class="text-[11px] text-slate-400">Sekolah</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase">NISN</p>
                            <p id="preview-card-nisn" class="font-mono font-bold text-slate-800">-</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase">Kelas</p>
                            <p id="preview-card-class" class="font-bold text-slate-800">-</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase">Gender</p>
                            <p id="preview-card-gender" class="font-semibold text-slate-700">-</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase">UID RFID</p>
                            <p id="preview-card-uid" class="font-mono font-bold text-blue-600">-</p>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400">Saldo e-Money:</span>
                        <span id="preview-card-balance" class="font-mono font-extrabold text-emerald-600 text-sm">Rp 0</span>
                    </div>
                </div>
            </div>
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
    const studentsByClassData = {{ Js::from($studentsByClass) }};

    function openAddStudentModal() {
        document.getElementById('new-card-uid').value = '';
        document.getElementById('add-student-modal').classList.remove('hidden');
    }
    function closeAddStudentModal() {
        document.getElementById('add-student-modal').classList.add('hidden');
    }

    function openImportModal() {
        document.getElementById('import-modal').classList.remove('hidden');
    }
    function closeImportModal() {
        document.getElementById('import-modal').classList.add('hidden');
    }

    // Activate RFID Card Modal
    function openActivateCardModal(st) {
        document.getElementById('activate-card-form').action = `/students/${st.id}/activate-card`;
        document.getElementById('activate-student-photo').src = st.photo;
        document.getElementById('activate-student-name').innerText = st.full_name;
        document.getElementById('activate-student-info').innerText = `${st.class_name} • NISN: ${st.nisn}`;
        const uidInput = document.getElementById('activate-card-uid');
        uidInput.value = '';
        document.getElementById('activate-card-modal').classList.remove('hidden');
        setTimeout(() => uidInput.focus(), 120);
    }
    function closeActivateCardModal() {
        document.getElementById('activate-card-modal').classList.add('hidden');
    }

    // Promote / Move Class Modal
    function openPromoteClassModal() {
        document.getElementById('promote-class-modal').classList.remove('hidden');
    }
    function closePromoteClassModal() {
        document.getElementById('promote-class-modal').classList.add('hidden');
    }

    function switchPromoteTab(mode) {
        const singleForm = document.getElementById('promote-form-single');
        const batchForm = document.getElementById('promote-form-batch');
        const btnSingle = document.getElementById('tab-btn-single');
        const btnBatch = document.getElementById('tab-btn-batch');

        if (mode === 'batch') {
            singleForm.classList.add('hidden');
            batchForm.classList.remove('hidden');
            btnBatch.className = "py-2 px-3 rounded-lg bg-white text-slate-900 shadow-sm transition";
            btnSingle.className = "py-2 px-3 rounded-lg text-slate-600 hover:text-slate-900 transition";
        } else {
            batchForm.classList.add('hidden');
            singleForm.classList.remove('hidden');
            btnSingle.className = "py-2 px-3 rounded-lg bg-white text-slate-900 shadow-sm transition";
            btnBatch.className = "py-2 px-3 rounded-lg text-slate-600 hover:text-slate-900 transition";
        }
    }

    // Smart helper to suggest next grade level (e.g., "X RPL A" -> "XI RPL A", "XI RPL B" -> "XII RPL B")
    function computeNextGradeName(className) {
        if (!className) return '';
        const trimmed = className.trim();
        if (/^XII\b/i.test(trimmed)) {
            return 'LULUS';
        }
        if (/^XI\b/i.test(trimmed)) {
            return trimmed.replace(/^XI\b/i, 'XII');
        }
        if (/^X\b/i.test(trimmed)) {
            return trimmed.replace(/^X\b/i, 'XI');
        }
        if (/^10\b/.test(trimmed)) {
            return trimmed.replace(/^10\b/, '11');
        }
        if (/^11\b/.test(trimmed)) {
            return trimmed.replace(/^11\b/, '12');
        }
        return trimmed;
    }

    function onSourceClassChange(sourceClass) {
        const listBox = document.getElementById('promote-student-list-box');
        const container = document.getElementById('promote-students-container');
        const targetInput = document.getElementById('promote-target-class');

        if (!sourceClass || !studentsByClassData[sourceClass]) {
            listBox.classList.add('hidden');
            container.innerHTML = '';
            return;
        }

        // Auto suggest target class if empty or auto-filled
        targetInput.value = computeNextGradeName(sourceClass);

        const list = studentsByClassData[sourceClass];
        container.innerHTML = '';
        list.forEach(st => {
            const label = document.createElement('label');
            label.className = "flex items-center space-x-2 p-2 rounded-lg hover:bg-slate-50 border border-slate-100 cursor-pointer text-xs";
            label.innerHTML = `
                <input type="checkbox" name="student_ids[]" value="${st.id}" checked
                       onchange="updatePromoteSelectedCount()"
                       class="promote-student-cb rounded text-indigo-600 focus:ring-indigo-500">
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-slate-800 truncate">${st.full_name}</p>
                    <p class="text-[10px] text-slate-400 font-mono">NISN: ${st.nisn}</p>
                </div>
            `;
            container.appendChild(label);
        });

        document.getElementById('promote-check-all').checked = true;
        updatePromoteSelectedCount();
        listBox.classList.remove('hidden');
    }

    function autoSuggestTargetClass() {
        const src = document.getElementById('promote-source-class').value;
        if (src) {
            document.getElementById('promote-target-class').value = computeNextGradeName(src);
        }
    }

    function toggleAllPromoteStudents(checked) {
        document.querySelectorAll('.promote-student-cb').forEach(cb => {
            cb.checked = checked;
        });
        updatePromoteSelectedCount();
    }

    function updatePromoteSelectedCount() {
        const checkedCount = document.querySelectorAll('.promote-student-cb:checked').length;
        document.getElementById('promote-selected-count').innerText = checkedCount;
    }

    function autoFillBatchPromotion() {
        document.querySelectorAll('.batch-class-input').forEach(inp => {
            const src = inp.getAttribute('data-source-class');
            const suggested = computeNextGradeName(src);
            if (suggested && suggested !== src) {
                inp.value = suggested;
            }
        });
    }

    function openEditStudentModal(student) {
        document.getElementById('edit-student-form').action = `/students/${student.id}`;
        document.getElementById('edit-full-name').value = student.full_name || '';
        document.getElementById('edit-nisn').value = student.nisn || '';
        document.getElementById('edit-gender').value = student.gender || 'L';
        document.getElementById('edit-class-name').value = student.class_name || '';
        document.getElementById('edit-school-name').value = student.school_name || '';
        document.getElementById('edit-photo-url').value = student.photo_url || '';
        document.getElementById('edit-photo-preview').src = student.photo_preview || '';
        document.getElementById('edit-card-uid').value = student.card_uid || '';
        document.getElementById('edit-daily-limit').value = student.daily_limit || 25000;
        document.getElementById('edit-parent-name').value = student.parent_name || '';
        document.getElementById('edit-parent-phone').value = student.parent_phone || '';
        document.getElementById('edit-student-modal').classList.remove('hidden');
    }
    function closeEditStudentModal() {
        document.getElementById('edit-student-modal').classList.add('hidden');
    }

    function openIdentityPreview(st) {
        document.getElementById('preview-card-photo').src = st.photo;
        document.getElementById('preview-card-name').innerText = st.full_name;
        document.getElementById('preview-card-school').innerText = st.school_name;
        document.getElementById('preview-card-nisn').innerText = st.nisn;
        document.getElementById('preview-card-class').innerText = st.class_name;
        document.getElementById('preview-card-gender').innerText = st.gender_label;
        document.getElementById('preview-card-uid').innerText = st.card_uid;
        document.getElementById('preview-card-balance').innerText = st.balance;
        const badge = document.getElementById('preview-card-status');
        badge.innerText = st.status;
        badge.className = st.status === 'ACTIVE'
            ? 'px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800'
            : (st.status === 'BELUM AKTIVASI' ? 'px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800' : 'px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800');
        document.getElementById('identity-preview-modal').classList.remove('hidden');
    }
    function closeIdentityPreview() {
        document.getElementById('identity-preview-modal').classList.add('hidden');
    }

    function previewImageFile(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById(previewId).src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function previewImageUrl(url, previewId) {
        if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
            document.getElementById(previewId).src = url;
        }
    }

    function generateRandomUIDFor(inputId) {
        const hex = '0123456789ABCDEF';
        let uid = '';
        for (let i = 0; i < 7; i++) {
            uid += hex[Math.floor(Math.random() * 16)];
        }
        document.getElementById(inputId).value = uid;
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
