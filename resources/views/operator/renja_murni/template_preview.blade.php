@extends('layouts.app')

@section('title', 'Template Saya - ' . ($template->name ?? 'Template Lampiran'))

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- FLASH NOTIFICATION -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-2xl text-xs font-bold flex items-center space-x-2.5 shadow-2xs">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- TAB SWITCHER -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-2">
        <a href="{{ route('operator.templates.renja-murni', ['code' => 'RENJA_LAMPIRAN_MURNI']) }}" 
           class="px-4 py-2 text-xs font-black rounded-xl border transition {{ ($template->code ?? '') === 'RENJA_LAMPIRAN_MURNI' ? 'bg-amber-500 text-white border-amber-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 shadow-2xs' }}">
            <i class="fa-solid fa-file-invoice mr-1.5"></i>
            <span>Lampiran RENJA Murni</span>
        </a>
        <a href="{{ route('operator.templates.renja-murni', ['code' => 'RENJA_LAMPIRAN_PERUBAHAN']) }}" 
           class="px-4 py-2 text-xs font-black rounded-xl border transition {{ ($template->code ?? '') === 'RENJA_LAMPIRAN_PERUBAHAN' ? 'bg-amber-500 text-white border-amber-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 shadow-2xs' }}">
            <i class="fa-solid fa-file-signature mr-1.5"></i>
            <span>Lampiran RENJA Perubahan</span>
        </a>
    </div>

    <!-- HEADER TITLE BANNER -->
    <div class="st-card-v2 p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-l-4 border-l-amber-500">
        <div>
            <div class="flex items-center space-x-2 text-[11px] font-black uppercase text-slate-400 mb-1">
                <span>Template Saya</span>
                <span>/</span>
                <span class="text-slate-900">{{ $template->name ?? 'Template Lampiran' }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-stamp text-amber-500"></i>
                <span>{{ $template->name ?? 'Template Lampiran' }}</span>
            </h1>
            <p class="text-xs text-slate-500 font-medium mt-1">
                {{ $template->description ?? 'Master struktur resmi dokumen Lampiran Peraturan Bupati (Perbub).' }}
            </p>
        </div>

        <div class="flex items-center space-x-3 shrink-0">
            <!-- Form Buat Dokumen Cepat dari Template -->
            <form method="POST" action="{{ route('operator.renja-murni.store-template') }}" class="flex items-center space-x-2">
                @csrf
                <input type="hidden" name="template_code" value="{{ $template->code ?? 'RENJA_LAMPIRAN_MURNI' }}">
                <select name="tahun_anggaran" class="st-select text-xs font-black h-10 rounded-xl px-3 bg-white border border-slate-300">
                    @php
                        $activeTa = session('active_ta', (int)date('Y'));
                    @endphp
                    @foreach(range(2025, 2035) as $y)
                        <option value="{{ $y }}" {{ $activeTa == $y - 1 ? 'selected' : '' }}>{{ $y - 1 }}–{{ $y }}</option>
                    @endforeach
                </select>

                <button type="submit" class="st-btn st-btn-amber font-black text-xs h-10 px-4 rounded-xl shadow-md">
                    <i class="fa-solid fa-circle-plus"></i>
                    <span>Buat Dokumen dari Template Ini</span>
                </button>
            </form>
        </div>
    </div>

    <!-- SPECIFICATION CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        
        <!-- Format Kertas -->
        <div class="st-card-v2 p-4 border-l-4 border-l-amber-500">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Format Kertas Resmi</span>
            <div class="text-base font-black text-slate-900 mt-1">F4 / Folio (215 × 330 mm)</div>
            <span class="text-[10px] text-slate-500 font-semibold mt-0.5 block">Standar Perbup Cirebon</span>
        </div>

        <!-- Margin -->
        <div class="st-card-v2 p-4 border-l-4 border-l-blue-500">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Margin Halaman</span>
            <div class="text-base font-black text-slate-900 mt-1">2 cm (Atas, Kanan, Bawah, Kiri)</div>
            <span class="text-[10px] text-slate-500 font-semibold mt-0.5 block">Batas tepi seragam</span>
        </div>

        <!-- Tipografi -->
        <div class="st-card-v2 p-4 border-l-4 border-l-indigo-500">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Font Standar</span>
            <div class="text-base font-black text-slate-900 mt-1">Bookman Old Style 12pt</div>
            <span class="text-[10px] text-slate-500 font-semibold mt-0.5 block">Spasi 1.5, Tanpa Bold Tubuh Dokumen</span>
        </div>

        <!-- Orientasi Dinamis -->
        <div class="st-card-v2 p-4 border-l-4 border-l-emerald-500">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Orientasi Halaman</span>
            <div class="text-base font-black text-slate-900 mt-1">Portrait & Section Landscape</div>
            <span class="text-[10px] text-slate-500 font-semibold mt-0.5 block">Khusus Tabel Matriks BAB IV</span>
        </div>

    </div>

    <!-- STRUKTUR LENGKAP TEMPLATE DOKUMEN -->
    <div class="st-card-v2 p-6 space-y-6">
        
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base font-black border border-amber-200">
                    <i class="fa-solid fa-sitemap"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">Struktur Dokumen Master Template</h3>
                    <p class="text-xs text-slate-500 font-medium">Struktur standar dokumen Lampiran Perbub yang akan digunakan untuk menghasilkan dokumen OPD.</p>
                </div>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-300">
                <i class="fa-solid fa-lock text-[10px]"></i> Master Bapperida (Read-Only)
            </span>
        </div>

        <!-- 1. BAGIAN AWAL (FRONT MATTER) -->
        <div class="space-y-3">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <i class="fa-solid fa-bookmark text-amber-500"></i>
                <span>Bagian Awal (Front Matter)</span>
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach($frontSections as $sec)
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase text-amber-600 px-2 py-0.5 rounded bg-amber-50 border border-amber-200">
                                {{ $sec->code }}
                            </span>
                            @if($sec->is_automatic)
                                <span class="text-[9px] font-bold text-sky-600 bg-sky-50 px-1.5 py-0.5 rounded">Auto Index</span>
                            @endif
                        </div>
                        <div class="font-bold text-slate-900 text-xs mt-1">{{ $sec->title }}</div>
                        <p class="text-[11px] text-slate-500 leading-snug">{{ $sec->guidance_text ?? 'Komponen halaman awal dokumen.' }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 2. BAGIAN UTAMA (BAB I s.d. BAB V) -->
        <div class="space-y-4 pt-2">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <i class="fa-solid fa-book-open text-blue-500"></i>
                <span>Batang Tubuh Dokumen (BAB I s.d. BAB V)</span>
            </h4>

            @php
                $grouped = $mainSections->groupBy(function($item) {
                    if ($item->section_type === 'chapter') {
                        return $item->code;
                    }
                    $codeParts = explode('.', $item->code);
                    $firstDigit = $codeParts[0] ?? '1';
                    $romans = ['1' => 'BAB I', '2' => 'BAB II', '3' => 'BAB III', '4' => 'BAB IV', '5' => 'BAB V'];
                    return $romans[$firstDigit] ?? 'BAB I';
                });
            @endphp

            <div class="space-y-4">
                @foreach($grouped as $babCode => $items)
                    @php
                        $chapter = $items->firstWhere('section_type', 'chapter') ?? $items->first();
                        $subchapters = $items->where('section_type', 'subchapter');
                    @endphp
                    <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2.5">
                                <span class="px-2.5 py-1 rounded-xl bg-slate-900 text-white font-black text-xs">
                                    {{ $babCode }}
                                </span>
                                <h5 class="text-sm font-black text-slate-900">{{ $chapter->title ?? $babCode }}</h5>
                            </div>
                            @if(str_contains($babCode, 'IV'))
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    <i class="fa-solid fa-arrows-left-right"></i> Landscape Compatible
                                </span>
                            @endif
                        </div>

                        @if(!empty($chapter->guidance_text))
                            <p class="text-xs text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100 leading-relaxed">
                                {{ $chapter->guidance_text }}
                            </p>
                        @endif

                        <!-- Sub-Bab List -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                            @foreach($subchapters as $sub)
                                <div class="p-2.5 rounded-xl bg-slate-50/70 border border-slate-100 flex items-start space-x-2">
                                    <span class="font-black text-xs text-amber-600 shrink-0 w-8">{{ $sub->code }}</span>
                                    <div class="text-xs">
                                        <div class="font-bold text-slate-800">{{ $sub->title }}</div>
                                        @if($sub->guidance_text)
                                            <div class="text-[10px] text-slate-500 mt-0.5">{{ $sub->guidance_text }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 3. LAMPIRAN -->
        @if($appendixSections->count() > 0)
        <div class="space-y-3 pt-2">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <i class="fa-solid fa-paperclip text-emerald-500"></i>
                <span>Bagian Lampiran</span>
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($appendixSections as $app)
                    <div class="p-3.5 rounded-2xl bg-emerald-50/40 border border-emerald-200 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase text-emerald-700 px-2 py-0.5 rounded bg-emerald-100/60">
                                {{ $app->code }}
                            </span>
                        </div>
                        <div class="font-bold text-slate-900 text-xs mt-1">{{ $app->title }}</div>
                        <p class="text-[11px] text-slate-500 leading-snug">{{ $app->guidance_text ?? 'Lampiran matriks pendukung Renja.' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>

</div>
@endsection
