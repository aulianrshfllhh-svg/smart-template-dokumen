@php
    $registryService = app(\App\Services\DocumentRegistryService::class);
    $activeTa = session('active_ta', (int) date('Y'));
    $variants = $registryService->getVariants('RENJA', $activeTa);

    $user = Auth::user();
    $opdId = $user ? ($user->opd_id ?? \App\Models\MasterOpd::first()?->id) : null;

    $existingMurni = $opdId ? $registryService->checkExistingDocument($opdId, 'RENJA_MURNI', $activeTa) : null;
    $existingPerubahan = $opdId ? $registryService->checkExistingDocument($opdId, 'RENJA_PERUBAHAN', $activeTa) : null;
    
    $murniTa = $activeTa + 1;
    $perubahanTa = $activeTa;
@endphp

<!-- MODAL BUAT DOKUMEN (STANDALONE ZERO-FAILURE HYBRID) -->
<div id="modal-buat-dokumen-opd"
     style="display: {{ request('open_modal') ? 'flex' : 'none' }};"
     class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs z-50 items-center justify-center p-3 sm:p-4 overflow-y-auto">

    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full border border-slate-200 overflow-hidden flex flex-col max-h-[92vh] my-auto"
         onclick="event.stopPropagation()">

        <!-- ========================================== -->
        <!-- MODAL HEADER                               -->
        <!-- ========================================== -->
        <div class="p-5 sm:p-6 border-b border-slate-100 bg-linear-to-r from-slate-900 to-slate-800 text-white shrink-0 relative">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-400/30 flex items-center justify-center text-amber-400 shrink-0 shadow-xs">
                        <i class="fa-solid fa-file-circle-plus text-lg"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-amber-400">e-Dokumen Perencanaan RENJA</div>
                        <h3 class="text-base sm:text-lg font-black tracking-tight">Buat Dokumen Baru</h3>
                    </div>
                </div>
                <button type="button" 
                        onclick="closeModalBuatDokumen()" 
                        class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- STEP BREADCRUMB PROGRESS -->
            <div class="grid grid-cols-2 gap-2 mt-4 text-[11px] font-black uppercase">
                <button type="button" 
                        id="modal-tab-btn-1"
                        onclick="modalGoToStep(1)" 
                        class="py-2 px-3 rounded-xl border transition flex items-center justify-center gap-1.5 cursor-pointer text-left bg-amber-500 text-slate-950 border-amber-400 font-black shadow-xs">
                    <span id="modal-tab-num-1" class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] bg-slate-950/40 text-amber-400">1</span>
                    <span class="truncate">1. Pilih Jenis Dokumen</span>
                </button>

                <button type="button" 
                        id="modal-tab-btn-2"
                        onclick="modalGoToStep(2)" 
                        class="py-2 px-3 rounded-xl border transition flex items-center justify-center gap-1.5 cursor-pointer text-left bg-white/5 text-slate-400 border-white/10">
                    <span id="modal-tab-num-2" class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] bg-white/10 text-slate-400">2</span>
                    <span class="truncate">2. Metode Pembuatan</span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL BODY                                 -->
        <!-- ========================================== -->
        <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-5">

            <!-- ========================================== -->
            <!-- STEP 1: PILIH JENIS DOKUMEN (MURNI / PERUBAHAN) -->
            <!-- ========================================== -->
            <div id="modal-step-1" class="space-y-4">
                <div class="space-y-1">
                    <h4 class="text-sm font-black text-slate-900">Pilih Jenis Dokumen yang Akan Dibuat</h4>
                    <p class="text-xs text-slate-500 font-medium">Pilih antara RENJA Murni atau RENJA Perubahan untuk siklus tahun anggaran aktif (TA {{ $activeTa }}).</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- PILIHAN 1: RENJA MURNI -->
                    <div id="card-option-renja-murni"
                         onclick="modalSelectVariantAndAdvance('RENJA_MURNI')"
                         class="p-4 sm:p-5 rounded-2xl border-2 border-slate-200 hover:border-amber-400 bg-white cursor-pointer transition-all flex flex-col justify-between hover:shadow-md relative group">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="w-11 h-11 rounded-2xl bg-amber-500 text-slate-950 flex items-center justify-center text-lg font-black shadow-xs group-hover:scale-105 transition-transform">
                                    <i class="fa-solid fa-file-lines"></i>
                                </div>
                                <span class="px-2.5 py-1 bg-amber-100 border border-amber-300 text-amber-900 text-[10px] font-black rounded-lg">
                                    Tahun: {{ $murniTa }}
                                </span>
                            </div>
                            <div>
                                <h5 class="text-base font-black text-slate-900 group-hover:text-amber-800 transition">RENJA Murni</h5>
                                <div class="text-xs font-bold text-slate-600">Rencana Kerja Awal (TA {{ $murniTa }})</div>
                                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                                    Dokumen Rencana Kerja Perangkat Daerah untuk tahun anggaran berikutnya (TA berjalan + 1).
                                </p>
                            </div>

                            @if($existingMurni)
                                <div class="px-2.5 py-1.5 bg-rose-50 border border-rose-200 text-rose-700 text-[10px] font-bold rounded-lg flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    <span>Dokumen RENJA Murni sudah ada di sistem</span>
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-black text-amber-700 group-hover:text-amber-900">
                            <span>Pilih RENJA Murni</span>
                            <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>

                    <!-- PILIHAN 2: RENJA PERUBAHAN -->
                    <div id="card-option-renja-perubahan"
                         onclick="modalSelectVariantAndAdvance('RENJA_PERUBAHAN')"
                         class="p-4 sm:p-5 rounded-2xl border-2 border-slate-200 hover:border-purple-400 bg-white cursor-pointer transition-all flex flex-col justify-between hover:shadow-md relative group">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="w-11 h-11 rounded-2xl bg-purple-600 text-white flex items-center justify-center text-lg font-black shadow-xs group-hover:scale-105 transition-transform">
                                    <i class="fa-solid fa-file-pen"></i>
                                </div>
                                <span class="px-2.5 py-1 bg-purple-100 border border-purple-300 text-purple-900 text-[10px] font-black rounded-lg">
                                    Tahun: {{ $perubahanTa }}
                                </span>
                            </div>
                            <div>
                                <h5 class="text-base font-black text-slate-900 group-hover:text-purple-800 transition">RENJA Perubahan</h5>
                                <div class="text-xs font-bold text-slate-600">Perubahan Rencana Kerja (TA {{ $perubahanTa }})</div>
                                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                                    Dokumen penyesuaian target kinerja dan pagu anggaran untuk tahun anggaran berjalan (TA {{ $perubahanTa }}).
                                </p>
                            </div>

                            @if($existingPerubahan)
                                <div class="px-2.5 py-1.5 bg-rose-50 border border-rose-200 text-rose-700 text-[10px] font-bold rounded-lg flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    <span>Dokumen RENJA Perubahan sudah ada di sistem</span>
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-black text-purple-700 group-hover:text-purple-900">
                            <span>Pilih RENJA Perubahan</span>
                            <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>

                <!-- DERIVED LAMPIRAN EXPLANATION NOTE -->
                <div class="p-3.5 bg-blue-50/70 border border-blue-200 rounded-2xl flex items-start space-x-3 text-xs text-blue-900">
                    <i class="fa-solid fa-circle-info text-blue-600 text-sm mt-0.5 shrink-0"></i>
                    <div class="leading-relaxed">
                        <span class="font-bold">Informasi Lampiran Otomatis:</span> Dokumen Lampiran Perbup / Kepbup merupakan <em>dokumen turunan otomatis</em> yang tersinkronisasi langsung dari Bab & Tabel Utama. Dokumen Lampiran tidak perlu dibuat secara terpisah.
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- STEP 2: PILIH METODE PEMBUATAN             -->
            <!-- ========================================== -->
            <div id="modal-step-2" style="display: none;" class="space-y-4">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2 text-[10px] font-black uppercase text-slate-400">
                        <span id="step-2-variant-badge" class="text-amber-600">RENJA Murni (TA {{ $murniTa }})</span>
                        <span>/</span>
                        <span>Pilih Metode Pembuatan</span>
                    </div>
                    <h4 class="text-sm font-black text-slate-900">Pilih Cara Pembuatan Dokumen</h4>
                    <p class="text-xs text-slate-500 font-medium">Pilih metode penyusunan yang paling sesuai dengan alur kerja Perangkat Daerah Anda.</p>
                </div>

                <!-- DUPLICATE WARNING BOXES -->
                <div id="duplicate-warning-murni" style="display: none;" class="p-4 bg-amber-50 border-2 border-amber-300 rounded-2xl space-y-3">
                    <div class="flex items-start space-x-3">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg mt-0.5 shrink-0"></i>
                        <div>
                            <h5 class="text-xs font-black text-amber-950 uppercase tracking-wider">Dokumen Sudah Ada di Sistem</h5>
                            <p class="text-xs text-amber-900 font-medium mt-0.5 leading-relaxed">
                                Perangkat Daerah Anda telah memiliki draf/dokumen <strong>RENJA Murni TA {{ $murniTa }}</strong>. Anda dapat langsung membuka dokumen tersebut atau beralih ke varian lain.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2 pt-2 border-t border-amber-200">
                        <button type="button" 
                                onclick="modalSelectVariantAndAdvance('RENJA_PERUBAHAN')"
                                class="text-xs text-amber-900 hover:text-amber-950 font-bold underline cursor-pointer text-left">
                            Beralih ke RENJA Perubahan &rarr;
                        </button>
                        @if($existingMurni)
                        <a href="{{ url('/renja-documents/' . $existingMurni->id . '/editor') }}" 
                           class="st-btn st-btn-amber st-btn-sm font-black shadow-xs flex items-center justify-center space-x-1.5 cursor-pointer">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            <span>Buka Dokumen di Editor</span>
                        </a>
                        @endif
                    </div>
                </div>

                <div id="duplicate-warning-perubahan" style="display: none;" class="p-4 bg-purple-50 border-2 border-purple-300 rounded-2xl space-y-3">
                    <div class="flex items-start space-x-3">
                        <i class="fa-solid fa-triangle-exclamation text-purple-600 text-lg mt-0.5 shrink-0"></i>
                        <div>
                            <h5 class="text-xs font-black text-purple-950 uppercase tracking-wider">Dokumen Sudah Ada di Sistem</h5>
                            <p class="text-xs text-purple-900 font-medium mt-0.5 leading-relaxed">
                                Perangkat Daerah Anda telah memiliki draf/dokumen <strong>RENJA Perubahan TA {{ $perubahanTa }}</strong>. Anda dapat langsung membuka dokumen tersebut atau beralih ke varian lain.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2 pt-2 border-t border-purple-200">
                        <button type="button" 
                                onclick="modalSelectVariantAndAdvance('RENJA_MURNI')"
                                class="text-xs text-purple-900 hover:text-purple-950 font-bold underline cursor-pointer text-left">
                            Beralih ke RENJA Murni &rarr;
                        </button>
                        @if($existingPerubahan)
                        <a href="{{ url('/renja-documents/' . $existingPerubahan->id . '/editor') }}" 
                           class="st-btn st-btn-purple st-btn-sm font-black shadow-xs flex items-center justify-center space-x-1.5 cursor-pointer text-white bg-purple-600">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            <span>Buka Dokumen di Editor</span>
                        </a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <!-- METHOD 1: TEMPLATE RESMI -->
                    <div id="method-card-template"
                         onclick="modalSelectMethod('template')"
                         class="p-4 rounded-2xl border-2 border-amber-500 bg-amber-50/50 ring-2 ring-amber-400/30 cursor-pointer transition-all flex flex-col justify-between">
                        <div class="space-y-2.5">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg font-black">
                                <i class="fa-solid fa-stamp"></i>
                            </div>
                            <div>
                                <h5 class="text-sm font-black text-slate-900">Gunakan Template Resmi</h5>
                                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                    Susun langsung di Editor Online berbasis struktur standar Pemkab Cirebon atau download template Word resmi.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[10px] font-bold text-amber-700">Editor Online & Template .docx</span>
                            <span id="method-check-template" class="w-5 h-5 rounded-full border bg-amber-500 border-amber-600 text-slate-950 font-bold flex items-center justify-center text-xs">
                                <i class="fa-solid fa-check text-[10px]"></i>
                            </span>
                        </div>
                    </div>

                    <!-- METHOD 2: UPLOAD DOKUMEN YANG SUDAH ADA -->
                    <div id="method-card-upload"
                         onclick="modalSelectMethod('upload')"
                         class="p-4 rounded-2xl border-2 border-slate-200 hover:border-slate-300 bg-white cursor-pointer transition-all flex flex-col justify-between">
                        <div class="space-y-2.5">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-lg font-black">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <div>
                                <h5 class="text-sm font-black text-slate-900">Upload Dokumen Word (.docx)</h5>
                                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                    Unggah naskah Word yang sudah disusun offline. Sistem membaca dan memetakan struktur BAB secara transparan.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[10px] font-bold text-blue-700">Import File .docx Langsung</span>
                            <span id="method-check-upload" class="w-5 h-5 rounded-full border border-slate-300 flex items-center justify-center text-xs">
                            </span>
                        </div>
                    </div>
                </div>

                <!-- SUB-PANEL UNTUK METHOD 1: TEMPLATE RESMI -->
                <div id="subpanel-method-template" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                    <div class="text-xs font-black text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-gear text-amber-600"></i>
                        <span>Pilihan Aksi Template Resmi</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <!-- ACTION 1A: DOWNLOAD TEMPLATE RESMI -->
                        <button type="button" 
                                onclick="downloadCurrentTemplate()"
                                class="p-3 bg-white border border-slate-200 hover:border-amber-400 hover:bg-amber-50/40 rounded-xl text-left transition flex items-center space-x-3 group cursor-pointer w-full">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-file-word text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-900 group-hover:text-amber-800 truncate">Download Template Word</div>
                                <div class="text-[10px] text-slate-500">File template .docx resmi</div>
                            </div>
                        </button>

                        <!-- ACTION 1B: BUAT DRAFT DI EDITOR ONLINE -->
                        <form action="{{ route('renja.store') }}" method="POST" class="m-0">
                            @csrf
                            <input type="hidden" name="variant_key" id="form-create-variant-key" value="RENJA_MURNI">
                            <input type="hidden" name="document_family" value="RENJA">
                            <input type="hidden" name="tahun_anggaran" id="form-create-ta" value="{{ $murniTa }}">
                            <input type="hidden" name="creation_method" value="template_official">

                            <button type="submit" 
                                    class="w-full p-3 bg-amber-500 hover:bg-amber-600 border border-amber-600 text-slate-950 font-black rounded-xl text-left transition flex items-center space-x-3 shadow-xs cursor-pointer">
                                <div class="w-8 h-8 rounded-lg bg-slate-950 text-amber-400 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-pen-nib text-sm"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-black truncate">Buat Draft di Editor Online</div>
                                    <div class="text-[10px] text-slate-900/80">Mulai menyusun narasi</div>
                                </div>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- SUB-PANEL UNTUK METHOD 2: UPLOAD WORD FORM -->
                <div id="subpanel-method-upload" style="display: none;" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                    <form action="{{ route('operator.renja-murni.store-upload') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="hidden" name="tahun_anggaran" id="upload-form-ta" value="{{ $murniTa }}">
                        <input type="hidden" name="jenis_dokumen" id="upload-form-jenis" value="RENJA Murni">

                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">
                                Pilih File Microsoft Word (.docx) <span class="text-rose-500">*</span>
                            </label>
                            <input type="file" 
                                   name="document_file" 
                                   accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" 
                                   required
                                   class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:cursor-pointer border border-slate-300 rounded-xl bg-white p-1">
                            <p class="text-[10px] text-slate-500 mt-1">Maksimal ukuran file: 20 MB. Dokumen akan dipetakan ke struktur BAB tanpa pengubahan sepihak.</p>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="st-btn st-btn-primary st-btn-sm font-black shadow-xs flex items-center space-x-1.5 cursor-pointer">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>Upload & Buka di Editor</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- MODAL FOOTER NAVIGATION                    -->
        <!-- ========================================== -->
        <div class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50 flex items-center justify-between shrink-0">
            <div>
                <button type="button" 
                        id="modal-btn-back"
                        style="display: none;"
                        onclick="modalGoToStep(1)" 
                        class="st-btn st-btn-secondary st-btn-sm font-bold flex items-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Kembali</span>
                </button>
            </div>

            <div class="flex items-center space-x-2">
                <button type="button" 
                        onclick="closeModalBuatDokumen()" 
                        class="st-btn st-btn-secondary st-btn-sm font-bold cursor-pointer">
                    Tutup
                </button>

                <button type="button" 
                        id="modal-btn-next"
                        onclick="modalGoToStep(2)" 
                        class="st-btn st-btn-amber st-btn-sm font-black shadow-xs flex items-center space-x-1.5 cursor-pointer">
                    <span>Lanjut ke Metode</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </div>
        </div>

    </div>
