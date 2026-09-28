@props(['title', 'description' => ''])
<section {{ $attributes->class(['ed-panel', 'ed-chart']) }}>
    <div class="ed-section-heading"><div><h2>{{ $title }}</h2>@if($description)<p>{{ $description }}</p>@endif</div>{{ $action ?? '' }}</div>
    {{ $slot }}
</section>
