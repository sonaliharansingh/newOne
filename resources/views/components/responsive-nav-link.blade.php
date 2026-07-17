@props(['active'])

@php
$classes = ($active ?? false) ? 'mobile-nav-link is-active' : 'mobile-nav-link';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
