@props([
    'showName' => true,
])

<a
    {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}
    @if (! $showName) aria-label="{{ config('app.name', 'Laravel') }}" @endif
>
    <img
        src="/logo-light.svg"
        alt=""
        width="32"
        height="32"
        class="size-8 dark:hidden"
    />

    <img
        src="/logo-dark.svg"
        alt=""
        width="32"
        height="32"
        class="hidden size-8 dark:block"
    />

    @if ($showName)
        <span class="truncate text-sm font-medium text-fg">
            {{ config('app.name', 'Laravel') }}
        </span>
    @endif
</a>