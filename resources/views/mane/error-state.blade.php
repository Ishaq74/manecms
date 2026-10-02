{{-- §466: an error says what happened, what to do next, and gives a reference for support. --}}
@props([
    'title',
    'description' => null,
    'reference' => null,
])

<div {{ $attributes->merge(['role' => 'alert'])->class('flex flex-col items-center gap-3 rounded-surface border border-line px-6 py-10 text-center') }}>
    <span class="flex size-12 items-center justify-center rounded-full bg-surface-sunken text-danger">
        <x-mane::icon name="exclamation-triangle" class="size-6" />
    </span>

    <p class="text-base font-semibold text-fg">{{ $title }}</p>

    @if ($description)
        <p class="max-w-md text-sm text-fg-muted">{{ $description }}</p>
    @endif

    @if ($reference)
        <p class="font-mono text-xs text-fg-muted">{{ __('Reference: :reference', ['reference' => $reference]) }}</p>
    @endif

    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
