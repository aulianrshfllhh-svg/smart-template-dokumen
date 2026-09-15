@extends('layouts.app')

@section('title', 'Detail Monitoring OPD — ' . $opd->nama_opd)

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- ========================================== --}}
    {{-- 1. TOP NAVIGATION & BREADCRUMB            --}}
    {{-- ========================================== --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <a href="{{ route('admin.monitoring-opd.index', ['tahun_anggaran' => $activeYear]) }}" 
           class="inline-flex items-center gap-2 text-xs font-black text-slate-700 hover:text-slate-900 bg-white border border-slate-200 px-4 py-2.5 rounded-xl shadow-2xs transition-all hover:bg-slate-50 self-start sm:self-auto">
            <i class="fa-solid fa-arrow-left text-xs text-slate-400"></i>
            <span>← Kembali ke Monitoring OPD</span>
        </a>

        <div class="flex items-center space-x-2 text-[11px] font-bold text-slate-400">
            <span>Pengelolaan Bapperida</span>
            <span>/</span>
            <a href="{{ route('admin.monitoring-opd.index') }}" class="hover:text-slate-600">Monitoring OPD</a>
            <span>/</span>
            <span class="text-slate-900 font-extrabold truncate max-w-[200px] sm:max-w-xs">{{ $opd->nama_opd }}</span>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- 2. OPD HEADER CARD & ACTIVE CYCLE STATUS   --}}
    {{-- ========================================== --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-2xs space-y-4 border-l-4 border-l-amber-500">
        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="space-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="bg-amber-500 text-slate-950 font-black text-[10px] uppercase px-3 py-1 rounded-full shadow-2xs">
                        PERANGKAT DAERAH
                    </span>

                    <span class="bg-slate-100 text-slate-700 font-extrabold text-[10px] px-2.5 py-1 rounded-full border border-slate-200">
                        Kode: {{ $opd->kode_opd ?? '-' }}
                    </span>

                    <span class="bg-amber-50 text-amber-900 font-extrabold text-[10px] px-2.5 py-1 rounded-full border border-amber-200">
                        Lampiran: {{ $opd->nomor_lampiran_romawi ?? '-' }}
                    </span>

                    {{-- BADGE OVERALL OPD STATUS --}}
                    <span class="{{ $opdBadgeClass }} text-[10px] uppercase px-3 py-1 rounded-full border shadow-2xs">
                        Status OPD: {{ $opdStatusLabel }}
                    </span>
                </div>

                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    {{ $opd->nama_opd }}
                </h1>

                <div class="flex items-center gap-4 text-xs text-slate-600 font-medium flex-wrap pt-1">
                    <span class="inline-flex items-center gap-1.5 font-bold text-slate-800">
                        <i class="fa-solid fa-users text-slate-400"></i>
                        {{ $opd->users->count() }} Akun Operator Terdaftar
                    </span>
                    <span>•</span>
                    <span class="inline-flex items-center gap-1.5 font-extrabold text-blue-700">
                        <i class="fa-solid fa-file-circle-check text-blue-500"></i>
                        Progress: {{ $opdStats['submitted_count'] }} / 4 Dokumen Active Cycle
                    </span>
                </div>
            </div>

            {{-- ACTIVE CYCLE BADGE & TA SELECTOR --}}
            <div class="flex items-center gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-200 shrink-0">
                <div class="text-right pr-2 hidden sm:block">
                    <div class="text-[10px] font-black uppercase text-slate-400">Siklus Aktif</div>
                    <div class="text-xs font-black text-blue-800">TA {{ $activeYear }}–{{ $activeYear + 1 }}</div>
                </div>
                <form method="GET" action="{{ route('admin.monitoring-opd.show', $opd->id) }}">
                    <select name="tahun_anggaran" onchange="this.form.submit()" class="st-select text-xs h-9 rounded-lg font-bold bg-white">
                        @foreach(range(2025, 2030) as $yOpt)
                            <option value="{{ $yOpt }}" {{ $activeYear == $yOpt ? 'selected' : '' }}>TA {{ $yOpt }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

    </div>

    {{-- ========================================== --}}
    {{-- 3. SUMMARY KPI CARDS UNTUK OPD INI        --}}
    {{-- ========================================== --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 sm:gap-4">
        
        <div class="st-card-v2 p-4 text-center space-y-1 border-l-4 border-l-blue-600">
            <div class="text-[10px] font-black uppercase text-slate-500 tracking-wider">Submitted / Processed</div>
            <div class="text-2xl font-black text-slate-900">{{ $opdStats['diproses'] }}</div>
            <div class="text-[10px] font-bold text-blue-600">Menunggu Verifikasi</div>
        </div>

        <div class="st-card-v2 p-4 text-center space-y-1 border-l-4 border-l-amber-500">
            <div class="text-[10px] font-black uppercase text-slate-500 tracking-wider">Perlu Revisi</div>
            <div class="text-2xl font-black text-amber-600">{{ $opdStats['revisi'] }}</div>
            <div class="text-[10px] font-bold text-amber-600">Dikembalikan ke OPD</div>
        </div>

        <div class="st-card-v2 p-4 text-center space-y-1 border-l-4 border-l-emerald-600">
            <div class="text-[10px] font-black uppercase text-slate-500 tracking-wider">Disetujui / Final</div>
            <div class="text-2xl font-black text-emerald-600">{{ $opdStats['disetujui'] }}</div>
            <div class="text-[10px] font-bold text-emerald-600">Selesai & Sah</div>
        </div>

        <div class="st-card-v2 p-4 text-center space-y-1 border-l-4 border-l-slate-400">
            <div class="text-[10px] font-black uppercase text-slate-500 tracking-wider">Draft / Penyusunan</div>
            <div class="text-2xl font-black text-slate-700">{{ $opdStats['draft'] }}</div>
            <div class="text-[10px] font-bold text-slate-500">Belum Disubmit</div>
        </div>

    </div>

    {{-- ========================================== --}}
    {{-- 4. DAFTAR 4 DOKUMEN SIKLUS AKTIF OPD       --}}
    {{-- ========================================== --}}
    <div class="st-card-v2 p-5 sm:p-6 space-y-5">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
            <div>
                <h2 class="text-sm sm:text-base font-black text-slate-900">4 Dokumen Perencanaan Siklus Aktif</h2>
                <p class="text-[11px] text-slate-500 font-medium">Status dan ketersediaan dokumen RENJA & Lampiran untuk TA {{ $activeYear }}–{{ $activeYear + 1 }}</p>
            </div>
            <span class="text-xs font-extrabold text-blue-800 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
                Siklus TA {{ $activeYear }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($cycleDocuments as $item)
                @php
                    $doc = $item['document'];
                    $hasDoc = $doc !== null;
                    
                    if ($hasDoc) {
                        $isDraft = in_array($doc->status, ['draft', 'belum_dikerjakan']);
                        $isRevisi = in_array($doc->status, ['perlu_revisi', 'revisi', 'revision']);
                        $isSubmitted = in_array($doc->status, ['submitted', 'menunggu_pemeriksaan', 'menunggu_verifikasi', 'dikirim_ulang']);
                        $isUnderReview = in_array($doc->status, ['sedang_diperiksa', 'sedang_direview', 'under_review']);
                        $isFinal = in_array($doc->status, ['disetujui', 'approved', 'dikunci', 'final']);
                    }
                @endphp

                <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-3.5 flex flex-col justify-between hover:border-slate-300 transition">
                    
                    <div class="space-y-3">
                        {{-- CARD HEADER & STATUS BADGE --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="space-y-1">
                                <span class="bg-slate-100 text-slate-700 font-extrabold text-[10px] px-2.5 py-0.5 rounded-md border border-slate-200 inline-block">
                                    TA {{ $item['tahun_anggaran'] }}
                                </span>
                                <h3 class="text-sm font-black text-slate-900">
                                    {{ $item['title'] }}
                                </h3>
                            </div>

                            @if($hasDoc)
                                <span class="{{ $doc->status_badge_class }} px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase border shadow-2xs shrink-0">
                                    {{ $doc->status_label }}
                                </span>
                            @else
                                <span class="bg-slate-100 text-slate-500 border border-slate-200 px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase shrink-0">
                                    Belum Dibuat
                                </span>
                            @endif
                        </div>

                        {{-- DOCUMENT CONTENT METADATA --}}
                        @if($hasDoc)
                            <div class="space-y-2 text-xs">
                                <div class="flex items-center justify-between text-[11px] text-slate-500 font-medium">
                                    <span>Progress Penyusunan:</span>
                                    <span class="font-black text-slate-800">{{ $doc->progress_percentage }}%</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ $doc->progress_percentage }}%"></div>
                                </div>

                                <div class="pt-1 space-y-1 text-[11px] text-slate-500 font-medium">
                                    <div><i class="fa-solid fa-clock text-slate-400 mr-1.5"></i> Update: <strong>{{ $doc->updated_at ? $doc->updated_at->diffForHumans() : '-' }}</strong></div>
                                    <div><i class="fa-solid fa-paper-plane text-slate-400 mr-1.5"></i> Diajukan: <strong>{{ $doc->submitted_at ? $doc->submitted_at->format('d M Y, H:i') : 'Belum diajukan' }}</strong></div>
                                </div>
                            </div>

                            {{-- CATATAN REVISI JIKA ADA --}}
                            @if($isRevisi && $doc->catatan_bapperida)
                                <div class="bg-rose-50 border border-rose-200 text-rose-950 p-3 rounded-xl text-xs space-y-1">
                                    <div class="font-black text-rose-900 flex items-center gap-1.5">
                                        <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Catatan Revisi Bapperida:
                                    </div>
                                    <div class="text-rose-900 font-semibold leading-relaxed">{{ $doc->catatan_bapperida }}</div>
                                </div>
                            @endif
                        @else
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs text-slate-400 italic">
                                Dokumen belum dibuat oleh Perangkat Daerah untuk TA {{ $item['tahun_anggaran'] }}.
                            </div>
                        @endif
                    </div>

                    {{-- CARD ACTION FOOTER --}}
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                        @if($hasDoc)
                            <a href="{{ route('renja.show', $doc->id) }}" 
                               class="st-btn st-btn-secondary st-btn-sm text-xs font-bold px-3.5 rounded-xl border border-slate-200">
                                <i class="fa-solid fa-eye text-xs"></i>
                                <span>Lihat Dokumen</span>
                            </a>

                            @if($isSubmitted || $isUnderReview)
                                <a href="{{ route('admin.review', $doc->id) }}" 
                                   class="st-btn st-btn-primary st-btn-sm text-xs font-black px-3.5 rounded-xl shadow-2xs flex items-center gap-1.5">
                                    <i class="fa-solid fa-clipboard-check text-xs"></i>
                                    <span>Periksa Dokumen →</span>
                                </a>
                            @elseif($isFinal)
                                <a href="{{ route('renja.print', $doc->id) }}" target="_blank"
                                   class="st-btn bg-emerald-600 hover:bg-emerald-700 text-white st-btn-sm text-xs font-black px-3 rounded-xl shadow-xs">
                                    <i class="fa-solid fa-print text-xs"></i>
                                    <span>Cetak PDF</span>
                                </a>
                            @endif
                        @else
                            <span class="text-[11px] font-bold text-slate-400 italic">Tidak ada tindakan</span>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>

    </div>

</div>
@endsection
