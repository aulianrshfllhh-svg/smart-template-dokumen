@extends('layouts.app')

@section('title', 'Detail Master Template - Bapperida')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- FLASH NOTIFICATION -->
    @if(session('success'))
        <div class="bg-emerald-50/90 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-2xl text-xs font-bold flex items-center space-x-2.5 shadow-xs">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50/90 border border-rose-200 text-rose-900 px-4 py-3 rounded-2xl text-xs font-bold flex items-center space-x-2.5 shadow-xs">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- 1. HEADER BANNER -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-l-4 border-l-amber-500">
        <div>
            <div class="flex items-center space-x-2 text-[11px] font-black uppercase text-slate-400 mb-1">
                <a href="{{ route('admin.templates.index') }}" class="hover:text-amber-600 transition">Manajemen Template</a>
                <span>/</span>
                <span class="text-slate-900">Detail Master Template</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>{{ $template->name }}</span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] uppercase font-black border {{ $template->is_active ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-slate-100 text-slate-500 border-slate-300' }}">
                    {{ $template->is_active ? 'AKTIF' : 'NON-AKTIF' }}
                </span>
            </h1>
            <p class="text-xs text-slate-500 font-medium mt-1">
                Kode Template: <span class="font-mono font-bold text-amber-600">{{ $template->code }}</span> • Target Siklus: <span class="font-bold text-slate-800">TA {{ $stats['target_cycle_year'] }}</span>
            </p>
        </div>

        <div class="flex items-center space-x-3 shrink-0">
            <a href="{{ route('admin.templates.edit', $template->id) }}" class="st-btn st-btn-primary st-btn-sm rounded-xl font-bold">
                <i class="fa-solid fa-sliders text-xs"></i>
                <span>Edit Struktur & Format</span>
            </a>
            <a href="{{ route('admin.templates.index') }}" class="st-btn st-btn-secondary st-btn-sm rounded-xl font-bold">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. METRICS METADATA CARDS -->
    <!-- ========================================== -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        
        <div class="st-card-v2 p-4 border-l-4 border-l-blue-600 space-y-1">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">TOTAL SEKSI</span>
            <div class="text-xl font-black text-slate-900 tracking-tight">{{ $stats['total_sections'] }}</div>
            <div class="text-[10px] text-blue-600 font-bold">Seksi Terdaftar</div>
        </div>

        <div class="st-card-v2 p-4 border-l-4 border-l-emerald-600 space-y-1">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">SEKSI WAJIB</span>
            <div class="text-xl font-black text-slate-900 tracking-tight">{{ $stats['required_count'] }}</div>
            <div class="text-[10px] text-emerald-600 font-bold">Must-have Sections</div>
        </div>

        <div class="st-card-v2 p-4 border-l-4 border-l-amber-500 space-y-1">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">EDITABLE OPD</span>
            <div class="text-xl font-black text-slate-900 tracking-tight">{{ $stats['editable_count'] }}</div>
            <div class="text-[10px] text-amber-600 font-bold">Dapat Diisi OPD</div>
        </div>

        <div class="st-card-v2 p-4 border-l-4 border-l-purple-600 space-y-1">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">AUTOMATIC</span>
            <div class="text-xl font-black text-slate-900 tracking-tight">{{ $stats['automatic_count'] }}</div>
            <div class="text-[10px] text-purple-600 font-bold">Generasi Sistem</div>
        </div>

        <div class="st-card-v2 p-4 border-l-4 border-l-slate-800 space-y-1 col-span-2 md:col-span-1">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">DIGUNAKAN DOKUMEN</span>
            <div class="text-xl font-black text-slate-900 tracking-tight">{{ $stats['usage_count'] }}</div>
            <div class="text-[10px] text-slate-600 font-bold">Dokumen OPD Active</div>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- 3. STRUKTUR HIERARKI SEKSI MASTER TEMPLATE -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-sm font-black text-slate-900">Struktur Hierarki Seksi (Section Tree)</h3>
                <p class="text-[11px] text-slate-500 font-medium">Susunan seksi Bab I - VI dan subbab yang akan dikloning ke dokumen baru OPD</p>
            </div>
            <span class="text-[11px] font-mono font-bold bg-slate-100 text-slate-700 px-3 py-1 rounded-lg border border-slate-200">
                Sequence Order Active
            </span>
        </div>

        <div class="space-y-3">
            @forelse($template->sections as $sec)
                <div class="p-3.5 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 {{ $sec->section_type === 'chapter' ? 'bg-slate-900 text-white border-slate-800' : 'bg-white text-slate-800 ml-0 sm:ml-6' }}">
                    <div class="flex items-center space-x-3 min-w-0">
                        <span class="text-[10px] font-mono font-black px-2 py-0.5 rounded-md border {{ $sec->section_type === 'chapter' ? 'bg-amber-400 text-slate-900 border-amber-300' : 'bg-slate-100 text-slate-700 border-slate-200' }}">
                            #{{ $sec->sequence }}
                        </span>
                        <div class="min-w-0">
                            <div class="text-xs font-black truncate flex items-center gap-2">
                                <span class="font-mono text-amber-500">{{ $sec->code }}</span>
                                <span>{{ $sec->title }}</span>
                            </div>
                            @if($sec->guidance_text)
                                <div class="text-[11px] opacity-75 font-medium mt-0.5 truncate">
                                    <i class="fa-solid fa-circle-info text-[9px]"></i> {{ $sec->guidance_text }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 shrink-0 text-[10px] font-bold">
                        <span class="px-2 py-0.5 rounded-md uppercase border {{ $sec->is_required ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-slate-100 text-slate-500 border-slate-300' }}">
                            {{ $sec->is_required ? 'Wajib' : 'Opsional' }}
                        </span>
                        <span class="px-2 py-0.5 rounded-md uppercase border {{ $sec->is_editable ? 'bg-blue-50 text-blue-800 border-blue-300' : 'bg-slate-100 text-slate-500 border-slate-300' }}">
                            {{ $sec->is_editable ? 'Editable' : 'Locked' }}
                        </span>
                        @if($sec->page_break_before)
                            <span class="px-2 py-0.5 rounded-md uppercase bg-purple-50 text-purple-800 border border-purple-200">
                                Page Break
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-500 text-xs font-medium">
                    Belum ada seksi yang didaftarkan pada master template ini.
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
