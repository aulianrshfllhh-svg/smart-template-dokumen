<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumen Rencana Kerja - {{ $document->opd->nama_opd ?? 'Perangkat Daerah' }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        /* ============================================================ */
        /* PRINT STYLES: Kertas F4 (215mm x 330mm), Margin 20mm (2 cm) */
        /* ============================================================ */
        @page {
            size: 215mm 330mm;
            margin: 20mm 20mm 20mm 20mm;
        }

        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }
            .no-print { display: none !important; }
            .doc-canvas { background: #fff !important; padding: 0 !important; min-height: 0 !important; margin-left: 0 !important; }
            .doc-page {
                width: 100% !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                border: none !important;
                page-break-after: auto;
                word-break: break-word !important;
                overflow-wrap: break-word !important;
            }
            .doc-page-wrapper { margin: 0 !important; }
            .doc-content, .doc-content * {
                word-break: break-word !important;
                overflow-wrap: break-word !important;
                max-width: 100% !important;
            }
        }

        /* ============================================================ */
        /* SCREEN STYLES: Word-like Document Preview                    */
        /* ============================================================ */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html {
            overflow-y: scroll;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #525659;
            color: #1e293b;
            min-height: 100vh;
        }

        /* --- Floating Toolbar (Word-style ribbon feel) --- */
        .toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: linear-gradient(180deg, #2b2d31 0%, #1e1f22 100%);
            border-bottom: 1px solid #3a3c40;
            box-shadow: 0 2px 12px rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            height: 52px;
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toolbar-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 14px;
        }

        .toolbar-title {
            color: #e5e7eb;
            font-size: 13px;
            font-weight: 600;
            max-width: 400px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .toolbar-subtitle {
            color: #9ca3af;
            font-size: 11px;
            font-weight: 400;
        }

        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toolbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .toolbar-btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            box-shadow: 0 1px 3px rgba(37,99,235,0.3);
        }
        .toolbar-btn-primary:hover {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: 0 2px 8px rgba(37,99,235,0.4);
            transform: translateY(-1px);
        }

        .toolbar-btn-secondary {
            background: #3a3c40;
            color: #d1d5db;
        }
        .toolbar-btn-secondary:hover {
            background: #4b4d52;
            color: #f3f4f6;
        }

        .toolbar-btn-success {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #fff;
            box-shadow: 0 1px 3px rgba(5,150,105,0.3);
        }
        .toolbar-btn-success:hover {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transform: translateY(-1px);
        }

        .toolbar-divider {
            width: 1px;
            height: 28px;
            background: #4b4d52;
            margin: 0 4px;
        }

        .toolbar-zoom {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #9ca3af;
            font-size: 11px;
            font-weight: 500;
        }

        .toolbar-zoom select {
            background: #3a3c40;
            color: #d1d5db;
            border: 1px solid #4b4d52;
            border-radius: 4px;
            padding: 3px 6px;
            font-size: 11px;
            cursor: pointer;
        }

        /* --- Sidebar Page Navigation --- */
        .sidebar {
            position: fixed;
            top: 52px;
            left: 0;
            bottom: 0;
            width: 200px;
            background: #2b2d31;
            border-right: 1px solid #3a3c40;
            overflow-y: auto;
            z-index: 999;
            padding: 16px 12px;
        }

        .sidebar-title {
            color: #9ca3af;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
            padding: 0 4px;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 6px;
            color: #d1d5db;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            margin-bottom: 2px;
        }
        .sidebar-item:hover {
            background: #3a3c40;
            color: #f9fafb;
        }
        .sidebar-item.active {
            background: rgba(37, 99, 235, 0.15);
            color: #60a5fa;
        }
        .sidebar-item i {
            font-size: 10px;
            width: 14px;
            text-align: center;
            opacity: 0.6;
        }

        .sidebar-separator {
            border-top: 1px solid #3a3c40;
            margin: 12px 4px;
        }

        .sidebar-info {
            padding: 8px 10px;
            border-radius: 6px;
            background: rgba(16,185,129,0.08);
            border: 1px solid rgba(16,185,129,0.15);
            margin-top: 8px;
        }
        .sidebar-info-label {
            color: #6ee7b7;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .sidebar-info-value {
            color: #d1d5db;
            font-size: 11px;
            font-weight: 500;
            margin-top: 2px;
        }

        /* --- Document Canvas (Main Area) --- */
        .doc-canvas {
            margin-left: 200px;
            padding-top: 76px;
            padding-bottom: 60px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #525659;
        }

        .doc-page-wrapper {
            margin-bottom: 16px;
        }

        /* --- Document Paper Page --- */
        .doc-page {
            width: 215mm;
            min-height: 330mm;
            background: #ffffff;
            padding: 20mm;
            box-shadow:
                0 1px 4px rgba(0,0,0,0.25),
                0 4px 16px rgba(0,0,0,0.15);
            border: 1px solid rgba(0,0,0,0.08);
            position: relative;
        }

        /* --- Ruler (Top) --- */
        .doc-ruler {
            width: 215mm;
            height: 22px;
            background: #f1f3f5;
            border: 1px solid #d0d0d0;
            border-bottom: 2px solid #c0c0c0;
            margin-bottom: 0;
            position: relative;
            display: flex;
            align-items: flex-end;
            padding: 0 20mm;
        }
        .doc-ruler::before {
            content: '';
            position: absolute;
            left: calc(20mm);
            right: calc(20mm);
            bottom: 0;
            height: 1px;
            background: repeating-linear-gradient(
                90deg,
                #999 0px, #999 1px,
                transparent 1px, transparent 10px
            );
        }

        /* --- Document Content Styles (Bookman Old Style 12pt) --- */
        .doc-content {
            font-family: 'Bookman Old Style', 'Bookman', 'URW Bookman L', Georgia, 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            font-weight: normal;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            box-sizing: border-box !important;
            max-width: 100% !important;
        }

        .doc-content * {
            font-weight: normal !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            max-width: 100% !important;
        }

        .doc-content h1,
        .doc-content h2,
        .doc-content h3,
        .doc-content h4,
        .doc-content b,
        .doc-content strong,
        .doc-content th {
            font-weight: normal !important;
        }

        /* Header Block Lampiran (Kanan Atas) */
        .header-lampiran {
            text-align: left;
            margin-left: 50%;
            line-height: 1.35;
            font-size: 12pt;
            text-transform: uppercase;
            margin-bottom: 24pt;
        }

        /* Heading BAB */
        .bab-heading {
            text-align: center;
            text-transform: uppercase;
            margin-top: 24pt;
            margin-bottom: 12pt;
            font-size: 12pt;
            line-height: 1.5;
        }

        .bab-heading.first-bab {
            margin-top: 0;
        }

        /* Sub-Bab */
        .subbab-heading {
            margin-top: 12pt;
            margin-bottom: 6pt;
            font-size: 12pt;
        }

        /* Paragraf narasi */
        .doc-content p {
            text-indent: 1cm;
            margin-bottom: 6pt;
            text-align: justify;
            font-size: 12pt;
            line-height: 1.5;
        }

        .doc-content .no-indent {
            text-indent: 0;
        }

        /* Tabel Renja */
        .doc-content table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8pt;
            margin-bottom: 12pt;
            font-size: 10pt;
        }

        .doc-content table th,
        .doc-content table td {
            border: 0.5pt solid #000;
            padding: 4pt 6pt;
            vertical-align: top;
            font-size: 10pt;
            line-height: 1.3;
        }

        .doc-content table th {
            text-align: center;
            background-color: #fafafa;
        }

        /* TTD Penutup */
        .signature-wrapper {
            float: right;
            width: 45%;
            text-align: center;
            margin-top: 24pt;
        }

        .clear { clear: both; }

        /* Page Number Footer */
        .page-number {
            position: absolute;
            bottom: 10mm;
            right: 20mm;
            font-size: 10pt;
            color: #888;
            font-family: 'Bookman Old Style', Georgia, serif;
        }

        /* Section Divider between BABs */
        .bab-section-break {
            page-break-before: always;
        }

        /* Content from editor (strip bold styling) */
        .section-content p {
            text-indent: 1cm;
            margin-bottom: 6pt;
            text-align: justify;
        }

        .section-content ul,
        .section-content ol {
            margin-left: 1cm;
            margin-bottom: 8pt;
        }

        .section-content li {
            margin-bottom: 3pt;
        }

        .section-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 8pt 0 12pt;
        }

        .section-content table th,
        .section-content table td {
            border: 0.5pt solid #000;
            padding: 4pt 6pt;
            vertical-align: top;
            font-size: 10pt;
        }

        /* Responsive adjustments */
        @media (max-width: 1100px) {
            .sidebar { display: none; }
            .doc-canvas { margin-left: 0; }
        }

        /* Zoom levels */
        .doc-canvas.zoom-75 .doc-page { transform: scale(0.75); transform-origin: top center; }
        .doc-canvas.zoom-90 .doc-page { transform: scale(0.90); transform-origin: top center; }
        .doc-canvas.zoom-100 .doc-page { transform: scale(1); }
        .doc-canvas.zoom-125 .doc-page { transform: scale(1.25); transform-origin: top center; }
        .doc-canvas.zoom-150 .doc-page { transform: scale(1.50); transform-origin: top center; }

        .doc-canvas.zoom-75 .doc-page-wrapper { margin-bottom: -80mm; }
        .doc-canvas.zoom-90 .doc-page-wrapper { margin-bottom: -30mm; }
        .doc-canvas.zoom-125 .doc-page-wrapper { margin-bottom: 80mm; }
        .doc-canvas.zoom-150 .doc-page-wrapper { margin-bottom: 160mm; }

        .doc-canvas.zoom-75 .doc-ruler { transform: scale(0.75); transform-origin: top center; }
        .doc-canvas.zoom-90 .doc-ruler { transform: scale(0.90); transform-origin: top center; }
        .doc-canvas.zoom-125 .doc-ruler { transform: scale(1.25); transform-origin: top center; }
        .doc-canvas.zoom-150 .doc-ruler { transform: scale(1.50); transform-origin: top center; }
    </style>
