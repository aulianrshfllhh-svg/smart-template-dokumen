@extends('layouts.app')

@section('title', 'Dashboard Verifikasi Bapperida')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- FLASH NOTIFICATION -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center space-x-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- VERIFIKATOR SPECIFIC HEADER BANNER -->
    <div class="st-card-v2 p-5 sm:p-6 bg-gradient-to-r from-blue-900 to-slate-900 text-white rounded-2xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 bg-blue-500/20 border border-blue-400/30 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider text-blue-300 mb-2">
                <i class="fa-solid fa-clipboard-check"></i>
                <span>Portal Khusus Verifikator Bapperida</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">Antrean Pemeriksaan & Verifikasi Dokumen OPD</h2>
            <p class="text-xs text-slate-300 mt-1 max-w-2xl">Periksa keselarasan bab, indikator kinerja, dan beri catatan revisi atau persetujuan sah untuk dokumen Rencana Kerja (Renja) Perangkat Daerah.</p>
        </div>
    </div>

    <!-- METRICS FOR VERIFIKATOR -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="st-card-v2 p-4 border-l-4 border-l-rose-500">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">🔴 Menunggu Verifikasi</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $stats['menunggu'] ?? 0 }}</div>
            <span class="text-[10px] text-rose-600 font-bold">Perlu Tindakan Segera</span>
        </div>
        <div class="st-card-v2 p-4 border-l-4 border-l-amber-500">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">🟡 Sedang Direvisi OPD</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $stats['revisi'] ?? 0 }}</div>
            <span class="text-[10px] text-amber-600 font-bold">Dalam Perbaikan SKPD</span>
        </div>
        <div class="st-card-v2 p-4 border-l-4 border-l-emerald-600">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">🟢 Telah Disetujui</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $stats['disetujui'] ?? 0 }}</div>
            <span class="text-[10px] text-emerald-600 font-bold">Dokumen Sah</span>
        </div>
        <div class="st-card-v2 p-4 border-l-4 border-l-indigo-600">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider block">📁 Total Dokumen</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $stats['total'] ?? 0 }}</div>
            <span class="text-[10px] text-indigo-600 font-bold">Terdaftar di Sistem</span>
        </div>
    </div>

    <!-- ANTREAN VERIFIKASI TABLE -->
    <div class="st-card-v2 p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Daftar Antrean Pemeriksaan Verifikator</h3>
                <p class="text-xs text-slate-500">Klik tombol "Review & Keputusan" untuk memeriksa isi bab dan memberikan catatan revisi</p>
            </div>
        </div>

        <div class="st-table-wrapper">
            <table class="w-full text-xs text-left text-slate-700 border-collapse">
                <thead class="bg-slate-50 text-slate-900 uppercase text-[10px] font-black tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-3.5">Nama Perangkat Daerah</th>
                        <th class="p-3.5">Dokumen & Tahun</th>
                        <th class="p-3.5 text-center">Progres Bab</th>
                        <th class="p-3.5 text-center">Status</th>
                        <th class="p-3.5 text-center">Tindakan Verifikasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($documents as $doc)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3.5 font-bold text-slate-900">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-xl bg-slate-900 text-amber-400 flex items-center justify-center font-bold text-xs shrink-0">
                                        <i class="fa-solid fa-building"></i>
                                    </div>
                                    <div>
                                        <div class="text-xs font-black text-slate-900">{{ $doc->opd->nama_opd ?? 'OPD' }}</div>
                                        <div class="text-[10px] text-slate-400 font-medium">Update: {{ $doc->updated_at ? $doc->updated_at->diffForHumans() : '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5 font-semibold text-slate-800">
                                <div>{{ $doc->jenis_dokumen }}</div>
                                <div class="text-[10px] text-slate-400 font-bold">TA {{ $doc->tahun_anggaran }}</div>
                            </td>
                            <td class="p-3.5 text-center">
                                <div class="flex items-center space-x-2 w-28 mx-auto">
                                    <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ $doc->progress_percentage }}%"></div>
                                    </div>
                                    <span class="text-[10px] font-black text-slate-700">{{ $doc->progress_percentage }}%</span>
                                </div>
                            </td>
                            <td class="p-3.5 text-center">
                                <span class="{{ $doc->status_badge_class }} px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase border">
                                    {{ $doc->status_label }}
                                </span>
                            </td>
                            <td class="p-3.5 text-center">
                                <a href="{{ route('admin.review', $doc->id) }}" class="st-btn st-btn-primary st-btn-sm text-[11px] h-7.5 px-3 rounded-xl">
                                    <i class="fa-solid fa-clipboard-check"></i>
                                    <span>Review & Keputusan</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400 text-xs font-semibold">
                                Tidak ada antrean dokumen yang menunggu verifikasi saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
