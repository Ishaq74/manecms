{{-- `side` is logical: `start` opens from the left in LTR and from the right in RTL. --}}
@props([
    'id',
    'title' => null,
    'size' => 'md',
    'side' => 'end',
    'wire' => null,
    'persistent' => false,
    'paddingless' => false,
])

@php
    throw_unless(in_array($side, ['start', 'end'], true), InvalidArgumentException::class, "Unknown ManeUI drawer side [{$side}].");

    $rightToLeft = App\Enums\TextDirection::current() === App\Enums\TextDirection::RightToLeft;
    $opensFromLeft = ($side === 'start') !== $rightToLeft;
@endphp

<x-ts-slide
    {{ $attributes }}
    :id="$id"
    :title="$title"
    :size="$size"
    :left="$opensFromLeft"
    :wire="$wire"
    :persistent="$persistent"
    :paddingless="$paddingless"
>
    {{ $slot }}

    @isset($footer)
        <x-slot:footer>{{ $footer }}</x-slot:footer>
    @endisset
</x-ts-slide>
