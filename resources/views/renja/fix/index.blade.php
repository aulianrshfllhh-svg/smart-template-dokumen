@extends('layouts.app')

@section('title', 'Dokumen Fix (Disetujui) - File Explorer')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{
    viewMode: '{{ $viewMode ?? 'list' }}',
    searchLocal: '{{ $searchQuery ?? '' }}',
    viewerModalOpen: {{ $activeSection ? 'true' : 'false' }},
    currentSectionTitle: '{{ $activeSection ? addslashes($activeSection->title) : '' }}',
    currentSectionCode: '{{ $activeSection ? addslashes($activeSection->sub_bab_code ?? $activeSection->bab_code) : '' }}',
    currentSectionContent: `{!! $activeSection ? addslashes($activeSection->content) : '' !!}`
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
    <div class="st-card-v2 p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-l-4 border-l-emerald-500 bg-gradient-to-r from-white via-white to-emerald-50/20">
        <div>
            <!-- Breadcrumb Navigation Interaktif -->
            <nav class="flex items-center space-x-2 text-[11px] font-black uppercase text-slate-400 mb-2 flex-wrap">
                <a href="{{ route('renja.workspace') }}" class="hover:text-amber-600 transition">Dokumen Saya</a>
                <span>/</span>
                <a href="{{ route('renja.workspace') }}" class="hover:text-amber-600 transition">RENJA</a>
                <span>/</span>
                @if(!$selectedDocument)
                    <span class="text-emerald-700">Dokumen Fix (Disetujui)</span>
                @else
                    <a href="{{ route('renja.fix.index', ['tahun_anggaran' => $selectedTa]) }}" class="hover:text-emerald-600 text-slate-600 transition">
                        Dokumen Fix
                    </a>
                    <span>/</span>
                    @if(!$activeBab)
                        <span class="text-emerald-700">{{ $selectedDocument->jenis_dokumen }} TA {{ $selectedDocument->tahun_anggaran }}</span>
                    @else
                        <a href="{{ route('renja.fix.show', $selectedDocument->id) }}" class="hover:text-emerald-600 text-slate-600 transition">
                            {{ $selectedDocument->jenis_dokumen }}
                        </a>
                        <span>/</span>
                        <span class="text-emerald-700">{{ $activeBab }}</span>
                    @endif
                @endif
            </nav>

            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-folder-open text-emerald-600"></i>
                    <span>Dokumen Fix</span>
                    @if($selectedDocument)
                        <span class="text-slate-400 font-normal text-base sm:text-lg">/ {{ $selectedDocument->jenis_dokumen }} TA {{ $selectedDocument->tahun_anggaran }}</span>
                    @endif
                </h1>

                <!-- Badge Status Resmi Repository -->
                <span class="px-3 py-1 rounded-full text-[10px] font-black tracking-wider uppercase bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1.5 shadow-2xs">
                    <i class="fa-solid fa-certificate text-emerald-600"></i>
                    <span>Dokumen Resmi &middot; Disetujui Bapperida</span>
                </span>
            </div>

            <p class="text-xs text-slate-500 font-medium mt-1">
                Repository dokumen RENJA yang telah disetujui dan dikunci oleh Bapperida (Read-Only &middot; Bebas Draf).
            </p>
        </div>

        <div class="flex items-center space-x-3 shrink-0">
            <!-- Selector Tahun Anggaran -->
            <form method="GET" action="{{ route('renja.fix.index') }}" class="flex items-center space-x-2">
                <label for="ta-select-fix" class="text-xs font-bold text-slate-600">Tahun:</label>
                <select id="ta-select-fix" name="tahun_anggaran" onchange="this.form.submit()" class="st-select text-xs font-black h-10 rounded-xl px-3 bg-white border border-slate-300">
                    <option value="all" {{ $selectedTa === 'all' ? 'selected' : '' }}>Semua TA</option>
                    @foreach(range(2025, 2035) as $y)
                        <option value="{{ $y }}" {{ $selectedTa == $y ? 'selected' : '' }}>TA {{ $y }}</option>
                    @endforeach
                </select>
                @if(!empty($jenisDokumenFilter) && $jenisDokumenFilter !== 'all')
                    <input type="hidden" name="jenis_dokumen" value="{{ $jenisDokumenFilter }}">
                @endif
                @if(!empty($searchQuery))
                    <input type="hidden" name="search" value="{{ $searchQuery }}">
                @endif
            </form>

            <a href="{{ route('renja.workspace', ['tahun_anggaran' => $selectedTa !== 'all' ? $selectedTa : session('active_ta', 2027)]) }}" class="st-btn st-btn-outline st-btn-sm font-bold text-xs">
                <i class="fa-solid fa-folder-tree text-amber-500"></i>
                <span>Workspace TA</span>
            </a>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. BANNER APPROVED DOCUMENT (JIKA DI DALAM FOLDER DOKUMEN) -->
    <!-- ========================================== -->
    @if($selectedDocument)
        @php
            $auditTrail = $selectedDocument->metadata['audit_trail'] ?? [];
            $approvalLog = collect($auditTrail)->reverse()->first(fn($log) => in_array($log['action'] ?? '', ['DOCUMENT_APPROVED', 'FINALIZED_BY_ADMIN_BAPPERIDA']));
            $approvalDate = $approvalLog['timestamp'] ?? $selectedDocument->updated_at?->toIso8601String();
            $approvedBy = $approvalLog['user_name'] ?? ($selectedDocument->assignedVerificator->nama_lengkap ?? 'Admin/Verifikator Bapperida');
        @endphp
        <div class="st-card-v2 p-4 sm:p-5 bg-gradient-to-r from-emerald-950 via-slate-900 to-slate-900 text-white rounded-3xl border border-emerald-500/30 shadow-lg flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-3.5">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-400/30 flex items-center justify-center text-xl font-black shrink-0 shadow-inner">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500 text-slate-950 shadow-xs">
                            ✓ DOKUMEN RESMI (FIX)
                        </span>
                        <span class="text-xs text-emerald-300 font-bold flex items-center gap-1">
                            <i class="fa-solid fa-circle-check text-emerald-400 text-[10px]"></i>
                            Telah Disetujui Bapperida
                        </span>
                    </div>
                    <div class="text-xs text-slate-300 font-medium mt-1">
                        Dokumen ini telah disetujui oleh Bapperida dan dikunci sebagai dokumen final (Read-Only).
                    </div>
                    <div class="flex items-center gap-4 text-[11px] text-slate-400 font-medium mt-1.5 flex-wrap">
                        <span><strong class="text-slate-200">OPD:</strong> {{ $selectedDocument->opd->nama_opd ?? '-' }} ({{ $selectedDocument->opd->nomor_lampiran_romawi ?? 'LAMPIRAN I' }})</span>
                        <span>&bull;</span>
                        <span><strong class="text-slate-200">Disetujui pada:</strong> {{ $approvalDate ? \Carbon\Carbon::parse($approvalDate)->format('d F Y, H:i') : '-' }}</span>
                        <span>&bull;</span>
                        <span><strong class="text-slate-200">Disetujui oleh:</strong> {{ $approvedBy }}</span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center flex-wrap gap-2 shrink-0">
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
            </div>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- 3. TOOLBAR (SEARCH, FILTER, SORT, VIEW)    -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-4 sm:p-5 space-y-4">
        <form method="GET" action="{{ $selectedDocument ? route('renja.fix.show', $selectedDocument->id) : route('renja.fix.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            @if($selectedDocument)
                <input type="hidden" name="document_id" value="{{ $selectedDocument->id }}">
            @endif

            <!-- 1. Search Bar -->
            <div class="sm:col-span-5 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ $searchQuery ?? '' }}" 
                       placeholder="🔍 Cari nama dokumen, BAB, subbab, atau konten..." 
                       class="st-input text-xs pl-9 h-10 rounded-xl w-full">
            </div>

            <!-- 2. Filter Jenis Dokumen (Hanya jika di level Root) -->
            @if(!$selectedDocument)
                <div class="sm:col-span-2">
                    <select name="jenis_dokumen" class="st-select text-xs h-10 rounded-xl font-bold w-full" onchange="this.form.submit()">
                        <option value="all">Semua Jenis</option>
                        <option value="Murni" {{ $jenisDokumenFilter === 'Murni' ? 'selected' : '' }}>RENJA Murni</option>
                        <option value="Perubahan" {{ $jenisDokumenFilter === 'Perubahan' ? 'selected' : '' }}>RENJA Perubahan</option>
                        <option value="Lampiran" {{ $jenisDokumenFilter === 'Lampiran' ? 'selected' : '' }}>Lampiran Perbub</option>
                    </select>
                </div>
            @endif

            <!-- 3. Sort Filter -->
            <div class="{{ !$selectedDocument ? 'sm:col-span-2' : 'sm:col-span-3' }}">
                <select name="sort" class="st-select text-xs h-10 rounded-xl font-bold w-full" onchange="this.form.submit()">
                    <option value="name_asc" {{ $sortBy === 'name_asc' ? 'selected' : '' }}>Nama A - Z</option>
                    <option value="name_desc" {{ $sortBy === 'name_desc' ? 'selected' : '' }}>Nama Z - A</option>
                    <option value="latest" {{ $sortBy === 'latest' ? 'selected' : '' }}>Terbaru</option>
                    <option value="oldest" {{ $sortBy === 'oldest' ? 'selected' : '' }}>Terlama</option>
                </select>
            </div>

            <!-- 4. Tombol Filter & Reset -->
            <div class="{{ !$selectedDocument ? 'sm:col-span-1' : 'sm:col-span-2' }} flex space-x-1.5">
                <button type="submit" class="st-btn st-btn-primary st-btn-sm h-10 rounded-xl text-xs font-black flex-1 justify-center shadow-xs">
                    <i class="fa-solid fa-filter text-xs"></i>
                    <span>Cari</span>
                </button>
                @if(!empty($searchQuery) || ($jenisDokumenFilter && $jenisDokumenFilter !== 'all'))
                    <a href="{{ $selectedDocument ? route('renja.fix.show', $selectedDocument->id) : route('renja.fix.index') }}" class="st-btn st-btn-secondary h-10 w-10 p-0 flex items-center justify-center shrink-0 rounded-xl" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                    </a>
                @endif
            </div>

            <!-- 5. View Mode Switcher (List vs Grid) -->
            <div class="sm:col-span-2 flex items-center justify-end space-x-1 bg-slate-100 p-1 rounded-xl">
                <button type="button" 
                        @click="viewMode = 'list'" 
                        :class="viewMode === 'list' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-800'"
                        class="flex-1 py-1.5 px-2 rounded-lg text-xs flex items-center justify-center gap-1.5 transition">
                    <i class="fa-solid fa-list text-xs"></i>
                    <span>List</span>
                </button>
                <button type="button" 
                        @click="viewMode = 'grid'" 
                        :class="viewMode === 'grid' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-800'"
                        class="flex-1 py-1.5 px-2 rounded-lg text-xs flex items-center justify-center gap-1.5 transition">
                    <i class="fa-solid fa-grip text-xs"></i>
                    <span>Grid</span>
                </button>
            </div>
        </form>
    </div>

    <!-- ========================================== -->
    <!-- 4. FILE EXPLORER MAIN CONTENT CONTAINER    -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 space-y-4">

        <!-- Navigation Bar / Back Buttons -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-wrap gap-2">
            <div class="flex items-center space-x-2">
                @if($activeBab && $selectedDocument)
                    <a href="{{ route('renja.fix.show', $selectedDocument->id) }}" class="st-btn st-btn-secondary st-btn-sm font-bold text-xs">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Kembali ke {{ $selectedDocument->jenis_dokumen }}</span>
                    </a>
                    <span class="text-slate-300">|</span>
                    <span class="text-xs font-black text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-folder-open text-amber-500"></i>
                        <span>Folder: {{ $activeBab }}</span>
                    </span>
                @elseif($selectedDocument)
                    <a href="{{ route('renja.fix.index', ['tahun_anggaran' => $selectedTa]) }}" class="st-btn st-btn-secondary st-btn-sm font-bold text-xs">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Kembali ke Root Dokumen Fix</span>
                    </a>
                    <span class="text-slate-300">|</span>
                    <span class="text-xs font-black text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-folder-tree text-emerald-600"></i>
                        <span>{{ $selectedDocument->jenis_dokumen }} TA {{ $selectedDocument->tahun_anggaran }}</span>
                    </span>
                @else
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-hard-drive text-slate-400 text-sm"></i>
                        <span class="text-xs font-black text-slate-800">Root Repository Dokumen Fix</span>
                    </div>
                @endif
            </div>

            <div class="text-[11px] font-bold text-slate-500">
                @if(!$selectedDocument)
                    <span>Total <strong>{{ $approvedDocuments->count() }}</strong> Dokumen Fix</span>
                @elseif($activeBab)
                    @php
                        $babSectionsCount = $selectedDocument->sections->where('bab_code', $activeBab)->count();
                    @endphp
                    <span><strong>{{ $babSectionsCount }}</strong> Sub-bab / Bagian</span>
                @else
                    @php
                        $groupedBabsCount = $selectedDocument->sections->groupBy('bab_code')->count();
                    @endphp
                    <span><strong>{{ $groupedBabsCount }}</strong> BAB &middot; <strong>{{ $selectedDocument->sections->count() }}</strong> Bagian</span>
                @endif
            </div>
        </div>

        <!-- ========================================== -->
        <!-- LEVEL 1: ROOT VIEW (DAFTAR DOKUMEN FIX)    -->
        <!-- ========================================== -->
        @if(!$selectedDocument)
            @if($approvedDocuments->isEmpty())
                <!-- Empty State -->
                <div class="p-12 text-center bg-slate-50/50 rounded-2xl border border-slate-100 space-y-3">
                    <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-3xl flex items-center justify-center text-3xl mx-auto border border-emerald-200 shadow-2xs">
                        <i class="fa-solid fa-folder-closed"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-black text-slate-900">Belum Ada Dokumen Fix</h3>
                        <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                            Belum terdapat dokumen RENJA yang disetujui Bapperida untuk Tahun Anggaran ini. Dokumen yang masih berupa draf atau dalam proses verifikasi tidak ditampilkan di repository ini.
                        </p>
                    </div>
                    <div class="pt-2 flex justify-center space-x-3">
                        <a href="{{ route('renja.workspace', ['tahun_anggaran' => $selectedTa !== 'all' ? $selectedTa : 2027]) }}" class="st-btn st-btn-amber st-btn-sm font-bold text-xs">
                            <i class="fa-solid fa-folder-tree"></i>
                            <span>Buka Workspace RENJA</span>
                        </a>
                    </div>
                </div>
            @else
                <!-- LIST VIEW (DEFAULT) -->
                <div x-show="viewMode === 'list'" class="st-table-wrapper rounded-2xl border border-slate-200">
                    <table class="w-full text-xs text-left text-slate-700 border-collapse">
                        <thead class="bg-slate-900 text-white uppercase text-[10px] font-black tracking-wider border-b border-slate-800">
                            <tr>
                                <th class="p-3.5">Nama Dokumen</th>
                                <th class="p-3.5">Jenis</th>
                                <th class="p-3.5 text-center">TA</th>
                                <th class="p-3.5">Nomor Lampiran</th>
                                <th class="p-3.5 text-center">Jumlah BAB</th>
                                <th class="p-3.5">Terakhir Diperbarui</th>
                                <th class="p-3.5 text-center">Status</th>
                                <th class="p-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($approvedDocuments as $doc)
                                @php
                                    $babCount = $doc->sections->groupBy('bab_code')->count();
                                    $jenisDokRaw = strtolower($doc->jenis_dokumen ?? '');
                                    $isLampiranPerbub = str_contains($jenisDokRaw, 'lampiran') || str_contains($jenisDokRaw, 'perbub');
                                    $isPerubahan = !$isLampiranPerbub && str_contains($jenisDokRaw, 'perubahan');
                                    $isMurni = !$isLampiranPerbub && !$isPerubahan;

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
                                <tr class="hover:bg-emerald-50/40 transition-colors">
                                    <!-- NAMA DOKUMEN FOLDER -->
                                    <td class="p-3.5 font-bold text-slate-900">
                                        <a href="{{ route('renja.fix.show', $doc->id) }}" class="flex items-center space-x-3 group">
                                            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-black text-sm border border-emerald-200 shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition">
                                                <i class="fa-solid fa-folder"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-black text-slate-900 group-hover:text-emerald-700 transition">
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

                                    <td class="p-3.5 text-slate-600 font-bold whitespace-nowrap">
                                        {{ $doc->opd->nomor_lampiran_romawi ?? 'LAMPIRAN I' }}
                                    </td>

                                    <td class="p-3.5 text-center font-extrabold text-slate-800 whitespace-nowrap">
                                        {{ $babCount > 0 ? "{$babCount} BAB" : '-' }}
                                    </td>

                                    <td class="p-3.5 text-slate-600 font-medium whitespace-nowrap text-[11px]">
                                        {{ $doc->updated_at ? $doc->updated_at->format('d M Y, H:i') : '-' }}
                                    </td>

                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-3 py-1 rounded-full font-black text-[10px] uppercase shadow-2xs">
                                            ✓ FIX RESMI
                                        </span>
                                    </td>

                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center space-x-1.5">
                                            <a href="{{ route('renja.fix.show', $doc->id) }}" 
                                               class="st-btn bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs st-btn-sm rounded-xl shadow-xs" 
                                               title="Buka Folder Explorer">
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

                <!-- GRID VIEW -->
                <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($approvedDocuments as $doc)
                        @php
                            $babCount = $doc->sections->groupBy('bab_code')->count();
                        @endphp
                        <div class="st-card-v2 p-5 border-t-4 border-t-emerald-500 hover:shadow-md transition-all flex flex-col justify-between group">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center text-lg font-black group-hover:bg-emerald-600 group-hover:text-white transition">
                                        <i class="fa-solid fa-folder"></i>
                                    </div>
                                    <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase">
                                        ✓ FIX
                                    </span>
                                </div>
                                <h3 class="text-sm font-black text-slate-900 leading-snug group-hover:text-emerald-700 transition">
                                    {{ $doc->jenis_dokumen }} TA {{ $doc->tahun_anggaran }}
                                </h3>
                                <div class="text-[11px] text-slate-400 font-medium mt-0.5">
                                    {{ $doc->opd->nama_opd ?? 'OPD' }} &middot; {{ $doc->opd->nomor_lampiran_romawi ?? 'LAMPIRAN I' }}
                                </div>
                                <div class="mt-3 bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-[11px] text-slate-600 flex justify-between">
                                    <span>Jumlah BAB:</span>
                                    <span class="font-black text-slate-900">{{ $babCount }} BAB</span>
                                </div>
                            </div>

                            <div class="pt-4 mt-4 border-t border-slate-100 flex items-center gap-1.5">
                                <a href="{{ route('renja.fix.show', $doc->id) }}" class="flex-1 st-btn bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs py-2 rounded-xl text-center shadow-xs">
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
        <!-- LEVEL 2 & 3: INSIDE DOCUMENT / BAB FOLDER -->
        <!-- ========================================== -->
        @else
            @php
                $groupedSections = $selectedDocument->sections->groupBy('bab_code');
            @endphp

            @if(!$activeBab)
                <!-- LEVEL 2: DAFTAR FOLDER BAB & FILE UTAMA DI DALAM DOKUMEN -->
                
                <!-- LIST VIEW (DEFAULT) -->
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
                            
                            <!-- FILE 1: COVER DOKUMEN RESMI -->
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-3.5 font-bold text-slate-900">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm border border-blue-200 shrink-0">
                                            <i class="fa-solid fa-file-lines"></i>
                                        </div>
                                        <div>
                                            <div class="text-xs font-black text-slate-900">📄 Cover Dokumen Resmi</div>
                                            <div class="text-[10px] text-slate-400">Sampul Depan Standar Bapperida</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3.5 text-slate-600 font-semibold">Front Matter (File)</td>
                                <td class="p-3.5 text-center text-slate-400">-</td>
                                <td class="p-3.5 text-center">
                                    <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase">
                                        ✓ FIX
                                    </span>
                                </td>
                                <td class="p-3.5 text-center">
                                    <a href="{{ route('renja.print', $selectedDocument->id) }}" target="_blank" class="st-btn st-btn-outline st-btn-sm text-xs font-bold">
                                        <i class="fa-solid fa-eye"></i>
                                        <span>Preview</span>
                                    </a>
                                </td>
                            </tr>

                            <!-- DAFTAR FOLDER BAB I s.d. BAB V -->
                            @foreach($groupedSections as $babCode => $sectionsInBab)
                                @php
                                    $babTitle = $sectionsInBab->first()->bab_title ?? $babCode;
                                @endphp
                                <tr class="hover:bg-emerald-50/40 transition">
                                    <td class="p-3.5 font-bold text-slate-900">
                                        <a href="{{ route('renja.fix.bab', ['id' => $selectedDocument->id, 'babCode' => urlencode($babCode)]) }}" class="flex items-center space-x-3 group">
                                            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm border border-amber-200 shrink-0 group-hover:bg-amber-500 group-hover:text-white transition">
                                                <i class="fa-solid fa-folder"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-black text-slate-900 group-hover:text-emerald-700 transition">
                                                    📁 {{ $babCode }}: {{ $babTitle }}
                                                </div>
                                                <div class="text-[10px] text-slate-400">
                                                    {{ $sectionsInBab->count() }} sub-bab / bagian narasi resmi
                                                </div>
                                            </div>
                                        </a>
                                    </td>
                                    <td class="p-3.5 text-slate-600 font-semibold">Folder BAB</td>
                                    <td class="p-3.5 text-center font-extrabold text-slate-900">{{ $sectionsInBab->count() }} Bagian</td>
                                    <td class="p-3.5 text-center">
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase">
                                            ✓ FIX
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <a href="{{ route('renja.fix.bab', ['id' => $selectedDocument->id, 'babCode' => urlencode($babCode)]) }}" 
                                           class="st-btn bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs st-btn-sm rounded-xl shadow-xs">
                                            <i class="fa-solid fa-folder-open"></i>
                                            <span>Buka BAB</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach

                            <!-- TABEL EVALUASI & UTAMA JIKA ADA -->
                            @if($selectedDocument->tableEvals && $selectedDocument->tableEvals->isNotEmpty())
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="p-3.5 font-bold text-slate-900">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm border border-purple-200 shrink-0">
                                                <i class="fa-solid fa-table"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-black text-slate-900">📄 Tabel Evaluasi Renja (BAB II)</div>
                                                <div class="text-[10px] text-slate-400">{{ $selectedDocument->tableEvals->count() }} entri data evaluasi</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-slate-600 font-semibold">Tabel Data Resmi</td>
                                    <td class="p-3.5 text-center font-bold">{{ $selectedDocument->tableEvals->count() }} Baris</td>
                                    <td class="p-3.5 text-center">
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase">
                                            ✓ FIX
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <a href="{{ route('renja.print', $selectedDocument->id) }}#bab-ii" target="_blank" class="st-btn st-btn-outline st-btn-sm text-xs font-bold">
                                            <i class="fa-solid fa-eye"></i>
                                            <span>Lihat Tabel</span>
                                        </a>
                                    </td>
                                </tr>
                            @endif

                            @if($selectedDocument->tableUtamas && $selectedDocument->tableUtamas->isNotEmpty())
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="p-3.5 font-bold text-slate-900">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm border border-blue-200 shrink-0">
                                                <i class="fa-solid fa-table"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-black text-slate-900">📄 Tabel Program & Kegiatan Utama (BAB IV)</div>
                                                <div class="text-[10px] text-slate-400">{{ $selectedDocument->tableUtamas->count() }} entri program & pagu</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-slate-600 font-semibold">Tabel Data Resmi</td>
                                    <td class="p-3.5 text-center font-bold">{{ $selectedDocument->tableUtamas->count() }} Baris</td>
                                    <td class="p-3.5 text-center">
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase">
                                            ✓ FIX
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <a href="{{ route('renja.print', $selectedDocument->id) }}#bab-iv" target="_blank" class="st-btn st-btn-outline st-btn-sm text-xs font-bold">
                                            <i class="fa-solid fa-eye"></i>
                                            <span>Lihat Tabel</span>
                                        </a>
                                    </td>
                                </tr>
                            @endif

                        </tbody>
                    </table>
                </div>

                <!-- GRID VIEW LEVEL 2 -->
                <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- Tile Cover -->
                    <div class="st-card-v2 p-4 border-l-4 border-l-blue-500 flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center text-lg mb-2">
                                <i class="fa-solid fa-file-lines"></i>
                            </div>
                            <h4 class="text-xs font-black text-slate-900">📄 Cover Dokumen Resmi</h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">Sampul Depan Standar Perbup</p>
                        </div>
                        <a href="{{ route('renja.print', $selectedDocument->id) }}" target="_blank" class="mt-3 st-btn st-btn-outline st-btn-sm w-full text-center text-xs font-bold">
                            <span>Preview</span>
                        </a>
                    </div>

                    <!-- Tiles BAB -->
                    @foreach($groupedSections as $babCode => $sectionsInBab)
                        @php
                            $babTitle = $sectionsInBab->first()->bab_title ?? $babCode;
                        @endphp
                        <div class="st-card-v2 p-4 border-l-4 border-l-amber-500 hover:shadow-md transition flex flex-col justify-between group">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center text-lg font-black group-hover:bg-amber-500 group-hover:text-white transition">
                                        <i class="fa-solid fa-folder"></i>
                                    </div>
                                    <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2 py-0.5 rounded-full font-black text-[9px]">
                                        ✓ FIX
                                    </span>
                                </div>
                                <h4 class="text-xs font-black text-slate-900 leading-snug group-hover:text-emerald-700 transition">
                                    📁 {{ $babCode }}: {{ $babTitle }}
                                </h4>
                                <p class="text-[11px] text-slate-400 mt-1">{{ $sectionsInBab->count() }} sub-bab narasi</p>
                            </div>
                            <a href="{{ route('renja.fix.bab', ['id' => $selectedDocument->id, 'babCode' => urlencode($babCode)]) }}" class="mt-3 st-btn bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs py-1.5 rounded-xl text-center shadow-xs">
                                <span>Buka Folder BAB</span>
                            </a>
                        </div>
                    @endforeach
                </div>

            @else
                <!-- LEVEL 3: DAFTAR SUB-BAB DI DALAM BAB TERTENTU -->
                @php
                    $sectionsInActiveBab = $selectedDocument->sections->where('bab_code', $activeBab);
                @endphp

                <!-- LIST VIEW SUB-BAB (DEFAULT) -->
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
                                <tr class="hover:bg-emerald-50/40 transition">
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
                                        Narasi Resmi
                                    </td>

                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase">
                                            ✓ FIX RESMI
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
                                                class="st-btn bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs st-btn-sm rounded-xl shadow-xs">
                                            <i class="fa-solid fa-book-open"></i>
                                            <span>Baca Dokumen</span>
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
                        <div class="st-card-v2 p-4 border-l-4 border-l-blue-500 hover:shadow-md transition flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center text-sm">
                                        <i class="fa-solid fa-file-lines"></i>
                                    </div>
                                    <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2 py-0.5 rounded-full font-black text-[9px]">
                                        ✓ FIX
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
                                    class="mt-3 st-btn bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs py-1.5 rounded-xl shadow-xs text-center w-full">
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
    <!-- 5. MODAL DOCUMENT VIEWER (READ ONLY)       -->
    <!-- ========================================== -->
    <div x-show="viewerModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-950/70 backdrop-blur-xs transition-opacity duration-200">
        
        <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col transform transition-all border border-slate-100 overflow-hidden"
             @click.away="viewerModalOpen = false">
            
            <!-- Modal Header Toolbar -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0 border-b border-slate-800">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500 text-slate-950 font-black flex items-center justify-center text-sm">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-emerald-400 font-bold text-xs" x-text="currentSectionCode"></span>
                            <span class="px-2 py-0.2 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-[9px] font-black uppercase">
                                ✓ DOKUMEN FIX (READ-ONLY)
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

            <!-- Stempel & Info Pengesahan Resmi -->
            <div class="bg-emerald-50 px-6 py-2.5 border-b border-emerald-200 text-emerald-900 text-xs font-semibold flex items-center justify-between shrink-0 flex-wrap gap-2">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-shield-halved text-emerald-600"></i>
                    <span>Status: <strong>Telah Disetujui & Dikunci oleh Bapperida</strong> (Tidak Dapat Diedit).</span>
                </div>
                <div class="text-[11px] text-emerald-800 font-bold">
                    Standar Format Resmi F4 Bookman Old Style 12pt
                </div>
            </div>

            <!-- Content Area (Bookman Old Style Paper Simulation) -->
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
                    Dokumen Saya &middot; RENJA &middot; Dokumen Fix (Disetujui)
                </span>
                <button type="button" @click="viewerModalOpen = false" class="st-btn st-btn-secondary st-btn-sm font-bold text-xs">
                    Tutup Viewer
                </button>
            </div>

        </div>
    </div>

</div>
@endsection
