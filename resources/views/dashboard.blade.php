@extends('layouts.app')

@section('title', 'Dashboard Operator')

@section('content')
<div class="max-w-7xl mx-auto space-y-4" x-data="{ wizardStep: 1, selectedJenis: 'Rencana Kerja (Renja)' }">

    {{-- FLASH NOTIFICATIONS --}}
    @if(session('success'))
        <div class="bg-emerald-50/90 border border-emerald-200 text-emerald-900 px-4 py-2.5 rounded-2xl text-xs font-bold shadow-xs flex items-center space-x-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50/90 border border-rose-200 text-rose-900 px-4 py-2.5 rounded-2xl text-xs font-bold shadow-xs flex items-center space-x-2.5">
            <i class="fa-solid fa-circle-exclamation text-rose-600 text-base shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <x-ui.page-heading :title="'Selamat Datang, '.(auth()->user()->nama_lengkap ?? 'Operator')" description="Susun dokumen, tindak lanjuti catatan verifikasi, dan kelola arsip perangkat daerah Anda." :eyebrow="auth()->user()->opd->nama_opd ?? 'Ruang kerja OPD'">
        <x-slot:actions><button type="button" onclick="openModalBuatDokumen()" class="st-btn st-btn-primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Buat Dokumen</button></x-slot:actions>
    </x-ui.page-heading>
    <div class="ed-stats">
        <x-ui.statistic-card label="Draft Dokumen" :value="$stats['draft'] ?? 0" icon="fa-file-pen" note="Lanjutkan penyusunan" :href="route('renja.index', ['status' => 'draft'])" />
        <x-ui.statistic-card label="Perlu Revisi" :value="$stats['revisi'] ?? 0" tone="red" icon="fa-rotate-left" note="Tindak lanjuti catatan" :href="route('renja.index', ['status' => 'revision_required'])" />
        <x-ui.statistic-card label="Sedang Diverifikasi" :value="$stats['menunggu'] ?? 0" tone="purple" icon="fa-clipboard-check" note="Dalam pemeriksaan" :href="route('renja.index', ['status' => 'under_verification'])" />
        <x-ui.statistic-card label="Dokumen Final" :value="$stats['disetujui'] ?? 0" tone="green" icon="fa-box-archive" note="Disetujui dan tersimpan" :href="route('renja.index', ['status' => 'approved'])" />
    </div>
    @include('components.ui.dashboard-charts', ['chartStats' => $stats])
    {{-- ========================================== --}}
    {{-- 3. ACTION ITEMS - MINIMAL & COMPACT        --}}
    {{-- ========================================== --}}
    @php
        $revisiDocs = $activeDocuments->filter(fn($d) => in_array($d->status, ['perlu_revisi', 'revisi', 'revision']));
        $draftDocs = $activeDocuments->filter(fn($d) => in_array($d->status, ['draft', 'belum_dikerjakan']));
        $pendingDocs = $activeDocuments->filter(fn($d) => in_array($d->status, ['menunggu_pemeriksaan', 'menunggu_verifikasi', 'submitted', 'sedang_diperiksa', 'dikirim_ulang']));
        $hasActionableItems = ($revisiDocs->count() > 0) || ($draftDocs->count() > 0) || ($pendingDocs->count() > 0);
    @endphp

    @if($hasActionableItems)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
        {{-- REVISI ACTIONS --}}
        @foreach($revisiDocs->take(1) as $doc)
            <div class="bg-rose-50 border border-rose-200 rounded-lg p-2.5 space-y-1.5">
                <div class="flex items-center gap-2">
                    <div class="w-5 h-5 rounded bg-rose-100 text-rose-700 flex items-center justify-center text-[9px] shrink-0">
                        <i class="fa-solid fa-exclamation"></i>
                    </div>
                    <span class="text-xs font-bold text-rose-900">Perlu Revisi</span>
                </div>
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-700">{{ $doc->jenis_dokumen ?? 'Renja' }}</p>
                    <a href="{{ route('renja.editor', $doc->id) }}" class="text-rose-700 hover:text-rose-900 font-bold text-xs">Buka →</a>
                </div>
            </div>
        @endforeach

        {{-- DRAFT ACTIONS --}}
        @foreach($draftDocs->take(1) as $doc)
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-2.5 space-y-1.5">
                <div class="flex items-center gap-2">
                    <div class="w-5 h-5 rounded bg-amber-100 text-amber-700 flex items-center justify-center text-[9px] shrink-0">
                        <i class="fa-solid fa-pen"></i>
                    </div>
                    <span class="text-xs font-bold text-amber-900">Draft</span>
                </div>
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-700">{{ $doc->jenis_dokumen ?? 'Renja' }}</p>
                    <a href="{{ route('renja.editor', $doc->id) }}" class="text-amber-700 hover:text-amber-900 font-bold text-xs">Lanjut →</a>
                </div>
            </div>
        @endforeach

        {{-- WAITING VERIFICATION --}}
        @foreach($pendingDocs->take(1) as $doc)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 space-y-1.5">
                <div class="flex items-center gap-2">
                    <div class="w-5 h-5 rounded bg-blue-100 text-blue-700 flex items-center justify-center text-[9px] shrink-0">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <span class="text-xs font-bold text-blue-900">Verifikasi</span>
                </div>
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-700">{{ $doc->jenis_dokumen ?? 'Renja' }}</p>
                    <a href="{{ route('renja.editor', $doc->id) }}" class="text-blue-700 hover:text-blue-900 font-bold text-xs">Lihat →</a>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- ========================================== --}}
    {{-- 4. PROGRESS - ULTRA MINIMAL                --}}
    {{-- ========================================== --}}
    <div class="ed-panel p-5 space-y-2">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-bold text-slate-900">Progress Dokumentasi</h2>
            <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded text-xs font-bold border border-emerald-200">
                <i class="fa-solid fa-check text-emerald-600 text-xs"></i>
                {{ $overallProgress['completed'] ?? 0 }}/{{ $overallProgress['total'] ?? 0 }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex-1 bg-slate-200 rounded-full h-1.5 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-500 to-emerald-500 h-1.5 rounded-full" style="width: {{ $overallProgress['percentage'] ?? 0 }}%"></div>
            </div>
            <span class="text-xs font-black text-slate-800 w-8 text-right">{{ $overallProgress['percentage'] ?? 0 }}%</span>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- 5. MAIN GRID: ACTIVE DOCS + SIDEBAR      --}}
    {{-- ========================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">

        {{-- LEFT COLUMN: DOKUMEN SEDANG DIKERJAKAN --}}
        <div class="lg:col-span-8 ed-panel p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-bold text-slate-900">Dokumen Aktif</h2>
                <a href="{{ route('renja.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800">Lihat Semua →</a>
            </div>
            <div class="st-table-wrapper rounded border border-slate-200/80 overflow-hidden">
                <x-ui.data-table class="w-full text-xs text-left text-slate-700">
                    <thead class="bg-slate-50 text-slate-900 font-bold border-b border-slate-200/80">
                        <tr>
                            <th class="p-2 text-[10px]">Dokumen</th>
                            <th class="p-2 text-[10px] text-center w-10">TA</th>
                            <th class="p-2 text-[10px] text-center">Progress</th>
                            <th class="p-2 text-[10px] text-center hidden sm:table-cell w-16">Status</th>
                            <th class="p-2 text-[10px] text-center w-12">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($activeDocuments->take(5) as $doc)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-2 font-bold text-slate-900 text-[11px]">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa-solid fa-file text-amber-600 text-[10px]"></i>
                                        <span class="truncate">{{ $doc->jenis_dokumen ?? 'Renja' }}</span>
                                    </div>
                                </td>
                                <td class="p-2 text-center text-[11px] text-slate-600 font-bold">{{ $doc->tahun_anggaran }}</td>
                                <td class="p-2 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <div class="w-10 bg-slate-200 rounded-full h-1 overflow-hidden">
                                            <div class="bg-amber-500 h-1" style="width: {{ $doc->progress_percentage }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-700 w-5 text-right">{{ $doc->progress_percentage }}%</span>
                                    </div>
                                </td>
                                <td class="p-2 text-center hidden sm:table-cell">
                                    <x-ui.status-badge :status="$doc->status" :label="$doc->status_label" />
                                </td>
                                <td class="p-2 text-center">
                                    @if($doc->isEditableByOpd())
                                        <a href="{{ route('renja.editor', $doc->id) }}" class="st-btn st-btn-primary st-btn-sm text-[8px] h-5 px-1.5 rounded" title="Edit">
                                            <i class="fa-solid fa-pen text-[7px]"></i>
                                        </a>
                                    @else
                                        <a href="{{ route('renja.editor', $doc->id) }}" class="st-btn st-btn-secondary st-btn-sm text-[8px] h-5 px-1.5 rounded" title="Preview">
                                            <i class="fa-solid fa-eye text-[7px]"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-4 text-center text-slate-400 text-xs">
                                    <i class="fa-solid fa-folder-open text-sm mb-1 block"></i>
                                    Tidak ada dokumen
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui.data-table>
            </div>
        </div>

        {{-- RIGHT COLUMN: SIDEBAR COMPACT --}}
        <div class="lg:col-span-4 space-y-3">

            {{-- CATATAN DARI BAPPERIDA --}}
            <div class="ed-panel p-5">
                <h2 class="text-xs font-bold text-slate-700 mb-2 flex items-center gap-1">
                    <i class="fa-solid fa-comment-dots text-amber-500"></i>
                    Catatan dari Bapperida
                </h2>

                @if($bapperidaNotes->count() > 0)
                    <div class="space-y-1.5">
                        @foreach($bapperidaNotes->take(2) as $noteGroup)
                            <div class="bg-amber-50 border border-amber-200 rounded p-2 space-y-1">
                                <div class="text-[9px] font-bold text-amber-900">{{ $noteGroup['document']->jenis_dokumen ?? 'Renja' }} TA {{ $noteGroup['document']->tahun_anggaran }}</div>
                                @foreach(collect($noteGroup['notes'])->take(1) as $note)
                                    <div class="text-[9px] text-slate-700 line-clamp-2 bg-white rounded p-1.5 border border-amber-100">{{ $note['catatan'] }}</div>
                                @endforeach
                                @if($noteGroup['document']->isEditableByOpd())
                                    <a href="{{ route('renja.editor', $noteGroup['document']->id) }}" class="st-btn st-btn-amber st-btn-sm w-full text-[8px] rounded py-1 font-bold">
                                        Revisi
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-2">
                        <i class="fa-solid fa-check text-emerald-500 text-sm mb-0.5 block"></i>
                        <span class="text-[9px] font-bold text-emerald-700">Bersih</span>
                    </div>
                @endif
            </div>

            {{-- AKSES CEPAT --}}
            <div class="ed-panel p-5">
                <h2 class="text-xs font-bold text-slate-700 mb-2">Akses Cepat</h2>
                <div class="grid grid-cols-3 gap-1.5">
                    <a href="{{ route('renja.index') }}" class="bg-white border border-slate-200 rounded p-1.5 flex flex-col items-center justify-center text-center hover:border-amber-400 hover:bg-amber-50/30 transition group">
                        <i class="fa-solid fa-folder text-amber-600 text-sm mb-0.5"></i>
                        <span class="text-[8px] font-bold text-slate-800 leading-tight">Dokumen</span>
                    </a>
                    <a href="{{ route('operator.templates.renja-murni') }}" class="bg-white border border-slate-200 rounded p-1.5 flex flex-col items-center justify-center text-center hover:border-indigo-400 hover:bg-indigo-50/30 transition group">
                        <i class="fa-solid fa-book text-indigo-600 text-sm mb-0.5"></i>
                        <span class="text-[8px] font-bold text-slate-800 leading-tight">Template RENJA</span>
                    </a>
                    <button type="button" onclick="document.getElementById('modal-panduan').classList.remove('hidden')" class="bg-white border border-slate-200 rounded p-1.5 flex flex-col items-center justify-center text-center hover:border-emerald-400 hover:bg-emerald-50/30 transition group">
                        <i class="fa-solid fa-circle-question text-emerald-600 text-sm mb-0.5"></i>
                        <span class="text-[8px] font-bold text-slate-800 leading-tight">Panduan</span>
                    </button>
                </div>
            </div>

            {{-- DEADLINE --}}
            <div class="ed-panel p-5">
                <h2 class="text-xs font-bold text-slate-700 mb-2 flex items-center gap-1">
                    <i class="fa-solid fa-clock text-rose-500"></i>
                    Deadline Penyusunan
                </h2>

                @if(count($deadlines) > 0)
                    <div class="space-y-1">
                        @foreach(array_slice($deadlines, 0, 3) as $dl)
                            <div class="flex items-center gap-1.5 p-1.5 rounded text-[9px]
                                {{ $dl['is_overdue'] ? 'bg-rose-50 border border-rose-200' : ($dl['is_urgent'] ? 'bg-amber-50 border border-amber-200' : 'bg-slate-50 border border-slate-200') }}
                            ">
                                <div class="text-[9px] font-bold shrink-0
                                    {{ $dl['is_overdue'] ? 'text-rose-700' : ($dl['is_urgent'] ? 'text-amber-700' : 'text-slate-600') }}
                                ">
                                    <i class="fa-solid {{ $dl['is_overdue'] ? 'fa-exclamation' : 'fa-calendar' }}"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-slate-900 truncate">{{ $dl['nama'] }}</div>
                                    <div class="text-slate-500 text-[8px]">{{ $dl['tanggal'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-2">
                        <i class="fa-solid fa-check text-emerald-500 text-sm mb-0.5 block"></i>
                        <span class="text-[9px] font-bold text-emerald-700">Semua clear</span>
                    </div>
                @endif
            </div>

        </div>
    </div>

    <x-ui.submission-timeline :timeline="$timeline" />
</div>

@endsection
