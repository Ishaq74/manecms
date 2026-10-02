@props([
    'text',
    'href',
    'icon' => null,
    'current' => false,
])

<x-ts-side-bar.item {{ $attributes }} :text="$text" :href="$href" :icon="$icon" :current="$current" />
