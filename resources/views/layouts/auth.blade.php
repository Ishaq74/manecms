{{-- Auth forms: no sidebar, single narrow centered column. --}}
<x-shell main-class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-sm">
        {{ $slot }}
    </div>
</x-shell>
