<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Naskah — e-Dokumen Bapperida Kabupaten Cirebon</title>
    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- FontAwesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', 'Plus Jakarta Sans', system-ui, sans-serif; }
    </style>
</head>
<body class="h-full bg-slate-950 text-slate-100 flex items-center justify-center p-4 relative overflow-hidden">

    <!-- Subtle Background Glow Elements -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl relative z-10 space-y-6">
        
        <!-- Header Branding -->
        <div class="text-center space-y-2">
            <div class="w-14 h-14 bg-amber-500 rounded-2xl flex items-center justify-center text-slate-950 font-black shadow-lg mx-auto mb-3">
                <i class="fa-solid fa-building-columns text-2xl"></i>
            </div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white leading-none">
                e-Dokumen Bapperida
            </h1>
            <p class="text-xs font-bold text-amber-400 uppercase tracking-widest">
                PEMERINTAH KABUPATEN CIREBON
            </p>
            <p class="text-[11px] text-slate-400">
                Sistem Perencanaan & Penyusunan Dokumen Perangkat Daerah
            </p>
        </div>

        @if(session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 p-3.5 rounded-xl text-xs flex items-center space-x-2.5">
                <i class="fa-solid fa-circle-check text-emerald-400 text-sm shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-amber-500/10 border border-amber-500/30 text-amber-300 p-3.5 rounded-xl text-xs flex items-center space-x-2.5">
                <i class="fa-solid fa-clock-rotate-left text-amber-400 text-sm shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-rose-500/10 border border-rose-500/30 text-rose-300 p-3.5 rounded-xl text-xs flex items-center space-x-2.5">
                <i class="fa-solid fa-circle-exclamation text-rose-400 text-sm shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="username_nip" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Username / NIP</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        <i class="fa-solid fa-user text-xs"></i>
                    </div>
                    <input type="text" name="username_nip" id="username_nip" value="{{ old('username_nip') }}" required autofocus
                           placeholder="Masukkan Username atau NIP"
                           class="w-full bg-slate-950/80 border border-slate-800 rounded-xl pl-10 pr-4 py-2.5 text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition">
                </div>
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        <i class="fa-solid fa-key text-xs"></i>
                    </div>
                    <input type="password" name="password" id="password" required
                           placeholder="Masukkan Kata Sandi"
                           class="w-full bg-slate-950/80 border border-slate-800 rounded-xl pl-10 pr-4 py-2.5 text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition">
                </div>
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center text-xs text-slate-400 cursor-pointer select-none">
                    <input type="checkbox" name="remember" value="1" checked class="rounded bg-slate-950 border-slate-800 text-amber-500 focus:ring-amber-400">
                    <span class="ml-2">Tetap Login di Perangkat Ini</span>
                </label>
            </div>

            <button type="submit" 
                    class="w-full h-11 bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-xs rounded-xl transition duration-150 shadow-lg hover:shadow-amber-500/20 flex items-center justify-center space-x-2">
                <i class="fa-solid fa-right-to-bracket text-sm"></i>
                <span>Masuk Sistem e-Dokumen</span>
            </button>
        </form>

        <div class="border-t border-slate-800/80 pt-4 text-center">
            <span class="text-[10px] font-semibold text-slate-500">
                &copy; {{ date('Y') }} Bapperida Kabupaten Cirebon. All rights reserved.
            </span>
        </div>
    </div>
</body>
</html>
