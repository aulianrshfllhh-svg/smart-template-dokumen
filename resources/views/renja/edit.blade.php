<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Narasi Dokumen Renja - {{ $document->opd->nama_opd }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Quill.js WYSIWYG Editor CDN -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .ql-container { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.95rem; border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; min-height: 160px; }
        .ql-toolbar { border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; background-color: #f8fafc; }
        /* Style override for read-only boxes */
        .read-only-content h2 { font-size: 1.25rem; font-weight: 600; margin-top: 0.5rem; }
        .read-only-content h3 { font-size: 1.1rem; font-weight: 600; margin-top: 0.5rem; }
        .read-only-content p { margin-bottom: 0.5rem; line-height: 1.6; text-align: justify; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen">
    <!-- Navbar Navigation -->
    <nav class="bg-slate-900 text-white px-6 py-4 flex justify-between items-center shadow-lg">
        <div class="flex items-center space-x-3">
            <span class="font-bold text-lg text-amber-400">e-Renja Bapperida</span>
            <span class="text-slate-400 text-xs border-l border-slate-700 pl-3">Kabupaten Cirebon</span>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm text-slate-300">{{ auth()->user()->nama_lengkap }} ({{ strtoupper(auth()->user()->role) }})</span>
            <a href="{{ route('operator.dashboard') }}" class="bg-slate-800 hover:bg-slate-700 text-xs px-3 py-1.5 rounded text-slate-200">Dashboard</a>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto p-6 space-y-6">
        
        <!-- Header Title Card -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-bold text-slate-900">{{ $document->opd->nama_opd }}</h1>
                    <span class="bg-amber-100 text-amber-900 font-bold text-xs px-3 py-1 rounded-md">
                        {{ $document->opd->nomor_lampiran_romawi }}
                    </span>
                </div>
                <p class="text-slate-500 text-sm mt-1">Tahun Anggaran {{ $document->tahun_anggaran }} | Status: 
                    <span class="font-bold uppercase 
                        @if($document->status === 'approved') text-emerald-600 
                        @elseif($document->status === 'revision') text-rose-600 
                        @elseif($document->status === 'submitted') text-sky-600 
                        @else text-amber-600 @endif">
                        {{ $document->status }}
                    </span>
                </p>
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ route('renja.print', $document->id) }}" target="_blank" class="bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs px-4 py-2.5 rounded-lg shadow transition">
                    <i class="fa-solid fa-print mr-1.5"></i> Cetak F4 PDF
                </a>
            </div>
        </div>

        @php
            $isEditable = in_array(strtolower($document->status), ['draft', 'revision']);
        @endphp

        <!-- Notice Alert for Status -->
        @if(!$isEditable)
            <div class="bg-amber-50 border border-amber-300 text-amber-900 p-4 rounded-xl shadow-sm flex items-center space-x-3">
                <i class="fa-solid fa-lock text-amber-600 text-lg"></i>
                <div class="text-xs">
                    <strong>Dokumen Terkunci (Read-Only):</strong> Status dokumen saat ini adalah <strong>{{ strtoupper($document->status) }}</strong>. Form pengisian bersifat pasif dan tidak dapat diubah oleh Operator OPD.
                </div>
            </div>
        @else
            <div class="bg-emerald-50 border border-emerald-300 text-emerald-900 p-4 rounded-xl shadow-sm flex items-center space-x-3">
                <i class="fa-solid fa-pen-to-square text-emerald-600 text-lg"></i>
                <div class="text-xs">
                    <strong>Form Edit Narasi Aktif:</strong> Anda dapat mengedit narasi dokumen. Sesuai regulasi penulisan resmi Kabupaten Cirebon, tombol <strong>Bold (B)</strong> telah dinonaktifkan secara ketat pada toolbar.
                </div>
            </div>
        @endif

        <!-- MAIN FORM EDIT NARASI -->
        <form id="form-renja-narasi" action="{{ route('operator.renja.update', $document->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Metadata Info Box -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Perangkat Daerah (OPD)</label>
                    <input type="text" value="{{ $document->opd->nama_opd }}" readonly class="w-full bg-slate-100 border border-slate-200 rounded-lg px-4 py-2 text-sm font-medium text-slate-700 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nomor Lampiran Romawi (Otomatis)</label>
                    <input type="text" value="{{ $document->opd->nomor_lampiran_romawi }}" readonly class="w-full bg-slate-100 border border-slate-200 rounded-lg px-4 py-2 text-sm font-bold text-amber-700 cursor-not-allowed">
                </div>
                <div>
                    <label for="tahun_anggaran" class="block text-xs font-bold text-slate-500 uppercase mb-1">Tahun Anggaran</label>
                    <input type="number" name="tahun_anggaran" id="tahun_anggaran" value="{{ old('tahun_anggaran', $document->tahun_anggaran) }}" {{ !$isEditable ? 'readonly' : '' }} required class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label for="jenis_dokumen" class="block text-xs font-bold text-slate-500 uppercase mb-1">Jenis Dokumen</label>
                    <input type="text" name="jenis_dokumen" id="jenis_dokumen" value="{{ old('jenis_dokumen', $document->jenis_dokumen) }}" {{ !$isEditable ? 'readonly' : '' }} required class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-amber-500">
                </div>
            </div>

            <!-- SECTION BAB I -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-6">
                <h3 class="font-bold text-base text-slate-900 border-b pb-2 flex items-center justify-between">
                    <span>BAB I PENDAHULUAN</span>
                    <span class="text-xs text-slate-400 font-normal">Sub-Bab 1.1 s/d 1.4</span>
                </h3>

                <!-- 1.1 Latar Belakang -->
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-1.5">1.1 Latar Belakang</label>
                    @if($isEditable)
                        <input type="hidden" name="latar_belakang" id="input-latar-belakang" value="{{ old('latar_belakang', $document->latar_belakang) }}">
                        <div id="editor-latar-belakang" class="bg-white">{!! old('latar_belakang', $document->latar_belakang) !!}</div>
                    @else
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-sm text-slate-800 read-only-content">
                            {!! $document->latar_belakang ?: '<p class="italic text-slate-400">Belum diisi.</p>' !!}
                        </div>
                    @endif
                </div>

                <!-- 1.2 Landasan Hukum -->
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-1.5">1.2 Landasan Hukum</label>
                    @if($isEditable)
                        <input type="hidden" name="landasan_hukum" id="input-landasan-hukum" value="{{ old('landasan_hukum', $document->landasan_hukum) }}">
                        <div id="editor-landasan-hukum" class="bg-white">{!! old('landasan_hukum', $document->landasan_hukum) !!}</div>
                    @else
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-sm text-slate-800 read-only-content">
                            {!! $document->landasan_hukum ?: '<p class="italic text-slate-400">Belum diisi.</p>' !!}
                        </div>
                    @endif
                </div>

                <!-- 1.3 Maksud dan Tujuan -->
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-1.5">1.3 Maksud dan Tujuan</label>
                    @if($isEditable)
                        <input type="hidden" name="maksud_tujuan" id="input-maksud-tujuan" value="{{ old('maksud_tujuan', $document->maksud_tujuan) }}">
                        <div id="editor-maksud-tujuan" class="bg-white">{!! old('maksud_tujuan', $document->maksud_tujuan) !!}</div>
                    @else
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-sm text-slate-800 read-only-content">
                            {!! $document->maksud_tujuan ?: '<p class="italic text-slate-400">Belum diisi.</p>' !!}
                        </div>
                    @endif
                </div>

                <!-- 1.4 Sistematika Penulisan -->
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-1.5">1.4 Sistematika Penulisan</label>
                    @if($isEditable)
                        <input type="hidden" name="sistematika" id="input-sistematika" value="{{ old('sistematika', $document->sistematika) }}">
                        <div id="editor-sistematika" class="bg-white">{!! old('sistematika', $document->sistematika) !!}</div>
                    @else
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-sm text-slate-800 read-only-content">
                            {!! $document->sistematika ?: '<p class="italic text-slate-400">Belum diisi.</p>' !!}
                        </div>
                    @endif
                </div>
            </div>

            <!-- SECTION BAB II -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-6">
                <h3 class="font-bold text-base text-slate-900 border-b pb-2">BAB II EVALUASI PELAKSANAAN RENJA OPD TAHUN LALU</h3>

                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-1.5">2.1 Evaluasi Pelaksanaan Renja Perangkat Daerah</label>
                    @if($isEditable)
                        <input type="hidden" name="evaluasi_narasi" id="input-evaluasi-narasi" value="{{ old('evaluasi_narasi', $document->evaluasi_narasi) }}">
                        <div id="editor-evaluasi-narasi" class="bg-white">{!! old('evaluasi_narasi', $document->evaluasi_narasi) !!}</div>
                    @else
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-sm text-slate-800 read-only-content">
                            {!! $document->evaluasi_narasi ?: '<p class="italic text-slate-400">Belum diisi.</p>' !!}
                        </div>
                    @endif
                </div>
            </div>

            <!-- SECTION BAB III -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-6">
                <h3 class="font-bold text-base text-slate-900 border-b pb-2">BAB III ISU-ISU STRATEGIS PERANGKAT DAERAH</h3>

                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-1.5">3.1 Analisis Isu Strategis Perangkat Daerah</label>
                    @if($isEditable)
                        <input type="hidden" name="isu_strategis_narasi" id="input-isu-strategis" value="{{ old('isu_strategis_narasi', $document->isu_strategis_narasi) }}">
                        <div id="editor-isu-strategis" class="bg-white">{!! old('isu_strategis_narasi', $document->isu_strategis_narasi) !!}</div>
                    @else
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-sm text-slate-800 read-only-content">
                            {!! $document->isu_strategis_narasi ?: '<p class="italic text-slate-400">Belum diisi.</p>' !!}
                        </div>
                    @endif
                </div>
            </div>

            <!-- SECTION BAB IV -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-6">
                <h3 class="font-bold text-base text-slate-900 border-b pb-2">BAB IV TUJUAN, SASARAN, PROGRAM DAN KEGIATAN</h3>

                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-1.5">4.1 Tujuan dan Sasaran Renja</label>
                    @if($isEditable)
                        <input type="hidden" name="tujuan_sasaran_narasi" id="input-tujuan-sasaran" value="{{ old('tujuan_sasaran_narasi', $document->tujuan_sasaran_narasi) }}">
                        <div id="editor-tujuan-sasaran" class="bg-white">{!! old('tujuan_sasaran_narasi', $document->tujuan_sasaran_narasi) !!}</div>
                    @else
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-sm text-slate-800 read-only-content">
                            {!! $document->tujuan_sasaran_narasi ?: '<p class="italic text-slate-400">Belum diisi.</p>' !!}
                        </div>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-1.5">4.2 Rencana Program dan Kegiatan Utama</label>
                    @if($isEditable)
                        <input type="hidden" name="program_kegiatan_narasi" id="input-program-kegiatan" value="{{ old('program_kegiatan_narasi', $document->program_kegiatan_narasi) }}">
                        <div id="editor-program-kegiatan" class="bg-white">{!! old('program_kegiatan_narasi', $document->program_kegiatan_narasi) !!}</div>
                    @else
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-sm text-slate-800 read-only-content">
                            {!! $document->program_kegiatan_narasi ?: '<p class="italic text-slate-400">Belum diisi.</p>' !!}
                        </div>
                    @endif
                </div>
            </div>

            <!-- SECTION BAB V -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-6">
                <h3 class="font-bold text-base text-slate-900 border-b pb-2">BAB V PENUTUP</h3>

                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-1.5">Narasi Penutup Dokumen</label>
                    @if($isEditable)
                        <input type="hidden" name="penutup_narasi" id="input-penutup-narasi" value="{{ old('penutup_narasi', $document->penutup_narasi) }}">
                        <div id="editor-penutup-narasi" class="bg-white">{!! old('penutup_narasi', $document->penutup_narasi) !!}</div>
                    @else
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-sm text-slate-800 read-only-content">
                            {!! $document->penutup_narasi ?: '<p class="italic text-slate-400">Belum diisi.</p>' !!}
                        </div>
                    @endif
                </div>
            </div>

            <!-- BUTTON ACTION BAR -->
            @if($isEditable)
                <div class="sticky bottom-6 z-30 bg-white/90 backdrop-blur p-4 rounded-xl border border-slate-200 shadow-xl flex items-center justify-between">
                    <a href="{{ route('operator.renja.show', $document->id) }}" class="text-sm font-bold text-slate-600 hover:text-slate-800 px-4 py-2">
                        Batal
                    </a>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm px-6 py-3 rounded-lg shadow-lg transition flex items-center space-x-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Perubahan Narasi</span>
                    </button>
                </div>
            @endif
        </form>
    </main>

    <!-- SCRIPT CONFIGURATION FOR QUILL WYSIWYG EDITOR WITH STRICT TOOLBAR GUARDRAIL -->
    @if($isEditable)
    <script>
        // STRICT TOOLBAR CONFIGURATION: DILARANG ADA BOLD ('bold')
        const strictQuillToolbarOptions = [
            [{ 'header': [2, 3, false] }],
            ['italic', 'underline'],                           // HANYA Italic & Underline (BOLD DIHILANGKAN SANGAT KETAT)
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],      // Numbered List & Bullet List
            [{ 'align': [] }],                                 // Align Left, Center, Right, Justify
            ['clean']                                          // Hapus Format
        ];

        // Helper instansiasi Quill Editor
        function setupQuillEditor(containerId, inputId) {
            const container = document.getElementById(containerId);
            const input = document.getElementById(inputId);

            if (!container || !input) return;

            const quill = new Quill(container, {
                theme: 'snow',
                modules: {
                    toolbar: strictQuillToolbarOptions
                },
                placeholder: 'Ketik narasi resmi di sini (tanpa format tebal)...'
            });

            // Sync HTML ke hidden input
            quill.on('text-change', function() {
                input.value = quill.root.innerHTML;
            });
        }

        // Inisialisasi seluruh field narasi saat DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            setupQuillEditor('editor-latar-belakang', 'input-latar-belakang');
            setupQuillEditor('editor-landasan-hukum', 'input-landasan-hukum');
            setupQuillEditor('editor-maksud-tujuan', 'input-maksud-tujuan');
            setupQuillEditor('editor-sistematika', 'input-sistematika');
            setupQuillEditor('editor-evaluasi-narasi', 'input-evaluasi-narasi');
            setupQuillEditor('editor-isu-strategis', 'input-isu-strategis');
            setupQuillEditor('editor-tujuan-sasaran', 'input-tujuan-sasaran');
            setupQuillEditor('editor-program-kegiatan', 'input-program-kegiatan');
            setupQuillEditor('editor-penutup-narasi', 'input-penutup-narasi');

            // Sync akhir sebelum submit
            const form = document.getElementById('form-renja-narasi');
            if (form) {
                form.addEventListener('submit', function() {
                    const fields = [
                        ['editor-latar-belakang', 'input-latar-belakang'],
                        ['editor-landasan-hukum', 'input-landasan-hukum'],
                        ['editor-maksud-tujuan', 'input-maksud-tujuan'],
                        ['editor-sistematika', 'input-sistematika'],
                        ['editor-evaluasi-narasi', 'input-evaluasi-narasi'],
                        ['editor-isu-strategis', 'input-isu-strategis'],
                        ['editor-tujuan-sasaran', 'input-tujuan-sasaran'],
                        ['editor-program-kegiatan', 'input-program-kegiatan'],
                        ['editor-penutup-narasi', 'input-penutup-narasi'],
                    ];

                    fields.forEach(([editorId, inputId]) => {
                        const ed = document.getElementById(editorId);
                        const inp = document.getElementById(inputId);
                        if (ed && inp && ed.querySelector('.ql-editor')) {
                            inp.value = ed.querySelector('.ql-editor').innerHTML;
                        }
                    });
                });
            }
        });
    </script>
    @endif
</body>
</html>
