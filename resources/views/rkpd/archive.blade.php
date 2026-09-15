@extends('layouts.app')

@section('title', 'Arsip Dokumen RKPD — Kabupaten Cirebon')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- HEADER BANNER -->
    <div class="st-card-v2 p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-l-4 border-l-emerald-500 bg-white">
        <div>
            <div class="flex items-center space-x-2 text-[11px] font-black uppercase text-slate-400 mb-1">
                <span>e-Dokumen Perencanaan</span>
                <span>/</span>
                <span class="text-emerald-700">Dokumen RKPD</span>
                <span>/</span>
                <span class="text-slate-900">Arsip</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-lg font-black shadow-xs shrink-0">
                    <i class="fa-solid fa-box-archive"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        Arsip Dokumen RKPD
                    </h1>
                    <p class="text-xs text-slate-500 font-medium">
                        Koleksi dokumen RKPD Pemerintah Daerah Kabupaten Cirebon dari berbagai tahun anggaran
                    </p>
                </div>
            </div>
        </div>

        <a href="{{ route('rkpd.index') }}" class="st-btn st-btn-secondary st-btn-sm font-bold text-xs shrink-0">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke RKPD</span>
        </a>
    </div>

    <!-- ARSIP ITEMS -->
    <div class="st-card-v2 p-5 sm:p-6 bg-white space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h4 class="text-sm font-black text-slate-900">Daftar Dokumen RKPD Resmi</h4>
            <span class="text-xs text-slate-400 font-semibold">Tahun Anggaran {{ $activeTa }} & Periode Sebelumnya</span>
        </div>

        <div class="space-y-3">
            <div class="p-4 rounded-2xl border border-slate-200 hover:border-emerald-400 bg-slate-50/50 hover:bg-emerald-50/20 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-base font-black shrink-0">
                        <i class="fa-solid fa-file-pdf"></i>
                    </div>
                    <div>
                        <div class="text-sm font-black text-slate-900">RKPD Kabupaten Cirebon TA {{ $activeTa }} (Murni)</div>
                        <div class="text-[11px] text-slate-500">Peraturan Bupati Cirebon tentang Rencana Kerja Pemerintah Daerah TA {{ $activeTa }}</div>
                    </div>
                </div>
                <div class="flex items-center space-x-2 shrink-0">
                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-900 font-bold text-[10px] rounded-lg">Resmi / Final</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl border border-slate-200 hover:border-emerald-400 bg-slate-50/50 hover:bg-emerald-50/20 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-800 flex items-center justify-center text-base font-black shrink-0">
                        <i class="fa-solid fa-file-pdf"></i>
                    </div>
                    <div>
                        <div class="text-sm font-black text-slate-900">RKPD Perubahan Kabupaten Cirebon TA {{ $activeTa }}</div>
                        <div class="text-[11px] text-slate-500">Peraturan Bupati Cirebon tentang Perubahan Rencana Kerja Pemerintah Daerah TA {{ $activeTa }}</div>
                    </div>
                </div>
                <div class="flex items-center space-x-2 shrink-0">
                    <span class="px-2.5 py-1 bg-indigo-100 text-indigo-900 font-bold text-[10px] rounded-lg">Tahap Penyusunan</span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
