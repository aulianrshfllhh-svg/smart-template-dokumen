<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Template Dokumen (Multi-Template) - {{ $activeTemplate?->name ?? 'e-Renja' }}</title>
    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- FontAwesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        
        body { 
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background-color: #F3F4F6; 
            color: #1F2937;
        }

        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        .ui-card {
            background-color: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        /* ===== COMPACT ADAPTIVE TOOLBAR (HEIGHT ~75px) ===== */
        .editor-toolbar {
            background: #FFFFFF;
            border-bottom: 1px solid #E5E7EB;
            min-height: 75px;
            padding: 6px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 0 10px;
            border-right: 1px solid #E5E7EB;
        }
        .toolbar-group:last-child { border-right: none; }
        .toolbar-group-label {
            font-size: 10px;
            font-weight: 600;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 2px;
            text-align: center;
        }
        .toolbar-btn {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
            border: 1px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            background: transparent;
            font-size: 11px;
            font-weight: 500;
            color: #374151;
            min-width: 34px;
            transition: all 0.15s ease;
        }
        .toolbar-btn:hover { background: #F3F4F6; border-color: #D1D5DB; color: #111827; }
        .toolbar-btn:active { background: #E5E7EB; }
        .toolbar-btn i { font-size: 14px; margin-bottom: 2px; }
        
        .toolbar-btn-sm {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            cursor: pointer;
            background: #FFFFFF;
            font-size: 11px;
            font-weight: 600;
            color: #374151;
            height: 28px;
            transition: all 0.15s ease;
        }
        .toolbar-btn-sm:hover { background: #F3F4F6; border-color: #D1D5DB; color: #111827; }
        .toolbar-btn-disabled {
            opacity: 0.4;
            cursor: not-allowed !important;
            pointer-events: none;
        }

        /* ===== DOCUMENT WORKSPACE CANVAS ===== */
        .document-workspace {
            width: 100%;
            min-height: calc(100vh - 240px);
            overflow-y: auto;
            background: #F3F4F6;
            padding: 24px 12px 60px;
            display: flex;
            justify-content: center;
        }

        .pages-zoom-wrapper {
            transform: scale(var(--editor-zoom, 1));
            transform-origin: top center;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: transform 0.15s ease;
        }

        /* Continuous F4 Paper Canvas (21.5cm x 33.0cm, 2cm margin padding) */
        .paper-f4, .continuous-paper, .preview-paper {
            width: 21.5cm !important;
            min-height: 33cm !important;
            height: auto !important;
            padding: 2cm !important;
            box-sizing: border-box !important;
            background: #FFFFFF !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08) !important;
            border: 1px solid #E5E7EB !important;
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
            border-radius: 4px !important;
            color: #000000 !important;
        }

        .paper-f4 *, .continuous-paper *, .preview-paper * {
            color: #000000;
        }

        .subbab-content {
            min-height: 120px !important;
            height: auto !important;
            outline: none !important;
            border: none !important;
            background: transparent !important;
            font-family: 'Bookman Old Style', 'Bookman', Georgia, serif !important;
            font-size: 12pt !important;
            line-height: 1.6 !important;
            color: #000000 !important;
            text-align: justify !important;
            box-sizing: border-box !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            tab-size: 4 !important;
            -moz-tab-size: 4 !important;
            white-space: pre-wrap !important;
        }

        .subbab-content p {
            margin-bottom: 4pt;
        }

        .subbab-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #F9FAFB;
            border-bottom: 1px solid #E5E7EB;
            padding: 6px 12px;
            border-radius: 6px;
            margin-top: 20px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="bg-[#F3F4F6] text-gray-800 min-h-screen flex flex-col justify-between pb-24" x-data="{ activeTab: 'narasi', showOutline: true, showValidation: false, showSmartPanel: false }">

    <div>
        <!-- READ ONLY BANNER NOTICE (Sprint 6.0 Fitur 6) -->
        @if($isReadOnly)
            <div class="bg-amber-500 text-slate-950 px-6 py-2 text-xs font-black flex items-center justify-between shadow-md">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-lock text-sm"></i>
                    <span>DOKUMEN DALAM MODE READ-ONLY (STATUS: {{ strtoupper($document->status) }}). Pengeditan locked.</span>
                </div>
                <span class="text-[10px] bg-slate-900 text-white px-2.5 py-0.5 rounded-full font-bold uppercase">Read Only Mode</span>
            </div>
        @endif

        <!-- ============================================================ -->
        <!-- SECTION 1: HEADER EDITOR FOUNDATION (FITUR 1)                -->
        <!-- ============================================================ -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-xs">
            <div class="max-w-[1600px] mx-auto px-6 h-16 flex items-center justify-between">
                
                <!-- Left: Branding, Nama Dokumen, TA, OPD -->
                <div class="flex items-center space-x-3.5">
                    <a href="{{ route('renja.index') }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl border border-slate-300 flex items-center justify-center shrink-0 transition" title="Kembali ke Dokumen Saya">
                        <i class="fa-solid fa-arrow-left text-sm"></i>
                    </a>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-[10px] font-black text-amber-600 uppercase tracking-wider">{{ $document->opd->nama_opd ?? 'SKPD' }}</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 bg-blue-100 text-blue-800 rounded-md uppercase border border-blue-200">
                                TA {{ $document->tahun_anggaran }}
                            </span>
                            <span class="text-[10px] font-bold px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md uppercase border border-slate-300">
                                {{ $activeTemplate?->code ?? 'MANUAL' }}
                            </span>
                        </div>
                        <h1 class="text-base font-extrabold tracking-tight text-gray-900 leading-tight">
                            {{ $document->jenis_dokumen }} TA {{ $document->tahun_anggaran }}
                        </h1>
                    </div>
                </div>

                <!-- Right: Autosave Status, Progress Info, Smart Panel Toggle, Status Badge -->
                <div class="flex items-center space-x-3">
                    
                    <!-- AUTOSAVE STATUS INDICATOR (FITUR 7) -->
                    <div class="hidden sm:flex items-center space-x-1.5 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700">
                        <span id="autosave-dot" class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                        <span id="autosave-status-text">✓ Tersimpan {{ $lastSavedFormatted }}</span>
                    </div>

                    <!-- SMART INFO PANEL TOGGLE BUTTON (FITUR 4) -->
                    <button x-on:click="showSmartPanel = !showSmartPanel" 
                            class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 font-extrabold rounded-xl text-xs flex items-center space-x-1.5 transition border border-amber-300 shadow-xs">
                        <i class="fa-solid fa-circle-info text-amber-600"></i>
                        <span>Smart Panel</span>
                    </button>

                    <!-- Dynamic Validation Toggle Button -->
                    <button x-on:click="showValidation = !showValidation" 
                            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold rounded-xl text-xs flex items-center space-x-1.5 transition border border-gray-300">
                        <i class="fa-solid fa-list-check text-emerald-600"></i>
                        <span>Validasi</span>
                    </button>

                    <!-- Progress Card Ringkas -->
                    <div class="hidden md:flex items-center space-x-3 bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-200">
                        <div class="text-right">
                            <span class="text-xs font-bold text-gray-900" id="progress-percentage-display">{{ $document->progress_percentage }}%</span>
                            <span class="text-[10px] text-gray-500 block leading-tight" id="progress-text-display">{{ $document->completed_sections_count }}/{{ $document->total_sections_count }} Seksi</span>
                        </div>
                        <div class="w-14 bg-gray-200 h-2 rounded-full overflow-hidden">
                            <div id="progress-bar" class="bg-amber-500 h-full rounded-full transition-all duration-500" style="width: {{ $document->progress_percentage }}%;"></div>
                        </div>
                    </div>

                    <!-- Status Badge V2 -->
                    <span class="px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wide flex items-center gap-1.5 border shadow-2xs {{ $document->status_badge_class ?? 'bg-amber-50 text-amber-800 border-amber-300' }}">
                        <span>{{ strtoupper($document->status) }}</span>
                    </span>
                </div>

            </div>
        </header>

        <!-- ============================================================ -->
        <!-- SMART INFORMATION PANEL (SPRINT 6.0 FITUR 4)                 -->
        <!-- ============================================================ -->
        <section x-show="showSmartPanel" class="max-w-[1600px] mx-auto px-6 pt-4" x-transition>
            <div class="ui-card p-5 bg-white border border-slate-200 space-y-4 shadow-lg rounded-2xl">
                <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                    <h3 class="font-black text-xs uppercase tracking-wider text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-amber-500 text-sm"></i>
                        <span>Smart Information Panel & Checklist Kelengkapan</span>
                    </h3>
                    <button x-on:click="showSmartPanel = false" class="text-slate-400 hover:text-slate-600 text-xs">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                    
                    <!-- 1. Progress Dokumen Total -->
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">PROGRESS DOKUMEN</span>
                        <div class="flex items-baseline justify-between">
                            <span class="text-xl font-black text-slate-900">{{ $document->progress_percentage }}%</span>
                            <span class="text-[10px] font-bold text-slate-500">{{ $document->completed_sections_count }}/{{ $document->total_sections_count }} Seksi</span>
                        </div>
                        <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                            <div class="bg-amber-500 h-full rounded-full" style="width: {{ $document->progress_percentage }}%;"></div>
                        </div>
                    </div>

                    <!-- 2. Checklist Kelengkapan Bab -->
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5 md:col-span-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">CHECKLIST KELENGKAPAN BAB</span>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-1">
                            @foreach($babStats as $bCode => $bInf)
                                <div class="flex items-center justify-between p-2 rounded-lg bg-white border border-slate-200 text-[11px]">
                                    <span class="font-bold text-slate-800">{{ $bCode }}</span>
                                    <span class="px-1.5 py-0.2 rounded font-black text-[9px] uppercase border
                                        {{ $bInf['status_label'] === 'Lengkap' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ($bInf['percentage'] > 0 ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-slate-100 text-slate-500 border-slate-300') }}">
                                        {{ $bInf['status_label'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- 3. Metadata Dokumen & Last Update -->
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">METADATA DOKUMEN</span>
                        <div class="space-y-1 text-[11px] text-slate-700">
                            <div><strong class="font-bold">OPD:</strong> {{ $document->opd->nama_opd ?? '-' }}</div>
                            @if(str_contains(strtolower($document->jenis_dokumen ?? ''), 'lampiran'))
                                <div><strong class="font-bold">Lampiran:</strong> {{ $document->opd->nomor_lampiran_romawi ?? 'I' }}</div>
                            @endif
                            <div><strong class="font-bold">Update Terakhir:</strong> {{ $document->updated_at ? $document->updated_at->format('d M Y, H:i') : '-' }}</div>
                            <div><strong class="font-bold">Oleh:</strong> {{ $document->updatedByUser->name ?? 'Operator OPD' }}</div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- PANEL VALIDASI DINAMIS BERBASIS KONFIGURASI TEMPLATE          -->
        <!-- ============================================================ -->
        <section x-show="showValidation" class="max-w-[1600px] mx-auto px-6 pt-4" x-transition>
            <div class="ui-card p-4 bg-slate-900 text-white space-y-3">
                <div class="flex justify-between items-center border-b border-slate-800 pb-2">
                    <h3 class="font-bold text-xs uppercase tracking-wider text-emerald-400 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Laporan Validasi Otomatis (Template {{ $activeTemplate?->code ?? 'MANUAL' }})</span>
                    </h3>
                    <button x-on:click="showValidation = false" class="text-slate-400 hover:text-white text-xs">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    @foreach($validationResult as $v)
                        <div class="p-2.5 rounded-lg border flex items-start space-x-2 {{ $v['type'] === 'success' ? 'bg-emerald-950/60 border-emerald-800 text-emerald-200' : 'bg-amber-950/60 border-amber-800 text-amber-200' }}">
                            <i class="fa-solid {{ $v['type'] === 'success' ? 'fa-circle-check text-emerald-400' : 'fa-triangle-exclamation text-amber-400' }} text-sm mt-0.5 shrink-0"></i>
                            <span class="leading-snug">{{ $v['message'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- SECTION 2: HEADER TENGAH (INFORMASI METADATA DOKUMEN CARDS) -->
        <!-- ============================================================ -->
        <section class="max-w-[1600px] mx-auto px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Nama OPD -->
                <div class="ui-card p-3.5 flex items-center space-x-3">
                    <div class="w-9 h-9 bg-blue-50 text-blue-700 rounded-lg flex items-center justify-center text-sm font-bold shrink-0 border border-blue-100">
                        <i class="fa-solid fa-building"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">NAMA PERANGKAT DAERAH</div>
                        <div class="text-xs font-bold text-gray-900 truncate" title="{{ $document->opd->nama_opd ?? 'PERANGKAT DAERAH' }}">
                            {{ strtoupper($document->opd->nama_opd ?? 'PERANGKAT DAERAH') }}
                        </div>
                    </div>
                </div>

                <!-- Card 2: Jenis Dokumen yang Sedang Dibuka (Read-only + Ubah Nama Dokumen) -->
                <div class="ui-card p-3.5 flex items-center justify-between space-x-3 bg-white border border-gray-200 rounded-xl">
                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                        <div class="w-9 h-9 bg-emerald-50 text-emerald-700 rounded-lg flex items-center justify-center text-sm font-bold shrink-0 border border-emerald-100">
                            <i class="fa-solid fa-file-contract"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1">
                                <span>DOKUMEN YANG DIBUKA</span>
                            </div>
                            <div class="text-xs font-bold text-gray-900 truncate" title="{{ $document->jenis_dokumen ?? ($activeTemplate?->name ?? 'DOKUMEN PERENCANAAN') }}">
                                {{ $document->jenis_dokumen ?? ($activeTemplate?->name ?? 'DOKUMEN PERENCANAAN') }}
                            </div>
                        </div>
                    </div>
                    @if(!$isReadOnly)
                        <button type="button" 
                                onclick="document.getElementById('modal-edit-document-name').classList.remove('hidden')"
                                class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 rounded-md text-[11px] font-bold flex items-center gap-1 shrink-0 transition cursor-pointer shadow-2xs"
                                title="Ubah Nama Dokumen Ini">
                            <i class="fa-solid fa-pen-to-square text-[10px] text-amber-600"></i>
                            <span>Ubah Nama</span>
                        </button>
                    @endif
                </div>

                <!-- Card 3: Lampiran Resmi -->
                @if(str_contains(strtolower($document->jenis_dokumen ?? ''), 'lampiran'))
                <div class="ui-card p-3.5 flex items-center space-x-3">
                    <div class="w-9 h-9 bg-amber-50 text-amber-700 rounded-lg flex items-center justify-center text-sm font-bold shrink-0 border border-amber-100">
                        <i class="fa-solid fa-bookmark"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">LAMPIRAN PERBUP</div>
                        <div class="text-xs font-bold text-gray-900 truncate">
                            {{ strtoupper($document->opd->nomor_lampiran_romawi ?? 'LAMPIRAN I') }}
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </section>

        <!-- ============================================================ -->
        <!-- SECTION 3: NAVIGASI DOKUMEN ADAPTIF (BAGIAN AWAL, UTAMA, LAMPIRAN) -->
        <!-- ============================================================ -->
        <section class="max-w-[1600px] mx-auto px-6 mb-4">
            <div class="ui-card p-2 flex items-center justify-between flex-wrap gap-2">
                
                <!-- OUTLINE NAVIGASI TABS -->
                <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                    
                    <!-- TOMBOL TAMBAH HALAMAN AWAL (FRONT MATTER DROPDOWN) -->
                    @if(!$isReadOnly)
                        <div x-data="{ open: false }" class="relative">
                            <button x-on:click="open = !open" type="button"
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-700 text-white transition flex items-center space-x-1.5 shadow-sm border border-indigo-500 cursor-pointer"
                                    title="Tambah Halaman Awal (Cover, Kata Pengantar, Daftar Isi, Gambar, Tabel, Lampiran)">
                                <i class="fa-solid fa-plus text-xs"></i>
                                <span>Halaman Awal</span>
                                <i class="fa-solid fa-chevron-down text-[10px] ml-0.5"></i>
                            </button>
                            <div x-show="open" x-on:click.away="open = false" x-transition
                                 class="absolute left-0 mt-1.5 w-60 bg-white border border-slate-200 rounded-xl shadow-2xl z-50 py-1.5 text-xs font-bold text-slate-800">
                                
                                <form action="{{ route('renja.editor.addFrontMatter', $document->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="cover">
                                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-indigo-50 hover:text-indigo-700 flex items-center justify-between transition cursor-pointer">
                                        <span class="flex items-center gap-2">
                                            <i class="fa-solid fa-book-open text-purple-600"></i>
                                            <span>+ Cover (opsional)</span>
                                        </span>
                                        @if($frontSections->where('section_type', 'cover')->count() > 0)
                                            <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-black">ADA</span>
                                        @endif
                                    </button>
                                </form>

                                <form action="{{ route('renja.editor.addFrontMatter', $document->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="preface">
                                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-indigo-50 hover:text-indigo-700 flex items-center justify-between transition cursor-pointer">
                                        <span class="flex items-center gap-2">
                                            <i class="fa-solid fa-feather text-amber-600"></i>
                                            <span>+ Kata Pengantar</span>
                                        </span>
                                        @if($frontSections->where('section_type', 'preface')->count() > 0)
                                            <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-black">ADA</span>
                                        @endif
                                    </button>
                                </form>

                                <div class="border-t border-slate-100 my-1"></div>

                                <form action="{{ route('renja.editor.addFrontMatter', $document->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="table_of_contents">
                                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-indigo-50 hover:text-indigo-700 flex items-center justify-between transition cursor-pointer">
                                        <span class="flex items-center gap-2">
                                            <i class="fa-solid fa-list-ol text-blue-600"></i>
                                            <span>+ Daftar Isi</span>
                                        </span>
                                        @if($frontSections->where('section_type', 'table_of_contents')->count() > 0)
                                            <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-black">ADA</span>
                                        @endif
                                    </button>
                                </form>

                                <form action="{{ route('renja.editor.addFrontMatter', $document->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="list_of_figures">
                                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-indigo-50 hover:text-indigo-700 flex items-center justify-between transition cursor-pointer">
                                        <span class="flex items-center gap-2">
                                            <i class="fa-regular fa-image text-emerald-600"></i>
                                            <span>+ Daftar Gambar</span>
                                        </span>
                                        @if($frontSections->where('section_type', 'list_of_figures')->count() > 0)
                                            <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-black">ADA</span>
                                        @endif
                                    </button>
                                </form>

                                <form action="{{ route('renja.editor.addFrontMatter', $document->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="list_of_tables">
                                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-indigo-50 hover:text-indigo-700 flex items-center justify-between transition cursor-pointer">
                                        <span class="flex items-center gap-2">
                                            <i class="fa-solid fa-table text-indigo-600"></i>
                                            <span>+ Daftar Tabel</span>
                                        </span>
                                        @if($frontSections->where('section_type', 'list_of_tables')->count() > 0)
                                            <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-black">ADA</span>
                                        @endif
                                    </button>
                                </form>

                                <form action="{{ route('renja.editor.addFrontMatter', $document->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="list_of_appendices">
                                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-indigo-50 hover:text-indigo-700 flex items-center justify-between transition cursor-pointer">
                                        <span class="flex items-center gap-2">
                                            <i class="fa-solid fa-paperclip text-purple-600"></i>
                                            <span>+ Daftar Lampiran</span>
                                        </span>
                                        @if($frontSections->where('section_type', 'list_of_appendices')->count() > 0)
                                            <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-black">ADA</span>
                                        @endif
                                    </button>
                                </form>

                            </div>
                        </div>
                    @endif

                    <!-- BAGIAN AWAL (Jika Ada Halaman Awal Yang Dibuat) -->
                    @if($frontSections->count() > 0)
                        <div class="relative">
                            <a href="{{ route('renja.editor', [$document->id, 'bab' => 'FRONT']) }}" 
                               class="px-3.5 py-1.5 rounded-xl text-xs font-bold border transition flex items-center space-x-1.5 shadow-2xs {{ $isFrontView ? 'bg-indigo-700 text-white border-indigo-700 font-extrabold shadow-md' : 'border-indigo-300 bg-indigo-50 text-indigo-900 hover:bg-indigo-100' }}">
                                <i class="fa-solid fa-book-bookmark text-xs"></i>
                                <span>BAGIAN AWAL</span>
                                <span class="px-1.5 py-0.2 {{ $isFrontView ? 'bg-indigo-900 text-indigo-100' : 'bg-indigo-200 text-indigo-900' }} text-[9px] font-black rounded">{{ $frontSections->count() }}</span>
                            </a>
                        </div>
                    @endif

                    <!-- BAGIAN UTAMA BAB TABS & COMPLETENESS BADGES (FITUR 2 & 3) -->
                    @php
                        $secStatuses = $document->section_review_status ?? [];
                    @endphp
                    @foreach($groupedBabs as $code => $bSecs)
                        @php 
                            $isActive = ($activeBabCode === $code); 
                            $bStat = $secStatuses[$code]['status'] ?? 'PENDING';
                            $bInfo = $babStats[$code] ?? ['status_label' => 'Belum Diisi', 'percentage' => 0];
                            $statLabel = $bInfo['status_label'];
                        @endphp
                        <a href="{{ route('renja.editor', [$document->id, 'bab' => $code]) }}" 
                           class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-2 border shadow-2xs
                                  {{ $isActive ? 'bg-slate-900 text-white border-slate-900 font-extrabold shadow-md' : ($bStat === 'NEEDS_REVISION' ? 'bg-amber-50 text-amber-900 border-amber-300 hover:bg-amber-100 font-black' : ($bStat === 'APPROVED' ? 'bg-emerald-50 text-emerald-900 border-emerald-300 hover:bg-emerald-100' : 'bg-white text-slate-700 hover:bg-slate-100 border-slate-200')) }}"
                           title="{{ $code }} — {{ $bSecs->first()->bab_title ?? '' }}">
                            @if($bStat === 'NEEDS_REVISION')
                                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-xs"></i>
                            @elseif($bStat === 'APPROVED')
                                <i class="fa-solid fa-circle-check text-emerald-600 text-xs"></i>
                            @endif
                            <span>{{ $code }}</span>
                            <span class="hidden xl:inline text-[10px] font-normal opacity-85">({{ \Illuminate\Support\Str::limit($bSecs->first()->bab_title ?? '', 15) }})</span>
                            
                            <!-- COMPLETENESS BADGE (FITUR 3) -->
                            <span class="px-1.5 py-0.2 text-[9px] font-black rounded-md uppercase border
                                  {{ $statLabel === 'Lengkap' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ($bInfo['percentage'] > 0 ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-slate-100 text-slate-500 border-slate-300') }}">
                                {{ $statLabel }}
                            </span>
                        </a>
                    @endforeach

                    <!-- TOMBOL TAMBAH BAB BARU MANUAL (+ BAB Baru) -->
                    @if(!$isReadOnly)
                        <button type="button" 
                                onclick="document.getElementById('modal-add-bab').classList.remove('hidden')"
                                class="px-3 py-1.5 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center space-x-1.5 shadow-sm border border-emerald-500 cursor-pointer"
                                title="Tambah BAB Baru secara Manual (Tanpa Terikat Template Default)">
                            <i class="fa-solid fa-plus text-xs"></i>
                            <span>BAB Baru</span>
                        </button>
                    @endif

                    <!-- BAGIAN LAMPIRAN TABS -->
                    @if($appendixSections->count() > 0)
                        <div x-data="{ open: false }" class="relative">
                            <button x-on:click="open = !open" 
                                    class="px-3.5 py-2 rounded-lg text-xs font-bold border border-purple-300 bg-purple-50 text-purple-800 hover:bg-purple-100 transition flex items-center space-x-1.5">
                                <i class="fa-solid fa-paperclip text-purple-600"></i>
                                <span>LAMPIRAN</span>
                                <i class="fa-solid fa-chevron-down text-[10px]"></i>
                            </button>
                            <div x-show="open" x-on:click.away="open = false" 
                                 class="absolute left-0 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-xl z-50 py-1 text-xs font-medium">
                                @foreach($appendixSections as $ap)
                                    <a href="#section-wrapper-{{ $ap->id }}" x-on:click="open = false" class="block px-4 py-2 hover:bg-gray-100 text-gray-700">
                                        {{ $ap->sub_bab_code }} {{ $ap->sub_bab_title }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>

                <!-- Modul Switch Tab (Narasi / Pratinjau / SIPD) -->
                <div class="flex items-center space-x-2">
                    <button x-on:click="activeTab = 'narasi'" 
                            :class="activeTab === 'narasi' ? 'bg-gray-900 text-white font-bold' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition">
                        📝 Narasi
                    </button>
                    <button x-on:click="activeTab = 'sipd'" 
                            :class="activeTab === 'sipd' ? 'bg-gray-900 text-white font-bold' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition">
                        📊 SIPD
                    </button>
                    <button x-on:click="activeTab = 'pratinjau'" 
                            :class="activeTab === 'pratinjau' ? 'bg-gray-900 text-white font-bold' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition">
                        👁️ Pratinjau
                    </button>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- MAIN WORKSPACE & ADAPTIVE RIBBON TOOLBAR EDITOR              -->
        <!-- ============================================================ -->
        <main class="max-w-[1600px] mx-auto px-6">

            <!-- MODUL 1: SUSUN NARASI -->
            <div x-show="activeTab === 'narasi'" class="w-full flex justify-center">
                <section class="w-full space-y-4">
                    
                    @php
                        $currBabStatus = $secStatuses[$activeBabCode]['status'] ?? 'PENDING';
                        $currBabNotes = $secStatuses[$activeBabCode]['notes'] ?? '';
                    @endphp

                    @if($currBabStatus === 'NEEDS_REVISION' && !empty($currBabNotes))
                        <div class="p-4 bg-amber-500/10 border-2 border-amber-500/40 text-amber-950 rounded-2xl space-y-1 shadow-xs">
                            <div class="flex items-center space-x-2 text-xs font-black text-amber-950 uppercase tracking-wider">
                                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-sm"></i>
                                <span>CATATAN REVISI VERIFIKATOR BAPPERIDA KHUSUS {{ $activeBabCode }}:</span>
                            </div>
                            <p class="text-xs text-amber-900 leading-relaxed font-semibold pl-6">
                                {{ $currBabNotes }}
                            </p>
                        </div>
                    @elseif($currBabStatus === 'APPROVED')
                        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-2xl flex items-center space-x-2.5 text-xs font-bold shadow-2xs">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                            <span>{{ $activeBabCode }} telah disetujui (Approved) oleh Verifikator Bapperida.</span>
                        </div>
                    @endif
                    
                    <!-- COMPACT ADAPTIVE TOOLBAR WITH TABLE LAYOUT TAB (HEIGHT ~85px) -->
                    <div class="ui-card overflow-hidden" x-data="{ ribbonTab: 'utama' }">
                        
                        <!-- TAB STRIP HEADER (HOME / UTAMA VS LAYOUT TABEL) -->
                        <div class="flex items-center space-x-6 border-b border-gray-200 px-4 pt-2 bg-gray-50/80 text-xs font-bold select-none">
                            <button type="button" x-on:click="ribbonTab = 'utama'" 
                                    :class="ribbonTab === 'utama' ? 'border-b-2 border-blue-700 text-blue-700 pb-1 font-extrabold' : 'text-gray-500 hover:text-gray-800 pb-1'">
                                <i class="fa-solid fa-house-chimney text-[11px] mr-1"></i>
                                <span>Utama</span>
                            </button>
                            <button type="button" x-on:click="ribbonTab = 'table_layout'" 
                                    :class="ribbonTab === 'table_layout' ? 'border-b-2 border-indigo-700 text-indigo-700 pb-1 font-extrabold' : 'text-gray-500 hover:text-gray-800 pb-1'"
                                    class="flex items-center gap-1.5">
                                <i class="fa-solid fa-table-cells text-[11px] text-indigo-600"></i>
                                <span>Layout Tabel (MS Word)</span>
                                <span class="px-1.5 py-0.2 bg-indigo-100 text-indigo-800 text-[9px] rounded font-bold uppercase">Tools</span>
                            </button>
                        </div>

                        <!-- TAB 1: TOOLBAR UTAMA -->
                        <div x-show="ribbonTab === 'utama'" class="editor-toolbar">
                            <div class="flex items-center space-x-1 flex-wrap gap-y-1">
                                
                                <!-- GROUP 1: CLIPBOARD -->
                                <div class="toolbar-group">
                                    <div class="flex items-center space-x-1">
                                        <button class="toolbar-btn" onclick="formatDoc('undo')" title="Undo (Ctrl+Z)">
                                            <i class="fa-solid fa-rotate-left text-blue-700"></i>
                                            <span>Undo</span>
                                        </button>
                                        <button class="toolbar-btn" onclick="formatDoc('redo')" title="Redo (Ctrl+Y)">
                                            <i class="fa-solid fa-rotate-right text-gray-600"></i>
                                            <span>Redo</span>
                                        </button>
                                    </div>
                                    <span class="toolbar-group-label">Clipboard</span>
                                </div>

                                <!-- GROUP 2: FONT & FORMATTING -->
                                <div class="toolbar-group">
                                    <div class="flex items-center space-x-1.5">
                                        <span class="px-2 py-1 bg-gray-100 border border-gray-200 rounded text-xs font-semibold text-gray-700 select-none">
                                            {{ $formatConfig['font_family'] ?? 'Bookman Old Style' }}
                                        </span>
                                        <span class="px-2 py-1 bg-gray-100 border border-gray-200 rounded text-xs font-semibold text-gray-700 select-none">
                                            {{ $formatConfig['font_size_pt'] ?? 12 }} pt
                                        </span>

                                        @if($toolbarConfig['bold'] ?? false)
                                            <button class="toolbar-btn-sm font-bold text-gray-900" onclick="formatDoc('bold')" title="Tebal (Bold)">
                                                <b>B</b>
                                            </button>
                                        @endif

                                        <button class="toolbar-btn-sm font-serif italic text-gray-800" onclick="formatDoc('italic')" title="Miring (Italic)">
                                            <i>I</i>
                                        </button>
                                        <button class="toolbar-btn-sm underline text-gray-800" onclick="formatDoc('underline')" title="Garis Bawah (Underline)">
                                            <u>U</u>
                                        </button>
                                    </div>
                                    <span class="toolbar-group-label">Font</span>
                                </div>

                                <!-- GROUP 3: PARAGRAPH ALIGNMENT & LISTS -->
                                <div class="toolbar-group">
                                    <div class="flex items-center space-x-1">
                                        <button class="toolbar-btn-sm" onclick="formatDoc('justifyLeft')" title="Rata Kiri"><i class="fa-solid fa-align-left text-gray-700"></i></button>
                                        <button class="toolbar-btn-sm" onclick="formatDoc('justifyCenter')" title="Rata Tengah"><i class="fa-solid fa-align-center text-gray-700"></i></button>
                                        <button class="toolbar-btn-sm" onclick="formatDoc('justifyRight')" title="Rata Kanan"><i class="fa-solid fa-align-right text-gray-700"></i></button>
                                        <button class="toolbar-btn-sm bg-blue-50 text-blue-700 border-blue-200" onclick="formatDoc('justifyFull')" title="Rata Kiri Kanan (Justify)"><i class="fa-solid fa-align-justify"></i></button>
                                        <button class="toolbar-btn-sm" onclick="formatDoc('insertUnorderedList')" title="Bullets List"><i class="fa-solid fa-list-ul text-gray-700"></i></button>
                                        <button class="toolbar-btn-sm" onclick="formatDoc('insertOrderedList')" title="Numbering List"><i class="fa-solid fa-list-ol text-gray-700"></i></button>
                                    </div>
                                    <span class="toolbar-group-label">Paragraf</span>
                                </div>

                                <!-- GROUP 4: INSERT & TOOL TEMPLATE -->
                                <div class="toolbar-group">
                                    <div class="flex items-center space-x-1">
                                        <button class="toolbar-btn" onclick="document.getElementById('modal-add-subbab').classList.remove('hidden')" title="Tambah Sub-Bab Baru">
                                            <i class="fa-solid fa-folder-plus text-emerald-600"></i>
                                            <span class="font-bold text-emerald-700">+ Sub-Bab</span>
                                        </button>
                                        <button class="toolbar-btn" onclick="document.getElementById('modal-word-table-tools').classList.remove('hidden'); ribbonTab = 'table_layout';" title="Insert & Layout Tabel">
                                            <i class="fa-solid fa-table text-indigo-600"></i>
                                            <span>Tabel</span>
                                        </button>
                                        <button class="toolbar-btn" onclick="insertImagePrompt()" title="Sisipkan Gambar">
                                            <i class="fa-regular fa-image text-gray-600"></i>
                                            <span>Gambar</span>
                                        </button>

                                        @if($toolbarConfig['cover_editor'] ?? false)
                                            <button class="toolbar-btn" onclick="document.getElementById('modal-edit-cover').classList.remove('hidden')" title="Edit Data Cover Dokumen">
                                                <i class="fa-solid fa-book-open text-purple-600"></i>
                                                <span class="font-bold text-purple-700">Cover</span>
                                            </button>
                                        @endif

                                        @if($toolbarConfig['automatic_lists'] ?? false)
                                            <button class="toolbar-btn" onclick="refreshAutomaticLists()" title="Perbarui Daftar Isi, Tabel & Gambar Otomatis">
                                                <i class="fa-solid fa-arrows-rotate text-amber-600"></i>
                                                <span class="font-bold text-amber-700">Ref. Daftar</span>
                                            </button>
                                        @endif
                                    </div>
                                    <span class="toolbar-group-label">Sisipkan & Fitur</span>
                                </div>

                                <!-- GROUP 5: MESIN CUCI V2 & AUTO-SUM (SPRINT 6.2) -->
                                <div class="toolbar-group bg-amber-50/50 border border-amber-200/60 rounded-xl p-1">
                                    <div class="flex items-center space-x-1">
                                        <button class="toolbar-btn" onclick="triggerActiveSectionAutoFix()" title="Mesin Cuci V2: Bersihkan Bold & Rapikan Format Dokumen (Pemkab Cirebon)">
                                            <i class="fa-solid fa-wand-magic-sparkles text-amber-600 animate-pulse"></i>
                                            <span class="font-black text-amber-900">Mesin Cuci V2</span>
                                        </button>
                                        <button class="toolbar-btn" onclick="triggerAutoSumCalculator()" title="Auto-Sum: Hitung Otomatis Total Pagu & Target Kinerja Tabel">
                                            <i class="fa-solid fa-calculator text-emerald-600"></i>
                                            <span class="font-bold text-emerald-800">Auto-Sum</span>
                                        </button>
                                    </div>
                                    <span class="toolbar-group-label text-amber-800 font-bold">Auto-Fix & Format</span>
                                </div>

                            </div>

                            <!-- EXPORT MS WORD BUTTON -->
                            <div class="flex items-center">
                                <a href="{{ route('renja.exportWord', $document->id) }}" 
                                   class="px-4 py-2 bg-[#1D4ED8] hover:bg-blue-800 text-white font-bold text-xs rounded-lg shadow-md transition flex items-center space-x-2 border border-blue-900">
                                    <i class="fa-solid fa-file-word text-blue-200 text-sm"></i>
                                    <span>Unduh MS Word</span>
                                </a>
                            </div>

                        </div>

                        <!-- TAB 2: TOOLBAR LAYOUT TABEL (MS WORD TAB SPECIFIC) -->
                        <div x-show="ribbonTab === 'table_layout'" class="editor-toolbar bg-indigo-50/30">
                            <div class="flex items-center space-x-2 flex-wrap gap-y-1.5">

                                <!-- GROUP 1: MERGE & SPLIT -->
                                <div class="toolbar-group">
                                    <div class="flex items-center space-x-1">
                                        <button class="toolbar-btn" onclick="tableMergeCells()" title="Gabung Sel Horizontal (Merge Cells)">
                                            <i class="fa-solid fa-object-group text-indigo-700"></i>
                                            <span class="font-bold">Merge Cells</span>
                                        </button>
                                        <button class="toolbar-btn" onclick="tableSplitCells()" title="Pecah Sel (Split Cells)">
                                            <i class="fa-solid fa-object-ungroup text-slate-700"></i>
                                            <span>Split Cells</span>
                                        </button>
                                    </div>
                                    <span class="toolbar-group-label">Merge & Split</span>
                                </div>

                                <!-- GROUP 2: ROW & COLUMN INSERTION -->
                                <div class="toolbar-group">
                                    <div class="flex items-center space-x-1">
                                        <button class="toolbar-btn-sm" onclick="tableAddRow('above')" title="Sisipkan Baris di Atas">
                                            <i class="fa-solid fa-arrow-up text-emerald-600"></i>
                                            <span>+ Atas</span>
                                        </button>
                                        <button class="toolbar-btn-sm" onclick="tableAddRow('below')" title="Sisipkan Baris di Bawah">
                                            <i class="fa-solid fa-arrow-down text-emerald-600"></i>
                                            <span>+ Bawah</span>
                                        </button>
                                        <button class="toolbar-btn-sm" onclick="tableAddColumn('before')" title="Sisipkan Kolom di Kiri">
                                            <i class="fa-solid fa-arrow-left text-blue-600"></i>
                                            <span>+ Kiri</span>
                                        </button>
                                        <button class="toolbar-btn-sm" onclick="tableAddColumn('after')" title="Sisipkan Kolom di Kanan">
                                            <i class="fa-solid fa-arrow-right text-blue-600"></i>
                                            <span>+ Kanan</span>
                                        </button>
                                    </div>
                                    <span class="toolbar-group-label">Baris & Kolom</span>
                                </div>

                                <!-- GROUP 3: CELL SIZE & AUTOFIT -->
                                <div class="toolbar-group">
                                    <div class="flex items-center space-x-1">
                                        <button class="toolbar-btn-sm" onclick="tableSetAutoFit('window')" title="AutoFit Window (100% Lebar Halaman)">
                                            <i class="fa-solid fa-expand text-blue-700"></i>
                                            <span>AutoFit 100%</span>
                                        </button>
                                        <button class="toolbar-btn-sm" onclick="tableSetAutoFit('content')" title="AutoFit Isi Konten">
                                            <i class="fa-solid fa-compress text-slate-700"></i>
                                            <span>AutoFit Konten</span>
                                        </button>
                                        <button class="toolbar-btn-sm" onclick="tableDistributeColumns()" title="Ratakan Lebar Seluruh Kolom (Distribute Columns)">
                                            <i class="fa-solid fa-table-columns text-purple-700"></i>
                                            <span>Distribute Cols</span>
                                        </button>
                                    </div>
                                    <span class="toolbar-group-label">Ukuran Sel & AutoFit</span>
                                </div>

                                <!-- GROUP 4: 9-BOX CELL ALIGNMENT GRID -->
                                <div class="toolbar-group">
                                    <div class="grid grid-cols-3 gap-0.5 bg-gray-200 p-1 rounded-lg border border-gray-300">
                                        <button class="w-5 h-5 bg-white hover:bg-indigo-100 rounded text-[9px] flex items-center justify-center" onclick="tableSetCellVAlign('top', 'left')" title="Rata Atas Kiri">↖️</button>
                                        <button class="w-5 h-5 bg-white hover:bg-indigo-100 rounded text-[9px] flex items-center justify-center" onclick="tableSetCellVAlign('top', 'center')" title="Rata Atas Tengah">⬆️</button>
                                        <button class="w-5 h-5 bg-white hover:bg-indigo-100 rounded text-[9px] flex items-center justify-center" onclick="tableSetCellVAlign('top', 'right')" title="Rata Atas Kanan">↗️</button>
                                        <button class="w-5 h-5 bg-white hover:bg-indigo-100 rounded text-[9px] flex items-center justify-center" onclick="tableSetCellVAlign('middle', 'left')" title="Rata Tengah Kiri">⬅️</button>
                                        <button class="w-5 h-5 bg-white hover:bg-indigo-100 rounded text-[9px] flex items-center justify-center" onclick="tableSetCellVAlign('middle', 'center')" title="Rata Tengah (Center)">⏹️</button>
                                        <button class="w-5 h-5 bg-white hover:bg-indigo-100 rounded text-[9px] flex items-center justify-center" onclick="tableSetCellVAlign('middle', 'right')" title="Rata Tengah Kanan">➡️</button>
                                        <button class="w-5 h-5 bg-white hover:bg-indigo-100 rounded text-[9px] flex items-center justify-center" onclick="tableSetCellVAlign('bottom', 'left')" title="Rata Bawah Kiri">↙️</button>
                                        <button class="w-5 h-5 bg-white hover:bg-indigo-100 rounded text-[9px] flex items-center justify-center" onclick="tableSetCellVAlign('bottom', 'center')" title="Rata Bawah Tengah">⬇️</button>
                                        <button class="w-5 h-5 bg-white hover:bg-indigo-100 rounded text-[9px] flex items-center justify-center" onclick="tableSetCellVAlign('bottom', 'right')" title="Rata Bawah Kanan">↘️</button>
                                    </div>
                                    <span class="toolbar-group-label">Alignment 9-Grid</span>
                                </div>

                                <!-- GROUP 5: CELL MARGINS & REPEAT HEADER -->
                                <div class="toolbar-group">
                                    <div class="flex items-center space-x-1">
                                        <button class="toolbar-btn-sm" onclick="tableSetCellPadding('4px 6px')" title="Padding Sel Rapat (4px)">Padding 4px</button>
                                        <button class="toolbar-btn-sm" onclick="tableSetCellPadding('8px 10px')" title="Padding Sel Normal (8px)">Padding 8px</button>
                                        <button class="toolbar-btn-sm" onclick="tableSetRepeatHeader()" title="Set Row 1 sebagai Header Berulang (Repeat Header Row)">
                                            <i class="fa-solid fa-heading text-amber-600"></i>
                                            <span>Repeat Header</span>
                                        </button>
                                    </div>
                                    <span class="toolbar-group-label">Cell Margins & Data</span>
                                </div>

                            </div>

                            <!-- EXPORT MS WORD BUTTON -->
                            <div class="flex items-center">
                                <a href="{{ route('renja.exportWord', $document->id) }}" 
                                   class="px-4 py-2 bg-[#1D4ED8] hover:bg-blue-800 text-white font-bold text-xs rounded-lg shadow-md transition flex items-center space-x-2 border border-blue-900">
                                    <i class="fa-solid fa-file-word text-blue-200 text-sm"></i>
                                    <span>Unduh MS Word</span>
                                </a>
                            </div>

                        </div>

                    </div>

                    <!-- FOCUSED ENLARGED DOCUMENT WORKSPACE CANVAS -->
                    <div class="document-workspace" id="renja-workspace-container">
                        <div class="pages-zoom-wrapper" id="pages-zoom-wrapper" style="--editor-zoom: 1;">

                            <!-- FLOATING CONTEXTUAL TABLE TOOLBAR ENGINE -->
                            <div id="table-context-bar" class="hidden sticky top-4 z-40 mb-3 mx-auto max-w-4xl bg-slate-900/95 backdrop-blur-md text-white p-2.5 rounded-xl shadow-2xl border border-slate-700 flex items-center justify-between flex-wrap gap-2 text-xs select-none">
                                <div class="flex items-center space-x-2 flex-wrap gap-y-1.5">
                                    <span class="px-2.5 py-1 bg-indigo-600 text-white rounded-lg font-extrabold text-[10px] uppercase tracking-wider flex items-center gap-1.5 shadow-xs">
                                        <i class="fa-solid fa-table text-[11px]"></i>
                                        <span>Tabel Controls</span>
                                    </span>

                                    <!-- BARIS -->
                                    <div class="flex items-center space-x-1 bg-slate-800 p-1 rounded-lg border border-slate-700">
                                        <button type="button" onclick="tableAddRow('above')" title="Tambah Baris di Atas" class="px-2 py-1 hover:bg-slate-700 rounded text-slate-200 hover:text-white flex items-center gap-1 font-bold">
                                            <i class="fa-solid fa-arrow-up text-[10px] text-emerald-400"></i>
                                            <span>+ Baris</span>
                                        </button>
                                        <button type="button" onclick="tableAddRow('below')" title="Tambah Baris di Bawah" class="px-2 py-1 hover:bg-slate-700 rounded text-slate-200 hover:text-white flex items-center gap-1 font-bold">
                                            <i class="fa-solid fa-arrow-down text-[10px] text-emerald-400"></i>
                                            <span>+ Baris</span>
                                        </button>
                                        <button type="button" onclick="tableDeleteRow()" title="Hapus Baris Ini" class="px-2 py-1 hover:bg-red-900/50 rounded text-red-300 hover:text-red-100 flex items-center gap-1 font-bold">
                                            <i class="fa-solid fa-trash-can text-[10px] text-red-400"></i>
                                        </button>
                                    </div>

                                    <!-- KOLOM -->
                                    <div class="flex items-center space-x-1 bg-slate-800 p-1 rounded-lg border border-slate-700">
                                        <button type="button" onclick="tableAddColumn('before')" title="Tambah Kolom di Kiri" class="px-2 py-1 hover:bg-slate-700 rounded text-slate-200 hover:text-white flex items-center gap-1 font-bold">
                                            <i class="fa-solid fa-arrow-left text-[10px] text-blue-400"></i>
                                            <span>+ Kolom</span>
                                        </button>
                                        <button type="button" onclick="tableAddColumn('after')" title="Tambah Kolom di Kanan" class="px-2 py-1 hover:bg-slate-700 rounded text-slate-200 hover:text-white flex items-center gap-1 font-bold">
                                            <i class="fa-solid fa-arrow-right text-[10px] text-blue-400"></i>
                                            <span>+ Kolom</span>
                                        </button>
                                        <button type="button" onclick="tableDeleteColumn()" title="Hapus Kolom Ini" class="px-2 py-1 hover:bg-red-900/50 rounded text-red-300 hover:text-red-100 flex items-center gap-1 font-bold">
                                            <i class="fa-solid fa-trash-can text-[10px] text-red-400"></i>
                                        </button>
                                    </div>

                                    <!-- POSISI / ALIGNMENT TABEL -->
                                    <div class="flex items-center space-x-1 bg-slate-800 p-1 rounded-lg border border-slate-700">
                                        <button type="button" onclick="tableSetAlignment('left')" title="Posisi Tabel Rata Kiri" class="px-2 py-1 hover:bg-slate-700 rounded text-slate-300 hover:text-white">
                                            <i class="fa-solid fa-align-left"></i>
                                        </button>
                                        <button type="button" onclick="tableSetAlignment('center')" title="Posisi Tabel Rata Tengah (Center)" class="px-2 py-1 hover:bg-slate-700 rounded text-slate-300 hover:text-white">
                                            <i class="fa-solid fa-align-center text-amber-400"></i>
                                        </button>
                                        <button type="button" onclick="tableSetAlignment('right')" title="Posisi Tabel Rata Kanan" class="px-2 py-1 hover:bg-slate-700 rounded text-slate-300 hover:text-white">
                                            <i class="fa-solid fa-align-right"></i>
                                        </button>
                                    </div>

                                    <!-- WARNA BACKGROUND SEL -->
                                    <div class="flex items-center space-x-1.5 bg-slate-800 p-1.5 rounded-lg border border-slate-700">
                                        <button type="button" onclick="tableSetCellBgColor('#F3F4F6')" class="w-4 h-4 rounded-full bg-gray-200 border border-white hover:scale-125 transition" title="Latar Sel: Abu Header"></button>
                                        <button type="button" onclick="tableSetCellBgColor('#EFF6FF')" class="w-4 h-4 rounded-full bg-blue-100 border border-white hover:scale-125 transition" title="Latar Sel: Biru Muda"></button>
                                        <button type="button" onclick="tableSetCellBgColor('#ECFDF5')" class="w-4 h-4 rounded-full bg-emerald-100 border border-white hover:scale-125 transition" title="Latar Sel: Hijau Muda"></button>
                                        <button type="button" onclick="tableSetCellBgColor('#FEF3C7')" class="w-4 h-4 rounded-full bg-amber-100 border border-white hover:scale-125 transition" title="Latar Sel: Kuning Muda"></button>
                                        <button type="button" onclick="tableSetCellBgColor('#FFFFFF')" class="w-4 h-4 rounded-full bg-white border border-gray-400 hover:scale-125 transition" title="Latar Sel: Putih"></button>
                                    </div>

                                    <!-- BORDER -->
                                    <div class="flex items-center space-x-1 bg-slate-800 p-1 rounded-lg border border-slate-700">
                                        <button type="button" onclick="tableSetBorderPattern('normal')" class="px-2 py-1 hover:bg-slate-700 rounded text-[11px] font-bold text-slate-200" title="Garis Bingkai 1px">1px</button>
                                        <button type="button" onclick="tableSetBorderPattern('thick')" class="px-2 py-1 hover:bg-slate-700 rounded text-[11px] font-bold text-slate-200" title="Garis Bingkai 2px">2px</button>
                                        <button type="button" onclick="tableSetBorderPattern('none')" class="px-2 py-1 hover:bg-slate-700 rounded text-[11px] font-bold text-red-300" title="Tanpa Garis Bingkai">Tanpa</button>
                                    </div>
                                </div>

                                <button type="button" onclick="tableRemoveEntireTable()" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white font-extrabold rounded-lg transition text-xs flex items-center gap-1 shadow">
                                    <i class="fa-solid fa-trash"></i>
                                    <span>Hapus Tabel</span>
                                </button>
                            </div>

                            <section class="paper-f4 continuous-paper" id="paper-canvas-main">

                                <!-- KONTEN SEKSI BAGIAN AWAL (FRONT MATTER) -->
                                @if($isFrontView && $frontSections->count() > 0)
                                    <div class="front-matter-container space-y-10 pb-10">
                                        <div class="text-center font-black text-sm uppercase tracking-wider text-slate-800 border-b pb-3 mb-6">
                                            BAGIAN AWAL DOKUMEN
                                        </div>
                                        @foreach($frontSections as $fs)
                                            <div class="front-section-wrapper relative group mb-10 pb-8 border-b border-slate-200 last:border-0" id="section-wrapper-{{ $fs->id }}">
                                                <div class="flex items-center justify-between border-b pb-2 mb-4">
                                                    <span class="text-xs font-black text-indigo-700 uppercase tracking-widest">[ {{ $fs->sub_bab_title }} ]</span>
                                                    @if(!$isReadOnly && !$fs->is_required && empty($fs->template_section_id))
                                                        <form action="{{ route('renja.editor.deleteSection', [$document->id, $fs->id]) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus {{ $fs->sub_bab_title }} dari Bagian Awal?')" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-bold flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                                                                <i class="fa-solid fa-trash-can"></i>
                                                                <span>Hapus Halaman</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>

                                                <!-- ISI SEKSI FRONT MATTER -->
                                                <div id="section-content-{{ $fs->id }}"
                                                     class="subbab-content {{ $fs->section_type === 'table_of_contents' ? 'select-none bg-slate-50/50 p-4 rounded-xl border border-slate-200' : '' }}"
                                                     contenteditable="{{ $fs->section_type === 'table_of_contents' ? 'false' : 'true' }}"
                                                     spellcheck="false"
                                                     data-section-id="{{ $fs->id }}"
                                                     oninput="{{ $fs->section_type === 'table_of_contents' ? '' : "saveSectionAjax('{$fs->id}')" }}">
                                                    {!! $fs->content !!}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- KONTEN BAB UTAMA DAN SUB-BAB -->
                                @if(!$isFrontView && $sections->count() === 0)
                                    <div class="text-center py-20 px-6 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50 space-y-4 my-8 select-none">
                                        <div class="w-14 h-14 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center mx-auto text-xl shadow-xs">
                                            <i class="fa-solid fa-file-circle-plus"></i>
                                        </div>
                                        <div class="space-y-1">
                                            <h3 class="text-sm font-extrabold text-slate-800">Dokumen Masih Kosong</h3>
                                            <p class="text-xs text-slate-500 max-w-sm mx-auto">Dokumen ini belum memiliki BAB maupun Sub-Bab. Klik tombol <strong class="text-emerald-700 font-black">+ BAB Baru</strong> pada navigasi atas untuk membuat BAB pertama secara manual.</p>
                                        </div>
                                        <button onclick="document.getElementById('modal-add-bab').classList.remove('hidden')" 
                                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md transition inline-flex items-center gap-2">
                                            <i class="fa-solid fa-plus text-xs"></i>
                                            <span>+ BAB Baru</span>
                                        </button>
                                    </div>
                                @elseif(!$isFrontView)
                                    <!-- VISUAL PAGE BREAK DIVIDER FOR NON-RENJA DOCUMENTS (BR-PAGE-01 & BR-PAGE-05) -->
                                    @php
                                        $isRenja = str_contains(strtoupper($document->jenis_dokumen), 'RENJA');
                                    @endphp
                                    @if(!$isRenja && !empty($activeBabCode))
                                        <div class="page-break-divider my-8 flex items-center justify-center select-none" id="page-break-{{ $activeBabCode }}">
                                            <div class="w-full border-t-2 border-dashed border-indigo-400"></div>
                                            <span class="px-4 py-1 bg-indigo-100 border border-indigo-300 rounded-full text-[10px] font-extrabold text-indigo-900 uppercase tracking-wider flex items-center gap-2 whitespace-nowrap shadow-xs">
                                                <i class="fa-solid fa-scissors text-indigo-600"></i>
                                                <span>PAGE BREAK — HALAMAN BARU</span>
                                            </span>
                                            <div class="w-full border-t-2 border-dashed border-indigo-400"></div>
                                        </div>
                                    @endif

                                    <!-- JUDUL BAB UTAMA -->
                                    @php
                                        $rawBabTitle = $activeSubBabs->first()->bab_title ?? '';
                                        $babTitleDefaults = [
                                            'BAB I' => 'PENDAHULUAN',
                                            'BAB II' => 'EVALUASI PELAKSANAAN RENJA PERANGKAT DAERAH TAHUN LALU',
                                            'BAB III' => 'TUJUAN DAN SASARAN PERANGKAT DAERAH',
                                            'BAB IV' => 'RENCANA KERJA DAN PENDANAAN PERANGKAT DAERAH',
                                            'BAB V' => 'TARGET KINERJA DAN INDIKATOR PROGRAM/KEGIATAN',
                                            'BAB VI' => 'PENUTUP',
                                        ];
                                        
                                        if (empty($rawBabTitle) || strtoupper(trim($rawBabTitle)) === strtoupper(trim($activeBabCode))) {
                                            $displayBabTitle = $babTitleDefaults[$activeBabCode] ?? '';
                                        } else {
                                            $displayBabTitle = strtoupper($rawBabTitle);
                                        }
                                        $isCanonicalBab = in_array(strtoupper(trim($activeBabCode)), ['BAB I', 'BAB II', 'BAB III', 'BAB IV', 'BAB V']);
                                    @endphp
                                    <div id="bab-header-main" class="bab-heading-block mb-6 relative group">
                                        <div style="text-align: center; text-transform: uppercase; font-family: 'Bookman Old Style', 'Bookman', serif; font-size: 12pt; margin-bottom: 16pt; font-weight: normal !important;">
                                            <div style="font-weight: normal !important;">{{ $activeBabCode }}</div>
                                            @if(!empty($displayBabTitle) && $displayBabTitle !== $activeBabCode)
                                                <div style="font-weight: normal !important;">{{ $displayBabTitle }}</div>
                                            @endif
                                        </div>
                                        @if(!$isReadOnly && !empty($activeBabCode) && !$isCanonicalBab)
                                            <div class="absolute right-0 top-0 opacity-0 group-hover:opacity-100 transition flex items-center space-x-1.5 bg-white/90 p-1 rounded-lg border border-slate-200 shadow-xs">
                                                <button type="button" onclick="document.getElementById('modal-edit-bab-title').classList.remove('hidden')" class="text-[10px] text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-300 px-2 py-0.5 rounded font-bold transition flex items-center gap-1" title="Edit Judul BAB Ini">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                    <span>Edit BAB</span>
                                                </button>
                                                <form action="{{ route('renja.editor.deleteBab', [$document->id, $activeBabCode]) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus {{ $activeBabCode }} beserta seluruh Sub-Bab di dalamnya?')" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-[10px] text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-300 px-2 py-0.5 rounded font-bold transition flex items-center gap-1" title="Hapus BAB Ini">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                        <span>Hapus BAB</span>
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- SUB-BAB SECTIONS LOOP -->
                                    @forelse($activeSubBabs as $sec)
                                        @php
                                            $cleanSubTitle = preg_replace('/^\d+(\.\d+)*\s*/', '', $sec->sub_bab_title);
                                            $isCanonicalSub = !empty($sec->template_section_id) || ($sec->is_required ?? false);
                                        @endphp
                                        <div class="subbab-wrapper mb-6" id="section-wrapper-{{ $sec->id }}">
                                            
                                            <!-- HEADER BAR SUB-BAB -->
                                            <div class="subbab-header-bar flex items-center justify-between group">
                                                <div class="flex items-center space-x-2 font-normal text-slate-900" style="font-family:'Bookman Old Style', 'Bookman', serif; font-size:12pt; font-weight: normal !important; color: #000000;">
                                                    <span style="font-weight: normal !important; color:#000000;">{{ $sec->sub_bab_code }}</span>
                                                    <span id="subbab-title-text-{{ $sec->id }}" style="font-weight: normal !important; color:#000000;">{{ $cleanSubTitle }}</span>
                                                    @if(!$isReadOnly && !$isCanonicalSub)
                                                        <button onclick="openEditSubBabModal('{{ $sec->id }}', '{{ e($sec->sub_bab_code) }}', '{{ e($cleanSubTitle) }}')" class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-amber-600 text-xs transition" title="Edit Judul Sub-Bab">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>
                                                    @endif
                                                </div>

                                                @if(!$isReadOnly && !$isCanonicalSub)
                                                    <div class="flex items-center space-x-2">
                                                        <form action="{{ route('renja.editor.deleteSection', [$document->id, $sec->id]) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Sub-Bab {{ $sec->sub_bab_code }} {{ e($cleanSubTitle) }}?')" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-rose-400 hover:text-rose-600 text-xs p-1" title="Hapus Sub-Bab">
                                                                <i class="fa-solid fa-trash-can"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- AREA KETIK NARASI SUB-BAB -->
                                            <div id="section-content-{{ $sec->id }}"
                                                 class="subbab-content"
                                                 contenteditable="true"
                                                 spellcheck="false"
                                                 data-section-id="{{ $sec->id }}"
                                                 onkeydown="{{ ($formatConfig['allow_bold'] ?? false) ? '' : 'preventBoldShortcut(event)' }}"
                                                 oninput="saveSectionAjax('{{ $sec->id }}')">
                                                {!! $sec->content !!}
                                            </div>

                                        </div>
                                    @empty
                                        <div class="text-center py-16 text-gray-400 text-sm">
                                            <i class="fa-solid fa-file-circle-plus text-3xl mb-3 text-gray-300"></i>
                                            <p>Belum ada Sub-Bab pada {{ $activeBabCode }}.</p>
                                            <p class="text-xs text-gray-400 mt-1">Klik tombol <strong class="text-emerald-600">+ Sub-Bab Baru</strong> di atas untuk menambah.</p>
                                        </div>
                                    @endforelse

                                    <!-- BUTTON TAMBAH SUB-BAB BARU PADA KANVAS -->
                                    <div class="mt-6 pt-4 border-t border-dashed border-gray-200 flex justify-center select-none">
                                        <button onclick="document.getElementById('modal-add-subbab').classList.remove('hidden')" 
                                                class="px-5 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-xs font-bold transition flex items-center space-x-2 shadow-xs group">
                                            <i class="fa-solid fa-plus text-emerald-600 group-hover:scale-110 transition"></i>
                                            <span>Tambah Sub-Bab Baru pada {{ $activeBabCode }}</span>
                                        </button>
                                    </div>
                                @endif

                            </section>

                        </div>
                    </div>

                </section>
            </div>

            <!-- MODUL 2: ANGGARAN SIPD -->
            <div x-show="activeTab === 'sipd'" class="ui-card p-6 space-y-6">
                <div class="flex justify-between items-center border-b pb-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Tabel Evaluasi & Matriks Anggaran SIPD</h2>
                        <p class="text-xs text-gray-500">Tabel realisasi dan perkiraan anggaran SKPD terintegrasi dengan SIPD RI.</p>
                    </div>
                    <button onclick="document.getElementById('modal-add-sipd').classList.remove('hidden')" class="bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs px-4 py-2 rounded-lg shadow transition flex items-center space-x-1.5">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Tambah Tabel SIPD</span>
                    </button>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="w-full text-xs text-left text-gray-700">
                        <thead class="bg-gray-100 text-gray-900 uppercase text-[10px] font-bold">
                            <tr>
                                <th class="p-3 border-b">Kode</th>
                                <th class="p-3 border-b">Uraian Program / Kegiatan</th>
                                <th class="p-3 border-b text-right">Pagu RKPD (Rp)</th>
                                <th class="p-3 border-b text-right">Realisasi (Rp)</th>
                                <th class="p-3 border-b text-center">Tingkat (%)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($document->tableEvals as $eval)
                                <tr class="hover:bg-gray-50">
                                    <td class="p-3 font-mono font-bold text-gray-900">{{ $eval->kode_program }}</td>
                                    <td class="p-3 font-medium">{{ $eval->nama_program }}</td>
                                    <td class="p-3 text-right font-mono">Rp {{ number_format($eval->pagu_rkpd, 0, ',', '.') }}</td>
                                    <td class="p-3 text-right font-mono text-emerald-700 font-bold">Rp {{ number_format($eval->realisasi_pagu ?? $eval->pagu_rkpd, 0, ',', '.') }}</td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">100%</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-gray-400">Belum ada data tabel evaluasi SIPD terhubung.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODUL 3: PRATINJAU DOKUMEN F4 -->
            <div x-show="activeTab === 'pratinjau'" class="w-full flex justify-center py-6">
                <section class="ui-card max-w-[900px] w-full p-12 space-y-6 preview-paper">
                    <div class="text-center font-bold text-sm uppercase tracking-wide border-b pb-4 mb-6">
                        PRATINJAU DOKUMEN — {{ $activeTemplate?->name ?? 'DOKUMEN' }} ({{ $activeBabCode }})
                    </div>

                    @forelse($activeSubBabs as $sec)
                        <div class="space-y-3 mb-8">
                            <h3 class="font-normal text-base text-gray-900 border-b border-dashed pb-1" style="font-family:'Bookman Old Style', serif;">
                                <span class="font-normal text-amber-700 mr-2">{{ $sec->sub_bab_code }}</span>
                                {{ preg_replace('/^\d+(\.\d+)*\s*/', '', $sec->sub_bab_title) }}
                            </h3>
                            <div class="text-justify leading-relaxed">
                                {!! $sec->content !!}
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-400 py-12">Belum ada konten untuk dipratinjau.</div>
                    @endforelse
                </section>
            </div>

        </main>
    </div>

    <!-- MODAL PILIH JENIS DOKUMEN TEMPLATE -->
    <div id="modal-select-template" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4 max-h-[85vh] flex flex-col border border-slate-200">
            <div class="flex justify-between items-center border-b pb-3 shrink-0">
                <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice text-blue-600"></i>
                    <span>Pilih Jenis Dokumen Smart Template</span>
                </h3>
                <button onclick="document.getElementById('modal-select-template').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>
            
            <form action="{{ route('renja.editor.switchTemplate', $document->id) }}" method="POST" class="space-y-4 flex-1 flex flex-col min-h-0">
                @csrf
                <div class="space-y-3 text-xs overflow-y-auto flex-1 pr-1">
                    <div class="flex items-center justify-between">
                        <p class="text-gray-600 font-medium">Pilih jenis dokumen untuk memuat konfigurasi otomatis:</p>
                        <button type="button" onclick="document.getElementById('modal-select-template').classList.add('hidden'); document.getElementById('modal-add-custom-template').classList.remove('hidden');" 
                                class="text-blue-700 hover:text-blue-900 font-extrabold text-[11px] underline flex items-center gap-1 shrink-0">
                            <i class="fa-solid fa-circle-plus"></i>
                            <span>+ Tambah Template Manual</span>
                        </button>
                    </div>

                    @forelse($availableTemplates as $tmpl)
                        @php
                            $isCurrent = ($activeTemplate?->id ?? null) === $tmpl->id || (!$activeTemplate && $tmpl->code === 'MANUAL');
                        @endphp
                        <label class="p-3.5 border rounded-xl flex items-start space-x-3 cursor-pointer hover:bg-blue-50/50 transition {{ $isCurrent ? 'border-blue-500 bg-blue-50/40 ring-2 ring-blue-500/20' : 'border-gray-200' }}">
                            <input type="radio" name="template_code" value="{{ $tmpl->code }}" {{ $isCurrent ? 'checked' : '' }} class="mt-0.5 text-blue-600 accent-blue-600">
                            <div>
                                <div class="font-bold text-gray-900 text-xs flex items-center gap-1.5">
                                    <span>{{ $tmpl->name }} ({{ $tmpl->code }})</span>
                                    @if(!$tmpl->id || $tmpl->code === 'MANUAL')
                                        <span class="px-1.5 py-0.5 bg-emerald-600 text-white rounded text-[9px] font-black uppercase">Tanpa Template</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">{{ $tmpl->description }}</div>
                            </div>
                        </label>
                    @empty
                        <div class="text-center py-6 px-4 bg-slate-50 rounded-xl border border-dashed border-slate-300 space-y-2">
                            <div class="w-10 h-10 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto text-base">
                                <i class="fa-solid fa-folder-open"></i>
                            </div>
                            <p class="text-xs text-slate-700 font-bold">Belum Ada Template Terdaftar</p>
                            <p class="text-[11px] text-slate-500 max-w-xs mx-auto">Seluruh template telah dihapus. Anda dapat membuat template baru secara manual menggunakan tombol di bawah ini.</p>
                        </div>
                    @endforelse
                </div>

                <div class="flex justify-between items-center pt-3 border-t shrink-0">
                    <button type="button" onclick="document.getElementById('modal-select-template').classList.add('hidden'); document.getElementById('modal-add-custom-template').classList.remove('hidden');" 
                            class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold rounded-lg border border-blue-200 flex items-center gap-1.5">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                        <span>Buat Template Baru</span>
                    </button>
                    <div class="flex space-x-2">
                        <button type="button" onclick="document.getElementById('modal-select-template').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-blue-700 text-white text-xs font-bold rounded-lg shadow">Terapkan Template</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL TAMBAH TEMPLATE DOKUMEN MANUAL -->
    <div id="modal-add-custom-template" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 space-y-4 max-h-[85vh] flex flex-col border border-slate-200">
            <div class="flex justify-between items-center border-b pb-3 shrink-0">
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-folder-plus text-blue-600"></i>
                    <span>Tambah Template Dokumen Baru (Manual)</span>
                </h3>
                <button onclick="document.getElementById('modal-add-custom-template').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <form action="{{ route('renja.templates.store') }}" method="POST" class="space-y-4 text-xs overflow-y-auto flex-1 pr-1">
                @csrf
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Nama Jenis Dokumen</label>
                    <input type="text" name="name" required placeholder="Contoh: Kebijakan Umum APBD (KUA-PPAS)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Kode Template (Singkatan/Kode Unik)</label>
                    <input type="text" name="code" required placeholder="Contoh: KUA_PPAS" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs uppercase font-mono focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Deskripsi Singkat Dokumen</label>
                    <textarea name="description" rows="2" placeholder="Jelaskan peruntukan dokumen ini..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="space-y-2 bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <label class="block font-bold text-gray-800 mb-1">Konfigurasi Komponen Dokumen:</label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="has_cover" value="1" checked class="rounded text-blue-600">
                        <span>Memiliki Halaman Cover Depan</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="has_front_sections" value="1" checked class="rounded text-blue-600">
                        <span>Memiliki Bagian Awal (Kata Pengantar, Daftar Isi/Tabel)</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="allow_bold" value="1" checked class="rounded text-blue-600">
                        <span>Mengizinkan Format Teks Tebal (Bold)</span>
                    </label>
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t">
                    <button type="button" onclick="document.getElementById('modal-add-custom-template').classList.add('hidden'); document.getElementById('modal-select-template').classList.remove('hidden');" class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg">Kembali</button>
                    <button type="submit" class="px-5 py-2 bg-blue-700 text-white text-xs font-bold rounded-lg shadow">Simpan Template Baru</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT COVER DOKUMEN (RKPD / EVALUASI RKPD) -->
    @if($formatConfig['has_cover'] ?? false)
    <div id="modal-edit-cover" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 space-y-4 max-h-[85vh] flex flex-col border border-slate-200">
            <div class="flex justify-between items-center border-b pb-3 shrink-0">
                <h3 class="font-bold text-slate-900 text-sm flex items-center space-x-2">
                    <i class="fa-solid fa-book-open text-purple-600"></i>
                    <span>Edit Data Cover {{ $activeTemplate?->code ?? 'MANUAL' }}</span>
                </h3>
                <button onclick="document.getElementById('modal-edit-cover').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>
            @php $cv = $document->cover_data ?? []; @endphp
            <form action="{{ route('renja.editor.updateCover', $document->id) }}" method="POST" class="space-y-3 text-xs overflow-y-auto flex-1 pr-1">
                @csrf
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Judul Utama Dokumen</label>
                    <input type="text" name="judul_dokumen" value="{{ $cv['judul_dokumen'] ?? strtoupper($activeTemplate?->name ?? 'DOKUMEN PERENCANAAN') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Tahun Anggaran</label>
                        <input type="number" name="tahun_anggaran" value="{{ $cv['tahun_anggaran'] ?? $document->tahun_anggaran }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Tahun Terbit</label>
                        <input type="text" name="tahun_terbit" value="{{ $cv['tahun_terbit'] ?? date('Y') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Nama Perangkat Daerah (OPD)</label>
                    <input type="text" name="nama_opd" value="{{ $cv['nama_opd'] ?? $document->opd->nama_opd }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Nama Pemerintah Daerah</label>
                        <input type="text" name="nama_pemda" value="{{ $cv['nama_pemda'] ?? 'PEMERINTAH KABUPATEN CIREBON' }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Lokasi Penerbitan</label>
                        <input type="text" name="lokasi" value="{{ $cv['lokasi'] ?? 'SUMBER' }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                </div>
                <div class="flex justify-end space-x-2 pt-2 border-t shrink-0">
                    <button type="button" onclick="document.getElementById('modal-edit-cover').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-purple-700 text-white text-xs font-bold rounded-lg shadow">Simpan Cover</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- MODAL EDIT JUDUL SUB-BAB -->
    @if($activeSection)
    <div id="modal-edit-subbab" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 space-y-4 max-h-[85vh] flex flex-col border border-slate-200">
            <div class="flex justify-between items-center border-b pb-3 shrink-0">
                <h3 class="font-extrabold text-slate-900 text-sm flex items-center space-x-2">
                    <i class="fa-solid fa-pen-to-square text-emerald-600"></i>
                    <span>Edit Sub-Bab {{ $activeSection->sub_bab_code }}</span>
                </h3>
                <button onclick="document.getElementById('modal-edit-subbab').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>
            <form action="{{ route('renja.editor.updateSubBabTitle', [$document->id, $activeSection->id]) }}" method="POST" class="space-y-4 text-xs overflow-y-auto flex-1 pr-1">
                @csrf
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nomor Sub-Bab</label>
                        <input type="text" name="sub_bab_code" value="{{ $activeSection->sub_bab_code }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs font-bold text-gray-800 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nama Sub-Bab (Manual)</label>
                        <input type="text" name="sub_bab_title" value="{{ preg_replace('/^\d+(\.\d+)*\s*/', '', $activeSection->sub_bab_title) }}" required placeholder="Contoh: Latar Belakang" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>
                <div class="flex justify-between items-center pt-2 shrink-0">
                    <button type="button" onclick="if(confirm('Hapus sub-bab ini?')){ document.getElementById('form-delete-subbab').submit(); }" class="px-3 py-2 bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-bold rounded-lg transition flex items-center space-x-1">
                        <i class="fa-solid fa-trash"></i>
                        <span>Hapus Sub-Bab</span>
                    </button>
                    <div class="flex space-x-2">
                        <button type="button" onclick="document.getElementById('modal-edit-subbab').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold rounded-lg shadow">Simpan</button>
                    </div>
                </div>
            </form>
            <form id="form-delete-subbab" action="{{ route('renja.editor.deleteSection', [$document->id, $activeSection->id]) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
    @endif

    <!-- MODAL ADD SUB-BAB BARU -->
    <div id="modal-add-subbab" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 space-y-4 max-h-[85vh] flex flex-col border border-slate-200">
            <div class="flex justify-between items-center border-b pb-3 shrink-0">
                <h3 class="font-bold text-slate-900 text-sm">Tambah Sub-Bab Baru pada {{ $activeBabCode }}</h3>
                <button onclick="document.getElementById('modal-add-subbab').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>
            <form action="{{ route('renja.editor.addSubBab', $document->id) }}" method="POST" class="space-y-4 text-xs overflow-y-auto flex-1 pr-1">
                @csrf
                <input type="hidden" name="bab_code" value="{{ $activeBabCode }}">
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nomor Sub-Bab</label>
                        <input type="text" name="sub_bab_code" placeholder="Kosongkan = Auto" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nama Sub-Bab (Manual)</label>
                        <input type="text" name="title" required placeholder="Contoh: Identifikasi Masalah" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>
                <div class="flex justify-end space-x-2 pt-2 shrink-0">
                    <button type="button" onclick="document.getElementById('modal-add-subbab').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-700 text-white text-xs font-bold rounded-lg shadow">Tambah Sub-Bab</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL TAMBAH BAB BARU MANUAL (+ BAB BARU) -->
    <div id="modal-add-bab" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 space-y-4 max-h-[85vh] flex flex-col border border-slate-200">
            <div class="flex justify-between items-center border-b pb-3 shrink-0">
                <h3 class="font-bold text-slate-900 text-sm flex items-center space-x-2">
                    <i class="fa-solid fa-folder-plus text-emerald-600"></i>
                    <span>Tambah BAB Baru secara Manual</span>
                </h3>
                <button onclick="document.getElementById('modal-add-bab').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>
            <form action="{{ route('renja.editor.addBab', $document->id) }}" method="POST" class="space-y-4 text-xs overflow-y-auto flex-1 pr-1">
                @csrf
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Kode BAB</label>
                        <input type="text" name="bab_code" required value="BAB {{ $groupedBabs->count() + 1 }}" placeholder="Contoh: BAB VII" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs font-bold uppercase focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block font-bold text-gray-700 mb-1">Judul BAB Baru</label>
                        <input type="text" name="bab_title" required placeholder="Contoh: Analisis Kebutuhan Daerah" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Judul Sub-BAB Pertama (Opsional)</label>
                    <input type="text" name="sub_bab_title" placeholder="Contoh: Latar Belakang Khusus" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500">
                    <span class="text-[10px] text-gray-500 mt-1 block">Otomatis membuat Sub-BAB pertama di bawah BAB baru ini.</span>
                </div>
                <div class="flex justify-end space-x-2 pt-2 border-t shrink-0">
                    <button type="button" onclick="document.getElementById('modal-add-bab').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow">Tambah BAB Baru</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT JUDUL BAB -->
    <div id="modal-edit-bab-title" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 space-y-4 max-h-[85vh] flex flex-col border border-slate-200">
            <div class="flex justify-between items-center border-b pb-3 shrink-0">
                <h3 class="font-bold text-slate-900 text-sm flex items-center space-x-2">
                    <i class="fa-solid fa-pen-to-square text-amber-600"></i>
                    <span>Edit Judul {{ $activeBabCode }}</span>
                </h3>
                <button onclick="document.getElementById('modal-edit-bab-title').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>
            @php 
                $curTitle = $activeSubBabs->first()?->bab_title ?? $activeBabCode;
            @endphp
            <form action="{{ route('renja.editor.updateBabTitle', $document->id) }}" method="POST" class="space-y-4 text-xs overflow-y-auto flex-1 pr-1">
                @csrf
                <input type="hidden" name="bab_code" value="{{ $activeBabCode }}">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Judul {{ $activeBabCode }}</label>
                    <input type="text" name="bab_title" value="{{ $curTitle }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="flex justify-end space-x-2 pt-2 border-t shrink-0">
                    <button type="button" onclick="document.getElementById('modal-edit-bab-title').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg shadow">Simpan Judul BAB</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL WORD TABLE TOOLS -->
    <div id="modal-word-table-tools" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 space-y-5 max-h-[85vh] flex flex-col border border-slate-200">
            <div class="flex justify-between items-center border-b pb-3 shrink-0">
                <h3 class="font-extrabold text-slate-900 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-table text-blue-600"></i>
                    <span>Word Table Tools — Insert & Layout</span>
                </h3>
                <button type="button" onclick="document.getElementById('modal-word-table-tools').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>
            
            <form onsubmit="submitInsertWordTable(event)" class="space-y-4 border-b border-slate-100 pb-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="table_rows_count" class="block text-xs font-bold text-gray-700 mb-1">Jumlah Baris (Rows)</label>
                        <input type="number" id="table_rows_count" value="3" min="1" max="50" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs font-bold text-gray-800 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="table_cols_count" class="block text-xs font-bold text-gray-700 mb-1">Jumlah Kolom (Cols)</label>
                        <input type="number" id="table_cols_count" value="4" min="1" max="25" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs font-bold text-gray-800 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold rounded-lg shadow-md transition flex items-center space-x-1.5">
                        <i class="fa-solid fa-table text-xs"></i>
                        <span>Sisipkan Tabel Baru ke Dokumen</span>
                    </button>
                </div>
            </form>
            <div class="flex justify-end pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-word-table-tools').classList.add('hidden')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-lg transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC & AUTOSAVE -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            width: '340px',
            customClass: { popup: 'rounded-xl shadow-lg text-xs font-semibold' }
        });
        function toastInfo(msg)  { Toast.fire({ icon: 'info',    title: msg }); }
        function toastWarn(msg)  { Toast.fire({ icon: 'warning', title: msg }); }
        function toastOk(msg)    { Toast.fire({ icon: 'success', title: msg }); }

        function preventBoldShortcut(e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
                e.preventDefault();
                toastWarn('Ketentuan Renja: Format teks tebal (Bold) tidak diperbolehkan.');
                return false;
            }
        }

        function formatDoc(cmd, value = null) {
            document.execCommand(cmd, false, value);
            const active = document.activeElement;
            if (active && active.contentEditable === 'true') {
                active.focus();
            }
        }

        function insertImagePrompt() {
            const url = prompt('Masukkan URL Gambar:');
            if (url) {
                formatDoc('insertImage', url);
            }
        }

        // =========================================================================
        // INTERACTIVE TABLE MANIPULATION SUITE (LAYOUT, POSISI, BARIS, KOLOM, BORDER)
        // =========================================================================
        let currentActiveCell = null;

        document.addEventListener('click', function(e) {
            const cell = e.target.closest('td, th');
            const bar = document.getElementById('table-context-bar');
            if (cell && cell.closest('[contenteditable="true"]')) {
                currentActiveCell = cell;
                if (bar) {
                    bar.classList.remove('hidden');
                }
            } else if (!e.target.closest('#table-context-bar') && !e.target.closest('#modal-word-table-tools')) {
                if (bar) {
                    bar.classList.add('hidden');
                }
            }
        });

        function getActiveCell() {
            if (currentActiveCell && document.body.contains(currentActiveCell)) {
                return currentActiveCell;
            }
            const sel = window.getSelection();
            if (sel.rangeCount > 0) {
                let node = sel.getRangeAt(0).startContainer;
                if (node.nodeType === 3) node = node.parentNode;
                const cell = node.closest('td, th');
                if (cell) return cell;
            }
            return null;
        }

        function getActiveTable() {
            const cell = getActiveCell();
            return cell ? cell.closest('table') : null;
        }

        function tableAddRow(position = 'below') {
            const cell = getActiveCell();
            if (!cell) {
                toastWarn('Klik pada sel tabel terlebih dahulu.');
                return;
            }
            const row = cell.closest('tr');
            const colCount = Array.from(row.children).reduce((acc, c) => acc + (parseInt(c.getAttribute('colspan')) || 1), 0);

            const newRow = document.createElement('tr');
            for (let i = 0; i < colCount; i++) {
                const newCell = document.createElement('td');
                newCell.style.border = '1px solid #000000';
                newCell.style.padding = '6px 8px';
                newCell.style.fontSize = '12pt';
                newCell.setAttribute('contenteditable', 'true');
                newCell.innerHTML = '&nbsp;';
                newRow.appendChild(newCell);
            }

            if (position === 'above') {
                row.parentNode.insertBefore(newRow, row);
            } else {
                row.parentNode.insertBefore(newRow, row.nextSibling);
            }
            toastOk('Baris baru berhasil ditambahkan.');
        }

        function tableDeleteRow() {
            const cell = getActiveCell();
            if (!cell) {
                toastWarn('Klik pada sel tabel terlebih dahulu.');
                return;
            }
            const row = cell.closest('tr');
            const table = row.closest('table');
            if (table.rows.length <= 1) {
                table.remove();
                const bar = document.getElementById('table-context-bar');
                if (bar) bar.classList.add('hidden');
                toastOk('Tabel berhasil dihapus.');
            } else {
                row.remove();
                toastOk('Baris berhasil dihapus.');
            }
        }

        function tableAddColumn(position = 'after') {
            const cell = getActiveCell();
            if (!cell) {
                toastWarn('Klik pada sel tabel terlebih dahulu.');
                return;
            }
            const row = cell.closest('tr');
            const colIndex = Array.from(row.children).indexOf(cell);
            const table = row.closest('table');

            Array.from(table.rows).forEach((r, idx) => {
                const isHeader = r.parentNode.tagName.toLowerCase() === 'thead' || idx === 0;
                const newCell = document.createElement(isHeader ? 'th' : 'td');
                newCell.style.border = '1px solid #000000';
                newCell.style.padding = '6px 8px';
                newCell.style.fontSize = '12pt';
                if (isHeader) {
                    newCell.style.textAlign = 'center';
                    newCell.style.backgroundColor = '#F3F4F6';
                    newCell.innerText = 'Header Baru';
                } else {
                    newCell.setAttribute('contenteditable', 'true');
                    newCell.innerHTML = '&nbsp;';
                }

                const targetCell = r.children[colIndex];
                if (targetCell) {
                    if (position === 'before') {
                        r.insertBefore(newCell, targetCell);
                    } else {
                        r.insertBefore(newCell, targetCell.nextSibling);
                    }
                } else {
                    r.appendChild(newCell);
                }
            });
            toastOk('Kolom baru berhasil ditambahkan.');
        }

        function tableDeleteColumn() {
            const cell = getActiveCell();
            if (!cell) {
                toastWarn('Klik pada sel tabel terlebih dahulu.');
                return;
            }
            const row = cell.closest('tr');
            const colIndex = Array.from(row.children).indexOf(cell);
            const table = row.closest('table');

            let totalCols = row.children.length;
            if (totalCols <= 1) {
                table.remove();
                const bar = document.getElementById('table-context-bar');
                if (bar) bar.classList.add('hidden');
                toastOk('Tabel berhasil dihapus.');
                return;
            }

            Array.from(table.rows).forEach(r => {
                if (r.children[colIndex]) {
                    r.children[colIndex].remove();
                }
            });
            toastOk('Kolom berhasil dihapus.');
        }

        function tableSetAlignment(align) {
            const table = getActiveTable();
            if (!table) {
                toastWarn('Klik pada tabel terlebih dahulu.');
                return;
            }
            if (align === 'left') {
                table.style.marginLeft = '0';
                table.style.marginRight = 'auto';
            } else if (align === 'center') {
                table.style.marginLeft = 'auto';
                table.style.marginRight = 'auto';
            } else if (align === 'right') {
                table.style.marginLeft = 'auto';
                table.style.marginRight = '0';
            }
            toastOk(`Posisi tabel diubah menjadi Rata ${align.toUpperCase()}`);
        }

        function tableSetCellBgColor(colorHex) {
            const cell = getActiveCell();
            if (!cell) {
                toastWarn('Klik pada sel tabel terlebih dahulu.');
                return;
            }
            cell.style.backgroundColor = colorHex;
            toastOk('Warna latar belakang sel diubah.');
        }

        function tableSetBorderPattern(pattern) {
            const table = getActiveTable();
            if (!table) {
                toastWarn('Klik pada tabel terlebih dahulu.');
                return;
            }
            if (pattern === 'none') {
                table.style.border = 'none';
                Array.from(table.querySelectorAll('td, th')).forEach(c => c.style.border = 'none');
            } else if (pattern === 'thick') {
                table.style.border = '2px solid #000000';
                Array.from(table.querySelectorAll('td, th')).forEach(c => c.style.border = '2px solid #000000');
            } else {
                table.style.border = '1px solid #000000';
                Array.from(table.querySelectorAll('td, th')).forEach(c => c.style.border = '1px solid #000000');
            }
            toastOk('Garis bingkai tabel diubah.');
        }

        function tableRemoveEntireTable() {
            const table = getActiveTable();
            if (!table) {
                toastWarn('Klik pada tabel terlebih dahulu.');
                return;
            }
            if (confirm('Apakah Anda yakin ingin menghapus seluruh tabel ini?')) {
                table.remove();
                const bar = document.getElementById('table-context-bar');
                if (bar) bar.classList.add('hidden');
                toastOk('Tabel berhasil dihapus.');
            }
        }

        // =========================================================================
        // EXTENDED MS WORD LAYOUT SUITE (MERGE, SPLIT, 9-GRID ALIGN, AUTOFIT, DISTRIBUTE)
        // =========================================================================
        function tableMergeCells() {
            const cell = getActiveCell();
            if (!cell) {
                toastWarn('Klik pada sel tabel terlebih dahulu.');
                return;
            }
            const nextCell = cell.nextElementSibling;
            if (!nextCell) {
                toastWarn('Tidak ada sel di sebelah kanan untuk digabungkan.');
                return;
            }
            let curColspan = parseInt(cell.getAttribute('colspan')) || 1;
            let nextColspan = parseInt(nextCell.getAttribute('colspan')) || 1;

            if (nextCell.innerText.trim() !== '') {
                cell.innerHTML = cell.innerHTML.trim() + ' ' + nextCell.innerHTML.trim();
            }
            cell.setAttribute('colspan', curColspan + nextColspan);
            nextCell.remove();
            toastOk('Sel berhasil digabungkan (Merge Cells).');
        }

        function tableSplitCells() {
            const cell = getActiveCell();
            if (!cell) {
                toastWarn('Klik pada sel tabel terlebih dahulu.');
                return;
            }
            let curColspan = parseInt(cell.getAttribute('colspan')) || 1;
            if (curColspan <= 1) {
                toastWarn('Sel ini belum digabungkan (colspan = 1).');
                return;
            }
            cell.removeAttribute('colspan');
            const row = cell.closest('tr');
            for (let i = 1; i < curColspan; i++) {
                const newCell = document.createElement(cell.tagName.toLowerCase());
                newCell.style.border = cell.style.border || '1px solid #000000';
                newCell.style.padding = cell.style.padding || '6px 8px';
                newCell.style.fontSize = '12pt';
                newCell.setAttribute('contenteditable', 'true');
                newCell.innerHTML = '&nbsp;';
                row.insertBefore(newCell, cell.nextSibling);
            }
            toastOk('Sel berhasil dipecah (Split Cells).');
        }

        function tableSetCellVAlign(vAlign, hAlign) {
            const cell = getActiveCell();
            if (!cell) {
                toastWarn('Klik pada sel tabel terlebih dahulu.');
                return;
            }
            cell.style.verticalAlign = vAlign;
            cell.style.textAlign = hAlign;
            toastOk(`Penjajaran sel diubah: Vertical ${vAlign.toUpperCase()}, Horizontal ${hAlign.toUpperCase()}`);
        }

        function tableSetCellPadding(paddingPx) {
            const table = getActiveTable();
            if (!table) {
                toastWarn('Klik pada tabel terlebih dahulu.');
                return;
            }
            Array.from(table.querySelectorAll('td, th')).forEach(c => {
                c.style.padding = paddingPx;
            });
            toastOk(`Padding sel tabel diubah menjadi ${paddingPx}.`);
        }

        function tableSetAutoFit(mode) {
            const table = getActiveTable();
            if (!table) {
                toastWarn('Klik pada tabel terlebih dahulu.');
                return;
            }
            if (mode === 'window') {
                table.style.width = '100%';
                table.style.tableLayout = 'auto';
            } else if (mode === 'content') {
                table.style.width = 'auto';
                table.style.tableLayout = 'auto';
            } else if (mode === 'fixed') {
                table.style.width = '100%';
                table.style.tableLayout = 'fixed';
            }
            toastOk(`AutoFit tabel diubah: ${mode.toUpperCase()}`);
        }

        function tableDistributeColumns() {
            const table = getActiveTable();
            if (!table) {
                toastWarn('Klik pada tabel terlebih dahulu.');
                return;
            }
            const firstRow = table.rows[0];
            if (!firstRow) return;
            const colCount = firstRow.children.length;
            const pct = (100 / colCount).toFixed(2) + '%';
            Array.from(table.rows).forEach(r => {
                Array.from(r.children).forEach(c => {
                    c.style.width = pct;
                });
            });
            toastOk('Lebar kolom berhasil diratakan sama besar (Distribute Columns).');
        }

        function tableSetRepeatHeader() {
            const table = getActiveTable();
            if (!table) {
                toastWarn('Klik pada tabel terlebih dahulu.');
                return;
            }
            let thead = table.querySelector('thead');
            if (!thead && table.rows.length > 0) {
                thead = document.createElement('thead');
                const firstRow = table.rows[0];
                thead.appendChild(firstRow);
                table.insertBefore(thead, table.firstChild);
                Array.from(thead.querySelectorAll('td')).forEach(td => {
                    const th = document.createElement('th');
                    th.innerHTML = td.innerHTML;
                    th.style.cssText = td.style.cssText;
                    th.style.backgroundColor = '#F3F4F6';
                    th.style.fontWeight = 'bold';
                    th.style.textAlign = 'center';
                    td.replaceWith(th);
                });
                toastOk('Baris pertama diset sebagai Repeat Header Row (thead).');
            } else {
                toastInfo('Header baris tabel (thead) sudah aktif.');
            }
        }

        function saveSectionAjax(sectionId) {
            const editable = document.getElementById(`section-content-${sectionId}`);
            if (!editable) return;

            const content = editable.innerHTML;
            const url = `/renja-documents/{{ $document->id }}/sections/${sectionId}`;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ content: content })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.progress) {
                    const pctDisp = document.getElementById('progress-percentage-display');
                    const txtDisp = document.getElementById('progress-text-display');
                    const bar = document.getElementById('progress-bar');
                    if (pctDisp) pctDisp.innerText = `${data.progress.percentage}%`;
                    if (txtDisp) txtDisp.innerText = `${data.progress.completed} / ${data.progress.total} Seksi Selesai`;
                    if (bar) bar.style.width = `${data.progress.percentage}%`;
                }
            })
            .catch(err => {
                console.error("Autosave Error:", err);
            });
        }

        function openEditSubBabModal(sectionId, code, title) {
            const form = document.getElementById('form-edit-subbab');
            const codeInput = document.getElementById('modal-edit-subbab')?.querySelector('input[name="sub_bab_code"]');
            const titleInput = document.getElementById('modal-edit-subbab')?.querySelector('input[name="sub_bab_title"]');
            const modal = document.getElementById('modal-edit-subbab');

            if (form && modal) {
                form.action = `/renja-documents/{{ $document->id }}/sections/${sectionId}/update-title`;
                if (codeInput) codeInput.value = code;
                if (titleInput) titleInput.value = title;
                modal.classList.remove('hidden');
            }
        }

        function refreshAutomaticLists() {
            toastOk('Daftar Isi, Tabel, Gambar, dan Lampiran berhasil diperbarui otomatis.');
        }

        function submitInsertWordTable(e) {
            e.preventDefault();
            const rows = parseInt(document.getElementById('table_rows_count').value) || 3;
            const cols = parseInt(document.getElementById('table_cols_count').value) || 4;

            let html = '<div class="overflow-x-auto my-4"><table class="w-full text-left border-collapse" style="font-family: \'Bookman Old Style\', Georgia, serif; font-size: 12pt; border: 1px solid #000000;">';
            html += '<thead><tr style="background-color: #F3F4F6; border-bottom: 1px solid #000000;">';
            for (let c = 1; c <= cols; c++) {
                html += '<th style="border: 1px solid #000000; padding: 6px 8px; text-align: center; font-size: 12pt;">Header ' + c + '</th>';
            }
            html += '</tr></thead><tbody>';

            for (let r = 1; r <= rows; r++) {
                html += '<tr>';
                for (let c = 1; c <= cols; c++) {
                    html += '<td style="border: 1px solid #000000; padding: 6px 8px; font-size: 12pt;" contenteditable="true">Data ' + r + '.' + c + '</td>';
                }
                html += '</tr>';
            }

            html += '</tbody></table></div><p></p>';

            formatDoc('insertHTML', html);
            document.getElementById('modal-word-table-tools').classList.add('hidden');
        }

        // Sprint 6.2: Mesin Cuci V2 & Auto-Sum Functions
        function triggerActiveSectionAutoFix() {
            const activeEditors = document.querySelectorAll('[contenteditable="true"]');
            if (activeEditors.length === 0) {
                alert('Tidak ada seksi editor aktif.');
                return;
            }

            if (!confirm('Jalankan Mesin Cuci V2? Format teks dan tabel akan dirapikan sesuai standar resmi Pemkab Cirebon.')) return;

            activeEditors.forEach(editor => {
                const sectionWrapper = editor.closest('[data-section-id]');
                if (!sectionWrapper) return;
                const sectionId = sectionWrapper.getAttribute('data-section-id');
                if (!sectionId) return;

                fetch(`/renja-documents/{{ $document->id }}/sections/${sectionId}/autofix`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.cleaned_content) {
                        editor.innerHTML = data.cleaned_content;
                    }
                })
                .catch(err => console.error("AutoFix Error:", err));
            });

            alert('Proses Mesin Cuci V2 Selesai! Format dokumen telah dirapikan.');
        }

        function triggerAutoSumCalculator() {
            const tables = document.querySelectorAll('.editor-canvas table, [contenteditable="true"] table');
            let updatedCount = 0;

            tables.forEach(table => {
                const rows = table.querySelectorAll('tbody tr');
                let colSum = {};

                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    cells.forEach((td, idx) => {
                        const rawText = td.innerText.replace(/[^0-9,-]/g, '').replace(',', '.');
                        const val = parseFloat(rawText) || 0;
                        if (val > 0) {
                            colSum[idx] = (colSum[idx] || 0) + val;
                        }
                    });
                });

                const lastRow = table.querySelector('tr:last-child') || table.querySelector('tfoot tr');
                if (lastRow) {
                    const cells = lastRow.querySelectorAll('td, th');
                    cells.forEach((td, idx) => {
                        if (colSum[idx] && colSum[idx] > 0) {
                            td.innerText = new Intl.NumberFormat('id-ID').format(colSum[idx]);
                            updatedCount++;
                        }
                    });
                }
            });

            if (updatedCount > 0) {
                alert(`Auto-Sum Selesai! Berhasil menghitung total pada ${updatedCount} kolom tabel.`);
            } else {
                alert('Auto-Sum selesai: Tidak ditemukan kolom angka nominal pada tabel.');
            }
        }

        // Fitur 8: Unsaved Changes Warning Logic
        let hasUnsavedChanges = false;

        document.addEventListener('input', function(e) {
            if (e.target.isContentEditable || e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                hasUnsavedChanges = true;
                const dot = document.getElementById('autosave-dot');
                const statusTxt = document.getElementById('autosave-status-text');
                if (dot) dot.className = 'w-2 h-2 rounded-full bg-amber-500 inline-block animate-ping';
                if (statusTxt) statusTxt.innerText = '● Menyimpan...';
            }
        });

        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = 'Perubahan belum disimpan. Apakah Anda yakin ingin keluar?';
                return 'Perubahan belum disimpan. Apakah Anda yakin ingin keluar?';
            }
        });

        // Intercept Autosave AJAX success to update status text
        const originalSaveSection = window.saveSectionAjax;
        window.saveSectionAjax = function(sectionId, content) {
            const dot = document.getElementById('autosave-dot');
            const statusTxt = document.getElementById('autosave-status-text');
            if (dot) dot.className = 'w-2 h-2 rounded-full bg-amber-500 inline-block animate-pulse';
            if (statusTxt) statusTxt.innerText = '● Menyimpan...';

            fetch(`/renja-documents/{{ $document->id }}/sections/${sectionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ content: content })
            })
            .then(res => res.json())
            .then(data => {
                hasUnsavedChanges = false;
                if (dot) dot.className = 'w-2 h-2 rounded-full bg-emerald-500 inline-block';
                if (statusTxt) statusTxt.innerText = '✓ Tersimpan baru saja';

                if (data.success && data.progress) {
                    const pctDisp = document.getElementById('progress-percentage-display');
                    const txtDisp = document.getElementById('progress-text-display');
                    const bar = document.getElementById('progress-bar');
                    if (pctDisp) pctDisp.innerText = `${data.progress.percentage}%`;
                    if (txtDisp) txtDisp.innerText = `${data.progress.completed} / ${data.progress.total} Seksi Selesai`;
                    if (bar) bar.style.width = `${data.progress.percentage}%`;
                }
            })
            .catch(err => {
                console.error("Autosave Error:", err);
                if (dot) dot.className = 'w-2 h-2 rounded-full bg-rose-500 inline-block';
                if (statusTxt) statusTxt.innerText = '⚠️ Gagal menyimpan';
            });
        };

        function updateZoomFromSlider(zoomValue) {
            const wrapper = document.getElementById('pages-zoom-wrapper');
            const display = document.getElementById('zoom-percent-display');
            const slider = document.getElementById('zoom-range-slider');

            const val = parseInt(zoomValue) || 100;
            const scaleVal = val / 100;

            if (wrapper) {
                wrapper.style.setProperty('--editor-zoom', scaleVal);
            }
            if (display) {
                display.innerText = `${val}%`;
            }
            if (slider) {
                slider.value = val;
            }
        }

        function stepZoom(delta) {
            const slider = document.getElementById('zoom-range-slider');
            if (slider) {
                let cur = parseInt(slider.value) || 100;
                let next = Math.min(150, Math.max(50, cur + delta));
                updateZoomFromSlider(next);
            }
        }

        function resetZoom100() {
            updateZoomFromSlider(100);
        }
    </script>

    <!-- ============================================================ -->
    <!-- SECTION 6: STICKY BOTTOM TOOLBAR (SPRINT 6.0 FITUR 5)        -->
    <!-- ============================================================ -->
    <div class="fixed bottom-8 left-0 right-0 h-12 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 flex items-center justify-between px-6 z-40 text-white shadow-2xl">
        <div class="flex items-center space-x-3 text-xs font-bold">
            <span class="text-slate-400">Pondasi Smart Editor:</span>
            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-md text-[10px] uppercase font-black">
                {{ $activeTemplate?->code ?? 'MANUAL' }}
            </span>
            <span class="hidden sm:inline text-slate-300">Format Resmi Pemkab Cirebon (Folio F4)</span>
        </div>

        <div class="flex items-center space-x-2">
            <!-- SIMPAN DRAFT (FITUR 5) -->
            @if(!$isReadOnly)
                <button type="button" 
                        onclick="hasUnsavedChanges = false; alert('Draft berhasil disimpan!')" 
                        class="st-btn st-btn-primary st-btn-sm h-8 rounded-xl font-extrabold text-xs px-3.5 shadow-md">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    <span>Simpan Draft</span>
                </button>

                <!-- AKSI ROLE-BASED (FINALISASI BAPPERIDA VS SUBMIT OPD) -->
                @if(Auth::user()->isAdmin() || Auth::user()->isVerifikator() || Auth::user()->isStaff())
                    <form action="{{ route('renja.finalize', $document->id) }}" method="POST" class="inline"
                          onsubmit="return confirm('Apakah Anda yakin ingin memfinalisasi dan mengunci dokumen ini?')">
                        @csrf
                        <button type="submit" 
                                class="st-btn st-btn-success st-btn-sm h-8 rounded-xl font-black text-xs px-3.5 shadow-md">
                            <i class="fa-solid fa-lock text-xs"></i>
                            <span>Finalisasi Dokumen</span>
                        </button>
                    </form>
                @else
                    <form action="{{ route('renja.submit', $document->id) }}" method="POST" class="inline"
                          onsubmit="return confirm('Kirim dokumen ini ke Admin Bapperida untuk diverifikasi?')">
                        @csrf
                        <button type="submit" 
                                class="st-btn st-btn-amber st-btn-sm h-8 rounded-xl font-black text-xs px-3.5 shadow-md">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            <span>Submit Verifikasi</span>
                        </button>
                    </form>
                @endif
            @else
                <span class="px-3 py-1 bg-slate-800 text-slate-400 rounded-xl text-xs font-bold border border-slate-700">
                    <i class="fa-solid fa-lock text-[10px] mr-1"></i> Read-Only Mode
                </span>
            @endif

            <!-- PREVIEW & EXPORT (DISABLED / COMING SOON - FITUR 5) -->
            <button type="button" disabled class="st-btn st-btn-secondary st-btn-sm h-8 rounded-xl opacity-40 cursor-not-allowed text-xs px-3" title="Fitur Preview PDF akan hadir pada Sprint berikutnya">
                <i class="fa-solid fa-eye text-xs"></i>
                <span class="hidden md:inline">Preview PDF</span>
            </button>

            <button type="button" disabled class="st-btn st-btn-secondary st-btn-sm h-8 rounded-xl opacity-40 cursor-not-allowed text-xs px-3" title="Fitur Export Word akan hadir pada Sprint berikutnya">
                <i class="fa-solid fa-file-word text-xs"></i>
                <span class="hidden md:inline">Export Word</span>
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SECTION 7: SIMPLIFIED STATUS BAR FOOTER                      -->
    <!-- ============================================================ -->
    <footer class="fixed bottom-0 left-0 right-0 h-8 bg-white border-t border-gray-200 flex items-center justify-between px-6 text-xs text-gray-600 select-none z-50 shadow-md">
        
        <!-- LEFT: INFORMASI STATUS DOKUMEN TEMPLATE -->
        <div class="flex items-center space-x-3 text-xs">
            <span class="font-bold text-gray-800">{{ $formatConfig['font_family'] ?? 'Bookman Old Style' }} • {{ $formatConfig['font_size_pt'] ?? 12 }} pt</span>
            <span class="text-gray-300">•</span>
            <span class="font-semibold text-gray-700">F4 (215×330mm)</span>
            <span class="text-gray-300">•</span>
            <span class="font-semibold text-gray-700">Margin 2 cm</span>
            <span class="text-gray-300">•</span>
            <span class="font-semibold text-emerald-700 flex items-center gap-1">
                <i class="fa-solid fa-check-circle text-[10px]"></i>
                <span>Autosaved</span>
            </span>
        </div>

        <!-- RIGHT: ZOOM CONTROLS & DISPLAY -->
        <div class="flex items-center space-x-3">
            <button onclick="stepZoom(-10)" class="w-5 h-5 flex items-center justify-center hover:bg-gray-100 rounded text-gray-600 font-bold text-xs" title="Zoom Out (-10%)">
                <i class="fa-solid fa-minus text-[10px]"></i>
            </button>
            <input type="range" 
                   id="zoom-range-slider" 
                   min="50" 
                   max="150" 
                   step="5" 
                   value="100" 
                   oninput="updateZoomFromSlider(this.value)"
                   class="w-24 h-1 bg-gray-300 rounded-lg appearance-none cursor-pointer accent-blue-700"
                   title="Geser zoom canvas F4">
            <button onclick="stepZoom(10)" class="w-5 h-5 flex items-center justify-center hover:bg-gray-100 rounded text-gray-600 font-bold text-xs" title="Zoom In (+10%)">
                <i class="fa-solid fa-plus text-[10px]"></i>
            </button>
            <span id="zoom-percent-display" 
                  onclick="resetZoom100()" 
                  class="hover:bg-gray-100 px-1.5 py-0.5 rounded cursor-pointer font-bold text-xs text-gray-800 min-w-[36px] text-center"
                  title="Klik untuk reset ke 100%">
                100%
            </span>
        </div>

    </footer>

    <!-- MODAL UBAH NAMA DOKUMEN -->
    <div id="modal-edit-document-name" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-slate-200">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-amber-500"></i>
                    <span>Ubah Nama Dokumen</span>
                </h3>
                <button type="button" onclick="document.getElementById('modal-edit-document-name').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <form action="{{ route('renja.updateName', $document->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Dokumen Resmi</label>
                    <input type="text" 
                           name="nama_dokumen" 
                           value="{{ $document->jenis_dokumen }}" 
                           required 
                           placeholder="Masukkan Nama Dokumen..." 
                           class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-900 focus:ring-2 focus:ring-amber-500">
                    <p class="text-[10px] text-slate-500 mt-1">Nama dokumen ini akan digunakan secara presisi saat mengekspor file Word / PDF.</p>
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modal-edit-document-name').classList.add('hidden')" class="st-btn st-btn-secondary st-btn-sm font-bold">Batal</button>
                    <button type="submit" class="st-btn st-btn-amber st-btn-md font-black shadow-md px-5 rounded-xl">
                        <i class="fa-solid fa-check text-xs mr-1"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
