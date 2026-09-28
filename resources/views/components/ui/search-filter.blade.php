@props(['action' => null, 'method' => 'GET'])
<form action="{{ $action ?? url()->current() }}" method="{{ $method }}" {{ $attributes->class(['ed-search-filter']) }} role="search">
    {{ $slot }}
</form>
