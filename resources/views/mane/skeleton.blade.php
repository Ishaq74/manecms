@props([
    'lines' => 3,
])

<div {{ $attributes->class('flex flex-col gap-2') }}>
    <span class="sr-only" role="status">{{ __('Loading…') }}</span>

    @for ($line = 1; $line <= $lines; $line++)
        <span
            aria-hidden="true"
            @class([
                'block h-3 animate-pulse rounded-control bg-surface-sunken ring-1 ring-line motion-reduce:animate-none',
                'w-2/3' => $line === $lines && $lines > 1,
                'w-full' => $line !== $lines || $lines === 1,
            ])
        ></span>
    @endfor
</div>
