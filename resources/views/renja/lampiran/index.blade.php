@extends('layouts.app')

@section('title', 'RENJA Lampiran (Murni & Perubahan)')

@section('content')
<div class="max-w-7xl mx-auto space-y-5" x-data="{ uploadModalMurni: false, uploadModalPerubahan: false, detailModalOpen: false, detailDocType: '' }">

    <!-- FLASH NOTIFICATIONS -->
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
        $isMurniFix = $renjaLampiranMurni && in_array(strtolower($renjaLampiranMurni->status), ['disetujui', 'approved', 'dikunci', 'final']);
        $isMurniSubmitted = $renjaLampiranMurni && in_array(strtolower($renjaLampiranMurni->status), ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted']);
        
        $isPerubahanFix = $renjaLampiranPerubahan && in_array(strtolower($renjaLampiranPerubahan->status), ['disetujui', 'approved', 'dikunci', 'final']);
        $isPerubahanSubmitted = $renjaLampiranPerubahan && in_array(strtolower($renjaLampiranPerubahan->status), ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted']);
    @endphp

    <!-- PROGRESS RENJA LAMPIRAN SUMMARY BAR -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 sm:p-4 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center space-x-2 text-xs font-bold text-slate-800 shrink-0">
            <i class="fa-solid fa-file-contract text-blue-600"></i>
            <span>{!! 'RENJA Lampiran (Perbup & Kepbup)' !!}</span>
        </div>

        <div class="flex items-center flex-wrap gap-2 text-xs font-bold">
            <!-- Step 1: Lampiran Murni -->
            @if($isMurniFix)
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px]">
                    <i class="fa-solid fa-circle-check text-emerald-500"></i> Lampiran Murni
                </span>
            @elseif($isMurniSubmitted)
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 text-[11px]">
                    <i class="fa-solid fa-hourglass-half text-blue-500"></i> Lampiran Murni (Verifikasi)
                </span>
            @elseif($renjaLampiranMurni)
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-purple-50 text-purple-700 border border-purple-200 text-[11px]">
                    <i class="fa-solid fa-pen text-purple-500"></i> Lampiran Murni (Draft)
                </span>
            @else
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-100 text-slate-500 border border-slate-200 text-[11px]">
                    <i class="fa-regular fa-circle text-slate-400"></i> Lampiran Murni
                </span>
            @endif

            <span class="text-slate-300">&rarr;</span>

            <!-- Step 2: Lampiran Perubahan -->
            @if($parentPerubahan)
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 text-[11px]">
                    <i class="fa-solid fa-circle-check text-indigo-500"></i> Lampiran Perubahan
                </span>
            @else
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-100 text-slate-400 border border-slate-200 text-[11px] opacity-75">
                    <i class="fa-regular fa-circle text-slate-400"></i> Lampiran Perubahan
                </span>
            @endif
        </div>

        <div class="flex items-center space-x-2 shrink-0">
            <span class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-xl border border-blue-200 text-xs font-black">
                {{ $romawiNumber }}
            </span>
        </div>
    </div>

    <!-- GRID 2 KARTU DOKUMEN TERPISAH (RINGKAS & SIMETRIS) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        <!-- CARD 1: RENJA LAMPIRAN MURNI -->
        <div class="st-card-v2 p-5 flex flex-col justify-between border-t-4 {{ $isMurniFix ? 'border-t-emerald-500' : ($isMurniSubmitted ? 'border-t-blue-500' : 'border-t-purple-600') }} relative overflow-hidden bg-white rounded-2xl shadow-xs">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl {{ $isMurniFix ? 'bg-emerald-50 text-emerald-600' : 'bg-purple-50 text-purple-600' }} flex items-center justify-center text-base font-black shrink-0">
                            <i class="fa-solid {{ $isMurniFix ? 'fa-file-circle-check' : 'fa-file-lines' }}"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-slate-900 leading-tight">RENJA Lampiran Murni</h2>
                            <span class="text-[11px] font-bold text-slate-500">Lampiran Perbup Renja • TA {{ $taMurni }}</span>
                        </div>
                    </div>

                    <!-- FOCUSED STATUS PILL -->
                    @if($parentMurni)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                            ● Mengikuti RENJA Murni
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                            🔒 RENJA Murni Belum Ada
                        </span>
                    @endif
                </div>

                @if($parentMurni)
                    <div class="bg-purple-50/60 p-3.5 rounded-xl border border-purple-100 text-xs space-y-1.5 mb-3">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 font-medium">Sumber Data:</span>
                            <span class="font-bold text-purple-900 truncate max-w-[180px]">
                                RENJA Murni TA {{ $taMurni }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 font-medium">Status Induk:</span>
                            <span class="font-bold text-purple-800 capitalize">
                                {{ $parentMurni->status === 'draft' ? 'Mengikuti RENJA Murni (Draft)' : $parentMurni->status }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] pt-1 border-t border-purple-100">
                            <span class="text-slate-500 font-medium">Nomor Lampiran:</span>
                            <span class="font-black text-purple-700">
                                {{ $romawiNumber }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/80 text-xs text-slate-500 mb-3">
                        <span class="text-[11px]">
                            Dapat diakses otomatis setelah dokumen RENJA Murni TA {{ $taMurni }} dibuat.
                        </span>
                    </div>
                @endif
            </div>

            <!-- ACTION BUTTONS -->
            <div class="pt-3 border-t border-slate-100">
                @if($parentMurni)
                    <div class="grid grid-cols-2 gap-2 w-full">
                        <a href="{{ route('renja.preview', ['id' => $renjaLampiranMurni ? $renjaLampiranMurni->id : $parentMurni->id, 'is_lampiran' => 1]) }}"
                           class="w-full st-btn bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs py-2 px-3 rounded-xl flex items-center justify-center gap-1.5 min-w-0 shadow-2xs">
                            <i class="fa-solid fa-eye text-xs shrink-0"></i>
                            <span class="truncate">Preview Lampiran</span>
                        </a>
                        <button type="button"
                                @click="detailModalOpen = true; detailDocType = 'murni';"
                                class="w-full st-btn bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-2 px-3 rounded-xl border border-slate-200 flex items-center justify-center gap-1.5 min-w-0 shadow-2xs cursor-pointer">
                            <i class="fa-solid fa-sliders text-xs shrink-0"></i>
                            <span class="truncate">Detail</span>
                        </button>
                    </div>
                @else
                    <button disabled class="w-full py-2 bg-slate-100 text-slate-400 font-bold text-xs rounded-xl cursor-not-allowed border border-slate-200 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-lock text-xs"></i>
                        <span>🔒 Menunggu RENJA Murni</span>
                    </button>
                @endif
            </div>
        </div>


        <!-- CARD 2: RENJA LAMPIRAN PERUBAHAN -->
        <div class="st-card-v2 p-5 flex flex-col justify-between border-t-4 border-t-indigo-600 relative overflow-hidden bg-white rounded-2xl shadow-xs">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base font-black shrink-0">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-slate-900 leading-tight">RENJA Lampiran Perubahan</h2>
                            <span class="text-[11px] font-bold text-slate-500">Lampiran Kepbup Perubahan • TA {{ $taPerubahan }}</span>
                        </div>
                    </div>

                    <!-- FOCUSED STATUS PILL -->
                    @if($parentPerubahan)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">
                            ● Mengikuti RENJA Perubahan
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                            🔒 RENJA Perubahan Belum Ada
                        </span>
                    @endif
                </div>

                @if($parentPerubahan)
                    <div class="bg-indigo-50/60 p-3.5 rounded-xl border border-indigo-100 text-xs space-y-1.5 mb-3">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 font-medium">Sumber Data:</span>
                            <span class="font-bold text-indigo-900 truncate max-w-[180px]">
                                RENJA Perubahan TA {{ $taPerubahan }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 font-medium">Status Induk:</span>
                            <span class="font-bold text-indigo-800 capitalize">
                                {{ $parentPerubahan->status === 'draft' ? 'Mengikuti RENJA Perubahan (Draft)' : $parentPerubahan->status }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] pt-1 border-t border-indigo-100">
                            <span class="text-slate-500 font-medium">Nomor Lampiran:</span>
                            <span class="font-black text-indigo-700">
                                {{ $romawiNumber }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/80 text-xs text-slate-500 mb-3">
                        <span class="text-[11px]">
                            Dapat diakses otomatis setelah dokumen RENJA Perubahan TA {{ $taPerubahan }} dibuat.
                        </span>
                    </div>
                @endif
            </div>

            <!-- ACTION BUTTONS -->
            <div class="pt-3 border-t border-slate-100">
                @if($parentPerubahan)
                    <div class="grid grid-cols-2 gap-2 w-full">
                        <a href="{{ route('renja.preview', ['id' => $renjaLampiranPerubahan ? $renjaLampiranPerubahan->id : $parentPerubahan->id, 'is_lampiran' => 1]) }}"
                           class="w-full st-btn bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2 px-3 rounded-xl flex items-center justify-center gap-1.5 min-w-0 shadow-2xs">
                            <i class="fa-solid fa-eye text-xs shrink-0"></i>
                            <span class="truncate">Preview Lampiran</span>
                        </a>
                        <button type="button"
                                @click="detailModalOpen = true; detailDocType = 'perubahan';"
                                class="w-full st-btn bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-2 px-3 rounded-xl border border-slate-200 flex items-center justify-center gap-1.5 min-w-0 shadow-2xs cursor-pointer">
                            <i class="fa-solid fa-sliders text-xs shrink-0"></i>
                            <span class="truncate">Detail</span>
                        </button>
                    </div>
                @else
                    <button disabled class="w-full py-2 bg-slate-100 text-slate-400 font-bold text-xs rounded-xl cursor-not-allowed border border-slate-200 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-lock text-xs"></i>
                        <span>🔒 Menunggu RENJA Perubahan</span>
                    </button>
                @endif
            </div>
        </div>

    </div>

    <!-- MODAL DETAIL DOKUMEN LAMPIRAN (TECHNICAL INFO & ACTIONS) -->
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
                        <h3 class="text-sm font-black text-slate-900">Detail &amp; Pengaturan Dokumen Lampiran</h3>
                        <span class="text-[11px] text-slate-500 font-semibold" x-text="detailDocType === 'murni' ? 'RENJA Lampiran Murni TA {{ $taMurni }}' : 'RENJA Lampiran Perubahan TA {{ $taPerubahan }}'"></span>
                    </div>
                </div>
                <button type="button" @click="detailModalOpen = false" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Technical Detail Content for Lampiran Murni -->
            <template x-if="detailDocType === 'murni'">
                <div class="                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-2">
                        <div class="flex justify-between text-[11px]">
                            <span class="text-slate-500 font-semibold">Perangkat Daerah:</span>
                            <strong class="text-slate-800 font-bold truncate max-w-[200px]">{{ $opd->nama_opd ?? 'OPD' }}</strong>
                        </div>
                        <div class="flex justify-between text-[11px]">
                            <span class="text-slate-500 font-semibold">Sumber Dokumen Induk:</span>
                            <strong class="text-purple-900 font-bold truncate max-w-[200px]">RENJA Murni TA {{ $taMurni }}</strong>
                        </div>
                        <div class="flex justify-between text-[11px]">
                            <span class="text-slate-500 font-semibold">Status Sinkronisasi:</span>
                            <span class="font-bold text-emerald-700">● Live Sync dari Induk ({{ $parentMurni?->status ?? 'Draft' }})</span>
                        </div>
                        <div class="flex justify-between text-[11px]">
                            <span class="text-slate-500 font-semibold">Nomor Lampiran Romawi:</span>
                            <span class="font-black text-purple-700">{{ $romawiNumber }}</span>
                        </div>
                        <div class="flex justify-between text-[11px] pt-1.5 border-t border-slate-200/60">
                            <span class="text-slate-500 font-semibold">Format Resmi:</span>
                            <span class="font-bold text-slate-700">F4 Folio • Margins 2cm • Bookman 12pt • Zero Bold</span>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end space-x-2 pt-2">
                        @if($parentMurni)
                            <a href="{{ route('renja.preview', ['id' => $renjaLampiranMurni ? $renjaLampiranMurni->id : $parentMurni->id, 'is_lampiran' => 1]) }}"
                               class="st-btn bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs py-2 px-4 rounded-xl flex items-center gap-1.5 shadow-2xs">
                                <i class="fa-solid fa-eye text-xs"></i>
                                <span>Buka Preview Lampiran</span>
                            </a>
                        @endif
                    </div>
                </div>
            </template>

            <!-- Technical Detail Content for Lampiran Perubahan -->
            <template x-if="detailDocType === 'perubahan'">
                <div class="space-y-3 text-xs">
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-2">
                        <div class="flex justify-between text-[11px]">
                            <span class="text-slate-500 font-semibold">Perangkat Daerah:</span>
                            <strong class="text-slate-800 font-bold truncate max-w-[200px]">{{ $opd->nama_opd ?? 'OPD' }}</strong>
                        </div>
                        <div class="flex justify-between text-[11px]">
                            <span class="text-slate-500 font-semibold">Sumber Dokumen Induk:</span>
                            <strong class="text-indigo-900 font-bold truncate max-w-[200px]">RENJA Perubahan TA {{ $taPerubahan }}</strong>
                        </div>
                        <div class="flex justify-between text-[11px]">
                            <span class="text-slate-500 font-semibold">Status Sinkronisasi:</span>
                            <span class="font-bold text-emerald-700">● Live Sync dari Induk ({{ $parentPerubahan?->status ?? 'Draft' }})</span>
                        </div>
                        <div class="flex justify-between text-[11px]">
                            <span class="text-slate-500 font-semibold">Nomor Lampiran Romawi:</span>
                            <span class="font-black text-indigo-700">{{ $romawiNumber }}</span>
                        </div>
                        <div class="flex justify-between text-[11px] pt-1.5 border-t border-slate-200/60">
                            <span class="text-slate-500 font-semibold">Format Resmi:</span>
                            <span class="font-bold text-slate-700">F4 Folio • Margins 2cm • Bookman 12pt • Zero Bold</span>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end space-x-2 pt-2">
                        @if($parentPerubahan)
                            <a href="{{ route('renja.preview', ['id' => $renjaLampiranPerubahan ? $renjaLampiranPerubahan->id : $parentPerubahan->id, 'is_lampiran' => 1]) }}"
                               class="st-btn bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2 px-4 rounded-xl flex items-center gap-1.5 shadow-2xs">
                                <i class="fa-solid fa-eye text-xs"></i>
                                <span>Buka Preview Lampiran</span>
                            </a>
                        @endif
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection
