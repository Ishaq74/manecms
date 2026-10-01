@props([
    'showName' => true,
])

<a {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <span class="flex aspect-square size-8 items-center justify-center rounded-md bg-primary-600 text-white">
        <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
    </span>

    @if ($showName)
        <span class="truncate text-sm font-medium text-dark-800 dark:text-white">
            {{ config('app.name', 'Laravel') }}
        </span>
    @endif
</a>
