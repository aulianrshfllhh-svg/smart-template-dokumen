@extends('layouts.app')

@section('title', 'Dashboard Operasional Staff Bapperida')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- STAFF SPECIFIC HEADER BANNER -->
    <div class="st-card-v2 p-5 sm:p-6 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-2xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 bg-indigo-500/20 border border-indigo-400/30 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider text-indigo-300 mb-2">
                <i class="fa-solid fa-headset"></i>
                <span>Portal Operasional Staf Bapperida</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">Dukungan Operasional & Layanan Bantuan OPD</h2>
            <p class="text-xs text-slate-300 mt-1 max-w-2xl">Pantau kelancaran penyusunan dokumen daerah, bantu verifikasi teknis format F4, dan kelola template acuan pengusulan.</p>
        </div>
    </div>

    <!-- METRICS FOR STAFF -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="st-card-v2 p-4 border-l-4 border-l-indigo-600">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">🏢 Total OPD Terdaftar</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $opdStats['total_opd'] ?? 71 }}</div>
            <span class="text-[10px] text-indigo-600 font-bold">Perangkat Daerah Kab. Cirebon</span>
        </div>
        <div class="st-card-v2 p-4 border-l-4 border-l-emerald-600">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">🟢 OPD Menyusun</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $opdStats['sudah_menyusun'] ?? 0 }}</div>
            <span class="text-[10px] text-emerald-600 font-bold">Partisipasi Aktif</span>
        </div>
        <div class="st-card-v2 p-4 border-l-4 border-l-amber-500">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">🟡 Belum Menyusun</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $opdStats['belum_menyusun'] ?? 0 }}</div>
            <span class="text-[10px] text-amber-600 font-bold">Perlu Pendampingan</span>
        </div>
        <div class="st-card-v2 p-4 border-l-4 border-l-blue-600">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">📄 Dokumen Masuk</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $stats['total'] ?? 0 }}</div>
            <span class="text-[10px] text-blue-600 font-bold">Total Pengajuan</span>
        </div>
    </div>

    <!-- QUICK ACTIONS & ACTIVITY STREAM FOR STAFF -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT COLUMN (7 COLS): RECENT LOGS -->
        <div class="lg:col-span-7 st-card-v2 p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
                    <span>Log Aktivitas Penyusunan OPD (FR-011)</span>
                </h3>
            </div>

            <div class="divide-y divide-slate-100 text-xs space-y-2.5">
                @foreach($recentActivities ?? [] as $act)
                    <div class="pt-2 flex items-center justify-between">
                        <div>
                            <div class="font-black text-slate-900 text-xs">{{ $act->opd->nama_opd ?? 'OPD' }}</div>
                            <div class="text-[10px] text-slate-500 font-medium">Memperbarui {{ $act->jenis_dokumen }} TA {{ $act->tahun_anggaran }}</div>
                        </div>
                        <div class="text-right">
                            <span class="{{ $act->status_badge_class }} px-2 py-0.5 rounded-full text-[9px] font-black uppercase border">
                                {{ $act->status_label }}
                            </span>
                            <div class="text-[9px] text-slate-400 font-semibold mt-0.5">{{ $act->updated_at ? $act->updated_at->diffForHumans() : '-' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- RIGHT COLUMN (5 COLS): QUICK ACCESS FOR STAFF -->
        <div class="lg:col-span-5 st-card-v2 p-5 sm:p-6 space-y-4">
            <h3 class="text-xs font-black uppercase text-slate-700 tracking-wider">Akses Pintas Operasional</h3>

            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('reference-documents.index') }}" class="st-card-v2 p-4 flex flex-col items-center justify-center text-center space-y-2 hover:border-amber-400 transition">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-200 flex items-center justify-center text-base">
                        <i class="fa-solid fa-book"></i>
                    </div>
                    <span class="text-xs font-extrabold text-slate-800">Acuan Dokumen</span>
                </a>

                <a href="{{ route('formatter.index') }}" class="st-card-v2 p-4 flex flex-col items-center justify-center text-center space-y-2 hover:border-amber-400 transition">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 border border-purple-200 flex items-center justify-center text-base">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <span class="text-xs font-extrabold text-slate-800">Auto-Fix Word</span>
                </a>

                <a href="{{ route('bapperida.monitoring') }}" class="st-card-v2 p-4 flex flex-col items-center justify-center text-center space-y-2 hover:border-amber-400 transition">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center text-base">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <span class="text-xs font-extrabold text-slate-800">Monitoring OPD</span>
                </a>

                <button type="button" onclick="document.getElementById('modal-panduan').classList.remove('hidden')" class="st-card-v2 p-4 flex flex-col items-center justify-center text-center space-y-2 hover:border-amber-400 transition">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center text-base">
                        <i class="fa-solid fa-book-open"></i>
                    </div>
                    <span class="text-xs font-extrabold text-slate-800">Panduan Teknis</span>
                </button>
            </div>
        </div>

    </div>

</div>
@endsection
