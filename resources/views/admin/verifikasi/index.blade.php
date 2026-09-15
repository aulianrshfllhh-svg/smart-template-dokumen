@extends('layouts.app')

@section('title', 'Workspace Verifikasi Dokumen Bapperida')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ loading: false }">

    <!-- FLASH NOTIFICATION -->
    @if(session('success'))
        <div class="bg-emerald-50/90 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-2xl text-xs font-bold shadow-xs flex items-center space-x-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- 1. WORKSPACE HEADER BANNER & QUICK STATS -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-l-4 border-l-amber-500">
        <div>
            <div class="inline-flex items-center space-x-2 bg-amber-500/10 text-amber-900 border border-amber-500/30 px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider mb-2">
                <i class="fa-solid fa-briefcase text-xs"></i>
                <span>WORKSPACE VERIFIKASI ADMIN BAPPERIDA</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Ruang Kerja Verifikasi Dokumen Daerah
            </h1>
            <p class="text-xs text-slate-500 font-medium mt-1 max-w-3xl">
                Proses pengajuan dokumen Rencana Kerja (Renja) dari 71 Perangkat Daerah se-Kabupaten Cirebon. Tinjau kelayakan bab F4, berikan catatan revisi, dan terbitkan persetujuan sah.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <!-- QUICK STATS WIDGET (LANGKAH 3D) -->
            <div class="hidden sm:flex items-center space-x-2 bg-slate-100 p-1.5 rounded-xl border border-slate-200 text-[11px] font-bold">
                <span class="px-2 py-0.5 bg-white rounded-lg text-slate-800 border border-slate-200/80 shadow-2xs" title="Aktivitas Hari Ini">
                    <i class="fa-solid fa-calendar-day text-amber-500 mr-1 text-[10px]"></i>Hari ini: <strong class="text-slate-900">{{ $quickStats['today'] ?? 0 }}</strong>
                </span>
                <span class="px-2 py-0.5 bg-white rounded-lg text-slate-800 border border-slate-200/80 shadow-2xs" title="Aktivitas Minggu Ini">
                    <i class="fa-solid fa-calendar-week text-blue-500 mr-1 text-[10px]"></i>Minggu ini: <strong class="text-slate-900">{{ $quickStats['this_week'] ?? 0 }}</strong>
                </span>
            </div>

            <!-- REFRESH WORKSPACE BUTTON (LANGKAH 3E) -->
            <button @click="loading = true; window.location.reload()" 
                    class="st-btn st-btn-secondary st-btn-sm text-xs rounded-xl font-bold px-3 py-2 flex items-center space-x-1.5 shadow-xs" 
                    title="Refresh Data Workspace (Pertahankan Filter)">
                <i class="fa-solid fa-rotate text-xs text-slate-600" :class="loading ? 'animate-spin text-amber-500' : ''"></i>
                <span>Refresh Data</span>
            </button>

            <div class="st-pill-v2 bg-slate-900 text-white font-mono text-[11px] px-3.5 py-2 rounded-xl border border-slate-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>TA 2027 • AKTIF</span>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. EXECUTIVE KPI CARDS -->
    <!-- ========================================== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-5">
        
        <!-- CARD 1: MENUNGGU VERIFIKASI -->
        <a href="{{ route('admin.verifikasi.index', ['status' => 'menunggu_verifikasi']) }}" 
           class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-blue-600 flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1 min-w-0">
                <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 truncate">
                    <span class="w-2 h-2 rounded-full bg-blue-600 inline-block animate-ping shrink-0"></span>
                    <span>MENUNGGU VERIFIKASI</span>
                </span>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $kpi['menunggu'] ?? 0 }}</div>
                <div class="text-[10px] sm:text-[11px] text-blue-600 font-bold block truncate">
                    Antrean Masuk Perlu Review
                </div>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-black border border-blue-100 shrink-0">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </a>

        <!-- CARD 2: SEDANG DIREVIEW -->
        <a href="{{ route('admin.verifikasi.index', ['status' => 'sedang_direview']) }}" 
           class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-purple-600 flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1 min-w-0">
                <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 truncate">
                    <span class="w-2 h-2 rounded-full bg-purple-600 inline-block shrink-0"></span>
                    <span>SEDANG DIREVIEW</span>
                </span>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $kpi['direview'] ?? 0 }}</div>
                <div class="text-[10px] sm:text-[11px] text-purple-600 font-bold block truncate">
                    Pemeriksaan Bab Berlangsung
                </div>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg font-black border border-purple-100 shrink-0">
                <i class="fa-solid fa-magnifying-glass-chart"></i>
            </div>
        </a>

        <!-- CARD 3: PERLU REVISI OPD -->
        <a href="{{ route('admin.verifikasi.index', ['status' => 'perlu_revisi']) }}" 
           class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-amber-500 flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1 min-w-0">
                <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 truncate">
                    <span class="w-2 h-2 rounded-full bg-amber-500 inline-block shrink-0"></span>
                    <span>PERLU REVISI OPD</span>
                </span>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $kpi['revisi'] ?? 0 }}</div>
                <div class="text-[10px] sm:text-[11px] text-amber-600 font-bold block truncate">
                    Dikembalikan ke SKPD
                </div>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-black border border-amber-100 shrink-0">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </a>

        <!-- CARD 4: DISETUJUI (FINAL) -->
        <a href="{{ route('admin.verifikasi.index', ['status' => 'disetujui']) }}" 
           class="st-card-v2 p-4 sm:p-5 border-l-4 border-l-emerald-600 flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1 min-w-0">
                <span class="text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 truncate">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block shrink-0"></span>
                    <span>DISETUJUI (FINAL)</span>
                </span>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $kpi['disetujui'] ?? 0 }}</div>
                <div class="text-[10px] sm:text-[11px] text-emerald-600 font-bold block truncate">
                    Dokumen Sah & Dikunci
                </div>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-black border border-emerald-100 shrink-0">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </a>

    </div>

    <!-- ========================================== -->
    <!-- 3. PRIORITY VERIFICATION PANEL (LANGKAH 3A ENHANCED) -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 space-y-4 bg-slate-900 text-white rounded-3xl border border-slate-800 shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3.5">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-black text-sm">
                    <i class="fa-solid fa-fire text-amber-400"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black tracking-tight text-white">Priority Verification Panel</h3>
                    <p class="text-[11px] text-slate-400 font-medium">Top 5 Dokumen Prioritas Urgensi (Indikator: Lama Menunggu, Priority Score, Jumlah Revisi, & Assigned Verifikator)</p>
                </div>
            </div>
            <span class="text-[10px] font-mono text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded-full border border-amber-500/20">
                AUTO-PRIORITY SCORING
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            @forelse($priorityDocs as $pDoc)
                <div class="bg-slate-800/80 hover:bg-slate-800 border border-slate-700/80 rounded-2xl p-3.5 space-y-3 flex flex-col justify-between transition">
                    <div class="space-y-2">
                        <!-- SKOR & LAMA MENUNGGU -->
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full {{ $pDoc->priority_score >= 75 ? 'bg-rose-500/20 text-rose-300 border border-rose-500/40' : 'bg-amber-500/20 text-amber-300 border border-amber-500/40' }}">
                                Skor: {{ $pDoc->priority_score }}
                            </span>
                            <span class="text-[10px] text-amber-400 font-bold flex items-center gap-1" title="Lama Menunggu">
                                <i class="fa-solid fa-hourglass-half text-[9px]"></i>
                                {{ $pDoc->submitted_at ? $pDoc->submitted_at->diffForHumans(null, true) : $pDoc->created_at->diffForHumans(null, true) }}
                            </span>
                        </div>

                        <!-- OPD & DOKUMEN -->
                        <div>
                            <div class="text-xs font-black text-white truncate" title="{{ $pDoc->opd->nama_opd ?? 'OPD' }}">
                                {{ $pDoc->opd->nama_opd ?? 'SKPD' }}
                            </div>
                            <div class="text-[10px] text-slate-400 truncate">
                                {{ $pDoc->jenis_dokumen }} (TA {{ $pDoc->tahun_anggaran }})
                            </div>
                        </div>

                        <!-- REVISI & VERIFIKATOR (FITUR 3A) -->
                        <div class="pt-1 border-t border-slate-700/60 flex items-center justify-between text-[10px] text-slate-400">
                            <span class="font-bold flex items-center gap-1" title="Jumlah Revisi">
                                <i class="fa-solid fa-rotate-left text-amber-400 text-[9px]"></i>
                                Revisi: {{ $pDoc->revision_count ?? 0 }}x
                            </span>
                            <span class="truncate max-w-[100px] text-slate-300 font-bold" title="Verifikator Ditugaskan">
                                {{ $pDoc->assignedVerificator->name ?? 'Belum ada' }}
                            </span>
                        </div>
                    </div>

                    <a href="{{ route('admin.verifikasi.review', $pDoc->id) }}" 
                       class="st-btn st-btn-amber st-btn-sm w-full text-[11px] py-1.5 rounded-xl font-bold justify-center shadow-md">
                        <i class="fa-solid fa-clipboard-check text-[10px]"></i>
                        <span>Review Sekarang</span>
                    </a>
                </div>
            @empty
                <div class="col-span-5 text-center py-6 text-slate-400 text-xs font-medium">
                    <i class="fa-solid fa-circle-check text-emerald-400 text-lg mb-1 block"></i>
                    <span>Tidak ada antrean dokumen prioritas tinggi saat ini. Seluruh pengajuan dalam kondisi bersih.</span>
                </div>
            @endforelse
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 4. WORKSPACE CORE WORKTABLE (8 vs 4 COLS) -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT COLUMN (8 COLS): WORKTABLE UTAMA -->
        <div class="lg:col-span-8 st-card-v2 p-5 sm:p-6 space-y-4">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3.5">
                <div>
                    <h3 class="text-sm font-black text-slate-900 tracking-tight">Tabel Pekerjaan Verifikasi Dokumen</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Lakukan pengawasan, tinjau PR-style per bab, dan tentukan keputusan status</p>
                </div>
                <span class="text-[11px] font-black text-slate-700 bg-slate-100 px-3 py-1 rounded-xl shrink-0 border border-slate-200">
                    Total: {{ $documents->total() }} Dokumen
                </span>
            </div>

            <!-- FILTER COMMAND BAR (LANGKAH 3C & SORTING 3B) -->
            <form action="{{ route('admin.verifikasi.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                <div class="sm:col-span-3 relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama OPD..." class="st-input pl-8 text-xs h-9.5 rounded-xl">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 text-xs absolute left-3 top-3"></i>
                </div>
                <div class="sm:col-span-2">
                    <select name="jenis_dokumen" class="st-select text-xs h-9.5 rounded-xl">
                        <option value="all">Jenis Dokumen</option>
                        <option value="Renja" {{ $jenisFilter == 'Renja' ? 'selected' : '' }}>Renja</option>
                        <option value="RKPD" {{ $jenisFilter == 'RKPD' ? 'selected' : '' }}>RKPD</option>
                        <option value="Renstra" {{ $jenisFilter == 'Renstra' ? 'selected' : '' }}>Renstra</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <select name="tahun_anggaran" class="st-select text-xs h-9.5 rounded-xl font-bold">
                        <option value="all">Semua TA</option>
                        @foreach(range(2025, 2035) as $yF)
                            <option value="{{ $yF }}" {{ ($tahunFilter ?? session('active_ta', 2027)) == $yF ? 'selected' : '' }}>TA {{ $yF }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <select name="status" class="st-select text-xs h-9.5 rounded-xl">
                        <option value="all" {{ $statusFilter == 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="menunggu_verifikasi" {{ in_array($statusFilter, ['menunggu_verifikasi', 'menunggu_pemeriksaan', 'submitted']) ? 'selected' : '' }}>Menunggu Verifikasi</option>
                        <option value="sedang_direview" {{ in_array($statusFilter, ['sedang_direview', 'sedang_diperiksa']) ? 'selected' : '' }}>Sedang Direview</option>
                        <option value="perlu_revisi" {{ in_array($statusFilter, ['perlu_revisi', 'revisi']) ? 'selected' : '' }}>Perlu Revisi</option>
                        <option value="disetujui" {{ in_array($statusFilter, ['disetujui', 'approved', 'final']) ? 'selected' : '' }}>Disetujui (Final)</option>
                        <option value="riwayat" {{ $statusFilter == 'riwayat' ? 'selected' : '' }}>Riwayat</option>
                    </select>
                </div>
                <div class="sm:col-span-3 flex space-x-1.5">
                    <!-- SORT COLUMN SELECT (LANGKAH 3B) -->
                    <select name="sort" class="st-select text-xs h-9.5 rounded-xl font-bold bg-slate-50">
                        <option value="priority_desc" {{ ($sort ?? '') == 'priority_desc' ? 'selected' : '' }}>Prioritas High</option>
                        <option value="updated_desc" {{ ($sort ?? '') == 'updated_desc' ? 'selected' : '' }}>Terbaru</option>
                        <option value="updated_asc" {{ ($sort ?? '') == 'updated_asc' ? 'selected' : '' }}>Terlama</option>
                    </select>

                    <button type="submit" class="st-btn st-btn-primary st-btn-sm h-9.5 rounded-xl text-xs font-bold px-3">
                        <i class="fa-solid fa-filter text-[11px]"></i>
                    </button>
                    @if($search || ($statusFilter && $statusFilter !== 'all') || ($jenisFilter && $jenisFilter !== 'all') || ($tahunFilter && $tahunFilter !== 'all'))
                        <a href="{{ route('admin.verifikasi.index') }}" class="st-btn st-btn-secondary h-9.5 w-9.5 p-0 flex items-center justify-center shrink-0 rounded-xl" title="Reset Filter">
                            <i class="fa-solid fa-rotate-left text-xs"></i>
                        </a>
                    @endif
                </div>
            </form>

            <!-- WORKTABLE DATA FEED (WITH STICKY HEADER & LOADING SKELETON) -->
            <div class="st-table-wrapper rounded-2xl border border-slate-200 max-h-[600px] overflow-y-auto relative">
                
                <!-- LOADING SKELETON STATE (LANGKAH 3B) -->
                <div x-show="loading" class="absolute inset-0 bg-white/80 backdrop-blur-2xs z-20 flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-circle-notch animate-spin text-amber-500 text-xl"></i>
                    <span class="text-xs font-bold text-slate-700">Memuat data workspace...</span>
                </div>

                <table class="w-full text-xs text-left text-slate-700 border-collapse">
                    <thead class="bg-slate-50 text-slate-900 uppercase text-[10px] font-black tracking-wider border-b border-slate-200 sticky top-0 z-10 shadow-2xs">
                        <tr>
                            <th class="p-3.5">Perangkat Daerah</th>
                            <th class="p-3.5">Dokumen & TA</th>
                            <th class="p-3.5 text-center">Progress</th>
                            <th class="p-3.5">Updated By & Time</th>
                            <th class="p-3.5 text-center">Status</th>
                            <th class="p-3.5 text-center">Aksi Work</th>
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
                                                Verifikator: <span class="font-bold text-slate-700">{{ $doc->assignedVerificator->name ?? 'Belum Ditugaskan' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- DOKUMEN & TAHUN -->
                                <td class="p-3.5 font-semibold text-slate-800 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">{{ $doc->jenis_dokumen }}</div>
                                    <div class="text-[10px] text-slate-500 font-black">TA {{ $doc->tahun_anggaran }}</div>
                                </td>

                                <!-- PROGRESS BAR -->
                                <td class="p-3.5 whitespace-nowrap min-w-[100px] text-center">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ $doc->progress_percentage }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-slate-700 shrink-0">{{ $doc->progress_percentage }}%</span>
                                    </div>
                                </td>

                                <!-- UPDATED BY & LAST UPDATED -->
                                <td class="p-3.5 text-slate-600 text-[11px] whitespace-nowrap">
                                    <div class="font-bold text-slate-900 flex items-center gap-1">
                                        <i class="fa-solid fa-user-pen text-[10px] text-slate-400"></i>
                                        <span>{{ $doc->updatedByUser->name ?? 'Operator SKPD' }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-medium flex items-center gap-1">
                                        <i class="fa-solid fa-clock text-[10px]"></i>
                                        <span>{{ $doc->updated_at ? $doc->updated_at->diffForHumans() : '-' }}</span>
                                    </div>
                                </td>

                                <!-- STATUS BADGE -->
                                <td class="p-3.5 text-center whitespace-nowrap">
                                    @php
                                        $statusEnum = \App\Enums\DocumentStatus::tryFrom($doc->status);
                                    @endphp
                                    <span class="{{ $statusEnum ? $statusEnum->badgeClass() : 'bg-slate-100 text-slate-800 border-slate-300' }} px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase inline-block border">
                                        {{ $statusEnum ? $statusEnum->label() : strtoupper($doc->status) }}
                                    </span>
                                </td>

                                <!-- AKSI REVIEW -->
                                <td class="p-3.5 text-center whitespace-nowrap">
                                    <a href="{{ route('admin.verifikasi.review', $doc->id) }}" 
                                       class="st-btn st-btn-primary st-btn-sm text-[11px] h-7.5 px-3 rounded-xl shadow-2xs font-bold"
                                       title="Buka PR-Style Review">
                                        <i class="fa-solid fa-clipboard-check text-[10px]"></i>
                                        <span>Review</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <!-- NO RESULT / EMPTY STATE (LANGKAH 3B) -->
                            <tr>
                                <td colspan="6" class="p-10 text-center text-slate-500 font-medium space-y-2">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-xl">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </div>
                                    <div class="font-bold text-slate-800 text-xs">Tidak Ada Dokumen Ditemukan</div>
                                    <div class="text-[11px] text-slate-400 max-w-sm mx-auto">
                                        Tidak ada antrean pengajuan yang sesuai dengan kriteria filter yang Anda pilih. Coba sesuaikan kata kunci pencarian atau reset filter.
                                    </div>
                                    @if($search || ($statusFilter && $statusFilter !== 'all'))
                                        <a href="{{ route('admin.verifikasi.index') }}" class="st-btn st-btn-secondary st-btn-sm inline-flex rounded-xl font-bold mt-2 text-xs">
                                            <i class="fa-solid fa-rotate-left mr-1.5 text-xs"></i> Reset Semua Filter
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- BETTER PAGINATION (LANGKAH 3B) -->
            <div class="pt-2">
                {{ $documents->links() }}
            </div>

        </div>

        <!-- RIGHT COLUMN (4 COLS): AUDIT LOG ACTIVITIES -->
        <div class="lg:col-span-4 space-y-6">
            
            <div class="st-card-v2 p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-clock-rotate-left text-amber-500 text-sm"></i>
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Verification Audit Feed</h3>
                    </div>
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                </div>

                <div class="space-y-3">
                    @forelse($recentLogs as $log)
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 space-y-1.5 text-xs">
                            <div class="flex items-center justify-between text-[10px] text-slate-400">
                                <span class="font-bold text-slate-700 truncate max-w-[140px]">{{ $log['opd_nama'] }}</span>
                                <span>{{ \Carbon\Carbon::parse($log['timestamp'])->diffForHumans() }}</span>
                            </div>
                            <div class="font-black text-slate-900 text-[11px]">
                                {{ $log['action'] }}
                            </div>
                            @if(!empty($log['notes']))
                                <p class="text-[11px] text-slate-600 line-clamp-2 italic">
                                    "{{ $log['notes'] }}"
                                </p>
                            @endif
                            <div class="text-[10px] text-slate-500 font-bold flex items-center gap-1">
                                <i class="fa-solid fa-user-check text-[9px] text-blue-500"></i>
                                <span>{{ $log['user_name'] }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-400 text-xs font-medium">
                            <span>Belum ada rekaman log verifikasi terbaru.</span>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
