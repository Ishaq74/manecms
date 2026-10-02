<x-ts-side-bar {{ $attributes }} collapsible smart navigate thin-scroll>
    @isset($brand)
        <x-slot:brand>{{ $brand }}</x-slot:brand>
    @endisset

    @isset($brandCollapsed)
        <x-slot:brandCollapsed>{{ $brandCollapsed }}</x-slot:brandCollapsed>
    @endisset

    {{ $slot }}

    @isset($footer)
        <x-slot:footer>{{ $footer }}</x-slot:footer>
    @endisset
</x-ts-side-bar>
