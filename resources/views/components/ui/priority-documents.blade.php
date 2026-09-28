@props(['documents'])
<x-ui.chart-container title="Prioritas Pemeriksaan" description="Dokumen yang perlu ditindaklanjuti berdasarkan waktu tunggu dan riwayat revisi.">
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($documents as $document)
            <x-ui.document-card :title="$document->opd->nama_opd ?? 'Perangkat Daerah'" :description="$document->jenis_dokumen.' · TA '.$document->tahun_anggaran" :href="route('admin.verifikasi.review', $document->id)" icon="fa-clipboard-check" tone="blue">
                <x-slot:badge><x-ui.status-badge :tone="$document->priority_score >= 75 ? 'red' : 'amber'" :label="'Skor '.$document->priority_score" /></x-slot:badge>
                <div class="text-xs text-slate-500 space-y-2">
                    <p>Menunggu {{ $document->submitted_at ? $document->submitted_at->diffForHumans(null, true) : $document->created_at->diffForHumans(null, true) }}</p>
                    <p>Revisi {{ $document->revision_count ?? 0 }} kali · {{ $document->assignedVerificator->name ?? 'Belum ditugaskan' }}</p>
                </div>
                <x-slot:actions><a href="{{ route('admin.verifikasi.review', $document->id) }}" class="st-btn st-btn-primary st-btn-sm">Review Sekarang <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></x-slot:actions>
            </x-ui.document-card>
        @empty
            <p class="ed-empty md:col-span-2 xl:col-span-3">Tidak ada dokumen prioritas yang menunggu pemeriksaan.</p>
        @endforelse
    </div>
</x-ui.chart-container>
