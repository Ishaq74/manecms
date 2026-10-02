{{-- Custom trigger for the `trigger` slot of <x-mane::dropdown>: a full-width button filled by the slot. --}}
<button
    type="button"
    x-ref="trigger"
    aria-haspopup="menu"
    x-bind:aria-expanded="open.toString()"
    x-on:click="open ? close(false) : show()"
    x-on:keydown.arrow-down.prevent="show()"
    {{ $attributes->class('flex w-full cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-start text-sm transition hover:bg-dark-800/5 dark:hover:bg-white/10') }}
>
    {{ $slot }}
</button>
