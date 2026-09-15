@extends('layouts.app')

@section('title', 'Executive Summary Dashboard Pimpinan')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- EXECUTIVE HEADER BANNER -->
    <div class="st-card-v2 p-6 bg-gradient-to-r from-amber-600 via-amber-700 to-slate-900 text-white rounded-2xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-lg">
        <div>
            <div class="inline-flex items-center space-x-2 bg-amber-400/20 border border-amber-300/40 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider text-amber-200 mb-2">
                <i class="fa-solid fa-crown"></i>
                <span>Executive Dashboard Pimpinan Daerah</span>
            </div>
            <h2 class="text-xl sm:text-3xl font-black text-white tracking-tight">Ringkasan Eksekutif Perencanaan Pembangunan</h2>
            <p class="text-xs text-amber-100/90 mt-1 max-w-2xl">Rekapitulasi progres penyusunan dokumen Rencana Kerja (Renja) dari 71 Perangkat Daerah se-Kabupaten Cirebon Tahun Anggaran 2027.</p>
        </div>
        
        <div class="bg-amber-950/50 backdrop-blur-xs p-3.5 rounded-2xl border border-amber-400/30 text-center shrink-0">
            <div class="text-[10px] font-black uppercase tracking-wider text-amber-300">Tahun Perencanaan</div>
            <div class="text-2xl font-black text-white">TA 2027</div>
        </div>
    </div>

    <!-- EXECUTIVE KPI CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="st-card-v2 p-5 border-l-4 border-l-emerald-600">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">🟢 Dokumen Disetujui (Sah)</span>
            <div class="text-3xl font-black text-slate-900 mt-1.5">{{ $stats['disetujui'] ?? 0 }}</div>
            <div class="text-[11px] text-emerald-600 font-bold mt-1">Siap Pelaksanaan APBD</div>
        </div>

        <div class="st-card-v2 p-5 border-l-4 border-l-blue-600">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">🔵 Menunggu Verifikasi</span>
            <div class="text-3xl font-black text-slate-900 mt-1.5">{{ $stats['menunggu'] ?? 0 }}</div>
            <div class="text-[11px] text-blue-600 font-bold mt-1">Proses Evaluasi Teknis</div>
        </div>

        <div class="st-card-v2 p-5 border-l-4 border-l-amber-500">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">🟡 Perlu Perbaikan OPD</span>
            <div class="text-3xl font-black text-slate-900 mt-1.5">{{ $stats['revisi'] ?? 0 }}</div>
            <div class="text-[11px] text-amber-600 font-bold mt-1">Dikembalikan ke SKPD</div>
        </div>

        <div class="st-card-v2 p-5 border-l-4 border-l-indigo-600">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">🏢 Partisipasi 71 OPD</span>
            <div class="text-3xl font-black text-slate-900 mt-1.5">{{ $opdStats['sudah_menyusun'] ?? 0 }} <span class="text-sm font-semibold text-slate-400">/ {{ $opdStats['total_opd'] ?? 71 }}</span></div>
            <div class="text-[11px] text-indigo-600 font-bold mt-1">{{ round((($opdStats['sudah_menyusun'] ?? 0) / max($opdStats['total_opd'] ?? 71, 1)) * 100) }}% OPD Telah Menyusun</div>
        </div>
    </div>

    <!-- PROGRES TOP OPD TABLE FOR PIMPINAN -->
    <div class="st-card-v2 p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-base font-black text-slate-900">Rekapitulasi Progres Perangkat Daerah (Top OPD)</h3>
                <p class="text-xs text-slate-500">Monitoring penyelesaian dokumen Rencana Kerja per dinas/badan</p>
            </div>
            <a href="{{ route('bapperida.monitoring') }}" class="text-xs font-bold text-blue-600 hover:underline">Lihat Laporan Lengkap 71 OPD →</a>
        </div>

        <div class="st-table-wrapper">
            <table class="w-full text-xs text-left text-slate-700 border-collapse">
                <thead class="bg-slate-50 text-slate-900 uppercase text-[10px] font-black tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-3.5">Perangkat Daerah</th>
                        <th class="p-3.5">Dokumen</th>
                        <th class="p-3.5 text-center">Progres Penyusunan</th>
                        <th class="p-3.5 text-center">Status Akhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($topOpds ?? [] as $top)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3.5 font-bold text-slate-900">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-900 flex items-center justify-center font-bold text-xs shrink-0 border border-amber-200">
                                        <i class="fa-solid fa-building"></i>
                                    </div>
                                    <span class="text-xs font-black text-slate-900">{{ $top->opd->nama_opd ?? 'OPD' }}</span>
                                </div>
                            </td>
                            <td class="p-3.5 font-semibold text-slate-700">
                                {{ $top->jenis_dokumen }} TA {{ $top->tahun_anggaran }}
                            </td>
                            <td class="p-3.5 text-center">
                                <div class="flex items-center space-x-2 w-36 mx-auto">
                                    <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $top->progress_percentage }}%"></div>
                                    </div>
                                    <span class="text-xs font-black text-slate-700">{{ $top->progress_percentage }}%</span>
                                </div>
                            </td>
                            <td class="p-3.5 text-center">
                                <span class="{{ $top->status_badge_class }} px-3 py-1 rounded-full font-black text-[10px] uppercase border">
                                    {{ $top->status_label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-400 text-xs font-semibold">
                                Belum ada data penyusunan dokumen Perangkat Daerah.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
