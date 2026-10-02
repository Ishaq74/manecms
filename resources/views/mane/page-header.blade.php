{{-- The one h1 of a page, its lead (prop or slot) and its page-level actions. --}}
@props([
    'title',
    'description' => null,
    'align' => 'start',
])

<header {{ $attributes->class([
    'flex flex-wrap items-end justify-between gap-4',
    'justify-center text-center' => $align === 'center',
]) }}>
    <div class="flex min-w-0 flex-col gap-1">
        <h1 class="text-2xl font-semibold tracking-tight text-fg">{{ $title }}</h1>

        @if ($description || $slot->isNotEmpty())
            <p class="text-sm text-fg-muted">{{ $description ?? $slot }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</header>
