@props(['title', 'description' => '', 'href' => null, 'icon' => 'fa-file-lines', 'tone' => 'blue'])
<article {{ $attributes->class(['ed-panel', 'ed-document', 'ed-tone-'.$tone]) }}>
    <div class="ed-document-heading"><span class="ed-icon"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></span>{{ $badge ?? '' }}</div>
    <h3>@if($href)<a href="{{ $href }}">{{ $title }}</a>@else{{ $title }}@endif</h3>
    @if($description)<p>{{ $description }}</p>@endif
    {{ $slot }}
    @isset($actions)<div class="ed-document-actions">{{ $actions }}</div>@endisset
</article>
