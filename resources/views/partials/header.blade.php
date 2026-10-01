@php
    $links = [
        ['route' => 'home', 'label' => __('Home')],
    ];
@endphp

{{--
    Visibility is driven by wrapper elements, never by `hidden` on a TallStackUI
    button: the button base classes include `inline-flex`, which Tailwind emits
    after `.hidden` in the stylesheet and would win.
--}}

<header class="sticky top-0 z-30 border-b border-dark-200 bg-white/80 backdrop-blur dark:border-dark-700 dark:bg-dark-900/80">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-2 px-4 sm:gap-3 sm:px-6 lg:px-8">
        <div class="sm:hidden">
            <x-button.circle
                flat
                icon="bars-3"
                x-on:click="$tsui.open.slide('mobile-navigation')"
                aria-label="{{ __('Open menu') }}"
                aria-controls="mobile-navigation"
                data-test="mobile-menu-button"
            />
        </div>

        <x-app-logo :href="route('home')" navigate />

        <nav aria-label="{{ __('Main navigation') }}" class="hidden items-center gap-1 sm:flex">
            @include('partials.nav-links', ['links' => $links])
        </nav>

        <div class="ms-auto flex items-center gap-2">
            @guest
                <div class="hidden items-center gap-2 sm:flex">
                    <x-button flat sm :href="route('login')" navigate :text="__('Log in')" />
                    <x-button sm :href="route('register')" navigate :text="__('Register')" />
                </div>

                <div class="sm:hidden">
                    <x-button.circle
                        flat
                        :href="route('login')"
                        navigate
                        aria-label="{{ __('Log in') }}"
                        data-test="mobile-login-button"
                    >
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                    </x-button.circle>
                </div>
            @else
                <div class="hidden sm:block">
                    <x-desktop-user-menu test-prefix="desktop-user-menu" />
                </div>
            @endguest

            {{--
                Icon visibility is pure CSS: partials/head puts `dark` on <html>
                before the first paint, so the right glyph is drawn on the very
                first frame. Alpine only handles the click, and setting `mode` is
                what persists the choice, since tallstackui_darkTheme watches it.

                x-theme-switch was not used here on purpose: both its variations
                cloak themselves and bind the icons with Alpine, which meant the
                control popped in after boot and shifted the header.
            --}}
            <button
                type="button"
                role="switch"
                aria-label="{{ __('Toggle theme') }}"
                x-bind:aria-checked="darkTheme.toString()"
                x-on:click="mode = darkTheme ? 'light' : 'dark'; $el.dispatchEvent(new CustomEvent('theme', { detail: { darkTheme: mode === 'dark', mode: mode } }))"
                data-test="theme-switch"
                class="cursor-pointer rounded-md p-1.5 text-dark-500 transition-colors hover:bg-dark-800/5 hover:text-dark-800 dark:text-dark-400 dark:hover:bg-white/10 dark:hover:text-white"
            >
                <span class="block dark:hidden">
                    <x-icon name="sun" />
                </span>

                <span class="hidden dark:block">
                    <x-icon name="moon" />
                </span>
            </button>
        </div>
    </div>

    <x-slide id="mobile-navigation" left size="sm" paddingless>
        <nav aria-label="{{ __('Mobile navigation') }}" class="flex flex-col gap-1 p-4">
            @include('partials.nav-links', ['links' => $links])

            <hr class="my-2 border-dark-200 dark:border-dark-700" />

            @guest
                <x-button
                    flat
                    block
                    :href="route('login')"
                    navigate
                    x-on:click="$tsui.close.slide('mobile-navigation')"
                    :text="__('Log in')"
                />

                <x-button
                    block
                    :href="route('register')"
                    navigate
                    x-on:click="$tsui.close.slide('mobile-navigation')"
                    :text="__('Register')"
                />
            @else
                <x-desktop-user-menu test-prefix="mobile-user-menu" />
            @endguest
        </nav>
    </x-slide>
</header>