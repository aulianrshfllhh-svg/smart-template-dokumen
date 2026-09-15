@extends('layouts.app')

@php
    $currentTa = (isset($tahunAnggaran) && is_numeric($tahunAnggaran) && (int)$tahunAnggaran >= 2020) ? (int)$tahunAnggaran : (session('active_ta', (int)date('Y')) >= 2020 ? session('active_ta', (int)date('Y')) : (int)date('Y'));
@endphp

@section('title', 'Workspace RENJA ' . $currentTa . '–' . ($currentTa + 1))

@section('content')
<div class="max-w-7xl mx-auto space-y-5" x-data="{ modalTambahOpen: false, modalDocType: 'murni', activeTab: 'template', uploadModalOpen: false, uploadDocType: 'murni', uploadTargetUrl: '{{ route('operator.renja-murni.store-upload') }}', detailModalOpen: false, detailDocType: '' }">

    <!-- FLASH NOTIFICATION -->
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
        $isMurniFix = $renjaMurni && in_array(strtolower($renjaMurni->status), ['disetujui', 'approved', 'dikunci', 'final']);
        $isMurniDraft = $renjaMurni && in_array(strtolower($renjaMurni->status), ['draft', 'belum_dikerjakan', 'perlu_revisi', 'revisi', 'revision']);
        $isMurniSubmitted = $renjaMurni && in_array(strtolower($renjaMurni->status), ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang', 'sedang_diperiksa', 'sedang_direview', 'under_review']);

        $isPerubahanFix = $renjaPerubahan && in_array(strtolower($renjaPerubahan->status), ['disetujui', 'approved', 'dikunci', 'final']);
        $isPerubahanDraft = $renjaPerubahan && in_array(strtolower($renjaPerubahan->status), ['draft', 'belum_dikerjakan', 'perlu_revisi', 'revisi', 'revision']);
        $isPerubahanSubmitted = $renjaPerubahan && in_array(strtolower($renjaPerubahan->status), ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'dikirim_ulang', 'sedang_diperiksa', 'sedang_direview', 'under_review']);
    @endphp

    <!-- PROGRESS RENJA SUMMARY BAR -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 sm:p-4 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center space-x-2 text-xs font-bold text-slate-800 shrink-0">
            <i class="fa-solid fa-bars-progress text-amber-500"></i>
            <span>Progress RENJA</span>
        </div>

        <div class="flex items-center flex-wrap gap-2 text-xs font-bold">
            <!-- Step 1: RENJA Murni -->
            @if($isMurniFix)
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px]">
                    <i class="fa-solid fa-circle-check text-emerald-500"></i> RENJA Murni
                </span>
            @elseif($isMurniSubmitted)
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 text-[11px]">
                    <i class="fa-solid fa-hourglass-half text-blue-500"></i> RENJA Murni (Verifikasi)
                </span>
            @elseif($renjaMurni)
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 text-[11px]">
                    <i class="fa-solid fa-pen text-amber-500"></i> RENJA Murni (Draft)
                </span>
            @else
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-100 text-slate-500 border border-slate-200 text-[11px]">
                    <i class="fa-regular fa-circle text-slate-400"></i> RENJA Murni
                </span>
            @endif

            <span class="text-slate-300">&rarr;</span>

            <!-- Step 2: RENJA Perubahan -->
            @if($isPerubahanFix)
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px]">
                    <i class="fa-solid fa-circle-check text-emerald-500"></i> RENJA Perubahan
                </span>
            @elseif($isPerubahanSubmitted)
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 text-[11px]">
                    <i class="fa-solid fa-hourglass-half text-blue-500"></i> RENJA Perubahan (Verifikasi)
                </span>
            @elseif($renjaPerubahan)
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-purple-50 text-purple-700 border border-purple-200 text-[11px]">
                    <i class="fa-solid fa-pen text-purple-500"></i> RENJA Perubahan (Draft)
                </span>
            @else
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-100 text-slate-400 border border-slate-200 text-[11px] opacity-75">
                    <i class="fa-regular fa-circle text-slate-400"></i> RENJA Perubahan
                </span>
            @endif

            <span class="text-slate-300">&rarr;</span>

            <!-- Step 3: Lampiran -->
            @if($renjaLampiranMurni || $renjaLampiranPerubahan)
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-purple-50 text-purple-700 border border-purple-200 text-[11px]">
                    <i class="fa-solid fa-file-lines text-purple-500"></i> Lampiran
                </span>
            @else
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-100 text-slate-400 border border-slate-200 text-[11px] opacity-75">
                    <i class="fa-regular fa-circle text-slate-400"></i> Lampiran
                </span>
            @endif
        </div>

        <div class="flex items-center space-x-2 shrink-0">
            <button type="button" onclick="openModalBuatDokumen()" 
                    class="st-btn st-btn-amber st-btn-sm rounded-xl font-black text-xs shadow-2xs">
                <i class="fa-solid fa-circle-plus text-xs"></i>
                <span>+ Buat Dokumen Baru</span>
            </button>
        </div>
    </div>

    {{-- BANNER DOKUMEN RESMI (FIX) JIKA RENJA MURNI TELAH DISETUJUI --}}
    @if($isMurniApproved && $renjaMurni)
        <div class="st-card-v2 p-4 bg-gradient-to-r from-emerald-950 via-slate-900 to-slate-900 text-white rounded-2xl border border-emerald-500/30 shadow-md flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-400/30 flex items-center justify-center text-lg font-black shrink-0">
                    <i class="fa-solid fa-certificate"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-emerald-500 text-slate-950">
                            DOKUMEN RESMI (FIX)
                        </span>
                        <span class="text-xs text-emerald-300 font-bold">Telah Disetujui Bapperida</span>
                    </div>
                    <h3 class="text-sm font-black text-white mt-0.5">
                        Dokumen RENJA Murni Tahun {{ $currentTa + 1 }} (FIX)
                    </h3>
                </div>
            </div>
            <div class="flex items-center flex-wrap gap-2 shrink-0">
                <a href="{{ route('renja.print', $renjaMurni->id) }}" target="_blank"
                   class="st-btn bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs px-3.5 py-1.5 rounded-xl shadow-sm transition">
                    <i class="fa-solid fa-folder-open"></i>
                    <span>Lihat Dokumen Fix</span>
                </a>
                <a href="{{ route('renja.exportWord', $renjaMurni->id) }}" 
                   class="st-btn bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs px-3 py-1.5 rounded-xl shadow-xs transition">
                    <i class="fa-solid fa-file-word"></i>
                    <span>Unduh Word</span>
                </a>
            </div>
        </div>
    @endif

    <!-- 3 KARTU DOKUMEN BERBASIS VIEWPORT RINGKAS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

        <!-- CARD 1: RENJA MURNI -->
        <div class="st-card-v2 p-5 flex flex-col justify-between border-t-4 {{ $isMurniFix ? 'border-t-emerald-500' : ($isMurniSubmitted ? 'border-t-blue-500' : 'border-t-amber-500') }} relative overflow-hidden bg-white rounded-2xl shadow-xs">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl {{ $isMurniFix ? 'bg-emerald-50 text-emerald-600' : ($isMurniSubmitted ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600') }} flex items-center justify-center text-base font-black shrink-0">
                            <i class="fa-solid {{ $isMurniFix ? 'fa-file-circle-check' : 'fa-file-invoice' }}"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-slate-900 leading-tight">RENJA Murni</h2>
                            <span class="text-[11px] font-bold text-slate-500">TA {{ $currentTa + 1 }}</span>
                        </div>
                    </div>

                    <!-- FOCUSED STATUS PILL -->
                    @if($isMurniFix)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            🟢 Disetujui (FIX)
                        </span>
                    @elseif($isMurniSubmitted)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
                            🟠 Menunggu Verifikasi
                        </span>
                    @elseif($renjaMurni)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">
                            🟡 Draft
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                            ⚪ Belum Dibuat
                        </span>
                    @endif
                </div>

                @if($renjaMurni)
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/80 text-xs space-y-1 mb-3">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 font-medium">Nama File:</span>
                            <span class="font-mono font-bold text-slate-800 truncate max-w-[140px]" title="{{ $renjaMurni->metadata['original_filename'] ?? 'RENJA Murni.docx' }}">
                                {{ $renjaMurni->metadata['original_filename'] ?? 'Dokumen RENJA' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 font-medium">Tanggal Upload:</span>
                            <span class="font-bold text-slate-700">
                                {{ ($renjaMurni->metadata['uploaded_at'] ?? null) ? date('d M Y', strtotime($renjaMurni->metadata['uploaded_at'])) : ($renjaMurni->created_at ? $renjaMurni->created_at->format('d M Y') : '-') }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/80 text-xs text-slate-500 mb-3">
                        <span class="text-[11px]">Belum ada dokumen RENJA Murni disiapkan untuk TA {{ $currentTa + 1 }}.</span>
                    </div>
                @endif
            </div>

            <!-- ACTION BUTTONS -->
            <div class="pt-3 border-t border-slate-100">
                @if($renjaMurni)
                    <div class="grid grid-cols-2 gap-2 w-full">
                        <a href="{{ route('renja.print', $renjaMurni->id) }}" target="_blank"
                           class="w-full st-btn bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-2 px-3 rounded-xl flex items-center justify-center gap-1.5 min-w-0 shadow-2xs">
                            <i class="fa-solid fa-eye text-xs shrink-0"></i>
                            <span class="truncate">Lihat Dokumen</span>
                        </a>
                        <button type="button" 
                                @click="detailModalOpen = true; detailDocType = 'murni';"
                                class="w-full st-btn bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-2 px-3 rounded-xl border border-slate-200 flex items-center justify-center gap-1.5 min-w-0 shadow-2xs cursor-pointer"
                                title="Lihat Detail Dokumen">
                            <i class="fa-solid fa-sliders text-xs shrink-0"></i>
                            <span class="truncate">Detail</span>
                        </button>
                    </div>
                @else
                    <button type="button" onclick="openModalBuatDokumen('RENJA_MURNI')" class="w-full st-btn st-btn-amber font-black text-xs py-2 rounded-xl text-center shadow-2xs cursor-pointer">
                        <i class="fa-solid fa-plus-circle text-xs"></i>
                        <span>+ Buat / Upload RENJA Murni</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- CARD 2: RENJA PERUBAHAN -->
        <div class="st-card-v2 p-5 flex flex-col justify-between border-t-4 {{ $isPerubahanFix ? 'border-t-emerald-500' : ($isPerubahanSubmitted ? 'border-t-blue-500' : 'border-t-purple-500') }} relative overflow-hidden bg-white rounded-2xl shadow-xs">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl {{ $isPerubahanFix ? 'bg-emerald-50 text-emerald-600' : 'bg-purple-50 text-purple-600' }} flex items-center justify-center text-base font-black shrink-0">
                            <i class="fa-solid {{ $isPerubahanFix ? 'fa-file-circle-check' : 'fa-code-compare' }}"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-slate-900 leading-tight">RENJA Perubahan</h2>
                            <span class="text-[11px] font-bold text-slate-500">TA {{ $currentTa }}</span>
                        </div>
                    </div>

                    <!-- FOCUSED STATUS PILL -->
                    @if($isPerubahanFix)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            🟢 Disetujui (FIX)
                        </span>
                    @elseif($isPerubahanSubmitted)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
                            🟠 Menunggu Verifikasi
                        </span>
                    @elseif($renjaPerubahan)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                            🟣 Draft
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $canCreatePerubahan ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                            {{ $canCreatePerubahan ? 'Belum Diunggah' : '🔒 Menunggu Persetujuan' }}
                        </span>
                    @endif
                </div>

                <p class="text-xs text-slate-500 leading-relaxed mb-3">
                    @if($renjaPerubahan)
                        Dokumen penyesuaian target kinerja &amp; anggaran TA {{ $currentTa }}.
                    @elseif($canCreatePerubahan)
                        RENJA Murni disetujui. RENJA Perubahan siap diunggah.
                    @else
                        Dapat disusun setelah RENJA Murni disetujui Bapperida.
                    @endif
                </p>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="pt-3 border-t border-slate-100">
                @if($renjaPerubahan)
                    <div class="grid grid-cols-2 gap-2 w-full">
                        <a href="{{ route('renja.print', $renjaPerubahan->id) }}" target="_blank"
                           class="w-full st-btn bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-2 px-3 rounded-xl flex items-center justify-center gap-1.5 min-w-0 shadow-2xs">
                            <i class="fa-solid fa-eye text-xs shrink-0"></i>
                            <span class="truncate">Lihat Dokumen</span>
                        </a>
                        <button type="button" 
                                @click="detailModalOpen = true; detailDocType = 'perubahan';"
                                class="w-full st-btn bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-2 px-3 rounded-xl border border-slate-200 flex items-center justify-center gap-1.5 min-w-0 shadow-2xs cursor-pointer"
                                title="Lihat Detail Dokumen">
                            <i class="fa-solid fa-sliders text-xs shrink-0"></i>
                            <span class="truncate">Detail</span>
                        </button>
                    </div>
                @elseif($canCreatePerubahan)
                    <button type="button"
                        onclick="openModalBuatDokumen('RENJA_PERUBAHAN')"
                        class="w-full st-btn bg-blue-600 hover:bg-blue-700 text-white font-black text-xs py-2 rounded-xl shadow-2xs flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-plus-circle text-xs"></i>
                        <span>+ Buat / Upload RENJA Perubahan</span>
                    </button>
                @else
                    <button disabled class="w-full py-2 bg-slate-100 text-slate-400 font-bold text-xs rounded-xl cursor-not-allowed border border-slate-200 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-lock text-xs"></i>
                        <span>Lihat Status</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- CARD 3: RENJA LAMPIRAN -->
        @php
            $romawiNumber = \App\Services\RenjaAutoFixService::getRomanHeaderForOpd($opd);
        @endphp
        <div class="st-card-v2 p-5 flex flex-col justify-between border-t-4 border-t-blue-500 relative overflow-hidden bg-white rounded-2xl shadow-xs">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base font-black shrink-0">
                            <i class="fa-solid fa-file-contract"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-slate-900 leading-tight">RENJA Lampiran</h2>
                            <span class="text-[11px] font-bold text-blue-700">{{ $romawiNumber }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 space-y-2 text-xs mb-3">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 font-medium">Lampiran Murni:</span>
                        @if($renjaMurni)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                ● Mengikuti RENJA Murni
                            </span>
                        @else
                            <span class="text-[10px] font-bold text-slate-400">Belum Ada</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-200/60 pt-1.5">
                        <span class="text-slate-600 font-medium">Lampiran Perubahan:</span>
                        @if($renjaPerubahan)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                ● Mengikuti RENJA Perubahan
                            </span>
                        @else
                            <span class="text-[10px] font-bold text-slate-400">Belum Ada</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- ACTION BUTTON -->
            <div class="pt-3 border-t border-slate-100">
                <a href="{{ route('renja.lampiran.index', ['tahun_anggaran' => $currentTa]) }}" 
                   class="w-full st-btn bg-blue-600 hover:bg-blue-700 text-white font-black text-xs py-2 rounded-xl flex items-center justify-center gap-1.5 shadow-2xs transition">
                    <i class="fa-solid fa-arrow-right-to-bracket text-xs"></i>
                    <span>Kelola &amp; Preview Lampiran</span>
                </a>
            </div>
        </div>

    </div>

    <!-- MODAL DETAIL DOKUMEN (ALL TECHNICAL DATA MOVED HERE TO KEEP CARDS CLEAN) -->
    <div x-show="detailModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs transition-opacity duration-200">
        
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 space-y-4 border border-slate-100"
             @click.away="detailModalOpen = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base font-black">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">Detail &amp; Pengaturan Dokumen</h3>
                        <span class="text-[11px] text-slate-500 font-semibold" x-text="detailDocType === 'murni' ? 'RENJA Murni TA {{ $currentTa + 1 }}' : 'RENJA Perubahan TA {{ $currentTa }}'"></span>
                    </div>
                </div>
                <button type="button" @click="detailModalOpen = false" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Technical Detail Content for Murni -->
            <template x-if="detailDocType === 'murni'">
                <div class="space-y-3 text-xs">
                    @if($renjaMurni)
                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-2">
                            <div class="flex justify-between text-[11px]">
                                <span class="text-slate-500 font-semibold">Nama File:</span>
                                <span class="font-mono font-bold text-slate-800 truncate max-w-[200px]" title="{{ $renjaMurni->metadata['original_filename'] ?? 'RENJA Murni.docx' }}">{{ $renjaMurni->metadata['original_filename'] ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between text-[11px]">
                                <span class="text-slate-500 font-semibold">Ukuran File:</span>
                                <span class="font-bold text-slate-800">
                                    @php $fsize = $renjaMurni->metadata['original_file_size'] ?? null; @endphp
                                    {{ $fsize ? number_format($fsize / 1024, 1) . ' KB' : '-' }}
                                </span>
                            </div>
                            <div class="flex justify-between text-[11px]">
                                <span class="text-slate-500 font-semibold">Tanggal Upload:</span>
                                <span class="font-bold text-slate-800">{{ ($renjaMurni->metadata['uploaded_at'] ?? null) ? date('d M Y, H:i', strtotime($renjaMurni->metadata['uploaded_at'])) : ($renjaMurni->created_at ? $renjaMurni->created_at->format('d M Y, H:i') : '-') }}</span>
                            </div>
                            <div class="flex justify-between text-[11px]">
                                <span class="text-slate-500 font-semibold">Sumber Dokumen:</span>
                                <span class="font-bold text-slate-800">
                                    @if(($renjaMurni->source_type ?? '') === 'upload_word')
                                        <i class="fa-solid fa-file-word text-blue-600"></i> Upload Word (Original)
                                    @else
                                        <i class="fa-solid fa-layer-group text-amber-600"></i> Template Resmi
                                    @endif
                                </span>
                            </div>
                            @if(!empty($renjaMurni->metadata['original_file_hash']))
                            <div class="flex justify-between text-[11px] pt-1.5 border-t border-slate-200/60 font-mono">
                                <span class="text-slate-500">SHA-256:</span>
                                <span class="text-slate-700 truncate max-w-[200px]" title="{{ $renjaMurni->metadata['original_file_hash'] }}">{{ substr($renjaMurni->metadata['original_file_hash'], 0, 18) }}...</span>
                            </div>
                            @endif
                        </div>

                        <!-- Modal Quick Actions -->
                        <div class="flex items-center justify-end space-x-2 pt-2">
                            @if(!$isMurniFix && !$isMurniSubmitted)
                                <button type="button"
                                        @click="detailModalOpen = false; uploadModalOpen = true; uploadDocType = 'murni'; uploadTargetUrl = '{{ route('operator.renja-murni.store-upload') }}'"
                                        class="st-btn bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2 px-3 rounded-xl flex items-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-cloud-arrow-up text-xs"></i>
                                    <span>Ganti File</span>
                                </button>
                                <form method="POST" action="{{ route('renja.submit', $renjaMurni->id) }}" onsubmit="return confirm('Kirim dokumen RENJA Murni ini ke Admin Bapperida?');" class="inline">
                                    @csrf
                                    <button type="submit" class="st-btn bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2 px-3 rounded-xl flex items-center gap-1.5 cursor-pointer">
                                        <i class="fa-solid fa-paper-plane text-xs"></i>
                                        <span>Submit ke Admin</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        <p class="text-slate-500 text-center py-4">Belum ada data dokumen.</p>
                    @endif
                </div>
            </template>

            <!-- Technical Detail Content for Perubahan -->
            <template x-if="detailDocType === 'perubahan'">
                <div class="space-y-3 text-xs">
                    @if($renjaPerubahan)
                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-2">
                            <div class="flex justify-between text-[11px]">
                                <span class="text-slate-500 font-semibold">Nama File:</span>
                                <span class="font-mono font-bold text-slate-800 truncate max-w-[200px]" title="{{ $renjaPerubahan->metadata['original_filename'] ?? 'RENJA Perubahan.docx' }}">{{ $renjaPerubahan->metadata['original_filename'] ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between text-[11px]">
                                <span class="text-slate-500 font-semibold">Ukuran File:</span>
                                <span class="font-bold text-slate-800">
                                    @php $fsize = $renjaPerubahan->metadata['original_file_size'] ?? null; @endphp
                                    {{ $fsize ? number_format($fsize / 1024, 1) . ' KB' : '-' }}
                                </span>
                            </div>
                            <div class="flex justify-between text-[11px]">
                                <span class="text-slate-500 font-semibold">Tanggal Upload:</span>
                                <span class="font-bold text-slate-800">{{ ($renjaPerubahan->metadata['uploaded_at'] ?? null) ? date('d M Y, H:i', strtotime($renjaPerubahan->metadata['uploaded_at'])) : ($renjaPerubahan->created_at ? $renjaPerubahan->created_at->format('d M Y, H:i') : '-') }}</span>
                            </div>
                            <div class="flex justify-between text-[11px]">
                                <span class="text-slate-500 font-semibold">Sumber Dokumen:</span>
                                <span class="font-bold text-slate-800">
                                    @if(($renjaPerubahan->source_type ?? '') === 'upload_word')
                                        <i class="fa-solid fa-file-word text-blue-600"></i> Upload Word (Original)
                                    @else
                                        <i class="fa-solid fa-layer-group text-purple-600"></i> Template Resmi
                                    @endif
                                </span>
                            </div>
                        </div>

                        <!-- Modal Quick Actions -->
                        <div class="flex items-center justify-end space-x-2 pt-2">
                            @if(!$isPerubahanFix && !$isPerubahanSubmitted)
                                <button type="button"
                                        @click="detailModalOpen = false; uploadModalOpen = true; uploadDocType = 'perubahan'; uploadTargetUrl = '{{ route('operator.renja-murni.store-upload') }}'"
                                        class="st-btn bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2 px-3 rounded-xl flex items-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-cloud-arrow-up text-xs"></i>
                                    <span>Ganti File</span>
                                </button>
                            @endif
                        </div>
                    @else
                        <p class="text-slate-500 text-center py-4">Belum ada data dokumen.</p>
                    @endif
                </div>
            </template>
        </div>
    </div>

    <!-- MODAL: UPLOAD WORD & KIRIM KE BAPPERIDA -->
    <div x-show="uploadModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">

        <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm" @click="uploadModalOpen = false"></div>

        <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden z-10">

            <div class="bg-gradient-to-r from-blue-700 to-blue-600 text-white px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                        <i class="fa-solid fa-cloud-arrow-up text-white text-base"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-sm">Upload Dokumen Word (<span x-text="uploadDocType === 'murni' ? 'RENJA Murni' : (uploadDocType === 'lampiran' ? 'RENJA Lampiran' : 'RENJA Perubahan')"></span>)</h3>
                        <p class="text-[11px] text-blue-200 font-medium">Unggah file Word (.docx) untuk diimport ke sistem</p>
                    </div>
                </div>
                <button type="button" @click="uploadModalOpen = false"
                        class="w-8 h-8 rounded-xl bg-white/20 hover:bg-white/30 flex items-center justify-center transition text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST"
                  action="{{ route('operator.renja-murni.store-upload') }}"
                  enctype="multipart/form-data"
                  class="p-6 space-y-4">
                @csrf

                <input type="hidden" name="tahun_anggaran" :value="(uploadDocType === 'murni' || uploadDocType === 'lampiran' || uploadDocType === 'lampiran_murni') ? '{{ $currentTa + 1 }}' : '{{ $currentTa }}'">
                <input type="hidden" name="jenis_dokumen" :value="uploadDocType === 'murni' ? 'RENJA Murni' : (uploadDocType === 'lampiran_murni' ? 'RENJA Lampiran Murni' : (uploadDocType === 'lampiran_perubahan' ? 'RENJA Lampiran Perubahan' : (uploadDocType === 'lampiran' ? 'RENJA Lampiran Murni' : 'RENJA Perubahan')))">

                <div class="bg-blue-50 border border-blue-200 rounded-2xl px-4 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-calendar-check text-sm"></i>
                    </div>
                    <div>
                        <div class="text-xs font-black text-blue-900">Tahun Anggaran: TA <span x-text="(uploadDocType === 'murni' || uploadDocType === 'lampiran' || uploadDocType === 'lampiran_murni') ? '{{ $currentTa + 1 }}' : '{{ $currentTa }}'"></span></div>
                        <div class="text-[11px] text-blue-700 font-medium">{{ $opd->nama_opd ?? 'Perangkat Daerah' }}</div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="upload_submit_file" class="block text-xs font-black text-slate-700">
                        Pilih File Dokumen Word (.docx)
                        <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="file"
                               id="upload_submit_file"
                               name="document_file"
                               accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                               class="st-input text-xs h-11 rounded-xl w-full file:mr-4 file:py-1.5 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-black file:bg-blue-600 file:text-white hover:file:bg-blue-700 transition"
                               required>
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="uploadModalOpen = false"
                            class="st-btn st-btn-outline font-bold text-xs py-2.5 px-4 rounded-xl">
                        Batal
                    </button>
                    <button type="submit"
                            class="st-btn bg-blue-600 hover:bg-blue-700 text-white font-black text-xs py-2.5 px-6 rounded-xl shadow-md flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Upload &amp; Simpan Dokumen</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
