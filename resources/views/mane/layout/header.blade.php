<x-ts-layout.header {{ $attributes }}>
    @isset($left)
        <x-slot:left>{{ $left }}</x-slot:left>
    @endisset

    @isset($right)
        <x-slot:right>{{ $right }}</x-slot:right>
    @endisset

    {{ $slot }}
</x-ts-layout.header>
