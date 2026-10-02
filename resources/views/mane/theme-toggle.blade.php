{{--
    One-click light/dark toggle for headers.

    Icon visibility is pure CSS: partials/head puts `dark` on <html> before the
    first paint, so the right glyph is drawn on the very first frame. Alpine only
    handles the click, and setting `mode` is what persists the choice, since
    tallstackui_darkTheme (on <html>) watches it.

    The TallStackUI theme switch is not used here on purpose: both its variations
    cloak themselves and bind the icons with Alpine, which made the control pop in
    after boot and shift the header.
--}}
<button
    type="button"
    role="switch"
    aria-label="{{ __('Toggle theme') }}"
    x-bind:aria-checked="darkTheme.toString()"
    x-on:click="mode = darkTheme ? 'light' : 'dark'; $el.dispatchEvent(new CustomEvent('theme', { detail: { darkTheme: mode === 'dark', mode: mode } }))"
    {{ $attributes->merge(['data-test' => 'theme-switch'])->class('cursor-pointer rounded-md p-1.5 text-dark-500 transition-colors hover:bg-dark-800/5 hover:text-dark-800 dark:text-dark-400 dark:hover:bg-white/10 dark:hover:text-white') }}
>
    <span class="block dark:hidden">
        <x-mane::icon name="sun" class="size-5" />
    </span>

    <span class="hidden dark:block">
        <x-mane::icon name="moon" class="size-5" />
    </span>
</button>
