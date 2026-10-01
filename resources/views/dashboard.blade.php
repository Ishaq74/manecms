<x-layouts::sidebar :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-8">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight text-dark-900 dark:text-white">
                {{ __('Dashboard') }}
            </h1>

            <p class="text-sm text-dark-500 dark:text-dark-400">
                {{ __('Welcome back, :name', ['name' => \Illuminate\Support\Str::of(auth()->user()->name)->before(' ')]) }}
            </p>
        </div>

        <div class="flex h-full w-full flex-1 flex-col gap-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-dark-200 dark:border-dark-700">
                    <x-placeholder-pattern class="absolute inset-0 size-full stroke-dark-900/20 dark:stroke-dark-100/20" />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-dark-200 dark:border-dark-700">
                    <x-placeholder-pattern class="absolute inset-0 size-full stroke-dark-900/20 dark:stroke-dark-100/20" />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-dark-200 dark:border-dark-700">
                    <x-placeholder-pattern class="absolute inset-0 size-full stroke-dark-900/20 dark:stroke-dark-100/20" />
                </div>
            </div>
            <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-dark-200 dark:border-dark-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-dark-900/20 dark:stroke-dark-100/20" />
            </div>
        </div>
    </div>
</x-layouts::sidebar>