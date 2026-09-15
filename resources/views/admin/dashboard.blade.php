@extends('layouts.app')

@section('title', 'Dashboard Monitoring & Verifikasi Bapperida')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- FLASH NOTIFICATION -->
    @if(session('success'))
        <div class="bg-emerald-50/90 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-2xl text-xs font-bold shadow-xs flex items-center space-x-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- HEADER SIKLUS AKTIF BADGE -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4.5 sm:p-5 rounded-2xl border border-slate-200 shadow-2xs">
        <div>
            <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">Dashboard Monitoring Bapperida</h1>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Pusat Pengendalian Evaluasi Teknis & Verifikasi Rencana Kerja Perangkat Daerah</p>
        </div>
        <div class="flex items-center space-x-2 self-start sm:self-auto shrink-0">
            <span class="inline-flex items-center space-x-1.5 bg-blue-50 text-blue-800 border border-blue-200 px-3.5 py-1.5 rounded-xl text-xs font-extrabold shadow-2xs">
                <i class="fa-solid fa-calendar-check text-blue-600"></i>
                <span>Siklus Aktif TA {{ $activeTa ?? session('active_ta', date('Y')) }}</span>
            </span>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 1. EXECUTIVE KPI CARDS (LEVEL 1 HIERARCHY) -->
    <!-- ========================================== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-5">
        
        <!-- CARD 1: DOKUMEN DISETUJUI -->
        <div class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-emerald-600 flex items-center justify-between min-h-[105px]">
            <div class="space-y-1 min-w-0 w-full">
                <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 whitespace-nowrap overflow-hidden">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block shrink-0"></span>
                    <span class="truncate">DOKUMEN DISETUJUI</span>
                </span>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $stats['disetujui'] ?? ($kpi['disetujui'] ?? 0) }}</div>
                <div class="text-[10px] sm:text-[11px] text-emerald-600 font-bold block truncate">
                    Disetujui & Final
                </div>
            </div>
        </div>

        <!-- CARD 2: MENUNGGU VERIFIKASI -->
        <div class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-blue-600 flex items-center justify-between min-h-[105px]">
            <div class="space-y-1 min-w-0 w-full">
                <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 whitespace-nowrap overflow-hidden">
                    <span class="w-2 h-2 rounded-full bg-blue-600 inline-block shrink-0"></span>
                    <span class="truncate">MENUNGGU VERIFIKASI</span>
                </span>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $stats['menunggu'] ?? ($kpi['menunggu'] ?? 0) }}</div>
                <div class="text-[10px] sm:text-[11px] text-blue-600 font-bold block truncate">
                    Proses Evaluasi Teknis
                </div>
            </div>
        </div>

        <!-- CARD 3: PERLU REVISI (CONSISTENT TERMINOLOGY) -->
        <div class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-amber-500 flex items-center justify-between min-h-[105px]">
            <div class="space-y-1 min-w-0 w-full">
                <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 whitespace-nowrap overflow-hidden">
                    <span class="w-2 h-2 rounded-full bg-amber-500 inline-block shrink-0"></span>
                    <span class="truncate">PERLU REVISI</span>
                </span>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $stats['revisi'] ?? ($kpi['revisi'] ?? 0) }}</div>
                <div class="text-[10px] sm:text-[11px] text-amber-600 font-bold block truncate">
                    Dikembalikan ke OPD
                </div>
            </div>
        </div>

        <!-- CARD 4: PARTISIPASI 71 OPD -->
        <div class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-indigo-600 flex items-center justify-between min-h-[105px]">
            <div class="space-y-1 min-w-0 w-full">
                <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 whitespace-nowrap overflow-hidden">
                    <i class="fa-solid fa-building text-indigo-500 text-xs shrink-0"></i>
                    <span class="truncate">PARTISIPASI 71 OPD</span>
                </span>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    {{ $opdStats['sudah_menyusun'] ?? 0 }} <span class="text-xs font-bold text-slate-400">/ {{ $opdStats['total_opd'] ?? 71 }}</span>
                </div>
                <div class="text-[10px] sm:text-[11px] text-indigo-600 font-bold block truncate">
                    {{ round((($opdStats['sudah_menyusun'] ?? 0) / max($opdStats['total_opd'] ?? 71, 1)) * 100) }}% OPD Telah Menyusun
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- 2. OPERATIONAL CORE WORKSPACE (GRID 8 vs 4 COLS) -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT COLUMN (8 COLS): TABEL ANTREAN UTAMA -->
        <div class="lg:col-span-8 st-card-v2 p-5 sm:p-6 space-y-4">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3.5">
                <div>
                    <h3 class="text-sm sm:text-base font-black text-slate-900 tracking-tight">Daftar Antrean Pengajuan Dokumen OPD</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Verifikasi dan tentukan keputusan status dokumen Rencana Kerja (Renja)</p>
                </div>
                <span class="text-[11px] font-black text-slate-700 bg-slate-100 px-3 py-1 rounded-xl self-start sm:self-auto shrink-0 border border-slate-200">
                    Total: {{ $documents->total() }} Dokumen
                </span>
            </div>

            <!-- LINEAR-STYLE QUICK COMMAND FILTER BAR -->
            <form action="{{ route('admin.dashboard') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                <div class="sm:col-span-5 relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama OPD..." class="st-input pl-8 text-xs h-9.5 rounded-xl">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 text-xs absolute left-3 top-3"></i>
                </div>
                <div class="sm:col-span-4">
                    <select name="status" class="st-select text-xs h-9.5 rounded-xl">
                        <option value="all" {{ $statusFilter == 'all' ? 'selected' : '' }}>Semua Status Dokumen</option>
                        <option value="menunggu_verifikasi" {{ in_array($statusFilter, ['menunggu_verifikasi', 'menunggu_pemeriksaan', 'submitted']) ? 'selected' : '' }}>Menunggu Verifikasi</option>
                        <option value="sedang_direview" {{ in_array($statusFilter, ['sedang_direview', 'sedang_diperiksa']) ? 'selected' : '' }}>Sedang Direview</option>
                        <option value="perlu_revisi" {{ in_array($statusFilter, ['perlu_revisi', 'revisi']) ? 'selected' : '' }}>Perlu Revisi</option>
                        <option value="disetujui" {{ in_array($statusFilter, ['disetujui', 'approved', 'final']) ? 'selected' : '' }}>Disetujui (Final)</option>
                        <option value="riwayat" {{ $statusFilter == 'riwayat' ? 'selected' : '' }}>Riwayat</option>
                    </select>
                </div>
                <div class="sm:col-span-3 flex space-x-1.5">
                    <button type="submit" class="st-btn st-btn-primary st-btn-sm flex-1 h-9.5 rounded-xl text-xs font-bold">
                        <i class="fa-solid fa-filter text-[11px]"></i>
                        <span>Filter</span>
                    </button>
                    @if($search || ($statusFilter && $statusFilter !== 'all') || ($jenisFilter && $jenisFilter !== 'all'))
                        <a href="{{ route('admin.dashboard') }}" class="st-btn st-btn-secondary h-9.5 w-9.5 p-0 flex items-center justify-center shrink-0 rounded-xl" title="Reset Filter">
                            <i class="fa-solid fa-rotate-left text-xs"></i>
                        </a>
                    @endif
                </div>
            </form>

            <!-- HIGH-CONTRAST DATA FEED TABLE -->
            <div class="st-table-wrapper rounded-2xl border border-slate-200 overflow-x-auto">
                <table class="w-full text-xs text-left text-slate-700 border-collapse">
                    <thead class="bg-slate-50 text-slate-900 uppercase text-[10px] font-black tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="p-3.5">Perangkat Daerah</th>
                            <th class="p-3.5">Dokumen & TA</th>
                            <th class="p-3.5 text-center">Progres</th>
                            <th class="p-3.5 text-center">Status</th>
                            <th class="p-3.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($documents as $doc)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <!-- OPD METADATA -->
                                <td class="p-3.5 font-bold text-slate-900">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-xl bg-slate-900 text-amber-400 flex items-center justify-center font-black text-xs shrink-0 shadow-xs">
                                            <i class="fa-solid fa-building text-xs"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-black text-slate-900 truncate max-w-[180px] sm:max-w-xs">
                                                {{ $doc->opd->nama_opd ?? 'OPD' }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-medium">
                                                Update: {{ $doc->updated_at ? $doc->updated_at->diffForHumans() : '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- DOKUMEN & TAHUN -->
                                <td class="p-3.5 font-semibold text-slate-800 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">{{ $doc->jenis_dokumen }}</div>
                                    <div class="text-[10px] text-slate-500 font-extrabold">TA {{ $doc->tahun_anggaran }}</div>
                                </td>

                                <!-- PROGRES BAB BAR -->
                                <td class="p-3.5 whitespace-nowrap min-w-[110px] text-center">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ $doc->progress_percentage }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-slate-700 shrink-0">{{ $doc->progress_percentage }}%</span>
                                    </div>
                                </td>

                                <!-- STATUS BADGE PILL -->
                                <td class="p-3.5 text-center whitespace-nowrap">
                                    <span class="{{ $doc->status_badge_class }} px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase inline-block border">
                                        {{ $doc->status_label }}
                                    </span>
                                </td>

                                <!-- AKSI REVIEW -->
                                <td class="p-3.5 text-center whitespace-nowrap">
                                    <a href="{{ route('admin.review', $doc->id) }}" 
                                       class="st-btn st-btn-primary st-btn-sm text-[11px] h-7.5 px-3 rounded-xl shadow-2xs font-bold"
                                       title="Review Dokumen">
                                        <i class="fa-solid fa-clipboard-check text-[10px]"></i>
                                        <span>Verifikasi</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400 text-xs">
                                    <i class="fa-solid fa-inbox text-3xl mb-2 text-slate-300 block"></i>
                                    <span class="font-bold">Tidak ada pengajuan dokumen yang sesuai dengan filter.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION LINKS -->
            <div class="pt-2 flex justify-end">
                {{ $documents->links() }}
            </div>

        </div>

        <!-- RIGHT COLUMN (4 COLS): MONITORING 71 OPD + PRIORITAS + AKSES CEPAT -->
        <div class="lg:col-span-4 space-y-5">
            
            <!-- PANEL RINGKASAN MONITORING 71 OPD (LEVEL 3 HIERARCHY) -->
            <div class="st-card-v2 p-4.5 sm:p-5 space-y-4 border-l-4 border-l-blue-600">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-xs font-black uppercase text-slate-800 tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-chart-pie text-emerald-600 text-xs"></i>
                            <span>Monitoring 71 OPD</span>
                        </h3>
                        <p class="text-[10px] text-slate-500 font-medium">Status progres penyusunan Perangkat Daerah</p>
                    </div>
                </div>

                <div class="space-y-2.5 text-xs">
                    <!-- TOTAL OPD -->
                    <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="font-bold text-slate-600 text-[11px]">Total Perangkat Daerah</span>
                        <span class="font-black text-slate-900 text-xs">{{ $opdStats['total_opd'] ?? 71 }} OPD</span>
                    </div>

                    <!-- SUDAH BERPARTISIPASI -->
                    <div class="flex items-center justify-between p-2.5 bg-emerald-50/70 rounded-xl border border-emerald-100">
                        <span class="font-bold text-emerald-900 text-[11px] flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Sudah Berpartisipasi</span>
                        </span>
                        <span class="font-black text-emerald-700 text-xs">{{ $opdStats['sudah_menyusun'] ?? 0 }} OPD</span>
                    </div>

                    <!-- BELUM BERPARTISIPASI -->
                    <div class="flex items-center justify-between p-2.5 bg-slate-100/70 rounded-xl border border-slate-200">
                        <span class="font-bold text-slate-700 text-[11px] flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                            <span>Belum Berpartisipasi</span>
                        </span>
                        <span class="font-black text-slate-700 text-xs">{{ $opdStats['belum_menyusun'] ?? 0 }} OPD</span>
                    </div>

                    <!-- PERLU REVISI -->
                    <div class="flex items-center justify-between p-2.5 bg-amber-50/70 rounded-xl border border-amber-100">
                        <span class="font-bold text-amber-900 text-[11px] flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>Perlu Revisi</span>
                        </span>
                        <span class="font-black text-amber-700 text-xs">{{ $stats['revisi'] ?? ($kpi['revisi'] ?? 0) }} OPD</span>
                    </div>
                </div>

                <!-- CTA LINK KE HALAMAN MONITORING DETIL -->
                <a href="{{ route('admin.monitoring-opd.index') }}" class="st-btn st-btn-primary w-full py-2.5 text-xs font-black rounded-xl flex items-center justify-center space-x-2 shadow-2xs">
                    <span>Lihat Monitoring OPD</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <!-- PRIORITAS VERIFIKASI UTAMA ALERT CARD -->
            @if(isset($pendingDoc) && $pendingDoc)
                <div class="st-card-v2 p-4.5 border border-rose-200 bg-rose-50/40 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black uppercase text-rose-800 tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation text-rose-600 text-xs"></i>
                            <span>Prioritas Verifikasi Utama</span>
                        </span>
                    </div>

                    <a href="{{ route('admin.review', $pendingDoc->id) }}" class="block p-3.5 bg-white hover:bg-rose-50/80 border border-rose-200 rounded-2xl transition group shadow-2xs">
                        <div class="text-xs font-black text-slate-900 truncate">
                            {{ $pendingDoc->opd->nama_opd ?? 'OPD' }}
                        </div>
                        <div class="text-[10px] text-slate-500 font-semibold mt-0.5">
                            {{ $pendingDoc->jenis_dokumen }} TA {{ $pendingDoc->tahun_anggaran }}
                        </div>
                        <div class="mt-2.5 flex items-center justify-between">
                            <span class="text-[9px] text-rose-700 font-extrabold">
                                Diajukan {{ $pendingDoc->updated_at ? $pendingDoc->updated_at->diffForHumans() : '-' }}
                            </span>
                            <span class="text-[10px] font-black text-rose-600 group-hover:translate-x-1 transition flex items-center gap-1">
                                <span>Verifikasi</span>
                                <i class="fa-solid fa-arrow-right text-[9px]"></i>
                            </span>
                        </div>
                    </a>
                </div>
            @endif

            <!-- GRID AKSES CEPAT ADMIN (2 UTAMA: VERIFIKASI & MONITORING) -->
            <div class="st-card-v2 p-4.5 sm:p-5 space-y-3">
                <h3 class="text-xs font-black uppercase text-slate-700 tracking-wider">Akses Cepat Admin</h3>

                <div class="grid grid-cols-2 gap-2.5">
                    <!-- 1. ANTREAN VERIFIKASI -->
                    <a href="{{ route('admin.verifikasi.index') }}" class="st-card-v2 p-3 flex flex-col items-center justify-center text-center space-y-1.5 hover:border-amber-400 transition group">
                        <div class="w-8.5 h-8.5 rounded-xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center text-xs group-hover:scale-110 transition">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                        <span class="text-[10px] font-extrabold text-slate-800 leading-tight">Antrean Verifikasi</span>
                    </a>

                    <!-- 2. MONITORING OPD -->
                    <a href="{{ route('admin.monitoring-opd.index') }}" class="st-card-v2 p-3 flex flex-col items-center justify-center text-center space-y-1.5 hover:border-amber-400 transition group">
                        <div class="w-8.5 h-8.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center text-xs group-hover:scale-110 transition">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <span class="text-[10px] font-extrabold text-slate-800 leading-tight">Monitoring OPD</span>
                    </a>
                </div>
            </div>

            <!-- RECENT ACTIVITY LOG -->
            @if(isset($recentActivities) && $recentActivities->count() > 0)
                <div class="st-card-v2 p-4.5 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="text-xs font-black uppercase text-slate-700 tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-clock-rotate-left text-blue-600 text-xs"></i>
                            <span>Aktivitas Terbaru</span>
                        </h3>
                    </div>

                    <div class="divide-y divide-slate-100 text-xs space-y-2">
                        @foreach($recentActivities->take(4) as $act)
                            <div class="pt-2 flex items-center justify-between">
                                <div class="min-w-0 pr-2">
                                    <div class="font-black text-slate-900 truncate text-[11px]">{{ $act->opd->nama_opd ?? 'OPD' }}</div>
                                    <div class="text-[10px] text-slate-500 font-medium truncate">{{ $act->jenis_dokumen }} ({{ $act->tahun_anggaran }})</div>
                                </div>
                                <span class="text-[9px] text-slate-400 font-semibold shrink-0">
                                    {{ $act->updated_at ? $act->updated_at->diffForHumans() : '-' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

    </div>

</div>
@endsection
