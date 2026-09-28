<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — e-Dokumen Bapperida Kabupaten Cirebon</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="ed-app" x-data="documentShell" :class="{ 'compact-tables': compactTables, 'reduce-motion': reduceMotion }" @keydown.escape.window="sidebarOpen = false">
    <a href="#main-content" class="ed-skip">Lewati navigasi</a>
    <div class="ed-shell">
        <div class="ed-sidebar-backdrop" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"></div>
        <x-ui.sidebar />
        <div class="ed-workspace">
            <x-ui.header />
            <main id="main-content" class="ed-main" tabindex="-1">@yield('content')</main>
        </div>
    </div>
    <!-- MODAL PANDUAN PENGGUNAAN -->
    <div id="modal-panduan" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-slate-900 text-sm flex items-center space-x-2">
                    <i class="fa-solid fa-book-open text-amber-500"></i>
                    <span>Panduan Penggunaan Aplikasi</span>
                </h3>
                <button type="button" onclick="document.getElementById('modal-panduan').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>
            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p><strong>1. Membuat Dokumen Baru:</strong> Klik tombol <span class="bg-amber-100 text-amber-900 px-2 py-0.5 rounded font-bold">+ Buat Dokumen Baru</span> di Dashboard, lalu pilih jenis dokumen yang ingin disusun.</p>
                <p><strong>2. Format Lembaran F4:</strong> Editor dokumen menyajikan tampilan persis lembaran kertas F4 fisik (21.5 cm x 33 cm) dengan margin Atas 2.5cm, Bawah 2.5cm, Kanan 2.5cm, Kiri 3cm.</p>
                <p><strong>3. Pintasan Keyboard:</strong></p>
                <ul class="list-disc pl-5 space-y-1 text-slate-700">
                    <li><code class="bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded font-mono text-[11px]">Ctrl + Enter</code>: Menambah lembaran F4 baru secara otomatis.</li>
                    <li><code class="bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded font-mono text-[11px]">Tab</code>: Menambah indentasi alinea / list item.</li>
                    <li><code class="bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded font-mono text-[11px]">Shift + Tab</code>: Mengurangi indentasi (outdent).</li>
                </ul>
            </div>
            <div class="flex justify-end pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-panduan').classList.add('hidden')" class="st-btn st-btn-primary st-btn-sm">Mengerti</button>
            </div>
        </div>
    </div>

    <!-- MODAL PROFIL PENGGUNA & PERANGKAT DAERAH -->
    @if(auth()->check())
    <div id="modal-profil-user" class="hidden fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 space-y-5 border border-slate-100 animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-11 h-11 rounded-2xl bg-amber-500 text-slate-950 flex items-center justify-center text-lg font-black shadow-md">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900 leading-tight">Profil Pengguna & OPD</h3>
                        <p class="text-xs text-slate-500 font-medium">Informasi Akun & Perangkat Daerah Aktif</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modal-profil-user').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Profile Info Body -->
            <div class="space-y-4 text-xs">
                <!-- User Card -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-sm shrink-0">
                            {{ strtoupper(substr(auth()->user()->nama_lengkap ?? auth()->user()->name ?? 'U', 0, 2)) }}
                        </div>
                        <div>
                            <div class="font-black text-slate-900 text-sm leading-snug">
                                {{ auth()->user()->nama_lengkap ?? auth()->user()->name ?? '-' }}
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium flex items-center gap-1.5 mt-0.5">
                                <span>Username: <strong>{{ auth()->user()->username ?? auth()->user()->email ?? '-' }}</strong></span>
                                <span>•</span>
                                <span class="bg-amber-100 text-amber-900 px-1.5 py-0.2 rounded font-black text-[9px] uppercase tracking-wider">
                                    {{ auth()->user()->role ?? 'Operator' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if(auth()->user()->nip)
                    <div class="flex justify-between items-center text-[11px] pt-2 border-t border-slate-200">
                        <span class="text-slate-500 font-semibold">NIP Operator:</span>
                        <span class="font-bold text-slate-800 font-mono">{{ auth()->user()->nip }}</span>
                    </div>
                    @endif
                </div>

                <!-- OPD Card -->
                <div class="bg-amber-50/50 p-4 rounded-2xl border border-amber-200/80 space-y-2.5">
                    <div class="text-[10px] font-black uppercase tracking-wider text-amber-700 flex items-center gap-1.5">
                        <i class="fa-solid fa-landmark"></i>
                        <span>Perangkat Daerah (OPD) Terdaftar</span>
                    </div>
                    <div>
                        <div class="font-black text-slate-900 text-xs">
                            {{ auth()->user()->opd->nama_opd ?? 'Pemerintah Kabupaten Cirebon' }}
                        </div>
                        @if(auth()->user()->opd?->singkatan_opd)
                            <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                                Singkatan: <strong class="text-slate-700">{{ auth()->user()->opd->singkatan_opd }}</strong>
                            </div>
                        @endif
                    </div>

                    @if(auth()->user()->opd?->kepala_opd)
                    <div class="pt-2 border-t border-amber-200/60 space-y-1 text-[11px]">
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-semibold">Kepala Perangkat Daerah:</span>
                            <span class="font-bold text-slate-800 text-right">{{ auth()->user()->opd->kepala_opd }}</span>
                        </div>
                        @if(auth()->user()->opd?->nip_kepala_opd)
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-semibold">NIP Kepala:</span>
                            <span class="font-bold text-slate-800 font-mono text-right">{{ auth()->user()->opd->nip_kepala_opd }}</span>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- Footer Action -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="st-btn st-btn-outline text-rose-600 border-rose-200 hover:bg-rose-50 font-bold text-xs py-2 px-3.5 rounded-xl">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Keluar Akun</span>
                    </button>
                </form>

                <button type="button" onclick="document.getElementById('modal-profil-user').classList.add('hidden')" class="st-btn st-btn-amber font-black text-xs py-2 px-5 rounded-xl shadow-xs">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif

    @include('components.modal_buat_dokumen')

    <x-ui.settings />
    @stack('scripts')
</body>
</html>
