{{-- @param list<array{label: string, href?: string|null}> $items  the last item is the current page --}}
@props([
    'items' => [],
])

<nav {{ $attributes->merge(['aria-label' => __('Breadcrumb')]) }}>
    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-fg-muted">
        @foreach ($items as $item)
            <li class="inline-flex items-center gap-1.5" wire:key="breadcrumb-{{ $loop->index }}">
                @if ($loop->last)
                    <span aria-current="page" class="font-medium text-fg">{{ $item['label'] }}</span>
                @else
                    @if (filled($item['href'] ?? null))
                        <a href="{{ $item['href'] }}" wire:navigate class="underline-offset-4 hover:text-fg hover:underline">{{ $item['label'] }}</a>
                    @else
                        <span>{{ $item['label'] }}</span>
                    @endif

                    <x-mane::icon name="chevron-right" class="size-4 shrink-0 rtl:rotate-180" />
                @endif
            </li>
        @endforeach
    </ol>
</nav>
