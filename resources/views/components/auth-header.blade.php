@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="text-xl font-semibold tracking-tight text-dark-800 dark:text-white">
        {{ $title }}
    </h1>

    <p class="text-sm text-dark-500 dark:text-dark-400">
        {{ $description }}
    </p>
</div>
