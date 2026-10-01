<footer class="border-t border-zinc-200 dark:border-zinc-700">
    <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}
        </p>

        <nav aria-label="{{ __('Footer navigation') }}" class="flex items-center gap-4">
            <a
                href="{{ route('home') }}"
                wire:navigate
                class="text-sm text-zinc-500 transition-colors hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white"
            >
                {{ __('Home') }}
            </a>

            @auth
                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    class="text-sm text-zinc-500 transition-colors hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white"
                >
                    {{ __('Dashboard') }}
                </a>
            @endauth
        </nav>
    </div>
</footer>