@extends('layouts.app')

@section('title', 'Dokumen Saya')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ isLoading: false, auditModalOpen: false, selectedAuditLog: [], selectedDocTitle: '' }">

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
    <!-- 1. HEADER TITLE BANNER                     -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-l-4 border-l-amber-500">
        <div>
            <div class="flex items-center space-x-2 text-[11px] font-black uppercase text-slate-400 mb-1">
                <span>Workspace Operator SKPD</span>
                <span>/</span>
                <span class="text-slate-900">Dokumen Saya</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Dokumen Saya
            </h1>
            <p class="text-xs text-slate-500 font-medium mt-1">
                Kelola seluruh dokumen perencanaan perangkat daerah.
            </p>
        </div>

        <div class="flex items-center space-x-3 shrink-0">
            <!-- TOMBOL UTAMA: BUAT DOKUMEN BARU -->
            <button type="button" onclick="openModalBuatDokumen()" 
                    class="st-btn st-btn-amber st-btn-lg shadow-md rounded-2xl font-black text-xs">
                <i class="fa-solid fa-circle-plus text-sm"></i>
                <span>+ Buat Dokumen Baru</span>
            </button>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. TOP EXECUTIVE KPI CARDS                 -->
    <!-- ========================================== -->
    @if(Auth::user()->isAdmin() || Auth::user()->isVerifikator() || Auth::user()->role === 'staff_bapperida')
        <!-- CARDS UNTUK AKUN BAPPERIDA (TANPA KARTU SUBMITTED) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            
            <!-- KPI 1: DRAFT / SEDANG DIKERJAKAN -->
            <a href="{{ route('renja.index', ['status' => 'draft']) }}" 
               class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-amber-500 flex items-center justify-between hover:shadow-md transition {{ $statusFilter === 'draft' ? 'ring-2 ring-amber-400 bg-amber-50/50' : '' }}">
                <div class="space-y-1 min-w-0">
                    <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 truncate">
                        <span>📝 DRAFT / SEDANG DIKERJAKAN</span>
                    </span>
                    <div class="flex items-baseline space-x-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $kpi['draft']['count'] ?? 0 }}</span>
                        <span class="text-xs font-bold text-amber-600">({{ $kpi['draft']['percentage'] ?? 0 }}%)</span>
                    </div>
                    <div class="text-[10px] text-amber-600 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-clock-rotate-left text-[9px]"></i>
                        <span>{{ $kpi['draft']['diff'] >= 0 ? '+' : '' }}{{ $kpi['draft']['diff'] }} dibanding minggu lalu</span>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-black border border-amber-200 shrink-0">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
            </a>

            <!-- KPI 2: FINAL / DIKUNCI -->
            <a href="{{ route('renja.index', ['status' => 'final']) }}" 
               class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-emerald-600 flex items-center justify-between hover:shadow-md transition {{ $statusFilter === 'final' ? 'ring-2 ring-emerald-500 bg-emerald-50/50' : '' }}">
                <div class="space-y-1 min-w-0">
                    <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 truncate">
                        <span>🟢 FINAL / DIKUNCI</span>
                    </span>
                    <div class="flex items-baseline space-x-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $kpi['final']['count'] ?? 0 }}</span>
                        <span class="text-xs font-bold text-emerald-600">({{ $kpi['final']['percentage'] ?? 0 }}%)</span>
                    </div>
                    <div class="text-[10px] text-emerald-600 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-circle-check text-[9px]"></i>
                        <span>{{ $kpi['final']['diff'] >= 0 ? '+' : '' }}{{ $kpi['final']['diff'] }} dibanding minggu lalu</span>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-black border border-emerald-200 shrink-0">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </a>

            <!-- KPI 3: TOTAL DOKUMEN -->
            <a href="{{ route('renja.index', ['status' => 'all', 'tahun_anggaran' => 'all', 'jenis_dokumen' => 'all', 'search' => '']) }}" 
               class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-blue-600 flex items-center justify-between hover:shadow-md transition {{ $statusFilter === 'all' || empty($statusFilter) ? 'ring-2 ring-blue-500 bg-blue-50/50' : '' }}"
               title="Lihat Seluruh Dokumen Perencanaan (Reset Filter)">
                <div class="space-y-1 min-w-0">
                    <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 truncate">
                        <span>📁 TOTAL DOKUMEN</span>
                    </span>
                    <div class="flex items-baseline space-x-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $documents->total() }}</span>
                        <span class="text-xs font-bold text-blue-600">(100%)</span>
                    </div>
                    <div class="text-[10px] text-blue-600 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-folder-open text-[9px]"></i>
                        <span>Seluruh Dokumen Perencanaan</span>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-black border border-blue-200 shrink-0">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
            </a>

        </div>
    @else
        <!-- TOP 4 EXECUTIVE KPI CARDS UNTUK OPD -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- KPI 1: DRAFT -->
            <a href="{{ route('renja.index', ['status' => 'draft']) }}" 
               class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-slate-400 flex items-center justify-between hover:shadow-md transition {{ $statusFilter === 'draft' ? 'ring-2 ring-slate-400 bg-slate-50' : '' }}">
                <div class="space-y-1 min-w-0">
                    <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 truncate">
                        <span>📝 DRAFT</span>
                    </span>
                    <div class="flex items-baseline space-x-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $kpi['draft']['count'] ?? 0 }}</span>
                        <span class="text-xs font-bold text-slate-400">({{ $kpi['draft']['percentage'] ?? 0 }}%)</span>
                    </div>
                    <div class="text-[10px] text-slate-500 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-clock-rotate-left text-[9px]"></i>
                        <span>{{ $kpi['draft']['diff'] >= 0 ? '+' : '' }}{{ $kpi['draft']['diff'] }} dibanding minggu lalu</span>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg font-black border border-slate-200 shrink-0">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
            </a>

            <!-- KPI 2: PERLU REVISI -->
            <a href="{{ route('renja.index', ['status' => 'revision_required']) }}" 
               class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-rose-500 flex items-center justify-between hover:shadow-md transition {{ in_array($statusFilter, ['revisi', 'perlu_revisi', 'revision_required', 'revision']) ? 'ring-2 ring-rose-400 bg-rose-50/50' : '' }}">
                <div class="space-y-1 min-w-0">
                    <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 truncate">
                        <span>🟠 PERLU REVISI</span>
                    </span>
                    <div class="flex items-baseline space-x-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $kpi['revisi']['count'] ?? 0 }}</span>
                        <span class="text-xs font-bold text-rose-600">({{ $kpi['revisi']['percentage'] ?? 0 }}%)</span>
                    </div>
                    <div class="text-[10px] text-rose-600 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-triangle-exclamation text-[9px]"></i>
                        <span>{{ $kpi['revisi']['diff'] >= 0 ? '+' : '' }}{{ $kpi['revisi']['diff'] }} dibanding minggu lalu</span>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg font-black border border-rose-200 shrink-0">
                    <i class="fa-solid fa-file-circle-exclamation"></i>
                </div>
            </a>

            <!-- KPI 3: SEDANG DIVERIFIKASI -->
            <a href="{{ route('renja.index', ['status' => 'under_verification']) }}" 
               class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-blue-600 flex items-center justify-between hover:shadow-md transition {{ in_array($statusFilter, ['submitted', 'under_verification', 'menunggu_verifikasi', 'menunggu']) ? 'ring-2 ring-blue-500 bg-blue-50/50' : '' }}">
                <div class="space-y-1 min-w-0">
                    <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 truncate">
                        <span>🔵 SEDANG DIVERIFIKASI</span>
                    </span>
                    <div class="flex items-baseline space-x-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $kpi['submitted']['count'] ?? 0 }}</span>
                        <span class="text-xs font-bold text-blue-600">({{ $kpi['submitted']['percentage'] ?? 0 }}%)</span>
                    </div>
                    <div class="text-[10px] text-blue-600 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-paper-plane text-[9px]"></i>
                        <span>{{ $kpi['submitted']['diff'] >= 0 ? '+' : '' }}{{ $kpi['submitted']['diff'] }} dibanding minggu lalu</span>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-black border border-blue-200 shrink-0">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
            </a>

            <!-- KPI 4: FINAL / DISUTUJUI -->
            <a href="{{ route('renja.index', ['status' => 'approved']) }}" 
               class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-emerald-600 flex items-center justify-between hover:shadow-md transition {{ in_array($statusFilter, ['final', 'approved', 'disetujui', 'dikunci']) ? 'ring-2 ring-emerald-500 bg-emerald-50/50' : '' }}">
                <div class="space-y-1 min-w-0">
                    <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 truncate">
                        <span>🟢 FINAL / DISUTUJUI</span>
                    </span>
                    <div class="flex items-baseline space-x-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $kpi['final']['count'] ?? 0 }}</span>
                        <span class="text-xs font-bold text-emerald-600">({{ $kpi['final']['percentage'] ?? 0 }}%)</span>
                    </div>
                    <div class="text-[10px] text-emerald-600 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-circle-check text-[9px]"></i>
                        <span>{{ $kpi['final']['diff'] >= 0 ? '+' : '' }}{{ $kpi['final']['diff'] }} dibanding minggu lalu</span>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-black border border-emerald-200 shrink-0">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </a>

        </div>
    @endif

    <!-- ========================================== -->
    <!-- 3. ACTION BAR FILTER & SEARCH              -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-4 sm:p-5 space-y-4">
        
        <form method="GET" action="{{ route('renja.index') }}" @submit="isLoading = true" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            
            <!-- SEARCH INPUT (LEFT SIDEBAR FILTER) -->
            <div class="sm:col-span-4 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ $search ?? '' }}" 
                       placeholder="Cari nama dokumen atau jenis..." 
                       class="st-input text-xs pl-9 h-10 rounded-xl">
            </div>

            <!-- FILTER TAHUN ANGGARAN -->
            <div class="sm:col-span-2">
                <select name="tahun_anggaran" class="st-select text-xs h-10 rounded-xl font-bold">
                    <option value="all">Semua TA</option>
                    @foreach(range(2025, 2035) as $yOpt)
                        <option value="{{ $yOpt }}" {{ ($tahunFilter ?? session('active_ta', 2027)) == $yOpt ? 'selected' : '' }}>TA {{ $yOpt }}</option>
                    @endforeach
                </select>
            </div>

            <!-- FILTER JENIS DOKUMEN -->
            <div class="sm:col-span-2">
                <select name="jenis_dokumen" class="st-select text-xs h-10 rounded-xl font-bold">
                    <option value="all">Semua Jenis</option>
                    <option value="Renja" {{ ($jenisFilter ?? '') == 'Renja' ? 'selected' : '' }}>Rencana Kerja (Renja)</option>
                    <option value="RKPD" {{ ($jenisFilter ?? '') == 'RKPD' ? 'selected' : '' }}>RKPD Kabupaten</option>
                    <option value="Evaluasi" {{ ($jenisFilter ?? '') == 'Evaluasi' ? 'selected' : '' }}>Evaluasi SKPD</option>
                </select>
            </div>

            <!-- FILTER STATUS -->
            <div class="sm:col-span-2">
                <select name="status" class="st-select text-xs h-10 rounded-xl font-bold">
                    <option value="all" {{ ($statusFilter ?? 'all') == 'all' ? 'selected' : '' }}>Semua Status</option>
                    @if(Auth::user()->isAdmin() || Auth::user()->isVerifikator() || Auth::user()->isStaff())
                        <option value="draft" {{ $statusFilter == 'draft' ? 'selected' : '' }}>📝 Draft / Sedang Dikerjakan</option>
                        <option value="final" {{ in_array($statusFilter, ['final', 'approved', 'disetujui']) ? 'selected' : '' }}>🟢 Final / Dikunci</option>
                    @else
                        <option value="draft" {{ $statusFilter == 'draft' ? 'selected' : '' }}>📝 Draft</option>
                        <option value="revision_required" {{ in_array($statusFilter, ['revisi', 'perlu_revisi', 'revision_required', 'revision']) ? 'selected' : '' }}>🟠 Perlu Revisi</option>
                        <option value="under_verification" {{ in_array($statusFilter, ['submitted', 'under_verification', 'menunggu_verifikasi', 'menunggu']) ? 'selected' : '' }}>🔵 Sedang Diverifikasi</option>
                        <option value="approved" {{ in_array($statusFilter, ['final', 'approved', 'disetujui']) ? 'selected' : '' }}>🟢 Dokumen Final / Disetujui</option>
                    @endif
                </select>
            </div>

            <!-- SUBMIT & RESET BUTTONS -->
            <div class="sm:col-span-2 flex space-x-1.5">
                <button type="submit" class="st-btn st-btn-primary st-btn-sm h-10 rounded-xl text-xs font-black flex-1 justify-center shadow-xs">
                    <i class="fa-solid fa-filter text-xs"></i>
                    <span>Filter</span>
                </button>
                
                @if(!empty($search) || ($tahunFilter && $tahunFilter !== 'all') || ($jenisFilter && $jenisFilter !== 'all') || ($statusFilter && $statusFilter !== 'all'))
                    <a href="{{ route('renja.index') }}" class="st-btn st-btn-secondary h-10 w-10 p-0 flex items-center justify-center shrink-0 rounded-xl" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                    </a>
                @endif
            </div>

        </form>

    </div>

    <!-- ========================================== -->
    <!-- 4. MAIN TABLE (MAIN WORKSPACE TABLE)       -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 space-y-4 relative">

        <!-- SKELETON LOADING OVERLAY -->
        <div x-show="isLoading" class="absolute inset-0 bg-white/80 backdrop-blur-xs z-30 flex items-center justify-center rounded-2xl">
            <div class="space-y-3 text-center">
                <div class="w-10 h-10 border-4 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                <div class="text-xs font-bold text-slate-700">Memuat Data Dokumen...</div>
            </div>
        </div>

        @if(($statusFilter && $statusFilter !== 'all') || !empty($search) || ($tahunFilter && $tahunFilter !== 'all') || ($jenisFilter && $jenisFilter !== 'all'))
            <div class="bg-blue-50/80 border border-blue-200/80 rounded-2xl p-3.5 flex items-center justify-between gap-3 text-xs mb-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-black text-blue-900 flex items-center gap-1.5">
                        <i class="fa-solid fa-filter text-blue-600"></i> Filter Aktif:
                    </span>
                    @if($statusFilter && $statusFilter !== 'all')
                        @php
                            $statusLabelMap = [
                                'draft' => 'Draft',
                                'revision_required' => 'Perlu Revisi',
                                'perlu_revisi' => 'Perlu Revisi',
                                'revisi' => 'Perlu Revisi',
                                'under_verification' => 'Sedang Diverifikasi',
                                'submitted' => 'Sedang Diverifikasi',
                                'approved' => 'Dokumen Final / Disetujui',
                                'final' => 'Dokumen Final / Disetujui',
                            ];
                            $displayStatusLabel = $statusLabelMap[strtolower($statusFilter)] ?? ucfirst(str_replace('_', ' ', $statusFilter));
                        @endphp
                        <span class="bg-blue-600 text-white px-3 py-1 rounded-xl font-bold flex items-center gap-1 text-[11px] shadow-2xs">
                            Status: {{ $displayStatusLabel }}
                        </span>
                    @endif
                    @if(!empty($search))
                        <span class="bg-slate-800 text-white px-3 py-1 rounded-xl font-bold flex items-center gap-1 text-[11px]">
                            Pencarian: "{{ $search }}"
                        </span>
                    @endif
                    @if($tahunFilter && $tahunFilter !== 'all')
                        <span class="bg-slate-700 text-white px-3 py-1 rounded-xl font-bold flex items-center gap-1 text-[11px]">
                            TA {{ $tahunFilter }}
                        </span>
                    @endif
                    @if($jenisFilter && $jenisFilter !== 'all')
                        <span class="bg-slate-700 text-white px-3 py-1 rounded-xl font-bold flex items-center gap-1 text-[11px]">
                            Jenis: {{ $jenisFilter }}
                        </span>
                    @endif
                </div>
                <a href="{{ route('renja.index') }}" class="text-[11px] font-black text-blue-700 hover:text-blue-900 underline whitespace-nowrap">
                    Reset Filter
                </a>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3.5">
            <div class="flex items-center space-x-2">
                <h3 class="text-sm font-black text-slate-900">Daftar Dokumen SKPD</h3>
                <span class="text-xs font-extrabold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">
                    Total {{ $documents->total() }} Dokumen
                </span>
            </div>
        </div>

        <!-- TABLE DATA -->
        <div class="st-table-wrapper rounded-2xl border border-slate-200">
            <table class="w-full text-xs text-left text-slate-700 border-collapse">
                <thead class="bg-slate-900 text-white uppercase text-[10px] font-black tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="p-3.5">Nama Dokumen</th>
                        <th class="p-3.5">Jenis Dokumen</th>
                        <th class="p-3.5">Perangkat Daerah</th>
                        <th class="p-3.5 text-center">TA</th>
                        <th class="p-3.5 text-center">Progress</th>
                        <th class="p-3.5">Tanggal Update</th>
                        <th class="p-3.5 text-center">Status</th>
                        <th class="p-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($documents as $doc)
                        @php
                            $isDraft = in_array($doc->status, ['draft', 'belum_dikerjakan']);
                            $isRevisi = in_array($doc->status, ['perlu_revisi', 'revisi', 'revision']);
                            $isSubmitted = in_array($doc->status, ['submitted', 'menunggu_pemeriksaan', 'menunggu_verifikasi', 'dikirim_ulang']);
                            $isUnderReview = in_array($doc->status, ['sedang_diperiksa', 'sedang_direview', 'under_review']);
                            $isFinal = in_array($doc->status, ['disetujui', 'approved', 'dikunci', 'final']);

                            $isBapperidaAdmin = Auth::user()->isAdmin() || Auth::user()->isVerifikator() || Auth::user()->isStaff();
                            $canEdit = $isBapperidaAdmin ? !$isFinal : ($isDraft || $isRevisi);
                            $canDelete = $isDraft || ($isBapperidaAdmin && !$isFinal);

                            if ($isBapperidaAdmin) {
                                if ($isFinal) {
                                    $badgeClass = 'bg-purple-100 text-purple-800 border border-purple-300';
                                    $statusLabel = 'FINAL';
                                } else {
                                    $badgeClass = 'bg-amber-50 text-amber-900 border border-amber-300 font-extrabold';
                                    $statusLabel = 'DRAFT';
                                }
                            } else {
                                $stEnum = \App\Enums\DocumentStatus::tryFrom($doc->status);
                                $badgeClass = $stEnum ? $stEnum->badgeClass() : 'bg-slate-100 text-slate-800';
                                $statusLabel = $stEnum ? $stEnum->label() : strtoupper($doc->status);
                            }

                            $auditTrail = $doc->metadata['audit_trail'] ?? [];
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            
                            <!-- NAMA DOKUMEN -->
                            <td class="p-3.5 font-bold text-slate-900 max-w-[220px]">
                                <div class="flex items-center space-x-3">
                                    <a href="{{ route('renja.show', ['id' => $doc->id, 'return_status' => $statusFilter ?? 'all']) }}" 
                                       class="w-8 h-8 rounded-xl bg-slate-100 text-blue-600 hover:bg-blue-600 hover:text-white flex items-center justify-center font-black text-xs border border-slate-200 shrink-0 transition-colors"
                                       title="Buka Detail Dokumen">
                                        <i class="fa-solid fa-file-word"></i>
                                    </a>
                                    <div class="min-w-0">
                                        <a href="{{ route('renja.show', ['id' => $doc->id, 'return_status' => $statusFilter ?? 'all']) }}" 
                                           class="text-xs font-black text-slate-900 hover:text-blue-600 transition-colors truncate block" 
                                           title="Buka Detail Dokumen {{ $doc->jenis_dokumen }} TA {{ $doc->tahun_anggaran }}">
                                            {{ $doc->jenis_dokumen }} TA {{ $doc->tahun_anggaran }}
                                        </a>
                                        <div class="text-[10px] text-slate-400 font-normal truncate">
                                            Lampiran: {{ $doc->opd->nomor_lampiran_romawi ?? '-' }}
                                        </div>

                                        <!-- CATATAN REVISI INLINE IF ANY -->
                                        @if($isRevisi)
                                            <div class="mt-1 bg-rose-50 border border-rose-200 text-rose-950 p-2 rounded-xl text-[10px] font-medium leading-relaxed">
                                                <span class="font-black text-rose-900 flex items-center gap-1">
                                                    <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Ada catatan revisi
                                                </span>
                                                @if($doc->catatan_bapperida)
                                                    <div class="mt-0.5 text-[10px] text-rose-900/90 font-semibold">{{ $doc->catatan_bapperida }}</div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- JENIS DOKUMEN -->
                            <td class="p-3.5 text-slate-700 font-bold whitespace-nowrap">
                                {{ $doc->jenis_dokumen }}
                            </td>

                            <!-- PERANGKAT DAERAH -->
                            <td class="p-3.5 text-slate-800 font-bold max-w-[180px] truncate" title="{{ $doc->opd->nama_opd ?? '-' }}">
                                {{ $doc->opd->nama_opd ?? '-' }}
                            </td>

                            <!-- TAHUN ANGGARAN -->
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 text-slate-900 font-black text-[11px]">
                                    {{ $doc->tahun_anggaran }}
                                </span>
                            </td>

                            <!-- PROGRESS PENYUSUNAN -->
                            <td class="p-3.5 text-center whitespace-nowrap min-w-[120px]">
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-[10px] font-bold text-slate-700">
                                        <span>Progress</span>
                                        <span>{{ $doc->progress_percentage }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ $doc->progress_percentage }}%"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- TANGGAL UPDATE -->
                            <td class="p-3.5 text-slate-600 font-medium whitespace-nowrap text-[11px]">
                                {{ $doc->updated_at ? $doc->updated_at->format('d M Y, H:i') : '-' }}
                            </td>

                            <!-- STATUS BADGE V2 -->
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span class="{{ $badgeClass }} px-3 py-1 rounded-full font-black text-[10px] uppercase border shadow-2xs">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <!-- AKSI PER BARIS (BERDASARKAN BUSINESS RULES) -->
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-1">
                                    
                                    <!-- LIHAT DOKUMEN FIX ATAU EDIT DOKUMEN -->
                                    @if($isFinal)
                                        <a href="{{ route('renja.print', $doc->id) }}" 
                                           target="_blank"
                                           class="st-btn bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] h-7.5 px-2.5 rounded-xl font-black shadow-xs flex items-center gap-1"
                                           title="Lihat Dokumen Resmi (FIX)">
                                            <i class="fa-solid fa-book-open text-[10px]"></i>
                                            <span>Lihat Fix</span>
                                        </a>
                                    @elseif($canEdit)
                                        @if($isRevisi)
                                            <a href="{{ route('renja.editor', $doc->id) }}" 
                                               class="st-btn bg-rose-600 hover:bg-rose-700 text-white text-[11px] h-7.5 px-2.5 rounded-xl font-black shadow-xs flex items-center gap-1"
                                               title="Lihat Catatan & Perbaiki Dokumen">
                                                <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                                                <span>Lihat & Perbaiki</span>
                                            </a>
                                        @else
                                            <a href="{{ route('renja.editor', $doc->id) }}" 
                                               class="st-btn st-btn-primary st-btn-sm text-[11px] h-7.5 px-2.5 rounded-xl font-bold"
                                               title="Edit Dokumen">
                                                <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                                                <span>Edit</span>
                                            </a>
                                        @endif
                                    @else
                                        <span class="st-btn st-btn-secondary st-btn-sm text-[11px] h-7.5 px-2.5 rounded-xl opacity-50 cursor-not-allowed" 
                                              title="Dokumen dikunci (Read-Only)">
                                            <i class="fa-solid fa-lock text-[10px]"></i>
                                            <span>Edit</span>
                                        </span>
                                    @endif

                                    <!-- FINALISASI (BAPPERIDA) ATAU SUBMIT (OPD) -->
                                    @if(Auth::user()->isAdmin() || Auth::user()->isVerifikator() || Auth::user()->isStaff())
                                        @if(!$isFinal)
                                            <form action="{{ route('renja.finalize', $doc->id) }}" method="POST" class="inline" 
                                                  onsubmit="return confirm('Apakah Anda yakin ingin memfinalisasi dan mengunci dokumen ini?')">
                                                @csrf
                                                <button type="submit" 
                                                        class="st-btn st-btn-success st-btn-sm text-[11px] h-7.5 px-2.5 rounded-xl font-bold shadow-xs"
                                                        title="Finalisasi & Kunci Dokumen">
                                                    <i class="fa-solid fa-lock text-[10px]"></i>
                                                    <span>Finalisasi</span>
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        @if($isDraft || $isRevisi)
                                            <form action="{{ route('renja.submit', $doc->id) }}" method="POST" class="inline" 
                                                  onsubmit="return confirm('Kirim dokumen ini ke Admin Bapperida untuk diverifikasi?')">
                                                @csrf
                                                <button type="submit" 
                                                        class="st-btn st-btn-amber st-btn-sm text-[11px] h-7.5 px-2.5 rounded-xl font-bold shadow-xs"
                                                        title="Submit Verifikasi ke Bapperida">
                                                    <i class="fa-solid fa-paper-plane text-[10px]"></i>
                                                    <span>Submit</span>
                                                </button>
                                            </form>
                                        @endif
                                    @endif

                                    <!-- PREVIEW DOKUMEN -->
                                    <a href="{{ route('renja.editor', $doc->id) }}" 
                                       class="st-btn st-btn-secondary st-btn-sm text-[11px] h-7.5 px-2 rounded-xl font-bold"
                                       title="Preview Dokumen">
                                        <i class="fa-solid fa-eye text-[10px]"></i>
                                    </a>

                                    <!-- EXPORT WORD & PRINT PDF (KHUSUS FINAL / SETUJU) -->
                                    @if($isFinal)
                                        <a href="{{ route('renja.exportWord', $doc->id) }}" 
                                           class="st-btn st-btn-success st-btn-sm text-[11px] h-7.5 px-2 rounded-xl font-bold"
                                           title="Export Word (.docx)">
                                            <i class="fa-solid fa-file-word text-[10px]"></i>
                                        </a>

                                        <a href="{{ route('renja.print', $doc->id) }}" 
                                           target="_blank"
                                           class="st-btn st-btn-secondary st-btn-sm text-[11px] h-7.5 px-2 rounded-xl font-bold"
                                           title="Cetak PDF F4">
                                            <i class="fa-solid fa-print text-[10px]"></i>
                                        </a>
                                    @endif

                                    <!-- RIWAYAT AUDIT LOG -->
                                    <button type="button" 
                                            @click="auditModalOpen = true; selectedAuditLog = {{ json_encode($auditTrail) }}; selectedDocTitle = '{{ $doc->jenis_dokumen }} TA {{ $doc->tahun_anggaran }}'"
                                            class="st-btn st-btn-secondary st-btn-sm text-[11px] h-7.5 px-2 rounded-xl font-bold"
                                            title="Riwayat Aktivitas">
                                        <i class="fa-solid fa-clock-rotate-left text-[10px]"></i>
                                    </button>

                                    <!-- HAPUS DOKUMEN (HANYA DRAFT) -->
                                    @if($canDelete)
                                        <form action="{{ route('renja.destroy', $doc->id) }}" method="POST" class="inline" 
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen DRAFT ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="st-btn st-btn-danger st-btn-sm text-[11px] h-7.5 px-2 rounded-xl font-bold"
                                                    title="Hapus Draft Dokumen">
                                                <i class="fa-solid fa-trash text-[10px]"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" 
                                                disabled
                                                class="st-btn st-btn-secondary st-btn-sm text-[11px] h-7.5 px-2 rounded-xl opacity-40 cursor-not-allowed"
                                                title="Dokumen dengan status {{ strtoupper($doc->status) }} tidak dapat dihapus">
                                            <i class="fa-solid fa-trash text-[10px]"></i>
                                        </button>
                                    @endif

                                </div>
                            </td>

                        </tr>
                    @empty
                        <!-- 5. EMPTY STATE (TAMPILKAN ILUSTRASI JIKA BELUM ADA DOKUMEN) -->
                        <tr>
                            <td colspan="8" class="p-12 text-center bg-slate-50/50">
                                <div class="max-w-md mx-auto space-y-4">
                                    <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-3xl flex items-center justify-center text-3xl mx-auto shadow-xs border border-amber-200 font-black">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </div>
                                    <div class="space-y-1">
                                        @if(in_array(strtolower($statusFilter ?? ''), ['draft']))
                                            <h4 class="text-base font-black text-slate-900">Belum Ada Dokumen Draft</h4>
                                            <p class="text-xs text-slate-500 leading-relaxed">
                                                Belum ada dokumen yang berstatus Draft untuk Perangkat Daerah Anda.
                                            </p>
                                        @elseif(in_array(strtolower($statusFilter ?? ''), ['revisi', 'perlu_revisi', 'revision_required', 'revision']))
                                            <h4 class="text-base font-black text-slate-900">Tidak Ada Dokumen Perlu Revisi</h4>
                                            <p class="text-xs text-slate-500 leading-relaxed">
                                                Tidak ada dokumen yang dikembalikan oleh Bapperida untuk diperbaiki.
                                            </p>
                                        @elseif(in_array(strtolower($statusFilter ?? ''), ['submitted', 'under_verification', 'menunggu_verifikasi', 'menunggu']))
                                            <h4 class="text-base font-black text-slate-900">Tidak Ada Dokumen Sedang Diverifikasi</h4>
                                            <p class="text-xs text-slate-500 leading-relaxed">
                                                Belum ada dokumen yang sedang dalam antrean verifikasi oleh Bapperida.
                                            </p>
                                        @elseif(in_array(strtolower($statusFilter ?? ''), ['final', 'approved', 'disetujui']))
                                            <h4 class="text-base font-black text-slate-900">Belum Ada Dokumen Final / Disetujui</h4>
                                            <p class="text-xs text-slate-500 leading-relaxed">
                                                Belum ada dokumen perencanaan yang telah disetujui atau difinalisasi.
                                            </p>
                                        @else
                                            <h4 class="text-base font-black text-slate-900">Belum Ada Dokumen</h4>
                                            <p class="text-xs text-slate-500 leading-relaxed">
                                                Belum ada dokumen perencanaan yang ditemukan sesuai dengan filter pencarian atau belum dibuat oleh OPD Anda.
                                            </p>
                                        @endif
                                    </div>
                                    <div class="flex items-center justify-center gap-2 pt-1">
                                        <a href="{{ route('renja.index') }}" 
                                           class="st-btn st-btn-secondary st-btn-md font-bold text-xs px-4 py-2.5 rounded-xl">
                                            <i class="fa-solid fa-rotate-left text-xs"></i>
                                            <span>Lihat Semua Dokumen</span>
                                        </a>
                                        <button type="button" onclick="openModalBuatDokumen()" 
                                                class="st-btn st-btn-amber st-btn-md font-black text-xs px-5 py-2.5 rounded-xl shadow-md">
                                            <i class="fa-solid fa-circle-plus text-xs"></i>
                                            <span>+ Buat Dokumen Baru</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <div class="pt-2">
            {{ $documents->links() }}
        </div>

    </div>

    <!-- MODAL AUDIT LOG HISTORY -->
    <div x-show="auditModalOpen" 
         x-transition
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" 
         style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm flex items-center space-x-2">
                        <i class="fa-solid fa-clock-rotate-left text-amber-500 text-base"></i>
                        <span>Riwayat Aktivitas Dokumen</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 font-bold" x-text="selectedDocTitle"></p>
                </div>
                <button type="button" @click="auditModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                <template x-if="selectedAuditLog.length === 0">
                    <div class="text-center py-6 text-slate-400 text-xs font-medium">
                        Belum ada riwayat aktivitas yang tercatat untuk dokumen ini.
                    </div>
                </template>

                <template x-for="(item, index) in selectedAuditLog" :key="index">
                    <div class="text-xs p-3 bg-slate-50 rounded-xl border border-slate-200/80 space-y-1">
                        <div class="flex items-center justify-between font-black text-slate-900">
                            <span x-text="item.action || 'ACTIVITY'"></span>
                            <span class="text-[10px] text-slate-400 font-normal" x-text="item.timestamp ? new Date(item.timestamp).toLocaleString('id-ID') : '-'"></span>
                        </div>
                        <div class="text-slate-700 font-medium" x-text="item.notes || '-'"></div>
                        <div class="text-[10px] text-slate-500 font-bold" x-text="'Oleh: ' + (item.user_name || 'User')"></div>
                    </div>
                </template>
            </div>

            <div class="flex justify-end pt-3 border-t border-slate-100">
                <button type="button" @click="auditModalOpen = false" class="st-btn st-btn-secondary st-btn-sm font-bold">Tutup</button>
            </div>
        </div>
    </div>

</div>

@endsection
