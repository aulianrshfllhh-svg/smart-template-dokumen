@extends('layouts.app')

@section('title', 'Upload Dokumen Acuan Baru')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- HEADER -->
    <div class="st-card p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up text-amber-500"></i>
                <span>Upload Dokumen Acuan (.docx)</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Upload file Word acuan (contoh: LAMPIRAN Renja OPD). Parser Python akan otomatis mendeteksi struktur BAB I-V, Sub-Bab, dan Tabel.
            </p>
        </div>

        <a href="{{ route('reference-documents.index') }}" class="st-btn st-btn-secondary st-btn-sm self-start md:self-auto">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            <span>Kembali</span>
        </a>
    </div>

    @if($errors->any())
        <div class="bg-rose-50 border border-rose-300 text-rose-800 px-4 py-3 rounded-xl shadow-2xs text-xs space-y-1">
            <div class="font-bold flex items-center gap-1.5 text-sm">
                <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                <span>Terjadi Kesalahan:</span>
            </div>
            <ul class="list-disc list-inside pl-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- FORM CARD -->
    <form action="{{ route('reference-documents.store') }}" method="POST" enctype="multipart/form-data" class="st-card p-6 sm:p-8 space-y-6">
        @csrf

        <!-- JENIS DOKUMEN -->
        <div>
            <label for="jenis_dokumen" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Jenis Dokumen Acuan <span class="text-rose-500">*</span>
            </label>
            <select name="jenis_dokumen" id="jenis_dokumen" required class="st-select">
                @foreach($jenisDokumenList as $key => $val)
                    <option value="{{ $key }}" {{ old('jenis_dokumen') == $key ? 'selected' : '' }}>{{ $val }}</option>
                @endforeach
            </select>
        </div>

        <!-- FILE UPLOAD DROPZONE -->
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                File Dokumen Word (.docx) <span class="text-rose-500">*</span>
            </label>
            <div class="border-2 border-dashed border-slate-300 hover:border-amber-500 transition rounded-2xl p-8 text-center bg-slate-50 relative cursor-pointer" onclick="document.getElementById('document_file').click()">
                <input type="file" name="document_file" id="document_file" accept=".docx" required class="hidden" onchange="updateFileName(this)">
                
                <div class="space-y-3" id="dropzone-prompt">
                    <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mx-auto text-2xl">
                        <i class="fa-solid fa-file-word"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800">Klik untuk memilih file .docx</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Format file Microsoft Word (.docx), Ukuran Maksimal 20 MB</p>
                    </div>
                </div>

                <div id="file-info" class="hidden space-y-2">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto text-xl">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <p class="text-xs font-extrabold text-slate-900" id="file-name-display"></p>
                    <p class="text-[10px] text-slate-500">Klik untuk mengganti file</p>
                </div>
            </div>
        </div>

        <!-- BUTTONS -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100">
            <a href="{{ route('reference-documents.index') }}" class="st-btn st-btn-secondary">
                Batal
            </a>
            <button type="submit" class="st-btn st-btn-amber">
                <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
                <span>Proses Extract Schema</span>
            </button>
        </div>
    </form>

</div>

@push('scripts')
<script>
    function updateFileName(input) {
        if (input.files && input.files[0]) {
            document.getElementById('dropzone-prompt').classList.add('hidden');
            document.getElementById('file-info').classList.remove('hidden');
            document.getElementById('file-name-display').textContent = input.files[0].name;
        }
    }
</script>
@endpush
@endsection
