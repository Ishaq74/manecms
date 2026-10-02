{{-- §464: every important screen tells empty, first use, no access, no results and error apart. --}}
@props([
    'kind' => 'empty',
    'title' => null,
    'description' => null,
])

@php
    [$icon, $defaultTitle] = match ($kind) {
        'empty' => ['inbox', __('Nothing here yet')],
        'first-use' => ['sparkles', __('Get started')],
        'no-access' => ['lock-closed', __('You do not have access to this content')],
        'no-results' => ['magnifying-glass', __('No result matches your search')],
        'error' => ['exclamation-triangle', __('This content could not be loaded')],
        default => throw new InvalidArgumentException("Unknown ManeUI empty state kind [{$kind}]."),
    };
@endphp

<div {{ $attributes->merge(['data-kind' => $kind])->class('flex flex-col items-center gap-3 px-6 py-10 text-center') }}>
    <span class="flex size-12 items-center justify-center rounded-full bg-surface-sunken text-fg-muted">
        <x-mane::icon :name="$icon" class="size-6" />
    </span>

    <p class="text-base font-semibold text-fg">{{ $title ?? $defaultTitle }}</p>

    @if ($description)
        <p class="max-w-md text-sm text-fg-muted">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
