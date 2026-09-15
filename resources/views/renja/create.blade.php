<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Dokumen Renja Baru</title>
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
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen">
    <!-- Navbar Navigation -->
    <nav class="bg-slate-900 text-white px-6 py-4 flex justify-between items-center shadow-lg">
        <div class="flex items-center space-x-3">
            <span class="font-bold text-lg text-amber-400">e-Renja Bapperida</span>
            <span class="text-slate-400 text-xs border-l border-slate-700 pl-3">Kabupaten Cirebon</span>
        </div>
        <a href="{{ route('operator.dashboard') }}" class="bg-slate-800 hover:bg-slate-700 text-xs px-3 py-1.5 rounded text-slate-200">Dashboard</a>
    </nav>

    <main class="max-w-4xl mx-auto p-6 space-y-6">
        <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Buat Dokumen Renja Baru</h1>
                <p class="text-slate-500 text-sm mt-1">Dokumen baru akan dibuat dalam status <span class="font-bold text-amber-600">DRAFT</span>.</p>
            </div>
            <a href="{{ route('operator.dashboard') }}" class="text-xs bg-slate-200 text-slate-700 font-semibold px-3.5 py-2 rounded-lg">Kembali</a>
        </div>

        <form id="form-create-renja" action="{{ route('operator.renja.store') }}" method="POST" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-6">
            @csrf

            <!-- System Auto-Fetched Readonly Guardrails -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Perangkat Daerah (OPD)</label>
                    <input type="text" value="{{ $opd->nama_opd }}" readonly class="w-full bg-slate-200 border border-slate-300 rounded-lg px-3.5 py-2 text-sm text-slate-700 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nomor Lampiran Romawi (Otomatis)</label>
                    <input type="text" value="{{ $nomorLampiranRomawi }}" readonly class="w-full bg-slate-200 border border-slate-300 rounded-lg px-3.5 py-2 text-sm font-bold text-amber-700 cursor-not-allowed">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="tahun_anggaran" class="block text-sm font-bold text-slate-700 mb-1">Tahun Anggaran</label>
                    <input type="number" name="tahun_anggaran" id="tahun_anggaran" value="{{ date('Y') + 1 }}" required class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-amber-500">
                </div>

                <div>
                    <label for="jenis_dokumen" class="block text-sm font-bold text-slate-700 mb-1">Jenis Dokumen</label>
                    <input type="text" name="jenis_dokumen" id="jenis_dokumen" value="Rencana Kerja (Renja)" required class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-amber-500">
                </div>
            </div>

            <div class="border-t border-slate-200 pt-4 space-y-6">
                <h3 class="font-bold text-slate-900 text-base">BAB I PENDAHULUAN</h3>
                
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-1.5">1.1 Latar Belakang</label>
                    <input type="hidden" name="latar_belakang" id="input-create-latar-belakang">
                    <div id="editor-create-latar-belakang" class="bg-white"></div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-1.5">1.2 Landasan Hukum</label>
                    <input type="hidden" name="landasan_hukum" id="input-create-landasan-hukum">
                    <div id="editor-create-landasan-hukum" class="bg-white"></div>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-4 space-y-6">
                <h3 class="font-bold text-slate-900 text-base">BAB V PENUTUP</h3>
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-1.5">Narasi Penutup Dokumen</label>
                    <input type="hidden" name="penutup_narasi" id="input-create-penutup-narasi">
                    <div id="editor-create-penutup-narasi" class="bg-white"></div>
                </div>
            </div>

            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 rounded-lg shadow-lg transition flex items-center justify-center space-x-2">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Simpan Dokumen Renja</span>
            </button>
        </form>
    </main>

    <!-- SCRIPT CONFIGURATION FOR QUILL WYSIWYG EDITOR WITH STRICT TOOLBAR GUARDRAIL -->
    <script>
        const strictQuillToolbarOptions = [
            [{ 'header': [2, 3, false] }],
            ['italic', 'underline'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'align': [] }],
            ['clean']
        ];

        function setupQuillEditor(containerId, inputId) {
            const container = document.getElementById(containerId);
            const input = document.getElementById(inputId);

            if (!container || !input) return;

            const quill = new Quill(container, {
                theme: 'snow',
                modules: { toolbar: strictQuillToolbarOptions },
                placeholder: 'Ketik narasi resmi di sini (tanpa format tebal)...'
            });

            quill.on('text-change', function() {
                input.value = quill.root.innerHTML;
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            setupQuillEditor('editor-create-latar-belakang', 'input-create-latar-belakang');
            setupQuillEditor('editor-create-landasan-hukum', 'input-create-landasan-hukum');
            setupQuillEditor('editor-create-penutup-narasi', 'input-create-penutup-narasi');

            const form = document.getElementById('form-create-renja');
            if (form) {
                form.addEventListener('submit', function() {
                    ['editor-create-latar-belakang', 'editor-create-landasan-hukum', 'editor-create-penutup-narasi'].forEach(id => {
                        const ed = document.getElementById(id);
                        const inpId = id.replace('editor-create-', 'input-create-');
                        const inp = document.getElementById(inpId);
                        if (ed && inp && ed.querySelector('.ql-editor')) {
                            inp.value = ed.querySelector('.ql-editor').innerHTML;
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
