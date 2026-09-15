@extends('layouts.app')

@section('title', 'Review Dokumen ' . ($document->jenis_dokumen ?? 'RENJA') . ' — ' . ($document->opd->nama_opd ?? 'Bapperida'))

@section('content')
<div class="max-w-7xl mx-auto space-y-5 pb-28" x-data="{ activeBabNav: '{{ $groupedSections->keys()->first() ?? '' }}' }">

    <!-- ============================================================ -->
    <!-- FLASH NOTIFICATION                                           -->
    <!-- ============================================================ -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-2xl text-xs font-bold flex items-center space-x-2.5 shadow-2xs">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-2xl text-xs font-bold flex items-center space-x-2.5 shadow-2xs">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- 1. COMPACT PAGE HEADER & DOCUMENT INFORMATION                -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-[11px] font-bold uppercase text-slate-400 mb-1">
                <a href="{{ route('admin.verifikasi.index') }}" class="hover:text-amber-600 transition flex items-center gap-1">
                    <i class="fa-solid fa-list-check text-amber-500"></i>
                    <span>Workspace Verifikasi</span>
                </a>
                <span>/</span>
                <span class="text-slate-700 font-extrabold">Review Dokumen</span>
            </div>
            <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight flex items-center gap-2 flex-wrap">
                <span>Review Dokumen {{ $document->jenis_dokumen ?? 'RENJA Murni' }}</span>
                <span class="text-slate-400 font-normal">•</span>
                <span class="text-amber-600 font-extrabold">{{ $document->opd->nama_opd ?? 'Perangkat Daerah' }}</span>
            </h1>
            <div class="text-xs text-slate-500 font-medium mt-0.5 flex items-center gap-2 flex-wrap">
                <span>Tahun Anggaran <strong>{{ $document->tahun_anggaran }}</strong></span>
                <span>•</span>
                <span>{{ $document->opd->nomor_lampiran_romawi ?? 'LAMPIRAN I' }}</span>
                <span>•</span>
                <span>Operator: <strong class="text-slate-700">{{ $document->updatedByUser->name ?? 'Operator OPD' }}</strong></span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            @php
                $statusEnum = \App\Enums\DocumentStatus::tryFrom($document->status);
            @endphp
            <span class="{{ $statusEnum ? $statusEnum->badgeClass() : 'bg-slate-100 text-slate-800' }} px-3 py-1.5 rounded-xl font-extrabold text-[11px] uppercase border shadow-2xs">
                {{ $statusEnum ? $statusEnum->label() : strtoupper($document->status) }}
            </span>

            <a href="{{ route('renja.preview', $document->id) }}" target="_blank" 
               class="st-btn bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-3.5 py-2 rounded-xl shadow-xs transition flex items-center gap-1.5"
               title="Buka Dokumen Utuh dalam High-Fidelity PDF Viewer">
                <i class="fa-solid fa-file-pdf text-amber-400"></i>
                <span>Lihat Dokumen Asli</span>
            </a>

            <a href="{{ route('admin.verifikasi.index') }}" 
               class="st-btn st-btn-outline st-btn-sm font-bold text-xs rounded-xl px-3 py-2 flex items-center gap-1.5 text-slate-600 hover:text-slate-900">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 2. DOCUMENT SUMMARY & PROGRESS REVIEW (UNIFIED CARD)          -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
        <!-- Left: Metadata summary in compact chips -->
        <div class="md:col-span-7 flex flex-wrap items-center gap-2 text-xs">
            <div class="bg-slate-50 border border-slate-200/70 rounded-xl px-3 py-1.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase mr-1">Nomor Lampiran:</span>
                <span class="font-bold text-amber-600">{{ \App\Services\RenjaAutoFixService::getRomanHeaderForOpd($document->opd) }}</span>
            </div>
            @if(isset($document->metadata['version']))
            <div class="bg-slate-50 border border-slate-200/70 rounded-xl px-3 py-1.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase mr-1">Version:</span>
                <span class="font-mono font-bold text-emerald-700">{{ $document->metadata['version'] }}</span>
            </div>
            @endif
            <div class="bg-slate-50 border border-slate-200/70 rounded-xl px-3 py-1.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase mr-1">Tanggal Submit:</span>
                <span class="font-bold text-slate-800">{{ $document->submitted_at ? $document->submitted_at->format('d M Y') : '-' }}</span>
            </div>
            <div class="bg-slate-50 border border-slate-200/70 rounded-xl px-3 py-1.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase mr-1">Total Bab:</span>
                <span class="font-bold text-slate-800">{{ $totalBabs }} Bab</span>
            </div>
            <div class="bg-emerald-50/70 border border-emerald-200/60 rounded-xl px-3 py-1.5 text-emerald-900">
                <span class="text-[10px] font-bold text-emerald-600 uppercase mr-1">Sudah Direview:</span>
                <span class="font-black">{{ $reviewedBabsCount }} Bab</span>
            </div>
        </div>

        <!-- Right: Progress Bar & Subtitle -->
        <div class="md:col-span-5 bg-slate-50/80 border border-slate-200/70 rounded-xl p-3 space-y-1.5">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700">Progress Review Dokumen</span>
                <span class="font-black text-slate-900">{{ $reviewedBabsCount }} / {{ $totalBabs }} BAB <span class="text-amber-600">({{ $reviewProgressPercentage }}%)</span></span>
            </div>
            <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-500 to-emerald-500 h-2 rounded-full transition-all duration-300" style="width: {{ $reviewProgressPercentage }}%"></div>
            </div>
            <div class="text-[10px] text-slate-500 font-medium text-right">
                @if($unreviewedBabsCount === 0 && $totalBabs > 0)
                    <span class="text-emerald-600 font-bold"><i class="fa-solid fa-check"></i> Seluruh BAB telah selesai direview</span>
                @else
                    <span>{{ $unreviewedBabsCount }} BAB masih menunggu review</span>
                @endif
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 3. COMPACT ALUR VERIFIKASI STEPPER                           -->
    <!-- ============================================================ -->
    <div class="bg-slate-900 text-white rounded-2xl border border-slate-800 p-3.5 shadow-md">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <div class="flex items-center space-x-2 shrink-0">
                <i class="fa-solid fa-timeline text-amber-400 text-xs"></i>
                <span class="text-[11px] font-black uppercase tracking-wider text-slate-300">Alur Verifikasi:</span>
            </div>

            <div class="flex items-center space-x-2 sm:space-x-4 overflow-x-auto w-full sm:w-auto py-1 text-[11px]">
                <div class="flex items-center space-x-1.5 shrink-0 text-slate-300 font-bold">
                    <span class="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-black">1</span>
                    <span>Submit OPD</span>
                </div>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-600 shrink-0"></i>
                <div class="flex items-center space-x-1.5 shrink-0 font-bold {{ in_array($document->status, ['sedang_diperiksa', 'under_review', 'disetujui', 'approved', 'final', 'perlu_revisi']) ? 'text-amber-400' : 'text-slate-500' }}">
                    <span class="w-5 h-5 rounded-full {{ in_array($document->status, ['sedang_diperiksa', 'under_review', 'disetujui', 'approved', 'final', 'perlu_revisi']) ? 'bg-amber-500 text-slate-950' : 'bg-slate-800 text-slate-500' }} flex items-center justify-center text-[10px] font-black">2</span>
                    <span>Review Bab</span>
                </div>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-600 shrink-0"></i>
                <div class="flex items-center space-x-1.5 shrink-0 font-bold {{ in_array($document->status, ['perlu_revisi', 'revisi']) ? 'text-rose-400' : 'text-slate-500' }}">
                    <span class="w-5 h-5 rounded-full {{ in_array($document->status, ['perlu_revisi', 'revisi']) ? 'bg-rose-500 text-white' : 'bg-slate-800 text-slate-500' }} flex items-center justify-center text-[10px] font-black">3</span>
                    <span>Revisi OPD</span>
                </div>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-600 shrink-0"></i>
                <div class="flex items-center space-x-1.5 shrink-0 font-bold {{ in_array($document->status, ['disetujui', 'approved', 'final']) ? 'text-emerald-400' : 'text-slate-500' }}">
                    <span class="w-5 h-5 rounded-full {{ in_array($document->status, ['disetujui', 'approved', 'final']) ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-500' }} flex items-center justify-center text-[10px] font-black">4</span>
                    <span>Disetujui (Sah)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 4. CORE REVIEW FORM (SIDEBAR BAB + REVIEW WORKSPACE)          -->
    <!-- ============================================================ -->
    <form action="{{ route('admin.verifikasi.decision', $document->id) }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- SIDEBAR NAVIGASI DOKUMEN (3 KOLOM - STICKY) -->
            <div class="lg:col-span-3 space-y-3.5 sticky top-20">
                <div class="bg-white rounded-2xl border border-slate-200/90 p-4 space-y-3 shadow-2xs">
                    <div class="border-b border-slate-100 pb-2.5 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-compass text-amber-500 text-xs"></i>
                            <h4 class="text-xs font-black uppercase text-slate-900 tracking-wider">Navigasi Dokumen</h4>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">{{ $totalBabs }} BAB</span>
                    </div>

                    <!-- BAB Links with User-Friendly Status Labels -->
                    <div class="space-y-1 text-xs">
                        @foreach($groupedSections as $babCode => $sections)
                            @php
                                $bStatus = $sectionStatuses[$babCode]['status'] ?? 'PENDING';
                            @endphp
                            <a href="#bab-{{ Str::slug($babCode) }}" 
                               @click="activeBabNav = '{{ $babCode }}'" 
                               class="flex items-center justify-between px-3 py-2 rounded-xl transition border {{ $bStatus === 'APPROVED' ? 'bg-emerald-50/60 text-emerald-950 border-emerald-200/80 hover:bg-emerald-100/60' : ($bStatus === 'NEEDS_REVISION' ? 'bg-amber-50/60 text-amber-950 border-amber-200/80 hover:bg-amber-100/60' : 'bg-slate-50/60 text-slate-700 border-slate-200/60 hover:bg-slate-100/80') }}"
                               title="Klik untuk berpindah ke bagian ini">
                                <div class="flex items-center space-x-2 truncate font-bold">
                                    <span class="text-xs shrink-0">
                                        @if($bStatus === 'APPROVED')
                                            <i class="fa-solid fa-circle-check text-emerald-600"></i>
                                        @elseif($bStatus === 'NEEDS_REVISION')
                                            <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                                        @else
                                            <i class="fa-regular fa-circle text-slate-400"></i>
                                        @endif
                                    </span>
                                    <span class="truncate">{{ $babCode }}</span>
                                </div>
                                <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded shrink-0 {{ $bStatus === 'APPROVED' ? 'bg-emerald-200/70 text-emerald-900' : ($bStatus === 'NEEDS_REVISION' ? 'bg-amber-200/80 text-amber-900' : 'bg-slate-200/70 text-slate-600') }}">
                                    @if($bStatus === 'APPROVED')
                                        Selesai
                                    @elseif($bStatus === 'NEEDS_REVISION')
                                        Revisi
                                    @else
                                        Menunggu
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>

                    <!-- FORM DELEGASI VERIFIKATOR -->
                    <div class="pt-3 border-t border-slate-100 space-y-1.5">
                        <label class="text-[10px] font-bold uppercase text-slate-400 block tracking-wider">Verifikator Bertugas:</label>
                        <select name="assigned_verificator_id" class="st-select text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 w-full" {{ $isLocked ? 'disabled' : '' }}>
                            <option value="">-- Pilih Verifikator --</option>
                            @foreach($availableVerificators as $vUser)
                                <option value="{{ $vUser->id }}" {{ $document->assigned_verificator_id == $vUser->id ? 'selected' : '' }}>
                                    {{ $vUser->name }} ({{ strtoupper($vUser->role) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Quick Link Full Native Viewer -->
                    <div class="pt-2 border-t border-slate-100">
                        <a href="{{ route('renja.preview', $document->id) }}" target="_blank"
                           class="w-full py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold transition flex items-center justify-center gap-1.5 border border-slate-200">
                            <i class="fa-solid fa-arrow-up-right-from-square text-xs text-amber-500"></i>
                            <span>Buka Full Document Viewer</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- MAIN REVIEW CONTENT (9 KOLOM) -->
            <div class="lg:col-span-9 space-y-5">
                
                @forelse($groupedSections as $babCode => $sections)
                    @php
                        $bStatus = $sectionStatuses[$babCode]['status'] ?? 'PENDING';
                        $bNotes = $sectionStatuses[$babCode]['notes'] ?? '';
                        $bNoteHistory = $sectionStatuses[$babCode]['updated_at'] ?? null;
                        $bReviewerName = $sectionStatuses[$babCode]['reviewer_name'] ?? null;
                    @endphp
                    <div id="bab-{{ Str::slug($babCode) }}" 
                         class="bg-white rounded-2xl border border-slate-200/90 p-5 sm:p-6 space-y-4 scroll-mt-24 shadow-2xs {{ $bStatus === 'APPROVED' ? 'ring-1 ring-emerald-400/40' : ($bStatus === 'NEEDS_REVISION' ? 'ring-1 ring-amber-400/40' : '') }}">
                        
                        <!-- 1. SECTION HEADER -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3.5">
                            <div class="flex items-center space-x-3">
                                <span class="w-9 h-9 rounded-xl bg-slate-900 text-amber-400 font-black text-xs flex items-center justify-center shadow-xs shrink-0">
                                    <i class="fa-solid fa-book-bookmark"></i>
                                </span>
                                <div>
                                    <h3 class="text-base sm:text-lg font-black text-slate-900">{{ $babCode }} : {{ $sections->first()->bab_title ?? '' }}</h3>
                                    <div class="text-[11px] text-slate-400 font-medium flex items-center gap-2">
                                        <span>{{ $sections->count() }} Subbab Terdaftar</span>
                                        <span>•</span>
                                        <span class="text-emerald-600 font-bold">Kelengkapan 100%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Review Dropdown per Bab -->
                            <div class="flex items-center space-x-2 shrink-0">
                                <label class="text-[11px] font-bold text-slate-600">Status Review:</label>
                                <select name="sections[{{ $babCode }}][status]" 
                                        {{ $isLocked ? 'disabled' : '' }}
                                        class="st-select text-xs font-bold h-9 rounded-xl border border-slate-300 px-3 {{ $bStatus === 'APPROVED' ? 'bg-emerald-50 text-emerald-900 border-emerald-300 font-black' : ($bStatus === 'NEEDS_REVISION' ? 'bg-amber-50 text-amber-950 font-black border-amber-300' : 'bg-slate-50 text-slate-700') }}">
                                    <option value="PENDING" {{ $bStatus === 'PENDING' ? 'selected' : '' }}>⏳ Menunggu Review</option>
                                    <option value="APPROVED" {{ $bStatus === 'APPROVED' ? 'selected' : '' }}>✅ Selesai / Disetujui</option>
                                    <option value="NEEDS_REVISION" {{ $bStatus === 'NEEDS_REVISION' ? 'selected' : '' }}>⚠️ Perlu Revisi</option>
                                </select>
                            </div>
                        </div>

                        <!-- 2. DOCUMENT PREVIEW / SUBBAB CONTENTS -->
                        <div class="space-y-3">
                            @foreach($sections as $sec)
                                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/70 space-y-2">
                                    <div class="flex items-center justify-between text-xs font-bold text-slate-900 border-b border-slate-200/50 pb-2">
                                        <span class="flex items-center gap-2">
                                            <i class="fa-solid fa-align-left text-slate-400 text-xs"></i>
                                            <span>{{ $sec->sub_bab_code }} {{ $sec->sub_bab_title }}</span>
                                        </span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md {{ $sec->is_completed ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                            {{ $sec->is_completed ? 'Selesai' : 'Draf' }}
                                        </span>
                                    </div>
                                    <div class="prose prose-xs max-w-none text-slate-800 bg-white p-3.5 rounded-lg border border-slate-200 text-xs max-h-52 overflow-y-auto leading-relaxed shadow-2xs">
                                        {!! $sec->content ?: '<em class="text-slate-400">Konten subbab belum diisi oleh OPD.</em>' !!}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- 3. CATATAN / INSTRUKSI REVISI (COMPACT TEXTAREA) -->
                        <div class="space-y-2 pt-2 border-t border-slate-100">
                            @if($bStatus === 'NEEDS_REVISION')
                                <div class="p-2.5 bg-amber-500/10 border border-amber-500/30 rounded-xl text-xs text-amber-900 font-bold flex items-center space-x-2">
                                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-sm shrink-0"></i>
                                    <span>Bagian ini ditandai <strong>PERLU REVISI</strong>. Tuliskan instruksi perbaikan spesifik di bawah ini.</span>
                                </div>
                            @endif

                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-700">
                                <label class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-comment-dots text-amber-500"></i>
                                    <span>Catatan / Instruksi Revisi Khusus {{ $babCode }}:</span>
                                </label>
                                @if($bNoteHistory)
                                    <span class="text-[10px] font-normal text-slate-400">
                                        Terakhir diubah: {{ \Carbon\Carbon::parse($bNoteHistory)->diffForHumans() }} {{ $bReviewerName ? "oleh {$bReviewerName}" : '' }}
                                    </span>
                                @endif
                            </div>
                            <textarea name="sections[{{ $babCode }}][notes]" 
                                      rows="2" 
                                      {{ $isLocked ? 'disabled' : '' }}
                                      placeholder="Tuliskan catatan perbaikan spesifik untuk {{ $babCode }} (opsional)..." 
                                      class="st-textarea text-xs rounded-xl border-slate-300 w-full p-2.5 {{ $bStatus === 'NEEDS_REVISION' ? 'border-amber-400 bg-amber-50/20 font-medium text-slate-900 focus:border-amber-500' : 'bg-slate-50/50' }}">{{ $bNotes }}</textarea>
                        </div>

                    </div>
                @empty
                    <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500 text-xs">
                        Belum ada seksi bab yang terdaftar dalam dokumen ini.
                    </div>
                @endforelse

            </div>

        </div>

        <!-- ============================================================ -->
        <!-- 5. COMPACT FIXED ACTION BAR (TIDAK MENUTUPI KONTEN)           -->
        <!-- ============================================================ -->
        <div class="fixed bottom-0 left-0 right-0 lg:left-[250px] bg-slate-950/95 backdrop-blur-md text-white border-t border-slate-800 px-4 py-3 z-40 shadow-2xl transition-all">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                
                <!-- SUMMARY NOTES INPUT -->
                <div class="flex-1 flex items-center space-x-2">
                    <label class="text-xs font-bold text-amber-400 whitespace-nowrap hidden sm:inline-flex items-center gap-1">
                        <i class="fa-solid fa-gavel text-[11px]"></i>
                        <span>Catatan Kesimpulan:</span>
                    </label>
                    <input type="text" name="catatan_bapperida" value="{{ $document->catatan_bapperida }}" 
                           placeholder="Tuliskan rangkuman kesimpulan review untuk OPD (opsional)..." 
                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-amber-500"
                           {{ $isLocked ? 'disabled' : '' }}>
                </div>

                <!-- DECISION ACTION BUTTONS (BALANCED & CONSISTENT) -->
                <div class="flex items-center space-x-2 shrink-0">
                    @if($isLocked)
                        <div class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center space-x-1.5">
                            <i class="fa-solid fa-lock text-xs"></i>
                            <span>Dokumen Telah Disetujui</span>
                        </div>
                    @else
                        <!-- Tertiary: Simpan Draft Review -->
                        <button type="submit" name="decision_type" value="simpan_draft" 
                                class="st-btn bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-bold rounded-xl px-3.5 py-1.5 transition flex items-center gap-1.5">
                            <i class="fa-solid fa-floppy-disk text-[11px]"></i>
                            <span>Simpan Draft</span>
                        </button>

                        <!-- Secondary: Minta Revisi OPD -->
                        <button type="submit" name="decision_type" value="minta_revisi" 
                                onclick="return confirm('Kembalikan dokumen ke OPD untuk PERBAIKAN / REVISI?')" 
                                class="st-btn bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold rounded-xl px-3.5 py-1.5 shadow-sm transition flex items-center gap-1.5">
                            <i class="fa-solid fa-triangle-exclamation text-[11px]"></i>
                            <span>Minta Revisi</span>
                        </button>

                        <!-- Primary: Setujui Dokumen (Sah) -->
                        <button type="submit" name="decision_type" value="setujui_dokumen" 
                                onclick="return confirm('Apakah Anda yakin ingin MENYETUJUI (SAH) seluruh dokumen Renja ini? Dokumen akan dikunci.')" 
                                class="st-btn bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-extrabold rounded-xl px-3.5 py-1.5 shadow-sm transition flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-check text-[11px]"></i>
                            <span>✓ Setujui Dokumen</span>
                        </button>
                    @endif
                </div>

            </div>
        </div>

    </form>

</div>
@endsection
