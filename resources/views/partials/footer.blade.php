<footer class="border-t border-line">
    <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <p class="text-sm text-fg-muted">
            &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}
        </p>

        <nav aria-label="{{ __('Footer navigation') }}" class="flex items-center gap-4">
            <x-mane::link size="sm" navigate colorless class="text-fg-muted transition-colors hover:text-fg" :href="route('home')" :text="__('Home')" />
        </nav>
    </div>
</footer>