</head>
<body>

    @php
        $isDocFix = in_array(strtolower($document->status ?? ''), ['disetujui', 'approved', 'dikunci', 'final']);
        $isUploadWord = $isUploadWord ?? (($document->source_type ?? '') === 'upload_word');
        $groupedSections = $groupedSections ?? collect();
        $originalFileUrl = $originalFileUrl ?? ($isUploadWord ? route('renja.original-file', $document->id) : null);
        $originalFileHash = $originalFileHash ?? $document->original_file_hash;
        $originalFilename = $originalFilename ?? $document->original_filename;
        $originalFileSize = $originalFileSize ?? $document->original_file_size;
        $originalUploadedAt = $originalUploadedAt ?? ($document->metadata['uploaded_at'] ?? null);
        $originalUploadedBy = $originalUploadedBy ?? ($document->metadata['uploaded_by'] ?? null);
    @endphp

    <!-- ============================================================ -->
    <!-- TOOLBAR (Word-like Top Ribbon)                                -->
    <!-- ============================================================ -->
    <div class="toolbar no-print">
        <div class="toolbar-left">
            <div class="toolbar-icon" style="{{ $isDocFix ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' : 'background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);' }}">
                <i class="fa-solid {{ $isDocFix ? 'fa-file-circle-check' : 'fa-file-word' }}"></i>
            </div>
            <div>
                <div class="toolbar-title flex items-center gap-2">
                    <span>{{ $document->opd->nama_opd ?? 'Perangkat Daerah' }}</span>
                    @if($isDocFix)
                        <span style="background: #10b981; color: #022c22; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-lock" style="font-size: 8px; margin-right: 2px;"></i> FIX RESMI
                        </span>
                    @else
                        <span style="background: #f59e0b; color: #451a03; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.5px;">
                            DRAF
                        </span>
                    @endif
                </div>
                <div class="toolbar-subtitle">
                    {{ $document->jenis_dokumen ?? 'RENJA' }} TA {{ $document->tahun_anggaran ?? '2027' }}
                    @if(str_contains(strtolower($document->jenis_dokumen ?? ''), 'lampiran'))
                        &middot; {{ $document->opd->nomor_lampiran_romawi ?? 'LAMPIRAN I' }}
                    @endif
                    @if($isUploadWord)
                        &middot; <span style="color: #4ade80; font-size: 10px;"><i class="fa-solid fa-shield-check"></i> ORIGINAL FILE</span>
                    @else
                        &middot; Standar F4 Bookman Old Style 12pt
                    @endif
                </div>
            </div>
        </div>
        <div class="toolbar-right">
            <div class="toolbar-zoom">
                <i class="fa-solid fa-magnifying-glass"></i>
                <select id="zoomSelect" onchange="changeZoom(this.value)">
                    <option value="75">75%</option>
                    <option value="90">90%</option>
                    <option value="100" selected>100%</option>
                    <option value="125">125%</option>
                    <option value="150">150%</option>
                </select>
            </div>

            <div class="toolbar-divider"></div>

            <a href="{{ route('renja.workspace', ['tahun_anggaran' => $document->tahun_anggaran ?? session('active_ta', 2027)]) }}" class="toolbar-btn toolbar-btn-secondary" title="Kembali ke Workspace RENJA">
                <i class="fa-solid fa-folder-tree"></i>
                Workspace
            </a>

            <a href="{{ route('renja.exportWord', $document->id) }}" class="toolbar-btn toolbar-btn-secondary" style="background: #1e3a8a; color: #93c5fd;" title="Unduh File MS Word (.docx)">
                <i class="fa-solid fa-file-word"></i>
                Unduh Word
            </a>

            @if(!$isDocFix && !$isUploadWord)
                <a href="{{ route('renja.editor', $document->id) }}" class="toolbar-btn toolbar-btn-secondary">
                    <i class="fa-solid fa-pen-to-square"></i>
                    Editor
                </a>
            @endif

            <button onclick="window.print()" class="toolbar-btn toolbar-btn-primary" style="{{ $isDocFix ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' : '' }}">
                <i class="fa-solid fa-print"></i>
                Cetak / PDF F4
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SIDEBAR: Navigasi BAB (Word-like Navigation Pane)             -->
    <!-- ============================================================ -->
    <div class="sidebar no-print">
        <div class="sidebar-title">Navigasi Dokumen</div>

        @if($isUploadWord)
            {{-- Upload Word: tampilkan info file original, bukan navigasi BAB dari DB --}}
            <div class="sidebar-info" style="background: rgba(74,222,128,0.1); border-color: rgba(74,222,128,0.25); margin-bottom: 8px;">
                <div class="sidebar-info-label" style="color: #4ade80;"><i class="fa-solid fa-shield-check"></i> Original Document</div>
                <div class="sidebar-info-value" style="color: #86efac; font-size: 10px; word-break: break-all;">{{ $originalFilename ?? 'document.docx' }}</div>
            </div>
            @if($originalFileHash)
            <div class="sidebar-info" style="margin-top: 6px;">
                <div class="sidebar-info-label">SHA-256</div>
                <div class="sidebar-info-value" style="font-family: monospace; font-size: 10px; color: #94a3b8; word-break: break-all;" title="{{ $originalFileHash }}">{{ substr($originalFileHash, 0, 16) }}...</div>
            </div>
            @endif
            @if($originalFileSize)
            <div class="sidebar-info" style="margin-top: 6px;">
                <div class="sidebar-info-label">Ukuran File</div>
                <div class="sidebar-info-value">{{ number_format($originalFileSize / 1024, 1) }} KB</div>
            </div>
            @endif
            @if($originalUploadedAt)
            <div class="sidebar-info" style="margin-top: 6px;">
                <div class="sidebar-info-label">Diunggah</div>
                <div class="sidebar-info-value" style="font-size: 10px;">{{ date('d M Y H:i', strtotime($originalUploadedAt)) }}</div>
            </div>
            @endif
            @if($originalUploadedBy)
            <div class="sidebar-info" style="margin-top: 6px;">
                <div class="sidebar-info-label">Oleh</div>
                <div class="sidebar-info-value" style="font-size: 10px;">{{ $originalUploadedBy }}</div>
            </div>
            @endif
            <div class="sidebar-separator"></div>
            <div class="sidebar-info" style="margin-top: 6px; background: rgba(59,130,246,0.08); border-color: rgba(59,130,246,0.2);">
                <div class="sidebar-info-label" style="color: #60a5fa; font-size: 9px;">INFO</div>
                <div class="sidebar-info-value" style="font-size: 9px; color: #93c5fd; line-height: 1.4;">Dokumen disimpan dalam format asli sesuai file yang diupload. Layout dan formatting dipertahankan.</div>
            </div>
        @else
            {{-- Template/Editor: navigasi BAB dari sections DB --}}
            @php
                $babCodes = ['BAB I', 'BAB II', 'BAB III', 'BAB IV', 'BAB V', 'BAB VI'];
            @endphp

            @foreach($babCodes as $idx => $babCode)
                @php
                    $babSections = $groupedSections->get($babCode, collect());
                    $babTitle = $babSections->first()->bab_title ?? '';
                    $hasSections = $babSections->isNotEmpty();
                @endphp

                @if($hasSections)
                    <a href="#{{ Str::slug($babCode) }}" class="sidebar-item" onclick="scrollToBab('{{ Str::slug($babCode) }}')">
                        <i class="fa-solid fa-bookmark"></i>
                        <span>{{ $babCode }}</span>
                    </a>
                @endif
            @endforeach
        @endif

        <div class="sidebar-separator"></div>

        <div class="sidebar-info" style="{{ $isDocFix ? 'background: rgba(16,185,129,0.12); border-color: rgba(16,185,129,0.3);' : '' }}">
            <div class="sidebar-info-label" style="{{ $isDocFix ? 'color: #34d399;' : '' }}">Status Dokumen</div>
            <div class="sidebar-info-value" style="{{ $isDocFix ? 'color: #10b981; font-weight: 700;' : 'color: #fbbf24;' }}">
                @if($isDocFix)
                    <i class="fa-solid fa-circle-check"></i> FIX (Disetujui)
                @else
                    {{ strtoupper($document->status ?? 'DRAFT') }}
                @endif
            </div>
        </div>

        <div class="sidebar-info" style="margin-top: 6px;">
            <div class="sidebar-info-label">Format Kertas</div>
            <div class="sidebar-info-value">F4 / Folio (215×330mm)</div>
        </div>
        <div class="sidebar-info" style="margin-top: 6px;">
            <div class="sidebar-info-label">Font Standar</div>
            <div class="sidebar-info-value">Bookman Old Style 12pt</div>
        </div>
        <div class="sidebar-info" style="margin-top: 6px;">
            <div class="sidebar-info-label">Margin</div>
            <div class="sidebar-info-value">2 cm (semua sisi)</div>
        </div>
        @if(str_contains(strtolower($document->jenis_dokumen ?? ''), 'lampiran'))
        <div class="sidebar-info" style="margin-top: 6px;">
            <div class="sidebar-info-label">Nomor Lampiran</div>
            <div class="sidebar-info-value" style="color: #60a5fa; font-weight: 700;">{{ $document->opd->nomor_lampiran_romawi ?? 'LAMPIRAN I' }}</div>
        </div>
        @endif
    </div>

    <!-- ============================================================ -->
    <!-- DOCUMENT CANVAS (Word-like Gray Canvas with White Pages)      -->
    <!-- ============================================================ -->
    @if($isUploadWord)
        <div class="doc-canvas zoom-100 flex flex-col items-center" id="docCanvas" style="padding-top: 76px; background: #525659; overflow-y: auto; min-height: 100vh;">
            <div class="no-print flex items-center justify-center p-3 border text-[11px] font-bold rounded-xl gap-2 mb-4 mt-4" style="width: 215mm; background: rgba(74,222,128,0.08); border-color: rgba(74,222,128,0.25); color: #86efac;">
                <i class="fa-solid fa-shield-check" style="color: #4ade80;"></i>
                <span>ORIGINAL DOCUMENT — Layout &amp; formatting 100% berasal dari file Word yang diupload.</span>
                @if($originalFileHash)
                <span style="font-family: monospace; color: #64748b; margin-left: 8px; font-size: 10px;">SHA-256: {{ substr($originalFileHash, 0, 12) }}...</span>
                @endif
            </div>
            <div id="docx-preview-container" class="docx-preview-wrapper" style="width: 215mm; background: white; box-shadow: 0 4px 16px rgba(0,0,0,0.15); margin-bottom: 40px;"></div>
        </div>
    @else
        <div class="doc-canvas zoom-100" id="docCanvas">

            <!-- Ruler -->
            <div class="doc-ruler no-print"></div>

            @php
                $pageNum = 1;
                $isFirstBab = true;
            @endphp

        {{-- === HALAMAN 1: HEADER LAMPIRAN + BAB I === --}}
        <div class="doc-page-wrapper">
            <div class="doc-page doc-content" id="bab-i">

                {{-- Header Block Lampiran Resmi Perbup & Kepbup Renja / Renstra --}}
                @if(str_contains(strtolower($document->jenis_dokumen ?? ''), 'lampiran'))
                <div class="header-lampiran">
                    @if(str_contains(strtolower($document->jenis_dokumen ?? ''), 'perubahan'))
                        {{ strtoupper($document->opd->nomor_lampiran_romawi ?? 'LAMPIRAN I') }}<br>
                        KEPUTUSAN BUPATI CIREBON<br>
                        NOMOR TAHUN {{ $document->tahun_anggaran ?? '2025' }}<br>
                        TENTANG<br>
                        PERUBAHAN RENCANA KERJA {{ strtoupper($document->opd->nama_opd ?? 'PERANGKAT DAERAH') }} TAHUN {{ $document->tahun_anggaran ?? '2025' }}
                    @elseif(str_contains(strtolower($document->jenis_dokumen ?? ''), 'renstra'))
                        {{ strtoupper($document->opd->nomor_lampiran_romawi ?? 'LAMPIRAN I') }}<br>
                        PERATURAN BUPATI CIREBON<br>
                        NOMOR TAHUN 2025-2029<br>
                        TENTANG<br>
                        RENCANA STRATEGIS {{ strtoupper($document->opd->nama_opd ?? 'PERANGKAT DAERAH') }} TAHUN 2025-2029
                    @else
                        {{ strtoupper($document->opd->nomor_lampiran_romawi ?? 'LAMPIRAN I') }}<br>
                        PERATURAN BUPATI CIREBON<br>
                        NOMOR TAHUN {{ $document->tahun_anggaran ?? '2026' }}<br>
                        TENTANG<br>
                        RENCANA KERJA {{ strtoupper($document->opd->nama_opd ?? 'PERANGKAT DAERAH') }} TAHUN {{ $document->tahun_anggaran ?? '2026' }}
                    @endif
                </div>
                @endif

                {{-- BAB I --}}
                @php
                    $babISections = $groupedSections->get('BAB I', collect());
                    $babITitle = $babISections->first()->bab_title ?? 'PENDAHULUAN';
                @endphp

                <div class="bab-heading first-bab" id="bab-i">
                    BAB I<br>
                    {{ strtoupper($babITitle) }}
                </div>

                @foreach($babISections as $section)
                    <div class="subbab-heading">{{ $section->sub_bab_code }}. {{ $section->sub_bab_title }}</div>
                    <div class="section-content">
                        {!! strip_tags($section->content ?? '<p>Belum ada isi.</p>', '<p><br><ol><ul><li><table><thead><tbody><tr><th><td><i><u><em><span><div><a>') !!}
                    </div>
                @endforeach

                @if($babISections->isEmpty())
                    <div class="bab-heading first-bab">
                        BAB I<br>
                        PENDAHULUAN
                    </div>
                    <div class="subbab-heading">1.1. Latar Belakang</div>
                    {!! $document->latar_belakang ?? '<p>Belum ada isi latar belakang.</p>' !!}

                    <div class="subbab-heading">1.2. Landasan Hukum</div>
                    {!! $document->landasan_hukum ?? '<p>Belum ada isi landasan hukum.</p>' !!}

                    <div class="subbab-heading">1.3. Maksud dan Tujuan</div>
                    {!! $document->maksud_tujuan ?? '<p>Belum ada isi maksud dan tujuan.</p>' !!}

                    <div class="subbab-heading">1.4. Sistematika Penulisan</div>
                    {!! $document->sistematika ?? '<p>Belum ada isi sistematika penulisan.</p>' !!}
                @endif

                <span class="page-number">{{ $pageNum++ }}</span>
            </div>
        </div>

        {{-- === HALAMAN SELANJUTNYA: BAB II s.d. BAB VI (DINAMIS) === --}}
        @foreach(['BAB II', 'BAB III', 'BAB IV', 'BAB V', 'BAB VI'] as $babCode)
            @php
                $babSections = $groupedSections->get($babCode, collect());
                $babTitle = $babSections->first()->bab_title ?? '';
            @endphp

            @if($babSections->isNotEmpty())
                <div class="doc-page-wrapper">
                    <div class="doc-page doc-content bab-section-break" id="{{ Str::slug($babCode) }}">

                        <div class="bab-heading first-bab">
                            {{ $babCode }}<br>
                            {{ strtoupper($babTitle) }}
                        </div>

                        @foreach($babSections as $section)
                            <div class="subbab-heading">{{ $section->sub_bab_code }}. {{ $section->sub_bab_title }}</div>
                            <div class="section-content">
                                {!! strip_tags($section->content ?? '<p>Belum ada isi.</p>', '<p><br><ol><ul><li><table><thead><tbody><tr><th><td><i><u><em><span><div><a>') !!}
                            </div>
                        @endforeach

                        {{-- Tabel Evaluasi (BAB II) --}}
                        @if($babCode === 'BAB II')
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th style="width: 15%;">Kode Rekening</th>
                                        <th>Nama Program / Kegiatan</th>
                                        <th>Indikator Kinerja</th>
                                        <th style="width: 12%;">Target Capaian</th>
                                        <th style="width: 15%;">Pagu Indikatif (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($document->tableEvals as $index => $eval)
                                        <tr>
                                            <td style="text-align: center;">{{ $index + 1 }}</td>
                                            <td>{{ $eval->kode_rekening }}</td>
                                            <td>{{ $eval->nama_program_kegiatan }}</td>
                                            <td>{{ $eval->indikator_kinerja }}</td>
                                            <td style="text-align: center;">{{ $eval->target_capaian }}</td>
                                            <td style="text-align: right;">{{ number_format($eval->pagu_indikatif, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" style="text-align: center; color: #999;">Data evaluasi belum diinput.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @endif

                        {{-- Tabel Utama Renja (BAB IV) --}}
                        @if($babCode === 'BAB IV')
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 14%;">Kode Rekening</th>
                                        <th>Uraian Program / Kegiatan</th>
                                        <th>Indikator Kinerja</th>
                                        <th>Lokasi</th>
                                        <th style="width: 10%;">Target {{ $document->tahun_anggaran ?? '2027' }}</th>
                                        <th style="width: 14%;">Pagu {{ $document->tahun_anggaran ?? '2027' }} (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($document->tableUtamas as $utama)
                                        <tr>
                                            <td>{{ $utama->kode_rekening }}</td>
                                            <td>{{ $utama->uraian }}</td>
                                            <td>{{ $utama->indikator }}</td>
                                            <td>{{ $utama->lokasi }}</td>
                                            <td style="text-align: center;">{{ $utama->target_2027 }}</td>
                                            <td style="text-align: right;">{{ number_format($utama->pagu_2027, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" style="text-align: center; color: #999;">Data program utama belum diinput.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @endif

                        {{-- TTD Penutup (BAB V) --}}
                        @if($babCode === 'BAB V')
                            <div style="margin-top: 36pt;">
                                <div class="signature-wrapper">
                                    BUPATI CIREBON,<br><br><br><br><br>
                                    IMRON
                                </div>
                                <div class="clear"></div>
                            </div>
                        @endif

                        <span class="page-number">{{ $pageNum++ }}</span>
                    </div>
                </div>
            @elseif($babCode === 'BAB II' && $babSections->isEmpty())
                {{-- Fallback BAB II dari field lama --}}
                <div class="doc-page-wrapper">
                    <div class="doc-page doc-content bab-section-break" id="bab-ii">
                        <div class="bab-heading first-bab">
                            BAB II<br>
                            EVALUASI PELAKSANAAN RENJA OPD TAHUN LALU
                        </div>

                        <div class="subbab-heading">2.1. Evaluasi Pelaksanaan Renja Perangkat Daerah</div>
                        {!! $document->evaluasi_narasi ?? '<p>Berikut disajikan hasil evaluasi pelaksanaan Rencana Kerja Perangkat Daerah.</p>' !!}

                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 15%;">Kode Rekening</th>
                                    <th>Nama Program / Kegiatan</th>
                                    <th>Indikator Kinerja</th>
                                    <th style="width: 12%;">Target Capaian</th>
                                    <th style="width: 15%;">Pagu Indikatif (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($document->tableEvals as $index => $eval)
                                    <tr>
                                        <td style="text-align: center;">{{ $index + 1 }}</td>
                                        <td>{{ $eval->kode_rekening }}</td>
                                        <td>{{ $eval->nama_program_kegiatan }}</td>
                                        <td>{{ $eval->indikator_kinerja }}</td>
                                        <td style="text-align: center;">{{ $eval->target_capaian }}</td>
                                        <td style="text-align: right;">{{ number_format($eval->pagu_indikatif, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: #999;">Data evaluasi belum diinput.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <span class="page-number">{{ $pageNum++ }}</span>
                    </div>
                </div>
            @elseif($babCode === 'BAB III' && $babSections->isEmpty())
                <div class="doc-page-wrapper">
                    <div class="doc-page doc-content bab-section-break" id="bab-iii">
                        <div class="bab-heading first-bab">
                            BAB III<br>
                            ISU-ISU STRATEGIS PERANGKAT DAERAH
                        </div>
                        <div class="subbab-heading">3.1. Analisis Isu Strategis Perangkat Daerah</div>
                        {!! $document->isu_strategis_narasi ?? '<p>Belum ada narasi isu strategis.</p>' !!}

                        <span class="page-number">{{ $pageNum++ }}</span>
                    </div>
                </div>
            @elseif($babCode === 'BAB IV' && $babSections->isEmpty())
                <div class="doc-page-wrapper">
                    <div class="doc-page doc-content bab-section-break" id="bab-iv">
                        <div class="bab-heading first-bab">
                            BAB IV<br>
                            TUJUAN, SASARAN, PROGRAM DAN KEGIATAN
                        </div>

                        <div class="subbab-heading">4.1. Tujuan dan Sasaran Renja</div>
                        {!! $document->tujuan_sasaran_narasi ?? '<p>Belum ada narasi tujuan dan sasaran.</p>' !!}

                        <div class="subbab-heading">4.2. Rencana Program dan Kegiatan Utama</div>
                        {!! $document->program_kegiatan_narasi ?? '<p>Rincian rencana program dan kegiatan disajikan pada tabel utama di bawah ini.</p>' !!}

                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 14%;">Kode Rekening</th>
                                    <th>Uraian Program / Kegiatan</th>
                                    <th>Indikator Kinerja</th>
                                    <th>Lokasi</th>
                                    <th style="width: 10%;">Target {{ $document->tahun_anggaran ?? '2027' }}</th>
                                    <th style="width: 14%;">Pagu {{ $document->tahun_anggaran ?? '2027' }} (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($document->tableUtamas as $utama)
                                    <tr>
                                        <td>{{ $utama->kode_rekening }}</td>
                                        <td>{{ $utama->uraian }}</td>
                                        <td>{{ $utama->indikator }}</td>
                                        <td>{{ $utama->lokasi }}</td>
                                        <td style="text-align: center;">{{ $utama->target_2027 }}</td>
                                        <td style="text-align: right;">{{ number_format($utama->pagu_2027, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: #999;">Data program utama belum diinput.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <span class="page-number">{{ $pageNum++ }}</span>
                    </div>
                </div>
            @elseif($babCode === 'BAB V' && $babSections->isEmpty())
                <div class="doc-page-wrapper">
                    <div class="doc-page doc-content bab-section-break" id="bab-v">
                        <div class="bab-heading first-bab">
                            BAB V<br>
                            PENUTUP
                        </div>

                        {!! $document->penutup_narasi ?? '<p>Demikian Rencana Kerja Perangkat Daerah ini disusun sebagai pedoman dalam pelaksanaan program dan kegiatan pembangunan tahun anggaran mendatang demi terwujudnya tata kelola pemerintahan yang efektif dan sejahtera di Kabupaten Cirebon.</p>' !!}

                        <div style="margin-top: 36pt;">
                            <div class="signature-wrapper">
                                BUPATI CIREBON,<br><br><br><br><br>
                                IMRON
                            </div>
                            <div class="clear"></div>
                        </div>

                        <span class="page-number">{{ $pageNum++ }}</span>
                    </div>
                </div>
            @endif
        @endforeach

    </div>
    @endif

    <script>
        // Zoom control
        function changeZoom(level) {
            const canvas = document.getElementById('docCanvas');
            canvas.className = 'doc-canvas zoom-' + level;
        }

        // Smooth scroll to BAB
        function scrollToBab(slug) {
            const el = document.getElementById(slug);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Highlight active sidebar item on scroll
        document.addEventListener('scroll', function() {
            const items = document.querySelectorAll('.sidebar-item');
            const babs = document.querySelectorAll('.doc-page[id]');

            let currentBab = '';
            babs.forEach(bab => {
                const rect = bab.getBoundingClientRect();
                if (rect.top <= 200) {
                    currentBab = bab.id;
                }
            });

            items.forEach(item => {
                const href = item.getAttribute('href');
                if (href && href === '#' + currentBab) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>

    @if($isUploadWord)
    <!-- JSZip and docx-preview for original DOCX rendering in browser -->
    <script src="https://unpkg.com/jszip/dist/jszip.min.js"></script>
    <script src="https://unpkg.com/docx-preview/dist/docx-preview.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const container = document.getElementById("docx-preview-container");
            const originalFileUrl = "{{ $originalFileUrl }}";
            const expectedHash = "{{ $originalFileHash ?? '' }}";

            // Set loading state
            container.innerHTML = `
                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px;color:#64748b;">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem;margin-bottom:12px;color:#3b82f6;"></i>
                    <p style="font-size:13px;font-weight:600;">Memuat Dokumen Word Asli...</p>
                    <p style="font-size:11px;color:#94a3b8;margin-top:4px;">Layout 100% dari file original</p>
                </div>
            `;

            fetch(originalFileUrl)
                .then(response => {
                    if (!response.ok) throw new Error("HTTP " + response.status + ": Gagal mengambil file original.");
                    return response.blob();
                })
                .then(blob => {
                    container.innerHTML = "";
                    if (expectedHash) {
                        console.info("[RENJA Original File] SHA-256 stored: " + expectedHash);
                        console.info("[RENJA Original File] File preview URL: " + originalFileUrl);
                    }
                    return docx.renderAsync(blob, container, null, {
                        className: "docx",
                        inWrapper: true,
                        ignoreWidth: false,
                        ignoreHeight: false,
                        ignoreFonts: false,
                        breakPages: true,
                        useBase64URL: true,
                        useMathMLPolyfill: false,
                        renderHeaders: true,
                        renderFooters: true,
                        renderFootnotes: true,
                        renderEndnotes: true,
                    });
                })
                .then(() => {
                    console.info("[RENJA Original File] Dokumen berhasil dirender dari file original.");
                })
                .catch(err => {
                    container.innerHTML = `
                        <div style="padding:32px;text-align:center;color:#dc2626;">
                            <i class="fa-solid fa-triangle-exclamation" style="font-size:2rem;margin-bottom:12px;display:block;"></i>
                            <p style="font-weight:700;margin-bottom:8px;">Gagal memuat file original</p>
                            <p style="font-size:12px;color:#9ca3af;">${err.message}</p>
                            <p style="font-size:11px;color:#6b7280;margin-top:8px;">Coba unduh file Word secara langsung menggunakan tombol "Unduh Word" di toolbar.</p>
                        </div>
                    `;
                    console.error("[RENJA Original File] Error:", err);
                });
        });
    </script>
    @endif

</body>
</html>
