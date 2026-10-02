{{-- A feature card: icon tile, title and description (slot). --}}
@props([
    'title',
    'icon' => null,
    'align' => 'start',
])

<x-mane::card rounded="2xl" {{ $attributes->class(['h-full', 'text-center' => $align === 'center']) }}>
    <div @class(['flex flex-col gap-3', 'items-center' => $align === 'center'])>
        @if ($icon)
            <span class="flex size-10 items-center justify-center rounded-[11px] bg-brand-subtle text-brand">
                <x-mane::icon :name="$icon" class="size-5" />
            </span>
        @endif

        <h3 class="font-display text-[1.1rem] font-semibold tracking-[-0.03em] text-fg">{{ $title }}</h3>

        @if ($slot->isNotEmpty())
            <div class="text-[0.93rem] text-fg-muted">{{ $slot }}</div>
        @endif
    </div>
</x-mane::card>
