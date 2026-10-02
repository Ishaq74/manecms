@props([
    'label' => null,
    'hint' => null,
    'icon' => null,
    'iconPosition' => 'start',
    'prefix' => null,
    'suffix' => null,
    'clearable' => false,
])

@php
    // Without an id, a name or wire:model, TallStackUI renders the label without `for`.
    if (! $attributes->has('id') && ! $attributes->has('name') && ! $attributes->wire('model')->value()) {
        $attributes = $attributes->merge(['id' => 'mane-field-'.Str::random(8)]);
    }
@endphp

<x-ts-input
    {{ $attributes }}
    :label="$label"
    :hint="$hint"
    :icon="$icon"
    :position="$iconPosition === 'end' ? 'right' : 'left'"
    :prefix="$prefix"
    :suffix="$suffix"
    :clearable="$clearable"
/>
