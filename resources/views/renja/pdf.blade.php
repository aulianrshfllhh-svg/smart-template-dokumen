<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $document->jenis_dokumen }} TA {{ $document->tahun_anggaran }} — {{ $document->opd->nama_opd ?? 'OPD' }}</title>
    <style>
        @page {
            size: 215mm 330mm; /* Folio F4 Standar Pemkab Cirebon */
            margin: 20mm 20mm 20mm 20mm;
        }

        body {
            font-family: 'Bookman Old Style', Georgia, serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000000;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        @if($document->isLampiranPerbub())
        * {
            font-weight: normal !important;
        }
        @endif

        .header-romawi {
            text-align: right;
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
            border-b: 1px solid #000;
            padding-bottom: 5px;
        }

        .doc-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 15px;
            margin-bottom: 25px;
        }

        .bab-header {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-top: 30px;
            margin-bottom: 15px;
            {{ ($isRenja ?? false) ? 'page-break-before: avoid;' : 'page-break-before: always;' }}
        }

        .bab-header:first-of-type {
            {{ (($isRenja ?? false) || ($frontSections->count() ?? 0) === 0) ? 'page-break-before: avoid;' : 'page-break-before: always;' }}
        }

        .subbab-header {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 8px;
        }

        p {
            text-align: justify;
            text-indent: 30px;
            margin-bottom: 12px;
            margin-top: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            margin-bottom: 16px;
            font-size: 11pt;
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th {
            border: 1px solid #000000;
            padding: 6px 8px;
            font-weight: bold;
            background-color: #f1f5f9;
            text-align: center;
        }

        td {
            border: 1px solid #000000;
            padding: 6px 8px;
        }

        .ttd-bupati {
            margin-top: 40px;
            float: right;
            width: 250px;
            text-align: center;
            font-weight: bold;
            page-break-inside: avoid;
        }

        .clear {
            clear: both;
        }
    </style>
</head>
<body>

@php
    $isLampiranDoc = $document->isLampiranPerbub();
    $sectionsToRender = $effectiveSections ?? $document->getEffectiveSections();
    $babsToRender = $sectionsToRender->groupBy('bab_code');
@endphp

    @if($isLampiranDoc)
        @php
            $isPerubahanDoc = str_contains(strtolower($document->jenis_dokumen ?? ''), 'perubahan');
            $taPeraturan = $isPerubahanDoc ? $document->tahun_anggaran : ($document->tahun_anggaran > 2020 ? ($document->tahun_anggaran - 1) : $document->tahun_anggaran);
        @endphp
        <!-- HEADER IDENTITAS DOKUMEN LAMPIRAN RESMI -->
        <div style="margin-left: 45%; text-align: left; line-height: 1.25; margin-bottom: 30px; font-size: 12pt;">
            {{ strtoupper($romawiHeader ?? 'LAMPIRAN I') }}<br>
            {{ $isPerubahanDoc ? 'KEPUTUSAN BUPATI CIREBON' : 'PERATURAN BUPATI CIREBON' }}<br>
            NOMOR           TAHUN {{ $taPeraturan }}<br>
            TENTANG<br>
            {{ $isPerubahanDoc ? 'PERUBAHAN RENCANA KERJA PERANGKAT DAERAH' : 'RENCANA KERJA PERANGKAT DAERAH' }} TAHUN {{ $document->tahun_anggaran }}
        </div>
    @else
        <!-- HEADER NOMOR LAMPIRAN ROMAWI OPD -->
        <div class="header-romawi">
            {{ $romawiHeader ?? 'LAMPIRAN I' }} PERATURAN BUPATI CIREBON<br>
            NOMOR {{ $document->opd->nomor_perbup ?? '86' }} TAHUN {{ $document->tahun_anggaran }}<br>
            TENTANG RENCANA KERJA {{ strtoupper($document->opd->nama_opd ?? 'PERANGKAT DAERAH') }}
        </div>

        <!-- DOKUMEN TITLE -->
        <div class="doc-title">
            {{ strtoupper($document->jenis_dokumen) }}<br>
            {{ strtoupper($document->opd->nama_opd ?? 'PERANGKAT DAERAH') }}<br>
            KABUPATEN CIREBON TAHUN ANGGARAN {{ $document->tahun_anggaran }}
        </div>
    @endif

    <!-- SEKSI BAB & CONTENT -->
    @foreach($babsToRender as $babCode => $bSecs)
        <div class="bab-header" style="{{ $isLampiranDoc ? 'font-weight: normal;' : '' }}">
            {{ strtoupper($babCode) }}<br>
            {{ strtoupper($bSecs->first()->bab_title ?? '') }}
        </div>

        @foreach($bSecs as $sec)
            @php
                $isChapterRow = ($sec->section_type === 'chapter') || (strtoupper(trim($sec->sub_bab_code ?? '')) === strtoupper(trim($sec->bab_code ?? '')));
                $hasSecContent = !empty(trim(strip_tags($sec->content ?? '')));
            @endphp

            @if($sec->sub_bab_title && !$isChapterRow)
                <div class="subbab-header" style="{{ $isLampiranDoc ? 'font-weight: normal;' : '' }}">
                    {{ strtoupper($sec->sub_bab_code) }}. {{ strtoupper($sec->sub_bab_title) }}
                </div>
            @endif

            @if($hasSecContent)
                <div class="section-body">
                    {!! $isLampiranDoc ? \App\Services\RenjaAutoFixService::stripBoldForLampiran($sec->content) : $sec->content !!}
                </div>
            @endif
        @endforeach
    @endforeach

    <!-- BLOK TANDA TANGAN BUPATI CIREBON (ANTI-ORPHAN) -->
    <div class="ttd-bupati">
        BUPATI CIREBON,<br><br><br><br><br>
        <strong>IMRON</strong>
    </div>

    <div class="clear"></div>

</body>
</html>
