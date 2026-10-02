{{-- TallStackUI renders the reveal toggle as an icon-only button; ManeUI gives it a name. --}}
@props([
    'label' => null,
    'hint' => null,
    'rules' => false,
])

@php
    // Without an id, a name or wire:model, TallStackUI renders the label without `for`.
    if (! $attributes->has('id') && ! $attributes->has('name') && ! $attributes->wire('model')->value()) {
        $attributes = $attributes->merge(['id' => 'mane-field-'.Str::random(8)]);
    }
@endphp

<div
    x-data
    x-init="$el.querySelector('[dusk=tallstackui_form_password_reveal]')?.setAttribute('aria-label', @js(__('Show or hide the password')))"
>
    <x-ts-password {{ $attributes }} :label="$label" :hint="$hint" :rules="$rules" />
</div>
