<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - EduCard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-sm w-full bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        
        <!-- Logo & Header -->
        <div class="text-center space-y-1">
            <div class="w-11 h-11 rounded-xl bg-slate-900 text-white flex items-center justify-center mx-auto text-lg mb-3">
                <i class="fa-solid fa-id-card-clip"></i>
            </div>
            <h1 class="text-xl font-extrabold tracking-tight text-slate-900">EduCard</h1>
            <p class="text-xs text-slate-400">Masuk ke akun petugas sekolah</p>
        </div>

        @if($errors->any())
        <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
            <span>{{ $errors->first() }}</span>
        </div>
        @endif

        @if(session('info'))
        <div class="p-3 rounded-xl bg-slate-100 text-slate-700 text-xs flex items-center space-x-2">
            <i class="fa-solid fa-circle-info flex-shrink-0"></i>
            <span>{{ session('info') }}</span>
        </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', 'admin@educard.sch.id') }}" required autofocus
                       class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:bg-white focus:outline-none transition">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi</label>
                <input type="password" id="password" name="password" value="Educard123!" required
                       class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:bg-white focus:outline-none transition">
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span class="text-slate-600 text-[11px]">Ingat saya</span>
                </label>
            </div>

            <button type="submit"
                    class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition">
                Masuk
            </button>
        </form>

        <div class="pt-4 border-t border-slate-100 space-y-2.5">
            <a href="{{ route('gate.monitor') }}"
               class="w-full py-2.5 px-3 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-2">
                <i class="fa-solid fa-tv"></i>
                <span>Buka Monitor Gerbang (Tanpa Login)</span>
            </a>
            <p class="text-center text-[11px] text-slate-400">
                Koneksi database aktif via Supabase API
            </p>
        </div>

    </div>

</body>
</html>
