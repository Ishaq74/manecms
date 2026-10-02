{{-- Decorative unless `label` is given, in which case the icon is announced as an image. --}}
@props([
    'name',
    'label' => null,
])

<x-ts-icon
    {{ $attributes->merge($label === null ? ['aria-hidden' => 'true'] : ['role' => 'img', 'aria-label' => $label]) }}
    :name="$name"
/>
