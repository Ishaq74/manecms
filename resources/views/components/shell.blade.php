@props([
    'mainClass' => 'mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="tallstackui_darkTheme({ default: 'dark' })"
      x-bind:class="darkTheme ? 'dark' : ''">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-svh bg-white text-zinc-800 antialiased dark:bg-zinc-900 dark:text-zinc-100">
        <div {{ $attributes->class('flex min-h-svh flex-col') }}>
            @include('partials.header')

            <main class="{{ $mainClass }}">
                {{ $slot }}
            </main>

            @include('partials.footer')
        </div>

        <x-toast />

        @livewireScripts
    </body>
</html>