<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Editor F4 - Bapperida Kabupaten Cirebon</title>
    
    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- FontAwesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        
        body, html {
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            background-color: #f1f5f9;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            display: flex;
            flex-direction: column;
        }

        /* RIBBON TOOLBAR */
        .editor-ribbon {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            overflow-x: auto;
            white-space: nowrap;
            flex-shrink: 0;
            z-index: 40;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .toolbar-group {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding-right: 0.75rem;
            border-right: 1px solid #e2e8f0;
        }
        .toolbar-group:last-child {
            border-right: none;
        }

        .tool-btn {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.5rem;
            border: 1px solid transparent;
            border-radius: 0.375rem;
            cursor: pointer;
            background-color: transparent;
            font-size: 0.75rem;
            font-weight: 600;
            color: #334155;
            min-width: 2.25rem;
            transition: all 0.15s ease;
        }
        .tool-btn:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }
        .tool-btn i {
            font-size: 0.875rem;
            margin-bottom: 0.125rem;
        }

        /* WORKSPACE & F4 CANVAS */
        .workspace-canvas {
            flex-grow: 1;
            width: 100%;
            overflow-y: auto;
            overflow-x: auto;
            padding: 2rem 1rem 5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
            background-color: #e2e8f0;
        }

        .pages-zoom-container {
            transform: scale(var(--editor-zoom, 1));
            transform-origin: top center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
            transition: transform 0.15s ease;
        }

        .paper-f4 {
            width: 21.5cm;
            min-height: 33cm;
            height: auto;
            background-color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            padding: 2.5cm 2.5cm 2.5cm 3cm;
            position: relative;
            display: flex;
            flex-direction: column;
            border-radius: 0.25rem;
            border: 1px solid #cbd5e1;
        }

        .locked-header {
            text-align: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 0.75rem;
            margin-bottom: 1.25rem;
            user-select: none;
        }
        .locked-header h2 {
            font-family: 'Bookman Old Style', Georgia, serif;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
        }
        .locked-header h3 {
            font-family: 'Bookman Old Style', Georgia, serif;
            font-size: 12pt;
            font-weight: bold;
            text-align: left;
            margin-top: 1rem;
        }

        .page-content {
            flex-grow: 1;
            outline: none;
            font-family: 'Bookman Old Style', Georgia, serif;
            font-size: 12pt;
            line-height: 1.6;
            text-align: justify;
            color: #0f172a;
            min-height: 200px;
        }

        .page-number {
            position: absolute;
            bottom: 1cm;
            right: 2.5cm;
            font-size: 10pt;
            color: #64748b;
            user-select: none;
            font-weight: bold;
        }
    </style>
