@props([
    'multiple' => false,
])

<x-ts-accordion {{ $attributes }} :multiple="$multiple" shadowless bordered>{{ $slot }}</x-ts-accordion>
