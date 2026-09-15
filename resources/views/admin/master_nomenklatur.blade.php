@extends('layouts.app')

@section('title', 'Master Nomenklatur')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="st-card p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 bg-purple-50 text-purple-800 border border-purple-200 px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider mb-2">
                <i class="fa-solid fa-list-ol"></i>
                <span>MASTER DATA SIPD</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Manajemen Master Nomenklatur Program & Kegiatan</h1>
            <p class="text-xs text-slate-500 mt-1">Pengelolaan master kode rekening dan nama nomenklatur sesuai standar Kepmendagri.</p>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="st-btn st-btn-secondary st-btn-sm">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            <span>Kembali ke Dashboard</span>
        </a>
    </div>

    <div class="st-card p-6 text-center space-y-3">
        <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-full flex items-center justify-center mx-auto text-xl">
            <i class="fa-solid fa-layer-group"></i>
        </div>
        <h3 class="text-base font-extrabold text-slate-900">Modul Nomenklatur Perencanaan</h3>
        <p class="text-xs text-slate-500 max-w-md mx-auto">
            Seluruh data struktur nomenklatur terintegrasi secara otomatis dengan mesin penyusun dokumen Renja.
        </p>
    </div>

</div>
@endsection
