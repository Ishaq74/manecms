@props([
    'size' => 'md',
])

<x-ts-spinner {{ $attributes->merge(['aria-hidden' => 'true']) }} :size="$size" />
