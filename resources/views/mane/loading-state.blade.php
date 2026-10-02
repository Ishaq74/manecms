{{-- §465: initial load, action in progress, background refresh and queued operation read differently. --}}
@props([
    'kind' => 'initial',
    'label' => null,
])

@php
    $defaultLabel = match ($kind) {
        'initial' => __('Loading…'),
        'action' => __('Working on it…'),
        'background' => __('Refreshing in the background…'),
        'queued' => __('Queued, it will run shortly.'),
        default => throw new InvalidArgumentException("Unknown ManeUI loading state kind [{$kind}]."),
    };
@endphp

<div {{ $attributes->merge(['role' => 'status', 'aria-live' => 'polite', 'data-kind' => $kind])->class('flex items-center gap-2 text-sm text-fg-muted') }}>
    @if ($kind === 'queued')
        <x-mane::icon name="clock" class="size-4 shrink-0" />
    @else
        <x-ts-spinner sm aria-hidden="true" />
    @endif

    <span>{{ $label ?? $defaultLabel }}</span>
</div>
