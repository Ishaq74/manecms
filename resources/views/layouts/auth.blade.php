{{-- Authentication pages: the logo, one ManeUI card with the page heading and an optional status, then a footer line. --}}
@props([
    'title' => null,
    'heading' => null,
    'description' => null,
    'status' => null,
])

<x-shell :title="$title" main-class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="flex w-full max-w-md flex-col gap-6">
        <x-app-logo :href="route('home')" class="self-center" wire:navigate />

        <x-mane::card>
            <div class="flex flex-col gap-6">
                @if ($heading)
                    <x-mane::page-header align="center" :title="$heading" :description="$description" />
                @endif

                @if ($status)
                    <x-mane::alert tone="success" :text="$status" data-test="auth-status" />
                @endif

                {{ $slot }}
            </div>
        </x-mane::card>

        @isset($footer)
            <p class="text-center text-sm text-fg-muted">{{ $footer }}</p>
        @endisset
    </div>
</x-shell>
