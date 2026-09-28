@extends('layouts.app')

@section('title', 'Executive Summary Dashboard Pimpinan')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- EXECUTIVE HEADER BANNER -->
    <x-ui.page-heading title="Ringkasan Eksekutif" description="Pantau penyelesaian dokumen perencanaan perangkat daerah Kabupaten Cirebon." eyebrow="Dashboard Pimpinan"><x-slot:actions><x-ui.status-badge tone="blue" :label="'TA '.session('active_ta', date('Y'))" /></x-slot:actions></x-ui.page-heading>

    <!-- EXECUTIVE KPI CARDS -->
    <div class="ed-stats">
<x-ui.statistic-card label="Dokumen Disetujui" :value="$stats['disetujui'] ?? 0" tone="green" note="Selesai diverifikasi" />
<x-ui.statistic-card label="Menunggu Verifikasi" :value="$stats['menunggu'] ?? 0" tone="purple" note="Proses evaluasi teknis" />
<x-ui.statistic-card label="Perlu Revisi" :value="$stats['revisi'] ?? 0" tone="red" note="Membutuhkan perbaikan" />
<x-ui.statistic-card label="Partisipasi OPD" :value="($opdStats['sudah_menyusun'] ?? 0).' / '.($opdStats['total_opd'] ?? 0)" note="Perangkat daerah berpartisipasi" />
</div>
@include('components.ui.dashboard-charts', ['chartStats' => $stats, 'participation' => $opdStats])

    <!-- PROGRES TOP OPD TABLE FOR PIMPINAN -->
    <div class="st-card-v2 p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-base font-black text-slate-900">Rekapitulasi Progres Perangkat Daerah (Top OPD)</h3>
                <p class="text-xs text-slate-500">Monitoring penyelesaian dokumen Rencana Kerja per dinas/badan</p>
            </div>
        </div>

        <div class="st-table-wrapper">
            <x-ui.data-table class="w-full text-xs text-left text-slate-700 border-collapse">
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
            </x-ui.data-table>
        </div>
    </div>

</div>
@endsection
