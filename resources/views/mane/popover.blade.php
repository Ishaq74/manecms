{{--
    Non-modal panel anchored to its trigger and positioned by Floating UI
    (x-anchor), so it flips and shifts to stay on screen. Escape and an outside click
    close it, and focus goes back to the trigger.
--}}
@props([
    'label',
    'icon' => 'chevron-down',
    'align' => 'start',
    'placement' => 'bottom',
])

@php
    $panelId = 'mane-popover-'.Str::random(8);
    $anchor = 'x-anchor.'.$placement.'-'.$align.'.offset.6';
@endphp

<div {{ $attributes->class('relative inline-block') }} x-data="{ open: false }" x-on:keydown.escape.prevent.stop="open = false; $refs.trigger.focus()">
    <x-mane::button
        variant="secondary"
        size="sm"
        :icon="$icon"
        icon-position="end"
        :text="$label"
        x-ref="trigger"
        x-on:click="open = ! open"
        aria-haspopup="dialog"
        aria-controls="{{ $panelId }}"
        x-bind:aria-expanded="open.toString()"
    />

    {{-- Not teleported: a popover often holds wire:model fields, which Livewire must keep morphing in place. --}}
    <div
            id="{{ $panelId }}"
            role="dialog"
            aria-label="{{ $label }}"
            x-show="open"
            x-cloak
            x-transition.opacity
            {{ $anchor }}="$refs.trigger"
            x-on:click.outside="if (! $refs.trigger.contains($event.target)) { open = false }"
            x-on:keydown.escape.prevent.stop="open = false; $refs.trigger.focus()"
            class="z-(--mane-z-dropdown) w-72 max-w-[calc(100vw-1rem)] rounded-surface border border-line bg-surface-raised p-4 text-sm text-fg shadow-lg"
        >
            {{ $slot }}
        </div>
</div>