</div>

<script>
    // Pure Vanilla JS Controller (Zero External Dependency)
    let currentModalVariant = 'RENJA_MURNI';
    let currentModalMethod = 'template';
    let currentModalStep = 1;
    const existingDocsData = {
        'RENJA_MURNI': @json($existingMurni ? true : false),
        'RENJA_PERUBAHAN': @json($existingPerubahan ? true : false)
    };
    const murniTaValue = {{ $murniTa }};
    const perubahanTaValue = {{ $perubahanTa }};

    window.openModalBuatDokumen = function(variant = null) {
        const modal = document.getElementById('modal-buat-dokumen-opd');
        if (modal) {
            modal.style.display = 'flex';
            if (variant) {
                modalSelectVariantAndAdvance(variant);
            } else {
                modalGoToStep(1);
            }
        }
    };

    window.closeModalBuatDokumen = function() {
        const modal = document.getElementById('modal-buat-dokumen-opd');
        if (modal) {
            modal.style.display = 'none';
        }
        modalGoToStep(1);
    };

    window.modalGoToStep = function(step) {
        currentModalStep = step;
        const step1 = document.getElementById('modal-step-1');
        const step2 = document.getElementById('modal-step-2');
        const tab1 = document.getElementById('modal-tab-btn-1');
        const tab2 = document.getElementById('modal-tab-btn-2');
        const num1 = document.getElementById('modal-tab-num-1');
        const num2 = document.getElementById('modal-tab-num-2');
        const btnBack = document.getElementById('modal-btn-back');
        const btnNext = document.getElementById('modal-btn-next');

        if (step === 1) {
            if (step1) step1.style.display = 'block';
            if (step2) step2.style.display = 'none';
            if (btnBack) btnBack.style.display = 'none';
            if (btnNext) btnNext.style.display = 'flex';

            if (tab1) {
                tab1.className = 'py-2 px-3 rounded-xl border transition flex items-center justify-center gap-1.5 cursor-pointer text-left bg-amber-500 text-slate-950 border-amber-400 font-black shadow-xs';
            }
            if (tab2) {
                tab2.className = 'py-2 px-3 rounded-xl border transition flex items-center justify-center gap-1.5 cursor-pointer text-left bg-white/5 text-slate-400 border-white/10';
            }
            if (num1) num1.className = 'w-4 h-4 rounded-full flex items-center justify-center text-[9px] bg-slate-950/40 text-amber-400';
            if (num2) num2.className = 'w-4 h-4 rounded-full flex items-center justify-center text-[9px] bg-white/10 text-slate-400';
        } else {
            if (step1) step1.style.display = 'none';
            if (step2) step2.style.display = 'block';
            if (btnBack) btnBack.style.display = 'flex';
            if (btnNext) btnNext.style.display = 'none';

            if (tab1) {
                tab1.className = 'py-2 px-3 rounded-xl border transition flex items-center justify-center gap-1.5 cursor-pointer text-left bg-white/10 text-emerald-300 border-white/20';
            }
            if (tab2) {
                tab2.className = 'py-2 px-3 rounded-xl border transition flex items-center justify-center gap-1.5 cursor-pointer text-left bg-amber-500 text-slate-950 border-amber-400 font-black shadow-xs';
            }
            if (num1) num1.className = 'w-4 h-4 rounded-full flex items-center justify-center text-[9px] bg-emerald-500 text-white';
            if (num2) num2.className = 'w-4 h-4 rounded-full flex items-center justify-center text-[9px] bg-slate-950/40 text-amber-400';

            updateStep2View();
        }
    };

    window.modalSelectVariantAndAdvance = function(variant) {
        currentModalVariant = variant;
        modalGoToStep(2);
    };

    function updateStep2View() {
        const badge = document.getElementById('step-2-variant-badge');
        const warnMurni = document.getElementById('duplicate-warning-murni');
        const warnPerubahan = document.getElementById('duplicate-warning-perubahan');
        const formVariant = document.getElementById('form-create-variant-key');
        const formTa = document.getElementById('form-create-ta');
        const uploadTa = document.getElementById('upload-form-ta');
        const uploadJenis = document.getElementById('upload-form-jenis');

        if (currentModalVariant === 'RENJA_MURNI') {
            if (badge) badge.innerText = 'RENJA Murni (TA ' + murniTaValue + ')';
            if (warnMurni) warnMurni.style.display = existingDocsData['RENJA_MURNI'] ? 'block' : 'none';
            if (warnPerubahan) warnPerubahan.style.display = 'none';
            if (formVariant) formVariant.value = 'RENJA_MURNI';
            if (formTa) formTa.value = murniTaValue;
            if (uploadTa) uploadTa.value = murniTaValue;
            if (uploadJenis) uploadJenis.value = 'RENJA Murni';
        } else {
            if (badge) badge.innerText = 'RENJA Perubahan (TA ' + perubahanTaValue + ')';
            if (warnMurni) warnMurni.style.display = 'none';
            if (warnPerubahan) warnPerubahan.style.display = existingDocsData['RENJA_PERUBAHAN'] ? 'block' : 'none';
            if (formVariant) formVariant.value = 'RENJA_PERUBAHAN';
            if (formTa) formTa.value = perubahanTaValue;
            if (uploadTa) uploadTa.value = perubahanTaValue;
            if (uploadJenis) uploadJenis.value = 'RENJA Perubahan';
        }
    }

    window.modalSelectMethod = function(method) {
        currentModalMethod = method;
        const cardTemplate = document.getElementById('method-card-template');
        const cardUpload = document.getElementById('method-card-upload');
        const checkTemplate = document.getElementById('method-check-template');
        const checkUpload = document.getElementById('method-check-upload');
        const subTemplate = document.getElementById('subpanel-method-template');
        const subUpload = document.getElementById('subpanel-method-upload');

        if (method === 'template') {
            if (cardTemplate) cardTemplate.className = 'p-4 rounded-2xl border-2 border-amber-500 bg-amber-50/50 ring-2 ring-amber-400/30 cursor-pointer transition-all flex flex-col justify-between';
            if (cardUpload) cardUpload.className = 'p-4 rounded-2xl border-2 border-slate-200 hover:border-slate-300 bg-white cursor-pointer transition-all flex flex-col justify-between';
            if (checkTemplate) {
                checkTemplate.className = 'w-5 h-5 rounded-full border bg-amber-500 border-amber-600 text-slate-950 font-bold flex items-center justify-center text-xs';
                checkTemplate.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i>';
            }
            if (checkUpload) {
                checkUpload.className = 'w-5 h-5 rounded-full border border-slate-300 flex items-center justify-center text-xs';
                checkUpload.innerHTML = '';
            }
            if (subTemplate) subTemplate.style.display = 'block';
            if (subUpload) subUpload.style.display = 'none';
        } else {
            if (cardTemplate) cardTemplate.className = 'p-4 rounded-2xl border-2 border-slate-200 hover:border-slate-300 bg-white cursor-pointer transition-all flex flex-col justify-between';
            if (cardUpload) cardUpload.className = 'p-4 rounded-2xl border-2 border-blue-500 bg-blue-50/50 ring-2 ring-blue-400/30 cursor-pointer transition-all flex flex-col justify-between';
            if (checkTemplate) {
                checkTemplate.className = 'w-5 h-5 rounded-full border border-slate-300 flex items-center justify-center text-xs';
                checkTemplate.innerHTML = '';
            }
            if (checkUpload) {
                checkUpload.className = 'w-5 h-5 rounded-full border bg-blue-600 border-blue-700 text-white font-bold flex items-center justify-center text-xs';
                checkUpload.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i>';
            }
            if (subTemplate) subTemplate.style.display = 'none';
            if (subUpload) subUpload.style.display = 'block';
        }
    };

    window.downloadCurrentTemplate = function() {
        const ta = (currentModalVariant === 'RENJA_MURNI') ? murniTaValue : perubahanTaValue;
        window.open('{{ url('/renja-templates') }}/' + currentModalVariant + '/download?tahun_anggaran=' + ta, '_blank');
    };

    // Close on backdrop click
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modal-buat-dokumen-opd');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModalBuatDokumen();
                }
            });
        }
    });
</script>
