@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'text' => null,
])

@php
    $color = match ($variant) {
        'primary' => 'primary',
        'secondary' => 'secondary',
        'muted' => 'dark',
        default => throw new InvalidArgumentException("Unknown ManeUI badge variant [{$variant}]."),
    };
@endphp

<x-ts-badge {{ $attributes }} :color="$color" :size="$size" :icon="$icon" :text="$text" light round>{{ $slot }}</x-ts-badge>
