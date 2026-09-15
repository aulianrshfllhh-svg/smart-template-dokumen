@extends('layouts.app')

@section('title', 'Manajemen Template Dokumen - Bapperida')

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
            <div class="inline-flex items-center space-x-2 bg-amber-500/10 text-amber-900 border border-amber-500/30 px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider mb-2">
                <i class="fa-solid fa-layer-group text-xs"></i>
                <span>STANDARD & DYNAMIC TEMPLATE ENGINE</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Manajemen Template Dokumen Perencanaan
            </h1>
            <p class="text-xs text-slate-500 font-medium mt-1 max-w-3xl">
                Kelola master template resmi Rencana Kerja (Renja) Murni, Perubahan, Lampiran Murni, dan Lampiran Perubahan. Atur status aktif/nonaktif, hirarki Bab I - V, petunjuk pengisian OPD, dan proteksi immutability.
            </p>
        </div>

        <div class="flex items-center space-x-3 shrink-0">
            <div class="st-pill-v2 bg-slate-900 text-white font-mono text-[11px] px-3.5 py-1.5 rounded-xl border border-slate-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span>TA AKTIF {{ $activeYear ?? date('Y') }}</span>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. SUMMARY METRICS CARDS -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        
        <div class="st-card-v2 p-5 border-l-4 border-l-blue-600 flex items-center justify-between">
            <div class="space-y-1 min-w-0">
                <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider block">TOTAL TEMPLATE SYSTEM</span>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $templates->count() }}</div>
                <div class="text-[11px] text-blue-600 font-bold">Master Template Terdaftar</div>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-black border border-blue-100">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
        </div>

        <div class="st-card-v2 p-5 border-l-4 border-l-emerald-600 flex items-center justify-between">
            <div class="space-y-1 min-w-0">
                <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider block">TEMPLATE AKTIF (BR-030)</span>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $templates->where('is_active', true)->count() }}</div>
                <div class="text-[11px] text-emerald-600 font-bold">Tersedia untuk Penyusunan OPD</div>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-black border border-emerald-100">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <div class="st-card-v2 p-5 border-l-4 border-l-amber-500 flex items-center justify-between">
            <div class="space-y-1 min-w-0">
                <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider block">PROTEKSI IMMUTABILITY (BR-24)</span>
                <div class="text-lg font-black text-slate-900 tracking-tight">Active Document Protection</div>
                <div class="text-[11px] text-amber-600 font-bold">Mencegah Penghapusan Template Terpakai</div>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-black border border-amber-100">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- 3. TABLE MASTER TEMPLATE -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 space-y-4">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3.5">
            <div>
                <h3 class="text-sm font-black text-slate-900 tracking-tight">Daftar Master Template Dokumen</h3>
                <p class="text-[11px] text-slate-500 font-medium">Kelola status aktif, struktur seksi bab, dan penggunaan oleh dokumen OPD</p>
            </div>
            <span class="text-[11px] font-black text-slate-700 bg-slate-100 px-3 py-1 rounded-xl shrink-0 border border-slate-200">
                Total: {{ $templates->count() }} Template Master
            </span>
        </div>

        <div class="st-table-wrapper rounded-2xl border border-slate-200 overflow-x-auto">
            <table class="w-full text-xs text-left text-slate-700 border-collapse min-w-[800px]">
                <thead class="bg-slate-50 text-slate-900 uppercase text-[10px] font-black tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-3.5">Kode & Nama Template</th>
                        <th class="p-3.5 text-center">Target Siklus TA</th>
                        <th class="p-3.5 text-center">Jumlah Seksi</th>
                        <th class="p-3.5 text-center">Digunakan Oleh</th>
                        <th class="p-3.5 text-center">Status (BR-030)</th>
                        <th class="p-3.5 text-center">Aksi Pengelolaan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($templates as $tpl)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            
                            <!-- KODE & NAMA TEMPLATE -->
                            <td class="p-3.5 font-bold text-slate-900">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-slate-900 text-amber-400 flex items-center justify-center font-black text-xs shrink-0 shadow-xs">
                                        <i class="fa-solid fa-file-invoice"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-black text-slate-900 truncate">
                                            {{ $tpl->name }}
                                        </div>
                                        <div class="text-[10px] font-mono text-amber-600 font-black">
                                            KODE: {{ $tpl->code }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- TARGET SIKLUS TA -->
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span class="bg-blue-50 text-blue-900 border border-blue-200 px-2.5 py-1 rounded-lg font-black text-[11px]">
                                    TA {{ $tpl->target_cycle_year ?? date('Y') }}
                                </span>
                            </td>

                            <!-- JUMLAH SEKSI BAB -->
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span class="bg-slate-100 text-slate-800 border border-slate-200 px-2.5 py-1 rounded-lg font-black text-xs">
                                    {{ $tpl->sections->count() }} Seksi Bab
                                </span>
                            </td>

                            <!-- DIGUNAKAN OLEH -->
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg font-black text-xs border {{ ($tpl->usage_count ?? 0) > 0 ? 'bg-amber-50 text-amber-900 border-amber-300' : 'bg-slate-100 text-slate-500 border-slate-200' }}">
                                    {{ $tpl->usage_count ?? 0 }} Dokumen
                                </span>
                            </td>

                            <!-- STATUS TOGGLE (BR-030) -->
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <form action="{{ route('admin.templates.toggleStatus', $tpl->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" 
                                            class="px-3 py-1 rounded-full font-black text-[10px] uppercase transition flex items-center space-x-1.5 border {{ $tpl->is_active ? 'bg-emerald-50 text-emerald-800 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-300 hover:bg-slate-200' }}"
                                            title="Klik untuk ubah status aktif template">
                                        <span class="w-2 h-2 rounded-full {{ $tpl->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                                        <span>{{ $tpl->is_active ? 'AKTIF (OPD)' : 'NON-AKTIF' }}</span>
                                    </button>
                                </form>
                            </td>

                            <!-- AKSI -->
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="{{ route('admin.templates.show', $tpl->id) }}" 
                                       class="st-btn st-btn-secondary st-btn-sm text-[11px] h-7.5 px-2.5 rounded-xl font-bold"
                                       title="Lihat Detail Seksi">
                                        <i class="fa-solid fa-eye text-[10px]"></i>
                                    </a>

                                    <a href="{{ route('admin.templates.edit', $tpl->id) }}" 
                                       class="st-btn st-btn-primary st-btn-sm text-[11px] h-7.5 px-3 rounded-xl font-bold shadow-xs">
                                        <i class="fa-solid fa-sliders text-[10px]"></i>
                                        <span>Edit</span>
                                    </a>

                                    @if(($tpl->usage_count ?? 0) === 0)
                                        <form action="{{ route('admin.templates.destroy', $tpl->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Hapus master template {{ $tpl->name }}?')" 
                                                    class="st-btn st-btn-danger st-btn-sm text-[11px] h-7.5 px-2.5 rounded-xl font-bold" title="Hapus Template">
                                                <i class="fa-solid fa-trash text-[10px]"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" disabled 
                                                class="st-btn st-btn-secondary opacity-40 cursor-not-allowed text-[11px] h-7.5 px-2.5 rounded-xl font-bold" 
                                                title="Template tidak dapat dihapus karena sedang digunakan oleh dokumen (BR-24)">
                                            <i class="fa-solid fa-lock text-[10px]"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500 font-medium">
                                Belum ada master template terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</div>
@endsection
