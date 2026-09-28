@props(['label', 'value', 'icon' => 'fa-file-lines', 'tone' => 'blue', 'note' => '', 'href' => null, 'unit' => ''])
@if($href)<a href="{{ $href }}" {{ $attributes->class(['ed-stat', 'ed-tone-'.$tone]) }}>@else<article {{ $attributes->class(['ed-stat', 'ed-tone-'.$tone]) }}>@endif
    <div class="ed-stat-top"><span class="ed-icon"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></span><span class="ed-stat-context">Tahun aktif</span></div>
    <p class="ed-stat-label">{{ $label }}</p>
    <div class="ed-stat-value">{{ $value }} <span>{{ $unit }}</span></div>
    <div class="ed-stat-footer"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span>{{ $note }}</span>@if($href)<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>@endif</div>
@if($href)</a>@else</article>@endif
