@extends('layouts.app')

@section('title', 'Detail Perangkat Daerah — ' . $opd->nama_opd)

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- HEADER / BREADCRUMB BANNER -->
    <div class="st-card-v2 p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-l-4 border-l-amber-500">
        <div>
            <div class="flex items-center space-x-2 text-[11px] font-bold uppercase text-slate-400 mb-1">
                <a href="{{ route('admin.master_opd.index') }}" class="hover:text-amber-600 transition">Perangkat Daerah</a>
                <span>/</span>
                <span class="text-slate-700 font-extrabold">Detail OPD</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-landmark text-amber-500 text-lg"></i>
                <span>{{ $opd->nama_opd }}</span>
            </h1>
            <p class="text-xs text-slate-500 font-medium mt-1">Kode OPD: <strong class="font-mono text-slate-800">{{ $opd->kode_opd }}</strong> • Nomor Lampiran: <strong class="text-amber-600">{{ $opd->nomor_lampiran_romawi ?? 'LAMPIRAN I' }}</strong></p>
        </div>

        <div class="flex items-center space-x-2 shrink-0">
            <a href="{{ route('admin.monitoring-opd.show', $opd->id) }}" class="st-btn bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl shadow-xs transition flex items-center gap-1.5">
                <i class="fa-solid fa-chart-line text-xs"></i>
                <span>Monitoring Progres Dokumen</span>
            </a>

            <a href="{{ route('admin.master_opd.index') }}" class="st-btn st-btn-secondary st-btn-sm text-xs font-bold rounded-xl">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <!-- PROFIL OPD & OPERATOR INFO CARDS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- CARD 1: IDENTITAS OPD -->
        <div class="st-card-v2 p-5 space-y-3">
            <div class="flex items-center space-x-2.5 text-xs font-black text-slate-800 border-b border-slate-100 pb-2.5 uppercase tracking-wider">
                <i class="fa-solid fa-building-columns text-amber-500"></i>
                <span>Identitas Perangkat Daerah</span>
            </div>
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Nama Lengkap</span>
                    <span class="font-bold text-slate-900">{{ $opd->nama_opd }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 pt-1">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Kode OPD</span>
                        <span class="font-mono font-bold text-slate-800">{{ $opd->kode_opd }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Nomor Lampiran</span>
                        <span class="font-bold text-amber-600">{{ $opd->nomor_lampiran_romawi ?? 'LAMPIRAN I' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 2: OPERATOR TERDAFTAR -->
        <div class="st-card-v2 p-5 space-y-3 lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <div class="flex items-center space-x-2.5 text-xs font-black text-slate-800 uppercase tracking-wider">
                    <i class="fa-solid fa-users text-indigo-500"></i>
                    <span>Akun Operator OPD Terdaftar</span>
                </div>
                <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700">
                    {{ $opd->users->count() }} User
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse($opd->users as $user)
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3 flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 font-black text-xs flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="min-w-0 flex-1 text-xs">
                            <div class="font-bold text-slate-900 truncate">{{ $user->name }}</div>
                            <div class="text-[11px] text-slate-500 truncate">{{ $user->email }}</div>
                            <div class="text-[10px] font-bold text-indigo-600 uppercase mt-0.5">Role: {{ strtoupper($user->role) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="sm:col-span-2 py-4 text-center text-slate-400 italic text-xs">
                        Belum ada akun operator yang terhubung dengan Perangkat Daerah ini.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- DOKUMEN PLAN & VERIFICATION HISTORY -->
    <div class="st-card-v2 p-5 sm:p-6 space-y-4">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3.5">
            <div>
                <h3 class="text-base font-black text-slate-900 tracking-tight">Dokumen Perencanaan milik {{ $opd->nama_opd }}</h3>
                <p class="text-xs text-slate-500">Daftar dokumen Renja, status verifikasi, dan riwayat pengajuan</p>
            </div>

            <!-- FILTER TAHUN ANGGARAN -->
            <form action="{{ route('admin.master_opd.show', $opd->id) }}" method="GET" class="flex items-center space-x-2">
                <select name="tahun_anggaran" onchange="this.form.submit()" class="st-select text-xs font-bold h-9 rounded-xl border-slate-300 px-3">
                    <option value="all" {{ (string)$tahunAnggaran === 'all' ? 'selected' : '' }}>Semua Tahun Anggaran</option>
                    @foreach($availableYears as $year)
                        <option value="{{ $year }}" {{ (string)$tahunAnggaran === (string)$year ? 'selected' : '' }}>Tahun {{ $year }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="space-y-4">
            @forelse($documents as $doc)
                <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-2xs space-y-3 hover:border-slate-300 transition">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-black bg-slate-900 text-amber-400 font-mono">
                                    TA {{ $doc->tahun_anggaran }}
                                </span>
                                <h4 class="text-sm font-black text-slate-900">{{ $doc->jenis_dokumen }}</h4>
                            </div>
                            <div class="text-xs text-slate-500 font-medium mt-1">
                                Operator: <strong>{{ $doc->updatedByUser->name ?? 'Operator OPD' }}</strong> • 
                                Submit: <strong>{{ $doc->submitted_at ? $doc->submitted_at->format('d M Y, H:i WIB') : 'Belum Submit' }}</strong>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2 self-start sm:self-auto shrink-0">
                            <span class="{{ $doc->status_badge_class }} px-3 py-1 rounded-xl text-xs font-extrabold uppercase shadow-2xs">
                                {{ $doc->status_label }}
                            </span>

                            @if(in_array($doc->status, ['submitted', 'menunggu_pemeriksaan', 'menunggu_verifikasi', 'sedang_diperiksa', 'dikirim_ulang']))
                                <a href="{{ route('admin.verifikasi.review', $doc->id) }}" class="st-btn bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-xs px-3.5 py-1.5 rounded-xl shadow-xs transition">
                                    <span>Verifikasi Sekarang</span>
                                </a>
                            @else
                                <a href="{{ route('renja.preview', $doc->id) }}" target="_blank" class="st-btn st-btn-secondary st-btn-sm text-xs font-bold rounded-xl px-3 py-1.5">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                    <span>Preview PDF</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- AUDIT TRAIL RECAP -->
                    @if(!empty($doc->metadata['audit_trail']) && is_array($doc->metadata['audit_trail']))
                        <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/60 space-y-1.5">
                            <div class="text-[10px] uppercase font-black tracking-wider text-slate-400 flex items-center gap-1.5">
                                <i class="fa-solid fa-clock-rotate-left text-amber-500"></i>
                                <span>Riwayat Pengiriman & Verifikasi (*Audit Trail*)</span>
                            </div>
                            <div class="space-y-1">
                                @foreach(array_slice(array_reverse($doc->metadata['audit_trail']), 0, 3) as $log)
                                    <div class="text-xs text-slate-700 flex items-start space-x-2">
                                        <span class="text-[10px] font-mono text-slate-400 whitespace-nowrap pt-0.5">
                                            {{ isset($log['timestamp']) ? \Carbon\Carbon::parse($log['timestamp'])->format('d/m/Y H:i') : '-' }}
                                        </span>
                                        <span class="font-bold text-slate-800">[{{ $log['action'] ?? 'LOG' }}]</span>
                                        <span class="text-slate-600 flex-1 truncate">{{ $log['notes'] ?? '' }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold">({{ $log['user_name'] ?? 'System' }})</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>
            @empty
                <div class="py-8 text-center text-slate-400 italic text-xs bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    Tidak ditemukan dokumen perencanaan untuk Perangkat Daerah ini.
                </div>
            @endforelse
        </div>

    </div>

</div>
@endsection
