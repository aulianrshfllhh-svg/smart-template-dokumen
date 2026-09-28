@props(['href', 'active' => false, 'icon' => 'fa-file-lines'])
<a href="{{ $href }}" @if($active) aria-current="page" @endif {{ $attributes->class(['ed-nav-link', 'is-active' => $active]) }}><i class="fa-solid {{ $icon }}" aria-hidden="true"></i><span>{{ $slot }}</span></a>
