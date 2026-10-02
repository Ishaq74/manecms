@props([
    'title' => null,
    'mainClass' => 'mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ App\Enums\TextDirection::current()->value }}"
      x-data="tallstackui_darkTheme({ default: 'dark' })">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-svh bg-surface text-fg antialiased">
        <div {{ $attributes->class('flex min-h-svh flex-col') }}>
            @include('partials.header')

            <main class="{{ $mainClass }}">
                {{ $slot }}
            </main>

            @include('partials.footer')
        </div>

        <x-mane::toast />

        @livewireScripts
    </body>
</html>