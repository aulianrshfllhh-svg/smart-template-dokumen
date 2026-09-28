@props(['timeline'])
<x-ui.chart-container title="Timeline Pengajuan" description="Riwayat pengajuan dan status dokumen perangkat daerah.">
    <div class="ed-timeline">
    @forelse($timeline as $entry)
        <div class="ed-timeline-entry">
            <span class="ed-icon"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>
            <div><h3>{{ $entry['document']->jenis_dokumen }} · TA {{ $entry['document']->tahun_anggaran }}</h3>
            @foreach($entry['events'] as $event)<p>{{ $event['label'] }} <time>{{ $event['date']->format('d M Y, H:i') }}</time></p>@endforeach</div>
        </div>
    @empty
        <p class="ed-empty">Belum ada riwayat pengajuan pada tahun anggaran ini.</p>
    @endforelse
    </div>
</x-ui.chart-container>
