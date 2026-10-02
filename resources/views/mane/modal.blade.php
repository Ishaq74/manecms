@props([
    'id',
    'title' => null,
    'size' => 'lg',
    'wire' => null,
    'persistent' => false,
])

<x-ts-modal {{ $attributes }} :id="$id" :title="$title" :size="$size" :wire="$wire" :persistent="$persistent">
    {{ $slot }}

    @isset($footer)
        <x-slot:footer>{{ $footer }}</x-slot:footer>
    @endisset
</x-ts-modal>
