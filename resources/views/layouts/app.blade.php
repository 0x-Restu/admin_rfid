<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EduCard') - Smart School RFID & POS</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .calc-btn { transition: all 0.12s ease; user-select: none; }
        .calc-btn:active { transform: scale(0.96); }
        .radar-beam {
            position: absolute; width: 100%; height: 100%; border-radius: 50%;
            animation: ripple 2s cubic-bezier(0, 0.2, 0.8, 1) infinite;
        }
        .radar-beam:nth-child(2) { animation-delay: 0.6s; }
        .radar-beam:nth-child(3) { animation-delay: 1.2s; }
        @keyframes ripple {
            0% { transform: scale(0.6); opacity: 0.8; }
            100% { transform: scale(2.2); opacity: 0; }
        }
    </style>
    @yield('styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex">

    @auth
    <!-- Mobile Sidebar Backdrop -->
    <div id="sidebar-backdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/40 z-40 lg:hidden hidden transition-opacity"></div>

    <!-- SIDEBAR -->
    <aside id="main-sidebar" class="fixed lg:sticky top-0 left-0 z-50 h-screen w-64 bg-white border-r border-slate-200 flex flex-col justify-between transition-transform duration-200 -translate-x-full lg:translate-x-0">
        
        <!-- Sidebar Top: Brand & Menu -->
        <div class="flex-1 flex flex-col min-h-0 overflow-y-auto">
            
            <!-- Brand Header -->
            <div class="h-16 flex items-center justify-between px-5 border-b border-slate-100 flex-shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                        <i class="fa-solid fa-id-card-clip"></i>
                    </div>
                    <div>
                        <span class="text-base font-extrabold tracking-tight text-slate-900">EduCard</span>
                        <span class="text-[10px] text-slate-400 font-medium block -mt-0.5">RFID & POS System</span>
                    </div>
                </a>
                <button type="button" onclick="toggleSidebar()" class="lg:hidden text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="p-4 space-y-5 text-xs">
                
                <!-- Group 1: General -->
                <div class="space-y-1">
                    <p class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Menu Utama</p>
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center space-x-2.5 px-3 py-2 rounded-xl font-semibold transition {{ request()->routeIs('dashboard') || request()->routeIs('home') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-chart-pie w-4 text-center"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <!-- Group 2: Kantin (POS) -->
                @if(auth()->user()->hasRole('kantin'))
                <div class="space-y-1">
                    <p class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Kantin Digital</p>
                    <a href="{{ route('canteen.index') }}"
                       class="flex items-center space-x-2.5 px-3 py-2 rounded-xl font-semibold transition {{ request()->routeIs('canteen.index') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-calculator w-4 text-center"></i>
                        <span>POS Kasir</span>
                    </a>
                    <a href="{{ route('canteen.transactions') }}"
                       class="flex items-center space-x-2.5 px-3 py-2 rounded-xl font-semibold transition {{ request()->routeIs('canteen.transactions') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-receipt w-4 text-center"></i>
                        <span>Laporan Penjualan</span>
                    </a>
                </div>
                @endif

                <!-- Group 3: Gerbang & Presensi -->
                @if(auth()->user()->hasRole('kesiswaan', 'akademik'))
                <div class="space-y-1">
                    <p class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Gerbang Presensi</p>
                    <a href="{{ route('gate.index') }}"
                       class="flex items-center space-x-2.5 px-3 py-2 rounded-xl font-semibold transition {{ request()->routeIs('gate.index') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-door-open w-4 text-center"></i>
                        <span>Terminal Gerbang</span>
                    </a>
                    <a href="{{ route('gate.history') }}"
                       class="flex items-center space-x-2.5 px-3 py-2 rounded-xl font-semibold transition {{ request()->routeIs('gate.history') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-clock-rotate-left w-4 text-center"></i>
                        <span>Rekap Presensi</span>
                    </a>
                    <a href="{{ route('gate.monitor') }}" target="_blank"
                       class="flex items-center space-x-2.5 px-3 py-2 rounded-xl font-semibold transition text-blue-600 hover:bg-blue-50">
                        <i class="fa-solid fa-tv w-4 text-center"></i>
                        <span>Monitor Public (No Login)</span>
                    </a>
                </div>
                @endif

                <!-- Group 4: Siswa & Kartu -->
                @if(auth()->user()->hasRole('kesiswaan', 'akademik', 'keuangan'))
                <div class="space-y-1">
                    <p class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Kesiswaan</p>
                    <a href="{{ route('students.index') }}"
                       class="flex items-center space-x-2.5 px-3 py-2 rounded-xl font-semibold transition {{ request()->routeIs('students.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-address-card w-4 text-center"></i>
                        <span>Data Siswa & Kartu</span>
                    </a>
                </div>
                @endif

                <!-- Group 5: Keuangan -->
                @if(auth()->user()->hasRole('keuangan'))
                <div class="space-y-1">
                    <p class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Keuangan</p>
                    <a href="{{ route('finance.index') }}"
                       class="flex items-center space-x-2.5 px-3 py-2 rounded-xl font-semibold transition {{ request()->routeIs('finance.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-wallet w-4 text-center"></i>
                        <span>Tagihan SPP & Tabungan</span>
                    </a>
                </div>
                @endif

                <!-- Group 6: Administrator -->
                @if(auth()->user()->isSuperAdmin())
                <div class="space-y-1 pt-1 border-t border-slate-100">
                    <p class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Administrator</p>
                    <a href="{{ route('admin.users.index') }}"
                       class="flex items-center space-x-2.5 px-3 py-2 rounded-xl font-semibold transition {{ request()->routeIs('admin.users.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-users w-4 text-center"></i>
                        <span>Manajemen Petugas</span>
                    </a>
                    <a href="{{ route('admin.security.index') }}"
                       class="flex items-center space-x-2.5 px-3 py-2 rounded-xl font-semibold transition {{ request()->routeIs('admin.security.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-shield-halved w-4 text-center"></i>
                        <span>Audit Log Forensik</span>
                    </a>
                </div>
                @endif

            </nav>
        </div>

        <!-- Sidebar Bottom: User Profile Card & Logout -->
        <div class="p-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
            <div class="flex items-center space-x-2.5 min-w-0 pr-2">
                <div class="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 truncate leading-tight">{{ auth()->user()->name }}</p>
                    <span class="text-[10px] text-slate-500 block truncate">{{ auth()->user()->role_badge['name'] }}</span>
                </div>
            </div>

            <form action="{{ route('logout') }}" method="POST" class="inline flex-shrink-0">
                @csrf
                <button type="submit" class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-white flex items-center justify-center transition border border-transparent hover:border-slate-200" title="Keluar">
                    <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                </button>
            </form>
        </div>

    </aside>
    @endauth

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 min-w-0 flex flex-col min-h-screen">
        
        @auth
        <!-- Header Bar -->
        <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 lg:px-8 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center space-x-3">
                <!-- Mobile Menu Button -->
                <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <h1 class="text-sm font-extrabold text-slate-900">
                    @yield('title', 'EduCard')
                </h1>
            </div>

            <div class="flex items-center space-x-3 text-xs text-slate-500">
                <div class="flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-[11px] font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Supabase Connected</span>
                </div>
            </div>
        </header>
        @endauth

        <!-- Main Body Content -->
        <main class="p-4 sm:p-6 lg:p-8 flex-1">
            @if(session('success'))
            <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
            @endif

            @if(session('error') || $errors->has('general'))
            <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                    <span class="font-medium">{{ session('error') ?: $errors->first('general') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 py-3 px-4 sm:px-6 lg:px-8 text-xs text-slate-400 flex items-center justify-between">
            <span>EduCard &bull; Smart School & POS Management</span>
            <span>&copy; {{ date('Y') }}</span>
        </footer>

    </div>

    <!-- Toggle Sidebar Script -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }

        function playRfidSound(type = 'success') {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();

                if (type === 'success') {
                    // Two-tone chime (A5 -> D6)
                    const osc1 = ctx.createOscillator();
                    const gain1 = ctx.createGain();
                    osc1.type = 'sine';
                    osc1.frequency.setValueAtTime(880, ctx.currentTime);
                    osc1.frequency.setValueAtTime(1174.66, ctx.currentTime + 0.09);
                    gain1.gain.setValueAtTime(0.18, ctx.currentTime);
                    gain1.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
                    osc1.connect(gain1);
                    gain1.connect(ctx.destination);
                    osc1.start();
                    osc1.stop(ctx.currentTime + 0.25);
                } else {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(160, ctx.currentTime);
                    gain.gain.setValueAtTime(0.22, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.35);
                }
            } catch (e) {}
        }

        window.educardVoiceEnabled = true;
        window.educardCurrentAudio = null;

        function speakBrowserFallback(text) {
            try {
                if (!('speechSynthesis' in window)) return;
                window.speechSynthesis.cancel();

                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'id-ID';
                utterance.rate = 0.96;
                utterance.pitch = 1.02;
                utterance.volume = 1.0;

                const voices = window.speechSynthesis.getVoices();
                // Prioritize natural neural Indonesian voices (Edge Natural Gadis/Ardi, Google Bahasa Indonesia, Damayanti, etc.)
                const idVoice =
                    voices.find(v => v.name.toLowerCase().includes('gadis') && v.name.toLowerCase().includes('natural')) ||
                    voices.find(v => v.name.toLowerCase().includes('ardi') && v.name.toLowerCase().includes('natural')) ||
                    voices.find(v => v.name.toLowerCase().includes('google') && v.lang.toLowerCase().includes('id')) ||
                    voices.find(v => v.lang.toLowerCase().includes('id-id') || v.lang.toLowerCase().includes('id_id')) ||
                    voices.find(v => v.name.toLowerCase().includes('indones'));

                if (idVoice) {
                    utterance.voice = idVoice;
                }

                window.speechSynthesis.speak(utterance);
            } catch (e) {}
        }

        function speakVoice(text) {
            if (!window.educardVoiceEnabled || !text) return;
            try {
                if (window.educardCurrentAudio) {
                    window.educardCurrentAudio.pause();
                    window.educardCurrentAudio.currentTime = 0;
                    window.educardCurrentAudio = null;
                }
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                }

                const ttsUrl = `/tts-voice?text=${encodeURIComponent(text)}`;
                const audio = new Audio(ttsUrl);
                audio.playbackRate = 1.04;
                window.educardCurrentAudio = audio;

                audio.onerror = function () {
                    speakBrowserFallback(text);
                };

                const playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.catch(() => {
                        speakBrowserFallback(text);
                    });
                }
            } catch (e) {
                speakBrowserFallback(text);
            }
        }

        if ('speechSynthesis' in window) {
            window.speechSynthesis.onvoiceschanged = () => {
                window.speechSynthesis.getVoices();
            };
        }
    </script>
    @yield('scripts')
</body>
</html>
