<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="tallstackui_darkTheme({ default: 'dark' })">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800" x-bind:class="darkTheme ? 'dark' : ''">
        <x-layout>
            <x-slot:menu>
                <x-side-bar navigate thin-scroll>
                    <x-slot:brand>
                        <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                    </x-slot:brand>

                    <x-side-bar.separator :text="__('Platform')" />

                    <x-side-bar.item
                        icon="computer-desktop"
                        :href="route('dashboard')"
                        :current="request()->routeIs('dashboard')"
                        :text="__('Dashboard')"
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
                </x-side-bar>
            </x-slot:menu>

            <x-slot:header>
                <x-layout.header>
                    <x-slot:left>
                        <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
                    </x-slot:left>

                    <x-slot:right>
                        <nav class="flex items-center gap-1">
                            <x-tooltip :text="__('Search')" position="bottom">
                                <a
                                    href="#"
                                    aria-label="{{ __('Search') }}"
                                    class="flex size-10 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-800/5 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-white/10 dark:hover:text-white"
                                >
                                    <x-icon name="magnifying-glass" />
                                </a>
                            </x-tooltip>

                            <x-tooltip :text="__('Repository')" position="bottom">
                                <a
                                    href="https://github.com/laravel/livewire-starter-kit"
                                    target="_blank"
                                    aria-label="{{ __('Repository') }}"
                                    class="hidden size-10 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-800/5 hover:text-zinc-800 max-lg:hidden dark:text-zinc-400 dark:hover:bg-white/10 dark:hover:text-white lg:flex"
                                >
                                    <x-icon name="folder" />
                                </a>
                            </x-tooltip>

                            <x-tooltip :text="__('Documentation')" position="bottom">
                                <a
                                    href="https://laravel.com/docs/starter-kits#livewire"
                                    target="_blank"
                                    aria-label="{{ __('Documentation') }}"
                                    class="hidden size-10 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-800/5 hover:text-zinc-800 max-lg:hidden dark:text-zinc-400 dark:hover:bg-white/10 dark:hover:text-white lg:flex"
                                >
                                    <x-icon name="book-open" />
                                </a>
                            </x-tooltip>
                        </nav>

                        <x-theme-switch simple only-icons lg />

                        @auth
                            <x-desktop-user-menu class="ms-1.5" />
                        @else
                            <x-button class="ms-1.5" :href="route('login')" navigate :text="__('Log in')" />
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
