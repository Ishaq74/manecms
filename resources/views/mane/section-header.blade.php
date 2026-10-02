{{-- A section title (h2, or h3 inside a section) and its lead, given as a prop or as the slot. --}}
@props([
    'title',
    'description' => null,
    'level' => 2,
])

@php
    throw_unless(in_array($level, [2, 3], true), InvalidArgumentException::class, "Unsupported ManeUI section heading level [{$level}].");
@endphp

<div {{ $attributes->class('flex flex-col gap-1') }}>
    @if ($level === 2)
        <h2 class="text-lg font-semibold tracking-tight text-fg">{{ $title }}</h2>
    @else
        <h3 class="text-base font-semibold text-fg">{{ $title }}</h3>
    @endif

    @if ($description || $slot->isNotEmpty())
        <p class="text-sm text-fg-muted">{{ $description ?? $slot }}</p>
    @endif
</div>
