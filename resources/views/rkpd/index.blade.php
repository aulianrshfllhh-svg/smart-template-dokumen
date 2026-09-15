@extends('layouts.app')

@section('title', 'Dokumen RKPD — Rencana Kerja Pemerintah Daerah')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- ========================================== -->
    <!-- 1. HEADER BANNER                           -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-l-4 border-l-emerald-500 bg-white">
        <div>
            <div class="flex items-center space-x-2 text-[11px] font-black uppercase text-slate-400 mb-1">
                <span>e-Dokumen Perencanaan</span>
                <span>/</span>
                <span class="text-emerald-700">Dokumen Daerah</span>
                <span>/</span>
                <span class="text-slate-900">RKPD</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-lg font-black shadow-xs shrink-0">
                    <i class="fa-solid fa-landmark"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        Dokumen RKPD
                    </h1>
                    <p class="text-xs text-slate-500 font-medium">
                        Rencana Kerja Pemerintah Daerah Kabupaten Cirebon
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap text-xs">
            <span class="inline-flex items-center gap-1 bg-slate-900 text-emerald-400 px-3 py-1 rounded-full text-xs font-black tracking-wide shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Siklus Aktif: TA {{ $activeTa }} & {{ $murniTa }}
            </span>
            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-900 border border-emerald-300">
                Tingkat Kabupaten
            </span>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. VARIAN DOKUMEN RKPD (2 CARDS)           -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        
        <!-- CARD 1: RKPD MURNI -->
        <div class="st-card-v2 p-5 border-2 border-emerald-500/80 bg-linear-to-br from-emerald-50/40 via-white to-white rounded-3xl shadow-xs space-y-4 relative overflow-hidden flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-1 bg-emerald-100 border border-emerald-300 text-emerald-900 text-[10px] font-black rounded-lg uppercase">
                        Tahun Dokumen: {{ $murniTa }}
                    </span>
                    <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-extrabold rounded-md flex items-center gap-1">
                        <i class="fa-solid fa-layer-group text-[9px]"></i>
                        <span>RKPD Induk</span>
                    </span>
                </div>

                <div>
                    <h3 class="text-lg font-black text-slate-900">RKPD Murni TA {{ $murniTa }}</h3>
                    <p class="text-xs text-slate-600 font-medium mt-1 leading-relaxed">
                        Dokumen Rencana Kerja Pemerintah Daerah awal Kabupaten Cirebon untuk tahun anggaran {{ $murniTa }} yang memuat prioritas pembangunan daerah, sasaran makro, dan pagu indikatif.
                    </p>
                </div>

                <!-- Metrics Agregasi Murni -->
                <div class="bg-white/90 border border-emerald-200/80 rounded-2xl p-3.5 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700">Progres Pengumpulan RENJA OPD:</span>
                        <span class="font-black text-emerald-800">{{ $murniSubmittedCount }} / {{ $totalOpd }} OPD</span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" 
                             style="width: {{ $totalOpd > 0 ? round(($murniSubmittedCount / $totalOpd) * 100) : 0 }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-slate-500 font-medium">
                        <span>{{ $murniApprovedCount }} OPD telah disetujui (Approved)</span>
                        <span class="font-bold text-emerald-700">{{ $totalOpd > 0 ? round(($murniSubmittedCount / $totalOpd) * 100) : 0 }}% Terkumpul</span>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-emerald-100 flex items-center justify-between text-xs">
                <span class="text-slate-500 text-[11px] font-medium">Dasar: RENJA Murni Seluruh OPD</span>
                @if(Auth::user()->isAdmin() || Auth::user()->isVerifikator() || Auth::user()->role === 'staff_bapperida')
                    <a href="{{ route('admin.monitoring.index') }}" class="st-btn st-btn-emerald st-btn-sm font-black shadow-xs">
                        <span>Monitoring Bapperida</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                @else
                    <a href="{{ route('renja.workspace') }}" class="st-btn st-btn-amber st-btn-sm font-black shadow-xs">
                        <span>Cek RENJA OPD Saya</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                @endif
            </div>
        </div>

        <!-- CARD 2: RKPD PERUBAHAN -->
        <div class="st-card-v2 p-5 border-2 border-indigo-500/80 bg-linear-to-br from-indigo-50/40 via-white to-white rounded-3xl shadow-xs space-y-4 relative overflow-hidden flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-1 bg-indigo-100 border border-indigo-300 text-indigo-900 text-[10px] font-black rounded-lg uppercase">
                        Tahun Dokumen: {{ $perubahanTa }}
                    </span>
                    <span class="px-2.5 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-extrabold rounded-md flex items-center gap-1">
                        <i class="fa-solid fa-clock-rotate-left text-[9px]"></i>
                        <span>Perubahan</span>
                    </span>
                </div>

                <div>
                    <h3 class="text-lg font-black text-slate-900">RKPD Perubahan TA {{ $perubahanTa }}</h3>
                    <p class="text-xs text-slate-600 font-medium mt-1 leading-relaxed">
                        Dokumen Perubahan Rencana Kerja Pemerintah Daerah Kabupaten Cirebon tahun berjalan untuk penyesuaian target kinerja, capaian triwulanan, dan pergeseran anggaran.
                    </p>
                </div>

                <!-- Metrics Agregasi Perubahan -->
                <div class="bg-white/90 border border-indigo-200/80 rounded-2xl p-3.5 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700">Progres Pengumpulan RENJA Perubahan:</span>
                        <span class="font-black text-indigo-800">{{ $perubahanSubmittedCount }} / {{ $totalOpd }} OPD</span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div class="bg-indigo-500 h-full rounded-full transition-all duration-500" 
                             style="width: {{ $totalOpd > 0 ? round(($perubahanSubmittedCount / $totalOpd) * 100) : 0 }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-slate-500 font-medium">
                        <span>Penyesuaian target kinerja daerah</span>
                        <span class="font-bold text-indigo-700">{{ $totalOpd > 0 ? round(($perubahanSubmittedCount / $totalOpd) * 100) : 0 }}% Terkumpul</span>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-indigo-100 flex items-center justify-between text-xs">
                <span class="text-slate-500 text-[11px] font-medium">Dasar: RENJA Perubahan Seluruh OPD</span>
                @if(Auth::user()->isAdmin() || Auth::user()->isVerifikator() || Auth::user()->role === 'staff_bapperida')
                    <a href="{{ route('admin.monitoring.index') }}" class="st-btn st-btn-primary st-btn-sm font-black shadow-xs">
                        <span>Monitoring Bapperida</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                @else
                    <a href="{{ route('renja.workspace') }}" class="st-btn st-btn-amber st-btn-sm font-black shadow-xs">
                        <span>Cek RENJA Perubahan Saya</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                @endif
            </div>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- 3. STATUS DOKUMEN RENJA OPD UNTUK RKPD     -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 bg-white space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-500/15 text-amber-600 flex items-center justify-center text-sm font-black">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div>
                    <h4 class="text-sm font-black text-slate-900">Kontribusi Dokumen OPD Anda ke RKPD</h4>
                    <p class="text-xs text-slate-500 font-medium">{{ $userOpd->nama_opd ?? 'Perangkat Daerah Kabupaten Cirebon' }}</p>
                </div>
            </div>
            <a href="{{ route('renja.workspace') }}" class="text-xs text-amber-700 hover:text-amber-900 font-black flex items-center gap-1">
                <span>Buka Workspace RENJA</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
            <!-- ITEM 1: RENJA MURNI OPD -->
            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 flex items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="text-[10px] font-black uppercase tracking-wider text-slate-400">RENJA Murni TA {{ $murniTa }}</div>
                    <div class="text-xs font-bold text-slate-800">
                        @if($opdRenjaMurni)
                            Status: <span class="capitalize font-black {{ $opdRenjaMurni->status === 'approved' ? 'text-emerald-700' : ($opdRenjaMurni->status === 'submitted' ? 'text-blue-700' : 'text-amber-700') }}">{{ $opdRenjaMurni->status }}</span>
                        @else
                            <span class="text-slate-400 italic">Belum dibuat oleh OPD</span>
                        @endif
                    </div>
                </div>
                @if($opdRenjaMurni)
                    <a href="{{ url('/renja-documents/' . $opdRenjaMurni->id . '/editor') }}" class="st-btn st-btn-secondary st-btn-sm font-bold text-xs shrink-0">
                        <span>Editor</span>
                    </a>
                @else
                    <button type="button" onclick="openModalBuatDokumen('RENJA_MURNI')" class="st-btn st-btn-amber st-btn-sm font-black text-xs shrink-0">
                        <span>+ Buat Murni</span>
                    </button>
                @endif
            </div>

            <!-- ITEM 2: RENJA PERUBAHAN OPD -->
            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 flex items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="text-[10px] font-black uppercase tracking-wider text-slate-400">RENJA Perubahan TA {{ $perubahanTa }}</div>
                    <div class="text-xs font-bold text-slate-800">
                        @if($opdRenjaPerubahan)
                            Status: <span class="capitalize font-black {{ $opdRenjaPerubahan->status === 'approved' ? 'text-emerald-700' : ($opdRenjaPerubahan->status === 'submitted' ? 'text-blue-700' : 'text-amber-700') }}">{{ $opdRenjaPerubahan->status }}</span>
                        @else
                            <span class="text-slate-400 italic">Belum dibuat oleh OPD</span>
                        @endif
                    </div>
                </div>
                @if($opdRenjaPerubahan)
                    <a href="{{ url('/renja-documents/' . $opdRenjaPerubahan->id . '/editor') }}" class="st-btn st-btn-secondary st-btn-sm font-bold text-xs shrink-0">
                        <span>Editor</span>
                    </a>
                @else
                    <button type="button" onclick="openModalBuatDokumen('RENJA_PERUBAHAN')" class="st-btn st-btn-purple st-btn-sm font-black text-xs shrink-0 text-white bg-purple-600 hover:bg-purple-700">
                        <span>+ Buat Perubahan</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 4. ALUR ARSITEKTUR RKPD                    -->
    <!-- ========================================== -->
    <div class="st-card-v2 p-5 sm:p-6 bg-slate-900 text-white rounded-3xl space-y-4">
        <div class="space-y-1">
            <div class="text-[10px] font-black uppercase tracking-wider text-emerald-400">Arsitektur Perencanaan Terintegrasi</div>
            <h4 class="text-sm sm:text-base font-black">Bagaimana RENJA Terhubung ke RKPD</h4>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div class="p-3.5 bg-white/5 border border-white/10 rounded-2xl space-y-1.5">
                <div class="w-6 h-6 rounded-lg bg-amber-500 text-slate-950 font-black flex items-center justify-center text-xs">1</div>
                <div class="font-bold text-slate-200">Penyusunan RENJA OPD</div>
                <p class="text-[11px] text-slate-400 leading-relaxed">Setiap Perangkat Daerah menyusun naskah RENJA Murni & Perubahan berbasis template standar.</p>
            </div>

            <div class="p-3.5 bg-white/5 border border-white/10 rounded-2xl space-y-1.5">
                <div class="w-6 h-6 rounded-lg bg-blue-500 text-white font-black flex items-center justify-center text-xs">2</div>
                <div class="font-bold text-slate-200">Verifikasi Bapperida</div>
                <p class="text-[11px] text-slate-400 leading-relaxed">Tim verifikator Bapperida menelaah target kinerja, program prioritas, dan pagu anggaran OPD.</p>
            </div>

            <div class="p-3.5 bg-white/5 border border-white/10 rounded-2xl space-y-1.5">
                <div class="w-6 h-6 rounded-lg bg-emerald-500 text-slate-950 font-black flex items-center justify-center text-xs">3</div>
                <div class="font-bold text-slate-200">Agregasi Otomatis RKPD</div>
                <p class="text-[11px] text-slate-400 leading-relaxed">Data program & kegiatan yang disetujui langsung terintegrasi ke dalam kompilasi dokumen RKPD.</p>
            </div>

            <div class="p-3.5 bg-white/5 border border-white/10 rounded-2xl space-y-1.5">
                <div class="w-6 h-6 rounded-lg bg-purple-500 text-white font-black flex items-center justify-center text-xs">4</div>
                <div class="font-bold text-slate-200">Penetapan Perbup RKPD</div>
                <p class="text-[11px] text-slate-400 leading-relaxed">Dokumen final RKPD Kabupaten Cirebon siap ditetapkan sebagai dasar penyusunan KUA-PPAS.</p>
            </div>
        </div>
    </div>

</div>
@endsection
