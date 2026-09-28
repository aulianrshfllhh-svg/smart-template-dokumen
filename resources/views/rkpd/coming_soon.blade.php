@extends('layouts.app')

@section('title', 'Modul RKPD — Segera Hadir (Coming Soon)')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- ========================================== -->
    <!-- 1. BREADCRUMB & HEADER                      -->
    <!-- ========================================== -->
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2 text-[11px] font-black uppercase tracking-wider text-slate-400">
            <span>e-Dokumen Perencanaan</span>
            <span>/</span>
            <span class="text-slate-500">Dokumen Daerah</span>
            <span>/</span>
            <span class="text-emerald-700">RKPD</span>
        </div>

        <a href="{{ route('renja.workspace') }}" 
           class="st-btn st-btn-secondary st-btn-sm font-bold text-xs shrink-0 inline-flex items-center gap-2">
            <i class="fa-solid fa-arrow-left text-slate-500"></i>
            <span>Kembali ke Dokumen Saya</span>
        </a>
    </div>

    <!-- ========================================== -->
    <!-- 2. HERO COMING SOON CARD                  -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-8 sm:p-12 relative overflow-hidden bg-white border border-slate-200/80 rounded-3xl shadow-sm text-center">
        
        <!-- Decorative Ambient Background Glows -->
        <div class="absolute -top-24 -right-24 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-2xl mx-auto space-y-6 relative z-10">
            
            <!-- Status Badge -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-amber-900 text-xs font-black uppercase tracking-wider shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                <i class="fa-solid fa-clock-rotate-left text-amber-600 text-xs"></i>
                <span>Tahap Pengembangan • Coming Soon</span>
            </div>

            <!-- Icon Header -->
            <div class="w-20 h-20 mx-auto rounded-3xl bg-linear-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center text-3xl font-black shadow-lg shadow-emerald-500/20 border-4 border-white">
                <i class="fa-solid fa-landmark"></i>
            </div>

            <!-- Main Heading -->
            <div class="space-y-2">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    Modul RKPD Kabupaten Cirebon
                </h1>
                <p class="text-sm text-slate-500 font-medium leading-relaxed">
                    Rencana Kerja Pemerintah Daerah (RKPD) Tingkat Kabupaten
                </p>
            </div>

            <!-- Explanatory Box -->
            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 text-left space-y-2 text-xs text-slate-600 leading-relaxed">
                <div class="flex items-center gap-2 font-black text-slate-800 text-xs uppercase tracking-wide">
                    <i class="fa-solid fa-circle-info text-emerald-600"></i>
                    <span>Informasi Fitur</span>
                </div>
                <p>
                    Modul RKPD saat ini sedang disiapkan untuk integrasi agregasi otomatis tingkat Pemerintah Daerah. 
                    Saat ini seluruh operasional difokuskan pada penyusunan, verifikasi, dan finalisasi dokumen 
                    <strong>Rencana Kerja (RENJA) Murni & Perubahan</strong> oleh masing-masing Perangkat Daerah.
                </p>
            </div>

            <!-- Future Roadmap Highlights -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 text-left">
                <div class="p-3.5 rounded-xl border border-slate-200/70 bg-white shadow-2xs space-y-1">
                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs font-black mb-1.5">
                        <i class="fa-solid fa-diagram-project"></i>
                    </div>
                    <div class="text-xs font-black text-slate-800">Agregasi OPD</div>
                    <p class="text-[11px] text-slate-500 leading-normal">
                        Kompilasi otomatis program & pagu dari seluruh RENJA Perangkat Daerah.
                    </p>
                </div>

                <div class="p-3.5 rounded-xl border border-slate-200/70 bg-white shadow-2xs space-y-1">
                    <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-800 flex items-center justify-center text-xs font-black mb-1.5">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div class="text-xs font-black text-slate-800">Penyelarasan Makro</div>
                    <p class="text-[11px] text-slate-500 leading-normal">
                        Sinkronisasi target indikator kinerja makro daerah dan prioritas pembangunan.
                    </p>
                </div>

                <div class="p-3.5 rounded-xl border border-slate-200/70 bg-white shadow-2xs space-y-1">
                    <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center text-xs font-black mb-1.5">
                        <i class="fa-solid fa-file-export"></i>
                    </div>
                    <div class="text-xs font-black text-slate-800">Ekspor Perbup/Perda</div>
                    <p class="text-[11px] text-slate-500 leading-normal">
                        Penerbitan dokumen penetapan RKPD Kabupaten siap cetak dan arsip digital.
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('renja.workspace') }}" 
                   class="st-btn bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-2.5 px-6 rounded-xl shadow-md transition flex items-center justify-center gap-2 w-full sm:w-auto">
                    <i class="fa-solid fa-folder-open text-xs"></i>
                    <span>Buka Workspace RENJA</span>
                </a>
                <a href="{{ route('operator.renja-murni.index') }}" 
                   class="st-btn st-btn-outline font-bold text-xs py-2.5 px-5 rounded-xl transition flex items-center justify-center gap-2 w-full sm:w-auto">
                    <i class="fa-solid fa-file-invoice text-xs"></i>
                    <span>Dokumen RENJA Murni</span>
                </a>
            </div>

        </div>
    </div>

</div>
@endsection
