@props([
    'href',
    'text' => null,
    'navigate' => false,
    'underline' => true,
    'colorless' => false,
    'size' => null,
    'blank' => false,
])

<x-ts-link
    {{ $attributes }}
    :href="$href"
    :text="$text"
    :navigate="$navigate"
    :underline="$underline"
    :colorless="$colorless"
    :size="$size"
    :blank="$blank"
>{{ $slot }}</x-ts-link>
