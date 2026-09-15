@extends('layouts.app')

@section('title', 'Validasi Dokumen - ' . ($document->cover_data['judul_dokumen'] ?? 'RENJA Murni'))

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

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

    <!-- HEADER TITLE BANNER -->
    <div class="st-card-v2 p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-l-4 border-l-indigo-500">
        <div>
            <div class="flex items-center space-x-2 text-[11px] font-black uppercase text-slate-400 mb-1">
                <a href="{{ route('operator.renja-murni.index') }}" class="hover:text-amber-600 transition">RENJA Murni</a>
                <span>/</span>
                <span class="text-slate-900">Validasi & Auto Fix</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles text-indigo-500"></i>
                <span>Validasi Dokumen Terhadap Master Template</span>
            </h1>
            <p class="text-xs text-slate-500 font-medium mt-1">
                {{ $document->cover_data['judul_dokumen'] ?? 'RENJA Murni' }} • TA {{ $document->tahun_anggaran ?? $document->year }} • {{ $document->opd?->nama_opd ?? 'OPD' }}
            </p>
        </div>

        <div class="flex items-center space-x-2.5 shrink-0">
            <!-- Tombol Buka Editor -->
            <a href="{{ route('renja.editor', $document->id) }}" class="st-btn st-btn-outline st-btn-md font-bold text-xs">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Buka Editor</span>
            </a>

            <!-- Tombol Submit ke Admin -->
            @if(in_array(strtolower($document->status), ['draft', 'belum_dikerjakan', 'perlu_revisi', 'revisi']))
                <form method="POST" action="{{ route('renja.submit', $document->id) }}" onsubmit="return confirm('Kirimkan dokumen ini ke Admin Bapperida?');">
                    @csrf
                    <button type="submit" class="st-btn bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs st-btn-md shadow-md">
                        <i class="fa-solid fa-paper-plane mr-1"></i>
                        <span>Submit ke Admin</span>
                    </button>
                </form>
            @endif

        </div>
    </div>

    <!-- SUMMARY METRIC CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        
        <!-- Status Sumber -->
        <div class="st-card-v2 p-4 border-l-4 border-l-slate-400">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Sumber Dokumen</span>
            <div class="text-base font-black text-slate-900 mt-1 flex items-center gap-1.5">
                @if(($document->source_type ?? '') === 'upload_word')
                    <i class="fa-solid fa-file-word text-blue-600"></i>
                    <span>Upload Word (.docx)</span>
                @else
                    <i class="fa-solid fa-layer-group text-amber-600"></i>
                    <span>Master Template</span>
                @endif
            </div>
            <span class="text-[10px] text-slate-500 font-semibold mt-0.5 block">Diimport & Disesuaikan</span>
        </div>

        <!-- Total Masalah -->
        <div class="st-card-v2 p-4 border-l-4 {{ ($validationResult['summary']['total_issues'] ?? 0) > 0 ? 'border-l-amber-500' : 'border-l-emerald-500' }}">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Total Catatan Validasi</span>
            <div class="text-2xl font-black {{ ($validationResult['summary']['total_issues'] ?? 0) > 0 ? 'text-amber-600' : 'text-emerald-600' }} mt-1">
                {{ $validationResult['summary']['total_issues'] ?? 0 }}
            </div>
            <span class="text-[10px] text-slate-500 font-semibold mt-0.5 block">
                {{ ($validationResult['summary']['total_issues'] ?? 0) > 0 ? 'Perlu Disesuaikan / Auto Fix' : 'Semua Standar Terpenuhi' }}
            </span>
        </div>

        <!-- Rekomendasi Landscape -->
        <div class="st-card-v2 p-4 border-l-4 border-l-indigo-500">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Kebutuhan Landscape</span>
            <div class="text-2xl font-black text-indigo-600 mt-1">
                {{ $validationResult['summary']['recommendations'] ?? 0 }} Seksi
            </div>
            <span class="text-[10px] text-slate-500 font-semibold mt-0.5 block">Section-level Landscape</span>
        </div>

        <!-- Status Pengiriman -->
        <div class="st-card-v2 p-4 border-l-4 border-l-emerald-500">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Status Dokumen</span>
            <div class="text-base font-black text-slate-900 mt-1">
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $document->status_badge_class }}">
                    {{ $document->status_label }}
                </span>
            </div>
            <span class="text-[10px] text-slate-500 font-semibold mt-0.5 block">
                {{ $document->isEditableByOpd() ? 'Dapat diedit & disubmit' : 'Terkunci / Dalam review' }}
            </span>
        </div>

    </div>

    <!-- DETAIL LAPORAN VALIDASI 3 PILAR -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- PILAR 1: KELENGKAPAN STRUKTUR BAB -->
        <div class="st-card-v2 p-5 space-y-4">
            <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-black border border-amber-200">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">1. Struktur Dokumen</h3>
                    <p class="text-[11px] text-slate-400 font-medium">Pengecekan kelengkapan BAB & Bagian Awal</p>
                </div>
            </div>

            <div class="space-y-2">
                @foreach($validationResult['structure'] as $st)
                    <div class="p-3 rounded-xl border {{ $st['status'] === 'valid' ? 'bg-emerald-50/50 border-emerald-200' : 'bg-amber-50/50 border-amber-200' }} flex items-start justify-between gap-2">
                        <div class="text-xs space-y-0.5">
                            <div class="font-bold text-slate-900">{{ $st['item'] }}</div>
                            <div class="text-[11px] text-slate-500">{{ $st['message'] }}</div>
                        </div>
                        <span class="shrink-0 text-sm {{ $st['status'] === 'valid' ? 'text-emerald-600' : 'text-amber-500' }}">
                            <i class="fa-solid {{ $st['status'] === 'valid' ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- PILAR 2: FORMAT & KETENTUAN PERBUP -->
        <div class="st-card-v2 p-5 space-y-4">
            <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-black border border-blue-200">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">2. Format & Layout</h3>
                    <p class="text-[11px] text-slate-400 font-medium">Kertas F4, margin 2cm, dan font standar</p>
                </div>
            </div>

            <div class="space-y-2">
                @foreach($validationResult['format'] as $fm)
                    <div class="p-3 rounded-xl border {{ $fm['status'] === 'valid' ? 'bg-emerald-50/50 border-emerald-200' : 'bg-amber-50/50 border-amber-200' }} flex items-start justify-between gap-2">
                        <div class="text-xs space-y-0.5">
                            <div class="font-bold text-slate-900">{{ $fm['item'] }}</div>
                            <div class="text-[11px] text-slate-500">{{ $fm['message'] }}</div>
                        </div>
                        <span class="shrink-0 text-sm {{ $fm['status'] === 'valid' ? 'text-emerald-600' : 'text-amber-500' }}">
                            <i class="fa-solid {{ $fm['status'] === 'valid' ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- PILAR 3: TABEL & SECTION-LEVEL LANDSCAPE -->
        <div class="st-card-v2 p-5 space-y-4">
            <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-black border border-indigo-200">
                    <i class="fa-solid fa-table"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">3. Tabel & Orientasi Halaman</h3>
                    <p class="text-[11px] text-slate-400 font-medium">Deteksi tabel lebar dan landscape per seksi</p>
                </div>
            </div>

            <div class="space-y-2">
                @foreach($validationResult['tables'] as $tb)
                    <div class="p-3 rounded-xl border {{ $tb['status'] === 'valid' ? 'bg-emerald-50/50 border-emerald-200' : ($tb['status'] === 'recommendation' ? 'bg-indigo-50/50 border-indigo-200' : 'bg-slate-50 border-slate-200') }} flex items-start justify-between gap-2">
                        <div class="text-xs space-y-0.5">
                            <div class="font-bold text-slate-900">{{ $tb['item'] }}</div>
                            <div class="text-[11px] text-slate-500">{{ $tb['message'] }}</div>
                        </div>
                        <span class="shrink-0 text-sm {{ $tb['status'] === 'valid' ? 'text-emerald-600' : ($tb['status'] === 'recommendation' ? 'text-indigo-600' : 'text-slate-400') }}">
                            <i class="fa-solid {{ $tb['status'] === 'valid' ? 'fa-circle-check' : ($tb['status'] === 'recommendation' ? 'fa-arrows-left-right' : 'fa-info-circle') }}"></i>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    <!-- PANDUAN FLOW OPERATOR KE ADMIN -->
    <div class="st-card-v2 p-5 bg-gradient-to-r from-slate-900 to-slate-800 text-white rounded-3xl">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center space-x-2 text-[10px] font-black uppercase tracking-wider text-amber-400">
                    <i class="fa-solid fa-circle-nodes"></i>
                    <span>Alur Bisnis Role Operator RENJA Murni</span>
                </div>
                <h4 class="text-base font-black">Siap Mengirim Dokumen ke Admin Bapperida?</h4>
                <p class="text-xs text-slate-300">
                    Setelah melakukan Auto Fix dan pemeriksaan akhir di Smart Editor, klik <strong>Submit ke Admin</strong> agar dokumen masuk ke antrean Verification Workspace Bapperida.
                </p>
            </div>

            <div class="flex items-center space-x-3 shrink-0">
                <a href="{{ route('renja.editor', $document->id) }}" class="st-btn st-btn-outline border-slate-600 text-white hover:bg-slate-700 font-bold text-xs py-2.5 px-4 rounded-xl">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Buka Editor Narasi</span>
                </a>
                <form method="POST" action="{{ route('renja.submit', $document->id) }}" onsubmit="return confirm('Kirimkan dokumen RENJA Murni ini ke Admin Bapperida?');">
                    @csrf
                    <button type="submit" class="st-btn st-btn-amber font-black text-xs py-2.5 px-5 rounded-xl shadow-lg">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Submit Sekarang</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
