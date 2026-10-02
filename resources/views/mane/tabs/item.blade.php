@props([
    'tab',
    'title' => null,
])

<x-ts-tab.items {{ $attributes }} :tab="$tab" :title="$title ?? $tab">{{ $slot }}</x-ts-tab.items>
