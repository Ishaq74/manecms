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

<header class="sticky top-0 z-(--mane-z-sticky) border-b border-line bg-surface/80 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-2 px-4 sm:gap-3 sm:px-6 lg:px-8">
        <div class="sm:hidden">
            <x-mane::icon-button icon="bars-3" :label="__('Open menu')" x-on:click="$tsui.open.slide('mobile-navigation')" aria-controls="mobile-navigation" data-test="mobile-menu-button" />
        </div>

        <x-app-logo :href="route('home')" wire:navigate />

        <nav aria-label="{{ __('Main navigation') }}" class="hidden items-center gap-1 sm:flex">
            @include('partials.nav-links', ['links' => $links])
        </nav>

        <div class="ms-auto flex items-center gap-2">
            @guest
                <div class="hidden items-center gap-2 sm:flex">
                    <x-mane::button variant="ghost" size="sm" :href="route('login')" navigate :text="__('Log in')" />
                    <x-mane::button size="sm" :href="route('register')" navigate :text="__('Register')" />
                </div>

                <div class="sm:hidden">
                    <x-mane::icon-button icon="user-circle" :label="__('Log in')" :href="route('login')" navigate data-test="mobile-login-button" />
                </div>
            @else
                <div class="hidden sm:block">
                    <x-desktop-user-menu test-prefix="desktop-user-menu" />
                </div>
            @endguest

            <x-mane::theme-toggle />
        </div>
    </div>

    <x-mane::drawer id="mobile-navigation" side="start" size="sm" paddingless>
        <nav aria-label="{{ __('Mobile navigation') }}" class="flex flex-col gap-1 p-4">
            @include('partials.nav-links', ['links' => $links])

            <hr class="my-2 border-line" />

            @guest
                <x-mane::button
                    variant="ghost"
                    block
                    :href="route('login')"
                    navigate
                    x-on:click="$tsui.close.slide('mobile-navigation')"
                    :text="__('Log in')"
                />

                <x-mane::button
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
    </x-mane::drawer>
</header>