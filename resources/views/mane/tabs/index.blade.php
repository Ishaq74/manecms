@props([
    'selected' => null,
])

<x-ts-tab {{ $attributes }} :selected="$selected">{{ $slot }}</x-ts-tab>
