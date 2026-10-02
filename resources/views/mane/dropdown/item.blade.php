{{-- A menu item: a link when `href` is given, a button otherwise (type="submit" inside a form). --}}
@props([
    'text' => null,
    'icon' => null,
    'href' => null,
    'navigate' => false,
    'separator' => false,
])

@php
    $classes = 'flex w-full cursor-pointer items-center gap-2 rounded-control px-2 py-1.5 text-start text-sm text-fg hover:bg-surface-sunken focus:bg-surface-sunken';
@endphp

@if ($separator)
    <div role="separator" class="my-1 border-t border-line"></div>
@endif

@if ($href !== null)
    <a href="{{ $href }}" role="menuitem" tabindex="-1" @if ($navigate) wire:navigate @endif x-on:click="open = false" {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-mane::icon :name="$icon" class="size-4 shrink-0 text-fg-muted" />
        @endif
        {{ $text ?? $slot }}
    </a>
@else
    <button type="{{ $attributes->get('type', 'button') }}" role="menuitem" tabindex="-1" {{ $attributes->except('type')->class($classes) }}>
        @if ($icon)
            <x-mane::icon :name="$icon" class="size-4 shrink-0 text-fg-muted" />
        @endif
        {{ $text ?? $slot }}
    </button>
@endif
