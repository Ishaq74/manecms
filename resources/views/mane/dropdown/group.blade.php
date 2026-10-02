{{-- A labelled group of menu items inside <x-mane::dropdown>. --}}
@props([
    'label',
])

@php
    $labelId = 'mane-menu-group-'.Str::random(8);
@endphp

<div role="group" aria-labelledby="{{ $labelId }}" {{ $attributes }}>
    <div id="{{ $labelId }}" class="px-2 pb-1 pt-2 text-xs font-medium uppercase tracking-wide text-fg-muted">{{ $label }}</div>

    {{ $slot }}
</div>
