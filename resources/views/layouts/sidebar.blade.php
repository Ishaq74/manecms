{{-- App pages with the collapsible sidebar: dashboard, settings, admin. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="tallstackui_darkTheme({ default: 'dark' })">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-svh bg-white text-zinc-800 antialiased dark:bg-zinc-900 dark:text-zinc-100">
        <x-layout>
            <x-slot:menu>
                <x-side-bar collapsible smart navigate thin-scroll>
                    <x-slot:brand>
                        <x-app-logo :href="route('home')" wire:navigate />
                    </x-slot:brand>

                    <x-side-bar.separator :text="__('Platform')" />

                    <x-side-bar.item
                        icon="computer-desktop"
                        :href="route('home')"
                        :current="request()->routeIs('home')"
                        :text="__('Home')"
                    />

                    @auth
                        <x-side-bar.item
                            icon="list-bullet"
                            :href="route('dashboard')"
                            :current="request()->routeIs('dashboard')"
                            :text="__('Dashboard')"
                        />
                    @endauth

                    <x-slot:footer>
                        @auth
                            <x-desktop-user-menu />
                        @else
                            <x-button block :href="route('login')" navigate :text="__('Log in')" />
                        @endauth
                    </x-slot:footer>
                </x-side-bar>
            </x-slot:menu>

            <x-slot:header>
                <x-layout.header>
                    <x-slot:right>
                        <x-theme-switch simple only-icons />

                        @guest
                            <x-button :href="route('login')" navigate :text="__('Log in')" />
                        @else
                            <x-desktop-user-menu class="lg:hidden" />
                        @endguest
                    </x-slot:right>
                </x-layout.header>
            </x-slot:header>

            <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>

            <x-slot:footer>
                @include('partials.footer')
            </x-slot:footer>
        </x-layout>

        <x-toast />

        @livewireScripts
    </body>
</html>