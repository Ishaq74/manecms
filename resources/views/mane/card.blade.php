@props([
    'paddingless' => false,
    'rounded' => 'lg',
])

<x-ts-card {{ $attributes }} :paddingless="$paddingless" :round="$rounded" bordered>
    @isset($header)
        <x-slot:header>{{ $header }}</x-slot:header>
    @endisset

    {{ $slot }}

    @isset($footer)
        <x-slot:footer>{{ $footer }}</x-slot:footer>
    @endisset
</x-ts-card>
