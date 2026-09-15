@extends('layouts.app')

@section('title', 'Auto-Fix MS Word Formatter')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ isLoading: false, fileName: '' }">

    <!-- Title & Subtitle Header -->
    <div class="st-card p-5 sm:p-6 text-center space-y-2">
        <div class="inline-flex items-center space-x-2 bg-emerald-50 text-emerald-800 border border-emerald-300 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
            <i class="fa-solid fa-soap text-emerald-600"></i>
            <span>Python Smart Template Auto-Formatter Engine</span>
        </div>
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">Mesin Cuci Dokumen Smart Template (Auto-Formatter)</h1>
        <p class="text-slate-500 text-xs max-w-xl mx-auto leading-relaxed">
            Pilih Perangkat Daerah, Jenis Dokumen Template, dan unggah dokumen Microsoft Word (<b>.docx</b>). Mesin Python kami akan mencuci dokumen secara otomatis sesuai aturan template yang dipilih.
        </p>
    </div>

    @if(session('success'))
        <div class="st-card border-2 border-emerald-500 p-6 space-y-5">
            <div class="flex items-center justify-between flex-wrap gap-4 border-b border-slate-100 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl font-bold shrink-0">
                        <i class="fa-solid fa-file-circle-check"></i>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Pencucian Dokumen Berhasil (100% Python Engine)</div>
                        <h3 class="text-lg font-extrabold text-slate-900">{{ session('opd_nama') ?? 'Dokumen Renja' }}</h3>
                        <div class="text-xs text-slate-500 font-semibold">{{ session('opd_romawi') }} • Formatted Bookman Old Style 12pt (F4 Margin 2 cm)</div>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('dashboard') }}" class="st-btn st-btn-secondary st-btn-sm">
                        <i class="fa-solid fa-house text-xs"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>

            <!-- ACTION BUTTONS: PREVIEW VS DOWNLOAD -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @if(session('preview_url'))
                    <a href="{{ session('preview_url') }}" target="_blank" class="st-btn st-btn-primary w-full">
                        <i class="fa-solid fa-eye text-xs"></i>
                        <span>Preview F4 PDF</span>
                    </a>
                @endif

                @if(session('editor_url'))
                    <a href="{{ session('editor_url') }}" class="st-btn st-btn-amber w-full">
                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                        <span>Buka Smart Editor</span>
                    </a>
                @endif

                @if(session('download_url'))
                    <a href="{{ session('download_url') }}" class="st-btn st-btn-success w-full">
                        <i class="fa-solid fa-download text-xs"></i>
                        <span>Unduh File (.docx)</span>
                    </a>
                @endif
            </div>

            <!-- LIVE EMBEDDED PREVIEW FRAME -->
            @if(session('preview_url'))
                <div class="mt-4 border border-slate-300 rounded-2xl overflow-hidden shadow-inner space-y-2 bg-slate-900 p-4">
                    <div class="flex items-center justify-between text-xs text-slate-300 px-2 font-bold">
                        <span class="flex items-center space-x-2">
                            <i class="fa-solid fa-desktop text-amber-400"></i>
                            <span>Pratinjau Lembaran F4 Naskah Hasil Pencucian</span>
                        </span>
                        <a href="{{ session('preview_url') }}" target="_blank" class="text-amber-400 hover:underline">
                            Buka di Tab Baru <i class="fa-solid fa-up-right-from-square text-[10px]"></i>
                        </a>
                    </div>
                    <iframe src="{{ session('preview_url') }}" class="w-full h-[500px] rounded-xl bg-white border border-slate-700"></iframe>
                </div>
            @endif
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl text-xs font-semibold flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-600 text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- UPLOAD FORM CARD -->
    <div class="st-card p-6 sm:p-8 space-y-6">
        <form action="{{ route('formatter.process') }}" method="POST" enctype="multipart/form-data" @submit="isLoading = true" class="space-y-6">
            @csrf
            <input type="hidden" name="tahun_anggaran" value="{{ session('active_ta', 2027) }}">

            <!-- STEP 1: PILIH OPD -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">1. Pilih Perangkat Daerah (OPD)</label>
                <select name="opd_id" required class="st-select">
                    <option value="" disabled {{ !auth()->user()?->opd_id ? 'selected' : '' }}>-- Pilih OPD Pemilik Dokumen --</option>
                    @foreach($opds as $opd)
                        <option value="{{ $opd->id }}" {{ (auth()->user()?->opd_id == $opd->id) ? 'selected' : '' }}>
                            {{ $opd->nama_opd }} ({{ $opd->nomor_lampiran_romawi }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- STEP 2: PILIH TEMPLATE -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">2. Pilih Jenis Template Naskah</label>
                <select name="template_code" required class="st-select">
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->code }}" {{ $loop->first ? 'selected' : '' }}>
                            {{ $tpl->name }} (Kode: {{ $tpl->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- STEP 3: DRAG & DROP UPLOAD FILE -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">3. Unggah Dokumen MS Word (.docx)</label>
                <div class="border-2 border-dashed border-slate-300 hover:border-amber-500 rounded-2xl p-8 text-center bg-slate-50 hover:bg-amber-50/30 transition cursor-pointer relative"
                     @dragover.prevent="" @drop.prevent="fileName = $event.dataTransfer.files[0].name; $refs.fileInput.files = $event.dataTransfer.files">
                    
                    <input type="file" name="document_file" ref="fileInput" required accept=".docx"
                           @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''"
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    
                    <div class="space-y-2 pointer-events-none">
                        <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div class="text-xs font-bold text-slate-800" x-text="fileName ? fileName : 'Tarik & Lepas File .docx di Sini'"></div>
                        <p class="text-[11px] text-slate-500">Atau klik untuk memilih file MS Word dari komputer Anda (Format: .docx)</p>
                    </div>
                </div>
            </div>

            <!-- SUBMIT BUTTON -->
            <button type="submit" :disabled="isLoading" 
                    class="st-btn st-btn-amber st-btn-lg w-full shadow-md">
                <template x-if="!isLoading">
                    <span class="flex items-center space-x-2">
                        <i class="fa-solid fa-wand-magic-sparkles text-sm"></i>
                        <span>Proses Auto-Format (Cuci Dokumen)</span>
                    </span>
                </template>
                <template x-if="isLoading">
                    <span class="flex items-center space-x-2">
                        <i class="fa-solid fa-spinner animate-spin text-sm"></i>
                        <span>Memproses Cuci Dokumen via Python...</span>
                    </span>
                </template>
            </button>

        </form>
    </div>

</div>
@endsection
