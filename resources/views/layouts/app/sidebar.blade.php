<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="tallstackui_darkTheme({ default: 'dark' })">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800" x-bind:class="darkTheme ? 'dark' : ''">
        <x-layout>
            <x-slot:menu>
                <x-side-bar collapsible smart navigate thin-scroll>
                    <x-slot:brand>
                        <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                    </x-slot:brand>

                    <x-side-bar.separator :text="__('Platform')" />

                    <x-side-bar.item
                        icon="computer-desktop"
                        :href="route('dashboard')"
                        :current="request()->routeIs('dashboard')"
                        :text="__('Dashboard')"
                        wire:navigate
                    />

                    <x-side-bar.separator :text="__('Resources')" />

                    <x-side-bar.item
                        icon="code-bracket"
                        href="https://github.com/laravel/livewire-starter-kit"
                        target="_blank"
                        :text="__('Repository')"
                    />

                    <x-side-bar.item
                        icon="document-text"
                        href="https://laravel.com/docs/starter-kits#livewire"
                        target="_blank"
                        :text="__('Documentation')"
                    />

                    <x-slot:footer>
                        @auth
                            <x-desktop-user-menu class="hidden lg:block" />
                        @else
                            <div class="hidden lg:block">
                                <x-button block :href="route('login')" navigate :text="__('Log in')" />
                            </div>
                        @endauth
                    </x-slot:footer>
                </x-side-bar>
            </x-slot:menu>

            <x-slot:header>
                <x-layout.header>
                    <x-slot:right>
                        <x-theme-switch simple only-icons lg />

                        @auth
                            <x-desktop-user-menu class="lg:hidden" />
                        @else
                            <x-button class="lg:hidden" :href="route('login')" navigate :text="__('Log in')" />
                        @endauth
                    </x-slot:right>
                </x-layout.header>
            </x-slot:header>

            {{ $slot }}
        </x-layout>

        <x-toast />

        @livewireScripts
    </body>
</html>
