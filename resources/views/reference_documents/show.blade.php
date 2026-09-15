@extends('layouts.app')

@section('title', 'Review Schema Acuan')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- FLASH NOTIFICATIONS -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-xl shadow-2xs text-xs font-semibold flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-amber-50 border border-amber-300 text-amber-800 px-4 py-3 rounded-xl shadow-2xs text-xs font-semibold flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 border border-rose-300 text-rose-800 px-4 py-3 rounded-xl shadow-2xs text-xs font-semibold flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- HEADER & ACTIONS BAR -->
    <div class="st-card p-5 sm:p-6 space-y-4">
        <div class="flex justify-between items-start flex-wrap gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 rounded-md text-xs font-extrabold bg-blue-50 text-blue-700 border border-blue-200 uppercase">
                        {{ $schema->jenis_dokumen }}
                    </span>
                    @php $badge = $schema->status_badge; @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold 
                        @if($badge['class'] === 'success') bg-emerald-100 text-emerald-800 border border-emerald-300
                        @elseif($badge['class'] === 'warning') bg-amber-100 text-amber-800 border border-amber-300
                        @elseif($badge['class'] === 'danger') bg-rose-100 text-rose-800 border border-rose-300
                        @else bg-slate-100 text-slate-700 @endif">
                        {{ $badge['label'] }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-file-word text-blue-600"></i>
                    <span>{{ $schema->original_filename }}</span>
                </h1>
                <p class="text-xs text-slate-500">
                    Diupload oleh <strong class="text-slate-700">{{ $schema->creator->nama_lengkap ?? 'Admin' }}</strong> pada {{ $schema->created_at ? $schema->created_at->format('d M Y H:i') : '-' }}
                </p>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="flex items-center space-x-3">
                @if($schema->isDraft())
                    <form action="{{ route('reference-documents.approve', $schema->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" onclick="return confirm('Setujui schema ini untuk digunakan di template editor Renja?')" class="st-btn st-btn-success st-btn-sm">
                            <i class="fa-solid fa-check text-xs"></i>
                            <span>Setujui Schema</span>
                        </button>
                    </form>

                    <button type="button" onclick="document.getElementById('rejectModal').classList.remove('hidden')" class="st-btn st-btn-danger st-btn-sm">
                        <i class="fa-solid fa-xmark text-xs"></i>
                        <span>Tolak</span>
                    </button>
                @endif

                @if($schema->isApproved())
                    <button type="button" onclick="document.getElementById('applyModal').classList.remove('hidden')" class="st-btn st-btn-amber st-btn-sm">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                        <span>Terapkan ke Dokumen Renja</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- SCHEMA STRUCTURE DETAILED VIEW -->
    <div class="st-card p-5 sm:p-6 space-y-6">
        <h3 class="text-base font-extrabold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
            <i class="fa-solid fa-sitemap text-amber-500"></i>
            <span>Struktur Naskah Hasil Extraction Engine ({{ $schema->chapters->count() }} Bab, {{ $schema->subChapters->count() }} Sub-Bab)</span>
        </h3>

        <div class="space-y-6">
            @forelse($schema->chapters as $chap)
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <div class="bg-slate-100 p-3.5 border-b border-slate-200 flex justify-between items-center">
                        <div class="font-extrabold text-xs text-slate-900 uppercase">
                            {{ $chap->bab_code }} {{ $chap->bab_title }}
                        </div>
                        <span class="text-[10px] bg-white border border-slate-300 text-slate-600 px-2 py-0.5 rounded font-bold">
                            {{ $chap->subChapters->count() }} Sub-Bab
                        </span>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @foreach($chap->subChapters as $sub)
                            <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition text-xs">
                                <div>
                                    <span class="font-extrabold text-slate-900 mr-2">{{ $sub->sub_bab_code }}</span>
                                    <span class="font-semibold text-slate-800">{{ $sub->sub_bab_title }}</span>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $sub->tipe_konten === 'tabel' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $sub->tipe_konten }}
                                        </span>
                                        @if($sub->sumber_data)
                                            <span class="text-[10px] text-slate-500 font-mono">Sumber: {{ $sub->sumber_data }}</span>
                                        @endif
                                    </div>
                                </div>

                                <button type="button" onclick="openEditSubModal('{{ $sub->id }}', '{{ e($sub->sub_bab_title) }}', '{{ $sub->tipe_konten }}', '{{ e($sub->sumber_data) }}')" class="st-btn st-btn-secondary st-btn-sm">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    <span>Edit</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-slate-400 text-xs italic">
                    Belum ada Bab yang terdeteksi pada schema ini.
                </div>
            @endforelse
        </div>
    </div>

</div>

<!-- MODAL REJECT -->
<div id="rejectModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
        <h3 class="font-extrabold text-slate-900 text-sm">Tolak Schema Dokumen Acuan</h3>
        <form action="{{ route('reference-documents.reject', $schema->id) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alasan Penolakan</label>
                <textarea name="rejection_notes" rows="3" required placeholder="Jelaskan alasan penolakan schema..." class="st-textarea"></textarea>
            </div>
            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="st-btn st-btn-secondary st-btn-sm">Batal</button>
                <button type="submit" class="st-btn st-btn-danger st-btn-sm">Tolak Schema</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL APPLY -->
<div id="applyModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4">
        <h3 class="font-extrabold text-slate-900 text-sm flex items-center gap-2">
            <i class="fa-solid fa-paper-plane text-indigo-600"></i>
            <span>Terapkan Schema ke Dokumen Renja OPD</span>
        </h3>
        <form action="{{ route('reference-documents.apply', $schema->id) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="renja_document_id" class="block text-xs font-bold text-slate-700 mb-1">Pilih Dokumen Renja Sasaran</label>
                <select name="renja_document_id" id="renja_document_id" required class="st-select">
                    <option value="">-- Pilih Dokumen Renja --</option>
                    @foreach($renjaDocuments as $doc)
                        <option value="{{ $doc->id }}">
                            {{ $doc->judul ?? 'Dokumen Renja #' . $doc->id }} — {{ $doc->opd->nama_opd ?? 'OPD' }} ({{ $doc->tahun ?? date('Y') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('applyModal').classList.add('hidden')" class="st-btn st-btn-secondary st-btn-sm">Batal</button>
                <button type="submit" class="st-btn st-btn-amber st-btn-sm">Terapkan ke Editor</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openEditSubModal(id, title, tipe, sumber) {
        // Simple edit handler
    }
</script>
@endpush
@endsection
