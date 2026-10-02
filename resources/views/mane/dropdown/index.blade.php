{{--
    Menu button (WAI-ARIA APG): arrow keys, Home and End move between items,
    Escape closes and gives focus back to the trigger, Tab leaves the menu.

    The panel is teleported to <body> and anchored with Floating UI (x-anchor):
    it escapes overflow and stacking contexts, flips and shifts to stay on screen.
    A custom trigger goes in the `trigger` slot as <x-mane::dropdown.trigger>.
--}}
@props([
    'label' => null,
    'icon' => 'chevron-down',
    'align' => 'end',
    'placement' => 'bottom',
])

@php
    $menuId = 'mane-menu-'.Str::random(8);
    $anchor = 'x-anchor.'.$placement.'-'.$align.'.offset.6';
@endphp

<div
    {{ $attributes->class('relative') }}
    x-data="{
        open: false,
        items() { return [...this.$refs.menu.querySelectorAll('[role=menuitem]')]; },
        focusAt(index) { this.items().at(index)?.focus(); },
        move(step) {
            const items = this.items();
            const current = items.indexOf(document.activeElement);
            items[(current + step + items.length) % items.length]?.focus();
        },
        show() { this.open = true; this.$nextTick(() => this.focusAt(0)); },
        close(returnFocus = true) { this.open = false; if (returnFocus) { this.$refs.trigger?.focus(); } },
    }"
    x-on:keydown.escape.prevent.stop="if (open) { close() }"
>
    @isset($trigger)
        {{ $trigger }}
    @else
        <x-mane::button
            variant="secondary"
            size="sm"
            :icon="$icon"
            icon-position="end"
            :text="$label"
            x-ref="trigger"
            aria-haspopup="menu"
            aria-controls="{{ $menuId }}"
            x-bind:aria-expanded="open.toString()"
            x-on:click="open ? close(false) : show()"
            x-on:keydown.arrow-down.prevent="show()"
        />
    @endisset

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            {{ $anchor }}="$refs.trigger"
            x-on:click.outside="if (! $refs.trigger.contains($event.target)) { open = false }"
            x-on:keydown.escape.prevent.stop="close()"
            class="z-(--mane-z-dropdown) w-max min-w-56 max-w-[calc(100vw-1rem)] rounded-surface border border-line bg-surface-raised p-1 text-fg shadow-lg"
        >
            @isset($header)
                <div class="border-b border-line px-2 pb-2 pt-1">{{ $header }}</div>
            @endisset

            <div
                id="{{ $menuId }}"
                role="menu"
                @if ($label) aria-label="{{ $label }}" @endif
                x-ref="menu"
                x-on:keydown.arrow-down.prevent="move(1)"
                x-on:keydown.arrow-up.prevent="move(-1)"
                x-on:keydown.home.prevent="focusAt(0)"
                x-on:keydown.end.prevent="focusAt(-1)"
                x-on:keydown.tab="close()"
                class="flex max-h-[min(24rem,calc(100vh-2rem))] flex-col overflow-y-auto"
            >
                {{ $slot }}
            </div>
        </div>
    </template>
</div>
