@php
    $links = [['home', __('Home')]];

    if (auth()->check()) {
        $links[] = ['dashboard', __('Dashboard')];
    }
@endphp

{{--
    Visibility is driven by wrapper elements, never by `hidden` on a TallStackUI
    button: the button base classes include `inline-flex`, which Tailwind emits
    after `.hidden` in the stylesheet and would win.
--}}

<header class="sticky top-0 z-30 border-b border-zinc-200 bg-white/80 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/80">
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
            @foreach ($links as [$name, $label])
                <a
                    href="{{ route($name) }}"
                    wire:navigate
                    @class([
                        'rounded-lg px-3 py-2 text-sm transition-colors',
                        'bg-zinc-800/5 font-medium text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs($name),
                        'text-zinc-500 hover:bg-zinc-800/5 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-white/[7%] dark:hover:text-white' => ! request()->routeIs($name),
                    ])
                    @if (request()->routeIs($name)) aria-current="page" @endif
                >
                    {{ $label }}
                </a>
            @endforeach
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
                    <x-desktop-user-menu />
                </div>
            @endguest

            <x-theme-switch simple only-icons />
        </div>
    </div>

    <x-slide id="mobile-navigation" left size="sm" paddingless>
        <nav aria-label="{{ __('Mobile navigation') }}" class="flex flex-col gap-1 p-4">
            @foreach ($links as [$name, $label])
                <a
                    href="{{ route($name) }}"
                    wire:navigate
                    x-on:click="$tsui.close.slide('mobile-navigation')"
                    @class([
                        'rounded-lg px-3 py-2 text-sm transition-colors',
                        'bg-zinc-800/5 font-medium text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs($name),
                        'text-zinc-500 hover:bg-zinc-800/5 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-white/[7%] dark:hover:text-white' => ! request()->routeIs($name),
                    ])
                    @if (request()->routeIs($name)) aria-current="page" @endif
                >
                    {{ $label }}
                </a>
            @endforeach

            <hr class="my-2 border-zinc-200 dark:border-zinc-700" />

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
                <x-desktop-user-menu />
            @endguest
        </nav>
    </x-slide>
</header>