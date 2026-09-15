@extends('layouts.app')

@section('title', 'Builder Struktur & Format F4 Template - Bapperida')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ activeTab: 'structure' }">

    <!-- FLASH NOTIFICATION -->
    @if(session('success'))
        <div class="bg-emerald-50/90 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-2xl text-xs font-bold flex items-center space-x-2.5 shadow-xs">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- 1. HEADER EDIT BANNER -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-l-4 border-l-amber-500">
        <div>
            <div class="flex items-center space-x-2 text-[11px] font-black uppercase text-slate-400 mb-1">
                <a href="{{ route('admin.templates.index') }}" class="hover:text-amber-600 transition">Manajemen Template</a>
                <span>/</span>
                <span class="text-slate-900">Builder Struktur & Format F4</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                {{ $template->name }}
            </h1>
            <p class="text-xs text-slate-500 font-medium mt-1">
                Kode Template: <span class="font-mono font-bold text-amber-600">{{ $template->code }}</span> • {{ $template->sections->count() }} Seksi Terdaftar
            </p>
        </div>

        <div class="flex items-center space-x-3 shrink-0">
            <a href="{{ route('admin.templates.index') }}" class="st-btn st-btn-secondary st-btn-sm rounded-xl font-bold">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Kembali ke Master Template</span>
            </a>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. TAB BUTTONS (CONFIG vs STRUCTURE) -->
    <!-- ========================================== -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-2">
        <button @click="activeTab = 'structure'" 
                :class="activeTab === 'structure' ? 'bg-slate-900 text-white font-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" 
                class="px-4 py-2 rounded-xl text-xs transition flex items-center space-x-2">
            <i class="fa-solid fa-sitemap text-xs"></i>
            <span>Struktur Bab I - VI & Guidance</span>
        </button>

        <button @click="activeTab = 'config'" 
                :class="activeTab === 'config' ? 'bg-slate-900 text-white font-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" 
                class="px-4 py-2 rounded-xl text-xs transition flex items-center space-x-2">
            <i class="fa-solid fa-gear text-xs"></i>
            <span>Konfigurasi Kertas F4 & Margins</span>
        </button>
    </div>

    <!-- ========================================== -->
    <!-- 3. TAB 1: KONFIGURASI KERTAS F4 & MARGINS -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'config'" class="st-card-v2 p-5 sm:p-6 space-y-6">
        <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900">Pengaturan Standar Format Kertas F4 & Margins</h3>
            <p class="text-[11px] text-slate-500 font-medium">Sesuai Perbup Cirebon: Ukuran Kertas F4 (215 x 330 mm), Margin Atas/Bawah/Kiri: 2.5 cm, Kanan: 3.0 cm</p>
        </div>

        <form action="{{ route('admin.templates.update', $template->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700">Nama Template:</label>
                    <input type="text" name="name" value="{{ $template->name }}" class="st-input text-xs rounded-xl" required>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700">Deskripsi Template:</label>
                    <input type="text" name="description" value="{{ $template->description }}" class="st-input text-xs rounded-xl">
                </div>
            </div>

            <!-- F4 PAPER DIMENSIONS -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-200">
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-600">Lebar Kertas (mm):</label>
                    <input type="number" name="paper_width_mm" value="{{ $template->format_config['paper_width_mm'] ?? 215 }}" class="st-input text-xs rounded-xl font-mono">
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-600">Tinggi Kertas (mm):</label>
                    <input type="number" name="paper_height_mm" value="{{ $template->format_config['paper_height_mm'] ?? 330 }}" class="st-input text-xs rounded-xl font-mono">
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-600">Margin Atas (cm):</label>
                    <input type="number" step="0.1" name="margin_top_cm" value="{{ $template->format_config['margin_top_cm'] ?? 2.5 }}" class="st-input text-xs rounded-xl font-mono">
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-600">Margin Bawah (cm):</label>
                    <input type="number" step="0.1" name="margin_bottom_cm" value="{{ $template->format_config['margin_bottom_cm'] ?? 2.5 }}" class="st-input text-xs rounded-xl font-mono">
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-600">Margin Kiri (cm):</label>
                    <input type="number" step="0.1" name="margin_left_cm" value="{{ $template->format_config['margin_left_cm'] ?? 2.5 }}" class="st-input text-xs rounded-xl font-mono">
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-600">Margin Kanan (cm):</label>
                    <input type="number" step="0.1" name="margin_right_cm" value="{{ $template->format_config['margin_right_cm'] ?? 3.0 }}" class="st-input text-xs rounded-xl font-mono">
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-600">Jenis Font Naskah:</label>
                    <select name="font_family" class="st-select text-xs rounded-xl">
                        <option value="Bookman Old Style" {{ ($template->format_config['font_family'] ?? '') == 'Bookman Old Style' ? 'selected' : '' }}>Bookman Old Style</option>
                        <option value="Calibri" {{ ($template->format_config['font_family'] ?? '') == 'Calibri' ? 'selected' : '' }}>Calibri</option>
                        <option value="Arial" {{ ($template->format_config['font_family'] ?? '') == 'Arial' ? 'selected' : '' }}>Arial</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-600">Ukuran Font (pt):</label>
                    <input type="number" name="font_size_pt" value="{{ $template->format_config['font_size_pt'] ?? 12 }}" class="st-input text-xs rounded-xl font-mono">
                </div>
            </div>

            <button type="submit" class="st-btn st-btn-primary st-btn-sm rounded-xl font-bold">
                <i class="fa-solid fa-floppy-disk text-xs"></i>
                <span>Simpan Konfigurasi Format F4</span>
            </button>
        </form>
    </div>

    <!-- ========================================== -->
    <!-- 4. TAB 2: STRUKTUR BAB I S/D VI & GUIDANCE -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'structure'" class="space-y-6">
        
        <!-- HEADER & TOMBOL TAMBAH SEKSI -->
        <div class="st-card-v2 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-black text-slate-900">Kelola Seksi Bab I s/d VI & Teks Petunjuk OPD</h3>
                <p class="text-[11px] text-slate-500 font-medium">Struktur seksi di bawah ini menjadi acuan otomatis (*snapshot*) saat OPD menyusun dokumen baru</p>
            </div>

            <button type="button" @click="document.getElementById('modal-tambah-seksi').classList.remove('hidden')" 
                    class="st-btn st-btn-amber st-btn-sm rounded-xl font-bold shadow-xs shrink-0">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>+ Tambah Seksi Bab Baru</span>
            </button>
        </div>

        <!-- LIST SEKSI BAB TEMPLATE -->
        <div class="space-y-4">
            @forelse($template->sections as $sec)
                <div class="st-card-v2 p-5 space-y-3 border-l-4 {{ $sec->section_type === 'chapter' ? 'border-l-slate-900 bg-slate-50/50' : 'border-l-amber-500' }}">
                    <form action="{{ route('admin.templates.updateSection', [$template->id, $sec->id]) }}" method="POST" class="space-y-3">
                        @csrf
                        @method('PUT')

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/80 pb-2.5">
                            <div class="flex items-center space-x-2">
                                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full {{ $sec->section_type === 'chapter' ? 'bg-slate-900 text-amber-400' : 'bg-amber-100 text-amber-900 border border-amber-300' }}">
                                    {{ strtoupper($sec->section_type) }}
                                </span>
                                <span class="text-xs font-mono font-bold text-slate-500">#{{ $sec->sequence }}</span>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button type="submit" class="st-btn st-btn-primary st-btn-sm h-7.5 px-3 rounded-xl text-[11px] font-bold">
                                    <i class="fa-solid fa-check text-[10px]"></i>
                                    <span>Simpan Perubahan</span>
                                </button>

                                <form action="{{ route('admin.templates.destroySection', [$template->id, $sec->id]) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Hapus seksi ini dari master template?')" 
                                            class="st-btn st-btn-danger st-btn-sm h-7.5 px-2.5 rounded-xl text-[11px] font-bold" title="Hapus Seksi">
                                        <i class="fa-solid fa-trash text-[10px]"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                            <div class="sm:col-span-3 space-y-1">
                                <label class="text-[11px] font-bold text-slate-600">Kode Seksi / Bab:</label>
                                <input type="text" name="code" value="{{ $sec->code }}" class="st-input text-xs font-mono rounded-xl" required>
                            </div>

                            <div class="sm:col-span-9 space-y-1">
                                <label class="text-[11px] font-bold text-slate-600">Judul Seksi / Bab:</label>
                                <input type="text" name="title" value="{{ $sec->title }}" class="st-input text-xs font-bold rounded-xl" required>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[11px] font-bold text-slate-600 flex items-center gap-1">
                                <i class="fa-solid fa-lightbulb text-amber-500"></i>
                                <span>Teks Petunjuk Pengisian untuk OPD (Guidance Text):</span>
                            </label>
                            <textarea name="guidance_text" rows="2" placeholder="Tuliskan petunjuk apa yang harus diisi OPD pada seksi ini..." class="st-textarea text-xs rounded-xl">{{ $sec->guidance_text }}</textarea>
                        </div>
                    </form>
                </div>
            @empty
                <div class="st-card-v2 p-8 text-center text-slate-500 text-xs">
                    Belum ada seksi bab yang didaftarkan. Klik "+ Tambah Seksi Bab Baru" untuk mulai menambahkan.
                </div>
            @endforelse
        </div>

    </div>

