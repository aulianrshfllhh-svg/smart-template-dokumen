@extends('layouts.app')

@section('title', 'Monitoring OPD — e-Dokumen Bapperida')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- ========================================== --}}
    {{-- 1. TOP HEADER & SIKLUS AKTIF BADGE        --}}
    {{-- ========================================== --}}
    <x-ui.page-heading title="Monitoring OPD" description="Pantau partisipasi dan progres penyusunan dokumen seluruh perangkat daerah." eyebrow="Monitoring & evaluasi"><x-slot:actions><x-ui.status-badge tone="blue" :label="'TA '.$tahunAnggaran" />@if(auth()->user()->isAdmin())<a href="{{ route('admin.master_opd.index') }}" class="st-btn st-btn-secondary"><i class="fa-solid fa-building" aria-hidden="true"></i> Master OPD</a>@endif</x-slot:actions></x-ui.page-heading>

    {{-- ========================================== --}}
    {{-- 2. SUMMARY KPI CARDS (MATCHES MOCKUP)      --}}
    {{-- ========================================== --}}
    <div class="ed-stats ed-stats-five">
 <x-ui.statistic-card label="Total OPD" :value="$kpi['total_opd']" icon="fa-building" note="Perangkat daerah terdaftar" />
 <x-ui.statistic-card label="Sudah Berpartisipasi" :value="$kpi['sudah_mengirim']" icon="fa-circle-check" tone="green" note="Telah mengajukan dokumen" />
 <x-ui.statistic-card label="Belum Berpartisipasi" :value="$kpi['belum_mengirim']" icon="fa-clock" tone="purple" note="Belum mengajukan dokumen" />
 <x-ui.statistic-card label="Perlu Revisi" :value="$kpi['perlu_revisi'] ?? 0" icon="fa-rotate-left" tone="red" note="Membutuhkan perbaikan" />
 <x-ui.statistic-card label="Disetujui" :value="$kpi['disetujui']" icon="fa-file-circle-check" tone="green" note="Dokumen selesai diperiksa" />
 </div>

    {{-- ========================================== --}}
    {{-- 3. FILTER BAR & SEARCH (MATCHES MOCKUP)    --}}
    {{-- ========================================== --}}
    <div class="st-card-v2 p-4 sm:p-5 space-y-4">
        <x-ui.search-filter method="GET" action="{{ route('admin.monitoring-opd.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            
            {{-- SEARCH INPUT --}}
            <div class="sm:col-span-5 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Cari OPD..." 
                       class="st-input text-xs pl-9 h-10 rounded-xl w-full">
            </div>

            {{-- FILTER TAHUN ANGGARAN --}}
            <div class="sm:col-span-3">
                <select name="tahun_anggaran" class="st-select text-xs h-10 rounded-xl font-bold w-full">
                    @foreach(range(2025, 2030) as $yOpt)
                        <option value="{{ $yOpt }}" {{ $tahunAnggaran == $yOpt ? 'selected' : '' }}>TA {{ $yOpt }}</option>
                    @endforeach
                </select>
            </div>

            {{-- FILTER STATUS --}}
            <div class="sm:col-span-2">
                <select name="status" class="st-select text-xs h-10 rounded-xl font-bold w-full">
                    <option value="all" {{ $statusFilter == 'all' ? 'selected' : '' }}>Status ▼</option>
                    <option value="belum_mengirim" {{ $statusFilter == 'belum_mengirim' ? 'selected' : '' }}>Belum Mulai</option>
                    <option value="sudah_mengirim" {{ $statusFilter == 'sudah_mengirim' ? 'selected' : '' }}>Mengirim</option>
                    <option value="sedang_diproses" {{ $statusFilter == 'sedang_diproses' ? 'selected' : '' }}>Diproses</option>
                    <option value="perlu_revisi" {{ $statusFilter == 'perlu_revisi' ? 'selected' : '' }}>Perlu Revisi</option>
                    <option value="disetujui" {{ $statusFilter == 'disetujui' ? 'selected' : '' }}>Selesai</option>
                </select>
            </div>

            {{-- SUBMIT & RESET BUTTONS --}}
            <div class="sm:col-span-2 flex space-x-1.5">
                <button type="submit" class="st-btn st-btn-primary st-btn-sm h-10 rounded-xl text-xs font-black flex-1 justify-center shadow-xs">
                    <i class="fa-solid fa-filter text-xs"></i>
                    <span>Filter</span>
                </button>
                
                @if(!empty($search) || ($tahunAnggaran && $tahunAnggaran != session('active_ta', date('Y'))) || ($statusFilter && $statusFilter !== 'all'))
                    <a href="{{ route('admin.monitoring-opd.index') }}" class="st-btn st-btn-secondary h-10 w-10 p-0 flex items-center justify-center shrink-0 rounded-xl" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                    </a>
                @endif
            </div>

        </x-ui.search-filter>
    </div>

    {{-- ========================================== --}}
    {{-- 4. DAFTAR MONITORING 71 OPD (TABLE LIST)  --}}
    {{-- ========================================== --}}
    <div class="st-card-v2 p-4 sm:p-6 space-y-4">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3.5">
            <div class="flex items-center space-x-2">
                <h3 class="text-sm font-black text-slate-900">Daftar Perangkat Daerah</h3>
                <span class="text-xs font-extrabold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">
                    Menampilkan {{ $opds->count() }} dari {{ $opds->total() }} OPD
                </span>
            </div>
            <div class="text-[11px] text-slate-500 font-bold">
                Siklus TA {{ $tahunAnggaran }}–{{ $tahunAnggaran + 1 }}
            </div>
        </div>

        {{-- TABLE DATA --}}
        <div class="st-table-wrapper rounded-2xl border border-slate-200 overflow-x-auto">
            <x-ui.data-table class="w-full text-xs text-left text-slate-700 border-collapse">
                <thead class="bg-slate-900 text-white uppercase text-[10px] font-black tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="p-3.5">OPD</th>
                        <th class="p-3.5 text-center">STATUS</th>
                        <th class="p-3.5 text-center">PROGRESS</th>
                        <th class="p-3.5 text-center">UPDATE TERAKHIR</th>
                        <th class="p-3.5 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($opds as $index => $opd)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            
                            {{-- NAMA & KODE PERANGKAT DAERAH --}}
                            <td class="p-3.5 font-bold text-slate-900 max-w-[280px]">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8.5 h-8.5 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-black text-xs border border-slate-200 shrink-0">
                                        <i class="fa-solid fa-landmark"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.monitoring-opd.show', ['opd' => $opd->id, 'tahun_anggaran' => $tahunAnggaran]) }}" 
                                           class="text-xs font-black text-slate-900 hover:text-amber-600 transition-colors truncate block" 
                                           title="{{ $opd->nama_opd }}">
                                            {{ $opd->nama_opd }}
                                        </a>
                                        <div class="text-[10px] text-slate-400 font-normal truncate">
                                            Kode: <strong class="font-bold text-slate-600">{{ $opd->kode_opd ?? '-' }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- STATUS BADGE --}}
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span class="{{ $opd->opd_badge_class }} px-3 py-1 rounded-full font-black text-[10px] uppercase border shadow-2xs">
                                    {{ $opd->opd_status_label }}
                                </span>
                            </td>

                            {{-- PROGRESS (X / 4 DOKUMEN) --}}
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span class="bg-slate-100 text-slate-800 border border-slate-200 px-3 py-1 rounded-full font-black text-xs">
                                    {{ $opd->submitted_count ?? 0 }} / 4
                                </span>
                            </td>

                            {{-- UPDATE TERAKHIR --}}
                            <td class="p-3.5 text-center text-slate-600 font-medium whitespace-nowrap text-[11px]">
                                @if($opd->last_update)
                                    <div class="font-bold text-slate-800">{{ $opd->last_update->format('d M Y, H:i') }}</div>
                                    <div class="text-[10px] text-slate-400 font-medium">{{ $opd->last_update->diffForHumans() }}</div>
                                @else
                                    <span class="text-slate-400 font-normal italic">—</span>
                                @endif
                            </td>

                            {{-- AKSI MONITORING --}}
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <a href="{{ route('admin.monitoring-opd.show', ['opd' => $opd->id, 'tahun_anggaran' => $tahunAnggaran]) }}" 
                                   class="st-btn bg-slate-900 hover:bg-slate-800 text-amber-400 hover:text-amber-300 text-[11px] h-7.5 px-3 rounded-xl font-extrabold shadow-2xs flex items-center gap-1.5 justify-center mx-auto"
                                   title="Lihat Detail Monitoring OPD">
                                    <i class="fa-solid fa-chart-line text-[10px]"></i>
                                    <span>Lihat Monitoring</span>
                                </a>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center bg-slate-50/50">
                                <div class="max-w-md mx-auto space-y-3">
                                    <div class="w-14 h-14 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center text-2xl mx-auto border border-slate-200">
                                        <i class="fa-solid fa-building-circle-xmark"></i>
                                    </div>
                                    <div class="space-y-1">
                                        <h4 class="text-sm font-black text-slate-900">Perangkat Daerah Tidak Ditemukan</h4>
                                        <p class="text-xs text-slate-500">
                                            Tidak ada data Perangkat Daerah yang sesuai dengan kata kunci pencarian atau filter status yang dipilih.
                                        </p>
                                    </div>
                                    <a href="{{ route('admin.monitoring-opd.index') }}" 
                                       class="st-btn st-btn-secondary text-xs font-bold px-4 py-2 rounded-xl inline-flex">
                                        Reset Filter Pencarian
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.data-table>
        </div>

        {{-- PAGINATION --}}
        <div class="pt-2">
            {{ $opds->links() }}
        </div>

    </div>

</div>
@endsection
