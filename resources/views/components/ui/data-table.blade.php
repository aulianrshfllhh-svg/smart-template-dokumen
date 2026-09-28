@props(['label' => 'Daftar data'])
<div class="ed-table-scroll" role="region" aria-label="{{ $label }}" tabindex="0">
    <table {{ $attributes->class(['ed-table']) }}>
        @isset($head)<thead>{{ $head }}</thead>@endisset
        {{ $slot }}
    </table>
</div>
