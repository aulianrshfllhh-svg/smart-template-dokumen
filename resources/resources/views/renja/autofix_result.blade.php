@extends('layouts.app')

@section('title', 'Laporan AutoFix - RENJA Lampiran')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Top Breadcrumb / Stepper Banner -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div class="flex items-center space-x-2 text-xs font-bold">
            <span class="text-slate-400">DOCX</span>
            <i class="fa-solid fa-chevron-right text-slate-300 text-[10px]"></i>
            <span class="text-slate-400">Format Checker</span>
            <i class="fa-solid fa-chevron-right text-slate-300 text-[10px]"></i>
            <span class="text-slate-400">Violation Report</span>
            <i class="fa-solid fa-chevron-right text-slate-300 text-[10px]"></i>
            <span class="text-slate-400">Generate Fix Plan</span>
            <i class="fa-solid fa-chevron-right text-slate-300 text-[10px]"></i>
            <span class="text-slate-400">Apply Fix</span>
            <i class="fa-solid fa-chevron-right text-slate-300 text-[10px]"></i>
            <span class="text-emerald-600 font-extrabold uppercase">Final Validation</span>
        </div>

        <a href="{{ route('renja.format-checker.upload') }}" class="st-btn st-btn-secondary st-btn-xs">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            <span>Periksa Dokumen Lain</span>
        </a>
    </div>

    @php
        $fixesCount = $pipelineResult['fixes_count'] ?? count($pipelineResult['applied_fixes'] ?? []);
        $appliedFixes = $pipelineResult['applied_fixes'] ?? [];
        $isCompliant = $pipelineResult['is_fully_compliant'] ?? false;
        $unfixedIssues = $pipelineResult['unfixed_issues'] ?? [];
        $versionTag = $pipelineResult['version'] ?? 'AutoFix-v1';
        $originalFilename = $pipelineResult['original_filename'] ?? 'Original.docx';
        $fixedFilename = $pipelineResult['fixed_filename'] ?? 'AutoFix-v1.docx';
    @endphp

    <!-- Success Header Card -->
    <div class="st-card p-6 border-l-8 {{ $isCompliant ? 'border-l-emerald-500' : 'border-l-amber-500' }} flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-start space-x-4">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shrink-0 {{ $isCompliant ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
                <i class="fa-solid {{ $isCompliant ? 'fa-circle-check text-emerald-600' : 'fa-triangle-exclamation text-amber-600' }}"></i>
            </div>
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-3 py-0.5 text-xs font-black uppercase rounded-full border {{ $isCompliant ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-800 border-amber-300' }}">
                        AUTOFIX SELESAI: {{ $versionTag }}
                    </span>
                    <span class="px-3 py-0.5 text-xs font-bold uppercase rounded-full bg-slate-100 text-slate-700 border border-slate-300">
                        {{ $fixesCount }} masalah diperbaiki
                    </span>
                </div>
                <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">
                    Perbaikan Format Dokumen Renja: {{ $document->opd->nama_opd }}
                </h1>
                <p class="text-xs text-slate-500">
                    Original: <b class="font-mono text-slate-700">{{ $originalFilename }}</b> (Tetap tersimpan) | Versi Baru: <b class="font-mono text-emerald-700">{{ $fixedFilename }}</b>
                </p>
            </div>
        </div>

        <!-- Primary Actions: Preview & Gunakan Hasil -->
        <div class="shrink-0 flex flex-wrap sm:flex-nowrap gap-3">
            <a href="{{ route('renja.print', $document->id) }}" target="_blank" class="st-btn st-btn-secondary st-btn-md shadow-xs">
                <i class="fa-solid fa-eye text-xs"></i>
                <span>[Preview Hasil AutoFix]</span>
            </a>

            <a href="{{ route('renja.editor', $document->id) }}" class="st-btn st-btn-success st-btn-md shadow-md">
                <i class="fa-solid fa-check-double text-xs"></i>
                <span>[Gunakan Hasil]</span>
            </a>
        </div>
    </div>

    <!-- FIX REPORT CARD (DYNAMIC ACTUAL COUNT) -->
    <div class="st-card p-6 sm:p-8 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles text-emerald-600"></i>
                <span>AUTOFIX SELESAI - LAPORAN PERBAIKAN FORMAT</span>
            </h2>
            <span class="text-xs font-black text-emerald-800 bg-emerald-100 px-3 py-1 rounded-full">
                {{ $fixesCount }} masalah diperbaiki
            </span>
        </div>

        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                @forelse($appliedFixes as $fix)
                    <div class="flex items-start space-x-2.5">
                        <span class="text-emerald-600 font-extrabold text-sm shrink-0">✓</span>
                        <span class="text-slate-800 font-semibold">{!! $fix !!}</span>
                    </div>
                @empty
                    <div class="text-slate-500 italic">Tidak ada perubahan format yang diperlukan.</div>
                @endforelse
            </div>
            
            <div class="pt-3 border-t border-slate-200 text-xs font-bold text-slate-700 flex items-center justify-between">
                <span>Total Perbaikan Format: <b>{{ $fixesCount }} masalah diperbaiki</b></span>
                <span class="text-[11px] text-slate-500 font-normal">*) Dihasilkan dari data diagnosis aktual tanpa mengubah substansi isi dokumen</span>
            </div>
        </div>
    </div>

    <!-- FINAL VALIDATION RE-CHECK REPORT -->
    <div class="st-card p-6 sm:p-8 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-blue-600"></i>
                <span>FINAL VALIDATION (RE-RUN FORMAT CHECKER)</span>
            </h3>
            <span class="px-3 py-1 rounded-full text-xs font-black uppercase {{ $isCompliant ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                {{ $isCompliant ? '✓ Format Sesuai (Validated)' : '⚠️ Perlu Perhatian Sebagian' }}
            </span>
        </div>

        @if($isCompliant)
            <div class="bg-emerald-50 border border-emerald-300 p-4 rounded-xl flex items-center space-x-3 text-xs text-emerald-900">
                <i class="fa-solid fa-circle-check text-emerald-600 text-xl shrink-0"></i>
                <div>
                    <div class="font-extrabold text-emerald-900">Dokumen Lolos Validasi Akhir Format Checker!</div>
                    <div>Dokumen versi <b>{{ $versionTag }}</b> telah memenuhi 100% aturan fisik Perbup/Kepbup Cirebon (Kertas F4, Margin 2cm, Bookman Old Style 12pt, Bebas Bold, No Cover/Header/Footer, & Tabel presisi).</div>
                </div>
            </div>
        @else
            <div class="bg-amber-50 border border-amber-300 p-4 rounded-xl space-y-3">
                <div class="flex items-center space-x-2 text-xs font-extrabold text-amber-900">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base"></i>
                    <span>{{ count($unfixedIssues) }} masalah belum dapat diperbaiki otomatis:</span>
                </div>
                <ul class="space-y-1 text-xs text-amber-800 pl-6 list-disc font-medium">
                    @foreach($unfixedIssues as $issue)
                        <li>⚠ {{ $issue }}</li>
                    @endforeach
                </ul>
                <div class="text-[11px] text-amber-700 italic border-t border-amber-200/60 pt-2">
                    *) Catatan: Format Checker dan AutoFix tidak mengubah substansi teks atau narasi dokumen. Masalah di atas dapat disesuaikan secara manual di Smart Editor.
                </div>
            </div>
        @endif
    </div>

    <!-- VERSIONING & GUARANTEE CARD -->
    <div class="bg-slate-900 text-white p-6 rounded-2xl space-y-3 text-xs shadow-md">
        <div class="flex items-center space-x-2 font-bold text-amber-400 text-sm">
            <i class="fa-solid fa-lock"></i>
            <span>JAMINAN SUBSTANSI & VERSIONING FILE:</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-slate-300">
            <div class="flex items-start space-x-2">
                <span class="text-emerald-400 font-bold">✓</span>
                <span><b>Preservasi Berkas Original</b>: Berkas asli <code class="text-amber-300 font-mono">{{ $originalFilename }}</code> tidak pernah tertimpa dan tetap tersedia di direktori.</span>
            </div>
            <div class="flex items-start space-x-2">
                <span class="text-emerald-400 font-bold">✓</span>
                <span><b>Proteksi Substansi Teks</b>: AutoFix hanya mengubah format (kertas, margin, font, bold, alignment, tabel), tanpa mengubah kalimat, angka, nama, atau target anggaran.</span>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-800 flex items-center justify-end space-x-3">
            <a href="{{ route('renja.print', $document->id) }}" target="_blank" class="st-btn st-btn-secondary st-btn-sm">
                <i class="fa-solid fa-eye text-xs"></i>
                <span>[Preview Hasil AutoFix]</span>
            </a>

            <a href="{{ route('renja.editor', $document->id) }}" class="st-btn st-btn-success st-btn-sm">
                <i class="fa-solid fa-check-double text-xs"></i>
                <span>[Gunakan Hasil]</span>
            </a>
        </div>
    </div>

</div>
@endsection