</head>
<body x-data="{ zoom: 100 }">

    <!-- TOP HEADER NAVIGATION -->
    <header class="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 shrink-0 z-50 shadow-xs">
        <div class="flex items-center space-x-3">
            <a href="{{ route('dashboard') }}" class="h-8 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition flex items-center space-x-1.5">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="border-l border-slate-200 h-6 hidden sm:block"></div>
            <div>
                <h1 class="text-xs sm:text-sm font-extrabold text-slate-900 leading-none">Draft Renja - Perangkat Daerah</h1>
                <span class="text-[10px] font-semibold text-slate-500 hidden sm:inline">Format Kertas F4 Fisik (21.5 x 33 cm)</span>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <button class="h-9 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5" onclick="alert('Dokumen Renja berhasil disimpan!')">
                <i class="fa-solid fa-floppy-disk text-xs"></i>
                <span>Simpan Draft</span>
            </button>
        </div>
    </header>

    <!-- MS WORD RIBBON TOOLBAR -->
    <div class="editor-ribbon">
        <!-- Clipboard Group -->
        <div class="toolbar-group">
            <button class="tool-btn" onclick="document.execCommand('undo')" title="Undo">
                <i class="fa-solid fa-rotate-left text-blue-600"></i>
                <span>Undo</span>
            </button>
            <button class="tool-btn" onclick="document.execCommand('redo')" title="Redo">
                <i class="fa-solid fa-rotate-right text-slate-600"></i>
                <span>Redo</span>
            </button>
        </div>

        <!-- Font Group -->
        <div class="toolbar-group">
            <button class="tool-btn" onclick="document.execCommand('bold')" title="Bold">
                <i class="fa-solid fa-bold text-slate-900"></i>
                <span>Bold</span>
            </button>
            <button class="tool-btn" onclick="document.execCommand('italic')" title="Italic">
                <i class="fa-solid fa-italic text-slate-800"></i>
                <span>Italic</span>
            </button>
            <button class="tool-btn" onclick="document.execCommand('underline')" title="Underline">
                <i class="fa-solid fa-underline text-slate-800"></i>
                <span>Underline</span>
            </button>
        </div>

        <!-- Paragraph Group -->
        <div class="toolbar-group">
            <button class="tool-btn" onclick="document.execCommand('justifyLeft')" title="Align Left">
                <i class="fa-solid fa-align-left text-slate-700"></i>
                <span>Kiri</span>
            </button>
            <button class="tool-btn" onclick="document.execCommand('justifyCenter')" title="Center">
                <i class="fa-solid fa-align-center text-slate-700"></i>
                <span>Tengah</span>
            </button>
            <button class="tool-btn" onclick="document.execCommand('justifyRight')" title="Align Right">
                <i class="fa-solid fa-align-right text-slate-700"></i>
                <span>Kanan</span>
            </button>
            <button class="tool-btn" onclick="document.execCommand('justifyFull')" title="Justify">
                <i class="fa-solid fa-align-justify text-blue-600"></i>
                <span>Justify</span>
            </button>
        </div>

        <!-- Page Action -->
        <div class="toolbar-group">
            <button class="tool-btn bg-blue-50 border-blue-200 text-blue-700 hover:bg-blue-100" onclick="addNewF4Page()">
                <i class="fa-solid fa-plus-circle text-blue-600"></i>
                <span>+ Lembaran F4</span>
            </button>
        </div>
    </div>

    <!-- WORKSPACE CANVAS -->
    <main class="workspace-canvas" id="workspace">
        <div class="pages-zoom-container" :style="'--editor-zoom: ' + (zoom / 100)">
            <div class="paper-f4" id="page-1">
                <div class="locked-header" contenteditable="false">
                    <h2>BAB I</h2>
                    <h2>PENDAHULUAN</h2>
                    <h3>1.1 Latar Belakang</h3>
                </div>
                <div class="page-content" contenteditable="true">
                    <p>Sesuai dengan Peraturan Menteri Dalam Negeri Republik Indonesia Nomor 86 Tahun 2017 bahwa setiap Satuan Kerja Perangkat Daerah (SKPD) di lingkungan Pemerintah Kabupaten Cirebon wajib menyusun Rencana Kerja Perangkat Daerah (Renja SKPD) sebagai dokumen perencanaan operasional 1 (satu) tahun anggaran.</p>
                    <p>Ketik narasi Anda di sini...</p>
                </div>
                <div class="page-number">Halaman 1</div>
            </div>
        </div>
    </main>

    <!-- MINIMALIST STATUS BAR -->
    <footer class="h-8 bg-white border-t border-slate-200 px-4 sm:px-6 flex items-center justify-between text-xs text-slate-600 shrink-0 z-50">
        <div class="flex items-center space-x-3 text-[11px]">
            <span class="font-bold text-slate-800">Bookman Old Style • 12 pt</span>
            <span class="text-slate-300">•</span>
            <span class="font-semibold text-slate-700">F4 (21.5cm × 33cm)</span>
        </div>

        <div class="flex items-center space-x-2">
            <button @click="zoom = Math.max(50, zoom - 10)" class="w-5 h-5 flex items-center justify-center hover:bg-slate-100 rounded text-slate-600">
                <i class="fa-solid fa-minus text-[10px]"></i>
            </button>
            <span x-text="zoom + '%'" @click="zoom = 100" class="font-bold text-[11px] text-slate-800 min-w-[36px] text-center cursor-pointer hover:bg-slate-100 rounded px-1">100%</span>
            <button @click="zoom = Math.min(150, zoom + 10)" class="w-5 h-5 flex items-center justify-center hover:bg-slate-100 rounded text-slate-600">
                <i class="fa-solid fa-plus text-[10px]"></i>
            </button>
        </div>
    </footer>

    <script>
        function addNewF4Page() {
            const container = document.querySelector('.pages-zoom-container');
            const pageCount = container.querySelectorAll('.paper-f4').length + 1;
            
            const newPage = document.createElement('div');
            newPage.className = 'paper-f4';
            newPage.id = 'page-' + pageCount;
            newPage.innerHTML = `
                <div class="page-content" contenteditable="true"><p><br></p></div>
                <div class="page-number">Halaman ${pageCount}</div>
            `;
            
            container.appendChild(newPage);
            newPage.querySelector('.page-content').focus();
            updatePageNumbers();
        }

        function updatePageNumbers() {
            const pages = document.querySelectorAll('.paper-f4');
            pages.forEach((page, idx) => {
                const footer = page.querySelector('.page-number');
                if (footer) footer.textContent = 'Halaman ' + (idx + 1);
            });
        }
    </script>
</body>
</html>
