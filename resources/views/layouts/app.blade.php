<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard Utama') — e-Dokumen Bapperida Kabupaten Cirebon</title>
    
    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- FontAwesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        
        [x-cloak] { display: none !important; }

        :root {
            --color-navy-dark: #0f172a;
            --color-navy: #1e293b;
            --color-primary: #2563eb;
            --color-accent: #d97706;
        }

        body {
            font-family: 'Inter', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Smart Template Design System Component Utility Classes */
        .st-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease-in-out;
        }
        .st-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07);
        }

        .st-input {
            width: 100%;
            height: 2.5rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            transition: all 0.15s ease-in-out;
            outline: none;
        }
        .st-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .st-select {
            width: 100%;
            height: 2.5rem;
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            font-size: 0.875rem;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            transition: all 0.15s ease-in-out;
            outline: none;
        }
        .st-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .st-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            height: 2.5rem;
            padding: 0 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .st-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .st-btn-primary {
            background-color: #0f172a;
            color: #ffffff;
        }
        .st-btn-primary:hover:not(:disabled) {
            background-color: #1e293b;
        }

        .st-btn-amber {
            background-color: #f59e0b;
            color: #0f172a;
        }
        .st-btn-amber:hover:not(:disabled) {
            background-color: #d97706;
            color: #ffffff;
        }

        .st-btn-secondary {
            background-color: #ffffff;
            border-color: #cbd5e1;
            color: #334155;
        }
        .st-btn-secondary:hover:not(:disabled) {
            background-color: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
        }

        .st-btn-success {
            background-color: #16a34a;
            color: #ffffff;
        }
        .st-btn-success:hover:not(:disabled) {
            background-color: #15803d;
        }

        .st-btn-danger {
            background-color: #ef4444;
            color: #ffffff;
        }
        .st-btn-danger:hover:not(:disabled) {
            background-color: #dc2626;
        }

        .st-btn-sm {
            height: 2rem;
            padding: 0 0.75rem;
            font-size: 0.75rem;
        }

        .st-btn-lg {
            height: 3rem;
            padding: 0 1.25rem;
            font-size: 1rem;
        }

        .st-table-wrapper {
            width: 100%;
            overflow-x: auto;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background: #ffffff;
        }

        /* V2 HIGH-FIDELITY DESIGN SYSTEM UTILITIES (LINEAR & VERCEL STYLE) */
        .st-card-v2 {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.04);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .st-card-v2:hover {
            border-color: #cbd5e1;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
        }
        .st-pill-v2 {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.625rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full antialiased text-slate-800 bg-slate-50" x-data="{ sidebarOpen: false }">

    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- ========================================== -->
        <!-- MOBILE SIDEBAR OVERLAY / BACKDROP          -->
        <!-- ========================================== -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 lg:hidden"
             style="display: none;">
        </div>

        <!-- ========================================== -->
        <!-- SIDEBAR (DESKTOP & MOBILE DRAWER)          -->
        <!-- ========================================== -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white flex flex-col justify-between transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:z-30 lg:w-[250px] lg:shrink-0 shadow-xl overflow-hidden"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            
            <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
                <!-- BRANDING / LOGO HEADER -->
                <div class="h-16 px-6 border-b border-slate-800 flex items-center justify-between shrink-0">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-amber-500 rounded-xl flex items-center justify-center text-slate-950 font-black shadow-md shrink-0">
                            <i class="fa-solid fa-building-columns text-base"></i>
                        </div>
                        <div>
                            <div class="text-sm font-extrabold tracking-tight text-white leading-none">e-Dokumen</div>
                            <div class="text-[10px] font-bold text-amber-400 tracking-wider uppercase mt-0.5">BAPPERIDA CIREBON</div>
                        </div>
                    </a>

                    <!-- Mobile Close Button -->
                    <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white p-1 focus:outline-none">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- MAIN MENU NAVIGATION -->
                <nav class="p-4 space-y-1 text-xs font-semibold overflow-y-auto flex-1">

                    @if(auth()->user() && (auth()->user()->isAdmin() || auth()->user()->isVerifikator()))
                        <!-- ========================================== -->
                        <!-- MENU UTAMA ROLE ADMIN / VERIFIKATOR        -->
                        <!-- ========================================== -->
                        <div class="px-3 pt-2 pb-1 text-[10px] uppercase font-black tracking-wider text-slate-400">ADMIN BAPPERIDA</div>

                        <!-- 1. Dashboard -->
                        <a href="{{ route('admin.dashboard') }}" 
                           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.dashboard') || (request()->routeIs('dashboard') && !auth()->user()->isOperator()) ? 'bg-amber-500 text-slate-950 font-bold shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <div class="flex items-center space-x-3">
                                <i class="fa-solid fa-house text-sm w-5 text-center"></i>
                                <span>Dashboard</span>
                            </div>
                        </a>

                        <!-- 2. Verifikasi (Dropdown: Antrean Verifikasi) -->
                        <div x-data="{ openVerifMenu: {{ request()->routeIs('admin.verifikasi.*') || request()->routeIs('admin.review') ? 'true' : 'false' }} }">
                            <button @click="openVerifMenu = !openVerifMenu" 
                                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.verifikasi.*') || request()->routeIs('admin.review') ? 'bg-amber-500 text-slate-950 font-bold shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <div class="flex items-center space-x-3">
                                    <i class="fa-solid fa-clipboard-check text-sm w-5 text-center"></i>
                                    <span>Verifikasi</span>
                                </div>
                                <i class="fa-solid text-[10px]" :class="openVerifMenu ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                            </button>
                            
                            <!-- SUB-TREE: Antrean Verifikasi -->
                            <div x-show="openVerifMenu" class="pl-8 pt-1 pb-1 space-y-1 text-[11px]">
                                <a href="{{ route('admin.verifikasi.index') }}" 
                                   class="flex items-center space-x-2 py-1.5 px-2.5 rounded-lg transition {{ request()->routeIs('admin.verifikasi.*') ? 'text-amber-300 font-bold bg-slate-800/80 border-l-2 border-amber-400' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                                    <i class="fa-solid fa-list-check text-[10px] text-amber-400"></i>
                                    <span>Antrean Verifikasi</span>
                                </a>
                            </div>
                        </div>

                        <!-- 3. Monitoring OPD -->
                        <a href="{{ route('admin.monitoring-opd.index') }}" 
                           class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.monitoring-opd.*') || request()->routeIs('bapperida.monitoring') ? 'bg-amber-500 text-slate-950 font-bold shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-chart-line text-sm w-5 text-center"></i>
                            <span>Monitoring OPD</span>
                        </a>

                        <!-- 4. Perangkat Daerah -->
                        <a href="{{ route('admin.master_opd.index') }}" 
                           class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.master_opd.*') ? 'bg-amber-500 text-slate-950 font-bold shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-landmark text-sm w-5 text-center"></i>
                            <span>Perangkat Daerah</span>
                        </a>

                        <!-- 5. Arsip Dokumen -->
                        <a href="{{ route('renja.archive.index') }}" 
                           class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('renja.archive.*') || request()->routeIs('renja.fix.*') ? 'bg-amber-500 text-slate-950 font-bold shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-box-archive text-sm w-5 text-center"></i>
                            <span>Arsip Dokumen</span>
                        </a>

                        <!-- 6. Template Dokumen -->
                        <a href="{{ route('admin.templates.index') }}" 
                           class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.templates.*') || request()->routeIs('reference-documents.*') || request()->routeIs('admin.master_nomenklatur.*') ? 'bg-amber-500 text-slate-950 font-bold shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-folder-tree text-sm w-5 text-center"></i>
                            <span>Template</span>
                        </a>

                    @else
                        <!-- ========================================== -->
                        <!-- MENU UTAMA ROLE OPERATOR SKPD              -->
                        <!-- ========================================== -->
                        <div class="px-3 pt-2 pb-1 text-[10px] uppercase font-black tracking-wider text-slate-400">OPERATOR</div>

                        <!-- 1. Dashboard Operator -->
                        <a href="{{ route('dashboard') }}" 
                           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('dashboard') ? 'bg-amber-500 text-slate-950 font-bold shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <div class="flex items-center space-x-3">
                                <i class="fa-solid fa-house text-sm w-5 text-center"></i>
                                <span>Dashboard</span>
                            </div>
                        </a>

                        <!-- 2. Dokumen Saya -->
                        <div x-data="{ openDocMenu: {{ request()->routeIs('renja.*') || request()->routeIs('operator.renja-murni.*') || request()->routeIs('rkpd.*') ? 'true' : 'false' }} }">
                            <button @click="openDocMenu = !openDocMenu" 
                                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('renja.*') || request()->routeIs('operator.renja-murni.*') || request()->routeIs('rkpd.*') ? 'bg-amber-500 text-slate-950 font-bold shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <div class="flex items-center space-x-3">
                                    <i class="fa-solid fa-folder-open text-sm w-5 text-center"></i>
                                    <span>Dokumen Saya</span>
                                </div>
                                <i class="fa-solid text-[10px]" :class="openDocMenu ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                            </button>
                            
                            <div x-show="openDocMenu" class="pl-4 pt-1.5 pb-1 space-y-2 text-[11px]">
                                <!-- MODUL 1: RENJA -->
                                <div class="space-y-1 bg-slate-950/40 p-2 rounded-xl border border-slate-800/60">
                                    <div class="px-1 py-0.5 text-[10px] font-black uppercase tracking-wider text-slate-400 flex items-center justify-between">
                                        <div class="flex items-center space-x-1.5">
                                            <i class="fa-solid fa-layer-group text-[9px] text-amber-400"></i>
                                            <span>RENJA</span>
                                        </div>
                                        <span class="text-[9px] bg-amber-400/15 text-amber-400 border border-amber-400/30 px-1.5 py-0.2 rounded font-bold">OPD</span>
                                    </div>

                                    <div class="space-y-0.5 pt-0.5">
                                        <a href="{{ route('renja.workspace') }}" 
                                           class="flex items-center justify-between py-1.5 px-2 rounded-lg transition-all duration-150 {{ request()->routeIs('renja.workspace') || request()->routeIs('operator.renja-murni.*') ? 'text-amber-300 font-bold bg-amber-500/20 border-l-2 border-amber-400 shadow-2xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/70' }}">
                                            <div class="flex items-center space-x-2">
                                                <i class="fa-solid fa-pen-ruler text-[11px] text-amber-400 shrink-0"></i>
                                                <span>Draft & Proses</span>
                                            </div>
                                            <span class="text-[9px] bg-amber-400/20 text-amber-300 border border-amber-400/30 px-1.5 py-0.2 rounded font-black">TA</span>
                                        </a>

                                        <a href="{{ route('renja.archive.index') }}" 
                                           class="flex items-center space-x-2 py-1.5 px-2 rounded-lg transition-all duration-150 {{ request()->routeIs('renja.archive.*') || request()->routeIs('renja.fix.*') ? 'text-cyan-300 font-bold bg-cyan-500/20 border-l-2 border-cyan-400 shadow-2xs' : 'text-slate-300 hover:text-cyan-300 hover:bg-slate-800/70' }}">
                                            <i class="fa-solid fa-box-archive text-[11px] text-cyan-400 shrink-0"></i>
                                            <span>Arsip</span>
                                        </a>
                                    </div>
                                </div>

                                <!-- MODUL 2: RKPD (BARU) -->
                                <div class="space-y-1 bg-slate-950/40 p-2 rounded-xl border border-slate-800/60">
                                    <div class="px-1 py-0.5 text-[10px] font-black uppercase tracking-wider text-slate-400 flex items-center justify-between">
                                        <div class="flex items-center space-x-1.5">
                                            <i class="fa-solid fa-landmark text-[9px] text-emerald-400"></i>
                                            <span>RKPD</span>
                                        </div>
                                        <span class="text-[9px] bg-emerald-400/15 text-emerald-400 border border-emerald-400/30 px-1.5 py-0.2 rounded font-bold">Daerah</span>
                                    </div>

                                    <div class="space-y-0.5 pt-0.5">
                                        <a href="{{ route('rkpd.index') }}" 
                                           class="flex items-center justify-between py-1.5 px-2 rounded-lg transition-all duration-150 {{ request()->routeIs('rkpd.index') || request()->routeIs('rkpd.workspace') ? 'text-emerald-300 font-bold bg-emerald-500/20 border-l-2 border-emerald-400 shadow-2xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/70' }}">
                                            <div class="flex items-center space-x-2">
                                                <i class="fa-solid fa-diagram-project text-[11px] text-emerald-400 shrink-0"></i>
                                                <span>Draft & Agregasi</span>
                                            </div>
                                            <span class="text-[9px] bg-emerald-400/20 text-emerald-300 border border-emerald-400/30 px-1.5 py-0.2 rounded font-black">KAB</span>
                                        </a>

                                        <a href="{{ route('rkpd.archive') }}" 
                                           class="flex items-center space-x-2 py-1.5 px-2 rounded-lg transition-all duration-150 {{ request()->routeIs('rkpd.archive') ? 'text-emerald-300 font-bold bg-emerald-500/20 border-l-2 border-emerald-400 shadow-2xs' : 'text-slate-300 hover:text-emerald-300 hover:bg-slate-800/70' }}">
                                            <i class="fa-solid fa-box-archive text-[11px] text-emerald-400 shrink-0"></i>
                                            <span>Arsip RKPD</span>
                                        </a>
                                    </div>
                                </div>

                                <!-- MASTER DAFTAR: SELURUH DOKUMEN (RENJA & RKPD) -->
                                <a href="{{ route('renja.index', ['status' => 'all', 'tahun_anggaran' => 'all', 'jenis_dokumen' => 'all', 'search' => '']) }}" 
                                   class="flex items-center space-x-2 py-2 px-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('renja.index') && !request()->routeIs('renja.workspace') && !request()->routeIs('renja.fix.*') && !request()->routeIs('renja.archive.*') && !request()->routeIs('operator.renja-murni.*') && !request()->routeIs('rkpd.*') ? 'text-amber-300 font-bold bg-amber-500/20 border-l-2 border-amber-400 shadow-2xs' : 'text-slate-400 hover:text-white hover:bg-slate-800/70' }}">
                                    <i class="fa-solid fa-table-list text-[12px] text-slate-400 shrink-0"></i>
                                    <span class="font-bold">Seluruh Dokumen</span>
                                </a>
                            </div>
                        </div>

                        <!-- 3. Acuan Dokumen -->
                        <a href="{{ route('reference-documents.index') }}" 
                           class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('reference-documents.*') ? 'bg-amber-500 text-slate-950 font-bold shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-file-invoice text-sm w-5 text-center"></i>
                            <span>Acuan Dokumen</span>
                        </a>
                    @endif

                    <!-- Profil & Panduan (Semua Role) -->
                    <div class="px-3 pt-3 pb-1 text-[10px] uppercase font-black tracking-wider text-slate-400">INFORMASI & AKUN</div>

                    <a href="#" onclick="document.getElementById('modal-profil-user')?.classList.remove('hidden')" 
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition-all duration-150">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-id-card text-sm w-5 text-center"></i>
                            <span>Profil Pengguna</span>
                        </div>
                    </a>

                    <button type="button" @click="document.getElementById('modal-panduan').classList.remove('hidden')" 
                            class="w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition-all duration-150">
                        <i class="fa-solid fa-book-open text-sm w-5 text-center"></i>
                        <span>Panduan Penggunaan</span>
                    </button>

                </nav>
            </div>

            <!-- FOOTER SIDEBAR INFO -->
            <div class="p-4 border-t border-slate-800 text-[11px] text-slate-400 space-y-2">
                <div class="text-center text-[10px] text-slate-500 pt-1">
                    &copy; {{ date('Y') }} Bapperida Kab. Cirebon
                </div>
            </div>
        </aside>

        <!-- ========================================== -->
        <!-- MAIN WRAPPER (HEADER + CONTENT)            -->
        <!-- ========================================== -->
        <div class="flex-1 flex flex-col min-w-0 min-h-screen">

            <!-- HEADER / TOPBAR (ATAS) -->
            <header class="h-16 bg-white border-b border-slate-200 sticky top-0 z-20 shadow-xs flex items-center justify-between px-4 sm:px-6 lg:px-8">
                
                <!-- Left: Hamburger toggle (Mobile) + Title + TA Badge -->
                <div class="flex items-center space-x-3">
                    <button @click="sidebarOpen = !sidebarOpen" 
                            class="lg:hidden p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-300"
                            aria-label="Toggle Navigation">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>

                    <div>
                        <div class="flex items-center space-x-2">
                            <h1 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight leading-none">
                                @yield('title', 'Dashboard Utama')
                            </h1>
                            <!-- DYNAMIC YEAR/PERIOD SELECTOR -->
                            <div class="hidden sm:inline-flex items-center">
                                <div class="inline-flex items-center space-x-1.5 bg-slate-900 text-amber-400 px-2.5 py-0.5 rounded-full text-[10px] font-black border border-slate-800 shadow-2xs hover:border-amber-500/50 transition">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                                    <select name="tahun_anggaran" onchange="window.location.href='/set-active-ta?tahun_anggaran=' + this.value" 
                                            class="bg-transparent text-amber-400 font-black text-[11px] cursor-pointer focus:outline-none border-none p-0 pr-1">
                                        @php
                                            $activeTa = session('active_ta', (int)date('Y'));
                                            if (request()->has('tahun_anggaran') && is_numeric(request('tahun_anggaran')) && (int)request('tahun_anggaran') >= 2020) {
                                                $activeTa = (int)request('tahun_anggaran');
                                            }
                                            $taYears = range(2025, 2035);
                                        @endphp
                                        @foreach($taYears as $y)
                                            <option value="{{ $y }}" class="bg-slate-900 text-white font-bold" {{ $activeTa == $y ? 'selected' : '' }}>
                                                {{ $y }}–{{ $y + 1 }} {{ $activeTa == $y ? '• AKTIF' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-500 hidden sm:block mt-0.5">Sistem Perencanaan & Penyusunan Dokumen Perangkat Daerah</p>
                    </div>
                </div>

                <!-- Right: User & OPD Profile + Role Badge + Logout -->
                <div class="flex items-center space-x-2.5 sm:space-x-3">
                    <div class="flex items-center space-x-2.5 bg-slate-50 px-3 py-1.5 rounded-full border border-slate-200">
                        <div class="w-7 h-7 rounded-full bg-slate-900 text-amber-400 font-bold flex items-center justify-center text-xs shrink-0">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <div class="text-left hidden md:block pr-1">
                            <span class="text-xs font-extrabold text-slate-900 block leading-tight truncate max-w-[180px]">
                                {{ auth()->user()->opd->nama_opd ?? auth()->user()->nama_lengkap ?? 'Perangkat Daerah' }}
                            </span>
                            <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider block">
                                OPD Kabupaten Cirebon
                            </span>
                        </div>
                    </div>

                    @if(auth()->check())
                        <span class="hidden sm:inline-flex px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200">
                            {{ strtoupper(auth()->user()->role ?? 'OPERATOR') }}
                        </span>
                    @endif

                    <!-- TOMBOL LOGOUT -->
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="h-9 px-3 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs"
                                title="Keluar Akun">
                            <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                            <span class="hidden sm:inline">Keluar</span>
                        </button>
                    </form>
                </div>
            </header>

            <!-- MAIN CONTENT AREA -->
            <main class="flex-1 bg-slate-50 p-4 sm:p-6 lg:p-8 min-h-[calc(100vh-64px)]">
                @yield('content')
            </main>
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
                        <div class="w-10 h-10 rounded-xl bg-slate-900 text-amber-400 flex items-center justify-center font-black text-sm shrink-0">
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

    @stack('scripts')
</body>
</html>
