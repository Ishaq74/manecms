{{-- Application frame: `menu` (the sidebar), `header`, the page and `footer`. --}}
<x-ts-layout {{ $attributes }}>
    @isset($menu)
        <x-slot:menu>{{ $menu }}</x-slot:menu>
    @endisset

    @isset($header)
        <x-slot:header>{{ $header }}</x-slot:header>
    @endisset

    {{ $slot }}

    @isset($footer)
        <x-slot:footer>{{ $footer }}</x-slot:footer>
    @endisset
</x-ts-layout>
