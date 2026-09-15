@extends('layouts.app')

@section('title', 'Detail Dokumen — ' . ($document->jenis_dokumen ?? 'RENJA'))

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ activeTab: 'info' }">

    {{-- FLASH NOTIFICATIONS --}}
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

    @php
        $isDraft = in_array($document->status, ['draft', 'belum_dikerjakan']);
        $isRevisi = in_array($document->status, ['perlu_revisi', 'revisi', 'revision']);
        $isSubmitted = in_array($document->status, ['submitted', 'menunggu_pemeriksaan', 'menunggu_verifikasi', 'dikirim_ulang']);
        $isUnderReview = in_array($document->status, ['sedang_diperiksa', 'sedang_direview', 'under_review']);
        $isFinal = in_array($document->status, ['disetujui', 'approved', 'dikunci', 'final']);
        $versionNum = ($document->revision_count ?? 0) + 1;
        $auditTrail = $document->metadata['audit_trail'] ?? [];
    @endphp

    {{-- ========================================== --}}
    {{-- 1. TOP HEADER NAVIGATION & BACK BUTTON     --}}
    {{-- ========================================== --}}
    <div class="flex items-center justify-between">
        <a href="{{ $backUrl ?? route('renja.index') }}" 
           class="inline-flex items-center gap-2 text-xs font-black text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3.5 py-2 rounded-xl shadow-2xs transition-all hover:bg-slate-50">
            <i class="fa-solid fa-arrow-left text-xs text-slate-400"></i>
            <span>← Kembali ke Dokumen Saya</span>
        </a>

        <div class="flex items-center space-x-2 text-[11px] font-bold text-slate-400">
            <span>e-Dokumen Bapperida</span>
            <span>/</span>
            <a href="{{ route('renja.index') }}" class="hover:text-slate-600">Dokumen Saya</a>
            <span>/</span>
            <span class="text-slate-900 font-extrabold">Detail Dokumen #{{ $document->id }}</span>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- 2. DOCUMENT IDENTITY CARD HEADER          --}}
    {{-- ========================================== --}}
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4 border-l-4 
        @if($isFinal) border-l-purple-600 
        @elseif($isRevisi) border-l-rose-500 
        @elseif($isSubmitted || $isUnderReview) border-l-blue-600 
        @else border-l-amber-500 @endif">
        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="space-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="{{ $document->status_badge_class }} px-3 py-1 rounded-full font-black text-[10px] uppercase border shadow-2xs">
                        {{ $document->status_label }}
                    </span>

                    <span class="bg-slate-100 text-slate-700 font-extrabold text-[10px] px-2.5 py-1 rounded-full border border-slate-200">
                        TA {{ $document->tahun_anggaran }}
                    </span>

                    <span class="bg-amber-50 text-amber-900 font-extrabold text-[10px] px-2.5 py-1 rounded-full border border-amber-200">
                        Lampiran: {{ $document->opd->nomor_lampiran_romawi ?? '-' }}
                    </span>

                    <span class="bg-indigo-50 text-indigo-800 font-black text-[10px] px-2.5 py-1 rounded-full border border-indigo-200">
                        Versi {{ $versionNum }}
                    </span>
                </div>

                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    {{ $document->jenis_dokumen }} TA {{ $document->tahun_anggaran }}
                </h1>

                <div class="flex items-center gap-2 text-xs text-slate-600 font-medium flex-wrap">
                    <span class="inline-flex items-center gap-1.5 font-bold text-slate-800">
                        <i class="fa-solid fa-building text-slate-400"></i>
                        {{ $document->opd->nama_opd ?? 'Perangkat Daerah' }}
                    </span>
                    <span class="text-slate-300">•</span>
                    <span>Diperbarui: {{ $document->updated_at ? $document->updated_at->format('d M Y, H:i') : '-' }}</span>
                </div>
            </div>

            {{-- ACTION BUTTONS DRIVEN BY DOCUMENT STATUS --}}
            <div class="flex items-center gap-2.5 flex-wrap shrink-0">
                @if($isDraft)
                    {{-- DRAFT ACTIONS --}}
                    <a href="{{ route('renja.editor', $document->id) }}"
                       class="st-btn st-btn-primary st-btn-md font-black text-xs px-4 py-2.5 rounded-xl shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                        <span>Lanjutkan Penyusunan</span>
                    </a>

                    <form action="{{ route('renja.submit', $document->id) }}" method="POST" class="inline"
                          onsubmit="return confirm('Apakah Anda yakin ingin mengajukan dokumen ini ke Bapperida untuk diverifikasi?')">
                        @csrf
                        <button type="submit" 
                                class="st-btn st-btn-amber st-btn-md font-black text-xs px-4 py-2.5 rounded-xl shadow-sm flex items-center gap-1.5">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            <span>Ajukan Verifikasi</span>
                        </button>
                    </form>
                @elseif($isRevisi)
                    {{-- REVISI ACTIONS --}}
                    <a href="{{ route('renja.editor', $document->id) }}"
                       class="st-btn bg-rose-600 hover:bg-rose-700 text-white font-black text-xs px-5 py-2.5 rounded-xl shadow-md flex items-center gap-1.5">
                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                        <span>Lihat & Perbaiki Dokumen</span>
                    </a>

                    <form action="{{ route('renja.submit', $document->id) }}" method="POST" class="inline"
                          onsubmit="return confirm('Kirim ulang dokumen yang telah diperbaiki ke Admin Bapperida?')">
                        @csrf
                        <button type="submit" 
                                class="st-btn st-btn-amber st-btn-md font-black text-xs px-4 py-2.5 rounded-xl shadow-sm flex items-center gap-1.5">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            <span>Kirim Ulang Verifikasi</span>
                        </button>
                    </form>
                @elseif($isSubmitted || $isUnderReview)
                    {{-- UNDER VERIFICATION ACTIONS (READ ONLY) --}}
                    <button type="button" @click="activeTab = 'preview'"
                            class="st-btn st-btn-primary st-btn-md font-black text-xs px-4 py-2.5 rounded-xl shadow-xs flex items-center gap-1.5">
                        <i class="fa-solid fa-eye text-xs"></i>
                        <span>Lihat Preview Dokumen</span>
                    </button>
                @elseif($isFinal)
                    {{-- FINAL / APPROVED ACTIONS (READ ONLY & DOWNLOAD) --}}
                    <a href="{{ route('renja.print', $document->id) }}" target="_blank"
                       class="st-btn bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-4 py-2.5 rounded-xl shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-print text-xs"></i>
                        <span>Cetak PDF F4</span>
                    </a>

                    <a href="{{ route('renja.exportWord', $document->id) }}"
                       class="st-btn bg-blue-700 hover:bg-blue-800 text-white font-black text-xs px-4 py-2.5 rounded-xl shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-file-word text-xs"></i>
                        <span>Unduh Word (.docx)</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- STATUS BANNER INFORMATION --}}
        @if($isSubmitted || $isUnderReview)
            <div class="bg-blue-50/90 border border-blue-200 text-blue-950 p-3.5 rounded-xl text-xs font-medium flex items-center gap-2.5">
                <i class="fa-solid fa-circle-info text-blue-600 text-base shrink-0"></i>
                <span><strong>Dokumen Sedang Diverifikasi:</strong> Dokumen ini telah diajukan dan sedang dalam proses pemeriksaan oleh Tim Bapperida. Dokumen ini sementara bersifat <strong>Read-Only</strong> dan tidak dapat diubah hingga proses verifikasi selesai.</span>
            </div>
        @elseif($isFinal)
            <div class="bg-emerald-50/90 border border-emerald-200 text-emerald-950 p-3.5 rounded-xl text-xs font-medium flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                <span><strong>Dokumen Final / Disetujui:</strong> Dokumen ini telah disetujui penuh oleh Bapperida Kabupaten Cirebon dan telah dikunci secara permanen. Anda dapat mengunduh berkas Word (.docx) atau mencetak berkas PDF resmi F4.</span>
            </div>
        @endif

    </div>

    {{-- ========================================== --}}
    {{-- 3. CATATAN REVISI (PROMINENT FOR REVISI)   --}}
    {{-- ========================================== --}}
    @if($isRevisi || !empty($document->catatan_bapperida))
        <div class="bg-rose-50/90 border-2 border-rose-300 rounded-2xl p-5 sm:p-6 shadow-xs space-y-3.5">
            <div class="flex items-center justify-between border-b border-rose-200 pb-3">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-rose-500 text-white flex items-center justify-center text-sm font-black shadow-xs">
                        <i class="fa-solid fa-comment-dots"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-rose-950 tracking-tight">Catatan Verifikasi Bapperida</h2>
                        <p class="text-[11px] text-rose-800 font-medium">Petunjuk dan catatan perbaikan yang wajib ditindaklanjuti oleh operator</p>
                    </div>
                </div>
                <a href="{{ route('renja.editor', $document->id) }}" 
                   class="st-btn bg-rose-600 hover:bg-rose-700 text-white font-black text-xs px-3.5 py-1.5 rounded-xl shadow-2xs flex items-center gap-1">
                    <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                    <span>Lihat & Perbaiki</span>
                </a>
            </div>

            <div class="space-y-3">
                @if(!empty($document->catatan_bapperida))
                    <div class="bg-white p-4 rounded-xl border border-rose-200 shadow-2xs space-y-1">
                        <div class="text-[10px] font-black uppercase text-rose-800 tracking-wider flex items-center justify-between">
                            <span>Catatan Umum Perbaikan</span>
                            <span class="text-slate-400 font-medium text-[10px]">
                                {{ $document->updated_at ? $document->updated_at->format('d M Y, H:i') : '-' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-800 font-semibold leading-relaxed">
                            {{ $document->catatan_bapperida }}
                        </p>
                    </div>
                @endif

                {{-- PER-SECTION REVIEWS IF ANY --}}
                @if(!empty($document->section_review_status) && is_array($document->section_review_status))
                    <div class="space-y-2 pt-1">
                        <div class="text-[10px] font-black uppercase text-rose-900 tracking-wider">Catatan Per Bab/Sub-Bab:</div>
                        @foreach($document->section_review_status as $babCode => $rStatus)
                            @if(($rStatus['status'] ?? '') === 'NEEDS_REVISION' && !empty($rStatus['notes']))
                                <div class="bg-white p-3 rounded-xl border border-rose-200 text-xs space-y-1">
                                    <div class="font-black text-rose-900 flex items-center justify-between">
                                        <span>Bab {{ $babCode }}</span>
                                        <span class="bg-rose-100 text-rose-800 px-2 py-0.5 rounded text-[9px] font-bold uppercase">Perlu Revisi</span>
                                    </div>
                                    <div class="text-slate-700 font-medium">{{ $rStatus['notes'] }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ========================================== --}}
    {{-- 4. TABS NAVIGATION                         --}}
    {{-- ========================================== --}}
    <div class="border-b border-slate-200 flex items-center space-x-2 text-xs font-black">
        <button type="button" @click="activeTab = 'info'" 
                :class="activeTab === 'info' ? 'border-amber-500 text-amber-600 bg-white shadow-2xs' : 'border-transparent text-slate-500 hover:text-slate-800 bg-slate-100/70'"
                class="px-4 py-2.5 rounded-t-xl border-b-2 font-black transition-all flex items-center gap-1.5">
            <i class="fa-solid fa-circle-info text-xs"></i>
            <span>Informasi & Timeline Dokumen</span>
        </button>

        <button type="button" @click="activeTab = 'preview'" 
                :class="activeTab === 'preview' ? 'border-amber-500 text-amber-600 bg-white shadow-2xs' : 'border-transparent text-slate-500 hover:text-slate-800 bg-slate-100/70'"
                class="px-4 py-2.5 rounded-t-xl border-b-2 font-black transition-all flex items-center gap-1.5">
            <i class="fa-solid fa-eye text-xs"></i>
            <span>Pratinjau Isi Narasi Dokumen</span>
        </button>

        <button type="button" @click="activeTab = 'pdf'" 
                :class="activeTab === 'pdf' ? 'border-amber-500 text-amber-600 bg-white shadow-2xs' : 'border-transparent text-slate-500 hover:text-slate-800 bg-slate-100/70'"
                class="px-4 py-2.5 rounded-t-xl border-b-2 font-black transition-all flex items-center gap-1.5">
            <i class="fa-solid fa-file-pdf text-xs"></i>
            <span>Pratinjau PDF F4 High-Fidelity</span>
        </button>
    </div>

    {{-- ========================================== --}}
    {{-- 5. TAB 1: INFORMASI & TIMELINE DOKUMEN     --}}
    {{-- ========================================== --}}
    <div x-show="activeTab === 'info'" class="space-y-6">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- LEFT COLUMN (7 COLS): INFORMASI DOKUMEN --}}
            <div class="lg:col-span-7 space-y-6">
                
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <h2 class="text-sm font-black text-slate-900 tracking-tight flex items-center gap-2">
                            <i class="fa-solid fa-file-lines text-amber-500"></i>
                            Informasi Dokumen Perencanaan
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/70 space-y-1">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Jenis Dokumen</div>
                            <div class="font-black text-slate-900">{{ $document->jenis_dokumen }}</div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/70 space-y-1">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Template Acuan</div>
                            <div class="font-black text-slate-900">{{ $document->template->name ?? 'Dokumen Manual (Tanpa Template)' }}</div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/70 space-y-1">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Perangkat Daerah</div>
                            <div class="font-black text-slate-900">{{ $document->opd->nama_opd ?? '-' }}</div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/70 space-y-1">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tahun Anggaran</div>
                            <div class="font-black text-slate-900">TA {{ $document->tahun_anggaran }}</div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/70 space-y-1">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Dibuat Pada</div>
                            <div class="font-bold text-slate-800">{{ $document->created_at ? $document->created_at->format('d M Y, H:i') : '-' }}</div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/70 space-y-1">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Terakhir Diperbarui</div>
                            <div class="font-bold text-slate-800">{{ $document->updated_at ? $document->updated_at->format('d M Y, H:i') : '-' }}</div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/70 space-y-1">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tanggal Diajukan</div>
                            <div class="font-bold text-slate-800">
                                {{ $document->submitted_at ? $document->submitted_at->format('d M Y, H:i') : 'Belum diajukan' }}
                            </div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/70 space-y-1">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Versi Dokumen</div>
                            <div class="font-black text-indigo-700">Versi {{ $versionNum }}</div>
                        </div>
                    </div>
                </div>

                {{-- RIWAYAT AUDIT TRAIL / LOG --}}
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <h2 class="text-sm font-black text-slate-900 tracking-tight flex items-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left text-amber-500"></i>
                            Riwayat Aktivitas & Log Versi Dokumen
                        </h2>
                    </div>

                    @if(!empty($auditTrail) && is_array($auditTrail) && count($auditTrail) > 0)
                        <div class="space-y-3 text-xs">
                            @foreach(array_reverse($auditTrail) as $trail)
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1">
                                    <div class="flex items-center justify-between font-black text-slate-900">
                                        <span class="uppercase tracking-wider text-[10px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                            {{ $trail['action'] ?? 'ACTIVITY' }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-medium">
                                            {{ !empty($trail['timestamp']) ? \Carbon\Carbon::parse($trail['timestamp'])->format('d M Y, H:i') : '-' }}
                                        </span>
                                    </div>
                                    <p class="text-slate-700 font-medium">{{ $trail['notes'] ?? 'Aktivitas dokumen.' }}</p>
                                    <div class="text-[10px] text-slate-400 font-bold">Oleh: {{ $trail['user_name'] ?? 'User' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 text-slate-400 space-y-1">
                            <i class="fa-solid fa-history text-2xl text-slate-300 block"></i>
                            <span class="text-xs font-bold">Belum ada catatan riwayat audit log.</span>
                        </div>
                    @endif
                </div>

            </div>

            {{-- RIGHT COLUMN (5 COLS): DYNAMIC TIMELINE LIFECYCLE --}}
            <div class="lg:col-span-5 space-y-6">
                
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-black text-slate-900 tracking-tight flex items-center gap-2">
                            <i class="fa-solid fa-timeline text-amber-500"></i>
                            Progress & Timeline Lifecycle
                        </h2>
                        <p class="text-[11px] text-slate-500 font-medium">Status perjalanan penyusunan hingga finalisasi dokumen</p>
                    </div>

                    <div class="space-y-4">
                        @foreach($timeline as $t)
                            <div class="flex gap-3">
                                <div class="flex flex-col items-center shrink-0">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold border
                                        @if($t['status'] === 'completed') bg-emerald-50 text-emerald-700 border-emerald-300
                                        @elseif($t['status'] === 'active') bg-blue-50 text-blue-700 border-blue-300 animate-pulse
                                        @else bg-slate-100 text-slate-400 border-slate-200
                                        @endif
                                    ">
                                        <i class="fa-solid {{ $t['icon'] }}"></i>
                                    </div>
                                    @if(!$loop->last)
                                        <div class="w-0.5 flex-1 bg-slate-200 mt-1"></div>
                                    @endif
                                </div>

                                <div class="flex-1 min-w-0 pb-3">
                                    <div class="flex items-center justify-between">
                                        <div class="text-xs font-black text-slate-900">{{ $t['title'] }}</div>
                                        @if($t['status'] === 'completed')
                                            <span class="text-[9px] font-black uppercase text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Selesai</span>
                                        @elseif($t['status'] === 'active')
                                            <span class="text-[9px] font-black uppercase text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200">Berlangsung</span>
                                        @else
                                            <span class="text-[9px] font-black uppercase text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-200">Belum</span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-slate-600 font-medium leading-relaxed mt-0.5">{{ $t['description'] }}</p>
                                    @if($t['timestamp'])
                                        <div class="text-[10px] text-slate-400 font-bold mt-1">
                                            {{ $t['timestamp'] ? \Carbon\Carbon::parse($t['timestamp'])->format('d M Y, H:i') : '' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

        </div>

    </div>

    {{-- ========================================== --}}
    {{-- 6. TAB 2: PRATINJAU ISI NARASI DOKUMEN     --}}
    {{-- ========================================== --}}
    <div x-show="activeTab === 'preview'" class="space-y-6" style="display: none;">
        
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 sm:p-8 shadow-xs space-y-8">
            <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900">{{ $document->jenis_dokumen }} TA {{ $document->tahun_anggaran }}</h2>
                    <p class="text-xs text-slate-500 font-medium">{{ $document->opd->nama_opd ?? '-' }}</p>
                </div>
                <a href="{{ route('renja.editor', $document->id) }}" class="st-btn st-btn-primary st-btn-sm text-xs font-bold">
                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                    <span>Buka di Editor</span>
                </a>
            </div>

            @php
                $groupedBabs = $document->sections->groupBy('bab_code');
            @endphp

            @forelse($groupedBabs as $babCode => $secs)
                @php
                    $firstSec = $secs->first();
                    $babTitle = $firstSec->bab_title ?? '';
                @endphp
                <div class="space-y-4">
                    @if(!empty($babCode) && str_contains(strtoupper($babCode), 'BAB'))
                        <div class="text-center border-b border-slate-200 pb-2">
                            <h2 class="text-sm font-black uppercase text-slate-900 tracking-wide">{{ $babCode }}</h2>
                            @if(!empty($babTitle))
                                <h3 class="text-xs font-black uppercase text-slate-800">{{ $babTitle }}</h3>
                            @endif
                        </div>
                    @endif

                    @foreach($secs as $sec)
                        @if($sec->section_type === 'chapter')
                            @continue
                        @endif

                        @php
                            $cleanTitle = preg_replace('/^\d+(\.\d+)*\s*/', '', $sec->sub_bab_title ?? '');
                        @endphp
                        <div class="space-y-2">
                            <h4 class="text-xs font-black text-slate-900">
                                @if(!empty($sec->sub_bab_code))
                                    {{ $sec->sub_bab_code }}. {{ strtoupper($cleanTitle) }}
                                @else
                                    {{ strtoupper($cleanTitle) }}
                                @endif
                            </h4>

                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs text-slate-800 leading-relaxed font-serif">
                                {!! $sec->content ?: '<p class="italic text-slate-400 font-sans">Belum diisi narasi.</p>' !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="text-center py-12 text-slate-400 space-y-2">
                    <i class="fa-solid fa-file-lines text-3xl text-slate-300 block"></i>
                    <div class="text-xs font-bold text-slate-600">Belum ada narasi Bab yang disusun pada dokumen ini.</div>
                </div>
            @endforelse
        </div>

    </div>

    {{-- ========================================== --}}
    {{-- 7. TAB 3: PRATINJAU PDF HIGH-FIDELITY     --}}
    {{-- ========================================== --}}
    <div x-show="activeTab === 'pdf'" class="space-y-4" style="display: none;">
        
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <span class="text-xs font-black text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-file-pdf text-rose-600"></i>
                High-Fidelity PDF Render Preview (Kertas F4 Folio 215 x 330 mm)
            </span>
            <div class="flex items-center gap-2">
                <a href="{{ route('renja.preview-pdf', ['id' => $document->id, 'download' => 1]) }}" 
                   class="st-btn st-btn-primary st-btn-sm text-xs font-bold">
                    <i class="fa-solid fa-download text-xs"></i>
                    <span>Unduh PDF</span>
                </a>
                <a href="{{ route('renja.print', $document->id) }}" target="_blank"
                   class="st-btn st-btn-secondary st-btn-sm text-xs font-bold">
                    <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                    <span>Buka Tab Baru</span>
                </a>
            </div>
        </div>

        <div class="bg-slate-900 rounded-2xl overflow-hidden border border-slate-800 shadow-xl h-[750px] relative">
            <iframe src="{{ route('renja.preview-pdf', $document->id) }}" class="w-full h-full border-0"></iframe>
        </div>

    </div>

</div>
@endsection
