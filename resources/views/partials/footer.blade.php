<footer class="border-t border-dark-200 dark:border-dark-700">
    <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <p class="text-sm text-dark-500 dark:text-dark-400">
            &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}
        </p>

        <nav aria-label="{{ __('Footer navigation') }}" class="flex items-center gap-4">
            <x-link sm navigate colorless class="text-dark-600 transition-colors hover:text-dark-900 dark:text-dark-400 dark:hover:text-white" :href="route('home')" :text="__('Home')" />
        </nav>
    </div>
</footer>