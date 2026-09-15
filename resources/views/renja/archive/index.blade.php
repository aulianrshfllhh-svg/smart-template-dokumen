@extends('layouts.app')

@section('title', 'Arsip RENJA - File Explorer')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{
    viewMode: '{{ $viewMode ?? 'list' }}',
    searchLocal: '{{ $searchQuery ?? '' }}',
    viewerModalOpen: {{ $activeSection ? 'true' : 'false' }},
    currentSectionTitle: '{{ $activeSection ? addslashes($activeSection->title) : '' }}',
    currentSectionCode: '{{ $activeSection ? addslashes($activeSection->sub_bab_code ?? $activeSection->bab_code) : '' }}',
    currentSectionContent: `{!! $activeSection ? addslashes($activeSection->content) : '' !!}`,
    modalRestoreOpen: false,
    restoreDocId: null,
    restoreDocTitle: ''
}">

    <!-- FLASH NOTIFICATION -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-2xl text-xs font-bold flex items-center space-x-2.5 shadow-2xs">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-2xl text-xs font-bold flex items-center space-x-2.5 shadow-2xs">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- 1. HEADER BANNER & REPOSITORY STATUS       -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-l-4 border-l-cyan-500 bg-gradient-to-r from-white via-white to-cyan-50/20">
        <div>
            <!-- Breadcrumb Navigation Interaktif -->
            <nav class="flex items-center space-x-2 text-[11px] font-black uppercase text-slate-400 mb-2 flex-wrap">
                <a href="{{ route('renja.workspace') }}" class="hover:text-amber-600 transition">Dokumen Saya</a>
                <span>/</span>
                <a href="{{ route('renja.workspace') }}" class="hover:text-amber-600 transition">RENJA</a>
                <span>/</span>
                @if(!$selectedYear && !$selectedDocument)
                    <span class="text-cyan-700">Arsip</span>
                @else
                    <a href="{{ route('renja.archive.index') }}" class="hover:text-cyan-600 text-slate-600 transition">
                        Arsip
                    </a>
                    <span>/</span>
                    @if($selectedYear && !$selectedDocument)
                        <span class="text-cyan-700">TA {{ $selectedYear }}</span>
                    @elseif($selectedDocument)
                        <a href="{{ route('renja.archive.year', $selectedDocument->tahun_anggaran) }}" class="hover:text-cyan-600 text-slate-600 transition">
                            TA {{ $selectedDocument->tahun_anggaran }}
                        </a>
                        <span>/</span>
                        @if(!$activeBab)
                            <span class="text-cyan-700">{{ $selectedDocument->jenis_dokumen }}</span>
                        @else
                            <a href="{{ route('renja.archive.document', $selectedDocument->id) }}" class="hover:text-cyan-600 text-slate-600 transition">
                                {{ $selectedDocument->jenis_dokumen }}
                            </a>
                            <span>/</span>
                            <span class="text-cyan-700">{{ $activeBab }}</span>
                        @endif
                    @endif
                @endif
            </nav>

            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-box-archive text-cyan-600"></i>
                    <span>Arsip RENJA</span>
                    @if($selectedYear && !$selectedDocument)
                        <span class="text-slate-400 font-normal text-base sm:text-lg">/ Folder TA {{ $selectedYear }}</span>
                    @elseif($selectedDocument)
                        <span class="text-slate-400 font-normal text-base sm:text-lg">/ {{ $selectedDocument->jenis_dokumen }} TA {{ $selectedDocument->tahun_anggaran }}</span>
                    @endif
                </h1>

                <!-- Badge Status Resmi Repository Arsip -->
                <span class="px-3 py-1 rounded-full text-[10px] font-black tracking-wider uppercase bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1.5 shadow-2xs">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>REPOSITORY FINAL</span>
                </span>
            </div>

            <p class="text-xs text-slate-500 font-medium mt-1">
                Dokumen RENJA yang telah disetujui dan ditetapkan sebagai dokumen final oleh Bapperida.
            </p>
        </div>

        <div class="flex items-center space-x-3 shrink-0">
            <a href="{{ route('renja.workspace') }}" class="st-btn st-btn-outline st-btn-sm font-bold text-xs">
                <i class="fa-solid fa-folder-tree text-amber-500"></i>
                <span>Workspace TA Aktif</span>
            </a>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. BANNER INFORMASI PENGESAHAN DOKUMEN     -->
    <!-- ========================================== -->
    @if($selectedDocument)
        @php
            $auditTrail = $selectedDocument->metadata['audit_trail'] ?? [];
            $approvalLog = collect($auditTrail)->reverse()->first(fn($log) => in_array($log['action'] ?? '', ['DOCUMENT_APPROVED', 'FINALIZED_BY_ADMIN_BAPPERIDA']));
            $approvalDate = $approvalLog['timestamp'] ?? $selectedDocument->updated_at?->toIso8601String();
            $approvedBy = $approvalLog['user_name'] ?? ($selectedDocument->assignedVerificator->nama_lengkap ?? 'Admin Bapperida');
            $isExplicitArchived = $selectedDocument->is_archived;
        @endphp
        <div class="st-card-v2 p-4 sm:p-5 bg-gradient-to-r from-slate-900 via-slate-900 to-cyan-950 text-white rounded-3xl border border-cyan-500/30 shadow-lg flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-3.5">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-400/30 flex items-center justify-center text-xl font-black shrink-0 shadow-inner">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500 text-slate-950 shadow-xs">
                            ✓ DISETUJUI BAPPERIDA
                        </span>
                        <span class="text-xs text-cyan-300 font-bold flex items-center gap-1">
                            <i class="fa-solid fa-certificate text-emerald-400 text-[10px]"></i>
                            Status: Final / Approved
                        </span>
                    </div>
                    <div class="text-xs text-slate-300 font-medium mt-1">
                        Dokumen RENJA TA {{ $selectedDocument->tahun_anggaran }} telah selesai diverifikasi dan ditetapkan sebagai dokumen final oleh Bapperida (Read-Only).
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-[11px] text-slate-300 font-medium mt-3 pt-2.5 border-t border-slate-800/80">
                        <div>
                            <span class="text-slate-400 text-[10px] uppercase font-bold block">Status</span>
                            <span class="font-black text-emerald-400 flex items-center gap-1">
                                <i class="fa-solid fa-circle-check text-[10px]"></i>
                                Disetujui Bapperida
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] uppercase font-bold block">Tanggal Disetujui</span>
                            <span class="font-bold text-white">{{ $approvalDate ? \Carbon\Carbon::parse($approvalDate)->translatedFormat('d F Y') : \Carbon\Carbon::parse($selectedDocument->updated_at)->translatedFormat('d F Y') }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] uppercase font-bold block">Verifikator</span>
                            <span class="font-bold text-white">{{ $approvedBy }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] uppercase font-bold block">OPD & TA</span>
                            <span class="font-bold text-white">{{ $selectedDocument->opd->nama_opd ?? '-' }} (TA {{ $selectedDocument->tahun_anggaran }})</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center flex-wrap gap-2 shrink-0 self-start md:self-center">
                <a href="{{ route('renja.exportWord', $selectedDocument->id) }}" 
                   class="st-btn bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl shadow-sm transition-all flex items-center gap-1.5" title="Download Word (.docx)">
                    <i class="fa-solid fa-file-word"></i>
                    <span>Unduh Word</span>
                </a>
                <a href="{{ route('renja.print', $selectedDocument->id) }}" target="_blank" 
                   class="st-btn bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl shadow-sm transition-all flex items-center gap-1.5" title="Cetak F4 PDF">
                    <i class="fa-solid fa-print"></i>
                    <span>Cetak F4</span>
                </a>
                @if(auth()->user() && (auth()->user()->isAdmin() || auth()->user()->isVerifikator()))
                    @if($selectedDocument->is_archived)
                        <form method="POST" action="{{ route('renja.archive.restore', $selectedDocument->id) }}" onsubmit="return confirm('Kembalikan dokumen ini dari arsip ke status aktif?');" class="inline">
                            @csrf
                            <button type="submit" class="st-btn bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-700 text-xs font-bold px-3 py-2 rounded-xl" title="Restore ke Dokumen Aktif">
                                <i class="fa-solid fa-rotate-left"></i>
                                <span>Restore</span>
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- 3. TOOLBAR (SEARCH, FILTER, SORT, VIEW)    -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-4 sm:p-5 space-y-4">
        <form method="GET" action="{{ route('renja.archive.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            @if($selectedDocument)
                <input type="hidden" name="document_id" value="{{ $selectedDocument->id }}">
            @endif

            <!-- 1. Search Bar -->
            <div class="sm:col-span-4 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ $searchQuery ?? '' }}" 
                       placeholder="🔍 Cari dokumen arsip, BAB, subbab..." 
                       class="st-input text-xs pl-9 h-10 rounded-xl w-full">
            </div>

            <!-- 2. Filter Tahun Anggaran Arsip -->
            <div class="sm:col-span-2">
                <select name="tahun_anggaran" class="st-select text-xs h-10 rounded-xl font-bold w-full" onchange="this.form.submit()">
                    <option value="all">Semua Tahun Arsip</option>
                    @foreach($availableArchiveYears as $y)
                        <option value="{{ $y }}" {{ ($selectedYear == $y) ? 'selected' : '' }}>TA {{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Filter Jenis Dokumen -->
            <div class="sm:col-span-2">
                <select name="jenis_dokumen" class="st-select text-xs h-10 rounded-xl font-bold w-full" onchange="this.form.submit()">
                    <option value="all">Semua Jenis</option>
                    <option value="Murni" {{ $jenisDokumenFilter === 'Murni' ? 'selected' : '' }}>RENJA Murni</option>
                    <option value="Perubahan" {{ $jenisDokumenFilter === 'Perubahan' ? 'selected' : '' }}>RENJA Perubahan</option>
                    <option value="Lampiran" {{ $jenisDokumenFilter === 'Lampiran' ? 'selected' : '' }}>Lampiran Perbub</option>
                </select>
            </div>

            <!-- 4. Sort Filter -->
            <div class="sm:col-span-2">
                <select name="sort" class="st-select text-xs h-10 rounded-xl font-bold w-full" onchange="this.form.submit()">
                    <option value="year_desc" {{ $sortBy === 'year_desc' ? 'selected' : '' }}>Tahun Terbaru</option>
                    <option value="year_asc" {{ $sortBy === 'year_asc' ? 'selected' : '' }}>Tahun Terlama</option>
                    <option value="name_asc" {{ $sortBy === 'name_asc' ? 'selected' : '' }}>Nama A - Z</option>
                    <option value="name_desc" {{ $sortBy === 'name_desc' ? 'selected' : '' }}>Nama Z - A</option>
                    <option value="latest" {{ $sortBy === 'latest' ? 'selected' : '' }}>Terakhir Update</option>
                </select>
            </div>

            <!-- 5. Tombol Filter & View Switcher -->
            <div class="sm:col-span-2 flex items-center space-x-2">
                <button type="submit" class="st-btn st-btn-primary st-btn-sm h-10 rounded-xl text-xs font-black flex-1 justify-center shadow-xs">
                    <i class="fa-solid fa-filter text-xs"></i>
                    <span>Filter</span>
                </button>
                <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-xl shrink-0">
                    <button type="button" 
                            @click="viewMode = 'list'" 
                            :class="viewMode === 'list' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-800'"
                            class="p-2 rounded-lg text-xs flex items-center justify-center transition" title="List View">
                        <i class="fa-solid fa-list text-xs"></i>
                    </button>
                    <button type="button" 
                            @click="viewMode = 'grid'" 
                            :class="viewMode === 'grid' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-800'"
                            class="p-2 rounded-lg text-xs flex items-center justify-center transition" title="Grid View">
                        <i class="fa-solid fa-grip text-xs"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================== -->
    <!-- 4. FILE EXPLORER MAIN CONTAINER            -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 space-y-4">

        <!-- Navigation Bar / Back Buttons -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-wrap gap-2">
            <div class="flex items-center space-x-2">
                @if($activeBab && $selectedDocument)
                    <a href="{{ route('renja.archive.document', $selectedDocument->id) }}" class="st-btn st-btn-secondary st-btn-sm font-bold text-xs">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Kembali ke {{ $selectedDocument->jenis_dokumen }}</span>
                    </a>
                    <span class="text-slate-300">|</span>
                    <span class="text-xs font-black text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-folder-open text-amber-500"></i>
                        <span>Folder: {{ $activeBab }}</span>
                    </span>
                @elseif($selectedDocument)
                    <a href="{{ route('renja.archive.year', $selectedDocument->tahun_anggaran) }}" class="st-btn st-btn-secondary st-btn-sm font-bold text-xs">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Kembali ke TA {{ $selectedDocument->tahun_anggaran }}</span>
                    </a>
                    <span class="text-slate-300">|</span>
                    <span class="text-xs font-black text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-folder-tree text-cyan-600"></i>
                        <span>{{ $selectedDocument->jenis_dokumen }} TA {{ $selectedDocument->tahun_anggaran }}</span>
                    </span>
                @elseif($selectedYear)
                    <a href="{{ route('renja.archive.index') }}" class="st-btn st-btn-secondary st-btn-sm font-bold text-xs">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Kembali ke Root Arsip</span>
                    </a>
                    <span class="text-slate-300">|</span>
                    <span class="text-xs font-black text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-folder-open text-cyan-600"></i>
                        <span>Folder Tahun Anggaran {{ $selectedYear }}</span>
                    </span>
                @else
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-box-archive text-cyan-600 text-sm"></i>
                        <span class="text-xs font-black text-slate-800">Root Repository Arsip RENJA</span>
                    </div>
                @endif
            </div>

            <div class="text-[11px] font-bold text-slate-500">
                @if(!$selectedYear && !$selectedDocument)
                    <span>Total <strong>{{ $archivedDocuments->count() }}</strong> Dokumen Final ({{ $documentsByYear->count() }} Tahun Anggaran)</span>
                @elseif($selectedYear && !$selectedDocument)
                    @php
                        $docsInYear = $archivedDocuments->where('tahun_anggaran', $selectedYear)->count();
                    @endphp
                    <span><strong>{{ $docsInYear }}</strong> Dokumen Final di TA {{ $selectedYear }}</span>
                @elseif($selectedDocument && $activeBab)
                    @php
                        $babCount = $selectedDocument->sections->where('bab_code', $activeBab)->count();
                    @endphp
                    <span><strong>{{ $babCount }}</strong> Sub-bab Narasi</span>
                @elseif($selectedDocument)
                    @php
                        $groupedBabs = $selectedDocument->sections->groupBy('bab_code')->count();
                    @endphp
                    <span><strong>{{ $groupedBabs }}</strong> BAB &middot; <strong>{{ $selectedDocument->sections->count() }}</strong> Bagian</span>
                @endif
            </div>
        </div>

        <!-- ========================================== -->
        <!-- LEVEL 1: ROOT VIEW (DAFTAR FOLDER TA ARSIP) -->
        <!-- ========================================== -->
        @if(!$selectedYear && !$selectedDocument)
            @if($documentsByYear->isEmpty())
                <!-- Empty State -->
                <div class="p-12 text-center bg-white rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
                    <div class="w-16 h-16 bg-cyan-50 text-cyan-600 rounded-3xl flex items-center justify-center text-3xl mx-auto border border-cyan-200 shadow-2xs">
                        <i class="fa-solid fa-box-archive"></i>
                    </div>
                    <div class="space-y-1.5 max-w-md mx-auto">
                        <h3 class="text-base font-black text-slate-900">Belum Ada Dokumen Arsip</h3>
                        <p class="text-xs text-slate-500 font-medium leading-relaxed">
                            Dokumen yang telah disetujui Bapperida akan otomatis muncul di repository ini.<br>
                            Dokumen yang masih dalam proses verifikasi tetap berada di Draft & Proses.
                        </p>
                    </div>
                    <div class="pt-2 flex justify-center">
                        <a href="{{ route('renja.workspace') }}" class="st-btn st-btn-amber st-btn-sm font-bold text-xs">
                            <i class="fa-solid fa-pen-ruler"></i>
                            <span>Buka Draft & Proses</span>
                        </a>
                    </div>
                </div>
            @else
                <!-- LIST VIEW (DEFAULT) -->
                <div x-show="viewMode === 'list'" class="st-table-wrapper rounded-2xl border border-slate-200">
                    <table class="w-full text-xs text-left text-slate-700 border-collapse">
                        <thead class="bg-slate-900 text-white uppercase text-[10px] font-black tracking-wider border-b border-slate-800">
                            <tr>
                                <th class="p-3.5">Folder Tahun Anggaran</th>
                                <th class="p-3.5 text-center">Jumlah Dokumen</th>
                                <th class="p-3.5">Jenis Dokumen Tersedia</th>
                                <th class="p-3.5 text-center">Status Lifecycle</th>
                                <th class="p-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($documentsByYear as $year => $docs)
                                <tr class="hover:bg-cyan-50/40 transition-colors">
                                    <td class="p-3.5 font-bold text-slate-900">
                                        <a href="{{ route('renja.archive.year', $year) }}" class="flex items-center space-x-3 group">
                                            <div class="w-10 h-10 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center font-black text-base border border-cyan-200 shrink-0 group-hover:bg-cyan-600 group-hover:text-white transition">
                                                <i class="fa-solid fa-folder"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-black text-slate-900 group-hover:text-cyan-700 transition">
                                                    📁 Folder Arsip TA {{ $year }}
                                                </div>
                                                <div class="text-[10px] text-slate-400 font-normal">
                                                    Dokumen perencanaan tahun anggaran {{ $year }}
                                                </div>
                                            </div>
                                        </a>
                                    </td>

                                    <td class="p-3.5 text-center font-black text-slate-800 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 border border-slate-200 text-slate-800 text-[11px]">
                                            {{ $docs->count() }} Dokumen
                                        </span>
                                    </td>

                                    <td class="p-3.5 text-slate-600 font-medium">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            @foreach($docs->pluck('jenis_dokumen')->unique() as $j)
                                                <span class="px-2 py-0.2 rounded-md bg-slate-100 text-slate-700 text-[10px] font-semibold border border-slate-200">
                                                    {{ $j }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>

                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-3 py-1 rounded-full font-black text-[10px] uppercase shadow-2xs">
                                            ✓ DISETUJUI BAPPERIDA
                                        </span>
                                    </td>

                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <a href="{{ route('renja.archive.year', $year) }}" class="st-btn bg-cyan-600 hover:bg-cyan-700 text-white font-black text-xs st-btn-sm rounded-xl shadow-xs">
                                            <i class="fa-solid fa-folder-open text-xs"></i>
                                            <span>Buka Folder TA</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- GRID VIEW ROOT -->
                <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($documentsByYear as $year => $docs)
                        <div class="st-card-v2 p-5 border-t-4 border-t-cyan-500 hover:shadow-md transition flex flex-col justify-between group">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-12 h-12 rounded-2xl bg-cyan-50 text-cyan-600 border border-cyan-200 flex items-center justify-center text-xl font-black group-hover:bg-cyan-600 group-hover:text-white transition">
                                        <i class="fa-solid fa-folder"></i>
                                    </div>
                                    <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase">
                                        ✓ FINAL
                                    </span>
                                </div>
                                <h3 class="text-sm font-black text-slate-900 leading-snug group-hover:text-cyan-700 transition">
                                    📁 Arsip TA {{ $year }}
                                </h3>
                                <p class="text-[11px] text-slate-400 font-medium mt-1">
                                    Berisi {{ $docs->count() }} dokumen perencanaan historis
                                </p>
                            </div>
                            <a href="{{ route('renja.archive.year', $year) }}" class="mt-4 st-btn bg-cyan-600 hover:bg-cyan-700 text-white font-black text-xs py-2 rounded-xl text-center shadow-xs">
                                <i class="fa-solid fa-folder-open"></i>
                                <span>Buka Folder TA {{ $year }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

        <!-- ========================================== -->
        <!-- LEVEL 2: DAFTAR DOKUMEN DI DALAM FOLDER TA -->
        <!-- ========================================== -->
        @elseif($selectedYear && !$selectedDocument)
            @php
                $docsInSelectedYear = $archivedDocuments->where('tahun_anggaran', $selectedYear);
            @endphp

            @if($docsInSelectedYear->isEmpty())
                <div class="p-10 text-center text-slate-400 space-y-2">
                    <i class="fa-solid fa-folder-open text-3xl text-slate-300"></i>
                    <p class="text-xs">Tidak ada dokumen arsip untuk Tahun Anggaran {{ $selectedYear }}.</p>
                </div>
            @else
                <!-- LIST VIEW DOKUMEN ARSIP -->
                <div x-show="viewMode === 'list'" class="st-table-wrapper rounded-2xl border border-slate-200">
                    <table class="w-full text-xs text-left text-slate-700 border-collapse">
                        <thead class="bg-slate-900 text-white uppercase text-[10px] font-black tracking-wider border-b border-slate-800">
                            <tr>
                                <th class="p-3.5">Nama Dokumen</th>
                                <th class="p-3.5">Jenis</th>
                                <th class="p-3.5 text-center">TA</th>
                                <th class="p-3.5 text-center">Status Approval</th>
                                <th class="p-3.5">Status Lifecycle</th>
                                <th class="p-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($docsInSelectedYear as $doc)
                                @php
                                    $jenisDokRaw = strtolower($doc->jenis_dokumen ?? '');
                                    $isLampiranPerbub = str_contains($jenisDokRaw, 'lampiran') || str_contains($jenisDokRaw, 'perbub');
                                    $isPerubahan = !$isLampiranPerbub && str_contains($jenisDokRaw, 'perubahan');

                                    if ($isLampiranPerbub) {
                                        $badgeClass = 'bg-violet-100 text-violet-800 border border-violet-300';
                                        $badgeLabel = '📜 Lampiran Perbub';
                                        $folderLabel = 'Lampiran Perbub TA ' . $doc->tahun_anggaran;
                                    } elseif ($isPerubahan) {
                                        $badgeClass = 'bg-amber-100 text-amber-800 border border-amber-300';
                                        $badgeLabel = '🔄 RENJA Perubahan';
                                        $folderLabel = 'RENJA Perubahan TA ' . $doc->tahun_anggaran;
                                    } else {
                                        $badgeClass = 'bg-sky-100 text-sky-800 border border-sky-300';
                                        $badgeLabel = '📄 RENJA Murni';
                                        $folderLabel = 'RENJA Murni TA ' . $doc->tahun_anggaran;
                                    }
                                @endphp
                                <tr class="hover:bg-cyan-50/40 transition">
                                    <td class="p-3.5 font-bold text-slate-900">
                                        <a href="{{ route('renja.archive.document', $doc->id) }}" class="flex items-center space-x-3 group">
                                            <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center font-black text-sm border border-cyan-200 shrink-0 group-hover:bg-cyan-600 group-hover:text-white transition">
                                                <i class="fa-solid fa-folder"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-black text-slate-900 group-hover:text-cyan-700 transition">
                                                    📁 {{ $folderLabel }}
                                                </div>
                                                <div class="text-[10px] text-slate-400 font-normal">
                                                    {{ $doc->opd->nama_opd ?? 'OPD' }}
                                                </div>
                                            </div>
                                        </a>
                                    </td>

                                    <td class="p-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black {{ $badgeClass }}">
                                            {{ $badgeLabel }}
                                        </span>
                                    </td>

                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 text-slate-900 font-black text-[11px]">
                                            TA {{ $doc->tahun_anggaran }}
                                        </span>
                                    </td>

                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase {{ $doc->status_badge_class }}">
                                            {{ $doc->status_label }}
                                        </span>
                                    </td>

                                    <td class="p-3.5 whitespace-nowrap">
                                         <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-0.5 rounded-full font-black text-[10px] uppercase">
                                             ✓ DOKUMEN FINAL
                                         </span>
                                     </td>

                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center space-x-1.5">
                                            <a href="{{ route('renja.archive.document', $doc->id) }}" 
                                               class="st-btn bg-cyan-600 hover:bg-cyan-700 text-white font-black text-xs st-btn-sm rounded-xl shadow-xs" 
                                               title="Buka Folder Dokumen">
                                                <i class="fa-solid fa-folder-open text-xs"></i>
                                                <span>Buka Folder</span>
                                            </a>
                                            <a href="{{ route('renja.exportWord', $doc->id) }}" 
                                               class="st-btn st-btn-outline st-btn-sm text-blue-700 border-blue-200 hover:bg-blue-50 font-bold text-xs" 
                                               title="Unduh Word (.docx)">
                                                <i class="fa-solid fa-file-word"></i>
                                            </a>
                                            <a href="{{ route('renja.print', $doc->id) }}" target="_blank" 
                                               class="st-btn st-btn-outline st-btn-sm text-slate-600 hover:bg-slate-50 font-bold text-xs" 
                                               title="Cetak F4 / PDF">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- GRID VIEW LEVEL 2 -->
                <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($docsInSelectedYear as $doc)
                        <div class="st-card-v2 p-5 border-t-4 border-t-cyan-500 hover:shadow-md transition flex flex-col justify-between group">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-10 h-10 rounded-2xl bg-cyan-50 text-cyan-600 border border-cyan-200 flex items-center justify-center text-lg font-black group-hover:bg-cyan-600 group-hover:text-white transition">
                                        <i class="fa-solid fa-folder"></i>
                                    </div>
                                    <span class="bg-cyan-100 text-cyan-800 border border-cyan-300 px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase">
                                        🔒 ARSIP
                                    </span>
                                </div>
                                <h3 class="text-sm font-black text-slate-900 leading-snug group-hover:text-cyan-700 transition">
                                    {{ $doc->jenis_dokumen }} TA {{ $doc->tahun_anggaran }}
                                </h3>
                                <div class="text-[11px] text-slate-400 font-medium mt-0.5">
                                    Status: <strong class="text-slate-700">{{ $doc->status_label }}</strong>
                                </div>
                            </div>
                            <div class="pt-4 mt-4 border-t border-slate-100 flex items-center gap-1.5">
                                <a href="{{ route('renja.archive.document', $doc->id) }}" class="flex-1 st-btn bg-cyan-600 hover:bg-cyan-700 text-white font-black text-xs py-2 rounded-xl text-center shadow-xs">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <span>Buka Folder</span>
                                </a>
                                <a href="{{ route('renja.exportWord', $doc->id) }}" class="st-btn st-btn-outline st-btn-sm text-blue-700 border-blue-200 hover:bg-blue-50 font-bold" title="Unduh Word">
                                    <i class="fa-solid fa-file-word"></i>
                                </a>
                                <a href="{{ route('renja.print', $doc->id) }}" target="_blank" class="st-btn st-btn-outline st-btn-sm text-slate-600" title="Cetak F4">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        <!-- ========================================== -->
        <!-- LEVEL 3 & 4: INSIDE ARCHIVED DOCUMENT      -->
        <!-- ========================================== -->
        @else
            @php
                $groupedSections = $selectedDocument->sections->groupBy('bab_code');
            @endphp

            @if(!$activeBab)
                <!-- LEVEL 3: FOLDER BAB & FILE ITEM DI DALAM DOKUMEN ARSIP -->
                <div x-show="viewMode === 'list'" class="st-table-wrapper rounded-2xl border border-slate-200">
                    <table class="w-full text-xs text-left text-slate-700 border-collapse">
                        <thead class="bg-slate-900 text-white uppercase text-[10px] font-black tracking-wider border-b border-slate-800">
                            <tr>
                                <th class="p-3.5">Nama Item</th>
                                <th class="p-3.5">Tipe Item</th>
                                <th class="p-3.5 text-center">Jumlah Sub-bab</th>
                                <th class="p-3.5 text-center">Status</th>
                                <th class="p-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <!-- Cover Item -->
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-3.5 font-bold text-slate-900">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm border border-blue-200 shrink-0">
                                            <i class="fa-solid fa-file-lines"></i>
                                        </div>
                                        <div>
                                            <div class="text-xs font-black text-slate-900">📄 Cover Dokumen Arsip</div>
                                            <div class="text-[10px] text-slate-400">Sampul Depan Standar Perbup</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3.5 text-slate-600 font-semibold">Front Matter (File)</td>
                                <td class="p-3.5 text-center text-slate-400">-</td>
                                <td class="p-3.5 text-center">
                                    <span class="bg-cyan-100 text-cyan-800 border border-cyan-300 px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase">
                                        🔒 ARSIP
                                    </span>
                                </td>
                                <td class="p-3.5 text-center">
                                    <a href="{{ route('renja.print', $selectedDocument->id) }}" target="_blank" class="st-btn st-btn-outline st-btn-sm text-xs font-bold">
                                        <i class="fa-solid fa-eye"></i>
                                        <span>Preview</span>
                                    </a>
                                </td>
                            </tr>

                            <!-- BAB Folders -->
                            @foreach($groupedSections as $babCode => $sectionsInBab)
                                @php
                                    $babTitle = $sectionsInBab->first()->bab_title ?? $babCode;
                                @endphp
                                <tr class="hover:bg-cyan-50/40 transition">
                                    <td class="p-3.5 font-bold text-slate-900">
                                        <a href="{{ route('renja.archive.bab', ['id' => $selectedDocument->id, 'babCode' => urlencode($babCode)]) }}" class="flex items-center space-x-3 group">
                                            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm border border-amber-200 shrink-0 group-hover:bg-amber-500 group-hover:text-white transition">
                                                <i class="fa-solid fa-folder"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-black text-slate-900 group-hover:text-cyan-700 transition">
                                                    📁 {{ $babCode }}: {{ $babTitle }}
                                                </div>
                                                <div class="text-[10px] text-slate-400">
                                                    {{ $sectionsInBab->count() }} sub-bab narasi historis
                                                </div>
                                            </div>
                                        </a>
                                    </td>
                                    <td class="p-3.5 text-slate-600 font-semibold">Folder BAB</td>
                                    <td class="p-3.5 text-center font-extrabold text-slate-900">{{ $sectionsInBab->count() }} Bagian</td>
                                    <td class="p-3.5 text-center">
                                        <span class="bg-cyan-100 text-cyan-800 border border-cyan-300 px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase">
                                            🔒 ARSIP
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <a href="{{ route('renja.archive.bab', ['id' => $selectedDocument->id, 'babCode' => urlencode($babCode)]) }}" 
                                           class="st-btn bg-cyan-600 hover:bg-cyan-700 text-white font-black text-xs st-btn-sm rounded-xl shadow-xs">
                                            <i class="fa-solid fa-folder-open"></i>
                                            <span>Buka BAB</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- GRID VIEW LEVEL 3 -->
                <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($groupedSections as $babCode => $sectionsInBab)
                        @php
                            $babTitle = $sectionsInBab->first()->bab_title ?? $babCode;
                        @endphp
                        <div class="st-card-v2 p-4 border-l-4 border-l-cyan-500 hover:shadow-md transition flex flex-col justify-between group">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-200 flex items-center justify-center text-lg font-black group-hover:bg-cyan-600 group-hover:text-white transition">
                                        <i class="fa-solid fa-folder"></i>
                                    </div>
                                    <span class="bg-cyan-100 text-cyan-800 border border-cyan-300 px-2 py-0.5 rounded-full font-black text-[9px]">
                                        🔒 ARSIP
                                    </span>
                                </div>
                                <h4 class="text-xs font-black text-slate-900 leading-snug group-hover:text-cyan-700 transition">
                                    📁 {{ $babCode }}: {{ $babTitle }}
                                </h4>
                                <p class="text-[11px] text-slate-400 mt-1">{{ $sectionsInBab->count() }} sub-bab narasi</p>
                            </div>
                            <a href="{{ route('renja.archive.bab', ['id' => $selectedDocument->id, 'babCode' => urlencode($babCode)]) }}" class="mt-3 st-btn bg-cyan-600 hover:bg-cyan-700 text-white font-black text-xs py-1.5 rounded-xl text-center shadow-xs">
                                <span>Buka Folder BAB</span>
                            </a>
                        </div>
                    @endforeach
                </div>

            @else
                <!-- LEVEL 4: DAFTAR SUB-BAB DI DALAM BAB ARSIP -->
                @php
                    $sectionsInActiveBab = $selectedDocument->sections->where('bab_code', $activeBab);
                @endphp

                <div x-show="viewMode === 'list'" class="st-table-wrapper rounded-2xl border border-slate-200">
                    <table class="w-full text-xs text-left text-slate-700 border-collapse">
                        <thead class="bg-slate-900 text-white uppercase text-[10px] font-black tracking-wider border-b border-slate-800">
                            <tr>
                                <th class="p-3.5">Kode & Judul Sub-bab</th>
                                <th class="p-3.5">Tipe Konten</th>
                                <th class="p-3.5 text-center">Status</th>
                                <th class="p-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($sectionsInActiveBab as $sec)
                                <tr class="hover:bg-cyan-50/40 transition">
                                    <td class="p-3.5 font-bold text-slate-900">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm border border-blue-200 shrink-0">
                                                <i class="fa-solid fa-file-lines"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-black text-slate-900">
                                                    📄 {{ $sec->sub_bab_code ?? '' }}. {{ $sec->sub_bab_title ?? $sec->bab_title }}
                                                </div>
                                                <div class="text-[10px] text-slate-400 font-normal">
                                                    {{ Str::limit(strip_tags($sec->content ?? 'Tidak ada narasi'), 90) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-3.5 text-slate-600 font-semibold whitespace-nowrap">
                                        Narasi Historis
                                    </td>

                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <span class="bg-cyan-100 text-cyan-800 border border-cyan-300 px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase">
                                            🔒 ARSIP (READ-ONLY)
                                        </span>
                                    </td>

                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <button type="button" 
                                                @click="
                                                    currentSectionTitle = '{{ addslashes($sec->title) }}';
                                                    currentSectionCode = '{{ addslashes($sec->sub_bab_code ?? $sec->bab_code) }}';
                                                    currentSectionContent = `{!! addslashes($sec->content ?? '<p class=\'text-slate-400\'>Belum ada isi.</p>') !!}`;
                                                    viewerModalOpen = true;
                                                "
                                                class="st-btn bg-cyan-600 hover:bg-cyan-700 text-white font-black text-xs st-btn-sm rounded-xl shadow-xs">
                                            <i class="fa-solid fa-book-open"></i>
                                            <span>Baca File Arsip</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-8 text-center text-slate-400">
                                        Tidak ada sub-bab di dalam folder ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- GRID VIEW SUB-BAB -->
                <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($sectionsInActiveBab as $sec)
                        <div class="st-card-v2 p-4 border-l-4 border-l-cyan-500 hover:shadow-md transition flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-200 flex items-center justify-center text-sm">
                                        <i class="fa-solid fa-file-lines"></i>
                                    </div>
                                    <span class="bg-cyan-100 text-cyan-800 border border-cyan-300 px-2 py-0.5 rounded-full font-black text-[9px]">
                                        🔒 ARSIP
                                    </span>
                                </div>
                                <h4 class="text-xs font-black text-slate-900 leading-snug">
                                    📄 {{ $sec->sub_bab_code ?? '' }}. {{ $sec->sub_bab_title ?? $sec->bab_title }}
                                </h4>
                                <p class="text-[10px] text-slate-500 mt-1 line-clamp-3 leading-relaxed">
                                    {{ strip_tags($sec->content ?? 'Tidak ada narasi') }}
                                </p>
                            </div>
                            <button type="button" 
                                    @click="
                                        currentSectionTitle = '{{ addslashes($sec->title) }}';
                                        currentSectionCode = '{{ addslashes($sec->sub_bab_code ?? $sec->bab_code) }}';
                                        currentSectionContent = `{!! addslashes($sec->content ?? '<p class=\'text-slate-400\'>Belum ada isi.</p>') !!}`;
                                        viewerModalOpen = true;
                                    "
                                    class="mt-3 st-btn bg-cyan-600 hover:bg-cyan-700 text-white font-black text-xs py-1.5 rounded-xl shadow-xs text-center w-full">
                                <i class="fa-solid fa-book-open"></i>
                                <span>Baca File</span>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

        @endif

    </div>

    <!-- ========================================== -->
    <!-- 5. MODAL DOCUMENT VIEWER ARSIP (READ ONLY) -->
    <!-- ========================================== -->
    <div x-show="viewerModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-950/70 backdrop-blur-xs transition-opacity duration-200">
        
        <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col transform transition-all border border-slate-100 overflow-hidden"
             @click.away="viewerModalOpen = false">
            
            <!-- Modal Header Toolbar -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0 border-b border-slate-800">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-cyan-500 text-slate-950 font-black flex items-center justify-center text-sm">
                        <i class="fa-solid fa-box-archive"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-cyan-400 font-bold text-xs" x-text="currentSectionCode"></span>
                            <span class="px-2 py-0.2 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-400/30 text-[9px] font-black uppercase">
                                🔒 DOKUMEN ARSIP (READ-ONLY)
                            </span>
                        </div>
                        <h3 class="text-sm font-black text-white" x-text="currentSectionTitle"></h3>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    @if($selectedDocument)
                        <a href="{{ route('renja.print', $selectedDocument->id) }}" target="_blank" class="st-btn st-btn-sm bg-slate-800 text-slate-200 hover:text-white border border-slate-700 font-bold text-xs" title="Cetak F4">
                            <i class="fa-solid fa-print"></i>
                            <span>Cetak</span>
                        </a>
                        <a href="{{ route('renja.exportWord', $selectedDocument->id) }}" class="st-btn st-btn-sm bg-blue-600 text-white hover:bg-blue-500 font-bold text-xs" title="Unduh Word">
                            <i class="fa-solid fa-file-word"></i>
                            <span>Word</span>
                        </a>
                    @endif
                    <button @click="viewerModalOpen = false" class="text-slate-400 hover:text-white p-1.5 rounded-xl hover:bg-slate-800 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Stempel Arsip & Info -->
            <div class="bg-cyan-50 px-6 py-2.5 border-b border-cyan-200 text-cyan-900 text-xs font-semibold flex items-center justify-between shrink-0 flex-wrap gap-2">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-shield-halved text-cyan-600"></i>
                    <span>Status: <strong>Dokumen Arsip Historis</strong> (Tersimpan Permanen &middot; Read-Only).</span>
                </div>
                <div class="text-[11px] text-cyan-800 font-bold">
                    Standar Format Resmi F4 Bookman Old Style 12pt
                </div>
            </div>

            <!-- Content Area Paper Simulation -->
            <div class="flex-1 p-6 sm:p-8 overflow-y-auto bg-slate-100">
                <div class="max-w-3xl mx-auto bg-white p-8 sm:p-12 rounded-2xl shadow-sm border border-slate-200 min-h-[500px]"
                     style="font-family: 'Bookman Old Style', 'URW Bookman L', Georgia, serif; font-size: 12pt; line-height: 1.6;">
                    
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-200 pb-2 mb-4" 
                        x-text="currentSectionCode + ' ' + currentSectionTitle"></h2>

                    <div class="text-slate-900 text-justify space-y-4" x-html="currentSectionContent">
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 bg-white border-t border-slate-100 flex items-center justify-between shrink-0">
                <span class="text-[11px] text-slate-400 font-medium">
                    Dokumen Saya &middot; RENJA &middot; Arsip Historis
                </span>
                <button type="button" @click="viewerModalOpen = false" class="st-btn st-btn-secondary st-btn-sm font-bold text-xs">
                    Tutup Viewer
                </button>
            </div>

        </div>
    </div>

</div>
@endsection
