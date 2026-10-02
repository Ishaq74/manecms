{{-- Marketing pages: full-bleed sections, each one sets its own container. --}}
@props([
    'title' => null,
])

<x-shell :title="$title" main-class="flex-1">
    {{ $slot }}
</x-shell>
