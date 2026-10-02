{{-- A key figure: the value first, its meaning below. --}}
@props([
    'value',
    'label',
    'delta' => null,
])

<div {{ $attributes->class('flex flex-col gap-1') }}>
    <p class="font-display text-[2rem] font-extrabold leading-none tracking-[-0.03em] text-brand">{{ $value }}</p>
    <p class="text-[0.85rem] text-fg-muted">{{ $label }}</p>

    @if ($delta)
        <p class="text-[0.75rem] font-semibold text-brand">{{ $delta }}</p>
    @endif
</div>
