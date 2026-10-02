{{-- A list item led by a check mark; `title` is set in bold before the text. --}}
@props([
    'title' => null,
])

<li {{ $attributes->class('relative ps-7 text-[0.95rem] leading-relaxed text-fg-muted') }}>
    <x-mane::icon name="check" class="absolute start-0 top-1 size-4 text-brand" />

    @if ($title)
        <span class="font-semibold text-fg">{{ $title }}</span>
    @endif

    {{ $slot }}
</li>
