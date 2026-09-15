@extends('layouts.app')

@section('title', 'Dokumen Saya - RENJA Murni')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ modalTambahOpen: false, activeTab: 'template' }">

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

    <!-- ========================================== -->
    <!-- 1. HEADER BANNER                           -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-l-4 border-l-amber-500">
        <div>
            <div class="flex items-center space-x-2 text-[11px] font-black uppercase text-slate-400 mb-1">
                <span>Dokumen Saya</span>
                <span>/</span>
                <span class="text-slate-900">RENJA Murni</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-amber-500"></i>
                <span>Dokumen RENJA Murni</span>
            </h1>
            <p class="text-xs text-slate-500 font-medium mt-1">
                Kelola dokumen Rencana Kerja (RENJA) Murni Perangkat Daerah {{ $opd->nama_opd ?? '' }}.
            </p>
        </div>

        <div class="flex items-center space-x-3 shrink-0">
            <!-- TOMBOL + TAMBAH RENJA MURNI -->
            <button type="button" @click="modalTambahOpen = true" 
                    class="st-btn st-btn-amber st-btn-lg shadow-md rounded-2xl font-black text-xs">
                <i class="fa-solid fa-circle-plus text-sm"></i>
                <span>+ Tambah RENJA Murni</span>
            </button>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. TOP EXECUTIVE KPI CARDS                 -->
    <!-- ========================================== -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- TOTAL -->
        <a href="{{ route('operator.renja-murni.index') }}" 
           class="st-card-v2 p-4 border-l-4 border-l-blue-500 hover:shadow-md transition {{ !request('status') && !request('source_type') ? 'ring-2 ring-blue-400 bg-blue-50/40' : '' }}">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Total RENJA</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $kpi['total'] }}</div>
            <div class="text-[10px] text-blue-600 font-bold mt-0.5">Semua Dokumen</div>
        </a>

        <!-- DRAFT -->
        <a href="{{ route('operator.renja-murni.index', ['status' => 'draft']) }}" 
           class="st-card-v2 p-4 border-l-4 border-l-slate-400 hover:shadow-md transition {{ request('status') === 'draft' ? 'ring-2 ring-slate-400 bg-slate-50' : '' }}">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Draft</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $kpi['draft'] }}</div>
            <div class="text-[10px] text-slate-500 font-bold mt-0.5">Dapat Diedit</div>
        </a>

        <!-- PERLU REVISI -->
        <a href="{{ route('operator.renja-murni.index', ['status' => 'revisi']) }}" 
           class="st-card-v2 p-4 border-l-4 border-l-amber-500 hover:shadow-md transition {{ request('status') === 'revisi' ? 'ring-2 ring-amber-400 bg-amber-50/40' : '' }}">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Perlu Revisi</span>
            <div class="text-2xl font-black text-amber-600 mt-1">{{ $kpi['revisi'] }}</div>
            <div class="text-[10px] text-amber-600 font-bold mt-0.5">Catatan Bapperida</div>
        </a>

        <!-- SUBMITTED -->
        <a href="{{ route('operator.renja-murni.index', ['status' => 'submitted']) }}" 
           class="st-card-v2 p-4 border-l-4 border-l-indigo-500 hover:shadow-md transition {{ request('status') === 'submitted' ? 'ring-2 ring-indigo-400 bg-indigo-50/40' : '' }}">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Submitted</span>
            <div class="text-2xl font-black text-indigo-600 mt-1">{{ $kpi['submitted'] }}</div>
            <div class="text-[10px] text-indigo-600 font-bold mt-0.5">Proses Verifikasi</div>
        </a>

        <!-- DISUJUI -->
        <a href="{{ route('operator.renja-murni.index', ['status' => 'final']) }}" 
           class="st-card-v2 p-4 border-l-4 border-l-emerald-500 hover:shadow-md transition {{ request('status') === 'final' ? 'ring-2 ring-emerald-400 bg-emerald-50/40' : '' }}">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Disetujui</span>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ $kpi['approved'] }}</div>
            <div class="text-[10px] text-emerald-600 font-bold mt-0.5">Final & Terkunci</div>
        </a>

        <!-- SUMBER UPLOAD WORD -->
        <a href="{{ route('operator.renja-murni.index', ['source_type' => 'upload_word']) }}" 
           class="st-card-v2 p-4 border-l-4 border-l-sky-500 hover:shadow-md transition {{ request('source_type') === 'upload_word' ? 'ring-2 ring-sky-400 bg-sky-50/40' : '' }}">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Upload Word</span>
            <div class="text-2xl font-black text-sky-600 mt-1">{{ $kpi['upload_source'] }}</div>
            <div class="text-[10px] text-sky-600 font-bold mt-0.5">Dari File .docx</div>
        </a>
    </div>

    <!-- ========================================== -->
    <!-- 3. FILTER & SEARCH BAR                     -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-4 sm:p-5">
        <form method="GET" action="{{ route('operator.renja-murni.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            
            <!-- SEARCH -->
            <div class="sm:col-span-4 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Cari judul dokumen atau tahun..." 
                       class="st-input text-xs pl-9 h-10 rounded-xl w-full">
            </div>

            <!-- FILTER TAHUN ANGGARAN -->
            <div class="sm:col-span-3">
                <select name="tahun_anggaran" class="st-select text-xs h-10 rounded-xl font-bold w-full">
                    <option value="all">Semua Tahun Anggaran</option>
                    @foreach(range(2025, 2035) as $y)
                        <option value="{{ $y }}" {{ request('tahun_anggaran') == $y ? 'selected' : '' }}>TA {{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <!-- FILTER SUMBER DOKUMEN -->
            <div class="sm:col-span-2">
                <select name="source_type" class="st-select text-xs h-10 rounded-xl font-bold w-full">
                    <option value="all">Semua Sumber</option>
                    <option value="template" {{ request('source_type') === 'template' ? 'selected' : '' }}>Template Resmi</option>
                    <option value="upload_word" {{ request('source_type') === 'upload_word' ? 'selected' : '' }}>Upload Word</option>
                </select>
            </div>

            <!-- FILTER STATUS -->
            <div class="sm:col-span-2">
                <select name="status" class="st-select text-xs h-10 rounded-xl font-bold w-full">
                    <option value="all">Semua Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="revisi" {{ request('status') === 'revisi' ? 'selected' : '' }}>Perlu Revisi</option>
                    <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="final" {{ request('status') === 'final' ? 'selected' : '' }}>Disetujui / Final</option>
                </select>
            </div>

            <!-- TOMBOL FILTER & RESET -->
            <div class="sm:col-span-1 flex gap-1.5">
                <button type="submit" class="st-btn st-btn-amber h-10 px-3 rounded-xl flex-1 justify-center" title="Terapkan Filter">
                    <i class="fa-solid fa-filter"></i>
                </button>
                <a href="{{ route('operator.renja-murni.index') }}" class="st-btn st-btn-outline h-10 px-3 rounded-xl justify-center text-slate-500" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>

        </form>
    </div>

    <!-- ========================================== -->
    <!-- 4. TABEL DOKUMEN RENJA MURNI               -->
    <!-- ========================================== -->
    <div class="st-card-v2 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">Tahun Anggaran</th>
                        <th class="py-3.5 px-4">Nama Dokumen</th>
                        <th class="py-3.5 px-4">Jenis Dokumen</th>
                        <th class="py-3.5 px-4">Sumber Dokumen</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Terakhir Diperbarui</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($documents as $doc)
                        <tr class="hover:bg-slate-50/80 transition duration-150">
                            
                            <!-- TAHUN ANGGARAN -->
                            <td class="py-3.5 px-4 font-black text-slate-900">
                                <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 font-black">
                                    TA {{ $doc->tahun_anggaran ?? $doc->year ?? '-' }}
                                </span>
                            </td>

                            <!-- NAMA DOKUMEN -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                    <span>{{ $doc->cover_data['judul_dokumen'] ?? 'RENJA ' . ($doc->opd?->nama_opd ?? 'OPD') }}</span>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    {{ $doc->opd?->nama_opd ?? 'Perangkat Daerah' }}
                                </div>
                            </td>

                            <!-- JENIS DOKUMEN -->
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-800">
                                    {{ $doc->jenis_dokumen ?? 'RENJA Murni' }}
                                </span>
                            </td>

                            <!-- SUMBER DOKUMEN -->
                            <td class="py-3.5 px-4">
                                @if(($doc->source_type ?? '') === 'upload_word')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[10px] font-black bg-blue-50 text-blue-700 border border-blue-200">
                                        <i class="fa-solid fa-file-word text-blue-600"></i>
                                        <span>Upload Word</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fa-solid fa-layer-group text-amber-600"></i>
                                        <span>Template Resmi</span>
                                    </span>
                                @endif
                            </td>

                            <!-- STATUS -->
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black {{ $doc->status_badge_class }}">
                                    {{ $doc->status_label }}
                                </span>
                            </td>

                            <!-- LAST UPDATED -->
                            <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                <div>{{ $doc->updated_at ? $doc->updated_at->isoFormat('D MMMM Y, HH:mm') : '-' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $doc->updated_at ? $doc->updated_at->diffForHumans() : '' }}</div>
                            </td>

                            <!-- AKSI -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end space-x-1.5">
                                    
                                    @php
                                        $isMurniDocFix = in_array(strtolower($doc->status), ['disetujui', 'approved', 'dikunci', 'final']);
                                    @endphp

                                    @if($isMurniDocFix)
                                        <!-- TOMBOL LIHAT DOKUMEN FIX -->
                                        <a href="{{ route('renja.print', $doc->id) }}" 
                                           target="_blank"
                                           class="st-btn bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs st-btn-sm shadow-xs" 
                                           title="Lihat Dokumen Resmi (FIX)">
                                            <i class="fa-solid fa-book-open"></i>
                                            <span class="hidden sm:inline">Lihat Fix</span>
                                        </a>
                                    @else
                                        <!-- TOMBOL EDITOR -->
                                        <a href="{{ route('renja.editor', $doc->id) }}" 
                                           class="st-btn st-btn-amber st-btn-sm font-bold text-xs" 
                                           title="Buka Editor Smart Template">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <span class="hidden sm:inline">Editor</span>
                                        </a>
                                    @endif

                                    <!-- TOMBOL SUBMIT KE ADMIN (Jika status draft / revisi) -->
                                    @if(in_array(strtolower($doc->status), ['draft', 'belum_dikerjakan', 'perlu_revisi', 'revisi', 'revision']))
                                        <form method="POST" action="{{ route('renja.submit', $doc->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin mengirimkan dokumen RENJA Murni ini kepada Admin Bapperida untuk diverifikasi?');" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="st-btn bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs st-btn-sm" 
                                                    title="Submit ke Admin Bapperida">
                                                <i class="fa-solid fa-paper-plane"></i>
                                                <span class="hidden sm:inline">Submit</span>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- DROPDOWN LAINNYA: PRINT, WORD, HAPUS -->
                                    <div x-data="{ openOptions: false }" class="relative inline-block text-left">
                                        <button @click="openOptions = !openOptions" class="st-btn st-btn-outline st-btn-sm px-2 text-slate-500 hover:text-slate-800">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div x-show="openOptions" @click.away="openOptions = false" 
                                             class="origin-top-right absolute right-0 mt-2 w-44 rounded-xl shadow-lg bg-white ring-1 ring-black/5 divide-y divide-slate-100 z-50">
                                            <div class="py-1 text-[11px] font-semibold">
                                                <a href="{{ route('renja.print', $doc->id) }}" target="_blank" class="flex items-center px-3 py-2 text-slate-700 hover:bg-slate-50">
                                                    <i class="fa-solid fa-print w-4 mr-2 text-slate-400"></i> Cetak / Preview
                                                </a>
                                                <a href="{{ route('renja.exportWord', $doc->id) }}" class="flex items-center px-3 py-2 text-blue-700 hover:bg-blue-50">
                                                    <i class="fa-solid fa-file-word w-4 mr-2 text-blue-600"></i> Unduh Word (.docx)
                                                </a>
                                                <a href="{{ route('renja.exportPdf', $doc->id) }}" class="flex items-center px-3 py-2 text-rose-700 hover:bg-rose-50">
                                                    <i class="fa-solid fa-file-pdf w-4 mr-2 text-rose-600"></i> Unduh PDF
                                                </a>
                                            </div>
                                            @if($doc->isEditableByOpd())
                                            <div class="py-1 text-[11px]">
                                                <form method="POST" action="{{ route('renja.destroy', $doc->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen RENJA ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="w-full flex items-center px-3 py-2 text-rose-600 hover:bg-rose-50 font-bold">
                                                        <i class="fa-solid fa-trash-can w-4 mr-2"></i> Hapus Dokumen
                                                    </button>
                                                </form>
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="max-w-xs mx-auto space-y-3">
                                    <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center mx-auto text-2xl border border-amber-200">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </div>
                                    <p class="font-bold text-slate-700 text-sm">Belum ada Dokumen RENJA Murni</p>
                                    <p class="text-xs text-slate-500">
                                        Silakan gunakan Template Resmi untuk mengunduh format Word atau unggah dokumen Word yang telah diisi.
                                    </p>
                                    <button type="button" @click="modalTambahOpen = true" class="st-btn st-btn-amber st-btn-sm font-black mx-auto">
                                        <i class="fa-solid fa-plus-circle"></i>
                                        <span>+ Buat / Upload RENJA Murni</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($documents->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $documents->links() }}
            </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- 5. MODAL: TAMBAH RENJA MURNI               -->
    <!-- ========================================== -->
    <div x-show="modalTambahOpen" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs transition-opacity duration-200"
         x-data="{ selectedTa: '{{ session('active_ta', 2027) }}' }">
        
        <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full p-6 sm:p-7 space-y-5 transform transition-all border border-slate-100"
             @click.away="modalTambahOpen = false">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-black border border-amber-200">
                        <i class="fa-solid fa-circle-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900 leading-tight">Buat Dokumen RENJA Murni</h3>
                        <p class="text-xs text-slate-500 font-medium">Perangkat Daerah: {{ $opd->nama_opd ?? 'Perangkat Daerah' }}</p>
                    </div>
                </div>
                <button @click="modalTambahOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 cursor-pointer">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Tab Switcher -->
            <div class="grid grid-cols-2 gap-2 bg-slate-100 p-1.5 rounded-2xl text-xs font-black">
                <button type="button" 
                        @click="activeTab = 'template'" 
                        :class="activeTab === 'template' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                        class="py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-layer-group text-amber-500"></i>
                    <span>Template Resmi</span>
                </button>

                <button type="button" 
                        @click="activeTab = 'upload'" 
                        :class="activeTab === 'upload' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                        class="py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-cloud-arrow-up text-blue-600"></i>
                    <span>Upload Dokumen</span>
                </button>
            </div>

            <!-- TAB 1: FORM BUAT DARI TEMPLATE -->
            <div x-show="activeTab === 'template'" class="space-y-4 pt-1">
                <!-- Card Download Template Word Resmi -->
                <div class="p-4 bg-amber-50/70 rounded-2xl border border-amber-200/80 space-y-3">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500 text-slate-950 font-black flex items-center justify-center shrink-0 text-base shadow-xs">
                            <i class="fa-solid fa-file-word"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-slate-900 text-sm">Gunakan Template Resmi RENJA Murni</h4>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                Isi template menggunakan Microsoft Word, kemudian upload kembali ke sistem.
                            </p>
                            <p class="text-[11px] text-slate-500 mt-1">
                                Format: Standar F4 Folio, Margin 2 cm, Bookman Old Style 12 pt, Cover, Pengesahan, Kata Pengantar, Daftar Isi, dan BAB I s.d. BAB V.
                            </p>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-amber-200/60 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <span class="text-[11px] font-bold text-amber-900">
                            Tahun Anggaran {{ session('active_ta', 2027) }}
                        </span>
                        <a href="{{ route('renja.templates.download', ['templateCode' => 'RENJA_MURNI', 'tahun_anggaran' => session('active_ta', 2027)]) }}"
                           class="st-btn st-btn-amber font-black text-xs py-2.5 px-5 rounded-xl shadow-md flex items-center justify-center gap-2 w-full sm:w-auto">
                            <i class="fa-solid fa-download text-xs"></i>
                            <span>Download Template Word</span>
                        </a>
                    </div>
                </div>

                <!-- Opsi Buat Online Langsung -->
                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between gap-3">
                    <div class="text-xs text-slate-600">
                        <span class="font-bold text-slate-800">Atau susun langsung secara online?</span>
                        <p class="text-[11px] text-slate-500">Buat draf dokumen langsung di sistem.</p>
                    </div>
                    <form method="POST" action="{{ route('operator.renja-murni.store-template') }}">
                        @csrf
                        <input type="hidden" name="tahun_anggaran" value="{{ session('active_ta', 2027) }}">
                        <button type="submit" class="st-btn bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-2 px-3.5 rounded-xl whitespace-nowrap cursor-pointer">
                            <span>Buat Online</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- TAB 2: FORM UPLOAD DOKUMEN WORD -->
            <div x-show="activeTab === 'upload'" class="space-y-4 pt-1">
                <form method="POST" action="{{ route('operator.renja-murni.store-upload') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="jenis_dokumen" value="RENJA Murni">

                    <!-- Pilih Tahun Anggaran -->
                    <div class="space-y-1.5">
                        <label for="ta_upload" class="block text-xs font-black text-slate-700">Tahun Anggaran</label>
                        <select id="ta_upload" name="tahun_anggaran" class="st-select text-xs font-black h-11 rounded-xl w-full" required>
                            @foreach(range(2025, 2035) as $y)
                                <option value="{{ $y }}" {{ ($y == 2027 || $y == session('active_ta', 2027)) ? 'selected' : '' }}>
                                    Tahun Anggaran {{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="p-3 bg-blue-50/70 rounded-2xl border border-blue-200 text-xs text-blue-900 leading-relaxed">
                        <div class="font-black flex items-center gap-1.5 mb-1">
                            <i class="fa-solid fa-cloud-arrow-up text-blue-600"></i>
                            <span>Upload Dokumen RENJA</span>
                        </div>
                        Upload file Word dokumen RENJA yang telah Anda isi. Dokumen akan tersimpan sebagai draf dan siap untuk dipreview serta dikirim ke Bapperida.
                    </div>

                    <div class="space-y-1.5">
                        <label for="doc_file" class="block text-xs font-black text-slate-700">Pilih File Dokumen RENJA Word (.docx)</label>
                        <input type="file" 
                               id="doc_file"
                               name="document_file" 
                               accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" 
                               class="st-input text-xs h-11 rounded-xl w-full file:mr-4 file:py-1.5 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-black file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-600"
                               required>
                        <p class="text-[11px] text-slate-400 font-medium">Format: <strong>.docx</strong> (Maksimal 20 MB)</p>
                    </div>

                    <div class="pt-3 flex items-center justify-end space-x-2">
                        <button type="button" @click="modalTambahOpen = false" class="st-btn st-btn-outline font-bold text-xs py-2.5 px-4 rounded-xl">
                            Batal
                        </button>
                        <button type="submit" class="st-btn bg-blue-600 hover:bg-blue-700 text-white font-black text-xs py-2.5 px-5 rounded-xl shadow-md cursor-pointer">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>Upload Dokumen RENJA</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

</div>
@endsection
