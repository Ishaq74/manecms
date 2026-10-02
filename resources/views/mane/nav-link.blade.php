{{-- A navigation entry: current page announced with aria-current, highlighted with surface tokens. --}}
@props([
    'href',
    'current' => false,
    'icon' => null,
    'navigate' => true,
])

<a
    href="{{ $href }}"
    @if ($navigate) wire:navigate @endif
    @if ($current) aria-current="page" @endif
    {{ $attributes->class([
        'flex items-center gap-2 whitespace-nowrap rounded-control px-3 py-2 text-sm font-medium transition-colors',
        'bg-surface-hover text-fg' => $current,
        'text-fg-muted hover:bg-surface-hover hover:text-fg' => ! $current,
    ]) }}
>
    @if ($icon)
        <x-mane::icon :name="$icon" class="size-4 shrink-0" />
    @endif

    {{ $slot }}
</a>
