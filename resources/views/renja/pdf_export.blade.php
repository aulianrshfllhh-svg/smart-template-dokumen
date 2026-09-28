<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $document->jenis_dokumen }} TA {{ $document->tahun_anggaran }} - {{ $document->opd->nama_opd ?? '' }}</title>
    <style>
        /* ============================================================ */
        /* PDF Export Style — dompdf WYSIWYG                           */
        /* Kertas F4 (215mm x 330mm), margin 20mm                       */
        /* Font: DejaVu Serif (pengganti Bookman Old Style di dompdf)   */
        /* ============================================================ */
        @page {
            size: 215mm 330mm;
            margin: 20mm 20mm 20mm 20mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "DejaVu Serif", Georgia, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000000;
            background: #ffffff;
        }

        /* Page breaks */
        .page-break {
            page-break-before: always;
            break-before: page;
        }

        /* Cover */
        .cover-page {
            text-align: center;
            padding-top: 4cm;
            min-height: 26cm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
        }

        .cover-title {
            font-size: 14pt;
            font-weight: normal;
            text-transform: uppercase;
            line-height: 1.6;
            margin-bottom: 12pt;
        }

        .cover-subtitle {
            font-size: 12pt;
            font-weight: normal;
            text-transform: uppercase;
        }

        .cover-bottom {
            font-size: 12pt;
            font-weight: normal;
            text-transform: uppercase;
            line-height: 2.0;
        }

        /* Heading BAB */
        .bab-heading {
            text-align: center;
            text-transform: uppercase;
            font-size: 12pt;
            font-weight: normal;
            line-height: 1.5;
            margin-top: 0;
            margin-bottom: 20pt;
        }

        /* Heading Sub-BAB */
        .subbab-heading {
            font-size: 12pt;
            font-weight: normal;
            line-height: 1.5;
            margin-top: 14pt;
            margin-bottom: 6pt;
        }

        /* Isi narasi paragraf */
        p {
            text-indent: 1cm;
            margin-bottom: 6pt;
            text-align: justify;
            line-height: 1.5;
            font-size: 12pt;
            font-weight: normal;
        }

        /* List tanpa indent */
        ul, ol {
            margin-left: 1.5cm;
            margin-bottom: 6pt;
            font-size: 12pt;
        }

        li {
            margin-bottom: 3pt;
            font-size: 12pt;
        }

        /* Tabel */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8pt;
            margin-bottom: 12pt;
            font-size: 10pt;
        }

        th, td {
            border: 0.5pt solid #000000;
            padding: 4pt 6pt;
            vertical-align: top;
            font-size: 10pt;
            line-height: 1.3;
        }

        th {
            background-color: #F3F4F6;
            text-align: center;
            font-weight: bold;
        }

        /* Caption tabel/gambar */
        .element-caption {
            font-size: 10pt;
            font-style: italic;
            text-align: center;
            margin-bottom: 4pt;
            margin-top: 8pt;
            font-weight: normal;
        }

        /* Daftar Isi, Daftar Tabel, Daftar Gambar */
        .index-title {
            text-align: center;
            text-transform: uppercase;
            font-size: 12pt;
            font-weight: normal;
            margin-bottom: 20pt;
        }

        .index-table {
            width: 100%;
            border-collapse: collapse;
            line-height: 2.0;
            font-size: 12pt;
        }

        .index-table td {
            border: none;
            padding: 2pt 0;
            font-size: 12pt;
        }

        .index-table .page-num {
            text-align: right;
            width: 40pt;
            white-space: nowrap;
        }

        .index-bab-row td {
            padding-top: 10pt;
            text-transform: uppercase;
            font-weight: normal;
        }

        .index-subbab-row td {
            padding-left: 24pt;
            font-weight: normal;
        }

        /* Header/Footer dompdf */
        #header {
            position: fixed;
            top: -15mm;
            left: 0;
            right: 0;
            font-size: 9pt;
            font-family: "DejaVu Serif", Georgia, serif;
            color: #333333;
            border-bottom: 0.5pt solid #cccccc;
            padding-bottom: 3pt;
        }

        #footer {
            position: fixed;
            bottom: -15mm;
            left: 0;
            right: 0;
            font-size: 9pt;
            font-family: "DejaVu Serif", Georgia, serif;
            text-align: center;
            color: #333333;
            border-top: 0.5pt solid #cccccc;
            padding-top: 3pt;
        }

        .page-number:after {
            content: counter(page);
        }

        .page-total:after {
            content: counter(pages);
        }
    </style>
