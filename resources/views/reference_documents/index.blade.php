@extends('layouts.app')

@section('title', 'Auto-Detect Modul Acuan')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- HEADER & ACTIONS -->
    <div class="st-card p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fa-solid fa-wand-magic-sparkles text-amber-500"></i>
                <span>Auto-Detect Modul dari Dokumen Acuan</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Sistem otomatis membaca struktur BAB, Sub-Bab, dan Tabel dari dokumen .docx acuan dan men-generate slot komponen di editor.
            </p>
        </div>

        <a href="{{ route('reference-documents.create') }}" class="st-btn st-btn-amber st-btn-sm self-start md:self-auto">
            <i class="fa-solid fa-upload text-xs"></i>
            <span>Upload Dokumen Acuan Baru</span>
        </a>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-xl shadow-2xs text-xs font-semibold flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-amber-50 border border-amber-300 text-amber-800 px-4 py-3 rounded-xl shadow-2xs text-xs font-semibold flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="st-card p-4 flex items-center space-x-4">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center font-bold shrink-0">
                <i class="fa-solid fa-folder-tree"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-semibold">Total Schema</div>
                <div class="text-xl font-extrabold text-slate-800">{{ $stats['total'] }}</div>
            </div>
        </div>

        <div class="st-card p-4 flex items-center space-x-4">
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center font-bold shrink-0">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-semibold">Menunggu Review</div>
                <div class="text-xl font-extrabold text-slate-800">{{ $stats['draft'] }}</div>
            </div>
        </div>

        <div class="st-card p-4 flex items-center space-x-4">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold shrink-0">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-semibold">Disetujui (Approved)</div>
                <div class="text-xl font-extrabold text-slate-800">{{ $stats['approved'] }}</div>
            </div>
        </div>

        <div class="st-card p-4 flex items-center space-x-4">
            <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-700 flex items-center justify-center font-bold shrink-0">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-semibold">Ditolak</div>
                <div class="text-xl font-extrabold text-slate-800">{{ $stats['rejected'] }}</div>
            </div>
        </div>
    </div>

    <!-- MAIN SCHEMAS TABLE -->
    <div class="st-card p-5 sm:p-6 space-y-4">
        <div class="st-table-wrapper">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-900 uppercase font-extrabold text-[10px] tracking-wider border-b border-slate-200">
                        <th class="p-3.5">Dokumen Acuan</th>
                        <th class="p-3.5">Jenis Dokumen</th>
                        <th class="p-3.5 text-center">Hasil Extraction</th>
                        <th class="p-3.5 text-center">Status Schema</th>
                        <th class="p-3.5 text-right">Aksi Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($schemas as $sch)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3.5 font-bold text-slate-900">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg bg-slate-900 text-amber-400 flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
                                        <i class="fa-solid fa-file-word"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-extrabold text-slate-900 truncate max-w-[260px]">{{ $sch->original_filename }}</div>
                                        <div class="text-[10px] text-slate-500 font-medium">Uploaded: {{ $sch->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="p-3.5 text-slate-600 font-semibold whitespace-nowrap">
                                {{ $sch->document_type }}
                            </td>

                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span class="bg-slate-100 text-slate-800 px-2.5 py-1 rounded-md text-[11px] font-bold">
                                    {{ $sch->chapters->count() }} Bab / {{ $sch->subChapters->count() }} Sub-Bab
                                </span>
                            </td>

                            <td class="p-3.5 text-center whitespace-nowrap">
                                @if($sch->status === 'approved')
                                    <span class="px-2.5 py-0.5 text-[10px] rounded-full font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">APPROVED</span>
                                @elseif($sch->status === 'rejected')
                                    <span class="px-2.5 py-0.5 text-[10px] rounded-full font-extrabold uppercase bg-rose-100 text-rose-800 border border-rose-300">REJECTED</span>
                                @else
                                    <span class="px-2.5 py-0.5 text-[10px] rounded-full font-extrabold uppercase bg-amber-100 text-amber-800 border border-amber-300">DRAFT REVIEW</span>
                                @endif
                            </td>

                            <td class="p-3.5 text-right whitespace-nowrap space-x-1.5">
                                <a href="{{ route('reference-documents.show', $sch->id) }}" class="st-btn st-btn-primary st-btn-sm">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                    <span>Detail Schema</span>
                                </a>

                                <form action="{{ route('reference-documents.destroy', $sch->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus schema acuan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="st-btn st-btn-danger st-btn-sm">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-slate-400">
                                <i class="fa-solid fa-file-contract text-4xl mb-3 text-slate-300 block"></i>
                                <span class="font-bold text-xs">Belum ada dokumen acuan yang diunggah.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
