{{-- An icon alone is not a name: `label` is required and becomes the accessible name. --}}
@props([
    'icon',
    'label',
    'variant' => 'ghost',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'navigate' => false,
    'loading' => null,
    'disabled' => false,
])

@php
    $style = match ($variant) {
        'primary' => ['color' => 'primary', 'solid' => true, 'outline' => false, 'flat' => false],
        'secondary' => ['color' => 'primary', 'solid' => false, 'outline' => true, 'flat' => false],
        'ghost' => ['color' => 'primary', 'solid' => false, 'outline' => false, 'flat' => true],
        'danger' => ['color' => 'red', 'solid' => true, 'outline' => false, 'flat' => false],
        default => throw new InvalidArgumentException("Unknown ManeUI icon button variant [{$variant}]."),
    };

    $attributes = $attributes->merge([
        'type' => $disabled ? 'button' : $type,
        'aria-label' => $label,
        'title' => $label,
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

<x-ts-button.circle
    {{ $attributes }}
    :icon="$icon"
    :color="$style['color']"
    :solid="$style['solid']"
    :outline="$style['outline']"
    :flat="$style['flat']"
    :size="$size"
    :href="$disabled ? null : $href"
    :loading="$disabled ? null : $loading"
/>
