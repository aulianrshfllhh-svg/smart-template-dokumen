@extends('layouts.app')

@section('title', 'Monitoring Matrix 71 Perangkat Daerah')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Title & Subtitle Header -->
    <div class="st-card p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 bg-amber-500/10 text-amber-900 border border-amber-500/30 px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider mb-2">
                <i class="fa-solid fa-shield-halved text-amber-600"></i>
                <span>MONITORING MATRIX BAPPERIDA</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Pengawasan Status 71 Perangkat Daerah / SKPD</h1>
            <p class="text-slate-500 text-xs mt-1">Pantau progres penyusunan dan kepatuhan pengisian Renja dari seluruh Perangkat Daerah Kabupaten Cirebon.</p>
        </div>

        <a href="{{ route('admin.verifikasi.index') }}" class="st-btn st-btn-primary st-btn-sm self-start md:self-auto">
            <i class="fa-solid fa-list-check text-xs"></i>
            <span>Workspace Verifikasi Admin</span>
        </a>
    </div>

    <!-- KPI STAT CARDS V2 -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="st-card p-4 space-y-1">
            <div class="text-slate-400 font-bold uppercase text-[9px] tracking-wider">TOTAL SKPD</div>
            <div class="text-xl font-black text-slate-900">{{ $stats['total_opd'] }}</div>
        </div>
        <div class="st-card p-4 space-y-1">
            <div class="text-slate-400 font-bold uppercase text-[9px] tracking-wider">DRAFT</div>
            <div class="text-xl font-black text-slate-600">{{ $stats['draft'] }}</div>
        </div>
        <div class="st-card p-4 space-y-1">
            <div class="text-slate-400 font-bold uppercase text-[9px] tracking-wider">SUBMITTED</div>
            <div class="text-xl font-black text-amber-600">{{ $stats['submitted'] }}</div>
        </div>
        <div class="st-card p-4 space-y-1">
            <div class="text-slate-400 font-bold uppercase text-[9px] tracking-wider">UNDER REVIEW</div>
            <div class="text-xl font-black text-indigo-600">{{ $stats['under_review'] }}</div>
        </div>
        <div class="st-card p-4 space-y-1">
            <div class="text-slate-400 font-bold uppercase text-[9px] tracking-wider">APPROVED</div>
            <div class="text-xl font-black text-emerald-600">{{ $stats['approved'] }}</div>
        </div>
        <div class="st-card p-4 space-y-1 bg-amber-500/10 border border-amber-500/30">
            <div class="text-amber-800 font-black uppercase text-[9px] tracking-wider">% PROGRESS KAB</div>
            <div class="text-xl font-black text-amber-900">{{ $stats['completion_rate'] }}%</div>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="st-card p-4">
        <form action="{{ route('bapperida.monitoring') }}" method="GET" class="flex flex-col md:flex-row items-center justify-between gap-3">
            <div class="flex items-center space-x-3 w-full md:w-auto">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase">Tahun Anggaran</label>
                    <select name="tahun_anggaran" onchange="this.form.submit()" class="st-select text-xs h-9 rounded-xl font-bold">
                        @foreach(range(2025, 2035) as $yOpt)
                            <option value="{{ $yOpt }}" {{ $tahunAnggaran == $yOpt ? 'selected' : '' }}>TA {{ $yOpt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="w-full md:w-72">
                <label class="block text-[10px] font-bold text-slate-500 uppercase">Cari Perangkat Daerah</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nama OPD / Kode..." class="st-input h-9 text-xs rounded-xl pr-8">
                    <button type="submit" class="absolute right-2.5 top-2.5 text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- OPD MONITORING MATRIX TABLE -->
    <div class="st-card p-5 sm:p-6 space-y-4">
        <div class="st-table-wrapper">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-900 uppercase font-extrabold text-[10px] tracking-wider border-b border-slate-200">
                        <th class="p-3.5">NO</th>
                        <th class="p-3.5">PERANGKAT DAERAH (OPD)</th>
                        <th class="p-3.5">LAMPIRAN ROMAWI</th>
                        <th class="p-3.5 text-center">STATUS TA {{ $tahunAnggaran }}</th>
                        <th class="p-3.5 text-center">PROGRESS %</th>
                        <th class="p-3.5 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($opds as $index => $opd)
                        @php
                            $docYear = $opd->documents->first();
                            $status = $docYear ? $docYear->status : 'belum_dikerjakan';
                            $stEnum = \App\Enums\DocumentStatus::tryFrom($status);
                            $badgeClass = $stEnum ? $stEnum->badgeClass() : 'bg-slate-100 text-slate-800';
                            $statusLabel = $stEnum ? $stEnum->label() : strtoupper($status);
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-3.5 font-black text-slate-400 text-[11px]">{{ $opds->firstItem() + $index }}</td>
                            <td class="p-3.5 font-bold text-slate-900">
                                <div>{{ $opd->nama_opd }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $opd->kode_opd }}</div>
                            </td>
                            <td class="p-3.5 font-bold text-slate-700">
                                <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded text-[10px] font-mono">
                                    {{ $opd->nomor_lampiran_romawi ?? 'LAMPIRAN I' }}
                                </span>
                            </td>
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span class="{{ $badgeClass }} px-2.5 py-0.5 rounded-full font-black text-[10px] uppercase border shadow-2xs">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span class="font-bold text-slate-900">{{ $docYear ? $docYear->progress_percentage : 0 }}%</span>
                            </td>
                            <td class="p-3.5 text-right whitespace-nowrap space-x-1">
                                @if($docYear)
                                    <a href="{{ route('admin.verifikasi.review', $docYear->id) }}" class="st-btn st-btn-primary st-btn-sm text-[10px] py-1 px-2">
                                        <i class="fa-solid fa-magnifying-glass text-[10px]"></i> Review
                                    </a>
                                    <a href="{{ route('renja.exportPdf', $docYear->id) }}" target="_blank" class="st-btn st-btn-secondary st-btn-sm text-[10px] py-1 px-2" title="Cetak PDF Folio F4">
                                        <i class="fa-solid fa-file-pdf text-rose-600 text-[10px]"></i> PDF
                                    </a>
                                    <a href="{{ route('renja.exportWord', $docYear->id) }}" class="st-btn st-btn-secondary st-btn-sm text-[10px] py-1 px-2" title="Export Word .docx">
                                        <i class="fa-solid fa-file-word text-blue-600 text-[10px]"></i> Word
                                    </a>
                                @else
                                    <span class="text-[10px] text-slate-400 italic">Belum Menyusun</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 text-xs">Tidak ditemukan data Perangkat Daerah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($opds->hasPages())
            <div class="pt-2">
                {{ $opds->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
