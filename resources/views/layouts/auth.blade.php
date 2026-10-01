<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="tallstackui_darkTheme({ default: 'dark' })">
    <head>
        @include('partials.head')
    </head>
    <body
        class="min-h-svh bg-white text-zinc-800 antialiased dark:bg-zinc-900 dark:text-zinc-100"
        x-bind:class="darkTheme ? 'dark' : ''"
    >
        <div class="flex min-h-svh flex-col">
            @include('partials.header')

            <main class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </main>

            @include('partials.footer')
        </div>

        <x-toast />

        @livewireScripts
    </body>
</html>