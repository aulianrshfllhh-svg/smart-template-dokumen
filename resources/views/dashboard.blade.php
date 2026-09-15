@extends('layouts.app')

@section('title', 'Dashboard Operator')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ wizardStep: 1, selectedJenis: 'Rencana Kerja (Renja)' }">

    {{-- FLASH NOTIFICATIONS --}}
    @if(session('success'))
        <div class="bg-emerald-50/90 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-2xl text-xs font-bold shadow-xs flex items-center space-x-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50/90 border border-rose-200 text-rose-900 px-4 py-3 rounded-2xl text-xs font-bold shadow-xs flex items-center space-x-2.5">
            <i class="fa-solid fa-circle-exclamation text-rose-600 text-base shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ========================================== --}}
    {{-- 1. HERO / PAGE HEADER (OPERATOR WORK CENTER) --}}
    {{-- ========================================== --}}
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1.5">
            <div class="flex items-center space-x-2 text-[11px] font-bold text-slate-400">
                <span>e-Dokumen Bapperida</span>
                <span>/</span>
                <span class="text-slate-600">Operator Work Center</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Dashboard Operator
            </h1>
            <div class="flex items-center gap-2 flex-wrap text-xs text-slate-600 font-medium pt-0.5">
                <span class="inline-flex items-center gap-1.5 font-bold text-slate-800">
                    <i class="fa-solid fa-building text-slate-400"></i>
                    {{ auth()->user()->opd->nama_opd ?? 'Perangkat Daerah' }}
                </span>
                <span class="text-slate-300">•</span>
                <span class="inline-flex items-center gap-1 bg-slate-900 text-amber-400 px-2.5 py-0.5 rounded-full text-[10px] font-black tracking-wide">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Periode {{ session('active_ta', (int)date('Y')) }}–{{ session('active_ta', (int)date('Y')) + 1 }} • AKTIF
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200">
                    {{ strtoupper(auth()->user()->role ?? 'OPERATOR') }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <button type="button" 
                    onclick="openModalBuatDokumen()" 
                    class="st-btn st-btn-amber st-btn-lg shadow-md rounded-2xl font-black text-xs cursor-pointer">
                <i class="fa-solid fa-circle-plus text-sm"></i>
                <span>+ Buat Dokumen Baru</span>
            </button>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- 2. KPI CARDS (4 Columns) + WoW STATS       --}}
    {{-- ========================================== --}}
    {{-- ========================================== --}}
    {{-- 2. KPI CARDS (4 Columns) + WoW STATS       --}}
    {{-- ========================================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">

        {{-- CARD 1: DRAFT (Amber/Orange) --}}
        <a href="{{ route('renja.index', ['status' => 'draft']) }}" class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs border-l-4 border-l-amber-500 hover:border-amber-400 hover:shadow-md transition-all block group cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 group-hover:text-amber-700 transition-colors">
                    <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0 animate-pulse"></span>
                    DRAFT
                </span>
                <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs group-hover:bg-amber-500 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-pen-ruler"></i>
                </div>
            </div>
            <div class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                {{ $stats['draft'] ?? 0 }}
            </div>
            <div class="mt-1 text-[10px] sm:text-[11px] font-semibold flex items-center gap-1">
                @if(($stats['wow_draft'] ?? 0) > 0)
                    <span class="text-emerald-600 font-bold"><i class="fa-solid fa-arrow-up text-[9px]"></i> +{{ $stats['wow_draft'] }}</span>
                @elseif(($stats['wow_draft'] ?? 0) < 0)
                    <span class="text-rose-600 font-bold"><i class="fa-solid fa-arrow-down text-[9px]"></i> {{ $stats['wow_draft'] }}</span>
                @else
                    <span class="text-slate-400">Stabil</span>
                @endif
                <span class="text-slate-400">vs minggu lalu</span>
            </div>
            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] font-extrabold text-amber-600 group-hover:text-amber-700">
                <span>Lihat Dokumen</span>
                <i class="fa-solid fa-arrow-right text-[9px] transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        {{-- CARD 2: PERLU REVISI (Red/Rose) --}}
        <a href="{{ route('renja.index', ['status' => 'revision_required']) }}" class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs border-l-4 border-l-rose-500 hover:border-rose-400 hover:shadow-md transition-all block group cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 group-hover:text-rose-700 transition-colors">
                    <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span>
                    PERLU REVISI
                </span>
                <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs group-hover:bg-rose-500 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
            </div>
            <div class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                {{ $stats['revisi'] ?? 0 }}
            </div>
            <div class="mt-1 text-[10px] sm:text-[11px] font-semibold flex items-center gap-1">
                @if(($stats['wow_revisi'] ?? 0) > 0)
                    <span class="text-rose-600 font-bold"><i class="fa-solid fa-arrow-up text-[9px]"></i> +{{ $stats['wow_revisi'] }}</span>
                @elseif(($stats['wow_revisi'] ?? 0) < 0)
                    <span class="text-emerald-600 font-bold"><i class="fa-solid fa-arrow-down text-[9px]"></i> {{ $stats['wow_revisi'] }}</span>
                @else
                    <span class="text-slate-400">Stabil</span>
                @endif
                <span class="text-slate-400">vs minggu lalu</span>
            </div>
            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] font-extrabold text-rose-600 group-hover:text-rose-700">
                <span>Lihat Dokumen</span>
                <i class="fa-solid fa-arrow-right text-[9px] transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        {{-- CARD 3: SEDANG DIVERIFIKASI (Blue) --}}
        <a href="{{ route('renja.index', ['status' => 'under_verification']) }}" class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs border-l-4 border-l-blue-600 hover:border-blue-400 hover:shadow-md transition-all block group cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 group-hover:text-blue-700 transition-colors">
                    <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0"></span>
                    SEDANG DIVERIFIKASI
                </span>
                <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
            </div>
            <div class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                {{ $stats['menunggu'] ?? 0 }}
            </div>
            <div class="mt-1 text-[10px] sm:text-[11px] font-semibold flex items-center gap-1">
                @if(($stats['wow_menunggu'] ?? 0) > 0)
                    <span class="text-blue-600 font-bold"><i class="fa-solid fa-arrow-up text-[9px]"></i> +{{ $stats['wow_menunggu'] }}</span>
                @elseif(($stats['wow_menunggu'] ?? 0) < 0)
                    <span class="text-slate-500 font-bold"><i class="fa-solid fa-arrow-down text-[9px]"></i> {{ $stats['wow_menunggu'] }}</span>
                @else
                    <span class="text-slate-400">Stabil</span>
                @endif
                <span class="text-slate-400">vs minggu lalu</span>
            </div>
            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] font-extrabold text-blue-600 group-hover:text-blue-700">
                <span>Lihat Dokumen</span>
                <i class="fa-solid fa-arrow-right text-[9px] transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        {{-- CARD 4: DOKUMEN FINAL / ARSIP (Green/Emerald) --}}
        <a href="{{ route('renja.index', ['status' => 'approved']) }}" class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs border-l-4 border-l-emerald-600 hover:border-emerald-400 hover:shadow-md transition-all block group cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 group-hover:text-emerald-700 transition-colors">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    DOKUMEN FINAL / ARSIP
                </span>
                <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-box-archive"></i>
                </div>
            </div>
            <div class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                {{ $stats['disetujui'] ?? 0 }}
            </div>
            <div class="mt-1 text-[10px] sm:text-[11px] font-semibold flex items-center gap-1">
                @if(($stats['wow_disetujui'] ?? 0) > 0)
                    <span class="text-emerald-600 font-bold"><i class="fa-solid fa-arrow-up text-[9px]"></i> +{{ $stats['wow_disetujui'] }}</span>
                @elseif(($stats['wow_disetujui'] ?? 0) < 0)
                    <span class="text-rose-600 font-bold"><i class="fa-solid fa-arrow-down text-[9px]"></i> {{ $stats['wow_disetujui'] }}</span>
                @else
                    <span class="text-slate-400">Stabil</span>
                @endif
                <span class="text-slate-400">vs minggu lalu</span>
            </div>
            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] font-extrabold text-emerald-600 group-hover:text-emerald-700">
                <span>Lihat Dokumen</span>
                <i class="fa-solid fa-arrow-right text-[9px] transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

    </div>

    {{-- ========================================== --}}
    {{-- 3. SECTION: YANG PERLU ANDA LAKUKAN       --}}
    {{-- ========================================== --}}
    @php
        // Action-oriented derivation from existing active documents & notes
        $revisiDocs = $activeDocuments->filter(fn($d) => in_array($d->status, ['perlu_revisi', 'revisi', 'revision']));
        $draftDocs = $activeDocuments->filter(fn($d) => in_array($d->status, ['draft', 'belum_dikerjakan']));
        $pendingDocs = $activeDocuments->filter(fn($d) => in_array($d->status, ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'sedang_diperiksa', 'dikirim_ulang']));
        $hasActionableItems = ($revisiDocs->count() > 0) || ($draftDocs->count() > 0) || ($pendingDocs->count() > 0);
    @endphp

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center text-sm font-black">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 tracking-tight">Yang Perlu Anda Lakukan</h2>
                    <p class="text-[11px] text-slate-500 font-medium">Prioritas tindakan operator untuk memperlancar proses perencanaan</p>
                </div>
            </div>
            @if($hasActionableItems)
                <span class="bg-slate-100 text-slate-700 font-bold px-2.5 py-0.5 rounded-full text-[10px]">
                    {{ $activeDocuments->count() }} Dokumen Aktif
                </span>
            @endif
        </div>

        @if($hasActionableItems)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                {{-- REVISI ACTIONS (HIGH PRIORITY) --}}
                @foreach($revisiDocs as $doc)
                    <div class="bg-rose-50/50 border border-rose-200 rounded-xl p-4 flex flex-col justify-between space-y-3 hover:border-rose-300 transition-colors">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-200 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-exclamation text-[8px]"></i>
                                    Perlu Revisi
                                </span>
                                <span class="text-[10px] font-bold text-slate-400">TA {{ $doc->tahun_anggaran }}</span>
                            </div>
                            <h3 class="text-xs font-black text-slate-900 leading-snug">
                                {{ $doc->jenis_dokumen ?? 'Renja' }}
                            </h3>
                            <p class="text-[11px] text-rose-700/90 font-medium mt-1 line-clamp-2">
                                Terdapat catatan koreksi dari Verifikator Bapperida yang perlu diperbaiki.
                            </p>
                        </div>
                        <div class="pt-2 border-t border-rose-200/60 flex items-center justify-between">
                            <span class="text-[10px] text-slate-500 font-medium">
                                {{ $doc->updated_at ? $doc->updated_at->diffForHumans() : '-' }}
                            </span>
                            <a href="{{ route('renja.editor', $doc->id) }}"
                               class="inline-flex items-center gap-1 text-xs font-black text-rose-700 hover:text-rose-900">
                                <span>Buka & Revisi</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                @endforeach

                {{-- DRAFT ACTIONS (WORK IN PROGRESS) --}}
                @foreach($draftDocs as $doc)
                    <div class="bg-amber-50/40 border border-amber-200/80 rounded-xl p-4 flex flex-col justify-between space-y-3 hover:border-amber-300 transition-colors">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200 flex items-center gap-1">
                                    <i class="fa-solid fa-pen text-[8px]"></i>
                                    Draft Sedang Disusun
                                </span>
                                <span class="text-[10px] font-bold text-slate-400">TA {{ $doc->tahun_anggaran }}</span>
                            </div>
                            <h3 class="text-xs font-black text-slate-900 leading-snug">
                                {{ $doc->jenis_dokumen ?? 'Renja' }}
                            </h3>
                            <div class="mt-2 space-y-1">
                                <div class="flex justify-between text-[10px] font-bold text-slate-600">
                                    <span>Progress Bab</span>
                                    <span>{{ $doc->progress_percentage }}%</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ $doc->progress_percentage }}%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-amber-200/60 flex items-center justify-between">
                            <span class="text-[10px] text-slate-500 font-medium">
                                {{ $doc->updated_at ? $doc->updated_at->diffForHumans() : '-' }}
                            </span>
                            <a href="{{ route('renja.editor', $doc->id) }}"
                               class="inline-flex items-center gap-1 text-xs font-black text-amber-800 hover:text-amber-950">
                                <span>Lanjutkan Penyusunan</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                @endforeach

                {{-- WAITING VERIFICATION (IN REVIEW) --}}
                @foreach($pendingDocs as $doc)
                    <div class="bg-blue-50/40 border border-blue-200/80 rounded-xl p-4 flex flex-col justify-between space-y-3 hover:border-blue-300 transition-colors">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-200 flex items-center gap-1">
                                    <i class="fa-solid fa-hourglass-half text-[8px]"></i>
                                    Menunggu Verifikasi
                                </span>
                                <span class="text-[10px] font-bold text-slate-400">TA {{ $doc->tahun_anggaran }}</span>
                            </div>
                            <h3 class="text-xs font-black text-slate-900 leading-snug">
                                {{ $doc->jenis_dokumen ?? 'Renja' }}
                            </h3>
                            <p class="text-[11px] text-blue-700/90 font-medium mt-1 line-clamp-2">
                                Dokumen telah diajukan dan sedang dalam antrean verifikasi Bapperida.
                            </p>
                        </div>
                        <div class="pt-2 border-t border-blue-200/60 flex items-center justify-between">
                            <span class="text-[10px] text-slate-500 font-medium">
                                Diajukan {{ $doc->submitted_at ? $doc->submitted_at->diffForHumans() : ($doc->updated_at ? $doc->updated_at->diffForHumans() : '-') }}
                            </span>
                            <a href="{{ route('renja.editor', $doc->id) }}"
                               class="inline-flex items-center gap-1 text-xs font-black text-blue-700 hover:text-blue-900">
                                <span>Lihat Dokumen</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- EMPTY STATE: ALL CAUGHT UP --}}
            <div class="bg-slate-50 border border-slate-200/70 rounded-xl p-6 text-center space-y-2">
                <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-base mx-auto">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h3 class="text-xs font-black text-slate-900">Tidak ada pekerjaan yang perlu dilakukan</h3>
                <p class="text-[11px] text-slate-500 max-w-md mx-auto font-medium">
                    Semua dokumen Anda sedang dalam proses yang sesuai. Anda dapat membuat dokumen baru atau melihat arsip dokumen melalui menu Dokumen Saya.
                </p>
            </div>
        @endif
    </div>

    {{-- ========================================== --}}
    {{-- 4. SECTION: PROGRESS PENYUSUNAN DOKUMEN    --}}
    {{-- ========================================== --}}
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3.5">
            <div>
                <h2 class="text-sm font-black text-slate-900 tracking-tight">Progress Penyusunan Dokumen</h2>
                <p class="text-[11px] text-slate-500 font-medium">Rekapitulasi capaian penulisan bab dan status legalitas dokumen perangkat daerah</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <div class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-800 px-3 py-1 rounded-xl border border-emerald-200 text-xs font-bold">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-xs"></i>
                    <span>Status Finalisasi: <strong>{{ $overallProgress['completed'] ?? 0 }} / {{ $overallProgress['total'] ?? 0 }} Dokumen Final</strong></span>
                </div>
            </div>
        </div>

        {{-- PROGRESS BAR & OVERVIEW --}}
        <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between">
                <div class="space-y-0.5">
                    <div class="text-xs font-black text-slate-800 uppercase tracking-wider">Progress Penyusunan Konten</div>
                    <div class="text-[11px] text-slate-500 font-medium">
                        <strong>{{ $overallProgress['completed_sections'] ?? 0 }}</strong> dari <strong>{{ $overallProgress['total_sections'] ?? 0 }}</strong> Bab telah diselesaikan
                    </div>
                </div>
                <div class="text-2xl font-black text-slate-900 tabular-nums">
                    {{ $overallProgress['percentage'] ?? 0 }}%
                </div>
            </div>

            <div class="w-full bg-slate-200 rounded-full h-3.5 overflow-hidden p-0.5">
                <div class="bg-gradient-to-r from-amber-500 to-emerald-500 h-2.5 rounded-full transition-all duration-700 ease-out" style="width: {{ $overallProgress['percentage'] ?? 0 }}%"></div>
            </div>

            <div class="text-[10px] text-slate-400 font-medium">
                <i class="fa-solid fa-circle-info mr-1 text-slate-400"></i>
                Catatan: 100% progress Bab menandakan seluruh isi dokumen telah ditulis, namun dokumen tetap memerlukan proses verifikasi Bapperida hingga berstatus Final.
            </div>
        </div>

        {{-- 2 STAT MINI CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200/80 text-center">
                <div class="text-xl font-black text-slate-900">{{ $overallProgress['total'] ?? 0 }}</div>
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mt-0.5">Total Dokumen</div>
            </div>
            <div class="bg-emerald-50/60 rounded-xl p-3.5 border border-emerald-200/80 text-center">
                <div class="text-xl font-black text-emerald-700">{{ $overallProgress['completed'] ?? 0 }}</div>
                <div class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider mt-0.5">Selesai / Final</div>
            </div>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- 5. MAIN GRID: LEFT (8 COLS) + RIGHT (4 COLS) --}}
    {{-- ========================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- LEFT COLUMN (8 COLS) --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- DOKUMEN SEDANG DIKERJAKAN --}}
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 tracking-tight">Dokumen Sedang Dikerjakan</h2>
                        <p class="text-[11px] text-slate-500 font-medium">Daftar dokumen aktif yang memerlukan perhatian Anda</p>
                    </div>
                    <a href="{{ route('renja.index') }}" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                        Lihat Semua Dokumen <i class="fa-solid fa-arrow-right text-[9px]"></i>
                    </a>
                </div>

                <div class="st-table-wrapper rounded-xl border border-slate-200/80 overflow-hidden">
                    <table class="w-full text-xs text-left text-slate-700 border-collapse">
                        <thead class="bg-slate-50 text-slate-900 uppercase text-[10px] font-black tracking-wider border-b border-slate-200/80">
                            <tr>
                                <th class="p-3.5">Nama Dokumen</th>
                                <th class="p-3.5 text-center">TA</th>
                                <th class="p-3.5 text-center">Progress</th>
                                <th class="p-3.5 text-center">Status</th>
                                <th class="p-3.5 text-center hidden sm:table-cell">Terakhir Diperbarui</th>
                                <th class="p-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($activeDocuments as $doc)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="p-3.5 font-bold text-slate-900">
                                        <div class="flex items-center space-x-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-slate-100 text-blue-600 flex items-center justify-center font-black text-[11px] border border-slate-200 shrink-0">
                                                <i class="fa-solid fa-file-lines"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-xs font-black text-slate-900 truncate max-w-[160px] sm:max-w-xs">
                                                    {{ $doc->jenis_dokumen ?? 'Renja' }}
                                                </div>
                                                <div class="text-[10px] text-slate-400 font-normal truncate">
                                                    {{ $doc->opd->nama_opd ?? 'OPD' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-center font-bold text-slate-700 whitespace-nowrap">{{ $doc->tahun_anggaran }}</td>
                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center space-x-1.5">
                                            <div class="w-14 bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ $doc->progress_percentage }}%"></div>
                                            </div>
                                            <span class="text-[10px] font-black text-slate-700 shrink-0 tabular-nums">{{ $doc->progress_percentage }}%</span>
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <span class="{{ $doc->status_badge_class }} px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase inline-block border">
                                            {{ $doc->status_label }}
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-center text-[10px] text-slate-400 hidden sm:table-cell whitespace-nowrap">
                                        {{ $doc->updated_at ? $doc->updated_at->diffForHumans() : '-' }}
                                    </td>
                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center space-x-1">
                                            @if($doc->isEditableByOpd())
                                                <a href="{{ route('renja.editor', $doc->id) }}"
                                                   class="st-btn st-btn-primary st-btn-sm text-[10px] h-7 px-2.5 rounded-lg font-bold"
                                                   title="Lanjutkan Pengeditan">
                                                    <i class="fa-solid fa-pen-to-square text-[9px]"></i>
                                                    <span>Lanjutkan</span>
                                                </a>
                                            @else
                                                <a href="{{ route('renja.editor', $doc->id) }}"
                                                   class="st-btn st-btn-secondary st-btn-sm text-[10px] h-7 px-2.5 rounded-lg font-bold"
                                                   title="Lihat / Preview Dokumen">
                                                    <i class="fa-solid fa-eye text-[9px]"></i>
                                                    <span>Preview</span>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-slate-400">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-lg mx-auto mb-2">
                                            <i class="fa-solid fa-folder-open"></i>
                                        </div>
                                        <div class="text-xs font-bold text-slate-600">Tidak ada dokumen yang sedang dikerjakan</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">Klik tombol "+ Buat Dokumen Baru" untuk memulai penyusunan dokumen.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TIMELINE PENGAJUAN --}}
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-sm font-black text-slate-900 tracking-tight">Timeline Pengajuan</h2>
                    <p class="text-[11px] text-slate-500 font-medium">Riwayat pengajuan dan review dokumen perangkat daerah</p>
                </div>

                @if($timeline->count() > 0)
                    <div class="space-y-4">
                        @foreach($timeline as $entry)
                            <div class="flex gap-3">
                                {{-- Vertical line connector --}}
                                <div class="flex flex-col items-center shrink-0">
                                    <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold border border-slate-200">
                                        <i class="fa-solid fa-file-lines"></i>
                                    </div>
                                    @if(!$loop->last)
                                        <div class="w-0.5 flex-1 bg-slate-200 mt-1"></div>
                                    @endif
                                </div>

                                <div class="flex-1 min-w-0 pb-3">
                                    <div class="text-xs font-black text-slate-900 truncate">
                                        {{ $entry['document']->jenis_dokumen ?? 'Renja' }} TA {{ $entry['document']->tahun_anggaran }}
                                    </div>
                                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                                        @foreach($entry['events'] as $event)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-black uppercase border
                                                @if($event['color'] === 'blue') bg-blue-50 text-blue-800 border-blue-200
                                                @elseif($event['color'] === 'amber') bg-amber-50 text-amber-800 border-amber-200
                                                @elseif($event['color'] === 'emerald') bg-emerald-50 text-emerald-800 border-emerald-200
                                                @elseif($event['color'] === 'indigo') bg-indigo-50 text-indigo-800 border-indigo-200
                                                @elseif($event['color'] === 'sky') bg-sky-50 text-sky-800 border-sky-200
                                                @else bg-slate-50 text-slate-800 border-slate-200
                                                @endif
                                            ">
                                                <i class="fa-solid {{ $event['icon'] }} text-[8px]"></i>
                                                {{ $event['label'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-medium mt-1">
                                        {{ $entry['document']->submitted_at ? $entry['document']->submitted_at->diffForHumans() : $entry['document']->updated_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6 text-slate-400">
                        <i class="fa-solid fa-timeline text-2xl mb-2 text-slate-300 block"></i>
                        <span class="text-xs font-bold">Belum ada riwayat pengajuan dokumen.</span>
                    </div>
                @endif
            </div>

            {{-- AKTIVITAS TERBARU --}}
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-sm font-black text-slate-900 tracking-tight">Aktivitas Terbaru</h2>
                    <p class="text-[11px] text-slate-500 font-medium">Catatan riwayat interaksi dokumen perangkat daerah Anda</p>
                </div>

                @if($recentActivity->count() > 0)
                    <div class="space-y-2">
                        @foreach($recentActivity as $activity)
                            <div class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition-colors">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold shrink-0 border
                                    @if($activity['color'] === 'amber') bg-amber-50 text-amber-600 border-amber-200
                                    @elseif($activity['color'] === 'blue') bg-blue-50 text-blue-600 border-blue-200
                                    @elseif($activity['color'] === 'emerald') bg-emerald-50 text-emerald-600 border-emerald-200
                                    @elseif($activity['color'] === 'purple') bg-purple-50 text-purple-600 border-purple-200
                                    @elseif($activity['color'] === 'sky') bg-sky-50 text-sky-600 border-sky-200
                                    @else bg-slate-50 text-slate-600 border-slate-200
                                    @endif
                                ">
                                    <i class="fa-solid {{ $activity['icon'] }}"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold text-slate-900 truncate">{{ $activity['action'] }}</div>
                                    <div class="text-[10px] text-slate-500 truncate">
                                        {{ $activity['document']->jenis_dokumen ?? 'Renja' }} TA {{ $activity['document']->tahun_anggaran }}
                                    </div>
                                </div>
                                <div class="text-[10px] text-slate-400 font-medium shrink-0">
                                    {{ $activity['time']->diffForHumans() }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6 text-slate-400">
                        <i class="fa-solid fa-clock-rotate-left text-2xl mb-2 text-slate-300 block"></i>
                        <span class="text-xs font-bold">Belum ada aktivitas tercatat.</span>
                    </div>
                @endif
            </div>

        </div>

        {{-- RIGHT COLUMN (4 COLS) --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- CATATAN TERBARU DARI BAPPERIDA --}}
            <div class="bg-white border border-slate-200/80 rounded-2xl p-4.5 sm:p-5 shadow-xs space-y-3.5">
                <div class="border-b border-slate-100 pb-2.5">
                    <h2 class="text-xs font-black uppercase text-slate-700 tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-comment-dots text-amber-500"></i>
                        Catatan dari Bapperida
                    </h2>
                </div>

                @if($bapperidaNotes->count() > 0)
                    <div class="space-y-3">
                        @foreach($bapperidaNotes as $noteGroup)
                            <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-black text-amber-900 truncate flex items-center gap-1">
                                        <i class="fa-solid fa-file-signature text-amber-600"></i>
                                        {{ $noteGroup['document']->jenis_dokumen ?? 'Renja' }} TA {{ $noteGroup['document']->tahun_anggaran }}
                                    </span>
                                </div>

                                @foreach($noteGroup['notes'] as $note)
                                    <div class="bg-white rounded-lg p-2.5 border border-amber-100 shadow-2xs">
                                        <div class="text-[10px] font-black text-amber-800 mb-0.5">{{ $note['bab'] }}</div>
                                        <div class="text-[11px] text-slate-700 leading-relaxed line-clamp-3">{{ $note['catatan'] }}</div>
                                        <div class="text-[9px] text-slate-400 mt-1 font-medium">{{ $note['waktu']->diffForHumans() }}</div>
                                    </div>
                                @endforeach

                                @if($noteGroup['document']->isEditableByOpd())
                                    <a href="{{ route('renja.editor', $noteGroup['document']->id) }}"
                                       class="st-btn st-btn-amber st-btn-sm w-full text-[10px] rounded-lg font-black mt-1 shadow-2xs">
                                        <i class="fa-solid fa-pen text-[9px]"></i> Buka & Revisi
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5 text-slate-400">
                        <i class="fa-solid fa-circle-check text-2xl mb-1.5 text-emerald-400 block"></i>
                        <span class="text-[11px] font-bold text-emerald-700">Tidak ada catatan revisi</span>
                        <p class="text-[10px] text-slate-400 mt-0.5">Semua dokumen memenuhi kriteria verifikasi.</p>
                    </div>
                @endif
            </div>

            {{-- AKSES CEPAT --}}
            <div class="bg-white border border-slate-200/80 rounded-2xl p-4.5 sm:p-5 shadow-xs space-y-3.5">
                <h2 class="text-xs font-black uppercase text-slate-700 tracking-wider">Akses Cepat</h2>

                <div class="grid grid-cols-3 gap-2">
                    {{-- 1. DOKUMEN SAYA --}}
                    <a href="{{ route('renja.index') }}"
                       class="bg-white border border-slate-200/80 rounded-xl p-2.5 flex flex-col items-center justify-center text-center space-y-1.5 hover:border-amber-400 hover:bg-amber-50/20 transition group">
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center text-xs group-hover:scale-105 transition">
                            <i class="fa-solid fa-folder-open"></i>
                        </div>
                        <span class="text-[10px] font-extrabold text-slate-800 leading-tight">Dokumen Saya</span>
                    </a>

                    {{-- 2. ACUAN DOKUMEN --}}
                    <a href="{{ route('reference-documents.index') }}"
                       class="bg-white border border-slate-200/80 rounded-xl p-2.5 flex flex-col items-center justify-center text-center space-y-1.5 hover:border-amber-400 hover:bg-amber-50/20 transition group">
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-200 flex items-center justify-center text-xs group-hover:scale-105 transition">
                            <i class="fa-solid fa-book"></i>
                        </div>
                        <span class="text-[10px] font-extrabold text-slate-800 leading-tight">Acuan Dokumen</span>
                    </a>

                    {{-- 3. PANDUAN --}}
                    <button type="button" onclick="document.getElementById('modal-panduan').classList.remove('hidden')"
                            class="bg-white border border-slate-200/80 rounded-xl p-2.5 flex flex-col items-center justify-center text-center space-y-1.5 hover:border-amber-400 hover:bg-amber-50/20 transition group">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center text-xs group-hover:scale-105 transition">
                            <i class="fa-solid fa-book-open"></i>
                        </div>
                        <span class="text-[10px] font-extrabold text-slate-800 leading-tight">Panduan</span>
                    </button>
                </div>
            </div>

            {{-- DEADLINE PENYUSUNAN --}}
            <div class="bg-white border border-slate-200/80 rounded-2xl p-4.5 sm:p-5 shadow-xs space-y-3.5">
                <div class="border-b border-slate-100 pb-2.5">
                    <h2 class="text-xs font-black uppercase text-slate-700 tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-clock text-rose-500"></i>
                        Deadline Penyusunan
                    </h2>
                </div>

                @if(count($deadlines) > 0)
                    <div class="space-y-2.5">
                        @foreach($deadlines as $dl)
                            <div class="flex items-center gap-3 p-2.5 rounded-xl border
                                {{ $dl['is_overdue'] ? 'bg-rose-50/80 border-rose-200' : ($dl['is_urgent'] ? 'bg-amber-50/80 border-amber-200' : 'bg-slate-50 border-slate-200') }}
                            ">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-black shrink-0
                                    {{ $dl['is_overdue'] ? 'bg-rose-100 text-rose-700 border border-rose-300' : ($dl['is_urgent'] ? 'bg-amber-100 text-amber-700 border border-amber-300' : 'bg-slate-100 text-slate-600 border border-slate-200') }}
                                ">
                                    <i class="fa-solid {{ $dl['is_overdue'] ? 'fa-triangle-exclamation' : 'fa-calendar-day' }}"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[11px] font-black text-slate-900 truncate">{{ $dl['nama'] }}</div>
                                    <div class="text-[10px] text-slate-500 font-medium">{{ $dl['tanggal'] }}</div>
                                </div>
                                <div class="text-right shrink-0">
                                    @if($dl['is_overdue'])
                                        <span class="text-[10px] font-black text-rose-700 bg-rose-100 px-2 py-0.5 rounded-full border border-rose-300">Lewat</span>
                                    @elseif($dl['is_urgent'])
                                        <span class="text-[10px] font-black text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full border border-amber-300">{{ $dl['sisa_hari'] }} hari</span>
                                    @else
                                        <span class="text-[10px] font-black text-slate-600 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-200">{{ $dl['sisa_hari'] }} hari</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5 text-slate-400">
                        <i class="fa-solid fa-calendar-check text-2xl mb-1.5 text-emerald-400 block"></i>
                        <span class="text-[11px] font-bold text-emerald-700">Semua dokumen selesai</span>
                        <p class="text-[10px] text-slate-400 mt-0.5">Tidak ada tenggat waktu yang mendesak.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>

</div>

@endsection
