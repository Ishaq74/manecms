{{-- TallStackUI renders one unlabelled box per character; ManeUI names each of them. --}}
@props([
    'label' => null,
    'hint' => null,
    'length' => 6,
])

<div
    x-data
    x-init="$nextTick(() => $el.querySelectorAll('input:not([type=hidden])').forEach((input, index) => input.setAttribute('aria-label', @js($label ?? __('Code')) + ' ' + (index + 1))))"
>
    <x-ts-pin {{ $attributes }} :label="$label" :hint="$hint" :length="$length" numbers />
</div>
