@php
    $romawiHeader = isset($romawiHeader) ? $romawiHeader : \App\Services\RenjaAutoFixService::getRomanHeaderForOpd($document->opd ?? null);
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pratinjau Dokumen — {{ $document->jenis_dokumen ?? 'RENJA' }} ({{ $document->opd->nama_opd ?? 'OPD' }})</title>

    <!-- FontAwesome & Google Fonts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- PDF.js Library (Mozilla Official) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #1e293b;
            color: #f8fafc;
            user-select: text;
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #0f172a;
        }
        ::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 9999px;
        }

        .pdf-page-container {
            background: #ffffff;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.05);
            border-radius: 2px;
            margin-bottom: 24px;
            position: relative;
            display: inline-block;
        }

        .pdf-page-canvas {
            display: block;
            width: 100%;
            height: auto;
            border-radius: 2px;
        }

        .page-badge {
            position: absolute;
            bottom: -18px;
            right: 8px;
            background: rgba(15, 23, 42, 0.85);
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body x-data="{ submitModalOpen: false }" class="h-full flex flex-col overflow-hidden bg-slate-900 antialiased select-none">

    @php
        $romawiHeader = isset($romawiHeader) ? $romawiHeader : \App\Services\RenjaAutoFixService::getRomanHeaderForOpd($document->opd ?? null);
        $jenisHeader = ($document->jenis_dokumen === 'RENJA Lampiran Perubahan') ? 'KEPUTUSAN BUPATI CIREBON' : 'PERATURAN BUPATI CIREBON';
        $subJenisHeader = ($document->jenis_dokumen === 'RENJA Lampiran Perubahan') ? 'PERUBAHAN RENCANA KERJA PERANGKAT DAERAH' : 'RENCANA KERJA PERANGKAT DAERAH';
        $versionTag = $document->metadata['version'] ?? 'v1.0';
        $isPerubahan = str_contains(strtolower($document->jenis_dokumen ?? ''), 'perubahan');
        $isLampiran = str_contains(strtolower($document->jenis_dokumen ?? ''), 'lampiran');
        $jenisLampiranLabel = $isLampiran ? ($isPerubahan ? 'Lampiran Kepbup Renja' : 'Lampiran Perbup Renja') : null;
    @endphp

    <header class="h-16 bg-slate-950 border-b border-slate-800 shrink-0 flex items-center justify-between px-3 sm:px-6 z-30 shadow-md">
        <!-- Left: Back Button & Header Info -->
        <div class="flex items-center space-x-3 min-w-0">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('renja.lampiran.index', ['tahun_anggaran' => $document->tahun_anggaran ?? 2027]) }}" 
               class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold transition shrink-0 border border-slate-700"
               title="Kembali ke Halaman Sebelumnya">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>← Kembali</span>
            </a>

            <div class="h-6 w-px bg-slate-800 shrink-0"></div>

            <!-- Workflow Title Header -->
            <div class="min-w-0">
                <div class="flex items-center space-x-2 truncate">
                    <h1 class="text-xs sm:text-sm font-extrabold text-white truncate">
                        {{ $document->jenis_dokumen ?? 'RENJA Murni' }}
                    </h1>
                    @if($isLampiran && $jenisLampiranLabel)
                        <span class="text-slate-500">•</span>
                        <span class="text-xs font-semibold text-emerald-400 truncate">{{ $jenisLampiranLabel }}</span>
                    @endif
                </div>
                <div class="text-[11px] text-slate-400 font-medium truncate">
                    {{ $document->opd->nama_opd ?? 'Kecamatan Depok' }}
                    @if($isLampiran)
                        • <strong class="text-amber-400 font-bold">{{ $romawiHeader }}</strong>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right: Action Buttons [Preview] [Submit] -->
        <div class="flex items-center space-x-2 shrink-0">
            <!-- [Preview] Button (Active) -->
            <span class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-blue-600/30 text-blue-300 border border-blue-500/50 text-xs font-extrabold shadow-xs">
                <i class="fa-solid fa-eye text-xs"></i>
                <span class="hidden md:inline">[Preview]</span>
            </span>

            <div class="h-6 w-px bg-slate-800 shrink-0"></div>

            <!-- [Submit] Button / Status Badge -->
            @if(Auth::user()->isOperator())
                @if(($document->status ?? '') === 'submitted')
                    <span class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 text-xs font-extrabold shadow-sm">
                        <i class="fa-solid fa-circle-check text-emerald-400 text-xs"></i>
                        <span>Submitted</span>
                    </span>
                @elseif(($document->status ?? '') === 'final')
                    <span class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 rounded-xl bg-blue-500/20 text-blue-300 border border-blue-500/40 text-xs font-extrabold shadow-sm">
                        <i class="fa-solid fa-lock text-blue-400 text-xs"></i>
                        <span>Final</span>
                    </span>
                @else
                    <button type="button" 
                            @click="submitModalOpen = true"
                            class="inline-flex items-center space-x-1.5 px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-md transition cursor-pointer">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                        <span>[Submit]</span>
                    </button>
                @endif
            @endif

            <!-- Print Direct -->
            <button onclick="window.print()" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold transition border border-slate-700">
                <i class="fa-solid fa-print"></i>
            </button>
        </div>
    </header>

    <!-- Session Banners -->
    @if(session('success'))
        <div class="bg-emerald-900/90 text-white text-xs px-4 py-2.5 flex items-center justify-between border-b border-emerald-700 font-semibold shrink-0">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-check text-emerald-400 text-sm"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-900/90 text-white text-xs px-4 py-2.5 flex items-center justify-between border-b border-rose-700 font-semibold shrink-0">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-exclamation text-amber-400 text-sm"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- 2. MAIN BODY (SIDEBAR INFORMASI & FORMAT STATUS)             -->
    <!-- ============================================================ -->
    <div class="flex-1 flex overflow-hidden relative">

        <!-- SIDEBAR INFORMASI DOKUMEN & FORMAT STATUS -->
        <aside id="viewerSidebar" class="w-72 sm:w-80 bg-slate-950/95 border-r border-slate-800 flex flex-col shrink-0 transition-all duration-200 ease-in-out z-20 overflow-hidden">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider text-slate-200 flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-blue-400"></i>
                    Informasi Dokumen
                </span>
            </div>

            <div class="p-4 space-y-4 overflow-y-auto flex-1 text-xs">
                
                <!-- 1. INFORMASI DOKUMEN CARD -->
                <div class="bg-slate-900 p-3.5 rounded-xl border border-slate-800 space-y-2">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Perangkat Daerah</span>
                        <div class="font-extrabold text-white text-xs mt-0.5">{{ $document->opd->nama_opd ?? 'Kecamatan Depok' }}</div>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Jenis Dokumen</span>
                        <div class="font-bold text-blue-400 text-[11px] mt-0.5">{{ $document->jenis_dokumen ?? 'RENJA Murni' }}</div>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Status Dokumen</span>
                        <div class="mt-0.5">
                            @if($isLampiran)
                                <span class="px-2 py-0.5 rounded-md bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[10px] font-black uppercase tracking-wider">
                                    ⚡ Otomatis dari RENJA Murni
                                </span>
                            @elseif(($document->status ?? '') === 'submitted')
                                <span class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[10px] font-black uppercase tracking-wider">Submitted</span>
                            @elseif(($document->status ?? '') === 'final')
                                <span class="px-2 py-0.5 rounded-md bg-blue-500/20 text-blue-400 border border-blue-500/30 text-[10px] font-black uppercase tracking-wider">Final</span>
                            @else
                                <span class="px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-400 border border-amber-500/30 text-[10px] font-black uppercase tracking-wider">Draft</span>
                            @endif
                        </div>
                    </div>
                    @if($isLampiran)
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Sumber Data</span>
                        <div class="font-semibold text-purple-400 text-[11px] mt-0.5">
                            RENJA Murni TA {{ $document->tahun_anggaran }}
                        </div>
                    </div>
                    @endif
                    <div class="grid {{ $isLampiran ? 'grid-cols-2' : 'grid-cols-1' }} gap-2 pt-1 border-t border-slate-800/80">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Tahun</span>
                            <div class="font-bold text-white text-xs mt-0.5">{{ $document->tahun_anggaran ?? '2027' }}</div>
                        </div>
                        @if($isLampiran)
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Nomor Lampiran</span>
                            <div class="font-bold text-amber-400 text-xs mt-0.5">{{ $romawiHeader }}</div>
                        </div>
                        @endif
                    </div>
                    <div class="pt-1 border-t border-slate-800/80">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Nama File</span>
                        <div class="font-mono text-[11px] text-slate-300 mt-0.5 break-all">{{ $originalFilename }}</div>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Version</span>
                        <div class="font-mono font-bold text-emerald-400 text-[11px] mt-0.5">{{ $versionTag }}</div>
                    </div>
                </div>



                <!-- Navigasi Halaman -->
                <div class="pt-2 border-t border-slate-800">
                    <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">Navigasi Halaman</div>
                    <div id="thumbnailsContainer" class="grid grid-cols-2 gap-2 text-center"></div>
                </div>

            </div>
        </aside>

        <!-- MAIN VIEWPORT / VIEWER CANVAS -->
        <main id="viewerMain" class="flex-1 overflow-y-auto overflow-x-auto bg-slate-900/90 relative flex flex-col items-center p-4 sm:p-8 select-text">

            <!-- LOADING SPINNER & OVERLAY -->
            <div id="loadingOverlay" class="absolute inset-0 bg-slate-900/95 flex flex-col items-center justify-center p-6 z-40">
                <div class="w-16 h-16 rounded-3xl bg-slate-800 border border-slate-700 flex items-center justify-center text-emerald-400 text-2xl shadow-xl mb-4 animate-pulse">
                    <i class="fa-solid fa-file-pdf"></i>
                </div>
                <h2 id="loadingTitle" class="text-sm sm:text-base font-black text-white text-center">Menyiapkan pratinjau dokumen...</h2>
                <p id="loadingSubtitle" class="text-xs text-slate-400 font-medium text-center mt-1 max-w-sm">
                    Melakukan rendering dokumen terkonfirmasi final.
                </p>
                <div class="w-48 bg-slate-800 rounded-full h-1.5 mt-4 overflow-hidden">
                    <div id="progressBar" class="bg-gradient-to-r from-emerald-500 to-teal-400 h-1.5 rounded-full transition-all duration-300 w-1/3 animate-pulse"></div>
                </div>
            </div>

            <!-- ERROR OVERLAY -->
            <div id="errorOverlay" class="hidden absolute inset-0 bg-slate-900/95 flex flex-col items-center justify-center p-6 z-40 text-center">
                <div class="w-14 h-14 rounded-2xl bg-rose-950/60 border border-rose-800/60 flex items-center justify-center text-rose-500 text-2xl shadow-lg mb-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h2 class="text-sm sm:text-base font-black text-white">Pratinjau belum dapat ditampilkan</h2>
                <p id="errorMessage" class="text-xs text-slate-400 font-medium mt-1 max-w-md">
                    Dokumen tidak dapat dirender secara otomatis. Anda tetap dapat mengunduh file asli secara langsung.
                </p>
                <div class="flex items-center gap-3 mt-4">
                    <a href="{{ $downloadWordUrl }}" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-download"></i>
                        <span>Unduh File Word</span>
                    </a>
                </div>
            </div>

            <!-- PAGES CONTAINER -->
            <div id="pagesContainer" class="flex flex-col items-center w-full max-w-5xl py-4"></div>
        </main>
    </div>

    <!-- FLOATING BOTTOM CONTROLS -->
    <div class="fixed bottom-5 left-1/2 -translate-x-1/2 z-30 bg-slate-950/90 backdrop-blur-md border border-slate-800/80 rounded-2xl shadow-2xl px-4 py-2 flex items-center space-x-3 text-xs text-slate-200">
        <div class="flex items-center space-x-1">
            <button id="btnZoomOut" class="w-7 h-7 rounded-lg bg-slate-800/80 hover:bg-slate-700 flex items-center justify-center text-slate-300 hover:text-white transition" title="Perkecil (-)">
                <i class="fa-solid fa-minus text-[10px]"></i>
            </button>
            <span id="zoomLabel" class="text-xs font-black text-white w-12 text-center tabular-nums cursor-pointer select-none">100%</span>
            <button id="btnZoomIn" class="w-7 h-7 rounded-lg bg-slate-800/80 hover:bg-slate-700 flex items-center justify-center text-slate-300 hover:text-white transition" title="Perbesar (+)">
                <i class="fa-solid fa-plus text-[10px]"></i>
            </button>
        </div>

        <div class="h-4 w-px bg-slate-800"></div>

        <div class="flex items-center space-x-1.5">
            <button id="btnPrevPage" class="w-7 h-7 rounded-lg bg-slate-800/80 hover:bg-slate-700 flex items-center justify-center text-slate-300 hover:text-white transition">
                <i class="fa-solid fa-chevron-left text-[10px]"></i>
            </button>

            <div class="flex items-center space-x-1 font-bold text-xs">
                <input type="number" id="inputCurrentPage" min="1" value="1" 
                       class="w-10 h-7 bg-slate-900 border border-slate-700 rounded-lg text-center font-black text-white text-xs focus:outline-none focus:border-emerald-500">
                <span class="text-slate-500 font-bold">/</span>
                <span id="totalPagesLabel" class="text-slate-400 font-bold">1</span>
            </div>

            <button id="btnNextPage" class="w-7 h-7 rounded-lg bg-slate-800/80 hover:bg-slate-700 flex items-center justify-center text-slate-300 hover:text-white transition">
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
            </button>
        </div>
    </div>

    <!-- PDF.JS RENDERER SCRIPT -->
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        const pdfUrl = @json($pdfStreamUrl);
        let pdfDoc = null;
        let scale = 1.15;
        let currentPage = 1;
        let totalPages = 0;

        const pagesContainer = document.getElementById('pagesContainer');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const loadingTitle = document.getElementById('loadingTitle');
        const loadingSubtitle = document.getElementById('loadingSubtitle');
        const progressBar = document.getElementById('progressBar');
        const errorOverlay = document.getElementById('errorOverlay');
        const errorMessage = document.getElementById('errorMessage');
        const viewerMain = document.getElementById('viewerMain');
        const btnZoomIn = document.getElementById('btnZoomIn');
        const btnZoomOut = document.getElementById('btnZoomOut');
        const zoomLabel = document.getElementById('zoomLabel');
        const btnPrevPage = document.getElementById('btnPrevPage');
        const btnNextPage = document.getElementById('btnNextPage');
        const inputCurrentPage = document.getElementById('inputCurrentPage');
        const totalPagesLabel = document.getElementById('totalPagesLabel');
        const thumbnailsContainer = document.getElementById('thumbnailsContainer');

        async function loadPdfDocument() {
            try {
                loadingOverlay.classList.remove('hidden');
                errorOverlay.classList.add('hidden');

                const loadingTask = pdfjsLib.getDocument({
                    url: pdfUrl,
                    cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                    cMapPacked: true,
                });

                loadingTask.onProgress = function (progress) {
                    if (progress.total > 0) {
                        const percent = Math.round((progress.loaded / progress.total) * 100);
                        progressBar.style.width = `${Math.min(90, percent)}%`;
                    }
                };

                pdfDoc = await loadingTask.promise;
                totalPages = pdfDoc.numPages;
                totalPagesLabel.textContent = totalPages;
                inputCurrentPage.max = totalPages;

                await renderAllPages();
                buildThumbnails();

                loadingOverlay.classList.add('hidden');
            } catch (error) {
                console.error("[PDF Preview Error]", error);
                loadingOverlay.classList.add('hidden');
                errorOverlay.classList.remove('hidden');
                errorMessage.textContent = error.message || "Gagal merender pratinjau PDF.";
            }
        }

        async function renderAllPages() {
            pagesContainer.innerHTML = '';
            const pixelRatio = window.devicePixelRatio || 1.5;

            for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
                const pageWrapper = document.createElement('div');
                pageWrapper.className = 'pdf-page-container';
                pageWrapper.id = `page-container-${pageNum}`;
                pageWrapper.dataset.pageNum = pageNum;

                const canvas = document.createElement('canvas');
                canvas.className = 'pdf-page-canvas';
                canvas.id = `pdf-canvas-${pageNum}`;

                const badge = document.createElement('div');
                badge.className = 'page-badge';
                badge.textContent = `Hal ${pageNum}`;

                pageWrapper.appendChild(canvas);
                pageWrapper.appendChild(badge);
                pagesContainer.appendChild(pageWrapper);

                renderPage(pageNum, canvas, pixelRatio);
            }
        }

        async function renderPage(pageNum, canvas, pixelRatio) {
            try {
                const page = await pdfDoc.getPage(pageNum);
                const viewport = page.getViewport({ scale: scale });

                const context = canvas.getContext('2d');
                canvas.height = viewport.height * pixelRatio;
                canvas.width = viewport.width * pixelRatio;
                canvas.style.width = `${viewport.width}px`;
                canvas.style.height = `${viewport.height}px`;

                const renderContext = {
                    canvasContext: context,
                    transform: [pixelRatio, 0, 0, pixelRatio, 0, 0],
                    viewport: viewport,
                };

                await page.render(renderContext).promise;
            } catch (err) {
                console.warn(`[Page ${pageNum} error]`, err);
            }
        }

        function buildThumbnails() {
            thumbnailsContainer.innerHTML = '';
            for (let i = 1; i <= totalPages; i++) {
                const thumbBtn = document.createElement('button');
                thumbBtn.className = 'p-2 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[11px] font-bold text-slate-300 hover:text-white transition flex flex-col items-center gap-1';
                thumbBtn.innerHTML = `<i class="fa-solid fa-file-lines text-emerald-400"></i><span>Hal ${i}</span>`;
                thumbBtn.addEventListener('click', () => scrollToPage(i));
                thumbnailsContainer.appendChild(thumbBtn);
            }
        }

        function scrollToPage(pageNum) {
            if (pageNum < 1 || pageNum > totalPages) return;
            const targetEl = document.getElementById(`page-container-${pageNum}`);
            if (targetEl) {
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                currentPage = pageNum;
                inputCurrentPage.value = pageNum;
            }
        }

        btnZoomIn.addEventListener('click', () => { scale = parseFloat((scale + 0.15).toFixed(2)); updateZoom(); });
        btnZoomOut.addEventListener('click', () => { if (scale > 0.5) scale = parseFloat((scale - 0.15).toFixed(2)); updateZoom(); });
        function updateZoom() { zoomLabel.textContent = `${Math.round(scale * 100)}%`; renderAllPages(); }

        btnPrevPage.addEventListener('click', () => { if (currentPage > 1) scrollToPage(currentPage - 1); });
        btnNextPage.addEventListener('click', () => { if (currentPage < totalPages) scrollToPage(currentPage + 1); });

        document.addEventListener('DOMContentLoaded', () => { loadPdfDocument(); });
    </script>

    <!-- MODAL KONFIRMASI SUBMIT (MODERN TAILWIND + ALPINE) -->
    <div x-show="submitModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         style="display: none;">
        
        <div class="bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden relative" @click.away="submitModalOpen = false">
            
            <!-- TOP ACCENT LINE -->
            <div class="h-1.5 bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-500"></div>

            <div class="p-6 space-y-5">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-xl shrink-0 shadow-inner">
                        <i class="fa-solid fa-paper-plane"></i>
                    </div>
                    <div class="space-y-1 min-w-0">
                        <h3 class="text-base font-black text-white leading-snug">Konfirmasi Kirim Dokumen</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Apakah Anda yakin ingin mengirimkan dokumen <strong class="text-emerald-400 font-bold">{{ $document->jenis_dokumen ?? 'RENJA Murni' }} TA {{ $document->tahun_anggaran ?? '2027' }}</strong> ini ke Admin Bapperida?
                        </p>
                    </div>
                </div>

                <div class="bg-slate-950/70 rounded-2xl p-4 border border-slate-800/80 space-y-1.5">
                    <div class="flex items-center gap-2 text-xs font-bold text-amber-400">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Informasi Status Dokumen</span>
                    </div>
                    <p class="text-[11px] text-slate-300 leading-relaxed">
                        Setelah dikirim, status dokumen akan berubah menjadi <strong class="text-emerald-400 uppercase font-black">Submitted</strong> dan terkunci untuk proses verifikasi Bapperida.
                    </p>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-1">
                    <button type="button" 
                            @click="submitModalOpen = false" 
                            class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold text-xs transition border border-slate-700 cursor-pointer">
                        Batal
                    </button>
                    <form action="{{ route('renja.submit', $document->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs shadow-lg shadow-emerald-950 transition flex items-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            <span>Ya, Kirim Sekarang</span>
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
