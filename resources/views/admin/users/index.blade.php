@extends('layouts.app')

@section('title', 'Manajemen Petugas & Role')

@section('content')
<div class="space-y-6">

    <!-- Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-extrabold text-slate-900 text-lg flex items-center">
                <i class="fa-solid fa-users-gear text-purple-600 mr-2"></i> Manajemen Petugas & Role Setiap Bidang
            </h3>
            <p class="text-xs text-slate-500">Kelola akun staf sekolah, tetapkan peran bidang (RBAC), dan pantau status aktif akun</p>
        </div>

        <button type="button" onclick="openCreateUserModal()"
                class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-md shadow-purple-500/20">
            <i class="fa-solid fa-user-plus"></i>
            <span>Tambah Petugas Baru</span>
        </button>
    </div>

    <!-- Role Summary Pills -->
    <div class="grid grid-cols-2 sm:grid-cols-6 gap-3">
        <a href="{{ route('admin.users.index') }}" class="p-3 rounded-xl bg-white border border-slate-200 shadow-sm text-center">
            <span class="text-[11px] font-bold text-slate-500 block">Total Akun</span>
            <span class="text-xl font-extrabold text-slate-900">{{ $roleCounts['total'] }}</span>
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'superadmin']) }}" class="p-3 rounded-xl bg-purple-50 border border-purple-200 shadow-sm text-center">
            <span class="text-[11px] font-bold text-purple-700 block">Super Admin</span>
            <span class="text-xl font-extrabold text-purple-900">{{ $roleCounts['superadmin'] }}</span>
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'keuangan']) }}" class="p-3 rounded-xl bg-amber-50 border border-amber-200 shadow-sm text-center">
            <span class="text-[11px] font-bold text-amber-700 block">Keuangan</span>
            <span class="text-xl font-extrabold text-amber-900">{{ $roleCounts['keuangan'] }}</span>
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'kantin']) }}" class="p-3 rounded-xl bg-blue-50 border border-blue-200 shadow-sm text-center">
            <span class="text-[11px] font-bold text-blue-700 block">Kantin POS</span>
            <span class="text-xl font-extrabold text-blue-900">{{ $roleCounts['kantin'] }}</span>
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'kesiswaan']) }}" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm text-center">
            <span class="text-[11px] font-bold text-emerald-700 block">Kesiswaan</span>
            <span class="text-xl font-extrabold text-emerald-900">{{ $roleCounts['kesiswaan'] }}</span>
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'akademik']) }}" class="p-3 rounded-xl bg-indigo-50 border border-indigo-200 shadow-sm text-center">
            <span class="text-[11px] font-bold text-indigo-700 block">Akademik</span>
            <span class="text-xl font-extrabold text-indigo-900">{{ $roleCounts['akademik'] }}</span>
        </a>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase font-semibold">
                    <tr>
                        <th class="p-3.5">Nama Petugas</th>
                        <th class="p-3.5">Email & Kontak</th>
                        <th class="p-3.5">Role / Peran Bidang</th>
                        <th class="p-3.5">Status Akun</th>
                        <th class="p-3.5">Login Terakhir</th>
                        <th class="p-3.5 text-center">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($users as $u)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-3.5">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-9 h-9 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 text-sm">{{ $u->name }}</p>
                                    <p class="text-[11px] text-slate-400">ID Petugas #{{ $u->id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-3.5">
                            <p class="font-semibold text-slate-800">{{ $u->email }}</p>
                            <p class="text-[11px] text-slate-400">{{ $u->phone ?: '-' }}</p>
                        </td>
                        <td class="p-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $u->role_badge['badge_class'] }}">
                                <i class="fa-solid {{ $u->role_badge['icon'] }} mr-1.5"></i>
                                {{ $u->role_badge['name'] }}
                            </span>
                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $u->bidang_name }}</p>
                        </td>
                        <td class="p-3.5">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $u->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                {{ $u->is_active ? 'AKTIF' : 'NONAKTIF' }}
                            </span>
                        </td>
                        <td class="p-3.5 font-mono text-slate-500 text-[11px]">
                            @if($u->last_login_at)
                                {{ $u->last_login_at->format('d/m/Y H:i') }}
                                <span class="block text-[10px] text-slate-400 font-sans">IP: {{ $u->last_login_ip }}</span>
                            @else
                                <span class="text-slate-400 italic">Belum login</span>
                            @endif
                        </td>
                        <td class="p-3.5 text-center">
                            <div class="flex items-center justify-center space-x-1.5">
                                <!-- Reset Password -->
                                <button type="button" onclick="openResetPasswordModal('{{ $u->id }}', '{{ $u->name }}')"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition" title="Ganti Kata Sandi">
                                    <i class="fa-solid fa-key"></i>
                                </button>

                                @if($u->id !== auth()->id())
                                <!-- Toggle Active Status Form -->
                                <form action="{{ route('admin.users.toggle-status', $u->id) }}" method="POST" class="inline" onsubmit="return confirm('Ubah status aktif akun ini?')">
                                    @csrf
                                    <button type="submit" class="p-1.5 {{ $u->is_active ? 'bg-amber-50 hover:bg-amber-100 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700' }} rounded-lg transition"
                                            title="{{ $u->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}">
                                        <i class="fa-solid {{ $u->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                    </button>
                                </form>

                                <!-- Delete Form -->
                                <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus permanen akun pengguna ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg transition" title="Hapus Akun">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400">Tidak ada pengguna ditemukan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $users->links() }}
        </div>
        @endif
    </div>

    <!-- ==================== MODAL TAMBAH PETUGAS ==================== -->
    <div id="create-user-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-slate-900 text-base">Tambah Akun Petugas Baru</h3>
                <button type="button" onclick="closeCreateUserModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap & Gelar *</label>
                    <input type="text" name="name" required placeholder="Contoh: Ahmad Fauzan, S.Kom"
                           class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email *</label>
                    <input type="email" name="email" required placeholder="staf@educard.sch.id"
                           class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Peran / Role Bidang *</label>
                    <select name="role" required class="w-full text-xs font-bold bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                        <option value="superadmin">Super Administrator (Akses Penuh Semua Modul)</option>
                        <option value="keuangan">Bidang Keuangan (SPP, Tabungan, Top-Up)</option>
                        <option value="kantin">Bidang Kantin (POS Kasir & Penjualan)</option>
                        <option value="kesiswaan">Bidang Kesiswaan (Presensi Gerbang & Kartu)</option>
                        <option value="akademik">Bidang Akademik (Rekap Kehadiran Guru)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp / HP</label>
                    <input type="text" name="phone" placeholder="08123456789"
                           class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi Awal * (Min 8 karakter)</label>
                    <input type="password" name="password" required value="Educard123!"
                           class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeCreateUserModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold shadow-md shadow-purple-500/20">
                        Buat Akun Petugas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL RESET PASSWORD ==================== -->
    <div id="reset-pwd-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-slate-900 text-base">Reset Kata Sandi Petugas</h3>
                <button type="button" onclick="closeResetPasswordModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="reset-pwd-form" method="POST" action="" class="space-y-4">
                @csrf
                <p class="text-xs text-slate-600">Perbarui kata sandi untuk <strong id="reset-pwd-name"></strong>:</p>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi Baru *</label>
                    <input type="password" name="new_password" required minlength="8" placeholder="Minimal 8 karakter"
                           class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeResetPasswordModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold shadow-md shadow-purple-500/20">
                        Simpan Sandi Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    function openCreateUserModal() {
        document.getElementById('create-user-modal').classList.remove('hidden');
    }
    function closeCreateUserModal() {
        document.getElementById('create-user-modal').classList.add('hidden');
    }

    function openResetPasswordModal(userId, userName) {
        const form = document.getElementById('reset-pwd-form');
        form.action = `/admin/users/${userId}/reset-password`;
        document.getElementById('reset-pwd-name').innerText = userName;
        document.getElementById('reset-pwd-modal').classList.remove('hidden');
    }
    function closeResetPasswordModal() {
        document.getElementById('reset-pwd-modal').classList.add('hidden');
    }
</script>
@endsection
