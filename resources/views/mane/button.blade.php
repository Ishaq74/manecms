{{--
    Disabled (§407): aria-disabled keeps the button focusable and announced, and
    the click handlers, link and submit type are dropped so it stays inert.
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'navigate' => false,
    'icon' => null,
    'iconPosition' => 'start',
    'loading' => null,
    'disabled' => false,
    'block' => false,
    'text' => null,
])

@php
    $style = match ($variant) {
        'primary' => ['color' => 'primary', 'solid' => true, 'outline' => false, 'flat' => false],
        'secondary' => ['color' => 'primary', 'solid' => false, 'outline' => true, 'flat' => false],
        'ghost' => ['color' => 'primary', 'solid' => false, 'outline' => false, 'flat' => true],
        'danger' => ['color' => 'red', 'solid' => true, 'outline' => false, 'flat' => false],
        default => throw new InvalidArgumentException("Unknown ManeUI button variant [{$variant}]."),
    };

    throw_unless(in_array($size, ['sm', 'md', 'lg'], true), InvalidArgumentException::class, "Unknown ManeUI button size [{$size}].");

    $attributes = $attributes->merge([
        'type' => $disabled ? 'button' : $type,
        'data-mane-submit' => $type === 'submit' ? '' : null,
    ]);

    if ($disabled) {
        $attributes = $attributes
            ->filter(fn (mixed $value, string $key): bool => preg_match('/^(wire:click|wire:navigate|x-on:click|@click)/', $key) !== 1)
            ->merge(['aria-disabled' => 'true'])
            ->class('opacity-60');
    } elseif ($navigate && $href !== null) {
        $attributes = $attributes->merge(['wire:navigate' => true]);
    }

    if (! $disabled && filled($loading)) {
        $attributes = $attributes->merge(['wire:target' => $loading]);
    }
@endphp

<x-ts-button
    {{ $attributes }}
    :color="$style['color']"
    :solid="$style['solid']"
    :outline="$style['outline']"
    :flat="$style['flat']"
    :size="$size"
    :href="$disabled ? null : $href"
    :icon="$icon"
    :position="$iconPosition === 'end' ? 'right' : 'left'"
    :loading="$disabled ? null : $loading"
    :block="$block"
    :text="$text"
>{{ $slot }}</x-ts-button>
