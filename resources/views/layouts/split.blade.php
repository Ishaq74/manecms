{{-- Auth variant: branded panel on the left, form on the right. --}}
<x-shell main-class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="grid w-full max-w-4xl items-center gap-12 lg:grid-cols-2">
        <div class="hidden lg:block">
            <h2 class="text-2xl font-semibold tracking-tight text-dark-900 dark:text-white">
                {{ config('app.name', 'Laravel') }}
            </h2>

            @php
                [$message, $author] = str(Illuminate\Foundation\Inspiring::quotes()->random())->explode('-');
            @endphp

            <blockquote class="mt-4 space-y-2">
                <p class="text-lg font-medium text-dark-700 dark:text-dark-200">
                    &ldquo;{{ trim($message) }}&rdquo;
                </p>

                <footer class="text-sm text-dark-500 dark:text-dark-400">
                    {{ trim($author) }}
                </footer>
            </blockquote>
        </div>

        <div class="w-full max-w-sm">
            {{ $slot }}
        </div>
    </div>
</x-shell>