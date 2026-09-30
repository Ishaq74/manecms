@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="text-xl font-semibold tracking-tight text-zinc-800 dark:text-white">
        {{ $title }}
    </h1>

    <p class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ $description }}
    </p>
</div>
