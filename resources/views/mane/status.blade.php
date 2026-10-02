{{-- A status is never colour alone (WCAG 1.4.1): every tone pairs an icon with a text. --}}
@props([
    'tone' => 'info',
    'text' => null,
])

@php
    [$icon, $colour] = match ($tone) {
        'success' => ['check-circle', 'text-success'],
        'warning' => ['exclamation-triangle', 'text-warning'],
        'danger' => ['x-circle', 'text-danger'],
        'info' => ['information-circle', 'text-info'],
        'muted' => ['minus-circle', 'text-fg-muted'],
        default => throw new InvalidArgumentException("Unknown ManeUI status tone [{$tone}]."),
    };
@endphp

<span {{ $attributes->merge(['data-tone' => $tone])->class(['inline-flex items-center gap-1.5 text-sm font-medium', $colour]) }}>
    <x-mane::icon :name="$icon" class="size-4 shrink-0" />
    <span>{{ $text ?? $slot }}</span>
</span>
