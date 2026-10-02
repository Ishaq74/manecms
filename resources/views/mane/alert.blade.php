@props([
    'tone' => 'info',
    'title' => null,
    'text' => null,
    'dismissible' => false,
])

@php
    [$icon, $colour] = match ($tone) {
        'success' => ['check-circle', 'text-success'],
        'warning' => ['exclamation-triangle', 'text-warning'],
        'danger' => ['x-circle', 'text-danger'],
        'info' => ['information-circle', 'text-info'],
        default => throw new InvalidArgumentException("Unknown ManeUI alert tone [{$tone}]."),
    };

    // Errors and warnings interrupt the screen reader; information waits its turn.
    $role = in_array($tone, ['danger', 'warning'], true) ? 'alert' : 'status';
@endphp

<div
    {{ $attributes->merge(['role' => $role, 'data-tone' => $tone])->class('flex gap-3 rounded-surface border border-line bg-surface-sunken p-4 text-sm text-fg') }}
    @if ($dismissible) x-data="{ shown: true }" x-show="shown" @endif
>
    <x-mane::icon :name="$icon" @class(['size-5 shrink-0', $colour]) />

    <div class="flex min-w-0 flex-1 flex-col gap-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif

        @if ($text || $slot->isNotEmpty())
            <div class="text-fg-muted">{{ $text ?? $slot }}</div>
        @endif
    </div>

    @if ($dismissible)
        <x-mane::icon-button icon="x-mark" size="sm" :label="__('Dismiss')" x-on:click="shown = false" />
    @endif
</div>
