@extends('layouts.app')

@section('title', 'Perangkat Daerah')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    <!-- FLASH NOTIFICATION -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-2xl text-xs font-bold shadow-xs flex items-center space-x-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- HEADER BANNER CLEAN & MODERN -->
    <div class="st-card-v2 p-4 sm:p-5 bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 text-white rounded-2xl border border-slate-800 shadow-md">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="space-y-1 min-w-0">
                <div class="inline-flex items-center space-x-2 bg-amber-500/20 text-amber-300 border border-amber-400/30 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider">
                    <i class="fa-solid fa-landmark text-[10px]"></i>
                    <span>MASTER DATA PERANGKAT DAERAH</span>
                </div>
                <h1 class="text-base sm:text-lg font-black text-white tracking-tight truncate">Data Perangkat Daerah Kabupaten Cirebon</h1>
                <p class="text-xs text-slate-400 font-medium truncate">Daftar {{ $totalOpdCount }} Organisasi Perangkat Daerah & Kecamatan beserta akun operator dan ketersediaan dokumen.</p>
            </div>

            <div class="flex items-center space-x-3 shrink-0">
                <div class="bg-slate-800/80 border border-slate-700/70 px-3.5 py-1.5 rounded-xl text-center">
                    <div class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Total OPD</div>
                    <div class="text-sm font-black text-amber-400">{{ $totalOpdCount }}</div>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="st-btn bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold px-3 py-1.5 rounded-xl border border-slate-700/80 transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="st-card-v2 p-3 sm:p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
        <form action="{{ route('admin.master_opd.index') }}" method="GET" class="flex flex-col sm:flex-row items-center justify-between gap-2.5">
            <!-- SEARCH INPUT -->
            <div class="w-full sm:w-72 relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama atau kode OPD..." class="st-input pl-9 pr-4 text-xs h-9 rounded-xl w-full border-slate-200 focus:border-amber-500 focus:ring-amber-500">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
            </div>

            <!-- FILTER CONTROLS -->
            <div class="flex items-center space-x-2 w-full sm:w-auto justify-end">
                <select name="tahun_anggaran" onchange="this.form.submit()" class="st-select text-xs font-extrabold h-9 rounded-xl border-slate-200 px-3 bg-slate-50 focus:bg-white">
                    <option value="2027" {{ (string)$tahunAnggaran === '2027' ? 'selected' : '' }}>TA 2027 (Aktif)</option>
                    <option value="2026" {{ (string)$tahunAnggaran === '2026' ? 'selected' : '' }}>TA 2026</option>
                    <option value="all" {{ (string)$tahunAnggaran === 'all' ? 'selected' : '' }}>Semua Tahun</option>
                </select>

                <button type="submit" class="st-btn bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs h-9 px-4 rounded-xl transition shadow-xs flex items-center space-x-1.5">
                    <i class="fa-solid fa-filter text-xs"></i>
                    <span>Filter</span>
                </button>

                @if($search || ($tahunAnggaran && $tahunAnggaran !== '2027'))
                    <a href="{{ route('admin.master_opd.index') }}" class="st-btn bg-slate-100 hover:bg-slate-200 text-slate-700 h-9 w-9 p-0 flex items-center justify-center rounded-xl transition" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- TABLE MASTER OPD (PRECISE INLINE PERCENTAGES FOR PERFECT 100% FIT) -->
    <div class="st-card-v2 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="w-full overflow-hidden">
            <table class="w-full text-left text-xs table-fixed">
                <thead class="bg-slate-100/80 border-b border-slate-200 text-slate-600 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th style="width: 4%" class="py-2.5 px-1.5 text-center">No</th>
                        <th style="width: 14%" class="py-2.5 px-2">Kode OPD</th>
                        <th style="width: 32%" class="py-2.5 px-2.5">Nama Perangkat Daerah</th>
                        <th style="width: 12%" class="py-2.5 px-2 text-center">Lampiran</th>
                        <th style="width: 22%" class="py-2.5 px-2.5">Operator</th>
                        <th style="width: 8%" class="py-2.5 px-2 text-center">Dokumen</th>
                        <th style="width: 8%" class="py-2.5 px-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($opds as $index => $opd)
                        <tr class="hover:bg-amber-50/30 transition">
                            <td class="py-2.5 px-1.5 text-center text-slate-400 font-bold">
                                {{ $opds->firstItem() + $index }}
                            </td>
                            <td class="py-2.5 px-2 font-mono font-bold text-slate-900 text-[10px] truncate" title="{{ $opd->kode_opd }}">
                                {{ $opd->kode_opd }}
                            </td>
                            <td class="py-2.5 px-2.5 min-w-0">
                                <div class="font-extrabold text-slate-900 text-xs leading-tight line-clamp-2" title="{{ $opd->nama_opd }}">{{ $opd->nama_opd }}</div>
                                @if(!empty($opd->singkatan) && strtolower(trim($opd->singkatan)) !== strtolower(trim($opd->nama_opd)))
                                    <div class="text-[10px] text-slate-400 font-semibold truncate">{{ $opd->singkatan }}</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-2 text-center truncate">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-lg text-[9px] font-black bg-amber-100/70 text-amber-900 border border-amber-300 truncate">
                                    {{ $opd->nomor_lampiran_romawi ?? 'LAMPIRAN I' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-2.5 min-w-0">
                                @if($opd->users && $opd->users->count() > 0)
                                    @php $opUser = $opd->users->first(); @endphp
                                    <div class="flex items-center space-x-1.5 min-w-0">
                                        <div class="w-5.5 h-5.5 rounded-lg bg-slate-900 text-amber-400 font-black flex items-center justify-center text-[8.5px] shrink-0">
                                            {{ strtoupper(substr($opUser->name ?? 'OP', 0, 2)) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-[10px] font-extrabold text-slate-900 truncate" title="{{ $opUser->name }}">{{ $opUser->name }}</div>
                                            <div class="text-[9px] text-slate-400 font-medium truncate" title="{{ $opUser->email }}">{{ $opUser->email }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-[9.5px] inline-flex items-center gap-1">
                                        <i class="fa-solid fa-user-slash text-[9px]"></i>
                                        <span>Belum terdaftar</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-2 text-center">
                                <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[9.5px] font-black {{ $opd->renja_documents_count > 0 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                    <span>{{ $opd->renja_documents_count }} Dok</span>
                                </span>
                            </td>
                            <td class="py-2.5 px-2 text-center">
                                <a href="{{ route('admin.master_opd.show', $opd->id) }}" 
                                   class="st-btn bg-slate-900 hover:bg-amber-500 hover:text-slate-950 text-white font-bold text-[9.5px] px-2 py-1 rounded-lg transition inline-flex items-center justify-center gap-1 w-full" title="Detail Perangkat Daerah">
                                    <i class="fa-solid fa-eye text-[8.5px]"></i>
                                    <span>Detail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400 italic text-xs">
                                <i class="fa-solid fa-folder-open text-xl text-slate-300 block mb-1.5"></i>
                                Tidak ada data Perangkat Daerah yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($opds->hasPages())
            <div class="p-3 border-t border-slate-100 bg-slate-50/60">
                {{ $opds->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
