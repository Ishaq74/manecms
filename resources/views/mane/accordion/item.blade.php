@props([
    'title',
    'open' => false,
    'id' => null,
])

<x-ts-accordion.items {{ $attributes }} :title="$title" :open="$open" :id="$id">{{ $slot }}</x-ts-accordion.items>