</div>

<!-- ========================================== -->
<!-- MODAL TAMBAH SEKSI BAB BARU -->
<!-- ========================================== -->
<div id="modal-tambah-seksi" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-slate-200 animate-in fade-in zoom-in duration-150">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900">Tambah Seksi Bab Baru ke Master Template</h3>
            <button type="button" onclick="document.getElementById('modal-tambah-seksi').classList.add('hidden')" class="text-slate-400 hover:text-slate-700">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <form action="{{ route('admin.templates.storeSection', $template->id) }}" method="POST" class="space-y-4">
            @csrf
            
            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Tipe Seksi:</label>
                <select name="section_type" class="st-select text-xs rounded-xl">
                    <option value="chapter">Bab Utama (Chapter)</option>
                    <option value="subchapter" selected>Subbab (Sub-Chapter)</option>
                    <option value="cover">Halaman Cover</option>
                    <option value="preface">Kata Pengantar</option>
                    <option value="appendix">Lampiran Tambahan</option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Kode Seksi (misal: BAB III atau 3.1):</label>
                <input type="text" name="code" placeholder="contoh: 3.1" class="st-input text-xs font-mono rounded-xl" required>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Judul Seksi / Subbab:</label>
                <input type="text" name="title" placeholder="contoh: Program dan Kegiatan Strategis Daerah" class="st-input text-xs rounded-xl" required>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Teks Petunjuk Pengisian OPD (Guidance Text):</label>
                <textarea name="guidance_text" rows="3" placeholder="Petunjuk bantuan untuk operator OPD..." class="st-textarea text-xs rounded-xl"></textarea>
            </div>

            <div class="pt-2 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('modal-tambah-seksi').classList.add('hidden')" class="st-btn st-btn-secondary st-btn-sm rounded-xl font-bold">Batal</button>
                <button type="submit" class="st-btn st-btn-primary st-btn-sm rounded-xl font-bold">+ Simpan Seksi Baru</button>
            </div>
        </form>
    </div>
</div>
@endsection