</head>
<body>
    {{-- Header dokumen --}}
    <div id="header">
        {{ strtoupper($document->opd->nama_opd ?? 'PERANGKAT DAERAH') }}
        &mdash;
        {{ $document->jenis_dokumen }}
        TA {{ $document->tahun_anggaran }}
    </div>

    {{-- Footer halaman --}}
    <div id="footer">
        - <span class="page-number"></span> -
    </div>

    {{-- COVER --}}
    @php $coverData = $document->cover_data ?? []; @endphp
    @if(!empty($coverData) || !empty($document->tahun_anggaran))
    <div class="cover-page">
        <div>
            <div class="cover-title">
                {{ strtoupper($coverData['judul_dokumen'] ?? $document->jenis_dokumen ?? 'RENCANA KERJA') }}
            </div>
            <div class="cover-subtitle">
                TAHUN ANGGARAN {{ $coverData['tahun_anggaran'] ?? $document->tahun_anggaran }}
            </div>
        </div>

        <div class="cover-bottom">
            <div>{{ strtoupper($coverData['nama_opd'] ?? $document->opd->nama_opd ?? '') }}</div>
            <div>{{ strtoupper($coverData['nama_pemda'] ?? 'PEMERINTAH KABUPATEN CIREBON') }}</div>
            <div>{{ strtoupper($coverData['lokasi'] ?? 'SUMBER') }}</div>
            <div>{{ $coverData['tahun_terbit'] ?? date('Y') }}</div>
        </div>
    </div>
    @endif

    {{-- FRONT MATTER (Kata Pengantar, Daftar Isi, Daftar Tabel, Daftar Gambar) --}}
    @foreach($frontSections as $frontSec)
        @if($frontSec->section_type === 'cover') @continue @endif
        <div class="page-break">
            <h2 class="index-title">{{ strtoupper($frontSec->sub_bab_title ?? '') }}</h2>
            @if($frontSec->section_type === 'table_of_contents' || $frontSec->section_type === 'list_of_tables' || $frontSec->section_type === 'list_of_figures' || $frontSec->section_type === 'list_of_appendices')
                {!! app(\App\Services\DocumentHtmlService::class)->forDocumentDisplay($document, $frontSec->content) !!}
            @else
                <div style="font-family:'DejaVu Serif',Georgia,serif; font-size:12pt;">
                    {!! app(\App\Services\DocumentHtmlService::class)->forDocumentDisplay($document, $frontSec->content) !!}
                </div>
            @endif
        </div>
    @endforeach

    {{-- BAB UTAMA --}}
    @php
        $babTitleDefaults = $babTitleDefaults ?? [
            'BAB I'   => 'PENDAHULUAN',
            'BAB II'  => 'HASIL EVALUASI RENJA PERANGKAT DAERAH TAHUN LALU',
            'BAB III' => 'TUJUAN DAN SASARAN PERANGKAT DAERAH',
            'BAB IV'  => 'RENCANA KERJA DAN PENDANAAN PERANGKAT DAERAH',
            'BAB V'   => 'PENUTUP',
        ];
    @endphp

    @foreach($groupedBabs as $babCode => $babSections)
        <div class="page-break">
            @php
                $firstBabSec = $babSections->first();
                $rawBabTitle = $firstBabSec->bab_title ?? '';
                if (empty($rawBabTitle) || strtoupper(trim($rawBabTitle)) === strtoupper(trim($babCode))) {
                    $displayBabTitle = $babTitleDefaults[$babCode] ?? '';
                } else {
                    $displayBabTitle = strtoupper($rawBabTitle);
                }
            @endphp

            <div class="bab-heading">
                {{ strtoupper($babCode) }}<br>
                @if(!empty($displayBabTitle) && $displayBabTitle !== strtoupper($babCode))
                    {{ $displayBabTitle }}
                @endif
            </div>

            @foreach($babSections as $sec)
                @if($sec->section_type === 'chapter')
                    {{-- Konten langsung di bawah BAB tanpa sub-bab --}}
                    <div style="font-family:'DejaVu Serif',Georgia,serif; font-size:12pt;">
                        {!! app(\App\Services\DocumentHtmlService::class)->forDocumentDisplay($document, $sec->content) !!}
                    </div>
                @else
                    @php
                        $cleanSubTitle = preg_replace('/^\d+(\.\d+)*\s*/', '', $sec->sub_bab_title ?? '');
                    @endphp
                    <div class="subbab-heading">
                        {{ $sec->sub_bab_code }} {{ $cleanSubTitle }}
                    </div>
                    <div style="font-family:'DejaVu Serif',Georgia,serif; font-size:12pt;">
                        {!! app(\App\Services\DocumentHtmlService::class)->forDocumentDisplay($document, $sec->content) !!}
                    </div>
                @endif
            @endforeach
        </div>
    @endforeach

    {{-- LAMPIRAN --}}
    @foreach($appendixSections as $ap)
        <div class="page-break">
            <div class="bab-heading">
                {{ strtoupper($ap->sub_bab_code ?? 'LAMPIRAN') }}<br>
                @if(!empty($ap->sub_bab_title))
                    {{ strtoupper($ap->sub_bab_title) }}
                @endif
            </div>
            <div style="font-family:'DejaVu Serif',Georgia,serif; font-size:12pt;">
                {!! app(\App\Services\DocumentHtmlService::class)->forDocumentDisplay($document, $ap->content) !!}
            </div>
        </div>
    @endforeach

</body>
</html>
