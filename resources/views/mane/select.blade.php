{{-- Native select with a real label association; TallStackUI's native select renders an orphan label. --}}
@props([
    'label' => null,
    'hint' => null,
    'options' => [],
    'placeholder' => null,
])

@php
    $property = $attributes->wire('model')->value() ?: $attributes->get('name');
    $id = $attributes->get('id') ?? 'mane-select-'.($property ? Str::slug((string) $property) : md5((string) $label));
    $describedBy = $hint ? $id.'-hint' : null;
@endphp

<div class="flex flex-col gap-1">
    @if ($label)
        <label for="{{ $id }}" class="text-sm font-medium text-dark-600 dark:text-dark-300">{{ $label }}</label>
    @endif

    <select
        {{ $attributes->merge(['id' => $id, 'aria-describedby' => $describedBy])->class([
            'h-control w-full rounded-control border-0 bg-surface-raised py-1.5 ps-3 pe-8 text-sm text-fg ring-1 ring-line-strong',
            'focus:ring-2 focus:ring-focus focus:outline-hidden',
            'aria-invalid:ring-danger',
        ]) }}
        @error($property) aria-invalid="true" @enderror
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}" wire:key="{{ $id }}-{{ $value }}">{{ $optionLabel }}</option>
        @endforeach

        {{ $slot }}
    </select>

    @if ($hint)
        <p id="{{ $id }}-hint" class="text-sm text-fg-muted">{{ $hint }}</p>
    @endif

    @error($property)
        <p class="text-sm font-medium text-danger">{{ $message }}</p>
    @enderror
</div>
