{{-- Auth variant: the form sits inside a bordered card. --}}
<x-shell main-class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
        <div class="rounded-xl border border-dark-200 bg-white px-10 py-8 shadow-xs dark:border-dark-700 dark:bg-dark-800/40">
            {{ $slot }}
        </div>
    </div>
</x-shell>
