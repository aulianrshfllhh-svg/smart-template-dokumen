@props(['title', 'description' => '', 'eyebrow' => 'Ruang kerja digital'])
<div {{ $attributes->class(['ed-page-heading']) }}>
    <div><span class="ed-eyebrow">{{ $eyebrow }}</span><h1>{{ $title }}</h1>@if($description)<p>{{ $description }}</p>@endif</div>
    @isset($actions)<div class="ed-heading-actions">{{ $actions }}</div>@endisset
</div>
