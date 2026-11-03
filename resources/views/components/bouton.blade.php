@props([
    'variant' => 'primary', // primary | secondary | danger
    'type' => 'button',
    'href' => null,
])

@php
    $classe = match($variant) {
        'secondary' => 'btn btn-secondary',
        'danger' => 'btn btn-danger',
        default => 'btn btn-primary',
    };
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classe]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classe]) }}>
        {{ $slot }}
    </button>
@endif
