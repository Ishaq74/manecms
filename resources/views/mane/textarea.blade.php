@props([
    'label' => null,
    'hint' => null,
    'count' => false,
    'resize' => false,
])

@php
    // Without an id, a name or wire:model, TallStackUI renders the label without `for`.
    if (! $attributes->has('id') && ! $attributes->has('name') && ! $attributes->wire('model')->value()) {
        $attributes = $attributes->merge(['id' => 'mane-field-'.Str::random(8)]);
    }
@endphp

<x-ts-textarea {{ $attributes }} :label="$label" :hint="$hint" :count="$count" :resize="$resize" />
