@props([
    'label' => null,
])

<x-ts-toggle {{ $attributes->merge(['role' => 'switch']) }} :label="$label" />
