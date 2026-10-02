{{-- Editorial section: kicker, display title, lead and content; `tone="sunken"` sets it on a contrasting band. --}}
@props([
    'title',
    'eyebrow' => null,
    'lead' => null,
    'tone' => 'default',
    'align' => 'start',
])

<section {{ $attributes->class([
    'py-20 sm:py-[84px]',
    'border-y border-line bg-surface-sunken' => $tone === 'sunken',
    'text-center' => $align === 'center',
]) }}>
    <div class="mx-auto max-w-[1160px] px-4 sm:px-6 lg:px-8">
        <div @class(['flex flex-col gap-3', 'items-center' => $align === 'center'])>
            @if ($eyebrow)
                <p class="mane-eyebrow">{{ $eyebrow }}</p>
            @endif

            <h2 class="mane-display max-w-[760px]">{{ $title }}</h2>

            @if ($lead)
                <p class="max-w-[640px] text-[1.05rem] text-fg-muted">{{ $lead }}</p>
            @endif
        </div>

        @if ($slot->isNotEmpty())
            <div class="mt-10">{{ $slot }}</div>
        @endif
    </div>
</section>
