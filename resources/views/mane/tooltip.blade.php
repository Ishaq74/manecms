{{--
    Describes its trigger (the slot) on hover and focus. WCAG 1.4.13: the text can
    be hovered, stays until dismissed, and Escape hides it.
--}}
@props([
    'text',
    'position' => 'top',
])

@php
    $id = 'mane-tooltip-'.md5($text);
    $placement = match ($position) {
        'top' => 'bottom-full mb-2',
        'bottom' => 'top-full mt-2',
        default => throw new InvalidArgumentException("Unknown ManeUI tooltip position [{$position}]."),
    };
@endphp

<span
    {{ $attributes->class('relative inline-flex') }}
    x-data="{ open: false }"
    x-init="$el.firstElementChild?.setAttribute('aria-describedby', '{{ $id }}')"
    x-on:mouseenter="open = true"
    x-on:mouseleave="open = false"
    x-on:focusin="open = true"
    x-on:focusout="open = false"
    x-on:keydown.escape="open = false"
>
    {{ $slot }}

    <span
        id="{{ $id }}"
        role="tooltip"
        x-show="open"
        x-cloak
        @class([
            'pointer-events-auto absolute start-1/2 z-(--mane-z-dropdown) w-max max-w-xs -translate-x-1/2 rtl:translate-x-1/2',
            'rounded-control bg-dark-900 px-2 py-1 text-xs font-medium text-white shadow-lg dark:bg-dark-100 dark:text-dark-900',
            $placement,
        ])
    >{{ $text }}</span>
</span>
