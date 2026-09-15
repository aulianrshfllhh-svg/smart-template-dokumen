<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review & Verifikasi Dokumen F4 — Bapperida Kabupaten Cirebon</title>
    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- FontAwesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #e5e7eb;
            margin: 0;
            padding: 0;
        }

        /* ===== WORKSPACE CANVAS ABU-ABU ABU MS WORD ===== */
        .review-workspace {
            background-color: #e5e7eb; /* Canvas Abu-Abu khas MS Word / Google Docs */
            padding: 40px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
            min-height: calc(100vh - 70px);
            box-sizing: border-box;
            width: 100%;
        }

        /* ===== LEMBARAN KERTAS FISIK F4 READ-ONLY ===== */
        .paper-f4-readonly {
            width: 21.5cm; /* Standar lebar F4 */
            min-height: 33cm; /* Standar tinggi F4 */
            padding: 2cm 2cm 2cm 2cm; /* Margin 2 cm seragam: Atas 2cm, Bawah 2cm, Kanan 2cm, Kiri 2cm */
            background-color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); /* Bayangan kertas fisik */
            position: relative;
            box-sizing: border-box;
            overflow-wrap: break-word;
            margin: 0 auto;
        }

        /* AREA KETIK READ-ONLY (contenteditable="false") - Bookman Old Style 12pt Tanpa Bold */
        .page-content-readonly, .page-content-readonly * {
            font-family: 'Bookman Old Style', 'Bookman', Georgia, serif !important;
            font-size: 12pt !important;
            font-weight: normal !important;
            line-height: 1.6;
            color: #000000;
        }

        .page-content-readonly p {
            text-indent: 1cm;
            margin-bottom: 0.75rem;
            line-height: 1.6;
            text-align: justify;
        }

        .page-content-readonly h1, .page-content-readonly h2, .page-content-readonly h3,
        .page-content-readonly b, .page-content-readonly strong, .page-content-readonly th {
            font-weight: normal !important;
            color: #000000;
        }

        .page-content-readonly table {
            border-collapse: collapse;
            width: 100%;
            margin: 1rem 0;
        }

        .page-content-readonly td, .page-content-readonly th {
            border: 1px solid #000000;
            padding: 6px 10px;
            font-size: 10pt !important;
        }

        /* PENANDA HALAMAN */
        .page-number-indicator {
            position: absolute;
            bottom: 0.8cm;
            right: 1cm;
            font-size: 9pt;
            color: #6b7280;
            font-family: 'Inter', sans-serif;
            user-select: none;
            pointer-events: none;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between">

    <!-- ========================================== -->
    <!-- 1. FLOATING ACTION BAR (TOPBAR STICKY)    -->
    <!-- ========================================== -->
    <header class="bg-slate-900 text-white sticky top-0 z-50 shadow-md h-16 border-b border-slate-800">
        <div class="max-w-[1600px] mx-auto px-6 h-full flex items-center justify-between">
            
            <!-- KIRI: TOMBOL "KEMBALI KE DASHBOARD" & INFO OPD -->
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.dashboard') }}" 
                   class="h-10 px-4 bg-slate-800 hover:bg-slate-700 text-slate-200 font-extrabold rounded-xl text-xs transition flex items-center space-x-2 border border-slate-700 shadow-xs"
                   title="Kembali ke Dashboard Verifikasi">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Kembali ke Dashboard</span>
                </a>

                <div class="h-6 w-px bg-slate-800"></div>

                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-extrabold text-sm text-white">Review Dokumen Renja</span>
                        <span class="bg-amber-500/20 text-amber-400 border border-amber-500/30 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                            READ-ONLY MODE
                        </span>
                    </div>
                    <div class="text-xs text-slate-400 font-medium">
                        OPD: <strong class="text-amber-400 uppercase">{{ $document->opd->nama_opd ?? 'OPD' }}</strong> (Tahun {{ $document->tahun_anggaran }})
                    </div>
                </div>
            </div>

            <!-- KANAN: TOMBOL KEPUTUSAN & PREVIEW -->
            <div class="flex items-center space-x-2.5">
                <a href="{{ route('renja.preview', $document->id) }}" target="_blank" 
                   class="h-10 px-3.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-extrabold text-xs rounded-xl shadow-xs transition flex items-center space-x-2"
                   title="Buka Dokumen Utuh dalam High-Fidelity PDF Viewer">
                    <i class="fa-solid fa-file-pdf text-amber-400"></i>
                    <span class="hidden sm:inline">Pratinjau PDF</span>
                </a>
                
                <!-- TOMBOL MERAH: TOLAK & MINTA REVISI -->
                <button type="button" 
                        onclick="document.getElementById('modal-catatan-revisi').classList.remove('hidden')"
                        class="h-10 px-4 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-md hover:shadow-lg transition flex items-center space-x-2">
                    <i class="fa-solid fa-file-circle-xmark text-sm"></i>
                    <span>Tolak & Minta Revisi</span>
                </button>

                <!-- TOMBOL HIJAU: VERIFIKASI & SETUJUI -->
                <form action="{{ route('admin.decision', $document->id) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="decision" value="disetujui">
                    <button type="submit" 
                            onclick="return confirm('Apakah Anda YAKIN ingin MENSETUJUI dokumen Renja ini?')"
                            class="h-10 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs rounded-xl shadow-md hover:shadow-lg transition flex items-center space-x-2">
                        <i class="fa-solid fa-circle-check text-sm"></i>
                        <span>Verifikasi & Setujui</span>
                    </button>
                </form>

            </div>

        </div>
    </header>

    <!-- STATUS BANNER JIKA ADA CATATAN SEBELUMNYA -->
    @if($document->catatan_bapperida)
        <div class="bg-amber-500 text-slate-950 px-8 py-3 font-semibold text-xs border-b border-amber-600 flex items-center justify-between">
            <div class="flex items-center space-x-2 max-w-5xl">
                <i class="fa-solid fa-triangle-exclamation text-base shrink-0"></i>
                <span><strong>Catatan Revisi Sebelumnya:</strong> "{{ $document->catatan_bapperida }}"</span>
            </div>
            <span class="text-[10px] uppercase font-bold bg-slate-950 text-white px-2.5 py-1 rounded-md">Status: {{ $document->status }}</span>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- 2. DOKUMEN VIEW (KERTAS F4 READ-ONLY)     -->
    <!-- ========================================== -->
    <main class="review-workspace">
        
        @forelse($groupedBabs as $bCode => $bSections)
            @php
                $firstSec = $bSections->first();
                $bTitle = $firstSec->bab_title ?? 'BAB DOKUMEN';
            @endphp

            <!-- LEMBARAN KERTAS FISIK F4 UNTUK SETIAP BAB -->
            <div class="paper-f4-readonly">
                
                <!-- CONTENT READ-ONLY (contenteditable="false") -->
                <div class="page-content-readonly" contenteditable="false">
                    
                    @if($loop->first)
                        <!-- HEADER KERTAS FORMAT LAMPIRAN PERBUP & KEPBUP -->
                        <div style="text-align: right; font-size: 10pt; font-family: 'Bookman Old Style', serif; margin-bottom: 24pt; font-weight: normal !important; text-transform: uppercase;">
                            @if(str_contains(strtolower($document->jenis_dokumen ?? ''), 'perubahan'))
                                <div>{{ strtoupper($document->opd->nomor_lampiran_romawi ?? 'LAMPIRAN I') }}</div>
                                <div>KEPUTUSAN BUPATI CIREBON</div>
                                <div>NOMOR TAHUN {{ $document->tahun_anggaran ?? '2025' }}</div>
                                <div>TENTANG</div>
                                <div>PERUBAHAN RENCANA KERJA {{ strtoupper($document->opd->nama_opd ?? 'PERANGKAT DAERAH') }} TAHUN {{ $document->tahun_anggaran ?? '2025' }}</div>
                            @elseif(str_contains(strtolower($document->jenis_dokumen ?? ''), 'renstra'))
                                <div>{{ strtoupper($document->opd->nomor_lampiran_romawi ?? 'LAMPIRAN I') }}</div>
                                <div>PERATURAN BUPATI CIREBON</div>
                                <div>NOMOR TAHUN 2025-2029</div>
                                <div>TENTANG</div>
                                <div>RENCANA STRATEGIS {{ strtoupper($document->opd->nama_opd ?? 'PERANGKAT DAERAH') }} TAHUN 2025-2029</div>
                            @else
                                <div>{{ strtoupper($document->opd->nomor_lampiran_romawi ?? 'LAMPIRAN I') }}</div>
                                <div>PERATURAN BUPATI CIREBON</div>
                                <div>NOMOR TAHUN {{ $document->tahun_anggaran ?? '2026' }}</div>
                                <div>TENTANG</div>
                                <div>RENCANA KERJA {{ strtoupper($document->opd->nama_opd ?? 'PERANGKAT DAERAH') }} TAHUN {{ $document->tahun_anggaran ?? '2026' }}</div>
                            @endif
                        </div>
                    @endif

                    <div style="text-align: center; text-transform: uppercase; font-weight: bold; margin-bottom: 20px;">
                        <div>{{ $bCode }}</div>
                        <div>{{ $bTitle }}</div>
                    </div>

                    @foreach($bSections as $sec)
                        @php
                            $cleanTitle = preg_replace('/^\d+(\.\d+)*\s*/', '', $sec->sub_bab_title);
                        @endphp
                        <div style="margin-bottom: 20px;">
                            <div style="font-weight: bold; margin-bottom: 6px;">
                                {{ $sec->sub_bab_code }}. {{ strtoupper($cleanTitle) }}
                            </div>
                            <div style="line-height: 1.6;">
                                {!! $sec->content !!}
                            </div>
                        </div>
                    @endforeach

                </div>

                <!-- PENANDA HALAMAN -->
                <div class="page-number-indicator">Halaman {{ $loop->iteration }} dari {{ $groupedBabs->count() }} F4 (Review Read-Only)</div>

            </div>

        @empty
            <div class="paper-f4-readonly flex flex-col items-center justify-center text-gray-400 text-center p-12">
                <i class="fa-solid fa-folder-open text-4xl mb-3"></i>
                <p class="font-bold text-xs">Belum ada konten narasi pada dokumen ini.</p>
            </div>
        @endforelse

    </main>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-gray-200 py-3 text-center text-xs text-gray-500">
        &copy; {{ date('Y') }} Bapperida Kabupaten Cirebon — Reviewer Mode (Read-Only Standard F4 21.5cm x 33cm)
    </footer>

    <!-- ========================================== -->
    <!-- 3. MODAL CATATAN REVISI ELEGAN DI TENGAH  -->
    <!-- ========================================== -->
    <div id="modal-catatan-revisi" class="hidden fixed inset-0 bg-slate-900/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-5 border border-gray-100 animate-in fade-in zoom-in duration-150">
            
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="font-extrabold text-rose-700 text-sm flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-base"></i>
                    <span>Tolak & Minta Revisi Dokumen</span>
                </h3>
                <button type="button" 
                        onclick="document.getElementById('modal-catatan-revisi').classList.add('hidden')" 
                        class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <form action="{{ route('admin.decision', $document->id) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="decision" value="revisi">

                <div>
                    <label for="catatan_bapperida" class="block text-xs font-extrabold text-gray-800 mb-1.5">
                        Tulis Catatan / Alasan Perbaikan untuk OPD <span class="text-rose-600">*</span>
                    </label>
                    <textarea id="catatan_bapperida" 
                              name="catatan_bapperida" 
                              rows="4" 
                              required 
                              placeholder="Contoh: Tolong sesuaikan narasi Bab I.2 Landasan Hukum dengan Permendagri No. 90 Tahun 2019 dan perbaiki tabel target indikator."
                              class="w-full border border-gray-300 rounded-xl p-3 text-xs font-medium text-gray-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 focus:outline-none leading-relaxed"></textarea>
                    <p class="text-[10px] text-gray-500 mt-1">Catatan ini akan langsung tampil di dashboard OPD terkait saat menyusun dokumen.</p>
                </div>

                <div class="flex justify-end space-x-3 pt-3 border-t">
                    <button type="button" 
                            onclick="document.getElementById('modal-catatan-revisi').classList.add('hidden')" 
                            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold rounded-xl shadow-md transition flex items-center space-x-1.5">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                        <span>Kirim Catatan Revisi</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

</body>
</html>
