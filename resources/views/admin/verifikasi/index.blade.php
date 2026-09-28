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
    <x-ui.page-heading title="Verifikasi Dokumen" description="Periksa dokumen masuk, berikan catatan revisi, dan pantau keputusan verifikasi." eyebrow="Pengawasan dokumen"><x-slot:actions><x-ui.status-badge tone="blue" :label="'TA '.session('active_ta', date('Y'))" /><button type="button" @click="loading = true; window.location.reload()" class="st-btn st-btn-secondary"><i class="fa-solid fa-rotate" :class="{ 'animate-spin': loading }" aria-hidden="true"></i> Muat Ulang</button></x-slot:actions></x-ui.page-heading>

    <!-- ========================================== -->
    <!-- 2. EXECUTIVE KPI CARDS -->
    <!-- ========================================== -->
    <div class="ed-stats">
 <x-ui.statistic-card label="Menunggu Verifikasi" :value="$kpi['menunggu'] ?? 0" icon="fa-inbox" note="Antrean masuk perlu review" :href="route('admin.verifikasi.index', ['status' => 'menunggu_verifikasi'])" />
 <x-ui.statistic-card label="Sedang Direview" :value="$kpi['direview'] ?? 0" icon="fa-magnifying-glass" tone="purple" note="Pemeriksaan sedang berlangsung" :href="route('admin.verifikasi.index', ['status' => 'sedang_direview'])" />
 <x-ui.statistic-card label="Perlu Revisi" :value="$kpi['revisi'] ?? 0" icon="fa-rotate-left" tone="red" note="Dikembalikan ke OPD" :href="route('admin.verifikasi.index', ['status' => 'perlu_revisi'])" />
 <x-ui.statistic-card label="Disetujui" :value="$kpi['disetujui'] ?? 0" icon="fa-circle-check" tone="green" note="Dokumen sah dan dikunci" :href="route('admin.verifikasi.index', ['status' => 'disetujui'])" />
 </div>

    <!-- ========================================== -->
    <!-- 3. PRIORITY VERIFICATION PANEL (LANGKAH 3A ENHANCED) -->
    <!-- ========================================== -->
    <x-ui.priority-documents :documents="$priorityDocs" />

    <!-- ========================================== -->
    <!-- 4. WORKSPACE CORE WORKTABLE (8 vs 4 COLS) -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT COLUMN (8 COLS): WORKTABLE UTAMA -->
        <div class="lg:col-span-8 st-card-v2 p-5 sm:p-6 space-y-4">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3.5">
                <div>
                    <h3 class="text-sm font-black text-slate-900 tracking-tight">Tabel Pekerjaan Verifikasi Dokumen</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Tinjau setiap bagian dokumen dan tentukan hasil pemeriksaan.</p>
                </div>
                <span class="text-[11px] font-black text-slate-700 bg-slate-100 px-3 py-1 rounded-xl shrink-0 border border-slate-200">
                    Total: {{ $documents->total() }} Dokumen
                </span>
            </div>

            <!-- FILTER COMMAND BAR (LANGKAH 3C & SORTING 3B) -->
            <x-ui.search-filter action="{{ route('admin.verifikasi.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-2">
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
                            <option value="{{ $yF }}" {{ ($tahunFilter ?? session('active_ta', (int) date('Y'))) == $yF ? 'selected' : '' }}>TA {{ $yF }}</option>
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
            </x-ui.search-filter>

            <!-- WORKTABLE DATA FEED (WITH STICKY HEADER & LOADING SKELETON) -->
            <div class="st-table-wrapper rounded-2xl border border-slate-200 max-h-[600px] overflow-y-auto relative">
                
                <!-- LOADING SKELETON STATE (LANGKAH 3B) -->
                <div x-show="loading" class="absolute inset-0 bg-white/80 backdrop-blur-2xs z-20 flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-circle-notch animate-spin text-amber-500 text-xl"></i>
                    <span class="text-xs font-bold text-slate-700">Memuat data workspace...</span>
                </div>

                <x-ui.data-table class="w-full text-xs text-left text-slate-700 border-collapse">
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
                                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-xs shrink-0 shadow-xs">
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
                </x-ui.data-table>
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
